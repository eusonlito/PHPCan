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

$Data->set('list', $Content->selectRelations(array(
    'table' => $Data->table,
    'fields' => $Data->selected_columns[$Data->table],
    'search' => $Vars->str('q'),
    'page' => $Vars->int('page'),
    'sort' => $Vars->str('phpcan_sortfield'),
    'sort_direction' => $Vars->str('phpcan_sortdirection'),
    'relation' => $Vars->get('relation'),
    'relation_id' => $Vars->get('relation_id'),
    'all' => $Vars->bool('all')
)));

$relation_info = $Content->Db->tableArray($Vars->get('relation'));
$relation_table = $Content->Db->getTable($relation_info['realname']);
$relation = $relation_table->getRelation($Data->table, $relation_info['name'], $relation_info['direction']);

$relation = $relation->settings;

$Data->set('table_info', array(
    'title' => $Content->info($Vars->get('connection'), 'name', $relation['tables'][1]),
    'description' => $Content->info($Vars->get('connection'), 'description', $relation['tables'][1]),

    'relation_direction' => $relation['direction'][1],
    'relation_name' => $relation['name'],
    'relation_table' => $Content->info($Vars->get('connection'), 'name', $relation['tables'][0]),
));
