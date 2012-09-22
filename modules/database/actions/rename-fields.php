<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

defined('ANS') or die();

$executed = array();
$error = false;
$fields = (array) $Vars->var['fields'];

$Config->db[$Db->getConnection()]['query_register'] = true;

foreach ($fields as $fields_index => $fields_value) {
    if (!$fields_value['table']
    || !$fields_value['current']
    || !$fields_value['target']
    || !$fields_value['format']) {
        $executed[$fields_index]['error'] = __('Some required fields are empty');
        $error = true;
        continue;
    }

    if ($fields_value['current'] == $fields_value['target']) {
        $executed[$fields_index]['error'] = __('Current a target field are equal.');
        $error = true;
        continue;
    }

    if (!$Db->tableExists($fields_value['table'])) {
        $executed[$fields_index]['error'] = __('Table %s seems doesn\'t exists', $fields_value['table']);
        $error = true;

        continue;
    }

    $ok = $Db->renameField($fields_value['table'], $fields_value['current'], $fields_value['target'], $fields_value['format']);

    $last = $Db->queryRegister(-1, 1);

    if (is_array($last)) {
        $last = array_values($last);
        $executed[$fields_index]['query'] = $last[0]['query'];
    }

    if (!$ok) {
        $last = array_values($Errors->getList(null, -1, 1));
        $executed[$fields_index]['error'] = __('"%s" update can not be applied: %s', $fields_value['current'], $last[0]);
        $error = true;
    }
}

if ($error) {
    $Vars->message(__('There are errors updating database'), 'error');
} else {
    $Vars->message(__('The database was updated successfully'), 'success');
}

return true;
