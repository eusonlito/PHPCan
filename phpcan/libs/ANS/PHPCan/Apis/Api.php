<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

namespace ANS\PHPCan\Apis;

defined('ANS') or die();

class Api
{
    private $Cache;

    protected $Errors;
    protected $Debug;
    protected $settings;

    public $curl_options = array();
    public $urls;

    /**
     * public function __construct ([string $autoglobal])
     *
     * return none
     */
    public function __construct ($autoglobal = '')
    {
        global $Debug, $Errors;

        $this->Debug = $Debug;
        $this->Errors = $Errors;

        if ($autoglobal) {
            global $Config;

            $Config->config['autoglobal'][] = $autoglobal;
        }

        $this->settings = array();

        $this->setCache();
    }

    private function setCache ()
    {
        global $Config;

        $settings = $Config->cache['types']['api'];

        if ($settings['expire'] && $settings['interface']) {
            $this->Cache = new \Cache\Cache($settings);
            $this->settings['cache'] = $settings;
        } else {
            $this->Cache = false;
            $this->settings['cache'] = array();
        }
    }

    /**
     * private function curl (string $url)
     *
     * return array
     */
     private function curl ($url)
     {
        //Save the url
        $this->urls[] = $url;

        $connection = curl_init($url);

        curl_setopt($connection, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($connection, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);

        if ($this->curl_options) {
            curl_setopt_array($connection, $this->curl_options);
        }

        $response = curl_exec($connection);
        curl_close($connection);

        return trim($response);
     }

     private function getUrlAndCache ($url, $data = array(), $cache = 0)
     {
         if (is_int($data)) {
            $cache = $data;
            $data = array();
        }

        if (is_array($data) && ($data = http_build_query($data))) {
            $url .= strpos($url, '?') ? ('&'.$data) : ('?'.$data);
        }

        return array($url, $cache);
     }

    /**
     * protected function getJSON (string $url, [array $data], [int $cache])
     *
     * return object/array
     */
    public function getJSON ($url, $data = array(), $cache = null)
    {
        list($url, $cache) = $this->getUrlAndCache($url, $data, $cache);

        $key = md5($url).'.json';

        $cache = is_null($cache) ? $this->settings['cache']['expire'] : $cache;
        $cache = ($cache && $this->Cache) ? $cache : false;

        if ($cache && $this->Cache->exists($key)) {
            return $this->Cache->get($key);
        }

        $return = json_decode($this->curl($url));

        if ($cache) {
            $this->Cache->set($key, $return, $cache);
        }

        return $return;
    }

    /**
     * protected function getXML (string $url, [array $data], [int $cache])
     *
     * return SimpleXML object
     */
    public function getXML ($url, $data = array(), $cache = null)
    {
        list($url, $cache) = $this->getUrlAndCache($url, $data, $cache);

        $key = md5($url).'.xml';

        $cache = is_null($cache) ? $this->settings['cache']['expire'] : $cache;
        $cache = ($cache && $this->Cache) ? $cache : false;

        if ($cache && $this->Cache->exists($key)) {
            return @new _SimpleXMLElement($this->Cache->get($key));
        }

        $return = $this->curl($url);

        if (!$return) {
            return false;
        }

        if ($cache) {
            $this->Cache->set($key, $return, $cache);
        }

        if (!$return || $return[0] != '<') {
            return false;
        }

        return @new _SimpleXMLElement($return);
    }

    /**
     * protected function getPHP (string $url, [array $data], [int $cache])
     *
     * return SimpleXML object
     */
    public function getPHP ($url, $data = array(), $cache = null)
    {
        list($url, $cache) = $this->getUrlAndCache($url, $data, $cache);

        $key = md5($url).'.txt';

        $cache = is_null($cache) ? $this->settings['cache']['expire'] : $cache;
        $cache = ($cache && $this->Cache) ? $cache : false;

        if ($cache && $this->Cache->exists($key)) {
            return unserialize($this->Cache->get($key));
        }

        $return = $this->curl($url);

        if (!$return) {
            return false;
        }

        if ($cache) {
            $this->Cache->set($key, $return, $cache);
        }

        return unserialize($return);
    }

    /**
     * public function toArray (array $results)
     *
     * Convert an object to array
     *
     * return array
     */
    public function toArray ($results)
    {
        if (!is_object($results) && !is_array($results)) {
            return $results;
        }

        $results = (array) $results;

        foreach ($results as $k => $result) {
            $results[$k] = $this->toArray($result);
        }

        return $results;
    }
}
