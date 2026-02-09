#!/bin/sh

# Wait for services to start
echo "Waiting for services to start..."
sleep 10

# Try to generate APP_KEY if not set (non-blocking)
if [ -z "$APP_KEY" ]; then
  echo "Generating APP_KEY..."
  php artisan key:generate --force 2>/dev/null || true
fi

# Delete stale cache files if they exist (non-blocking)
rm -f /app/bootstrap/cache/config.php 2>/dev/null || true
rm -f /app/bootstrap/cache/routes-*.php 2>/dev/null || true
rm -f /app/bootstrap/cache/services.php 2>/dev/null || true

# Try to run migrations (non-blocking) - skip cache commands that trigger blade-icons issue
echo "Running database migrations..."
php artisan migrate --force 2>/dev/null || echo "Migrations failed - will retry on next start"

# Create storage link (non-blocking)
php artisan storage:link 2>/dev/null || true

# Set permissions
chown -R www-data:www-data /app/storage 2>/dev/null || true
chmod -R 775 /app/storage 2>/dev/null || true

echo "Application ready - starting PHP-FPM"

# Execute the main command
if [ "$1" = "php-fpm" ]; then
  exec /usr/local/sbin/php-fpm --nodaemonize --fpm-config /usr/local/etc/php-fpm.conf
else
  exec "$@"
fi
