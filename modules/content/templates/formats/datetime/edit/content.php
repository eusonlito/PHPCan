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

echo $Form->text(array(
    'variable' => $info['varname'].'[0]',
    'class' => 'datetime',
    'value' => $info['data'][''],
    'id' => $info['id_field'],
    'required' => $Table->getFormatSettings($info['field'], 'required'),
    'error' => $info['error']['']
));
