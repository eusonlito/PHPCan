<?php
defined('ANS') or die();

$tables = array();

foreach ($Config->tables as $tables) {
    foreach (array_keys($tables) as $table) {
        $tables[$table] = format($table);
    }
}

$actions = array(
    'rename' => __('Rename')
);

$formats = array(
    'varchar' => __('Varchar'),
    'text' => __('Text'),
    'integer' => __('Integer'),
    'float' => __('Float'),
    'boolean' => __('Boolean')
);

if ($Data->actions['rename-fields'] === null) {
    $Vars->var['fields'] = array(array('table' => '', 'current' => '', 'target' => '', 'format' => ''));
}
