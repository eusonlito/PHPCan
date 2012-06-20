<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

defined('ANS') or die();

if ($info['data']['']) {
    echo $Html->a(array(
        'text' => $Html->img($info['data'][''], '', 'zoomCrop,50,50'),
        'title' => $Html->imgSrc($info['data']['']),
        'href' => $Html->imgSrc($info['data']['']),
        'style' => 'float: left; margin-right: 20px'
    ));
}

echo $Form->file(array(
    'name' => $info['varname'].'[0]',
    'id' => $info['id_field'],
    'error' => $info['error']['']
));
?>

<p>
    <?php
    if ($info['data']['']) {
        echo '<label>';
        echo $Form->checkbox(array(
            'name' => $info['varname'].'[0]',
            'force' => false,
            'value' => 1
        ));
        echo ' '.__('Delete image').'</label> | ';
    }
    ?>

    <span class="link" id="<?php echo $info['id_field'] ?>_url"><?php __e('Upload image from an url'); ?></span>
    <span class="link hidden" id="<?php echo $info['id_field'] ?>_computer"><?php __e('Upload image from my computer'); ?></span>
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
            $('#<?php echo $info['id_field']; ?>').changeInputType('file').removeClass('f50');
            $(this).hide();
            $('#<?php echo $info['id_field']; ?>_url').show();

            return false;
        });
    });
</script>
