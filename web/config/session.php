<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

defined('ANS') or die();

$config['session'] = array(
    'sessions' => array(
        'regular' => array(
            'table' => 'users',
            'name' => 'session-regular',
            'maintain_time' => 3600 * 24 * 10,
            'encrypt' => 'md5',
            'allow_duplicates' => true,

            'id_field' => 'id',
            'user_field' => 'user',
            'username_field' => 'name',
            'password_field' => 'password',
            'password_tmp_field' => 'password_tmp',
            'enabled_field' => 'enabled',
            'avatar_field' => 'avatar',
            'signup_date_field' => 'signup_date',
            'unsubscribe_field' => 'unsubscribed',

            'fields' => array(
                'user' => 'user',
                'password' => 'password',
                'name' => 'name',
                'email' => 'email',
                'avatar' => 'avatar',
                'language' => 'language'
            )
        )
    )
);
