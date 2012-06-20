<?php
/*
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

defined('ANS') or die();

$Data->execute('check.php', true);

$Vars->set('id', explodeTrim(',', $Vars->get('id')));

$Data->set('edit', $Content->selectEdit(array(
    'table' => $Vars->get('table'),
    'ids' => $Vars->get('id'),
    'relation' => $Vars->str('relation'),
    'relation_id' => $Vars->int('relation_id')
)));

if (!$Data->edit) {
    $Vars->message(__('There is not any register with id "%s"', implode(',', $Vars->get('id'))), 'error');
    redirect(path(true, true, 'list'));
}

$Data->set('table_info', array(
    'title' => $Content->info($Vars->get('connection'), 'name', $Vars->get('table')),
    'description' => $Content->info($Vars->get('connection'), 'description', $Vars->get('table'))
));
