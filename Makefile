DOCKER_COMPOSE := COMPOSE_BAKE=true docker compose
DEV_CONTAINER=$(DOCKER_COMPOSE) exec -it pokio
DEV_CONTAINER_NON_INTERACTIVE=$(DOCKER_COMPOSE) exec pokio
RUN=composer


.PHONY: install
install:
	$(DEV_CONTAINER) env COMPOSER_ROOT_VERSION=dev-main $(RUN) install

.PHONY: tests
tests:
	$(DEV_CONTAINER) $(RUN) test

.PHONY: enter
enter:
	$(DEV_CONTAINER_NON_INTERACTIVE) bash

.PHONY: build
build:
	$(DOCKER_COMPOSE) build

.PHONY: build-clean
build-clean:
	$(DOCKER_COMPOSE) build --no-cache

.PHONY: up
up:
	$(DOCKER_COMPOSE) up -d

.PHONY: down
down:
	$(DOCKER_COMPOSE) down

.PHONY: lint
lint:
	$(DEV_CONTAINER) $(RUN) lint

.PHONY: types
types:
	$(DEV_CONTAINER) $(RUN) test:types

.PHONY: test
test:
	$(DEV_CONTAINER) $(RUN) test:unit
