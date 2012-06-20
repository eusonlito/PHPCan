<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

defined('ANS') or die();

if (!$info['data']['']) {
    echo '&nbsp;';

    return true;
}

echo $Html->a(array(
    'href' => $Html->imgSrc($info['data']['']),
    'title' => $Html->imgSrc($info['data']['']),
    'text' => $Html->img($info['data'][''], '', 'zoomCrop,60,60')
));
