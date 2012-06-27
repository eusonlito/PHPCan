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

class Enum extends Formats implements Iformats
{
    public $format = 'enum';

    public function check ($value)
    {
        $this->error = array();

        return $this->validate($value);
    }

    public function settings ($settings)
    {
        $this->settings = $this->setSettings($settings, array(
            '' => array(
                'db_type' => 'enum',
                'db_values' => $settings['values'],
                'db_null' => true,

                'values' => array(),
            )
        ));

        return $this->settings;
    }
}
