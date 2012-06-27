<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

defined('ANS') or die();

$Config->load('modules-users.php', 'scene');

$Session = new \ANS\PHPCan\Users\Session('Session');

$Session->add('module', array(
    'users' => $Config->modules_users
));

$Session->load();

if ($Vars->getRoute(0, 'login')) {
    return;
}

if (!$Session->logged()) {
    redirect(path('login'));
}
