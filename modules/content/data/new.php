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

$Data->set('edit', $Content->selectNew(array(
    'table' => $Vars->get('table'),
    'relation' => $Vars->str('relation'),
    'relation_id' => $Vars->int('relation_id')
)));

$Data->set('table_info', array(
    'title' => $Content->info($Vars->get('connection'), 'name', $Vars->get('table')),
    'description' => $Content->info($Vars->get('connection'), 'description', $Vars->get('table'))
));
