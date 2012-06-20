<?php
defined('ANS') or die();

$Data->set('queries', array());

$executed = $Data->actions['db-update'];

foreach ($Db->updateDB(false) as $query) {
    $key = md5($query);

    $Data->data['queries'][] = array(
        'query' => $query,
        'key' => $key,
        'status' => $executed[$key] ? ($executed[$key]['error'] ? 'error' : 'executed') : 'not-executed'
    );

    unset($executed[$key]);
}

if ($executed !== null) {
    foreach ($executed as $key => $query) {
        $Data->data['queries'][] = array(
            'query' => $query['query'],
            'key' => $key,
            'status' => $query['error'] ? 'error' : 'executed'
        );
    }
}

unset($executed);

if (!$Data->queries) {
    $Data->set('body_class', 'splash');
}
