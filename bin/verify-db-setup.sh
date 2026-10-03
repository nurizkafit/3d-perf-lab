#!/usr/bin/env bash
# Creates the verification database if needed, migrates it, and seeds base data and dev fixtures.
# Safe to run repeatedly: migrations run only when pending, seeders are idempotent.
source "$(dirname "$0")/_verify-guard.sh"

DB_SOCKET_ARG=()
DB_SOCKET_VALUE="$(grep -E '^DB_SOCKET=' .env.verify | cut -d= -f2- || true)"
[ -n "$DB_SOCKET_VALUE" ] && DB_SOCKET_ARG=(--socket="$DB_SOCKET_VALUE")
DB_USER="$(grep -E '^DB_USERNAME=' .env.verify | cut -d= -f2-)"
DB_PASS="$(grep -E '^DB_PASSWORD=' .env.verify | cut -d= -f2-)"

MYSQL_PWD="$DB_PASS" mysql -u"$DB_USER" "${DB_SOCKET_ARG[@]}" \
    -e "CREATE DATABASE IF NOT EXISTS \`$EXPECTED_DB\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"

mkdir -p storage/verify/private

php artisan migrate --env=verify --force
php artisan db:seed --env=verify --force
php artisan db:seed --env=verify --force --class='Database\Seeders\DevFixtureSeeder'

echo "Verification environment ready: database $EXPECTED_DB, storage storage/verify/."
