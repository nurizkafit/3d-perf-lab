#!/usr/bin/env bash
# Builds a release archive for Hostinger (DEPLOYMENT.md §5.1 steps 1–3) on the developer machine.
# Output: release/3d-perf-lab-<git short hash>-<date>.tar.gz with app-core/ (the Laravel project, production vendor/)
# and public_html/ (the contents of public/ with the built assets and the front controller from deploy/).
# It never includes .env files, node_modules/, storage contents, fixtures/, docs/, tests/, or the graph.
set -euo pipefail
cd "$(dirname "$0")/.."

name="3d-perf-lab-$(git rev-parse --short HEAD)-$(date +%Y%m%d-%H%M)"
work="release/${name}"
rm -rf "${work}"
mkdir -p "${work}/app-core" "${work}/public_html"

echo "1/4 Building front-end assets…"
npm run build >/dev/null

echo "2/4 Copying the project…"
rsync -a ./ "${work}/app-core/" \
    --exclude '.git/' --exclude 'node_modules/' --exclude 'vendor/' --exclude 'release/' \
    --exclude '.env' --exclude '.env.*' \
    --exclude 'storage/verify/' --exclude 'storage/app/private/' --exclude 'storage/logs/*.log' \
    --exclude 'storage/framework/sessions/*' --exclude 'storage/framework/views/*' --exclude 'storage/framework/cache/data/*' \
    --exclude 'fixtures/' --exclude 'docs/' --exclude 'tests/' --exclude 'graphify-out/' --exclude 'm13/' \
    --exclude '.claude/' --exclude 'CLAUDE.md' --exclude '.graphifyignore' --exclude 'bin/' --exclude 'deploy/' --exclude 'phpunit.xml' --exclude '*.png' --exclude '.DS_Store' --exclude '.phpunit.result.cache' --exclude 'public/hot'
cp .env.production.example "${work}/app-core/.env.production.example"
cp deploy/app-core.htaccess "${work}/app-core/.htaccess"
mkdir -p "${work}/app-core/storage/app/private"

echo "3/4 Installing production dependencies…"
composer install --no-dev --optimize-autoloader --no-interaction --quiet --working-dir="${work}/app-core"

echo "4/4 Moving public/ to public_html/ and packing…"
cp -R "${work}/app-core/public/." "${work}/public_html/"
rm -rf "${work}/app-core/public"
cp deploy/public_html/index.php "${work}/public_html/index.php"
tar -czf "release/${name}.tar.gz" -C release "${name}"
rm -rf "${work}"
echo "Done: release/${name}.tar.gz ($(du -h "release/${name}.tar.gz" | cut -f1))"
