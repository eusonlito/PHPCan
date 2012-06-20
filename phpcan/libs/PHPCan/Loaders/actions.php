<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

defined('ANS') or die();

if (!$Vars->actions) {
    return;
}

foreach (array_keys($Vars->actions) as $action_name) {
    if ($action = $Data->getAction($action_name)) {
        $Data->afterAction($action, include($action['file']));
    }
}
