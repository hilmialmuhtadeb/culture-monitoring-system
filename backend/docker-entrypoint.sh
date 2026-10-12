#!/bin/sh
set -e

# Default port Render adalah 10000 jika $PORT tidak didefinisikan
PORT="${PORT:-10000}"
echo "Configuring Nginx to listen on port: $PORT"
sed -i "s/__PORT__/$PORT/g" /etc/nginx/nginx.conf

echo "Optimizing Laravel cache..."
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

echo "Linking storage..."
php artisan storage:link || true

echo "Running database migrations..."
php artisan migrate --force

if [ "$RUN_SEEDERS" = "true" ]; then
    echo "Running initial database seeders..."
    php artisan db:seed --force || true
fi

echo "Starting PHP-FPM..."
php-fpm -D

echo "Starting Nginx on port $PORT..."
exec nginx -g "daemon off;"
