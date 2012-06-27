<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/
define('ANS', true);

error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);

require('phpcan/router.php');

require(filePath('libs|ANS/PHPCan/Loaders/index.php'));
