<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

namespace ANS\PHPCan\Templates\Html;

defined('ANS') or die();

class Media
{
    private $Debug;
    private $Html;

    public $width = 480;
    public $height = 360;

    /**
     * public function __construct ($Html, [string $autoglobal])
     */
    public function __construct (\ANS\PHPCan\Templates\Html\Html $Html, $autoglobal = '')
    {
        global $Vars, $Debug;

        if ($Vars->getExitMode('ajax')) {
            $this->tabindex = 1000;
        }

        $this->Debug = $Debug;
        $this->Html = $Html;

        if ($autoglobal) {
            global $Config;

            $Config->config['autoglobal'][] = $autoglobal;
        }
    }

    /**
     * public function media (array $url, [int $width], [int $height])
     */
    public function media ($url, $width = 0, $height = 0)
    {
        $url['info'] = is_string($url['info']) ? unserialize($url['info']) : $url['info'];

        switch ($url['type']) {
            case 'youtube':
                return $this->youtube($url['info'], $width, $height);

            case 'vimeo':
                return $this->vimeo($url['info'], $width, $height);
        }
    }

    /**
     * public function img (array $url)
     */
    public function img ($url)
    {
        switch ($url['type']) {
            case 'youtube':
            case 'vimeo':
            case 'twitpic':
            case 'slideshare':
            case 'dailymotion':
                return $url['info']['image'];
        }
    }

    /**
     * public function youtube (string/array $id, [int $width], [int $height])
     */
    public function youtube ($id, $width = 0, $height = 0)
    {
        if (is_array($id)) {
            $id = $id['id'];
        }

        $params = array(
            'width' => $width ? $width : $this->width,
            'height' => $height ? $height : $this->height,
            'src' => 'http://www.youtube.com/embed/'.$id.'?wmode=Opaque',
            'frameborder' => '0'
        );

        return '<iframe'.$this->Html->params($params).' allowfullscreen></iframe>';
    }

    /**
     * public function vimeo (string/array $id, [int $width], [int $height])
     */
    public function vimeo ($id, $width = 480, $height = 360)
    {
        if (is_array($id)) {
            $id = $id['id'];
        }

        $params = array(
            'width' => $width ? $width : $this->width,
            'height' => $height ? $height : $this->height,
            'src' => 'http://player.vimeo.com/video/'.$id.'?title=0&amp;byline=0&amp;portrait=0',
            'frameborder' => '0'
        );

        return '<iframe'.$this->Html->params($params).' webkitAllowFullScreen allowFullScreen></iframe>';
    }
}
