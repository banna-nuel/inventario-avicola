# Inventario Avícola

Sistema web para controlar el inventario de una empresa avícola: productos con su propia unidad de medida, entradas y salidas de stock con alerta de stock bajo, historial filtrable, gestión de usuarios con dos roles y reportes en pantalla.

Hecho con **Laravel 12**, **Blade**, **Tailwind CSS v4 (CLI standalone, sin Node ni npm)** y **JavaScript vanilla**, sobre **MySQL**. Interfaz 100 % en español, responsive de 320 a 1920 px, con contraste WCAG AA.

---

## Inicio rápido (Docker)

Con Docker Desktop abierto, en PowerShell y dentro de la carpeta del proyecto (escribe la ruta tal como está en el disco: usa Tab):

```powershell
powershell -ExecutionPolicy Bypass -File scripts\docker-init.ps1   # una sola vez: crea los secretos
docker compose up -d --build                                        # espera "healthy" en: docker compose ps
docker compose exec app php artisan migrate --seed --force          # tablas y datos de demostración
```

Abre **http://127.0.0.1:8080** y entra con una de las [cuentas de demostración](#cuentas-de-demostración). Paso a paso y uso diario: [Docker (opcional)](#docker-opcional) y [docs/docker.md](docs/docker.md). Sin Docker, con XAMPP: [Levantar el proyecto desde cero](#levantar-el-proyecto-desde-cero-también-en-el-portátil).

---

## Qué incluye la v1

| Módulo | Administrador | Encargado de inventario |
|---|---|---|
| Inicio, Dashboard y Buscar | Sí | Sí (Buscar: solo productos) |
| Productos (listado, stock y alertas) | Ver y gestionar | Solo ver |
| Movimientos (entradas, salidas e historial) | Sí | Sí |
| Categorías | Sí | No (403) |
| Usuarios | Sí | No (403) |
| Reportes (en pantalla, imprimibles) | Sí | No (403) |

- **Inicio:** accesos a los módulos según el rol y un aviso de cuántos productos están en stock bajo.
- **Icosaedro 3D que se transforma en huevo** (RNF-08) en Inicio y en el login: aparece como icosaedro y a los 1,2 s se vuelve huevo; el click alterna entre las dos formas. Gira solo, sigue al cursor y reacciona al scroll, al click y al arrastre táctil. Es decorativo y respeta "reducir movimiento" (queda un huevo estático). Ver [Icosaedro → huevo](#icosaedro--huevo-rnf-08).
- **Dashboard:** productos activos, activos en stock bajo, movimientos de hoy (día calendario en hora de Bogotá) y la lista de alertas ordenada por mayor faltante, con acceso directo para registrar una entrada.
- **Buscar:** mínimo 2 caracteres, resultados agrupados (10 por grupo) con "Ver todos".
- **Historial:** filtros por producto, tipo y rango de fechas (inclusivo), paginado.
- **Reportes:** stock por categoría, stock bajo y los 10 productos más movidos en un rango; con botón "Imprimir".

---

## Requisitos

- **PHP 8.2 o superior** con las extensiones `pdo_mysql`, `mbstring`, `openssl`, `fileinfo` y `pdo_sqlite` (esta última solo para las pruebas). XAMPP para Windows las trae.
- **Composer 2**.
- **MySQL o MariaDB** (por ejemplo, el de XAMPP, usuario `root` sin contraseña).
- **`tailwindcss.exe` v4.3.x** (el ejecutable standalone de Tailwind para Windows; no se usa Node ni npm). No está en Git: se descarga una vez por equipo (paso 2 abajo).
- **Opcional:** Docker Desktop, para correrla en contenedores sin instalar nada de lo anterior ([Docker](#docker-opcional)).

---

## Levantar el proyecto desde cero (también en el portátil)

En PowerShell, dentro de la carpeta del proyecto:

```powershell
# 1. Dependencias de PHP
composer install

# 2. Tailwind (una sola vez por equipo): se guarda como tailwindcss.exe en la raíz del proyecto
Invoke-WebRequest -Uri https://github.com/tailwindlabs/tailwindcss/releases/download/v4.3.3/tailwindcss-windows-x64.exe -OutFile tailwindcss.exe

# 3. Configuración local
Copy-Item .env.example .env
php artisan key:generate

# 4. Crear la base de datos (con MySQL de XAMPP encendido; también se puede hacer en phpMyAdmin)
C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE IF NOT EXISTS inventario_avicola CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"

# 5. Tablas y datos de demostración
php artisan migrate --seed

# 6. Compilar los estilos (minificados)
composer css

# 7. Servidor de desarrollo
php artisan serve
```

Abre **http://127.0.0.1:8000** e inicia sesión con una de las cuentas de demostración.

**Si ya tenías el proyecto en otra PC** y solo trajiste cambios nuevos: `composer install`, `php artisan migrate` (aplica solo las migraciones nuevas) y `composer css`.

**Para volver a los datos de demostración** (borra todo lo de la base de desarrollo): `php artisan migrate:fresh --seed`.

### Variables importantes del `.env`

| Variable | Valor | Para qué |
|---|---|---|
| `APP_DEBUG` | `false` | Con `true`, un error muestra código y datos internos. Actívalo solo para depurar en tu PC. |
| `APP_TIMEZONE` | `America/Bogota` | "Hoy", el historial y los reportes usan la hora de Bogotá. |
| `APP_LOCALE` | `es` | Mensajes y validaciones en español. |
| `SESSION_LIFETIME` | `30` | La sesión se cierra tras 30 minutos sin actividad (RNF-04). |
| `DB_DATABASE` | `inventario_avicola` | Base de desarrollo. |

---

## Docker (opcional)

Una segunda forma de correr la app, **además** de XAMPP: nginx + PHP 8.4 + MySQL 8.4 en contenedores endurecidos (sin root, disco de solo lectura, sin capabilities, base sin puerto publicado). La guía completa, pensada para quien nunca usó Docker, está en **[docs/docker.md](docs/docker.md)**.

**Requisitos:** Docker Desktop (con WSL 2), ~2 GB de disco y el puerto 8080 libre. No hace falta PHP, Composer ni Tailwind en Windows.

**Primera vez** (PowerShell, en la carpeta del proyecto escrita tal como está en el disco: usa Tab para completar la ruta):

```powershell
# Crea .env.docker.app y .env.docker.db con APP_KEY y contraseñas aleatorias (no las muestra)
powershell -ExecutionPolicy Bypass -File scripts\docker-init.ps1

docker compose up -d --build
docker compose exec app php artisan migrate --seed --force
```

Abre **http://127.0.0.1:8080** y entra con las [cuentas de demostración](#cuentas-de-demostración) (`admin@test.com` / `encargado@test.com`, contraseña `12345678`).

| Para | Comando |
|---|---|
| Prender | `docker compose up -d` |
| Ver el estado (`db` y `web` deben decir `healthy`) | `docker compose ps` |
| Ver los logs | `docker compose logs -f` |
| Aplicar cambios del código | `docker compose up -d --build` |
| Apagar (los datos se conservan) | `docker compose down` |
| ⚠️ Apagar **y borrar los datos** de la base Docker | `docker compose down -v` |
| Pruebas dentro de Docker | `docker build --target test -t inventario-avicola/test:local .` |

**Puertos:** solo **127.0.0.1:8080** (la app). MySQL no se publica, así que no choca con el 3306 de XAMPP; tampoco con el 8000 de `php artisan serve`. La base de Docker y la de XAMPP son distintas.

**Secretos:** uno por servicio, `.env.docker.app` (Laravel) y `.env.docker.db` (MySQL), fuera de Git. `db` no recibe `APP_KEY` y `APP_KEY` nunca se guarda en la imagen. La contraseña de root de MySQL se imprime una sola vez en el log de `db` (cómo verla o rotarla: [docs/docker.md](docs/docker.md#7-la-contraseña-de-root-de-mysql)). Con HTTPS, `SESSION_SECURE_COOKIE` va en `true`.

---

## Scripts de Composer

| Comando | Qué hace |
|---|---|
| `composer css` | Compila `resources/css/input.css` → `public/css/app.css` minificado (para entregar). |
| `composer css:watch` | Recompila los estilos cada vez que cambia una vista o el CSS (déjalo abierto en otra terminal mientras desarrollas). |
| `composer dev` | Arranca el servidor (`php artisan serve`). |
| `composer test` | Limpia la caché de configuración y corre todas las pruebas. |
| `composer setup` | Instalación rápida: `composer install`, copia `.env`, genera la clave, migra (sin datos de demostración) y compila los estilos. |

---

## Cuentas de demostración

Solo para desarrollo (las crea `php artisan migrate --seed`):

| Rol | Correo | Contraseña |
|---|---|---|
| Administrador | `admin@test.com` | `12345678` |
| Encargado de inventario | `encargado@test.com` | `12345678` |

El seeder crea además 5 categorías, 10 productos con las 7 unidades de medida (4 en stock bajo y 1 inactivo) y 33 movimientos de los últimos 14 días. El stock de cada producto es la suma de su historial, y el seeder lo verifica al terminar.

---

## Pruebas

```powershell
php artisan test
```

Corren con **SQLite en memoria** (configurado en `phpunit.xml`), así que nunca tocan la base de desarrollo. Cubren las reglas de negocio (RN-01 a RN-09), los permisos de cada ruta para cada rol (`tests/Feature/RutasTest.php`), las validaciones, los filtros, los reportes, la seguridad y que no haya consultas N+1.

### Pruebas contra MySQL (opcional)

SQLite ignora el bloqueo de filas (`lockForUpdate`) que protege el stock cuando dos personas registran a la vez. Para verificarlo en MySQL hay una base separada de pruebas:

```powershell
C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE IF NOT EXISTS inventario_avicola_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
$env:DB_CONNECTION = 'mysql'; $env:DB_DATABASE = 'inventario_avicola_test'
php vendor/bin/phpunit
Remove-Item Env:DB_CONNECTION, Env:DB_DATABASE
```

Con MySQL, la prueba `test_rn04_en_mysql_la_fila_del_producto_se_bloquea_con_for_update` confirma que se ejecuta `select * from productos where id = ? limit 1 for update` (con SQLite se omite). Como red de seguridad, las pruebas **se niegan a correr** contra cualquier base cuyo nombre no termine en `_test` (ver `tests/TestCase.php`), porque cada prueba borra la base.

---

## Actualizar Three.js (archivo de vendor)

Three.js no se instala con npm ni se carga desde un CDN: sus archivos están en `public/js/vendor/three-r186/` y se sirven desde la propia app. La carpeta lleva la versión a propósito: el servidor web de Docker le pide al navegador que guarde `/js/vendor/` un año sin volver a preguntar, así que una versión nueva tiene que tener **otra ruta** (si no, quien ya abrió la app seguiría con la copia vieja, o con una mezcla de las dos). Para pasar a otra versión:

1. En https://github.com/mrdoob/three.js/releases elige una versión estable publicada **hace más de 2 semanas** (por ejemplo `r187`).
2. Descarga los dos archivos de su carpeta `build/` en una carpeta nueva con la versión (`three.module.js` importa a `three.core.js`, así que van juntos):

   ```powershell
   $v = 'r187'
   New-Item -ItemType Directory public/js/vendor/three-$v | Out-Null
   Invoke-WebRequest "https://raw.githubusercontent.com/mrdoob/three.js/$v/build/three.module.js" -OutFile public/js/vendor/three-$v/three.module.js
   Invoke-WebRequest "https://raw.githubusercontent.com/mrdoob/three.js/$v/build/three.core.js" -OutFile public/js/vendor/three-$v/three.core.js
   Get-FileHash public/js/vendor/three-$v/*.js -Algorithm SHA256
   ```

3. Cambia la ruta en `public/js/icosaedro.js` (`import('./vendor/three-r187/three.module.js')`) y en `tests/Feature/IcosaedroTest.php` (la constante `VENDOR` y la ruta del `import`).
4. Actualiza `public/js/vendor/THIRD_PARTY.md`: versión, URLs, ubicación, fecha, tamaño y SHA-256 de cada archivo (y la licencia, si cambió).
5. Borra la carpeta de la versión anterior (`public/js/vendor/three-r186/`).
6. Corre `php artisan test`: `IcosaedroTest` falla si los SHA-256 de `THIRD_PARTY.md` no coinciden con los archivos o si el `import` no apunta a la carpeta nueva.
7. Abre Inicio y el login y revisa que el icosaedro gire, que se transforme en huevo y que la consola del navegador no tenga errores. Con Docker: `docker compose up -d --build`.

---

## Icosaedro → huevo (RNF-08)

El componente `x-icosaedro` aparece como icosaedro (30 aristas) y, a los ~1,2 s, se transforma en un huevo en ~1,4 s con la curva `cubic-bezier(.22, 1, .36, 1)` (`--ease-asentar`). Se queda como huevo; cada click alterna entre las dos formas (~1 s) y además le da el impulso de siempre.

**Fórmula de deformación** (la misma en `public/js/icosaedro.js` y en `app/View/Components/Icosaedro.php`; `IcosaedroTest` comprueba que coincidan). Cada punto (x, y, z) de la esfera de radio 1 va a:

```text
x' = 0,82 · x · (1 − 0,2 · y)
y' = 0,82 · 1,32 · y
z' = 0,82 · z · (1 − 0,2 · y)
```

- `0,2` (asimetría, k): ensancha la mitad de abajo y afina la de arriba. El extremo ancho queda abajo.
- `1,32` (alargamiento): da la proporción alto:ancho ≈ 1,3:1 en pantalla (medida: 1,31).
- `0,82` (escala): el huevo ocupa en pantalla lo mismo que el icosaedro.
- El punto de partida fue `x·(1 − 0,15·y)`, `y' = 1,25·y`. Se afinó a ojo: con esos valores el huevo quedaba 1,24:1, y con k = 0,2 el extremo angosto se nota más.

**Cómo se transforma:**
- Cada arista se parte en tramos (16 en el 3D, 8 en el SVG).
- Cada punto tiene dos lugares: sobre la arista recta (icosaedro) y el mismo punto llevado a la esfera y deformado (huevo). La transformación interpola entre los dos, así que hay correspondencia uno a uno y el icosaedro inicial no tiene líneas extra.
- Con solo los 30 arcos la silueta se veía abollada. Por eso, al final de la transformación (del 80 % al 100 %) aparece con un fundido el **contorno del huevo visto desde la cámara**: los puntos donde la visual roza la superficie, calculados con la misma cuenta en PHP y en JS.
- El huevo gira solo alrededor de su eje vertical, con una inclinación fija de 0,2 rad (se ve un poco desde arriba) para no volcarse. La inclinación hacia el cursor queda limitada a ±15°.

**Estados estáticos (SVG calculado en PHP):**
- El icosaedro se ve mientras carga el 3D, así que no hay salto.
- El huevo es el estado final con "reducir movimiento" o sin WebGL. En esos casos no se descarga Three.js.
- Los dos SVG están uno encima del otro dentro del tamaño reservado, así que nada se mueve.

---

## Estructura del proyecto

```
app/
  Enums/UnidadMedida.php           Lista cerrada de unidades (libras, kilogramos, unidades, cubetas, bultos, litros, dosis)
  Http/Controllers/                Un controlador por módulo (Producto, Movimiento, Usuario, Reporte, ...)
  Http/Middleware/                 VerificarRol (role:admin) y UsuarioActivo (expulsa cuentas desactivadas)
  Http/Requests/                   Validaciones con mensajes en español (Form Requests)
  Models/                          Categoria, Producto, MovimientoInventario, User
  Providers/AppServiceProvider.php Búsqueda whereContiene() con LIKE escapado
config/navegacion.php              Barra flotante y tarjetas de Inicio (ruta, etiqueta, ícono y roles)
database/migrations/               Esquema (los cambios van siempre en migraciones nuevas)
database/seeders/DatabaseSeeder.php Datos de demostración de la granja
resources/css/input.css            Tokens del diseño (@theme), transiciones y estilos de impresión
resources/views/components/        Componentes Blade: boton, campo, select, hoja, tabla, badge, alerta...
resources/views/{modulo}/          Vistas de cada módulo; create/edit son "hojas" sobre el listado
public/js/app.js                   JavaScript vanilla (hoja accesible, filtros, resumen de stock, imprimir)
public/js/icosaedro.js             Icosaedro 3D que se transforma en huevo (módulo ES; carga Three.js solo cuando hace falta)
public/js/vendor/three-r186/       Three.js autoalojado, en una carpeta con su versión
public/js/vendor/THIRD_PARTY.md    Versión, origen, SHA-256 y licencia de Three.js
app/View/Components/Icosaedro.php  Componente x-icosaedro (calcula los SVG estáticos: icosaedro y huevo)
tests/Feature/                     Pruebas de cada fase
docs/                              Requerimientos, casos de uso, modelo de datos y arquitectura
docs/docker.md                     Guía de Docker para principiantes
Dockerfile                         Imágenes de Docker por etapas (composer, css, runtime, test, web)
compose.yaml                       Pila Docker: web (nginx), app (PHP-FPM) y db (MySQL 8.4)
docker/                            Configuración de PHP, php-fpm y nginx para las imágenes
.env.docker.*.example              Plantillas de variables de Docker, una por servicio (app y db)
scripts/docker-init.ps1            Crea .env.docker.app y .env.docker.db con secretos aleatorios
```

---

## Mapa de rutas

Todas las rutas tienen nombre y el código usa `route('nombre')`. Las protegidas exigen sesión y cuenta activa (`auth` + `activo`).

| Método | URL | Nombre | Quién |
|---|---|---|---|
| GET | `/` | `raiz` | Redirige a Inicio |
| GET, POST | `/login` | `login`, `login.entrar` | Invitados |
| POST | `/logout` | `logout` | Con sesión |
| GET | `/home` | `home` | Ambos roles |
| GET | `/dashboard` | `dashboard` | Ambos roles |
| GET | `/search` | `search` | Ambos roles |
| GET | `/productos` | `productos.index` | Ambos roles |
| GET, POST | `/productos/create`, `/productos` | `productos.create`, `productos.store` | Admin |
| GET, PUT | `/productos/{producto}/edit`, `/productos/{producto}` | `productos.edit`, `productos.update` | Admin |
| PATCH | `/productos/{producto}/estado` | `productos.cambiar-estado` | Admin |
| GET | `/movimientos` | `movimientos.index` | Ambos roles |
| GET, POST | `/movimientos/create`, `/movimientos` | `movimientos.create`, `movimientos.store` | Ambos roles |
| GET, POST, PUT, DELETE | `/categorias...` | `categorias.index/create/store/edit/update/destroy` | Admin |
| GET, POST | `/usuarios`, `/usuarios/create` | `usuarios.index`, `usuarios.create`, `usuarios.store` | Admin |
| GET, PUT | `/usuarios/{usuario}/edit`, `/usuarios/{usuario}` | `usuarios.edit`, `usuarios.update` | Admin |
| PATCH | `/usuarios/{usuario}/estado` | `usuarios.cambiar-estado` | Admin |
| GET | `/reportes` | `reportes.index` | Admin |
| GET | `/reportes/stock-por-categoria` | `reportes.stock-por-categoria` | Admin |
| GET | `/reportes/stock-bajo` | `reportes.stock-bajo` | Admin |
| GET | `/reportes/mas-movidos` | `reportes.mas-movidos` | Admin |

No existen a propósito (responden 404 o 405): ver un recurso (`show`), eliminar productos o usuarios (se desactivan) y editar o eliminar movimientos (son inmutables).

---

## Reglas de negocio

| Regla | Dónde vive |
|---|---|
| **RN-01** No se registra una salida mayor al stock. | `MovimientoInventario::registrar()` |
| **RN-02** Stock bajo = `stock_actual < stock_minimo` (mínimo propio de cada producto). | `Producto::scopeStockBajo()`, `enStockBajo()`, `scopeEnAlerta()` |
| **RN-03** Productos y usuarios no se eliminan: se desactivan. Una categoría con productos no se elimina. | Controladores y vistas (botón deshabilitado con el motivo) |
| **RN-04** Cada movimiento va en una transacción que bloquea la fila del producto y actualiza el stock. El stock inicial de un producto nuevo también entra como movimiento. | `MovimientoInventario::registrar()` (`DB::transaction` + `lockForUpdate`) |
| **RN-05/06** Permisos por rol. | Middleware `role:admin` en `routes/web.php`, probado en `RutasTest` |
| **RN-07** Nadie se desactiva ni se quita el rol de administrador a sí mismo: siempre queda un admin activo. | `UsuarioRequest` y `UsuarioController::cambiarEstado()` |
| **RN-08** Cada producto tiene su unidad; si ya tiene movimientos, la unidad queda fija. Las cantidades de unidades distintas nunca se suman. | `UnidadMedida`, `ProductoRequest`, reporte de stock por categoría |
| **RN-09** Los movimientos no se editan ni se eliminan; un error se corrige con un movimiento inverso. | Sin rutas de edición + el modelo lanza una excepción |

---

## Decisiones tomadas y por qué

- **Sin Node, npm ni Vite:** Tailwind v4 standalone (`tailwindcss.exe`) y JavaScript sin dependencias. Menos piezas que instalar en cada equipo.
- **Formularios en "hojas":** crear y editar se abren sobre el listado (en móvil suben desde abajo; en escritorio son un modal centrado), como la pantalla "New collection" del Figma. Son accesibles como diálogo modal: foco inicial, Tab atrapado, Esc cierra y el foco vuelve al botón que la abrió.
- **Unidades como lista cerrada (enum):** evita variantes como "lb" y "libras" y permite mostrar singular o plural.
- **El stock solo cambia con movimientos:** `stock_actual` no se puede asignar desde un formulario; así siempre es igual a la suma del historial.
- **Números con coma decimal:** los campos numéricos aceptan "12,5" y "12.5" (un `type="number"` rechaza la coma en navegadores en inglés).
- **`select` nativos:** accesibles y cómodos en el celular.
- **Navegación en una sola definición** (`config/navegacion.php`): la barra y las tarjetas de Inicio no pueden mostrar un enlace que lleve a 403 o 404 (lo comprueba `RutasTest`).
- **Búsqueda con LIKE escapado:** "50%" o "a_b" se buscan como texto, no como comodines.
- **Transiciones entre páginas** con View Transitions de CSS, solo si la persona no pidió "reducir movimiento". En navegadores sin soporte (como Firefox) se navega normal.
- **Reportes en pantalla e imprimibles:** sin exportar a PDF o Excel en la v1.
- **Three.js autoalojado y cargado tarde:** sin CDN ni scripts externos o inline (permite una CSP estricta). Se descarga después de que la página cargó y solo si el icosaedro está a la vista; sin WebGL o con "reducir movimiento" queda un huevo estático en SVG.

---

## Pendientes futuros (fuera de la v1)

- Cambio de contraseña por la propia persona (hoy la cambia un administrador desde Usuarios).
- Recuperación de contraseña por correo.
- Exportar reportes a PDF o Excel.
- Pedidos, entregas y pagos; notificaciones; varios almacenes.
- Para un servidor en producción: HTTPS con cookies seguras (`SESSION_SECURE_COOKIE=true`) y cabeceras de seguridad (por ejemplo `X-Frame-Options`).

---

## Diferencias con la documentación de `docs/`

- La tabla de usuarios es `users` (no `usuarios`), con las columnas `rol` y `activo`.
- No se usa Laravel Breeze: la autenticación es manual (`AuthController`).
- La paleta oscura del documento 01 se descartó: el diseño sigue el Figma (tema claro, monocromático, acento naranja mínimo).
- Los movimientos son inmutables (RN-09) y el stock inicial entra como movimiento de entrada.
