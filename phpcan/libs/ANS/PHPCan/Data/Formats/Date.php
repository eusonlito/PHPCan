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

class Date extends Formats implements Iformats
{
    public $format = 'date';

    protected function validDate ($date, $format = 'Y-m-d')
    {
        $this->error = array();

        if ($date && !is_string($date)) {
            $this->error[''] = __('Field "%s" is not a valid date.', __($this->name));

            return false;
        }

        if (empty($date) || preg_match('#^0{1,4}[/\.-]0{1,4}[/\.-]0{1,4}#', $date)) {
            return preg_replace('/[0-9]/', 0, date($format, 0));
        }

        if (!preg_match('#^[0-9]{1,4}[/\.-][0-9]{1,4}[/\.-][0-9]{1,4}$#', $date)) {
            $this->error[''] = __('Field "%s" is not a valid date.', __($this->name));

            return false;
        }

        if (!($new_date = strtotime($date))) {
            $this->error[''] = __('Field "%s" is not a valid date.', __($this->name));

            return false;
        }

        return date($format, $new_date);
    }

    public function check ($value)
    {
        $this->error = array();

        if (!($new_value = $this->validDate($value['']))) {
            $this->error[''] = __('Field "%s" is not a valid date.', __($this->name));

            return false;
        }

        if (($new_value === '0000-00-00') && $this->settings['']['required']) {
            $this->error[''] = __('Field "%s" can not be empty', __($this->name));

            return false;
        }

        return true;
    }

    public function valueDB (\ANS\PHPCan\Data\Db $Db, $value, $language = '', $id = 0)
    {
        return $this->fixValue($value);
    }

    public function fixValue ($value)
    {
        return array('' => $this->validDate($value['']));
    }

    public function valueForm ($value)
    {
        return array('' => $this->validDate($value[''], $this->settings['']['date_format']));
    }

    public function valueHtml ($value)
    {
        return array('' => $this->validDate($value[''], $this->settings['']['date_format']));
    }

    public function settings ($settings)
    {
        $this->settings = $this->setSettings($settings, array(
            '' => array(
                'db_type' => 'date',

                'date_format' => 'd-m-Y',
                'default' => '0000-00-00',
            )
        ));

        $this->settings['']['db_default'] = '0000-00-00';

        return $this->settings;
    }
}
