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
    'regular' => array(
        'table' => 'users',
        'name' => 'session',
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
            'user' => array('user', 'email'),
            'password' => 'password',
            'name' => 'name',
            'avatar' => 'avatar'
        )
    ),

    'facebook' => array(
        'table' => 'users',
        'name' => 'session',
        'maintain_time' => 3600 * 24 * 10,
        'encrypt' => 'md5',
        'allow_duplicates' => true,

        'id_field' => 'facebook_id',
        'user_field' => 'facebook_user',
        'username_field' => 'facebook_name',
        'password_field' => 'password',
        'password_tmp_field' => 'password_tmp',
        'enabled_field' => 'enabled',
        'avatar_field' => 'facebook_avatar',
        'signup_date_field' => 'signup_date',
        'unsubscribe_field' => 'unsubscribed',

        'raw_field' => 'facebook_raw',

        'fields' => array(
            'uid' => 'facebook_id',
            'name' => array('facebook_name', 'name'),
            'email' => array('facebook_email', 'email'),
            'username' => 'facebook_user',
            'pic_big' => array('facebook_avatar', 'avatar')
        ),

        'disable_update' => array('user', 'name', 'avatar'),

        'facebook_fields' => array('uid', 'username', 'name', 'pic_big', 'profile_url', 'current_location', 'email')
    ),

    'twitter' => array(
        'table' => 'users',
        'name' => 'session',
        'maintain_time' => 3600 * 24 * 10,
        'encrypt' => 'md5',
        'allow_duplicates' => true,

        'id_field' => 'twitter_id',
        'user_field' => 'twitter_user',
        'username_field' => 'twitter_name',
        'password_field' => 'password',
        'password_tmp_field' => 'password_tmp',
        'enabled_field' => 'enabled',
        'avatar_field' => 'twitter_avatar',
        'signup_date_field' => 'signup_date',
        'unsubscribe_field' => 'unsubscribed',

        'raw_field' => 'twitter_raw',
        'token_field' => 'twitter_token',
        'token_secret_field' => 'twitter_token_secret',

        'request_token' => 'http://twitter.com/oauth/request_token',
        'access_token' => 'http://twitter.com/oauth/access_token',
        'authorize' => 'http://twitter.com/oauth/authorize',

        'fields' => array(
            'id' => 'twitter_id',
            'name' => array('twitter_name', 'name'),
            'screen_name' => 'twitter_user',
            'profile_image_url' => array('twitter_avatar', 'avatar'),
            'twitter_allow' => 'twitter_allow'
        ),

        'disable_update' => array('user', 'name', 'avatar'),

        'twitter_fields' => array('id', 'name', 'screen_name', 'profile_image_url', 'location')
    )
);
