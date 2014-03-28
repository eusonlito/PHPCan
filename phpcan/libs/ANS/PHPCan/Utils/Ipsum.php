<?php
namespace ANS\PHPCan\Utils;

defined('ANS') or die();

class Ipsum
{
    private $settings = array();

    public function __construct ($autoglobal = '')
    {
        global $Debug, $Db, $Config;

        $this->Debug = $Debug;
        $this->Db = $Db;
        $this->Config = $Config;

        if ($autoglobal) {
            $Config->config['autoglobal'][] = $autoglobal;
        }
    }

    public function setSettings ($settings)
    {
        $relations = array();

        if ($settings['relations']) {
            foreach ($settings['relations'] as $relation) {
                list($table1, $table2) = explode(' ', $relation['tables']);
                list($mode1, $mode2) = explode(' ', $relation['mode']);

                $relations[$table1][$table2.$relation['name']] = array(
                    'table' => $table2,
                    'name' => $relation['name'],
                    'mode' => $mode1.' '.$mode2
                );

                $relations[$table2][$table1.$relation['name']] = array(
                    'table' => $table1,
                    'name' => $relation['name'],
                    'mode' => $mode2.' '.$mode1
                );
            }
        }

        $settings['relations'] = $relations;

        $this->settings = $settings;
    }

    public function fill ($table, $rows = 0)
    {
        if (is_array($table)) {
            foreach ($table as $each) {
                self::fill($each[0], $each[1]);
            }

            return;
        }

        $rows = intval($rows);

        if (empty($this->settings['tables'][$table]) || (intval($rows) < 1)) {
            return false;
        }

        if ($this->settings['truncate']) {
            $this->Db->query('TRUNCATE '.$table.';');
        }

        $fields = $this->settings['tables'][$table];

        for ($i > 0; $i < $rows; ++$i) {
            $data = array();

            foreach ($fields as $field => $settings) {
                $settings = is_array($settings) ? $settings : array('format' => $settings);

                if ($settings['languages']) {
                    if ($settings['languages'] === 'all') {
                        $settings['languages'] = array_keys($this->Config->languages['availables']);
                    }

                    foreach ((array)$settings['languages'] as $language) {
                        $data[$field.'-'.$language] = $this->getFaker($settings, $language);
                    }

                    continue;
                }

                $data[$field] = $this->getFaker($settings);

                if ($settings['required'] && empty($data[$field])) {
                    $data = array();
                    break;
                }
            }

            if ($data) {
                $this->Db->insert(array(
                    'table' => $table,
                    'data' => $data
                ));
            }
        }
    }

    public function relate ($relations)
    {
        $processed = array();

        foreach ($relations as $tables) {
            $table1 = $tables[0];
            $table2 = $tables[1];
            $name = $tables[2];

            $relation = $this->settings['relations'][$table1][$table2.$name];

            if (empty($relation)) {
                continue;
            }

            $code = implode('', $tables);

            if (empty($processed[$code])) {
                $this->Db->unrelate(array(
                    'name' => $name,
                    'tables' => array(
                        array(
                            'table' => $table1,
                            'conditions' => 'all'
                        ),
                        array(
                            'table' => $table2,
                            'conditions' => 'all'
                        )
                    )
                ));
            }

            $processed[$code] = true;

            $rows1 = $this->Db->select(array(
                'table' => $table1,
                'fields' => 'id'
            ));

            $rows2 = $this->Db->select(array(
                'table' => $table2,
                'fields' => 'id'
            ));

            if (in_array($relation['mode'], array('1 x', 'x x'))) {
                for ($i = 0, $max = rand(0, 3); $i <= $max; $i++) {
                    $rows1 = array_merge($rows1, $rows1);
                }
            }

            foreach ($rows1 as $row) {
                $this->Db->relate(array(
                    'name' => $name,
                    'tables' => array(
                        array(
                            'table' => $table1,
                            'conditions' => array(
                                'id' => $row['id']
                            )
                        ),
                        array(
                            'table' => $table2,
                            'conditions' => array(
                                'id' => $rows2[array_rand($rows2)]['id']
                            )
                        )
                    )
                ));
            }
        }
    }

    private function getFaker ($settings, $lang = 'es_ES')
    {
        if (!strstr($lang, '_')) {
            $lang = strtolower($lang).'_'.strtoupper($lang);
        }

        try {
            $Faker = \Faker\Factory::create($lang);
        } catch (\Exception $e) {
            $Faker = \Faker\Factory::create('es_ES');
        }

        switch ($settings['format']) {
            case 'boolean':
                return rand(0, 1);
            case 'date':
                return $Faker->dateTimeBetween('-1 year', '+1 year')->format('Y-m-d');
            case 'datetime':
                return $Faker->dateTimeBetween('-1 year', '+1 year')->format('Y-m-d H:i:s');
            case 'email':
                return alphaNumeric($Faker->email, '@.-');
            case 'enum':
                return $settings['values'][array_rand($settings['values'])];
            case 'file':
            case 'image':
                return filePath('common|default/images/'.rand(1, 5).'.jpg');
            case 'float':
                list($integer, $decimal) = explode(',', $settings['length_max'] ?: '8,2');

                $min = $settings['value_min'] ? intval($settings['value_min']) : 0;

                if ($settings['value_max']) {
                    $max = intval($settings['value_max']);
                } else {
                    $max = str_replace(9, $integer);
                }

                return $Faker->randomFloat($decimal, $min, $max);
            case 'gmaps':
                $longitude = (float)42.759113;
                $latitude = (float)-7.838745;
                $radius = rand(1, 100);

                $lng_min = $longitude - $radius / abs(cos(deg2rad($latitude)) * 69);
                $lng_max = $longitude + $radius / abs(cos(deg2rad($latitude)) * 69);
                $lat_min = $latitude - ($radius / 69);
                $lat_max = $latitude + ($radius / 69);

                return array(
                    'x' => (rand($lng_min * 10000000, $lng_max * 10000000) / 10000000),
                    'y' => (rand($lat_min * 10000000, $lat_max * 10000000) / 10000000),
                    'z' => rand(5, 13)
                );
            case 'html':
                return '<p>'.implode('</p><p>', $Faker->paragraphs(rand(3, 6))).'</p>';
            case 'id_text':
            case 'title':
            case 'varchar':
                return ucfirst(preg_replace('/[^a-z0-9\s]/', '', $Faker->sentence(rand(2, 5))));
            case 'integer':
                return $Faker->randomNumber(rand(2, 5));
            case 'ip':
                return $Faker->ipv4;
            case 'sort':
                return $Faker->randomNumber(rand(1, 3));
            case 'text':
                return $Faker->text;
            case 'url':
                return $Faker->url;
        }
    }
}
