<?php defined('ANS') or die(); ?>

<script>
    $(document).ready(function () {
        $('#search-table').quicksearch('#list-tables article');
    });
</script>

<header>
    <?php
    echo $Form->search(array(
        'id' => 'search-table',
        'placeholder' => __('Filter'),
        'class' => 'no-appearance',
        'accesskey' => 'F'
    ));
    ?>
</header>

<section class="content" id="list-tables">
    <?php foreach ((array) $Data->menu_tables as $table) { ?>
    <article>
        <hgroup>
            <h1><?php echo $table['name']; ?></h1>
            <?php if (hasText($table['description'])) {
                echo '<h2>'.$table['description'].'</h2>';
            } ?>
        </hgroup>

        <nav><?php
        echo $Html->a(array(
            'text' => __('List'),
            'href' => path($table['connection'], $table['table'], 'list'),
            'class' => 'button',
            'data-icon' => 'triangle-1-e'
        ));
        echo $Html->a(array(
            'text' => __('New'),
            'href' => path($table['connection'], $table['table'], 'new'),
            'class' => 'button',
            'data-icon' => 'plus'
        ));
        ?></nav>
    </article>
    <?php } ?>
</section>
