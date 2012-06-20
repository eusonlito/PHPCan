<?php
defined('ANS') or die();

$config['modules_users'] = array(
    array(
        'name' => 'admin',
        'password' => uniqid(),
        'modules' => array('content', 'svn', 'database', 'gettext', 'cache')
    )
);
