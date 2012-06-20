<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

defined('ANS') or die();

echo $Form->textarea(array(
    'variable' => $info['varname'].'[0][code]',
    'value' => $info['data']['code'],
    'id' => $info['id_field'],
    'class' => 'code f100'
));

echo $Form->text(array(
    'variable' => $info['varname'].'[0][filename]',
    'value' => $info['data']['filename'],
    'class' => 'f50',
    'error' => $info['error'][''],
    'label_text' => __('File name')
));

echo $Form->button(array(
    'text' => __('HTML Preview'),
    'rel' => $info['id_field'],
    'class' => 'preview',
    'data-icon' => 'search'
));
