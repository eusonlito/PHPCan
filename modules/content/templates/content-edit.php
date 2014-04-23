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
        <h2><?php
            if ($Vars->get('id')) {
                __e('Editing #%s', implode(', ', $Vars->get('id')));
            } else {
                __e('Creating new');
            }

            if ($Vars->get('relation')) {
                echo '. '.__('Related with %s #%s', $Vars->get('relation'), $Vars->get('relation_id'));
            }
        ?></h2>
    </hgroup>

    <?php if (hasText($Data->table_info['description'])) {
        echo '<p>'.$Data->table_info['description'].'</p>';
    } ?>

    <nav>
        <div class="actions">
            <?php
            if ($Vars->get('relation')) {
                echo $Html->a(array(
                    'text' => __('Only related elements'),
                    'href' => path(true, true, 'related_with', $Vars->get('relation'), $Vars->get('relation_id')),
                    'class' => 'button',
                    'data-icon' => 'link'
                ));
                echo $Html->a(array(
                    'text' => __('All elements'),
                    'href' => path(true, true, 'related_with', $Vars->get('relation'), $Vars->get('relation_id')).'?all=1',
                    'class' => 'button',
                    'data-icon' => 'link'
                ));
            } else {
                echo $Html->a(array(
                    'text' => __('List'),
                    'href' => path(true, true, 'list'),
                    'class' => 'button',
                    'data-icon' => 'home',
                    'accesskey' => 'L'
                ));
            }
            echo $Html->a(array(
                'text' => __('New'),
                'href' => path(true, true, 'new').get(),
                'class' => 'button'.($Vars->getRoute(2, 'new') ? ' secondary' : ''),
                'data-icon' => 'plus',
                'accesskey' => 'N'
            ));
            if ($Vars->getRoute(3, 'edit')) {
                echo $Html->a(array(
                    'text' => __('Edit'),
                    'href' => path().get(),
                    'class' => 'button secondary',
                    'data-icon' => 'pencil'
                ));
            }

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

        <?php $Templates->render('aux-search.php', array('action_url' => path(true, true, 'list'))); ?>
    </nav>
</header>

<form class="content edit" action="<?php echo path().get(); ?>" method="post" enctype="multipart/form-data">
    <?php
    $Templates->render('aux-edit-form.php', array('edit' => $Data->edit));

    echo $Form->hidden($table, 'table');
    echo $Form->hidden($id, 'id');
    echo $Form->hidden($Vars->get('redirect'), 'redirect');
    ?>

    <footer>
    <?php
    echo $Form->submit(array(
        'value' => __('Save'),
        'name' => 'phpcan_action[save]',
        'class' => 'save',
        'button' => true,
        'data-icon' => 'disk',
        'accesskey' => 'S'
    ));

    if ($Vars->get('id')) {
        echo $Form->submit(array(
            'value' => __('Duplicate'),
            'name' => 'phpcan_action[save]',
            'class' => 'duplicate',
            'button' => true,
            'data-icon' => 'disk',
            'onclick' => 'removeId();'
        ));
    }

    echo $Form->button(array(
        'value' => __('Reset'),
        'class' => 'reset',
        'type' => 'reset',
        'data-icon' => 'refresh',
        'accesskey' => 'R'
    ));

    if ($Vars->get('id')) {
        echo $Form->submit(array(
            'value' => __('Delete'),
            'button' => true,
            'data-icon' => 'trash',
            'data-confirm-delete' => 'true',
            'class' => 'secondary delete',
            'action' => array(
                'name' => 'delete'
            )
        ));
    }
    ?>
    </footer>
</form>
