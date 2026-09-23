#!/bin/sh
set -e

# Render injects DATABASE_URL when Postgres is linked; Laravel reads DB_URL.
if [ -n "${DATABASE_URL}" ] && [ -z "${DB_URL}" ]; then
  export DB_URL="${DATABASE_URL}"
fi

php artisan storage:link --force 2>/dev/null || true

if [ -n "${APP_KEY}" ]; then
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
fi

if [ -n "${DB_URL}" ] || [ -n "${DB_HOST}" ]; then
  php artisan migrate --force --no-interaction
fi

exec "$@"
