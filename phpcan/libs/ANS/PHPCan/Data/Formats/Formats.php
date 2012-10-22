<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

namespace ANS\PHPCan\Data\Formats;

defined('ANS') or die();

abstract class Formats
{
    public $name;
    public $table;
    public $format;
    public $settings;
    public $languages = array();
    public $default_language;
    public $fields;
    public $subformats = array();

    protected $error;
    protected $Debug;

    /**
     * public function __construct (string $table, string $field, $languages, [array $settings])
     *
     * return none
     */
    public function __construct ($table, $field, $languages, $settings = array())
    {
        global $Debug;

        $this->Debug = $Debug;
        $this->table = $table;
        $this->name = $field;

        $this->languageSettings($languages, $settings['languages'], $settings['default_language']);

        $this->settings($settings);

        foreach ($this->languages as $language) {
            $language_suffix = $language ? '-'.$language : '';

            foreach ($this->settings as $subformat => $settings) {
                $subformat_suffix = $subformat ? '-'.$subformat : '';

                $this->fields[$language][$subformat] = $this->name.$language_suffix.$subformat_suffix;
            }
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

        $Events->bind('formats.'.$this->table.'.'.$this->name, $event_names, $function);
    }

    /**
     * public function bindEvent (string/array $event_names)
     *
     * return boolean
     */
    public function unbindEvent ($event_names)
    {
        global $Events;

        $Events->unbind('formats.'.$this->table.'.'.$this->name);
    }

    /**
     * public function getField ([string $subformat], [string $language])
     *
     * return string
     */
    public function getField ($subformat = '', $language = 'all')
    {
        $field = $this->fields[$language] ? $this->fields[$language] : $this->fields[$this->default_language];

        return $field[$subformat] ? $field[$subformat] : current($field);
    }

    /**
     * public function getFields ([string $fields], [string $language])
     *
     * return array
     */
    public function getFields ($fields = '', $language = 'all')
    {
        if ($language) {
            if ($language === 'default') {
                $language = $this->default_language;
            } else if ($language !== 'all') {
                $language = in_array($language, $this->languages) ? $language : $this->default_language;
            }
        } else {
            $language = $this->default_language;
        }

        $pieces = explode('-', $fields, 3);

        switch (count($pieces)) {
            case 0:
            case 1:
                $return = array();

                if ($language === 'all') {
                    foreach ($this->fields as $lang => $subformats) {
                        foreach ($subformats as $subformat => $field) {
                            $return[$field] = array(
                                'language' => $lang,
                                'subformat' => $subformat
                            );
                        }
                    }

                    return $return;
                }

                foreach ($this->fields[$language] as $subformat => $field) {
                    $return[$field] = array(
                        'language' => $language,
                        'subformat' => $subformat
                    );
                }

                return $return;

            case 2:
                $return = array();

                if ($this->fields[$pieces[1]]) {
                    foreach ($this->fields[$pieces[1]] as $subformat => $field) {
                        $return[$field] = array(
                            'language' => $pieces[1],
                            'subformat' => $subformat
                        );
                    }

                    return $return;
                }

                if ($language === 'all') {
                    foreach ($this->fields as $lang => $subformats) {
                        $return[$subformats[$pieces[1]]] = array(
                            'language' => $lang,
                            'subformat' => $pieces[1]
                        );
                    }

                    return $return;
                }

                return array(
                    $this->fields[$language][$pieces[1]] => array(
                        'language' => $language,
                        'subformat' => $pieces[1]
                    )
                );

            case 3:
                if ($this->fields[$pieces[1]][$pieces[2]]) {
                    return array(
                        $this->fields[$pieces[1]][$pieces[2]] => array(
                            'language' => $pieces[1],
                            'subformat' => $pieces[2]
                        )
                    );
                } else {
                    $this->Debug->error(__('Field "%s" doesn\'t exist in this format', $this->name));

                    return false;
                }
        }
    }

    /**
     * public function getErrors ()
     *
     * Return an array with the errors
     *
     * return array
     */
    public function getErrors ()
    {
        return (array) $this->error;
    }

    /**
     * public function getDefaultValues ()
     *
     * return mixed/array
     */
    public function getDefaultValues ()
    {
        $return = array();

        foreach ($this->settings as $subformat => $settings) {
            $return[$subformat] = $settings['default'];
        }

        return $this->fixValue($return);
    }

    /**
    * public function fixValue ($value)
    *
    * return array
    */
    public function fixValue ($value)
    {
        return $return;
    }

    /**
     * protected function validate (string $name, string $value, array $requirements)
     *
     * Validate a value with a list of requirements
     *
     * return boolean
     */
    protected function validate ($values)
    {
        if (!is_array($values)) {
            $this->error = __('Field "%s" has not valid values', __($this->name));
            return false;
        }

        foreach ($this->settings as $subformat => $settings) {
            $value = $values[$subformat];

            foreach ($settings as $settings_name => $settings_value) {
                if (!$settings_value) {
                    continue;
                }

                switch ($settings_name) {
                    case 'required':
                        if (empty($value)) {
                            $this->error[$subformat] = __('Field "%s" can not be empty', __($this->name));
                            continue 2;
                        }
                        break;

                    case 'unsigned':
                        if (intval($value) < 0) {
                            $this->error[$subformat] = __('Field "%s" is not a valid positive number.', __($this->name));
                            continue 2;
                        }
                        break;

                    case 'length_max':
                        if (strpos($settings_value, ',')) {
                            $settings_value = intval($settings_value);
                            $value = intval($value);
                        }

                        if (strlen($value) > $settings_value) {
                            $this->error[$subformat] = __('Field "%s" should has less than %s characters', __($this->name), $settings_value);
                            continue 2;
                        }
                        break;

                    case 'length_min':
                        if (!$settings['required'] && empty($value)) {
                            break;
                        } elseif (strlen($value) < $settings_value) {
                            $this->error[$subformat] = __('Field "%s" should has more than %s characters', __($this->name), $settings_value);
                            continue 2;
                        }
                        break;

                    case 'value_max':
                        if (intval($value) > $settings_value) {
                            $this->error[$subformat] = __('The max value for field "%s" is %s', __($this->name), $settings_value);
                            continue 2;;
                        }
                        break;

                    case 'value_min':
                        if (intval($value) < $settings_value) {
                            $this->error[$subformat] = __('The min value for field "%s" is %s', __($this->name), $settings_value);
                            continue 2;
                        }
                        break;

                    case 'pattern':
                        if ($settings['required'] && !preg_match($settings_value, $value)) {
                            $this->error[$subformat] = __('Field "%s" is not a valid value', __($this->name));
                            continue 2;
                        }
                        break;

                    case 'db_type':
                        switch ($settings_value) {
                            case 'tinyint':
                            case 'smallint':
                            case 'mediumint':
                            case 'integer':
                            case 'bigint':
                                if (!preg_match('/^-?[0-9]*$/', $value)) {
                                    $this->error[$subformat] = __('Field "%s" is not a valid number.', __($this->name));
                                    continue 2;
                                }
                                break;

                            case 'boolean':
                            case 'bool':
                                if (!is_bool($value) && !is_null($value) && is_null(filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE))) {
                                    $this->error[$subformat] = __('Field "%s" is not valid to a boolean', __($this->name));
                                    continue 2;
                                }
                                break;

                            case 'float':
                            case 'decimal':
                            case 'double':
                                if (!preg_match('/^[+-]?[0-9]*[,\.]?[0-9]*$/', $value)) {
                                    $this->error[$subformat] = __('Field "%s" is not a valid float number.', __($this->name));
                                    continue 2;
                                }
                                break;

                            case 'date':
                                if (!preg_match('#^[0-9]{1,4}[/-][0-9]{1,2}[/-][0-9]{1,4}$#', $value)) {
                                    $this->error[$subformat] = __('Field "%s" is not a valid date.', __($this->name));
                                    continue 2;
                                }
                                break;

                            case 'datetime':
                                if (!preg_match('#^[0-9]{1,4}[/-][0-9]{1,2}[/-][0-9]{1,4} [0-9]{1,2}:[0-9]{1,2}:[0-9]{1,2}$#', $value)) {
                                    $this->error[$subformat] = __('Field "%s" is not a valid time and date.', __($this->name));
                                    continue 2;
                                }
                                break;

                            case 'enum':
                                if (strlen($value) && !in_array($value, $settings['values'])) {
                                    $this->error[$subformat] = __('Field "%s" is not a valid value', __($this->name));
                                    continue 2;
                                }
                                break;
                        }
                        break;
                }
            }
        }

        return $this->error ? false : true;
    }

    /**
     * public function valueDB (array $value, object $Db, array $id)
     *
     * Convert the value of this format before save in DB
     *
     * return array
     */
    public function valueDB (\ANS\PHPCan\Data\Db $Db, $value, $language = '', $id = 0)
    {
        $array = array();

        foreach ($this->settings as $subformat => $setting) {
            switch ($setting['db_type']) {
                case 'tinyint':
                case 'smallint':
                case 'mediumint':
                case 'integer':
                case 'bigint':
                case 'bool':
                    $array[$subformat] = intval($value[$subformat]);
                    break;

                case 'float':
                case 'double':
                    $array[$subformat] = floatval($value[$subformat]);
                    break;

                default:
                    $array[$subformat] = $value[$subformat];
                    break;
            }
        }

        return $array;
    }

    /**
     * public function valueForm (array $value)
     *
     * Convert the value of this format before show in a form
     *
     * return array
     */
    public function valueForm ($value)
    {
        return $value;
    }

    /**
     * public function valueHTML (array $value)
     *
     * Convert the value of this format before show in the html
     *
     * return array
     */
    public function valueHTML ($value)
    {
        return $value;
    }

    /**
     * public function explodeData (mixed $value, $subformat = '')
     *
     * Convert the value of this format to an array with subformats
     *
     * return array
     */
    public function explodeData ($value, $subformat = '')
    {
        $return = array();

        if (is_array($value)) {
            foreach ($this->subformats as $subformat) {
                if (array_key_exists($subformat, $value)) {
                    $return[$subformat] = $value[$subformat];
                }
            }

            return $return;
        }

        reset($this->settings);

        $subformat = key($this->settings);

        return array($subformat => $value);
    }

    /**
     * protected function setSettings (array $custom_settings, array $default_settings)
     *
     * Set basic settings to this format
     *
     * return array
     */
    protected function setSettings ($custom_settings, $default_settings)
    {
        $new_settings = array();

        //Common settings
        $common_settings = array(
            'previous_fields' => '',
            'index' => '',
            'unique' => '',
            'fulltext' => '',
            'required' => '',
        );

        //Process settings
        foreach ($default_settings as $name => $value) {
            $current_settings = array();

            $value += $common_settings;

            foreach ($value as $k => $v) {
                switch ($k) {
                    //Settings which affect db settings
                    case 'length_max':
                    case 'unsigned':
                    case 'index':
                    case 'unique':
                    case 'fulltext':
                    case 'default':
                        $current_settings[$k] = isset($custom_settings[$k]) ? $custom_settings[$k] : $v;
                        $current_settings['db_'.$k] = $current_settings[$k];
                        break;

                    //Other settings
                    default:
                        $current_settings[$k] = isset($custom_settings[$k]) ? $custom_settings[$k] : $v;
                }
            }

            //Save settings
            $new_settings[$name] = $current_settings;
            $this->subformats[] = $name;
        }

        return $new_settings;
    }

    /**
     * private function languageSettings (array $all_languages, array $languages, string $default_language)
     *
     * Define the availables and default language of the format
     *
     * return none
     */
    private function languageSettings ($all_languages, $languages, $default)
    {
        if ($languages) {
            if ($languages === 'all') {
                $languages = $all_languages;
            } else {
                $languages = (array) $languages;

                foreach ($languages as $key => $language) {
                    if (!in_array($language, $all_languages)) {
                        unset($languages[$key]);
                    }
                }
            }

            if (!$default || !in_array($default, $languages)) {
                $default = $languages[0];
            }
        }

        if ($languages) {
            $this->languages = $languages;
            $this->default_language = $default;
        } else {
            $this->languages = array('');
            $this->default_language = '';
        }
    }
}
