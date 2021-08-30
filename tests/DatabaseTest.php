<?php

namespace Javanile\Sheetbase\Tests;

use Javanile\Sheetbase\Database;
use PHPUnit\Framework\TestCase;

class DatabaseTest extends TestCase
{
    public function testGoogleJsonConnect()
    {
        $db = new Database([
            'provider' => 'google',
            'json_file' => getenv('GOOGLE_AUTH_JSON_FILE'),

            'database' => [
                'test' => getenv('GOOGLE_DATABASE'),
            ],

            'cache' => false,
        ]);

        #$db->addDatabase('new-database');

        $db->setDatabase('test');
        if (!$db->hasTable('test')) {
            $db->addTable('test');
        }

        //$this->assertEquals($message, $mysqlImport->run());
        //$this->assertEquals(2, $mysqlImport->getExitCode());
    }
}
