<?php
defined('ANS') or die();

$contents = array();
$folders = array(
    'phpcan' => BASE_PATH.$Config->phpcan_paths['cache'],
    'scene' => SCENE_PATH.$Config->scene_paths['cache']
);

foreach ($folders as $base => $path) {
    $contents[$base] = array();

    foreach (glob($path.'*', GLOB_ONLYDIR) as $folder) {
        $Current = new \RecursiveDirectoryIterator($folder);

        $folder = basename($folder);
        $contents[$base][$folder] = array();

        foreach (new \RecursiveIteratorIterator($Current) as $Info) {
            $contents[$base][$folder]['size'] += $Info->getSize();
            ++$contents[$base][$folder]['files'];
        }

        $contents[$base][$folder]['size'] = humanSize($contents[$base][$folder]['size']);
    }
}

$contents = array_filter($contents);
