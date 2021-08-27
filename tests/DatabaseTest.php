<?php

namespace Javanile\Sheetbase\Tests;

use Javanile\Sheetbase\Database;
use PHPUnit\Framework\TestCase;

class DatabaseTest extends TestCase
{
    public function testConnect()
    {
        $db = new Database([
            'client_id' => getenv('GOOGLE_CLIENT_ID'),
            'email_app' => getenv('GOOGLE_EMAIL_APP'),
            'p12_file' => getenv('GOOGLE_P12_FILE'),

            'database' => [
                'test' => getenv('GOOGLE_DATABASE'),
            ],

            'cache' => false,
        ]);

        //$this->assertEquals($message, $mysqlImport->run());
        //$this->assertEquals(2, $mysqlImport->getExitCode());
    }
}
