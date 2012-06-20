<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

defined('ANS') or die();

foreach ($edit as $fieldset) {
    echo '<fieldset>';

    echo $Form->hidden($fieldset['action'], $fieldset['varname'].'[action]');

    foreach ($fieldset['data'] as $field) {
        $Templates->render($field['templates']['index'], array(
            'template_content' => $field['templates']['content'],
            'info' => $field['data']
        ));
    }

    foreach ($fieldset['tables'] as $added_table) {
        $Templates->render('aux-edit-form.php', array('edit' => $added_table));
    }

    echo $Form->hiddens($fieldset['vars']);

    echo '</fieldset>';
}
