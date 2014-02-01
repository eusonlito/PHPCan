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
            'head' => 'modules_common|templates/aux-head.php',
            'header' => 'modules_common|templates/aux-header.php',
            'navigation' => 'aux-navigation.php',
            'footer' => 'modules_common|templates/aux-footer.php',
            'css' => array(
                'common|csans/csans.css',
                'common|jquery.colorbox/example2/colorbox.css',
                'modules_common|templates/css/jquery-ui/jquery.ui.base.css',
                'modules_common|templates/css/jquery-ui/jquery-ui-timepicker-addon.css',
                ($schema.'://fonts.googleapis.com/css?family=Arimo:regular,bold'),
                '$styles.css'
            ),
            'js' => array(
                'common|jquery/jquery.min.js',
                'common|jquery.ui/jquery.ui.min.js',
                'common|jquery.ui/jquery.ui.selectmenu.js',
                'common|jquery.ui/jquery-ui-timepicker-addon.js',
                'common|jquery.colorbox/jquery.colorbox-min.js',
                'common|jquery.cookie/jquery.cookie.js',
                'modules_common|templates/js/scripts.js',
                '$scripts.js'
            )
        ),
        'templates #html' => array('base' => 'modules_common|templates/base-html.php'),
        'templates #iframe' => array('base' => 'base-iframe.php'),
        'data' => 'menu-tables.php'
    ),

    'login' => array(
        'templates' => array('base' => 'modules_common|templates/base-login.php')
    ),

    'index' => array(
        'templates' => array(
            'content' => 'content-main.php',
            'js' => 'common|jquery.quicksearch/jquery.quicksearch.js',
            'css' => '$styles-main.css'
        )
    ),

    'undefined' => array(
        'templates' => array('base' => '')
    ),

    '$connection/$table/list' => array(
        'templates' => array(
            'content' => 'content-list.php',
            'css' => '$styles-list.css',
            'js' => 'scripts-list.js'
        ),
        'data' => 'list.php'
    ),

    '$connection/$table/csv-export' => array(
        'data' => 'csv-export.php'
    ),

    '$connection/$table/new' => array(
        'templates' => array(
            'content' => 'content-edit.php',
            'css' => '$styles-edit.css',
            'js' => array(
                '$scripts-edit.js'
            )
        ),
        'data' => 'new.php'
    ),

    '$connection/$table/$id/edit' => array(
        'templates' => array(
            'content' => 'content-edit.php',
            'css' => '$styles-edit.css',
            'js' => array(
                '$scripts-edit.js'
            )
        ),
        'data' => 'edit.php'
    ),

    '$connection/$table/related_with/$relation/$relation_id:int' => array(
        'templates' => array(
            'content' => 'content-relations.php',
            'css' => '$styles-list.css',
            'js' => 'scripts-relations.js'
        ),
        'data' => 'relations.php'
    ),

    'uploads/*' => array(
        'templates' => array(
            'css' => '$styles-uploads.css',
            'js' => array(
                'common|jquery.html5uploader/jquery.html5uploader.min.js',
                'common|jquery.quicksearch/jquery.quicksearch.js',
                'scripts-uploads.js',
            ),
            'content' => 'content-uploads.php',
        ),
        'data' => 'uploads.php'
    )
);
