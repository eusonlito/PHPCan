<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

namespace PHPCan;

defined('ANS') or die();

class Content
{
    private $Debug;
    private $Errors;

    public $Db;
    public $settings = array();

    private $format_errors;
    private $connection;

    /**
     * public function __construct ([string $autoglobal])
     *
     * return none
     */
    public function __construct ($autoglobal = '')
    {
        global $Debug, $Errors, $Config, $Db;

        $this->Debug = $Debug;
        $this->Errors = $Errors;
        $this->settings['tables'] = $Config->content_tables;
        $this->settings['relations'] = $Config->content_relations;
        $this->settings['add_tables'] = $Config->content_add_tables;
        $this->settings['views'] = $Config->content_views;
        $this->settings['languages'] = array_keys((array) $Config->scene_languages['availables']);

        $this->Db = $Db;

        $this->setLanguage();

        if ($autoglobal) {
            $Config->config['autoglobal'][] = $autoglobal;
        }
    }

    /**
     * public function view ($connection, $table, $id)
     *
     */
    public function view ($connection, $table, $id)
    {
        $function = $this->settings['views'][$connection][$table];

        if (is_callable($function) || (is_string($function) && function_exists($function)) || (is_array($function) && is_object($function[0]) && method_exists($function[0], $function[1]))) {
            return call_user_func($function, $this->Db, $table, $id);
        } else {
            global $Vars;
            $Vars->message('The view function for the table %s is not callable', $table);
        }
    }

    /**
     * public function setLanguage ([$language])
     *
     */
    public function setLanguage ($language = '')
    {
        global $Vars;

        if ($language == 'all') {
            $this->settings['language'] = 'all';
            $Vars->deleteCookie('phpcan_content_data_language');
        } elseif ($language && in_array($language, $this->settings['languages'])) {
            $this->settings['language'] = $language;
            $Vars->setCookie('phpcan_content_data_language', $language, 3600*24);
        } elseif ($language = $Vars->getCookie('phpcan_content_data_language')) {
            $this->settings['language'] = $language;
        } else {
            $this->settings['language'] = 'all';
        }

        return true;
    }

    /**
     * public function getLanguages ()
     *
     */
    public function getLanguages ()
    {
        $languages = $this->settings['languages'];
        array_unshift($languages, 'all');

        return $languages;
    }

    /**
     * public function getLanguage ()
     *
     */
    public function getLanguage ()
    {
        return $this->settings['language'];
    }

    /**
     * public function setConnection (string $connection)
     *
     * return boolean
     */
    public function setConnection ($connection)
    {
        $this->Db->setDatabase(getDatabaseObject($connection), $this->settings['languages'], $this->settings['tables'][$connection], $this->settings['relations'][$connection]);
        $this->Db->language($this->settings['language']);

        $this->connection = $connection;

        return $this->Db->getDatabase() ? true : false;
    }

    /**
     * public function checkTable ($table)
     *
     * return boolean
     */
    public function checkTable ($table)
    {
        return $this->Db->tableExists($table);
    }

    /**
    * public function checkSelectedFields (array $fields, [array/string $selected_tables])
    *
    * return boolean
    */
    public function checkSelectedFields ($selected_fields, $selected_tables = array())
    {
        if (!is_array($selected_fields)) {
            $selected_fields = array($selected_fields);
        }

        $tables = $this->settings['tables'][$this->connection];

        if (!$tables) {
            return array();
        } elseif (!$selected_tables) {
            $selected_tables = array_keys($tables);
        } elseif (!is_array($selected_tables)) {
            $selected_tables = array($selected_tables);
        }

        $valid_fields = array();

        foreach ($tables as $tables_values) {
            foreach ($selected_tables as $tables_key) {
                if (!$tables[$tables_key]) {
                    continue;
                }

                $valid_fields[$tables_key] = array();

                $fields = array_keys($tables[$tables_key]);

                if (!$selected_fields[$tables_key] || !is_array($selected_fields[$tables_key])) {
                    $valid_fields[$tables_key] = $fields;
                } else {
                    $valid_fields[$tables_key] = array_intersect($selected_fields[$tables_key], $fields);
                }

                $valid_fields[$tables_key][] = 'id';
            }
        }

        return $valid_fields;
    }

    /**
    * public function info (string $connection, string $type, string $table, [string $field])
    *
    * return string
    */
    public function info ($connection, $type, $table, $field = '')
    {
        if (!$field) {
            $key = $connection.'-'.$table.'-'.$type;
            $string = __($key, null, true);

            return is_null($string) ? (($type == 'name') ? format($table) : '') : $string;
        }

        $key = $connection.'-'.$table.'-'.$field.'-'.$type;
        $string = __($connection.'-'.$table.'-'.$field.'-'.$type, null, true);

        return is_null($string) ? (($type == 'name') ? format($field) : '') : $string;
    }

    /**
     * public function selectMenuTables ()
     *
     * return array
     */
    public function selectMenuTables ()
    {
        $menu_tables = array();

        if (!$this->settings['tables']) {
            return $menu_tables;
        }

        foreach ($this->settings['tables'] as $connection => $tables) {
            $tables = array_keys($tables);
            sort($tables);

            foreach ($tables as $table) {
                $menu_tables[] = array(
                    'name' => $this->info($connection, 'name', $table),
                    'description' => $this->info($connection, 'description', $table),
                    'table' => $table,
                    'connection' => $connection,
                    'url' => path($connection, $table, 'list')
                );
            }
        }

        return $menu_tables;
    }

    /**
     * private function getQuery (array $options, [boolean $add_tables])
     *
     * return array
     */
    private function getQuery ($options, $add_tables = false)
    {
        $query = array(
            'table' => $options['table'],
            'fields' => $options['fields'] ? $options['fields'] : '*',
            'sort' => $options['sort'] ? $options['sort'] : 'id',
            'sort_direction' => $options['sort_direction'] ? $options['sort_direction'] : 'DESC'
        );

        if ($add_tables) {
            $add_tables = $this->settings['add_tables'][$this->connection][$options['table']];

            if ($add_tables) {
                $query['add_tables'] = $add_tables;
            }
        }

        if ($options['search']) {
            $Search = new \PHPCan\Data\Search($this->Db);

            $query_search = $Search->getQuery(array(
                'text' => $options['search'],
                'operators' => 'all',
                'where' => array(
                    'table' => $options['table'],
                    'fields' => '*'
                )
            ));

            $query = $this->Db->mergeQuery($query_search, $query);
        }

        if ($options['limit']) {
            $query['limit'] = intval($options['limit']);
        }

        return $query;
    }

    /**
     * public function selectEdit (array $options)
     *
     * return array
     */
    public function selectEdit ($options)
    {
        $query = $this->getQuery($options);

        $query['conditions']['id'] = $options['ids'];
        $query['limit'] = count($options['ids']);
        $query['rows'] = true;
        $query['exit_format'] = 'form';

        $result = $this->Db->select($query);

        if (!$result) {
            return false;
        }

        $result = $this->queryToArray($query, $result, false, 'edit');
        $result = $this->explodeResult($options['table'], $result[$options['table']], $this->format_errors);
        $result = $this->getFormResult($result, true);

        if ($options['relation'] && $options['relation_id']) {
            $relation_vars = array(
                $result[0]['varname'].'[tables]['.$options['relation'].'][0][id]' => $options['relation_id'],
                $result[0]['varname'].'[tables]['.$options['relation'].'][0][action]' => 'relate'
            );

            foreach ($result as &$row) {
                $row['vars'] = $relation_vars;
            }
        }

        return $result;
    }

    /**
     * public function selectNew (array $options)
     *
     * return array
     */
    public function selectNew ($options)
    {
        $query = $this->getQuery($options);

        $result = $this->queryToArray($query, array(), true, 'edit');
        $result = $this->explodeResult($options['table'], $result[$options['table']], $this->format_errors);
        $result = $this->getFormResult($result);

        if ($options['relation'] && $options['relation_id']) {
            $relation_vars = array(
                $result[0]['varname'].'[tables]['.$options['relation'].'][0][id]' => $options['relation_id'],
                $result[0]['varname'].'[tables]['.$options['relation'].'][0][action]' => 'relate'
            );

            foreach ($result as &$row) {
                $row['vars'] = $relation_vars;
            }
        }

        return $result;
    }

    /**
     * public function selectList (array $options)
     *
     * return array
     */
    public function selectList ($options)
    {
        $query = $this->getQuery($options, true);

        if ($query['limit'] === -1) {
            unset($query['limit']);
        } else {
            $query['limit'] = $query['limit'] ?: 20;

            $query['pagination'] = array(
                'map' => 20,
                'page' => $options['page']
            );
        }

        $result = $this->Db->select($query);

        $processed_result = $this->queryToArray($query, $result, true, 'edit');
        $processed_result[$options['table']] = $this->explodeResult($options['table'], $processed_result[$options['table']]);

        $list = array(
            'head' => $this->getHeadResult($processed_result[$options['table']]),
            'body' => $result ? $this->getBodyResult($processed_result[$options['table']]) : array()
        );

        return $list;
    }

    /**
     * public function selectRelations (array $options)
     *
     * return array
     */
    public function selectRelations ($options)
    {
        $query = $this->getQuery($options, true);

        $query['limit'] = 20;

        $query['pagination'] = array(
            'map' => 20,
            'page' => $options['page']
        );

        if (!$options['all']) {
            $query['conditions'][$options['relation'].'.id'] = $options['relation_id'];
        }

        $options['relation'] = $this->Db->tableArray($options['relation']);

        $query['add_tables']['phpcan_related'] = array(
            'table' => $options['relation']['realname'],
            'fields' => 'id',
            'name' => $options['relation']['name'],
            'direction' => $options['relation']['direction'],
            'conditions' => array(
                'id' => $options['relation_id']
            ),
            'limit' => 1
        );

        $result = $this->Db->select($query);

        $relations = array();

        foreach ($result as $num_row => $row) {
            $relations[$num_row] = $row['phpcan_related'] ? true : false;
            unset($result[$num_row]['phpcan_related']);
        }

        unset($query['add_tables']['phpcan_related']);

        $processed_result = $this->queryToArray($query, $result, true, 'edit');
        $processed_result[$options['table']] = $this->explodeResult($options['table'], $processed_result[$options['table']]);

        $list = array(
            'head' => $this->getHeadResult($processed_result[$options['table']]),
            'body' => $result ? $this->getBodyResult($processed_result[$options['table']]) : array()
        );

        foreach ($relations as $num_row => $row) {
            $list['body'][$num_row]['related'] = $row;
        }

        return $list;
    }

    /**
     * public function saveEdit (array $array, $overwrite_control)
     *
     * return bool/int
     */
    public function saveEdit ($array, $overwrite_control)
    {
        $query = $this->arrayToQuery($array, $overwrite_control);

        $this->format_errors = array();

        $result = $this->Db->save($query, true);

        $this->format_errors = $this->Errors->get();

        return $result;
    }

    /**
     * public function delete (array $config)
     *
     * return bool/int
     */
    public function delete ($config)
    {
        $query = array(
            'table' => $config['table'],
            'conditions' => array(
                'id' => $config['id']
            ),
            'limit' => count($config['id'])
        );

        return $this->Db->delete($query);
    }

    /**
     * public function saveRelation (array $config)
     *
     * return bool/int
     */
    public function saveRelation ($config)
    {
        $relation = $this->Db->tableArray($config['relation']);
        $table = $this->Db->getTable($config['table']);

        $relation_object = $table->getRelation($relation['realname'], $relation['name'], $relation['direction']);

        if (!$relation_object) {
            $this->Debug->error('db', 'There is not relation between %s and %s', $config['table'], $config['relation']);

            return false;
        }

        $query = array(
            'name' => $relation_object->settings['name'],
            'tables' => array(
                array(
                    'table' => $relation_object->settings['tables'][0],
                    'direction' => $relation_object->settings['direction'][0],
                    'conditions' => array(
                        'id' => $config['id']
                    )
                ),
                array(
                    'table' => $relation_object->settings['tables'][1],
                    'direction' => $relation_object->settings['direction'][1],
                    'conditions' => array(
                        'id' => $config['relation_id']
                    )
                )
            )
        );

        if ($config['action'] == 'relate') {
            return $this->Db->relate($query);
        }

        return $this->Db->unrelate($query);
    }

    /**
     * private function getFormResult (array $result, [bool $all_relations])
     *
     * return array
     */
    private function getFormResult ($result, $all_relations = false)
    {
        global $Templates;

        $table = $this->Db->getTable($result[0]['table']);

        $relations = $all_relations ? $table->getRelations() : array();

        $format_templates = $this->getFormatTemplates($result[0]['data'], 'edit');

        foreach ($result as $num_row => &$row) {
            foreach ($row['data'] as &$field) {
                $field = array(
                    'templates' => $format_templates[$field['format']],
                    'data' => $field
                );
            }

            foreach ($relations as $relation_name => $relation) {
                if ($row['tables'][$relation_name] || $relation->settings['auto']) {
                    continue;
                }

                $row['data']['relation '.$relation_name] = array(
                    'templates' => array(
                        'index' => 'relations/default/index.php',
                        'content' => 'relations/default/content.php'
                    ),
                    'data' => array(
                        'table' => $relation->settings['tables'][1],
                        'name' => $relation->settings['name'],
                        'varname' => $row['varname'].'[tables]['.$this->Db->tableString($relation->settings['tables'][0], '', $relation->settings['name'], $relation->settings['direction'][0]).']',
                        'direction' => $relation->settings['direction'][1],
                        'relation' => $this->Db->tableString($relation->settings['tables'][0], '', $relation->settings['name'], $relation->settings['direction'][1]),
                        'unique' => $relation->unique ? false : true,
                        'id' => $row['id'],
                        'title' => format($relation_name),
                        'description' => __('%s %s related with %s %s #%s', $relation->settings['tables'][1], $relation->settings['direction'][0], $relation->settings['tables'][0], $relation->settings['name'], $row['id'])
                    )
                );
            }

            foreach ((array) $row['tables'] as $added_table => $added_table_result) {
                $row['tables'][$added_table] = $this->getFormResult($added_table_result, $all_relations);
            }
        }

        return $result;
    }

    /**
     * private function getHeadResult (array $result)
     *
     * return array
     */
    private function getHeadResult ($result)
    {
        $result = current($result);

        $head = array(array(
            'table' => $result['table'],
            'name' => $result['name'],
            'direction' => $result['direction'],
            'title' => format($result['table'].'-'.$result['name'], $result['direction']),
            'cols' => count($result['data'])
        ));

        foreach ($result['data'] as $name => $info) {
            $head[0]['data'][] = array(
                'format' => $info['format'],
                'field' => $info['field'],
                'title' => $info['title'],
                'description' => $info['description']
            );
        }

        foreach ((array) $result['tables'] as $added_table => $added_table_result) {
            $head = array_merge($head, $this->getHeadResult($added_table_result));
        }

        return $head;
    }

    /**
     * private function getFormatTemplates (array $result, string $template)
     *
     * return array
     */
    private function getFormatTemplates ($result, $template)
    {
        global $Templates;

        $format_templates = array();

        foreach ($result as $info) {
            if ($format_templates[$info['format']]) {
                continue;
            }

            $format_templates[$info['format']] = array(
                'index' => $Templates->exists('formats/'.$info['format'].'/'.$template.'/index.php') ? 'formats/'.$info['format'].'/'.$template.'/index.php' : 'formats/default/'.$template.'/index.php',
                'content' => $Templates->exists('formats/'.$info['format'].'/'.$template.'/content.php') ? 'formats/'.$info['format'].'/'.$template.'/content.php' : 'formats/default/'.$template.'/content.php'
            );
        }

        return $format_templates;
    }

    /**
     * private function getBodyResult (array $result)
     *
     * return array
     */
    private function getBodyResult ($result)
    {
        $body = array();

        $format_templates = $this->getFormatTemplates($result[0]['data'], 'list');

        foreach ($result as $num_row => $row) {
            $body[$num_row] = array(
                'table' => $row['table'],
                'id' => $row['id'],
                'rows' => $row['rows'],
                'view' => $row['view']
            );

            foreach ($row['data'] as $name => $info) {
                $info['templates'] = $format_templates[$info['format']];

                $body[$num_row]['data'][$name][0] = $info;
            }

            foreach ((array) $row['tables'] as $added_table => $added_table_result) {
                $body[$num_row]['data'] += $this->_getBodyResult($added_table_result);
            }
        }

        return $body;
    }

    /**
     * private function _getBodyResult (array $result)
     *
     * return array
     */
    private function _getBodyResult ($result)
    {
        $result_body = array();

        $format_templates = $this->getFormatTemplates($result[0]['data'], 'list');

        foreach ($result as $num_row => $row) {
            foreach ($row['data'] as $name => $info) {
                $info['templates'] = $format_templates[$info['format']];

                $result_body[$name][$num_row] = $info;
            }

            foreach ((array) $row['tables'] as $added_table => $added_table_result) {
                $result_body[$num_row] = array_merge($result_body[$num_row], $this->_getBodyResult($added_table_result));
            }
        }

        return $result_body;
    }

    /**
     * private function explodeResult (string $table, array $result, [array $errors])
     *
     * return array
     */
    private function explodeResult ($table, $result, $errors = array())
    {
        $rows = 1;

        foreach ($result as $num_row => &$row) {
            $errors_row = $errors[$row['table']][$num_row];

            $row['view'] = ($this->settings['views'][$this->connection][$table]) ? true : false;

            foreach ($row['tables'] as $added_table => &$added_table_result) {
                $added_table_result = $this->explodeResult($added_table, $added_table_result, $errors[$row['table']][$added_table]);

                $count = count($added_table_result);

                if ($count > $rows) {
                    $rows = $count;
                }
            }

            $result_data = array();

            foreach ($row['data'] as $format_name => $format_data) {
                foreach ($format_data['fields'] as $language => $fields) {
                    $result_data[$table.' '.$format_name.' '.$language] = array(
                        'table' => $row['table'],
                        'id' => $row['id'],
                        'format' => $format_data['format'],
                        'field' => $format_name,
                        'title' => format($this->info($this->connection, 'name', $row['table'], $format_name), $language),
                        'description' => $this->info($this->connection, 'description', $row['table'], $format_name),
                        'language' => $language,
                        'varname' => $format_data['varname'].'['.($language ? $language : 0).']',
                        'rows' => $rows,
                        'data' => $fields,
                        'error' => $errors_row[$format_name][$language],
                        'view' => $row['view']
                    );
                }
            }

            $row['data'] = $result_data;
            $row['rows'] = $rows;
        }

        return $result;
    }

    /**
     * private function queryToArray (array $query, array $result, [boolean $empty_values], [string $varname])
     *
     * Group the result of a select query by table, format, language and fields
     *
     * return array
     */
    private function queryToArray ($query, $result, $empty_values = false, $varname = '')
    {
        $table = $this->Db->getTable($query['table']);

        $group_table_name = $this->Db->tableString($query['table'], $query['name'], $query['direction']);
        $group_result = array();
        $tmp_group_result = array();

        $default_values = $table->getDefaultValues($query['fields']);
        $fields = array_merge(array('id'), array_keys($default_values));

        if ($empty_values && !$result) {
            $result = array($default_values);
            $result = $table->valueForm($result);
        }

        if (!isNumericalArray($result)) {
            $result = array($result);
        }

        foreach ($fields as $field) {
            foreach ($result as $num_row => $row) {
                $tmp_group_result[$num_row][$field] = $row[$field];
            }
        }

        $tmp_group_result = $table->explodeData($tmp_group_result, $this->settings['language']);

        foreach ($tmp_group_result as $num_row => $row) {
            $row_varname = $varname.'['.$group_table_name.']['.$num_row.']';

            //Row
            $group_result[$num_row] = array(
                'id' => $row['id'][''][''],
                'table' => $query['table'],
                'name' => $query['name'],
                'direction' => $query['direction'],
                'tables' => array(),
                'data' => array(),
                'varname' => $row_varname
            );

            $group_result[$num_row]['action'] = $group_result[$num_row]['id'] ? 'update' : 'insert';

            //Data
            foreach ($row as $format_name => $fields) {
                $format = $table->getFormat($format_name);

                $group_result[$num_row]['data'][$format_name] = array(
                    'varname' => $row_varname.'[data]['.$format_name.']',
                    'format' => $format->format,
                    'fields' => $fields
                );
            }

            //Added tables
            foreach ((array) $query['add_tables'] as $added_name => $added_table) {
                $added_name = $this->Db->addedName($added_name, $added_table['table'], $added_table['name'], $added_table['direction']);

                if (!$empty_values && !$result[$num_row][$added_name]) {
                    continue;
                }

                $group_result[$num_row]['tables'] += $this->queryToArray($added_table, $result[$num_row][$added_name], $empty_values, $row_varname.'[tables]');
            }
        }

        return array($group_table_name => $group_result);
    }

    /**
     * private function arrayToQuery (array $result, array $overwrite_control)
     *
     * return array
     */
    private function arrayToQuery ($result, $overwrite_control)
    {
        $query = array();

        foreach ($result as $table => $rows) {
            $table_info = $this->Db->tableArray($table);
            $table = $this->Db->getTable($table_info['realname']);

            foreach ($rows as $num => $row) {
                $action = $row['action'];

                if (!$action) {
                    continue;
                }

                $row['data'] = current($table->implodeData(array($row['data'])));

                if ($overwrite_control[$table_info['realname']][$num]['data']) {
                    $row['overwrite_control'] = current($table->implodeData(array($overwrite_control[$table_info['realname']][$num]['data'])));
                } else {
                    $row['overwrite_control'] = array();
                }

                $options = array(
                    'data' => $row['data'],
                    'table' => $table_info['realname'],
                    'name' => $table_info['name'],
                    'direction' => $table_info['directon'],
                    'overwrite_control' => $row['overwrite_control']
                );

                switch ($action) {
                    case 'relate':
                    case 'unrelate':
                        $options['conditions']['id'] = $row['id'];
                        $options['no_duplicate'] = true;

                        if (!is_array($row['id']) || count($row['id']) == 1) {
                            $options['limit'] = 1;
                        }

                        break;

                    case 'update':
                    case 'delete':
                        $options['conditions']['id'] = $row['data']['id'];
                        unset($options['data']['id']);

                        if (!is_array($row['id']) || count($row['id']) == 1) {
                            $options['limit'] = 1;
                        }

                        break;
                }

                if ($row['tables']) {
                    $options += $this->arrayToQuery($row['tables'], $row['overwrite_control']);
                }

                $query[$action][] = $options;
            }
        }

        return $query;
    }
}
