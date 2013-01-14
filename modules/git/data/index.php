<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

if (($Data->actions['update'] === null) && ($Data->actions['status'] === null)) {
    $Data->set('body_class', 'splash');
}
