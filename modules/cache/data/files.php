<?php
defined('ANS') or die();

$contents = $folders = array();

if ($Config->phpcan_paths['cache'] && is_dir(BASE_PATH.$Config->phpcan_paths['cache'])) {
    $folders['phpcan'] = realpath(BASE_PATH.$Config->phpcan_paths['cache']);
}

if ($Config->scene_paths['cache'] && is_dir(SCENE_PATH.$Config->scene_paths['cache'])) {
    $folders['scene'] = realpath(SCENE_PATH.$Config->scene_paths['cache']);
}

foreach ($folders as $base => $path) {
    $contents[$base] = array();

    foreach (glob($path.'*', GLOB_ONLYDIR) as $folder) {
        $Current = new \RecursiveDirectoryIterator($folder);

        $folder = str_replace(BASE_PATH, '', $folder);
        $contents[$base][$folder] = array();

        foreach (new \RecursiveIteratorIterator($Current) as $Info) {
            $contents[$base][$folder]['size'] += $Info->getSize();
            ++$contents[$base][$folder]['files'];
        }

        $contents[$base][$folder]['size'] = humanSize($contents[$base][$folder]['size']);
    }
}

$contents = array_filter($contents);
