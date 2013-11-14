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

$action = $Vars->get('action');

$ok = $Content->saveRelation(array(
    'action' => $action,
    'table' => $Vars->get('table'),
    'id' => explode(',', $Vars->get('id')),
    'relation' => $Vars->get('relation'),
    'relation_id' => $Vars->int('relation_id')
));

if ($ok) {
    if ($action === 'relate') {
        $Vars->message(__('The elements have been related succesfully!'), 'success');
    } else {
        $Vars->message(__('The elements have been unrelated succesfully!'), 'success');
    }

    return true;
}

if ($action === 'relate') {
    $Vars->message(__('Ops, there was an error relating the elements'), 'error');
} else {
    $Vars->message(__('Ops, there was an error unrelating the elements'), 'error');
}
