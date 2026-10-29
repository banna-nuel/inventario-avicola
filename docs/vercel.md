# La demo en Vercel

Para **ver** la app en internet, no para usarla con datos reales. El flujo con XAMPP y la pila Docker local ([docs/docker.md](docker.md)) siguen igual.

## Cómo funciona

- Vercel construye el `Dockerfile` sin `--target`, o sea su última etapa, `vercel`: una sola imagen con Apache y PHP 8.4.27 (imagen oficial `php:8.4.27-apache-trixie`).
- La base es **SQLite dentro del contenedor**. Cada vez que el contenedor arranca, se crea de cero con los datos de demostración (las fechas del seeder son relativas a hoy).
- Vercel apaga el contenedor tras **5 minutos sin visitas** (30 s en las vistas previas). La siguiente visita arranca uno nuevo, tarda unos segundos más y **todo lo registrado se pierde**: la demo vuelve al estado inicial.
- Si llegan muchas visitas a la vez, Vercel puede abrir más de una copia del contenedor, y cada una tiene su propia base. Para mirar la app da igual.
- La sesión va en una cookie cifrada con `APP_KEY`: sigue valiendo aunque Vercel cambie de copia.

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
   Copia la línea completa (empieza con `base64:`) como valor de `APP_KEY`, para *Production* y *Preview*. Usa una clave nueva: no la de tu `.env` ni la de `.env.docker.app`.
6. **Deploy.** La primera construcción tarda unos minutos: baja las imágenes, instala las dependencias de PHP y compila el CSS.
7. **Abre la URL** (`https://<nombre>.vercel.app`) y entra con las [cuentas de demostración](../README.md#cuentas-de-demostración).
8. **Protégela (recomendado):** *Settings → Deployment Protection → Vercel Authentication*. Así solo entra quien tenga sesión en tu cuenta de Vercel. Revisa a qué despliegues aplica: si el dominio de producción queda abierto, cualquiera con el enlace puede entrar como administrador, porque las contraseñas de demostración son conocidas. En ese caso, no compartas el enlace.

No hace falta la CLI de Vercel (se instala con npm): todo se hace desde la página y cada push a la rama conectada vuelve a desplegar.

## Variables

Solo `APP_KEY` es obligatoria. Las demás ya vienen en la imagen (etapa `vercel` del `Dockerfile`) y no hace falta tocarlas:

| Variable | Valor en la imagen | Para qué |
|---|---|---|
| `DB_CONNECTION`, `DB_DATABASE` | `sqlite`, `storage/demo/inventario.sqlite` | La base de demostración dentro del contenedor. |
| `SESSION_DRIVER` | `cookie` | La sesión no depende de la copia del contenedor. |
| `SESSION_SECURE_COOKIE` | `true` | Las cookies solo viajan por HTTPS. |
| `TRUSTED_PROXIES` | `*` | Laravel le cree al proxy de Vercel que la página es https y arma los enlaces con https (`bootstrap/app.php`). Al contenedor solo se llega a través de ese proxy. |
| `APP_DEBUG` | `false` | Un error no muestra código ni datos internos. |
| `LOG_CHANNEL` | `stderr` | Los errores salen en los logs de Vercel. |
| `PORT` | `80` | Donde escucha Apache; es el que usa Vercel si no defines otro. |

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

**Lo que registré desapareció.**
Es lo esperado: el contenedor se apagó por falta de visitas y arrancó uno nuevo con los datos de demostración.
