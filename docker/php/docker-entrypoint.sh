#!/bin/bash
set -e

# Fix storage permissions (volume mount overwrites Dockerfile chown)
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Copy .env if it doesn't exist
if [ ! -f /var/www/.env ]; then
    cp /var/www/.env.example /var/www/.env
fi

# Generate APP_KEY only if missing or empty
if ! grep -q '^APP_KEY=.\+' /var/www/.env; then
    php artisan key:generate --force --no-interaction
fi

# Publish only config files (migrations are already in database/migrations/)
php artisan vendor:publish --tag=sanctum-config --quiet || true
php artisan vendor:publish --tag=permission-config --quiet || true

# Clear config cache so fresh config files take effect
php artisan config:clear --quiet

# Run migrations (files are pinned in repo — safe to re-run, already-run ones are skipped)
php artisan migrate --force

# Seed database (seeders use firstOrCreate — idempotent)
php artisan db:seed --force || true

exec "$@"
