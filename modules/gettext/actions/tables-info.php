<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

defined('ANS') or die();

$Config->load('tables.php', 'scene');

$settings = $Config->gettext_builders['tables_info'];

$file = filePath($Config->gettext_builders['tables_info']['input']);

$File = new \ANS\PHPCan\Files\File;

if (!$File->saveText('', $file)) {
    $Vars->message(__('Gettext file haven\'t writing permissions'), 'error');

    return false;
}

$string = '<?php'."\n".'defined(\'ANS\') or die();'."\n";

foreach ($Config->tables as $connection => $tables) {
    foreach ($tables as $table => $fields) {
        $string .= "\n".'__(\''.$connection.'-'.$table.'-name\');';
        $string .= "\n".'__(\''.$connection.'-'.$table.'-description\');';

        foreach (array_keys($fields) as $field) {
            $string .= "\n".'__(\''.$connection.'-'.$table.'-'.$field.'-name\');';
            $string .= "\n".'__(\''.$connection.'-'.$table.'-'.$field.'-description\');';
        }
    }
}

$string .= "\n".'?>';

$File->saveText($string, $file);

$Vars->message(__('Gettext file was generated successfully'), 'correct');
