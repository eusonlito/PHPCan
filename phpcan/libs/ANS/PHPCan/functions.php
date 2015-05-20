<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

defined('ANS') or die();

/**
 * function cliParams (void)
 *
 * Generate an array from client execution params
 * 
 * return array
 */
function cliParams () {
    $params = getopt('d:r:g:p:f:s:', array(
        'ssl:',
        'domain:',
        'route:',
        'get:',
        'post:',
        'files:'
    ));

    if (empty($params)) {
        return array();
    }

    $return = array();

    foreach ($params as $key => $value) {
        switch ($key) {
            case 's':
            case 'ssl':
                $return['ssl'] = $value;
                continue 2;

            case 'r':
            case 'route':
                $return['route'] = $value;
                continue 2;

            case 'g':
            case 'get':
                parse_str($value, $_GET);
                continue 2;

            case 'p':
            case 'post':
                parse_str($value, $_POST);
                continue 2;

            case 'f':
            case 'files':
                parse_str($value, $_FILES);
                continue 2;
        }
    }

    return $return;
}

/*
 * function __ ($text, [$args = null], [$null = false], [$settings = array])
 *
 * return string
 */
function __ ($text, $args = null, $null = false, $settings = array())
{
    static $Gettext = null;

    if (!is_object($Gettext) || (is_array($settings) && isset($settings['language']))) {
        $Gettext = getGettextObject(isset($settings['language']) ? $settings['language'] : null);
        $text = is_object($Gettext) ? $Gettext->translate($text, $null) : $text;
    } else {
        $text = $Gettext->translate($text, $null);
    }

    if (is_null($args)) {
        return $text;
    } else if (is_array($args)) {
        return vsprintf($text, $args);
    } else {
        $args = func_get_args();

        array_shift($args);

        return vsprintf($text, $args);
    }
}

/*
 * function __e ($text)
 *
 * echo string
 */
function __e ($text, $args = null)
{
    if (count(func_get_args()) > 2) {
        $args = func_get_args();

        array_shift($args);
    }

    echo __($text, $args);
}

/*
 * function hasText (string $text)
 *
 * Check if a string has any text value (no spaces, line breaks or html tags)
 *
 * return boolean
 */
function hasText ($text)
{
    return strlen(trim(strip_tags($text, '<img><video><embed><object><iframe>'))) ? true : false;
}

/*
 * function isNumericalArray (array $array)
 *
 * Return true if the array is numerical or false if it's asociative
 *
 * return boolean
 */
function isNumericalArray ($array)
{
    if (is_array($array)) {
        return preg_match('/^[0-9]+$/', implode('', array_keys($array)));
    } else {
        return false;
    }
}

/*
 * function isMultidimensionalArray (array $array)
 *
 * Return true if all values of the array are subarrays
 *
 * return boolean
 */
function isMultidimensionalArray ($array)
{
    if (!is_array($array)) {
        return false;
    }

    foreach ($array as $value) {
        if (!is_array($value)) {
            return false;
        }
    }

    return true;
}

/*
 * function absolutePath ([string/bool arg1], [string/bool arg2], [...])
 *
 * Return string
 */
function absolutePath ()
{
    return path(array('args' => func_get_args(), 'host' => true));
}

/*
 * function path ([string/bool arg1], [string/bool arg2], [...])
 *
 * Return string
 */
function path ()
{
    global $Vars, $Config, $Debug;

    $args = func_get_args();
    $options = array();

    if (is_array($args[0])) {
        if (isset($args[0]['args'])) {
            $options = $args[0];

            unset($options['args']);

            $args = $args[0]['args'];
        }

        if ($args && is_array($args[0])) {
            $options = array_merge($options, array_shift($args));
        }

        if (isset($options['args'])) {
            $args = is_array($options['args']) ? $options['args'] : array($options['args']);
        }
    }

    if (isset($options['scene']) || isset($options['module'])) {
        $scene = $Vars->getScene();

        if (!isset($options['scene']) || empty($Config->scenes[$options['scene']])) {
            $options['scene'] = $scene;
        }

        if (($Config->scenes[$options['scene']]['detect'] === 'subdomain')) {
            if ($options['scene'] !== $scene) {
                $subdomain = $Config->scenes[$options['scene']]['subdomain'] ?: $options['scene'];

                if (substr_count(SERVER_NAME, '.') === 1) {
                    $domain = $subdomain.'.'.SERVER_NAME;
                } else {
                    $domain = preg_replace('/^[^\.]*/', $subdomain, SERVER_NAME);
                }

                $path = scheme().$domain.port().'/';
            } else {
                $path = BASE_WWW;
            }
        } else {
            $path = BASE_WWW.$options['scene'].'/';
        }

        if (isset($options['module'])) {
            if ($options['module'] && $Config->scenes[$options['scene']]['modules'][$options['module']]) {
                $path .= (($Config->scenes[$options['scene']]['modules'][$options['module']]) ? MODULE_WWW_SUBFOLDER.'/'.$options['module'].'/' : '');
            }
        } else if (MODULE_NAME) {
            $path .= MODULE_WWW_SUBFOLDER.'/'.MODULE_NAME.'/';
        }
    } else {
        $path = MODULE_NAME ? MODULE_WWW : SCENE_WWW;
    }

    if (($Config->languages['detect'] === 'subfolder') && is_array($Config->languages['availables'])) {
        $languages = array_keys($Config->languages['availables'], true, true);

        if (count($languages) > 1) {
            if (!isset($options['language'])) {
                $path .= $Vars->getLanguage().'/';
            } else if (isset($options['language']) && in_array($options['language'], $languages)) {
                $path .= $options['language'].'/';
            }
        }
    }

    if (isset($options['exit_mode'])) {
        if ($options['exit_mode'] && $Config->exit_modes[$options['exit_mode']]) {
            $path .= $options['exit_mode'].'/';
        }
    } else if ($Config->exit_modes[$Vars->getExitMode()]['lock']) {
        $path .= $Vars->getExitMode().'/';
    }

    if ($args) {
        $n = 0;

        while ($args) {
            $arg = array_shift($args);

            if (is_array($arg)) {
                continue;
            }

            if (($arg === true) && $Vars->path[$n]) {
                $path .= $Vars->path[$n].'/';
            } else if (strlen($arg) && ($arg !== false)) {
                $path .= $arg.'/';
            }

            $n++;
        }
    } else {
        if ($Vars->path) {
            if (($Vars->path[0] !== 'index') || $Vars->path[1]) {
                $path .= implode('/', $Vars->getPath()).'/';
            }
        }
    }

    if ($options['host'] && empty($options['scene'])) {
        $path = host().$path;
    }

    return $path;
}

/**
 * function referer (string $default, [boolean $redirect], [string $disabled])
 *
 * Return the string or redirect to previous page
 *
 * return string
 */
function referer ($default, $redirect = true, $disabled = '')
{
    if (!is_array($default)) {
        $default = array($default);
    }

    if (empty($disabled)) {
        $disabled = path('users', 'login');
    }

    $referer = parse_url(getenv('HTTP_REFERER'));
    $referer_str = $referer['path'].($referer['query'] ? ('?'.$referer['query']) : '');
    $request = getenv('REQUEST_URI');
    $url = '';

    if (empty($referer['host']) || empty($referer['path']) || ($referer['host'] !== SERVER_NAME)) {
        foreach ($default as $default_value) {
            if ($default_value !== $request) {
                $url = $default_value;
                break;
            }
        }
    } else if (($referer_str !== $request) && (empty($disabled) || !strstr($referer['path'], $disabled))) {
        $url = $referer['path'].($referer['query'] ? ('?'.$referer['query']) : '');
    } else {
        foreach ($default as $default_value) {
            if ($default_value !== $request) {
                $url = $default_value;
                break;
            }
        }
    }

    $url = $url ?: path('');

    if ($redirect) {
        redirect($url);
    } else if ($url) {
        return $url;
    }
}

/**
 * function filePath (string $path)
 *
 * return the correct path of the file
 *
 * return string
 */
function filePath ($path)
{
    if ($path[0] === '/' || ((OS === 'WIN') && (strpos($path, ':/') !== false))) {
        return $path;
    }

    global $Config, $Vars;

    preg_match('#(([\w-]+)/)?([\w-]+)(\|(.*))?#', $path, $matches);

    $context = $matches[2] ? $matches[2] : (MODULE_NAME ? 'module' : 'scene');
    $basedir = $matches[3];
    $path = $matches[5];

    if ($context === 'module') {
        $location = MODULE_PATH.$Config->module_paths[$basedir];
    } else if ($context === 'phpcan') {
        $location = BASE_PATH.$Config->phpcan_paths[$basedir];
    } else {
        $location = SCENE_PATH.$Config->scene_paths[$basedir];
    }

    return fixPath($location.$path);
}

/*
 * function fileWeb (string $path, [boolean $dynamic], [boolean $full])
 *
 * return the correct path of the file
 *
 * return string
 */
function fileWeb ($path, $dynamic = false, $host = false)
{
    if (($path[0] === '/') || parse_url($path, PHP_URL_SCHEME)) {
        return $path;
    }

    global $Config, $Vars;

    if ($host === true) {
        $host = host();
    }

    preg_match('#(([\w-]+)/)?([\w-]+)(\|(.*))?#', $path, $matches);

    $context = $matches[2] ? $matches[2] : (MODULE_NAME ? 'module' : 'scene');
    $basedir = $matches[3];
    $path = $matches[5];

    if ($dynamic) {
        if (MODULE_NAME) {
            $location = MODULE_WWW.$context.'/'.$basedir.'/';
        } else {
            $location = SCENE_WWW.$context.'/'.$basedir.'/';
        }
    } else if ($context === 'module') {
        $location = MODULE_REAL_WWW.$Config->module_paths[$basedir];
    } else if ($context === 'phpcan') {
        $location = BASE_WWW.$Config->phpcan_paths[$basedir];
    } else {
        $location = SCENE_REAL_WWW.$Config->scene_paths[$basedir];
    }

    return $host.fixPath($location.$path);
}

function webLinkFromCache ($url)
{
    global $Config;

    return WWW.preg_replace('#^'.WWW.$Config->scene_paths['cache'].'#', '', $url);
}

function createCacheLink ($file, $params = array())
{
    global $Config;

    $query = '';

    if ($params) {
        $query = '/_'.wordwrap(deflate64($params), 50, '_/_', true).'_/';
    }

    if (parse_url($file, PHP_URL_SCHEME)) {
        $file = preg_replace('#(^.*://[^/]+'.BASE_WWW.'[^/]+/)#', '$1'.$Config->scene_paths['cache'].'scene/', $file);
        return dirname($file).$query.basename($file);
    }

    if (MODULE_NAME) {
        $base = WWW;
        $file = preg_replace('#^'.MODULE_WWW.'#', '', $file);
    } else {
        $base = SCENE_REAL_WWW;
        $file = preg_replace('#^'.BASE_WWW.'#', '', $file);
    }

    return $base.$Config->scene_paths['cache'].dirname($file).$query.basename($file);
}

function parseCacheLink ($url)
{
    global $Config;

    if (MODULE_NAME) {
        $base = WWW;
    } else {
        if (preg_match('#'.SCENE_NAME.'/?#', WWW)) {
            $base = WWW;
        } else {
            $base = WWW.SCENE_NAME.'/';
        }
    }

    $url = preg_replace('#^'.$base.$Config->scene_paths['cache'].'#', '', $url);

    preg_match_all('#/_(.*?)_(?=/)#', $url, $params);

    $params = array_filter($params[1]);

    if ($params) {
        $params = inflate64(implode($params));
    }

    if ($params && $params['files']) {
        return array($params['files'], $params);
    } else {
        return array(array(WWW.preg_replace('#/_(.*?)_(?=/)#', '', $url)), $params);
    }
}

function cacheFile ()
{
    global $Config;

    $folder = DOCUMENT_ROOT.dirname(REQUEST_URI).'/';

    if (MODULE_NAME) {
        $folder = preg_replace(
            '#^'.MODULE_WWW.SCENE_NAME.'/'.$Config->scene_paths['cache'].'module#',
            SCENE_WWW.SCENE_NAME.'/'.$Config->scene_paths['cache'].MODULE_NAME,
            $folder
        );
    }

    return $folder.basename(REQUEST_URI);
}

/*
 * function fixPath (string $path)
 *
 * resolve '//' or '/./' or '/foo/../' in a path
 *
 * Return string
 */
function fixPath ($path)
{
    $replace = array('#(/\.?/)#', '#/(?!\.\.)[^/]+/\.\./#');

    do {
        $path = preg_replace($replace, '/', $path, -1, $n);
    } while ($n > 0);

    return $path;
}

/*
 * function get ([string $name], [string $value], [bool $add_all_get_variables])
 * function get ([array $values], [bool $add_all_get_variables])
 *
 * Return string
 */
function get ($name = null, $value = true, $add_all_get_variables = true)
{
    global $Vars;

    $get = http_build_query($Vars->getGetVars($name, $value, $add_all_get_variables));

    return $get ? '?'.$get : '';
}

/**
 * function redirect ([string $url])
 *
 * URI redirect
 */
function redirect ($url = null)
{
    global $Vars, $Debug;

    if (is_null($url)) {
        $url = path().get();
    }

    if ($Vars->message['outbox']) {
        $Vars->setCookie('phpcan_message', $Vars->message['outbox'], 10);
        $Vars->setCookie('phpcan_message_type', $Vars->message['type'], 10);
    }

    if ($Data && $Data->actions) {
        $Vars->setCookie('phpcan_executed_actions', $Data->actions, 10);
    }

    if (headers_sent($file, $line)) {
        if (empty($Debug->settings['redirect'])) {
            $Debug->e('misc', 'Cannot redirect to "'.$url.'" because headers have been sent in "'.$file.'" (line '.$line.')');
        } else {
            $Debug->error('misc', 'Cannot redirect to "'.$url.'" because headers have been sent in "'.$file.'" (line '.$line.')');
        }
    } else if (empty($Debug->settings['redirect'])) {
        $Debug->e($url, __('Redirect'));
    } else {
        header('Cache-Control: no-cache');
        header('Location: '.$url);
    }

    exit;
}

/**
 * function includeFile (string $_file, [array $_data_content], [boolean $_once])
 *
 * return boolean
 */
function includeFile ($_file, $_data_content = array(), $_once = false)
{
    if (empty($_file) || !is_file($_file)) {
        if (defined('DEV') && (DEV === true)) {
            die(__('File %s does not exists', $_file));
        }

        return;
    }

    global $Config;

    foreach ((array)$Config->config['autoglobal'] as $_each) {
        global $$_each;
    }

    if ($_data_content) {
        extract($_data_content, EXTR_SKIP);
    }

    if ($_once) {
        return include_once ($_file);
    } else {
        return include ($_file);
    }
}

/**
 * function getDatabaseConnection ([string $connection])
 *
 * return false/object
 */
function getDatabaseConnection ($connection = null)
{
    global $Config;

    if (empty($Config->db)) {
        return false;
    }

    if (is_null($connection)) {
        foreach ($Config->db as $connection => $settings) {
            if ($settings['default']) {
                return $connection;
            }
        }
    }

    return $Config->db[$connection] ? $connection : false;
}

/**
 * function getImageObject ()
 *
 * return false/object
 */
function getImageObject ()
{
    return new \ANS\PHPCan\Files\Images\Image;
}

/**
 * function getDatetimeObject ([string $time], [string $timezone])
 *
 * return false/object
 */
function getDatetimeObject ($time = null, $timezone = null)
{
    return new \ANS\PHPCan\I18n\Datetime($time, $timezone);
}

/**
 * function getGettextObject ([string $language], [array $folders])
 *
 * return false/object
 */
function getGettextObject ($language = '', $folders = array())
{
    if (empty($language)) {
        global $Vars;

        if (!is_object($Vars) || !($language = $Vars->getLanguage())) {
            return false;
        }
    }

    if (empty($folders)) {
        $folders = array(
            filePath('phpcan/languages|'),
            filePath('languages|'),
        );
    }

    $Gettext = new \ANS\PHPCan\I18n\Gettext;

    foreach ((array)$folders as $folder) {
        $folder .= $language;

        if (!is_dir($folder)) {
            continue;
        }

        $language_files = glob($folder.'/*.mo');

        if (empty($language_files)) {
            continue;
        }

        foreach ($language_files as $each) {
            $Gettext->load($each);
        }
    }

    return $Gettext ? $Gettext : false;
}

/**
 * function alphaNumeric (string $text, [array/string $allow], [string $default])
 *
 * Return string
 */
function alphaNumeric ($text, $allow = '', $default = '-')
{
    $text = htmlentities(trim(strip_tags($text)), ENT_NOQUOTES, 'UTF-8');
    $text = preg_replace('/&(\w)\w+;/', '$1', $text);

    $replace = array();

    if ($allow) {
        $expr = '[^\w';

        if (is_string($allow)) {
            $expr .= preg_quote($allow, '/');
        } else if (is_array($allow)) {
            foreach ($allow as $from => $to) {
                if (is_string($from)) {
                    $replace[$from] = $to;
                }

                if ($to) {
                    $expr .= preg_quote($to, '/');
                }
            }
        }

        $expr .= ']';
    } else {
        $expr = '\W';
    }

    if ($replace) {
        $text = strtr($text, $replace);
    }

    $default_exp = preg_quote($default, '/');

    $text = preg_replace('/'.$expr.'/', $default, $text);
    $text = preg_replace('/'.$default_exp.'+/', $default, $text);

    return strtolower($text);
}

function randomString ($min, $max = 0, $letters = true, $numbers = true)
{
    $chars = ($letters ? 'abcdefghijklmnopqrstuvwxyz' : '').($numbers ? '0123456789' : '');
    $strlen = strlen($chars);
    $min = (integer)$min;
    $max = (integer)$max;
    $max = ($max < $min) ? $min : $max;
    $length = ($min === $max) ? $min : rand($min, $max);

    for ($string = '', $i = 0; $i < $length; ++$i) {
        $string .= substr($chars, rand(0, $strlen - 1), 1);
    }

    return $string;
}

/**
 * function arrayKeyValues (array $array, string $key, string $recursive)
 *
 * Return array
 */
function arrayKeyValues ($array, $key, $recursive = '')
{
    if (is_object($array)) {
        $array = (array)$array;
    } else if (!is_array($array)) {
        return array();
    }

    $return = array();

    if (isNumericalArray($array)) {
        foreach ($array as $value) {
            $return = array_merge($return, arrayKeyValues($value, $key, $recursive));
        }

        return $return;
    }

    if (array_key_exists($key, $array)) {
        $return[] = $array[$key];
    }

    if ($recursive && is_array($array[$recursive]) && $array[$recursive]) {
        $return = array_merge($return, arrayKeyValues($array[$recursive], $key, $recursive));
    }

    return $return;
}

/**
 * function simpleArrayColumn (array $array, string $key, [string $index = null])
 *
 * Return array
 */
function simpleArrayColumn ($array, $key, $index = null)
{
    if (is_object($array)) {
        $array = (array)$array;
    } else if (!is_array($array)) {
        return array();
    }

    $return = array();

    if ($index === null) {
        foreach ($array as $v) {
            $return[] = $v[$key];
        }
    } else {
        foreach ($array as $v) {
            $return[$v[$index]] = $v[$key];
        }
    }

    return $return;
}

function arrayChunkVertical ($array, $columns)
{
    return array_chunk($array, ceil(count($array) / $columns));
}

/**
 * function ip ()
 *
 * return string
 */
function ip ()
{
    $list = array($_SERVER['HTTP_CLIENT_IP'], $_SERVER['HTTP_X_FORWARDED_FOR'], $_SERVER['REMOTE_ADDR']);

    foreach ($list as $ips) {
        foreach (explode(',', $ips) as $ip) {
            if (trim($ip) && ($ip !== 'unknown')) {
                return $ip;
            }
        }
    }

    return $list[0] ?: $list[1] ?: $list[2];
}

/*
 * function arrayMergeReplaceRecursive (array $array1, array $array2, [array $array3], ...)^
 *
 * return array
 */
function arrayMergeReplaceRecursive ()
{
    $params = func_get_args();
    $return = (array)array_shift($params);

    foreach ($params as $array) {
        if (!is_array($array)) {
            continue;
        }

        foreach ($array as $key => $value) {
            if (is_numeric($key) && (!in_array($value, $return))) {
                if (is_array($value)) {
                    $return[] = arrayMergeReplaceRecursive($return[$$key], $value);
                } else {
                    $return[] = $value;
                }
            } else {
                if (isset($return[$key]) && is_array($value) && is_array($return[$key])) {
                    $return[$key] = arrayMergeReplaceRecursive($return[$key], $value);
                } else {
                    $return[$key] = $value;
                }
            }
        }
    }

    return $return;
}

/*
 * function arrayMergeReplaceRecursiveStrict (array $array1, array $array2, [array $array3], ...)^
 *
 * return array
 */
function arrayMergeReplaceRecursiveStrict ()
{
    $params = func_get_args();

    $return = array_shift($params);

    foreach ($params as $array) {
        if (!is_array($array)) {
            continue;
        }

        foreach ($array as $key => $value) {
            if (isset($return[$key]) && is_array($value) && is_array($return[$key])) {
                $return[$key] = arrayMergeReplaceRecursiveStrict($return[$key], $value);
            } else {
                $return[$key] = $value;
            }
        }
    }

    return $return;
}

/**
 * function urlInfo (string $url)
 *
 * return array
 */
function urlInfo ($url)
{
    $url = trim($url);

    if (empty($url)) {
        return array();
    }

    if (strpos($url, '://') === false) {
        $url = 'http://'.$url;
    }

    $info = parse_url($url);
    $info['url'] = $url;

    if ($info['query']) {
        parse_str($info['query'], $info['query']);
    } else {
        $info['query'] = array();
    }

    $info += pathinfo($info['path']);
    $info['path'] = explodeTrim('/', $info['path']);

    return $info;
}

/**
 * function encrypt (string $text)
 *
 * Text encryption
 *
 * return string
 */
function encrypt ($text)
{
    if (function_exists('mcrypt_encrypt')) {
        global $Config;

        return trim(str_replace('/', '|', base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $Config->key, $text, MCRYPT_MODE_ECB, mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND)))));
    } else {
        return trim(str_replace('/', '|', base64_encode($text)));
    }
}

/**
 * function decrypt (string $text)
 *
 * Text decryption
 *
 * return string
 */
function decrypt ($text)
{
    if (function_exists('mcrypt_encrypt')) {
        global $Config;

        return trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $Config->key, base64_decode(str_replace(array('|', ' '), array('/', '+'), $text)), MCRYPT_MODE_ECB, mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND)));
    } else {
        return trim(base64_decode(str_replace(array('|', ' '), array('/', '+'), $text)));
    }
}

/**
 * function textCutter (string $text, [int/string $limit], [string $end])
 *
 * Return string
 */
function textCutter ($text, $limit = 140, $end = '...')
{
    if (is_int($limit)) {
        if (strlen($text) <= $limit) {
            return $text;
        }
    } else {
        $limit = mb_strpos($text, $limit);

        if ($limit === false) {
            return $text;
        }
    }

    $length = strlen($text);
    $num = 0;
    $tag = 0;

    for ($n = 0; $n < $length; $n++) {
        if ($text[$n] === '<') {
            $tag++;
            continue;
        }

        if ($text[$n] === '>') {
            $tag--;
            continue;
        }

        if ($tag === 0) {
            $num++;

            if ($num >= $limit) {
                $text = substr($text, 0, $n);
                $space = strrpos($text, ' ');

                if ($space) {
                    $text = substr($text, 0, $space);
                }

                break;
            }
        }
    }

    if (strlen($text) === $length) {
        return $text;
    }

    $text .= $end;

    if (preg_match_all('|(<([\w]+)[^>]*>)|', $text, $aBuffer) && !empty($aBuffer[1])) {
        preg_match_all("|</([a-zA-Z]+)>|", $text, $aBuffer2);

        if (count($aBuffer[2]) !== count($aBuffer2[1])) {
            foreach ($aBuffer[2] as $k => $tag) {
                if ($tag !== $aBuffer2[1][$k]) {
                    $text .= '</'.$tag.'>';
                }
            }
        }
    }

    return $text;
}

/**
 * function xssClean (array/string $text)
 *
 * return text
 */
function xssClean ($text)
{
    if (is_array($text)) {
        return array_map('xssClean', $text);
    }

    // Fix entities;
    $text = str_replace(array('&amp;','&lt;','&gt;'), array('&amp;amp;','&amp;lt;','&amp;gt;'), $text);
    $text = preg_replace('/(&#*\w+)[\x00-\x20]+;/u', '$1;', $text);
    $text = preg_replace('/(&#x*[0-9A-F]+);*/iu', '$1;', $text);
    $text = html_entity_decode($text, ENT_COMPAT, 'UTF-8');

    // Remove any attribute starting with "on" or xmlns
    $text = preg_replace('#(<[^>]+?[\x00-\x20"\'])(?:on|xmlns)[^>]*+>#iu', '$1>', $text);

    // Remove javascript: and vbscript: protocols
    $text = preg_replace('#([a-z]*)[\x00-\x20]*=[\x00-\x20]*([`\'"]*)[\x00-\x20]*j[\x00-\x20]*a[\x00-\x20]*v[\x00-\x20]*a[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2nojavascript...', $text);
    $text = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*v[\x00-\x20]*b[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2novbscript...', $text);
    $text = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*-moz-binding[\x00-\x20]*:#u', '$1=$2nomozbinding...', $text);

    // Only works in IE: <span style="width: expression(alert('Ping!'));"></span>
    $text = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?expression[\x00-\x20]*\([^>]*+>#i', '$1>', $text);
    $text = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?behaviour[\x00-\x20]*\([^>]*+>#i', '$1>', $text);
    $text = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:*[^>]*+>#iu', '$1>', $text);

    // Remove namespaced elements (we do not need them)
    $text = preg_replace('#</*\w+:\w[^>]*+>#i', '', $text);

    // Strip multi-line comments
    $text = preg_replace('#<![\s\S]*?–[ \t\n\r]*>#', '', $text);

    do {
        // Remove really unwanted tags
        $old_text = $text;
        $text = preg_replace('#</*(?:applet|b(?:ase|gsound|link)|frame(?:set)?|(frame|layer)|l(?:ayer|ink)|meta|s(?:cript|tyle)|title|xml)[^>]*+>#i', '', $text);
    } while ($old_text !== $text);

    return $text;
}

function isXML ($xml)
{
    libxml_use_internal_errors(true);

    $doc = new \DOMDocument('1.0', 'utf-8');

    $doc->loadXML($xml);

    return libxml_get_errors() ? false : true;
}

/**
 * function explodeTrim (string $delimiter, string $text, [int $limit], [boolean $empty])
 *
 * Return string
 */
function explodeTrim ($delimiter, $text, $limit = null, $empty = false)
{
    $return = array();

    $explode = is_null($limit) ? explode($delimiter, $text) : explode($delimiter, $text, $limit);

    foreach ($explode as $text_value) {
        $text_value = trim($text_value);

        if ($empty || ($text_value !== '')) {
            $return[] = $text_value;
        }
    }

    return $return;
}

function is_https ()
{
    return (empty($_SERVER['HTTPS']) || ($_SERVER['HTTPS'] !== 'on')) ? false : true;
}

/**
 * function host ()
 *
 * returns schema and hsot
 *
 * return string
 */
function host ()
{
    return scheme().SERVER_NAME.port();
}

function scheme ()
{
    return is_https() ? 'https://' : 'http://';
}

function port ()
{
    $port = intval(getenv('SERVER_PORT'));

    if (is_https()) {
        return ($port && ($port !== 443)) ? (':'.$port) : '';
    } else {
        return ($port && ($port !== 80)) ? (':'.$port) : '';
    }
}

/**
 * function encodeAscii (string $string)
 *
 * returns the ascii value of a string
 *
 * returns string
 */
function encodeAscii ($string)
{
    $return = '';
    $length = strlen($string);

    for ($i = 0; $i < $length; $i++) {
        $return .= '&#'.ord($string[$i]).';';
    }

    return $return;
}

/**
* function deflate64 (mixed $data)
*
* return the data compressed
*
* return string
*/
function deflate64 ($data)
{
    return $data ? base64_encode(gzdeflate(serialize($data))) : '';
}

/**
* function inflate64 (string $data)
*
* return the data uncompressed
*
* return string
*/
function inflate64 ($data)
{
    if (empty($data)) {
        return '';
    }

    $data = @gzinflate(base64_decode($data));

    return $data ? unserialize($data) : '';
}

/**
* function pre (mixed $pre, [boolean $return = false ])
*
* Print or return the contents of received var
*
* return string
*/
function pre ($pre, $return = false)
{
    $str = '';

    if (is_null($pre)) {
        $str .= 'NULL';
    } else if (is_bool($pre)) {
        $str .= $pre ? 'TRUE' : 'FALSE';
    } else if (is_string($pre)) {
        $str .= '"'.$pre.'"';
    } else {
        $str .= print_r($pre, true);
    }

    if ($return) {
        return $str;
    } else {
        echo '<pre style="color: black; background: white; position: relative; z-index: 9999;">'.str_replace(' ', '&nbsp;', htmlspecialchars($str)).'</pre>';
    }
}

/**
* function trimArray (array $input)
*
* Trim all array values
*
* return array
*/
function trimArray ($array)
{
    if (!is_array($array)) {
        return trim($array);
    }
 
    return array_map('trimArray', $array);
}

/**
 * private function trace ()
 *
 * return array
 */
function trace ()
{
    $Exception = new \Exception;

    $trace = explode("\n", $Exception->getTraceAsString());

    array_pop($trace);

    return str_replace(BASE_PATH, '', implode("\n", $trace))."\n";
}
