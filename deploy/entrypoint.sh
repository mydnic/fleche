#!/bin/sh
# Runs before anything else in the container: make sure APP_KEY exists, then on
# the web boot migrate, seed, create the first admin and warm the caches.
set -e

# Generated once into the storage volume, reused on every later boot: a new
# key on restart would log everybody out and break encrypted values.
# An APP_KEY set in .env always wins.
KEYS=/var/www/storage/app/.keys.env

if [ -z "$APP_KEY" ]; then
    if [ ! -f "$KEYS" ]; then
        mkdir -p "$(dirname "$KEYS")"
        echo "APP_KEY=base64:$(php -r 'echo base64_encode(random_bytes(32));')" > "$KEYS"
        chmod 600 "$KEYS"
        chown www-data:www-data "$KEYS"
        echo "Generated APP_KEY into $KEYS. Back up that volume with the database."
    fi

    APP_KEY=$(sed -n 's/^APP_KEY=//p' "$KEYS")
    export APP_KEY
fi

case "$1" in
    /usr/bin/supervisord)
        php artisan migrate --force --isolated
        php artisan storage:link --force >/dev/null 2>&1 || true

        # A typo in ADMIN_PASSWORD must not keep the instance down: the setup
        # screen is still a way in.
        php artisan fleche:install || echo 'Could not create the admin from the environment; use the setup screen.'

        php artisan config:cache
        php artisan route:cache
        php artisan view:cache
        php artisan event:cache
        ;;
esac

exec "$@"
