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

<form action="<?php echo path(); ?>" method="post" class="content">
    <fieldset>
        <table>
            <thead>
                <tr>
                    <th><?php __e('Table'); ?></th>
                    <th><?php __e('Current field'); ?></th>
                    <th><?php __e('New field'); ?></th>
                    <th><?php __e('New format'); ?></th>
                    <th><?php __e('Rows'); ?></th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($Vars->var['fields'] as $fields_index => $fields_value) { ?>
                <tr>
                    <td><?php
                        echo $Form->select($tables, array(
                            'name' => 'fields['.$fields_index.'][table]',
                            'value' => $fields_value['table']
                        ));
                    ?></td>

                    <td><?php
                        echo $Form->text(array(
                            'name' => 'fields['.$fields_index.'][current]',
                            'value' => $fields_value['current']
                        ));
                    ?></td>

                    <td><?php
                        echo $Form->text(array(
                            'name' => 'fields['.$fields_index.'][target]',
                            'value' => $fields_value['target']
                        ));
                    ?></td>

                    <td><?php
                        echo $Form->select($formats, array(
                            'name' => 'fields['.$fields_index.'][format]',
                            'value' => $fields_value['format']
                        ));
                    ?></td>

                    <td>
                        <a href="#" class="button action-add-tr" data-icon="circle-plus"><?php __e('Add'); ?></a>
                        <a href="#" class="button action-remove-tr" data-icon="circle-minus"><?php __e('Remove'); ?></a>
                    </td>
                </tr>

                <?php if ($executed[$fields_index]) { ?>
                <tr>
                    <td colspan="3" class="status <?php echo $executed[$fields_index]['error'] ? 'error' : 'success'; ?>">
                        <?php echo $executed[$fields_index]['error'] ?: __('Fields action was executed successfully'); ?>
                    </td>

                    <td colspan="2" class="status">
                        <strong><?php echo $executed[$fields_index]['query']; ?></strong>
                    </td>
                </tr>
                <?php } ?>

                <?php } ?>
            </tbody>
        </table>
    </fieldset>

    <footer>
        <?php
        echo $Form->submit(array(
            'value' => 'Update database',
            'button' => true,
            'name' => 'phpcan_action[rename-fields]'
        ));
        ?>
    </footer>
</form>
