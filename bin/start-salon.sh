#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
if [[ ! -f database/database.sqlite ]]; then
  touch database/database.sqlite
fi
php artisan migrate --force
npm run build
php artisan serve --host=0.0.0.0 --port=8000
