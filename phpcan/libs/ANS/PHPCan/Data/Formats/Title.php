<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

namespace ANS\PHPCan\Data\Formats;

defined('ANS') or die();

class Title extends Formats implements Iformats
{
    const CHARS = 'abcdefghijklmnopqrstuvwxyz0123456789';

    public $format = 'title';

    public function check ($value)
    {
        $this->error = array();

        return $this->validate($value);
    }

    public function valueDB (\ANS\PHPCan\Data\Db $Db, $value, $language = '', $id = 0)
    {
        $value = $this->fixValue($value);

        if ($this->settings['url']['unique']) {
            $field = $this->getField('url', $language);
            $num = 0;

            $exists_query = array(
                'table' => $this->table,
                'conditions' => array(
                    $field => $value['url']
                ),
                'comment' => __('Checking for duplications in %s', $field)
            );

            if ($id) {
                $exists_query['conditions']['id !='] = $id;
            }

            while (!$value['url'] || $Db->selectCount($exists_query)) {
                if ($this->auto) {
                    $exists_query['conditions'][$field] = $value['url'] = $this->randomValue();
                } else {
                    $value['url'] = explode('-', $value['url']);
                    $num = $num ? intval(array_pop($value['url'])) : 0;
                    $exists_query['conditions'][$field] = $value['url'] = implode('-', $value['url']).'-'.(++$num);
                }
            }
        }

        $return = array(
            'title' => $value['title'],
            'url' => $value['url']
        );

        return $return;
    }

    public function fixValue ($value)
    {
        $value['title'] = strip_tags($value['title']);
        $value['url'] = alphaNumeric($value['title'], array('-', ' ' => '-'));

        if (empty($value['url'])) {
            $this->auto = true;
            $value['url'] = $this->randomValue();
        } else {
            $this->auto = false;
        }

        return $value;
    }

    public function randomValue ()
    {
        $settings = $this->settings['url'];

        if ($settings['min'] === $settings['max']) {
            $length = $settings['min'];
        } else {
            $length = rand($settings['min'], $settings['max']);
        }

        $strlen = strlen(self::CHARS);

        for ($string = '', $i = 0; $i < $length; ++$i) {
            $string .= substr(self::CHARS, rand(0, $strlen - 1), 1);
        }

        return $string;
    }

    public function settings ($settings)
    {
        $this->settings = $this->setSettings($settings, array(
            'title' => array(
                'db_type' => 'varchar',

                'length_max' => 255
            ),
            'url' => array(
                'db_type' => 'varchar',

                'unique' => $this->name,
                'length_max' => 255,

                'min' => 12,
                'max' => 12
            )
        ));

        unset($this->settings['url']['fulltext'], $this->settings['url']['db_fulltext'], $this->settings['url']['required']);

        return $this->settings;
    }
}
