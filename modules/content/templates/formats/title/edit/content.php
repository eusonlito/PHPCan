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
    'variable' => $info['varname'].'[title]',
    'value' => $info['data']['title'],
    'id' => $info['id_field'],
    'class' => 'f100',
    'lang' => $info['language'],
    'required' => $Table->getFormatSettings($info['field'], 'required'),
    'error' => $info['error']['']
));

echo '<small class="row">'.$info['data']['url'].'</small>';
