<?php
defined('ANS') or die();

$Data->execute('check.php', true);

$columns = $Content->checkSelectedFields($Vars->var['show_columns']);

$Vars->setCookie('phpcan_show_columns', $columns, 3600 * 48);

redirect(path().get());
