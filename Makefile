.PHONY: build test fresh up down logs clean

build:
	docker compose build

test:
	docker compose up --abort-on-container-exit --exit-code-from app

fresh: build test

up:
	docker compose up -d

down:
	docker compose down

logs:
	docker compose logs -f app

clean:
	docker compose down -v --remove-orphans
	docker system prune -f
