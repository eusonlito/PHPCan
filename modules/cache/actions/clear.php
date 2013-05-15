<?php
defined('ANS') or die();

if (empty($Vars->var['selected']) || !is_array($Vars->var['selected'])) {
    return false;
}

$Files = new \ANS\PHPCan\Files\File('File');

$ok = 0;

$folders = array(
    'phpcan' => BASE_PATH.$Config->phpcan_paths['cache'],
    'scene' => SCENE_PATH.$Config->scene_paths['cache']
);

foreach ($folders as $base => $path) {
    foreach (glob($path.'*', GLOB_ONLYDIR) as $folder) {
        if (in_array($base.'-'.basename($folder), $Vars->var['selected'], true)) {
            if ($Files->delete($folder)) {
                ++$ok;
            }
        }
    }
}

if ($ok == count($Vars->var['selected'])) {
    $Vars->message(__('Cache cleared successfully'), 'success');
} else {
    $Vars->message(__('Some cache files or folders can not be cleared. Permissions problems into cache folder?'), 'error');
}

return true;
