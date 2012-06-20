<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

defined('ANS') or die();

$Data->execute('check.php', true);

$Content->view($Vars->get('view[connection]'), $Vars->get('view[table]'), $Vars->int('view[id]'));
