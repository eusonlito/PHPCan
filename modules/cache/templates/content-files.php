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

<?php if ($folders): ?>

<form action="<?php echo path(); ?>" method="post" class="content">
    <fieldset>
        <table>
            <thead>
                <tr>
                    <th><?php __e('Folder'); ?></th>
                    <th><?php __e('Files'); ?></th>
                    <th><?php __e('Size'); ?></th>
                    <th class="actions"><?php __e('Clear'); ?></th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($folders as $folders_key => $folders_value): ?>
                <tr>
                    <td><label for="<?php echo $folders_key; ?>" class="inline-block"><?php echo $folders_key; ?></label></td>
                    <td><label for="<?php echo $folders_key; ?>" class="inline-block"><?php echo $folders_value['files']; ?></label></td>
                    <td><label for="<?php echo $folders_key; ?>" class="inline-block"><?php echo $folders_value['size']; ?></label></td>

                    <td class="actions"><?php
                        echo $Form->checkbox(array(
                            'name' => 'selected[]',
                            'id' => $folders_key,
                            'value' => $folders_key,
                            'force' => false
                        ));
                    ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </fieldset>

    <footer>
        <?php
        echo $Form->submit(array(
            'value' => 'Clear',
            'button' => true,
            'name' => 'phpcan_action[clear]'
        ));
        ?>
    </footer>
</form>

<?php else: ?>
    <?php echo ad(__('Cache folder is empty'), 'success'); ?>
<?php endif;
