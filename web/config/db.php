<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

defined('ANS') or die();

$config['db'] = array(
    'default' => array(
        'type' => 'mysql',
        'host' => 'localhost',
        'database' => '',
        'user' => '',
        'password' => '',
        'charset' => 'utf8',

        'save_query_register' => true,
        'simulate_saves' => false,
        'default' => true,
    )
);
