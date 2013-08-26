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

class Embed extends Formats implements Iformats
{
    public $format = 'embed';

    public function check ($value)
    {
        $this->error = array();
        $settings = $this->settings;

        if (empty($settings['required']) && empty($value[''])) {
            return true;
        }

        if (substr($value[''], 0, 1) === '<') {
            return true;
        }

        $Url = new \ANS\PHPCan\Data\Formats\Url_media($this->table, $this->name, $this->languages);

        $value[''] = $Url->fixUrl($value[''], true);

        if (!$Url->checkUrl($value[''], 'url')) {
            return false;
        }

        if (!$this->validate($value)) {
            return false;
        }

        if (!$Url->getInfo($value[''])) {
            $this->error[''] = __('Service url for field "%s" is not available', __($this->name));

            return false;
        }

        return true;
    }

    public function valueDB (\ANS\PHPCan\Data\Db $Db, $value, $language = '', $id = 0)
    {
        if (substr($value[''], 0, 1) === '<') {
            return $value;
        }

        $Url = new \ANS\PHPCan\Data\Formats\Url_media($this->table, $this->name, $this->languages);

        $value_url = $Url->fixValue(array('url' => $value['']));

        if (!is_array($value_url) || empty($value_url['type'])) {
            return $value;
        }

        $Html = new \ANS\PHPCan\Templates\Html\Html;
        $Media = new \ANS\PHPCan\Templates\Html\Media($Html);

        $value_url['info'] = unserialize($value_url['info']);

        return array('' => $Media->media($value_url));
    }

    public function settings ($settings)
    {
        $this->settings = $this->setSettings($settings, array(
            '' => array(
                'db_type' => 'texto',
                'db_type' => 'text'
            )
        ));

        return $this->settings;
    }
}
