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

echo $Form->url(array(
    'variable' => $info['varname'].'[0]',
    'value' => $info['data'][''],
    'id' => $info['id_field'],
    'class' => 'f50',
    'required' => $Table->getFormatSettings($info['field'], 'required'),
    'error' => $info['error']['']
));

if ($info['data']['']) {
    echo $Html->a(array(
        'text' => __('Open url (new window)'),
        'href' => $info['data'][''],
        'target' => '_blank',
        'class' => 'link'
    ));
}
