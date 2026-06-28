.PHONY: setup up down restart bash logs migrate seed fresh test

setup:
	cp -n .env.example .env || true
	docker compose up -d --build
	@echo "API disponível em http://localhost:8000"

up:
	docker compose up -d

down:
	docker compose down

restart:
	docker compose restart app

bash:
	docker compose exec app bash

logs:
	docker compose logs -f app

migrate:
	docker compose exec app php artisan migrate

seed:
	docker compose exec app php artisan db:seed --force

fresh:
	docker compose exec app php artisan migrate:fresh --seed --force

tinker:
	docker compose exec app php artisan tinker

test:
	docker compose exec app php artisan test
