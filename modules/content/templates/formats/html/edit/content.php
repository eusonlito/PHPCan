<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

defined('ANS') or die();

$Table = $Content->Db->getTable($info['table']);

echo $Form->textarea(array(
    'variable' => $info['varname'].'[0]',
    'value' => $info['data'][''],
    'id' => $info['id_field'],
    'class' => 'html f100',
    'lang' => $info['language'],
//	'required' => $Table->getFormatSettings($info['field'], 'required'),
    'error' => $info['error']['']
));
?>

<script type="text/javascript">
CKEDITOR_BASEPATH = '<?php echo fileWeb('common|ckeditor'); ?>/';
</script>

<?php echo $Html->jsLink('common|ckeditor/ckeditor.js'); ?>

<script type="text/javascript">
    $(document).ready(function () {
        CKEDITOR.replace('<?php echo $info['varname'].'[0]'; ?>', {
            forcePasteAsPlainText: true,
            toolbar: [
                ['Source','-','Undo','Redo','-','Link','Unlink','-','Bold','Italic','Strike','Subscript','Superscript','RemoveFormat'],
                ['Format','NumberedList','BulletedList','-','Outdent','Indent','Blockquote'],
                ['Image','Table','SpecialChar'],
                ['Find','Replace'],
                ['Preview','ShowBlocks','Maximize']
            ],
            toolbarCanCollapse: false,
            allowedContent: true
        });
    });
</script>
