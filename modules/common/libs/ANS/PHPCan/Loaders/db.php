<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

defined('ANS') or die();

$Db = new \ANS\PHPCan\Data\Db('Db');

$Db->setConnection();
$Db->language($Vars->getLanguage());
