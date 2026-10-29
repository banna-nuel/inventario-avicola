# Inventario Avícola con Docker

Guía para levantar la app con Docker sin haberlo usado antes. Docker es **una forma más** de correr el proyecto: el flujo con XAMPP y `php artisan serve` sigue igual y las dos cosas pueden convivir.

## Índice

1. [Docker en un minuto](#1-docker-en-un-minuto)
2. [Qué hay en esta pila](#2-qué-hay-en-esta-pila)
3. [Requisitos](#3-requisitos)
4. [Primera vez, paso a paso](#4-primera-vez-paso-a-paso)
5. [Uso diario](#5-uso-diario)
6. [Apagar y borrar los datos](#6-apagar-y-borrar-los-datos)
7. [La contraseña de root de MySQL](#7-la-contraseña-de-root-de-mysql)
8. [Pruebas](#8-pruebas)
9. [Qué hace cada archivo](#9-qué-hace-cada-archivo)
10. [Protecciones y por qué](#10-protecciones-y-por-qué)
11. [Problemas frecuentes](#11-problemas-frecuentes)
12. [Actualizar versiones](#12-actualizar-versiones)
13. [Actualizaciones de seguridad de PHP](#13-actualizaciones-de-seguridad-de-php)

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

> **Importante en Windows:** abre la terminal en la carpeta escribiendo la ruta tal como está en el disco. Usa la tecla **Tab** para completar (`cd C:\Users\...\proyectos\Pe` + Tab), o en el Explorador haz click derecho → "Abrir en Terminal". Si escribes una mayúscula distinta (por ejemplo `personales` en vez de `Personales`), `docker compose build` falla con `additional privileges requested` (ver [Problemas frecuentes](#11-problemas-frecuentes)).

### 4.1 Crear los archivos de secretos

```powershell
powershell -ExecutionPolicy Bypass -File scripts\docker-init.ps1
```

El script crea dos archivos, uno por servicio, a partir de sus plantillas:

| Archivo | Lo recibe | Qué tiene |
|---|---|---|
| `.env.docker.app` | solo `app` | Variables de Laravel, con `APP_KEY` y `DB_PASSWORD` ya generadas |
| `.env.docker.db` | solo `db` | Variables de MySQL, con `MYSQL_PASSWORD` igual a `DB_PASSWORD` |

- Genera la clave y la contraseña con el generador criptográfico de Windows (.NET `RandomNumberGenerator`).
- **No muestra los secretos** en pantalla y **nunca sobrescribe**: si alguno de los dos archivos ya existe, no cambia nada.
- Los dos archivos **no van a Git** (`.gitignore`). Las plantillas (`.env.docker.app.example` y `.env.docker.db.example`) sí, y nunca llevan valores reales.
- `-ExecutionPolicy Bypass` deja correr el script solo esta vez, sin cambiar la configuración de Windows.

MySQL no recibe `APP_KEY` ni nada de Laravel, y Laravel no recibe las variables de MySQL: cada contenedor tiene solo lo que necesita.

### 4.2 Construir y levantar la pila

```powershell
docker compose up -d --build
docker compose ps
```

`--build` construye las imágenes. La primera vez baja las imágenes base y tarda unos minutos. En este equipo, sin caché y con las imágenes base ya descargadas, tardó **83 s**; las siguientes veces reutiliza lo que no cambió: ~10-15 s.

`-d` deja los contenedores corriendo en segundo plano. Espera a que `db` y `web` digan `healthy` en `docker compose ps`; la primera vez tarda ~30 s mientras MySQL se inicializa.

### 4.3 Crear las tablas y los datos de demostración

```powershell
docker compose exec app php artisan migrate --seed --force
```

`docker compose exec app ...` corre un comando dentro del contenedor `app` que ya está funcionando. Las migraciones **no** corren solas al arrancar, a propósito: cambiar la base es una decisión tuya. `--force` es necesario porque la app corre en modo producción.

### 4.4 Entrar

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
- **Si cambias `.env.docker.app` o `.env.docker.db`**, `docker compose up -d` recrea los contenedores afectados con los valores nuevos.
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

⚠️ **Borra también los volúmenes: se pierden todos los datos de la base Docker.** No hay papelera. Úsalo solo si quieres empezar de cero (por ejemplo, para que MySQL vuelva a crear el usuario con otra contraseña). Después hay que repetir los pasos 4.2 y 4.3. Si además quieres contraseñas nuevas, antes borra `.env.docker.app` y `.env.docker.db` y vuelve a correr el script del paso 4.1.

Nunca hace falta `docker system prune` ni `docker volume prune` para este proyecto: esos comandos borran cosas de **todos** los proyectos de Docker del equipo.

La base de XAMPP (`inventario_avicola` en tu MySQL de Windows) es otra base distinta: Docker no la toca.

---

## 7. La contraseña de root de MySQL

La app no usa root: entra con `inventario`, que solo tiene permisos sobre su base. Pero MySQL igual crea dos cuentas de root (`root@localhost`, desde dentro del contenedor, y `root@%`, desde la red interna) con una contraseña aleatoria.

### Verla

MySQL la imprime **una sola vez**, en el log del primer arranque (con el volumen vacío):

```powershell
docker compose logs db | Select-String "GENERATED ROOT PASSWORD"
```

Ese log vive con el contenedor: desaparece con `docker compose down` o cuando Compose recrea `db` (por ejemplo, al cambiar `.env.docker.db` o la imagen). Si la vas a necesitar, guárdala en un gestor de contraseñas apenas levantes la pila por primera vez. Si ya no aparece, usa "Recuperarla".

### Rotarla (sabiendo la actual)

```powershell
docker compose exec db mysql -uroot -p
```

Escribe la contraseña actual (no se ve mientras escribes) y, dentro de MySQL, cambia las dos cuentas:

```sql
ALTER USER 'root'@'localhost' IDENTIFIED BY 'la-nueva';
ALTER USER 'root'@'%' IDENTIFIED BY 'la-nueva';
exit
```

### Recuperarla (si se perdió)

Se arranca una copia temporal de MySQL sin control de permisos y sin red, se pone una contraseña nueva y se vuelve a la normalidad. Los datos no se tocan y el usuario de la app sigue con su contraseña.

```powershell
# 1. Apagar la base y levantar la copia temporal (sin permisos, sin red)
docker compose stop db
docker compose run -d --rm --no-deps --name inventario-avicola-db-rescate db --skip-grant-tables --skip-networking --mysqlx=OFF

# 2. Esperar unos segundos y entrar sin contraseña
docker exec -it inventario-avicola-db-rescate mysql -uroot
```

```sql
FLUSH PRIVILEGES;
ALTER USER 'root'@'localhost' IDENTIFIED BY 'la-nueva';
ALTER USER 'root'@'%' IDENTIFIED BY 'la-nueva';
exit
```

```powershell
# 3. Apagar la copia temporal (se borra sola) y volver a prender la base
docker stop inventario-avicola-db-rescate
docker compose start db
```

`FLUSH PRIVILEGES` vuelve a cargar los permisos, que la copia temporal arrancó sin cargar; sin él, `ALTER USER` no funciona. Mientras dura el procedimiento, la app no tiene base (`db` está apagada). Este procedimiento se probó sobre la pila de demo.

---

## 8. Pruebas

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

## 9. Qué hace cada archivo

| Archivo | Qué hace |
|---|---|
| `Dockerfile` | La receta de las imágenes, por etapas (ver abajo). |
| `compose.yaml` | Los servicios, sus redes, volúmenes, límites y protecciones. Su `name: inventario-avicola` es el prefijo de contenedores, redes y volúmenes. |
| `.dockerignore` | Qué archivos puede ver Docker al construir. Deja **todo** fuera y solo vuelve a incluir lo necesario: `.env`, `vendor/`, `storage/` (con el respaldo SQL), `docs/`, los `.sql`, los `.zip` y la base SQLite nunca entran. |
| `.env.docker.app.example`, `.env.docker.db.example` | Plantillas de variables de cada servicio (sin secretos). |
| `.env.docker.app`, `.env.docker.db` | Tus secretos reales, uno por servicio. Ignorados por Git. |
| `scripts/docker-init.ps1` | Crea los dos archivos de secretos con valores aleatorios (paso 4.1). |
| `docker/php/produccion.ini` | Ajustes de PHP: sin versión en las cabeceras, sin errores en pantalla, OPcache, límites. |
| `docker/php/fpm.conf` | Cuántos procesos de PHP atienden peticiones y sus límites. |
| `docker/php/entrypoint.sh` | Lo que corre cada vez que arranca `app`: revisa `APP_KEY`, arma la caché de configuración y las vistas, y arranca PHP. |
| `docker/nginx/default.conf` | El servidor web: qué sirve nginx, qué pasa a PHP, gzip y caché. |
| `docker/nginx/cabeceras-seguridad.conf` | Las cabeceras de seguridad de todas las respuestas. |
| `docker/vercel/apache.conf`, `docker/vercel/entrypoint.sh` | Solo para la demo en Vercel (etapa `vercel`): Apache con las mismas reglas que nginx, y el arranque que crea la base SQLite de demostración ([docs/vercel.md](vercel.md)). |
| `.gitattributes` | Fuerza finales de línea LF en los archivos que van a Linux (un `.sh` con CRLF de Windows no se puede ejecutar). |

### Las etapas del Dockerfile

| Etapa | Qué hace | ¿Termina en una imagen que corre? |
|---|---|---|
| `base` | PHP 8.4.27 sobre Alpine + las extensiones que faltan (`pdo_mysql` y `bcmath`, que pide `laravel-lang`) + `php.ini` de producción. | Es la base de `app` |
| `composer` | `composer install --no-dev` con `composer.lock` y el autoloader optimizado. Revisa que PHP cumpla todos los requisitos del lock. | No, solo pasa `vendor/` |
| `css` | Baja el binario oficial de Tailwind v4.3.3 para Linux, **verifica su SHA-256** contra el fijado en el Dockerfile y compila el CSS con `--minify`. Sin npm. | No, solo pasa `app.css` |
| `runtime` | Código + `vendor/` + CSS. Sin `.env`, sin `tests/`, sin herramientas de compilación. Deja hechas las cachés de paquetes, rutas y vistas. Corre como `www-data`. | Sí: servicio `app` |
| `test` | Con dependencias de desarrollo; corre la suite completa. | Solo para probar |
| `web` | nginx sin privilegios con la carpeta `public/` y el CSS compilado. | Sí: servicio `web` |
| `vercel` | Imagen única para la demo en Vercel: Apache + PHP 8.4.27 (`php:8.4.27-apache-trixie`) + SQLite con los datos de demostración. Es la **última** etapa: Vercel construye sin `--target`. La pila local no la usa ([docs/vercel.md](vercel.md)). | Solo en Vercel |

Las extensiones se eligieron mirando `composer.lock` (`ext-*` que piden los paquetes) y el código. Todo lo demás que piden ya viene en la imagen oficial; `intl` no hace falta (las fechas en español las traduce Carbon).

### Por qué la caché de configuración se arma al arrancar y no al construir

`php artisan config:cache` guarda en un archivo los valores **del momento** en que corre. Si corriera al construir la imagen, guardaría una configuración sin `APP_KEY` y sin las credenciales (o peor: con ellas dentro de la imagen). Por eso:

- **Al construir** se hacen las cachés que no dependen del entorno: `route:cache` y `view:cache`.
- **Al arrancar** el contenedor, `entrypoint.sh` corre `config:cache` con las variables de `.env.docker.app` y la guarda en `/tmp` (en memoria). También rehace las vistas compiladas, porque el volumen `storage` sobrevive a los rebuilds.
- `bootstrap/cache` queda dentro de la imagen y es de solo lectura. No es un volumen: un volumen guardaría la caché de rutas del primer build y, al cambiar el código, las rutas quedarían viejas.

---

## 10. Protecciones y por qué

| Protección | Dónde | Por qué |
|---|---|---|
| **Versiones fijas con digest** (`php:8.4.27-fpm-alpine3.24@sha256:...`) | `Dockerfile`, `compose.yaml` | El tag dice la versión; el digest garantiza que siempre se baja **exactamente** la misma imagen, aunque alguien vuelva a publicar el tag. Construir hoy o en un año da lo mismo. |
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
| **Secretos fuera de la imagen y por servicio** | `.env.docker.app`, `.env.docker.db` | `APP_KEY` y las contraseñas llegan como variables al arrancar; nunca se copian a la imagen ni van a Git. Cada servicio recibe solo las suyas: `db` no ve `APP_KEY` y `app` no ve las variables de MySQL. Se generan con un generador criptográfico (`scripts/docker-init.ps1`). Docker *secrets* (archivos montados en vez de variables) queda como posible mejora de la auditoría. |
| **Contexto de build mínimo** | `.dockerignore` | Lista de lo permitido. El respaldo SQL de `storage/app/respaldos` (datos viejos) nunca entra. Comprobado: el contexto tiene 156 archivos (3,5 MB) y ninguno prohibido. |
| **Sin subida de archivos y peticiones chicas** | `produccion.ini`, `default.conf` | La app no recibe archivos: `file_uploads = Off` y `session.upload_progress.enabled = 0`. El cuerpo de una petición tiene un tope de 2 MB en nginx (`client_max_body_size`) y en PHP (`post_max_size`); cada cabecera, de 8 KB (`large_client_header_buffers 4 8k`), y las cabeceras deben llegar en 10 s (`client_header_timeout`). Lo que se pasa recibe 413, 400 o 414 de nginx y no llega a PHP. |
| **Sin errores en pantalla** | `produccion.ini`, `APP_DEBUG=false` | Un error no muestra código ni datos internos; se registra en `docker compose logs app`. |
| **Sin versiones a la vista** | `server_tokens off`, `expose_php = Off` | La cabecera `Server` dice solo `nginx` y no hay `X-Powered-By`. |
| **Solo `index.php` se ejecuta** | `default.conf` | Cualquier otro `.php` da 404, y `/index.php` no se puede pedir directo. Los archivos ocultos (`.env`, `.git`) también dan 404. |
| **Cabeceras de seguridad** | `cabeceras-seguridad.conf` | `X-Content-Type-Options`, `X-Frame-Options: DENY`, `Referrer-Policy` y `Permissions-Policy`, también en las páginas de error. |
| **gzip solo para estáticos** | `default.conf` | Los JS, CSS, SVG y JSON viajan comprimidos (`three.core.js`: 1,46 MB → 288 KB). Las páginas de PHP no se comprimen: llevan el token CSRF, y comprimirlo junto con texto del usuario facilita el ataque BREACH cuando hay HTTPS. |
| **Sin migraciones automáticas** | — | Cambiar la base es una decisión explícita (`migrate --force`), no un efecto de prender la app. |
| **Logs rotados** | `compose.yaml` | Máximo 3 archivos de 10 MB por servicio: los logs no llenan el disco. |

### Pendiente (lo define la auditoría de seguridad)

- **Content-Security-Policy (CSP).** La app ya está preparada (sin scripts externos ni inline), pero una CSP mal armada rompe páginas: se agrega y se prueba en la auditoría.
- **HTTPS y HSTS.** Esta pila sirve HTTP local. Con HTTPS: `SESSION_SECURE_COOKIE=true` en `.env.docker.app`, cabecera `Strict-Transport-Security`, y entonces sí importa la protección contra BREACH.
- **Escaneos** de vulnerabilidades de imágenes, de la app y de secretos (Trivy, ZAP, gitleaks).

---

## 11. Problemas frecuentes

**`additional privileges requested: pass "--allow=fs.read=Dockerfile"` al construir.**
La ruta de la terminal tiene otra mayúscula que la carpeta real (por ejemplo `...\personales\...` cuando en disco es `...\Personales\...`). Windows no distingue mayúsculas, pero el constructor de Docker Compose sí. Entra a la carpeta con la mayúscula correcta (Tab para completar) y repite.

**`ERROR: falta APP_KEY en .env.docker.app` y `app` se reinicia una y otra vez.**
Falta el paso 4.1 (o `APP_KEY` quedó vacía). Corre el script y después `docker compose up -d`.

**La app muestra un error de conexión a la base.**
`DB_PASSWORD` (en `.env.docker.app`) y `MYSQL_PASSWORD` (en `.env.docker.db`) tienen que ser iguales; el script los deja así. Ojo: MySQL crea el usuario **solo la primera vez**, con el volumen vacío. Si cambias la contraseña después, la base ya creada sigue con la anterior: vuelve a poner la anterior o borra los datos (sección 6) y empieza de cero.

**El puerto 8080 está ocupado.**
Cambia el primer número en `compose.yaml` (`"127.0.0.1:8081:8080"`) y también `APP_URL` en `.env.docker.app`.

**"Hay algo en el 3306": ¿la base de Docker quedó abierta?**
No. El 3306 de Windows es el MySQL/MariaDB de **XAMPP**. La base de Docker no publica ningún puerto (`docker compose port db 3306` no devuelve un puerto).

**La contraseña de root de MySQL aparece en los logs, o ya no aparece.**
Se imprime una sola vez, en el primer arranque, y el log se va con el contenedor. Cómo verla, rotarla o recuperarla: [sección 7](#7-la-contraseña-de-root-de-mysql).

**Al arrancar `app` aparece `'user' directive is ignored when FPM is not running as root`.**
Es esperado: el contenedor ya corre como `www-data`, sin root.

**¿Por qué Three.js está en `public/js/vendor/three-r186/`?**
nginx le pide al navegador que guarde `/js/vendor/` y `/fonts/` un año sin volver a preguntar. Con la versión en la carpeta, una versión nueva tiene otra ruta y se descarga de nuevo; con el mismo nombre, quien ya abrió la app seguiría con la copia vieja. El README explica cómo actualizarlo ("Actualizar Three.js").

---

## 12. Actualizar versiones

Si la actualización es por un aviso de seguridad de PHP, sigue la [sección 13](#13-actualizaciones-de-seguridad-de-php).

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
| PHP | 8.4.27 (FPM, Alpine 3.24; y Apache, Debian trixie, para la demo en Vercel) |
| Composer | 2.10.3 |
| nginx | 1.30.6 (nginx-unprivileged, Alpine 3.24) |
| MySQL | 8.4.11 (LTS) |
| Debian (solo para compilar el CSS) | trixie-20261005-slim |
| Tailwind CSS | v4.3.3 |

---

## 13. Actualizaciones de seguridad de PHP

Cuando php.net publica una versión que corrige vulnerabilidades, la imagen se actualiza así. Los avisos salen en [php.net](https://www.php.net/) y en los [avisos de seguridad de php-src](https://github.com/php/php-src/security/advisories), y cada uno dice qué versión los corrige en cada rama (8.2, 8.3, 8.4...). Ejemplo real: PHP 8.4.27 (21-10-2026) corrige CVE-2026-109618 y CVE-2026-109619.

1. **Confirma la versión que corrige el aviso en la rama 8.4**, la que usa esta imagen.
2. **Comprueba que la imagen oficial ya existe**, con la misma versión de Alpine que el `Dockerfile` (o la vigente), y anota su digest:
   ```powershell
   docker manifest inspect php:8.4.27-fpm-alpine3.24
   docker buildx imagetools inspect php:8.4.27-fpm-alpine3.24 --format "{{.Manifest.Digest}}"
   ```
   Si responde `no such manifest`, todavía no está publicada; puede tardar unas horas después del anuncio (la 8.4.27 apareció en Docker Hub unas 11 horas después del aviso). **Espera**: no compiles PHP por tu cuenta, no uses imágenes que no sean la oficial `php` y no apliques mitigaciones a medias.
3. **Cambia tag y digest juntos** en `ARG PHP_IMAGE` del `Dockerfile` y también en `ARG PHP_APACHE_IMAGE` (la misma versión con `-apache-trixie`, la de la [demo en Vercel](vercel.md)), y la versión en las tablas de esta guía (etapas, protecciones y versiones actuales).
4. **Reconstruye sin caché, bajando de nuevo las imágenes base**, y confirma la versión:
   ```powershell
   docker compose build --pull --no-cache
   docker compose up -d
   docker compose exec app php -v
   ```
5. **Prueba**: la suite completa (`docker build --pull --target test -t inventario-avicola/test:local .`), `MovimientoTest` contra MySQL ([sección 8](#8-pruebas)) y un recorrido corto en el navegador: entrar con los dos roles, registrar un movimiento y ver el huevo en Inicio.
6. **Revisa** que `docker compose ps` muestre `db` y `web` en `healthy` y que `docker compose logs app web` no tenga errores ni reinicios.

**El PHP de XAMPP es otra instalación**: esta imagen no lo actualiza. Revisa si el aviso también cubre la rama 8.2 de XAMPP (en este ejemplo sí: se corrige en 8.2.35) y actualiza XAMPP. La rama 8.2 deja de recibir parches de seguridad el 31-12-2026.
