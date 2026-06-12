#!/usr/bin/env bash
#
# Deploy-script voor Combell shared hosting (Laravel / TALL-stack).
#
# Gebruik:  log in via SSH, ga naar de projectmap en draai:  bash deploy.sh
#
# Bouwt BEWUST geen frontend-assets: de Combell-Node is te oud voor Vite.
# Bouw lokaal (`npm run build`), commit `public/build/` mee en push — dit
# script haalt dat via `git pull` op. Zie ook bin/release.sh (lokaal).
#
# Het script leidt alle paden zelf af uit zijn eigen locatie, zodat het
# zonder aanpassing werkt op elke Combell-site met dezelfde layout
# (~/<project> naast ~/www).
set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"   # de projectmap (repo-root)
WWW_DIR="$(dirname "$APP_DIR")/www"                       # de vaste Combell-docroot
PUBLIC_DIR="$APP_DIR/public"                              # Laravel's public-map

cd "$APP_DIR"

echo "==> 1/7  Git pull (code + gebouwde assets)"
git pull --ff-only

echo "==> 2/7  Composer install (productie)"
composer install --no-dev --optimize-autoloader --no-interaction

echo "==> 3/7  Oude caches leegmaken"
php artisan optimize:clear

echo "==> 4/7  Database-migraties"
php artisan migrate --force

echo "==> 5/7  /www docroot opnieuw koppelen aan public"
# Combell's docroot staat vast op /www en mag geen symlink ZIJN (nginx weigert
# dat). Daarom is /www een echte map met een mini-index.php + symlinks naar de
# public-assets (symlinks BINNEN de docroot werken wel). We bouwen 'm elke deploy
# vers op, zodat nieuwe bestanden in public/ automatisch meekomen.
if [ ! -e "$PUBLIC_DIR/build/manifest.json" ]; then
  echo "    !! WAARSCHUWING: public/build ontbreekt. Lokaal 'npm run build' gedraaid en gecommit?" >&2
fi
mkdir -p "$WWW_DIR"
find "$WWW_DIR" -mindepth 1 -delete
find "$PUBLIC_DIR" -maxdepth 1 -mindepth 1 ! -name index.php ! -name storage \
  -exec ln -s {} "$WWW_DIR/" \;
ln -s "$APP_DIR/storage/app/public" "$WWW_DIR/storage"
printf "%s\n" "<?php require '$PUBLIC_DIR/index.php';" > "$WWW_DIR/index.php"

echo "==> 6/7  Caches heropbouwen (productiesnelheid)"
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan filament:optimize 2>/dev/null || true

echo "==> 7/7  Schrijfrechten op storage + cache"
chmod -R 775 storage bootstrap/cache

echo ""
echo "✅ Deploy klaar."
