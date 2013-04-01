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

if (!$Shell->commandExists('svn')) {
    $Vars->message('The SVN command doesn\'t exists on this system', 'error');

    return false;
}

if (!$Config->svn['username'] || !$Config->svn['password']) {
    $Vars->message('No username or password configured', 'error');

    return false;
}

$Shell->cd(BASE_PATH);

$cmd = 'svn update --no-auth-cache --trust-server-cert --non-interactive --force --username '.$Config->svn['username'].' --password '.$Config->svn['password'].' --accept theirs-full';

$result = $Shell->exec($cmd);

if ($result === null) {
    $logs = BASE_PATH.$Config->phpcan_paths['logs'];

    $cmd .= ' > '.$logs.'svn-update.log 2> '.$logs.'svn-update.err';

    $Shell->exec($cmd);

    if (is_file($logs.'svn-update.err')) {
        $Vars->message(__('Error executing SVN update: <p>%s</p>', file_get_contents($logs.'svn-update.err')), 'error');
    } else {
        $Vars->message(__('Error executing SVN update, please check log file %s', $logs.'svn-update.err'), 'error');
    }
}

return $result;
