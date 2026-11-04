#!/bin/sh
# Inicialização do contêiner: prepara o banco SQLite com dados de demonstração
# relativos à data de hoje e sobe o Apache.
set -e
cd /var/www/html

# URL pública: o Render informa em RENDER_EXTERNAL_URL
export APP_URL="${APP_URL:-${RENDER_EXTERNAL_URL:-http://localhost:${PORT}}}"

# Chave da aplicação: usa a configurada ou gera uma para esta execução
if [ -z "$APP_KEY" ]; then
    APP_KEY="base64:$(head -c 32 /dev/urandom | base64)"
    export APP_KEY
fi

mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs storage/app/public
touch database/database.sqlite

echo "→ Criando banco e dados de demonstração..."
php artisan migrate:fresh --seed --force
php artisan storage:link --force || true

echo "→ Otimizando..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan filament:optimize || true

chown -R www-data:www-data storage bootstrap/cache database

# Rotina automática (expira pendentes, marca no-show) a cada minuto, como o cron faria
(
    while true; do
        sleep 60
        runuser -u www-data -- php artisan schedule:run --no-interaction > /dev/null 2>&1 || true
    done
) &

echo "→ Aurora no ar em ${APP_URL}"
exec apache2-foreground
