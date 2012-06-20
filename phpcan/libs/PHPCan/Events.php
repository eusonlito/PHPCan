<?php
/*
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

namespace PHPCan;

defined('ANS') or die();

class Events
{
    private $Debug;
    private $events = array();

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

    public function load ($events = array())
    {
        if (!$events) {
            global $Config;

            if (!$Config->events) {
                return true;
            }

            $events = $Config->events;
        }

        foreach ($events as $mode => $rows) {
            foreach ($rows as $name => $actions) {
                foreach ($actions as $action => $function) {
                    $this->bind($mode.'.'.$name, $action, $function);
                }
            }
        }

        return true;
    }

    /**
     * public function bind (string $element, string/array $events, function/array $function)
     *
     * return boolean
     */
    public function bind ($element, $events, $function)
    {
        if (!$element) {
            return false;
        }

        if (!is_array($events)) {
            $events = array($events);
        }

        if (is_callable($function)
        || (is_string($function) && function_exists($function))
        || (is_array($function) && is_object($function[0]) && method_exists($function[0], $function[1]))) {
            foreach ($events as $event) {
                $this->events[$element][$event] = array('function' => $function);
            }

            return true;
        }

        $this->Debug->error('events', __('There is not valid function for the events %s', implode(', ', $events)));

        return false;
    }

    /**
     * public function unbind (string $element, [string/array $events])
     *
     * return boolean
     */
    public function unbind ($element, $events = false)
    {
        if (!$elements) {
            return false;
        }

        if (!$events) {
            unset($this->events[$element]);
        }

        foreach ((array) $events as $event) {
            unset($this->events[$element][$event]);
        }
    }

    /**
     * public function defined (string $element, [string $event])
     *
     * return boolean
     */
    public function defined ($element, $event = false)
    {
        if (!$event) {
            return isset($this->events[$element]);
        }

        return isset($this->events[$element][$event]);
    }

    /**
     * public function trigger (string $element, string $event)
     *
     * return mixed
     */
    public function trigger ($element, $event)
    {
        if (!$event || !($event_settings = $this->events[$element][$event])) {
            return false;
        }

        $arguments = func_get_args();
        array_shift($arguments);
        array_shift($arguments);

        return call_user_func_array($this->events[$element][$event]['function'], $arguments);
    }
}
