<?php
/*
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

namespace ANS\PHPCan\I18n;

defined('ANS') or die();

class Gettext_builder
{
    private $Debug;
    private $settings = array();

    /**
     * public function __construct ([string $autoglobal])
     *
     * return none
     */
    public function __construct ($autoglobal = '')
    {
        global $Debug;

        $this->Debug = $Debug;

        if ($autoglobal) {
            global $Config;

            $Config->config['autoglobal'][] = $autoglobal;
        }
    }

    public function setSettings ($settings) {
        $settings['exclude'] = (array)$settings['exclude'];

        if ($settings['exclude']) {
            foreach ($settings['exclude'] as &$exclude) {
                $exclude = preg_quote($exclude, '#');
            }

            unset($exclude);
        }

        $settings['input'] = (array)$settings['input'];

        if ($settings['input']) {
            foreach ($settings['input'] as &$input) {
                if (strstr($input, '|')) {
                    $input = filePath($input);
                }
            }

            unset($input);
        }

        $this->settings = $settings;
    }

    public function getEntries ($po_files)
    {
        if (empty($this->settings['input'])) {
            return false;
        }

        $return = array(
            'headers' => array(
                'Project-Id-Version' => '',
                'Report-Msgid-Bugs-To' => '',
                'POT-Creation-Date' => '',
                'PO-Revision-Date' => '',
                'Last-Translator' => '',
                'Language-Team' => '',
                'Content-Type' => '',
                'Content-Transfer-Encoding' => '',
                'Plural-Forms' => '',
            )
        );

        $return['entries'] = $this->scan($this->settings['input'], $this->settings['exclude']);

        if (empty($po_files)) {
            return $return;
        }

        foreach ($po_files as $po_file) {
            $po_file = filePath($po_file);

            if (!is_file($po_file)) {
                continue;
            }

            $po_entries = $this->parsePo($po_file);

            if (empty($po_entries)) {
                continue;
            }

            foreach ($return['entries'] as $key => &$entry) {
                if (empty($po_entries['entries'][$key])) {
                    continue;
                }

                foreach ($po_entries['entries'][$key] as $po_key => $po_entry) {
                    if (empty($entry[$po_key])) {
                        $entry[$po_key] = $po_entry;
                    }
                }

                $return['headers'] = $po_entries['headers'];
            }
        }
        
        return $return;
    }

    private function scan ($folders, $excludes = array())
    {
        if (!is_array($folders)) {
            $folders = array($folders);
        }

        $File = new \ANS\PHPCan\Files\File;

        $finfo = finfo_open(FILEINFO_MIME);
        $entries = array();

        foreach ($folders as $content) {
            if ($excludes) {
                foreach ($excludes as $exclude) {
                    if (preg_match('#'.$exclude.'#', $content)) {
                        continue 2;
                    }
                }
            }

            if (is_file($content) && (substr(finfo_file($finfo, $content), 0, 4) === 'text')) {
                $entries = arrayMergeReplaceRecursive($entries, $this->extractStrings($content));
            } else if (is_dir($content)) {
                $entries = arrayMergeReplaceRecursive($entries, $this->scan($File->listFolder($content, '*'), $excludes));
            }
        }

        return $entries;
    }

    public function extractStrings ($filename)
    {
        if (!is_file($filename)) {
            $this->Debug->error('gettext', __('The file %s does not exist.', $filename));

            return false;
        }

        $lines = file($filename, FILE_IGNORE_NEW_LINES);
        $entries = array();

        foreach ($lines as $num => $text) {
            preg_match_all('/__e?\(((?<!\\\)"(.*?)(?<!\\\)"|(?<!\\\)\'(.*?)(?<!\\\)\')/i', $text, $matches);

            if (empty($matches[1])) {
                continue;
            }

            foreach ($matches[3] as $entry) {
                $entry = str_replace('\\', '', $entry);
                $entries[$entry]['msgid'] = $entry;
                $entries[$entry]['msgstr'] = array();
                $entries[$entry]['references'][] = $filename.':'.($num + 1);
            }
        }

        return $entries;
    }

    private function dequote ($str)
    {
        return substr ($str, 1, -1);
    }

    private function nl2array ($str)
    {
        if (is_string($str)) {
            $str = explode("\n", $str);
        }

        return (array)$str;
    }

    public function parsePo ($filename)
    {
        if (!is_file($filename)) {
            $this->Debug->error('gettext', __('The file %s does not exist.', $filename));

            return false;
        }

        if (substr($filename, strrpos($filename, '.')) !== '.po') {
            $this->Debug->error('gettext', __('The file %s is not a PO file.', $filename));

            return false;
        }

        $lines = file($filename, FILE_IGNORE_NEW_LINES);

        $entries = array();
        $headers = array(
            'Project-Id-Version' => '',
            'Report-Msgid-Bugs-To' => '',
            'POT-Creation-Date' => '',
            'PO-Revision-Date' => '',
            'Last-Translator' => '',
            'Language-Team' => '',
            'Content-Type' => '',
            'Content-Transfer-Encoding' => '',
            'Plural-Forms' => '',
        );

        $i = 2;

        while ($line = $lines[$i++]) {
            $line = $this->dequote($line);
            list ($key, $data) = explode(':', $line, 2);

            if (empty($key) || !isset($headers[$key])) {
                continue;
            }

            $headers[$key] = trim($data);
        }

        //Entries
        $entry = array();

        for ($n = count($lines); $i <= $n; $i++) {
            $line = trim($lines[$i]);

            if ($line === '') {
                if ($entry['msgid']) {
                    if (empty($entry['msgstr'])) {
                        $entry['msgstr'] = array();
                    }

                    $entries[$entry['msgid']] = $entry;
                }

                $entry = array();
                continue;
            }

            list ($key, $data) = explode(' ', $line, 2);

            switch ($key) {
                case '#':
                    $entry['comments'][] = $data;
                    break;

                case '#:':
                    $entry['references'][] = $data;
                    break;

                case 'msgid':
                    $entry['msgid'] = str_replace('\\', '', $this->dequote($data));
                    break;

                case 'msgstr':
                    $entry['msgstr'][] = str_replace(array('\n', '\\'), array("\n", ''), $this->dequote($data));
                    break;
            }
        }

        return array('headers' => $headers, 'entries' => $entries);
    }

    public function generatePo ($array, $filename)
    {
        $lines = array('msgid ""', 'msgstr ""');

        //Headers
        foreach ($array['headers'] as $name => $value) {
            $lines[] = '"'.$name.': '.$value.'"';
        }

        $lines[] = '';

        //Entries
        foreach ($array['entries'] as $entry) {
            if ($entry['comments']) {
                foreach ((array)$entry['comments'] as $comment) {
                    $lines[] = '# '.$comment;
                }
            }

            if ($entry['references']) {
                foreach ((array)$entry['references'] as $reference) {
                    $lines[] = '#: '.$reference;
                }
            }

            $lines[] = 'msgid "'.str_replace('"', '\\"', $entry['msgid']).'"';
            $lines[] = 'msgstr "'.str_replace(array("\r", "\n", '"'), array('', '\n', '\\"'), $entry['msgstr']).'"';
            $lines[] = '';
        }

        //Save file
        $File = new \ANS\PHPCan\Files\File;

        return $File->saveText(implode("\n", $lines), filePath($filename));
    }

    public function generateMo ($array, $filename)
    {
        foreach ($array['entries'] as $key => $entry) {
            if ($entry['msgstr'] === '') {
                unset($array['entries'][$key]);
            }
        }

        ksort($array['entries'], SORT_STRING);

        $offsets = array();
        $ids = '';
        $strings = '';

        foreach ($array['entries'] as $entry) {
            $id = $entry['msgid'];

            if (isset ($entry['msgid_plural'])) {
                $id .= "\x00" . $entry['msgid_plural'];
            }

            if (array_key_exists('msgctxt', $entry)) {
                $id = $entry['msgctxt'] . "\x04" . $id;
            }

            //Plural msgstrs are NUL-separated
            $str = str_replace("\n", "\x00", $entry['msgstr']);

            $offsets[] = array(strlen($ids), strlen($id), strlen($strings), strlen($str));

            // plural msgids are not stored (?)
            $ids .= $id . "\x00";
            $strings .= $str . "\x00";
        }

        $key_start = 7 * 4 + count($array['entries']) * 4 * 4;
        $value_start = $key_start + strlen($ids);
        $key_offsets = array();
        $value_offsets = array();

        //Calculate
        foreach ($offsets as $v) {
            list ($o1, $l1, $o2, $l2) = $v;

            $key_offsets[] = $l1;
            $key_offsets[] = $o1 + $key_start;
            $value_offsets[] = $l2;
            $value_offsets[] = $o2 + $value_start;
        }

        $offsets = array_merge($key_offsets, $value_offsets);

        //Generate binary data
        $mo = pack('Iiiiiii', 0x950412de, 0, count($array['entries']), 7 * 4, 7 * 4 + count($array['entries']) * 8, 0, $key_start);

        foreach ($offsets as $offset) {
            $mo .= pack('i', $offset);
        }

        $mo .= $ids.$strings;

        //Save file
        $File = new \ANS\PHPCan\Files\File;

        return $File->saveText($mo, filePath($filename));
    }
}
