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

echo $Form->url(array(
    'variable' => $info['varname'].'[url]',
    'value' => $info['data']['url'],
    'id' => $info['id_field'],
    'class' => 'f50',
    'required' => $Table->getFormatSettings($info['field'], 'required'),
    'error' => $info['error']['url']
));

if ($info['data']['url']) {
    $Html_media = new \PHPCan\Templates\Html\Media($Html);

    echo '<div class="f50 media">';
        echo $Html_media->media($info['data']);

        echo '<p>'.$info['data']['type'].' ';
        echo $Html->a(array(
            'text' => __('Open url (new window)'),
            'href' => $info['data']['url'],
            'target' => '_blank',
            'class' => 'link'
        ));
        echo '</p>';
    echo '</div>';
}
