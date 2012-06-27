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

$connection = getDatabaseConnection();

$Db->setConnection($connection, array_keys((array)$Config->scene_languages['availables']), $Config->tables[$connection], $Config->relations[$connection]);
$Db->language($Vars->getLanguage());
