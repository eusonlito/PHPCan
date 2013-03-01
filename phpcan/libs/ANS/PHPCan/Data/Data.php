<?php
/*
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

namespace ANS\PHPCan\Data;

defined('ANS') or die();

class Data
{
    private $Debug;

    public $data = array();
    public $actions = array();

    /**
     * public function __construct ([string $autoglobal])
     *
     * return none
     */
    public function __construct ($autoglobal = '')
    {
        global $Debug, $Vars;

        $this->Debug = $Debug;

        if ($autoglobal) {
            global $Config;

            $Config->config['autoglobal'][] = $autoglobal;
        }

        if ($actions = $Vars->getCookie('phpcan_executed_actions')) {
            $this->actions = (array)$actions;
        }

        $Vars->deleteCookie('phpcan_executed_actions');
    }

    /**
     * public function __get (string $name)
     *
     * return none
     */
    public function __get ($name)
    {
        return $this->data[$name];
    }

    /**
     * public function __set (string $name, $value)
     *
     * return none
     */
    public function __set ($name, $value)
    {
        $this->data[$name] = $value;
    }

    /**
     * public function __isset (string $name)
     *
     * return none
     */
    public function __isset ($name)
    {
        return isset($this->data[$name]);
    }

    /**
     * public function set (string/array $name, [mixed $value], [mixed $default])
     *
     * return none
     */
    public function set ($name, $value = null, $default = null)
    {
        if (is_array($name)) {
            $this->data = array_merge($this->data, $name);
        } else {
            if (!is_null($default)) {
                $value = $value ? $value : $default;
            }

            $this->data[$name] = $value;
        }
    }

    /**
     * public function get (string $name, [mixed $default])
     *
     * return none
     */
    public function get ($name, $default = null)
    {
        if (!is_null($default)) {
            return isset($this->data[$name]) ? $this->data[$name] : $default;
        }

        return $this->data[$name];
    }

    /**
     * public function arr (string $name)
     *
     * return none
     */
    public function arr ($name)
    {
        return (array)$this->data[$name];
    }

    /**
     * public function file (string $data, [boolean $empty_data])
     *
     * Get the path of a data file
     */
    public function file ($data, $empty_data = true)
    {
        global $Config;

        $empty_data = $empty_data ? filePath('phpcan/libs|ANS/PHPCan/Utils/empty_template.php') : false;

        if (!is_string($data)) {
            return $empty_data;
        } else if (isset($Config->data[$data])) {
            if ($Config->data[$data]) {
                $data = filePath('data|'.$Config->data[$data]);
            } else {
                return $empty_data;
            }
        } else if (strstr($data, '|') !== false) {
            $data = filePath($data);
        } else if ($data) {
            $data = filePath('data|'.$data);
        } else {
            return $empty_data;
        }

        return is_file($data) ? $data : $empty_data;
    }

    /**
     * public function execute (string $data, [array $data_content], [boolean $once])
     *
     * Include a template
     *
     * return string/boolean
     */
    public function execute ($data, $data_content = array(), $once = false)
    {
        $data = $this->file($data, false);

        if (empty($data)) {
            return false;
        }

        if (is_bool($data_content)) {
            $once = $data_content;
            $data_content = array();
        }

        return includeFile($data, $data_content, $once);
    }

    /**
     * public function getAction (string $action_name)
     *
     * Get the path of a data file
     */
    public function getAction ($action_name)
    {
        if (empty($action_name)) {
            return false;
        }

        global $Config;

        $action = $Config->config['actions'][$action_name];

        if (empty($action)) {
            $this->Debug->error('actions', __('The action "%s" doesn\'t exists', $action_name));

            return false;
        }

        if (!is_array($action)) {
            $action = array('file' => $action);
        }

        if (empty($action['file'])) {
            $this->Debug->error('actions', __('Script file isn\'t defined to action "%s"', $action_name));

            return false;
        }

        if ($action['disabled']) {
            return false;
        }

        if (!is_file($action['file'])) {
            $action['file'] = strpos($action['file'], '|') === false ? filePath('actions|'.$action['file']) : filePath($action['file']);
        }

        if (!is_file($action['file'])) {
            $this->Debug->error('actions', __('The file "%s" does not exists', $action['file']));

            return false;
        }

        global $Vars;

        $action['name'] = $action_name;
        $action['value'] = $Vars->actions[$action_name];

        return $action;
    }

    /**
     * public function afterAction (string $action)
     *
     * Executes afterAction actions
     */
    public function afterAction ($action, $return)
    {
        if (empty($action)) {
            return false;
        }

        global $Vars;

        $this->actions[$action['name']] = $return;

        if (($return !== false) && $action['redirect'] && $Vars->getExitModeConfig('action_redirect')) {
            if (is_string($action['redirect'])) {
                redirect($action['redirect']);
            } else {
                redirect();
            }
        }
    }
}
