#!/bin/sh
set -e

# Wait for PostgreSQL to be ready
echo "Waiting for PostgreSQL to be ready..."
until pg_isready -h postgres -U ${DB_USERNAME:-postgres} >/dev/null 2>&1; do
  echo "PostgreSQL is unavailable - sleeping"
  sleep 2
done
echo "PostgreSQL is up"

# Wait for Redis to be ready
echo "Waiting for Redis to be ready..."
until redis-cli -h redis ping >/dev/null 2>&1; do
  echo "Redis is unavailable - sleeping"
  sleep 2
done
echo "Redis is up"

# Generate APP_KEY if not set
if [ -z "$APP_KEY" ]; then
  echo "Generating APP_KEY..."
  php artisan key:generate --force
fi

# Run migrations
echo "Running database migrations..."
php artisan migrate --force

# Clear and cache configuration
echo "Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Create storage link
php artisan storage:link || true

# Set permissions
chown -R www-data:www-data /app/storage
chmod -R 775 /app/storage

echo "Application setup complete"

# Execute the main command
exec "$@"
