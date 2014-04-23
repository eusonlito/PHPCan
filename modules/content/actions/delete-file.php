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

$file = filePath('scene/uploads|'.$Vars->get('file'));

if (!is_file($file)) {
    $Vars->message(__('This file doesn\'t exits'), 'error');
    return false;
}

$ok = $Files->delete($file);

if (empty($ok)) {
    $Vars->message(__('There was an error deleting the file'), 'error');
    return false;
}

$Vars->message(__('File was deleted successfully'), 'success');
