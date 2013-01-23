<?php
/**
* phpCan v.1
*
* $Id: class_session_regular.php 1 2011-05-24 22:15:29Z Lito $
*
* 2009 Copyright - phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

namespace ANS\PHPCan\Users\Sessions;

defined('ANS') or die();

class Regular implements Isession
{
    protected $Debug = object;
    protected $Errors = object;
    protected $settings = array();
    protected $conditions = array();

    private $logged = boolean;
    private $user = array();

    /*
     * public function __construct ($settings)
     */
    public function __construct ($settings)
    {
        global $Debug, $Errors;

        $this->Debug = $Debug;
        $this->Errors = $Errors;

        $this->settings = $settings['sessions']['regular'];
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

        if ($session && $session[$settings['user_field']] && $session[$settings['password_field']]) {
            $user = decrypt($session[$settings['user_field']]);
            $password = decrypt($session[$settings['password_field']]);
        } else {
            $control = $this->getCookie('control');

            if (empty($control['1']) || empty($control['2'])) {
                return false;
            }

            $user = decrypt($control['1']);
            $password = $this->selectPassword($user);

            $maintain_code = $this->encode($user.$password);

            if ($maintain_code !== $control['2']) {
                return false;
            }
        }

        $user = $this->checkUser($user, $password);

        if (empty($user)) {
            return false;
        }

        $this->user = $user;
        $this->user['ip'] = ip();

        $this->logged = true;

        return $this->user;
    }

    /*
    * public function login (array $data)
    *
    * return boolean
    */
    public function login ($data = array(), $update = false)
    {
        $settings = $this->settings;

        if (empty($data['username']) || empty($data['password'])) {
            $this->Errors->set($settings['errors'], __('You haven\'t filled all the form fields'));

            return false;
        }

        $user = $this->checkUser($data['username'], $data['password'], $update ? true : false);

        if (empty($user)) {
            return false;
        }

        if (empty($update)) {
            $data['password'] = $this->encode($user['id'].$data['password']);
        }

        $this->user = $user;
        $this->user['ip'] = ip();

        //Save session data
        $this->setCookie('data', array(
            $settings['user_field'] => encrypt($data['username']),
            $settings['password_field'] => encrypt($data['password'])
        ));

        //Maintain session
        if ($data['maintain']) {
            $this->maintain($data['username'], $data['password']);
        }

        $this->logged = true;

        return $this->user;
    }

    private function checkUser ($username, $password, $encoded = true)
    {
        if (empty($username) || empty($password)) {
            return false;
        }

        $settings = $this->settings;

        $selection = $this->userExists(array(
            $settings['user_field'] => $username
        ));

        if (empty($selection)) {
            $this->user = array();
            $this->Errors->set($settings['errors'], __('Don\'t exists an user with this user and password'));

            return false;
        }

        if (empty($encoded)) {
            $password = $this->encode($selection['id'].$password);
        }

        if ($selection[$settings['enabled_field']] == 0) {
            $this->user = array();
            $this->Errors->set($settings['errors'], __('This user is not active'));

            return false;
        } else if (($selection[$settings['password_field']] !== $password)) {
            $this->user = array();
            $this->Errors->set($settings['errors'], __('Don\'t exists an user with this user and password'));

            return false;
        }

        return $selection;
    }

    /*
    * public function logout ([array $data])
    */
    public function logout ($data = array())
    {
        $this->deleteCookie();

        $this->id = 0;
        $this->user = array();
        $this->logged = false;
    }

    /*
    * public function userAdd (array $user_data)
    *
    * return boolean
    */
    public function userAdd ($user_data)
    {
        $settings = $this->settings;

        $clean_password = $user_data[$settings['password_field']];
        $clean_password_repeat = $user_data[$settings['password_field'].'_repeat'];

        $user_data = $this->userData($user_data);

        if (empty($user_data)) {
            $this->Errors->set($settings['errors'], __('No data are received'));

            return false;
        }

        if (empty($settings['allow_duplicates'])) {
            $exists = $this->userExists(array(
                $settings['user_field'] => $user_data[$settings['user_field']],
            ));

            if ($exists) {
                $this->Errors->set($settings['errors'], __('Sorry but there is already someone registered with that %s', __($settings['user_field'])));

                return false;
            }
        }

        if ($clean_password !== $clean_password_repeat) {
            $this->Errors->set($settings['errors'], __('Password and repeat password are differents'));

            return false;
        } else if (strlen($clean_password) < 6) {
            $this->Errors->set($settings['errors'], __('Password length must be %s characters at least', 6));

            return false;
        }

        $user_data[$settings['signup_date_field']] = date('Y-m-d H:i:s');

        if (!isset($user_data[$settings['enabled_field']])) {
            $user_data[$settings['enabled_field']] = 1;
        }

        unset($user_data[$settings['password_field'].'_repeat']);

        $params = array(
            'table' => $settings['table'],
            'data' => $user_data
        );

        global $Db;

        //Save user data
        $id_users = $Db->insert($params);

        if ($id_users) {
            return $id_users;
        }

        $errors = $this->Errors->getList();

        if (empty($errors)) {
            $this->Errors->set($settings['errors'], __('Error saving data, please check the input form values.'));
        }

        return false;
    }

    /*
    * public function userEdit (array $info)
    *
    * return boolean
    */
    public function userEdit ($info)
    {
        $settings = $this->settings;

        $id_user = $info['id'];
        $user_data = $info['data'];

        //Check if user exists
        $user = $this->userExists(array(
            $settings['id_field'] => $id_user
        ));

        if (empty($user)) {
            $this->Errors->set($settings['errors'], __('User not exists'));

            return false;
        }

        $user_data = $this->userData($user_data);

        if (empty($user_data)) {
            return false;
        }

        unset($user_data[$settings['password_field']]);

        if ($user_data[$settings['avatar_field']]['error'] > 0) {
            unset($user_data[$settings['avatar_field']]);
        }

        //Save user data
        $parameters = array(
            'table' => $settings['table'],
            'data' => $user_data,
            'conditions' => array(
                $settings['id_field'] => $id_user
            ),
            'limit' => 1
        );

        global $Db;

        if ($Db->update($parameters)) {
            return true;
        }

        $errors = $this->Errors->getList();

        if (empty($errors)) {
            $this->Errors->set($settings['errors'], __('Error saving data, please check the input form values.'));
        }

        return false;
    }

    /*
    * public function passwordEdit (array $info)
    *
    * return boolean
    */
    public function passwordEdit ($info)
    {
        $id_user = $info['id'];
        $password = $info['password'];
        $password_repeat = $info['password_repeat'];

        $settings = $this->settings;

        if (empty($password)) {
            $this->Errors->set($settings['errors'], __('You need to fill the new password field.'));

            return false;
        }

        if (strlen($password) < 6) {
            $this->Errors->set($settings['errors'], __('Password length must be %s characters at least', 6));

            return false;
        }

        if ($password !== $password_repeat) {
            $this->Errors->set($settings['errors'], __('Password and password repeat don\'t match.'));

            return false;
        }

        //Check if id_user exists
        $user = $this->userExists(array(
            $settings['id_field'] => $id_user
        ));

        if (empty($user)) {
            $this->Errors->set($settings['errors'], __('User not exists'));

            return false;
        }

        //Save user data
        $parameters = array(
            'table' => $settings['table'],
            'data' => array(
                $settings['password_field'] => $password,
                $settings['password_tmp_field'] => ''
            ),
            'conditions' => array(
                $settings['id_field'] => $id_user
            ),
            'limit' => 1
        );

        global $Db;

        $ok = $Db->update($parameters);

        if (empty($ok)) {
            $errors = $this->Errors->getList();

            if (empty($errors)) {
                $this->Errors->set($settings['errors'], __('Error saving data, please check the input form values.'));
            }

            return false;
        }

        return true;
    }

    /*
    * protected function selectPassword (string $user)
    *
    * return string
    */
    protected function selectPassword ($user)
    {
        global $Db;

        $conditions = array_merge($this->conditions, array(
            $this->settings['user_field'] => $user,
        ));

        $select = array(
            'table' => $this->settings['table'],
            'fields' => array($this->settings['password_field']),
            'conditions' => $conditions,
            'limit' => 1
        );

        $info = $Db->select($select);

        return $info ? $info[$this->settings['password_field']] : false;
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
            } else if (isset($user_data[$name])) {
                $data[$dbfield] = $user_data[$name];
            }
        }

        return $data;
    }

    /*
    * protected function maintain (string $user, string $password)
    *
    * return boolean
    */
    protected function maintain ($user, $password)
    {
        if (empty($this->settings['maintain_time'])) {
            return false;
        }

        $maintain_code = $this->encode($user.$password);

        $this->setCookie('control', array(
            '1' => encrypt($user),
            '2' => $maintain_code
        ));

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
