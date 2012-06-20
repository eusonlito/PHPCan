<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

defined('ANS') or die();

$config['i18n'] = array(
    'time_formats' => array(
        'default' => array(
            604800 => array(__('$l $j, $F $Y'), true),
            172800 => array(__('$d days ago'), false),
            86400 => array(__('one day ago'), false),
            7200 => array(__('$h hours ago'), false),
            3600 => array(__('one hour ago'), false),
            120 => array(__('$i minutes ago'), false),
            60 => array(__('one minute ago'), false),
            0 => array(__('some seconds ago'), false),
            -1 => array(__('$l $j, $F $Y'), true)
        ),
        'absolute' => array(
            array(__('$l $j, $F $Y'), true)
        ),
        'absolute-hour' => array(
            array(__('at $H:$i on $l $j, $F $Y'), true)
        ),
        'absolute-time' => array(
            array(__('at $H:$i:$s on $l $j, $F $Y'), true)
        )
    ),
    'time_translations' => array(
        '$l_Monday' => __('Monday'),
        '$l_Tuesday' => __('Tuesday'),
        '$l_Wednesday' => __('Wednesday'),
        '$l_Thursday' => __('Thursday'),
        '$l_Friday' => __('Friday'),
        '$l_Saturday' => __('Saturday'),
        '$l_Sunday' => __('Sunday'),

        '$D_Mon' => __('Mon'),
        '$D_Tue' => __('Tue'),
        '$D_Wed' => __('Wed'),
        '$D_Thu' => __('Thu'),
        '$D_Fri' => __('Fri'),
        '$D_Sat' => __('Sat'),
        '$D_Sun' => __('Sun'),

        '$F_January' => __('January'),
        '$F_February' => __('February'),
        '$F_March' => __('March'),
        '$F_April' => __('April'),
        '$F_May' => __('May_F'),
        '$F_June' => __('June'),
        '$F_July' => __('July'),
        '$F_August' => __('August'),
        '$F_September' => __('September'),
        '$F_October' => __('October'),
        '$F_November' => __('November'),
        '$F_December' => __('December'),

        '$n_1' => __('January'),
        '$n_2' => __('February'),
        '$n_3' => __('March'),
        '$n_4' => __('April'),
        '$n_5' => __('May_F'),
        '$n_6' => __('June'),
        '$n_7' => __('July'),
        '$n_8' => __('August'),
        '$n_9' => __('September'),
        '$n_10' => __('October'),
        '$n_11' => __('November'),
        '$n_12' => __('December'),

        '$M_Jan' => __('Jan'),
        '$M_Feb' => __('Feb'),
        '$M_Mar' => __('Mar'),
        '$M_Apr' => __('Apr'),
        '$M_May' => __('May'),
        '$M_Jun' => __('Jun'),
        '$M_Jul' => __('Jul'),
        '$M_Aug' => __('Aug'),
        '$M_Sep' => __('Sep'),
        '$M_Oct' => __('Oct'),
        '$M_Nov' => __('Nov'),
        '$M_Dec' => __('Dec'),

        '$a_AM' => __('AM'),
        '$a_PM' => __('PM'),
        '$A_am' => __('am'),
        '$A_pm' => __('pm')
    )
);
