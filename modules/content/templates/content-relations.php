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
        <h2><?php __e('%s related with %s %s #%s', $Data->table_info['relation_direction'], $Data->table_info['relation_table'], $Data->table_info['relation_name'], $Vars->get('relation_id')); ?></h2>
    </hgroup>

    <nav>
        <div class="actions">
            <?php
            echo $Html->a(array(
                'text' => __('Related elements'),
                'href' => path(),
                'class' => 'button'.($Vars->get('all') ? '' : ' secondary'),
                'data-icon' => 'link'
            ));
            echo $Html->a(array(
                'text' => __('All elements'),
                'href' => path().get('all', true),
                'class' => 'button'.($Vars->get('all') ? ' secondary' : ''),
                'data-icon' => 'search'
            ));
            echo $Html->a(array(
                'text' => __('New'),
                'href' => path(true, true, 'new').get(array(
                    'relation' => $Vars->get('relation'),
                    'relation_id' => $Vars->get('relation_id')
                )),
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

    <table class="list">
        <thead><?php $Templates->render('aux-list-table-head.php', array('header' => $Data->list['head'])); ?></thead>

        <tbody>
            <?php
            $Templates->render('aux-list-table-body.php', array(
                'rows' => $Data->list['body'],
                'actions_template' => 'aux-relations-table-body-actions.php'
            ));
            ?>
        </tbody>

        <tfoot>
            <tr>
                <td class="actions">
                    <form id="form-relate" method="post" action="<?php echo path().get() ?>">
                        <fieldset>
                            <?php echo $Form->hidden($table, 'table'); ?>
                            <?php echo $Form->hidden($Vars->get('relation'), 'relation'); ?>
                            <?php echo $Form->hidden($Vars->get('id'), 'relation_id'); ?>
                            <?php echo $Form->hidden('relate', 'action'); ?>
                            <?php echo $Form->hidden('relate-unrelate', 'phpcan_action'); ?>
                            <?php echo $Form->hidden('', 'id'); ?>
                        </fieldset>
                    </form>
                    <form id="form-unrelate" method="post" action="<?php echo path().get() ?>">
                        <fieldset>
                            <?php echo $Form->hidden($table, 'table'); ?>
                            <?php echo $Form->hidden($Vars->get('relation'), 'relation'); ?>
                            <?php echo $Form->hidden($Vars->get('id'), 'relation_id'); ?>
                            <?php echo $Form->hidden('unrelate', 'action'); ?>
                            <?php echo $Form->hidden('relate-unrelate', 'phpcan_action'); ?>
                            <?php echo $Form->hidden('', 'id'); ?>
                        </fieldset>
                    </form>
                    <?php
                        echo $Form->checkbox(array(
                            'id' => 'option-select'
                        ));

                        echo $Html->a(array(
                            'text' => __('Relate'),
                            'href' => '#form-relate',
                            'class' => 'button',
                        ));
                        echo $Html->a(array(
                            'text' => __('Unrelate'),
                            'href' => '#form-unrelate',
                            'class' => 'button',
                        ));
                    ?>
                </td>
            </tr>
        </tfoot>
    </table>

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
                        'checked' => in_array($columns_values, (array) $Data->selected_columns[$tables_key]),
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
