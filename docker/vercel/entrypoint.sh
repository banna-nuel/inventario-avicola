#!/bin/sh
# Punto de entrada de la imagen para Vercel (etapa "vercel" del Dockerfile, docs/vercel.md).
# Solo para demostración.
#
# En cada arranque crea una base SQLite nueva dentro del contenedor, con los datos de demostración
# (las fechas del seeder son relativas a "hoy"). Vercel apaga el contenedor tras unos minutos sin
# visitas: lo que se haya registrado se pierde y la próxima visita empieza de cero.
#
# Este archivo tiene que tener finales de línea LF (ver .gitattributes).
set -eu

cd /var/www/html

# APP_KEY llega de las variables del proyecto en Vercel. Sin ella no se pueden cifrar las cookies
# de sesión: mejor no arrancar y decir por qué (el mensaje sale en los logs de Vercel).
if [ -z "${APP_KEY:-}" ]; then
    echo "ERROR: falta APP_KEY. Agrégala en Vercel: Settings > Environment Variables (docs/vercel.md)." >&2
    exit 1
fi

# Caché de configuración con las variables de este contenedor (en /tmp, se rehace en cada arranque)
php artisan config:cache --no-interaction

# Base de demostración nueva: tablas y datos del seeder
mkdir -p "$(dirname "$DB_DATABASE")"
rm -f "$DB_DATABASE"
touch "$DB_DATABASE"
php artisan migrate --seed --force --no-interaction

# Apache atiende las peticiones como www-data: la base y storage/ tienen que ser suyos
chown -R www-data:www-data storage

exec "$@"
