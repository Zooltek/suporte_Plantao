## =============================================================================
## Makefile — atalhos para o ambiente Docker do projeto Suporte (Laravel 12)
## =============================================================================

# BuildKit habilita multi-stage cache mounts (essencial para o novo Dockerfile)
export DOCKER_BUILDKIT ?= 1
export COMPOSE_DOCKER_CLI_BUILD ?= 1

# DB_EXPOSE=1 publica a porta do MySQL no host (overlay opt-in). Sem isso, o
# banco fica acessível apenas pela rede interna — evita conflito na porta 3306.
DBPORT_OVERLAY       := $(if $(DB_EXPOSE),-f docker-compose.dbport.yml,)
DEV_COMPOSE          := docker compose -f docker-compose.yml -f docker-compose.dev.yml $(DBPORT_OVERLAY)
DEV_BROWSER_COMPOSE  := COMPOSE_PROFILES=browser docker compose -f docker-compose.yml -f docker-compose.dev.yml $(DBPORT_OVERLAY)
LAN_COMPOSE          := $(DEV_COMPOSE)
PROD_COMPOSE         := docker compose -f docker-compose.yml -f docker-compose.prod.yml
UP_ARGS              := up -d --build --remove-orphans

.DEFAULT_GOAL := help

## --- DEV ---------------------------------------------------------------------

.PHONY: dev
dev: guard-optional-app-public-url ## Sobe o ambiente padrão de desenvolvimento e homologação compartilhada
	$(DEV_COMPOSE) $(UP_ARGS)

.PHONY: lan
lan: dev ## Alias legado: usa o mesmo fluxo do make dev

.PHONY: up
up: guard-optional-app-public-url ## Alias: sobe o ambiente padrão sem rebuild
	$(DEV_COMPOSE) up -d

.PHONY: down
down: ## Para todos os containers do ambiente dev, incluindo serviços opcionais de browser
	$(DEV_BROWSER_COMPOSE) down

.PHONY: lan-down
lan-down: down ## Alias legado: usa o mesmo fluxo do make down

.PHONY: restart
restart: ## Reinicia todos os containers de dev
	$(DEV_COMPOSE) restart

.PHONY: build
build: ## Rebuilda imagens do dev sem cache intermediário
	$(DEV_COMPOSE) build --pull

.PHONY: vite-build
vite-build: ## Gera build dos assets do Vite
	docker exec plantao12_vite npm run build

.PHONY: browser-up
browser-up: ## Sobe o Selenium opcional para Dusk/E2E sem trocar o ambiente principal
	$(DEV_BROWSER_COMPOSE) up -d plantao12_selenium

.PHONY: logs-browser
logs-browser: ## Tail nos logs do Selenium opcional
	docker logs -f plantao12_selenium

.PHONY: logs
logs: ## Tail nos logs do app
	docker logs -f plantao12_app

.PHONY: logs-worker
logs-worker: ## Tail nos logs do worker
	docker logs -f plantao12_worker

.PHONY: logs-vite
logs-vite: ## Tail nos logs do Vite
	docker logs -f plantao12_vite

.PHONY: shell
shell: ## Bash dentro do container da aplicação
	docker exec -it plantao12_app bash

.PHONY: mysql
mysql: ## Abre cliente mysql dentro do container do DB
	docker exec -it plantao12_mysql mysql -u$${DB_USERNAME:-plantao12_user} -p

## --- LARAVEL -----------------------------------------------------------------

.PHONY: migrate
migrate: ## php artisan migrate
	docker exec plantao12_app php artisan migrate

.PHONY: migrate-fresh
migrate-fresh: ## php artisan migrate:fresh --seed
	docker exec plantao12_app php artisan migrate:fresh --seed

.PHONY: key
key: ## Gera APP_KEY
	docker exec plantao12_app php artisan key:generate

.PHONY: cache-clear
cache-clear: ## Limpa todos os caches do Laravel
	docker exec plantao12_app php artisan optimize:clear

.PHONY: test
test: ## Roda Pest dentro do container
	docker exec plantao12_app ./vendor/bin/pest

.PHONY: test-coverage
test-coverage: ## Pest com cobertura (XDEBUG_MODE=coverage)
	docker exec -e XDEBUG_MODE=coverage plantao12_app ./vendor/bin/pest -c phpunit.coverage.xml --coverage --min=90

## --- PROD --------------------------------------------------------------------

.PHONY: prod
prod: ## Sobe o ambiente de produção (build + up -d)
	$(PROD_COMPOSE) $(UP_ARGS)

.PHONY: prod-build
prod-build: ## Rebuild das imagens de produção
	$(PROD_COMPOSE) build --pull

.PHONY: prod-down
prod-down: ## Para o ambiente de produção
	$(PROD_COMPOSE) down

.PHONY: prod-logs
prod-logs: ## Tail nos logs do app em produção
	$(PROD_COMPOSE) logs -f plantao12_app

.PHONY: prod-migrate
prod-migrate: ## Migrations forçadas em produção
	$(PROD_COMPOSE) exec plantao12_app php artisan migrate --force

## --- LIMPEZA -----------------------------------------------------------------

.PHONY: clean
clean: ## Remove containers, redes e volumes do dev (DESTRUTIVO — apaga o banco)
	$(DEV_BROWSER_COMPOSE) down -v

.PHONY: fresh
fresh: clean dev migrate-fresh ## Rebuild completo do zero + seed

.PHONY: guard-optional-app-public-url
guard-optional-app-public-url:
	@if [ -n "$(APP_PUBLIC_URL)" ]; then \
		case "$(APP_PUBLIC_URL)" in \
			http://localhost|https://localhost|http://localhost:*|https://localhost:*|http://127.0.0.1|https://127.0.0.1|http://127.0.0.1:*|https://127.0.0.1:*) \
				echo "ERRO: APP_PUBLIC_URL deve usar um host/IP alcançável pela rede local, não localhost/127.0.0.1." >&2; exit 1 ;; \
			http://*|https://*) ;; \
			*) \
				echo "ERRO: APP_PUBLIC_URL deve começar com http:// ou https://." >&2; exit 1 ;; \
		esac; \
	fi

## --- HELP --------------------------------------------------------------------

.PHONY: help
help: ## Lista os comandos disponíveis
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z0-9_.-]+:.*?## / {printf "  \033[36m%-18s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)

