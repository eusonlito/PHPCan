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
        $stmin = -128;      // Tinyint signed min value
        $stmax = 127;       // Tinyint signed max value
        $stlen = 3;         // Tinyint signed length value
        $utmin = 0;         // Tinyint unsigned min value
        $utmax = 255;       // Tinyint unsigned max value
        $utlen = 3;         // Tinyint unsigned length value
        $ttype = 'tinyint'; // Tinyint type

        $simin = -2147483648; // Integer signed min value
        $simax = 2147483647;  // Integer signed max value
        $silen = 10;          // Integer signed length value
        $uimin = 0;           // Integer unsigned min value
        $uimax = 4294967295;  // Integer unsigned max value
        $uilen = 10;          // Integer unsigned length value
        $itype = 'integer';   // Integer type

        $sbmin = -9223372036854775808; // Bigint signed min value
        $sbmax = 9223372036854775807;  // Bigint signed max value
        $sblen = 19;                   // Bigint signed length value
        $ubmin = 0;                    // Bigint unsigned min value
        $ubmax = 18446744073709551615; // Bigint unsigned max value
        $ublen = 20;                   // Bigint unsigned length value
        $btype = 'bigint';             // Bigint type

        $svmin = '-'.str_repeat('9', 254); // Varchar signed min value
        $svmax = str_repeat('9', 255);     // Varchar signed max value
        $svlen = 254;                      // Varchar signed length value
        $uvmin = 0;                        // Varchar unsigned min value
        $uvmax = str_repeat('9', 255);     // Varchar unsigned max value
        $uvlen = 255;                      // Varchar unsigned length value
        $vtype = 'varchar';                // Varchar type

        $u = array_key_exists('unsigned', $settings) ? $settings['unsigned'] : true;

        $val = $settings['value_max'] ?: ($u ? $uimax : $simax);
        $len = intval($settings['length_max']) ?: ($u ? $uilen : $silen);

        $this->settings = $this->setSettings($settings, array(
            '' => array(
                'db_type' => $itype,

                'default' => 0,
                'value_min' => ($u ? $uimin : $simin),
                'value_max' => ($u ? $uimax : $simax),
                'length_max' => ($u ? $uilen : $silen),
                'unsigned' => true
            )
        ));

        if ($val) {
            if ($this->settings['']['unsigned'] === true) {
                $s = 'u';
                $t = ($val > $ubmax) ? 'v' : (($val > $uimax) ? 'b' : (($val > $utmax) ? 'i' : 't'));
            } else {
                $s = 's';
                $t = ($val > $sbmax) ? 'v' : (($val > $simax) ? 'b' : (($val > $stmax) ? 'i' : 't'));
            }

            $this->settings['']['length_max'] = ${$s.$t.'len'};
        } else {
            if ($this->settings['']['unsigned'] === true) {
                $s = 'u';
                $t = ($len > $ublen) ? 'v' : (($len > $uilen) ? 'b' : (($len > $utlen) ? 'i' : 't'));
            } else {
                $s = 's';
                $t = ($len > $sblen) ? 'v' : (($len > $silen) ? 'b' : (($len > $stlen) ? 'i' : 't'));
            }

            $this->settings['']['value_max'] = ${$s.$t.'max'};
        }

        $this->settings['']['db_type'] = ${$t.'type'};
        $this->settings['']['value_min'] = ${$s.$t.'min'};

        return $this->settings;
    }
}
