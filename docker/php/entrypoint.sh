#!/bin/sh
# Punto de entrada del contenedor "app": corre cada vez que el contenedor arranca.
#
# Prepara lo que depende de las variables de este contenedor (.env.docker.app) y después le pasa el
# control al comando del contenedor: php-fpm por defecto, o el que se escriba en
# "docker compose run --rm app <comando>".
#
# Este archivo tiene que tener finales de línea LF (ver .gitattributes): con CRLF de Windows,
# Linux no lo puede ejecutar.
set -eu

cd /var/www/html

# Sin APP_KEY la app no puede cifrar sesiones ni cookies: mejor no arrancar y decir por qué.
# Otros comandos sí se permiten sin clave (por ejemplo, un php artisan para revisar algo).
if [ "${1:-}" = "php-fpm" ] && [ -z "${APP_KEY:-}" ]; then
    echo "ERROR: falta APP_KEY en .env.docker.app." >&2
    echo "Créalo con: powershell -ExecutionPolicy Bypass -File scripts\docker-init.ps1 (docs/docker.md)" >&2
    exit 1
fi

# El volumen de storage podría estar vacío (por ejemplo, si se creó a mano): asegurar sus carpetas
mkdir -p storage/app/private storage/app/public storage/framework/cache/data \
    storage/framework/sessions storage/framework/views storage/logs

# Caché de configuración con las variables de este contenedor. Se guarda en $APP_CONFIG_CACHE
# (/tmp, en memoria) y se rehace en cada arranque: un cambio en .env.docker.app se aplica al
# recrear el contenedor (docker compose up -d).
php artisan config:cache --no-interaction

# Vistas compiladas al día con el código de esta imagen: el volumen de storage sobrevive a los
# rebuilds y podría tener vistas compiladas de una versión anterior
php artisan view:cache --no-interaction

exec "$@"
