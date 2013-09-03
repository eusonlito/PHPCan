<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

defined('ANS') or die();

$Config->load(array(
    'scene' => array('languages.php', 'db.php', 'tables.php'),
    'module' => array('actions.php', 'routes.php', 'events.php', 'plugins.php')
));

$Config->load('languages.php', 'scene', 'scene_');

if ($Config->plugins) {
    foreach ($Config->plugins as $plugin) {
        $loader = filePath('plugins|'.$plugin['folder'].'/config/config.php');

        if ($plugin['enabled'] && is_file($loader)) {
            include ($loader);
        }
    }
}

$Vars->setRoutesConfig();
$Vars->setLanguagesConfig();
$Vars->setExitModesConfig();

$Vars->detectLanguage();
$Vars->detectExitMode();
$Vars->detectRoute();

$Config->load('i18n.php', 'scene');

$Config->templates = $Vars->getRouteConfig('templates', array('css', 'js'));
$Config->data = $Vars->getRouteConfig('data');
