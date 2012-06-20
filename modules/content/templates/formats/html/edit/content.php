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

echo $Html->jsLink('common|ckeditor/ckeditor.js');
echo $Html->jsLink('common|ckeditor/adapters/jquery.js');
?>

<script type="text/javascript">
    $(document).ready(function () {
        $('textarea.html').ckeditor({
            forcePasteAsPlainText: true,
            extraPlugins: 'abbr',
            toolbar: [
                ['Source','-','Undo','Redo','-','Link','Unlink','-','Bold','Italic','Abbr','Strike','Subscript','Superscript','RemoveFormat'],
                ['Format','NumberedList','BulletedList','-','Outdent','Indent','Blockquote'],
                ['Image','Table','SpecialChar'],
                ['Find','Replace'],
                ['Preview','ShowBlocks','Maximize']
            ],
            toolbarCanCollapse: false
        });
    });
</script>
