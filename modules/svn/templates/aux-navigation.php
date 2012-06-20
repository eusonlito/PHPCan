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

<form action="<?php echo path(); ?>" method="post">
    <fieldset>
    <?php
    echo $Form->button(array(
        'text' => __('Update'),
        'name' => 'phpcan_action',
        'value' => 'svn-update',
        'type' => 'submit',
        'data-icon' => 'transferthick-e-w'
    ));
    ?>
    </fieldset>
</form>
