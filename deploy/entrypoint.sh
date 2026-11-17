#!/bin/sh
set -e

# Render (and most hosts) tell the container which port to listen on.
PORT="${PORT:-80}"
sed -ri "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# Make env vars (DB_*, SMTP_*) visible to PHP under Apache.
printenv | grep -E '^(DB_|SMTP_|APP_URL|RENDER_EXTERNAL_URL|PORT)' | sed -E 's/^([^=]+)=(.*)$/SetEnv \1 "\2"/' > /etc/apache2/conf-enabled/zz-app-env.conf || true

# First boot: create tables + demo data if the database is empty.
php /var/www/html/deploy/init_db.php || echo "[entrypoint] init_db failed, continuing"

exec apache2-foreground
