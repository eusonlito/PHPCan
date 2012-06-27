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

class Password extends Formats implements Iformats
{
    private $password;

    public $format = 'password';

    public function check ($value)
    {
        $this->error = array();

        $this->password = $value[''];

        return $this->validate($value);
    }

    public function valueDB (\PHPCan\Data\Db $Db, $value, $language = '', $id = 0)
    {
        if (!$value['']) {
            return false;
        }

        return array('' => $this->encrypt($value['']));
    }

    public function fixValue ($value)
    {
        return array('' => '');
    }

    public function encrypt ($string)
    {
        if ($this->settings['']['encrypt']) {
            return hash($this->settings['']['encrypt'], $string);
        } else {
            return $string;
        }
    }

    public function valueForm ($value)
    {
        return array('' => '');
    }

    public function afterSave (\PHPCan\Data\Db $Db, $values)
    {
        if (!$values['new_value']['']) {
            return true;
        }

        $query = array(
            'table' => $this->table,
            'data' => array(
                $this->name => $this->encrypt($values['id'].$this->password)
            ),
            'conditions' => array(
                'id' => $values['id']
            ),
            'limit' => 1
        );

        $Db->query($Db->Database->update($query));

        $this->password = '';

        return true;
    }

    public function settings ($settings)
    {
        $this->bindEvent(array('afterInsert', 'afterUpdate'), array($this, 'afterSave'));

        $this->settings = $this->setSettings($settings, array(
            '' => array(
                'db_type' => 'varchar',

                'length_max' => 32,
                'length_min' => 5,
                'encrypt' => 'md5'
            )
        ));

        return $this->settings;
    }
}
