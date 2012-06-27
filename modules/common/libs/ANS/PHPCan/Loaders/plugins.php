<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

defined('ANS') or die();

if ($Config->plugins) {
    foreach ($Config->plugins as $plugin) {
        $loader = filePath('plugins|'.$plugin['folder'].'/libs/Loaders/index.php');

        if ($plugin['enabled'] && is_file($loader)) {
            include ($loader);
        }
    }
}
