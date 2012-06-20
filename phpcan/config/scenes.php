<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*
* detect => subfolder/subdomain
*/

defined('ANS') or die();

$config['scenes'] = array(
    'web' => array(
        'folder' => 'web/',
        'detect' => 'subfolder',
        'default' => true,

        'modules' => array(
            'content' => array(
                'folder' => 'content/',
                'detect' => 'subfolder'
            ),
            'svn' => array(
                'folder' => 'svn/',
                'detect' => 'subfolder'
            ),
            'database' => array(
                'folder' => 'database/',
                'detect' => 'subfolder'
            ),
            'gettext' => array(
                'folder' => 'gettext/',
                'detect' => 'subfolder'
            ),
            'cache' => array(
                'folder' => 'cache/',
                'detect' => 'subfolder'
            )
        )
    )
);
