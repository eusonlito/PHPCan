<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

defined('ANS') or die();

echo $Form->password(array(
    'name' => $info['varname'].'[0]',
    'id' => $info['id_field'],
    'class' => 'f50',
    'autocomplete' => 'off',
    'error' => $info['error']['']
));
