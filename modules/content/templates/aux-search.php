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

<form id="search" class="flex" action="<?php echo $action_url; ?>" method="get">
    <fieldset>
        <?php
        echo $Form->search(array(
            'variable' => 'q',
            'placeholder' => __('Search'),
            'class' => 'no-appearance',
            'accesskey' => 'F'
        ));

        echo $Form->submit(array(
            'value' => __('Search'),
            'class' => 'hidden-a11y'
        ));

        echo $Form->hiddens($Vars->get(array('all')));

        if ($Vars->exists('q')) {
            echo '<p>'.__('You are searching by "%s"', $Vars->str('q')).' | '.$Html->a('See all', path().get('q', null)).'</p>';
        }
        ?>
    </fieldset>
</form>
