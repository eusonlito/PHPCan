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

        return $this->validate($this->setDecimals($value));
    }

    public function valueDB (\ANS\PHPCan\Data\Db $Db, $value, $language = '', $id = 0)
    {
        return $this->setDecimals($value);
    }

    public function setDecimals ($value)
    {
        list($max, $dec) = explode(',', $this->settings['x']['length_max']);

        $value['x'] = round($value['x'], $dec);

        list($max, $dec) = explode(',', $this->settings['y']['length_max']);

        $value['y'] = round($value['y'], $dec);

        return $value;
    }

    public function settings ($settings)
    {
        $this->settings = $this->setSettings($settings, array(
            'x' => array(
                'db_type' => 'decimal',

                'length_max' => '16,13',
                'value_max' => 180,
                'value_min' => -180,
                'default' => 0
            ),
            'y' => array(
                'db_type' => 'decimal',

                'length_max' => '16,13',
                'value_max' => 180,
                'value_min' => -180,
                'default' => 0
            ),
            'z' => array(
                'db_type' => 'tinyint',

                'unsigned' => true,
                'length_max' => 2,
                'value_max' => 21,
                'default' => 5
            )
        ));

        foreach ($this->settings as &$settings) {
            if (strstr($settings['length_max'], ',') === false) {
                continue;
            }

            list($integer, $decimal) = explode(',', $settings['length_max']);

            $settings['db_default'] = '0.'.str_repeat('0', $decimal);
        }

        unset($settings);

        return $this->settings;
    }
}
