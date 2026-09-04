# Deploy

> Guia operacional para colocar o **Amura Suporte** (Laravel 12) em produção
> usando a stack Docker oficial do projeto (`docker-compose.yml` +
> `docker-compose.prod.yml`). Todos os comandos abaixo foram extraídos
> diretamente do repositório (Dockerfile, Makefile, `docker/entrypoint.sh`,
> `.env.production.example`, `scripts/backup.sh`).

---

## 1. Visão geral

**Stack**

- **Backend:** PHP 8.4 (runtime no container) / `^8.2` (mínimo no `composer.json`), Laravel 12
- **Frontend (assets):** Vite 7 + TailwindCSS + Alpine.js, compilados para `public/build/` no Stage 2 do Dockerfile
- **Banco:** MySQL 8.0
- **Cache / Sessão / Fila / Rate Limiter:** Redis (4 databases segregadas — `0` default, `1` cache, `2` queue, `3` session, `4` rate limiter)
- **Servidor Web:** Nginx + PHP-FPM, orquestrados por Supervisor dentro do container
- **Broadcasting:** Pusher (`BROADCAST_CONNECTION=pusher`)
- **WhatsApp:** Evolution API v2 (self-hosted, externo ao compose de produção)
- **Mail:** SMTP (default `smtp.kinghost.net:465` / SMTPS)
- **Auth API:** Laravel Sanctum
- **Permissões:** Spatie Permission
- **Busca:** Laravel Scout, driver `database`
- **Docs API:** L5-Swagger (em produção, `L5_SWAGGER_GENERATE_ALWAYS=false`)
- **Telescope:** **DESLIGADO** em produção

**Arquitetura em produção**

```
                    [ Reverse Proxy / TLS  <-  HTTPS público ]
                                   |
                                   v
                  127.0.0.1:8090  ->  suporte12_app  (Nginx + PHP-FPM)
                                         |
            +----------------------------+----------------------------+
            v                            v                            v
     suporte12_mysql (8.0)       suporte12_redis             suporte12_worker
                                                     (php artisan queue:work redis)

                                   suporte12_scheduler
                                   (php artisan schedule:run a cada 60s)
```

- O app **só** escuta em `127.0.0.1:8090` (overlay de prod). O TLS público e o roteamento externo são responsabilidade de um conector fora do compose. **Definido:** servidor local + **Cloudflare Tunnel** (`cloudflared`) — ver **seção 12.2**.
- Não há bind-mount em produção: o código vive **dentro da imagem** Docker.
- Imagem é multi-stage:
  - **Stage 1 (vendor):** Composer install `--no-dev --optimize-autoloader`
  - **Stage 2 (assets):** `npm ci && npm run build` -> `public/build/`
  - **Stage 3 (runtime):** PHP 8.4-fpm-alpine + Nginx + Supervisor

---

## 2. Pré-requisitos

### No servidor de produção

| Item | Versão mínima / observação |
|---|---|
| Docker Engine | 24+ (BuildKit habilitado — `Makefile` já exporta `DOCKER_BUILDKIT=1`) |
| Docker Compose plugin | v2 (`docker compose ...`) |
| `make` | qualquer versão (atalhos do `Makefile`) |
| `git` | para clonar/atualizar o repositório |
| `curl` | usado pelo `HEALTHCHECK` do container app |
| Sistema operacional | Linux (testado em kernel 6.8) |
| Reverse proxy externo | **Cloudflare Tunnel** (`cloudflared`) — **definido**, ver 12.2 |
| Certificado TLS | Gerenciado pela Cloudflare na borda (Universal SSL) — ver 12.2 |
| Porta liberada no firewall | **Nenhuma porta de entrada** (túnel outbound-only); `8090` não exposto — ver 12.2 |

### Acessos e credenciais necessários

- Conta SMTP funcional (`MAIL_USERNAME` / `MAIL_PASSWORD`)
- Conta Pusher com `app_id`, `key`, `secret` e `cluster`
- Instância Evolution API ativa + chave (`WHATSAPP_EVOLUTION_API_KEY`) e nome de instância (`WHATSAPP_EVOLUTION_INSTANCE`) — **A definir** se será no mesmo host
- Domínio público com DNS apontando para o servidor (`APP_URL`)
- Segredo HMAC do webhook do WhatsApp (`WHATSAPP_WEBHOOK_SECRET`)
- Caso use Plenus: URL pública da API (`PLENUS_API_URL`)

### Conhecimento operacional

- Familiaridade com `docker compose`, `docker exec`, logs Docker
- `php artisan` rodando dentro do container `suporte12_app`

---

## 3. Variáveis de ambiente

> Lista derivada de `.env.production.example`, `.env.example`, `config/*.php` e
> `app/`. Variáveis que o framework Laravel lê com defaults razoáveis e que **não
> aparecem** no `.env.production.example` foram omitidas — preencha-as apenas se
> precisar customizar (ver `config/` para defaults).

### 3.1 Núcleo da aplicação

| Variável | Descrição | Exemplo | Obrigatória |
|---|---|---|---|
| `APP_NAME` | Nome exibido na UI / e-mails | `Suporte` | Sim |
| `APP_ENV` | Ambiente — define caches e migrations forçadas no entrypoint | `production` | Sim |
| `APP_KEY` | Chave de criptografia Laravel — gerar com `php artisan key:generate --show` | `base64:...` | Sim |
| `APP_DEBUG` | **DEVE** ser `false` em produção | `false` | Sim |
| `APP_URL` | URL pública canônica | `https://suporte.exemplo.com.br` | Sim |
| `APP_PUBLIC_URL` | Override opcional (cenário LAN) — em prod, deixar vazio | _(vazio)_ | Não |
| `APP_LOCALE` | Locale principal | `pt_BR` | Não (default) |
| `APP_FALLBACK_LOCALE` | Locale fallback | `en` | Não |
| `APP_FAKER_LOCALE` | Locale do faker | `pt_BR` | Não |
| `APP_MAINTENANCE_DRIVER` | Driver do modo manutenção | `file` | Não |
| `BCRYPT_ROUNDS` | Custo do bcrypt | `12` | Não |

### 3.2 URLs externas

| Variável | Descrição | Exemplo | Obrigatória |
|---|---|---|---|
| `TASK_APP_URL` | URL do sistema de tarefas externo usado por `popupTarefa()` | _(definir)_ | Se usar integração |
| `NOTIFICATION_API_URL` | API de notificações externa | _(definir)_ | Se usar integração |
| `PLENUS_API_URL` | URL base do Sistema Plenus | `https://sistemaplenus.com.br` | Se usar integração |

### 3.3 Banco de dados (MySQL)

| Variável | Descrição | Exemplo | Obrigatória |
|---|---|---|---|
| `DB_CONNECTION` | Driver | `mysql` | Sim |
| `DB_HOST` | Host do MySQL — dentro do compose é o nome do container | `suporte12_mysql` | Sim |
| `DB_PORT` | Porta | `3306` | Sim |
| `DB_DATABASE` | Nome do banco | `suporte12_db` | Sim (compose valida) |
| `DB_USERNAME` | Usuário | `suporte12_user` | Sim (compose valida) |
| `DB_PASSWORD` | Senha forte — gerar com `openssl rand -base64 32` | _(secreto)_ | Sim (compose valida) |
| `DB_ROOT_PASSWORD` | Senha root do MySQL — gerar com `openssl rand -base64 32` | _(secreto)_ | Sim (compose valida) |

### 3.4 Redis (cache, fila, sessão, rate limiter)

| Variável | Descrição | Exemplo | Obrigatória |
|---|---|---|---|
| `REDIS_CLIENT` | Cliente PHP (`phpredis` está instalado na imagem) | `phpredis` | Sim |
| `REDIS_HOST` | Host (nome do container) | `suporte12_redis` | Sim |
| `REDIS_PASSWORD` | Senha — `null` se sem autenticação | `null` | Sim |
| `REDIS_PORT` | Porta | `6379` | Sim |
| `REDIS_DB` | DB default | `0` | Não |
| `REDIS_CACHE_DB` | DB do cache | `1` | Não |
| `REDIS_QUEUE_DB` | DB das filas | `2` | Não |
| `REDIS_SESSION_DB` | DB das sessões | `3` | Não |
| `REDIS_RATE_LIMITER_DB` | DB do rate limiter | `4` | Não |
| `REDIS_RATE_LIMITER_CONNECTION` | Nome da conexão | `rate_limiter` | Não |
| `REDIS_TTL_SHORT` | TTL curto (s) | `300` | Não |
| `REDIS_TTL_DEFAULT` | TTL padrão (s) | `3600` | Não |
| `REDIS_TTL_LONG` | TTL longo (s) | `86400` | Não |
| `REDIS_TTL_SESSION` | TTL de sessão (s) | `36000` | Não |

### 3.5 Cache / Sessão / Fila

| Variável | Descrição | Exemplo | Obrigatória |
|---|---|---|---|
| `CACHE_DRIVER` | Driver de cache | `redis` | Sim |
| `QUEUE_CONNECTION` | Conexão de fila | `redis` | Sim |
| `SESSION_DRIVER` | Driver de sessão | `redis` | Sim |
| `SESSION_LIFETIME` | TTL em minutos | `600` | Sim |
| `SESSION_COOKIE` | Nome do cookie | `laravel_session` | Não |
| `SESSION_SECURE_COOKIE` | `true` em produção (HTTPS) | `true` | Sim |
| `SESSION_HTTP_ONLY` | Cookie httpOnly | `true` | Sim |
| `SESSION_SAME_SITE` | SameSite | `lax` | Sim |
| `FILESYSTEM_DISK` | Disco padrão | `local` | Não |

### 3.6 Rate Limiting (requisições por minuto)

| Variável | Default | Descrição |
|---|---|---|
| `RATE_LIMIT_API` | `60` | API genérica |
| `RATE_LIMIT_API_STRICT` | `30` | Endpoints sensíveis |
| `RATE_LIMIT_REPORTS` | `10` | Relatórios |
| `RATE_LIMIT_TASKS_API` | `120` | API de tarefas |
| `RATE_LIMIT_ADMIN_API` | `60` | API admin |
| `RATE_LIMIT_PASSWORD_RESET` | `3` | Reset de senha |
| `RATE_LIMIT_AUTH` | `5` | Tentativas de login |
| `RATE_LIMIT_WHATSAPP_WEBHOOK` | `300` | Webhook WhatsApp |
| `RATE_LIMIT_INTEGRATION` | `60` | Integração financeiro (por IP — brute-force de API key) |

### 3.7 Broadcasting (Pusher)

| Variável | Descrição | Exemplo | Obrigatória |
|---|---|---|---|
| `BROADCAST_CONNECTION` | Driver | `pusher` | Sim |
| `PUSHER_APP_ID` | ID do app na Pusher | _(real)_ | Sim |
| `PUSHER_APP_KEY` | Key pública | _(real)_ | Sim |
| `PUSHER_APP_SECRET` | Secret | _(real)_ | Sim |
| `PUSHER_APP_CLUSTER` | Cluster | `mt1` | Sim |
| `PUSHER_PORT` | Porta | `443` | Sim |
| `PUSHER_SCHEME` | Esquema | `https` | Sim |

### 3.8 Vite (assets)

> Valores lidos no **build de assets** (Stage 2 do Dockerfile). Mudar exige rebuild da imagem.

| Variável | Descrição | Exemplo |
|---|---|---|
| `VITE_APP_NAME` | Nome injetado no frontend | `${APP_NAME}` |
| `VITE_PUSHER_APP_KEY` | Pusher key no JS | `${PUSHER_APP_KEY}` |
| `VITE_PUSHER_APP_CLUSTER` | Cluster Pusher no JS | `${PUSHER_APP_CLUSTER}` |

### 3.9 Mail (SMTP)

| Variável | Descrição | Exemplo | Obrigatória |
|---|---|---|---|
| `MAIL_MAILER` | Driver | `smtp` | Sim |
| `MAIL_SCHEME` | Scheme | `smtps` | Sim |
| `MAIL_HOST` | Host SMTP | `smtp.kinghost.net` | Sim |
| `MAIL_PORT` | Porta | `465` | Sim |
| `MAIL_USERNAME` | Usuário SMTP | `noreply@amura.com.br` | Sim |
| `MAIL_PASSWORD` | Senha SMTP | _(secreto)_ | Sim |
| `MAIL_FROM_ADDRESS` | Remetente | `noreply@amura.com.br` | Sim |
| `MAIL_FROM_NAME` | Nome do remetente | `Amura` | Sim |
| `MAIL_EHLO_DOMAIN` | Domínio EHLO | `amura.com.br` | Sim |

### 3.10 Logs

| Variável | Descrição | Exemplo | Obrigatória |
|---|---|---|---|
| `LOG_CHANNEL` | Canal raiz | `stack` | Sim |
| `LOG_STACK` | Canais agregados | `single` | Sim |
| `LOG_DEPRECATIONS_CHANNEL` | Canal de deprecations | `null` | Não |
| `LOG_LEVEL` | Nível mínimo em prod | `warning` | Sim |

### 3.11 Telescope & Swagger

| Variável | Descrição | Exemplo |
|---|---|---|
| `TELESCOPE_ENABLED` | **DEVE** ser `false` em produção | `false` |
| `L5_SWAGGER_CONST_HOST` | Host base da doc Swagger | `https://suporte.exemplo.com.br` |
| `L5_SWAGGER_GENERATE_ALWAYS` | Em prod, **`false`** (não regerar a cada request) | `false` |

### 3.12 WhatsApp (Evolution API)

| Variável | Descrição | Exemplo | Obrigatória |
|---|---|---|---|
| `WHATSAPP_ENABLED` | Liga/desliga integração | `true` | Sim (se usar) |
| `WHATSAPP_PROVIDER` | `evolution` ou `generic` (Meta Cloud) | `evolution` | Sim |
| `WHATSAPP_FROM_NUMBER` | Número do remetente (E.164) | `27981180125` | Sim |
| `WHATSAPP_API_URL` | URL da Evolution API de produção | _(definir)_ | Sim |
| `WHATSAPP_WEBHOOK_URL` | URL pública do webhook | `${APP_URL}/api/webhook/whatsapp` | Sim |
| `WHATSAPP_EVOLUTION_INSTANCE` | Nome da instância na Evolution | _(definir)_ | Sim |
| `WHATSAPP_EVOLUTION_API_KEY` | API key real (não usar o default de dev) | _(secreto)_ | Sim |
| `WHATSAPP_API_TOKEN` | Token (só se `WHATSAPP_PROVIDER=generic`) | _(secreto)_ | Condicional |
| `WHATSAPP_WEBHOOK_SECRET` | HMAC `X-Hub-Signature-256` — **obrigatório** em prod | _(secreto forte)_ | Sim |
| `WHATSAPP_SESSION_TTL` | Minutos antes de expirar conversa | `60` | Não |
| `WHATSAPP_AUTO_IDENTIFY_COMPANY_BY_PHONE` | Identificação auto pelo telefone | `true` | Não |
| `WHATSAPP_ORIGIN_ID` | ID do `ticketit_origin` | `5` | Não |
| `WHATSAPP_DEFAULT_AGENT_ID` | Agent padrão (vazio = fila do setor) | _(vazio)_ | Não |
| `WHATSAPP_DEFAULT_STATUS_ID` | Status inicial dos tickets | `4` | Não |
| `WHATSAPP_DEFAULT_PRIORITY_ID` | Prioridade padrão | `1` | Não |
| `WHATSAPP_DEPARTMENT_SUPORTE_ID` | Mapeamento setor Suporte | `1` | Não |
| `WHATSAPP_DEPARTMENT_FINANCEIRO_ID` | Mapeamento setor Financeiro | `2` | Não |
| `WHATSAPP_DEPARTMENT_COMERCIAL_ID` | Mapeamento setor Comercial | `3` | Não |
| `WHATSAPP_CATEGORY_SUPORTE` | Mapeamento categoria Suporte | `1` | Não |
| `WHATSAPP_CATEGORY_FINANCEIRO` | Mapeamento categoria Financeiro | `1` | Não |
| `WHATSAPP_CATEGORY_COMERCIAL` | Mapeamento categoria Comercial | `1` | Não |
| `WHATSAPP_MSG_*` (16 variáveis) | Templates de mensagens do bot — vazio usa defaults do `config/whatsapp.php` | _(vazio)_ | Não |

### 3.13 Docker / runtime

| Variável | Descrição | Exemplo | Obrigatória |
|---|---|---|---|
| `APP_UID` | UID do usuário `appuser` dentro do container | `1000` | Sim |
| `APP_GID` | GID do grupo `appuser` | `1000` | Sim |
| `SCOUT_DRIVER` | Driver Scout | `database` | Não |

> **Variáveis adicionais detectadas no código** (AWS S3, Memcached, Beanstalkd,
> SQS, Ably, Reverb, Postmark, Resend, Slack, DynamoDB) **NÃO** são usadas pelo
> deploy padrão — são templates do Laravel para drivers alternativos. Ignore a
> menos que você ative explicitamente um desses drivers.

---

## 4. Infraestrutura necessária

### 4.1 No host (servidor de produção)

| Recurso | Descrição |
|---|---|
| **Docker network `suporte12_net`** | Criada automaticamente pelo compose (bridge) |
| **Volume `suporte12_dbdata`** | Persistência do MySQL — criado automaticamente. **Backup obrigatório.** |
| **Storage local** | `storage/app/` (uploads, anexos de chamados, mídias do WhatsApp) — vive dentro da imagem do container; **deve** ser persistido via volume nomeado em produção real. **A definir:** se persistência do `storage/` será adicionada (hoje o compose não monta volume para `storage/`) |
| **Porta `127.0.0.1:8090`** | Bind local do app (reverse proxy externo encaminha para cá) |

### 4.2 Serviços geridos pelo compose (`docker-compose.yml` + overlay prod)

| Container | Imagem | Função |
|---|---|---|
| `suporte12_mysql` | `mysql:8.0` | Banco de dados — healthcheck `mysqladmin ping`, retries=36, start_period=6m |
| `suporte12_redis` | `redis:alpine` | Cache/fila/sessão — healthcheck `redis-cli ping` |
| `suporte12_app` | `suporte12_app:latest` (build local) | Nginx + PHP-FPM 8.4 + Supervisor — healthcheck `curl /up` |
| `suporte12_worker` | `suporte12_app:latest` | `php artisan queue:work redis --sleep=3 --tries=3 --timeout=90` |
| `suporte12_scheduler` | `suporte12_app:latest` | Loop `while true; do php artisan schedule:run; sleep 60; done` (overlay prod) |

### 4.3 Serviços externos (fora do compose)

| Serviço | Uso |
|---|---|
| **Cloudflare Tunnel + TLS** | Termina HTTPS na borda e encaminha pelo `cloudflared` ao container. **Definido** — ver 12.2 |
| **DNS** | Gerenciado na Cloudflare; registro criado pelo próprio túnel. **Definido** — ver 12.2 |
| **Pusher** | Broadcasting. Conta + app criados em <https://pusher.com> |
| **SMTP** | Envio de e-mails. Default: Kinghost; pode usar Postmark/Resend/SES configurando `config/mail.php` |
| **Evolution API** | Provedor WhatsApp self-hosted. **Não é orquestrado no `docker-compose.prod.yml`** — em dev existe `suporte12_evolution` no overlay dev, mas em produção deve ser instância separada |

---

## 5. Build

A imagem é multi-stage e **autossuficiente**: o `vendor/` é instalado no Stage 1
e o `public/build/` é gerado no Stage 2 — não é necessário rodar `composer
install` nem `npm run build` no host.

### 5.1 Build padrão (com Makefile)

```bash
make prod-build
# equivale a:
# DOCKER_BUILDKIT=1 docker compose -f docker-compose.yml -f docker-compose.prod.yml build --pull
```

### 5.2 Build direto

```bash
DOCKER_BUILDKIT=1 docker compose \
  -f docker-compose.yml \
  -f docker-compose.prod.yml \
  build --pull
```

### 5.3 O que o build faz

1. **Stage `vendor`**: `composer install --no-dev --prefer-dist --optimize-autoloader --no-scripts`
2. **Stage `assets`**: `npm ci --prefer-offline --no-audit --no-fund` e em seguida `npm run build` (Vite -> `public/build/`)
3. **Stage `runtime`**: instala extensões PHP (`pdo_mysql`, `mbstring`, `exif`, `pcntl`, `bcmath`, `gd`, `zip`, `redis`, `pdo_sqlite`), copia código + `vendor/` + `public/build/`, define `appuser` (UID/GID configuráveis), roda `php artisan package:discover`
4. Tagueia a imagem como **`suporte12_app:latest`** — usada pelos serviços `app`, `worker` e `scheduler`

---

## 6. Deploy passo a passo

> Todos os comandos abaixo são executados **no servidor**, a partir da raiz do
> repositório clonado.

### 6.1 Primeiro deploy

```bash
# 1. Clonar o repositório
git clone <url-do-repo> /opt/suporte
cd /opt/suporte

# 2. Preparar .env de produção
cp .env.production.example .env

# 3. Gerar APP_KEY (rode em qualquer máquina com PHP, ou use uma imagem temporária)
docker run --rm -v "$PWD":/app -w /app composer:2 sh -c "composer install --no-dev --no-scripts -q && php artisan key:generate --show"
# Cole o valor (base64:...) em APP_KEY no .env

# 4. Preencher TODAS as senhas/credenciais no .env
#    - DB_PASSWORD, DB_ROOT_PASSWORD (openssl rand -base64 32)
#    - MAIL_PASSWORD
#    - PUSHER_APP_ID/KEY/SECRET
#    - WHATSAPP_EVOLUTION_API_KEY, WHATSAPP_WEBHOOK_SECRET
#    - APP_URL apontando para o domínio público
nano .env

# 5. Build + up (já roda migrations via entrypoint quando APP_ENV=production)
make prod
# equivale a:
# docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build --remove-orphans

# 6. Verificar containers
docker compose -f docker-compose.yml -f docker-compose.prod.yml ps

# 7. Health check
curl -fsS http://127.0.0.1:8090/up && echo "OK"

# 8. (Primeira vez apenas) Seed inicial — APENAS se o banco estiver vazio
#    ATENÇÃO: rodar seeds em produção é decisão do operador. Em produção real
#    geralmente não se executa seed; popule via fixtures controladas.
# docker exec suporte12_app php artisan db:seed --force

# 9. Configurar o reverse proxy externo para encaminhar:
#    https://suporte.exemplo.com.br  ->  http://127.0.0.1:8090
#    (cabeçalhos: X-Forwarded-For, X-Forwarded-Proto=https)

# 10. (Opcional) Agendar backup no cron do host
crontab -e
# adicionar:
# 10 3 * * * /opt/suporte/scripts/backup.sh >> /var/log/suporte-backup.log 2>&1
```

### 6.2 Deploys subsequentes

```bash
cd /opt/suporte

# 1. Atualizar código
git fetch --all
git checkout main       # ou a branch/tag de release
git pull --ff-only

# 2. Rebuild + restart
make prod
# ou, mais granular:
# docker compose -f docker-compose.yml -f docker-compose.prod.yml build --pull
# docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d --no-deps suporte12_app suporte12_worker suporte12_scheduler

# 3. O entrypoint roda automaticamente em APP_ENV=production:
#    - php artisan config:cache
#    - php artisan route:cache
#    - php artisan view:cache
#    - php artisan event:cache
#    - php artisan migrate --force
#    - php artisan storage:link --force

# 4. Health check + smoke test
curl -fsS http://127.0.0.1:8090/up && echo "OK"
```

> **Observação importante:** o `docker/entrypoint.sh` aplica `config:cache`,
> `route:cache`, `view:cache`, `event:cache` e roda `migrate --force` toda vez
> que o container `suporte12_app` sobe (quando `APP_ENV=production`). Não é
> necessário rodar esses comandos manualmente.

---

## 7. Migrações e seeds

### 7.1 Em produção

```bash
# Rodar migrations pendentes (forçado — bypass do "rodar em prod?")
make prod-migrate
# equivale a:
# docker compose -f docker-compose.yml -f docker-compose.prod.yml exec suporte12_app php artisan migrate --force

# Listar status das migrations
docker exec suporte12_app php artisan migrate:status

# (Raríssimo) executar uma seed específica em prod
docker exec suporte12_app php artisan db:seed --class=NomeDoSeederSeguro --force
```

> **Nunca** rodar `migrate:fresh` ou `migrate:refresh` em produção — apagam
> todas as tabelas. O atalho `make migrate-fresh` é **apenas para DEV**.

### 7.2 Reverter uma migration

```bash
# Reverter o último batch
docker exec suporte12_app php artisan migrate:rollback --step=1 --force

# Reverter os últimos N batches
docker exec suporte12_app php artisan migrate:rollback --step=3 --force
```

> Migrations destrutivas (DROP COLUMN, DROP TABLE) **não** são reversíveis sem
> backup. Sempre rode `scripts/backup.sh` antes de qualquer release que toque
> em schema. Para rollback seguro de schema, prefira restaurar o dump do MySQL.

### 7.3 Estratégia recomendada para releases com schema change

1. `scripts/backup.sh /var/backups/suporte/pre-release-$(date +%F)`
2. `git pull` da nova versão
3. `make prod-build` (não sobe ainda)
4. Em janela de manutenção: `make prod` (o entrypoint aplica `migrate --force`)
5. Smoke test -> se falhar, ver seção **9. Rollback**

---

## 8. Health check e verificação pós-deploy

### 8.1 Health endpoint

A aplicação expõe `GET /up` (Laravel padrão, configurado em
`bootstrap/app.php:13`). O Dockerfile usa esse endpoint no `HEALTHCHECK`:

```bash
# Direto no host (porta local)
curl -fsS http://127.0.0.1:8090/up
# Resposta esperada: HTTP 200, HTML do health-check Laravel

# Status do healthcheck Docker
docker inspect --format='{{json .State.Health}}' suporte12_app | jq

# Status agregado dos containers
docker compose -f docker-compose.yml -f docker-compose.prod.yml ps
```

### 8.2 Verificações funcionais

```bash
# 1. Banco respondendo
docker exec suporte12_app php artisan migrate:status | head

# 2. Redis OK
docker exec suporte12_redis redis-cli ping
# -> PONG

# 3. Worker rodando (não pode estar em restart loop)
docker logs --tail=50 suporte12_worker

# 4. Scheduler rodando
docker logs --tail=50 suporte12_scheduler

# 5. Login admin (substituir host)
curl -I https://suporte.exemplo.com.br/admin/login
# -> HTTP 200

# 6. Webhook WhatsApp acessível externamente
curl -I https://suporte.exemplo.com.br/api/webhook/whatsapp
# -> HTTP 401 (esperado sem assinatura) ou 405 sem POST
```

### 8.3 Métricas e logs (ver seção 10)

---

## 9. Rollback

### 9.1 Rollback de aplicação (sem mudança de schema)

```bash
cd /opt/suporte

# Identificar a versão anterior
git log --oneline -n 10

# Voltar para o commit/tag anterior
git checkout <sha-anterior>   # ou: git checkout v1.2.3

# Rebuild + restart
make prod
```

### 9.2 Rollback de aplicação **com** mudança de schema

> Migrations não são triviais de reverter. O caminho seguro é restaurar o dump
> feito pelo `scripts/backup.sh` antes da release.

```bash
# 1. Parar a aplicação para evitar writes durante o restore
docker compose -f docker-compose.yml -f docker-compose.prod.yml stop suporte12_app suporte12_worker suporte12_scheduler

# 2. Restaurar o último dump (ajustar caminho)
LATEST_DUMP=$(ls -1t /var/backups/suporte/db-*.sql.gz | head -1)
gunzip -c "$LATEST_DUMP" | docker exec -i suporte12_mysql sh -c '
  exec mysql -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"
'

# 3. (Opcional) Restaurar storage/app
LATEST_STORAGE=$(ls -1t /var/backups/suporte/storage-*.tar.gz | head -1)
tar -xzf "$LATEST_STORAGE" -C /opt/suporte

# 4. Voltar o código
git checkout <sha-anterior>

# 5. Subir de novo
make prod
```

### 9.3 Rollback de imagem (sem rebuild)

Se você marca tags da imagem (ex.: `suporte12_app:v1.2.3`), pode trocar o `image:`
no compose para a tag anterior e rodar `docker compose up -d --no-deps
suporte12_app suporte12_worker suporte12_scheduler`. **A definir:** pipeline de
tageamento — hoje o build local sempre escreve em `suporte12_app:latest`.

---

## 10. Monitoramento e logs

### 10.1 Logs do Docker (json-file, rotativo)

Configuração já no compose (`max-size: 10m`, `max-file: 5` em prod):

```bash
# Atalhos do Makefile
make prod-logs                    # logs do app (follow)

# Diretos
docker logs -f --tail=200 suporte12_app
docker logs -f --tail=200 suporte12_worker
docker logs -f --tail=200 suporte12_scheduler
docker logs -f --tail=200 suporte12_mysql
docker logs -f --tail=200 suporte12_redis
```

### 10.2 Logs do Laravel

O canal raiz é `stack` -> `single` (default). O arquivo vive em
`storage/logs/laravel.log` **dentro do container**:

```bash
# Tail do log Laravel
docker exec -it suporte12_app tail -f storage/logs/laravel.log
```

> `php artisan pail` é dev-dependency e **não está disponível** no build de
> produção (`composer install --no-dev`). Use `tail` direto no log.

### 10.3 Métricas

- **HEALTHCHECK do Docker:** `docker compose ps` mostra status `healthy`/`unhealthy`
- **Telescope:** **desligado em produção** (`TELESCOPE_ENABLED=false`)
- **Métricas de aplicação:** **A definir** — não há integração nativa com Prometheus/Datadog/New Relic no projeto

### 10.4 Backups

```bash
# Manual
/opt/suporte/scripts/backup.sh

# Custom: destino e retenção
BACKUP_DIR=/mnt/backup BACKUP_RETENTION_DAYS=30 /opt/suporte/scripts/backup.sh

# Cron diário recomendado (host)
10 3 * * * /opt/suporte/scripts/backup.sh >> /var/log/suporte-backup.log 2>&1
```

O script faz:
1. `mysqldump --single-transaction --routines --triggers` -> `db-YYYYMMDD-HHMMSS.sql.gz`
2. `tar -czf storage-YYYYMMDD-HHMMSS.tar.gz storage/app`
3. Remove arquivos com mais de `BACKUP_RETENTION_DAYS` (default 14)

> **A definir:** envio off-site dos backups (S3, B2, etc.). Hoje o script só
> grava localmente.

---

## 11. Troubleshooting

### 11.1 Container `suporte12_app` em `restarting`

```bash
docker logs --tail=200 suporte12_app
```

**Causas comuns:**
- `APP_KEY` vazia -> entrypoint falha. Solução: gerar e popular `APP_KEY`.
- MySQL não está `healthy` -> o entrypoint aguarda em `until php -r ... fsockopen`. Solução:
  ```bash
  docker logs suporte12_mysql
  docker compose -f docker-compose.yml -f docker-compose.prod.yml restart suporte12_mysql
  ```
- Migration nova falhou -> ver `storage/logs/laravel.log`.

### 11.2 `make prod` reclama de variáveis obrigatórias

```
DB_DATABASE obrigatório / DB_USERNAME obrigatório / ...
```

O compose tem guards (`${VAR:?...}`) em `DB_DATABASE`, `DB_USERNAME`,
`DB_PASSWORD`, `DB_ROOT_PASSWORD`. Preencher no `.env`.

### 11.3 Worker não processa jobs

```bash
docker logs --tail=200 suporte12_worker
# Healthcheck verifica: pgrep -f 'artisan queue:work redis'
docker compose -f docker-compose.yml -f docker-compose.prod.yml restart suporte12_worker
```

Verifique também:
- `QUEUE_CONNECTION=redis`
- `REDIS_QUEUE_DB=2`
- Redis acessível: `docker exec suporte12_redis redis-cli -n 2 LLEN queues:default`

### 11.4 Imagens (uploads) retornam 404 / quebradas

- O `storage/app/public` precisa do symlink `public/storage`. O entrypoint roda
  `php artisan storage:link --force` automaticamente, mas se a imagem do
  container for trocada o symlink se perde. Recriar:
  ```bash
  docker exec suporte12_app php artisan storage:link --force
  ```
- Para mídias do WhatsApp, há rota autenticada dedicada (ver commit
  `1a781135 fix(whatsapp): serve mídia do chamado via rota autenticada`).

### 11.5 Caches do Laravel desatualizados após deploy

Apenas em casos extremos (entrypoint deveria recriar). Para limpar manualmente:
```bash
docker exec suporte12_app php artisan optimize:clear
docker exec suporte12_app php artisan config:cache
docker exec suporte12_app php artisan route:cache
docker exec suporte12_app php artisan view:cache
docker exec suporte12_app php artisan event:cache
```

### 11.6 HTTPS / cookies de sessão não persistem

- Confirmar que o reverse proxy envia `X-Forwarded-Proto: https`.
- `SESSION_SECURE_COOKIE=true` exige HTTPS — em ambiente sem TLS o cookie é descartado.
- Confirmar que `APP_URL` usa `https://...`.

### 11.7 Webhook do WhatsApp retorna 401

- `WHATSAPP_WEBHOOK_SECRET` ausente ou inconsistente entre Evolution e Laravel.
- Verificar header `X-Hub-Signature-256` no payload.

### 11.8 Permissões em `storage/` ou `bootstrap/cache/`

O Dockerfile já aplica `chmod -R ug+rwX storage bootstrap/cache`, mas se houver
volume montado externamente, alinhar UID/GID com `APP_UID`/`APP_GID`:

```bash
docker exec -u root suporte12_app chown -R appuser:appuser storage bootstrap/cache
```

### 11.9 "Class not found" ou autoload corrompido

```bash
docker exec suporte12_app composer dump-autoload --optimize --no-dev
docker exec suporte12_app php artisan package:discover --ansi
```

### 11.10 Espaço em disco

- Volume `suporte12_dbdata` cresce sem limite — monitorar com `docker system df`.
- Logs de container são rotacionados (`max-size: 10m`, `max-file: 5` em prod), mas o `storage/logs/laravel.log` **não** é. Adicionar `logrotate` no host:
  ```
  /opt/suporte/storage/logs/laravel.log {
      daily
      rotate 14
      compress
      missingok
      copytruncate
  }
  ```

---

## 12. Definição do ambiente em nuvem

> Esta seção responde às solicitações do cliente sobre o provisionamento em
> nuvem. Diferente das seções 1–11 (guia operacional), aqui o foco é **o que a
> equipe de infraestrutura precisa definir/fornecer** para colocar o projeto em
> execução em qualquer provedor.

### 12.1 Informações de configuração necessárias (independente do provedor)

O projeto roda em **containers Docker autossuficientes** (app, MySQL, Redis,
worker, scheduler). Por isso, a maior parte da configuração interna é
**portável** e **não depende** do provedor de nuvem escolhido (AWS, GCP, Azure,
Hostinger, VPS dedicado etc.). O que realmente muda de um ambiente para outro
são as **credenciais** (banco, e-mail, integrações) e o **domínio público**.

> **Importante sobre `user_name` / `password` do banco:** você **não** precisa
> criar o usuário/senha do MySQL manualmente. O container `suporte12_mysql` cria
> o banco, o usuário e as senhas **automaticamente no primeiro boot**, a partir
> dos valores que você definir no `.env` (`DB_DATABASE`, `DB_USERNAME`,
> `DB_PASSWORD`, `DB_ROOT_PASSWORD`). Basta **escolher** valores fortes.

Ponto de partida: **copie `.env.production.example` para `.env`** (já vem com
todos os campos e os defaults seguros de produção) e preencha apenas o que está
listado abaixo. A referência completa de cada variável está na **seção 3**.

#### A. Valores que VOCÊ gera no próprio servidor (segredos — não vêm de terceiros)

| Variável | Como gerar | Observação |
|---|---|---|
| `APP_KEY` | `php artisan key:generate --show` (ver passo 3 da seção 6.1) | Gerar **uma única vez**. Nunca alterar depois — invalida sessões e dados criptografados. |
| `DB_PASSWORD` | `openssl rand -base64 32` | Senha do usuário da aplicação. Você **escolhe** o valor; o MySQL cria o usuário com ela. |
| `DB_ROOT_PASSWORD` | `openssl rand -base64 32` | Senha do root do MySQL. |
| `WHATSAPP_WEBHOOK_SECRET` | `openssl rand -hex 32` | HMAC do webhook (`X-Hub-Signature-256`). Obrigatório se usar WhatsApp. Deve ser o mesmo configurado na Evolution. |
| `FINANCEIRO_API_KEY` | `openssl rand -hex 32` | Chave da integração inbound do financeiro (header `X-API-Key` nos endpoints `/api/v1/integration/*`). **Fail-closed**: se vazia, esses endpoints respondem 401. Contrato documentado no Swagger (tag "Integração - Financeiro"). |
| `REDIS_PASSWORD` | `openssl rand -base64 32` (opcional) | Só se quiser Redis autenticado. Default `null` (sem auth) é aceitável pois o Redis não é exposto fora da rede Docker. |

#### B. Valores que vêm de serviços externos (você obtém junto ao provedor de cada serviço)

| Variável(eis) | Onde obter |
|---|---|
| `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_SCHEME` | Provedor de e-mail/SMTP (default do template: Kinghost). |
| `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`, `MAIL_EHLO_DOMAIN` | Definidos por você conforme o remetente desejado. |
| `PUSHER_APP_ID`, `PUSHER_APP_KEY`, `PUSHER_APP_SECRET`, `PUSHER_APP_CLUSTER` | Painel da conta Pusher (<https://pusher.com>). |
| `WHATSAPP_API_URL`, `WHATSAPP_EVOLUTION_INSTANCE`, `WHATSAPP_EVOLUTION_API_KEY`, `WHATSAPP_FROM_NUMBER` | Instância da Evolution API de produção (serviço externo, fora deste compose). |

#### C. Valores que dependem do domínio / ambiente escolhido

| Variável | O que definir | Exemplo |
|---|---|---|
| `APP_URL` | Domínio público canônico, **sempre com HTTPS** | `https://suporte.seu-dominio.com.br` |
| `L5_SWAGGER_CONST_HOST` | Mesmo valor de `APP_URL` | `https://suporte.seu-dominio.com.br` |
| `WHATSAPP_WEBHOOK_URL` | Já referencia `${APP_URL}` — manter como está | `${APP_URL}/api/webhook/whatsapp` |
| `APP_UID` / `APP_GID` | UID/GID do usuário que roda o Docker no host (rode `id -u` / `id -g`) | `1000` / `1000` |

#### D. Valores internos fixos (NÃO mudam entre provedores — rede interna do Docker)

Estes já vêm prontos no `.env.production.example` e **não precisam ser
alterados** — apontam para os containers dentro da rede `suporte12_net`:

| Variável | Valor fixo | Por quê |
|---|---|---|
| `APP_ENV` | `production` | Liga caches e `migrate --force` no entrypoint. |
| `APP_DEBUG` | `false` | Segurança — nunca `true` em produção. |
| `DB_HOST` | `suporte12_mysql` | Nome do container MySQL na rede Docker. |
| `DB_PORT` | `3306` | — |
| `DB_CONNECTION` | `mysql` | — |
| `DB_DATABASE` | `suporte12_db` | Nome do banco criado no primeiro boot (pode trocar, desde que `.env` e compose batam). |
| `DB_USERNAME` | `suporte12_user` | Usuário criado no primeiro boot (pode trocar). |
| `REDIS_HOST` | `suporte12_redis` | Nome do container Redis na rede Docker. |
| `REDIS_PORT` | `6379` | — |

> `DB_DATABASE` e `DB_USERNAME` **podem** ser personalizados se o cliente
> preferir outros nomes — basta que o valor no `.env` seja o mesmo lido pelo
> compose (ele usa `${DB_DATABASE}` / `${DB_USERNAME}` para criar o banco). Em um
> primeiro deploy não há motivo para mudar; manter os defaults simplifica.

#### E. Checklist mínimo para "colocar o projeto em execução"

Com o `.env` copiado do template, o **mínimo** que precisa ser preenchido para o
sistema subir é:

- [ ] `APP_KEY` — gerada (item A)
- [ ] `APP_URL` — domínio público HTTPS (item C)
- [ ] `DB_PASSWORD` e `DB_ROOT_PASSWORD` — senhas fortes (item A)
- [ ] `MAIL_*` — credenciais SMTP, se for enviar e-mails (item B)
- [ ] `PUSHER_*` — se usar notificações em tempo real (item B)
- [ ] `WHATSAPP_*` — apenas se `WHATSAPP_ENABLED=true` (itens A e B)
- [ ] `APP_UID` / `APP_GID` — alinhados ao usuário do host (item C)

> O compose **valida** na subida e aborta com erro claro se faltar
> `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` ou `DB_ROOT_PASSWORD` (ver
> seção 11.2). As demais variáveis têm defaults seguros no template.

#### F. O que NÃO depende deste `.env` (provisionado fora do projeto)

Independente do provedor, estes itens são responsabilidade da infra e **não**
fazem parte do `.env` da aplicação (ver seções 2 e 4.3):

- **Domínio + DNS** apontando para o IP público do servidor.
- **Reverse proxy + TLS** (Nginx/Caddy/Traefik) terminando HTTPS e encaminhando
  para `127.0.0.1:8090` com os cabeçalhos `X-Forwarded-Proto: https` e
  `X-Forwarded-For`.
- **Docker Engine 24+ e Docker Compose v2** instalados no host.
- **Instância da Evolution API** (se usar WhatsApp) — serviço externo, não
  orquestrado por este compose em produção.

### 12.2 Hospedagem escolhida: servidor local + Cloudflare Tunnel

**Topologia definida pelo cliente:** a aplicação roda em um **servidor local**
(on-premises, possivelmente atrás de NAT, sem IP público fixo) e é exposta à
internet por um **Cloudflare Tunnel** (`cloudflared`). Isso **resolve** itens que
estavam como "A definir" nas seções 2 e 4.3:

| Item antes "A definir" | Resolução com Cloudflare Tunnel |
|---|---|
| Reverse proxy externo | `cloudflared` — dispensa Nginx/Caddy/Traefik na frente |
| Certificado TLS | TLS gerenciado pela Cloudflare na borda (Universal SSL) |
| DNS | Gerenciado na Cloudflare (registro criado pelo próprio túnel) |
| Abrir portas 80/443 no firewall | **Não é necessário** — o túnel é **outbound-only** |
| IP público fixo | **Não é necessário** — funciona atrás de NAT |

**Fluxo da requisição:**

```
[ Navegador ] --HTTPS--> [ Borda Cloudflare ] --túnel cifrado--> [ cloudflared (servidor local) ]
                                                                          |
                                                                          v  HTTP interno
                                                              http://suporte12_app:8080
                                                              (ou http://127.0.0.1:8090)
```

- A Cloudflare termina o HTTPS público; o `cloudflared` mantém conexão de **saída**
  persistente com a borda. **Nenhuma porta de entrada** precisa ser aberta.
- A conexão `cloudflared → aplicação` é **HTTP simples** dentro do servidor (o
  tráfego sai cifrado pelo túnel). Logo, a aplicação **recebe HTTP** e precisa
  confiar no `X-Forwarded-Proto: https` enviado pela Cloudflare — ver **12.2.1**.

#### 12.2.1 ⚠️ Ação obrigatória no código — Trusted Proxies

Hoje o projeto **não** configura proxies confiáveis (`bootstrap/app.php` não chama
`trustProxies`). Sem isso, atrás do túnel a aplicação enxerga as requisições como
**HTTP** e ocorre:

- URLs absolutas geradas como `http://` (assets, redirects, links de e-mail);
- cookie de sessão com flag `Secure` **descartado** (com `SESSION_SECURE_COOKIE=true`) → **não loga**;
- possível **loop de redirecionamento**.

**Correção** — adicionar dentro do `->withMiddleware(function (Middleware $middleware) {...})` em `bootstrap/app.php`:

```php
// A origem só é acessível pelo Cloudflare Tunnel (não exposta publicamente),
// então é seguro confiar no proxy para ler os cabeçalhos X-Forwarded-*.
$middleware->trustProxies(at: '*', headers:
    Request::HEADER_X_FORWARDED_FOR |
    Request::HEADER_X_FORWARDED_HOST |
    Request::HEADER_X_FORWARDED_PORT |
    Request::HEADER_X_FORWARDED_PROTO
);
```

> `Request` já está importado no topo de `bootstrap/app.php`. Confiar em `'*'` é
> aceitável **somente porque a origem (`127.0.0.1:8090` / `suporte12_app:8080`)
> não é acessível diretamente da internet** — apenas o `cloudflared` a alcança.
> Se a porta `8090` for exposta na rede local, restrinja `at:` às faixas do
> conector (ex.: `['127.0.0.1', '172.16.0.0/12']`).

#### 12.2.2 Subir o `cloudflared`

Crie o túnel em **Cloudflare Zero Trust → Networks → Tunnels**, escolha o conector
**Docker** e copie o **token** gerado. Duas formas de rodar:

**Opção A (recomendada) — container no mesmo compose, na rede `suporte12_net`.**
Crie um overlay `docker-compose.cloudflared.yml`:

```yaml
services:
  cloudflared:
    image: cloudflare/cloudflared:latest
    container_name: suporte12_cloudflared
    restart: unless-stopped
    command: tunnel --no-autoupdate run
    environment:
      TUNNEL_TOKEN: ${CLOUDFLARE_TUNNEL_TOKEN:?defina o token do túnel no .env}
    depends_on:
      - suporte12_app
    networks:
      - suporte12_net

networks:
  suporte12_net:
    external: true
```

Suba junto com a stack:

```bash
docker compose \
  -f docker-compose.yml \
  -f docker-compose.prod.yml \
  -f docker-compose.cloudflared.yml \
  up -d
```

No painel do túnel, configure a **Public Hostname**:
- **Domain:** o domínio de `APP_URL` (ex.: `suporte.seu-dominio.com.br`)
- **Service:** `http://suporte12_app:8080` ← nome do container na rede Docker

> Com esta opção **não é preciso publicar a porta `8090`** no host — o
> `cloudflared` fala direto com o container pela rede interna. É possível remover
> o bloco `ports:` do `docker-compose.prod.yml` para fechar totalmente a
> superfície (mantenha apenas se quiser acesso local para debug).

**Opção B — `cloudflared` como serviço do host (systemd).** Instale o
`cloudflared` no host e aponte o **Service** do túnel para a porta publicada:
- **Service:** `http://localhost:8090`

Requer manter o bind `127.0.0.1:8090` do overlay de prod.

#### 12.2.3 Ajustes no `.env` decorrentes

| Variável | Valor | Motivo |
|---|---|---|
| `APP_URL` | `https://suporte.seu-dominio.com.br` | Domínio público do túnel (HTTPS) |
| `L5_SWAGGER_CONST_HOST` | = `APP_URL` | — |
| `SESSION_SECURE_COOKIE` | `true` | A borda serve HTTPS; depende do trustProxies (12.2.1) |
| `CLOUDFLARE_TUNNEL_TOKEN` | _(token do painel)_ | Apenas na Opção A (container) |

#### 12.2.4 Configuração recomendada no painel Cloudflare

- **SSL/TLS → Overview:** modo **Full** (ou **Full (strict)**). Com túnel a conexão
  de origem já é cifrada pela Cloudflare; **não** use **Flexible**.
- **SSL/TLS → Edge Certificates:** "Always Use HTTPS" **ligado**.
- **Webhook do WhatsApp:** garanta que `https://<APP_URL>/api/webhook/whatsapp` não
  seja bloqueado por **WAF / Bot Fight Mode**. Crie regra de *skip* para esse path
  se a Evolution API receber `403`/challenge.

#### 12.2.5 Pontos de atenção da Cloudflare

- **Timeout de ~100s (erro 524):** o proxy da Cloudflare aborta respostas mais
  longas que ~100s. O nginx interno permite 120s (`fastcgi_read_timeout 120`).
  Relatórios pesados podem estourar o limite da borda antes do nginx — preferir
  processar via fila (já há `suporte12_worker`) / download assíncrono.
- **Limite de upload:** plano Free da Cloudflare limita o corpo a **100 MB**; o
  nginx limita a **64 MB** (`client_max_body_size 64m`). Anexos/mídia de WhatsApp
  abaixo de 64 MB passam normalmente. Para aumentar, ajuste ambos.
- **Cache de conteúdo dinâmico:** **não** habilite "Cache Everything" no domínio —
  respostas autenticadas não devem ser cacheadas. O padrão (cachear só estáticos
  por extensão) é seguro; os assets do Vite em `/build/` já vêm com
  `Cache-Control: immutable`.
- **HSTS com `preload`:** em produção a aplicação envia
  `Strict-Transport-Security: max-age=31536000; includeSubDomains; preload`. Após o
  primeiro acesso o domínio **só** abrirá via HTTPS — estabilize o túnel antes de
  divulgar o domínio.
- **IP real do cliente:** com `trustProxies` ativo, o IP vem do `X-Forwarded-For`
  preenchido pela Cloudflare (há também `CF-Connecting-IP`). Os rate limiters por
  IP (seção 3.6) passam a usar o IP real, não o do túnel.

#### 12.2.6 Verificação pós-túnel

```bash
# 1. Túnel conectado (Opção A)
docker logs --tail=50 suporte12_cloudflared    # esperado: "Registered tunnel connection"

# 2. Health check pela URL pública
curl -fsS https://suporte.seu-dominio.com.br/up && echo OK

# 3. HTTPS detectado pela app (se trustProxies falhar, o cookie não vem "secure")
curl -sI https://suporte.seu-dominio.com.br/admin/login | grep -iE "set-cookie|location"
#   - cookie de sessão deve conter "; secure"
#   - não deve haver redirect para http://

# 4. Webhook do WhatsApp acessível externamente
curl -I https://suporte.seu-dominio.com.br/api/webhook/whatsapp
#   -> 401/405 esperado (sem assinatura/sem POST); NÃO deve ser 403 da Cloudflare
```

---

## Suposições feitas e o que revisar manualmente

### Suposições

1. **Hospedagem é um único VPS Linux** rodando Docker, com reverse proxy externo terminando TLS. O projeto não traz nenhum playbook de provedor (AWS/GCP/Azure/Hostinger), então o `deploy.md` assume self-hosted.
2. **A imagem é construída no próprio servidor** (`make prod` faz `--build`). Não há registry/CI configurado — `docker-compose.prod.yml` referencia `suporte12_app:latest` localmente.
3. **`scripts/backup.sh` roda no host** (não no container). O cron precisa ser configurado fora do compose.
4. **`storage/app/`** é persistido apenas indiretamente: o compose de prod **não** monta volume para `storage/`. Anexos sobrevivem enquanto o container não for `down`, mas serão perdidos em rebuilds destrutivos. Forte recomendação: adicionar `- ./storage:/var/www/html/storage` (ou volume nomeado) em `docker-compose.prod.yml`.
5. **Evolution API roda fora** do compose de produção (em dev existe `suporte12_evolution` no overlay dev, mas não há overlay de prod para ela). O deploy assume que existe uma instância Evolution externa apontada por `WHATSAPP_API_URL`.
6. **`APP_KEY` é gerada uma vez** e mantida ao longo dos deploys — não regerar; mudá-la invalidaria sessões e dados criptografados.
7. **Roteamento externo / TLS** está definido como **Cloudflare Tunnel em servidor local** (ver seção 12.2). O `cloudflared` encaminha para `suporte12_app:8080` (ou `127.0.0.1:8090`) e envia os `X-Forwarded-*` — exige o ajuste de `trustProxies` (12.2.1).
8. **Migrações são executadas automaticamente** pelo entrypoint quando `APP_ENV=production`. Em ambientes com várias réplicas, isso causaria race conditions — hoje só existe **uma** réplica, então é seguro.

### Recomendações para revisão manual

- [ ] **Persistência de `storage/`** — decidir se adiciona volume no overlay de prod. Sem isso, qualquer rebuild remove anexos.
- [ ] **Trusted Proxies (CÓDIGO — pré-requisito do Cloudflare Tunnel)** — adicionar `$middleware->trustProxies(...)` em `bootstrap/app.php` (ver 12.2.1). **Sem isso a aplicação não funciona corretamente atrás do túnel** (URLs `http://`, cookie de sessão descartado, login quebrado).
- [x] **Reverse proxy + TLS** — **definido:** Cloudflare Tunnel em servidor local (ver 12.2). Versionar o overlay `docker-compose.cloudflared.yml` junto do repositório.
- [ ] **Tageamento de imagens** — hoje sempre `latest`. Adotar `suporte12_app:v<git-sha>` simplifica rollback sem rebuild.
- [ ] **Pipeline de CI/CD** — não há `.github/workflows`, `.gitlab-ci.yml` etc. **A definir.**
- [ ] **Off-site backups** — `scripts/backup.sh` grava local; integrar com S3/B2.
- [ ] **Monitoramento externo** — uptime (UptimeRobot/StatusCake), APM (Sentry/New Relic), métricas de host (Prometheus/node-exporter). **A definir.**
- [ ] **Estratégia de release com schema breaking change** — hoje migrations rodam no boot do `app` sem janela de manutenção formal.
- [ ] **Rotação do `storage/logs/laravel.log`** — adicionar `logrotate` no host.
- [ ] **Multi-réplica / HA** — arquitetura atual é single-node. Se for escalar, separar MySQL/Redis para serviços geridos, remover migrate do entrypoint e mover para job dedicado.
- [ ] **Revisar `WHATSAPP_MSG_*`** — todos vazios no `.env.production.example`, caindo nos defaults de `config/whatsapp.php`. Confirmar se os defaults estão adequados para o cliente final.
- [ ] **`SCOUT_DRIVER=database`** — funcional para volume baixo. Se a busca ficar lenta, avaliar Meilisearch/Algolia.
- [ ] **Secrets** — hoje vivem em `.env` no host. Avaliar Vault/SOPS/Doppler.
