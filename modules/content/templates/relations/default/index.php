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

<div class="format">
    <label class="field f20">
        <strong><?php echo $Content->info($Vars->get('connection'), 'name', $info['title'] ?: $info['table']); ?></strong>
    </label>

    <div class="f80">
        <?php $Templates->render($template_content, array('info' => $info)); ?>
    </div>
</div>
