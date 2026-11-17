.PHONY: install start start-infra build logs test test-integration analyse cs shell db-shell redis-shell reload migrate migrate-rollback migrate-test db-test-reset seed

install:
	composer install
	cp -n .env.example .env || true

start-infra:
	docker compose up -d db cache
	docker compose ps

build:
	docker compose build app

start:
	docker compose up -d
	docker compose ps

reload:
	docker compose exec -T web nginx -s reload

stop:
	docker compose down

restart:
	docker compose down && docker compose up -d --build

logs:
	docker compose logs -f app web

test:
	docker compose exec -T app vendor/bin/phpunit --testsuite=Unit || vendor/bin/phpunit --testsuite=Unit

test-integration:
	$(MAKE) migrate-test
	docker compose exec -T app vendor/bin/phpunit -c phpunit.integration.xml

analyse:
	vendor/bin/phpstan analyse app config routes public database phinx.php --level=8

cs:
	vendor/bin/phpcs app config routes public tests database phinx.php

migrate:
	docker compose exec -T app vendor/bin/phinx migrate -e development

migrate-rollback:
	docker compose exec -T app vendor/bin/phinx rollback -e development

migrate-test:
	@docker compose exec -T db psql -U ecom -d postgres -tAc "SELECT 1 FROM pg_database WHERE datname = 'ecom_test'" | grep -q 1 || docker compose exec -T db createdb -U ecom ecom_test
	docker compose exec -T app vendor/bin/phinx migrate -e test

db-test-reset:
	docker compose exec -T db dropdb -U ecom --if-exists ecom_test
	$(MAKE) migrate-test

seed:
	docker compose exec -T app vendor/bin/phinx seed:run -e development

shell:
	docker compose exec app sh

db-shell:
	docker compose exec db psql -U ecom -d ecom

redis-shell:
	docker compose exec cache redis-cli
