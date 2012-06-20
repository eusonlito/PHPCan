<?php defined('ANS') or die(); ?>

<title><?php echo MODULE_TITLE; ?></title>

<meta http-equiv="Content-type" content="text/html; charset=utf-8" />

<!-- Site info -->
<meta name="author" content="A navalla suíza - http://anavallasuiza.com" />
<meta name="generator" content="phpCan <?php echo PHPCAN_VERSION; ?>" />

<!-- Google Chrome Frame -->
<!--[if lt IE 9]>
<meta http-equiv="X-UA-Compatible" content="IE=Edge;chrome=1">
<![endif]-->

<?php echo $Html->jsLink('common|modernizr/modernizr.min.js'); ?>

<script type="text/javascript">
    paths = {
        base: "<?php echo MODULE_WWW; ?>"
    };
</script>

<?php include($Templates->file('sub-head')); ?>

<?php
echo $Html->cssLinks($Config->templates['css']);
echo $Html->jsLinks($Config->templates['js']);
