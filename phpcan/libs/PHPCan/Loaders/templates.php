<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

defined('ANS') or die();

//Launch exit_mode headers
if (!headers_sent()) {
    if ($Vars->getExitModeConfig('header')) {
        header($Vars->getExitModeConfig('header'));
    }

    if ($Config->cache['headers_no_cache']) {
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');
    }
}

//Load template files
include($Templates->file('base'));
