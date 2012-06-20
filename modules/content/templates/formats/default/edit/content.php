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

foreach ($info['data'] as $subfield => $value) {
    echo $Form->text(array(
        'variable' => $info['varname'].'['.($subfield ? $subfield : '0').']',
        'id' => $info['id_field'].$subfield,
        'lang' => $info['language'],
        'value' => $value,
        'class' => 'f100',
        'label_text' => $subfield,
        'label_class' => 'subformat f100',
        'required' => $Table->getFormatSettings($info['field'], 'required'),
        'error' => $info['error'][$subfield]
    ));
}
