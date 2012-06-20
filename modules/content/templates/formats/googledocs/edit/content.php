<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

defined('ANS') or die();

echo $Form->file(array(
    'name' => $info['varname'].'[0]',
    'id' => $info['id_field'],
    'error' => $info['error']['']
));
?>

<p>
    <?php
    if ($info['data']['']) {
        echo '<label>';
        echo $Form->checkbox(array(
            'name' => $info['varname'].'[0]',
            'force' => false,
            'value' => 1
        ));
        echo ' '.__('Delete file').'</label>';
    }
    ?>

    <?php
    if ($info['data']['']) {
        echo ' | '.$Html->a(array(
            'text' => __('View file'),
            'href' => $Html->imgSrc($info['data']['']),
            'target' => '_blank',
            'class' => 'link no-colorbox'
        ));
    }
    ?>
</p>
