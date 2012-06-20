<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

defined('ANS') or die();

$config['exit_modes'] = array(
    'html' => array(
        'lock' => false,
        'action_redirect' => true,
        'header' => 'Content-Type: text/html'
    ),
    'ajax' => array(
        'lock' => false,
        'action_redirect' => false
    ),
    'iframe' => array(
        'lock' => true,
        'action_redirect' => false,
        'header' => 'Content-Type: text/html'
    )
);
