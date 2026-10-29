# check=skip=FromPlatformFlagConstDisallowed
#
# Dockerfile de Inventario Avícola (Fase 11).
#
# Un Dockerfile es la "receta" para construir una imagen: una foto del sistema de archivos con
# todo lo que la app necesita para correr (PHP, extensiones, código y dependencias).
# Este es "multi-etapa": cada FROM empieza una etapa nueva y las etapas se pasan archivos con
# COPY --from=... Así las herramientas de construcción (Composer, Tailwind, compiladores) no
# terminan en la imagen final, que queda más chica y con menos cosas que puedan fallar o atacarse.
#
# Etapas (docs/docker.md las explica con más detalle):
#   base      PHP 8.4 + las extensiones que usa el proyecto + php.ini de producción
#   composer  1. dependencias de PHP sin las de desarrollo (vendor/)
#   css       2. compila el CSS con el binario oficial de Tailwind (sin npm)
#   runtime   3. la imagen de la app (php-fpm): código + vendor + CSS, sin .env y sin root
#   test      4. opcional: corre php artisan test (docker build --target test .)
#   web       5. nginx con la carpeta public/ (la imagen del servicio web)
#   vercel    6. imagen única para la demo en Vercel: Apache + PHP + SQLite (docs/vercel.md)
#
# compose.yaml siempre construye con --target (runtime y web); no hace falta construir "a mano".
# "vercel" es la última etapa a propósito: Vercel construye el Dockerfile sin --target, y sin
# --target se construye la última.

# --------------------------------------------------------------------------------------------
# Versiones fijas. El tag dice la versión exacta y el digest (sha256:...) identifica la imagen
# byte a byte: aunque alguien vuelva a publicar el mismo tag, Docker baja siempre la misma.
# Para actualizar: cambiar tag y digest juntos (docs/docker.md, "Actualizar versiones").
# --------------------------------------------------------------------------------------------
ARG PHP_IMAGE=php:8.4.27-fpm-alpine3.24@sha256:8ce34c869fae0f8da2c82d8aeda222f0782e8e5ae2b370fb43cbad84e9e2ea55
# La misma versión de PHP con Apache incluido, solo para la etapa vercel. Se actualiza junto con
# PHP_IMAGE: las dos tienen que decir siempre la misma versión.
ARG PHP_APACHE_IMAGE=php:8.4.27-apache-trixie@sha256:be1326658a7d36dba6be79a71212d6ff9c5f854335b8ba77d714da1852c1b2ea
ARG COMPOSER_IMAGE=composer:2.10.3@sha256:af98f42dfff7c68ba8d53c2164fd9fde1087b7d449514baa38c418b1f6bc4bac
ARG NGINX_IMAGE=nginxinc/nginx-unprivileged:1.30.6-alpine3.24@sha256:782bb89768cc87b7f95c18b95baa1af250e5d451f26794f59363540161147c01
ARG DEBIAN_IMAGE=debian:trixie-20261005-slim@sha256:a29215f6a35e51e22adffa17f89e9d2ef06214e64a2bad10d765c46aea49f11f

# Solo para sacar el binario de Composer (la imagen de Composer trae PHP 8.5; nosotros usamos
# el binario sobre nuestra base 8.4, así Composer revisa los requisitos contra el PHP real)
FROM ${COMPOSER_IMAGE} AS composer-bin


# ============================================================================================
# base: PHP-FPM 8.4 sobre Alpine Linux (una distribución mínima, de pocos MB)
# ============================================================================================
FROM ${PHP_IMAGE} AS base

# Extensiones que faltan en la imagen oficial (las demás ya vienen: ctype, dom, fileinfo,
# filter, iconv, mbstring, openssl, session, tokenizer, xml, pdo_sqlite, opcache...):
#   - pdo_mysql: la conexión con MySQL
#   - bcmath: la exige dragon-code/support, una dependencia de laravel-lang (composer.lock)
# Para compilarlas hacen falta herramientas ($PHPIZE_DEPS); se instalan y se borran en el
# mismo paso, así no quedan en la imagen.
RUN set -eux; \
    apk add --no-cache --virtual .herramientas-de-compilacion $PHPIZE_DEPS; \
    docker-php-ext-install -j"$(nproc)" bcmath pdo_mysql; \
    apk del --no-network .herramientas-de-compilacion; \
    cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Ajustes propios encima del php.ini de producción (expose_php, errores, OPcache...)
# y del pool de php-fpm (cuántos procesos atienden peticiones)
COPY docker/php/produccion.ini $PHP_INI_DIR/conf.d/zz-inventario.ini
COPY docker/php/fpm.conf /usr/local/etc/php-fpm.d/zz-inventario.conf

WORKDIR /var/www/html


# ============================================================================================
# 1. composer: dependencias de PHP de producción (vendor/)
# ============================================================================================
FROM base AS composer

COPY --from=composer-bin /usr/bin/composer /usr/bin/composer
# Dentro de la etapa de construcción se corre como root; esto evita el aviso de Composer
ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_NO_INTERACTION=1

# Primero solo composer.json y composer.lock: si no cambian, Docker reutiliza este paso
# (caché) y no vuelve a descargar las dependencias cuando solo cambió el código.
# --no-dev: sin PHPUnit, Pint, Sail... · --no-scripts: los scripts necesitan el código (después)
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-progress

# Con el código ya copiado, se arma el autoloader optimizado (un mapa de todas las clases)
# y se verifica que el PHP de la imagen cumpla lo que pide composer.lock
COPY app ./app
COPY database ./database
RUN composer dump-autoload --no-dev --optimize --no-scripts \
    && composer check-platform-reqs --no-dev


# ============================================================================================
# 2. css: compila resources/css/input.css con el binario standalone de Tailwind v4.3.3
# ============================================================================================
# Debian slim (glibc) en vez de Alpine (musl): el binario para glibc corre sin instalar nada.
# Siempre linux/amd64: el CSS resultante es el mismo en cualquier equipo y así el SHA-256
# fijado abajo siempre corresponde al archivo que se baja.
FROM --platform=linux/amd64 ${DEBIAN_IMAGE} AS css

ARG TAILWIND_VERSION=v4.3.3
# SHA-256 de tailwindcss-linux-x64 v4.3.3, verificado contra el digest de la release en GitHub y
# contra su sha256sums.txt. Si el archivo bajado no coincide, el build se detiene.
ARG TAILWIND_SHA256=dc61b3ac6b8c9ca874c0cc4c57b2409791a64c5540404ca5f5367360babc313a

ADD https://github.com/tailwindlabs/tailwindcss/releases/download/${TAILWIND_VERSION}/tailwindcss-linux-x64 /usr/local/bin/tailwindcss
RUN echo "${TAILWIND_SHA256}  /usr/local/bin/tailwindcss" | sha256sum -c - \
    && chmod 755 /usr/local/bin/tailwindcss

# Tailwind busca las clases en los archivos de la carpeta de trabajo: se copian las carpetas
# que tienen clases (vistas, componentes y el JavaScript propio)
WORKDIR /src
COPY resources ./resources
COPY app ./app
COPY config ./config
COPY public/js ./public/js
RUN tailwindcss -i resources/css/input.css -o public/css/app.css --minify


# ============================================================================================
# 3. runtime: la imagen del servicio "app" (php-fpm)
# ============================================================================================
FROM base AS runtime

# La caché de configuración de Laravel se genera al arrancar el contenedor (docker/php/entrypoint.sh),
# no aquí: aquí no existen APP_KEY ni las credenciales, y no deben quedar guardadas en la imagen.
# Va a /tmp (en memoria, ver compose.yaml) porque el resto del disco es de solo lectura.
ENV APP_CONFIG_CACHE=/tmp/laravel-config.php
# "php artisan tinker" guarda su historial en la carpeta personal ($HOME); con el disco de solo
# lectura, esa carpeta es /tmp
ENV HOME=/tmp

# Código de la app. Los archivos quedan de root: el usuario que corre PHP (www-data) solo puede
# leerlos. tests/, docs/ y los archivos locales no se copian (.dockerignore filtra el resto).
COPY --from=composer /var/www/html/vendor ./vendor
COPY app ./app
COPY bootstrap ./bootstrap
COPY config ./config
COPY database ./database
COPY lang ./lang
COPY public ./public
COPY resources ./resources
COPY routes ./routes
COPY artisan composer.json ./
COPY --from=css /src/public/css/app.css ./public/css/app.css
COPY docker/php/entrypoint.sh /usr/local/bin/inventario-entrypoint

# - storage/: las carpetas que Laravel espera (el volumen se llena con esto la primera vez).
#   Es lo único que puede escribir www-data.
# - Cachés que no dependen del entorno, para no armarlas en cada petición:
#   package:discover (paquetes), route:cache (rutas) y view:cache (vistas Blade compiladas).
RUN set -eux; \
    mkdir -p storage/app/private storage/app/public storage/framework/cache/data \
        storage/framework/sessions storage/framework/views storage/logs; \
    php artisan package:discover --ansi; \
    php artisan route:cache; \
    php artisan view:cache; \
    chown -R www-data:www-data storage; \
    chmod 755 /usr/local/bin/inventario-entrypoint

# Desde aquí todo corre como www-data (uid 82), nunca como root
USER www-data

# php-fpm escucha en el puerto 9000 de la red interna; nginx (servicio web) le pasa index.php
EXPOSE 9000
ENTRYPOINT ["inventario-entrypoint"]
CMD ["php-fpm"]


# ============================================================================================
# 4. test (opcional): docker build --target test .
#    Instala también las dependencias de desarrollo y corre toda la suite con SQLite en memoria
#    (phpunit.xml). Si una prueba falla, el build falla. Esta imagen nunca se usa para correr la app.
# ============================================================================================
FROM base AS test

COPY --from=composer-bin /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_NO_INTERACTION=1

COPY composer.json composer.lock ./
RUN composer install --no-scripts --no-autoloader --prefer-dist --no-progress

COPY . .
COPY --from=css /src/public/css/app.css ./public/css/app.css

# Un .env solo para las pruebas, con una APP_KEY nueva (phpunit.xml no trae una)
RUN set -eux; \
    composer dump-autoload --optimize; \
    mkdir -p storage/app/private storage/app/public storage/framework/cache/data \
        storage/framework/sessions storage/framework/views storage/framework/testing storage/logs; \
    cp .env.example .env; \
    php artisan key:generate --no-interaction; \
    php artisan test


# ============================================================================================
# 5. web: nginx sin privilegios (usuario nginx, uid 101) con la carpeta public/
#    Sirve los archivos estáticos y le pasa a php-fpm solo index.php (docker/nginx/default.conf)
# ============================================================================================
FROM ${NGINX_IMAGE} AS web

COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY docker/nginx/cabeceras-seguridad.conf /etc/nginx/snippets/cabeceras-seguridad.conf
COPY public /var/www/html/public
COPY --from=css /src/public/css/app.css /var/www/html/public/css/app.css


# ============================================================================================
# 6. vercel: imagen única para la demo en Vercel (docs/vercel.md). Solo para mostrar la app.
#    Vercel corre una sola imagen, así que aquí Apache con PHP integrado (imagen oficial
#    php:8.4.x-apache) sirve public/ y ejecuta index.php. La base es SQLite dentro del contenedor,
#    nueva en cada arranque con los datos de demostración: no es para datos reales.
# ============================================================================================
FROM ${PHP_APACHE_IMAGE} AS vercel-base

# bcmath, igual que en base (la exige dragon-code/support); pdo_sqlite ya viene en la imagen
# oficial. Las imágenes Debian de PHP ya traen las herramientas para compilar extensiones.
RUN set -eux; \
    docker-php-ext-install -j"$(nproc)" bcmath; \
    cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Los mismos ajustes de PHP que la pila local (sin subida de archivos, sin errores en pantalla...)
COPY docker/php/produccion.ini $PHP_INI_DIR/conf.d/zz-inventario.ini

WORKDIR /var/www/html


# Revisa que este PHP cumpla lo que pide composer.lock (como hace la etapa composer con la base
# Alpine). Va en una etapa aparte para que Composer no quede en la imagen final.
FROM vercel-base AS vercel-requisitos

COPY --from=composer-bin /usr/bin/composer /usr/bin/composer
COPY composer.json composer.lock ./
COPY --from=composer /var/www/html/vendor ./vendor
RUN COMPOSER_ALLOW_SUPERUSER=1 composer check-platform-reqs --no-dev \
    && touch /requisitos-ok


FROM vercel-base AS vercel

# Valores fijos de la demo; ninguno es secreto. APP_KEY no va aquí: llega de las variables del
# proyecto en Vercel, que también puede cambiar cualquiera de estos.
#   - SQLite dentro del contenedor (storage/demo), nueva en cada arranque (entrypoint).
#   - Sesión en una cookie cifrada: sigue valiendo aunque Vercel abra otra copia del contenedor.
#   - Cookies solo por HTTPS, y TRUSTED_PROXIES=* para creerle al proxy de Vercel que la página es
#     https (bootstrap/app.php). Al contenedor solo se llega a través de ese proxy.
#   - PORT: donde escucha Apache; Vercel usa el 80 si en el proyecto no se define otro.
ENV APP_NAME="Inventario Avícola" \
    APP_ENV=production \
    APP_DEBUG=false \
    APP_TIMEZONE=America/Bogota \
    APP_LOCALE=es \
    APP_FALLBACK_LOCALE=en \
    LOG_CHANNEL=stderr \
    LOG_LEVEL=warning \
    DB_CONNECTION=sqlite \
    DB_DATABASE=/var/www/html/storage/demo/inventario.sqlite \
    SESSION_DRIVER=cookie \
    SESSION_LIFETIME=30 \
    SESSION_SECURE_COOKIE=true \
    CACHE_STORE=database \
    QUEUE_CONNECTION=sync \
    MAIL_MAILER=log \
    TRUSTED_PROXIES=* \
    APP_CONFIG_CACHE=/tmp/laravel-config.php \
    HOME=/tmp \
    PORT=80

# Hace que esta etapa espere a que la revisión de requisitos haya pasado
COPY --from=vercel-requisitos /requisitos-ok /usr/local/share/inventario-requisitos-ok

COPY --from=composer /var/www/html/vendor ./vendor
COPY app ./app
COPY bootstrap ./bootstrap
COPY config ./config
COPY database ./database
COPY lang ./lang
COPY public ./public
COPY resources ./resources
COPY routes ./routes
COPY artisan composer.json ./
COPY --from=css /src/public/css/app.css ./public/css/app.css
COPY docker/vercel/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/vercel/entrypoint.sh /usr/local/bin/inventario-vercel-entrypoint

# - Apache: escucha solo en $PORT, con el módulo de cabeceras y sin listados de carpetas ni
#   página de estado (/server-status).
# - storage/: las carpetas que Laravel espera, más storage/demo para la base SQLite.
# - Cachés que no dependen del entorno: paquetes, rutas y vistas compiladas.
# - storage/ queda de www-data, el usuario con el que Apache atiende las peticiones; el código
#   queda de root y www-data solo puede leerlo.
RUN set -eux; \
    echo 'Listen ${PORT}' > /etc/apache2/ports.conf; \
    a2enmod headers; \
    a2dismod -f autoindex status; \
    mkdir -p storage/app/private storage/app/public storage/framework/cache/data \
        storage/framework/sessions storage/framework/views storage/logs storage/demo; \
    php artisan package:discover --ansi; \
    php artisan route:cache; \
    php artisan view:cache; \
    chown -R www-data:www-data storage; \
    chmod 755 /usr/local/bin/inventario-vercel-entrypoint

EXPOSE 80
ENTRYPOINT ["inventario-vercel-entrypoint"]
CMD ["apache2-foreground"]
