<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

defined('ANS') or die();

echo '<td class="actions" rowspan="'.$rowspan.'">';

echo $Form->checkbox(0, $id);

echo $Html->a(array(
    'text' => __('Delete'),
    'title' => __('Delete'),
    'href' => path().get(),
    'data-icon' => 'trash',
    'data-no-text' => '1',
    'data-confirm-delete' => 'true',
    'class' => 'button',
    'action' => array(
        'name' => 'delete',
        'params' => array(
            'connection' => $Vars->get('connection'),
            'table' => $table,
            'id' => $id
        )
    )
));

if ($view) {
    echo $Html->a(array(
        'text' => __('View'),
        'title' => __('View in public web'),
        'href' => path(),
        'data-icon' => 'extlink',
        'data-no-text' => '1',
        'class' => 'button',
        'action' => array(
            'name' => 'view',
            'params' => array(
                'view[connection]' => $Vars->get('connection'),
                'view[table]' => $table,
                'view[id]' => $id
            )
        )
    ));
}

echo '<div class="buttonset">';
echo $Html->a(array(
    'text' => __('Edit'),
    'title' => __('Edit'),
    'href' => path(true, true, $id, 'edit'),
    'class' => 'button',
    'data-icon' => 'pencil'
));
echo $Html->a(array(
    'text' => __('Edit'),
    'title' => __('Edit'),
    'href' => path(true, true, $id, 'edit'),
    'class' => 'button iframe',
    'data-icon' => 'newwin',
    'data-no-text' => '1',
));
echo '</div>';
echo '</td>';
