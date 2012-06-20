<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

defined('ANS') or die();

$config['cache'] = array(
    'types' => array(
        'api' => array(
            'expire' => 0, // 360
            'interface' => 'files'
        ),
        'config' => array(
            'expire' => 0, // 3600 * 24 * 30
            'interface' => 'apc'
        ),
        'db' => array(
            'expire' => 0, // 60
            'interface' => 'apc'
        ),
        'css' => array(
            'expire' => 0, // 3600 * 24 * 30
            'interface' => 'files',
            'compress' => true
        ),
        'data' => array(
            'expire' => 0, // 600
            'interface' => 'apc'
        ),
        'default' => array(
            'expire' => 0, // 600
            'interface' => 'apc'
        ),
        'images' => array(
            'expire' => 0, // 3600 * 24 * 30,
            'interface' => 'files'
        ),
        'js' => array(
            'expire' => 0, // 3600 * 24 * 30
            'interface' => 'files',
            'compress' => true
        ),
        'templates' => array(
            'expire' => 0, // 600
            'interface' => 'apc'
        )
    ),
    'memcached' => array(
        'host' => 'localhost',
        'port' => 11211
    ),
    'headers_no_cache' => true, // false
);
