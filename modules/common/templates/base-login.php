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

    <body id="html" class="login">
        <?php if ($Vars->messageExists()) { ?>
            <div id="message" class="<?php echo $Vars->messageType(); ?>">
                <?php echo $Vars->message(); ?>
            </div>
        <?php } ?>

        <div class="wrapper">
            <header class="main">
                <?php include($Templates->file('header')); ?>
            </header>

            <section class="main">
                <form action="<?php echo path(); ?>" method="post">
                    <h1><?php __e('Please, login to enter in the Admin area:'); ?></h1>

                    <fieldset>
                        <?php echo $Form->hidden(getenv('HTTP_REFERER'), 'referer'); ?>

                        <p><?php
                        echo $Form->text(array(
                            'name' => 'login[user]',
                            'autofocus' => true,
                            'label_text' => __('User')
                        ));
                        ?></p>

                        <p><?php
                        echo $Form->password(array(
                            'name' => 'login[password]',
                            'label_text' => __('Password'),
                        ));
                        ?></p>

                        <p><?php
                        echo $Form->submit(array(
                            'name' => 'phpcan_action[login]',
                            'value' => __('Login')
                        ));
                        ?></p>
                    </fieldset>
                </form>
            </section>

            <footer class="main">
                <?php include($Templates->file('footer')); ?>
            </footer>
        </div>
    </body>
</html>
