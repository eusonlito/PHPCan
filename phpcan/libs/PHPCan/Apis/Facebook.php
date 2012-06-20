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

//Api documentation: http://developers.facebook.com/docs/reference/api/

class Facebook extends Api
{
    private $access_token;

    /**
     * public function accessToken ([string $key])
     *
     * return array
     */
    public function accessToken ($key = null)
    {
        if (is_null($key)) {
            return $this->access_token;
        }

        if ($this->access_token = $key) {
            $this->curl_options[CURLOPT_SSL_VERIFYPEER] = false;
        }
    }

    /**
     * public function get (string $path, [array $data], [int $cache], [boolean $return_array])
     *
     * return array
     */
    public function get ($path, $data = array(), $cache = null, $return_array = true)
    {
        if ($this->access_token) {
            if (is_int($data)) {
                $cache = $data;
                $data = array('access_token' => $this->access_token);
            } else {
                $data['access_token'] = $this->access_token;
            }

            $return = $this->getJSON('https://graph.facebook.com/'.$path, $data, $cache);
        } else {
            $return = $this->getJSON('http://graph.facebook.com/'.$path, $data, $cache);
        }

        if ($return_array) {
            return $this->toArray($return);
        }

        return $return;
    }
}
