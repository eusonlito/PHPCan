<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

defined('ANS') or die();

$Files = new \PHPCan\Files\File;

$folder = filePath('scene/uploads|'.$Vars->get('path').'/'.$Vars->get('folder'));

if (is_dir($folder)) {
    $Vars->message(__('There is a folder with the same name'), 'error');

    return false;
}

$ok = $Files->makeFolder($folder);

if (!$ok) {
    $Vars->message(__('There was an error creating the folder'), 'error');

    return false;
}

$Vars->message(__('Folder was created successfully'), 'success');
