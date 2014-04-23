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

<header>
    <hgroup>
        <h1><?php __e('Uploads'); ?></h1>
    </hgroup>

    <nav>
        <ul>
            <?php
            echo '<li>'.$Html->a(__('Uploads'), path('uploads')).' / </li>';
            $path = '';

            foreach ($Data->path as $folder) {
                $path .= $folder.'/';

                echo '<li>'.$Html->a($folder, path('uploads').$path).' / </li>';
            }
            ?>
        </ul>

        <div class="flex">
        <?php
        echo $Form->search(array(
            'id' => 'search-files',
            'placeholder' => __('Filter'),
            'class' => 'no-appearance'
        ));
        ?>
        </div>
    </nav>
</header>

<script type="text/javascript">
    $(document).ready(function () {
        $('#search-files').quicksearch('ul li.file, ul li.folder', {
            stripeRows: ['name']
        });

        var $files = $('#new-file');

        $files.click(function () {
            $('#input-upload-file').click();
        });

        if (typeof(FileReader) == "undefined") {
            $('#input-upload-file').change(function () {
                $(this).parents('form').submit();
            });
        } else {
            var slugify = function (text) {
                text = text.replace(/[^-a-zA-Z0-9,&\s]+/ig,'');
                text = text.replace(/-/gi,"_");
                text = text.replace(/\s/gi,"-");

                return text;
            }

            $files.bind('dragover', function () {
                $(this).addClass('hover');
            });
            $files.bind('dragleave', function () {
                $(this).removeClass('hover');
            });

            $files.add('#input-upload-file').html5Uploader({
                name: 'files',
                postUrl: '<?php
                    echo path().get(array(
                        'phpcan_action' => 'upload',
                        'phpcan_exit_mode' => 'ajax',
                        'path' => implode('/', $Data->path)
                    ));
                ?>',
                onServerLoadStart: function (e, file) {
                    var $loading = $('#loading ul');

                    if (!$loading.length) {
                        $loading = $('<div id="loading"><ul></ul></div>').insertAfter('div.content').find('ul');
                    }

                    $('<li class="uploading"></li>')
                        .prependTo($loading).attr('id', 'file-' + slugify(file.name))
                        .html('<strong>' + file.name + '</strong><progress max="' + e.total + '"></progress>');
                },
                onServerProgress: function (e, file) {
                    $('#file-' + slugify(file.name)).find('progress').prop('value', e.loaded);
                },
                onServerLoad: function (e, file) {
                    $('#file-' + slugify(file.name)).removeClass('uploading').find('progress').prop('value', e.total);

                    if (!$('#loading li.uploading').length) {
                        $.cookie('phpcan_message', '<?php __e('Files Uploaded successfully'); ?>', {
                            path: '<?php echo BASE_WWW; ?>'
                        });
                        $.cookie('phpcan_message_type', '<?php __e('success'); ?>', {
                            path: '<?php echo BASE_WWW; ?>'
                        });

                        document.location.href = document.location.href;
                    }
                },
            });
        }

        $('#new-folder').click(function () {
            var name = $.trim(prompt('<?php __e('Folder name'); ?>'));

            if (name) {
                $('#input-new-folder').val(name).parents('form').submit();
            }
        });
    });
</script>

<div class="content">
    <form action="<?php echo path(); ?>" method="post" enctype="multipart/form-data" class="hidden-a11y">
        <fieldset>
            <?php
            echo $Form->file(array(
                'name' => 'files',
                'id' => 'input-upload-file',
                'multiple' => true
            ));

            echo $Form->hidden('upload', 'phpcan_action');
            echo $Form->hidden(implode('/', $Data->path), 'path');
            echo $Form->submit(__('Upload files'));
            ?>
        </fieldset>
    </form>

    <form action="<?php echo path(); ?>" method="post" class="hidden-a11y">
        <fieldset>
            <?php
            echo $Form->Text(array(
                'name' => 'folder',
                'id' => 'input-new-folder',
            ));

            echo $Form->hidden('create-folder', 'phpcan_action');
            echo $Form->hidden(implode('/', $Data->path), 'path');
            echo $Form->submit(__('Create new folder'));
            ?>
        </fieldset>
    </form>

    <ul>
        <li id="new-file">
            <?php __e('Click or drop files here to upload'); ?>
        </li>
        <li id="new-folder">
            <?php __e('Click here to create a new folder'); ?>
        </li>

        <?php foreach ($Data->folders as $folder): ?>
        <li class="folder">
            <div class="main">
            <?php
            echo $Html->a(array(
                'text' => $folder['name'],
                'href' => path('uploads').$folder['path']
            ));
            ?>
            </div>
        </li>
        <?php endforeach; ?>

        <?php foreach ($Data->files as $file): ?>
        <li class="file">
            <div class="options">
                <?php
                echo $Html->a(array(
                    'title' => $file['name'],
                    'text' => __('View or download'),
                    'href' => $Html->imgSrc($file['path']),
                    'rel' => ($file['type'] == 'image') ? 'alternate' : null,
                    'target' => ($file['type'] == 'image') ? null : '_blank'
                ));
                ?>

                <?php echo '<label>'.__('Source').': '.$Form->text($Html->imgSrc($file['path'])).'</label>'; ?>

                <?php if ($file['type'] == 'image'): ?>
                    <?php echo '<label>'.__('Dinamic').': '.$Form->text($Html->imgSrc($file['path'], 'your_options')).'</label>'; ?>
                <?php endif; ?>

                <?php
                echo $Html->a(array(
                    'text' => __('Delete file'),
                    'title' => __('Delete file'),
                    'data-confirm-delete' => 'true',
                    'class' => 'bottom',
                    'href' => path(),
                    'action' => array(
                        'name' => 'delete-file',
                        'params' => array(
                            'file' => $file['path']
                        )
                    )
                )); ?>
            </div>

            <div class="main">
                <?php
                if ($file['type'] == 'image') {
                    echo '<span class="name hidden">'.$file['name'].'</span>';
                    echo $Html->img($file['path'], '', 'zoomCrop,120,120');
                } else {
                    echo '<span class="name">'.$file['name'].'</span>';
                    echo '<strong class="bottom">.'.$file['extension'].'</strong>';
                }
                ?>
            </div>
        </li>
        <?php endforeach; ?>
    </ul>

    <?php if (!$Data->folders && !$Data->files): ?>
    <?php echo ad(__('This folder is empty')); ?>
    <?php endif; ?>

    <?php if ($Data->path): ?>

    <footer>
        <?php
        echo $Html->a(array(
            'text' => (($Data->folders || $Data->files) ? __('Delete folder <strong>%s</strong> and its content', end($Data->path)) : __('Delete this folder')),
            'data-icon' => 'trash',
            'data-confirm-delete' => (($Data->folders || $Data->files) ? __('Are you sure that you want to delete folder %s and its content?', end($Data->path)) : __('Are you sure that you want to delete this empty folder?')),
            'class' => 'button secondary',
            'href' => path(),
            'action' => array(
                'name' => 'delete-folder',
                'params' => array(
                    'path' => implode('/', $Data->path)
                )
            )
        ));
        ?>
    </footer>
    <?php endif; ?>
</div>
