<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

defined('ANS') or die();

$config['languages'] = array(
    'detect' => 'get', // Method for language detection (subfolder/subdomain/get)
    'default' => '', // If default is defined, load this language as default, else try to load the browser language
    'availables' => array(
        'es' => true,
        'en' => true
    )
);
