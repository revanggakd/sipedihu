#!/bin/sh
set -e

# Generate .env dari environment variables
cat > /var/www/html/.env << EOF
APP_NAME=${APP_NAME:-SIPEDIHU}
APP_ENV=${APP_ENV:-production}
APP_KEY=${APP_KEY}
APP_DEBUG=${APP_DEBUG:-false}
APP_URL=${APP_URL}

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=${DB_HOST}
DB_PORT=${DB_PORT:-3306}
DB_DATABASE=${DB_DATABASE}
DB_USERNAME=${DB_USERNAME}
DB_PASSWORD=${DB_PASSWORD}

BROADCAST_DRIVER=log
CACHE_DRIVER=database
QUEUE_CONNECTION=sync
SESSION_DRIVER=database
SESSION_LIFETIME=120

TELEGRAM_BOT_TOKEN=${TELEGRAM_BOT_TOKEN}
TELEGRAM_CHAT_ID=${TELEGRAM_CHAT_ID}

ESP32_API_KEY=${ESP32_API_KEY}
EOF

# Generate key kalau belum ada
if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force
fi

# Clear cache
php artisan config:clear
php artisan cache:clear

# Jalankan supervisord
exec /usr/bin/supervisord -c /etc/supervisord.conf