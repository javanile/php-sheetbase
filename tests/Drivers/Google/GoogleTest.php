<?php

namespace Javanile\Sheetbase\Drivers\Tests;

use Javanile\Sheetbase\Database;
use Javanile\Sheetbase\Drivers\Google\Google;
use PHPUnit\Framework\TestCase;

class GoogleTest extends TestCase
{
    protected $driver;

    public function setUp()
    {
        $this->driver = new Google([
            'json_file' => getenv('GOOGLE_AUTH_JSON_FILE'),
            'database' => [ 'test' => getenv('GOOGLE_DATABASE') ],
            'cache' => false,
        ]);

        $this->driver->setDatabase('test');
        if (!$this->driver->hasTable('test')) {
            $this->driver->addTable('test');
        }
    }

    public function testAddDatabase()
    {}

    public function testSetDatabase($database)
    {}

    public function testHasDatabase($name)
    {}

    public function testAddTable()
    {}

    public function testHasTable()
    {}

    public function testSetTable()
    {}

    public function testGetTables()
    {
        $this->driver->setDatabase('test');
        $tables = $this->driver->getTables();
        $this->assertTrue(count($tables) > 0);
    }

    public function testSet()
    {}

    public function testGet()
    {}

    public function testSearch()
    {}

    public function testColumn()
    {}

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
        foreach ($data as $row) {
            $this->assertTrue(is_array($row));
            foreach ($row as $cell) {
                $this->assertFalse(is_array($cell));
            }
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
