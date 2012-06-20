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

<header>
    <?php if ($Data->actions['svn-update'] !== null): ?>
    <h1><?php __e('SVN Update'); ?>:</h1>
    <?php endif; ?>
</header>

<article>
    <?php if ($Data->actions['svn-update'] !== null): ?>
    <pre><?php echo $Data->actions['svn-update']; ?></pre>
    <?php endif; ?>
</article>
