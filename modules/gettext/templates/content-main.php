<?php defined('ANS') or die(); ?>

<header>
    <script>
        $(document).ready(function () {
            $('#search-gettext').quicksearch('#menu-gettext article');
        });
    </script>

    <?php
    echo $Form->search(array(
        'id' => 'search-gettext',
        'placeholder' => __('Filter'),
        'class' => 'no-appearance',
        'accesskey' => 'F'
    ));
    ?>
</header>

<div class="content" id="menu-gettext">
    <?php foreach ((array) $Data->menu_gettext as $id => $gettext) { ?>
    <article>
        <hgroup>
            <h1><?php echo format($id); ?></h1>
            <?php if (hasText($gettext['description'])) {
                echo '<h2>'.$gettext['description'].'</h2>';
            } ?>
        </hgroup>

        <nav><?php
        echo $Html->a(array(
            'text' => __('translate'),
            'href' => path('translate', $id),
            'class' => 'button'
        ));

        foreach ($gettext['languages'] as $language) {
            $po = $gettext['output'].$language.'/'.$id.'.po';
            $mo = $gettext['output'].$language.'/'.$id.'.mo';

            if (is_file(filePath($po))) {
                echo $Html->a(array(
                    'text' => __('PO (%s)', $language),
                    'href' => fileWeb($po),
                    'class' => 'download'
                ));
            }

            if (is_file(filePath($mo))) {
                echo $Html->a(array(
                    'text' => __('MO (%s)', $language),
                    'href' => fileWeb($mo),
                    'class' => 'download'
                ));
            }
        }

        if ($gettext['actions'] && is_array($gettext['actions'])) {
            foreach ($gettext['actions'] as $name => $action) {
                echo $Html->a(array(
                    'text' => $name,
                    'href' => path().get(array('phpcan_action' => $action)),
                    'class' => 'button'
                ));
            }
        }
        ?></nav>
    </article>
    <?php } ?>
</div>
