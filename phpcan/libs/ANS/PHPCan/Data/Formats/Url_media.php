<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

namespace ANS\PHPCan\Data\Formats;

defined('ANS') or die();

class Url_media extends Url implements Iformats
{
    public $format = 'url_media';

    public function check ($value)
    {
        $this->error = array();

        if (!$this->settings['url']['required'] && !$value['url']) {
            return true;
        }

        if ($value['url']) {
            $value['url'] = $this->fixUrl($value['url'], true);

            if (!$this->checkUrl($value['url'], 'url')) {
                return false;
            }
        }

        if (!$this->validate($value)) {
            return false;
        }

        if (!$this->getInfo($value['url'])) {
            $this->error['url'] = __('Service url for field "%s" is not available', __($this->name));

            return false;
        }

        return true;
    }

    public function valueDB (\ANS\PHPCan\Data\Db $Db, $value, $language = '', $id = 0)
    {
        return $this->fixValue($value);
    }

    public function fixValue ($value)
    {
        if (!$value['url']) {
            return false;
        }

        $value['url'] = $this->fixUrl($value['url'], true);

        $info = $this->getInfo($value['url']);

        if ($info) {
            return array(
                'url' => $value['url'],
                'type' => $info['type'],
                'info' => base64_encode(serialize($info['info']))
            );
        }

        return false;
    }

    public function valueHtml ($value)
    {
        $value['info'] = $this->_unserialize($value['info']);

        return $value;
    }

    public function valueForm ($value)
    {
        $value['info'] = $this->_unserialize($value['info']);

        return $value;
    }

    public function _unserialize ($value)
    {
        if (!$value) {
            return $value;
        } elseif (strstr($value, ':') === false) {
            $value = base64_decode($value);
        }

        $value = unserialize($value);

        return $value;
    }

    public function getInfo ($url)
    {
        $url_info = urlInfo($url);

        foreach ($this->settings['type']['db_values'] as $type) {
            $fn = 'type_'.$type;

            if (method_exists($this, $fn)) {
                if ($info = $this->$fn($url_info)) {
                    return array(
                        'type' => $type,
                        'info' => $info
                    );
                }
            }
        }

        return false;
    }

    public function settings ($settings)
    {
        $settings['values'] = array(
            'youtube',
            'vimeo',
            'dailymotion',
            'flickr_pool',
            'gmaps',
            'imgur',
            'slideshare',
            'twitpic'
        );

        $this->settings = $this->setSettings($settings, array(
            'url' => array(
                'db_type' => 'varchar',

                'length_max' => 255,
            ),
            'type' => array(
                'db_type' => 'enum',
                'db_values' => $settings['values'],
                'db_null' => true,

                'values' => array(),
            ),
            'info' => array(
                'db_type' => 'text',
            )
        ));

	unset($this->settings['type']['required'], $this->settings['info']['required']);

        return $this->settings;
    }

    /**
     * youtube
     *
     * http://www.youtube.com/watch?v=$id
     * http://youtu.be/$id
    */
    private function type_youtube ($url_info)
    {
        if ($url_info['host'] === 'youtu.be') {
            $id = $url_info['basename'];
        } elseif ($url_info['host'] === 'www.youtube.com') {
            if ($url_info['query']['v']) {
                $id = $url_info['query']['v'];
            } elseif (($url_info['path'][0] === 'v') && $url_info['path'][1]) {
                $id = $url_info['path'][1];
            }
        }

        if (!$id) {
            return false;
        }

        $Api = new \ANS\PHPCan\Apis\Api;

        $info = $Api->getXML('http://gdata.youtube.com/feeds/api/videos/'.$id);

        if (!$info) {
            return false;
        }

        return array(
            'id' => $id,
            'title' => (string) $info->title,
            'description' => (string) $info->content,
            'image' => 'http://img.youtube.com/vi/'.$id.'/0.jpg'
        );
    }

    /**
     * vimeo
     *
     * http://vimeo.com/$id
     * http://www.vimeo.com/$id
    */
    private function type_vimeo ($url_info)
    {
        if (!preg_match('/vimeo.com$/i', $url_info['host']) || !($id = $url_info['basename'])) {
            return false;
        }

        $Api = new \ANS\PHPCan\Apis\Api;

        $info = $Api->getPHP('http://vimeo.com/api/v2/video/'.$id.'.php');

        if (!$info) {
            return false;
        }

        return array(
            'id' => $id,
            'title' => $info[0]['title'],
            'description' => $info[0]['description'],
            'image' => $info[0]['thumbnail_large']
        );
    }

    /**
     * dailymotion
     *
     * http://www.dailymotion.com/video/$id
    */
    private function type_dailymotion ($url_info)
    {
        if (($url_info['host'] !== 'www.dailymotion.com') || ($url_info['dirname'] !== '/video') || !($id = current(explode('_', $url_info['basename'], 2)))) {
            return false;
        }

        $Api = new \ANS\PHPCan\Apis\Api;
        $Api->curl_options[CURLOPT_SSL_VERIFYPEER] = false;

        $info = $Api->getJSON('https://api.dailymotion.com/video/'.$id, array('fields' => 'title,description,thumbnail_url'));

        if (!$info) {
            return false;
        }

        return array(
            'id' => $id,
            'title' => $info->title,
            'description' => $info->description,
            'image' => $info->thumbnail_url
        );
    }

    /**
     * slideshare
     *
     * http://www.slideshare.net/$user/$id
    */
    private function type_slideshare ($url_info)
    {
        if (($url_info['host'] !== 'www.slideshare.net') || (count($url_info['path']) !== 2)) {
            return false;
        }

        $Api = new \ANS\PHPCan\Apis\Api;

        $info = $Api->getJSON('http://www.slideshare.net/api/oembed/2', array(
            'format' => 'json',
            'url' => $url_info['url']
        ));

        if (!$info) {
            return false;
        }

        return array(
            'id' => $info->slideshow_id,
            'title' => $info->title,
            'image' => $info->thumbnail
        );
    }

    /**
     * gmaps
     *
     * http://maps.google.??/maps/$query
    */
    private function type_gmaps ($url_info)
    {
        if (!preg_match('/^maps\.google\.[a-z]+$/', $url_info['host']) || ($url_info['basename'] !== 'maps')) {
            return false;
        }

        $sll = explode(',', $url_info['query']['sll'], 2);

        return array(
            'q' => $url_info['query']['q'],
            'x' => $sll[0],
            'y' => $sll[1],
            'z' => $url_info['query']['z'],
            'image' => 'http://maps.googleapis.com/maps/api/staticmap'.get(array(
                'center' => $url_info['query']['q'],
                'zoom' => $url_info['query']['z'],
                'size' => '500x400',
                'sensor' => 'false'
            ))
        );
    }

    /**
     * flickr_pool
     *
     * http://www.flickr.com/groups/$id/pool/
    */
    private function type_flickr_pool ($url_info)
    {
        if (($url_info['host'] !== 'www.flickr.com') || ($url_info['path'][0] !== 'groups') || ($url_info['path'][2] !== 'pool')) {
            return false;
        }

        $id = $url_info['path'][1];

        $Api = new \ANS\PHPCan\Apis\Api;

        $info = $Api->getPHP('http://api.flickr.com/services/feeds/groups_pool.gne', array(
            'format' => 'php_serial',
            'id' => $id
        ));

        $items = array();

        foreach ($info['items'] as $item) {
            $items[] = array(
                'title' => $item['title'],
                'image' => $item['photo_url'],
                'author' => $item['author_name'],
            );
        }

        return array(
            'id' => $id,
            'title' => $info['title'],
            'description' => $info['description'],
            'items' => $items
        );
    }

    /**
     * twitpic
     *
     * http://www.twitpic.com/$id
     * http://twitpic.com/$id
    */
    private function type_twitpic ($url_info)
    {
        if (!preg_match('/^(www\.)?twitpic\.com$/', $url_info['host']) || (count($url_info['path']) !== 1)) {
            return false;
        }

        return array(
            'id' => $url_info['path'][0],
            'image' => 'http://twitpic.com/show/thumb/'.$url_info['path'][0].'.jpg',
        );
    }

    /**
     * imgur
     *
     * http://imgur.com/gallery/$id
    */
    private function type_imgur ($url_info)
    {
        if (($url_info['host'] !== 'imgur.com') || ($url_info['path'][0] !== 'gallery') || !$url_info['path'][1]) {
            return false;
        }

        $id = $url_info['path'][1];

        $Api = new \ANS\PHPCan\Apis\Api;

        $info = $Api->getJSON('http://imgur.com/gallery/'.$id.'.json');

        return array(
            'id' => $id,
            'title' => $info->gallery->image->title,
            'width' => $info->gallery->image->width,
            'height' => $info->gallery->image->height,
            'image' => 'http://i.imgur.com/'.$id.$info->gallery->image->ext,
        );
    }
}
