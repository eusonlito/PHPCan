<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

defined('ANS') or die();

echo $Html->a(array(
    'text' => __('Home'),
    'href' => MODULE_WWW,
    'class' => 'button',
    'data-icon' => 'home'
));

echo $Form->select(array(
    'id' => 'menu-gettext',
    'title' => __('Select gettext'),
    'options' => array_keys($Data->menu_gettext),
    'option_text_as_value' => true,
    'value' => $Data->gettext,
    'data-url' => path('translate')
));
