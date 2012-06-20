<?php
/*
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

defined('ANS') or die();

$File = new \PHPCan\Files\File;

$Data->path = $Vars->get(':num');
$Data->folders = array();
$Data->files = array();

$uploads_path = filePath('scene/uploads|');
$path = $uploads_path.implode('/', $Data->path);

if (!is_dir($path)) {
    $Vars->message(__('This folder does not exits'), 'error');
    redirect(path('uploads'));
}

foreach ($File->listFolder($path, '*', 0, GLOB_MARK) as $k => $file) {
    $file = str_replace($uploads_path, '', $file);

    if (substr($file, -1) == '/') {
        $Data->data['folders'][] = array(
            'name' => pathinfo($file, PATHINFO_BASENAME),
            'path' => $file
        );
    } else {
        $info = pathinfo($file);
        $type = 'file';

        switch ($info['extension']) {
            case 'jpg':
            case 'jpeg':
            case 'gif':
            case 'png':
                $type = 'image';
        }

        $Data->data['files'][] = array(
            'name' => $info['basename'],
            'extension' => $info['extension'],
            'path' => $file,
            'type' => $type
        );
    }
}
