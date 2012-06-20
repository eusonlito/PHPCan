<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

defined('ANS') or die();

include_once (filePath('modules_common|libs/PHPCan/Loaders/config.php'));
include_once (filePath('modules_common|libs/PHPCan/Loaders/db.php'));

define('MODULE_TITLE', __('Content Manager'));

// Set debug settings

$Debug->setSettings('debug');

// Session

include_once (filePath('modules_common|libs/PHPCan/session.php'));
include_once (filePath('modules_common|libs/PHPCan/Loaders/components.php'));

// Content Module settings

$Config->load('content.php', 'module');

$Content = new \PHPCan\Content('Content');

// Message

$Vars->loadMessage();

// Functions

include_once (filePath('modules_common|libs/PHPCan/functions.php'));
include_once (filePath('libs|PHPCan/functions.php'));

$Events->load();

include_once (filePath('modules_common|libs/PHPCan/Loaders/plugins.php'));

// Actions, data and templates

include_once (filePath('phpcan/libs|PHPCan/Loaders/actions.php'));
include_once (filePath('phpcan/libs|PHPCan/Loaders/data.php'));
include_once (filePath('phpcan/libs|PHPCan/Loaders/templates.php'));
