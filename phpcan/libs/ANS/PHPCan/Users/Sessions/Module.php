<?php
/*
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

namespace ANS\PHPCan\Users\Sessions;

defined('ANS') or die();

class Module implements Isession
{
    protected $Debug;
    protected $settings = array();
    protected $conditions = array();

    /**
     * public function __construct ($settings)
     */
    public function __construct ($settings)
    {
        global $Debug, $Errors;

        $this->Debug = $Debug;
        $this->Errors = $Errors;

        $this->settings = array_merge(array(
            'cookie' => 'session-module',
            'duration' => (3600 * 24 * 30),
            'errors' => 'session-module'
        ), $settings);
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

    /**
     * public function start ()
     *
     * return false or user id
     */
    public function start ()
    {
        return true;
    }

    /**
     * public function load ()
     *
     * return false or user data
     */
    public function load ()
    {
        global $Vars;

        $cookie = $Vars->getCookie($this->settings['cookie']);
        $module = $this->settings['module'] ? $this->settings['module'] : $Vars->getModule();

        foreach ((array)$this->settings['users'] as $config) {
            if (($cookie === md5($config['name'].$config['password'])) && in_array($module, (array)$config['modules'])) {
                return $config;
            }
        }

        return false;
    }

    /**
     * public function login ($data)
     *
     * return false or user data
     */
    public function login ($data = array())
    {
        global $Vars;

        $module = $this->settings['module'] ? $this->settings['module'] : $Vars->getModule();
        $user = false;

        foreach ((array)$this->settings['users'] as $config) {
            if (($config['name'] === $data['user']) && ($config['password'] === $data['password']) && (in_array($module, (array)$config['modules']))) {
                $user = $config;
                break;
            }
        }

        if ($user) {
            $Vars->setCookie($this->settings['cookie'], md5($user['name'].$user['password']), $this->settings['duration']);
        } else {
            $this->Errors->set($this->settings['errors'], __('The user or password is not correct!'));

            return false;
        }

        return $user;
    }

    /**
     * public function logout ()
     *
     * return boolean
     */
    public function logout ()
    {
        global $Vars;

        return $Vars->deleteCookie($this->settings['cookie']);
    }
}
