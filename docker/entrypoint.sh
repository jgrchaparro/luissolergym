#!/bin/sh
# Entrypoint de producción.
# - Fija el puerto de Apache al que inyecta Render ($PORT)
# - Regenera y calienta la caché de Symfony en prod
# - Pasa el control a Apache (CMD apache2-foreground)

set -e

# Forzamos APP_ENV=prod en runtime. Esto blinda contra:
#   - un APP_ENV=dev heredado del .env del repo
#   - un APP_ENV=dev que alguien haya dejado en el dashboard de Render
# Apache (lanzado con exec más abajo) hereda este export.
export APP_ENV=prod
export APP_DEBUG=0

PORT="${PORT:-10000}"

# Reescribe Listen en ports.conf y el VirtualHost
sed -ri "s!^Listen .*!Listen ${PORT}!g" /etc/apache2/ports.conf
sed -ri "s!<VirtualHost \\*:[0-9]+>!<VirtualHost *:${PORT}>!g" \
    /etc/apache2/sites-available/000-default.conf

# Carpetas volátiles (por si un volumen las vació)
mkdir -p var/cache var/log
chown -R www-data:www-data var

# Cache de Symfony en prod
php bin/console cache:clear   --env=prod --no-debug || true
php bin/console cache:warmup  --env=prod --no-debug || true

echo "[entrypoint] Apache listening on port ${PORT} (APP_ENV=${APP_ENV})"

exec "$@"
