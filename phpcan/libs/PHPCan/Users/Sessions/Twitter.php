<?php
/**
* phpCan v.1
*
* $Id: class_session_regular.php 261 2011-01-30 21:40:59Z lito $
*
* 2009 Copyright - phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

namespace PHPCan\Users\Sessions;

defined('ANS') or die();

use \PHPCan\Apis;

class Twitter extends Oauth2client implements Isession {
    protected $Debug = object;
    protected $Errors = object;
    protected $settings = array();

    public $twitter = array();

    private $API = false;
    private $logged = false;
    private $token = array();
    private $user = array();
    private $new = false;

    /*
     * public function __construct ($settings)
     */
    public function __construct ($settings)
    {
        global $Debug, $Errors;

        $this->Debug = $Debug;
        $this->Errors = $Errors;

        $this->settings = array_merge(array(
            'errors' => 'session-twitter',
        ), $settings['common'], $settings['sessions']['twitter']);
    }

    /*
    * public function load (void)
    *
    * return array
    */
    public function load ()
    {
        $session = $this->getCookie('data');
        $control = $this->getCookie('control');

        if ($session && $session[$this->settings['user_field']] && $session[$this->settings['id_field']]) {
            $user = decrypt($session[$this->settings['user_field']]);
            $id = decrypt($session[$this->settings['id_field']]);

            $exists = $this->userExists(array(
                $this->settings['id_field'] => $id,
                $this->settings['user_field'] => $user
            ));

            if (!$exists) {
                return false;
            }
        } elseif ($control['1'] && $control['2']) {
            $user = decrypt($control['1']);
            $exists = $this->userExists(array(
                $this->settings['user_field'] => $user
            ));

            if (!$exists) {
                return false;
            }

            $id = $exists[$this->settings['id_field']];

            if ($this->encode($user.$id) !== $control['2']) {
                return false;
            }
        } else {
            global $Session;

            $exists = $Session->user('');

            if (!$exists[$this->settings['id_field']] || !$exists[$this->settings['token_field']] || !$exists[$this->settings['token_secret_field']]) {
                return false;
            }
        }

        if ($exists[$this->settings['token_field']] && $exists[$this->settings['token_secret_field']]) {
            $this->token = array(
                'oauth_token' => $exists[$this->settings['token_field']],
                'oauth_token_secret' => $exists[$this->settings['token_secret_field']]
            );

            $this->setCookie('control', $this->token);
        }

        $this->user = $exists;
        $this->user['ip'] = ip();

        $this->logged = true;

        return $this->user;
    }

    private function setAPI ($oauth_token = '', $oauth_token_secret = '')
    {
        if ($oauth_token && $oauth_token_secret) {

            $this->API = new Twitter\TwitterOAuth($this->settings['api_key'], $this->settings['secret_key'], $oauth_token, $oauth_token_secret);
        } else {
            $this->API = new Twitter\TwitterOAuth($this->settings['api_key'], $this->settings['secret_key']);
        }
    }

    public function API ()
    {
        if ($this->API) {
            return $this->API;
        }

        $control = $this->getCookie('control');

        if (is_array($control) && $control['oauth_token'] && $control['oauth_token_secret']) {
            $this->setAPI($control['oauth_token'], $control['oauth_token_secret']);
        } else {
            $this->setAPI();
        }

        return $this->API;
    }

    public function setToken ($url = '', $force = false)
    {
        if (!$force && $this->token) {
            return $this->token;
        }

        $this->token = $this->API()->getRequestToken($url);

        while (!$this->token['oauth_token']) {
            $this->logout();
            $this->load();

            $this->token = $this->API()->getRequestToken($url);
        }

        $this->setCookie('control', array(
            'oauth_token' => $this->token['oauth_token'],
            'oauth_token_secret' => $this->token['oauth_token_secret']
        ));
    }

    public function getAuthorizeURL ($url = '')
    {
        $this->setToken($url);

        return $this->API()->getAuthorizeURL($this->token['oauth_token']);
    }

    /*
    * public function login (void)
    *
    * return boolean
    */
    public function loginService ()
    {
        if ($this->twitter) {
            return $this->twitter;
        }

        if ($_GET['oauth_verifier']) {
            $this->token = $this->API()->getAccessToken($_GET['oauth_verifier']);
        }

        $this->twitter = $this->API()->get('account/verify_credentials');

        if (!$this->twitter || $this->twitter->error) {
            $this->twitter = false;
        }

        return $this->twitter;
    }

    /*
    * public function login (void)
    *
    * return boolean
    */
    public function login ($data = array())
    {
        if (!$this->loginService()) {
            return false;
        }

        $user = $this->userExists(array(
            $this->settings['id_field'] => $this->twitter->id
        ));

        $this->new = $user ? false : true;

        $this->setCookie('data', array(
            $this->settings['user_field'] => encrypt($user[$this->settings['user_field']]),
            $this->settings['id_field'] => encrypt($user[$this->settings['id_field']])
        ));

        $this->user = $user;
        $this->logged = true;

        return $user;
    }

    /**
    * public function logout ()
    *
    * return boolean
    */
    public function logout ()
    {
        $this->deleteCookie();

        $this->id = 0;
        $this->user = array();
        $this->logged = false;
    }

    public function getTwitterFields ()
    {
        $info = array();

        foreach ($this->settings['fields'] as $field => $fields) {
            if (!is_array($fields)) {
                $fields = array($fields);
            }

            foreach ($fields as $each) {
                if (isset($this->twitter->$field)) {
                    $info[$each] = $this->twitter->$field;
                }
            }
        }

        return $info;
    }

    /*
    * public function userAdd (void)
    *
    * return boolean
    */
    public function userAdd ()
    {
        if ($this->API() && !$this->twitter) {
            return false;
        }

        global $Session, $Db;

        $user = $Session->user();

        if ($user) {
            $this->updateTwitterInfo();

            $Db->update(array(
                'table' => $this->settings['users_table'],
                'data' => array(
                    $this->settings['fields']['twitter_allow'] => 1
                ),
                'conditions' => array(
                    'id' => $Session->user('id')
                ),
                'limit' => 1
            ));

            return true;
        }

        $save_info = $this->getTwitterFields();

        $save_info[$this->settings['fields']['twitter_allow']] = 1;
        $save_info[$this->settings['enabled_field']] = 1;
        $save_info[$this->settings['signup_date_field']] = date('Y-m-d H:i:s');

        if ($this->token['oauth_token'] && $this->token['oauth_token_secret']) {
            $save_info[$this->settings['token_field']] = $this->token['oauth_token'];
            $save_info[$this->settings['token_secret_field']] = $this->token['oauth_token_secret'];
        }

        $Db->insert(array(
            'table' => $this->settings['users_table'],
            'data' => $save_info
        ));

        return true;
    }

    /*
    * private function updateTwitterInfo ()
    *
    * return false/array
    */
    public function updateTwitterInfo ()
    {
        $save_info = array();

        $save_info = $this->getTwitterFields();

        if ($this->settings['disable_update']) {
            foreach ($this->settings['disable_update'] as $field) {
                unset($save_info[$field]);
            }
        }

        if ($this->token['oauth_token'] && $this->token['oauth_token_secret']) {
            $save_info[$this->settings['token_field']] = $this->token['oauth_token'];
            $save_info[$this->settings['token_secret_field']] = $this->token['oauth_token_secret'];
        }

        global $Db, $Session;

        $query = array(
            'table' => $this->settings['users_table'],
            'data' => $save_info,
            'conditions' => array(
                'id' => $Session->user('id')
            ),
            'limit' => 1
        );

        $ok = $Db->update($query);

        if (!$ok) {
            $this->error = __('Error_saving_data');
        }

        return true;
    }

    /**
    * public function userEdit (array $info)
    *
    * return boolean
    */
    public function userEdit ($info)
    {
        if ($this->user['id'] != $info['id']) {
            return true;
        }

        global $Db;

        $settings = $this->settings;

        //Check if user exists
        $user = $this->userExists(array(
            'id' => $this->user['id'],
            $settings['enabled_field'] => 1
        ));

        if (!$user) {
            $this->Errors->set($settings['errors'], __('User not exists'));

            return false;
        }

        $this->updateTwitterInfo();

        $info['data'] = $this->userData($info['data']);

        return $Db->save(array(
            'update' => array(
                'table' => $this->settings['users_table'],
                'data' => $info['data'],
                'conditions' => array(
                    'id' => $this->user['id']
                ),
                'limit' => 1
            )
        ));
    }

    /*
    * protected function maintain (string $user, string $password)
    *
    * return boolean
    */
    protected function maintain ($user, $password)
    {
        return true;
    }

    /*
    * protected function encode (string $text)
    *
    * return string
    */
    public function encode ($text)
    {
        return hash($this->settings['encrypt'], $text);
    }

    /*
    * public function unsubscribeUser (void)
    *
    * Function to disable user accout. All user info will be cleaned and
    * the username will be updated with a generic value
    *
    * return boolean
    */
    public function unsubscribeUser ()
    {
        global $Db;

        return $Db->update(array(
            'table' => $settings['users_table'],
            'data' => array(
                $this->config['unsubscribe_field'] => 1,
                $this->config['enabled_field'] => 0
            ),
            'conditions' => array(
                'id' => $this->user('id')
            ),
            'limit' => 1
        ));
    }

    /*
    * private function userData (array $user_data)
    *
    * Create data for edit/create user
    *
    * return false/array
    */
    private function userData ($user_data)
    {
        $data = array();

        foreach ($this->settings['fields'] as $name => $dbfield) {
            if (is_string($user_data[$name])) {
                $data[$dbfield] = trim($user_data[$name]);
            } elseif (isset($user_data[$name])) {
                $data[$dbfield] = $user_data[$name];
            }
        }

        return $data;
    }

    /*
    * public function userExists ([array $conditions])
    *
    * Check if exists an user
    *
    * return array
    */
    public function userExists ($conditions)
    {
        global $Db;

        $query = array(
            'table' => $this->settings['users_table'],
            'fields' => '*',
            'conditions' => $conditions,
            'limit' => 1
        );

        return $Db->select($query);
    }

    private function setCookie ($name, $value)
    {
        global $Vars;

        $exists = $this->getCookie($name);

        if ($exists && is_array($exists) && is_array($value)) {
            $value = array_merge($exists, $value);
        }

        $Vars->setCookie($this->settings['name'].'-'.$name, $value, $this->settings['maintain_time']);

        return $value;
    }

    private function getCookie ($name)
    {
        global $Vars;

        return $Vars->getCookie($this->settings['name'].'-'.$name);
    }

    private function deleteCookie ($name = '')
    {
        global $Vars;

        if ($name) {
            return $Vars->deleteCookie($this->settings['name'].'-'.$name);
        } else {
            foreach (array_keys($_COOKIE) as $name) {
                if (strstr($name, $this->settings['name'])) {
                    $Vars->deleteCookie($name);
                }
            }

            return true;
        }
    }
}
