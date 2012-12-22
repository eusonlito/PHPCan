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

class Table
{
    private $Debug;
    private $Db;
    private $table;
    private $settings;
    private $processed = array();

    /**
     * public function __construct (object $Db, string $table, array $table_config, array $relation_config)
     *
     * return none
     */
    public function __construct (\ANS\PHPCan\Data\Db $Db, $table, $table_config, $relation_config)
    {
        global $Debug;

        $this->Debug = $Debug;
        $this->Db = $Db;
        $this->table = $table;

        $this->settings = array(
            'table' => $table_config,
            'relations' => $relation_config
        );

        if (empty($this->settings['table'])) {
            $this->Debug->fatalError(__('The configuration for the table "%s" does not exists!', $this->table));
        }
    }

    /**
     * public function __get (string $name)
     *
     * return none
     */
    public function __get ($name)
    {
        switch ($name) {
            case 'relations':
                if ($this->processed['relations'] !== true) {
                    $this->setRelationsConfig();
                }

                return $this->relations;

            case 'formats':
                if ($this->processed['formats'] !== true) {
                    $this->setFormatsConfig();
                }

                return $this->$name;
        }
    }

    /**
     * public function __isset (string $name)
     *
     * return none
     */
    public function __isset ($name)
    {
        switch ($name) {
            case 'relations':
                if ($this->processed['relations'] !== true) {
                    $this->setRelationsConfig();
                }

                return isset($this->relations);

            case 'formats':
                if ($this->processed['formats'] !== true) {
                    $this->setFormatsConfig();
                }

                return isset($this->$name);
        }
    }

    /**
     * public function bindEvent (string/array $event_names, mixed $function)
     *
     * return boolean
     */
    public function bindEvent ($event_names, $function)
    {
        global $Events;

        $Events->bind('tables.'.$this->table, $event_names, $function);
    }

    /**
     * public function fieldArray (string $name)
     *
     * return array
     */
    public function fieldArray ($name)
    {
        if (empty($name)) {
            return array();
        }

        if (strpos($name, '[')) {
            preg_match('/([\w-]+)\[([\w-]+)\]/', $name, $match);

            return array(
                'realname' => trim($match[1]),
                'newname' => trim($match[2])
            );
        }

        return array(
            'realname' => $name,
            'newname' => $name
        );
    }

    /**
     * public function fieldString (string/array $realname, [string $newname])
     *
     * return false/string
     */
    public function fieldString ($realname, $newname = '')
    {
        if (is_array($realname)) {
            $newname = $realname['newname'];
            $realname = $realname['realname'];
        } else if (empty($realname)) {
            return false;
        }

        if ($newname && ($newname !== $realname)) {
            $realname .= '['.$newname.']';
        }

        return $realname;
    }

    /**
     * public function fieldInfo (string $field)
     *
     * return false/array
     */
    public function fieldInfo ($field)
    {
        if (!($format = $this->getFormat($field))) {
            return false;
        }

        if (strpos($field, '-') === false) {
            return array(
                'field' => $field,
                'language' => (count($format->languages) === 1) ? '' : null,
                'subformat' => $format->settings[''] ? '' : null
            );
        }

        $pieces = explode('-', $field, 3);

        if ($pieces[2]) {
            if (in_array($pieces[1], $format->languages) && $format->settings[$pieces[2]]) {
                return array(
                    'field' => $pieces[0],
                    'language' => $pieces[1],
                    'subformat' => $pieces[2]
                );
            }

            return false;
        }

        if ($format->settings[$pieces[1]]) {
            return array(
                'field' => $pieces[0],
                'language' => (count($format->languages) === 1) ? '' : null,
                'subformat' => $pieces[1]
            );
        }

        if (in_array($pieces[1], $format->languages)) {
            return array(
                'field' => $pieces[0],
                'language' => $pieces[1],
                'subformat' => $format->settings[''] ? '' : null
            );
        }

        return false;
    }

    /**
     * private function tableString (string/array $realname, [string $newname], [string $name], [string $direction])
     *
     * return false/string
     */
    private function tableString ($realname, $newname = '', $name = '', $direction = '')
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
     * public function getFormat (string $field)
     *
     * return object/false
     */
    public function getFormat ($field)
    {
        list($field) = explode('-', $field, 2);

        if ($this->formats[$field]) {
            return $this->formats[$field];
        }

        return false;
    }

    /**
     * public function selectField (string $field, [string $language])
     *
     * return string
     */
    public function selectField ($field, $language = 'all')
    {
        $field = $this->selectFields($field, $language, '', false);

        return current($field);
    }

    /**
     * public function selectFields ([string/array $fields], [string $language], [string $prefix], [boolean $force_id])
     *
     * return array
     */
    public function selectFields ($fields = '*', $language = 'all', $prefix = '', $force_id = true)
    {
        if ($fields === '**') {
            $fields = array_keys($this->formats);
        } else if ($fields === '*') {
            $fields = array();

            foreach ($this->formats as $format_name => $format) {
                if ($format->format === 'id_relation') {
                    continue;
                }

                $fields[] = $format_name;
            }
        }

        $new_fields = array();

        foreach ((array) $fields as $field) {
            $field = $this->fieldArray($field);

            if (!($info = $this->fieldInfo($field['realname']))) {
                $this->Debug->error('db', __('The field "%s" doesn\'t exist', $field['realname']));

                return array();
            }

            if ($field['realname'] === $field['newname']) {
                $renamed = false;
                $field['newname'] = $info['field'];
                $subformat_suffix = ($info['subformat'] || is_null($info['subformat'])) ? true : false;
            } else {
                $renamed = true;
                $subformat_suffix = is_null($info['subformat']) ? true : false;
            }

            if ($renamed && !is_null($info['language']) && !is_null($info['subformat'])) {
                $language_suffix = $subformat_suffix = false;
            } else {
                $language_suffix = ((($language === 'all') && (is_null($info['language']))) || ($info['language'])) ? true : false;
            }

            $format = $this->getFormat($field['realname']);

            foreach ($format->getFields($field['realname'], $language) as $realname => $field_info) {
                $newname = $prefix.$field['newname'];

                if ($language_suffix) {
                    $newname .= '-'.$field_info['language'];
                }

                if ($subformat_suffix) {
                    $newname .= '-'.$field_info['subformat'];
                }

                $new_fields[] = $this->fieldString($realname, $newname);
            }
        }

        //Add id field
        if ($force_id && !in_array('id', $new_fields)) {
            array_unshift($new_fields, 'id');
        }

        return $new_fields;
    }

    /**
     * public function explodeData (array $data, [string $language])
     *
     * Group data fields into rows, formats, languages and subformats
     *
     * return array
     */
    public function explodeData ($data, $language = '')
    {
        if (!isNumericalArray($data) || !isMultidimensionalArray($data)) {
            $data = array($data);
        }

        foreach ($data as $num_row => $row) {
            $sorted_row = array();

            foreach ($row as $field => $value) {
                //Literal field
                if (is_int($field) && is_string($value)) {
                    $sorted_row[$field] = $value;
                    continue;
                }

                if (!($info = $this->fieldInfo($field))) {
                    $this->Debug->error('table', __('The field "%s" doesn\'t exist', $field));

                    return false;
                }

                //Language
                if (is_null($info['language']) || is_array($value)) {
                    $has_language = false;

                    $format = $this->getFormat($info['field']);

                    if (is_array($value)) {
                        $has_language = true;

                        foreach (array_keys($value) as $lang) {
                            if (!isset($format->fields[$lang])) {
                                $has_language = false;
                                break;
                            }
                        }
                    }

                    if (!is_array($value) || empty($has_language)) {
                        if ($language === 'all') {
                            $info['language'] = $format->languages;
                        } else {
                            $info['language'] = in_array($language, $format->languages) ? $language : $format->default_language;
                        }
                    } else {
                        $info['language'] = array_keys($value);
                    }
                }

                //Sort row
                if (is_array($info['language'])) {
                    foreach ($info['language'] as $lang) {
                        if (is_array($value) && isset($value[$lang])) {
                            $sorted_row[$info['field']][$lang][$info['subformat']] = $value[$lang];
                        } else {
                            $sorted_row[$info['field']][$lang][$info['subformat']] = $value;
                        }
                    }
                } else {
                    $sorted_row[$info['field']][$info['language']][$info['subformat']] = $value;
                }
            }

            foreach ($sorted_row as $format_name => $value) {
                //Literal field
                if (is_int($format_name) && is_string($value)) {
                    $sorted_row[$format_name] = $value;
                    continue;
                }

                $format = $this->getFormat($format_name);

                foreach ($value as $lang => $subvalue) {
                    if (isset($subvalue['']) && (count($subvalue) === 1)) {
                        $subvalue = $subvalue[''];
                    }

                    $sorted_row[$format_name][$lang] = $format->explodeData($subvalue);
                }
            }

            $data[$num_row] = $sorted_row;
        }

        return $data;
    }

    /**
     * public function implodeData (array $data)
     *
     * implode fields grouped with explodeFields function
     *
     * return array
     */
    public function implodeData ($data)
    {
        $return = array();

        foreach ($data as $num_row => $row) {
            if (!is_array($row)) {
                continue;
            }

            foreach ($row as $format => $languages) {
                //Literal field
                if (is_int($format) && is_string($languages)) {
                    $return[$num_row][$format] = $languages;
                    continue;
                }

                $languages = array_filter($languages);

                if (empty($languages)) {
                    continue;
                }

                foreach ($languages as $language => $fields) {
                    $language = $language ? '-'.$language : '';

                    foreach ($fields as $field => $value) {
                        $field = $field ? '-'.$field : '';

                        $return[$num_row][$format.$language.$field] = $value;
                    }
                }
            }
        }

        return $return;
    }

    /**
     * public function checkValues (array $data)
     *
     * Execute the format check function and return the errors
     *
     * return array
     */
    public function checkValues ($data)
    {
        $errors = array();

        foreach ($data as $num_row => $row) {
            foreach ($row as $format => $languages) {
                //Literal field
                if (is_int($format)) {
                    continue;
                }

                $format = $this->getFormat($format);

                foreach ($languages as $language => $fields) {
                    if (!$format->check($fields)) {
                        $errors[$num_row][$format->name][$language] = $format->getErrors();
                    }
                }
            }
        }

        return $errors;
    }

    /**
     * private function getDefaultValues ([array $fields])
     *
     * return array
     */
    public function getDefaultValues ($fields = array())
    {
        $default_values = array();

        if ($fields === '*') {
            $fields = array();
        }

        foreach ($this->formats as $field => $format) {
            if ($fields && !in_array($field, $fields)) {
                continue;
            }

            if (in_array($format->format, array('id', 'id_relation'))) {
                continue;
            }

            foreach ($format->languages as $language) {
                $default_values[$format->name][$language] = $format->getDefaultValues();
            }
        }

        return $default_values;
    }

    /**
     * public function valueDB (array $values, [array $ids])
     *
     * Execute de format valueDB function
     *
     * return boolean
     */
    public function valueDB ($values, $ids = array())
    {
        foreach ($values as $num_row => &$row) {
            foreach ($row as $format => &$languages) {
                //Literal field
                if (is_int($format)) {
                    continue;
                }

                $format = $this->getFormat($format);

                foreach ($languages as $language => &$fields) {
                    $fields = $format->valueDB($this->Db, $fields, $language, $ids[$num_row]);

                    if ($fields === false) {
                        unset($languages[$language]);
                    }
                }
            }
        }

        return $values;
    }

    /**
     * public function valueForm (array $values)
     *
     * Execute de format valueForm function
     *
     * return boolean
     */
    public function valueForm ($values)
    {
        foreach ($values as $num_row => &$row) {
            foreach ($row as $format => &$languages) {
                //Literal field
                if (is_int($format)) {
                    continue;
                }

                $format = $this->getFormat($format);

                foreach ($languages as $language => &$fields) {
                    $fields = $format->valueForm($fields);
                }
            }
        }

        return $values;
    }

    /**
     * public function valueHtml (array $values)
     *
     * Execute de format valueForm function
     *
     * return boolean
     */
    public function valueHtml ($values)
    {
        foreach ($values as $num_row => &$row) {
            foreach ($row as $format => &$languages) {
                //Literal field
                if (is_int($format)) {
                    continue;
                }

                $format = $this->getFormat($format);

                foreach ($languages as $language => &$fields) {
                    $fields = $format->valueHtml($fields);
                }
            }
        }

        return $values;
    }

    /**
     * public function getFormatSettings (string $field, [string $name], [string $compare])
     *
     * return mixed/false
     */
    public function getFormatSettings ($field, $name = '', $compare = '')
    {
        $field = $this->fieldInfo($field);
        $format = $this->getFormat($field['field']);

        $settings = $format->settings[$field['subformat']];

        if (empty($settings)) {
            return false;
        }

        if ($name) {
            if ($compare) {
                return ($settings[$name] === $compare) ? true : false;
            }

            return $settings[$name];
        }

        return $settings;
    }

    /**
     * public function setFormatSettings (string $field, array $settings, [string/array $subformat = ''])
     *
     * return boolean
     */
    public function setFormatSettings ($field, $settings, $subformat = '')
    {
        $format = $this->getFormat($field);

        if (empty($format) || !is_array($settings)) {
            return false;
        }

        if (empty($subformat)) {
            $subformat = array_keys($format->settings);
        }

        foreach ((array) $subformat as $subformat_value) {
            if (is_array($format->settings[$subformat_value])) {
                $format->settings[$subformat_value] = array_merge($format->settings[$subformat_value], $settings);
            }
        }

        return true;
    }

    /**
     * public function getRelations ()
     *
     * return array
     */
    public function getRelations ()
    {
        if (empty($this->relations)) {
            return array();
        }

        return $this->relations;
    }

    /**
     * public function getRelation (string $table, [string $name], [string $direction])
     *
     * return false/object
     */
    public function getRelation ($table, $name = '', $direction = '')
    {
        if (empty($table)) {
            return false;
        }

        $rel_name = $this->tableString($table, '', $name, $direction);

        return $this->relations[$rel_name];
    }

    /**
     * public function related (string $table, [string $name], [string $direction])
     *
     * return boolean
     */
    public function related ($table, $name = '', $direction = '')
    {
        if (empty($table)) {
            return false;
        }

        $rel_name = $this->tableString($table, '', $name, $direction);

        return is_object($this->relations[$rel_name]);
    }

    /**
     * private function setFormatsConfig ()
     *
     * return none
     */
    private function setFormatsConfig ()
    {
        if ($this->processed['formats']) {
            return true;
        }

        $this->formats = $this->fields = array();

        //Id format is required in all tables
        $this->formats['id'] = new \ANS\PHPCan\Data\Formats\Id($this->table, 'id', $this->Db->languages);
        $this->fields['id'] = array(
            'format' => 'id',
            'format_type' => 'id',
            'subformat' => '',
            'field' => 'id',
            'language' => 'default',
            'real_field' => 'id',
            'default_language' => 0
        );

        //Set remaining formats
        foreach ($this->settings['table'] as $field => $settings) {
            if ($field === 'id') {
                continue;
            }

            if (!is_array($settings)) {
                $settings = array('format' => $settings);
            }

            $class_name = '\\ANS\\PHPCan\\Data\\Formats\\'.ucfirst($settings['format']);

            $this->formats[$field] = new $class_name($this->table, $field, $this->Db->languages, $settings);
        }

        //Remove table settings
        unset($this->settings['table']);

        $this->processed['formats'] = true;
    }

    /**
     * private function setRelationsConfig ()
     *
     * return none
     */
    private function setRelationsConfig ()
    {
        if ($this->processed['relations']) {
            return true;
        }

        $this->relations = array();

        foreach ((array) $this->settings['relations'] as $name => $settings) {
            $class_name = '\\ANS\\PHPCan\\Data\\Relations\\Relation_'.str_replace(' ', '_', $settings['mode']);
            $this->relations[$name] = new $class_name($this->Db, $settings);
        }

        //Remove relations settings
        unset($this->settings['relations']);

        $this->processed['relations'] = true;
    }
}
