<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

namespace ANS\PHPCan\Templates;

defined('ANS') or die();

class Templates
{
    private $Debug;
    private $Cache;

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

            if ($this->Cache->loaded() !== true) {
                $this->Cache = false;
            }
        } else {
            $this->Cache = false;
        }
    }

    /**
     * public function file (string $template, [boolean $empty_template], [boolean $debug])
     *
     * Load a template
     *
     * return boolean/string
     */
    public function file ($template, $empty_template = true)
    {
        global $Config;

        $empty_template = $empty_template ? filePath('phpcan/libs|ANS/PHPCan/Utils/empty_template.php') : false;
        $debug = false;
        $templates = $Config->templates;

        if ($templates[$template]) {
            if ((strstr($template, '/templates/') !== false) && is_file($templates[$template])) {
                $template = $templates[$template];
            } else {
                if (strpos($templates[$template], '|') === false) {
                    $templates[$template] = 'templates|'.$templates[$template];
                }

                $template = filePath($templates[$template]);

                $debug = true;
            }
        } else if (isset($templates[$template])) {

            return $empty_template;
        } else if ($template) {
            if (!is_file($template)) {
                if (strpos($template, '|') === false) {
                    $template = 'templates|'.$template;
                }

                $template = filePath($template);
            }
        } else {
            return $empty_template;
        }

        return is_file($template) ? $template : $empty_template;
    }

    /**
     * public function render (string $render_settings, [array $data], [boolean $return_html], [string $separation], [string $after])
     *
     * Render a template
     *
     * return string/boolean
     */
    public function render ($render_settings, $data = null, $return_html = false, $separation = null, $after = null)
    {
        if (is_string($render_settings)) {
            $render_settings = array(
                'template' => $render_settings,
                'data' => $data,
                'return_html' => $return_html
            );

            if (!is_null($separation)) {
                if (is_null($after)) {
                    $render_settings['separation'] = $separation;
                } else {
                    $render_settings['before'] = $separation;
                    $render_settings['after'] = $after;
                }
            }
        }

        if (!($render_settings['template'] = $this->file($render_settings['template'], false))) {
            return false;
        }

        ob_start();
        $return = array();

        if (is_array($render_settings['data']) && (isNumericalArray($render_settings['data']) || empty($render_settings['data']))) {
            foreach ($render_settings['data'] as $index => $data_content_value) {
                $data_content_value['index'] = $index;

                if ($render_settings['common_data']) {
                    $data_content_value += (array)$render_settings['common_data'];
                }

                includeFile($render_settings['template'], $data_content_value);

                $return[] = ob_get_contents();
                ob_clean();
            }
        } else {
            if ($render_settings['common_data']) {
                $render_settings['data'] += (array)$render_settings['common_data'];
            }

            includeFile($render_settings['template'], $render_settings['data']);

            $return[] = ob_get_contents();
        }

        ob_end_clean();

        if (empty($return)) {
            $return = '';
        } else {
            $render_settings['after'] = ($render_settings['after'] ? $render_settings['after'] : '')."\n";
            $render_settings['before'] = ($render_settings['before'] ? $render_settings['before'] : '')."\n";
            $render_settings['separation'] = $render_settings['after'].$render_settings['separation'].$render_settings['before'];

            $return = $render_settings['before'].implode($render_settings['separation'], $return).$render_settings['after'];

            if ($render_settings['tabulate']) {
                $return = preg_replace('/^/m', str_repeat("\t", intval($render_settings['tabulate'])), $return);
            }
        }

        if (empty($render_settings['return_html'])) {
            echo $return;

            return true;
        }

        return $return;
    }

    /**
     * public function exists (string $template)
     *
     * return boolean
     */
    public function exists ($template)
    {
        return ($this->file($template, false) ? true : false);
    }

    /**
     * public function add (string $name, string $file, [string $exit_mode])
     *
     * return none
     */
    public function add ($name, $file, $exit_mode = '')
    {
        global $Config, $Vars;

        if (empty($exit_mode) || ($exit_mode === 'all') || $Vars->getExitMode($exit_mode)) {
            $Config->config['templates'][$name] = $file;
        }
    }

    /**
     * public function remove ($name, [string $exit_mode])
     *
     * return none
     */
    public function remove ($name, $exit_mode = '')
    {
        global $Config, $Vars;

        if (empty($exit_mode) || ($exit_mode === 'all') || $Vars->getExitMode($exit_mode)) {
            unset($Config->config['templates'][$name]);
        }
    }
}
