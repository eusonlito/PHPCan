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

<h1><?php echo $Html->a(MODULE_TITLE, path('')); ?></h1>

<nav>
    <?php
    if ($Session->logged()) {
        echo __('Hi, %s', $Session->user('name')).' ';
        echo $Html->a('('.__('Logout').')', path('').get('phpcan_action', 'logout'));
        echo ' | ';

        if (!$Vars->getPath(0, 'login')) {
            $available_modules = array_keys($Vars->getSceneConfig('modules'));

            foreach ($Session->user('modules') as $module) {
                if (in_array($module, $available_modules)) {
                    echo $Html->a($module, path(array('module' => $module, 'language' => false), '')).' | ';
                }
            }
        }
    }

    echo $Html->a(array(
        'text' => __('Go to web'),
        'href' => SCENE_WWW
    ));
    ?>
</nav>
