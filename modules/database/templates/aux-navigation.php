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
    'text' => __('Check changes'),
    'href' => path(''),
    'class' => 'button',
    'data-icon' => 'arrowrefresh-1-s'
));

echo $Html->a(array(
    'text' => __('Rename fields'),
    'href' => path('rename-fields'),
    'class' => 'button',
    'data-icon' => 'pencil'
));
