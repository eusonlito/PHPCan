<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

defined('ANS') or die();

$Data = new \PHPCan\Data\Data('Data');
$Templates = new \PHPCan\Templates\Templates('Templates');
$Html = new \PHPCan\Templates\Html\Html('Html');
$Form = new \PHPCan\Templates\Html\Form($Html, 'Form');
