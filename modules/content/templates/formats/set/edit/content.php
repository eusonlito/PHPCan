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
$settings = $Table->getFormatSettings($info['field']);

echo $Form->select(array(
	'options' => $settings['values'],
	'first_option' => ($settings['required'] ? null : ' '),
	'option_text_as_value' => true,
	'variable' => $info['varname'].'[0]',
	'value' => $info['data'][''],
	'id' => $info['id_field'],
	'required' => $settings['required'],
	'multiple' => 'multiple',
	'error' => $info['error']['']
));
