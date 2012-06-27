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
* function format (string $text, [string $add])
*
* return string
*/
function format ($text, $add = '')
{
    if (!is_string($text)) {
        return $text;
    }

    $text = ucfirst(str_replace(array('_', '-'), ' ', $text));

    if ($add) {
        $text .= '('.$add.')';
    }

    $text = str_replace('(', ' (', $text);

    return $text;
}

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

/**
* function humanSize (integer $bytes)
*
* Return the human size from an integer value
*
* return string
*/

function humanSize ($bytes)
{
    $div = 1;
    $suffix = '';

    if ($bytes < 1024) {
        $suffix = 'B';
    } elseif ($bytes < 1048576) {
        $div = 1024;
        $suffix = 'KB';
    } elseif ($bytes < 1073741824) {
        $div = 1048576;
        $suffix = 'MB';
    } elseif ($bytes < 1099511627776) {
        $div = 1073741824;
        $suffix = 'GB';
    } else {
        $div = 1099511627776;
        $suffix = 'TB';
    }

    return number_format(round(($bytes / $div), 2)).' '.$suffix;
}
