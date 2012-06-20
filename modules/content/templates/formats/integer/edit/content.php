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

echo $Form->number(array(
    'variable' => $info['varname'].'[0]',
    'id' => $info['id_field'],
    'value' => $info['data'][''],
    'required' => $Table->getFormatSettings($info['field'], 'required'),
    'min' => $Table->getFormatSettings($info['field'], 'value_min'),
    'max' => $Table->getFormatSettings($info['field'], 'value_max'),
    'error' => $info['error']['']
));
