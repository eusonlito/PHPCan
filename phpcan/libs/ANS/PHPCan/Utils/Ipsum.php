<?php
namespace ANS\PHPCan\Utils;

defined('ANS') or die();

class Ipsum
{
    private $settings = array();

    public function __construct ($autoglobal = '')
    {
        global $Debug, $Db;

        $this->Debug = $Debug;
        $this->Db = $Db;

        if ($autoglobal) {
            global $Config;

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

        $Faker = \Faker\Factory::create('es_ES');

        for ($i > 0; $i < $rows; ++$i) {
            $data = array();

            foreach ($fields as $field => $settings) {
                $settings = is_array($settings) ? $settings : array('format' => $settings);

                switch ($settings['format']) {
                    case 'boolean':
                        $data[$field] = rand(0, 1);
                        break;
                    case 'date':
                        $data[$field] = $Faker->dateTimeBetween('-1 year', '+1 year')->format('Y-m-d');
                        break;
                    case 'datetime':
                        $data[$field] = $Faker->dateTimeBetween('-1 year', '+1 year')->format('Y-m-d H:i:s');
                        break;
                    case 'email':
                        $data[$field] = $Faker->freeEmail;
                        break;
                    case 'enum':
                        $data[$field] = $settings['values'][array_rand($settings['values'])];
                        break;
                    case 'file':
                    case 'image':
                        $data[$field] = filePath('common|default/images/'.rand(1, 5).'.jpg');
                        break;
                    case 'html':
                        $data[$field] = '<p>'.implode('</p><p>', $Faker->paragraphs(rand(3, 6))).'</p>';
                        break;
                    case 'id_text':
                    case 'title':
                    case 'varchar':
                        $data[$field] = ucfirst(preg_replace('/[^a-z0-9\s]/', '', $Faker->sentence(rand(2, 5))));
                        break;
                    case 'integer':
                        $data[$field] = $Faker->randomNumber(rand(2, 5));
                        break;
                    case 'ip':
                        $data[$field] = $Faker->ipv4;
                        break;
                    case 'sort':
                        $data[$field] = $Faker->randomNumber(rand(1, 3));
                        break;
                    case 'text':
                        $data[$field] = $Faker->text;
                        break;
                    case 'url':
                        $data[$field] = $Faker->url;
                        break;
                }

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
}
