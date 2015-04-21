<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

defined('ANS') or die();

set_time_limit(0);
ini_set('max_execution_time', 0);

$Data->set('gettext', $Config->gettext_builders[$Vars->get('id_gettext')]);

if (!$Data->gettext) {
    $Vars->message('The gettext collection %s does not exists!', $Vars->get('id_gettext'));
    redirect(path(''));
}

$Data->data['gettext']['id'] = $Vars->get('id_gettext');

if (!$Vars->get('id_language') && $Data->gettext['languages']) {
    redirect(path(true, true, current($Data->gettext['languages'])));
}

if (!$Vars->get('id_language') || !$Data->gettext['languages'] || !in_array($Vars->get('id_language'), $Data->gettext['languages'])) {
    $Vars->message(__('The language is not valid'), 'error');
    redirect(path(''));
}

$Data->data['gettext']['language'] = $Vars->get('id_language');

$Gettext_builder = new \ANS\PHPCan\I18n\Gettext_builder;

$Gettext_builder->setSettings($Data->gettext);

$sources = (array) $sources;
$sources[] = $Data->gettext['output'].$Data->gettext['language'].'/'.$Data->gettext['id'].'.po';

$File = new \ANS\PHPCan\Files\File;

foreach ($sources as $sources_value) {
    $file = filePath($sources_value);

    if (!$File->makeFolder(dirname($file))) {
        $Vars->message(__('Folder %s is not writable. Please, fix the folder permissions.', dirname(fileWeb($sources_value))), 'error');
        return false;
    }

    if (is_file($file) && !is_writable($file)) {
        $Vars->message(__('File %s is not writable. Please, fix the file permissions.', fileWeb($sources_value)), 'error');
        return false;
    }
}

$translations = $Gettext_builder->getEntries($sources);

if ($Vars->var['empty'] && $translations['entries']) {
    foreach ($translations['entries'] as $translations_index => $translations_value) {
        $msgstr = trim(implode('', (array) ($translations_value['msgstr'])));

        if ($msgstr) {
            unset($translations['entries'][$translations_index]);
        }
    }
}

$Data->set('translations', $translations);

unset($translations);
