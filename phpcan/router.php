<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/
defined('ANS') or die();

define('PHPCAN_VERSION', '1.2');
define('METHOD', php_sapi_name());
define('OS', stripos(PHP_OS, 'win') === false ? 'UNIX' : 'WIN');

define('BASE_PATH', preg_replace('#[/\\\]+#', '/', dirname(__DIR__).'/'));
define('PHPCAN_PATH', BASE_PATH.'phpcan/');
define('LIBS_PATH', PHPCAN_PATH.'libs/');

require (LIBS_PATH.'ANS/PHPCan/functions.php');

if (METHOD === 'cli') {
    $params = cliParams();

    if (empty($params['domain']) || empty($params['route'])) {
        throw new Exception('domain and route parameters are required');
    }

    define('SERVER_NAME', $params['domain']);

    putenv('SERVER_NAME='.SERVER_NAME);
    putenv('DOCUMENT_ROOT='.dirname(__DIR__).'/');
    putenv('REQUEST_URI='.$params['route'].($_GET ? ('?'.http_build_query($_GET)) : ''));

    unset($params);
} else {
    define('SERVER_NAME', getenv('SERVER_NAME') ?: 'localhost');
}

define('DOMAIN_CONFIG_PATH', SERVER_NAME.'/');
define('DEFAULT_CONFIG_PATH', 'default/');
define('MODULE_WWW_SUBFOLDER', 'admin');
define('DOCUMENT_ROOT', preg_replace('#[/\\\]+#', '/', realpath(getenv('DOCUMENT_ROOT'))));
define('BASE_WWW', preg_replace('|^'.DOCUMENT_ROOT.'|i', '', BASE_PATH));
define('REQUEST_URI', getenv('REQUEST_URI'));

if (str_replace(BASE_WWW, '', REQUEST_URI) && is_dir(DOCUMENT_ROOT.REQUEST_URI)) {
    $indexes = glob(DOCUMENT_ROOT.REQUEST_URI.'index.*');

    if ($indexes) {
        header('HTTP/1.1 301 Moved Permanently');
        header('Location: '.str_replace(DOCUMENT_ROOT, '', $indexes[0]));
        exit;
    }

    unset($indexes);
}

use ANS\PHPCan\Loader;

require (LIBS_PATH.'ANS/PHPCan/Loader.php');

Loader::register(LIBS_PATH);

$Debug = new \ANS\PHPCan\Debug;
$Config = new \ANS\PHPCan\Config('Config');
$Vars = new \ANS\PHPCan\Vars('Vars');
$Events = new \ANS\PHPCan\Events('Events');
$Errors = new \ANS\PHPCan\Data\Errors('Errors');

//Load basic configuration
$Config->load(array('paths.php', 'scenes.php'), PHPCAN_PATH.'config/');

$Debug->setSettings('', 'Debug');

//Detect scene and module
$Vars->load();

$Vars->setScenesConfig($Config->scenes);
$Vars->detectScene();
$Vars->detectModule();

//Scene paths
define('SCENE_NAME', $Vars->getScene());
define('SCENE_PATH', BASE_PATH.$Vars->getSceneConfig('folder'));
define('SCENE_WWW', BASE_WWW.($Vars->getSceneConfig('detect', 'subfolder') && !$Vars->getSceneConfig('default') ? (SCENE_NAME.'/') : ''));
define('SCENE_REAL_WWW', BASE_WWW.$Vars->getSceneConfig('folder'));

define('MODULE_NAME', $Vars->getModule());

if (MODULE_NAME) {
    define('MODULES_PATH', BASE_PATH.$Config->phpcan_paths['modules']);
    define('MODULE_PATH', BASE_PATH.$Config->phpcan_paths['modules'].$Vars->getModuleConfig('folder'));
    define('MODULE_WWW', SCENE_WWW.MODULE_WWW_SUBFOLDER.'/'.MODULE_NAME.'/');
    define('MODULE_REAL_WWW', BASE_WWW.$Config->phpcan_paths['modules'].$Vars->getModuleConfig('folder'));

    define('WWW', MODULE_WWW);
} else {
    define('WWW', SCENE_WWW);
}

$Config->load('cache.php', (MODULE_NAME ? 'module': 'scene'));

$Config->setCache();

$Cache = new \ANS\Cache\Cache($Config->cache['types']['default']);

$Config->load(array('paths.php', 'misc.php'), 'scene');

if (MODULE_NAME) {
    $Config->load(array('paths.php', 'misc.php'), 'module');
}
