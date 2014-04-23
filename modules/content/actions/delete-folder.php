<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

defined('ANS') or die();

if ($Vars->var['confirm'] !== __('DELETE')) {
    $Vars->message(__('Please write exact "DELETE" if you want to delete this content'), 'error');
    return false;
}

$Vars->delete('confirm');

$Files = new \ANS\PHPCan\Files\File;

$folder = filePath('scene/uploads|'.$Vars->get('path'));

if (!is_dir($folder)) {
    $Vars->message(__('This folder does not exists'), 'error');
    return false;
}

$ok = $Files->delete($folder);

if (empty($ok)) {
    $Vars->message(__('There was an error deleting the folder'), 'error');
    return false;
}

$Vars->message(__('Folder was deleted successfully'), 'success');

redirect(path('uploads').$Vars->get('path').'/../');
