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

class Image extends File implements Iformats
{
    public $format = 'image';

    public function valueDB (\ANS\PHPCan\Data\Db $Db, $value, $language = '', $id = 0)
    {
        $result = $this->saveFile($value[''], $id);

        if (is_array($result) || ($result === false)) {
            return $result;
        }

        $this->transformImage($result);

        return array('' => $this->settings['']['subfolder'].$result);
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

        $this->settings['']['images'] = $this->settings[''];

        return $this->settings;
    }
}
