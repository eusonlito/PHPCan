<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

namespace ANS\PHPCan\Data;

defined('ANS') or die();

class Errors
{
    private $Debug;
    private $errors = array();

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
    }

    /**
     * public function set (string $name, array $error, [int $index])
     *
     * return none
     */
    public function set ($name, $error, $index = null)
    {
        if (is_null($index)) {
            $this->errors[$name] = arrayMergeReplaceRecursiveStrict((array) $this->errors[$name], (array) $error);
        } else {
            $this->errors[$name][$index] = arrayMergeReplaceRecursiveStrict((array) $this->errors[$name][$index], (array) $error);
        }
    }

    /**
     * public function get ([string $name], [int $offset], [int $length])
     *
     * return none
     */
    public function get ($name = '', $offset = 0, $length = null)
    {
        $errors = $name ? $this->errors[$name] : $this->errors;

        if (is_array($errors) && ($offset || $length)) {
            return array_slice($errors, $offset, $length, true);
        } else {
            return $errors;
        }
    }

    /**
     * public function clean ([string $name])
     *
     * return none
     */
    public function clean ($name = '')
    {
        if ($name) {
            unset($this->errors[$name]);
        } else {
            $this->errors = array();
        }
    }

    /**
     * public function getList ([string $name], [int $offset], [int $length])
     *
     * return none
     */
    public function getList ($name = '', $offset = 0, $length = null)
    {
        $errors = $name ? $this->errors[$name] : $this->errors;
        $list = array();

        $this->_getList($errors, $list);

        if (is_array($list) && ($offset || $length)) {
            return array_slice($list, $offset, $length, true);
        } else {
            return $list;
        }
    }

    /**
     * private function _getList (mixed $errors, array &$list)
     *
     * return none
     */
    private function _getList ($errors, &$list)
    {
        if (is_array($errors)) {
            foreach ($errors as $error) {
                $this->_getList($error, $list);
            }
        } else if ($errors) {
            $list[] = $errors;
        }
    }
}
