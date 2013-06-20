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

class Realfile extends File implements Iformats
{
    public $format = 'realfile';

    public function explodeData ($value, $subformat = '')
    {
        return parent::explodeData($value);
    }

    public function check ($value)
    {
        $this->error = array();

        if (!$this->checkFile($value[''], 'location')) {
            return false;
        }

        return true;
    }

    public function valueDB (\ANS\PHPCan\Data\Db $Db, $value, $language = '', $id = 0)
    {
        $value = $value[''];

        if (is_array($value) && !array_key_exists('tmp_name', $value)) {
            return $value;
        }

        $result = $this->saveFile($value, $id, 'location');

        if (is_array($result)) {
            return array(
                'location' => $result['location'],
                'name' => '',
                'type' => '',
                'size' => 0
            );
        } else if ($result === false) {
            return false;
        }

        $settings = $this->settings['location'];

        //Transform image
        if (preg_match('/\.(png|gif|jpe?g)$/i', $result)) {
            $Image = getImageObject();

            $Image->setSettings();

            $Image->load($settings['base_path'].$settings['uploads'].$settings['subfolder'].$result);

            if ($settings['images']['transform']) {
                $Image->transform($settings['images']['transform'], false);
            }

            $Image->save();
        }

        $finfo = array('name' => $result);

        $file = $settings['base_path'].$settings['uploads'].$settings['subfolder'].$result;

        return array(
            'name' => (is_string($value) ? basename($value) : $value['name']),
            'type' => strtolower(pathinfo($finfo['name'], PATHINFO_EXTENSION)),
            'location' => $settings['subfolder'].$result,
            'size' => round(filesize($file) / 1024)
        );
    }

    protected function saveFile ($value, $id, $subformat = '')
    {
        return parent::saveFile($value, $id, 'location');
    }

    public function afterSave (\ANS\PHPCan\Data\Db $Db, $values)
    {
        return parent::afterSave($Db, $values, 'location');
    }

    public function settings ($settings)
    {
        global $Config;

        $this->bindEvent(array('afterUpdate', 'afterDelete'), array($this, 'afterSave'));

        parent::settings($settings);

        $this->settings = $this->setSettings($settings, array(
            'name' => array_merge(array(
                'db_type' => 'varchar',
                'length_max' => 100
            ), $this->settings['']),
            'type' => array(
                'db_type' => 'varchar',
                'length_max' => 40
            ),
            'size' => array(
                'db_type' => 'integer',
                'value_min' => 0,
                'value_max' => 4294967295,
                'length_max' => 10,
                'unsigned' => true
            ),
            'location' => $this->settings['']
        ));


        return $this->settings;
    }
}
