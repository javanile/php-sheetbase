<?php

namespace Javanile\MysqlImport\Tests;

use Javanile\Sheetbase\Database;
use PHPUnit\Framework\TestCase;

class DatabaseTest extends TestCase
{
    public function testConnect()
    {
        $file = __DIR__.'/fixtures/database.sql';
        $message = "Unknown option '-kWrong'.";

        $mysqlImport = new MysqlImport(['MYSQL_ROOT_PASSWORD' => 'secret'], [$file, '-kWrong']);
        $this->assertEquals($message, $mysqlImport->run());
        $this->assertEquals(2, $mysqlImport->getExitCode());

        $mysqlImport = new MysqlImport([], ['-psecret', '-kWrong', $file]);
        $this->assertEquals($message, $mysqlImport->run());
        $this->assertEquals(2, $mysqlImport->getExitCode());
    }
}
