<?php
defined('ANS') or die();

if (empty($Vars->var['selected']) || !is_array($Vars->var['selected'])) {
    return false;
}

$ok = 0;
$folders = array();

if ($Config->phpcan_paths['cache'] && is_dir(BASE_PATH.$Config->phpcan_paths['cache'])) {
    $folders['phpcan'] = realpath(BASE_PATH.$Config->phpcan_paths['cache']);
}

if ($Config->scene_paths['cache'] && is_dir(SCENE_PATH.$Config->scene_paths['cache'])) {
    $folders['scene'] = realpath(SCENE_PATH.$Config->scene_paths['cache']);
}

$Files = new \ANS\PHPCan\Files\File('File');

foreach ($folders as $base => $path) {
    foreach (glob($path.'*', GLOB_ONLYDIR) as $folder) {
        $folder = str_replace(BASE_PATH, '', $folder);

        if (in_array($base.'-'.$folder, $Vars->var['selected'], true)) {
            if ($Files->delete(BASE_PATH.$folder)) {
                ++$ok;
            }
        }
    }
}

if ($ok === count($Vars->var['selected'])) {
    $Vars->message(__('Cache cleared successfully'), 'success');
} else {
    $Vars->message(__('Some cache files or folders can not be cleared. Permissions problems into cache folder?'), 'error');
}

return true;
