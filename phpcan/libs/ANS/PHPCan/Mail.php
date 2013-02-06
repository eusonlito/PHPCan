<?php
/*
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

namespace ANS\PHPCan;

defined('ANS') or die();

class Mail extends \PHPMailer
{
    public function __construct ()
    {
        parent::__construct();

        global $Config;

        if (empty($Config->mail)) {
            $Config->load('mail.php', 'scene');
        }

        $this->setParams($Config->mail);
    }

    public function setParams ($params)
    {
        if (empty($params)) {
            return false;
        }

        $this->CharSet = $params['charset'];
        $this->From = $params['from'];
        $this->FromName = $params['fromname'];
        $this->Host = $params['host'];
        $this->Port = $params['port'];
        $this->SMTPSecure = $params['secure'];
        $this->SMTPAuth = $params['auth'];
        $this->Username = $params['user'];
        $this->Password = $params['password'];
        $this->Timeout = $params['timeout'];
        $this->SMTPDebug = $params['debug'];

        if ($params['sendmail']) {
            $this->isSendMail();
        } else {
            $this->isSmtp();
        }

        $this->IsHTML(true);
    }

    private function getAddress ($address, $name = '')
    {
        $recipients = array();
        $address = is_string($address) ? array($address, $name) : $address;

        if (is_array($address[0])) {
            foreach ($address as $address_value) {
                if (is_array($address_value)) {
                    $recipients[] = array($address_value[0], $address_value[1]);
                } else {
                    $recipients[] = array($address_value[0], null);
                }
            }
        } else {
            $recipients[] = array($address[0], $address[1] ?: null);
        }

        return $recipients;
    }

    private function AddMethod ($address, $name, $method)
    {
        $Parent = new \ReflectionClass($this);

        if (empty($address) || ($Parent->hasMethod($method) !== true)) {
            return array();
        }

        $recipients = $this->getAddress($address, $name);

        foreach ($recipients as $recipients_value) {
            parent::$method($recipients_value[0], $recipients_value[1]);
        }

        return $recipients;
    }

    public function AddAddress ($address, $name = '')
    {
        return $this->AddMethod($address, $name, __FUNCTION__);
    }

    public function AddCC ($address, $name = '')
    {
        return $this->AddMethod($address, $name, __FUNCTION__);
    }

    public function AddBCC ($address, $name = '')
    {
        return $this->AddMethod($address, $name, __FUNCTION__);
    }

    public function AddReplyTo ($address, $name = '')
    {
        if (is_array($address)) {
            parent::AddReplyTo($address[0], $address[1]);
        } else {
            parent::AddReplyTo($address, $name ?: null);
        }
    }

    public function AddAttachment ($file, $name = '')
    {
        $files = array();

        if (is_string($file)) {
            $file = array(array(
                'file' => $file,
                'name' => $name
            ));
        } else if (!isset($file[0])) {
            $file = array($file);
        }

        foreach ($file as $file_value) {
            $file_location = $file_name = '';

            if (is_string($file_value)) {
                $file_location = $file_value;
                $file_name = basename($file_value);
            } else {
                if ($file_value['tmp_name'] && is_file($file_value['tmp_name'])) {
                    $file_location = $file_value['tmp_name'];
                    $file_name = $file_value['name'];
                } else if ($file_value['file'] && is_file($file_value['file'])) {
                    $file_location = $file_value['file'];
                    $file_name = $file_value['name'] ?: basename($file_value['file']);
                }
            }

            if ($file_location && $file_name) {
                $added = parent::AddAttachment($file_location, $file_name);

                if ($added) {
                    $files[] = array($file_location, $file_name);
                }
            }
        }

        return $files;
    }
}
