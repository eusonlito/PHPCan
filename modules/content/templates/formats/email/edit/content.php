<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

defined('ANS') or die();

$Table = $Content->Db->getTable($info['table']);

echo $Form->email(array(
    'variable' => $info['varname'].'[0]',
    'value' => $info['data'][''],
    'id' => $info['id_field'],
    'required' => $Table->getFormatSettings($info['field'], 'required'),
    'error' => $info['error'][''],
    'class' => 'f50'
));
