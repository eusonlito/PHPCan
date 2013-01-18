<?php
defined('ANS') or die();

global $Config;

$languages = array_keys($Config->scene_languages['availables']);

$config['gettext_builders'] = array(
    'generic_dates' => array(
        'input' => 'scene/config|i18n.php',
        'output' => 'scene/languages|',
        'languages' => array('en', 'gl', 'es')
    ),
    'scene_texts' => array(
        'input' => array('scene/templates|', 'scene/data|', 'scene/actions|', 'scene/languages|', 'scene/includes|'),
        'output' => 'scene/languages|',
        'languages' => $languages
    ),
    'tables_info' => array(
        'input' => 'phpcan/modules|content/languages/tables-info.php',
        'output' => 'phpcan/modules|content/languages/',
        'languages' => $languages,
        'actions' => array(
            __('Update') => 'tables-info'
        )
    ),
    'phpcan' => array(
        'description' => __('phpCan errors'),
        'input' => 'phpcan/libs|ANS/PHPCan/',
        'output' => 'phpcan/languages|',
        'languages' => array('en', 'gl', 'es')
    )
);

foreach (glob(MODULES_PATH.'*', GLOB_ONLYDIR) as $module) {
    $name = basename($module);
    $base = 'phpcan/modules|'.$name.'/';

    $config['gettext_builders']['module_'.$name] = array(
        'description' => __('%s Module', $name),
        'input' => array(),
        'output' => $base.'languages/',
        'languages' => $languages
    );

    foreach (glob($module.'/*', GLOB_ONLYDIR) as $folders) {
        $config['gettext_builders']['module_'.$name]['input'][] = $base.basename($folders).'/';
    }
}
