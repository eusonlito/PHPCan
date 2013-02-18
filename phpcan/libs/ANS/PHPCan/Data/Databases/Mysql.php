<?php
/*
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

namespace ANS\PHPCan\Data\Databases;

defined('ANS') or die();

class Mysql implements Idatabase
{
    private $Debug;
    private $Db;
    private $settings = array();

    /**
     * public function __construct (object $Db)
     *
     * return none
     */
    public function __construct ($Db)
    {
        global $Debug;

        $this->Debug = $Debug;
        $this->Db = $Db;
    }

    /**
    * public function getDSN (array $settings)
    *
    * return the DSN string
    *
    * return string
    */
    public function getDSN ($settings)
    {
        $dsn = 'mysql:host='.$settings['host'];

        if ($settings['port']) {
            $dsn .= ';port='.$settings['port'];
        } else if ($settings['unix_socket']) {
            $dsn .= ';unix_socket='.$settings['unix_socket'];
        }

        $dsn .= ';dbname='.$settings['database'];

        if ($settings['charset']) {
            $dsn .= ';charset='.$settings['charset'];
        }

        return $dsn;
    }

    /**
     * public function getSchemaDifferences ($tables)
     *
     * Check differences between config tables definitions
     * and the actual database schema
     *
     * return array
     */
    public function getSchemaDifferences ($tables)
    {
        if (!is_object($this->Db)) {
            return false;
        }

        //Get real table definitions
        $real_tables = array();
        $real_indexes = array();

        $tmp_tables = $this->Db->queryResult('SHOW TABLES;');

        foreach ($tmp_tables as $table) {
            $table = current($table);

            //Get indexes
            $indexes = $this->Db->queryResult('SHOW INDEX FROM `'.$table.'`;');

            foreach ($indexes as $index) {
                if ($index['Non_unique']) {
                    $type = ($index['Index_type'] === 'FULLTEXT') ? 'fulltext' : 'index';
                } else if ($index['Key_name'] === 'PRIMARY') {
                    $type = 'key';
                } else {
                    $type = 'unique';
                }

                $real_indexes[$table][$index['Column_name']][$type] = $index['Key_name'];
            }

            //Get fields
            $describe = $this->Db->queryResult('DESCRIBE `'.$table.'`;');

            foreach ($describe as $fields) {
                $type = $fields['Type'];
                $field = $fields['Field'];

                preg_match('#^(\w+)(\((.+)\))?( unsigned)?$#', $type, $t);

                if (!strstr($t[3], "'")) { //For enum values
                    if (strstr($t[3], ',')) {
                        list($integers, $decimals) = explode(',', $t[3]);

                        $t[3] = intval($integers).','.intval($decimals);
                    } else {
                        $t[3] = intval($t[3]);
                    }
                }

                $real_tables[$table][$field] = array(
                    'type' => ($t[1] === 'int') ? 'integer' : $t[1],
                    'length_max' => $t[3],
                    'unsigned' => $t[4] ? true : false,
                    'null' => ($fields['Null'] === 'NO') ? false : true,
                    'incremental' => strstr($fields['Extra'], 'auto_increment') ? true : false,
                    'default' => $fields['Default'],
                );

                unset($t);
            }
        }

        //Get virtual table definitions
        $virtual_tables = array();
        $virtual_indexes = array();
        $renamed_fields = array();

        foreach ($tables as $table => $table_object) {
            foreach ($table_object->formats as $format) {
                foreach ($format->getFields() as $field => $info) {
                    $settings = $format->settings[$info['subformat']];

                    switch ($settings['db_type']) {
                        case 'boolean':
                        case 'bool':
                            $settings['db_type'] = 'tinyint';
                            $settings['db_length_max'] = 1;

                            break;
                    }

                    //Get indexes
                    $virtual_indexes[$table][$field] = array(
                        'type' => $settings['db_type'],
                        'key' => $settings['db_key'] ? 'PRIMARY' : '',
                        'index' => $settings['db_index'],
                        'unique' => $settings['db_unique'],
                        'fulltext' => $settings['db_fulltext'],
                        'length' => $settings['db_length_max']
                    );

                    if (in_array($settings['db_type'], array('set', 'enum'), true)) {
                        if (!is_array($settings['db_values'])) {
                            $settings['db_values'] = explode(',', $settings['db_values']);
                        }

                        $settings['db_length_max'] = "'".implode("','", $settings['db_values'])."'";
                    } else {
                        if (strstr($settings['db_length_max'], ',')) {
                            list($integers, $decimals) = explode(',', $settings['db_length_max']);

                            $settings['db_length_max'] = intval($integers).','.intval($decimals);
                        } else {
                            $settings['db_length_max'] = intval($settings['db_length_max']);
                        }
                    }

                    //Get fields
                    $virtual_tables[$table][$field] = array(
                        'type' => $settings['db_type'],
                        'length_max' => $settings['db_length_max'],
                        'unsigned' => $settings['db_unsigned'],
                        'null' => $settings['db_null'],
                        'incremental' => $settings['db_incremental'],
                        'default' => $settings['db_default'],
                    );
                }
            }
        }

        $query = array();

        $all_tables = array_merge(array_keys($real_tables), array_keys($virtual_tables));
        $all_tables = array_unique($all_tables);

        //Compare real_tables and virtual_tables fields and create the query
        foreach ($all_tables as $table) {
            //Add table
            if (empty($real_tables[$table])) {
                $query_fields = array();

                //Insert fields and keys
                foreach ($virtual_tables[$table] as $field => $settings) {
                    $query_fields[] =	'`'.$field.'` '.$this->fieldSchema($settings);
                }

                $query[] = 'CREATE TABLE `'.$table.'` ('.implode(', ', $query_fields).') ENGINE=MyISAM DEFAULT CHARSET=utf8;';

                continue;
            }

            //Drop table
            if (empty($virtual_tables[$table])) {
                $query[] = 'DROP TABLE `'.$table.'`;';

                continue;
            }

            $all_fields = array_merge(array_keys($virtual_tables[$table]), array_keys($real_tables[$table]));
            $all_fields = array_unique($all_fields);

            foreach ($all_fields as $field) {
                //Add field
                if (empty($real_tables[$table][$field])) {
                    $query[] = 'ALTER TABLE `'.$table.'` ADD `'.$field.'` '.$this->fieldSchema($virtual_tables[$table][$field]).';';

                    continue;
                }

                //Drop field
                if (empty($virtual_tables[$table][$field])) {
                    $query[] = 'ALTER TABLE `'.$table.'` DROP `'.$field.'`;';

                    continue;
                }

                //Modify field
                $diff = array_diff_assoc($virtual_tables[$table][$field], $real_tables[$table][$field]);

                if ($diff) {
                    $query[] = 'ALTER TABLE `'.$table.'` MODIFY `'.$field.'` '.$this->fieldSchema($virtual_tables[$table][$field]).';';
                }
            }
        }

        //Compare real_tables and virtual_tables indexes and create the query
        foreach ($virtual_indexes as $table => $fields) {
            $table_keys = array();

            foreach ($fields as $field => $key) {
                if (($key['key'] != $real_indexes[$table][$field]['key']) && ($field !== 'id')) {
                    $table_keys['PRIMARY']['action'] = $key['key'] ? 'ADD PRIMARY KEY' : 'DROP PRIMARY KEY';
                    $table_keys['PRIMARY']['name'] = '';
                    $table_keys['PRIMARY']['field'][] = array(
                        'name' => $field,
                        'type' => $key['type'],
                        'length' => $key['length']
                    );
                }

                if ($key['unique'] != $real_indexes[$table][$field]['unique']) {
                    if ($real_indexes[$table][$field]['unique']) {
                        $table_keys['drop-'.$real_indexes[$table][$field]['unique']] = array(
                            'action' => 'DROP INDEX',
                            'name' => $real_indexes[$table][$field]['unique']
                        );
                    }

                    if ($key['unique']) {
                        if (empty($table_keys['add-'.$key['unique']])) {
                            $table_keys['add-'.$key['unique']] = array(
                                'action' => 'ADD UNIQUE',
                                'name' => $key['unique'],
                                'field' => array()
                            );
                        }

                        $table_keys['add-'.$key['unique']]['field'][] = array(
                            'name' => $field,
                            'type' => $key['type'],
                            'length' => $key['length']
                        );
                    }
                }

                if ($key['index'] !== $real_indexes[$table][$field]['index']) {
                    if ($real_indexes[$table][$field]['index']) {
                        $table_keys['drop-'.$real_indexes[$table][$field]['index']] = array(
                            'action' => 'DROP INDEX',
                            'name' => $real_indexes[$table][$field]['index']
                        );
                    }

                    if ($key['index']) {
                        if (empty($table_keys['add-'.$key['index']])) {
                            $table_keys['add-'.$key['index']] = array(
                                'action' => 'ADD INDEX',
                                'name' => $key['index'],
                                'field' => array()
                            );
                        }

                        $table_keys['add-'.$key['index']]['field'][] = array(
                            'name' => $field,
                            'type' => $key['type'],
                            'length' => $key['length']
                        );
                    }
                }

                if ($key['fulltext'] !== $real_indexes[$table][$field]['fulltext']) {
                    if ($real_indexes[$table][$field]['fulltext']) {
                        $table_keys['drop-'.$real_indexes[$table][$field]['fulltext']] = array(
                            'action' => 'DROP INDEX',
                            'name' => $real_indexes[$table][$field]['fulltext']
                        );
                    }

                    if ($key['fulltext']) {
                        if (empty($table_keys['add-'.$key['fulltext']])) {
                            $table_keys['add-'.$key['fulltext']] = array(
                                'action' => 'ADD FULLTEXT',
                                'name' => (is_string($key['fulltext']) ? $key['fulltext'] : $table),
                                'field' => array()
                            );
                        }

                        $table_keys['add-'.$key['fulltext']]['field'][] = array(
                            'name' => $field,
                            'type' => $key['type'],
                            'length' => $key['length']
                        );
                    }
                }
            }

            foreach ($table_keys as $indexes) {
                if (empty($indexes['field'])) {
                    $query[] = 'ALTER TABLE `'.$table.'` '.$indexes['action'].' `'.$indexes['name'].'`;';
                    continue;
                }

                // The length maximum for the index in UTF-8 char values is 333 bytes
                $length = intval(333 / count($indexes['field']));

                foreach ($indexes['field'] as $key => $field) {
                    if (in_array($field['type'], array('varchar','text'))) {
                        $indexes['field'][$key] = '`'.$field['name'].'` ('.(($length > $field['length']) ? $field['length'] : $length).')';
                    } else {
                        $indexes['field'][$key] = '`'.$field['name'].'`';
                    }
                }

                $query[] = 'ALTER TABLE `'.$table.'` '.$indexes['action'].' `'.$indexes['name'].'` ('.implode(',', $indexes['field']).');';
            }
        }

        return $query;
    }

    /**
     * function fieldSchema (array $field)
     *
     * Create each definition field line
     *
     * return string
     */
    private function fieldSchema ($field)
    {
        $query = $field['type'];

        if ($field['length_max']) {
            $query .= '('.$field['length_max'].')';
        }

        if ($field['unsigned']) {
            $query .= ' unsigned';
        }

        $query .= $field['null'] ? ' NULL' : ' NOT NULL';

        if ($field['incremental']) {
            $query .= ' AUTO_INCREMENT PRIMARY KEY';
        } else if (isset($field['default'])) {
            $query .= ' default "'.$field['default'].'"';
        }

        return $query;
    }

    /**
        * public function getRenameField (string $table, string $from, string $to, string $type)
        *
        * Create de ALTER TABLE MySQL query string to rename a field
        *
        * return boolean
        */
    public function renameField ($table, $from, $to, $type)
    {
        $schema = $this->fieldSchema(array(
            'type' => $type,
            'length_max' => (($type === 'int') ? 9 : (($type === 'varchar') ? 255 : null))
        ));

        return 'ALTER TABLE `'.$table.'` CHANGE `'.$from.'` `'.$to.'` '.$schema.';';
    }

    /**
     * private function setFields (array $fields, string $table)
     *
     * Create the array with select fields
     *
     * return array
     */
    private function setFields ($fields, $table)
    {
        $q_fields = array();

        foreach ($fields as $field) {
            $name = $this->getName($field);

            if ($name['new'] !== $name['real']) {
                $q_fields[] = '`'.$table.'`.`'.$name['real'].'` AS `'.$name['new'].'`';
            } else {
                $q_fields[] = '`'.$table.'`.`'.$name['real'].'`';
            }
        }

        return $q_fields;
    }

    /**
     * private function setLimit (int $offset, int $limit)
     *
     * Creates the limit of a query
     *
     * return string
     */
    private function setLimit ($offset, $limit)
    {
        if ($limit) {
            if ($offset) {
                return ' LIMIT '.intval($offset).', '.intval($limit);
            } else {
                return ' LIMIT '.intval($limit);
            }
        }

        return '';
    }

    /**
     * private function setSort (array/string $sort)
     *
     * Creates the sort of a query
     *
     * return string
     */
    private function setSort ($sort)
    {
        if ($sort) {
            $sort = (array) $sort;

            foreach ($sort as &$sort_value) {
                $sort_value = preg_replace('/^([^\.]+)\.([^\s]+)/', '`$1`.`$2`', $sort_value);
            }

            return ' ORDER BY '.implode(', ', $sort);
        }

        return '';
    }

    /**
     * private function setGroup (array/string $group)
     *
     * Creates the group of a query
     *
     * return string
     */
    private function setGroup ($group)
    {
        if ($group) {
            $group = (array) $group;

            foreach ($group as &$group_value) {
                $group_value = preg_replace('/^([^\.]+)\.([^\s]+)/', '`$1`.`$2`', $group_value);
            }

            return ' GROUP BY '.implode(', ', (array) $group);
        }

        return '';
    }

    /**
     * function select (array $select)
     *
     * Create de SELECT MySQL query string
     *
     * return integer
     */
    public function select ($select)
    {
        if (!is_array($select)) {
            return array();
        }

        $q_fields = $q_tables = $q_where = $q_join = array();

        foreach ($select['fields'] as $table => $fields) {
            $table_name = $this->getName($table);

            $q_fields = array_merge((array) $this->setFields($fields, $table_name['new']), $q_fields);

            $q_tables[] = $this->setTable($table_name);
        }

        foreach ((array) $select['fields_commands'] as $command) {
            $q_fields[] = $command;
        }

        if (is_array($select['join'])) {
            $q_join_tables_all = array();

            foreach ($select['join'] as $join) {
                $q_join_tables = array();

                foreach ($join['fields'] as $table => $fields) {
                    $table_name = $this->getName($table);

                    $q_fields = array_merge((array) $this->setFields($fields, $table_name['new']), $q_fields);

                    $table = $table;

                    if (!in_array($table, $q_tables) && !in_array($table, $q_join_tables_all)) {
                        $q_join_tables[] = $q_join_tables_all[] = $table;
                    }
                }

                $q_join[] = array(
                    'tables' => $q_join_tables,
                    'conditions' => $join['conditions']
                );
            }
        }

        if (!count($q_fields)) {
            return false;
        }

        $q_tables = array_unique($q_tables);

        $query = 'SELECT '.implode(', ', $q_fields).' FROM ('.implode(', ', $q_tables).')';

        if (count($q_join)) {
            foreach ($q_join as $join) {
                $query .= ' LEFT JOIN ('.implode(', ', $join['tables']).') ON ('.$this->where($join['conditions']).')';
            }
        }

        if (count($select['conditions'])) {
            $conditions = $this->where($select['conditions'], 'AND');

            if ($conditions) {
                $query .= ' WHERE '.$conditions;
            }
        }

        $query .= $this->setGroup($select['group']);
        $query .= $this->setSort($select['sort']);
        $query .= $this->setLimit($select['offset'], $select['limit']);

        return $query.';';
    }

    /**
     * public function insert (array $data)
     *
     * Create de INSERT MySQL query string
     *
     * return false/integer
     */
    public function insert ($data)
    {
        if (empty($data['table'])) {
            return false;
        }

        $table = $data['table'];

        if (empty($data['data']) || !is_array($data['data'])) {
            return 'INSERT INTO `'.$table.'` VALUES ();';
        }

        if (!is_int(key($data['data']))) {
            $data['data'] = array($data['data']);
        }

        unset($data['data']['id']);

        $fields = array();

        foreach ($data['data'] as $row) {
            if (!is_array($row)) {
                continue;
            }

            $fieldsRow = array_keys($row);
            $fields = array_merge($fields, $fieldsRow);
        }

        $fields = array_unique($fields);
        $query_fields = '(`'.implode('`, `', $fields).'`)';

        $values = array();

        foreach ($data['data'] as $row) {
            $valueRow = array();

            foreach ($fields as $field) {
                $valueRow[] = $this->escapeString($row[$field]);
            }

            $valueRow = '("'.implode('", "', $valueRow).'")';
            $values[] = $valueRow;
        }

        $values = implode(', ', $values);

        return 'INSERT INTO `'.$table.'` '.$query_fields.' VALUES '.$values.';';
    }

    /**
     * public function replace (array $data)
     *
     * Create de REPLACE MySQL query string
     *
     * return integer
     */
    public function replace ($data)
    {
        if (empty($data['table']) || empty($data['data']) || !is_array($data['data'])) {
            return false;
        }

        $table = $data['table'];

        if (!is_int(key($data['data']))) {
            $data['data'] = array($data['data']);
        }

        $fields = array();

        foreach ($data['data'] as $row) {
            $fieldsRow = array_keys($row);
            $fields = array_merge($fields, $fieldsRow);
        }

        $fields = array_unique($fields);
        $query_fields = '(`'.implode('`, `', $fields).'`)';

        $values = array();

        foreach ($data['data'] as $row) {
            $valueRow = array();

            foreach ($fields as $field) {
                $valueRow[] = $this->escapeString($row[$field]);
            }

            $valueRow = '("'.implode('", "', $valueRow).'")';
            $values[] = $valueRow;
        }

        $values = implode(', ', $values);

        return 'REPLACE INTO `'.$table.'` '.$query_fields.' VALUES '.$values.';';
    }

    /**
     * public function update (array $data)
     *
     * Create de UPDATE MySQL query string
     *
     * return integer
     */
    public function update ($data)
    {
        if (empty($data['table']) || empty($data['data']) || !is_array($data['data'])) {
            return false;
        }

        $table = $data['table'];

        $query = 'UPDATE `'.$table.'` SET ';

        foreach ($data['data'] as $field => $value) {
            if (is_integer($field)) {
                $query .= $value.', ';
            } else {
                $query .= '`'.$field.'` = "'.$this->escapeString($value).'", ';
            }
        }

        $query = substr($query, 0, -2);

        $data['conditions'] = $this->where($data['conditions']);

        if ($data['conditions']) {
            $query .= ' WHERE '.$data['conditions'];
        }

        $query .= $this->setSort($data['sort']);
        $query .= $this->setLimit($data['offset'], $data['limit']);

        return $query.';';
    }

    public function escapeString ($string) { 
        if (is_array($string)) {
            return array_map(__METHOD__, $string);
        }

        if (!empty($string) && is_string($string)) {
            return str_replace(array('\\', "\0", "\n", "\r", "'", '"', "\x1a"), array('\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'), $string);
        }

        return $string;
    } 

    /**
     * public function delete (string $data)
     *
     * Create de UPDATE MySQL query string
     *
     * return boolean
     */
    public function delete ($data)
    {
        if (empty($data['table'])) {
            return false;
        }

        $table = $data['table'];

        $query = 'DELETE FROM `'.$table.'`';

        $data['conditions'] = $this->where($data['conditions']);

        if (empty($data['conditions']) && empty($data['limit'])) {
            $query = 'TRUNCATE `'.$table.'`;';
        } else if ($data['conditions']) {
            $query .= ' WHERE '.$data['conditions'];
        }

        $query .= $this->setSort($data['sort']);
        $query .= $this->setLimit($data['offset'], $data['limit']);

        return $query.';';
    }

    /**
     * private function where (array $conditions)
     *
     * Create a WHERE string
     *
     * return string
     */
    private function where ($conditions, $first = 'AND')
    {
        if ($conditions === 'all') {
            return '';
        }

        $q = '';

        if ($first === 'AND') {
            $second = 'OR';
        } else {
            $first = 'OR';
            $second = 'AND';
        }

        foreach ($conditions as $key => $value) {
            if (is_int($key)) {
                if (is_array($value)) {
                    $q .= ' ('.trim($this->where($value, $second)).') '.$first;
                    continue;
                } else {
                    $q .= ' '.$value.' '.$first;
                }

                continue;
            }

            preg_match('/^([0-9]+ )?([^\s]+)\s?(.*)$/', $key, $options);

            $field = trim($options[2]);
            $mode = trim($options[3]);

            if (empty($field)) {
                continue;
            }

            if (strpos($field, '.')) {
                $field = '`'.str_replace('.', '`.`', $field).'`';
            } else {
                $field = '`'.$field.'`';
            }

            $value = $this->escapeString($value);

            switch ($mode) {
                case '':
                case '=':
                case 'IN':
                    if (is_array($value)) {
                        if (count($value) > 1) {
                            $q .= ' '.$field.' IN ("'.implode('","', array_unique((array) $value)).'")';
                        } else {
                            $q .= ' '.$field.' = "'.current($value).'"';
                        }
                    } else {
                        $q .= ' '.$field.' = "'.$value.'"';
                    }
                    break;
                case '!=':
                case 'NOT':
                    if (is_array($value)) {
                        if (count($value) > 1) {
                            $q .= ' '.$field.' NOT IN ("'.implode('","', array_unique((array) $value)).'")';
                        } else {
                            $q .= ' '.$field.' != "'.current($value).'"';
                        }
                    } else {
                        $q .= ' '.$field.' != "'.$value.'"';
                    }
                    break;
                case 'BETWEEN':
                    $q .= ' '.$field.' BETWEEN "'.$value[0].'" AND "'.$value[1].'"';
                    break;
                case '>':
                case '>=':
                case '<':
                case '<=':
                case 'LIKE':
                case 'NOT LIKE':
                case 'REGEXP':
                    $q .= ' '.$field.' '.$mode.' "'.$value.'"';
                    break;
                case 'IS NULL':
                    $q .= ' '.$field.' IS NULL';
                    break;
                case 'IN SET':
                    $q .= ' FIND_IN_SET("'.$value.'", '.$field.') > 0';
                    break;
            }

            $q .= ' '.$first;
        }

        $q = trim($q);

        return substr($q, 0, strrpos($q, ' '));
    }

    /**
     * private function setTable (array $table)
     *
     * return false/string
     */
    private function setTable ($table)
    {
        if (empty($table['real'])) {
            return false;
        }

        return ($table['real'] === $table['new']) ? ('`'.$table['real'].'`') : ('`'.$table['real'].'` AS `'.$table['new'].'`');
    }

    /**
     * private function getName (string $table)
     *
     * return false/array
     */
    private function getName ($table)
    {
        if (empty($table)) {
            return false;
        }

        if (strstr($table, '[')) {
            preg_match_all('/[\w-]+/', $table, $tables);

            return array(
                'real' => trim($tables[0][0]),
                'new' => trim($tables[0][1])
            );
        }

        return array(
            'real' => $table,
            'new' => $table,
        );
    }
}
