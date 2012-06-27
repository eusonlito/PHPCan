<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

namespace ANS\PHPCan;

defined('ANS') or die();

class Shell
{
    private $Debug;
    private $command_register = array();

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
     * public function cd ([string $directory])
     *
     * return string
     */
    public function cd ($directory = null)
    {
        if (is_null($directory)) {
            $directory = BASE_PATH;
        }

        return $this->exec('cd '.escapeshellarg($directory));
    }

    /**
     * public function exec (string/array $command, [bool $escape])
     *
     * return mixed
     */
    public function exec ($command, $escape = false)
    {
        $command = (array) $command;

        if ($escape) {
            foreach ($command as &$cmd) {
                $cmd = escapeshellcmd($cmd);
            }
        }

        $this->command_register = array_merge($this->command_register, $command);

        return shell_exec(implode(';', $command));
    }

    /**
     * function commandRegister ([int $offset], [int $length])
     *
     * Return executed commands
     *
     * return array
     */
    public function commandRegister ($offset = 0, $length = null)
    {
        if ($offset || $length) {
            return array_slice($this->command_register, $offset, $length, true);
        }

        return $this->command_register;
    }

    /**
     * public function commandExists (string $command)
     *
     * return string
     */
    public function commandExists ($command)
    {
        if (!$command || !$this->exec('which '.escapeshellcmd($command))) {
            return false;
        }

        return true;
    }
}
