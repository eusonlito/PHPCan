<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

defined('ANS') or die();

echo $Html->jsLinks(array(
    'http://maps.google.com/maps/api/js?sensor=false',
    'templates|formats/gmaps/edit/gmapsDraggable.js'
));

echo '<div class="map" id="'.$info['id_field'].'_gmap"></div>';

echo $Form->text(array(
    'variable' => $info['varname'].'[x]',
    'value' => $info['data']['x'],
    'id' => $info['id_field'].'_x',
    'class' => 'f25',
    'label_text' => __('Latitude').'<br>',
    'error' => $info['error']['x']
));

echo '<br>';
echo '<br>';

echo $Form->text(array(
    'variable' => $info['varname'].'[y]',
    'value' => $info['data']['y'],
    'id' => $info['id_field'].'_y',
    'class' => 'f25',
    'label_text' => __('Longitude').'<br>',
    'error' => $info['error']['y']
));

echo '<br>';
echo '<br>';

echo $Form->text(array(
    'variable' => $info['varname'].'[z]',
    'value' => $info['data']['z'],
    'id' => $info['id_field'].'_z',
    'class' => 'f25',
    'label_text' => __('Zoom').'<br>',
    'error' => $info['error']['z']
));

echo '<br>';
echo '<br>';

echo $Form->button(array(
    'text' => __('Search in map'),
    'data-icon' => 'search',
    'class' => 'secondary',
    'id' => $info['id_field'].'_s',
));
?>

<script type="text/javascript">
    $(document).ready(function () {
        gmapsDraggable('<?php echo $info['id_field']; ?>');
    });
</script>
