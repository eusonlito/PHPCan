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
    'save' => array(
        'file' => 'save.php',
        'redirect' => true
    ),
    'tables-info' => array(
        'file' => 'tables-info.php',
        'redirect' => true
    )
);
