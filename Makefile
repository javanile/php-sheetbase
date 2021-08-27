#!make

install:
	@docker-compose run --rm composer install

require-google-apiclient:
	@docker-compose run --rm composer require google/apiclient:^2.10

test-database-google-json-connect:
	@docker-compose run --rm phpunit tests --filter DatabaseTest::testGoogleJsonConnect

test-driver-google-add-database:
	@docker-compose run --rm phpunit tests --filter GoogleTest::testAddDatabase

test-driver-google-set-database:
	@docker-compose run --rm phpunit tests --filter GoogleTest::testSetDatabase

test-driver-google-add-table:
	@docker-compose run --rm phpunit tests --filter GoogleTest::testAddTable

test-driver-google-set-table:
	@docker-compose run --rm phpunit tests --filter GoogleTest::testSetTable

test-driver-google-get-tables:
	@docker-compose run --rm phpunit tests --filter GoogleTest::testGetTables

test-driver-google-insert:
	@docker-compose run --rm phpunit tests --filter GoogleTest::testInsert

test-driver-google-all:
	@docker-compose run --rm phpunit tests --filter GoogleTest::testAll

test-driver-google-set:
	@docker-compose run --rm phpunit tests --filter /GoogleTest::testSet$$/

test-driver-google-get:
	@docker-compose run --rm phpunit tests --filter /GoogleTest::testGet$$/

test-driver-google-get-row-count:
	@docker-compose run --rm phpunit tests --filter GoogleTest::testGetRowCount
