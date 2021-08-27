#!make

install:
	@docker-compose run --rm composer install

test-database-connect:
	@docker-compose run --rm phpunit tests --filter DatabaseTest::testConnect
