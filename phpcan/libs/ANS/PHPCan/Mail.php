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

use PHPMailer;

class Mail extends PHPMailer
{
    public $Log;

    public function __construct ()
    {
        global $Config, $Debug;

        $this->Debug = $Debug;

        parent::__construct();

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
        $this->Log = isset($params['log']) ? $params['log'] : '';

        switch ($params['mailer']) {
            case 'sendmail':
                $this->isSendMail();
                break;

            case 'mail':
                $this->isMail();
                break;

            default:
                $this->isSmtp();
        }

        $this->IsHTML(true);
    }

    private function getAddress ($address, $name = '')
    {
        $recipients = array();
        $address = is_string($address) ? array($address, $name) : $address;
        $total = count($address);

        for ($i = 0; $i < $total; $i++) {
            if (empty($address[$i])) {
                continue;
            }

            if (is_array($address[$i])) {
                $recipients[] = array($address[$i][0], $address[$i][1]);
            } elseif (empty($address[$i + 1]) || strpos($address[$i + 1], '@')) {
                $recipients[] = array($address[$i], null);
            } else {
                $recipients[] = array($address[$i], $address[++$i]);
            }
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

    public function getAllRecipients ()
    {
        return array_keys($this->all_recipients);
    }

    public function send()
    {
        $response = parent::send();

        if (empty($this->Log)) {
            return $response;
        }

        $log = array_filter(array(
            'ErrorInfo' => $this->ErrorInfo,
            'From' => $this->From,
            'FromName' => $this->FromName,
            'Subject' => $this->Subject,
            'to' => $this->to,
            'cc' => $this->cc,
            'bcc' => $this->bcc,
            'ReplyTo' => $this->ReplyTo,
            'all_recipients' => $this->all_recipients
        ));

        $this->Debug->store($log, $this->Log, true);

        return $response;
    }
}
