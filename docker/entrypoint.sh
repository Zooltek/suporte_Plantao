#!/bin/bash
set -e

echo "[Entrypoint] Iniciando container Laravel..."

# Permite sobrescrever a URL pública apenas quando o modo compartilhado precisar
# gerar URLs absolutas voltadas para outras máquinas da rede local.
if [ -n "${APP_PUBLIC_URL:-}" ]; then
  export APP_URL="${APP_PUBLIC_URL}"
  echo "[Entrypoint] APP_URL ajustado a partir de APP_PUBLIC_URL: ${APP_URL}"
fi

# Diretórios de runtime podem não existir na imagem final quando estão vazios
# no checkout. Garantimos isso antes de limpar caches ou iniciar o app.
mkdir -p \
  storage/framework/cache/data \
  storage/framework/sessions \
  storage/framework/testing \
  storage/framework/views \
  storage/logs \
  bootstrap/cache

# Espera MySQL ficar pronto
until php -r "exit(!is_resource(@fsockopen(getenv('DB_HOST'), getenv('DB_PORT') ?: 3306)));"; do
  echo "[Entrypoint] Aguardando MySQL em ${DB_HOST}:${DB_PORT:-3306}..."
  sleep 2
done
echo "[Entrypoint] MySQL disponível."

# Dependências do Composer (apenas em DEV).
# Em DEV o bind-mount do host (.:/var/www/html) sobrepõe o /var/www/html da
# imagem e esconde o vendor/ compilado no build. Num clone novo o vendor/ nem
# existe; após um `git pull` que altere o composer.lock ele fica desatualizado.
# Sem isto, qualquer `php artisan` aborta em "require vendor/autoload.php".
# Em produção o vendor/ vem pronto na imagem e não há bind-mount — não tocamos.
# Para evitar corrida entre os containers que compartilham o mesmo bind-mount,
# apenas o "app" (COMPOSER_AUTO_INSTALL=1) instala; os demais aguardam o vendor/.
if [ "${APP_ENV}" != "production" ]; then
    if [ "${COMPOSER_AUTO_INSTALL:-0}" = "1" ]; then
        if [ ! -f vendor/autoload.php ] || [ composer.lock -nt vendor/autoload.php ]; then
            echo "[Entrypoint] vendor/ ausente ou desatualizado — rodando composer install..."
            COMPOSER_PROCESS_TIMEOUT=2000 composer install --no-interaction --prefer-dist --no-progress
        fi
    elif [ ! -f vendor/autoload.php ]; then
        echo "[Entrypoint] Aguardando o container app preparar o vendor/ (composer install)..."
        until [ -f vendor/autoload.php ]; do
            sleep 3
        done
        echo "[Entrypoint] vendor/ disponível."
    fi
fi

# Chave da aplicação (APP_KEY) — apenas em DEV.
# O .env.example vem com APP_KEY vazio; num clone novo o php-fpm subiria sem
# chave e a primeira request quebraria com Illuminate\Encryption\MissingAppKeyException.
# Apenas o "app" (COMPOSER_AUTO_INSTALL=1) gera a chave, evitando corrida na
# escrita do .env compartilhado pelo bind-mount; os demais apenas aguardam.
# Importante: php-fpm e worker rodam com clear_env=no e herdam a env do container,
# que vem com APP_KEY vazia. Como o Dotenv do Laravel NÃO sobrescreve uma variável
# já presente no ambiente, não basta gravar no .env — é preciso reexportar a chave
# para o processo exec'ado mais abaixo (supervisord/queue:work).
# Em produção a chave vem fixa no .env e NUNCA é gerada automaticamente: cada
# container criaria uma chave diferente, quebrando sessões e dados criptografados.
if [ "${APP_ENV}" != "production" ]; then
    if [ "${COMPOSER_AUTO_INSTALL:-0}" = "1" ]; then
        if ! grep -qE '^APP_KEY=base64:' .env 2>/dev/null; then
            echo "[Entrypoint] APP_KEY ausente — gerando chave da aplicação..."
            php artisan key:generate --force
        fi
    else
        # Espera limitada (não trava o boot para sempre se o app falhar ao gerar a chave).
        echo "[Entrypoint] Aguardando o container app gerar a APP_KEY no .env..."
        app_key_timeout=60
        until grep -qE '^APP_KEY=base64:' .env 2>/dev/null; do
            app_key_timeout=$((app_key_timeout - 2))
            if [ "${app_key_timeout}" -le 0 ]; then
                echo "[Entrypoint] ERRO: APP_KEY não foi gerada pelo container app em 60s — abortando." >&2
                exit 1
            fi
            sleep 2
        done
    fi
    # Sincroniza a env do processo com o .env (clear_env=no herda env vazia do container).
    APP_KEY="$(grep -E '^APP_KEY=' .env | head -n1 | cut -d= -f2-)"
    export APP_KEY
    # Fail-fast: nunca seguir o boot com chave vazia (evita subir app quebrado).
    if [ -z "${APP_KEY}" ]; then
        echo "[Entrypoint] ERRO: APP_KEY continua vazia após geração/sincronização — abortando." >&2
        exit 1
    fi
    echo "[Entrypoint] APP_KEY sincronizada a partir do .env."
fi

# Migrations (idempotente; falhas não impedem o boot em dev)
# Rodam ANTES da limpeza de caches: com CACHE_STORE=database (fallback do
# Laravel 12) o cache:clear faz DELETE na tabela `cache`, que num banco
# zerado só existe após as migrations criarem-na.
echo "[Entrypoint] Executando migrations..."
if [ "${APP_ENV}" = "production" ]; then
    php artisan migrate --force
else
    php artisan migrate --force || true
fi

# Caches do Laravel conforme ambiente
if [ "${APP_ENV}" = "production" ]; then
    echo "[Entrypoint] Ambiente de produção — aplicando caches..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan event:cache
else
    echo "[Entrypoint] Ambiente de desenvolvimento — limpando caches..."
    php artisan optimize:clear || true
fi

# Symlink público para storage/app/public (idempotente; sobrescreve se o alvo mudar)
echo "[Entrypoint] Garantindo symlink public/storage..."
php artisan storage:link --force || true

echo "[Entrypoint] Inicialização concluída."

# Comando passado via compose (ex: queue:work) ou Nginx+PHP-FPM via supervisord
if [ $# -gt 0 ]; then
    echo "[Entrypoint] Executando: $*"
    exec "$@"
else
    echo "[Entrypoint] Iniciando supervisord (nginx + php-fpm)..."
    exec supervisord -c /etc/supervisord.conf
fi
