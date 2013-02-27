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

class Form
{
    public $tabindex = 1;

    private $Debug;
    private $Html;

    /**
     * public function __construct ($Html, [string $autoglobal])
     */
    public function __construct (\ANS\PHPCan\Templates\Html\Html $Html, $autoglobal = '')
    {
        global $Vars, $Debug;

        if ($Vars->getExitMode('ajax')) {
            $this->tabindex = 1000;
        }

        $this->Debug = $Debug;
        $this->Html = $Html;

        if ($autoglobal) {
            global $Config;

            $Config->config['autoglobal'][] = $autoglobal;
        }
    }

    /**
     * private function formInputParams (string/array $value, [string $name], [string $label])
     *
     * Return array
     */
    private function formInputParams ($value, $name = '', $label = '')
    {
        $error = '';
        $extra = '';

        if (is_array($value) && empty($name)) {
            $params = $value;

            if ($name && empty($params['label_text'])) {
                $params['label_text'] = $name;
            }
        } else if (is_array($name)) {
            $params = $name;
        } else {
            $params = array(
                'value' => $value,
                'name' => $name,
                'label_text' => $label
            );
        }

        //Label params
        $label = array();

        foreach (array('text', 'position', 'class', 'for') as $param) {
            if (isset($params['label_'.$param])) {
                $label[$param] = $params['label_'.$param];
                unset($params['label_'.$param]);
            }
        }

        //Variables
        if ($params['variable']) {
            global $Vars;

            if ($Vars->exists($params['variable'])) {
                $params['value'] = $Vars->get($params['variable']);
            }

            $params['name'] = $params['variable'];
        }

        //Generate id if is needle
        if (empty($params['id'])) {
            if ($label['for']) {
                $params['id'] = $label['for'];
            } else {
                $params['id'] = uniqid('id_');
            }
        }

        //Errors
        if ($params['error']) {
            if (is_array($params['error'])) {
                $params['error'] = implode('. ', $params['error']);
            }

            if (empty($params['error_class'])) {
                $params['error_class'] = 'error';
            }

            $params['error_label_class'] = $params['error_label_class'] ? $params['error_label_class'].' '.$params['error_class'] : $params['error_class'];

            $error .= '<label for="'.$params['id'].'" class="'.$params['error_label_class'].'">'.$params['error'].'</label>';
            $params['class'] .= $params['class'] ? ' '.$params['error_class'] : $params['error_class'];
        }

        //Overwrite_control
        if ($params['overwrite_control']) {
            $extra .= $this->hidden(array(
                'value' => $params['overwrite_control'],
                'name' => $params['overwrite_control_prefix'] ? $params['overwrite_control_prefix'].$params['name'] : 'default_'.$params['name']
            ));
        }

        //Tabindex
        if ($params['tabindex'] === false) {
            unset($params['tabindex']);
        } else {
            if (empty($params['tabindex'])) {
                $params['tabindex'] = $this->tabindex;
            } else {
                $this->tabindex = $params['tabindex'];
            }

            $this->tabindex++;
        }

        //Unset phpCan attributes
        unset (
            $params['variable'],
            $params['error'],
            $params['error_class'],
            $params['overwrite_control'],
            $params['overwrite_control_suffix']
        );

        return array('params' => $params, 'label' => $label, 'error' => $error, 'extra' => $extra);
    }

    /**
     * private function formLabel (array $params)
     *
     * Return array
     */
    private function formLabel ($params)
    {
        if (empty($params['label']) || empty($params['label']['text'])) {
            unset ($params['label']);

            return $params;
        }

        $text = $params['label']['text'];
        $position = $params['label']['position'];

        unset($params['label']['text'], $params['label']['position']);

        $params['label']['for'] = $params['params']['id'];

        $label = '<label'.$this->Html->params($params['label']).'>'.$text.'</label>';

        if ($position === 'after') {
            $params['label_after'] = ' '.$label;
        } else {
            $params['label_before'] = $label.' ';
        }

        unset ($params['label']);

        return $params;
    }

    /**
     * function submit (string/array $value, [string $name])
     *
     * Return string
     */
    public function submit ($value, $name = '')
    {
        if (is_array($value) && $value['button']) {
            unset($value['button']);
            $value['type'] = 'submit';

            return $this->button($value, $name);
        }

        $params = $this->formInputParams($value, $name, '');
        $params['params']['type'] = 'submit';

        if (empty($params['params']['name'])) {
            unset($params['params']['name']);
        }

        return '<input'.$this->Html->params($params['params']).' />'.$params['error'].$params['extra'];
    }

    /**
     * function reset (string/array $value)
     *
     * Return string
     */
    public function reset ($value)
    {
        $params = $this->formInputParams($value, '', '');
        $params['params']['type'] = 'reset';

        unset($params['params']['name']);

        return '<input'.$this->Html->params($params['params']).' />'.$params['error'].$params['extra'];
    }

    /**
     * function button (string/array $value, [string $name], [string $type])
     *
     * Return string
     */
    public function button ($value, $name = '', $type = 'button')
    {
        $params = $this->formInputParams($value, $name, '');

        if (empty($params['params']['type'])) {
            $params['params']['type'] = $type ? $type : 'button';
        }

        $text = $params['params']['text'] ? $params['params']['text'] : $params['params']['value'];

        if ($action = $this->Html->action($params['params']['action'])) {
            if ($action['onclick']) {
                $params['params']['onclick'] .= $action['onclick'];
            }

            $params['params']['name'] = 'phpcan_action['.$action['name'].']';
        }

        if (empty($params['params']['name'])) {
            unset($params['params']['name']);
        }

        unset($params['params']['action'], $params['params']['text']);

        return '<button'.$this->Html->params($params['params']).'>'.$text.'</button>'.$params['error'].$params['extra'];
    }

    /**
     * function hidden (string/array $value, [string $name])
     *
     * Return string
     */
    public function hidden ($value, $name = '')
    {
        $params = $this->formInputParams($value, $name, '');

        if (empty($params['params']['name'])) {
            return '';
        }

        unset ($params['params']['tabindex']);
        $this->tabindex--;

        $params['params']['type'] = 'hidden';

        return '<input'.$this->Html->params($params['params']).' />'.$params['extra'];
    }

    /**
     * function hiddens (array $values)
     *
     * Return string
     */
    public function hiddens ($values)
    {
        $code = '';

        foreach ((array) $values as $name => $value) {
            $code .= $this->hidden($value, $name);
        }

        return $code;
    }

    /**
     * function text (string/array $value, [string $name], [string $label])
     *
     * Return string
     */
    public function text ($value, $name = '', $label = '')
    {
        $params = $this->formInputParams($value, $name, $label);
        $params = $this->formLabel($params);

        $params['params']['type'] = 'text';

        return $params['label_before'].'<input'.$this->Html->params($params['params']).' />'.$params['label_after']."\n".$params['error'].$params['extra'];
    }

    /**
     * function tel (string/array $value, [string $name], [string $label])
     *
     * Return string
     */
    public function tel ($value, $name = '', $label = '')
    {
        $params = $this->formInputParams($value, $name, $label);
        $params = $this->formLabel($params);

        $params['params']['type'] = 'tel';

        return $params['label_before'].'<input'.$this->Html->params($params['params']).' />'.$params['label_after']."\n".$params['error'].$params['extra'];
    }

    /**
     * function email (string/array $value, [string $name], [string $label])
     *
     * Return string
     */
    public function email ($value, $name = '', $label = '')
    {
        $params = $this->formInputParams($value, $name, $label);
        $params = $this->formLabel($params);

        $params['params']['type'] = 'email';

        return $params['label_before'].'<input'.$this->Html->params($params['params']).' />'.$params['label_after']."\n".$params['error'].$params['extra'];
    }

    /**
     * function url (string/array $value, [string $name], [string $label])
     *
     * Return string
     */
    public function url ($value, $name = '', $label = '')
    {
        $params = $this->formInputParams($value, $name, $label);
        $params = $this->formLabel($params);

        $params['params']['type'] = 'url';

        return $params['label_before'].'<input'.$this->Html->params($params['params']).' />'.$params['label_after']."\n".$params['error'].$params['extra'];
    }

    /**
     * function number (string/array $value, [string $name], [string $label])
     *
     * Return string
     */
    public function number ($value, $name = '', $label = '')
    {
        $params = $this->formInputParams($value, $name, $label);
        $params = $this->formLabel($params);

        $params['params']['type'] = 'number';

        return $params['label_before'].'<input'.$this->Html->params($params['params']).' />'.$params['label_after']."\n".$params['error'].$params['extra'];
    }

    /**
     * function range (string/array $value, [string $name], [string $label])
     *
     * Return string
     */
    public function range ($value, $name = '', $label = '')
    {
        $params = $this->formInputParams($value, $name, $label);
        $params = $this->formLabel($params);

        $params['params']['type'] = 'range';

        return $params['label_before'].'<input'.$this->Html->params($params['params']).' />'.$params['label_after']."\n".$params['error'].$params['extra'];
    }

    /**
     * function date (string/array $value, [string $name], [string $label])
     *
     * Return string
     */
    public function date ($value, $name = '', $label = '')
    {
        $params = $this->formInputParams($value, $name, $label);
        $params = $this->formLabel($params);

        $params['params']['type'] = 'date';

        return $params['label_before'].'<input'.$this->Html->params($params['params']).' />'.$params['label_after']."\n".$params['error'].$params['extra'];
    }

    /**
     * function month (string/array $value, [string $name], [string $label])
     *
     * Return string
     */
    public function month ($value, $name = '', $label = '')
    {
        $params = $this->formInputParams($value, $name, $label);
        $params = $this->formLabel($params);

        $params['params']['type'] = 'month';

        return $params['label_before'].'<input'.$this->Html->params($params['params']).' />'.$params['label_after']."\n".$params['error'].$params['extra'];
    }

    /**
     * function week (string/array $value, [string $name], [string $label])
     *
     * Return string
     */
    public function week ($value, $name = '', $label = '')
    {
        $params = $this->formInputParams($value, $name, $label);
        $params = $this->formLabel($params);

        $params['params']['type'] = 'week';

        return $params['label_before'].'<input'.$this->Html->params($params['params']).' />'.$params['label_after']."\n".$params['error'].$params['extra'];
    }

    /**
     * function time (string/array $value, [string $name], [string $label])
     *
     * Return string
     */
    public function time ($value, $name = '', $label = '')
    {
        $params = $this->formInputParams($value, $name, $label);
        $params = $this->formLabel($params);

        $params['params']['type'] = 'time';

        return $params['label_before'].'<input'.$this->Html->params($params['params']).' />'.$params['label_after']."\n".$params['error'].$params['extra'];
    }

    /**
     * function datetime (string/array $value, [string $name], [string $label])
     *
     * Return string
     */
    public function datetime ($value, $name = '', $label = '')
    {
        $params = $this->formInputParams($value, $name, $label);
        $params = $this->formLabel($params);

        $params['params']['type'] = 'datetime';

        return $params['label_before'].'<input'.$this->Html->params($params['params']).' />'.$params['label_after']."\n".$params['error'].$params['extra'];
    }

    /**
     * function datetimelocal (string/array $value, [string $name], [string $label])
     *
     * Return string
     */
    public function datetimelocal ($value, $name = '', $label = '')
    {
        $params = $this->formInputParams($value, $name, $label);
        $params = $this->formLabel($params);

        $params['params']['type'] = 'datetime-local';

        return $params['label_before'].'<input'.$this->Html->params($params['params']).' />'.$params['label_after']."\n".$params['error'].$params['extra'];
    }

    /**
     * function search (string/array $value, [string $name], [string $label])
     *
     * Return string
     */
    public function search ($value, $name = '', $label = '')
    {
        $params = $this->formInputParams($value, $name, $label);
        $params = $this->formLabel($params);

        $params['params']['type'] = 'search';

        return $params['label_before'].'<input'.$this->Html->params($params['params']).' />'.$params['label_after']."\n".$params['error'].$params['extra'];
    }

    /**
     * function color (string/array $value, [string $name], [string $label])
     *
     * Return string
     */
    public function color ($value, $name = '', $label = '')
    {
        $params = $this->formInputParams($value, $name, $label);
        $params = $this->formLabel($params);

        $params['params']['type'] = 'color';

        return $params['label_before'].'<input'.$this->Html->params($params['params']).' />'.$params['label_after']."\n".$params['error'].$params['extra'];
    }

    /**
     * function textarea (string/array $value, [string $name], [string $label])
     *
     * Return string
     */
    public function textarea ($value, $name = '', $label = '')
    {
        $params = $this->formInputParams($value, $name, $label);
        $params = $this->formLabel($params);

        $text = $params['params']['value'];

        unset($params['params']['value']);

        return $params['label_before'].'<textarea'.$this->Html->params($params['params']).'>'.$this->Html->escapeParams($text).'</textarea>'.$params['label_after']."\n".$params['error'].$params['extra'];
    }

    /**
     * function password (string/array $name, [string $label])
     *
     * Return string
     */
    public function password ($name, $label = '')
    {
        $params = $this->formInputParams('', $name, $label);
        $params = $this->formLabel($params);

        $params['params']['type'] = 'password';

        unset($params['params']['value']);

        return $params['label_before'].'<input'.$this->Html->params($params['params']).' />'.$params['label_after']."\n".$params['error'].$params['extra'];
    }

    /**
     * function file (string/array $name, [string $label])
     *
     * Return string
     */
    public function file ($name, $label = '')
    {
        $params = $this->formInputParams('', $name, $label);
        $params = $this->formLabel($params);

        $params['params']['type'] = 'file';

        //Multiple files
        if ($params['params']['multiple']) {
            $params['params']['name'] .= '[]';
        }

        unset($params['params']['value']);

        return $params['label_before'].'<input'.$this->Html->params($params['params']).' />'.$params['label_after']."\n".$params['error'].$params['extra'];
    }

    /**
     * function checkbox (string/array $checked, [string $name], [string $label])
     *
     * Return string
     */
    public function checkbox ($checked, $name = '', $label = '')
    {
        if (is_array($checked)) {
            $params = $this->formInputParams($checked, $name, $label);

            if ($params['params']['checked']) {
                $params['params']['checked'] = 'checked';
            } else {
                unset($params['params']['checked']);
            }
        } else {
            $params = $this->formInputParams(1, $name, $label);

            if ($checked) {
                $params['params']['checked'] = 'checked';
            }
        }

        if (empty($params['label']['position'])) {
            $params['label']['position'] = 'after';
        }

        $params = $this->formLabel($params);

        $params['params']['type'] = 'checkbox';

        if (!array_key_exists('force', $params['params']) || $params['params']['force']) {
            $hidden = $this->hidden(0, $params['params']['name']);
        } else {
            $hidden = '';
        }

        unset($params['params']['force']);

        return $hidden.$params['label_before'].'<input'.$this->Html->params($params['params']).' />'.$params['label_after']."\n".$params['error'].$params['extra'];
    }

    /**
     * private function options (array $options, array &$params)
     *
     * Return array
     */
    private function options ($options, &$params)
    {
        $result = array();

        foreach (array('first_option', 'option_title', 'option_text', 'option_text_separator', 'option_value', 'option_selected', 'option_disabled', 'option_text_as_value') as $value) {
            $$value = $params[$value];
            unset($params[$value]);
        }

        $selected = (array)$params['value'];

        unset($params['value']);

        array_walk($selected, function (&$value) {
            $value = (string)$value;
        });

        //First option
        if (isset($first_option)) {
            $result[] = array(
                'value' => '',
                'text' => $first_option
            );
        }

        //Multiple array
        if ($option_title || $option_value) {
            $option_text_separator = $option_text_separator ?: ' ';

            foreach ((array)$options as $option) {
                $value = (string)($option_value ? $option[$option_value] : $option[$option_title]['url']);

                if ($option_text && is_string($option_text)) {
                    $text = $option[$option_text];
                } else if ($option_text && is_array($option_text)) {
                    $text = array();

                    foreach ($option_text as $option_text_value) {
                        $text[] = $option[$option_text_value];
                    }

                    $text = implode($option_text_separator, $text);
                } else {
                    $text = $option[$option_title]['title'];
                }

                $result[] = array(
                    'value' => $value,
                    'text' => (empty($params['gettext']) ? $text : __($text)),
                    'selected' => (($selected && in_array($value, $selected, true)) || ($option_selected && $option[$option_selected])) ? true : null,
                    'disabled' => $option[$option_disabled] ? 'disabled' : null
                );
            }

            return $result;
        }

        //Simple array
        foreach ((array) $options as $value => $option) {
            $value = (string)($option_text_as_value ? $option : $value);

            $result[] = array(
                'value' => $value,
                'text' => (empty($params['gettext']) ? $option : __($option)),
                'selected' => ($selected && in_array($value, $selected, true)) ? true : null
            );
        }

        return $result;
    }

    /**
     * public function select ([array $options], [string $value], [string $name], [string $label])
     *
     * Return string
     */
    public function select ($options, $value = null, $name = '', $label = '')
    {
        if (is_null($value)) {
            $value = $options;
            $options = $value['options'];
            unset($value['options']);
        }

        $params = $this->formInputParams($value, $name, $label);
        $params = $this->formLabel($params);

        //Multiple selections
        if ($params['params']['multiple']) {
            $params['params']['name'] .= '[]';
        }

        $options_text = '';

        $options = $this->options($options, $params['params']);

        foreach ($options as $option) {
            $options_text .= '<option'.$this->Html->params(array(
                'value' => $option['value'],
                'selected' => $option['selected'],
                'disabled' => $option['disabled']
            )).'>'.$option['text'].'</option>'."\n";
        }

        return $params['label_before'].'<select'.$this->Html->params($params['params']).'>'."\n".$options_text."\n".'</select>'.$params['label_after']."\n".$params['error'].$params['extra'];
    }

    /**
     * function radio (array $options, string $value], [string $name], [string $label])
     *
     * Return string
     */
    public function radio ($options, $value = null, $name = '', $label = '')
    {
        if (is_null($value)) {
            $value = $options;
            $options = $value['options'];
            unset($value['options']);
        }

        $params = $this->formInputParams($value, $name, $label);

        if (empty($params['label']['position'])) {
            $params['label']['position'] = 'after';
        }

        $params['params']['type'] = 'radio';

        $options = $this->options($options, $params['params']);

        $result = '';
        $n = 1;

        foreach ($options as $option) {
            $option['id'] = $params['params']['name'].'_'.$n++;

            $labelParams = $this->formLabel(array(
                'params' => array(
                    'id' => $option['id']
                ),
                'label' => array(
                    'text' => $option['text'],
                    'position' => $params['label']['position']
                )
            ));

            $result .= $labelParams['label_before'].'<input'.$this->Html->params(array(
                'type' => 'radio',
                'name' => $params['params']['name'],
                'value' => $option['value'],
                'id' => $option['id'],
                'checked' => $option['selected'],
                'disabled' => $option['disabled']
            )).' />'.$labelParams['label_after']."\n";
        }

        return $result."\n".$params['error'].$params['extra'];
    }
}
