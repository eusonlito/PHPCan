<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

defined('ANS') or die();

$Config->load(array('actions.php', 'routes.php', 'db.php', 'tables.php', 'languages.php', 'events.php'));

$Vars->setRoutesConfig();
$Vars->setLanguagesConfig();
$Vars->setExitModesConfig();

$Vars->detectLanguage();
$Vars->detectExitMode();
$Vars->detectRoute();

$Config->load('i18n.php');

$Config->templates = $Vars->getRouteConfig('templates', array('css', 'js'));
$Config->data = $Vars->getRouteConfig('data');

$Db = new \ANS\PHPCan\Data\Db('Db');
$Db->setConnection();
$Db->language($Vars->getLanguage());

// Set debug settings

$Debug->setSettings('debug');

// Basic classes

$Data = new \ANS\PHPCan\Data\Data('Data');
$Templates = new \ANS\PHPCan\Templates\Templates('Templates');
$Html = new \ANS\PHPCan\Templates\Html\Html('Html');
$Form = new \ANS\PHPCan\Templates\Html\Form($Html, 'Form');

// Message

$Vars->loadMessage();

// Functions

include_once (filePath('libs|ANS/PHPCan/functions.php'));
include_once (filePath('libs|ANS/PHPCan/preload.php'));

$Events->load();

// Actions, data and templates

include_once (filePath('phpcan/libs|ANS/PHPCan/Loaders/actions.php'));
include_once (filePath('phpcan/libs|ANS/PHPCan/Loaders/data.php'));
include_once (filePath('phpcan/libs|ANS/PHPCan/Loaders/templates.php'));
