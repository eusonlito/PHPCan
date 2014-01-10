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
        'driver' => 'mysql',
        'host' => 'localhost',
        'database' => '',
        'user' => '',
        'password' => '',
        'charset' => 'utf8',

        'query_register_log' => false,
        'query_register_store' => 'db.log',
        'query_register_append' => false,
        'query_register_errors' => 'db.error',

        'default' => true
    )
);
