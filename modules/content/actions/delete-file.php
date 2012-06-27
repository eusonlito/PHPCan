<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

defined('ANS') or die();

$Files = new \ANS\PHPCan\Files\File;

$file = filePath('scene/uploads|'.$Vars->get('file'));

if (!is_file($file)) {
    $Vars->message(__('This file doesn\'t exits'), 'error');

    return false;
}

$ok = $Files->delete($file);

if (!$ok) {
    $Vars->message(__('There was an error deleting the file'), 'error');

    return false;
}

$Vars->message(__('File was deleted successfully'), 'success');
