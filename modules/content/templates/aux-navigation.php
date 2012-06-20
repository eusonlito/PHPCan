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

if ($Data->menu_tables) {
    echo $Form->select(array(
        'id' => 'menu-tables',
        'title' => __('Select a table'),
        'options' => $Data->menu_tables,
        'option_text' => 'name',
        'option_value' => 'url',
        'value' => path(true, true, 'list'),
    ));
}
?>
<div class="buttonset">
<?php
    echo $Html->a(array(
        'text' => __('Uploads'),
        'href' => path('uploads'),
        'class' => 'button',
        'data-icon' => 'image'
    ));
    echo $Html->a(array(
        'text' => __('Uploads'),
        'href' => path('uploads'),
        'class' => 'button iframe',
        'data-icon' => 'newwin',
        'data-no-text' => '1',
    ));
?>
</div>
