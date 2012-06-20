<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

defined('ANS') or die();

if (!$Content->setConnection($Vars->get('connection'))) {
    $Vars->message(__('The connection %s is not available', $Vars->get('connection')), 'error');
    redirect(path(''));
}

if (!$Content->checkTable($Vars->get('table'))) {
    $Vars->message(__('The table %s is not available', $Vars->get('table')), 'error');
    redirect(path(''));
}

//Data language
$Data->set('content_data_language', $Content->getLanguage());
$Data->set('content_data_languages', $Content->getLanguages());
