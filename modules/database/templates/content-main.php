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

<?php if ($Data->queries): ?>

<form action="<?php echo path(); ?>" method="post" class="content">
    <fieldset>
        <table>
            <thead>
                <tr>
                    <th class="actions"><?php __e('Select'); ?></th>
                    <th><?php __e('Queries'); ?></th>
                    <th class="status"><?php __e('Status'); ?></th>
                </tr>
            </thead>

            <tbody>
                <?php $checkbox = false; ?>
                <?php foreach ($Data->queries as $query): ?>
                <?php $id_query = uniqid(); ?>

                <tr>
                    <td class="actions"><?php
                        if ($query['status'] != 'executed') {
                            echo $Form->checkbox(array(
                                'name' => 'selected['.$query['key'].']',
                                'id' => $id_query
                            ));
                        }
                    ?></td>

                    <td><label for="<?php echo $id_query; ?>" class="inline-block"><?php echo $query['query']; ?></label></td>

                    <td class="status"><?php
                        switch ($query['status']) {
                            case 'error':
                                echo ad(__('This query was an error'), 'error');
                                $checkbox = true;
                                break;

                            case 'executed':
                                echo ad(__('This query was executed succesfully'), 'success');
                                break;

                            default:
                                echo ad(__('This query was not executed yet'));
                                $checkbox = true;
                                break;
                        }
                    ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>

            <?php if ($checkbox): ?>
            <tfoot>
            <tr>
                <td class="actions">
                <?php
                echo $Form->checkbox(array(
                    'id' => 'option-select'
                ));
                ?>
                </td>
            </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </fieldset>

    <footer>
        <?php
        echo $Form->submit(array(
            'value' => 'Update database',
            'button' => true,
            'name' => 'phpcan_action[db-update]'
        ));
        ?>
    </footer>
</form>

<script type="text/javascript">
    $('table').checkTable();
</script>

<?php else: ?>
    <?php echo ad(__('Database is updated'), 'success'); ?>
<?php endif;
