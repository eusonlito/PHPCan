<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

namespace ANS\PHPCan;

defined('ANS') or die();

class Config implements \ArrayAccess, \Iterator, \Countable
{
    public $config = array();

    private $loaded;
    private $Debug;
    private $Cache;

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
            $this->config['autoglobal'][] = $autoglobal;
        }
    }

    public function setCache ()
    {
        $settings = $this->cache['types']['config'];

        if ($settings['expire'] && $settings['interface']) {
            $this->Cache = new \ANS\Cache\Cache($settings);

            if ($this->Cache->loaded() !== true) {
                $this->Cache = false;
            }
        } else {
            $this->Cache = false;
        }
    }

    /**
     * public function __get (string $name)
     *
     * return none
     */
    public function __get ($name)
    {
        return $this->config[$name];
    }

    /**
     * public function __set (string $name, $value)
     *
     * return none
     */
    public function __set ($name, $value)
    {
        $this->config[$name] = $value;
    }

    /**
     * public function __isset (string $name)
     *
     * return none
     */
    public function __isset ($name)
    {
        return isset($this->config[$name]);
    }

    /**
     * public function load (string/array $list, [string $context], [string $prefix])
     *
     * return boolean
     */
    public function load ($list, $context = null, $prefix = '')
    {
        global $Vars;

        if (empty($list)) {
            return false;
        }

        $module = $Vars->getModule();
        $scene = $Vars->getScene();

        $context = is_null($context) ? ($module ? 'module' : 'scene') : $context;

        if (is_string($list)) {
            $list = array($context => array($list));
        } else if (isset($list[0])) {
            $list = array($context => $list);
        }

        $includes = array();

        foreach ($list as $context => $files) {
            foreach ($files as $file) {
                if ($context === 'phpcan') {
                    $includes[] = PHPCAN_PATH.'config/'.$file;
                    $includes[] = PHPCAN_PATH.'config/'.DEFAULT_CONFIG_PATH.$file;
                    $includes[] = PHPCAN_PATH.'config/'.DOMAIN_CONFIG_PATH.$file;
                } else if ($context === 'scene') {
                    if (empty($scene)) {
                        continue;
                    }

                    $includes[] = SCENE_PATH.'config/'.$file;
                    $includes[] = SCENE_PATH.'config/'.DEFAULT_CONFIG_PATH.$file;
                    $includes[] = SCENE_PATH.'config/'.DOMAIN_CONFIG_PATH.$file;
                } else if ($context === 'module') {
                    if (empty($module)) {
                        continue;
                    }

                    $includes[] = MODULES_PATH.'common/config/'.$file;
                    $includes[] = MODULE_PATH.'config/'.$file;
                    $includes[] = MODULE_PATH.'config/'.DEFAULT_CONFIG_PATH.$file;
                    $includes[] = MODULE_PATH.'config/'.DOMAIN_CONFIG_PATH.$file;
                    $includes[] = SCENE_PATH.'config/'.$module.'/'.$file;
                    $includes[] = SCENE_PATH.'config/'.DEFAULT_CONFIG_PATH.$module.'/'.$file;
                    $includes[] = SCENE_PATH.'config/'.DOMAIN_CONFIG_PATH.$module.'/'.$file;
                } else {
                    $includes[] = $context.$file;
                    $includes[] = $context.DEFAULT_CONFIG_PATH.$file;
                    $includes[] = $context.DOMAIN_CONFIG_PATH.$file;
                }
            }
        }

        $cache_key = md5(serialize($includes).$prefix);

        if ($this->Cache && $this->Cache->exists($cache_key)) {
            $config = $this->Cache->get($cache_key);

            if (is_array($config)) {
                return $this->config = arrayMergeReplaceRecursive($this->config, $config);
            } else {
                return false;
            }
        }

        $current = array();

        foreach ($includes as $file) {
            $this->loaded[] = $file;

            if (!is_file($file)) {
                continue;
            }

            $config = array();

            include ($file);

            if (!is_array($config)) {
                continue;
            }

            $current = arrayMergeReplaceRecursive($current, $config);
        }

        if (empty($current)) {
            return false;
        }

        $config = array();

        foreach ($current as $key => $value) {
            $config[$prefix.$key] = $this->expand($value);
        }

        if ($this->Cache) {
            $this->Cache->set($cache_key, $config);
        }

        $this->config = arrayMergeReplaceRecursive($this->config, $config);

        return true;
    }

    /**
    * public function getLoaded (void)
    *
    * Return the loaded configuration files
    *
    * return array
    */
    public function getLoaded ()
    {
        return $this->loaded;
    }

    /**
     * ArrayAccess: final public function offsetExists ($offset)
     *
     * return boolean
     */
    final public function offsetExists ($offset)
    {
        return array_key_exists($offset, $this->config);
    }

    /**
     * ArrayAccess: final public function offsetGet ($offset)
     *
     * return mixed
     */
    final public function offsetGet ($offset)
    {
        return $this->config[$offset];
    }

    /**
     * ArrayAccess: final public function offsetSet ($offset, $value)
     *
     * return none
     */
    final public function offsetSet ($offset, $value)
    {
        $this->config[$offset] = $value;
    }

    /**
     * ArrayAccess: final public function offsetUnset ($offset)
     *
     * return none
     */
    final public function offsetUnset ($offset)
    {
        unset($this->config[$offset]);
    }

    /**
     * Iterator: final public function current ()
     *
     * return mixed
     */
    final public function current ()
    {
        return current($this->config);
    }

    /**
     * Iterator: final public function key ()
     *
     * return string/int
     */
    final public function key ()
    {
        return key($this->config);
    }

    /**
     * Iterator: final public function next ()
     *
     * return none
     */
    final public function next ()
    {
        return next($this->config);
    }

    /**
     * Iterator: final public function rewind ()
     *
     * return none
     */
    final public function rewind ()
    {
        return reset($this->config);
    }

    /**
     * Iterator: final public function valid ()
     *
     * return none
     */
    final public function valid ()
    {
        return isset($this->config[key($this->config)]);
    }

    /**
     * Countable: final public function count ()
     *
     * return none
     */
    final public function count ()
    {
        return count($this->config);
    }

    /**
     * private function expand ($data)
     *
     * Expand the array config width commas
     *
     * return boolean
     */
    private function expand ($data)
    {
        if (!is_array($data)) {
            return $data;
        }

        foreach ($data as $key => $value) {
            if (strstr($key, ',')) {
                $keys = explode(',', $key);

                foreach ($keys as $k) {
                    $k = trim($k);

                    $data[$k] = array_merge_recursive((array)$data[$k], (array)$value);
                    $data[$k] = $this->expand($data[$k]);
                }

                unset($data[$key]);
            } else if (is_array($data[$key])) {
                $data[$key] = $this->expand($data[$key]);
            }
        }

        return $data;
    }
}
