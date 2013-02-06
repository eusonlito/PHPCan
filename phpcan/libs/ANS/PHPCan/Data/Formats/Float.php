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

class Float extends Formats implements Iformats
{
    public $format = 'float';

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
        return array('' => str_replace(',', '.', $value['']));
    }

    public function settings ($settings)
    {
        $this->settings = $this->setSettings($settings, array(
            '' => array(
                'db_type' => 'float',

                'length_min' => '0',
                'length_max' => '8,2',
                'unsigned' => true,
                'default' => '0.00',
                'value_min' => '0.00',
                'value_max' => '99999999.99'
            )
        ));

        $settings = $this->settings[''];

        list($integer, $decimal) = explode(',', $settings['length_max']);

        $settings['default'] = '0.'.str_repeat('0', $decimal);
        $settings['db_default'] = $settings['default'];

        if ($settings['unsigned']) {
            $settings['value_min'] = 0;
        } else {
            $settings['value_min'] = '-'.str_repeat('9', $integer);
            $settings['value_min'] .= '.'.str_repeat('9', $decimal);
        }

        $settings['value_max'] = str_repeat('9', $integer);
        $settings['value_max'] .= '.'.str_repeat('9', $decimal);

        return $this->settings[''] = $settings;
    }
}
