<?php
/*
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

namespace ANS\PHPCan\Apis\Google;

defined('ANS') or die();

class Docs extends Services
{
    const service = 'writely';
    const host = 'https://docs.google.com/';

    private $collection;
    private $valid_extensions = array(
        'csv', 'tsv', 'tab', 'html', 'htm', 'doc', 'docx', 'ods', 'odt', 'rtf',
        'sxw', 'txt', 'xls', 'xlsx', 'pdf', 'ppt', 'ppt', 'pps', 'wmf', 'zip', 'rar'
    );
    private $valid_mimes = array(
        'text/csv',
        'text/tab-separated-values',
        'text/html',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/x-vnd.oasis.opendocument.spreadsheet',
        'application/vnd.oasis.opendocument.text',
        'application/rtf',
        'application/vnd.sun.xml.writer',
        'text/plain',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/pdf',
        'application/vnd.ms-powerpoint',
        'image/x-wmf',
        'application/zip',
        'application/x-rar-compressed'
    );

    public function __construct ($login = true)
    {
        parent::__construct();

        if (empty($login)) {
            return $this;
        }

        global $Config;

        $Config->load('apis.php', 'scene');

        $settings = $Config->apis['google']['docs'];

        if ($settings['user'] && $settings['password']) {
            $this->login($settings['user'], $settings['password'], self::service);
        } else {
            $this->Errors->set('api', __('User and password are not configured to login into Google Docs'), 'google-docs');
        }

        return $this;
    }

    public function validMime ($mime)
    {
        return in_array($mime, $this->valid_mimes);
    }

    public function validExtension ($extension)
    {
        return in_array($extension, $this->valid_extensions);
    }

    public function getCollections ()
    {
        if (empty($this->logged)) {
            return false;
        }

        $headers = array(
            'headers' => array(
                'Authorization' => $this->toAuthorizationHeader(),
                'GData-Version' => '3.0'
            )
        );

        $url = self::host.'feeds/default/private/full/-/folder?showfolders=true';
        $response = $this->fetch($url, $headers, false);

        if ($response['error']['code']) {
            $this->Errors->set('api', $response['error']['description'], 'google-docs');

            return false;
        }

        $Xml = new \SimpleXMLElement($response['body']);

        $url = self::host.'feeds/default/private/full/folder%3A';
        $collections['home'] = 'root/contents';

        if ($Xml && $Xml->entry) {
            foreach ($Xml->entry as $Collection) {
                $collections[(string) $Collection->title] = str_replace($url, '', $Collection->content->attributes()->src);
            }
        }

        return $collections;
    }

    public function setCollection ($collection, $check = false)
    {
        $this->collection = $collection;

        if ($check) {
            $collections = $this->getCollections();

            if (!in_array($collection, $collections)) {
                $this->collection = '';
            }
        }

        return $this->collection;
    }

    # Resumable Uploads (required): http://code.google.com/intl/gl/apis/gdata/docs/resumable_upload.html

    public function upload ($file, $name, $collection = '')
    {
        if (empty($this->logged)) {
            return false;
        }

        if (!is_file($file)) {
            $this->Errors->set('api', __('File "%s" is not available', basename($file)), 'google-docs');

            return false;
        }

        $collection = $collection ?: $this->collection;

        if (empty($collection)) {
            $this->Errors->set('api', __('You must choose a collection to store the file'), 'google-docs');

            return false;
        }

        $Files = new \ANS\PHPCan\Files\File;

        $size = filesize($file);
        $name = rawurlencode($name);
        $mime = $Files->getMimeType($file);

        /*
        POST /feeds/upload/create-session/default/private/full?convert=false HTTP/1.1
        Host: docs.google.com
        GData-Version: <version number>
        Authorization: <your authorization header here>
        Content-Length: 0
        Slug: MyTitle
        X-Upload-Content-Type: application/pdf
        X-Upload-Content-Length: 1234567

        <empty body>
        */

        $info = '
            <?xml version="1.0" encoding="UTF-8"?>
            <entry xmlns="http://www.w3.org/2005/Atom" xmlns:docs="http://schemas.google.com/docs/2007">
                <category scheme="http://schemas.google.com/g/2005#kind" term="http://schemas.google.com/docs/2007#document"/>
                <gAcl:scope type="default"/>
                <title>'.$name.'</title>
            </entry>
        ';

        $url = self::host.'/feeds/upload/create-session/default/private/full/folder%3A'.$collection.'?convert=false';
        $data = array(
            'headers' => array(
                'GData-Version' => '3.0',
                'Authorization' => $this->toAuthorizationheader(),
                'Content-Length' => strlen($info),
                'Slug' => $name,
                'X-Upload-Content-Type' => $mime,
                'X-Upload-Content-Length' => $size,
            ),
            'method' => 'POST',
            'post' => $info
        );

        $response = $this->fetch($url, $data);

        if (empty($response['successful'])) {
            $this->Errors->set('api', $response['body'], 'google-docs');

            return false;
        }

        /*
        PUT $response['headers']['location'] HTTP/1.1
        Host: docs.google.com
        Content-Length: 100000
        Content-Type: application/pdf
        Content-Range: bytes 0-99999/1234567

        <bytes 0-99999>
        */

        $url = $response['headers']['location'];
        $start = 0;

        do {
            $data = array(
                'headers' => array(
                    'Content-Length' => ($size - $start),
                    'Content-Type' => $mime,
                    'Content-Range:' => ('bytes '.$start.'-'.($size - 1).'/'.$size)
                ),
                'post' => file_get_contents($file, false, null, $start, ($size - $start)),
                'method' => 'PUT'
            );

            $response = $this->fetch($url, $data);

            if ($response['headers']['range']) {
                $start = explode('-', $response['range']);
                $start = intval(end($start)) + 1;

                if ($start === 1) {
                    break;
                }
            }
        } while ($response['status'] == 308);

        if (empty($response['successful'])) {
            $this->Errors->set('api', $response['body'], 'google-docs');

            return false;
        }

        $Xml = new \SimpleXMLElement($response['body']);

        return (string) $Xml->link->attributes()->href;
    }
}
