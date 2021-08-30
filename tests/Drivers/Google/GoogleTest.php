<?php

namespace Javanile\Sheetbase\Drivers\Tests;

use Javanile\Sheetbase\Drivers\Google\Google;
use PHPUnit\Framework\TestCase;

class GoogleTest extends TestCase
{
    protected $driver;

    public function setUp()
    {
        $this->driver = new Google([
            'json_file' => getenv('GOOGLE_AUTH_JSON_FILE'),
            'database' => [
                'test' => getenv('GOOGLE_DATABASE')
            ],
            'cache' => false,
            'zero_based' => false,
        ]);

        $this->driver->setDatabase('test');
        if (!$this->driver->hasTable('test')) {
            $this->driver->addTable('test');
        }
    }

    public function testAddDatabase()
    {
        $database = 'test-'.time();
        $spreadsheetId = $this->driver->addDatabase($database);

        $this->assertTrue(strlen($spreadsheetId) == 44);
    }

    public function testSetDatabase()
    {
        $newDatabase = 'test-'.time();
        $newTable = 'table-'.$newDatabase;

        $this->driver->setDatabase('test');
        $tables = $this->driver->getTables();
        $this->assertFalse(in_array($newTable, $tables));

        $this->driver->addDatabase($newDatabase);
        $this->driver->setDatabase($newDatabase);
        $this->driver->addTable($newTable);
        $tables = $this->driver->getTables();
        $this->assertTrue(in_array($newTable, $tables));

        $this->driver->setDatabase('test');
        $tables = $this->driver->getTables();
        $this->assertFalse(in_array($newTable, $tables));
    }

    public function testHasDatabase()
    {
        $this->assertTrue($this->driver->hasDatabase('test'));
        $this->assertFalse($this->driver->hasDatabase('random-database-name-'.time()));
    }

    public function testAddTable()
    {
        $newTable = 'table-'.time();
        $this->driver->setDatabase('test');
        $this->driver->addTable($newTable);
        $tables = $this->driver->getTables();
        $this->assertTrue(in_array($newTable, $tables));
    }

    public function testHasTable()
    {
        $this->driver->setDatabase('test');
        $this->assertTrue($this->driver->hasTable('test'));
    }

    public function testSetTable()
    {
        $newTable = 'table-'.time();
        $newValue1 = 'value-'.rand(1, 1000);
        $newValue2 = 'value-'.rand(1001, 2000);

        $this->driver->setDatabase('test');
        $this->driver->setTable('test');
        $this->driver->set(1, 1, $newValue1);

        $this->driver->addTable($newTable);
        $this->driver->setTable($newTable);
        $this->driver->set(1, 1, $newValue2);

        $this->driver->setTable('test');
        $this->assertEquals($newValue1, $this->driver->get(1, 1));

        $this->driver->setTable($newTable);
        $this->assertEquals($newValue2, $this->driver->get(1, 1));
    }

    public function testGetTables()
    {
        $this->driver->setDatabase('test');
        $tables = $this->driver->getTables();
        $this->assertTrue(count($tables) > 0);
    }

    public function testSet()
    {
        $this->driver->setDatabase('test');
        $this->driver->setTable('test');
        $this->assertEquals(1, $this->driver->set(0, 0, 'Hello World!'));
    }

    public function testGet()
    {
        $newValue = 'value-'.time();
        $this->driver->setDatabase('test');
        $this->driver->setTable('test');
        $this->assertEquals(1, $this->driver->set(0, 0, $newValue));
        $this->assertEquals($newValue, $this->driver->get(0, 0));
    }

    public function testSearch()
    {
        $this->driver->setDatabase('test');
        $this->driver->setTable('test');
        $newValues = [];
        for ($i = 1; $i <= 10; $i++) {
            $newValue = 'value-'.rand(0, 10000);
            $newValues[] = $newValue;
            $this->driver->set($i, 1, $newValue);
        }
        $column = $this->driver->search($newValues[3], 1);

        var_dump($column);
    }

    public function testColumn()
    {
        $this->driver->setDatabase('test');
        $this->driver->setTable('test');
        $newValues = [];
        for ($i = 1; $i <= 10; $i++) {
            $newValue = 'value-'.rand(0, 10000);
            $newValues[] = $newValue;
            $this->driver->set($i, 1, $newValue);
        }
    }

    public function testInsert()
    {
        $this->driver->setDatabase('test');
        $this->driver->setTable('test');
        $this->driver->insert(['A', 'B', 'C']);
    }

    public function testAll()
    {
        $this->driver->setDatabase('test');
        $this->driver->setTable('test');
        $data = $this->driver->all();

        $this->assertTrue(is_array($data));
        $rowIndex = 1;
        foreach ($data as $index => $row) {
            $this->assertEquals($rowIndex, $index);
            $this->assertTrue(is_array($row));
            $colIndex = 1;
            foreach ($row as $index => $cell) {
                $this->assertEquals($colIndex, $index);
                $this->assertFalse(is_array($cell));
                $colIndex++;
            }
            $rowIndex++;
        }
    }

    public function testGetRowCount()
    {
        $this->driver->setDatabase('test');
        $this->driver->setTable('test');
        $count = $this->driver->getRowCount();

        $this->assertTrue($count > 0);
    }

    public function testGetColCount()
    {
        $this->driver->setDatabase('test');
        $this->driver->setTable('test');
        $count = $this->driver->getRowCount();

        $this->assertTrue($count > 0);
    }
}
