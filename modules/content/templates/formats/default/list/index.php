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

<td rowspan="<?php echo $info['rows']; ?>" class="format_<?php echo $info['format']; ?>">
    <?php $Templates->render($template_content, array('info' => $info)); ?>
</td>
