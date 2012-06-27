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

<html>
    <head>
        <title><?php __e('Ups... an error has occurred'); ?></title>

        <meta http-equiv="Content-type" content="text/html; charset=utf-8" />

        <!-- Site info -->
        <meta name="author" content="A navalla suíza - http://anavallasuiza.com" />
        <meta name="description" content="">
        <meta name="generator" content="phpCan <?php echo PHPCAN_VERSION; ?>" />

        <!-- Google Chrome Frame -->
        <!--[if lt IE 9]>
        <meta http-equiv="X-UA-Compatible" content="IE=Edge;chrome=1">
        <![endif]-->

        <!-- Favicon -->
        <link rel="shortcut icon" href="<?php echo path(''); ?>favicon.ico">

        <!--[if lt IE 9]>
        <script src="//html5shim.googlecode.com/svn/trunk/html5.js"></script>
        <![endif]-->
    </head>

    <body>
        <h1><?php __e('Ups... an error has occurred'); ?></h1>

        <p><?php __e(' We are gathering all the data about this error to try to sort it out ASAP.'); ?></p>

        <?php if ($this->settings['print']) { ?>
        <div class="fatal-error">
            <?php $this->e($error); ?>
        </div>
        <?php } ?>
    </body>
</html>
