<?php
/*
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

namespace ANS\PHPCan\Apis\Google;

defined('ANS') or die();

use \ANS\PHPCan\Apis;

//API Documentations: http://code.google.com/apis/websearch/docs/reference.html

class Search extends \ANS\PHPCan\Apis\Api
{
    private $result = [];

    /**
     * public function search (string/array $data, [int $cache])
     *
     * return array
     */
    public function search($data, $cache = null)
    {
        if (is_string($data)) {
            $data = array('q' => $data);
        }

        $data['v'] = '1.0';
        $data['rsz'] = empty($data['rsz']) ? '8' : $data['rsz'];
        $data['start'] = empty($data['start']) ? '1' : $data['start'];
        $data['ip'] = ip();

        $this->result = $this->getJSON('http://ajax.googleapis.com/ajax/services/search/web', $data, $cache);

        return $this;
    }

    public function getRaw()
    {
        return $this->toArray($this->result);
    }

    public function getSimple()
    {
        if (empty($this->result) || empty($this->result->responseData->results)) {
            return array(
                'results' => array(),
                'pagination' => array()
            );
        }

        $simple = array();

        foreach ($this->result->responseData->results as $value) {
                $simple['results'][] = array(
                    'url' => $value->unescapedUrl,
                    'escaped_url' => $value->url,
                    'domain' => $value->visibleUrl,
                    'cache' => $value->cacheUrl,
                    'formated_title' => $value->title,
                    'title' => $value->titleNoFormatting,
                    'text' => $value->content
                );
        }

        $simple['pagination'] = array(
            'first' => 1,
            'last' => count($this->result->responseData->cursor->pages),
            'total_pages' => count($this->result->responseData->cursor->pages),
            'total' => $this->result->responseData->cursor->estimatedResultCount,
            'page' => $this->result->responseData->cursor->currentPageIndex + 1
        );

        return $simple;
    }
}
