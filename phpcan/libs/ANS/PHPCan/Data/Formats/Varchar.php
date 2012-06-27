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

class Varchar extends Formats implements Iformats
{
    public $format = 'varchar';

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
        return array('' => strip_tags($value['']));
    }

    public function settings ($settings)
    {
        $this->settings = $this->setSettings($settings, array(
            '' => array(
                'db_type' => 'varchar',

                'length_max' => 255,
                'length_min' => '',
                'pattern' => ''
            )
        ));

        return $this->settings;
    }
}
