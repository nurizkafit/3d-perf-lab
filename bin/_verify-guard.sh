# Shared guard for bin/verify-*.sh. Sourced, not executed.
# Clears the config cache, then refuses to continue unless the verify environment
# points at the verification database and the verification storage root (AGENT.md §9).

set -euo pipefail
cd "$(dirname "$0")/.."

EXPECTED_DB="render_lab_verify"

if [ ! -f .env.verify ]; then
    echo "Refused: .env.verify is missing. Copy .env.verify.example to .env.verify and fill it in." >&2
    exit 1
fi

php artisan config:clear --env=verify >/dev/null

read -r CONFIGURED_DB STORAGE_ROOT < <(php artisan tinker --env=verify --execute='echo config("database.connections.".config("database.default").".database")." ".config("filesystems.disks.local.root");')

if [ "$CONFIGURED_DB" != "$EXPECTED_DB" ]; then
    echo "Refused: the verify environment uses database '$CONFIGURED_DB', not '$EXPECTED_DB'." >&2
    exit 1
fi

case "$STORAGE_ROOT" in
    "$(pwd)/storage/verify/"*) ;;
    *)
        echo "Refused: the verify environment uses storage '$STORAGE_ROOT', not storage/verify/." >&2
        exit 1
        ;;
esac
