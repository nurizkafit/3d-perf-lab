#!/usr/bin/env bash
# Serves the app against the verification environment on port 8001.
source "$(dirname "$0")/_verify-guard.sh"

exec php artisan serve --env=verify --port=8001
