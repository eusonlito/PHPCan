<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

defined('ANS') or die();

$config['actions'] = array(
    'login' => 'modules_common|actions/login.php',
    'logout' => 'modules_common|actions/logout.php',
    'view' => 'view.php',

    'language' => array(
        'file' => 'language.php',
        'redirect' => true
    ),
    'save' => array(
        'file' => 'save.php',
        'redirect' => true,
    ),
    'delete' => array(
        'file' => 'delete.php',
        'redirect' => true
    ),
    'relate-unrelate' => array(
        'file' => 'relate-unrelate.php',
        'redirect' => true
    ),
    'upload' => array(
        'file' => 'upload.php',
        'redirect' => true
    ),
    'create-folder' => array(
        'file' => 'create-folder.php',
        'redirect' => true
    ),
    'delete-file' => array(
        'file' => 'delete-file.php',
        'redirect' => true
    ),
    'delete-folder' => array(
        'file' => 'delete-folder.php',
        'redirect' => true
    ),
    'show-columns' => array(
        'file' => 'show-columns.php',
        'redirect' => true
    )
);
