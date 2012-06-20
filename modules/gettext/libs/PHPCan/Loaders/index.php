<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

defined('ANS') or die();

include (filePath('modules_common|libs/PHPCan/Loaders/config.php'));

define('MODULE_TITLE', __('Gettext Translator'));

// Set debug settings

$Debug->setSettings('debug');

// Session

include_once (filePath('modules_common|libs/PHPCan/session.php'));
include_once (filePath('modules_common|libs/PHPCan/Loaders/components.php'));

// Gettext Module settings

$Config->load('gettext.php', 'module');

// Message

$Vars->loadMessage();

// Functions

include_once(filePath('modules_common|libs/PHPCan/functions.php'));

// Actions, data and templates

include_once (filePath('phpcan/libs|PHPCan/Loaders/actions.php'));
include_once (filePath('phpcan/libs|PHPCan/Loaders/data.php'));
include_once (filePath('phpcan/libs|PHPCan/Loaders/templates.php'));
