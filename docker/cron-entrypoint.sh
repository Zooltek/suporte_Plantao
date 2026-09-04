#!/bin/sh
set -e

# Cron entrypoint para o scheduler do Laravel
# Executa o cron do Laravel a cada minuto

echo "[*] Iniciando cron do Laravel..."

# Cria o crontab para o usuário appuser
# O artisan schedule:run deve ser executado no diretório do aplicativo
cat > /tmp/laravel-cron <<'EOF'
* * * * * cd /var/www/html && php artisan schedule:run >> /dev/null 2>&1
EOF

crontab /tmp/laravel-cron
rm /tmp/laravel-cron

echo "[*] Cron configurado. Iniciando cron daemon..."

# Inicia o cron daemon em foreground
exec crond -f -l 2
