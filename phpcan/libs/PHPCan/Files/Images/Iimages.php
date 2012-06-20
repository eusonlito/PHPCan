<?php
/*
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

namespace PHPCan\Files\Images;

defined('ANS') or die();

interface Iimages {
    public function load ($image);
    public function unload ();
    public function save ($filename = '');
    public function resize ($width, $height = 0, $enlarge = false);
    public function crop ($width, $height, $x = 0, $y = 0);
    public function flip ();
    public function flop ();
    public function zoomCrop ($width, $height);
    public function show ($header = true);
    public function rotate ($degrees, $background = null);
    public function merge ($image, $x = 0, $y = 0);
    public function convert ($format);
    public function getContents ();
}
