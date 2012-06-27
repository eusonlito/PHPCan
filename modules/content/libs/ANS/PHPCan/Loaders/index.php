<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

defined('ANS') or die();

include_once (filePath('modules_common|libs/ANS/PHPCan/Loaders/config.php'));
include_once (filePath('modules_common|libs/ANS/PHPCan/Loaders/db.php'));

define('MODULE_TITLE', __('Content Manager'));

// Set debug settings

$Debug->setSettings('debug');

// Session

include_once (filePath('modules_common|libs/ANS/PHPCan/session.php'));
include_once (filePath('modules_common|libs/ANS/PHPCan/Loaders/components.php'));

// Content Module settings

$Config->load('content.php', 'module');

$Content = new \ANS\PHPCan\Content('Content');

// Message

$Vars->loadMessage();

// Functions

include_once (filePath('modules_common|libs/ANS/PHPCan/functions.php'));
include_once (filePath('libs|ANS/PHPCan/functions.php'));

$Events->load();

include_once (filePath('modules_common|libs/ANS/PHPCan/Loaders/plugins.php'));

// Actions, data and templates

include_once (filePath('phpcan/libs|ANS/PHPCan/Loaders/actions.php'));
include_once (filePath('phpcan/libs|ANS/PHPCan/Loaders/data.php'));
include_once (filePath('phpcan/libs|ANS/PHPCan/Loaders/templates.php'));
