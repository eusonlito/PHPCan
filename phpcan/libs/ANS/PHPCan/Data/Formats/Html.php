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

class Html extends Formats implements Iformats
{
    public $format = 'html';

    public function check ($value)
    {
        $this->error = array();

        return $this->validate($value);
    }

    public function valueForm ($value)
    {
        return array(
            '' => str_replace("\n", '', $value[''])
        );
    }

    public function valueDB (\ANS\PHPCan\Data\Db $Db, $value, $language = '', $id = 0)
    {
        return $this->fixValue($value);
    }

    public function fixValue ($value)
    {
        $value = xssClean($value['']);

        if (empty($this->settings['']['clean'])) {
            $value = preg_replace('# style=["\'][^"\']+["\']#i', '', $value);
            $value = preg_replace('#</?font[^>]*>#', '', $value);
        } else {
            $valid = 'class|src|target|alt|title|href|rel';

            $value = preg_replace('# ('.$valid.')=#i', ' |$1|', $value);
            $value = preg_replace('# [a-z]+=["\'][^"\']*["\']#i', '', $value);
            $value = preg_replace('#\|('.$valid.')\|#i', ' $1=', $value);
            $value = preg_replace('#</?(font|span)[^>]*>#', '', $value);
        }

        $value = str_replace('&nbsp;', ' ', $value);

        return array('' => $value);
    }

    public function settings ($settings)
    {
        $this->settings = $this->setSettings($settings, array(
            '' => array(
                'db_type' => 'text',

                'length_max' => ''
            )
        ));

        return $this->settings;
    }
}
