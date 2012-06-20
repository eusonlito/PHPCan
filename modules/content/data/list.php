<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

defined('ANS') or die();

$Data->execute('check.php', true);

$Data->table = $Vars->get('table');
$Data->columns = array($Data->table => array_keys($Content->Db->getTable($Data->table)->formats));
$Data->selected_columns = $Content->checkSelectedFields($Vars->getCookie('phpcan_show_columns'), $Data->table);

$Data->set('list', $Content->selectList(array(
    'table' => $Data->table,
    'fields' => $Data->selected_columns[$Data->table],
    'search' => $Vars->str('q'),
    'page' => $Vars->int('page'),
    'sort' => $Vars->str('phpcan_sortfield'),
    'sort_direction' => $Vars->str('phpcan_sortdirection'),
)));

//Redirect to edit if there is just one result in the searching
if ($Vars->str('q') && count($Data->list['body']) == 1) {
    redirect(path(true, true, $Data->list['body'][0]['id'], 'edit'));
}

$Data->set('table_info', array(
    'title' => $Content->info($Vars->get('connection'), 'name', $Data->table),
    'description' => $Content->info($Vars->get('connection'), 'description', $Data->table)
));
