<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

namespace ANS\PHPCan\Files\Css;

defined('ANS') or die();

class Css {
    private $Debug;
    private $Css;

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

        $settings = $Config->cache['types']['css'];

        if ($settings['expire'] && $settings['interface']) {
            $this->Cache = new \ANS\Cache\Cache($settings);
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
            $settings = 'css';
        }

        if (is_string($settings) && $Config->config[$settings]) {
            $this->settings = $Config->$settings;
            $this->settings['cache'] = $Config->cache['types'][$settings];
        } else if (is_array($settings)) {
            $this->settings = $settings;
        } else {
            return false;
        }
    }

    public function load ($file) {
        $this->Css = \Stylecow\Parser::parseFile($file);

        return $this;
    }

    public function transform ($plugins) {
        $this->Css->applyPlugins($plugins);

        return $this;
    }

    public function toString () {
        return (string)$this->Css;
    }

    /**
     * public function show ([boolean $header], [boolean $die])
     *
     * Print the css file
     */
    public function show ($header = true, $die = true)
    {
        $cache = $this->settings['cache']['expire'];
        $cache = ($cache && $this->Cache) ? $cache : false;

        if ($header) {
            header('Content-type: text/css');

            if ($cache) {
                header('Expires: '.gmdate('D, d M Y H:i:s', (time() + $cache).' GMT'));
            }
        }

        //Get text
        $text = $this->toString();

        //Save cache
        if ($cache) {
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
     * Print the cached css file
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
            header('Content-type: text/css');
            header('Expires: '.gmdate('D, d M Y H:i:s', (time() + $cache).' GMT'));
        }

        echo $this->Cache->get($key);

        if ($die) {
            die();
        }
    }
}
