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

<div class="format format-id">
    <label class="field">
        <?php echo format($info['table']).' #'.$info['data']['']; ?>
    </label>

    <div>
    <?php
    echo $Form->hidden($info['data'][''], $info['varname'].'[0]');

    if ($info['view']) {
        echo $Html->a(array(
            'text' => __('View'),
            'title' => __('View in public web'),
            'href' => path(),
            'data-icon' => 'extlink',
            'class' => 'button',
            'target' => '_blank',
            'action' => array(
                'name' => 'view',
                'params' => array(
                    'view[connection]' => $Vars->get('connection'),
                    'view[table]' => $info['table'],
                    'view[id]' => $info['id']
                )
            )
        ));
    }
    ?>
    </div>
</div>
