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

class Facebook implements Isession {
    protected $Debug = object;
    protected $Errors = object;
    protected $settings = array();

    public $facebook = array();

    private $API = false;
    private $logged = false;
    private $user = array();

    /*
     * public function __construct ($settings)
     */
    public function __construct ($settings)
    {
        global $Debug, $Errors;

        $this->Debug = $Debug;
        $this->Errors = $Errors;
        $this->settings = array_merge(array(
            'errors' => 'session-facebook',
        ), $settings['common'], $settings['sessions']['facebook']);
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
            return false;
        }

        $this->user = $exists;
        $this->user['ip'] = ip();

        $this->logged = true;

        return $this->user;
    }

    public function API ()
    {
        if ($this->API) {
            return $this->API;
        }

        $this->API = new Facebook\Facebook(array(
            'appId' => $this->settings['api_id'],
            'secret' => $this->settings['secret_key'],
            'cookie' => true
        ));

        return $this->API;
    }

    public function loginService ()
    {
        if ($this->facebook) {
            return $this->facebook;
        }

        $user = $this->API()->getUser();

        if ($user) {
            $this->facebook = current($this->API->api(array(
                'method' => 'users.getinfo',
                'uids' => $user,
                'fields' => implode(',', $this->settings['facebook_fields']),
            )));
        } else {
            $this->facebook = array();
        }

        return $this->facebook;
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
            $this->settings['id_field'] => $this->facebook['uid']
        ));

        if (!$user) {
            $this->Errors->set($this->settings['errors'], __('This user isn\'t logged from Facebook'));

            return false;
        }

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

    public function getFacebookFields ()
    {
        $info = array();

        foreach ($this->settings['fields'] as $field => $fields) {
            if (!is_array($fields)) {
                $fields = array($fields);
            }

            foreach ($fields as $each) {
                if (isset($this->facebook[$field])) {
                    $info[$each] = $this->facebook[$field];
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
        if ($this->API() && !$this->facebook) {
            return false;
        }

        global $Session, $Db;

        $user = $Session->user();

        if ($user) {
            $this->updateFacebookInfo();

            $Db->update(array(
                'table' => $this->settings['users_table'],
                'data' => array(
                    $this->settings['fields']['facebook_allow'] => 1
                ),
                'conditions' => array(
                    'id' => $Session->user('id')
                ),
                'limit' => 1
            ));

            return true;
        }

        $save_info = $this->getFacebookFields();

        $save_info[$this->settings['fields']['facebook_allow']] = 1;
        $save_info[$this->settings['enabled_field']] = 1;
        $save_info[$this->settings['signup_date_field']] = date('Y-m-d H:i:s');

        $Db->insert(array(
            'table' => $this->settings['users_table'],
            'data' => $save_info
        ));

        return true;
    }

    /*
    * private function updateFacebookInfo ()
    *
    * return false/array
    */
    public function updateFacebookInfo ()
    {
        if ($this->API() && !$this->facebook) {
            return false;
        }

        $save_info = $this->getFacebookFields();

        if ($this->settings['disable_update']) {
            foreach ($this->settings['disable_update'] as $field) {
                unset($save_info[$field]);
            }
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

    /*
    * public function getPermissions (string $name)
    *
    * return boolean
    */
    public function getPermissions ($name)
    {
        return $this->API()->api(array(
            'method' => 'users.hasAppPermission',
            'uid' => $this->user['uid'],
            'ext_perm' => $name
        )) ? true : false;
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

        $this->updateFacebookInfo();

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
