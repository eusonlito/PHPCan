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

class Image extends File implements Iformats
{
    public $format = 'image';

    public function valueDB (\PHPCan\Data\Db $Db, $value, $language = '', $id = 0)
    {
        $result = $this->saveFile($value[''], $id);

        if (is_array($result) || ($result === false)) {
            return $result;
        }

        $settings = $this->settings[''];
        $file = $settings['base_path'].$settings['uploads'].$settings['subfolder'].$result;

        $Image = getImageObject();

        $Image->setSettings();

        $Image->load($file);

        //Transform image
        if ($settings['transform'] && preg_match('/\.(jpg|png|gif|jpeg)$/i', $result)) {
            $Image->transform($settings['transform'], false);
        }

        $Image->save();

        return array('' => $settings['subfolder'].$result);
    }

    public function settings ($settings)
    {
        parent::settings($settings);

        $this->settings['']['default'] = $settings['default'];
        $this->settings['']['transform'] = $settings['transform'];

        $this->settings['']['mime_types'] = array(
            'image/png',
            'image/jpeg',
            'image/gif'
        );

        return $this->settings;
    }
}
