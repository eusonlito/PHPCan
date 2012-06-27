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

class Ip extends Formats implements Iformats
{
    public $format = 'ip';

    public function check ($value)
    {
        $this->error = array();

        if ($value[''] && !is_string($value[''])) {
            $this->error[''] = __('Field "%s" is not a valid ip value', __($this->name));

            return false;
        }

        if (!$this->validate($value)) {
            return false;
        }

        if ($value[''] && filter_var($value[''], FILTER_VALIDATE_IP) === false) {
            $this->error[''] = __('Field "%s" is not a valid ip value', __($this->name));

            return false;
        }

        return true;
    }

    public function settings ($settings)
    {
        $this->settings = $this->setSettings($settings, array(
            '' => array(
                'db_type' => 'varchar',

                'length_max' => 39,
                'length_min' => '',
            )
        ));

        return $this->settings;
    }
}
