#!make

install:
	@docker-compose run --rm composer install

require-google-apiclient:
	@docker-compose run --rm composer require google/apiclient:^2.10

test-database-google-json-connect:
	@docker-compose run --rm phpunit tests --filter DatabaseTest::testGoogleJsonConnect

test-driver-google-get-tables:
	@docker-compose run --rm phpunit tests --filter GoogleTest::testGetTables

test-driver-google-insert:
	@docker-compose run --rm phpunit tests --filter GoogleTest::testInsert

test-driver-google-all:
	@docker-compose run --rm phpunit tests --filter GoogleTest::testAll

test-driver-google-get-row-count:
	@docker-compose run --rm phpunit tests --filter GoogleTest::testGetRowCount
