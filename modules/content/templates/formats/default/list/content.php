<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

defined('ANS') or die();

if (count($info['data']) > 1) {
    foreach ($info['data'] as $field => $value) {
        echo '<strong>'.$field.':</strong> ';
        print_r($value);
        echo '<br />';
    }
} else {
    print_r(current($info['data']));
}
