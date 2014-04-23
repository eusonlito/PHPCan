<?php
/**
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

namespace ANS\PHPCan\Data\Formats;

defined('ANS') or die();

class Relate_masive extends File implements Iformats
{
    public $format = 'relate_masive';

    public function check ($value)
    {
        $this->error = array();

        if (!$this->checkFile($value[''])) {
            return false;
        }

        if ($value['']['name'] && !preg_match('/\.zip$/i', $value['']['name'])) {
            $this->error[$subformat] = __('Only ZIP files are allowed for fields "%s".', __($this->name));
            return false;
        }

        return true;
    }

    public function afterSave (\ANS\PHPCan\Data\Db $Db, $values)
    {
        if (empty($values['new_value'][''])) {
            return parent::afterSave($Db, $values);
        }

        $settings = $this->settings[''];

        $tmp = sys_get_temp_dir().'/'.uniqid();

        mkdir($tmp, 0700);

        $zip = $this->getRealPath().$values['new_value'][''];

        shell_exec($settings['unzip'].' "'.$zip.'" -d "'.$tmp.'"');

        $File = new \ANS\PHPCan\Files\File;

        $contents = $File->listFolder($tmp);

        if (empty($contents)) {
            return true;
        }

        $relate = $settings['relate'];

        $current = $Db->select(array(
            'table' => $this->table,
            'fields' => array_keys($relate['fields']),
            'limit' => 1,
            'conditions' => array(
                'id' => $values['id']
            )
        ));

        foreach ($contents as $file) {
            if ($settings['valid_extensions']
            && !in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), $settings['valid_extensions'], true)) {
                continue;
            }

            $data = array($relate['file'] => $file);

            foreach ($relate['fields'] as $original => $target) {
                $data[$target] = $current[$original];
            }

            $Db->insert(array(
                'table' => $relate['table'],
                'data' => $data,
                'relate' => array(
                    array(
                        'table' => $this->table,
                        'limit' => 1,
                        'conditions' => array(
                            'id' => $current['id']
                        )
                    )
                )
            ));
        }

        return true;
    }

    public function settings ($settings)
    {
        parent::settings($settings);

        if (empty($settings['relate'])) {
            throw new \Exception('ZIP formats needs "relate" settings');
        }

        $unzip = trim(shell_exec('which unzip'));

        if (empty($unzip)) {
            throw new \Exception('ZIP formats needs "unzip" command installed');
        }

        $this->settings['']['relate'] = $settings['relate'];
        $this->settings['']['valid_extensions'] = (array)$settings['valid_extensions'];
        $this->settings['']['unzip'] = $unzip;

        return $this->settings;
    }
}
