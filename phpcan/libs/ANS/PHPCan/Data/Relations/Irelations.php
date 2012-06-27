<?php
/*
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

namespace ANS\PHPCan\Data\Relations;

defined('ANS') or die();

interface Irelations {
    public static function extend ($settings);
    public function removeDependent ();
    public function unrelateDependent ();
    public function selectConditions ($renamed_table0, $renamed_table1, $condition);
    public function relate ($operations_table0, $operations_table1, $options = array());
    public function unrelate ($operations_table0, $operations_table1, $options = array());
}
