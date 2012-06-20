<?php defined('ANS') or die(); ?>

<header>
    <hgroup>
        <h1><?php echo format($Data->gettext['id']); ?></h1>
        <h2><?php echo $Data->gettext['language']; ?></h2>

        <?php if (hasText($Data->gettext['description'])) {
            echo '<p>'.$Data->gettext['description'].'</p>';
        } ?>
    </hgroup>

    <nav>
        <div class="actions buttonset">
            <?php
            foreach ($Data->gettext['languages'] as $language) {
                echo $Html->a(array(
                    'text' => $language,
                    'href' => path(true, true, $language).get(),
                    'class' => 'button'.(($language == $Data->gettext['language']) ? ' secondary' : '')
                ));
            }
            ?>
        </div>

        <div class="actions buttonset">
            <?php
            echo $Html->a(array(
                'text' => __('Show all'),
                'href' => path(),
                'class' => 'button'.(!$Vars->var['empty'] ? ' secondary' : '')
            ));

            echo $Html->a(array(
                'text' => __('Without translation'),
                'href' => path().get('empty', 1),
                'class' => 'button'.($Vars->var['empty'] ? ' secondary' : '')
            ));
            ?>
        </div>

        <div class="flex">
            <?php
            echo $Form->search(array(
                'id' => 'search-gettext',
                'placeholder' => __('Filter'),
                'class' => 'no-appearance',
                'accesskey' => 'F'
            ));
            ?>
        </div>
    </nav>

    <script>
    $(document).ready(function () {
        $('#search-gettext').quicksearch('#list-gettext tr');
    });
    </script>
</header>

<?php if ($Data->translations): ?>

<form action="<?php echo path().get(); ?>" method="post" id="translate" class="content translate">
    <h1><?php __e('Translations'); ?></h1>

    <fieldset class="translations">
        <table>
            <thead>
                <tr>
                    <th class="num"><?php __e('Num'); ?></th>
                    <th class="msgid"><?php __e('Id'); ?></th>
                    <th><?php __e('Translation'); ?></th>
                    <th><?php __e('Comments'); ?></th>
                </tr>
            </thead>

            <tbody id="list-gettext">
                <?php $n = 0; ?>
                <?php foreach ($Data->translations['entries'] as $entry): ?>
                <tr>
                    <th class="num"><?php echo ($n + 1); ?></th>
                    <th class="msgid">
                        <label for="entry_<?php echo $n; ?>" title="<?php __e('Double click to copy'); ?>"><?php echo htmlentities($entry['msgid'], ENT_NOQUOTES, 'UTF-8'); ?></label>
                        <?php echo $Form->hidden($entry['msgid'], 'translate[entries]['.$n.'][msgid]'); ?>

                        <div class="details">
                            <div class="summary"><?php __e('References'); ?>:</div>
                            <ul>
                                <li><?php echo implode('</li><li>', $entry['references']); ?></li>
                            </ul>
                        </div>
                    </th>

                    <td class="msgstr">
                        <?php
                        echo $Form->textarea(array(
                            'name' => 'translate[entries]['.$n.'][msgstr]',
                            'value' => implode("\n", $entry['msgstr']),
                            'id' => 'entry_'.$n
                        ));
                        ?>
                    </td>

                    <td class="comments">
                        <?php
                        echo $Form->textarea(array(
                            'name' => 'translate[entries]['.$n.'][comments]',
                            'value' => implode("\n", (array) $entry['comments']),
                            'tabindex' => false
                        ));
                        ?>
                    </td>
                </tr>
                <?php $n++; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </fieldset>

    <h1><?php __e('Headers'); ?></h1>

    <fieldset class="headers">
        <?php foreach ($Data->translations['headers'] as $name => $value): ?>
        <p>
            <?php
            echo $Form->text(array(
                'name' => 'translate[headers]['.$name.']',
                'value' => $value,
                'label_text' => $name
            ));
            ?>
        </p>
        <?php endforeach; ?>
    </fieldset>

    <footer>
        <?php
        echo $Form->hidden('save', 'phpcan_action');
        echo $Form->hidden($Data->gettext['id'], 'translate[id]');
        echo $Form->hidden(array(
            'name' => 'translate[language]',
            'value' => $Data->gettext['language'],
            'id' => 'language_to_save'
        ));

        echo $Form->submit(array(
            'value' => __('Save'),
            'class' => 'right',
            'button' => true,
            'data-icon' => 'disk',
            'accesskey' => 'S'
        ));

        echo $Form->button(array(
            'value' => __('Reset'),
            'button' => true,
            'class' => 'right',
            'type' => 'reset',
            'data-icon' => 'refresh'
        ));

        foreach ($Data->gettext['languages'] as $language) {
            if ($Data->gettext['language'] != $language) {
                echo $Form->button(array(
                    'text' => __('Save as %s', $language),
                    'type' => 'submit',
                    'value' => $language,
                    'name' => 'translate[language]',
                    'class' => 'secondary save-as',
                    'button' => true,
                    'data-icon' => 'disk'
                ));
            }
        }
        ?>
    </footer>
</form>

<?php endif;
