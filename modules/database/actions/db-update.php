<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

defined('ANS') or die();

$queries = $Db->updateDB(false);
$selected = $Vars->arr('selected');
$error = false;
$executed = array();

if (!$queries || !$selected) {
    return $executed;
}

foreach ($queries as $query) {
    $key = md5($query);

    if ($selected[$key]) {
        $executed[$key] = array(
            'query' => $query,
            'key' => $key
        );

        if ($Db->Database->query($query) === false) {
            $executed[$key]['error'] = 1;
            $error = true;
        }
    }
}

if ($error) {
    $Vars->message(__('There were errors updating database'), 'error');
} else {
    $Vars->message(__('The database was updated successfully'), 'success');
}

return $executed;
