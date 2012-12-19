<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

namespace ANS\PHPCan\Data\Relations;

defined('ANS') or die();

class Relation_x_x extends Relations implements Irelations
{
    public $unique = false;

    /**
     * public static function extend (array $settings)
     */
    public static function extend ($settings)
    {
        $return = array();

        $settings = self::basicSettings($settings);

        if (empty($settings['relation_table'])) {
            $settings['relation_table'] = $settings['tables'][0].'_'.$settings['tables'][1].($settings['name'] ? '_'.$settings['name'] : '');
            $auto = true;
        } else {
            $auto = false;
        }

        $return['relations'][] = $settings;

        //Inverted relation
        $settings2 = $settings;
        $settings2['join'] = array_reverse($settings['join']);
        $settings2['direction'] = array_reverse($settings['direction']);
        $settings2['tables'] = array_reverse($settings['tables']);

        $return['relations'][] = $settings2;

        //Relation without direction
        if ($settings['direction'] && ($settings['tables'][0] == $settings['tables'][1])) {
            $settings2 = $settings;
            $settings2['direction'] = array();
            $settings2['auto'] = $auto;

            $return['relations'][] = $settings2;
        }

        //Adding '1 x' relations with table 0 and direction 0
        $settings2 = $settings;
        $settings2['tables'] = array($settings['tables'][0], $settings['relation_table']);
        $settings2['mode'] = '1 x';
        $settings2['join'] = array($settings['join'][0], '');
        $settings2['direction'] = array_fill(0, 2, $settings['direction'][0]);
        $settings2['auto'] = $auto;

        $return['relations'][] = $settings2;

        if ($settings['direction'] && ($settings['tables'][0] == $settings['tables'][1])) {
            //Adding '1 x' relations with table 0 and direction 1
            $settings2['join'] = array($settings['join'][1], '');
            $settings2['direction'] = array_fill(0, 2, $settings['direction'][1]);

            $return['relations'][] = $settings2;

            //Adding '1 x' relations with table 0 and no direction
            $settings2['join'] = $settings['join'];
            $settings2['direction'] = array();

            $return['relations'][] = $settings2;
        }

        //Adding 'x 1' relations with table 0 and direction 0
        $settings2 = $settings;
        $settings2['tables'] = array($settings['relation_table'], $settings['tables'][0]);
        $settings2['mode'] = 'x 1';
        $settings2['join'] = array('', $settings['join'][0]);
        $settings2['direction'] = array_fill(0, 2, $settings['direction'][0]);
        $settings2['auto'] = $auto;

        $return['relations'][] = $settings2;

        //Adding 'x 1' relations with table 0 and direction 1
        if ($settings['direction'] && ($settings['tables'][0] == $settings['tables'][1])) {
            $settings2['join'] = array('', $settings['join'][1]);
            $settings2['direction'] = array_fill(0, 2, $settings['direction'][1]);

            $return['relations'][] = $settings2;

            $settings2['join'] = $settings['join'];
            $settings2['direction'] = array();

            $return['relations'][] = $settings2;
        }

        if ($settings['tables'][0] != $settings['tables'][1]) {
            //Adding '1 x' relations with table 1
            $settings2 = $settings;
            $settings2['tables'] = array($settings['tables'][1], $settings['relation_table']);
            $settings2['mode'] = '1 x';
            $settings2['join'] = array($settings['join'][1], '');
            $settings2['direction'] = array_fill(0, 2, $settings['direction'][1]);
            $settings2['auto'] = $auto;

            $return['relations'][] = $settings2;

            //Adding 'x 1' relations with table 1
            $settings2 = $settings;
            $settings2['tables'] = array($settings['relation_table'], $settings['tables'][1]);
            $settings2['mode'] = 'x 1';
            $settings2['join'] = array('', $settings['join'][1]);
            $settings2['direction'] = array_fill(0, 2, $settings['direction'][1]);
            $settings2['auto'] = $auto;

            $return['relations'][] = $settings2;
        }

        //New fields
        $return['new_fields'] = array(
            $settings['relation_table'] => array(
                $settings['join'][1] => array('format' => 'id_relation'),
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

        //Autorelated tables with direction
        if ($this->settings['direction'] && ($this->settings['tables'][0] == $this->settings['tables'][1])) {
            $relation_conditions[] = '`'.$renamed_table1.'`.id = `'.$this->settings['relation_table'].'`.'.$this->settings['join'][0];
            $relation_conditions[] = '`'.$this->settings['relation_table'].'`.'.$this->settings['join'][1].' = `'.$renamed_table0.'`.id';

        //Different joins with no direction
        } else if ($this->settings['auto'] && empty($this->settings['direction']) && ($this->settings['join'][0] != $this->settings['join'][1])) {
            $relation_conditions[] = '((`'.$renamed_table0.'`.id = `'.$this->settings['relation_table'].'`.'.$this->settings['join'][0]
                                    .' AND `'.$renamed_table1.'`.id = `'.$this->settings['relation_table'].'`.'.$this->settings['join'][1].')'
                                    .' OR (`'.$renamed_table0.'`.id = `'.$this->settings['relation_table'].'`.'.$this->settings['join'][1]
                                    .' AND `'.$renamed_table1.'`.id = `'.$this->settings['relation_table'].'`.'.$this->settings['join'][0].'))';

        //Add normal relation condition
        } else {
            $relation_conditions[] = '`'.$renamed_table1.'`.id = `'.$this->settings['relation_table'].'`.'.$this->settings['join'][1];
            $relation_conditions[] = '`'.$this->settings['relation_table'].'`.'.$this->settings['join'][0].' = `'.$renamed_table0.'`.id';
        }

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
        if (empty($operations_table0['conditions']) || empty($operations_table1['conditions'])) {
            $this->Debug->error('db', __('For security, conditions param is needed to relate function. If you want relate all rows, use conditions = "all"'));

            return false;
        }

        $ids_table0 = $this->getIds($this->settings['tables'][0], $operations_table0);
        $ids_table1 = $this->getIds($this->settings['tables'][1], $operations_table1);

        $relation_data = array();
        $extra_data = (array) $options['data'];

        foreach ((array) $ids_table0 as $id_table0) {
            if (empty($id_table0)) {
                continue;
            }

            foreach ((array) $ids_table1 as $id_table1) {
                if (empty($id_table1)) {
                    continue;
                }

                $related = $this->Db->selectCount(array(
                    'table' => $this->settings['relation_table'],
                    'conditions' => array(
                        $this->settings['join'][0] => $id_table0,
                        $this->settings['join'][1] => $id_table1
                    ),
                    'comment' => __('Checking if the tables %s and %s are related', $this->settings['tables'][0], $this->settings['tables'][1])
                ));

                if (empty($related)) {
                    $relation_data[] = array(
                        $this->settings['join'][0] => $id_table0,
                        $this->settings['join'][1] => $id_table1
                    ) + $extra_data;
                }
            }
        }

        //All registers are just related
        if (empty($relation_data)) {
            return true;
        }

        $ok = $this->Db->insert(array(
            'table' => $this->settings['relation_table'],
            'data' => $relation_data,
            'table_events' => false,
            'comment' => __('Relating the tables %s and %s', $this->settings['tables'][0], $this->settings['tables'][1])
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
        if (empty($operations_table0['conditions']) || empty($operations_table1['conditions'])) {
            $this->Debug->error('db', __('For security, conditions param is needed to unrelate function. If you want unrelate all rows, use conditions = "all"'));

            return false;
        }

        $operations = array(
            'table' => $this->settings['relation_table'],
        );

        if ($operations_table0['conditions'] !== 'all') {
            $operations['conditions'][$this->settings['join'][0]] = $this->getIds($this->settings['tables'][0], $operations_table0);
        }

        if ($operations_table1['conditions'] !== 'all') {
            $operations['conditions'][$this->settings['join'][1]] = $this->getIds($this->settings['tables'][1], $operations_table1);
        }

        if (empty($operations['conditions'])) {
            $operations['conditions'] = 'all';
        }

        $operations['table_events'] = false;
        $operations['comment'] = __('Unrelating the tables %s and %s', $this->settings['tables'][0], $this->settings['tables'][1]);

        if ($this->Db->delete($operations) === false) {
            return false;
        }

        //Unrelate autorelations with no direction
        if ($operations['conditions'] !== 'all' && ($this->settings['tables'][0] == $this->settings['tables'][1]) && empty($this->settings['direction'])) {
            $conditions = array();

            if ($operations['conditions'][$this->settings['join'][0]]) {
                $conditions[$this->settings['join'][1]] = $operations['conditions'][$this->settings['join'][0]];
            }

            if ($operations['conditions'][$this->settings['join'][1]]) {
                $conditions[$this->settings['join'][0]] = $operations['conditions'][$this->settings['join'][1]];
            }

            $operations['conditions'] = $conditions;
            $operations['comment'] = __('Unrelating the tables %s and %s in reverse mode', $this->settings['tables'][0], $this->settings['tables'][1]);

            if ($this->Db->delete($operations) === false) {
                return false;
            }
        }

        return true;
    }
}
