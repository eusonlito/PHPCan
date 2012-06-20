<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

defined('ANS') or die();

if (!$Config->data) {
    return false;
}

//Load data files
foreach ($Config->data as $value) {
    if (false === include_once($Data->file($value))) {
        break;
    }
}
