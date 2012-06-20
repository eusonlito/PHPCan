<?php defined('ANS') or die(); ?>

<title><?php echo $Html->meta('title', null, false); ?></title>

<meta http-equiv="Content-type" content="text/html; charset=utf-8" />

<!-- Site info -->
<meta name="author" content="A navalla suíza - http://anavallasuiza.com" />
<meta name="description" content="">
<meta name="generator" content="phpCan <?php echo PHPCAN_VERSION; ?>" />

<!-- Google Chrome Frame -->
<!--[if lt IE 9]>
<meta http-equiv="X-UA-Compatible" content="IE=Edge;chrome=1">
<![endif]-->

<!-- Mobile devices -->
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- Favicon -->
<link rel="shortcut icon" href="<?php echo path(''); ?>favicon.ico">
<link rel="apple-touch-icon" href="<?php echo path(''); ?>apple-touch-icon.png">

<!--[if lt IE 9]>
<script src="//html5shim.googlecode.com/svn/trunk/html5.js"></script>
<![endif]-->

<!-- Css files -->
<?php echo $Html->cssLinks($Config->templates['css']); ?>

<!-- Javascript files -->
<?php echo $Html->jsLinks($Config->templates['js']);
