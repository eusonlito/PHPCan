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

        if ($session && $session['id'] && $session[$settings['password_field']]) {
            $id = decrypt($session['id']);
            $password = decrypt($session[$settings['password_field']]);
        } else {
            $control = $this->getCookie('control');

            if (empty($control['1']) || empty($control['2'])) {
                return false;
            }

            $id = decrypt($control['1']);
            $password = $this->selectPassword($id);

            $maintain_code = $this->encode($id.$password);

            if ($maintain_code !== $control['2']) {
                return false;
            }
        }

        $user = $this->checkUser(array(
            'id' => $id,
            $settings['password_field'] => $password
        ), $password);

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

        if (empty($data['username'])) {
            $this->Errors->set($settings['errors'], __('You haven\'t filled all the form fields'), $settings['user_field']);
            return false;
        }

        if (empty($data['password'])) {
            $this->Errors->set($settings['errors'], __('You haven\'t filled all the form fields'), $settings['password_field']);
            return false;
        }

        $user = $this->checkUser(array(
            $settings['user_field'] => $data['username'],
            $settings['password_field'] => $data['password']
        ), $data['password'], $update ? true : false);

        if (empty($user)) {
            $user = $this->checkUser(array(
                $settings['user_field'] => $data['username']
            ), $data['password'], $update ? true : false);
        }

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
            'id' => encrypt($user['id']),
            $settings['password_field'] => encrypt($data['password'])
        ));

        //Maintain session
        if ($data['maintain']) {
            $this->maintain($user['id'], $data['password']);
        }

        $this->logged = true;

        return $this->user;
    }

    private function checkUser ($conditions, $password, $encoded = true)
    {
        if (empty($conditions) || empty($password)) {
            return false;
        }

        $settings = $this->settings;

        $selection = $this->userExists($conditions);

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

        if (empty($user_data[$settings['user_field']])) {
            $this->Errors->set($settings['errors'], __('The user field is required!'), $settings['user_field']);
            return false;
        }

        if (empty($settings['allow_duplicates'])) {
            $exists = $this->userExists(array(
                $settings['user_field'] => $user_data[$settings['user_field']],
            ));

            if ($exists) {
                $this->Errors->set($settings['errors'], __('Sorry but there is already someone registered with that %s', __($settings['user_field'])), $settings['user_field']);
                return false;
            }
        }

        if ($clean_password !== $clean_password_repeat) {
            $this->Errors->set($settings['errors'], __('Password and repeat password are differents'), $settings['password_field']);
            return false;
        } else if (strlen($clean_password) < 6) {
            $this->Errors->set($settings['errors'], __('Password length must be %s characters at least', 6), $settings['password_field']);
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
            $this->Errors->set($settings['errors'], __('User not exists'), $settings['user_field']);
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
            $this->Errors->set($settings['errors'], __('You need to fill the new password field.'), $settings['password_field']);
            return false;
        }

        if (strlen($password) < 6) {
            $this->Errors->set($settings['errors'], __('Password length must be %s characters at least', 6), $settings['password_field']);
            return false;
        }

        if ($password !== $password_repeat) {
            $this->Errors->set($settings['errors'], __('Password and password repeat don\'t match.'), $settings['password_field']);
            return false;
        }

        //Check if id_user exists
        $user = $this->userExists(array(
            $settings['id_field'] => $id_user
        ));

        if (empty($user)) {
            $this->Errors->set($settings['errors'], __('User not exists'), $settings['user_field']);
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

        if ($ok) {
            return true;
        }

        $errors = $this->Errors->getList();

        if (empty($errors)) {
            $this->Errors->set($settings['errors'], __('Error saving data, please check the input form values.'));
        }

        return false;
    }

    /*
    * protected function selectPassword (string $id)
    *
    * return string
    */
    protected function selectPassword ($id)
    {
        global $Db;

        $settings = $this->settings;

        $conditions = array_merge($this->conditions, array(
            'id' => $id,
        ));

        $select = array(
            'table' => $settings['table'],
            'fields' => array($settings['password_field']),
            'conditions' => $conditions,
            'limit' => 1
        );

        $info = $Db->select($select);

        return $info ? $info[$settings['password_field']] : false;
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

    /*
    * protected function maintain (string $id, string $password)
    *
    * return boolean
    */
    protected function maintain ($id, $password)
    {
        if (empty($this->settings['maintain_time'])) {
            return false;
        }

        $maintain_code = $this->encode($id.$password);

        $this->setCookie('control', array(
            '1' => encrypt($id),
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
            $this->deleteCookie('data');

            foreach (array_keys($_COOKIE) as $name) {
                if (strstr($name, $this->settings['name'])) {
                    $Vars->deleteCookie($name);
                }
            }

            return true;
        }
    }
}
