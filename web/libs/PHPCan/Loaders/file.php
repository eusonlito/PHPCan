<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

defined('ANS') or die();

$Config->load('languages.php');

$Vars->setLanguagesConfig();
$Vars->detectLanguage();

$Config->load('i18n.php');

include_once (filePath('libs|PHPCan/functions.php'));
include_once (filePath('phpcan/libs|PHPCan/Loaders/file.php'));
