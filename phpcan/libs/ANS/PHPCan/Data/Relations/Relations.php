<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

namespace ANS\PHPCan\Data\relations;

defined('ANS') or die();

abstract class Relations
{
    protected $Debug;
    protected $Db;

    public $settings = array();

    /**
     * public function __construct (object $Db, [array $settings])
     */
    public function __construct (\ANS\PHPCan\Data\Db $Db, $settings = array())
    {
        global $Debug;

        $this->Debug = $Debug;
        $this->Db = $Db;

        if ($settings) {
            $this->settings = $settings;
        }
    }

    /**
     * protected function basicSettings (array $relation)
     *
     * return array
     */
    public static function basicSettings ($relation, $direction_in_joins = true)
    {
        if (!is_array($relation['tables'])) {
            $relation['tables'] = explode(' ', $relation['tables']);
        }

        if (empty($relation['direction']) && ($relation['tables'][0] == $relation['tables'][1])) {
            $relation['direction'] = array('parent', 'child');
        } else if ($relation['direction'] && !is_array($relation['direction'])) {
            $relation['direction'] = array_fill(0, 2, $relation['direction']);
        } else if (empty($relation['direction'])) {
            $relation['direction'] = array();
        }

        if ($relation['join_field']) {
            if (is_array($relation['join_field'])) {
                $relation['join'] = $relation['join_field'];
            } else {
                $relation['join'] = array_fill(0, 2, $relation['join_field']);
            }
        } else {
            $relation['join'] = array();

            foreach ($relation['tables'] as $k => $table) {
                $name_join_field = 'id_'.$table;

                if ($relation['name']) {
                    $name_join_field .= '_'.$relation['name'];
                }

                if ($direction_in_joins && $relation['direction'][$k]) {
                    $name_join_field .= '_'.$relation['direction'][$k];
                }

                $relation['join'][] = $name_join_field;
            }
        }

        return $relation;
    }

    /**
     * protected function getIds (string $table, array $operations)
     *
     * return array
     */
    protected function getIds ($table, $operations)
    {
        if (is_array($operations['conditions']) && count($operations['conditions']) == 1 && (array_key_exists('id', $operations['conditions']) || array_key_exists($table.'.id', $operations['conditions']))) {
            return (array)current($operations['conditions']);
        }

        $operations['table'] = $table;

        return (array)$this->Db->selectIds($operations);
    }

    /**
     * protected function getTable (string $realname, string $newname)
     *
     * return array
     */
    protected function getTable ($realname, $newname)
    {
        if (empty($newname) || ($realname === $newname)) {
            return $realname;
        }

        return $realname.'['.$newname.']';
    }
}
