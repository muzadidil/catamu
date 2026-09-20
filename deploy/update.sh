#!/bin/bash
#
# Update CATAMU di server (Hostinger / Niagahoster, lewat SSH).
#
#   cd ~/domains/<domain>/public_html
#   bash deploy/update.sh
#
# Kalau `php` di SSH bukan versi 8.2:
#   PHP_BIN=/usr/bin/php8.2 bash deploy/update.sh
#
# Catatan: skrip ini sengaja TIDAK memakai `config:cache` dan `route:cache`.
# routes/web.php memakai closure (baris 91 dan 172) sehingga route:cache selalu
# gagal, dan config yang ter-cache membuat perubahan .env tidak terbaca. Pada
# skala demo selisih kecepatannya tidak terasa.

set -euo pipefail

cd "$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PHP_BIN="${PHP_BIN:-php}"

echo "==> Folder aplikasi : $PWD"
echo "==> PHP             : $("$PHP_BIN" -r 'echo PHP_VERSION;')"

if [ ! -d .git ]; then
  echo "!! Folder ini bukan hasil clone git, jadi tidak ada yang bisa di-pull."
  echo "   Lihat bagian 'Kalau server belum berupa clone git' di CATATAN-DEPLOY-ADATAMU.md."
  exit 1
fi

# Berhenti kalau ada file yang diedit langsung di server, supaya git pull tidak
# menimpa perbaikan darurat yang belum sempat masuk ke repo.
if [ -n "$(git status --porcelain)" ]; then
  echo "!! Ada file yang berubah di server. Periksa dulu sebelum update:"
  git status --short
  exit 1
fi

if command -v composer >/dev/null 2>&1; then
  COMPOSER_CMD="composer"
elif [ -f composer.phar ]; then
  COMPOSER_CMD="$PHP_BIN composer.phar"
else
  echo "==> composer belum ada, mengunduh composer.phar (sekali saja)"
  curl -sS https://getcomposer.org/installer | "$PHP_BIN" -- --install-dir=. --filename=composer.phar
  COMPOSER_CMD="$PHP_BIN composer.phar"
fi

# Apa pun yang terjadi di bawah, aplikasi harus kembali menyala.
trap '"$PHP_BIN" artisan up >/dev/null 2>&1 || true' EXIT

"$PHP_BIN" artisan down --retry=15 || true

git pull --ff-only origin main
$COMPOSER_CMD install --no-dev --optimize-autoloader --no-interaction
"$PHP_BIN" artisan migrate --force
"$PHP_BIN" artisan config:clear
"$PHP_BIN" artisan view:clear
"$PHP_BIN" artisan cache:clear

echo "==> Selesai. Versi sekarang: $(git log --oneline -1)"
