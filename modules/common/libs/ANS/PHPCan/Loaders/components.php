<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

defined('ANS') or die();

$Data = new \ANS\PHPCan\Data\Data('Data');
$Templates = new \ANS\PHPCan\Templates\Templates('Templates');
$Html = new \ANS\PHPCan\Templates\Html\Html('Html');
$Form = new \ANS\PHPCan\Templates\Html\Form($Html, 'Form');
