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

$translation = $Vars->get('translate');

$Gettext_builder = new \ANS\PHPCan\I18n\Gettext_builder;

$translation_config = $Config->gettext_builders[$translation['id']];

if (empty($translation['id']) || empty($translation_config)) {
    $Vars->message(__('The gettext collection %s is not valid', $translation['id']), 'error');
    return false;
}

if (empty($translation['language']) || !in_array($translation['language'], $translation_config['languages'])) {
    $Vars->message(__('The language %s is not valid', $translation['language']), 'error');
    return false;
}

$Gettext_builder->setSettings($translation_config);

$translation['headers']['PO-Revision-Date'] = date(DATE_RFC822);
$translation['headers']['Last-Translator'] = $Session->user('name');

$file = filePath($translation_config['output'].$translation['language'].'/'.$translation['id']);

if ($Vars->var['empty']) {
    $current = $Gettext_builder->getEntries(array($file.'.po'));

    if ($current['entries']) {
        foreach ($current['entries'] as $current_index => $current_value) {
            $msgstr = trim(implode('', (array) ($current_value['msgstr'])));

            if ($msgstr) {
                $translation['entries'][] = array(
                    'msgid' => $current_value['msgid'],
                    'msgstr' => implode("\n", (array) $current_value['msgstr']),
                    'comments' => implode("\n", (array) $current_value['comments'])
                );
            }
        }
    }
}

if (!is_file($file.'.po')) {
    $translation['headers']['POT-Creation-Date'] = date(DATE_RFC822);
}

if (!$Gettext_builder->generatePo($translation, $file.'.po')) {
    $Vars->message(__('There was an error generating the po file'), 'error');
    return false;
}
if (!$Gettext_builder->generateMo($translation, $file.'.mo')) {
    $Vars->message(__('There was an error generating the mo file'), 'error');
    return false;
}

$Vars->message(__('The translations have been saved successfully'), 'success');

redirect(path(true, true, $translation['language']).get());
