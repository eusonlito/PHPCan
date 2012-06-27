<?php
/**
* phpCan - http://idc.anavallasuiza.com/phpcan
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

namespace ANS\PHPCan\Users;

defined('ANS') or die();

class Acl
{
    private $permissions;
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
    }

    /**
     * public function check (string $mode, string $code)
     *
     * Check permission data
     */
    public function check ($mode, $code)
    {
        return $this->permissions[$mode][$code];
    }

    /**
     * public function setPermission (string $mode, [string $code], [mixed $value])
     *
     * Set permissions data
     */
    public function setPermission ($mode, $code = '', $value = '')
    {
        if (is_array($mode)) {
            foreach ($mode as $each) {
                $this->permissions[$each['mode']][$each['code']] = $each['enabled'];
            }

            return;
        }

        $this->permissions[$mode][$code] = $value;
    }

    /**
     * public function getAllPermissions (void)
     *
     * Get all permissions
     */
    public function getAllPermissions ()
    {
        return $this->permissions;
    }
}
