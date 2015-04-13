<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

defined('ANS') or die();

$Shell = new \ANS\PHPCan\Shell;

if (!$Shell->commandExists('git')) {
    $Vars->message('The GIT command doesn\'t exists on this system', 'error');

    return false;
}

$Shell->cd(BASE_PATH);

$cmd = 'git pull';

$result = $Shell->exec($cmd);

if ($result === null) {
    $logs = BASE_PATH.$Config->phpcan_paths['logs'];

    $cmd .= ' > '.$logs.'git-update.log 2> '.$logs.'git-update.err';

    $Shell->exec($cmd);

    if (is_file($logs.'git-update.err')) {
        $Vars->message(__('Error executing GIT update: <p>%s</p>', file_get_contents($logs.'git-update.err')), 'error');
    } else {
        $Vars->message(__('Error executing GIT update, please check log file %s', $logs.'git-update.err'), 'error');
    }
}

return $result;
