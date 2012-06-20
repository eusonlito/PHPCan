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

<header>
    <hgroup>
        <h1><?php echo $Data->table_info['title']; ?></h1>
        <h2><?php __e('List'); ?></h2>
    </hgroup>

    <?php if (hasText($Data->table_info['description'])) {
        echo '<p>'.$Data->table_info['description'].'</p>';
    } ?>

    <nav>
        <div class="actions">
            <?php
            echo $Html->a(array(
                'text' => __('List'),
                'href' => path(true, true, 'list'),
                'class' => 'button secondary',
                'data-icon' => 'home',
                'accesskey' => 'L'
            ));
            echo $Html->a(array(
                'text' => __('New'),
                'href' => path(true, true, 'new'),
                'class' => 'button',
                'data-icon' => 'plus',
                'accesskey' => 'N'
            ));
            if ($Data->content_data_languages) {
                echo $Form->select(array(
                    'id' => 'menu-languages',
                    'title' => __('Language to show'),
                    'data-url' => path(),
                    'value' => $Data->content_data_language,
                    'options' => $Data->content_data_languages,
                    'option_text_as_value' => true,
                ));
            }
            ?>
        </div>

        <?php $Templates->render('aux-search.php', array('action_url' => path())); ?>
    </nav>
</header>

<div class="content">
    <?php if ($Data->list['body']): ?>

    <div class="overflow-x">
        <table class="list">
            <thead><?php $Templates->render('aux-list-table-head.php', array('header' => $Data->list['head'])); ?></thead>

            <tbody>
                <?php
                $Templates->render('aux-list-table-body.php', array(
                    'rows' => $Data->list['body'],
                    'actions_template' => 'aux-list-table-body-actions.php'
                ));
                ?>
            </tbody>

            <tfoot>
                <tr>
                    <td class="actions">
                        <?php
                            echo $Form->checkbox(array(
                                'id' => 'option-select'
                            ));
                            echo $Html->a(array(
                                'text' => __('Delete'),
                                'href' => path(true, true, '|id|', 'edit'),
                                'data-icon' => 'trash',
                                'data-no-text' => '1',
                                'class' => 'button',
                                'action' => array(
                                    'name' => 'delete',
                                    'confirm' => __('Do you realy want to delete this?'),
                                )
                            ));
                            echo $Html->a(array(
                                'text' => __('Edit'),
                                'href' => path(true, true, '|id|', 'edit'),
                                'class' => 'button',
                                'data-icon' => 'pencil',
                                'accesskey' => 'E'
                            ));
                        ?>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

    <?php $Templates->render('modules_common|templates/aux-pagination.php', array('pagination' => $Data->data['pagination'])); ?>

    <div class="settings">
        <?php
        echo $Html->a(array(
            'text' => __('Show columns'),
            'href' => '#settings-show-columns',
            'class' => 'button open-dialog secondary',
            'data-icon' => 'gear'
        ));
        ?>

        <?php
        echo $Html->a(array(
            'text' => __('Export as CSV'),
            'href' => path(true, true, 'csv-export').get(),
            'class' => 'button secondary',
            'data-icon' => 'gear'
        ));
        ?>
    </div>

    <div id="settings-show-columns" class="dialog" title="<?php __e('Show columns'); ?>">
        <form action="#" method="post">
            <?php foreach ($Data->columns as $tables_key => $tables_value): ?>
            <fieldset>
                <h2><?php echo $tables_key; ?></h2>

                <?php foreach ($tables_value as $columns_values): ?>
                <p><?php
                    echo $Form->checkbox(array(
                        'name' => 'show_columns['.$tables_key.'][]',
                        'value' => $columns_values,
                        'label_text' => (is_null(__($columns_values, null, true)) ? format($columns_values) : __($columns_values)),
                        'checked' => in_array($columns_values, $Data->selected_columns[$tables_key]),
                        'force' => false
                    ));
                ?></p>
                <?php endforeach; ?>

            </fieldset>
            <?php endforeach; ?>

            <footer><?php
                echo $Form->button(array(
                    'value' => __('Save'),
                    'name' => 'phpcan_action[show-columns]',
                    'type' => 'submit',
                    'data-icon' => 'disk'
                ));
            ?></footer>
        </form>
    </div>

    <?php else: ?>
    <?php echo ad(__('No results have been found')); ?>
    <?php endif; ?>
</div>
