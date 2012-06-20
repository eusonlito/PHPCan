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

$ok = $Content->saveEdit($Vars->arr('edit'), $Vars->arr('overwrite_control'));

if ($ok) {
    $Vars->message(__('Data has been saved succesfully!'), 'success');

    if ($ok['insert']) {
        redirect(path(true, true, implode(',', $ok['insert']), 'edit').get());
    }

    return true;
}

$Vars->message(__('Ops, there was an error saving data: <p>%s</p>', implode('</p><p>', $Errors->getList())), 'error');

return false;
