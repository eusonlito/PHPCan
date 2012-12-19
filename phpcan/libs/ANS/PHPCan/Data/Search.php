<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

namespace ANS\PHPCan\Data;

defined('ANS') or die();

class Search
{
    private $query = array();
    private $words = array();
    private $settings = array();
    private $Debug;
    private $Db;
    private $Errors;

    public function __construct (Db $Db)
    {
        global $Debug, $Errors;

        $this->Db = $Db;
        $this->Debug = $Debug;
        $this->Errors = $Errors;
    }

    /**
     * function setDb (object $Db)
     */
    public function setData (Db $Db)
    {
        $this->Db = $Db;
    }

    /**
     * function getQuery (array $settings)
     *
     * Create a conditions query for "like" searches
     *
     * return array/false
     */
    public function getQuery ($settings)
    {
        $this->setSettings($settings);

        if (!$this->setText() || !$this->setFields()) {
            return false;
        }

        if (!$this->setWords()) {
            return array();
        }

        //Build conditions
        $this->allQuery();
        $this->fieldQuery();

        return $this->query;
    }

    /**
     * private function setSettings (array $settings)
     *
     * return none
     */
    private function setSettings ($settings)
    {
        $this->settings = $settings;

        if (empty($this->settings['min_length'])) {
            $this->settings['min_length'] = 3;
        }

        if (empty($this->settings['errors'])) {
            $this->settings['errors'] = 'search';
        }

        $operators = array(
            '=' => array('LIKE', '{STRING}'),
            ':' => array('LIKE', '%{STRING}%'),
            '^=' => array('LIKE', '{STRING}%'),
            '$=' => array('LIKE', '%{STRING}'),
            '!=' => array('NOT LIKE', '{STRING}'),
            '!:' => array('NOT LIKE', '%{STRING}%'),
            '>' => array('>', '{STRING}'),
            '<' => array('<', '{STRING}'),
            '<=' => array('<=', '{STRING}'),
            '>=' => array('>=', '{STRING}')
        );

        if ($this->settings['operators'] === 'all') {
            $this->settings['operators'] = $operators;
        } else if (is_array($this->settings['operators'])) {
            foreach ($this->settings['operators'] as $key => $value) {
                if (array_key_exists($value, $operators)) {
                    $this->settings['operators'][$key] = $value;
                } else {
                    unset($this->settings['operators'][$key]);
                }
            }
        } else {
            $this->settings['operators'] = '';
        }
    }

    /**
     * private function setText (void)
     *
     * return boolean
     */
    private function setText ()
    {
        if (is_string($this->settings['text'])) {
            $this->settings['text'] = trim($this->settings['text']);

        } else if (is_array($this->settings['text'])) {
            $text = '';

            foreach ($this->settings['text'] as $term) {
                if (!in_array($term['name'], $this->settings['fields'])) {
                    continue;
                }

                if (!preg_match('/^[\=\!\^\$]$/', $term['operator'])) {
                    $term['operator'] = ':';
                }

                if (strpos(' ', $term['value'])) {
                    $term['value'] = '"'.$term['value'].'"';
                }

                $text .= ' '.$term['name'].trim($term['operator']).trim($term['value']);
            }

            $this->settings['text'] = trim($text);
        }

        if (empty($this->settings['text'])) {
            $this->Errors->set($this->settings['errors'], __('There is no text to search or it is not valid'));

            return false;
        }

        return true;
    }

    /**
     * private function setFields (void)
     *
     * return boolean
     */
    private function setFields ()
    {
        if (empty($this->settings['where'])) {
            $this->Debug->error('search', __('There is not "where" parameter in the searching'));

            return false;
        }

        if (!isNumericalArray($this->settings['where'])) {
            $this->settings['where'] = array($this->settings['where']);
        }

        if (!$this->getTablesAndFields($this->settings['where'])) {
            return false;
        }

        unset($this->settings['where']);

        return $this->fields ? true : false;
    }

    /**
     * private function getTablesAndFields (array $where, [string $prev_table])
     *
     * return array
     */
    private function getTablesAndFields ($where, $prev_table = '')
    {
        foreach ($where as $select) {
            $table_array = $this->Db->tableArray($select['table'], $select['name'], $select['direction']);
            $table_string = $prev_table.$this->Db->tableString($table_array);
            $table_object = $this->Db->getTable($table_array['realname']);

            if (empty($table_array)) {
                $this->Debug->error('search', __('The table "%s" doesn\'t exists', $select['table']));

                return false;
            }

            if (!is_array($select['fields'])) {
                $select['fields'] = $table_object->selectFields($select['fields'], $this->Db->language(), '', false);
            }

            if (empty($select['fields'])) {
                $this->Debug->error('search', __('There is not fields defined in table "%s"', $select['table']));

                return false;
            }

            foreach ($select['fields'] as $search_key => $field) {
                $field = $table_object->fieldArray($field);

                if (is_int($search_key)) {
                    $search_key = $field['newname'];
                }

                $search_key = $select['prefix'].$search_key;

                if (isset($this->fields['fields'][$search_key])) {
                    $this->Debug->error('search', __('The search key %s is just defined', $search_key));
                }

                $this->fields['fields'][$search_key] = array(
                    'table' => $table_string,
                    'field' => $field['realname']
                );
            }

            if (!isset($select['on_demand']) || $select['on_demand']) {
                foreach ($select['fields'] as $search_key => $field) {
                    $field = $table_object->fieldArray($field);

                    $this->fields['all'][$table_string][] = $field['realname'];
                }
            }

            if ($select['add_tables']) {
                if (!isNumericalArray($select['add_tables'])) {
                    $select['add_tables'] = array($select['add_tables']);
                }

                if (!$this->getTablesAndFields($select['add_tables'], $table_string.'.')) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * public function getString (void)
     *
     * Return the last query in text format
     */
    public function getString ()
    {
        $query = '';

        foreach ((array) $this->words['fields'] as $word) {
            $query .= ' '.$word['text'];
        }

        $query .= implode(' ', (array) $this->words['all']);

        return trim($query);
    }

    /**
     * private function setWords (void)
     *
     * Divide a text in words
     *
     * return boolean
     */
    private function setWords ()
    {
        $text = $this->settings['text'];
        $this->words = array();

        if (strlen($text) < $this->settings['min_length']) {
            return false;
        }

        //Prepare regular expression with operators and fields
        $operators = '';
        $fields = '';
        $chars = '';

        if ($this->settings['operators']) {
            $operators = array_keys($this->settings['operators']);

            foreach ($operators as &$operator) {
                $chars .= $operator;
                $operator = preg_quote($operator, '/');
            }

            $chars = array_unique(str_split($chars));
            $chars = preg_quote(implode('', $chars), '/');
            $operators = implode('|', $operators);

            $fields = array_keys($this->fields['fields']);

            foreach ($fields as &$field) {
                $field = preg_quote($field, '/');
            }

            $fields = implode('|', $fields);

            if (empty($fields) || empty($operators)) {
                $fields = $operators = '';
            }
        }

        //Remove strange characters
        $text = trim(preg_replace(array('/[^\w"áéíóúçñüÁÉÍÓÚÇÑÜ\-'.$chars.']/', '/\s+/'), ' ', $text));

        //Parse the string
        preg_match_all('/(('.$fields.')\s?('.$operators.')\s?)?("([^"]*)"|([^ ]*))/', $text, $words);

        foreach ($words[0] as $key => $value) {
            $value = trim($value);

            if (empty($value)) {
                continue;
            }

            $word = array(
                'field' => $words[2][$key],
                'operator' => $words[3][$key],
                'value' => $words[5][$key] ? $words[5][$key] : $words[6][$key],
                'text' => $words[0][$key]
            );

            if ($word['field'] && empty($this->fields['fields'][$word['field']])) {
                $word['value'] = $word['text'];
                unset($word['field'], $word['operator']);
            }

            if (empty($word['operator']) && strlen($word['value']) < $this->settings['min_length']) {
                continue;
            }

            if ($word['operator']) {
                $this->words['fields'][] = $word;
            } else {
                $this->words['all'][] = $word['value'];
            }
        }

        if ($this->words['all']) {
            $this->words['all'] = array_unique($this->words['all']);
        }

        return $this->words ? true : false;
    }

    /**
     * private function allQuery (void)
     *
     * Create conditions to search all words across all fields
     */
    private function allQuery ()
    {
        $num = 0;

        foreach ((array) $this->words['all'] as $w => $word) {
            foreach ($this->fields['all'] as $table => $field) {
                foreach ($field as $f) {
                    $this->query['conditions_and'][$w][($num++).' '.$table.'.'.$f.' LIKE'] = '%'.$word.'%';
                }
            }
        }
    }

    /**
     * private function fieldQuery (void)
     *
     * Create conditions to search in specific fields
     */
    private function fieldQuery ()
    {
        $num = 0;

        foreach ((array) $this->words['fields'] as $word) {
            $field = $word['field'];
            $value = $word['value'];

            if (!($settings = $this->fields['fields'][$field])) {
                continue;
            }

            if (!($operator = $this->settings['operators'][$word['operator']])) {
                continue;
            }

            $value = str_replace('{STRING}', $value, $operator[1]);

            if ($operator[0]) {
                $operation = ' '.$operator[0];
            }

            if ($settings['id_search'] && preg_match('/^[0-9]*$/', $value)) {
                $settings['field'] = 'id';
                $operation = '';
            }

            if ($settings['value_function']) {
                if (is_callable($settings['value_function'])) {
                    $value = $settings['value_function']($value);
                } else {
                    $this->Debug->error('db', __('This function does not exists (%s)'));
                }
            }

            $condition = ($num++).' '.$this->Db->tableString($settings['table'], $settings['name'], $settings['direction']).'.'.$settings['field'].$operation;

            $this->query['conditions_and'][$condition] = $value;
        }
    }
}
