<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

namespace ANS\PHPCan\Files;

defined('ANS') or die();

class File
{
    public $folder;

    private $Debug;

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

    /**
     * public function setFolder (string $folder, [boolean $make_if_not_exists])
     *
     * return boolean
     */
    public function setFolder ($folder, $make_if_not_exists = false)
    {
        if (!$folder) {
            return false;
        }

        if (substr($folder, -1) !== '/') {
            $folder .= '/';
        }

        if ($make_if_not_exists) {
            $ok = $this->makeFolder($folder);
        } else {
            $ok = is_dir($folder);
        }

        if ($ok) {
            $this->folder = $folder;
        }

        return $ok;
    }

    /**
     * public function listFolder (string $path, [string $pattern], [int $depth], [int $flags])
     *
     * return array
     */
    public function listFolder ($path, $pattern = '*', $depth = 0, $flags = 0)
    {
        $matches = array();
        $folders = array(rtrim($path, '/'));

        while ($folder = array_shift($folders)) {
            $content = glob($folder.'/'.$pattern, $flags);

            if (!$content) {
                continue;
            }

            $matches = array_merge($matches, $content);

            if ($depth != 0) {
                $moreFolders = glob($folder.'/*', GLOB_ONLYDIR);
                $depth = ($depth < -1) ? -1: $depth + count($moreFolders) - 2;
                $folders = array_merge($folders, $moreFolders);
            }
        }

        return $matches;
    }

    /**
     * public function makeFolder (string $folder, [int $permissions])
     *
     * return boolean
     */
    public function makeFolder ($folder, $permissions = 0755)
    {
        if (is_dir($folder)) {
            return true;
        }

        if (is_file($folder)) {
            $this->Debug->error('file', __('The folder "%s" just exists as a file', $folder));

            return false;
        }

        $folder = preg_replace(array('#/?\.+/#', '#/+#'), '/', str_replace('\\', '/', $folder));
        $rel_folder = preg_replace('|^'.BASE_PATH.'|', '', $folder.'/');

        $dirs = explode('/', $rel_folder);
        $dir = BASE_PATH;

        foreach ($dirs as $part) {
            if (empty($part)) {
                continue;
            }

            $dir .= '/'.$part;

            if (!is_dir($dir)) {
                if (!@mkdir($dir, $permissions)) {
                    return false;
                }
            }
        }

        clearstatcache();

        if (is_dir($folder)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * public function delete (string $folder)
     *
     * return boolean
     */
    public function delete ($folder)
    {
        if (is_file($folder)) {
            return @unlink($folder);
        }

        if (is_dir($folder)) {
            $Iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($folder), \RecursiveIteratorIterator::CHILD_FIRST);

            foreach ($Iterator as $FileInfo) {
                $filename = $FileInfo->getFileName();

                if (($filename === '.') || ($filename === '..')) {
                    continue;
                }

                if ($FileInfo->isDir()) {
                    @rmdir($FileInfo->__toString());
                } else {
                    @unlink($FileInfo->__toString());
                }
            }

            return @rmdir($folder);
        }

        return false;
    }

    /**
     * public function save (string/array $file, [string $destination], [string $filename], [int $permissions])
     *
     * save a file from post or url
     *
     * return false/string
     */
    public function save ($file, $destination = '', $filename = '', $permissions = 0644)
    {
        if (isNumericalArray($file)) {
            $return = array();

            foreach ($file as $f) {
                if (!($return[] = $this->save($f, $destination))) {
                    return false;
                }
            }

            return $return;
        }

        if (!$file) {
            return false;
        }

        if (substr($destination, -1) !== '/') {
            $pathinfo = pathinfo($destination);

            if (!$filename) {
                $filename = $pathinfo['basename'];
            }

            $destination = $pathinfo['dirname'];
        }

        //Set folder
        if (!$this->setFolder($destination, true) && !$this->folder) {
            return false;
        }

        //Save post file
        if (is_array($file)) {
            if (!$file['size'] || !is_file($file['tmp_name'])) {
                return false;
            }

            if (!$filename) {
                $filename = $file['name'];
            }

            if (!rename($file['tmp_name'], $this->folder.$filename)) {
                return false;
            }

            chmod($this->folder.$filename, $permissions);

            return $this->fixExtension($this->folder.$filename);
        }

        //Save url file
        if (!$filename) {
            $filename = pathinfo($file, PATHINFO_BASENAME);
        }

        if (!copy($file, $this->folder.$filename)) {
            return false;
        }

        chmod($this->folder.$filename, $permissions);

        return $this->fixExtension($this->folder.$filename);
    }

    /**
     * public function fixExtension (string $filename)
     *
     * return text
     */
    public function fixExtension ($filename)
    {
        $pathinfo = pathinfo($filename);
        $ext = strtolower($pathinfo['extension']);

        if ($ext && ($ext === $pathinfo['extension'])) {
            return $filename;
        }

        if (!$ext) {
            $ext = $this->calculateExtension($filename);
            $name = $filename;
        } else {
            $name = $pathinfo['dirname'].'/'.$pathinfo['filename'];
        }

        if (!rename($filename, $name.'.'.$ext)) {
            $this->Debug->error('file', __('The file %s couldn\'t be renamed to %s', $filename, $name.'.'.$ext));

            return false;
        }

        return $name.'.'.$ext;
    }

    /**
     * public function saveText (string $text, string $destination, [string $filename])
     *
     * save text into a file
     *
     * return boolean
     */
    public function saveText ($text, $destination, $filename = '')
    {
        if (!$filename) {
            $filename = pathinfo($destination, PATHINFO_BASENAME);
            $destination = pathinfo($destination, PATHINFO_DIRNAME).'/';
        }

        if (!$this->setFolder($destination, true)) {
            return false;
        }

        return (file_put_contents($destination.$filename, $text) === false) ? false : $destination.$filename;
    }

    /**
     * public function calculateExtension (string $file)
     *
     * return string
     */
    public function calculateExtension ($file)
    {
        $mime = $this->getMimeType($file);
        $mime_ext = explode('/', $mime);
        $mime_ext = end($mime_ext);

        if (preg_match('/^[a-z0-9]{2,4}$/', $mime_ext) && ($mime_ext !== 'zip')) {
            return $mime_ext;
        }

        global $Config;

        $Config->load('mimes.php', 'phpcan');

        if ($mime_ext === 'zip') {
            $file_ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

            if (($key = array_search($file_ext, $Config->mimes)) !== false) {
                $ext = $Config->mimes[$key];
            } else {
                $ext = $mime_ext;
            }
        } else {
            $ext = $Config->mimes[$mime] ?: $mime_ext;
        }

        return $ext;
    }

    /**
     * public function getMimeType (string $file, [string $compare])
     *
     * return false/string
     */
    public function getMimeType ($file, $compare = '')
    {
        if (!$file || !is_file($file)) {
            return false;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file);
        finfo_close($finfo);

        // Sometimes some images returns wrong mimetype (like application/octet-stream)
        if (($mime_type === 'application/octet-stream') && ($image = image_type_to_mime_type(exif_imagetype($file)))) {
            $mime_type = $image;
        }

        if (is_array($compare)) {
            return in_array($mime_type, $compare);
        }

        if ($compare) {
            return ($compare === $mime_type) ? true : false;
        }

        return $mime_type;
    }

    /**
     * public function mergeFolder (string $source, [string $destination], [array $options], [array &$actions])
     *
     * return boolean
     */
    public function mergeFolders ($source, $destination = null, $options = array(), &$actions = array())
    {
        if (is_null($destination)) {
            $destination = $this->folder;
        }

        if (!is_array($actions)) {
            $actions = array();
        }

        $source = dirname($source . '/.').'/';
        $destination = dirname($destination . '/.').'/';

        $source_files = glob($source.'*', GLOB_MARK);
        $destination_files = glob($destination.'*', GLOB_MARK);

        $sub_options = $options;
        $sub_options['test_mode'] = true;

        foreach ($source_files as $file) {
            if (!$this->_checkFileAction($file, $destination, $destination_files, $options, $actions)) {
                continue;
            }

            if (substr($file, -1) === '/') {
                $name = explode('/', $file, -1);
                $this->mergeFolders($file, $destination.end($name), $sub_options, $actions);
            }
        }

        if ($options['delete']) {
            foreach ($destination_files as $file) {
                if (!$this->_checkFileAction($file, $source, $source_files, $options, $actions, true)) {
                    continue;
                }
            }
        }

        if (!$options['test_mode']) {
            foreach ($actions as $action) {
                switch ($action['action']) {
                    case 'make_folder':
                        $this->makeFolder($action['to']);
                        break;

                    case 'copy_file':
                    case 'replace':
                        $this->save($action['from'], $action['to']);
                        break;

                    case 'delete':
                        $this->delete($action['to']);
                        break;
                }
            }
        }
    }

    /**
     * private function _checkFileAction (string $file, string $destination, array $destination_files, array $options, array &$actions, [bool $inverted])
     *
     * return boolean
     */
    private function _checkFileAction ($file, $destination, $destination_files, $options, &$actions, $inverted = false)
    {
        if ($options['ignore']) {
            if (preg_match($options['ignore'], $file)) {
                $actions[] = array(
                    'action' => 'ignore',
                    'from' => $file
                );

                return false;
            }
        }

        if ($options['filter']) {
            if (!preg_match($options['filter'], $file)) {
                $actions[] = array(
                    'action' => 'no_filter',
                    'from' => $file
                );

                return false;
            }
        }

        if (substr($file, -1) === '/') {
            $name = explode('/', $file, -1);
            $destination = $destination.end($name).'/';
            $type = 'folder';
        } else {
            $destination = $destination.pathinfo($file, PATHINFO_BASENAME);
            $type = 'file';
        }

        if ($inverted && !in_array($destination, $destination_files)) {
            $actions[] = array(
                'action' => 'delete',
                'to' => $file
            );

            return false;
        }

        if (!in_array($destination, $destination_files)) {
            if (!$options['no_make']) {
                $actions[] = array(
                    'action' => ($type === 'file') ? 'copy_file' : 'make_folder',
                    'from' => $file,
                    'to' => $destination
                );
            }

            return true;
        }

        if ($options['replace'] && ($type === 'file')) {
            $actions[] = array(
                'action' => 'replace',
                'from' => $file,
                'to' => $destination
            );
        }

        return true;
    }
}
