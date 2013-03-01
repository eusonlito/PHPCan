<?php
/*
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

namespace ANS\PHPCan\Apis;

defined('ANS') or die();

//Api documentation: https://dev.twitter.com/docs/api

class Twitter extends Api
{
    /**
     * public function search (string/array $data, [int $cache], [boolean $return_array])
     *
     * return array
     */
    public function search ($data, $cache = null, $return_array = true)
    {
        if (is_string($data)) {
            $data = array('q' => $data);
        }

        $return = $this->getJSON('http://search.twitter.com/search.json', $data, $cache);

        if ($return_array) {
            return $this->toArray($return);
        }
    }

    /**
     * public function get (string $path, [array $data], [int $cache], [boolean $return_array])
     *
     * return array
     */
    public function get ($path, $data = array(), $cache = null, $return_array = true)
    {
        $url = 'http://api.twitter.com/1/'.$path.'.json';

        $return = $this->getJSON($url, $data, $cache);

        if ($return_array) {
            return $this->toArray($return);
        }

        return $return;
    }

    /**
     * public function getHashTags (string $text)
     *
     * return array
     */
    public function getHashTags ($text)
    {
        preg_match_all('/#(\w+)/u', $text, $matches);

        return (array)$matches[1];
    }

    /**
     * public function getUsers (string $text)
     *
     * return array
     */
    public function getUsers ($text)
    {
        preg_match_all('/@(\w+)/u', $text, $matches);

        return (array)$matches[1];
    }

    /**
     * public function autoLinks (string/array $text, [boolean $users], [boolean $hashtags], [boolean $links])
     *
     * return string
     */
    public function autoLinks ($text, $users = true, $hashtags = true, $links = true)
    {
        if (is_array($text)) {
            if (is_object(current($text))) {
                foreach ($text as $k => $t) {
                    $text[$k]->text = $this->autoLinks($text[$k]->text, $users, $hashtags);
                }
            } else {
                foreach ($text as $k => $t) {
                    $text[$k]['text'] = $this->autoLinks($text[$k]['text'], $users, $hashtags);
                }
            }

            return $text;
        }

        if ($links) {
            $text = preg_replace('/(http[s]?:\/\/[^\s]+)/u', ' <a href="\\1">\\1</a>', $text);
        }

        if ($users) {
            $text = preg_replace('/@(\w+)/u', '<a href="http://twitter.com/\\1">@\\1</a>', $text);
        }

        if ($hashtags) {
            $text = preg_replace('/#(\w+)/u', '<a href="http://search.twitter.com/search?q=%23\\1">#\\1</a>', $text);
        }

        return $text;
    }
}
