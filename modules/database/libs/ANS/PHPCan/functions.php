<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

defined('ANS') or die();

/**
* function ad (string $text, [string $type])
*
* return string
*/
function ad ($text, $type = 'info')
{
    $html = '<div class="ui-widget ad">';

    switch ($type) {
        case 'error':
            $html .= '<div class="ui-state-error ui-corner-all inline-block"><p>';
            $html .= '<span class="ui-icon ui-icon-alert" style="float: left; margin-right: .3em;"></span>';
            break;

        case 'success':
            $html .= '<div class="ui-state-highlight success ui-corner-all inline-block"><p>';
            $html .= '<span class="ui-icon ui-icon-check" style="float: left; margin-right: .3em;"></span>';
            break;

        default:
            $html .= '<div class="ui-state-highlight ui-corner-all inline-block"><p>';
            $html .= '<span class="ui-icon ui-icon-info" style="float: left; margin-right: .3em;"></span>';
            break;
    }

    $html .= $text.'</p></div></div>';

    return $html;
}
