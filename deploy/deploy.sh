#!/usr/bin/env bash
# Deploys the committed code (HEAD) to the GoDaddy cPanel server over SSH.
#
#   bash deploy/deploy.sh            # build, upload, install, migrate, switch to the new version
#   bash deploy/deploy.sh rollback   # switch back to the previous version (code only – not the database)
#
# Server layout (all under ~/usman-electronics):
#   releases/<date>-<commit>/   one folder per deployed version (last 3 kept)
#   current -> releases/...     the live version; ~/public_html points to current/public
#   shared/.env                 server settings and secrets (never in Git, never overwritten)
#   shared/storage/             uploaded images, quote files, logs, backups (kept between versions)
#
# Needs: SSH key ~/.ssh/usman_godaddy authorised in cPanel → SSH Access, Node/npm locally (the server has no Node).
set -euo pipefail

SSH_USER="${DEPLOY_USER:-juxq9yqhbiw8}"
SSH_HOST="${DEPLOY_HOST:-118.139.163.54}"
SSH_KEY="${DEPLOY_KEY:-$HOME/.ssh/usman_godaddy}"
APP_DIR="usman-electronics"   # relative to the home folder on the server

ssh_run() { ssh -i "$SSH_KEY" -o BatchMode=yes -o LogLevel=ERROR "$SSH_USER@$SSH_HOST" "$@"; }

cd "$(dirname "$0")/.."

if [[ "${1:-}" == "rollback" ]]; then
    ssh_run "bash -s" <<EOF
set -e
cd ~/$APP_DIR
current=\$(readlink current)
previous=\$(ls -1d releases/* | grep -v "\$current" | sort | tail -1)
[ -n "\$previous" ] || { echo "No previous version to roll back to."; exit 1; }
ln -sfn "\$previous" current.tmp && mv -Tf current.tmp current
cd current && php artisan optimize >/dev/null && echo "Rolled back to \$previous"
EOF
    exit 0
fi

if [[ -n "$(git status --porcelain)" ]]; then
    echo "Note: uncommitted changes are NOT deployed – only the last commit ($(git rev-parse --short HEAD))."
fi

RELEASE="$(date +%Y%m%d-%H%M%S)-$(git rev-parse --short HEAD)"
ARCHIVE="$(mktemp -d)/release.tar"

echo "→ Building CSS/JS"
rm -f public/hot   # a leftover dev-server file makes the live site load assets from localhost
npm run build >/dev/null

echo "→ Packing $RELEASE"
git archive --format=tar -o "$ARCHIVE" HEAD
tar -rf "$ARCHIVE" public/build
gzip -f "$ARCHIVE"

echo "→ Uploading"
scp -i "$SSH_KEY" -o BatchMode=yes -o LogLevel=ERROR -q "$ARCHIVE.gz" "$SSH_USER@$SSH_HOST:~/$APP_DIR-upload.tar.gz"

echo "→ Installing on the server"
ssh_run "bash -s" <<EOF
set -e
cd ~
mkdir -p $APP_DIR/releases $APP_DIR/shared/storage
RELEASE_DIR=~/$APP_DIR/releases/$RELEASE
mkdir -p "\$RELEASE_DIR"
tar -xzf $APP_DIR-upload.tar.gz -C "\$RELEASE_DIR"
rm -f $APP_DIR-upload.tar.gz

# Storage and settings are shared between versions
if [ ! -d ~/$APP_DIR/shared/storage/framework ]; then
    cp -r "\$RELEASE_DIR/storage/." ~/$APP_DIR/shared/storage/
fi
mkdir -p ~/$APP_DIR/shared/storage/app/private ~/$APP_DIR/shared/storage/framework/{cache/data,sessions,views} ~/$APP_DIR/shared/storage/logs
rm -rf "\$RELEASE_DIR/storage"
ln -s ~/$APP_DIR/shared/storage "\$RELEASE_DIR/storage"
[ -f ~/$APP_DIR/shared/.env ] && ln -s ~/$APP_DIR/shared/.env "\$RELEASE_DIR/.env"

cd "\$RELEASE_DIR"
composer install --no-dev --optimize-autoloader --no-interaction --no-progress -q
chmod -R u+rwX,g+rwX bootstrap/cache ~/$APP_DIR/shared/storage
ln -sfn ~/$APP_DIR/shared/storage/app/public public/storage

if grep -q '^APP_KEY=base64:' ~/$APP_DIR/shared/.env 2>/dev/null; then
    php artisan migrate --force
    php artisan optimize
    php artisan filament:optimize >/dev/null 2>&1 || true
else
    echo "!! shared/.env missing or without APP_KEY – skipped migrations/caches (first-time setup)."
fi

# Go live: switch the 'current' link in one step
cd ~/$APP_DIR
ln -sfn "releases/$RELEASE" current.tmp && mv -Tf current.tmp current
php current/artisan queue:restart >/dev/null 2>&1 || true

# Keep the last 3 versions
ls -1d releases/* | sort | head -n -3 | xargs -r rm -rf
echo "Live version: $RELEASE"
EOF
echo "✓ Done"
