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

class Text extends Formats implements Iformats
{
    public $format = 'text';

    public function check ($value)
    {
        $this->error = array();

        return $this->validate($value);
    }

    public function valueDB (\ANS\PHPCan\Data\Db $Db, $value, $language = '', $id = 0)
    {
        return $this->fixValue($value);
    }

    public function fixValue ($value)
    {
        return array('' => ($this->settings['']['raw'] ? $value[''] : strip_tags($value[''])));
    }

    public function settings ($settings)
    {
        $this->settings = $this->setSettings($settings, array(
            '' => array(
                'db_type' => 'text',

                'raw' => $settings['raw'],
                'length_max' => '',
                'length_min' => ''
            )
        ));

        unset($this->settings[$this->name]['db_length_max']);

        return $this->settings;
    }
}
