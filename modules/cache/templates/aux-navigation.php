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
    'text' => __('APC'),
    'href' => path('index'),
    'class' => 'button',
    'data-icon' => 'disk'
));

echo $Html->a(array(
    'text' => __('Memcache'),
    'href' => path('memcache'),
    'class' => 'button',
    'data-icon' => 'disk'
));

echo $Html->a(array(
    'text' => __('Files'),
    'href' => path('files'),
    'class' => 'button',
    'data-icon' => 'disk'
));
