<?php
defined('ANS') or die();

$folders = array();
$total = array('size' => 0, 'files' => 0);
$cache_path = filePath($Config->phpcan_paths['cache']);

foreach ((array) glob($cache_path.'*') as $folder) {
    if (!is_dir($folder)) {
        continue;
    }

    $Current = new \PHPCan\RecursiveDirectoryIterator($folder);

    $folder = basename($folder);
    $folders[$folder] = array();

    foreach (new RecursiveIteratorIterator($Current) as $Info) {
        $folders[$folder]['size'] += $Info->getSize();
        ++$folders[$folder]['files'];
    }

    $total['size'] += $folders[$folder]['size'];
    $total['files'] += $folders[$folder]['files'];

    $folders[$folder]['size'] = humanSize($folders[$folder]['size']);
}

$total['size'] = humanSize($total['size']);
