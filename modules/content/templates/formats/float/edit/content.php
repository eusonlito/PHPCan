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

list($integer, $decimal) = explode(',', $Table->getFormatSettings($info['field'], 'length_max'), 2);

echo $Form->number(array(
    'variable' => $info['varname'].'[0]',
    'value' => $info['data'][''],
    'id' => $info['id_field'],
    'required' => $Table->getFormatSettings($info['field'], 'required'),
    'min' => $Table->getFormatSettings($info['field'], 'value_min'),
    'max' => $Table->getFormatSettings($info['field'], 'value_max'),
    'step' => "0." . str_repeat("0", $decimal - 1) . "1",
    'error' => $info['error']['']
));
