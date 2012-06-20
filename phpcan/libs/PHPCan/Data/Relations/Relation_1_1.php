<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

namespace PHPCan\Data\Relations;

defined('ANS') or die();

class Relation_1_1 extends Relations implements Irelations
{
    public $unique = true;

    /**
     * public static function extend (array $settings)
     */
    public static function extend ($settings)
    {
        $return = array();

        $settings = self::basicSettings($settings, false);

        $settings['direction'] = array();
        $return['relations'][] = $settings;

        //Inverted relation
        if ($settings['tables'][0] != $settings['tables'][1]) {
            $settings2 = $settings;
            $settings2['join'] = array_reverse($settings['join']);
            $settings2['tables'] = array_reverse($settings['tables']);

            $return['relations'][] = $settings2;
        }

        //New fields
        $return['new_fields'] = array(
            $settings['tables'][0] => array(
                $settings['join'][1] => array('format' => 'id_relation')
            ),
            $settings['tables'][1] => array(
                $settings['join'][0] => array('format' => 'id_relation')
            )
        );

        return $return;
    }

    /**
     * public function removeDependent ()
     *
     * return boolean
     */
    public function removeDependent ()
    {
        if ($this->settings['dependent']) {
            return true;
        }

        return false;
    }

    /**
     * public function unrelateDependent ()
     *
     * return boolean
     */
    public function unrelateDependent ()
    {
        if ($this->settings['auto']) {
            return false;
        }

        return true;
    }

    /**
     * public function selectConditions (string $renamed_table0, string $renamed_table1, array $condition)
     *
     * return array
     */
    public function selectConditions ($renamed_table0, $renamed_table1, $condition)
    {
        $conditions = array();
        $relation_field = array();
        $relation_conditions = array();

        //Add the main condition
        if ($condition['field']) {
            $conditions[$condition['num'].$renamed_table1.'.'.$condition['field'].$condition['condition']] = $condition['value'];

            $relation_field[$this->getTable($this->settings['tables'][1], $renamed_table1)] = array('id[id_prev_table]');
        }

        //Add relation conditions
        $relation_conditions[] = '`'.$renamed_table0.'`.id = `'.$renamed_table1.'`.'.$this->settings['join'][0];
        $relation_conditions[] = '`'.$renamed_table1.'`.id = `'.$renamed_table0.'`.'.$this->settings['join'][1];

        return array(
            'relation_conditions' => $relation_conditions,
            'relation_field' => $relation_field,
            'relation_table' => $this->settings['relation_table'],
            'conditions' => $conditions
        );
    }

    /**
     * public function relate (array $operations_table0, array $operations_table1, [array $options])
     *
     * return boolean
     */
    public function relate ($operations_table0, $operations_table1, $options = array())
    {
        //Unrelate first
        $ids_table0 = $this->getIds($this->settings['tables'][0], $operations_table0);
        $ids_table1 = $this->getIds($this->settings['tables'][1], $operations_table1);

        $ok = $this->Db->update(array(
            'table' => $this->settings['tables'][0],
            'data' => array($this->settings['join'][1] => 0),
            'conditions' => array($this->settings['join'][1] => $ids_table1),
            'comment' => __('Unrelating before the table %s with %s', $this->settings['tables'][0], $this->settings['tables'][1])
        ));

        if ($ok === false) {
            return false;
        }

        $ok = $this->Db->update(array(
            'table' => $this->settings['tables'][1],
            'data' => array($this->settings['join'][0] => 0),
            'conditions' => array($this->settings['join'][0] => $ids_table0),
            'comment' => __('Unrelating before the table %s with %s', $this->settings['tables'][1], $this->settings['tables'][0])
        ));

        if ($ok === false) {
            return false;
        }

        //Relate
        $id_table0 = current($ids_table0);
        $id_table1 = current($ids_table1);

        $ok = $this->Db->update(array(
            'table' => $this->settings['tables'][0],
            'data' => array($this->settings['join'][1] => $id_table1),
            'conditions' => array('id' => $ids_table0),
            'limit' => 1,
            'comment' => __('Relating the table %s with %s', $this->settings['tables'][0], $this->settings['tables'][1])
        ));

        if ($ok === false) {
            return false;
        }

        $ok = $this->Db->update(array(
            'table' => $this->settings['tables'][1],
            'data' => array($this->settings['join'][0] => $id_table0),
            'conditions' => array('id' => $ids_table1),
            'limit' => 1,
            'comment' => __('Relating the table %s with %s', $this->settings['tables'][1], $this->settings['tables'][0])
        ));

        if ($ok === false) {
            return false;
        }

        return true;
    }

    /**
     * public function unrelate (array $operations_table0, array $operations_table1, [array $options])
     *
     * return boolean
     */
    public function unrelate ($operations_table0, $operations_table1, $options = array())
    {
        $ids_table0 = $this->getIds($this->settings['tables'][0], $operations_table0);
        $ids_table1 = $this->getIds($this->settings['tables'][1], $operations_table1);

        $ok = $this->Db->update(array(
            'table' => $this->settings['tables'][0],
            'data' => array($this->settings['join'][1] => 0),
            'conditions' => array(
                'id' => $ids_table0,
                $this->settings['join'][1] => $ids_table1
            ),
            'comment' => __('Unrelating the table %s with %s', $this->settings['tables'][0], $this->settings['tables'][1])
        ));

        if ($ok === false) {
            return false;
        }

        $ok = $this->Db->update(array(
            'table' => $this->settings['tables'][1],
            'data' => array($this->settings['join'][0] => 0),
            'conditions' => array(
                'id' => $ids_table1,
                $this->settings['join'][0] => $ids_table0
            ),
            'comment' => __('Unrelating the table %s with %s', $this->settings['tables'][1], $this->settings['tables'][0])
        ));

        if ($ok === false) {
            return false;
        }

        return true;
    }
}
