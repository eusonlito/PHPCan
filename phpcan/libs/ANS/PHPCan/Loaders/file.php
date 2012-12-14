<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

defined('ANS') or die();

//Set debug settings
$Debug->setSettings('debug');

if ($Vars->var['packed']) {
    $files = inflate64($Vars->var['packed']);

    if (!$files) {
        die('/* '.__('File %s doesn\'t exists', $files).' */');
    } elseif (is_array($files)) {
        $files = array_unique($files);
    }
} else {
    $files = $Vars->path;

    if (is_file(BASE_PATH.implode('/', $files))) {
        $files = array('/'.implode('/', $files));
    } else {
        $context = array_shift($files);
        $basedir = array_shift($files);

        if (strstr(getenv('REQUEST_URI'), '$') !== false) {
            $files[0] = '$'.$files[0];
        }

        $files = array($context.'/'.$basedir.'|'.implode('/', $files));
    }
}

$cache = false;
$ext = strtolower(pathinfo($files[0], PATHINFO_EXTENSION));

if (($ext === 'css') || ($ext === 'js')) {
    header('Content-type: '.($ext === 'css' ? 'text/css' : 'text/javascript'));

    $settings = $Config->cache['types'][$ext];

    if ($settings['expire'] && $settings['interface']) {
        $cache = $settings['expire'];
        $key = md5(serialize($files));

        header('Expires: '.gmdate('D, d M Y H:i:s', (time() + $cache).' GMT'));

        $Cache = new \ANS\Cache\Cache($settings);

        if ($Cache->exists($key)) {
            die($Cache->get($key));
        }

        ob_start();
    }
}

if ($ext === 'css') {
    $Config->load('css.php');
    $Css = new \ANS\PHPCan\Files\Css\Css;
}

foreach ($files as $files_value) {
    if (($ext === 'css') && strstr($files_value, '://')) {
        echo "\n".'@import "'.$files_value.'";'."\n";
        continue;
    }

    if (($files_value[0] === '/') && is_file(BASE_PATH.$files_value)) {
        $file = BASE_PATH.$files_value;
    } else {
        $file = filePath($files_value);
    }

    if ($dynamic = (strstr($files_value, '$') !== false)) {
        $files_value = str_replace('$', '', $files_value);
        $file = str_replace('$', '', $file);
    }

    if (strstr($file, '?')) {
        list($file, $query) = explode('?', $file, 2);

        parse_str($query, $query);

        $Vars->var = array_merge($Vars->var, $query);
    }

    if (!is_file($file)) {
        if (defined('DEV') && DEV && in_array($ext, array('jpg', 'jpeg', 'gif', 'png'))) {
            $file = filePath('common|default/images/'.rand(1,4).'.jpg');
        } else {
            echo "\n".'/* '.__('File %s doesn\'t exists', fileWeb($files_value)).' */';
            continue;
        }
    }

    switch ($ext) {
        case 'jpg':
        case 'jpeg':
        case 'gif':
        case 'png':
            $Image = getImageObject();

            $Image->setSettings();

            if (!$Image->showCached($file, $Vars->str('options'))) {
                $Image->load($file)->transform($Vars->str('options'))->show();
            }

            break;

        case 'css':
            $config = $Vars->var['config'];
            $config = $Config->css[$config] ?: current($Config->css);

            if (!$Css->showCached($file, false, false)) {
                echo "\n";

                if ($dynamic) {
                    echo $Css->load($file)->transform($config['plugins'])->transform(array('BaseUrl' => dirname(fileWeb($files_value)).'/'))->toString();
                } else {
                    echo $Css->load($file)->transform(array('BaseUrl' => dirname(fileWeb($files_value)).'/'))->toString();
                }
            }

            break;

        case 'js':
            echo "\n";

            if ($dynamic) {
                include ($file);
            } else {
                echo file_get_contents($file);
            }

            break;

        case 'less':
            echo "\n";

            $lc = new lessc($file);

            try {
                header('Content-type: text/css');
                echo $lc->parse();
            } catch (exception $e) {
                die($e->getMessage());
            }

            break;

        default:
            $Debug->fatalError(__('This file cannot be pre-processed'));
    }
}

if ($cache) {
    $contents = ob_get_contents();

    ob_end_clean();

    echo $Cache->set($key, $contents);
}
