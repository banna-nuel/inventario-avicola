# Inventario Avícola con Docker

Guía para levantar la app con Docker sin haberlo usado antes. Docker es **una forma más** de correr el proyecto: el flujo con XAMPP y `php artisan serve` sigue igual y las dos cosas pueden convivir.

## Índice

1. [Docker en un minuto](#1-docker-en-un-minuto)
2. [Qué hay en esta pila](#2-qué-hay-en-esta-pila)
3. [Requisitos](#3-requisitos)
4. [Primera vez, paso a paso](#4-primera-vez-paso-a-paso)
5. [Uso diario](#5-uso-diario)
6. [Apagar y borrar los datos](#6-apagar-y-borrar-los-datos)
7. [Pruebas](#7-pruebas)
8. [Qué hace cada archivo](#8-qué-hace-cada-archivo)
9. [Protecciones y por qué](#9-protecciones-y-por-qué)
10. [Problemas frecuentes](#10-problemas-frecuentes)
11. [Actualizar versiones](#11-actualizar-versiones)

---

## 1. Docker en un minuto

| Palabra | Qué es | En este proyecto |
|---|---|---|
| **Imagen** | Una "foto" de un sistema con un programa ya instalado y listo. No cambia. | `inventario-avicola/app` (PHP + la app), `inventario-avicola/web` (nginx), `mysql:8.4.11` |
| **Contenedor** | Una imagen en marcha, aislada del resto del equipo. Se puede apagar, borrar y volver a crear desde la imagen. | `app`, `web` y `db` |
| **Dockerfile** | La receta para construir una imagen. | `Dockerfile` |
| **Volumen** | Una carpeta que Docker guarda aparte para que los datos sobrevivan cuando se borra el contenedor. | `mysql-datos` (la base) y `storage` (archivos de Laravel) |
| **Red** | Por dónde se hablan los contenedores. | `edge` (hacia tu equipo) e `internal` (aislada) |
| **Docker Compose** | Levanta varios contenedores juntos, descritos en un archivo. | `compose.yaml` |

La idea: en vez de instalar PHP, MySQL y nginx en Windows, se usan imágenes con versiones exactas. Quien tenga Docker obtiene exactamente el mismo entorno.

---

## 2. Qué hay en esta pila

```
   Tu navegador
        │  http://127.0.0.1:8080   (solo desde este equipo)
        ▼
 ┌──────────────┐   red "edge"
 │ web (nginx)  │
 └──────┬───────┘
        │  red "internal" (sin internet, sin acceso desde fuera)
        │  solo index.php, por el puerto 9000
 ┌──────▼───────┐         ┌──────────────┐
 │ app (PHP 8.4 │────────▶│ db (MySQL    │
 │  + Laravel)  │  3306   │  8.4)        │
 └──────┬───────┘         └──────┬───────┘
        │                        │
   volumen storage         volumen mysql-datos
```

- **web**: nginx sin privilegios. Sirve directamente los archivos de `public/` (CSS, JS, fuentes) y le pasa a PHP solo `index.php`. Agrega las cabeceras de seguridad y comprime con gzip los archivos de texto.
- **app**: PHP-FPM 8.4 con la app. Es el único que habla con la base.
- **db**: MySQL 8.4 (versión LTS, de soporte largo). No tiene puerto publicado: desde Windows no se puede entrar a esta base, solo desde `app`.

---

## 3. Requisitos

- **Docker Desktop** para Windows (usa WSL 2). Comprueba que funciona:
  ```powershell
  docker version
  docker compose version
  ```
- Unos **2 GB libres** de disco (la imagen de MySQL pesa ~1,1 GB; la de la app, ~225 MB; la de nginx, ~93 MB).
- El puerto **8080** libre en tu equipo.
- No hace falta PHP, Composer, MySQL ni Tailwind en Windows: todo se instala dentro de las imágenes.

---

## 4. Primera vez, paso a paso

Todos los comandos van en PowerShell, **dentro de la carpeta del proyecto**.

> **Importante en Windows:** abre la terminal en la carpeta escribiendo la ruta tal como está en el disco. Usa la tecla **Tab** para completar (`cd C:\Users\...\proyectos\Pe` + Tab), o en el Explorador haz click derecho → "Abrir en Terminal". Si escribes una mayúscula distinta (por ejemplo `personales` en vez de `Personales`), `docker compose build` falla con `additional privileges requested` (ver [Problemas frecuentes](#10-problemas-frecuentes)).

### 4.1 Crear el archivo de secretos

```powershell
Copy-Item .env.docker.example .env.docker
```

`.env.docker` tiene la clave de la app y las contraseñas. **No va a Git** (está en `.gitignore`). La plantilla `.env.docker.example` sí está en Git, y nunca lleva valores reales.

### 4.2 Construir las imágenes

```powershell
docker compose build
```

La primera vez baja las imágenes base y tarda unos minutos. En este equipo, sin caché y con las imágenes base ya descargadas, tardó **83 s**. Las siguientes veces reutiliza lo que no cambió: ~10-15 s.

### 4.3 Generar la clave y la contraseña

```powershell
# Clave de cifrado de Laravel (APP_KEY)
docker compose run --rm --no-deps app php artisan key:generate --show

# Una contraseña aleatoria para la base
docker compose run --rm --no-deps app php -r "echo bin2hex(random_bytes(24)), PHP_EOL;"
```

Abre `.env.docker` y pega:

- el primer resultado (empieza con `base64:`) en `APP_KEY=`;
- el segundo en **las dos** líneas `DB_PASSWORD=` y `MYSQL_PASSWORD=`. Tienen que ser iguales: una la usa Laravel para entrar y la otra MySQL para crear el usuario.

`docker compose run --rm` levanta un contenedor de un solo uso, corre el comando y lo borra (`--rm`). `--no-deps` evita levantar la base para esto.

### 4.4 Levantar la pila

```powershell
docker compose up -d
docker compose ps
```

`-d` deja los contenedores corriendo en segundo plano. Espera a que `db` y `web` digan `healthy` en `docker compose ps`; la primera vez tarda ~30 s mientras MySQL se inicializa.

### 4.5 Crear las tablas y los datos de demostración

```powershell
docker compose exec app php artisan migrate --seed --force
```

`docker compose exec app ...` corre un comando dentro del contenedor `app` que ya está funcionando. Las migraciones **no** corren solas al arrancar, a propósito: cambiar la base es una decisión tuya. `--force` es necesario porque la app corre en modo producción.

### 4.6 Entrar

Abre **http://127.0.0.1:8080**.

| Rol | Correo | Contraseña |
|---|---|---|
| Administrador | `admin@test.com` | `12345678` |
| Encargado de inventario | `encargado@test.com` | `12345678` |

Son cuentas de demostración: si algún día la app se publica, se cambian o se borran.

---

## 5. Uso diario

| Quiero... | Comando |
|---|---|
| Prender la app | `docker compose up -d` |
| Ver si está bien | `docker compose ps` |
| Apagarla (los datos quedan) | `docker compose down` |
| Ver los logs (Ctrl+C para salir) | `docker compose logs -f` · solo uno: `docker compose logs -f app` |
| Aplicar cambios del código | `docker compose up -d --build` |
| Correr un comando de artisan | `docker compose exec app php artisan <comando>` |
| Consola de Laravel (tinker) | `docker compose exec app php artisan tinker` |
| Migraciones nuevas | `docker compose exec app php artisan migrate --force` |

- **El código vive dentro de la imagen**, no se lee de tu carpeta. Si cambias un archivo, hay que reconstruir (`docker compose up -d --build`) para verlo en Docker. Para programar a diario sigue siendo más cómodo XAMPP con `php artisan serve`.
- **Si cambias `.env.docker`**, `docker compose up -d` recrea los contenedores afectados con los valores nuevos.
- **Puertos:** la app usa el **8080**. XAMPP usa el 80/443 (Apache) y el 3306 (MySQL), y `php artisan serve` el 8000: no chocan. La base de Docker no se publica en ningún puerto.

---

## 6. Apagar y borrar los datos

```powershell
docker compose down
```

Apaga y borra los **contenedores** y las redes del proyecto. Los **volúmenes** (la base y `storage`) quedan: al volver a hacer `up -d`, los datos siguen ahí.

```powershell
docker compose down -v
```

⚠️ **Borra también los volúmenes: se pierden todos los datos de la base Docker.** No hay papelera. Úsalo solo si quieres empezar de cero (por ejemplo, para que MySQL vuelva a crear el usuario con otra contraseña). Después hay que repetir los pasos 4.4 y 4.5.

Nunca hace falta `docker system prune` ni `docker volume prune` para este proyecto: esos comandos borran cosas de **todos** los proyectos de Docker del equipo.

La base de XAMPP (`inventario_avicola` en tu MySQL de Windows) es otra base distinta: Docker no la toca.

---

## 7. Pruebas

### Toda la suite, dentro de Docker

```powershell
docker build --target test -t inventario-avicola/test:local .
```

Construye la etapa `test`: instala también las dependencias de desarrollo y corre `php artisan test` con SQLite en memoria. Si una prueba falla, el build falla. Resultado actual: **169 pasan y 1 omitida** (la de MySQL), lo mismo que en XAMPP. El `-t` le pone nombre a la imagen (sin nombre, cada corrida dejaría una imagen anónima).

### Movimientos contra MySQL 8.4 real (perfil `test`)

```powershell
docker compose --profile test run --rm pruebas
docker compose --profile test down db-test
```

Levanta una base de pruebas desechable (en memoria, se borra sola) y corre `MovimientoTest` contra ella. Así se confirma lo que SQLite no puede: el bloqueo `select ... for update` de RN-04 y los `SAVEPOINT` de las transacciones. Resultado actual: **23 pasan**, incluida `rn04 en mysql la fila del producto se bloquea con for update`, y MySQL registró 68 `SAVEPOINT` y 5 `ROLLBACK TO SAVEPOINT`. El perfil está apagado por defecto: `docker compose up` no lo levanta. La segunda línea apaga solo la base de pruebas, sin tocar la app.

---

## 8. Qué hace cada archivo

| Archivo | Qué hace |
|---|---|
| `Dockerfile` | La receta de las imágenes, por etapas (ver abajo). |
| `compose.yaml` | Los servicios, sus redes, volúmenes, límites y protecciones. Su `name: inventario-avicola` es el prefijo de contenedores, redes y volúmenes. |
| `.dockerignore` | Qué archivos puede ver Docker al construir. Deja **todo** fuera y solo vuelve a incluir lo necesario: `.env`, `vendor/`, `storage/` (con el respaldo SQL), `docs/`, los `.sql`, los `.zip` y la base SQLite nunca entran. |
| `.env.docker.example` | Plantilla de variables (sin secretos). Se copia como `.env.docker`. |
| `.env.docker` | Tus secretos reales. Ignorado por Git. |
| `docker/php/produccion.ini` | Ajustes de PHP: sin versión en las cabeceras, sin errores en pantalla, OPcache, límites. |
| `docker/php/fpm.conf` | Cuántos procesos de PHP atienden peticiones y sus límites. |
| `docker/php/entrypoint.sh` | Lo que corre cada vez que arranca `app`: revisa `APP_KEY`, arma la caché de configuración y las vistas, y arranca PHP. |
| `docker/nginx/default.conf` | El servidor web: qué sirve nginx, qué pasa a PHP, gzip y caché. |
| `docker/nginx/cabeceras-seguridad.conf` | Las cabeceras de seguridad de todas las respuestas. |
| `.gitattributes` | Fuerza finales de línea LF en los archivos que van a Linux (un `.sh` con CRLF de Windows no se puede ejecutar). |

### Las etapas del Dockerfile

| Etapa | Qué hace | ¿Termina en una imagen que corre? |
|---|---|---|
| `base` | PHP 8.4.26 sobre Alpine + las extensiones que faltan (`pdo_mysql` y `bcmath`, que pide `laravel-lang`) + `php.ini` de producción. | Es la base de `app` |
| `composer` | `composer install --no-dev` con `composer.lock` y el autoloader optimizado. Revisa que PHP cumpla todos los requisitos del lock. | No, solo pasa `vendor/` |
| `css` | Baja el binario oficial de Tailwind v4.3.3 para Linux, **verifica su SHA-256** contra el fijado en el Dockerfile y compila el CSS con `--minify`. Sin npm. | No, solo pasa `app.css` |
| `runtime` | Código + `vendor/` + CSS. Sin `.env`, sin `tests/`, sin herramientas de compilación. Deja hechas las cachés de paquetes, rutas y vistas. Corre como `www-data`. | Sí: servicio `app` |
| `test` | Con dependencias de desarrollo; corre la suite completa. | Solo para probar |
| `web` | nginx sin privilegios con la carpeta `public/` y el CSS compilado. | Sí: servicio `web` |

Las extensiones se eligieron mirando `composer.lock` (`ext-*` que piden los paquetes) y el código. Todo lo demás que piden ya viene en la imagen oficial; `intl` no hace falta (las fechas en español las traduce Carbon).

### Por qué la caché de configuración se arma al arrancar y no al construir

`php artisan config:cache` guarda en un archivo los valores **del momento** en que corre. Si corriera al construir la imagen, guardaría una configuración sin `APP_KEY` y sin las credenciales (o peor: con ellas dentro de la imagen). Por eso:

- **Al construir** se hacen las cachés que no dependen del entorno: `route:cache` y `view:cache`.
- **Al arrancar** el contenedor, `entrypoint.sh` corre `config:cache` con las variables de `.env.docker` y la guarda en `/tmp` (en memoria). También rehace las vistas compiladas, porque el volumen `storage` sobrevive a los rebuilds.
- `bootstrap/cache` queda dentro de la imagen y es de solo lectura. No es un volumen: un volumen guardaría la caché de rutas del primer build y, al cambiar el código, las rutas quedarían viejas.

---

## 9. Protecciones y por qué

| Protección | Dónde | Por qué |
|---|---|---|
| **Versiones fijas con digest** (`php:8.4.26-fpm-alpine3.24@sha256:...`) | `Dockerfile`, `compose.yaml` | El tag dice la versión; el digest garantiza que siempre se baja **exactamente** la misma imagen, aunque alguien vuelva a publicar el tag. Construir hoy o en un año da lo mismo. |
| **Hash del binario de Tailwind** | `Dockerfile` (etapa `css`) | Si el archivo descargado fuera alterado, el SHA-256 no coincide y el build se detiene. El hash se comprobó contra GitHub y contra el `sha256sums.txt` oficial. |
| **Sin root** | `www-data` (82) en app, `nginx` (101) en web, `mysql` (999) en db | Si alguien lograra ejecutar código, no tendría permisos de administrador dentro del contenedor. |
| **Disco de solo lectura** (`read_only: true`) | los tres | No se pueden modificar programas ni configuración. Solo se escribe en `/tmp` y `/run` (en memoria) y en los volúmenes. Probado: escribir en el código, en `/etc` o en `public/` da `Read-only file system`. |
| **Sin capabilities** (`cap_drop: [ALL]`, ninguna agregada) | los tres | Las capabilities son permisos especiales de Linux (cambiar dueños de archivos, cambiar de usuario, abrir puertos bajos...). Ningún servicio las necesita: nginx escucha en el 8080 (no en el 80), PHP-FPM en el 9000, y MySQL corre como `mysql` desde el principio. Si MySQL arrancara como root, su script de inicio necesitaría capabilities como `CHOWN` (cambiar el dueño de sus carpetas) y `SETUID`/`SETGID` (bajar al usuario `mysql`); corriéndolo ya como `mysql` no hace falta ninguna. Comprobado: `CapEff` es `0000000000000000` en los tres. |
| **`no-new-privileges`** | los tres | Un proceso no puede ganar permisos nuevos (por ejemplo, con programas setuid). |
| **Puerto solo en 127.0.0.1** | `web` | La app se abre desde este equipo, no desde otros de la red. Comprobado: el 8080 está cerrado en las IP de red del equipo. |
| **Base sin puerto publicado** | `db` | Nadie fuera de Docker puede conectarse a MySQL. |
| **Red `internal` aislada** | `app`, `db` | Sin salida a internet ni entrada desde fuera: si `app` fuera comprometida, no podría descargar ni enviar nada. Solo `web` está además en `edge`. |
| **Usuario de base con permisos mínimos** | `MYSQL_USER` | La app entra con `inventario`, que solo tiene permisos sobre `inventario_avicola`. La contraseña de root es aleatoria y la app no la usa. |
| **X Plugin de MySQL apagado** | `db` (`--mysqlx=OFF`) | Un servicio de red menos (puerto 33060) que la app no usa. |
| **Límites de CPU, memoria y procesos** | los tres | Un error o un abuso no se come el equipo: web 0,5 CPU / 128 MB, app 1 CPU / 512 MB, db 1 CPU / 1 GB. |
| **Healthchecks** | `db`, `web` | `db` está sana cuando responde por TCP; `web`, cuando `/up` responde 200 pasando por nginx, PHP y Laravel. `app` espera a que `db` esté sana. |
| **Secretos fuera de la imagen** | `.env.docker` | `APP_KEY` y las contraseñas llegan como variables al arrancar. Nunca se copian a la imagen ni van a Git. |
| **Contexto de build mínimo** | `.dockerignore` | Lista de lo permitido. El respaldo SQL de `storage/app/respaldos` (datos viejos) nunca entra. Comprobado: el contexto tiene 156 archivos (3,5 MB) y ninguno prohibido. |
| **Sin errores en pantalla** | `produccion.ini`, `APP_DEBUG=false` | Un error no muestra código ni datos internos; se registra en `docker compose logs app`. |
| **Sin versiones a la vista** | `server_tokens off`, `expose_php = Off` | La cabecera `Server` dice solo `nginx` y no hay `X-Powered-By`. |
| **Solo `index.php` se ejecuta** | `default.conf` | Cualquier otro `.php` da 404, y `/index.php` no se puede pedir directo. Los archivos ocultos (`.env`, `.git`) también dan 404. |
| **Cabeceras de seguridad** | `cabeceras-seguridad.conf` | `X-Content-Type-Options`, `X-Frame-Options: DENY`, `Referrer-Policy` y `Permissions-Policy`, también en las páginas de error. |
| **gzip solo para estáticos** | `default.conf` | Los JS, CSS, SVG y JSON viajan comprimidos (`three.core.js`: 1,46 MB → 288 KB). Las páginas de PHP no se comprimen: llevan el token CSRF, y comprimirlo junto con texto del usuario facilita el ataque BREACH cuando hay HTTPS. |
| **Sin migraciones automáticas** | — | Cambiar la base es una decisión explícita (`migrate --force`), no un efecto de prender la app. |
| **Logs rotados** | `compose.yaml` | Máximo 3 archivos de 10 MB por servicio: los logs no llenan el disco. |

### Pendiente (lo define la auditoría de seguridad)

- **Content-Security-Policy (CSP).** La app ya está preparada (sin scripts externos ni inline), pero una CSP mal armada rompe páginas: se agrega y se prueba en la auditoría.
- **HTTPS y HSTS.** Esta pila sirve HTTP local. Con HTTPS: `SESSION_SECURE_COOKIE=true` en `.env.docker`, cabecera `Strict-Transport-Security`, y entonces sí importa la protección contra BREACH.
- **Escaneos** de vulnerabilidades de imágenes, de la app y de secretos (Trivy, ZAP, gitleaks).

---

## 10. Problemas frecuentes

**`additional privileges requested: pass "--allow=fs.read=Dockerfile"` al construir.**
La ruta de la terminal tiene otra mayúscula que la carpeta real (por ejemplo `...\personales\...` cuando en disco es `...\Personales\...`). Windows no distingue mayúsculas, pero el constructor de Docker Compose sí. Entra a la carpeta con la mayúscula correcta (Tab para completar) y repite.

**`ERROR: falta APP_KEY en .env.docker` y `app` se reinicia una y otra vez.**
Falta el paso 4.3. Completa `APP_KEY` y corre `docker compose up -d`.

**La app muestra un error de conexión a la base.**
`DB_PASSWORD` y `MYSQL_PASSWORD` tienen que ser iguales. Ojo: MySQL crea el usuario **solo la primera vez**, con el volumen vacío. Si cambias la contraseña después, la base ya creada sigue con la anterior: vuelve a poner la anterior o borra los datos (sección 6) y empieza de cero.

**El puerto 8080 está ocupado.**
Cambia el primer número en `compose.yaml` (`"127.0.0.1:8081:8080"`) y también `APP_URL` en `.env.docker`.

**"Hay algo en el 3306": ¿la base de Docker quedó abierta?**
No. El 3306 de Windows es el MySQL/MariaDB de **XAMPP**. La base de Docker no publica ningún puerto (`docker compose port db 3306` no devuelve un puerto).

**La contraseña de root de MySQL aparece en los logs.**
Es la aleatoria de la primera inicialización (`GENERATED ROOT PASSWORD` en `docker compose logs db`). La app no la usa. Esos logs se borran junto con el contenedor (`docker compose down`); al volver a levantarlo no se imprime otra vez, porque la base ya está creada.

**Al arrancar `app` aparece `'user' directive is ignored when FPM is not running as root`.**
Es esperado: el contenedor ya corre como `www-data`, sin root.

**Cambié Three.js y algunos navegadores siguen con la versión vieja.**
nginx le dice al navegador que guarde `/js/vendor/` y `/fonts/` por un año. Al actualizar Three.js, pon los archivos en una carpeta con la versión (por ejemplo `public/js/vendor/three-r187/`), cambia la ruta del `import` en `public/js/icosaedro.js` y la que revisa `IcosaedroTest`.

---

## 11. Actualizar versiones

1. Busca la versión nueva en Docker Hub (por ejemplo, `php` con tags `8.4.x-fpm-alpine3.yy`).
2. Obtén su digest:
   ```powershell
   docker buildx imagetools inspect php:8.4.27-fpm-alpine3.24 --format "{{.Manifest.Digest}}"
   ```
3. Cambia **tag y digest juntos** en el `ARG` del `Dockerfile` (o en `compose.yaml` para MySQL).
4. Para Tailwind: cambia `TAILWIND_VERSION` y `TAILWIND_SHA256`. El hash está en la página de la release en GitHub (columna *digest* de `tailwindcss-linux-x64`) y en su `sha256sums.txt`. Usa la misma versión que `tailwindcss.exe` de Windows, para que el CSS sea igual en los dos flujos.
5. Reconstruye y prueba:
   ```powershell
   docker compose build --no-cache
   docker build --target test -t inventario-avicola/test:local .
   docker compose up -d
   ```

Versiones actuales:

| Componente | Versión |
|---|---|
| PHP | 8.4.26 (FPM, Alpine 3.24) |
| Composer | 2.10.3 |
| nginx | 1.30.6 (nginx-unprivileged, Alpine 3.24) |
| MySQL | 8.4.11 (LTS) |
| Debian (solo para compilar el CSS) | trixie-20261005-slim |
| Tailwind CSS | v4.3.3 |
