#!/usr/bin/env bash
#
# Lokaal release-script (draai op je dev-machine, NIET op de server).
#
# Gebruik:  bash bin/release.sh ["commit-boodschap"]
#
# Bouwt de frontend-assets, commit `public/build/` (+ andere wijzigingen) en
# pusht naar main. Daarna log je in op de server en draai je `bash deploy.sh`.
#
# Waarom hier en niet op de server: Combell's Node is te oud voor Vite, dus de
# build gebeurt lokaal en reist als artefact mee in git.
set -euo pipefail

cd "$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"   # repo-root

MSG="${1:-Release: assets gebouwd en gedeployed}"

echo "==> Assets bouwen (Vite)"
npm run build

echo "==> Wijzigingen committen"
git add -A
if git diff --cached --quiet; then
  echo "    Niets te committen — werkboom is schoon."
else
  git commit -m "$MSG"
fi

echo "==> Pushen naar main"
git push origin main

echo ""
echo "✅ Gepusht. Log nu in op de server en draai:  bash deploy.sh"
