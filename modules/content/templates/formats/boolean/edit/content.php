<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

defined('ANS') or die();

echo $Form->checkbox(array(
    'variable' => $info['varname'].'[0]',
    'value' => 1,
    'checked' => $info['data'][''],
    'id' => $info['id_field'],
    'error' => $info['error']['']
));
