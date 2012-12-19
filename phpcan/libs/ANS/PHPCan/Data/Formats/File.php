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

class File extends Formats implements Iformats
{
    public $format = 'file';

    public function explodeData ($value, $subformat = '')
    {
        if (is_null($subformat)) {
            return parent::explodeData($value);
        } else {
            return parent::explodeData(array($subformat => $value));
        }
    }

    public function check ($value)
    {
        $this->error = array();

        if (!$this->checkFile($value[''])) {
            return false;
        }

        return true;
    }

    protected function checkFile ($value, $subformat = '')
    {
        $settings = $this->settings[$subformat];

        $File = new \ANS\PHPCan\Files\File;

        $path = $settings['base_path'].$settings['uploads'].$settings['subfolder'];

        if (!is_dir($path)) {
            if (!$File->makeFolder($path)) {
                $this->error[$subformat] = __('The folder "%s" to store the field "%s" haven\'t writing permissions', $path, __($this->name));

                return false;
            }
        } else if (!is_writable($path)) {
            $this->error[$subformat] = __('The folder "%s" to store the field "%s" haven\'t writing permissions', $path, __($this->name));

            return false;
        }

        if (!is_array($value)) {
            if (empty($settings['required']) && empty($value)) {
                return true;
            } else if ($settings['required'] && empty($value)) {
                $this->error[$subformat] = __('Field "%s" can not be empty', __($this->name));

                return false;
            } else if (!is_string($value)) {
                $this->error[$subformat] = __('Field "%s" is an invalid format', __($this->name));

                return false;
            } else {
                return true;
            }
        } else if ($settings['required'] && !is_string($value['name'])) {
            $this->error[$subformat] = __('Field "%s" can not be empty', __($this->name));

            return false;
        } else if (!is_string($value['name'])) {
            return true;
        }

        if ($settings['no_valid_extensions'] && in_array(strtolower(pathinfo($value['name'], PATHINFO_EXTENSION)), $settings['no_valid_extensions'])) {
            $this->error[$subformat] = __('Field "%s" can not allow this file type', __($this->name));

            return false;
        }

        if ($value['error']) {
            switch ($value['error']) {
                case 1:
                    $this->error[$subformat] = __('File "%s" exceeds the upload_max_filesize directive in php.ini.', __($this->name));

                    return false;
                case 2:
                    $this->error[$subformat] = __('File "%s" exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.', __($this->name));

                    return false;
                case 3:
                    $this->error[$subformat] = __('File "%s" was only partially uploaded.', __($this->name));

                    return false;
                case 6:
                    $this->error[$subformat] = __('Missing a temporary folder.');

                    return false;
                case 7:
                    $this->error[$subformat] = __('Failed to write file "%s" to disk.', __($this->name));

                    return false;
                case 8:
                    $this->error[$subformat] = __('File "%s" upload was stopped by extension.', __($this->name));

                    return false;
            }
        }

        if (empty($settings['required']) && ($value['size'] == 0)) {
            return true;
        }

        if ($settings['required'] && ($value['size'] == 0)) {
            $this->error[$subformat] = __('Field "%s" can not be empty', __($this->name));

            return false;
        }

        if ($settings['mime_types']) {
            if (!$File->getMimeType($value['tmp_name'], $settings['mime_types'])) {
                $this->error[$subformat] = __('Field "%s" is an invalid format', __($this->name));

                return false;
            }
        }

        return true;
    }

    public function valueDB (\ANS\PHPCan\Data\Db $Db, $value, $language = '', $id = 0)
    {
        $result = $this->saveFile($value[''], $id);

        if (is_array($result) || ($result === false)) {
            return $result;
        }

        $settings = $this->settings[''];

        //Transform image
        if (preg_match('/\.(jpg|png|gif|jpeg)$/i', $result)) {
            $Image = getImageObject();

            $Image->setSettings();

            $Image->load($settings['base_path'].$settings['uploads'].$settings['subfolder'].$result);

            if ($settings['images']['transform']) {
                $Image->transform($settings['images']['transform'], false);
            }

            $Image->save();
        }

        return array('' => $this->settings['']['subfolder'].$result);
    }

    protected function saveFile ($value, $id, $subformat = '')
    {
        $settings = $this->settings[$subformat];

        //If the file doesn't exits
        if (empty($value) || ($value == 1) || (is_array($value) && (($value['size'] == 0) || !is_file($value['tmp_name'])))) {
            if ($id) {
                if ($value == 1) {
                    return array($subformat => ($settings['default'] ?: ''));
                } else {
                    return false;
                }
            } else {
                return $settings['default'] ? array($subformat => $settings['default']) : false;
            }
        }

        if (is_string($value) && (!strstr($value, '://') && !is_file($value))) {
            return array($subformat => $value);
        }

        if (is_array($value)) {
            $new_name = alphaNumeric($value['name'], '-.');
        } else {
            $new_name = alphaNumeric(basename(preg_replace('#\?.*#', '', $value)), '-.');
        }

        $uniqid = uniqid().'-';

        if ($settings['length_max'] < strlen($settings['subfolder'].$uniqid.$new_name)) {
            $max_len = $settings['length_max'] - strlen($settings['subfolder']) - strlen($uniqid) - 5;
            $new_name = $uniqid.substr($new_name, -$max_len);
        } else {
            $new_name = $uniqid.$new_name;
        }

        $File = new \ANS\PHPCan\Files\File;

        if (!($saved_file_name = $File->save($value, $settings['base_path'].$settings['uploads'].$settings['subfolder'], $new_name))) {
            $this->error[$subformat] = __('Error storing the new file for field "%s"', __($this->name));

            return false;
        }

        return preg_replace('#^'.$settings['base_path'].$settings['uploads'].$settings['subfolder'].'#', '', $saved_file_name);
    }

    public function afterSave (\ANS\PHPCan\Data\Db $Db, $values)
    {
        return true;

        $settings = $this->settings[$subformat];
        $old = $values['old_value'][''];
        $new = $values['new_value'][''];

        if ($old && ($old != $settings['default']) && (($new == 1) || ($new != $old))) {
            $old_file = $this->settings['']['base_path'].$this->settings['']['uploads'].$old;

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

                'length_max' => 250,
                'max_size' => intval(ini_get('upload_max_filesize')),
                'no_valid_extensions' => array('php', 'php3'),
                'base_path' => SCENE_PATH,
                'uploads' => $Config->scene_paths['uploads'],
                'subfolder' => $this->table.'/'.$this->name.'/',

                'images' => array(),
                'documents' => array()
            )
        ));

        return $this->settings;
    }
}
