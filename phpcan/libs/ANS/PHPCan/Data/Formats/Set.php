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

class Set extends Formats implements Iformats
{
    public $format = 'set';

    public function explodeData ($value, $subformat = '')
    {
        return parent::explodeData(array($subformat => $value));
    }

    public function check ($value)
    {
        $this->error = array();

        return $this->validate($value);
    }

    public function valueDB (\ANS\PHPCan\Data\Db $Db, $value, $language = '', $id = 0)
    {
        return array('' => implode(',', $value['']));
    }

    public function valueForm ($value)
    {
        return array('' => explode(',', $value['']));
    }

    public function valueHtml ($value)
    {
        return $this->valueForm($value);
    }

    public function settings ($settings)
    {
        $this->settings = $this->setSettings($settings, array(
            '' => array(
                'db_type' => 'set',
                'db_values' => $settings['values'],

                'default' => ($settings['required'] ? $settings['values'][0] : ''),
                'values' => array(),
                'null' => true
            )
        ));

        return $this->settings;
    }
}
