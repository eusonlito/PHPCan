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

class Url extends Formats implements Iformats
{
    public $format = 'url';

    public function check ($value)
    {
        $this->error = array();

        $value[''] = $this->fixUrl($value[''], $this->settings['']['expand_short_url']);

        if (!$this->checkUrl($value[''])) {
            return false;
        }

        if (!$this->validate($value)) {
            return false;
        }

        return true;
    }

    public function valueDB (\ANS\PHPCan\Data\Db $Db, $value, $language = '', $id = 0)
    {
        return $this->fixValue($value);
    }

    public function fixValue ($value)
    {
        return array('' => $this->fixUrl($value[''], $this->settings['']['expand_short_url']));
    }

    public function fixUrl ($url, $expand = false)
    {
        if (empty($url) || preg_match('#^(https?|ftp)://$#', $url)) {
            return '';
        }

        if (!preg_match('#^(https?|ftp)://#', $url)) {
            $url = 'http://'.$url;
        }

        if ($expand) {
            switch (parse_url($url, PHP_URL_HOST)) {
                case 'bit.ly':
                case 'cli.gs':
                case 'goo.gl':
                case 'j.mp':
                case 'shorturl.com':
                case 'tiny.cc':
                case 'tinyurl.com':
                    $ch = curl_init($url);

                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

                    if (curl_exec($ch)) {
                        $responseInfo = curl_getinfo($ch);
                        $url = '';

                        if ($responseInfo['http_code'] == 200) {
                            $url = $responseInfo['url'];
                        }
                    }

                    curl_close($ch);
            }
        }

        return $url;
    }

    public function checkUrl ($value, $subformat = '')
    {
        $settings = $this->settings[$subformat];

        if ($value && !is_string($value)) {
            $this->error[$subformat] = __('Field "%s" is not a valid url.', __($this->name));

            return false;
        }

        if (empty($value) && $settings['required']) {
            $this->error[$subformat] = __('Field "%s" can not be empty', __($this->name));

            return false;
        }

        if (empty($value)) {
            return true;
        }

        # Port is not checked correctly from filter_var > parse_url
        # http://www.mail-archive.com/php-bugs@lists.php.net/msg143797.html

        if (filter_var($value, FILTER_VALIDATE_URL) === false) {
            $this->error[$subformat] = __('Field "%s" is not a valid url.', __($this->name));

            return false;
        }

        if (!preg_match('/^[a-z0-9\.-]+\.[a-z]{2,4}$/', parse_url($value, PHP_URL_HOST))) {
            $this->error[$subformat] = __('Field "%s" is not a valid url.', __($this->name));

            return false;
        }

        return true;
    }

    public function settings ($settings)
    {
        $this->settings = $this->setSettings($settings, array(
            '' => array(
                'db_type' => 'varchar',

                'length_max' => 255,
                'expand_short_url' => false
            )
        ));

        return $this->settings;
    }
}
