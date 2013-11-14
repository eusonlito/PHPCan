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

class Facebook implements Isession {
    protected $Debug = object;
    protected $Errors = object;
    protected $settings = array();
    protected $conditions = array();

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

        $settings = $this->settings;

        $user = $this->userExists(array(
            $settings['id_field'] => $this->facebook['uid']
        ));

        if (empty($user)) {
            $this->Errors->set($settings['errors'], __('This user isn\'t logged from Facebook'));
            return false;
        }

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

        $this->API()->destroySession();

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
        if ($this->API() && empty($this->facebook)) {
            return false;
        }

        global $Session, $Db;

        $user = $Session->user();

        if ($user) {
            return $this->updateFacebookInfo();
        }

        $settings = $this->settings;
        $save_info = $this->getFacebookFields();

        if ($settings['enabled_field']) {
            $save_info[$settings['enabled_field']] = 1;
        } if ($settings['signup_date_field']) {
            $save_info[$settings['signup_date_field']] = date('Y-m-d H:i:s');
        } if ($settings['raw_field']) {
            $save_info[$settings['raw_field']] = base64_encode(serialize($this->facebook));
        }

        return $Db->insert(array(
            'table' => $settings['table'],
            'data' => $save_info
        ));
    }

    /*
    * private function updateFacebookInfo ()
    *
    * return false/array
    */
    public function updateFacebookInfo ()
    {
        if ($this->API() && empty($this->facebook)) {
            return false;
        }

        $settings = $this->settings;
        $save_info = $this->getFacebookFields();

        if ($settings['disable_update']) {
            foreach ($settings['disable_update'] as $field) {
                unset($save_info[$field]);
            }
        }

        if ($settings['raw_field']) {
            $save_info[$settings['raw_field']] = base64_encode(serialize($this->facebook));
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

    /*
    * public function getPermissions (string $name)
    *
    * return boolean
    */
    public function getPermissions ($name)
    {
        return $this->API()->api(array(
            'method' => 'users.hasAppPermission',
            'uid' => $this->user[$this->settings['id_field']],
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

        $this->updateFacebookInfo();

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
    * protected function encode (string $text)
    *
    * return string
    */
    public function encode ($text)
    {
        return hash($this->settings['encrypt'], $text);
    }

    /*
    * public function unsubscribeUser ([$data = array()])
    *
    * Function to disable user accout. All user info should be cleaned and
    * the username should be updated with a generic value
    *
    * return boolean
    */
    public function unsubscribeUser ($data = array())
    {
        $settings = $this->settings;

        if ($settings['unsubscribe_field']) {
            $data[$settings['unsubscribe_field']] = 1;
        }

        if ($settings['unsubscribe_date_field']) {
            $data[$settings['unsubscribe_date_field']] = date('Y-m-d H:i:s');
        }

        if ($settings['enabled_field']) {
            $data[$settings['enabled_field']] = 0;
        }

        if (empty($data)) {
            return true;
        }

        global $Db, $Session;

        return $Db->update(array(
            'table' => $settings['table'],
            'data' => $data,
            'conditions' => array(
                'id' => $Session->user('id')
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

        $settings = $this->settings;
        $exists = $this->getCookie($name);

        if ($exists && is_array($exists) && is_array($value)) {
            $value = array_merge($exists, $value);
        }

        $Vars->setCookie($settings['name'].'-'.$name, $value, $settings['maintain_time']);

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
