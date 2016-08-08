<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

namespace ANS\PHPCan\Files\Images;

defined('ANS') or die();

require_once (LIBS_PATH.'/imagecow/imagecow/src/autoloader.php');

class Image
{
    public $settings = array();

    protected $Debug;
    public $Image;

    /**
     * public function __construct ([string $autoglobal])
     *
     * return none
     */
    public function __construct ($autoglobal = '', $lib = null)
    {
        global $Debug;

        $this->Debug = $Debug;

        if ($autoglobal) {
            global $Config;

            $Config->config['autoglobal'][] = $autoglobal;
        }

        $this->setCache();
    }

    private function setCache ()
    {
        global $Config;

        $settings = $Config->cache['types']['images'];

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

        $this->settings = array();

        if (is_null($settings)) {
            $settings = 'images';
        }

        if (is_string($settings) && $Config->cache['types'][$settings]) {
            $this->settings['cache'] = $Config->cache['types'][$settings];
        }

        if (is_string($settings) && $Config->config[$settings]) {
            $this->settings = array_merge($this->settings, $Config->config[$settings]);
        } else if (is_array($settings)) {
            $this->settings = array_merge($this->settings, $settings);
        } else {
            return false;
        }

        if (empty($this->settings['quality'])) {
            $this->settings['quality'] = 90;
        }

        return false;
    }

    /**
     * public function transform ([string $operations], [$cache = null])
     *
     * Execute a list of operations
     */
    public function transform ($operations = '', $cache = null)
    {
        if (empty($operations)) {
            return $this;
        }

        $this->Image->transform(str_replace('zoomCrop', 'resizeCrop', $operations));

        $cache = is_null($cache) ? $this->settings['cache']['expire'] : $cache;
        $cache = ($cache && $this->Cache) ? $cache : false;

        if ($cache) {
            $key = md5($this->Image->getFileName().serialize($operations));
            $this->Cache->set($key, $this->getContents(), $cache);
        }

        return $this;
    }

    /**
     * public function showCached (string $file, [string $transform], [bool $header])
     *
     * Print the cached image file
     *
     * return boolean
     */
    public function showCached ($file, $transform = '', $header = true)
    {
        $cache = $this->settings['cache']['expire'];
        $cache = ($cache && $this->Cache) ? $cache : false;

        $key = md5($file.serialize($transform));

        if (empty($cache) || !$this->Cache->exists($key)) {
            return false;
        }

        if ($header) {
            $info = getImageSize($file);

            header('Content-Type: '.$info['mime']);
            header('Expires: '.gmdate('D, d M Y H:i:s', (time() + $cache)).' GMT');
        }

        echo $this->Cache->get($key);

        die();
    }

    /**
     * public function load (string $image)
     *
     * Load an image
     */
    public function load ($image)
    {
        $this->Image = \Imagecow\Image::fromFile($image);
        $this->Image->quality($this->settings['quality']);

        return $this;
    }

    /**
     * public function getContents (void)
     *
     * Return the current image source
     *
     * return string
     */
    public function getContents ()
    {
        return $this->Image->getString();
    }

    /**
     * public function unload (void)
     *
     * Destroy an image
     */
    public function unload ()
    {
        unset($this->Image);
        return $this;
    }

    /**
     * public function save (string $filename)
     *
     * Save the image to file
     */
    public function save ($filename = '')
    {
        $this->Image->save($filename);

        return $this;
    }

    /**
     * public function resize (int $width, [int $height], [bool $enlarge])
     *
     * Resize an image
     */
    public function resize ($width, $height = 0, $enlarge = false)
    {
        $this->Image->resize($width, $height, $enlarge);

        return $this;
    }

    /**
     * public function crop (int $width, int $height, [int $x], [int $y])
     *
     * Crop an image
     */
    public function crop ($width, $height, $x = 'center', $y = 'middle')
    {
        $this->Image->crop($width, $height, $x, $y);

        return $this;
    }

    /**
     * public function flip (void)
     *
     * Invert an image vertically
     */
    public function flip ()
    {
        $this->Image->flip();

        return $this;
    }

    /**
     * public function flop (void)
     *
     * Invert an image horizontally
     */
    public function flop ()
    {
        $this->Image->flop();

        return $this;
    }

    /**
     * public function zoomCrop (int $width, int $height, [string $x], [string $y])
     *
     * Crop an resize an image to specific dimmensions
     */
    public function zoomCrop ($width, $height, $x = 'center', $y = 'middle')
    {
        $this->Image->resizeCrop($width, $height, $x, $y);

        return $this;
    }

    /**
     * public function show ([bool $header])
     *
     * Return the image
     */
    public function show ($header = true)
    {
        $this->Image->show();
    }

    /**
     * public function rotate (int $degrees)
     *
     * Rotate an image
     */
    public function rotate ($degrees)
    {
        $this->Image->rotate($degrees);

        return $this;
    }

    /**
     * public function convert (string $format)
     *
     * Convert an image to another format
     */
    public function convert ($format)
    {
        $this->Image->format($format);

        return $this;
    }
}
