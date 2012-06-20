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

$ok = $Files->setFolder(filePath('scene/uploads|'.$Vars->get('path')));

if (!$ok) {
    $Vars->message(__('Can not set uploads folder. Has the correct permissions?'), 'error');

    return false;
}

$errors = array();

$files = $Vars->get('files');
$files = isNumericalArray($files) ? $files : array($files);

foreach ($files as $file) {
    $ok = $Files->save($file, '', alphaNumeric($file['name'], array('.' => '.', ' ' => '-')));

    if (!$ok) {
        $errors[] = $file;
    }
}

if ($Vars->getExitMode('ajax')) {
    die();
}

if ($errors) {
    $Vars->message(__('There was an error saving some file (%s)', implode(', ', $errors)), 'error');

    return false;
}

$Vars->message(__('Files were saved successfully'), 'success');
