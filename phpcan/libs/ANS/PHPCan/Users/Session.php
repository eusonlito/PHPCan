<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

namespace ANS\PHPCan\Users;

defined('ANS') or die();

class Session
{
    protected $logged = array();
    protected $user = array();
    protected $settings = array();
    protected $Debug;

    public $sessions = array();

    /**
     * public function __construct ([string $autoglobal])
     *
     * return none
     */
    public function __construct ($autoglobal = '')
    {
        global $Debug;

        $this->Debug = $Debug;

        if ($autoglobal) {
            global $Config;

            $Config->config['autoglobal'][] = $autoglobal;
        }
    }

    /**
     * public function setSettings ([string/array $settings])
     *
     * return boolean
     */
    public function setSettings ($settings = 'session')
    {
        global $Config;

        if (is_string($settings) && $Config->config[$settings]) {
            $this->settings = $Config->config[$settings];

            return true;
        }

        if (is_array($settings)) {
            $this->settings = $settings;

            return true;
        }

        return false;
    }

    /**
     * public function setConditions ($sessions, $conditions = array())
     *
     * return array
     */
    public function setConditions ($sessions, $conditions = array())
    {
        if (empty($conditions)) {
            $conditions = $sessions;
            $sessions = array();

            foreach (array_keys($this->sessions) as $session) {
                $sessions[$session] = $conditions;
            }
        } else if (is_string($sessions)) {
            $sessions = array($sessions => $conditions);
        }

        if (!is_array($sessions)) {
            return false;
        }

        foreach ($sessions as $session => $conditions) {
            $this->sessions[$session]->setConditions($conditions);
        }

        return true;
    }

    /**
     * public function add (string/array $sessions, [array $settings])
     *
     * return none
     */
    public function add ($sessions, $settings = null)
    {
        if (is_string($sessions)) {
            $sessions = array($sessions => $settings);
        }

        if (!is_array($sessions)) {
            return;
        }

        foreach ($sessions as $name => $settings) {
            $class_name = '\\ANS\\PHPCan\\Users\\Sessions\\'.ucfirst($name);

            $this->sessions[$name] = new $class_name($settings ?: $this->settings[$name]);
        }
    }

    /**
     * public function load ([string/array $sessions])
     *
     * return none
     */
    public function load ($sessions = null)
    {
        $sessions = $this->getSessions($sessions);

        foreach ($sessions as $session) {
            if ($this->logged($session)) {
                continue;
            }

            $logged = $this->sessions[$session]->load();

            if ($logged) {
                $this->logged[$session] = true;

                if (is_array($logged)) {
                    $this->user = array_merge($this->user, $logged);
                }
            }
        }

        return $this->logged;
    }

    /**
     * public function login (string/array $sessions, [array $data])
     *
     * return boolean
     */
    public function login ($sessions, $data = null)
    {
        if (is_string($sessions)) {
            $sessions = array($sessions => $data);
        }

        if (!is_array($sessions)) {
            return false;
        }

        foreach ($sessions as $session => $data) {
            $logged = $this->sessions[$session]->login($data);

            if ($logged) {
                $this->logged[$session] = true;

                if (is_array($logged)) {
                    $this->user = array_merge($this->user, $logged);
                }
            }
        }

        return $this->logged;
    }

    /**
     * public function setUser (string/array $sessions, array $user)
     *
     * return array
     */
    public function setUser ($sessions, $user)
    {
        if (is_string($sessions)) {
            $sessions = array($sessions);
        }

        if (!is_array($sessions)) {
            return false;
        }

        foreach ($sessions as $session) {
            $this->user = array_merge($this->user, $user);
        }

        return $this->user;
    }

    /**
     * private function getSessions ([string $sessions])
     *
     * return array
     */
    private function getSessions ($session = null)
    {
        if (is_null($session)) {
            return array_keys($this->sessions);
        }

        if (!is_array($session)) {
            return ($session && is_object($this->sessions[$session])) ? array($session) : array();
        }

        $return = array();

        foreach ($session as $name) {
            if (is_object($this->sessions[$name])) {
                $return[] = $name;
            }
        }

        return $return;
    }

    /**
     * public function logged ([string $session = ''])
     *
     * return boolean
     */
    public function logged ($session = '')
    {
        if ($session) {
            return $this->logged[$session] ? true : false;
        }

        return $this->logged ? true : false;
    }

    /**
     * public function user ([string $field = ''])
     *
     * return array
     */
    public function user ($field = '')
    {
        return $field ? $this->user[$field] : $this->user;
    }

    /**
     * public function logout ([string $session], [array $data])
     *
     * return none
     */
    public function logout ($sessions = null, $data = array())
    {
        $sessions = $this->getSessions($sessions);

        foreach ($sessions as $name) {
            if ($this->logged($name)) {
                $this->sessions[$name]->logout($data);
                unset($this->logged[$name]);
            }
        }
    }

    /**
     * public function execute (string $function, [array $params], [string/array $sessions])
     *
     * return array
     */
    public function execute ($function, $params = array(), $sessions = null)
    {
        $sessions = $this->getSessions($sessions);

        $return = array();

        foreach ($sessions as $name) {
            if (method_exists($this->sessions[$name], $function)) {
                $return[$name] = $this->sessions[$name]->$function($params);
            }
        }

        return $return;
    }
}
