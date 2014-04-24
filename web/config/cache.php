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
            'expire' => 0, // 360,
            'interface' => 'files',
            'folder' => filePath('phpcan/cache|api'),
            'compress' => true,
            'pack' => true
        ),
        'config' => array(
            'expire' => 0, // 3600 * 24 * 30,
            'interface' => 'apc'
        ),
        'db' => array(
            'expire' => 0, // 60
            'interface' => 'apc'
        ),
        'css' => array(
            'expire' => 0, // 3600 * 24 * 30,
            'interface' => 'files',
            'folder' => filePath('phpcan/cache|css'),
            'minify' => true,
            'compress' => true,
            'pack' => true
        ),
        'data' => array(
            'expire' => 0, // 600,
            'interface' => 'apc'
        ),
        'default' => array(
            'expire' => 0, // 600,
            'interface' => 'apc'
        ),
        'images' => array(
            'expire' => 3600 * 24 * 30,
            'interface' => 'files',
            'folder' => filePath('phpcan/cache|images')
        ),
        'js' => array(
            'expire' => 0, // 3600 * 24 * 30,
            'interface' => 'files',
            'folder' => filePath('phpcan/cache|js'),
            'minify' => true,
            'compress' => true,
            'pack' => true
        ),
        'templates' => array(
            'expire' => 0, // 600,
            'interface' => 'apc'
        )
    ),
    'headers_no_cache' => false
);
