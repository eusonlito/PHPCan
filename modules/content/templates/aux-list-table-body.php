<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

defined('ANS') or die();

foreach ($rows as $row) {
    $rowspan = current($row['data']);
    $rowspan = $rowspan[0]['rows'];

    for ($n = 0; $n < $row['rows']; $n++) {

        echo '<tr>';

        if ($n == 0) {
            $Templates->render($actions_template, array(
                'table' => $row['table'],
                'id' => $row['id'],
                'related' => $row['related'],
                'rowspan' => $rowspan,
                'view' => $row['view']
            ));
        }

        foreach ($row['data'] as $cols) {
            if (!$cols[$n]) {
                continue;
            }

            $Templates->render($cols[$n]['templates']['index'], array(
                'template_content' => $cols[$n]['templates']['content'],
                'info' => $cols[$n]
            ));
        }

        echo '</tr>';
    }
}
