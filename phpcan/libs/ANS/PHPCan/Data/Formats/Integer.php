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

class Integer extends Formats implements Iformats
{
    public $format = 'integer';

    public function check ($value)
    {
        $this->error = array();

        return $this->validate($value);
    }

    public function settings ($settings)
    {
        $this->settings = $this->setSettings($settings, array(
            '' => array(
                'db_type' => 'integer',

                'value_min' => 0,
                'value_max' => 4294967295,
                'length_max' => 10,
                'unsigned' => true
            )
        ));

        if ($this->settings['']['value_max'] <= 255) {
            $this->settings['']['db_type'] = 'tinyint';
            $this->settings['']['max_value'] = 255;
            $this->settings['']['length_max'] = 3;
        }

        return $this->settings;
    }
}
