#!/usr/bin/env bash
# Drops all tables in the verification database, deletes storage/verify/private,
# then rebuilds both. Verification environment only; the guard refuses anything else.
source "$(dirname "$0")/_verify-guard.sh"

echo "Resetting $CONFIGURED_DB and $STORAGE_ROOT"
rm -rf storage/verify/private
mkdir -p storage/verify/private

php artisan migrate:fresh --env=verify --force
php artisan db:seed --env=verify --force
php artisan db:seed --env=verify --force --class='Database\Seeders\DevFixtureSeeder'

echo "Verification environment reset."
