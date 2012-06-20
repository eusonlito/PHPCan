<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

defined('ANS') or die();

echo $Html->element('h1', array('title' => $info['data']['title']), textCutter($info['data']['title'], 50));
