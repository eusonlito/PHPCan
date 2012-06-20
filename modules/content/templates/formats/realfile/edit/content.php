<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

defined('ANS') or die();

echo $Form->file(array(
    'name' => $info['varname'].'[0][name]',
    'id' => $info['id_field'],
    'error' => $info['error']['name']
));
?>

<p>
    <?php
    if ($info['data']['location']) {
        echo '<label>';
        echo $Form->checkbox(array(
            'name' => $info['varname'].'[0][name]',
            'force' => false,
            'value' => 1
        ));
        echo ' '.__('Delete file').'</label> | ';
    }
    ?>

    <span class="link" id="<?php echo $info['id_field'] ?>_url"><?php __e('Upload image from an url'); ?></span>
    <span class="link hidden" id="<?php echo $info['id_field'] ?>_computer"><?php __e('Upload image from my computer'); ?></span>

    <?php
    if ($info['data']['location']) {
        echo ' | '.$Html->a(array(
            'text' => __('View file'),
            'href' => $Html->imgSrc($info['data']['location']),
            'target' => '_blank',
            'class' => 'link no-colorbox'
        ));
    }
    ?>
</p>

<script type="text/javascript">
    $(document).ready(function () {
        $('#<?php echo $info['id_field']; ?>_url').click(function () {
            $('#<?php echo $info['id_field']; ?>').changeInputType('url').addClass('f50');
            $(this).hide();
            $('#<?php echo $info['id_field']; ?>_computer').show();

            return false;
        });

        $('#<?php echo $info['id_field']; ?>_computer').click(function () {
            $('#<?php echo $info['id_field']; ?>').changeInputType('file').addClass('f100');
            $(this).hide();
            $('#<?php echo $info['id_field']; ?>_url').show();

            return false;
        });
    });
</script>
