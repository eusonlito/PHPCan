<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

defined('ANS') or die();
?>

<div class="buttonset">
    <?php
    echo $Html->a(array(
        'text' => __('Related elements'),
        'title' => $info['description'],
        'href' => path(true, $info['table'], 'related_with', $info['relation'], $info['id']),
        'class' => 'iframe button',
        'data-icon' => 'link'
    ));

    echo $Html->a(array(
        'text' => __('All elements'),
        'title' => $info['description'],
        'href' => path(true, $info['table'], 'related_with', $info['relation'], $info['id']).'?all=1',
        'class' => 'iframe button',
        'data-icon' => 'search'
    ));

    echo $Html->a(array(
        'text' => __('New'),
        'title' => __('Create new %s', $info['description']),
        'href' => path(true, $info['table'], 'new').get(array(
            'relation' => $info['relation'],
            'relation_id' => $info['id']
        )),
        'class' => 'iframe button',
        'data-icon' => 'plus'
    ));
    ?>
</div>
