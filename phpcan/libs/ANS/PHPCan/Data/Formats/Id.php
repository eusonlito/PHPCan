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

class Id extends Formats implements Iformats
{
    public $format = 'id';

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
                'db_incremental' => true,
                'unsigned' => true,
                'key' => 'PRIMARY',

                'length_max' => 10
            )
        ));

        return $this->settings;
    }
}
