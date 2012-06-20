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

$list = $Content->selectList(array(
    'table' => $Data->table,
    'fields' => $Data->selected_columns[$Data->table],
    'limit' => -1,
    'search' => $Vars->str('q'),
    'sort' => $Vars->str('phpcan_sortfield'),
    'sort_direction' => $Vars->str('phpcan_sortdirection'),
));

$csv = '';
$cnt_tables = count($list['head']);

foreach ($list['head'] as $tables) {
    foreach ($tables['data'] as $fields) {
        if ($cnt_tables === 1) {
            $csv .= $fields['title'].';';
        } else {
            $csv .= $fields['title'].' ('.$tables['title'].');';
        }
    }

    $csv = substr($csv, 0, -1)."\n";
}

foreach ($list['body'] as $index => $row) {
    foreach ($row['data'] as $field) {
        foreach ($field as $info) {
            $csv .= '"'.str_replace('"', '""', implode(',', $info['data'])).'";';
        }
    }

    $csv = substr($csv, 0, -1)."\n";
}

header('Pragma: private');
header('Expires: 0');
header('Cache-control: private, must-revalidate');
header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Type: application/force-download');
header('Content-Transfer-Encoding: binary');
header('Content-Disposition: attachment; filename="'.format($Data->table).'.csv"');
header('Content-Length: '.strlen($csv));

echo $csv;

exit;
