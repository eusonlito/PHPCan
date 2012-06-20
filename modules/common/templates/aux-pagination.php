<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

defined('ANS') or die();

if (!$pagination) {
    return false;
}
?>

<nav class="pagination">
    <p><?php __e('%s results have been found', $pagination['total']); ?></p>

    <?php if ($pagination['total_pages'] > 1): ?>
    <ul class="tabs">
        <?php
        if ($pagination['page'] == 1) {
            echo '<li><span>'.__('First').'</span></li><li><span>'.__('Previous').'</span></li>';
        } else {
            echo '<li>'.$Html->a(__('First'), path().get('page', 1)).'</li>';
            echo '<li>'.$Html->a(__('Previous'), path().get('page', $pagination['page'] - 1)).'</li>';
        }

        for ($n = $pagination['first']; $n <= $pagination['last']; $n++) {
            if ($pagination['page'] == $n) {
                echo '<li><span>'.$n.'</span></li>';
            } else {
                echo '<li>'.$Html->a($n, path().get('page', $n)).'</li>';
            }
        }

        if ($pagination['page'] >= $pagination['total_pages']) {
            echo '<li><span>'.__('Next').'</span></li><li><span>'.__('Last').'</span></li>';
        } else {
            echo '<li>'.$Html->a(__('Next'), path().get('page', $pagination['page'] + 1)).'</li>';
            echo '<li>'.$Html->a(__('Last'), path().get('page', $pagination['total_pages'])).'</li>';
        }
        ?>
    </ul>
    <?php endif; ?>
</nav>
