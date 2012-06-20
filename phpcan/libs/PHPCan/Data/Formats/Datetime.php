<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

namespace PHPCan\Data\Formats;

defined('ANS') or die();

class Datetime extends Formats implements Iformats
{
    public $format = 'datetime';

    protected function validDateTime ($datetime, $format = 'Y-m-d H:i:s')
    {
        if ($datetime && !is_string($datetime)) {
            $this->error[''] = __('Field "%s" is not a valid date.', __($this->name));

            return false;
        }

        if (empty($datetime) || preg_match('#^0{1,4}[/\.-]0{1,4}[/\.-]0{1,4}#', $datetime)) {
            return preg_replace('/[0-9]/', 0, date($format, 0));
        }

        if (!preg_match('#^[0-9]{1,4}[/\.-][0-9]{1,4}[/\.-][0-9]{1,4} [0-9]{1,2}:[0-9]{1,2}:[0-9]{1,2}#', $datetime)) {
            $this->error[''] = __('Field "%s" is not a valid date.', __($this->name));

            return false;
        }

        if (!($new_datetime = strtotime($datetime))) {
            $this->error[''] = __('Field "%s" is not a valid date.', __($this->name));

            return false;
        }

        return date($format, $new_datetime);
    }

    public function check ($value)
    {
        $this->error = array();

        if (!($new_value = $this->validDateTime($value['']))) {
            $this->error[''] = __('Field "%s" is not a valid date and time.', __($this->name));

            return false;
        }

        if (($new_value == '0000-00-00 00:00:00') && $this->settings['']['required']) {
            $this->error[''] = __('Field "%s" can not be empty', __($this->name));

            return false;
        }

        return true;
    }

    public function valueDB (\PHPCan\Data\Db $Db, $value, $language = '', $id = 0)
    {
        return $this->fixValue($value);
    }

    public function fixValue ($value)
    {
        return array('' => $this->validDateTime($value['']));
    }

    public function valueForm ($value)
    {
        return array('' => $this->validDateTime($value[''], $this->settings['']['date_format']));
    }

    public function valueHtml ($value)
    {
        return array('' => $this->validDateTime($value[''], $this->settings['']['date_format']));
    }

    public function settings ($settings)
    {
        $this->settings = $this->setSettings($settings, array(
            '' => array(
                'db_type' => 'datetime',

                'default' => '0000-00-00 00:00:00',
                'date_format' => 'd-m-Y H:i:s',
            )
        ));

        $this->settings['']['db_default'] = '0000-00-00 00:00:00';

        return $this->settings;
    }
}
