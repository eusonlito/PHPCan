<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

namespace ANS\PHPCan\I18n;

defined('ANS') or die();

class Datetime extends \DateTime {
    private $Debug;

    /**
     * public function __construct ([string $time], [int $timezone])
     *
     * return string
     */
    public function __construct ($time = 'now', $timezone = NULL)
    {
        global $Debug;

        $this->Debug = $Debug;

        if (empty($time)) {
            return parent::__construct();
        }

        if (preg_match('/^[0-9]+$/', $time)) {
            parent::__construct();

            $this->setTimeStamp($time);

            return $this;
        }

        if (empty($timezone)) {
            return parent::__construct($time);
        }

        return parent::__construct($time, $timezone);
    }

    /**
     * public function getMonth ([int $month], [bool $abbr])
     *
     * return string
     */
    public function getMonth ($month = 0, $abbr = false)
    {
        global $Config;

        if (preg_match('/^[0-9]$/', $month)) {
            $month = intval($month);
        } else if (preg_match('/^[0-9]+$/', $month)) {
            $month = date('n', $month);
        } else {
            $month = date('n', strtotime($month));
        }

        if ($month > 12 || $month < 1) {
            $month = intval($this->format('n'));
        }

        if ($month > 12 || $month < 1) {
            $month = intval($this->format('n'));
        }

        if ($abbr) {
            $months = array('$M_Jan', '$M_Feb', '$M_Mar', '$M_Apr', '$M_May', '$M_Jun', '$M_Jul', '$M_Aug', '$M_Sep', '$M_Oct', '$M_Nov', '$M_Dec');
        } else {
            $months = array('$F_January', '$F_February', '$F_March', '$F_April', '$F_May', '$F_June', '$F_July', '$F_August', '$F_September', '$F_October', '$F_November', '$F_December');
        }

        return $Config->i18n['time_translations'][$months[$month - 1]];
    }

    /**
     * public function getWeekDay ([int $day], [bool $abbr])
     *
     * return string
     */
    public function getWeekDay ($day = 0, $abbr = false)
    {
        global $Config;

        if (preg_match('/^[0-9]$/', $day)) {
            $day = intval($day);
        } else if (preg_match('/^[0-9]+$/', $day)) {
            $day = date('N', $day);
        } else {
            $day = date('N', strtotime($day));
        }

        if ($day > 7 || $day < 1) {
            $day = intval($this->format('N'));
        }

        if ($abbr) {
            $days = array('$D_Mon', '$D_Tue', '$D_Wed', '$D_Thu', '$D_Fri', '$D_Sat', '$D_Sun');
        } else {
            $days = array('$l_Monday', '$l_Tuesday', '$l_Wednesday', '$l_Thursday', '$l_Friday', '$l_Saturday', '$l_Sunday');
        }

        return $Config->i18n['time_translations'][$days[$day - 1]];
    }

    /** public function humanDiff (mixed $start, mixed $end)
    *
    * return string
    */
    public function humanDiff ($start, $end)
    {
        $start = preg_match('/^[0-9]+$/', $start) ? $start : strtotime($start);
        $end = preg_match('/^[0-9]+$/', $end) ? $end : strtotime($end);

        $diff = ($end > $start) ? ($end - $start) : ($start - $end);

        if (empty($diff)) {
            return __('now');
        }

        $strings = array(
            'single' => array(
                'y' => 'year',
                'm' => 'month',
                'd' => 'day',
                'h' => 'hour',
                'i' => 'minute',
                's' => 'second'
            ),
            'plural' => array(
                'y' => 'years',
                'm' => 'months',
                'd' => 'days',
                'h' => 'hours',
                'i' => 'minutes',
                's' => 'seconds'
            )
        );

        $times = array(
            'y' => 60 * 60 * 24 * 30 * 12,
            'm' => 60 * 60 * 24 * 30,
            'd' => 60 * 60 * 24,
            'h' => 60 * 60,
            'i' => 60,
        );

        foreach ($times as $key => $value) {
            $time = floor($diff / $value);

            if ($time) {
                $diff -= $time * $value;
                $string .= $time.' '.__(($time === 1) ? $strings['single'][$key] : $strings['plural'][$key]).' ';
            }
        }

        return trim($string.__('and').' '.$diff.' '.__(($diff === 1) ? $strings['single']['s'] : $strings['plural']['s']));
    }

    /**
     * public function __format ([array/string $format])
     *
     * return string
     */
    public function __format ($format = 'default')
    {
        global $Config;

        if (is_string($format)) {
            if (isset($Config->i18n['time_formats'][$format])) {
                $time_formats = $Config->i18n['time_formats'][$format];
            } else {
                return false;
            }
        } else {
            $time_formats = isMultidimensionalArray($format) ? $format : array($format);
        }

        krsort($time_formats);

        $Now = new \DateTime('now');

        $diff = $this->diff($Now);

        $diff_seconds = ($diff->days * 86400) + ($diff->h * 3600) + ($diff->i * 60) + $diff->s;

        if ($diff->invert) {
            $diff_seconds = -$diff_seconds;
        }

        $array_time_formats_seconds = array_keys($time_formats);

        $k = 0;

        foreach ($time_formats as $time_formats_seconds => $time_formats_value) {
            list($format, $absolute) = $time_formats_value;

            if (($diff_seconds < $time_formats_seconds) && array_key_exists(++$k, $array_time_formats_seconds)) {
                continue;
            }

            preg_match_all('(\$([a-zA-z]))', $format, $matches, PREG_SET_ORDER);

            if (empty($matches)) {
                return $format;
            }

            $format_chars = array();

            foreach ($matches as $match) {
                $format_chars[] = $match[1];
            }

            if ($absolute) {
                $format_chars = $this->format(implode(',', $format_chars));
            } else {
                $format_chars = $diff->format('%'.implode(',%', $format_chars));
            }

            $format_chars = explode(',', $format_chars);

            $replaces = array();

            foreach ($format_chars as $k => $value) {
                $char = $matches[$k][0];

                if (array_key_exists($char.'_'.$value, $Config['i18n']['time_translations'])) {
                    $value = $Config->i18n['time_translations'][$char.'_'.$value];
                }

                $replaces[$char] = $value;
            }

            return strtr($format, $replaces);
        }
    }
}
