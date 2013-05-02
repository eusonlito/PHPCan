<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

namespace ANS\PHPCan\Files\Js;

defined('ANS') or die();

class Js extends JSMin {
    private $Debug;
    private $file;
    private $contents;

    public $settings = array();


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

        $this->setCache();
        $this->setSettings();
    }

    private function setCache ()
    {
        global $Config;

        $settings = $Config->cache['types']['js'];

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
     * public function setSettings (array $settings = null)
     *
     * return boolean
     */
    public function setSettings ($settings = null)
    {
        global $Config;

        if (is_null($settings)) {
            $settings = 'js';
        }

        if (is_string($settings) && $Config->config[$settings]) {
            $this->settings = $Config->$settings;
        } else if (is_array($settings)) {
            $this->settings = $settings;
        }

        if ($this->Cache && is_array($Config->cache['types'][$settings])) {
            $this->settings['cache'] = $Config->cache['types'][$settings];
        } else {
            $this->settings['cache'] = array();
        }

        return $this->settings;
    }

    public function load ($file)
    {
        $this->file = '';
        $this->contents = '';

        if (!is_file($file)) {
            return $this;
        }

        $this->file = $file;

        return $this;
    }

    public function process ()
    {
        if (empty($this->file)) {
            return $this;
        }

        ob_start();

        include ($this->file);

        $this->contents = ob_get_contents();

        ob_end_clean();

        return $this;
    }

    public function toString ()
    {
        if (empty($this->file)) {
            return '';
        }

        if (empty($this->contents)) {
            $this->contents = file_get_contents($this->file);
        }

        return ($this->settings['cache']['minify'] ? $this->minify($this->contents) : $this->contents);
    }

    /**
     * public function show ([boolean $header], [boolean $die])
     *
     * Print the js file
     */
    public function show ($header = true, $die = true)
    {
        $cache = $this->settings['cache']['expire'];
        $cache = ($cache && $this->Cache) ? $cache : false;

        if ($header) {
            header('Content-type: text/js');

            if ($cache) {
                header('Expires: '.gmdate('D, d M Y H:i:s', (time() + $cache).' GMT'));
            }
        }

        $text = $this->toString();

        if ($cache) {
            $text = $this->settings['cache']['minify'] ? $this->minify($text) : $text;
            $this->Cache->set(md5($this->file), $text, $cache);
        }

        echo $text;

        if ($die) {
            die();
        }
    }

    /**
     * public function showCached (string $file, [boolean $header], [boolean $die])
     *
     * Print the cached js file
     *
     * return boolean
     */
    public function showCached ($file, $header = true, $die = true)
    {
        $cache = $this->settings['cache']['expire'];
        $cache = ($cache && $this->Cache) ? $cache : false;

        $key = md5($file);

        if (empty($cache) || !$this->Cache->exists($key)) {
            return false;
        }

        if ($header) {
            header('Content-type: text/js');
            header('Expires: '.gmdate('D, d M Y H:i:s', (time() + $cache).' GMT'));
        }

        echo $this->Cache->get($key);

        if ($die) {
            die();
        }
    }
}
