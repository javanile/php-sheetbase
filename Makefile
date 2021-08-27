#!make

install:
	@docker-compose run --rm composer install

require-google-apiclient:
	@docker-compose run --rm composer require google/apiclient:^2.10

test-database-connect:
	@docker-compose run --rm phpunit tests --filter DatabaseTest::testConnect
