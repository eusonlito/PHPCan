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

class Html extends Formats implements Iformats
{
    public $format = 'html';

    public function check ($value)
    {
        $this->error = array();

        return $this->validate($value);
    }

    public function valueForm ($value)
    {
        return array(
            '' => str_replace("\n", '', $value[''])
        );
    }

    public function valueDB (\PHPCan\Data\Db $Db, $value, $language = '', $id = 0)
    {
        return $this->fixValue($value);
    }

    public function fixValue ($value)
    {
        return array('' => xssClean($value['']));
    }

    public function settings ($settings)
    {
        $this->settings = $this->setSettings($settings, array(
            '' => array(
                'db_type' => 'text',

                'length_max' => ''
            )
        ));

        return $this->settings;
    }
}
