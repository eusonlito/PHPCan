<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

defined('ANS') or die();

$schema = ($_SERVER['HTTPS'] === 'on') ? 'https' : 'http';

$config['routes'] = array(

    //Default
    '*' => array(
        'templates' => array(
            'base' => 'modules_common|templates/base-html.php',
            'head' => 'modules_common|templates/aux-head.php',
            'header' => 'modules_common|templates/aux-header.php',
            'navigation' => 'aux-navigation.php',
            'footer' => 'modules_common|templates/aux-footer.php',
            'css' => array(
                'common|csans/csans.css',
                'modules_common|templates/css/jquery-ui/jquery.ui.min.css',
                ($schema.'://fonts.googleapis.com/css?family=Arimo:regular,bold'),
                '$styles.css'
            ),
            'js' => array(
                'common|jquery/jquery.min.js',
                'common|jquery.ui/jquery.ui.min.js',
                'modules_common|templates/js/scripts.js'
            )
        )
    ),

    'login' => array(
        'templates' => array('base' => 'modules_common|templates/base-login.php')
    ),

    'index' => array(
        'templates' => array('content' => 'content-apc.php')
    ),

    'apc/iframe' => array(
        'templates' => array('base' => 'base-apc.php')
    ),

    'memcache' => array(
        'templates' => array('content' => 'content-memcache.php')
    ),

    'memcache/iframe' => array(
        'templates' => array('base' => 'base-memcache.php')
    ),

    'files' => array(
        'templates' => array('content' => 'content-files.php'),
        'data' => 'files.php'
    ),
);
