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

    <body id="html" class="<?php echo $Data->body_class; ?>">
        <div class="top">
            <div class="wrapper">
                <?php if ($Vars->messageExists()): ?>
                <div id="message" class="<?php echo $Vars->messageType(); ?>">
                    <?php echo $Vars->message(); ?>
                </div>
                <?php endif; ?>

                <header class="main">
                    <?php include($Templates->file('header')); ?>
                </header>

                <nav class="main">
                    <?php include($Templates->file('navigation')); ?>
                </nav>
            </div>
        </div>

        <section class="wrapper main">
            <?php include($Templates->file('content')); ?>
        </section>

        <footer class="main">
            <?php include($Templates->file('footer')); ?>
        </footer>
    </body>
</html>
