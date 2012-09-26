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

class Gmaps extends Formats implements Iformats
{
    public $format = 'gmaps';

    public function explodeData ($value, $subformat = '')
    {
        if (!is_array($value)) {
            return $value;
        }

        return parent::explodeData($value, $subformat = '');
    }

    public function check ($value)
    {
        $this->error = array();

        if ($value && !is_array($value)) {
            $this->error[''] = __('Field "%s" is not a valid Google Maps point.', __($this->name));

            return false;
        }

        return $this->validate($value);
    }

    public function settings ($settings)
    {
        $this->settings = $this->setSettings($settings, array(
            'x' => array(
                'db_type' => 'decimal',

                'length_max' => '17,15',
                'value_max' => 180,
                'value_min' => -180,
                'default' => '0.000000000000000',
            ),
            'y' => array(
                'db_type' => 'decimal',

                'length_max' => '17,15',
                'value_max' => 90,
                'value_min' => -90,
                'default' => '0.000000000000000',
            ),
            'z' => array(
                'db_type' => 'tinyint',

                'default' => 5,
                'unsigned' => true,
                'length_max' => 2,
                'value_max' => 21,
                'default' => 5
            )
        ));

        return $this->settings;
    }
}
