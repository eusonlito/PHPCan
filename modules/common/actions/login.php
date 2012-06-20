<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

defined('ANS') or die();

$Session->login('module', $Vars->arr('login'));

if ($Errors->get('session-module')) {
    $Vars->message(implode('<br />', $Errors->get('session-module')), 'error');

    return false;
}

$Vars->message(__('Welcome, %s!', $Session->user('name')), 'success');

if (!$Vars->var['referer'] || strstr($Vars->var['referer'], 'login')) {
    redirect(path(''));
}

redirect($Vars->var['referer']);
