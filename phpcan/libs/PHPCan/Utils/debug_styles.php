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

<style type="text/css">

.phpcan_debug_e,
.phpcan_debug_show {
    color: #000;
    display: block;
    padding: 20px;
    margin: 20px;
    text-align: left;
    font-size: 13px;
    line-height: 14px;
    white-space: pre-wrap;
    position: relative;
    z-index: 10000;
    background: #FFF;

    border-radius: 8px;
    -moz-border-radius: 8px;
    -webkit-border-radius: 8px;
}
.phpcan_debug_note {
    background: #EFEFEF;
}
.phpcan_debug_timing {
    background: #EFECD1;
}
.phpcan_debug_show h1,
.phpcan_debug_e h1 {
    color: #000;
    font-family: Arial;
    font-weight: bold;
    font-size: 1.4em;
    }

.phpcan_debug_menu li a {
    background: #bbb;
    padding: 5px;
    line-height: 2em;
    text-decoration: none;
    color: #fff;
    }
.phpcan_debug_menu li a:hover {
    background: #fff;
    color: #bbb;
    }

/* E */
.phpcan_debug_e {
    color: #999;
    white-space: pre-wrap;
    }

.phpcan_debug_e .subtitle {
    color: #000;
    font-family: Arial;
    }

/* Show */
.phpcan_debug_show p {
    background: #dcdcdc;
    font-style: italic;
    padding: 10px;
    margin-bottom: 20px;
    }
.phpcan_debug_show li {
    background: #efefef;
    }
.phpcan_debug_show dt {
    float: left;
    margin: 0 5px 0 0;
    background: #ccc;
    padding: 5px;
    }
.phpcan_debug_show dd {
    padding: 5px;
    }
.phpcan_debug_show code {
    color: #999;
    display: inline;
    line-height: 13px;
    white-space: pre-wrap;
    }
</style>
