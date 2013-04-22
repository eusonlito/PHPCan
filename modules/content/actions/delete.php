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

$Vars->set('id', explodeTrim(',', $Vars->get('id')));

$ok = $Content->delete(array(
    'table' => $Vars->get('table'),
    'id' => $Vars->get('id')
));

if ($ok) {
    $Vars->message(__('The rows has been deleted successfully'), 'success');
    redirect(path(true, true, 'list').get('q', $Vars->get('q')));
}

$Vars->message(__('There was an error deleting the row %s in the table %s', $Vars->get('id'), $Vars->get('table')), 'error');

return false;
