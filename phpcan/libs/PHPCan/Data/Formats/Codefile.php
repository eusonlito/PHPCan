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

class Codefile extends Formats implements Iformats
{
    public $format = 'codefile';
    public $code = '';

    public function check ($value)
    {
        $this->error = array();

        if (is_array($value[''])) {
            $filename = $value['']['filename'];
            $code = $value['']['code'];
        } else {
            $filename = null;
            $code = $value[''];
        }

        if ($filename && !$this->validate($filename)) {
            return false;
        }

        $settings = $this->settings[''];

        if ($settings['required'] && !$code) {
            $this->error[''] = __('Field "%s" can not be empty', __($this->name));

            return false;
        }

        if (!$code) {
            return true;
        }

        $File = new \PHPCan\Files\File;

        $path = $settings['base_path'].$settings['uploads'].$settings['subfolder'];

        if (!is_dir($path)) {
            if (!$File->makeFolder($path)) {
                $this->error[''] = __('The folder to store the file haven\'t writing permissions');

                return false;
            }
        } elseif (!is_writable($path)) {
            $this->error[''] = __('The folder to store the file haven\'t writing permissions');

            return false;
        }

        return true;
    }

    public function valueForm ($value)
    {
        $file = $this->settings['']['base_path'].$this->settings['']['uploads'].$value[''];

        return array(
            'filename' => preg_replace('|^'.preg_quote($this->settings['']['subfolder'], '|').'|', '', $value['']),
            'code' => is_file($file) ? file_get_contents($file) : ''
        );
    }

    public function valueDB (\PHPCan\Data\Db $Db, $value, $language = '', $id = 0)
    {
        $filepath = $this->settings['']['base_path'].$this->settings['']['uploads'];

        $code = $value['code'] ? $value['code'] : $value[''];
        $filename = $value['filename'];

        if ($id && !$filename) {
            $field = $this->getField('', $language);

            $filename = $Db->select(array(
                'table' => $this->table,
                'fields' => $field,
                'conditions' => array(
                    'id' => $id
                ),
                'limit' => 1,
                'comment' => __('Selecting the filename of %s', __($this->name))
            ));

            $filename = $filename[$field];
        }

        $filename = $this->settings['']['subfolder'].($filename ? preg_replace('|^'.preg_quote($this->settings['']['subfolder'], '|').'|', '', $filename) : uniqid().'.php');

        if (!$code) {
             if (is_file($filepath.$filename)) {
                unlink($filepath.$filename);
            }

            return array('' => '');
        }

        $File = new \PHPCan\Files\File();

        $File->saveText($code, $filepath.$filename);

        return array('' => $filename);
    }

    public function afterSave (\PHPCan\Data\Db $Db, $values)
    {
        if ($values['old_value'][''] && ($values['old_value'][''] != $values['new_value'][''])) {
            $old_file = $this->settings['']['base_path'].$this->settings['']['uploads'].$values['old_value'][''];

            if (is_file($old_file)) {
                unlink($old_file);
            }
        }

        return true;
    }

    public function settings ($settings)
    {
        global $Config;

        $this->bindEvent(array('afterUpdate', 'afterDelete'), array($this, 'afterSave'));

        $this->settings = $this->setSettings($settings, array(
            '' => array(
                'db_type' => 'varchar',

                'length_max' => 150,
                'base_path' => SCENE_PATH,
                'uploads' => $Config->scene_paths['uploads'],
                'subfolder' => $this->table.'/'.$this->name.'/'
            )
        ));

        $this->subformats[] = 'filename';
        $this->subformats[] = 'code';

        return $this->settings;
    }
}
