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

namespace ANS\PHPCan\Users\Sessions;

defined('ANS') or die();

use \ANS\PHPCan\Apis;

class Twitter implements Isession {
    protected $Debug = object;
    protected $Errors = object;
    protected $settings = array();
    protected $conditions = array();

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
        $this->settings = $settings;
        $this->settings['errors'] = $this->settings['errors'] ?: $this->settings['name'];
    }

    /*
    * public function setConditions (array $conditions)
    *
    * return array
    */
    public function setConditions ($conditions)
    {
        if (is_array($conditions)) {
            return $this->conditions = $conditions;
        }
    }

    /*
    * public function load (void)
    *
    * return array
    */
    public function load ()
    {
        $settings = $this->settings;

        $session = $this->getCookie('data');
        $control = $this->getCookie('control');

        if ($session && $session[$settings['user_field']] && $session[$settings['id_field']]) {
            $user = decrypt($session[$settings['user_field']]);
            $id = decrypt($session[$settings['id_field']]);

            $exists = $this->userExists(array(
                $settings['id_field'] => $id,
                $settings['user_field'] => $user
            ));

            if (empty($exists)) {
                return false;
            }
        } else if ($control['1'] && $control['2']) {
            $user = decrypt($control['1']);
            $exists = $this->userExists(array(
                $settings['user_field'] => $user
            ));

            if (empty($exists)) {
                return false;
            }

            $id = $exists[$settings['id_field']];

            if ($this->encode($user.$id) !== $control['2']) {
                return false;
            }
        } else {
            global $Session;

            $exists = $Session->user('');

            if (empty($exists[$settings['id_field']]) || empty($exists[$settings['token_field']]) || empty($exists[$settings['token_secret_field']])) {
                return false;
            }
        }

        if ($exists[$settings['token_field']] && $exists[$settings['token_secret_field']]) {
            $this->token = array(
                'oauth_token' => $exists[$settings['token_field']],
                'oauth_token_secret' => $exists[$settings['token_secret_field']]
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
        $settings = $this->settings;

        if ($oauth_token && $oauth_token_secret) {
            $this->API = new Twitter\TwitterOAuth($settings['consumer_key'], $settings['consumer_secret'], $oauth_token, $oauth_token_secret);
        } else {
            $this->API = new Twitter\TwitterOAuth($settings['consumer_key'], $settings['consumer_secret']);
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
        if (empty($force) && $this->token) {
            return $this->token;
        }

        $this->token = $this->API()->getRequestToken($url);

        while (empty($this->token['oauth_token'])) {
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

        if (empty($this->twitter) || $this->twitter->error) {
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

        $settings = $this->settings;

        $user = $this->userExists(array(
            $settings['id_field'] => $this->twitter->id
        ));

        $this->new = $user ? false : true;

        $this->setCookie('data', array(
            $settings['user_field'] => encrypt($user[$settings['user_field']]),
            $settings['id_field'] => encrypt($user[$settings['id_field']])
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
        if ($this->API() && empty($this->twitter)) {
            return false;
        }

        global $Session, $Db;

        $settings = $this->settings;
        $user = $Session->user();

        if ($user) {
            $this->updateTwitterInfo();

            $Db->update(array(
                'table' => $settings['table'],
                'data' => array(
                    $settings['fields']['twitter_allow'] => 1
                ),
                'conditions' => array(
                    'id' => $Session->user('id')
                ),
                'limit' => 1
            ));

            return true;
        }

        $save_info = $this->getTwitterFields();

        if ($settings['enabled_field']) {
            $save_info[$settings['enabled_field']] = 1;
        } if ($settings['signup_date_field']) {
            $save_info[$settings['signup_date_field']] = date('Y-m-d H:i:s');
        } if ($settings['raw_field']) {
            $save_info[$settings['raw_field']] = base64_encode(serialize($this->twitter));
        }

        $save_info[$settings['fields']['twitter_allow']] = 1;

        if ($this->token['oauth_token'] && $this->token['oauth_token_secret']) {
            $save_info[$settings['token_field']] = $this->token['oauth_token'];
            $save_info[$settings['token_secret_field']] = $this->token['oauth_token_secret'];
        }

        $Db->insert(array(
            'table' => $settings['table'],
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
        if ($this->API() && empty($this->facebook)) {
            return false;
        }

        $settings = $this->settings;
        $save_info = $this->getTwitterFields();

        if ($settings['disable_update']) {
            foreach ($settings['disable_update'] as $field) {
                unset($save_info[$field]);
            }
        }

        if ($settings['raw_field']) {
            $save_info[$settings['raw_field']] = base64_encode(serialize($this->twitter));
        }

        if ($this->token['oauth_token'] && $this->token['oauth_token_secret']) {
            $save_info[$settings['token_field']] = $this->token['oauth_token'];
            $save_info[$settings['token_secret_field']] = $this->token['oauth_token_secret'];
        }

        global $Db, $Session;

        $query = array(
            'table' => $settings['table'],
            'data' => $save_info,
            'conditions' => array(
                'id' => $Session->user('id')
            ),
            'limit' => 1
        );

        $ok = $Db->update($query);

        if (empty($ok)) {
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

        $conditions = array(
            'id' => $this->user['id']
        );

        if ($settings['enabled_field']) {
            $info['enabled_field'] = 1;
        }

        //Check if user exists
        $user = $this->userExists($conditions);

        if (empty($user)) {
            $this->Errors->set($settings['errors'], __('User not exists'));
            return false;
        }

        $this->updateTwitterInfo();

        $info['data'] = $this->userData($info['data']);

        return $Db->update(array(
            'table' => $settings['table'],
            'data' => $info['data'],
            'conditions' => array(
                'id' => $this->user['id']
            ),
            'limit' => 1
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

        $settings = $this->settings;
        $data = array();

        if ($settings['unsubscribe_field']) {
            $data[$settings['unsubscribe_field']] = 1;
        }

        if ($settings['unsubscribe_date_field']) {
            $data[$settings['unsubscribe_date_field']] = 1;
        }

        if ($settings['enabled_field']) {
            $data[$settings['enabled_field']] = 0;
        }

        if (empty($data)) {
            return true;
        }

        return $Db->update(array(
            'table' => $settings['table'],
            'data' => $data,
            'conditions' => array(
                'id' => $this->user('id')
            ),
            'limit' => 1
        ));
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
            'table' => $this->settings['table'],
            'fields' => '*',
            'conditions' => array_merge($this->conditions, $conditions),
            'limit' => 1
        );

        return $Db->select($query);
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
            if (is_string($user_data[$name]) || isset($user_data[$name])) {
                if (is_array($dbfield)) {
                    foreach ($dbfield as $field) {
                        $data[$field] = is_string($user_data[$name]) ? trim($user_data[$name]) : $user_data[$name];
                    }
                } else {
                    $data[$dbfield] = is_string($user_data[$name]) ? trim($user_data[$name]) : $user_data[$name];
                }
            }
        }

        return $data;
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

        $settings = $this->settings;

        if ($name) {
            return $Vars->deleteCookie($settings['name'].'-'.$name);
        } else {
            $this->deleteCookie('data');
            $this->deleteCookie('control');

            foreach (array_keys($_COOKIE) as $name) {
                if (strstr($name, $settings['name'])) {
                    $Vars->deleteCookie($name);
                }
            }

            return true;
        }
    }
}
