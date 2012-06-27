<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*
* Copyright (c) 2003 Danilo Segan <danilo@kvota.net>.
* Copyright (c) 2005 Nico Kaiser <nico@siriux.net>
* Copyright (c) 2010 A Navalla Suiza <idc@anavallasuiza.com>
*
* This file is part of PHP-gettext.
*
* PHP-gettext is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
* (at your option) any later version.
*
* PHP-gettext is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with PHP-gettext; if not, write to the Free Software
* Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
*/

namespace ANS\PHPCan\I18n;

defined('ANS') or die();

class Gettext
{
    //public:
    public $error = 0; // public variable that holds error code (0 if no error)

    //private:
    private $BYTEORDER = 0; // 0: low endian, 1: big endian
    private $STREAM = NULL;
    private $originals = NULL; // offset of original table
    private $translations = NULL; // offset of translation table
    private $total = 0; // total string count
    private $table_originals = NULL; // table for original strings (offsets)
    private $table_translations = NULL; // table for translated strings (offsets)
    private $cache_translations = array(); // original -> translation mapping

    const MAGIC1 = -1794895138;
    const MAGIC2 = -569244523;
    const MAGIC3 = 2500072158;

    /**
     * public function __construct ([string $time], [int $timezone])
     *
     * return string
     */
    public function __construct ($time = 'now', $timezone = NULL)
    {
        global $Debug;

        $this->Debug = $Debug;
    }

    private function readInt ()
    {
        $read = $this->STREAM->read(4);

        if ($read === false) {
            return false;
        }

        if ($this->BYTEORDER == 0) {
            $read = unpack('V', $read); // low endian
        } else {
            $read = unpack('N', $read); // big endian
        }

        return array_shift($read);
    }

    private function readIntArray ($count)
    {
        if ($this->BYTEORDER == 0) {
            // low endian
            return unpack('V'.$count, $this->STREAM->read(4 * $count));
        } else {
            // big endian
            return unpack('N'.$count, $this->STREAM->read(4 * $count));
        }
    }

    public function load ($file)
    {
        if (!is_file($file)) {
            return false;
        }

        $Reader = new \ANS\PHPCan\I18n\CachedFileReader($file);

        if (!$Reader || isset($Reader->error) ) {
            return false;
        }

        $this->STREAM = $Reader;
        $magic = $this->readInt();

        if (($magic == self::MAGIC1) || ($magic == self::MAGIC3)) { // to make sure it works for 64-bit platforms
            $this->BYTEORDER = 0;
        } elseif ($magic == (self::MAGIC2 & 0xFFFFFFFF)) {
            $this->BYTEORDER = 1;
        } else {
            $this->error = 1; // not MO file

            return false;
        }

        $this->readInt();

        $this->total = $this->readInt();
        $this->originals = $this->readInt();
        $this->translations = $this->readInt();

        $this->STREAM->seekto($this->originals);
        $this->table_originals = $this->readIntArray($this->total * 2);
        $this->STREAM->seekto($this->translations);
        $this->table_translations = $this->readIntArray($this->total * 2);

        for ($i = 0; $i < $this->total; $i++) {
            $this->STREAM->seekto($this->table_originals[$i * 2 + 2]);
            $original = $this->STREAM->read($this->table_originals[$i * 2 + 1]);

            if ($original) {
                $this->STREAM->seekto($this->table_translations[$i * 2 + 2]);
                $translation = $this->STREAM->read($this->table_translations[$i * 2 + 1]);
                $this->cache_translations[$original] = $translation;
            }
        }

        unset($this->table_originals, $this->table_translations, $this->STREAM, $Reader);
    }

    public function translate ($string, $null = false)
    {
        if (isset($this->cache_translations[$string])) {
            return $this->cache_translations[$string];
        } elseif ($null === true) {
            return null;
        } else {
            return $string;
        }
    }
}

class CachedFileReader
{
    public $_pos;
    public $_str;

    public function __construct ($filename)
    {
        if (is_file($filename)) {
            $length = filesize($filename);
            $fd = fopen($filename,'rb');

            if (!$fd) {
                $this->error = 3; // Cannot read file, probably permissions

                return false;
            }

            $this->_str = fread($fd, $length);

            fclose($fd);
        } else {
            $this->error = 2; // File doesn't exist

            return false;
        }
    }

    public function StringReader ($str='')
    {
        $this->_str = $str;
        $this->_pos = 0;
    }

    public function read ($bytes)
    {
        $data = substr($this->_str, $this->_pos, $bytes);
        $this->_pos += $bytes;

        if (strlen($this->_str) < $this->_pos) {
            $this->_pos = strlen($this->_str);
        }

        return $data;
    }

    public function seekto ($pos)
    {
        $this->_pos = $pos;

        if (strlen($this->_str) < $this->_pos) {
            $this->_pos = strlen($this->_str);
        }

        return $this->_pos;
    }

    public function currentpos ()
    {
        return $this->_pos;
    }

    public function length ()
    {
        return strlen($this->_str);
    }
}
