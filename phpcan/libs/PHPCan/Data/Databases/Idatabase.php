<?php
/*
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

namespace PHPCan\Data\Databases;

defined('ANS') or die();

interface Idatabase {
    public function select ($select);
    public function insert ($data);
    public function replace ($data);
    public function update ($data);
    public function delete ($data);

    public function renameField ($table, $from, $to, $type);

    public function escapeString ($values);

    public function getSchemaDifferences ($tables);
}
