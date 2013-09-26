<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

namespace ANS\PHPCan\Files\Css;

defined('ANS') or die();

class Css {
    private $Debug;
    private $Css;

    public $settings = array();

    protected $_inHack = false;

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
        $this->setSettings();
    }

    private function setCache ()
    {
        global $Config;

        $settings = $Config->cache['types']['css'];

        if ($settings['expire'] && $settings['interface']) {
            $this->Cache = new \ANS\Cache\Cache($settings);

            if ($this->Cache->loaded() !== true) {
                $this->Cache = false;
            }
        } else {
            $this->Cache = false;
        }
    }

    /**
     * public function setSettings (array $settings = null)
     *
     * return boolean
     */
    public function setSettings ($settings = null)
    {
        global $Config;

        if (is_null($settings)) {
            $settings = 'css';
        }

        if (is_string($settings)) {
            if ($Config->config[$settings]) {
                $this->settings = $Config->config[$settings];
            }

            if ($this->Cache && is_array($Config->cache['types'][$settings])) {
                $this->settings['cache'] = $Config->cache['types']['css'];
            } else {
                $this->settings['cache'] = array();
            }
        } else if (is_array($settings)) {
            $this->settings = $settings;
        }

        return $this->settings;
    }

    public function load ($file)
    {
        $this->Css = \Stylecow\Parser::parseFile($file);

        return $this;
    }

    public function transform ($plugins)
    {
        $this->Css->applyPlugins($plugins);

        return $this;
    }

    public function toString ($options = array())
    {
        $css = $this->Css->toString((array)$options);

        return ($this->settings['cache']['minify'] ? $this->minify($css) : $css);
    }

    /**
     * Minify a CSS string
     * 
     * based on https://github.com/mrclay/minify/
     * 
     * @param string $css
     * 
     * @return string
     */
    protected function minify ($css)
    {
        $css = str_replace("\r\n", "\n", $css);
        $css = preg_replace('@>/\\*\\s*\\*/@', '>/*keep*/', $css);
        $css = preg_replace('@/\\*\\s*\\*/\\s*:@', '/*keep*/:', $css);
        $css = preg_replace('@:\\s*/\\*\\s*\\*/@', ':/*keep*/', $css);
        $css = preg_replace_callback('@\\s*/\\*([\\s\\S]*?)\\*/\\s*@', array($this, 'minifyComments'), $css);
        $css = preg_replace('/\\s*{\\s*/', '{', $css);
        $css = preg_replace('/;?\\s*}\\s*/', '}', $css);
        $css = preg_replace('/\\s*;\\s*/', ';', $css);
        $css = preg_replace('/
                url\\(      # url(
                \\s*
                ([^\\)]+?)  # 1 = the URL (really just a bunch of non right parenthesis)
                \\s*
                \\)         # )
            /x', 'url($1)', $css);
        $css = preg_replace('/
                \\s*
                ([{;])              # 1 = beginning of block or rule separator 
                \\s*
                ([\\*_]?[\\w\\-]+)  # 2 = property (and maybe IE filter)
                \\s*
                :
                \\s*
                (\\b|[#\'"-])        # 3 = first character of a value
            /x', '$1$2:$3', $css);
        $css = preg_replace_callback('/
                (?:              # non-capture
                    \\s*
                    [^~>+,\\s]+  # selector part
                    \\s*
                    [,>+~]       # combinators
                )+
                \\s*
                [^~>+,\\s]+      # selector part
                {                # open declaration block
            /x'
            ,array($this, 'minifySelectors'), $css);
        $css = preg_replace('/([^=])#([a-f\\d])\\2([a-f\\d])\\3([a-f\\d])\\4([\\s;\\}])/i'
            , '$1#$2$3$4$5', $css);
        $css = preg_replace_callback('/font-family:([^;}]+)([;}])/'
            ,array($this, '_fontFamilyCB'), $css);
        $css = preg_replace('/@import\\s+url/', '@import url', $css);
        $css = preg_replace('/[ \\t]*\\n+\\s*/', "\n", $css);
        $css = preg_replace('/([\\w#\\.\\*]+)\\s+([\\w#\\.\\*]+){/', "$1\n$2{", $css);
        $css = preg_replace('/
            ((?:padding|margin|border|outline):\\d+(?:px|em)?) # 1 = prop : 1st numeric value
            \\s+
            /x'
            ,"$1\n", $css);
        $css = preg_replace('/:first-l(etter|ine)\\{/', ':first-l$1 {', $css);

        return trim($css);
    }
    
    /**
     * Replace what looks like a set of selectors  
     *
     * @param array $m regex matches
     * 
     * @return string
     */
    protected function minifySelectors ($m)
    {
        return preg_replace('/\\s*([,>+~])\\s*/', '$1', $m[0]);
    }
    
    /**
     * Process a comment and return a replacement
     * 
     * @param array $m regex matches
     * 
     * @return string
     */
    protected function minifyComments ($m)
    {
        $hasSurroundingWs = (trim($m[0]) !== $m[1]);

        $m = $m[1]; 

        if ($m === 'keep') {
            return '/**/';
        }

        if ($m === '" "') {
            return '/*" "*/';
        }

        if (preg_match('@";\\}\\s*\\}/\\*\\s+@', $m)) {
            return '/*";}}/* */';
        }

        if ($this->_inHack) {
            if (preg_match('@
                    ^/               # comment started like /*/
                    \\s*
                    (\\S[\\s\\S]+?)  # has at least some non-ws content
                    \\s*
                    /\\*             # ends like /*/ or /**/
                @x', $m, $n)) {

                $this->_inHack = false;

                return "/*/{$n[1]}/**/";
            }
        }

        if (substr($m, -1) === '\\') {
            $this->_inHack = true;
            return '/*\\*/';
        }

        if ($m !== '' && $m[0] === '/') {
            $this->_inHack = true;
            return '/*/*/';
        }

        if ($this->_inHack) {
            $this->_inHack = false;
            return '/**/';
        }

        return $hasSurroundingWs ? ' ' : '';
    }
    
    /**
     * Process a font-family listing and return a replacement
     * 
     * @param array $m regex matches
     * 
     * @return string   
     */
    protected function _fontFamilyCB($m)
    {
        // Issue 210: must not eliminate WS between words in unquoted families
        $pieces = preg_split('/(\'[^\']+\'|"[^"]+")/', $m[1], null, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        $out = 'font-family:';
        while (null !== ($piece = array_shift($pieces))) {
            if ($piece[0] !== '"' && $piece[0] !== "'") {
                $piece = preg_replace('/\\s+/', ' ', $piece);
                $piece = preg_replace('/\\s?,\\s?/', ',', $piece);
            }
            $out .= $piece;
        }
        return $out . $m[2];
    }

    /**
     * public function show ([boolean $header], [boolean $die])
     *
     * Print the css file
     */
    public function show ($header = true, $die = true)
    {
        $cache = $this->settings['cache']['expire'];
        $cache = ($cache && $this->Cache) ? $cache : false;

        if ($header) {
            header('Content-type: text/css');

            if ($cache) {
                header('Expires: '.gmdate('D, d M Y H:i:s', (time() + $cache).' GMT'));
            }
        }

        $text = $this->toString();

        if ($cache) {
            $text = $this->settings['cache']['minify'] ? $this->minify($text) : $text;
            $this->Cache->set(md5($this->file), $text, $cache);
        }

        echo $text;

        if ($die) {
            die();
        }
    }

    /**
     * public function showCached (string $file, [boolean $header], [boolean $die])
     *
     * Print the cached css file
     *
     * return boolean
     */
    public function showCached ($file, $header = true, $die = true)
    {
        $cache = $this->settings['cache']['expire'];
        $cache = ($cache && $this->Cache) ? $cache : false;

        $key = md5($file);

        if (empty($cache) || !$this->Cache->exists($key)) {
            return false;
        }

        if ($header) {
            header('Content-type: text/css');
            header('Expires: '.gmdate('D, d M Y H:i:s', (time() + $cache).' GMT'));
        }

        echo $this->Cache->get($key);

        if ($die) {
            die();
        }
    }
}
