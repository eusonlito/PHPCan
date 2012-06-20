<?php
namespace PHPCan\Apis;

defined('ANS') or die();

/**
* Simple PHP Bit.ly API Wrapper Class.
*
* @author Jeff Johns <phpfunk@gmail.com>
* @license MIT License
*/
class Bitly extends Api
{
  public $apiKey    =   NULL;
  public $login     =   NULL;
  public $res       =   NULL;

  protected static $endpoint = 'http://api.bit.ly/v3';

  public function __construct ($autoglobal = '')
  {
    parent::__construct($autoglobal);

    global $Config;

    $Config->load('bitly.php');

    $this->apiKey = $Config->bitly['api_key'];
    $this->login = $Config->bitly['user'];
  }

  /**
  * Magic method to request call for any method called
  * that does not exist in the object.
  *
  * @param  string  $method   method called
  * @param  array   $args     array of arguments
  *
  * @return array
  */
  public function __call($method, $args)
  {
    return self::call($method, $args, "object");
  }

  /**
  * Magic method to request call for any method called
  * that does not exist. This is the static version.
  * Only available in PHP 5.3.0+
  *
  * @param  string  $method   method called
  * @param  array   $args     array of arguments
  *
  * @return array
  */
  public static function __callStatic($method, $args)
  {
    return self::call($method, $args, "static");
  }

  /**
  * Called when you call a method that doesn't exist.
  * Both the static and OO methods call this method
  * to send the API requests to bit.ly.
  *
  * @param  string  $method   method called
  * @param  array   $args     array of arguments
  * @param  string  $type     type of call (object || static)
  *
  * @return array
  */
  protected function call($method, $args=array(), $type)
  {
    $method         =   strtolower($method);
    $params         =   null;
    $format         =   'json';
    $key_found      =   false;
    $login_found    =   false;
    $args[0]        =   (!isset($args[0])) ? array() : $args[0];

    foreach ($args[0] as $key => $val) {
      $amp              = (empty($params)) ? '' : '&';
      $params          .=   $amp . $key . '=' . urlencode($val);
      $format           =   ($key == 'format')  ? strtolower($val) : $format;
      $key_found        =   ($key == 'apiKey')  ? true : $key_found;
      $login_found      =   ($key == 'login')  ? true : $login_found;
    }

    $params .= ($key_found === false && $type == 'object') ? '&apiKey=' . $this->apiKey : '';
    $params .= ($login_found === false && $type == 'object') ? '&login=' . $this->login : '';
    $params = (substr($params, 0, 1) == '&') ? substr($params, 1) : $params;

    $res = file_get_contents(self::$endpoint . '/' . $method . '?' . $params);
    if ($format == 'xml') {
      $res = simplexml_load_string(self::remove_cdata($res));
    } else {
      $res = json_decode($res);
    }

    if ($type == 'object') {
      $this->res = $res;
      $this->apiKey = ($key_found === true) ? $args[0]['apiKey'] : $this->apiKey;
      $this->login = ($login_found === true) ? $args[0]['login'] : $this->login;
    }

    return $res;
  }

  /**
  * General method to get data from the returned
  * result. You can call any key from the results
  * object returned from the API call.
  *
  * @param  string  $what : What you want to be returned (EX: shortUrl)
  *
  * @return bool || string || array
  */
  public function get($what)
  {
    $method = 'get_' . $what;
    if (method_exists($this, $method)) {
      return $this->$method();
    }

    $key = $this->get_key();
    if ($key === false) {
      return (isset($this->res->data->$what)) ? $this->res->data->$what : false;
    }

    return (isset($this->res->data->$key->$what)) ? $this->res->data->$key->$what : false;
  }

  /**
  * Returns a string with the error code and message
  *
  * @return string
  */
  protected function get_error()
  {
    return 'Error Number (' . $this->res->status_code . '): ' . $this->res->status_txt;
  }

  /**
  * Returns the first key, where applicable under
  * the results object returned from the API.
  *
  * @return bool || string
  */
  protected function get_key()
  {
    foreach ((array) $this->res->data as $key => $arr) {
      if (is_array($this->res->data->$key)) {
        return $key;
      } else {
        return false;
      }
    }
  }

  /**
  * Returns a boolean if there is an error or not
  *
  * @return bool
  */
  public function is_error()
  {
    return ($this->res->status_code != '200') ? true : false;
  }

  /**
  * Returns boolean if the string is json or not
  *
  * @param  string  $str  String to be evaluated
  *
  * @return bool
  */
  protected function is_json($str)
  {
    return (substr(trim($str), 0, 1) == '<') ? false : true;
  }


  /**
  * Remove CDATA tags from XML
  *
  * @param  string   $str The string to remove CDATA from
  *
  * @return string
  */
  protected static function remove_cdata($str)
  {
    return preg_replace('#<!\[CDATA\[(.*?)\]\]>#s', "$1", $str);
  }

}
