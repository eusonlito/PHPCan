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

class Vars
{
    public $path;
    public $subdomains = array();
    public $message = array();
    public $data = array();
    public $actions = array();
    public $get = array();
    public $post = array();
    public $var = array();
    public $route_config = array();

    private $route;
    private $routes;
    private $scene;
    private $module;
    private $exit_mode = '';
    private $exit_modes = array();
    private $language = '';
    private $languages = array();
    private $compress_cookies = true;
    private $Debug;

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
     * public function overload (void)
     *
     * return boolean
     */
    public function overload ()
    {
        if (isset($_SERVER['CONTENT_LENGTH'])) {
            $size = trim(strtolower(ini_get('upload_max_filesize')));
            $last = substr($size, -1);
            $size = intval($size);

            switch ($last) {
                case 'g':
                    $size *= 1024;
                case 'm':
                    $size *= 1024;
                case 'k':
                    $size *= 1024;
            }

            if ($_SERVER['CONTENT_LENGTH'] > $size) {
                return true;
            }
        }

        return false;
    }

    /**
     * public function load (void)
     *
     * Load all vars
     *
     * return none
     */
    public function load ()
    {
        //Get path
        $url = getenv('REQUEST_URI');

        $this->path = $this->url2path($url);
        $this->actions = $this->url2actions($url);

        //Variables
        $get = (array) filter_input_array(INPUT_GET);
        $post = arrayMergeReplaceRecursiveStrict($this->arrayFiles(), (array) filter_input_array(INPUT_POST));

        $this->get = array_keys($get);
        $this->post = array_keys($post);
        $this->var = arrayMergeReplaceRecursiveStrict($get, $post);

        //Save the actions
        foreach ((array) $this->var['phpcan_action'] as $action_name => $action_value) {
            if (is_int($action_name)) {
                $action_name = $action_value;
                $action_value = null;
            }

            $action_name = trim($action_name);

            if ($action_name) {
                $this->actions[$action_name] = $action_value;
            }
        }

        $this->delete('phpcan_action');

        //Save the current subdomains
        if (preg_match('/localhost$/', SERVER_NAME)) {
            $this->subdomains = array_reverse(explode('.', str_replace('.localhost', '', SERVER_NAME)));
        } else {
            // FIX: problem with domains with double extension: co.uk
            $this->subdomains = array_reverse(explode('.', preg_replace('/[a-z0-9-]+\.([a-z]{2,4})+$/', '', SERVER_NAME)));

            array_shift($this->subdomains);
        }
    }

    /**
     * public function url2path (string $url)
     *
     * Load all vars
     *
     * return none
     */
    public function url2path ($url)
    {
        //Get path
        $url = preg_replace('|^'.preg_quote(BASE_WWW).'|', '', $url);
        $url = str_replace('$', '', parse_url($url, PHP_URL_PATH));

        $url = explode(':', $url);
        $url = explode('/', $url[0]);

        $path = array();

        foreach ($url as $value) {
            if (!empty($value)) {
                $path[] = trim(urldecode($value));
            }
        }

        if (!$path) {
            $path[] = 'index';
        }

        return $path;
    }

    /**
     * public function url2actions (string $url)
     *
     * Load all vars
     *
     * return none
     */
    public function url2actions ($url)
    {
        $actions = array();
        $url = explode('?', $url);

        list($null, $params) = explode(':', $url[0], 2);

        if ($params) {
            foreach (explodeTrim(',', $params) as $action) {
                $actions[$action] = null;
            }
        }

        return $actions;
    }

    /**
     * private function arrayFiles (void)
     *
     * Fix the order data of the array $_FILES
     *
     * return array
     */
    private function arrayFiles ()
    {
        if (!$_FILES) {
            return array();
        }

        $array_files = array();

        foreach ($_FILES as &$values) {
            $values = $this->fixFiles($values);
        }

        return $_FILES;
    }

    /**
     * private function _arrayFiles (void)
     *
     * To execute recursively from arrayFiles
     *
     * return array
     */
    private function _arrayFiles ($array, $last)
    {
        $return = array();

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $return[$key] = $this->_arrayFiles($value, $last);
            } else {
                $return[$key][$last] = $value;
            }
        }

        return $return;
    }

    /**
    * private function moveToRight (array $files)
    *
    * Private function used by fixArray
    * Returns array
    */
    private function fixFiles ($files)
    {
        $results = array();

        if (!isset($files['name']) || !is_array($files['name'])) {
            return $files;
        }

        foreach ($files['name'] as $index => $name) {
            $reordered = array(
                'name' => $files['name'][$index],
                'tmp_name' => $files['tmp_name'][$index],
                'size' => $files['size'][$index],
                'type' => $files['type'][$index],
                'error' => $files['error'][$index]
            );

            if (is_array($name)) {
                $reordered = $this->fixFiles($reordered);
            }

            $results[$index] = $reordered;
        }

        return $results;
    }

    /**
     * public function get (string/int/array $vars, [string $def_filter])
     *
     * return mixed
     */
    public function get ($vars = null, $def_filter = null)
    {
        if (is_null($vars)) {
            $vars = array_keys($this->var);
        }

        $return = $this->_get($vars, $this->var, $def_filter);

        if (is_array($vars) || $vars === ':num') {
            return (array) $return;
        }

        return $return[$vars];
    }

    /**
     * private function _get (string/int/array $names, array $values, string $def_filter)
     *
     * return null/array
     */
    private function _get ($names, $values, $def_filter)
    {
        if (!$values) {
            return null;
        }

        $return = array();

        foreach ((array) $names as $name => $filter) {
            if (!is_array($filter)) {
                if (is_int($name)) {
                    $name = $filter;
                    $filter = $def_filter;
                }

                $filter = $this->getSanitizeFilter($filter);

                if (isset($values[$name])) {
                    $return[$name] = $values[$name];

                    if (!is_array($return[$name])) {
                        $return[$name] = filter_var($return[$name], $filter[0], $filter[1]);
                    }

                    continue;
                }

                if (strpos($name, '[') && strpos($name, ']')) {
                    $subarrays = explode('[', str_replace(']', '', $name));
                    $value = $values;

                    while ($subarrays) {
                        $value = $value[array_shift($subarrays)];
                    }

                    if (isset($value)) {
                        $return[$name] = $value;

                        if (!is_array($return[$name])) {
                            $return[$name] = filter_var($return[$name], $filter[0], $filter[1]);
                        }
                    }

                    continue;
                }

                if ($name === ':num') {
                    foreach (array_keys($values) as $name) {
                        if (is_int($name) && isset($values[$name])) {
                            $return[$name] = $values[$name];

                            if (!is_array($return[$name])) {
                                $return[$name] = filter_var($return[$name], $filter[0], $filter[1]);
                            }
                        }
                    }

                    continue;
                }

                continue;
            }

            $return[$name] = $this->_get($filter, $values[$name], $def_filter);
        }

        return $return;
    }

    /**
    * function getGetVars ([string $name], [string $value], [bool $add_all_get_variables])
    * function getGetVars ([array $values], [bool $add_all_get_variables])
    *
    * Return string
    */
    public function getGetVars ($name = null, $value = true, $add_all_get_variables = true)
    {
        $values = array();
        $null = array();

        if (!is_null($name)) {
            if (is_array($name)) {
                $add_all_get_variables = $value;

                foreach ($name as $k => $v) {
                    if (is_int($k)) {
                        if (in_array($v, $this->get)) {
                            $values[$v] = $this->var[$v];
                        }

                    } elseif (is_null($v)) {
                        $null[] = $k;
                    } else {
                        $values[$k] = $v;
                    }
                }

            } elseif (is_null($value)) {
                $null[] = $name;
            } else {
                $values[$name] = $value;
            }
        }

        if ($add_all_get_variables === true) {
            foreach ($this->get as $name) {
                if (!isset($values[$name])) {
                    $values[$name] = $this->var[$name];
                }
            }
        }

        if ($null) {
            foreach ($null as $name) {
                unset($values[$name]);
            }
        }

        return $values;
    }

    /**
     * public function set (string/int $name, mixed $value)
     *
     * return mixed
     */
    public function set ($name, $value)
    {
        $this->var[$name] = $value;
    }

    /**
     * public function int (string/int $name)
     *
     * return integer
     */
    public function int ($name)
    {
        return $this->get($name, 'int');
    }

    /**
     * public function str (string/int $name)
     *
     * return string
     */
    public function str ($name)
    {
        return trim($this->get($name, 'string'));
    }

    /**
     * public function arr (string/int $name)
     *
     * return array
     */
    public function arr ($name)
    {
        $arr = $this->get($name);

        if (is_null($arr)) {
            return array();
        }

        return (array) $arr;
    }

    /**
     * public function bool (string/int $name)
     *
     * return boolean
     */
    public function bool ($name)
    {
        return $this->get($name, 'bool');
    }

    /**
     * public function setCookie (string $name, string $value, [int $duration])
     *
     * return boolean
     */
    public function setCookie ($name, $value, $duration = null)
    {
        if (is_null($duration)) {
            $duration = 86400; //one day
        }

        if ($this->compress_cookies) {
            return setcookie('gz:'.$name, deflate64($value), time() + $duration, BASE_WWW);
        } else {
            return setcookie($name, serialize($value), time() + $duration, BASE_WWW);
        }
    }

    /**
     * public function getCookie (string $name, [string $filter])
     *
     * return boolean
     */
    public function getCookie ($name, $filter = '')
    {
        $filter = $this->getSanitizeFilter($filter);

        if ($this->compress_cookies) {
            return inflate64(filter_input(INPUT_COOKIE, 'gz:'.$name, $filter[0], $filter[1]));
        } else {
            return unserialize(filter_input(INPUT_COOKIE, $name, $filter[0], $filter[1]));
        }
    }

    /**
     * public function deleteCookie (string $name)
     *
     * return boolean
     */
    public function deleteCookie ($name)
    {
        if (!preg_match('/^gz:/', $name)) {
            $name = 'gz:'.$name;
        }

        return setcookie($name, '', 1, BASE_WWW);
    }

    /**
     * private function getSanitizeFilter ($name)
     *
     * return int
     */
    private function getSanitizeFilter ($name)
    {
        switch ($name) {
            case 'string':
                return array(FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);

            case 'int':
                return array(FILTER_SANITIZE_NUMBER_INT);

            case 'bool':
                return array(FILTER_VALIDATE_BOOLEAN);

            case 'float':
                return array(FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

            case 'special_chars':
                return array(FILTER_SANITIZE_SPECIAL_CHARS);

            case 'encoded':
                return array(FILTER_SANITIZE_ENCODED);

            case 'url':
                return array(FILTER_SANITIZE_URL);

            case 'email':
                return array(FILTER_SANITIZE_EMAIL);
        }

        return array(FILTER_UNSAFE_RAW);
    }

    /**
     * public function is (string $type, mixed $name)
     *
     * return boolean
     */
    public function is ($type, $name)
    {
        if (!isset($this->var[$name])) {
            return false;
        }

        $value = $this->var[$name];

        switch ($type) {
            case 'int':
                return (filter_var($value, FILTER_VALIDATE_INT) === false) ? false : true;

            case 'bool':
                return is_null(filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)) ? false : true;

            case 'float':
                return (filter_var($value, FILTER_VALIDATE_FLOAT) === false) ? false : true;

            case 'url':
                return (filter_var($value, FILTER_VALIDATE_URL) === false) ? false : true;

            case 'email':
                return (filter_var($value, FILTER_VALIDATE_EMAIL) === false) ? false : true;
        }
    }

    /**
     * public function isInt ($name)
     *
     * return boolean
     */
    public function isInt ($name)
    {
        return $this->is('int', $name);
    }

    /**
     * public function isBool ($name)
     *
     * return boolean
     */
    public function isBool ($name)
    {
        return $this->is('bool', $name);
    }

    /**
     * public function isFloat ($name)
     *
     * return boolean
     */
    public function isFloat ($name)
    {
        return $this->is('float', $name);
    }

    /**
     * public function isUrl ($name)
     *
     * return boolean
     */
    public function isUrl ($name)
    {
        return $this->is('url', $name);
    }

    /**
     * public function isEmail ($name)
     *
     * return boolean
     */
    public function isEmail ($name)
    {
        return $this->is('email', $name);
    }

    /**
     * private function pathShift (void)
     */
    private function pathShift ()
    {
        array_shift($this->path);

        if (!$this->path) {
            $this->path[] = 'index';
        }
    }

    /**
     * public function setRoutesConfig ([array $config])
     *
     * Set the available routes
     *
     * return none
     */
    public function setRoutesConfig ($config = null)
    {
        if (is_null($config)) {
            global $Config;

            $this->routes = (array) $Config->routes;
        } else {
            $this->routes = (array) $config;
        }
    }

    /**
     * public function detectRoute ()
     *
     * Set route and return its config
     *
     * return array
     */
    public function detectRoute ()
    {
        //Detect regular expressions
        $url = implode('/', $this->path).'/';
        $route_list = array();
        $undefined = true;

        $search = array(
            '%([^/\*])$%', //Put "/" at the end if doesn't exists
            '%\.(\?)?/%', //Replace "./"
            '%\*%', //Replace "*/"
            '%\$[\w-]+(\?)?/%', //Replace $var/
            '%\$[\w-]+:int(\?)?%' //Replace $var:int/
        );

        $replace = array(
            '\1/',
            '([^/]+/)\1',
            '(.+)?',
            '([^/]+/)\1',
            '([0-9]+)\1'
        );

        $this->route_config = array();

        foreach ($this->routes as $route => $settings) {
            $route_regexp = preg_replace($search, $replace, $route);

            if (preg_match('%^'.$route_regexp.'$%', $url)) {
                $filtered_settings = array();

                foreach ($settings as $settings_name => $settings_value) {
                    if (!$settings_value) {
                        continue;
                    }

                    if (strpos($settings_name, '#')) {
                        list($settings_name, $exit_mode) = explodeTrim('#', $settings_name, 2);

                        if (!$this->getExitMode($exit_mode)) {
                            continue;
                        }
                    }

                    $this->route_config[$settings_name][] = (array) $settings_value;
                }

                if ($route !== '*') {
                    $undefined = false;
                    $route_list[] = $route;
                }
            }
        }

        if ($undefined) {
            foreach ((array) $this->routes['undefined'] as $settings_name => $settings_value) {
                if (strpos($settings_name, '#')) {
                    list($settings_name, $exit_mode) = explodeTrim('#', $settings_name, 2);

                    if (!$this->getExitMode($exit_mode)) {
                        continue;
                    }
                }

                $this->route_config[$settings_name][] = (array) $settings_value;
            }

            $this->var += $this->path;

            return false;
        }

        //Parse config_list
        $path_total = 0;

        foreach ($route_list as $route) {
            $this_route = explode('/', $route);

            foreach ($this_route as $i => $section) {
                if ($section === '*') {
                    continue;
                }

                if ($i > $path_total) {
                    $path_total = $i;
                }

                $section_key = preg_replace('#[^0-9a-z_-]#i', '', str_replace(':int', '', $section));

                //Get vars
                if ($section[0] === '$') {
                    if (strstr($section, ':int')) {
                        $this->path[$i] = intval($this->path[$i]);
                    }

                    if (empty($this->var[$section_key])) {
                        $this->var[$section_key] = $this->path[$i];
                    }
                }

                $this->route[$i] = $section_key;
            }
        }

        if ($path_total < (count($this->path) - 1)) {
            $this->var += array_slice($this->path, ($path_total + 1));
        }

        return true;
    }


    /**
     * function getRouteConfig ([string $name], [string/array $preserve])
     *
     * returns the $this->route_config value
     *
     * return array
     */
    public function getRouteConfig ($name = null, $preserve = array())
    {
        if (is_null($name)) {
            return $this->route_config;
        }

        if (!$this->route_config[$name]) {
            return false;
        }

        $config = call_user_func_array('array_merge', $this->route_config[$name]);

        if ($preserve) {
            foreach ((array) $preserve as $value) {
                $preserve_config = array();

                foreach ($this->route_config[$name] as $value_config) {
                    if ($value_config[$value]) {
                        $preserve_config[] = (array) $value_config[$value];
                    }
                }

                if ($preserve_config) {
                    $config[$value] = call_user_func_array('array_merge', $preserve_config);
                }
            }
        }

        if ($config) {
            ksort($config, SORT_STRING);
        }

        return $config;
    }



    /**
     * function getRoute ([int $index], [int $compare])
     *
     * returns the $this->route value
     *
     * return boolean/string
     */
    public function getRoute ($index = null, $compare = null)
    {
        if (is_null($index)) {
            return $this->route;
        }

        if (is_null($compare)) {
            return $this->route[$index];
        }

        return ($this->route[$index] === $compare) ? true : false;
    }


    /**
     * function getPath ([int $index], [int $compare])
     *
     * returns the $this->path value
     *
     * return boolean/string
     */
    public function getPath ($index = null, $compare = null)
    {
        if (is_null($index)) {
            return $this->path;
        }

        if (is_null($compare)) {
            return $this->path[$index];
        }

        return ($this->path[$index] === $compare) ? true : false;
    }


    /**
     * public function delete (string/int $name)
     *
     * Delete a variable
     */
    public function delete ($name)
    {
        if ($name === 0 || $name === '0') {
            array_shift($this->var);
        } else {
            unset($this->var[$name]);
        }

        unset($_GET[$name], $_POST[$name], $_FILES[$name], $_COOKIE[$name], $_COOKIE['gz:'.$name]);
    }


    /**
     * public function setScenesConfig ([array $config])
     *
     * Set the available languages
     *
     * return none
     */
    public function setScenesConfig ($config = null)
    {
        if (is_null($config)) {
            global $Config;

            $this->scenes = (array) $Config->scenes;
        } else {
            $this->scenes = (array) $config;
        }
    }


    /**
     * public function detectScene ()
     *
     * Detect the current scene
     *
     * return boolean
     */
    public function detectScene ()
    {
        if (!$this->scenes) {
            $this->Debug->fatalError('vars', 'There is not any scene defined');
        }

        //Get scene by subfolder
        $scene = strtolower($this->path[0]);

        if ($scene && ($this->scenes[$scene]['detect'] === 'subfolder')) {
            $this->scene = $scene;
            $this->pathShift();

            return true;
        }

        //Get scene by subdomain
        reset($this->subdomains);

        $scene = current($this->subdomains);

        if ($scene && ($this->scenes[$scene]['detect'] === 'subdomain')) {
            $this->scene = $scene;
            array_shift($this->subdomains);

            return true;
        }

        //Get default scene
        foreach ($this->scenes as $scene => $settings) {
            if ($settings['default']) {
                $this->scene = $scene;

                return true;
            }
        }

        //Get first scene by default
        reset($this->scenes);

        if ($scene = key($this->scenes)) {
            $this->scene = $scene;
        }

        return true;
    }


    /**
     * public function getScene ([string $scene])
     *
     * returns the $this->scene value
     *
     * return boolean/string
     */
    public function getScene ($scene = '')
    {
        if (!$scene) {
            return $this->scene;
        } else {
            return ($this->scene === $scene) ? true : false;
        }
    }


    /**
     * public function getSceneConfig ([string $variable], [string $compare])
     *
     * returns the config of $this->scene
     *
     * return boolean/string/array
     */
    public function getSceneConfig ($variable = '', $compare = '')
    {
        if (!$variable) {
            return $this->scenes[$this->scene];
        }

        if ($compare) {
            return ($this->scenes[$this->scene][$variable] == $compare) ? true : false;
        }

        return $this->scenes[$this->scene][$variable];
    }


    /**
     * public function detectModule ()
     *
     * Detect the current module
     *
     * return boolean
     */
    public function detectModule ()
    {
        $modules = $this->getSceneConfig('modules');

        if (!$modules || (strtolower($this->path[0]) != MODULE_WWW_SUBFOLDER)) {
            return false;
        }

        $this->pathShift();

        $module = strtolower($this->path[0]);

        if ($modules[$module]) {
            $this->pathShift();
            $this->module = $module;

            return true;
        } elseif ($module === 'index') {
            $this->module = key($modules);

            return true;
        }

        return false;
    }


    /**
     * public function getModule ([string $module])
     *
     * returns the $this->module value
     *
     * return boolean/string
     */
    public function getModule ($module = '')
    {
        if (!$module) {
            return $this->module;
        } else {
            return ($this->module === $module) ? true : false;
        }
    }


    /**
     * public function getModuleConfig ([string $variable], [string $compare])
     *
     * returns the config of $this->module
     *
     * return boolean/string/array
     */
    public function getModuleConfig ($variable = '', $compare = '')
    {
        $module_config = $this->getSceneConfig('modules');
        $module_config = $module_config[$this->module];

        if (!$variable) {
            return $module_config;
        }

        if ($compare) {
            return ($module_config[$variable] == $compare) ? true : false;
        }

        return $module_config[$variable];
    }


    /**
     * public function setLanguagesConfig ([array $config])
     *
     * Set the available languages
     *
     * return none
     */
    public function setLanguagesConfig ($config = null)
    {
        if (is_null($config)) {
            global $Config;

            $this->languages = (array) $Config->languages;
        } else {
            $this->languages = (array) $config;
        }
    }


    /**
     * public function getLanguages ([boolean $only_actives])
     *
     * Get the available languages
     *
     * return array
     */
    public function getLanguages ($only_actives = false)
    {
        if (!$only_actives) {
            return array_keys((array) $this->languages['availables']);
        }

        $languages = array();

        foreach ((array) $this->languages['availables'] as $language => $active) {
            if ($active) {
                $languages[] = $language;
            }
        }

        return $languages;
    }


    /**
     * public function detectLanguage ([string $lang])
     *
     * Detect the current language
     *
     * return boolean
     */
    public function detectLanguage ($lang = '')
    {
        $languages = $this->getLanguages(true);

        if (!$languages) {
            return false;
        }

        $language_var = 'phpcan_language_'.$this->scene;

        if ($this->module) {
            $language_var .= '_'.$this->module;
        }

        $cookie_time = 3600 * 24 * 365;

        //Set language directly
        if ($lang && in_array($lang, $languages)) {
            $this->language = $lang;

            if ($this->getCookie($language_var) != $this->language) {
                $this->setCookie($language_var, $this->language, $cookie_time);
            }

            return true;
        }

        //Detect language
        switch ($this->languages['detect']) {
            case 'subfolder':
                $language = $this->path[0];

                if (in_array($language, $languages)) {
                    $this->language = $language;
                    $this->pathShift();
                }
                break;

            case 'get':
                $language = $this->str('lang');

                if (in_array($language, $languages)) {
                    $this->language = $language;
                }
                break;

            case 'subdomain':
                reset($this->subdomains);

                $language = current($this->subdomains);

                if (in_array($language, $languages)) {
                    $this->language = $language;
                    array_shift($this->subdomains);
                }
                break;
        }

        //If language was detected
        if ($this->language) {
            //Save cookie
            if ($this->getCookie($language_var) != $this->language) {
                $this->setCookie($language_var, $this->language, $cookie_time);
            }

            return true;
        }

        //Language cookie
        if ($this->getCookie($language_var)) {
            $language = $this->getCookie($language_var);

            if (in_array($language, $languages)) {
                $this->language = $language;

                return true;
            }
        }

        //Default config
        if ($this->languages['default']) {
            $language = $this->languages['default'];

            if (in_array($language, $languages)) {
                $this->language = $language;

                $this->setCookie($language_var, $this->language, $cookie_time);

                return true;
            }
        }

        //Browser language
        $browser = $this->getBrowserLanguages();

        foreach ($browser as $language) {
            if (in_array($language, $languages)) {
                $this->language = $language;

                $this->setCookie($language_var, $this->language, $cookie_time);

                return true;
            }
        }

        //First available language
        reset($languages);

        $this->language = current($languages);

        $this->setCookie($language_var, $this->language, $cookie_time);

        return true;
    }


    /**
     * public function getBrowserLanguages ()
     *
     * Return the browser accepted languages
     *
     * return array
     */
    public function getBrowserLanguages ()
    {
        if (!$_SERVER['HTTP_ACCEPT_LANGUAGE']) {
            return array();
        }

        $browser = explode(',', str_replace(array(' ', 'q='), '', strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE'])));
        $languages = array();

        foreach ($browser as $language) {
            list($language, $q) = explode(';', $language);

            $q = is_null($q) ? 1 : $q;

            if (strstr($language, '-')) {
                $language = explode('-', $language);

                if (!$languages[$language[1]]) {
                    $languages[$language[1]] = $q;
                }

                if (!$languages[$language[0]]) {
                    $languages[$language[0]] = $q;
                }
            } else {
                $languages[$language] = $q;
            }
        }

        arsort($languages, SORT_NUMERIC);

        return array_keys($languages);
    }

    /**
     * function getLanguage ([string $language])
     *
     * returns the $this->language value
     *
     * return boolean/string
     */
    public function getLanguage ($language = '')
    {
        if (!$language) {
            return $this->language;
        } else {
            return ($this->language === $language) ? true : false;
        }
    }


    /**
     * public function setExitModesConfig ([array $config])
     *
     * Set the available exit modes
     *
     * return none
     */
    public function setExitModesConfig ($config = null)
    {
        if (is_null($config)) {
            global $Config;

            $this->exit_modes = (array) $Config->config['exit_modes'];
        } else {
            $this->exit_modes = (array) $config;
        }
    }



    /**
     * public function detectExitMode ()
     *
     * Asign $this->exit_mode value
     *
     * return true
     */
    public function detectExitMode ()
    {
        //Detect by phpcan_exit_mode variable
        if ($this->exists('phpcan_exit_mode')) {
            $exit_mode = $this->str('phpcan_exit_mode');
            $this->delete('phpcan_exit_mode');

            if ($exit_mode && $this->exit_modes[$exit_mode]) {
                $this->exit_mode = $exit_mode;

                //Remove from route too
                if ($this->path[0] && $this->exit_modes[$this->path[0]]) {
                    $this->pathShift();
                }

                return true;
            }
        }

        //Detect by path
        if ($this->path[0] && $this->exit_modes[$this->path[0]]) {
            $this->exit_mode = $this->path[0];
            $this->pathShift();

            return true;
        }

        //Autodetect ajax
        if ($_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest' && $this->exit_modes['ajax']) {
            $this->exit_mode = 'ajax';

            return true;
        }

        //Get the default exit_mode
        foreach ($this->exit_modes as $exit_mode => $settings) {
            if ($settings['default']) {
                $this->exit_mode = $exit_mode;

                return true;
            }
        }

        //Get the first exit mode by default
        reset($this->exit_modes);

        if ($exit_mode = key($this->exit_modes)) {
            $this->exit_mode = $exit_mode;
        }

        return true;
    }


    /**
     * function getExitMode ([string $exit_mode])
     *
     * returns the $this->exit_mode value
     *
     * return boolean/string
     */
    public function getExitMode ($exit_mode = '')
    {
        if (!$exit_mode) {
            return $this->exit_mode;
        } else {
            return ($this->exit_mode === $exit_mode) ? true : false;
        }
    }


    /**
     * function getExitModeConfig ([string $variable], [string $compare])
     *
     * returns the config of $this->exit_mode
     *
     * return boolean/string/array
     */
    public function getExitModeConfig ($variable = '', $compare = '')
    {
        if (!$variable) {
            return $this->exit_modes[$this->exit_mode];
        }

        if ($compare) {
            return ($this->exit_modes[$this->exit_mode][$variable] == $compare) ? true : false;
        }

        return $this->exit_modes[$this->exit_mode][$variable];
    }


    /**
     * function loadMessage (void)
     *
     * load the message var
     */
    public function loadMessage ()
    {
        $this->message['inbox'] = $this->getCookie('phpcan_message');
        $this->message['type'] = $this->getCookie('phpcan_message_type');
        $this->message['outbox'] = '';

        $this->deleteCookie('phpcan_message');
        $this->deleteCookie('phpcan_message_type');
    }


    /**
     * function message ([string $message], [string $type])
     *
     * save/return a message
     *
     * return string
     */
    public function message ($message = '', $type = '')
    {
        if (empty($message)) {
            return $this->message['inbox'];
        } else {
            $this->message['inbox'] = $message;
            $this->message['outbox'] = $message;
            $this->message['type'] = $type;
        }
    }

    /**
     * function messageExists (void)
     *
     * return boolean
     */
    public function messageExists ()
    {
        return $this->message['inbox'] ? true : false;
    }

    /**
     * function messageType (string $type)
     *
     * return boolean or string
     */
    public function messageType ($type = '')
    {
        if (empty($type)) {
            return $this->message['type'];
        } else {
            return ($this->message['type'] === $type) ? true : false;
        }
    }

    /**
     * public function exists (string/int $name)
     *
     * return boolean
     */
    public function exists ($name)
    {
        if (strpos($name, '[') && strpos($name, ']')) {
            $name = preg_replace('/^([^\[]+)/', '[\\1]', $name);
            $name = str_replace(array('[', ']'), array('["', '"]'), $name);
            $name = preg_replace('/(\["([0-9]+)"\])+/', '[\\2]', $name);

            eval('$return = isset($this->var'.$name.');');

            return $return;
        }

        return isset($this->var[$name]);
    }
}
