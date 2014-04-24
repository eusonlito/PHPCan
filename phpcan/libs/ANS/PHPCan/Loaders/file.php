<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

defined('ANS') or die();

$Debug->setSettings('debug');

list($files, $params) = parseCacheLink(REQUEST_URI);

if (strstr($files[0], '?')) {
    list($file) = explode('?', $files[0], 2);
} else {
    $file = $files[0];
}

$cache = $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

if ($ext === 'css') {
    $Config->load('css.php');

    $Css = new \ANS\PHPCan\Files\Css\Css;

    $config = $params['config'];
    $config = $Config->css[$config] ?: current($Config->css);
} else if ($ext === 'js') {
    $Js = new \ANS\PHPCan\Files\Js\Js;
} else {
    $cache = 'images';
}

ob_start();

foreach ($files as $file) {
    if (($ext === 'css') && strstr($file, '://')) {
        echo "\n".'@import "'.$file.'";'."\n";
        continue;
    }

    if (strstr($file, '|')) {
        $file = fileWeb($file);
    } else {
        $file = explodeTrim('/', preg_replace('#^'.WWW.'#', '', $file));

        $context = array_shift($file);
        $basedir = array_shift($file);

        $file = fileWeb($context.'/'.$basedir.'|'.implode('/', $file));
    }

    if (strstr($file, '$') !== false) {
        $params['dynamic'] = true;
        $file = str_replace('$', '', $file);
    } else if (array_key_exists('dynamic', $params) !== true) {
        $params['dynamic'] = false;
    }

    if (strstr($file, '?')) {
        list($file, $query) = explode('?', $file, 2);

        parse_str($query, $query);

        $params = $params + $query;
    }

    $realfile = DOCUMENT_ROOT.$file;
    $exists = is_file($realfile);

    if ($exists !== true) {
        if (in_array($ext, array('jpg', 'jpeg', 'gif', 'png'), true)) {
            if ((defined('DEV') !== true) || (DEV !== true)) {
                header($_SERVER['SERVER_PROTOCOL'].' 404 Not Found');
                header('Status: 404 Not Found');

                die();
            }

            $realfile = filePath('common|default/images/'.rand(1, 5).'.jpg');
        } else if (count($files) === 1) {
            header($_SERVER['SERVER_PROTOCOL'].' 404 Not Found');
            header('Status: 404 Not Found');

            die();
        } else {
            continue;
        }
    }

    switch ($ext) {
        case 'jpg':
        case 'jpeg':
        case 'gif':
        case 'png':
            if ($params['options']) {
                $Image = getImageObject();

                $Image->setSettings();

                echo $Image->load($realfile)->transform($params['options'], false)->getContents();
            } else {
                echo file_get_contents($realfile);
            }

            break 2;

        case 'css':
            echo "\n";

            if ($params['dynamic']) {
                echo $Css->load($realfile)->transform($config['plugins'])->transform(array('BaseUrl' => dirname($file).'/'))->toString($config['stringfy']);
            } else {
                echo $Css->load($realfile)->transform(array('BaseUrl' => dirname($file).'/'))->toString($config['stringfy']);
            }
            break;

        case 'js':
            echo "\n";

            if ($params['dynamic']) {
                if (!defined('LANGUAGE')) {
                    $Config->load('languages.php');

                    $Vars->setLanguagesConfig();
                    $Vars->detectLanguage();

                    define('LANGUAGE', $Vars->getLanguage());
                }

                echo $Js->load($realfile)->process()->toString();
            } else {
                echo $Js->load($realfile)->toString();
            }

            break;

        default:
            header($_SERVER['SERVER_PROTOCOL'].' 404 Not Found');
            header('Status: 404 Not Found');

            die();
    }
}

$contents = ob_get_contents();

ob_end_clean();

$File = new \ANS\PHPCan\Files\File;

if ($ext === 'css') {
    header('Content-type: text/css');
} else if ($ext === 'js') {
    header('Content-type: application/javascript');
} else {
    header('Content-type: '.$File->getMimeType($file));
}

$cache = $Config->cache['types'][$cache];

if (empty($cache['expire'])) {
    die($contents);
}

header('Expires: '.gmdate('D, d M Y H:i:s', (time() + $cache['expire']).' GMT'));

$file = cacheFile();
$folder = dirname($file);

if ($File->makeFolder($folder) && is_writable($folder)) {
    file_put_contents($file, $contents);
}

die($contents);
