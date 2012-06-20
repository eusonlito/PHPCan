<?php
defined('ANS') or die();

if (!$Vars->var['selected'] || !is_array($Vars->var['selected'])) {
    return false;
}

$Files = new \PHPCan\Files\File('File');

$ok = 0;
$folders = array();
$cache_path = filePath($Config->phpcan_paths['cache']);

foreach (glob($cache_path.'*') as $folder) {
    if (is_dir($folder)) {
        $folders[] = basename($folder);
    }
}

foreach ($Vars->var['selected'] as $selected) {
    if (in_array($selected, $folders)) {
        if ($Files->delete($cache_path.$selected)) {
            ++$ok;
        }
    }
}

if ($ok == count($Vars->var['selected'])) {
    $Vars->message(__('Cache cleared successfully'), 'success');
} else {
    $Vars->message(__('Some cache files or folders can not be cleared. Permissions problems into cache folder?'), 'error');
}

return true;
