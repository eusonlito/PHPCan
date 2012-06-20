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

<tr>
    <th rowspan="2" class="actions"><?php __e('Actions'); ?></th>

    <?php
    foreach ($header as $table) {
        echo '<th class="table" colspan="'.$table['cols'].'">'.$table['title'].'</th>';
    }
    ?>
</tr>

<tr>
    <?php
    foreach ($header as $table) {
        foreach ($table['data'] as $cols) {
            echo '<th class="fields format_'.$cols['format'].'" title="'.$cols['description'].'">';
            echo $Html->a(array(
                'text' => $cols['title'],
                'href' => path().get(array(
                        'phpcan_sortfield' => $cols['field'],
                        'phpcan_sortdirection' => ($Vars->get('phpcan_sortfield') == $cols['field'] && $Vars->get('phpcan_sortdirection') == 'DESC') ? 'ASC' : 'DESC',
                        'page' => 1
                    )),
                'class' => ($Vars->get('phpcan_sortfield') == $cols['field']) ? 'selected' : null
                ));
            echo '</th>';
        }
    }
    ?>
</tr>
