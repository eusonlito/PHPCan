<?php
/*
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

namespace ANS\PHPCan\Data;

defined('ANS') or die();

class Db
{
    private $Debug;
    private $Errors;
    private $Events;
    private $Cache;
    private $connection;
    private $query_register = array();
    private $settings;
    private $drivers = array(
        '4d', 'cubrid', 'dblib', 'firebird', 'ibm', 'informix', 'mssql',
        'mysql', 'oci', 'odbc', 'pgsql', 'sqlsrv', 'sysbase', 'sqlite'
    );

    public $PDO;
    public $tables;
    public $Database;
    public $language = '';
    public $languages = array();

    /**
     * public function __construct ([string $autoglobal])
     *
     * return none
     */
    public function __construct ($autoglobal = '')
    {
        global $Debug, $Errors, $Events, $Config;

        $this->Debug = $Debug;
        $this->Errors = $Errors;
        $this->Events = $Events;

        if ($autoglobal) {
            $Config->config['autoglobal'][] = $autoglobal;
        }

        $this->setCache();

        register_shutdown_function(array($this, 'callRegisteredShutdown'));
    }

    private function setCache ()
    {
        global $Config;

        $settings = $Config->cache['types']['db'];

        if ($settings['expire'] && $settings['interface']) {
            $this->Cache = new \ANS\Cache\Cache($settings);
        } else {
            $this->Cache = false;
        }
    }

    /**
     * public function setConnection ([string $connection], [array $languages], [array $tables_config], [array $relations_config])
     *
     * return none
     */
    public function setConnection ($connection = 'default', $languages = array(), $tables_config = null, $relations_config = null)
    {
        $this->checkConnection($connection);
        $this->connect();

        $this->languages = (array)$languages;

        if (empty($this->languages)) {
            global $Config;

            $this->languages = array_keys((array) $Config->languages['availables']);
        }

        //Config
        if (is_null($tables_config)) {
            global $Config;

            $tables_config = $Config->tables[$this->connection];
            $relations_config = $Config->relations[$this->connection];
        }

        //Create tables
        $complete_config = $this->completeTableConfig($tables_config, $relations_config);
        $this->tables = array();

        if ($complete_config['tables']) {
            foreach ($complete_config['tables'] as $table => $table_config) {
                $this->tables[$table] = new \ANS\PHPCan\Data\Table($this, $table, $table_config, $complete_config['relations'][$table]);
            }
        }
    }

    /**
     * public function checkConnection ([string $connection])
     *
     * Set a connection name to followings operations and connect to database
     *
     * return boolean
     */
    public function checkConnection ($connection = 'default')
    {
        global $Config;

        if ($connection && $Config->db[$connection]) {
            $this->connection = $connection;
        } else {
            if (empty($Config->db)) {
                throw new \InvalidArgumentException(__('Does not exists database configuration'));
            }

            $this->connection = key($Config->db);

            foreach ($Config->db as $connection => $settings) {
                if ($settings['default']) {
                    $this->connection = $connection;
                    break;
                }
            }
        }

        if (empty($this->connection)) {
            throw new \InvalidArgumentException(__('Connection can not be loaded'));
        }

        $this->settings = $Config->db[$this->connection];

        if (!in_array($this->settings['driver'], $this->drivers)) {
            throw new \InvalidArgumentException(__('Sorry but %s databases are not supported', $this->settings['driver']));
        }

        return $this->connection;
    }

    /**
    * public function getConnection ()
    *
    * return current connection name
    *
    * return string
    */
    public function getConnection ()
    {
        return $this->connection;
    }

    /**
    * public function getAvailableDrivers ()
    *
    * return the available connection drivers
    *
    * return array
    */
    public function getAvailableDrivers ()
    {
        return \PDO::getAvailableDrivers();
    }

    /**
     * public function connect (void)
     *
     * Start a connection
     *
     * return boolean
     */
    public function connect ()
    {
        if ($this->PDO) {
            return true;
        }

        if (empty($this->settings['database']) || empty($this->settings['user'])) {
          return false;
        }

        $this->setDatabase();

        try {
            $this->PDO = new \PDO(
                $this->Database->getDSN($this->settings),
                $this->settings['user'],
                $this->settings['password'],
                $this->settings['options']
            );
        } catch (\PDOException $e) {
            throw new \InvalidArgumentException($e->getMessage());
        }

        return true;
    }

    /**
     * public function connected ()
     *
     * Return if connection is established
     *
     * return boolean
     */
    public function connected ()
    {
        return $this->PDO ? true : false;
    }

    /*
    * private function setInterface (void)
    *
    */
    private function setDatabase ()
    {
        $this->Database = false;

        $class = '\\ANS\\PHPCan\\Data\\Databases\\'.ucfirst($this->settings['driver']);

        if (class_exists($class)) {
            $this->Database = new $class($this);
        } else {
            throw new \InvalidArgumentException(__('Sorry but don\'t exists any driver with name %s', $this->settings['driver']));
        }

        return $this->Database ? true : false;
    }

    /**
      * public function getDatabase ()
      *
      * return object
      */
    public function getDatabase ()
    {
        return $this->Database;
    }

    /**
    * private function error (string $message, [boolean $fatal = false])
    *
    * Register errors
    */
    private function error ($message, $fatal = false)
    {
        $this->Debug->e($message);

        $this->Errors->set('db', $message);

        if ($fatal) {
            $this->Debug->fatalError($message);
        }

        return false;
    }

    /**
     * private function completeTableConfig (array $tables_config, array $relations_config)
     *
     * Complete table config
     */
    private function completeTableConfig ($tables_config, $relations_config)
    {
        //Extend relations
        $extended_relations = array();

        foreach ((array) $relations_config as $relation) {
            $class_name = '\\ANS\\PHPCan\\Data\\Relations\\Relation_'.str_replace(' ', '_', $relation['mode']);

            if (is_callable(array($class_name, 'extend'))) {
                $extend = $class_name::extend($relation);
            } else {
                $this->error(__('The relation mode "%s" does not exits', $relation['mode']), true);

                continue;
            }

            foreach ($extend['relations'] as $settings) {
                if (empty($settings['auto']) && (empty($tables_config[$settings['tables'][0]]) || empty($tables_config[$settings['tables'][1]]))) {
                    continue;
                }

                $table = $settings['tables'][0];
                $name = $this->tableString($settings['tables'][1], '', $settings['name'], $settings['direction'][0]);

                $extended_relations[$table][$name] = $settings;
            }

            $tables_config = arrayMergeReplaceRecursive(
                $tables_config,
                $extend['new_fields']
            );
        }

        return array(
            'relations' => $extended_relations,
            'tables' => $tables_config
        );
    }

    /**
     * function updateDB ([boolean $execute])
     *
     * Update de database with the config table definitions
     *
     * return false/array
     */
    public function updateDB ($execute = true)
    {
        if (!$this->connected()) {
            $this->connect();
        }

        $query_list = $this->Database->getSchemaDifferences($this->tables);

        if (empty($execute) || empty($query_list)) {
            return $query_list;
        }

        foreach ($query_list as $query) {
            $this->query($query, __('Updating database'));
        }

        return $query_list;
    }

    /**
     * function query (string $query, [string $comment], [bool $simulate])
     *
     * Launch mysql_query function
     *
     * return integer
     */
    public function query ($query, $comment = '', $simulate = false)
    {
        if (!$this->connected()) {
            return false;
        }

        if (empty($simulate)) {
            try {
                $this->Result = $this->PDO->query($query);

                if (empty($this->Result)) {
                    $this->error(end($this->PDO->errorInfo()));
                }
            } catch (\PDOException $e) {
                $this->error($e->getMessage());
            }
        }

        return $this->Result ? true : false;
    }

    /**
     * public function result ()
     *
     * Get data from last query
     *
     * return array
     */
    public function result ()
    {
        if (!is_object($this->Result)) {
            return array();
        }

        $result = $this->Result->fetchAll(\PDO::FETCH_ASSOC);

        $this->Result->closeCursor();

        return $result;
    }

    /**
     * public function queryResult (string $query, [string $comment])
     *
     * Execute a query and return the result or false
     *
     * return array/false
     */
    public function queryResult ($query, $comment = '')
    {
        if ($this->query($query, $comment) === false) {
            return false;
        }

        return $this->result();
    }

    /**
     * function queryRegister ([int $offset], [int $length])
     *
     * Return executed queries
     *
     * return array
     */
    public function queryRegister ($offset = 0, $length = null)
    {
        if (is_array($this->query_register) && ($offset || $length)) {
            return array_slice($this->query_register, $offset, $length, true);
        }

        return $this->query_register;
    }

    /**
     * function clearQueryRegister ()
     *
     * Clear all queries registered
     */
    public function clearQueryRegister ($offset = 0, $length = null)
    {
        $this->query_register = array();
    }

    /**
     * function tableExists (string $table)
     *
     * Return false/string
     */
    public function tableExists ($table)
    {
        if (empty($table) || empty($this->tables[$table])) {
            return false;
        }

        return true;
    }

    /**
     * public function getTable (string $table)
     *
     * return false/object
     */
    public function getTable ($table)
    {
        if ($table && $this->tables[$table]) {
            return $this->tables[$table];
        }

        return false;
    }

    /**
     * public function tableString (string/array $realname, [string $newname], [string $name], [string $direction])
     *
     * return false/string
     */
    public function tableString ($realname, $newname = '', $name = '', $direction = '')
    {
        if (is_array($realname)) {
            $name = $realname['name'];
            $direction = $realname['direction'];
            $newname = $realname['newname'];
            $realname = $realname['realname'];
        } else if (empty($realname)) {
            return false;
        }

        if ($newname && ($newname !== $realname)) {
            $realname .= '['.$newname.']';
        }

        if ($direction) {
            $realname .= '-'.$direction;
        }

        if ($name) {
            $realname .= '('.$name.')';
        }

        return $realname;
    }

    /**
     * public function tableArray (string $table, [string $name], [string $direction])
     *
     * return string/array
     */
    public function tableArray ($table, $name = '', $direction = '')
    {
        if (empty($table)) {
            return false;
        }

        preg_match('/^([\w\[\]]+)(\-([\w]+))?(\(([\w]+)\))?$/', $table, $match);

        if (strstr($match[1], '[')) {
            preg_match_all('/\w+/', $match[1], $tables);

            $realname = trim($tables[0][0]);
            $newname = trim($tables[0][1]);
        } else {
            $realname = $newname = $match[1];
        }

        $table = array(
            'realname' => $realname,
            'newname' => $newname,
            'direction'=> $direction ? $direction : $match[3],
            'name' => $name ? $name : $match[5]
        );

        return $table;
    }

    /**
     * private function getIdFromConditions (array $operations)
     *
     * return array
     */
    private function getIdFromConditions ($operations)
    {
        $table = $operations['table'];
        $conditions = $operations['conditions'];

        if (empty($operations['conditions_or']) && is_array($conditions) && count($conditions) === 1 && (array_key_exists('id', $conditions) || array_key_exists($table.'.id', $conditions))) {
            $ids = current($conditions);
        } else {
            $ids = $this->selectIds($operations);
        }

        return (array) $ids;
    }

    /**
     * private function checkTableEvent (string $table, array $data, string $event)
     *
     * return boolean
     */
    private function checkTableEvent ($table, $data, $event)
    {
        $return = array();

        foreach (array_keys($data) as $num_row) {
            $return[$num_row] = $this->Events->defined('tables.'.$table, $event);
        }

        return $return;
    }

    /**
     * private function triggerTableEvent (string $table, string $event_position, string $event_type, array $rows, array $old_values, [array $new_values])
     *
     * Trigger the Table events
     */
    private function triggerTableEvent ($table, $event_position, $event_type, $rows, $old_values, $new_values = null)
    {
        if (empty($rows)) {
            return true;
        }

        $event = $event_position.$event_type;
        $element = 'tables.'.$table;

        if (!$this->Events->defined($element, $event)) {
            return true;
        }

        if ($event_type === 'Insert') {
            $id_values = $trigger_values = $new_values;
        } else if ($event_type === 'Delete') {
            $id_values = $trigger_values = $old_values;
        } else {
            $id_values = $old_values;
            $trigger_values = $new_values;
        }

        foreach ($rows as $num_row => $row) {
            $id = $id_values[$num_row]['id'][''][''];

            $this->Events->trigger($element, $event, $this, array(
                'id' => $id,
                'old_value' => $old_values[$num_row],
                'new_value' => $new_values[$num_row],
                'event' => $event,
                'table' => $table
            ));
        }

        return true;
    }

    /**
     * private function checkFormatEvent (string $table, array $data, string $event)
     *
     * return boolean
     */
    private function checkFormatEvent ($table, $data, $event)
    {
        $return = array();

        foreach ($data as $num_row => $row) {
            foreach ($row as $format => $languages) {
                if (is_int($format) && is_string($languages)) { //Literal field
                    continue;
                }

                if ($this->Events->defined('formats.'.$table.'.'.$format, $event)) {
                    $return[$num_row] = true;

                    continue 2;
                }
            }
        }

        return $return;
    }

    /**
     * private function triggerFormatEvent (string $table, string $event_position, string $event_type, array $rows, array $old_values, [array $new_values])
     *
     * Trigger the format events
     */
    private function triggerFormatEvent ($table, $event_position, $event_type, $rows, $old_values, $new_values = null)
    {
        if (empty($rows)) {
            return true;
        }

        $event = $event_position.$event_type;

        if ($event_type === 'Insert') {
            $id_values = $trigger_values = $new_values;
        } else if ($event_type === 'Delete') {
            $id_values = $trigger_values = $old_values;
        } else {
            $id_values = $old_values;
            $trigger_values = $new_values;
        }

        foreach ($rows as $num_row => $row) {
            $id = $id_values[$num_row]['id'][''][''];

            foreach ($trigger_values[$num_row] as $format => $languages) {
                if (is_int($format) && is_string($languages)) { //Literal field
                    continue;
                }

                $element = 'formats.'.$table.'.'.$format;

                if (!$this->Events->defined($element, $event)) {
                    continue;
                }

                foreach ($languages as $language => $value) {
                    $this->Events->trigger($element, $event, $this, array(
                        'id' => $id,
                        'old_value' => $old_values[$num_row][$format][$language],
                        'new_value' => $new_values[$num_row][$format][$language],
                        'language' => $language,
                        'table' => $table,
                        'field' => $format,
                        'event' => $event
                    ));
                }
            }
        }

        return true;
    }

    /**
     * public function groupSelectedFormatFields (string $table, array $fields)
     *
     * Group data fields into rows, formats, languages and formats
     *
     * return array
     */
    public function groupSelectedFormatFields ($table, $fields)
    {
        $return = array();

        foreach ($fields as $field) {
            $info = $this->fieldInfo($table, $field);

            if (empty($info)) {
                return $this->error(__('The field "%s" doesn\'t exits', __($field)));
            }

            foreach ($info as $field_info) {
                $return[$field_info['format']][$field_info['language']][$field_info['subformat']] = $field;
            }
        }

        return $return;
    }

    /**
     * private function overwriteControl (array $old_data, array $new_data)
     *
     * return array
     */
    private function overwriteControl ($old_data, $new_data)
    {
        $data_return = array();
        $errors = array();

        if (!is_array($new_data)) {
            return true;
        }

        foreach ($new_data as $num_row => $formats) {
            foreach ($formats as $format => $contents) {
                if (($format === 'id') || !isset($old_data[$num_row][$format])) {
                    continue;
                }

                foreach ($contents as $language => $values) {
                    foreach ($values as $key => $value) {
                        if (is_string($value)) {
                            $value = str_replace(array("\n", "\r", "\t"), '', $value);
                            $value = str_replace('&amp;', '&', $value);
                        }

                        if (is_string($old_data[$num_row][$format][$language][$key])) {
                            $old_data[$num_row][$format][$language][$key] = str_replace(array("\n", "\r", "\t"), '', $old_data[$num_row][$format][$language][$key]);

                            while (strstr($old_data[$num_row][$format][$language][$key], '&amp;')) {
                                $old_data[$num_row][$format][$language][$key] = str_replace('&amp;', '&', $old_data[$num_row][$format][$language][$key]);
                            }
                        }

                        if ($value == $old_data[$num_row][$format][$language][$key]) {
                            continue;
                        }

                        return $this->error(__('Field "%s" was edited before yours changes!', __($format)));
                    }
                }
            }
        }

        return true;
    }

    /**
     * private function checkSaveOperations (array $operations, string $action, [array &$format_errors])
     *
     * prepare and check de operations array before execute it
     *
     * return false/array
     */
    private function checkSaveOperations ($operations, $action, &$format_errors = array())
    {
        $errors = array();

        //Check if table exists
        if (!$this->tableExists($operations['table'])) {
            return $this->error(__('The table "%s" doesn\'t exists', __($operations['table'])));
        }

        $table = $this->getTable($operations['table']);

        //Merge conditions
        if ($action !== 'insert') {
            $this->mergeConditions($operations);
        }

        //For insert and update actions
        if ($action !== 'delete') {
            if (empty($operations['data'])) {
                return $this->error(__('There is no data in "%s" operation for the table "%s"', __($action), __($operations['table'])));
            }

            //Group data
            $unique_data = isNumericalArray($operations['data']) ? false : true;

            $operations['data'] = $table->explodeData($operations['data'], $operations['language']);

            if ($operations['data'] === false) {
                return $this->error(__('There is no data in "%s" operation for the table "%s"', __($action), __($operations['table'])));
            }

            //Errors
            if ($errors = $table->checkValues($operations['data'])) {
                $format_errors = $unique_data ? current($errors) : $errors;
            }

            //Overwrite control
            //DISABLED
            if (false && ($action === 'update') && $operations['overwrite_control']) {
                //News and old values
                $old_values = $this->select(array(
                    'table' => $operations['table'],
                    'fields' => '*',
                    'conditions' => $operations['conditions'],
                    'limit' => $operations['limit']
                ));

                if (empty($old_values)) {
                    return ($old_values === false) ? false : true;
                }

                if (!$this->overwriteControl($table->explodeData($old_values), $table->explodeData($operations['overwrite_control']))) {
                    return false;
                } else {
                    unset($old_values);
                }
            }
        }

        //Sub insert/update
        $suboperations = array('insert', 'update');
        $total_rows = count($operations['data']);

        foreach ($suboperations as $action) {
            if (empty($operations[$action]) || !is_array($operations[$action])) {
                continue;
            }

            $unique = false;

            if (!isNumericalArray($operations[$action])) {
                $operations[$action] = array($operations[$action]);
                $unique = true;
            }

            foreach ($operations[$action] as $k => &$operation) {
                //Check if relation exists
                if (!$table->related($operation['table'], $operation['name'], $operation['direction'])) {
                    return $this->error(__('There is not relations between the tables "%s" and "%s"', __($operations['table']), __($operation['table'])));
                }

                $error_name = $operation['errors'] ? $operation['errors'] : $action.'_'.$operation['table'];

                //Apply recursively this function
                if (!($operation = $this->checkSaveOperations($operation, $action, $errors))) {
                    return false;
                }

                if ($errors) {
                    if ($unique) {
                        $format_errors[$error_name] = $errors;
                    } else {
                        $format_errors[$error_name][$k] = $errors;
                    }
                }
            }
        }

        //Sub relate/unrelate
        $suboperations = array('relate', 'unrelate');

        foreach ($suboperations as $action) {
            if ($operations[$action] && is_array($operations[$action])) {
                if (!isNumericalArray($operations[$action])) {
                    $operations[$action] = array($operations[$action]);
                }

                foreach ($operations[$action] as &$operation) {
                    //Check if table exists
                    if (!$this->tableExists($operation['table'])) {
                        return $this->error(__('The table "%s" doesn\'t exists', __($operation['table'])));
                    }

                    //Check if relation exists
                    if (!$table->related($operation['table'], $operation['name'], $operation['direction'])) {
                        return $this->error(__('There is not relations between the tables "%s" and "%s"', __($operations['table']), __($operation['table'])));
                    }
                }
            }
        }

        return $operations;
    }

    /**
     * public function save (array $operations, [boolean $return_id])
     *
     * return boolean/int/array
     */
    public function save ($operations, $return_id = true)
    {
        if (isNumericalArray($operations)) {
            $ok = array();

            foreach ($operations as $k => $operation) {
                $ok[$k] = $this->save($operation, $return_id);

                if ($ok[$k] === false) {
                    return false;
                }
            }

            return $return_id ? $ok : true;
        }

        foreach ($operations as $action => $operation) {
            switch ($action) {
                case 'insert':
                case 'delete':
                case 'update':
                case 'relate':
                case 'unrelate':
                    $ok[$action] = $this->$action($operation, $return_id);

                    if ($ok[$action] === false) {
                        return false;
                    }
                    break;

                default:
                    return $this->error(__('The action "%s" is not valid', $action));
            }
        }

        return $return_id ? $ok : true;
    }

    /**
     * public function insert (array $operations, [boolean $return_id])
     *
     * return boolean/int/array
     */
    public function insert ($operations, $return_id = true)
    {
        if (isNumericalArray($operations)) {
            $err = false;
            $checked_operations = array();

            foreach ($operations as $k => $operation) {
                $errors = array();

                $checked_operations[$k] = $this->checkSaveOperations($operation, 'insert', $errors);

                if ($checked_operations[$k] === false) {
                    return false;
                }

                if ($errors) {
                    return $this->Error($errors);
                }
            }

            $ok = array();

            foreach ($checked_operations as $k => $operation) {
                $ok[$k] = $this->makeInsert($operation, $return_id);

                if ($ok[$k] === false) {
                    return false;
                }
            }

            return $return_id ? $ok : true;
        }

        $operations = $this->checkSaveOperations($operations, 'insert', $errors);

        if (empty($operations)) {
            return false;
        }

        if ($errors) {
            return $this->Error($errors);
        }

        return $this->makeInsert($operations, $return_id);
    }

    /**
     * public function update (array $operations, [boolean $return_id])
     *
     * Update rows in database
     *
     * return boolean/int/array
     */
    public function update ($operations, $return_id = true)
    {
        if (isNumericalArray($operations)) {
            $err = false;
            $checked_operations = array();

            foreach ($operations as $k => $operation) {
                $errors = array();

                $checked_operations[$k] = $this->checkSaveOperations($operation, 'update', $errors);

                if ($checked_operations[$k] === false) {
                    return false;
                }

                if ($errors) {
                    return $this->Error($errors);
                }
            }

            $ok = array();

            foreach ($checked_operations as $k => $operation) {
                $ok[$k] = $this->makeUpdate($operation, $return_id);

                if ($ok[$k] === false) {
                    return false;
                }
            }

            return $return_id ? $ok : true;
        }

        $operations = $this->checkSaveOperations($operations, 'update', $errors);

        if (empty($operations)) {
            return false;
        }

        if ($errors) {
            return $this->Error($errors);
        }

        return $this->makeUpdate($operations, $return_id);
    }

    /**
     * public function delete (array $operations, [boolean $return_id])
     *
     * Delete rows in database
     *
     * return boolean/int/array
     */
    public function delete ($operations, $return_id = true)
    {
        if (isNumericalArray($operations)) {
            $ok = array();

            foreach ($operations as $k => $operation) {
                $ok[$k] = $this->delete($operation, $return_id);

                if ($ok[$k] === false) {
                    return false;
                }
            }

            return $return_id ? $ok : true;
        }

        if (!($operations = $this->checkSaveOperations($operations, 'delete'))) {
            return false;
        }

        return $this->makeDelete($operations, $return_id);
    }

    /**
     * public function relate (array $operations)
     *
     * relate two registers
     *
     * return boolean
     */
    public function relate ($operations)
    {
        if (isNumericalArray($operations)) {
            foreach ($operations as $k => $operation) {
                if ($this->relate($operation) === false) {
                    return false;
                }
            }

            return true;
        }

        return $this->makeRelateUnrelate('relate', $operations);
    }

    /**
     * public function unrelate (array $operations)
     *
     * unrelate two registers
     *
     * return boolean
     */
    public function unrelate ($operations)
    {
        if (isNumericalArray($operations)) {
            foreach ($operations as $k => $operation) {
                if ($this->unrelate($operation) === false) {
                    return false;
                }
            }

            return true;
        }

        return $this->makeRelateUnrelate('unrelate', $operations);
    }

    /**
     * private function makeInsert (array $operations, boolean $return_id)
     *
     * Insert rows in database
     *
     * return boolean/int/array
     */
    private function makeInsert ($operations, $return_id)
    {
        $table = $this->getTable($operations['table']);

        //Subinserts
        if ($operations['insert']) {
            foreach ($operations['insert'] as $insert) {
                $ids = $this->makeInsert($insert, true);

                if ($ids === false) {
                    return false;
                }

                if ($operations['relate'] && !isNumericalArray($operations['relate'])) {
                    $operations['relate'] = array($operations['relate']);
                }

                $operations['relate'][] = array(
                    'name' => $insert['name'],
                    'table' => $insert['table'],
                    'direction' => $insert['direction'],
                    'conditions' => array(
                        'id' => $ids
                    ),
                    'options' => $insert['options'],
                );
            }
        }

        //News and old values
        $old_values = array();
        $default_values = $table->getDefaultValues();

        $new_values = $operations['data'];

        foreach ($new_values as &$row) {
            $row = arrayMergeReplaceRecursive($default_values, $row);
        }

        // Check for events
        if ($operations['table_events'] !== false) {
            $event_table_before = $this->checkTableEvent($operations['table'], $operations['data'], 'beforeInsert');
        }
        
        $event_format_before = $this->checkFormatEvent($operations['table'], $operations['data'], 'beforeInsert');

        if ($event_table_before) {
            $this->triggerTableEvent($operations['table'], 'before', 'Insert', $event_table_before, array(), $new_values);
        }

        if ($event_format_before) {
            $this->triggerFormatEvent($operations['table'], 'before', 'Insert', $event_format_before, array(), $new_values);
        }

        if ($operations['table_events'] !== false) {
            $event_table_after = $this->checkTableEvent($operations['table'], $operations['data'], 'afterInsert');
        }

        $event_format_after = $this->checkFormatEvent($operations['table'], $operations['data'], 'afterInsert');

        //Convert values
        $new_values = $table->valueDB($new_values);

        //Ungroup data
        $operations['data'] = $table->implodeData($new_values);

        $query = $this->Database->insert($operations);

        $ok = $this->query($query);

        if ($this->settings['query_register_log']) {
            $this->query_register[] = array(
                'operation' => 'insert',
                'operations' => $operations,
                'query' => $query,
                'trace' => trace()
            );
        }

        if ($ok === false) {
            return $this->error(__('There was an error in the insert operation'));
        }

        //Select ids if needle
        if ($event_format_before || $event_table_before || $return_id || $operations['relate']) {
            $last_id = $this->PDO->lastInsertId();
        }

        //afterInsert
        if ($event_format_after || $event_table_after) {
            foreach ($new_values as $num_row => &$row) {
                $row['id'][''][''] = $last_id;
            }

            if ($event_table_after) {
                $this->triggerTableEvent($operations['table'], 'after', 'Insert', $event_table_after, array(), $new_values);
            }

            if ($event_format_after) {
                $this->triggerFormatEvent($operations['table'], 'after', 'Insert', $event_format_after, array(), $new_values);
            }
        }

        //Relate
        if ($operations['relate']) {
            if (!$this->makeRelateUnrelateWith('relate', $operations['table'], $last_id, $operations['relate'])) {
                return false;
            }
        }

        return $return_id ? $last_id : true;
    }

    /**
     * private function makeUpdate (array $operations, [boolean $return_id])
     *
     * Update/replace rows in database
     *
     * return boolean/int/array
     */
    private function makeUpdate ($operations, $return_id = true)
    {
        $table = $this->getTable($operations['table']);

        //Subinserts
        if ($operations['insert']) {
            foreach ($operations['insert'] as $insert) {
                $ids = $this->makeInsert($insert, true);

                if ($ids === false) {
                    return false;
                }

                if ($operations['relate'] && !isNumericalArray($operations['relate'])) {
                    $operations['relate'] = array($operations['relate']);
                }

                $operations['relate'][] = array(
                    'name' => $insert['name'],
                    'table' => $insert['table'],
                    'direction' => $insert['direction'],
                    'conditions' => array(
                        'id' => $ids
                    ),
                    'options' => $insert['options'],
                );
            }
        }

        //News and old values
        if ($operations['old_values']) {
            $old_values = $operations['old_values'];
            unset($operations['old_values']);
        } else {
            $old_values = $this->select(array(
                'table' => $operations['table'],
                'fields' => '*',
                'conditions' => $operations['conditions'],
                'sort' => $operations['sort'],
                'limit' => $operations['limit'],
                'offset' => $operations['offset']
            ));

            if (empty($old_values)) {
                return ($old_values === false) ? false : true;
            }

            $old_values = $table->explodeData($old_values);
        }

        $new_values = $operations['data'];

        //Save ids
        $ids = array();

        foreach ($old_values as $row) {
            $ids[] = $row['id'][''][''];
        }

        //Change the conditions
        $operations['conditions'] = array(
            'id' => $ids
        );

        $operations['limit'] = count($ids);

        // Check for events
        if ($operations['table_events'] !== false) {
            $event_table_before = $this->checkTableEvent($operations['table'], $operations['data'], 'beforeUpdate');
        }

        $event_format_before = $this->checkFormatEvent($operations['table'], $operations['data'], 'beforeUpdate');

        if ($event_table_before) {
            $this->triggerTableEvent($operations['table'], 'before', 'Update', $event_table_before, $old_values, $new_values);
        }

        if ($event_format_before) {
            $this->triggerFormatEvent($operations['table'], 'before', 'Update', $event_format_before, $old_values, $new_values);
        }

        if ($operations['table_events'] !== false) {
            $event_table_after = $this->checkTableEvent($operations['table'], $operations['data'], 'afterUpdate');
        }

        $event_format_after = $this->checkFormatEvent($operations['table'], $operations['data'], 'afterUpdate');

        //Convert values
        $new_values = $table->valueDB($new_values, $ids);

        //Ungroup data
        $operations['data'] = current($table->implodeData($new_values));

        $query = $this->Database->update($operations);

        $ok = $this->query($query);

        if ($this->settings['query_register_log']) {
            $this->query_register[] = array(
                'operation' => 'update',
                'operations' => $operations,
                'query' => $query,
                'trace' => trace()
            );
        }

        if ($ok === false) {
            return $this->error(__('There was an error in the update operation'));
        }

        if ($event_table_after) {
            $this->triggerTableEvent($operations['table'], 'after', 'Update', $event_table_after, $old_values, $new_values);
        }

        if ($event_format_after) {
            $this->triggerFormatEvent($operations['table'], 'after', 'Update', $event_format_after, $old_values, $new_values);
        }

        //Update
        if ($operations['update']) {
            foreach ($operations['update'] as $update) {
                $update_table = $this->tableArray($update['table'], $update['name'], $update['direction']);
                $condition = $this->tableString($operations['table'], '', $update_table['name'], $update_table['direction']);
                $condition = $update_table['realname'].'.'.$condition.'.id';
                $update['conditions'][$condition] = $ids;

                $this->makeUpdate($update);
            }
        }

        //Relate
        if ($operations['relate']) {
            if (!$this->makeRelateUnrelateWith('relate', $operations['table'], $ids, $operations['relate'])) {
                return false;
            }
        }

        //Unrelate
        if ($operations['unrelate']) {
            if (!$this->makeRelateUnrelateWith('unrelate', $operations['table'], $ids, $operations['relate'])) {
                return false;
            }
        }

        return $return_id ? (count($ids) > 1 ? $ids : current($ids)) : true;
    }

    /**
     * public function makeDelete (array $operations, [boolean $return_id])
     *
     * Delete rows in database
     *
     * return boolean/int/array
     */
    public function makeDelete ($operations, $return_id = true)
    {
        $table = $this->getTable($operations['table']);

        //Check for events
        $tmp_data = array($table->getDefaultValues());

        // Check for events
        if ($operations['table_events'] !== false) {
            $event_table_before = $this->checkTableEvent($operations['table'], $tmp_data, 'beforeDelete');
        }

        $event_table_after = $this->checkTableEvent($operations['table'], $tmp_data, 'afterDelete');

        if ($operations['table_events'] !== false) {
            $event_format_before = $this->checkFormatEvent($operations['table'], $tmp_data, 'beforeDelete');
        }

        $event_format_after = $this->checkFormatEvent($operations['table'], $tmp_data, 'afterDelete');

        $event = ($event_table_before || $event_table_after || $event_format_before || $event_format_after);

        //News and old values
        if ($event || $return_id || $table->getRelations()) {
            $old_values = $this->select(array(
                'table' => $operations['table'],
                'fields' => ($event ? '*' : 'id'),
                'conditions' => $operations['conditions'],
                'sort' => $operations['sort'],
                'limit' => $operations['limit'],
                'offset' => $operations['offset']
            ));

            if (empty($old_values)) {
                return ($old_values === false) ? false : true;
            }

            $old_values = $table->explodeData($old_values);

            //Save ids
            $ids = array();

            foreach ($old_values as $row) {
                $ids[] = $row['id'][''][''];
            }

            //Change the conditions
            $operations['conditions'] = array(
                'id' => $ids
            );

            $operations['limit'] = count($ids);

            if ($event_table_before) {
                $this->triggerTableEvent($operations['table'], 'before', 'Delete', $event_table_before, $old_values);
            }

            if ($event_format_before) {
                $this->triggerFormatEvent($operations['table'], 'before', 'Delete', $event_format_before, $old_values);
            }
        }

        //Relations
        foreach ($table->getRelations() as $relation) {
            //Delete dependent relations
            if ($relation->removeDependent()) {
                $this->delete(array(
                    'table' => $relation->settings['tables'][1],
                    'conditions' => array(
                        $operations['table'].'.id' => $ids
                    )
                ));

                continue;
            }

            //Unrelate relations
            if ($relation->unrelateDependent()) {
                $relation->unrelate(array('conditions' => $operations['conditions']), array('conditions' => 'all'));
            }
        }

        $query = $this->Database->delete($operations);

        $ok = $this->query($query);

        if ($this->settings['query_register_log']) {
            $this->query_register[] = array(
                'operation' => 'delete',
                'operations' => $operations,
                'query' => $query,
                'trace' => trace()
            );
        }

        if ($ok === false) {
            return $this->error(__('There was an error in the delete operation'));
        }

        if ($event_table_after) {
            $this->triggerTableEvent($operations['table'], 'after', 'Delete', $event_table_after, $old_values);
        }

        if ($event_format_after) {
            $this->triggerFormatEvent($operations['table'], 'after', 'Delete', $event_format_after, $old_values);
        }

        return $return_id ? (count($ids) > 1 ? $ids : current($ids)) : true;
    }

    /**
     * private function makeRelateUnrelate (string $type, array $operations)
     *
     * Execute relate or unrelate functions
     *
     * return boolean
     */
    private function makeRelateUnrelate ($type, $operations)
    {
        $this->mergeConditions($operations['tables'][0]);
        $this->mergeConditions($operations['tables'][1]);

        if ($operations['tables'][0]['direction'] || $operations['tables'][1]['direction']) {
            if ($operations['tables'][0]['direction']) {
                $table = $this->getTable($operations['tables'][0]['table']);

                if ($relation = $table->getRelation($operations['tables'][1]['table'], $operations['name'], $operations['tables'][1]['direction'])) {
                    if ($this->settings['query_register_log']) {
                        $this->query_register[] = array(
                            'operation' => $type,
                            'operations' => $operations,
                            'trace' => trace()
                        );
                    }

                    return $relation->$type($operations['tables'][0], $operations['tables'][1], $operations['options']);
                }
            } else {
                $table = $this->getTable($operations['tables'][1]['table']);

                if ($relation = $table->getRelation($operations['tables'][0]['table'], $operations['name'], $operations['tables'][0]['direction'])) {
                    if ($this->settings['query_register_log']) {
                        $this->query_register[] = array(
                            'operation' => $type,
                            'operations' => $operations,
                            'trace' => trace()
                        );
                    }

                    return $relation->$type($operations['tables'][1], $operations['tables'][0], $operations['options']);
                }
            }
        } else {
            $table = $this->getTable($operations['tables'][0]['table']);

            if (!is_object($table)) {
                return $this->error(__('Table "%s" used in relation/unrelation don\'t exists', __($operations['tables'][0]['table'])));
            }

            if ($relation = $table->getRelation($operations['tables'][1]['table'], $operations['name'])) {
                if ($this->settings['query_register_log']) {
                    $this->query_register[] = array(
                        'operation' => $type,
                        'operations' => $operations,
                        'trace' => trace()
                    );
                }

                return $relation->$type($operations['tables'][0], $operations['tables'][1], $operations['options']);
            }
        }

        return $this->error(__('There is not relations between the tables "%s" and "%s"', __($operations['tables'][0]['table']), __($operations['tables'][1]['table'])));
    }

    /**
     * private function makeRelateUnrelateWith (string $type, string $table1, array $ids, array $operations)
     *
     * Execute relate or unrelate functions with ids
     *
     * return boolean
     */
    private function makeRelateUnrelateWith ($type, $table1, $ids, $operations)
    {
        $table1_operations = array(
            'conditions' => array(
                'id' => $ids
            )
        );

        if (!isNumericalArray($operations)) {
            $operations = array($operations);
        }

        foreach ($operations as $operation) {
            $relation = $this->tables[$operation['table']]->getRelation($table1, $operation['name'], $operation['direction']);

            if (empty($relation)) {
                return $this->error(__('There is not relations between the tables "%s" and "%s"', __($operation['table']), __($table1)));
            }

            $this->mergeConditions($operation);

            if ($relation->$type($operation, $table1_operations, $operation['options']) === false) {
                if ($this->settings['query_register_log']) {
                    $this->query_register[] = array(
                        'operation' => $type,
                        'operations' => $operation,
                        'tables' => array($operation, $table1_operations),
                        'trace' => trace()
                    );
                }

                return false;
            };
        }

        return true;
    }

    /**
     * public function language ([string $language])
     *
     * return string
     */
    public function language ($language = null)
    {
        if (!is_null($language)) {
            if (($language === '') || ($language === 'all') || in_array($language, $this->languages)) {
                $this->language = $language;
            } else {
                $this->error(__('The language "%s" is not valid', $language));
            }
        }

        return $this->language;
    }

    /**
     * public function selectCount (string $table, [array $conditions])
     *
     * return integer
     */
    public function selectCount ($operations = array(), $conditions = array())
    {
        if (is_array($operations)) {
            unset($operations['add_tables']);
            unset($operations['join_tables']);
            unset($operations['sort']);
            unset($operations['sort_commands']);
            unset($operations['limit']);
            unset($operations['offset']);
            unset($operations['pagination']);
            unset($operations['fields']);
        } else {
            $operations = array(
                'table' => $operations,
                'conditions' => $conditions
            );
        }

        $operations['fields_commands'] = 'COUNT(*)';

        $table = $this->tableArray($operations['table']);

        if (empty($table) || !$this->tableExists($table['realname'])) {
            return $this->error(__('There is not table to select'));
        }

        //Make select
        $result = $this->makeSelect($operations);

        if ($result === false) {
            return false;
        }

        if ($result) {
            if ($operations['group']) {
                return count($result);
            } else {
                return intval(current(current($result)));
            }
        } else {
            return 0;
        }
    }

    /**
     * public function selectWithId (string $table, int $id, [string $fields])
     *
     * return string/array
     */
    public function selectWithId ($table, $id, $fields = '*')
    {
        $result = $this->select(array(
            'table' => $table,
            'fields' => $fields,
            'conditions' => array(
                'id' => $id
            ),
            'limit' => 1
        ));

        return $result;
    }

    /**
     * public function selectIds (array $operations)
     *
     * return false/int/array
     */
    public function selectIds ($operations)
    {
        if (is_string($operations)) {
            $operations = array('table' => $operations);
        }

        $operations['fields'] = 'id';

        unset($operations['add_tables']);
        unset($operations['join_tables']);
        unset($operations['sort']);
        unset($operations['sort_commands']);
        unset($operations['pagination']);

        $table = $this->tableArray($operations['table']);

        if (empty($table) || !$this->tableExists($table['realname'])) {
            return $this->error(__('There is not table to select'));
        }

        //Make select
        $ids = $this->makeSelect($operations);

        if (!is_array($ids)) {
            return $ids;
        }

        if ($ids['id']) {
            return $ids['id'];
        }

        foreach ($ids as &$id) {
            $id = $id['id'];
        }

        return $ids;
    }

    /**
     * private function selectPaginate (array $operations)
     *
     * return array
     */
    private function selectPaginate ($operations)
    {
        global $Data;

        if ($operations['limit'] == 0) {
            $operations['limit'] = 10;
        }

        $total = $this->selectCount($operations);

        $pagination = $this->getPagination(array_merge($operations, array('total' => $total)));

        $operations['offset'] = ($pagination['page'] * $operations['limit']) - $operations['limit'];

        $pagination_store = $operations['pagination']['store'] ? $operations['pagination']['store'] : 'pagination';

        $Data->set($pagination_store, $pagination);

        return $operations;
    }

    public function getPagination ($operations)
    {
        if (empty($operations['limit'])) {
            return array();
        }

        $page = intval($operations['pagination']['page']);
        $map = intval($operations['pagination']['map']);

        $pagination = array('table' => $operations['table']);
        $pagination['page'] = $page;
        $pagination['total'] = $operations['total'];
        $pagination['total_pages'] = ceil($pagination['total'] / $operations['limit']);

        if (($pagination['page'] < 1) || ($pagination['page'] > $pagination['total_pages'])) {
            $pagination['page'] = 1;
        }

        if ($map == 0) {
            $map = 10;
        }

        $interval = intval($map / 2);

        $pagination['first'] = $pagination['page'] - $interval;
        $pagination['last'] = $pagination['page'] + $interval;

        if ($pagination['first'] < 1) {
            $pagination['first'] = 1;
            $pagination['last'] = $map;
        }

        if ($pagination['last'] > $pagination['total_pages']) {
            $pagination['last'] = $pagination['total_pages'];
            $pagination['first'] = $pagination['total_pages'] - $map;

            if ($pagination['first'] < 1) {
                $pagination['first'] = 1;
            }
        }

        while (($pagination['last'] - $pagination['first']) >= $map) {
            if (($pagination['page'] - $pagination['first']) > $interval) {
                $pagination['first']++;
            } else {
                $pagination['last']--;
            }
        }

        $pagination['previous'] = (($pagination['page'] - 1) < 1) ? false : ($pagination['page'] - 1);
        $pagination['next'] = (($pagination['page'] + 1) > $pagination['total_pages']) ? false : ($pagination['page'] + 1);

        return $pagination;
    }

    /**
    * private function getQueryKey (array $operations)
    *
    * Return the generated query key
    *
    * return boolean
    */
    private function getQueryKey ($operations)
    {
        if ($this->Cache) {
            return md5(serialize($operations));
        }
    }

    /**
    * private function existsCache (string $query_key)
    *
    * Check if this query is cached
    *
    * return boolean
    */
    private function existsCache ($query_key)
    {
        if ($this->Cache) {
            return $this->Cache->exists($query_key);
        }
    }

    /**
    * private function getCache (string $query_key, array $operations)
    *
    * Get a query from cache
    *
    * return boolean
    */
    private function getCache ($query_key, $operations)
    {
        if (empty($this->Cache)) {
            return false;
        }

        if (is_array($operations['pagination'])) {
            global $Data;

            $pagination_key = md5($query_key.'_pagination');

            $Data->set(($operations['pagination']['store'] ?: 'pagination'), $this->Cache->get($pagination_key));
        }

        return $this->Cache->get($query_key);
    }

    /**
    * private function putCache (string $query_key, array $operations, array $result)
    *
    * Set a query in cache
    *
    * return boolean
    */
    private function putCache ($query_key, $operations, $result)
    {
        if (empty($this->Cache)) {
            return false;
        }

        $expire = is_integer($operations['cache']) ? $operations['cache'] : null;
        $pagination_key = md5($query_key.'_pagination');

        if ($pagination_key) {
            global $Data;

            $this->Cache->set($pagination_key, $Data->{$operations['pagination']['store'] ?: 'pagination'}, $expire);
        }

        return $this->Cache->set($query_key, $result, $expire);
    }

    /**
     * public function select ([array/string $operations])
     *
     * return false/array
     */
    public function select ($operations = '')
    {
        if (is_string($operations)) {
            $operations = array('table' => $operations);
        }

        if (!isset($operations['cache']) && $this->Cache) {
            $operations['cache'] = $this->Cache->getSettings('expire');
        }

        if ($operations['cache'] && $this->Cache) {
            $query_key = $this->getQueryKey($operations);

            if ($this->existsCache($query_key)) {
                return $this->getCache($query_key, $operations);
            }
        }

        $table = $this->tableArray($operations['table']);

        if (empty($table) || !$this->tableExists($table['realname'])) {
            return $this->error(__('There is not table to select'));
        }

        if (is_array($operations['pagination'])) {
            $operations = $this->selectPaginate($operations);
        }

        //Make select
        $result = $this->makeSelect($operations, '', 'html');

        if (empty($result)) {
            return $result;
        }

        reset($result);

        if (($operations['limit'] == 1) && empty($operations['rows'])) {
            $result = current($result);
        }

        if ($operations['cache'] && $this->Cache) {
            return $this->putCache($query_key, $operations, $result);
        } else {
            return $result;
        }
    }

    /**
     * private function makeSelect (array $data, [string $prev_table], [string $exit_format])
     *
     * Make selects recursively
     *
     * return array
     */
    private function makeSelect ($data, $prev_table = '', $exit_format = '')
    {
        $add_tables = (array) $data['add_tables'];
        $exit_format = isset($data['exit_format']) ? $data['exit_format'] : $exit_format;

        unset($data['add_tables']);

        if (empty($data['fields']) && empty($data['fields_commands'])) {
            $data['fields'] = '*';
        }

        //Main table
        $table = $data['table'];
        $table_data = $this->tableArray($table);

        if (empty($table) || empty($table_data) || !$this->tableExists($table_data['realname'])) {
            return $this->error(__('There is not table to select'));
        }

        //Make select
        $data = $this->processQuery($data, $prev_table);

        if (empty($data)) {
            return $data;
        }

        $query = $this->Database->select($data);

        $tmp_result = $this->queryResult($query);

        if ($this->settings['query_register_log']) {
            $this->query_register[] = array(
                'operation' => 'select',
                'operations' => $data,
                'query' => $query,
                'trace' => trace()
            );
        }

        if ($tmp_result === false) {
            return array();
        }

        //Explode values
        $result = array();

        if ($tmp_result) {
            foreach (current($tmp_result) as $field => $value) {
                if (strpos($field, '-') === false) {
                    foreach ($tmp_result as $num_row => $row) {
                        $result[$num_row][$field] = $row[$field];
                    }

                    continue;
                }

                $sub_arrays = explode('-', $field, 3);

                if ($sub_arrays[2]) {
                    foreach ($tmp_result as $num_row => $row) {
                        $result[$num_row][$sub_arrays[0]][$sub_arrays[1]][$sub_arrays[2]] = $row[$field];
                    }

                    continue;
                }

                foreach ($tmp_result as $num_row => $row) {
                    $result[$num_row][$sub_arrays[0]][$sub_arrays[1]] = $row[$field];
                }
            }

            if ($exit_format && $result) {
                $result = $this->transformValues($exit_format, $data, $result);
            }
        }

        unset($tmp_result);

        //add_tables
        if ($add_tables && $result) {

            //Get the ids
            if (count($result) === 1) {
                $ids = $result[0]['id'];
            } else {
                $ids = array();

                foreach ($result as $id) {
                    $ids[] = $id['id'];
                }
            }

            //Execute sub_selects recursively
            foreach ($add_tables as $name => $select) {
                if (is_string($select)) {
                    $select = array('table' => $select);
                } else if (!isset($select['table'])) {
                    $select['table'] = $name;
                }

                $added_table = $this->tableArray($select['table'], $select['name'], $select['direction']);

                if (!$this->tableExists($added_table['realname'])) {
                    $this->error(__('The table "%s" doesn\'t exists', __($added_table['realname'])));
                    continue;
                }

                //Check if tables are related
                $relation = $this->tables[$added_table['realname']]->getRelation($table_data['realname'], $added_table['name'], $added_table['direction']);

                if (empty($relation)) {
                    $this->error(__('There is not relations between the tables "%s" and "%s"', __($added_table['realname']), __($table)));
                    continue;
                }

                //Tree
                if ($select['tree']) {
                    $select['add_tables'][$name] = $select;

                    if (is_array($select['tree'])) {
                        $select['add_tables'][$name] = arrayMergeReplaceRecursive($select, $select['tree']);
                    } else {
                        $select['add_tables'][$name] = $select;
                    }
                }

                //Add a condition to link with current table
                $condition = $this->tableString($table_data['realname'], 'phpcan_related_'.$table_data['newname'], $relation->settings['name'], $relation->settings['direction'][1]).'.id';

                if (empty($select['conditions'][$condition])) {
                    $select['conditions'][$condition] = $ids;
                }

                //Get limit & offset
                $limit = $select['limit'];
                $offset = $select['offset'];

                unset($select['limit'], $select['offset']);

                if (empty($limit)) {
                    if ($relation->unique) {
                        $limit = 1;
                    }
                }

                $select['table'] = $this->tableString($added_table['realname'], $added_table['newname']);

                //Execute the selection
                $sub_result = $this->makeSelect($select, 'phpcan_related_'.$table_data['newname']);

                if ($sub_result === false) {
                    return false;
                }

                //Merge $result and $sub_result
                $name = $this->addedName($name, $added_table['newname'], $added_table['name'], $added_table['direction']);

                foreach ($result as &$result_row) {
                    $result_row[$name] = array();

                    if (empty($sub_result)) {
                        continue;
                    }

                    foreach ($sub_result as $sub_result_row) {
                        if ($sub_result_row['id_prev_table'] == $result_row['id']) {
                            unset($sub_result_row['id_prev_table']);

                            $result_row[$name][] = $sub_result_row;
                        }
                    }

                    //Limit
                    if ($limit || $offset) {
                        $result_row[$name] = array_slice($result_row[$name], intval($offset), intval($limit));
                    }

                    //Unique result
                    if (($limit == 1) && empty($select['rows'])) {
                        $result_row[$name] = current($result_row[$name]);
                    }
                }

                unset ($result_row);
            }
        }

        return $result;
    }

    /**
     * public function addedName (string $name, string $table, [string $rel_name], [string $rel_direction])
     *
     * return string
     */
    public function addedName ($name, $table, $rel_name = '', $rel_direction = '')
    {
        if (!is_int($name)) {
            return $name;
        }

        if (is_array($table)) {
            $table = $table['newname'];
        }

        $name = $table;

        if ($rel_name) {
            $name .= '_'.$rel_name;
        }

        if ($rel_direction) {
            $name .= '_'.$rel_direction;
        }

        return $name;
    }

    /**
     * private function processQuery (array $query, string $prev_table)
     *
     * Process the query options before execute (fields, conditions, order, etc)
     *
     * return array
     */
    private function processQuery ($query, $prev_table)
    {
        //Get table
        $table_name = $this->tableArray($query['table']);
        $table = $this->getTable($table_name['realname']);

        //Get fields names
        $query['fields'] = array(
            $query['table'] => (empty($query['fields']) && $query['fields_commands']) ? array() : $table->selectFields($query['fields'], ($query['language'] ?: $this->language))
        );

        //Prepare conditions
        $this->mergeConditions($query);

        //Process conditions
        $renamed_tables = $used_tables = array($query['table'] => $table_name['newname']);
        $conditions = $this->processConditions($query['table'], $query['conditions'], $prev_table, $renamed_tables, $used_tables);

        if ($conditions === false) {
            return false;
        }

        $query['conditions'] = $conditions['conditions'];
        $query['fields'] = arrayMergeReplaceRecursive($query['fields'], $conditions['fields']);

        //Join tables
        if ($query['join_tables']) {
            $query['join'] = $this->processJoin($table_name['newname'], $query['join_tables'], $renamed_tables, $used_tables);
        }

        unset($query['join_tables']);

        $query['sort'] = $this->sort($table_name['newname'], $query['sort'], $query['sort_direction'], $query['sort_commands'], $renamed_tables);

        $query['group'] = $this->group($table_name['newname'], $query['group'], $query['group_commands'], $renamed_tables);

        $query['fields_commands'] = (array) $query['fields_commands'];

        return $query;
    }

    /**
     * private function processJoin (array $table_base, array $join_tables, array &$renamed_tables, array &$used_tables)
     *
     * process join operations
     *
     * return array
     */
    private function processJoin ($table_base, $join_tables, &$renamed_tables, &$used_tables)
    {
        if (!isNumericalArray($join_tables)) {
            $join_tables = array($join_tables);
        }

        foreach ($join_tables as $name => &$join) {
            if (!is_array($join)) {
                $join = array('table' => $join);
            }

            //Insert $table_base at first
            $tables = explode('.', $join['table']);

            if (($tables[0] !== $table_base) || empty($tables[1])) {
                array_unshift($tables, $table_base);
            }

            //Get last table
            $last_table = $this->tableArray(array_pop($tables), $join['name'], $join['direction']);

            $tables[] = $this->tableString($last_table);

            $join['table'] = implode('.', $tables);

            //Rename table if it's needle to avoid conflicts and save it
            if ($renamed_tables[$join['table']]) {
                $last_table['newname'] = $renamed_tables[$join['table']];
            } else {
                if (in_array($last_table['newname'], $renamed_tables)) {
                    $last_table['newname'] = $last_table['newname'].'_'.uniqid();
                }

                $renamed_tables[$join['table']] = $last_table['newname'];
                end($tables);
                $tables[key($tables)] = $this->tableString($last_table);

                $join['table'] = implode('.', $tables);
            }

            //Get fields
            if (empty($join['fields'])) {
                $join['fields'] = '*';
            }

            $prefix = 'join-'.$this->addedName($name, $last_table['newname'], $last_table['name'], $last_table['direction']).'-';

            $join['fields'] = array(
                $last_table['newname'] => $this->selectFields($last_table['realname'], $join['fields'], $prefix)
            );

            //Prepare conditions
            $this->mergeConditions($join);

            //If table wasn't used previously, add the conditions
            if (empty($used_tables[$join['table']]) && empty($join['conditions'])) {
                $join['conditions'][$join['table'].'._'] = '';
            }

            //Process conditions
            $conditions = $this->processConditions($join['table'], $join['conditions'], '', $renamed_tables, $used_tables);

            if ($conditions === false) {
                return false;
            }

            $join['conditions'] = $conditions['conditions'];

            $join['fields'] = arrayMergeReplaceRecursive($join['fields'], $conditions['fields']);
        }

        return $join_tables;
    }

    /**
     * public function mergeConditions (&array $query)
     *
     * Merge all kinds of condition in an unique array
     *
     * return none
     */
    public function mergeConditions (&$query)
    {
        if (($query['conditions'] === 'all') || ($query['conditions_and'] === 'all') || ($query['conditions_or'] === 'all')) {
            $conditions = 'all';
        } else {
            $conditions = arrayMergeReplaceRecursive((array) $query['conditions'], (array) $query['conditions_and']);

            if ($query['conditions_or']) {
                $conditions[] = $query['conditions_or'];
            }
        }

        $query['conditions'] = $conditions;

        unset($query['conditions_and'], $query['conditions_or']);
    }

    /**
     * private function processConditions (array $table_base, array $conditions, string $prev_table, array &$renamed_tables, [string $mode])
     *
     * Process the conditions of the query
     *
     * return none
     */
    private function processConditions ($table_base, $conditions, $prev_table, &$renamed_tables, &$used_tables, $mode = 'and')
    {
        $used_tables_here = $used_tables;
        $used_tables_buffer = array();

        $result = array(
            'conditions' => array(),
            'fields' => array(),
        );

        if (empty($conditions)) {
            return $result;
        }

        if ($conditions === 'all') {
            $result['conditions'] = 'all';

            return $result;
        }

        foreach ($conditions as $name => $value) {
            //Literal conditions
            if (is_int($name)) {
                if (!is_array($value)) {
                    $result['conditions'][$name] = $value;
                }

                continue;
            }

            //Parse condition
            preg_match('/^([0-9]+ )?(([\(\)\[\]\-\.\w]+)?\.)?([\w-]+) ?(.*)$/', $name, $t);

            $last_condition = array(
                'num' => $t[1],
                'tables' => $t[3] ? $t[3] : $table_base,
                'field' => ($t[4] === '_') ? '' : $t[4],
                'condition' => $t[5] ? ' '.$t[5] : '',
            );

            //Insert the $table_base
            if (!preg_match('/^'.preg_quote($table_base).'(\.|$)/', $last_condition['tables']) && !preg_match('/^'.preg_quote($renamed_tables[$table_base]).'(\.|$)/', $last_condition['tables'])) {
                $last_condition['tables'] = $table_base.'.'.$last_condition['tables'];
            }

            //Explode tables
            $tables = explode('.', $last_condition['tables']);

            //Create semiconditions
            $t = '';

            foreach ($tables as &$v) {
                $v = $this->tableArray($v);
                $t .= '.'.$this->tableString($v);
                $v['tables'] = substr($t, 1);
            }

            //Insert value in the last element
            $k = count($tables) - 1;
            $tables[$k]['value'] = $value;
            $tables[$k] += $last_condition;

            //Process tables
            foreach ($tables as $k => $current) {
                if (($k == 0) && $tables[1]) {
                    continue;
                }

                $table = $this->getTable($current['realname']);
                $previous = $tables[$k-1];

                //Rename table if it's needle to avoid conflicts and save it
                if ($renamed_tables[$current['tables']]) {
                    $current['newname'] = $renamed_tables[$current['tables']];
                } else {
                    if (in_array($current['newname'], $renamed_tables)) {
                        $current['newname'] = $current['newname'].'_'.uniqid();
                    }

                    $renamed_tables[$current['tables']] = $current['newname'];
                }

                //If table was used previously
                if ($used_tables_here[$current['tables']]) {
                    if ($current['field']) {
                        if (!($field = $table->selectField($current['field'], $this->language))) {
                            return $this->error(__('The field %s doesn\'t exists in the table %s', __($current['field']), __($current['realname'])));
                        }

                        $field = $table->fieldArray($field);
                        $name = $current['num'].$current['newname'].'.'.$field['realname'].$current['condition'];
                        $result['conditions'][$name] = $value;
                    }

                    continue;
                }

                //Save table
                if ($mode === 'or') {
                    $used_tables_buffer[$current['tables']] = $current['newname'];
                } else {
                    $used_tables_here[$current['tables']] = $current['newname'];
                }

                $result['fields'][$this->tableString($current['realname'], $current['newname'])] = array();

                //Check if the table exists
                if (!$this->tableExists($current['realname'])) {
                    return $this->error(__('The table "%s" doesn\'t exists', __($current['realname'])));
                }

                //Check if the field exists
                if ($current['field']) {
                    if (!($field = $table->selectField($current['field'], $this->language))) {
                        return $this->error(__('The field %s doesn\'t exists in the table %s', __($current['field']), __($current['realname'])));
                    }

                    $field = $table->fieldArray($field);
                    $current['field'] = $field['realname'];
                }

                //Check if the relation exists
                $relation = $this->tables[$previous['realname']]->getRelation($current['realname'], $current['name'], $current['direction']);

                if (empty($relation)) {
                    return $this->error(__('There is not relations between the tables "%s" and "%s"', __($previous['realname']), __($current['realname'])));
                }

                //Execute and merge results
                $rel_condition = $relation->selectConditions($previous['newname'], $current['newname'], $current);

                if ($mode === 'or') {
                    $result['conditions'][] = arrayMergeReplaceRecursive((array) $rel_condition['conditions'], (array) $rel_condition['relation_conditions']);
                } else {
                    if ($rel_condition['conditions']) {
                        $result['conditions'] = arrayMergeReplaceRecursive($rel_condition['conditions'], $result['conditions']);
                    }

                    foreach ($rel_condition['relation_conditions'] as $condition) {
                        $result['conditions'][] = $condition;
                    }
                }

                if ($rel_condition['relation_table'] && !array_key_exists($rel_condition['relation_table'], $result['fields'])) {
                    $result['fields'][$rel_condition['relation_table']] = array();
                    $used_tables[$previous['tables'].'.'.$rel_condition['relation_table']] = $rel_condition['relation_table'];
                }

                if ($prev_table && ($prev_table === $current['newname']) && $rel_condition['relation_field']) {
                    $result['fields'] = arrayMergeReplaceRecursive($rel_condition['relation_field'], $result['fields']);
                }
            }
        }

        //Subconditions
        foreach ($conditions as $name => $value) {
            if (is_int($name) && is_array($value)) {
                $subresult = $this->processConditions($table_base, $value, $prev_table, $renamed_tables, $used_tables_here, (($mode === 'and') ? 'or' : 'and'));

                if ($subresult === false) {
                    return false;
                }

                $result['conditions'][] = $subresult['conditions'];
                $result['fields'] = arrayMergeReplaceRecursive($subresult['fields'], $result['fields']);
            }
        }

        if ($used_tables_buffer) {
            $used_tables_here = arrayMergeReplaceRecursive($used_tables_here, $used_tables_buffer);
        }

        $used_tables = $used_tables_here;

        return $result;
    }

    /**
     * private function sort (string $table_base, string/array $sort, string $sort_direction, string/array $sort_commands, array $renamed_tables)
     *
     * return string
     */
    private function sort ($table_base, $sort, $sort_direction, $sort_commands, $renamed_tables)
    {
        $return = $sort_commands ? (array) $sort_commands : array();

        foreach ((array) $sort as $v) {
            if (empty($v)) {
                continue;
            }

            preg_match('/^(([\(\)\[\]\-\.\w]+)?\.)?([\w-]+)( \w+)?$/', trim($v), $t);

            $table_path = $t[2] ? $t[2] : $table_base;
            $table = $this->getTable($table_path);
            $field = $table->fieldArray($table->selectField($t[3], $this->language));
            $direction = $t[4] ? trim($t[4]) : ( $sort_direction ? strtoupper($sort_direction) : 'ASC' );

            //Insert the $base_table
            $preg_table_base = preg_quote($table_base);

            if (!preg_match('/^'.$preg_table_base.'(\.|$)/', $table_path)) {
                $table_path = $table_base.'.'.$table_path;
            }

            $table = $renamed_tables[$table_path];

            if (empty($table)) {
                $this->error(__('The table %s hasn\'t been selected so you can\'t sort by it', __($table_path)));
            }

            $return[] = $table.'.'.$field['realname'].' '.$direction;
        }

        return $return;
    }

    /**
     * private function group (string $table_base, string/array $group, string/array $renamed_tables)
     *
     * return string
     */
    private function group ($table_base, $group, $group_commands = array(), $renamed_tables)
    {
        $return = $group_commands ? (array) $group_commands : array();

        foreach ((array) $group as $v) {
            preg_match('/^(([\(\)\[\]\-\.\w]+)?\.)?([\w-]+)$/', trim($v), $t);

            $table_path = $t[2] ? $t[2] : $table_base;
            $table = $this->getTable($table_path);
            $field = $table->fieldArray($table->selectField($t[3], $this->language));

            //Insert the $base_table
            $preg_table_base = preg_quote($table_base);

            if (!preg_match('/^'.$preg_table_base.'(\.|$)/', $table_path)) {
                $table_path = $table_base.'.'.$table_path;
            }

            $table = $renamed_tables[$table_path];

            if (empty($table)) {
                $this->error(__('The table %s hasn\'t been selected so you can\'t group by it', __($table_path)));
                continue;
            }

            $return[] = $table.'.'.$field['realname'];
        }

        return $return;
    }

    /**
     * public function mergeQuery (array $query1, array $query2)
     *
     * Merge the current selection with other query and return result
     *
     * return array
     */
    public function mergeQuery ($query1, $query2)
    {
        return arrayMergeReplaceRecursive((array) $query1, (array) $query2);
    }

    /**
     * private function transformValues (string $fn, array $query, array $result)
     *
     * return array
     */
    private function transformValues ($fn, $query, $result)
    {
        switch ($fn) {
            case 'html':
                $fn = 'valueHtml';
                break;

            case 'form':
                $fn = 'valueForm';
                break;

            default:
                return $this->error(__('The exit format %s is not valid', $fn));
        }

        $unique = false;

        if (!isNumericalArray($result)) {
            $unique = true;
            $result = array($result);
        }

        foreach ($query['fields'] as $table => $fields) {
            $table = $this->getTable($table);
            $groups = array();

            foreach ($fields as $field) {
                $field = $table->fieldArray($field);
                $info = $table->fieldInfo($field['realname']);
                list($group) = explode('-', $field['newname'], 2);

                $groups[$group]['format'] = $info['field'];

                if ($info['language'] && (($this->language === 'all') || ($this->language && ($this->language !== $info['language'])))) {
                    $groups[$group]['languages'][$info['language']] = 1;
                }

                if ($info['subformat']) {
                    $groups[$group]['subformats'][$info['subformat']] = 1;
                }
            }

            foreach ($groups as $name => $group) {
                $format = $table->getFormat($group['format']);

                if ($group['languages']) {
                    if ($group['subformats']) {
                        foreach ($result as &$row) {
                            if (!is_array($row[$name])) {
                                $row[$name] = array(key($group['subformats']) => $row[$name]);
                                $row[$name] = $format->$fn($row[$name]);

                                if (count($row[$name]) === 1) {
                                    $row[$name] = current($row[$name]);
                                }

                                continue;
                            }

                            foreach ($row[$name] as $key => &$language_row) {
                                if (!is_array($language_row)) {
                                    $language_row = array(key($group['subformats']) => $language_row);
                                }

                                $language_row = $format->$fn($language_row);

                                if (count($language_row) === 1) {
                                    $language_row = current($language_row);
                                }
                            }
                        }
                    } else {
                        foreach ($result as &$row) {
                            foreach ($row[$name] as &$language_row) {
                                $language_row = $format->$fn(array('' => $language_row));

                                if (count($language_row) === 1) {
                                    $language_row = current($language_row);
                                }
                            }
                        }
                    }
                } else {
                    if ($group['subformats']) {
                        foreach ($result as &$row) {
                            $row[$name] = $format->$fn($row[$name]);
                        }
                    } else {
                        foreach ($result as &$row) {
                            $row[$name] = $format->$fn(array('' => $row[$name]));

                            if (count($row[$name]) === 1) {
                                $row[$name] = current($row[$name]);
                            }
                        }
                    }
                }
            }
        }

        reset($result);

        return $unique ? current($result) : $result;
    }

    public function renameField ($table, $from, $to, $type)
    {
        $query = $this->Database->renameField($table, $from, $to, $type);

        $ok = $this->query($query);

        if ($this->settings['query_register_log']) {
            $this->query_register[] = array(
                'operation' => 'renameField',
                'query' => $query,
                'trace' => trace()
            );
        }

        if ($ok === false) {
            return $this->error(__('There was an error in the rename operation'));
        }

        return true;
    }

    public function callRegisteredShutdown ()
    {

        if (empty($this->settings['query_register_log']) || empty($this->settings['query_register_store'])) {
            return true;
        }

        if (!is_string($this->settings['query_register_store'])) {
            return true;
        }

        global $Config;

        $log = BASE_PATH.$Config->phpcan_paths['logs'].$this->settings['query_register_store'];

        if (!is_writable(dirname($log)) || (is_file($log) && !is_writable($log))) {
            return true;
        }

        if ($this->settings['query_register_append']) {
            return file_put_contents($log, print_r($this->query_register, true), FILE_APPEND);
        } else {
            return file_put_contents($log, print_r($this->query_register, true));
        }
    }
}
