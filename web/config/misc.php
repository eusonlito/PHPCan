<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

defined('ANS') or die();

// Encryption key
$config['key'] = substr(md5('Write her your key'), -16);

// Autoglobals definition
$config['autoglobal'] = array();

// Exit modes configuration

$config['exit_modes'] = array(
    'html' => array(
        'lock' => false,
        'action_redirect' => true
    ),
    'ajax' => array(
        'lock' => false,
        'action_redirect' => false
    )
);

// Debug configuration

$config['debug'] = array(
    'print' => true,
    'save' => false,
    'redirect' => true,
    'ip' => array('127.0.0.1')
);

// Images configuration

$config['images'] = array(
    'quality' => 90,
    'library' => extension_loaded('imagick') ? 'imagick' : 'gd'
);
