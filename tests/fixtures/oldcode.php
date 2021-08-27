<?php

##
error_reporting(E_ALL);
ini_set('display_errors',1);

##
require_once __DIR__.'/GoogleDB_Drive.php';
require_once __DIR__.'/GoogleDB_Cache.php';

##
require_once __DIR__.'/lib/google-api-php-client/src/Google/autoload.php';
require_once __DIR__.'/lib/google-api-php-client/src/Google/Spreadsheet/Spreadsheet.php';
require_once __DIR__.'/lib/google-api-php-client/src/Google/Spreadsheet/SpreadsheetService.php';
require_once __DIR__.'/lib/google-api-php-client/src/Google/Spreadsheet/SpreadsheetFeed.php';
require_once __DIR__.'/lib/google-api-php-client/src/Google/Spreadsheet/ServiceRequestFactory.php';
require_once __DIR__.'/lib/google-api-php-client/src/Google/Spreadsheet/Exception.php';
require_once __DIR__.'/lib/google-api-php-client/src/Google/Spreadsheet/UnauthorizedException.php';
require_once __DIR__.'/lib/google-api-php-client/src/Google/Spreadsheet/ServiceRequestInterface.php';
require_once __DIR__.'/lib/google-api-php-client/src/Google/Spreadsheet/DefaultServiceRequest.php';
require_once __DIR__.'/lib/google-api-php-client/src/Google/Spreadsheet/Util.php';
require_once __DIR__.'/lib/google-api-php-client/src/Google/Spreadsheet/WorksheetFeed.php';
require_once __DIR__.'/lib/google-api-php-client/src/Google/Spreadsheet/Worksheet.php';
require_once __DIR__.'/lib/google-api-php-client/src/Google/Spreadsheet/CellFeed.php';
require_once __DIR__.'/lib/google-api-php-client/src/Google/Spreadsheet/CellEntry.php';
require_once __DIR__.'/lib/google-api-php-client/src/Google/Spreadsheet/ListFeed.php';
