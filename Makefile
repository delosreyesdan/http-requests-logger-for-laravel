.PHONY: build up down test clean logs

build:
	docker-compose build app redis

up:
	docker-compose up -d app redis

down:
	docker-compose down

test: build
	docker-compose up --abort-on-container-exit --exit-code-from app

clean:
	docker-compose down -v --remove-orphans
	docker system prune -f

logs:
	docker-compose logs -f app
