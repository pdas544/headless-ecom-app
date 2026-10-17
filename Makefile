.PHONY: install start start-infra build logs test analyse cs shell db-shell redis-shell reload

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

analyse:
	vendor/bin/phpstan analyse app config routes public --level=8

cs:
	vendor/bin/phpcs --standard=PSR12 app config routes public tests

shell:
	docker compose exec app sh

db-shell:
	docker compose exec db psql -U ecom -d ecom

redis-shell:
	docker compose exec cache redis-cli
