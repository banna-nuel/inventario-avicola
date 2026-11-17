# La demo en Vercel

Para **ver** la app en internet, no para usarla con datos reales. El flujo con XAMPP y la pila Docker local ([docs/docker.md](docker.md)) siguen igual.

## Cómo funciona

- Vercel construye el `Dockerfile` sin `--target`, o sea su última etapa, `vercel`: una sola imagen con Apache y PHP 8.4.27 (imagen oficial `php:8.4.27-apache-trixie`).
- La base es **SQLite dentro del contenedor**. Cada vez que el contenedor arranca, se crea de cero con los datos de demostración (las fechas del seeder son relativas a hoy).
- Vercel apaga el contenedor tras **5 minutos sin visitas** (30 s en las vistas previas). La siguiente visita arranca uno nuevo, tarda unos segundos más y **todo lo registrado se pierde**: la demo vuelve al estado inicial.
- Si llegan muchas visitas a la vez, Vercel puede abrir más de una copia del contenedor, y cada una tiene su propia base. Para mirar la app da igual.
- La sesión se guarda en la base del contenedor, en JSON; la cookie solo lleva su identificador (ver [Sesiones](#sesiones)).

Para datos reales haría falta otra cosa: una base MySQL externa, usuarios propios sin las cuentas de demostración y terminar la auditoría de seguridad.

## Paso a paso

1. **Sube el código a GitHub.** Vercel construye desde el repositorio: sube la rama (`git push origin feature/vercel`) o intégrala en `main` y sube `main`.
2. **Crea la cuenta** en [vercel.com](https://vercel.com) entrando con tu cuenta de GitHub. El plan Hobby es gratis para uso personal.
3. **Importa el proyecto:** *Add New → Project* y elige `inventario-avicola`. Si no aparece (el repositorio es privado), usa *Adjust GitHub App Permissions* y dale acceso a ese repositorio.
4. **Framework Preset:** Vercel ve el `Dockerfile` y elige **Container**. No cambies *Build Command* ni *Output Directory*.
5. **Variable `APP_KEY`** (en *Environment Variables*, antes de *Deploy*). Genera una clave nueva en tu PC, dentro de la carpeta del proyecto:
   ```powershell
   php artisan key:generate --show
   ```
   Copia la línea completa (empieza con `base64:`) como valor de `APP_KEY`, para *Production* y *Preview*. Usa una clave nueva: no la de tu `.env` ni la de `.env.docker.app`. No la pegues en ningún chat ni la compartas: va solo en Vercel (ver [Rotar la APP_KEY](#rotar-la-app_key)).
6. **Deploy.** La primera construcción tarda unos minutos: baja las imágenes, instala las dependencias de PHP y compila el CSS.
7. **Abre la URL** (`https://<nombre>.vercel.app`) y entra con las [cuentas de demostración](../README.md#cuentas-de-demostración).
8. **Protégela (recomendado):** *Settings → Deployment Protection → Vercel Authentication*. Así solo entra quien tenga sesión en tu cuenta de Vercel. Revisa a qué despliegues aplica: si el dominio de producción queda abierto, cualquiera con el enlace puede entrar como administrador, porque las contraseñas de demostración son conocidas. En ese caso, no compartas el enlace.

No hace falta la CLI de Vercel (se instala con npm): todo se hace desde la página y cada push a la rama conectada vuelve a desplegar.

## Variables

Solo `APP_KEY` es obligatoria. Las demás ya vienen en la imagen (etapa `vercel` del `Dockerfile`) y no hace falta tocarlas:

| Variable | Valor en la imagen | Para qué |
|---|---|---|
| `DB_CONNECTION`, `DB_DATABASE` | `sqlite`, `storage/demo/inventario.sqlite` | La base de demostración dentro del contenedor. |
| `SESSION_DRIVER` | `database` | La sesión se guarda en la base del contenedor; la cookie solo lleva su identificador. |
| `SESSION_SECURE_COOKIE` | `true` | Las cookies solo viajan por HTTPS. |
| `TRUSTED_PROXIES` | `*` | Laravel le cree al proxy de Vercel que la página es https y arma los enlaces con https (`bootstrap/app.php`). Al contenedor solo se llega a través de ese proxy. |
| `APP_DEBUG` | `false` | Un error no muestra código ni datos internos. |
| `LOG_CHANNEL` | `stderr` | Los errores salen en los logs de Vercel. |
| `PORT` | `80` | Donde escucha Apache; es el que usa Vercel si no defines otro. |

## Sesiones

- La sesión se guarda en la base del contenedor (`SESSION_DRIVER=database`) y en formato JSON (`config/session.php`, igual que en XAMPP y en la pila Docker local). La cookie del navegador solo lleva un identificador al azar, cifrado con `APP_KEY`: los datos de la sesión nunca viajan en ella.
- JSON es por seguridad: al leer la sesión no se crean objetos de PHP, así que una sesión fabricada no puede ejecutar código en el servidor, ni siquiera con la `APP_KEY` filtrada. Con la clave sí se podrían falsificar cookies cifradas, por eso igual se cuida (ver [Rotar la APP_KEY](#rotar-la-app_key)).
- La sesión dura lo que dura el contenedor: si Vercel lo apaga por falta de visitas o abre otra copia, hay que volver a entrar. Además, se cierra tras 30 minutos sin actividad.

## Rotar la APP_KEY

Cámbiala si alguna vez se vio fuera de Vercel (un chat, un correo, una captura, un archivo subido a GitHub) o si alguien más tuvo acceso al proyecto en Vercel.

1. **Genera una nueva en tu PC**, dentro de la carpeta del proyecto:
   ```powershell
   php artisan key:generate --show
   ```
   `--show` solo la muestra en tu pantalla: no cambia tu `.env`. Cópiala completa, con `base64:` al principio.
2. **No la pegues en ningún chat, correo, issue ni captura**, y no la guardes en un archivo del repositorio. Nadie te la tiene que pedir: va solo en Vercel.
3. **Cámbiala en Vercel:** *Settings → Environment Variables*, en `APP_KEY` usa *Edit*, pega la clave nueva y guarda, para *Production* y *Preview*.
4. **Vuelve a desplegar:** *Deployments*, los tres puntos `⋯` del último despliegue, *Redeploy*. Hasta que termine, la demo sigue con la clave vieja.
5. Al cambiarla se cierran las sesiones abiertas: hay que volver a entrar. Los datos de la demo no dependen de la clave.

Las claves de tu `.env` (XAMPP) y de `.env.docker.app` son otras y no cambian con esto.

## Probarla en tu PC antes de subirla

Con Docker Desktop abierto, en PowerShell y en la carpeta del proyecto (con la mayúscula correcta en la ruta, ver [docs/docker.md](docker.md#11-problemas-frecuentes)):

```powershell
docker build -t inventario-avicola/vercel:local .
$env:APP_KEY = php artisan key:generate --show
docker run --rm -p 127.0.0.1:8081:80 -e APP_KEY -e SESSION_SECURE_COOKIE=false inventario-avicola/vercel:local
```

Abre **http://127.0.0.1:8081**. `-e APP_KEY` le pasa la clave sin escribirla en el comando. `SESSION_SECURE_COOKIE=false` es solo para esta prueba: en tu PC no hay HTTPS y, sin eso, el navegador no guarda la cookie de sesión. El puerto 8081 no choca con la pila Docker (8080). `Ctrl+C` la apaga y `--rm` borra el contenedor.

## Actualizar

- **Código:** cada push a la rama conectada despliega de nuevo.
- **PHP:** igual que en [docs/docker.md](docker.md#13-actualizaciones-de-seguridad-de-php), pero cambiando también `ARG PHP_APACHE_IMAGE` (la misma versión, con `-apache-trixie`). Las dos imágenes tienen que decir siempre la misma versión de PHP.

## Problemas frecuentes

**La página da error y en los logs de Vercel (*Deployments → Logs*) aparece `ERROR: falta APP_KEY`.**
Falta el paso 5. Agrega la variable y vuelve a desplegar (*Redeploy*).

**La primera visita tarda unos segundos.**
Es el arranque: el contenedor crea la base y carga los datos de demostración (unos 5 s).

**La página sale sin estilos o el navegador avisa de contenido inseguro.**
Laravel cree que la página es http. `TRUSTED_PROXIES` tiene que seguir en `*`: no la cambies en Vercel.

**Lo que registré desapareció, o me pide entrar otra vez.**
Es lo esperado: el contenedor se apagó por falta de visitas y arrancó uno nuevo, con los datos de demostración y sin las sesiones anteriores.
