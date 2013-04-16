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

class Email extends Formats implements Iformats
{
    public $format = 'email';

    public function check ($value)
    {
        $this->error = array();

        if ($value[''] && !is_string($value[''])) {
            $this->error[''] = __('Field "%s" is not a valid email.', __($this->name));

            return false;
        }

        if (!$this->validate($value)) {
            return false;
        }

        if (strlen($value['']) && !filter_var($value[''], FILTER_VALIDATE_EMAIL)) {
            $this->error[''] = __('Field "%s" is not a valid email.', __($this->name));

            return false;
        }

        return true;
    }

    public function settings ($settings)
    {
        $this->settings = $this->setSettings($settings, array(
            '' => array(
                'db_type' => 'varchar',

                'length_max' => 100
            )
        ));

        return $this->settings;
    }
}
