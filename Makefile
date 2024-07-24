$(shell (if [ ! -e .env ]; then cp default.env .env; fi))
include .env
export

RUN_ARGS = $(filter-out $@,$(MAKECMDGOALS))

include .make/utils.mk
include .make/docker-compose-shared-services.mk

.PHONY: install
install: erase build start wait## clean current environment, recreate dependencies and spin up again;

.PHONY: install-lite
install-lite: build start

.PHONY: start
start: ##up-services ## spin up environment docker-compose up -d
	docker-compose up --build -d

.PHONY: stop
stop: ## stop environment
	docker-compose stop

.PHONY: start services
start-services: shared-service-up ## up shared services

.PHONY: stop services
stop-services: shared-service-stop ## stop shared services

.PHONY: start-all
start-all: start start-services ## start full project environment

.PHONY: stop-all
stop-all: stop ## stop full project environment

.PHONY: remove
remove: ## remove project docker containers
	docker-compose rm -v -f

.PHONY: erase
erase: stop-all remove docker-remove-volumes ## stop and delete containers, clean volumes

.PHONY: build
build: ## build environment and initialize composer and project dependencies
	docker-compose build --pull

.PHONY: setup
setup: setup-enqueue ## setup-db build environment and initialize composer and project dependencies

.PHONY: clean
clean: ## Clear build vendor report folders
	rm -rf build/ vendor/ var/

.PHONY: migrate
migrate:
	docker-compose exec -T dao_mariadb sh /tmp/initdb/simple-db.sh
	make migrate-pgsql

.PHONY: migrate-pgsql
migrate-pgsql:
	docker-compose exec -T dao_postgres sh /tmp/initdb/simple-db.sh

.PHONY: tests
tests: migrate migrate-pgsql run-tests

.PHONY: run-tests
run-tests:
	docker-compose -f $(CWD)/docker-compose.yml run --rm --no-deps dao_php sh -lc "php ./vendor/phpunit/phpunit/phpunit -c ./tests/phpunit.xml --testdox --stderr --colors"
