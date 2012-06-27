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

//API documentation: http://develop.github.com/

class Github extends Api
{
    /**
     * public function get (string $path, [int $cache], [boolean $return_array])
     *
     * return array
     */
    public function get ($path, $cache = null, $return_array = true)
    {
        $url = 'http://github.com/api/v2/json/'.$path;

        $return = $this->getJSON($url, $cache);

        if ($return_array) {
            return $this->toArray($return);
        }

        return $return;
    }
}
