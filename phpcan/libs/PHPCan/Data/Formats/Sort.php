<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

namespace PHPCan\Data\Formats;

defined('ANS') or die();

class Sort extends Formats implements Iformats
{
    public $format = 'sort';

    public function check ($value)
    {
        $this->error = array();

        return $this->validate($value);
    }

    public function afterSave (\PHPCan\Data\Db $Db, $values)
    {
        $new_value = intval($values['new_value']['']);
        $old_value = intval($values['old_value']['']);

        if ($new_value == $old_value) {
            return true;
        }

        $field = $this->getField('', $values['language']);

        $exists = $Db->selectCount(array(
            'table' => $this->table,
            'conditions' => array(
                'id !=' => $values['id'],
                $field => $new_value
            )
        ));

        if (!$exists) {
            return true;
        }

        $query = array(
            'table' => $this->table,
            'data' => array(),

            'data' => array(
                ('`'.$this->name.'` = `'.$this->name.'` + 1')
            ),
            'conditions' => array(
                'id !=' => $values['id'],
            ),
            'comment' => __('Moving the other sort values to keep a free gap for the current sort')
        );

        if ($new_value < $old_value) {
            $query['data'][0] = "$field = $field + 1";
            $query['conditions']["$field >="] = $new_value;
        } else {
            $query['data'][0] = "$field = $field - 1";
            $query['conditions']["$field <="] = $new_value;
            $query['conditions']["$field >"] = $old_value;
        }

        $Db->query($Db->Database->update($query));

        return true;
    }

    public function settings ($settings)
    {
        $this->bindEvent(array('afterUpdate', 'afterInsert'), array($this, 'afterSave'));

        $this->settings = $this->setSettings($settings, array(
            '' => array(
                'db_type' => 'integer',

                'length_max' => 8,
                'unsigned' => true
            )
        ));

        return $this->settings;
    }
}
