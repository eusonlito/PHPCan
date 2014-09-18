<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

namespace ANS\PHPCan\Templates\Html;

defined('ANS') or die();

class Html
{
    private $once = array();
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

        $this->setSettings();
    }

    private function setSettings ()
    {
        global $Config;

        $settings = $Config->cache['types']['templates'];

        if ($settings['expire'] && $settings['interface']) {
            $this->Cache = new \ANS\Cache\Cache($settings);

            if ($this->Cache->loaded() === true) {
                $this->settings['cache'] = $settings;
            } else {
                $this->Cache = false;
            }
        } else {
            $this->Cache = false;
            $this->settings['cache'] = array();
        }

        $this->settings['autoversion'] = $Config->autoversion;
    }

    /**
     * public function element (string $tagname, [array $params])
     *
     * Return string
     */
    public function element ($tagname, $params = null, $text = null)
    {
        $result = '<'.$tagname;

        if (is_array($params)) {
            $result .= $this->params($params);
        } else {
            $text = $params;
        }

        if (is_null($text)) {
            $result .= ' />';
        } else {
            $result .= '>'.$text.'</'.$tagname.'>';
        }

        return $result;
    }

    /**
     * public function time (string/array $time, [string|closure $text], [string/array $format], [string $title])
     *
     * Return string
     */
     public function time ($time, $text = '', $format = 'default', $title = null)
     {
        $params = array();

        if (is_array($time)) {
            $params = $time;
            $time = $params['time'];
            $text = isset($params['text']) ? $params['text'] : $text;
            $title = isset($params['title']) ? $params['title'] : $title;
            $format = isset($params['format']) ? $params['format'] : $format;

            unset($params['time'], $params['text'], $params['format'], $params['title']);
        }

        $Datetime = getDatetimeObject($time);
        $params['datetime'] = $Datetime->format(DATE_W3C);

        if ($text) {
            if (is_string($text)) {
                if ($format) {
                    $text = sprintf($text, $Datetime->__format($format));
                } else {
                    $text = sprintf($text, $time);
                }
            } else if ($text instanceof \Closure) {
                if ($format) {
                    $text = $text($Datetime->__format($format));
                } else {
                    $text = $text($text, $time);
                }
            }
        } else {
            if ($format) {
                $text = $Datetime->__format($format);
            } else {
                $text = $time;
            }
        }

        if ($title === null) {
            $params['title'] = $Datetime->__format('absolute');
        } else {
            $params['title'] = sprintf($title, $Datetime->__format('absolute'));
        }

        return '<time'.$this->params($params).'>'.$text.'</time>';
    }

    /**
     * public function a ([string/array $text], [string $href], [string $title])
     *
     * Return string
     */
    public function a ($text, $href = '', $title = null)
    {
        if (is_array($text)) {
            $params = $text;
        } else {
            $params = array(
                'text' => $text,
                'href' => $href,
                'title' => $title
            );
        }

        $text = $params['text'];
        unset($params['text']);

        //Params optimization
        if (empty($params['href'])) {
            $params['href'] = $text;
        }

        $params['href'] = str_replace('&amp;', '&', $params['href']);
        $params['href'] = str_replace('&', '&amp;', $params['href']);

        //Action
        if ($action = $this->action($params['action'])) {
            if ($action['onclick']) {
                $params['onclick'] .= $action['onclick'];
            }

            if ($action['method'] === 'post') {
                $id = uniqid('name_form_action_');

                $extra = '<form action="'.$params['href'].'" method="post" name="'.$id.'" style="display:none;"><fieldset>'.$action['params'].'</fieldset></form>';

                if ($action['rel_form']) {
                    $params['rel'] = $id;
                } else {
                    $params['onclick'] .= 'document.'.$id.'.submit(); return false;';
                }
            } else if ($action['params']) {
                $params['href'] .= (strstr($params['href'], '?') ? '&amp;' : '?').$action['params'];
            } else {
                $params['href'] .= (strstr($params['href'], '?') ? '&amp;' : '?').'phpcan_action='.$action['name'];
            }
        }

        unset($params['action']);

        if ($params['target'] === '_blank') {
            unset($params['target']);
            $params['onclick'] .= 'window.open(this.href); return false;';
        }

        return $extra.'<a'.$this->params($params).'>'.$text.'</a>';
    }

    /**
     * public function action (string/array $action, [string $method])
     *
     * Return false/array
     */
    public function action ($action, $method = '')
    {
        if (is_string($action)) {
            $action = array('name' => $action);
        }

        if (empty($action) || empty($action['name'])) {
            return false;
        }

        if ($method) {
            $action['method'] = $method;
        }

        if ($action['confirm']) {
            $action['confirm'] = str_replace('\'', '\\\'', $action['confirm']);
            $action['onclick'] .= 'if (!confirm(\''.$action['confirm'].'\')) {return false;}';
        }

        if ($action['method'] === 'post') {
            $params = '<input type="hidden" value="'.$action['name'].'" name="phpcan_action" />';

            foreach ((array)$action['params'] as $name => $value) {
                $params .= '<input type="hidden" value="'.$value.'" name="'.$name.'" />';
            }

            $action['params'] = $params;
        } else if (is_array($action['params'])) {
            $action['params'] = http_build_query(array('phpcan_action' => $action['name']) + $action['params']);
        } else if ($action['params']) {
            $action['params'] = 'phpcan_action['.$action['name'].']='.$action['params'];
        }

        return $action;
    }

    /**
     * public function aList (array $data, [string $text], [string $slug], [string $url], [string $separator])
     * public function aList (array $data, string $slug, [string $url], [string $separator])
     *
     * Return array
     */
    public function aList ($data, $text = null, $slug = null, $href = null, $separator = ', ')
    {
        if (empty($data) || !is_array($data)) {
            return '';
        }

        $links = array();

        if (is_array($data[0][$text])) {
            $separator = is_null($href) ? $separator : $href;
            $href = $slug;

            foreach ($data as $value) {
                $links[] = $this->a($value[$text]['title'], $href.$value[$text]['url']);
            }
        } else {
            foreach ($data as $value) {
                $links[] = $this->a($value[$text], $href.$value[$slug]);
            }
        }

        return implode($separator, $links);
    }

    /**
     * public function ul (array $options)
     *
     * Return string
     */
    public function ul ($options)
    {
        $params = $options;

        unset(
            $params['data'],
            $params['text'],
            $params['slug'],
            $params['recursive'],
            $params['title'],
            $params['href'],
            $params['selected']
        );

        $li = $this->li($options['data'], $options);

        return '<ul'.$this->params($params).'>'.$li['text'].'</ul>';
    }

    /**
     * private function li (array $data, array $options)
     *
     * Return string
     */
    private function li ($data, $options)
    {
        $return = array();

        foreach ((array)$data as $value) {
            if ($options['title'] && $value[$options['title']]) {
                $text = $this->a($value[$options['title']]['title'], $options['href'].$value[$options['title']]['url']);
                $class = ($options['selected'] && ($options['selected'] == $value[$options['title']]['url'])) ? 'selected' : '';
            } else if ($options['slug'] && $value[$options['slug']]) {
                $text = $this->a($value[$options['text']], $options['href'].$value[$options['slug']]);
                $class = ($options['selected'] && ($options['selected'] == $value[$options['slug']])) ? 'selected' : '';
            } else {
                $text = $value[$options['text']];
                $class = ($options['selected'] && ($options['selected'] == $text)) ? 'selected' : '';
            }

            if ($options['recursive'] && $value[$options['recursive']]) {
                $sub_ul = $this->li($value[$options['recursive']], $options);

                if ($sub_ul['text']) {
                    $text .= '<ul>'.$sub_ul['text'].'</ul>';
                }

                if ($sub_ul['selected']) {
                    $class = 'sub_selected';
                }
            }

            if ($class) {
                $return['selected'] = true;
            }

            $return['text'] .= '<li'.($class ? ' class="'.$class.'"' : '').'>'.$text;

            $return['text'] .= '</li>';
        }

        return $return;
    }

    /**
     * function img (string/array $src, [string $alt], [string $transform])
     *
     * Return string
     */
    public function img ($src, $alt = '', $transform = '')
    {
        if (is_array($src)) {
            $params = $src;
        } else {
            $params = array(
                'src' => $src,
                'alt' => $alt,
                'transform' => $transform
            );
        }

        if ($params['src']) {
            $params['src'] = $this->imgSrc($params);
        }

        unset($params['transform'], $params['host']);

        $params['alt'] = isset($params['alt']) ? $params['alt']  : '';

        if ($params['base64']) {
            $cache = $this->settings['cache']['expire'];
            $cache = ($cache && $this->Cache) ? $cache : false;

            $key = md5($params['src']);
            $src = fileWeb($params['src'], false, true);

            if ($cache && $this->Cache->exists($key)) {
                $file = $this->Cache->get($key);
            } else {
                $filepath = DOCUMENT_ROOT.$params['src'];

                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $file = 'data:'.finfo_file($finfo, $filepath).';base64,'.base64_encode(file_get_contents($filepath));
                finfo_close($finfo);

                if ($cache) {
                    $this->Cache->set($key, $file);
                }
            }

            $params['src'] = $file;
        }

        unset($params['base64']);

        return '<img'.$this->params($params).' />';
    }

    /**
     * public function imgSrc (string $params, [string $transform])
     *
     * return string
     */
    public function imgSrc ($params, $transform = '')
    {
        if (is_string($params)) {
            $params = array(
                'src' => $params,
                'transform' => $transform
            );
        }

        if (empty($params['src'])) {
            return '';
        }

        $src = $params['src'];

        if ((strpos($src, '|') === false) && !parse_url($src, PHP_URL_SCHEME) && ($src[0] !== '/')) {
            $src = 'scene/uploads|'.$src;
        }

        if ($params['transform']) {
            $src = createCacheLink(fileWeb($src, true), array('options' => $params['transform']));
        } else {
            $src = fileWeb($src);
        }

        $src = str_replace('&amp;', '&', $src);
        $src = str_replace('&', '&amp;', $src);

        if ($params['host']) {
            $src = host().$src;
        }

        return $src;
    }

    /**
     * public jsLinks (array $src, [integer/string $time = 'auto'])
     *
     * Create the code to import various javascript files
     */
    public function jsLinks ($files, $time = 'auto')
    {
        global $Config;

        $code = "\n";
        $files = (array)$files;
        $cache = $Config->cache['types']['js'];

        if ($cache['pack'] && $cache['expire'] && $cache['interface']) {
            $external = $local = array();

            foreach ($files as $value) {
                if (strstr($value, '://')) {
                    $external[] = $value;
                    continue;
                }

                if ((strpos($value, '|') === false) && !parse_url($value, PHP_URL_SCHEME)) {
                    $value = 'templates|js/'.$value;
                }

                $local[] = $this->settings['autoversion'] ? $this->autoVersion($value) : $value;
            }

            if ($external) {
                foreach ($external as $value) {
                    $code .= $this->jsLink($value)."\n";
                }
            }

            if ($local) {
                global $Vars;

                $time = ($time === 'auto') ? round(time() / $cache['expire']) : $time;

                $params['type'] = 'text/javascript';
                $params['src'] = createCacheLink(fileWeb('templates|js/').$time.'.js', array(
                    'files' => $local,
                    'language' => $Vars->getLanguage()
                ));

                $code .= '<script'.$this->params($params).'></script>'."\n";
            }
        } else {
            foreach ($files as $value) {
                $code .= $this->jsLink($value)."\n";
            }
        }

        return $code;
    }

    /**
     * public jsLink (string/array $file)
     *
     * Create the code to import a javascript file
     *
     * return string
     */
    public function jsLink ($file)
    {
        if (empty($file)) {
            return '';
        }

        if (is_array($file)) {
            $params = $file;
            $file = $params['src'];
        } else {
            $params = array();
        }

        if ((strpos($file, '|') === false) && !parse_url($file, PHP_URL_SCHEME)) {
            $file = 'templates|js/'.$file;
        }

        $file = $this->settings['autoversion'] ? $this->autoVersion($file) : $file;

        if (strstr($file, '$') !== false) {
            global $Vars;

            $file = str_replace('$', '', $file);

            $options = array(
                'dynamic' => true,
                'language' => $Vars->getLanguage()
            );

            if (strstr($file, '?') !== false) {
                list($file, $query) = explode('?', $file, 2);

                parse_str($query, $query);

                $options = $options + $query;
            }

            $params['src'] = createCacheLink(fileWeb($file, true), $options);
        } else {
            $params['src'] = fileWeb($file);
        }

        if (empty($params['src'])) {
            return '';
        }

        $params['type'] = 'text/javascript';

        return '<script'.$this->params($params).'></script>';
    }

    /**
     * public cssLinks (array $files, [integer/string $time = 'auto'])
     *
     * Create the code to import various css files
     */
    public function cssLinks ($files, $time = 'auto', $media = null)
    {
        global $Config;

        $code = "\n";
        $files = (array)$files;
        $cache = $Config->cache['types']['css'];

        if ($cache['pack'] && $cache['expire'] && $cache['interface']) {
            $external = $local = array();

            foreach ($files as $value) {
                if (strstr($value, '://')) {
                    $external[] = $value;
                    continue;
                }

                if ((strpos($value, '|') === false) && !parse_url($value, PHP_URL_SCHEME)) {
                    $value = 'templates|css/'.$value;
                }

                $local[] = $this->settings['autoversion'] ? $this->autoVersion($value) : $value;
            }

            if ($external) {
                foreach ($external as $value) {
                    $code .= $this->cssLink(array('href' => $value, 'media' => $media))."\n";
                }
            }

            if ($local) {
                $time = ($time === 'auto') ? round(time() / $cache['expire']) : $time;

                $params['href'] = createCacheLink(fileWeb('templates|css/').$time.'.css', array('files' => $local));
                $params['type'] = 'text/css';
                $params['rel'] = 'stylesheet';
                $params['media'] = $media;

                $code .= '<link'.$this->params($params).' />'."\n";
            }
        } else {
            foreach ($files as $value) {
                $code .= $this->cssLink(array('href' => $value, 'media' => $media))."\n";
            }
        }

        return $code;
    }

    /**
     * public cssLink (string/array $file)
     *
     * Create the code to import a css file
     *
     * return string
     */
    public function cssLink ($file)
    {
        if (empty($file)) {
            return '';
        }

        if (is_array($file)) {
            $params = $file;
            $file = $params['href'];
        } else {
            $params = array();
        }

        if ((strstr($file, '|') === false) && !parse_url($file, PHP_URL_SCHEME)) {
            $file = 'templates|css/'.$file;
        }

        $file = $this->settings['autoversion'] ? $this->autoVersion($file) : $file;

        if (strstr($file, '$') !== false) {
            $file = str_replace('$', '', $file);

            $options = array('dynamic' => true);

            if (strstr($file, '?') !== false) {
                list($file, $query) = explode('?', $file, 2);

                parse_str($query, $query);

                $options = $options + $query;
            }

            $params['href'] = createCacheLink(fileWeb($file, true), $options);
        } else {
            $params['href'] = fileWeb($file);
        }

        if (empty($params['href'])) {
            return '';
        }

        $params['type'] = 'text/css';
        $params['rel'] = 'stylesheet';

        return '<link'.$this->params($params).' />';
    }

    /**
     * public dinamicCssLink (string $file)
     *
     * Create the code to import a css file by javascript
     *
     * return string
     */
    public function dinamicCssLink ($file)
    {
        if (empty($file)) {
            return '';
        }

        if ((strpos($file, '|') === false) && !parse_url($file, PHP_URL_SCHEME)) {
            $file = 'templates|css/'.$file;
        }

        $href = fileWeb($file, (strpos($file, '$') === false));

        if (empty($file) || !$this->once($file)) {
            return '';
        }

        return '<script type="text/javascript">'
            .'(function () {'
            .'var headNode = document.getElementsByTagName("head")[0];'
            .'var cssNode = document.createElement("link");'
            .'cssNode.type = "text/css";'
            .'cssNode.rel = "stylesheet";'
            .'cssNode.href = "'.$file.'";'
            .'headNode.appendChild(cssNode);'
            .'})();'
            .'</script>';
    }

    /**
     * private function autoVersion ($file)
     *
     * return string
     */
    private function autoVersion ($file)
    {
        if ($this->settings['autoversion'] !== true) {
            return $file;
        }

        if (strstr($file, '|')) {
            $realfile = filePath(str_replace('$', '', $file));
        } else if (strstr($file, '$')) {
            $realfile = preg_replace('#^'.preg_quote(BASE_WWW, '#').'#', '', $file);
            $realfile = explode('/', str_replace('$', '', $realfile));

            $context = array_shift($realfile);
            $basedir = array_shift($realfile);

            $realfile = filePath($context.'/'.$basedir.'|'.implode('/', $realfile));
        } else {
            $realfile = DOCUMENT_ROOT.array_shift(explode('?', $file));
        }

        if (!is_file($realfile)) {
            return $file;
        }

        return $file.(strstr($file, '?') ? '&amp;' : '?').filemtime($realfile);
    }

    /**
     * public params (array $params)
     *
     * Return string
     */
    public function params ($params)
    {
        if (!is_array($params)) {
            return '';
        }

        $txt = '';

        //Short cuts
        foreach ($params as $key => $value) {
            switch ($key) {
                case 'confirm':
                    if (($value = str_replace('\'', '\\\'', $value))) {
                        $params['onclick'] .= 'if (!confirm(\''.$value.'\')) {return false;}';
                    }

                    unset($params[$key]);
            }
        }

        foreach ($params as $key => $value) {
            if (is_null($value)) {
                continue;
            }

            switch ($key) {
                //XHTML flags
                case 'selected':
                case 'checked':
                case 'disabled':
                case 'multiple':
                case 'autofocus':
                case 'required':
                    if ($value) {
                        $txt .= ' '.$key;
                    }
                    break;

                default:
                    $txt .= ' '.$key.'="'.$this->escapeParams($value).'"';
            }
        }

        return $txt;
    }

    /**
     * public function escapeParams (string/array $text)
     *
     * Return string
     */
    public function escapeParams ($text)
    {
        $in = array('&','\\"','"','<','>','&amp;amp;');
        $out = array('&amp;','&quot;','&quot;','&lt;','&gt;','&amp;');

        return str_replace($in, $out, $text);
    }

    /**
     * function flash (array/string $params, [int $width], [int $height])
     *
     * return string
     */
    public function flash ($params, $width = 0, $height = 0)
    {
        if (!is_array($params)) {
            $params = array(
                'src' => $params,
                'width' => $width,
                'height' => $height
            );
        }

        if (empty($params['src'])) {
            return '';
        }

        $params_object = array(
            'width' => $params['width'],
            'height' => $params['height'],
            'type' => 'application/x-shockwave-flash',
            'data' => $params['src']
        );

        $params_param = array(
            'movie' => $params['src'],
            'quality' => $params['quality'] ? $params['quality'] : 'high',
            'bgcolor' => $params['background'] ? $params['background'] : '#FFFFFF',
            'wmode' => $params['wmode'] ? $params['wmode'] : 'transparent'
        );

        $txt = '<object'.$this->params($params_object).'>';

        foreach ($params_param as $name => $value) {
            if (empty($value)) {
                continue;
            }
            $txt .= '<param name="'.$name.'" value="'.$value.'" />';
        }
        $txt .= '</object>';

        return $txt;
    }

    /**
     * function once (string $text)
     *
     * Return the same string once
     *
     * Return string
     */
    public function once ($text)
    {
        $code = md5($text);

        if (!in_array($code, $this->once)) {
            $this->once[] = $code;

            return $text;
        } else {
            return '';
        }
    }

    /**
     * function each (string $text, int $each, int $index, [int $offset])
     *
     * Return the string in multiples of $number
     *
     * Return string
     */
    public function each ($text, $each, $index, $offset = 0)
    {
        $index = $index + 1 + $offset;

        if (($index >= 1) && (($index % $each) === 0)) {
            return $text;
        }

        return '';
    }

    /**
    * public function meta (string $key, [string $value = null], [boolean $tag = true])
    *
    * Get or set a meta string from controller value
    *
    * return string
    */
    public function meta ($key, $value = null, $tag = true)
    {
        if ($value !== null) {
            if (is_string($value)) {
                $value = trim(htmlspecialchars(strip_tags($value), ENT_QUOTES));
            } else if (is_array($value)) {
                array_walk($value, function (&$value) {
                    $value = trim(htmlspecialchars(strip_tags($value), ENT_QUOTES));
                });
            }

            return $this->meta[$key] = $value;
        }

        global $Vars;

        if ($this->meta[$key]) {
            $content = $this->meta[$key];
        } else {
            $controller = implode('/', (array)$Vars->getRoute());
            $code = 'meta:'.$key.':'.$controller;
            $meta = $this->meta[$key];

            if (is_string($meta)) {
                $content = __($code, $meta);
                $content = ($content === $code) ? __('meta:'.$key) : $content;
                $content = ($content === ('meta:'.$key)) ? '' : $content;
            }
        }

        if (is_string($content)) {
            $content = str_replace('"', '&quot;', strip_tags($content));
        }

        if ($tag) {
            return '<meta name="'.$key.'" content="'.$content.'" />';
        } else {
            return $content;
        }
    }
}
