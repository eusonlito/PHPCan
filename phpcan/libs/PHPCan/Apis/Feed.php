<?php
/*
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

namespace PHPCan\Apis;

defined('ANS') or die();

class Feed extends Api
{
    /**
     * public function get (string $url, [int $cache])
     *
     * return array
     */
    public function get ($url, $cache = null)
    {
        return $this->getXML($url, $cache);
    }

    /**
     * public function atomEntries (object/string $xml)
     *
     * return array
     */
    public function atomEntries ($xml, $cache = null)
    {
        if (is_string($xml)) {
            $xml = $this->get($xml, $cache);
        }

        $entries = array();

        if (!$xml->entry) {
            return $entries;
        }

        foreach ($xml->entry as $entry) {
            foreach ($entry->link as $link) {
                if ($link->attributes()->rel == 'alternate') {
                    $link = $link->attributes()->href;
                    break;
                }
            }

            $entries[] = array(
                'id' => (string) $entry->id,
                'title' => strip_tags((string) $entry->title),
                'link' => (string) $link,
                'published' => (string) $entry->published,
                'updated' => (string) $entry->updated,
                'author' => (array) $entry->author->name,
                'content' => (string) $entry->content
            );
        }

        return $entries;
    }

    /**
     * private function rssEntries (object/string $xml)
     *
     * return array
     */
    public function rssEntries ($xml, $cache = null)
    {
        if (is_string($xml)) {
            $xml = $this->get($xml, $cache);
        }

        $entries = array();

        if (!$xml->channel->item) {
            return $entries;
        }

        foreach ($xml->channel->item as $entry) {
            $entries[] = array(
                'id' => (string) $entry->id,
                'title' => strip_tags((string) $entry->title),
                'link' => (string) $entry->link,
                'published' => (string) $entry->pubDate,
                'content' => (string) $entry->description
            );
        }

        return $entries;
    }
}
