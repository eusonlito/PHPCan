<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

defined('ANS') or die();
?>

<!DOCTYPE html>

<html lang="<?php echo $Vars->getLanguage(); ?>">
    <head>
        <?php include($Templates->file('head')); ?>
    </head>

    <body id="iframe">
        <?php if ($Vars->messageExists()): ?>
        <div id="message" class="<?php echo $Vars->messageType(); ?>">
            <?php echo $Vars->message(); ?>
        </div>
        <?php endif; ?>
        <section class="main">
            <?php include($Templates->file('content')); ?>
        </section>
    </body>
</html>
