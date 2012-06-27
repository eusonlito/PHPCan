<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

defined('ANS') or die();

include (filePath('modules_common|libs/ANS/PHPCan/Loaders/config.php'));

define('MODULE_TITLE', __('SVN Updater'));

// Set debug settings

$Debug->setSettings('debug');

// Session

include_once (filePath('modules_common|libs/ANS/PHPCan/session.php'));
include_once (filePath('modules_common|libs/ANS/PHPCan/Loaders/components.php'));

// SVN Module settings

$Config->load('svn.php', 'module');

// Message

$Vars->loadMessage();

// Functions

include_once(filePath('modules_common|libs/ANS/PHPCan/functions.php'));

// Actions, data and templates

include_once (filePath('phpcan/libs|ANS/PHPCan/Loaders/actions.php'));
include_once (filePath('phpcan/libs|ANS/PHPCan/Loaders/data.php'));
include_once (filePath('phpcan/libs|ANS/PHPCan/Loaders/templates.php'));
