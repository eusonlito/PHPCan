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

        $this->setCache();
    }

    private function setCache ()
    {
        global $Config;

        $settings = $Config->cache['types']['templates'];

        if ($settings['expire'] && $settings['interface']) {
            $this->Cache = new \ANS\Cache\Cache($settings);
            $this->settings['cache'] = $settings;
        } else {
            $this->Cache = false;
            $this->settings['cache'] = array();
        }
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
     * public function time (string/array $time, [string $text], [string/array $format])
     *
     * Return string
     */
     public function time ($time, $text = '', $format = 'default')
     {
        $params = array();

        if (is_array($time)) {
            $params = $time;
            $time = $params['time'];
            $text = isset($params['text']) ? $params['text'] : $text;
            $format = isset($params['format']) ? $params['format'] : $format;

            unset($params['time'], $params['text'], $params['format']);
        }

        $Datetime = getDatetimeObject($time);
        $params['datetime'] = $Datetime->format(DATE_W3C);

        if ($text) {
            if ($format) {
                $text = sprintf($text, $Datetime->__format($format));
            } else {
                $text = sprintf($text, $time);
            }
        } else {
            if ($format) {
                $text = $Datetime->__format($format);
            } else {
                $text = $time;
            }
        }

        $params['title'] = $Datetime->__format('absolute');

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
        if (!$params['href']) {
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
            } elseif ($action['params']) {
                $params['href'] .= strpos($params['href'], '?') ? '&amp;'.$action['params'] : '?'.$action['params'];
            } else {
                $params['href'] .= ':'.$action['name'];
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

        if (!$action || !$action['name']) {
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

            foreach ((array) $action['params'] as $name => $value) {
                $params .= '<input type="hidden" value="'.$value.'" name="'.$name.'" />';
            }

            $action['params'] = $params;
        } elseif (is_array($action['params'])) {
            $action['params'] = http_build_query(array('phpcan_action' => $action['name']) + $action['params']);
        } elseif ($action['params']) {
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
        if (!$data || !is_array($data)) {
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

        foreach ((array) $data as $value) {
            if ($options['title'] && $value[$options['title']]) {
                $text = $this->a($value[$options['title']]['title'], $options['href'].$value[$options['title']]['url']);
                $class = ($options['selected'] && $options['selected'] == $value[$options['title']]['url']) ? 'selected' : '';
            } elseif ($options['slug'] && $value[$options['slug']]) {
                $text = $this->a($value[$options['text']], $options['href'].$value[$options['slug']]);
                $class = ($options['selected'] && $options['selected'] == $value[$options['slug']]) ? 'selected' : '';
            } else {
                $text = $value[$options['text']];
                $class = ($options['selected'] && $options['selected'] == $text) ? 'selected' : '';
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

        if (!$params['src']) {
            return '';
        }

        //Params optimization
        $params['src'] = $this->imgSrc($params);

        unset($params['transform'], $params['host']);

        if (!$params['alt']) {
            $params['alt'] = '';
        }

        //base64
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

        if (!$params['src']) {
            return '';
        }

        $src = $params['src'];

        if ((strpos($src, '|') === false) && !parse_url($src, PHP_URL_SCHEME) && ($src[0] !== '/')) {
            $src = 'scene/uploads|'.$src;
        }

        if ($params['transform']) {
            $src = fileWeb($src, true).get(array('options' => $params['transform']), false);
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
     * public jsLinks (array $src, [integer $time])
     *
     * Create the code to import various javascript files
     */
    public function jsLinks ($files, $time = 0)
    {
        global $Config;

        $code = "\n";
        $files = (array) $files;
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

                $local[] = $value;
            }

            if ($external) {
                foreach ($external as $value) {
                    $code .= $this->jsLink($value)."\n";
                }
            }

            if ($local) {
                $time = ($time === 'auto') ? round(time() / $cache['expire']) : $time;

                $params['src'] = path('').'$.js?time='.$time.'&packed='.urlencode(deflate64($local));
                $params['type'] = 'text/javascript';

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
        if (!$file) {
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

        $params['src'] = fileWeb($file, (strpos($file, '$') !== false));

        if (!$params['src']) {
            return '';
        }

        $params['type'] = 'text/javascript';

        return '<script'.$this->params($params).'></script>';
    }

    /**
     * public cssLinks (array $files, [integer $time = 0])
     *
     * Create the code to import various css files
     */
    public function cssLinks ($files, $time = 0, $media = null)
    {
        global $Config;

        $code = "\n";
        $files = (array) $files;
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

                $local[] = $value;
            }

            if ($external) {
                foreach ($external as $value) {
                    $code .= $this->cssLink(array('href' => $value, 'media' => $media))."\n";
                }
            }

            if ($local) {
                $time = ($time === 'auto') ? round(time() / $cache['expire']) : $time;

                $params['href'] = path('').'$.css?time='.$time.'&packed='.urlencode(deflate64($local));
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
        if (!$file) {
            return '';
        }

        if (is_array($file)) {
            $params = $file;
            $file = $params['href'];
        } else {
            $params = array();
        }

        if ((strpos($file, '|') === false) && !parse_url($file, PHP_URL_SCHEME)) {
            $file = 'templates|css/'.$file;
        }

        $params['href'] = fileWeb($file, (strpos($file, '$') !== false));

        if (!$params['href']) {
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
        if (!$file) {
            return '';
        }

        if ((strpos($file, '|') === false) && !parse_url($file, PHP_URL_SCHEME)) {
            $file = 'templates|css/'.$file;
        }

        $href = fileWeb($file, (strpos($file, '$') === false));

        if (!$file || !$this->once($file)) {
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

        if (!$params['src']) {
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
            if (!$value) {
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

        if ($index > 1 && ($index%$each) == 0) {
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
            return $this->meta[$key] = trim(htmlspecialchars(strip_tags($value), ENT_QUOTES));
        }

        global $Vars;

        if ($this->meta[$key]) {
            $content = $this->meta[$key];
        } else {
            $controller = implode('/', (array) $Vars->getRoute());
            $code = 'meta:'.$key.':'.$controller;
            $content = __($code, $this->meta[$key]);
            $content = ($content == $code) ? __('meta:'.$key) : $content;
            $content = ($content == ('meta:'.$key)) ? '' : $content;
        }

        $content = str_replace('"', '&quot;', strip_tags($content));

        if ($tag) {
            return '<meta name="'.$key.'" content="'.$content.'" />';
        } else {
            return $content;
        }
    }
}
