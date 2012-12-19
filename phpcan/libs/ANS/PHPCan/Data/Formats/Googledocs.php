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

class GoogleDocs extends File implements iFormats
{
    public $format = 'googledocs';

    public function valueDB (\ANS\PHPCan\Data\Db $Db, $value, $language = '', $id = 0)
    {
        $value = $value[''];
        $settings = $this->settings[''];

        if (empty($value) || ($value == 1) || (is_array($value) && (($value['size'] == 0) || !is_file($value['tmp_name'])))) {
            if ($id) {
                if ($value == 1) {
                    return array('' => ($settings['default'] ?: ''));
                } else {
                    return false;
                }
            } else {
                return $settings['default'] ? array('' => $settings['default']) : false;
            }
        }

        $Files = new \ANS\PHPCan\Files\File;

        $mime = $Files->getMimeType($value['tmp_name']);
        $ext = strtolower(pathinfo($value['name'], PATHINFO_EXTENSION));

        global $Gdocs;

        if (!is_object($Gdocs)) {
            $Gdocs = new \ANS\PHPCan\Apis\Google\Docs();
        }

        if ($Gdocs->validMime($mime) || $Gdocs->validExtension($ext)) {
            $name = uniqid().'-'.alphaNumeric($value['name'], '-.');

            if ($link = $Gdocs->upload($value['tmp_name'], $name, $settings['collection'])) {
                return array('' => $link);
            } else if (empty($settings['local'])) {
                return false;
            }
        }

        $result = $this->saveFile($value, $id);

        if (is_array($result) || ($result === false)) {
            return $result;
        }

        return array('' => $settings['subfolder'].$result);
    }

    public function settings ($settings)
    {
        parent::settings($settings);

        $this->settings['']['collection'] = $settings['collection'] ?: 'root/contents';
        $this->settings['']['local'] = isset($settings['local']) ? $settings['local'] : true;
        $this->settings['']['default'] = $settings['default'];

        return $this->settings;
    }
}
