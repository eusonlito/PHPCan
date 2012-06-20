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
    'text' => __('Edit and relate'),
    'href' => path(true, true, $id, 'edit').get(array(
        'relation' => $Vars->get('relation'),
        'relation_id' => $Vars->get('relation_id'),
        'redirect' => path().get()
    )),
    'class' => 'button',
    'data-icon' => 'pencil',
    'data-no-text' => 1
));

echo $Html->a(array(
    'text' => $related ? __('Unrelate') : __('Relate'),
    'href' => path().get(),
    'class' => 'button',
    'action' => array(
        'name' => 'relate-unrelate',
        'method' => 'post',
        'params' => array(
            'table' => $table,
            'id' => $id,
            'relation' => $Vars->get('relation'),
            'relation_id' => $Vars->get('id'),
            'action' => $related ? 'unrelate' : 'relate',
        )
    )
));

echo '</td>';
