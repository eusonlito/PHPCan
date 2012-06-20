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

class Id_text extends Formats implements Iformats
{
    public $format = 'id_text';
    public $auto = false;

    public function check ($value)
    {
        $this->error = array();

        return $this->validate($value);
    }

    public function valueDB (\PHPCan\Data\Db $Db, $value, $language = '', $id = 0)
    {
        $value = $this->fixValue($value);
        $value = $value[''];

        if ($this->settings['']['unique']) {
            $field = $this->getField('', $language);
            $num = 0;

            $exists_query = array(
                'table' => $this->table,
                'conditions' => array(
                    $field => $value
                ),
                'comment' => __('Checking for duplications in %s', $field)
            );

            if ($id) {
                $exists_query['conditions']['id !='] = $id;
            }

            while (!$value || $Db->selectCount($exists_query)) {
                if ($this->auto) {
                    $exists_query['conditions'][$field] = $value = $this->randomValue();
                } else {
                    $value = explode('-', $value);
                    $num = $num ? intval(array_pop($value)) : 0;
                    $exists_query['conditions'][$field] = $value = implode('-', $value).'-'.(++$num);
                }
            }
        }

        return array('' => $value);
    }

    public function fixValue ($value)
    {
        $value = alphaNumeric(strip_tags($value['']), array('-', ' ' => '-'));

        if (empty($value)) {
            $this->auto = true;
            $value = $this->randomValue();
        } else {
            $this->auto = false;
        }

        return array('' => $value);
    }

    public function randomValue ()
    {
        $length = $this->settings['']['length_auto'];

        return strtolower(substr(md5(uniqid()), rand(0, 32 - $length), $length));
    }

    public function settings ($settings)
    {
        $this->settings = $this->setSettings($settings, array(
            '' => array(
                'db_type' => 'char',

                'unique' => $this->name,
                'length_max' => 255,
                'length_auto' => 12
            )
        ));

        return $this->settings;
    }
}
