# UML · 00 — Contexto, diferencias y reglas

**Para qué es:** insumo para dibujar los diagramas UML de casos de uso, clases y secuencia de Inventario Avícola. No contiene diagramas: solo datos sacados del sistema real.
**Base:** rama `docs/uml`, creada desde `feature/icosaedro` (commit `b1cf288`; la v1 está marcada con el tag `v1.0.0` = `21ed8bd`). Laravel 12.69.2, PHP 8.2.
**Fecha:** 2026-10-12.

**Revisión 2 (2026-10-12)**
- La traza SQL y el esquema se confirmaron en **MariaDB 10.4.32**, contra `inventario_avicola_test`. Con eso se cierran V-04 y V-05.
- V-01 y V-02 se cerraron con una aclaración del usuario: las subclases Cliente, Administrador y Repartidor vienen del análisis original de la materia.
- Cada archivo tiene ahora una **priorización** al inicio.

**Archivos de este paquete**

| Archivo | Contenido |
|---|---|
| `00-contexto-y-reglas.md` | Resumen y límites, diferencias con `docs/`, actores y permisos, reglas e invariantes, glosario, trazabilidad, lista de [VERIFICAR] |
| `01-casos-de-uso.md` | **§0 priorización** (15 casos de uso principales, 4 de soporte, omitibles y agrupación por paquete). Además: límite del sistema, paquetes, casos de uso UC-01…UC-30, relaciones `<<include>>`/`<<extend>>`, cobertura de rutas |
| `02-clases.md` | **§0 priorización** (qué atributos y métodos mostrar u omitir en cada una de las 3 vistas). Además: esquema real de la base de datos, clases de dominio y enums, capa de aplicación, notas de modelado |
| `03-secuencias.md` | **Priorización** (las 8 secuencias imprescindibles, en orden). Además: escenarios SEQ-00…SEQ-18 con participantes, mensajes, fragmentos, transacciones y orden real de SQL |

**Cómo se citan las fuentes**
- Código: `ruta/archivo.php:línea` o `Clase::método`.
- Rutas: por su nombre, por ejemplo `productos.store` (`routes/web.php`).
- Pruebas: `ClaseTest::test_nombre`, en `tests/Feature/`.
- SQL: traza tomada con `DB::listen` en un test temporal (sin commitear), en MariaDB 10.4.32 contra `inventario_avicola_test`, nunca la de desarrollo. Ver `03-secuencias.md` §0.
- `[VERIFICAR]` marca lo que no se pudo confirmar en el sistema. La lista completa está en la §7.

**IDs**
- Se respetan los de `docs/` (RF-01…RF-11, RU-01…RU-07, RN-01…RN-09, CU-01…CU-08).
- Nuevos, con la misma convención:
  - Requisitos funcionales: RF-12…RF-16.
  - Reglas de negocio: RN-10…RN-23.
  - Invariantes técnicos: INV-01…INV-13.
  - Diferencias con `docs/`: DIF-01…DIF-30.
  - Casos de uso UML: UC-01…UC-30.
  - Escenarios de secuencia: SEQ-00…SEQ-18.
  - Puntos a verificar: V-01…V-10.

---

## 1. Resumen del sistema y límites

Inventario Avícola v1 es una aplicación web para controlar el inventario de una granja avícola. Está hecha con Laravel 12, MySQL, Blade + Tailwind y JavaScript sin dependencias.

- **Acceso:** dos roles, Administrador y Encargado de inventario, inician sesión con correo y contraseña.
- **Catálogo:** cada producto pertenece a una categoría y tiene su propia unidad de medida y su stock mínimo.
- **Stock:** el stock **solo** cambia con movimientos de entrada o salida.
  - Cada movimiento se registra en una transacción que bloquea la fila del producto.
  - Los movimientos no se editan ni se eliminan.
- **Alertas:** el sistema avisa cuando un producto activo queda por debajo de su mínimo (en Dashboard, listado, Inicio y al registrar).
- **Pantallas:** historial filtrable y búsqueda.
- **Solo para el administrador:** gestión de usuarios con desactivación (sin borrado) y tres reportes en pantalla que se pueden imprimir.

**Incluye (dentro del límite del sistema)**
- Inicio y cierre de sesión con autenticación propia (`AuthController`, sin Breeze). Bloqueo tras 3 intentos fallidos. Expulsión de cuentas desactivadas.
- Inicio (accesos por rol), Dashboard, Buscar.
- Categorías (CRUD), productos (crear, editar, activar o desactivar), movimientos (entrada y salida) e historial.
- Usuarios (crear, editar, activar o desactivar).
- Reportes: stock por categoría, stock bajo y más movidos, con "Imprimir".
- Páginas de error propias: 403, 404, 419 y 5xx.

**No incluye (fuera del límite)**
- Recuperación de contraseña por correo (RF-02).
- Exportar reportes a PDF o Excel (RF-11).
- Cambio de contraseña por la propia persona.
- "Recordarme" y verificación de correo.
- Pedidos, clientes, repartidores, pagos y notificaciones (`docs/01` §9).
- Varios almacenes, API y app móvil.
- Editar o eliminar movimientos (RN-09).
- Eliminar productos o usuarios (RN-03).

**Otros límites**
- **Actores externos:** ninguno. No hay servidor de correo, API externa ni otro sistema conectado.
- **Rutas del framework (infraestructura, sin caso de uso):**
  - `GET /up` (estado del servidor).
  - `GET|PUT storage/{path}` (`storage.local`, `storage.local.upload`).
  - Fuente: `php artisan route:list --only-vendor`.
- **RNF-08 (icosaedro 3D en Inicio y login):** es decorativo y no produce caso de uso (`app/View/Components/Icosaedro.php`).

---

## 2. Diferencias entre `docs/` y el sistema real

No se corrigió ningún documento de `docs/`. Esta tabla las registra.

| ID | Tema | Lo que dice el doc | Lo que hace el sistema | Fuente |
|---|---|---|---|---|
| DIF-01 | Actores Cliente y Repartidor | `docs/01` §9: "Gestión de pedidos y clientes" y "Asignación y seguimiento de repartidores" existen "en el análisis original de la materia"; `docs/01` §2 ya define solo 2 actores | No existen actores, roles, tablas ni rutas para clientes o repartidores. `users.rol` solo admite `admin` y `encargado`. Aclaración del usuario: en el análisis original eran subclases de usuario, junto con Administrador (DIF-27) | `database/migrations/2026_09_12_043713_add_rol_and_activo_to_users_table.php:15`; `routes/web.php`; `User::ROLES`. V-01 (cerrado) |
| DIF-02 | Tabla `users` frente a "usuarios" | `docs/04` §3: "User.php (viene por defecto, es tu tabla usuarios)". `docs/03` ya usa `users` | La tabla es `users` (la de Laravel), con columnas en inglés (`name`, `email`, `password`) más `rol` y `activo`. El resto del dominio está en español: llave foránea `movimientos_inventario.usuario_id → users.id`, URL `/usuarios`, `UsuarioController`, parámetro `{usuario}`. La clase es `User` | `database/migrations/0001_01_01_000000_create_users_table.php`; `routes/web.php:66-71`; `MovimientoInventario::usuario()` |
| DIF-03 | Valores de rol | `docs/01` §2: "Administrador", "Encargado de Inventario". `docs/03`: `ENUM('admin','encargado') NOT NULL` sin valor por defecto | `ENUM('admin','encargado') NOT NULL DEFAULT 'encargado'`. Etiquetas en `User::ROLES`: `admin` → "Administrador", `encargado` → "Encargado de inventario" | migración `2026_09_12_043713`:15; `app/Models/User.php:54-57` |
| DIF-04 | Columnas internas de `users` | `docs/03` omite a propósito `email_verified_at` y `remember_token` | Existen, pero la app no las usa (no hay "recordarme" ni verificación de correo). `Auth::logout()` solo renueva `remember_token` si no está vacío: las cuentas creadas por la app o el seeder lo tienen en `NULL` | migración `users`; `UsuarioController::store`; `DatabaseSeeder:27-41`; traza SEQ-09 |
| DIF-05 | Destino tras el login | CU-01 paso 3: "redirige al dashboard correspondiente según el rol" | Redirige a **Inicio** (`/home`) para los dos roles, o a la URL protegida que se pidió antes del login (`redirect()->intended(route('home'))`). El Dashboard es el mismo para ambos roles | `AuthController::login:63`; `AutenticacionTest::test_inicia_sesion_y_va_a_home`; `DashboardTest::test_los_indicadores_son_iguales_para_los_dos_roles` |
| DIF-06 | Bloqueo por intentos | CU-01 alternativo: "3 veces seguidas, el sistema bloquea el acceso temporalmente" | Ver los detalles debajo de la tabla | `AuthController` (`MAX_INTENTOS = 3`, `SEGUNDOS_BLOQUEO = 60`); `AutenticacionTest` (4 pruebas de bloqueo); SEQ-02, SEQ-03 |
| DIF-07 | Cuenta desactivada con sesión abierta | No se menciona | Una cuenta inactiva no inicia sesión. Si tenía la sesión abierta, el middleware `activo` la cierra en su siguiente petición y la manda al login con un aviso | `AuthController::login:53-57`; `app/Http/Middleware/UsuarioActivo.php`; `AutenticacionTest::test_un_usuario_desactivado_con_la_sesion_abierta_es_expulsado` |
| DIF-08 | RN-03 (productos y usuarios) | "Un producto o usuario **con movimientos** asociados no se elimina físicamente; se marca como inactivo" | **Ningún** producto ni usuario se elimina, tenga o no movimientos: no hay ruta `destroy` (404/405). Se desactivan y se pueden **volver a activar** (`PATCH …/estado`) | `routes/web.php:19-22, 58-71`; `RutasTest::test_las_urls_que_no_deben_existir_responden_404_o_405`; `ProductoTest::test_desactivar_y_volver_a_activar_un_producto` |
| DIF-09 | Eliminar categorías | RF-05 "CRUD de categorías", sin reglas de borrado | Una categoría con productos (activos o inactivos) **no** se elimina: hay mensaje de error y botón deshabilitado con el motivo. Sin productos, se elimina físicamente. El README la agrupa en RN-03; aquí es RN-15 | `CategoriaController::destroy:53-68`; `resources/views/categorias/index.blade.php:57-86`; `CategoriaTest::test_rn03_no_se_elimina_una_categoria_con_productos` |
| DIF-10 | Casos de uso de categorías | `docs/02` no tiene CU para RF-05; su matriz de trazabilidad no incluye RF-05 | Hay 6 rutas de categorías: se agregan UC-12 a UC-15 | `routes/web.php:56`; `docs/02` "Matriz de Trazabilidad" |
| DIF-11 | RN-07 | "No se permite desactivar la única cuenta de Administrador activa" | Ver los detalles debajo de la tabla | `UsuarioRequest::rules:40`; `UsuarioController::cambiarEstado:67-71`; `UsuarioTest::test_rn07_*`, `test_aunque_haya_otro_admin_nadie_se_desactiva_ni_se_quita_el_rol_a_si_mismo` |
| DIF-12 | Unidad de medida | RF-04: "libras, unidades, kg, etc."; `docs/03`: `VARCHAR(20)` de texto libre | Lista **cerrada**: enum PHP `UnidadMedida` con 7 valores (`libras`, `kilogramos`, `unidades`, `cubetas`, `bultos`, `litros`, `dosis`). "kg" o "lb" se rechazan. La columna sigue siendo `VARCHAR(20)`. Si el producto ya tiene movimientos, la unidad no se puede cambiar | `app/Enums/UnidadMedida.php`; `ProductoRequest::reglasDeUnidad`; `ProductoTest::test_validaciones_al_crear_en_espanol`, `test_con_movimientos_la_unidad_ya_no_cambia` |
| DIF-13 | Stock inicial | CU-02 paso 2: el admin "ingresa ... stock inicial"; `docs/03`: `stock_actual DEFAULT 0` | El stock inicial **no** se escribe en `productos.stock_actual`. Si es mayor que 0, se registra como **movimiento de entrada** del administrador, en la misma transacción que crea el producto. `stock_actual` no se puede asignar desde ningún formulario | `ProductoController::store:41-51`; `Producto::$fillable` (`app/Models/Producto.php:14-21`); `ProductoTest::test_el_stock_inicial_entra_como_un_movimiento_de_entrada`, `test_si_falla_el_movimiento_inicial_no_se_crea_el_producto`, `test_no_se_puede_fijar_el_stock_actual_desde_el_formulario` |
| DIF-14 | Nombre de producto único | CU-02 alternativo: error "si ... el nombre ya existe"; `docs/03` no declara `UNIQUE` | La unicidad se valida **solo en la aplicación** (`Rule::unique` en `ProductoRequest`). La base de datos no tiene índice `UNIQUE` en `productos.nombre` | `ProductoRequest::rules:43`; migración `create_productos_table` |
| DIF-15 | Alerta de stock bajo | CU-05 paso 4: alerta si la **salida** deja el stock bajo el mínimo. RF-07: alerta en dashboard o listado | Ver los detalles debajo de la tabla | `MovimientoController::store:59-65`; `InicioController`; `Producto::scopeEnAlerta`; `MovimientoTest::test_una_entrada_que_no_alcanza_el_minimo_avisa_que_sigue_bajo`; `DashboardTest::test_inicio_muestra_una_linea_discreta_con_los_productos_en_stock_bajo` |
| DIF-16 | Indicadores del Dashboard (RF-03) | "total de productos, productos con stock bajo, movimientos del día" | "Productos activos" (no el total), "Activos en stock bajo" y "Movimientos de hoy" (día calendario en `America/Bogota`). Lista de alertas ordenada por mayor faltante, con acceso para registrar una entrada. Igual para los dos roles | `DashboardController::__invoke`; `DashboardTest` |
| DIF-17 | Historial (RF-08, CU-06) | Filtrable por fecha y tipo; CU-06 agrega producto | Ver los detalles debajo de la tabla | `FiltrosHistorialRequest`; `MovimientoController::historial`; `HistorialTest` |
| DIF-18 | Listado de productos (CU-06) | Muestra stock, unidad y estado | Además: búsqueda por nombre o categoría, filtro por categoría y por estado (Activos por defecto, Stock bajo, Inactivos, Todos), 15 por página. El encargado lo ve sin acciones de administrador | `ProductoController::listado`; `ProductoTest` |
| DIF-19 | Reportes (RF-10, CU-08) | 3 reportes y "opcionalmente exporta a PDF o Excel" | Ver los detalles debajo de la tabla | `ReporteController`; `RangoFechasRequest`; `public/js/app.js:240-246`; `ReporteTest` |
| DIF-20 | RF-02 recuperación de contraseña | Prioridad media | **No implementado**: no hay rutas. La tabla `password_reset_tokens` existe por la migración base de Laravel, pero no se usa. El administrador cambia las contraseñas desde Usuarios | `routes/web.php`; migración `0001_01_01_000000`; README "Pendientes futuros" |
| DIF-21 | RF-11 exportar PDF o Excel | Prioridad baja | **No implementado**; en su lugar se puede imprimir (DIF-19) | README "Pendientes futuros" |
| DIF-22 | Pantallas nuevas | Los docs no mencionan Inicio ni Buscar | **Inicio** (`/home`): saludo, accesos por rol y aviso de stock bajo. **Buscar** (`/search`): productos para los dos roles; además categorías y usuarios para el admin | `InicioController`; `BusquedaController`; `config/navegacion.php` |
| DIF-23 | Rutas | `docs/04` §5: `Route::resource` de productos, categorías y usuarios con `role:admin`; `/movimientos/nuevo`; `/dashboard` sin nombre | Ver los detalles debajo de la tabla | `routes/web.php`; `php artisan route:list` |
| DIF-24 | Capas (dónde se valida) | `docs/04` §4: "ProductoController@store valida" | Ver los detalles debajo de la tabla | `app/Http/Requests/*`; comentario en `MovimientoRequest.php:8-13`; `bootstrap/app.php:15-18` |
| DIF-25 | Modelo de datos | `docs/03` §1: `int id`, sin timestamps en el diagrama | `bigint(20) unsigned` autoincremental. Todas las tablas tienen `created_at` y `updated_at`, y son InnoDB con `utf8mb4_unicode_ci`. Índice `movimientos_inventario_fecha_index` (migración `2026_10_11_120000`). Las 3 llaves foráneas son `ON UPDATE RESTRICT` y `ON DELETE RESTRICT`: no hay borrado en cascada | `database/migrations/*`; confirmado en MariaDB con `SHOW CREATE TABLE`, `db:table --json` e `information_schema` (`02-clases.md` §A) |
| DIF-26 | RN-09 (inmutables) | Regla de negocio | Además de no tener rutas, el modelo lanza `LogicException` si se intenta actualizar o borrar un movimiento | `MovimientoInventario::booted`; `MovimientoTest::test_rn09_un_movimiento_no_se_edita_ni_se_elimina` |
| DIF-27 | Subclases de usuario (Cliente, Administrador, Repartidor) | Vienen del **análisis original de la materia**, no de `docs/` (aclaración del usuario; ese análisis no está en el repo). `docs/` **no** tiene subclases: el diagrama ER de `docs/03` modela `rol` como atributo `enum` de USERS, y `docs/01` §2 define solo Administrador y Encargado | El sistema usa **un solo** modelo `User` con `rol` = `enum('admin','encargado')`; no hay subclases. Cliente y Repartidor no existen (DIF-01). Administrador es un valor de `rol`, no una clase. Cómo modelarlo: `02-clases.md` §D.1 | `docs/03-modelo-datos.md` §1; `docs/01-requerimientos.md` §2; `app/Models/User.php`; migración `2026_09_12_043713:15`. V-02 (cerrado) |
| DIF-28 | RNF-04 (sesión de 30 min) | 30 minutos de inactividad | `.env.example` trae `SESSION_LIFETIME=30` (está probado). El valor efectivo depende del `.env` de cada equipo: en este equipo es **120** (`php artisan config:show session.lifetime`), así que RNF-04 no se cumple aquí hasta cambiar el `.env` | `.env.example:33`; `AutenticacionTest::test_la_plantilla_del_env_fija_la_sesion_en_30_minutos`. [VERIFICAR] V-03 |
| DIF-29 | Sesiones y caché en la base de datos | No se menciona | Con la configuración de `.env.example` (`SESSION_DRIVER=database`, `CACHE_STORE=database`), la sesión vive en la tabla `sessions` y el contador de intentos del login en la tabla `cache`. Aparecen como consultas de infraestructura en las secuencias | `.env.example:32, 42`; traza con `DB::listen` (`03-secuencias.md` §0) |
| DIF-30 | Cerrar sesión | No hay caso de uso | `POST /logout` desde el botón "Cerrar sesión" del encabezado: UC-03 | `routes/web.php:35`; `resources/views/layouts/app.blade.php:36-42` |

**Detalles de las filas largas**

- **DIF-06 (bloqueo por intentos):**
  - Cuenta 3 intentos fallidos por clave *correo en minúsculas + IP*, dentro de una ventana de 60 s que empieza en el primer fallo.
  - Bloquea hasta que vence esa ventana, incluso si la contraseña es correcta.
  - Un login correcto reinicia el contador.
  - Un intento con la contraseña correcta sobre una cuenta inactiva no suma ni reinicia el contador.
- **DIF-11 (RN-07):** la regla del sistema es más estricta y distinta.
  - Nadie puede desactivar su propia cuenta ni quitarse el rol de administrador, aunque haya otros administradores.
  - Sí puede desactivar a otro administrador o cambiarle el rol.
  - Como solo un administrador activo gestiona usuarios, siempre queda al menos uno activo (ver la salvedad en V-08).
- **DIF-15 (alerta de stock bajo):** además de lo que dice el doc:
  - Avisa también cuando una **entrada** no alcanza el mínimo ("sigue en stock bajo").
  - Muestra una línea de aviso en Inicio.
  - Marca "Stock bajo" en el listado y en Buscar.
  - Solo los productos **activos** cuentan como alerta.
- **DIF-17 (historial):**
  - Filtros por producto, tipo, desde y hasta (inclusivo, en hora de Bogotá).
  - 20 por página, del más reciente al más antiguo.
  - Un filtro inválido vuelve al historial sin filtros, con el error junto al campo.
  - El filtro de producto incluye los inactivos.
- **DIF-19 (reportes):**
  - Los 3 reportes son en pantalla; no hay exportación. En su lugar hay un botón **"Imprimir"** (`window.print()` y estilos `@media print`).
  - "Más movidos": top 10 por número de movimientos en un rango de fechas (por defecto, los últimos 30 días con hoy incluido), con los totales de entradas y salidas en la unidad de cada producto. Incluye productos inactivos.
  - "Stock por categoría": no suma cantidades de unidades distintas. Solo muestra productos activos.
- **DIF-23 (rutas):**
  - Todas tienen nombre y ninguna tiene `show`.
  - Productos y usuarios no tienen `destroy`; en su lugar hay `PATCH …/estado`.
  - Se usa `/movimientos/create`, no `/movimientos/nuevo`.
  - Todas las rutas protegidas pasan por el grupo `auth` + `activo`.
  - El listado de productos es para los dos roles.
  - Hay rutas nuevas: `/home`, `/search` y `/reportes/*`.
  - La raíz `/` redirige a Inicio.
- **DIF-24 (dónde se valida):**
  - La validación de forma está en 6 Form Requests.
  - Las reglas que dependen del stock en ese momento (RN-01 y producto inactivo) están en el **modelo** (`MovimientoInventario::registrar()`), dentro de la transacción.
  - Los permisos están en los middleware propios `activo` y `role`.

---

## 3. Actores

### 3.1 Actores del sistema

| Actor | Descripción | Cómo se identifica (campo y valores) | Fuente |
|---|---|---|---|
| **Administrador** | Control total: todo lo del encargado más catálogo (crear, editar, activar o desactivar productos; CRUD de categorías), usuarios y reportes | `users.rol = 'admin'` y `users.activo = true`. En el código: `User::esAdmin()` (`$this->rol === 'admin'`) | `app/Models/User.php:60-63`; `routes/web.php:55-78` |
| **Encargado de inventario** | Registra entradas y salidas; consulta productos, historial, Dashboard, Inicio y Buscar (solo productos). No gestiona catálogo, usuarios ni reportes (403) | `users.rol = 'encargado'` (valor por defecto de la columna) y `users.activo = true` | migración `2026_09_12_043713`; `routes/web.php:40-52` |
| *Usuario* (abstracto, opcional) | Generalización de los dos anteriores: cualquier persona con sesión iniciada y cuenta activa. Útil para los casos de uso que comparten | Sesión iniciada (`auth`) y `users.activo = true` (`activo`) | `routes/web.php:38` |

**Estados que no son actores del negocio**
- **Invitado (sin sesión):** es el estado previo al login. Solo puede usar UC-01; cualquier ruta protegida lo manda a `/login` (`bootstrap/app.php:22`).
- **Cuenta desactivada:** `users.activo = false`. No inicia sesión (UC-01 E4) y, si tenía sesión abierta, se cierra en su siguiente petición (UC-29).

**Actores secundarios:** ninguno. No hay sistemas externos.
- La impresión de reportes la hace el diálogo del navegador (`window.print()`).
- La hora de referencia para "hoy" es la zona `America/Bogota` (`APP_TIMEZONE`, `.env.example:7`).

### 3.2 ¿Un rol incluye todo lo del otro? (generalización)

**Sí.** En la matriz de `RutasTest`, toda ruta que puede usar el encargado también la puede usar el administrador:
- Ambos: 7 rutas (`home`, `dashboard`, `search`, `productos.index`, `movimientos.index`, `movimientos.create`, `movimientos.store`).
- Solo administrador: 21 rutas.
- El encargado no tiene ninguna ruta exclusiva.
- Total: 28 rutas protegidas.

Por eso es válida la generalización **Administrador ──▷ Encargado de inventario**: el administrador hereda los casos de uso del encargado.

La otra opción, también válida, es un actor abstracto *Usuario* del que heredan los dos. En ese caso, los casos de uso compartidos se asocian a *Usuario*.

Dos matices que no rompen la generalización:
- **UC-06 Buscar:** el administrador ve más grupos de resultados (categorías y usuarios); es una variación dentro del mismo caso de uso (`BusquedaController::__invoke:26`).
- **Interfaz:** a cada rol solo se le muestran los botones que puede usar. Por ejemplo, el encargado no ve "Nuevo producto" ni "Editar" (`ProductoTest::test_el_encargado_ve_el_listado_sin_acciones_de_administrador`).

### 3.3 Matriz de permisos por ruta (rol × ruta × resultado)

Para las 28 rutas protegidas, la fuente es `tests/Feature/RutasTest.php`:
- **Matriz:** `RutasTest::matriz()`.
- **Invitado:** `RutasTest::test_un_invitado_va_al_login_desde_cualquier_ruta_protegida`.
- **Encargado en rutas de admin:** `RutasTest::test_el_encargado_recibe_403_en_las_rutas_de_admin`.
- **Rol autorizado:** `RutasTest::test_el_rol_autorizado_nunca_recibe_403_404_ni_500`. Las pantallas (GET) responden 200 y las escrituras responden 302, porque siempre terminan en una redirección: éxito, error de validación o error de negocio.

Las 4 rutas de acceso (`raiz`, `login`, `login.entrar`, `logout`) están fuera de esa matriz (`RutasTest::RUTAS_DE_ACCESO`). Su resultado sale del código y de las pruebas que se indican en cada fila.

| # | Método | URI | Nombre | Middleware (orden real) | Invitado | Encargado | Administrador |
|---|---|---|---|---|---|---|---|
| 1 | GET | `/` | `raiz` | web | 302 → `/home` → 302 → `/login` | 302 → `/home` | 302 → `/home` |
| 2 | GET | `/login` | `login` | web, guest | 200 (formulario) | 302 → `/home` | 302 → `/home` |
| 3 | POST | `/login` | `login.entrar` | web, guest | 302 → `/home` (éxito) o 302 → `/login` (error) | 302 → `/home` (guest) | 302 → `/home` (guest) |
| 4 | POST | `/logout` | `logout` | web | 302 → `/login` | 302 → `/login` | 302 → `/login` |
| 5 | GET | `/home` | `home` | web, auth, activo | 302 → `/login` | 200 | 200 |
| 6 | GET | `/dashboard` | `dashboard` | web, auth, activo | 302 → `/login` | 200 | 200 |
| 7 | GET | `/search` | `search` | web, auth, activo | 302 → `/login` | 200 | 200 |
| 8 | GET | `/productos` | `productos.index` | web, auth, activo | 302 → `/login` | 200 | 200 |
| 9 | GET | `/productos/create` | `productos.create` | web, auth, activo, role:admin | 302 → `/login` | **403** | 200 |
| 10 | POST | `/productos` | `productos.store` | web, auth, activo, role:admin | 302 → `/login` | **403** | 302 |
| 11 | GET | `/productos/{producto}/edit` | `productos.edit` | web, auth, activo, role:admin | 302 → `/login` | **403** (404 si el id no existe) | 200 |
| 12 | PUT | `/productos/{producto}` | `productos.update` | web, auth, activo, role:admin | 302 → `/login` | **403** | 302 |
| 13 | PATCH | `/productos/{producto}/estado` | `productos.cambiar-estado` | web, auth, activo, role:admin | 302 → `/login` | **403** | 302 |
| 14 | GET | `/movimientos` | `movimientos.index` | web, auth, activo | 302 → `/login` | 200 | 200 |
| 15 | GET | `/movimientos/create` | `movimientos.create` | web, auth, activo | 302 → `/login` | 200 | 200 |
| 16 | POST | `/movimientos` | `movimientos.store` | web, auth, activo | 302 → `/login` | 302 | 302 |
| 17 | GET | `/categorias` | `categorias.index` | web, auth, activo, role:admin | 302 → `/login` | **403** | 200 |
| 18 | GET | `/categorias/create` | `categorias.create` | web, auth, activo, role:admin | 302 → `/login` | **403** | 200 |
| 19 | POST | `/categorias` | `categorias.store` | web, auth, activo, role:admin | 302 → `/login` | **403** | 302 |
| 20 | GET | `/categorias/{categoria}/edit` | `categorias.edit` | web, auth, activo, role:admin | 302 → `/login` | **403** | 200 |
| 21 | PUT o PATCH | `/categorias/{categoria}` | `categorias.update` | web, auth, activo, role:admin | 302 → `/login` | **403** | 302 |
| 22 | DELETE | `/categorias/{categoria}` | `categorias.destroy` | web, auth, activo, role:admin | 302 → `/login` | **403** | 302 |
| 23 | GET | `/usuarios` | `usuarios.index` | web, auth, activo, role:admin | 302 → `/login` | **403** | 200 |
| 24 | GET | `/usuarios/create` | `usuarios.create` | web, auth, activo, role:admin | 302 → `/login` | **403** | 200 |
| 25 | POST | `/usuarios` | `usuarios.store` | web, auth, activo, role:admin | 302 → `/login` | **403** | 302 |
| 26 | GET | `/usuarios/{usuario}/edit` | `usuarios.edit` | web, auth, activo, role:admin | 302 → `/login` | **403** | 200 |
| 27 | PUT | `/usuarios/{usuario}` | `usuarios.update` | web, auth, activo, role:admin | 302 → `/login` | **403** | 302 |
| 28 | PATCH | `/usuarios/{usuario}/estado` | `usuarios.cambiar-estado` | web, auth, activo, role:admin | 302 → `/login` | **403** | 302 |
| 29 | GET | `/reportes` | `reportes.index` | web, auth, activo, role:admin | 302 → `/login` | **403** | 200 |
| 30 | GET | `/reportes/stock-por-categoria` | `reportes.stock-por-categoria` | web, auth, activo, role:admin | 302 → `/login` | **403** | 200 |
| 31 | GET | `/reportes/stock-bajo` | `reportes.stock-bajo` | web, auth, activo, role:admin | 302 → `/login` | **403** | 200 |
| 32 | GET | `/reportes/mas-movidos` | `reportes.mas-movidos` | web, auth, activo, role:admin | 302 → `/login` | **403** | 200 |

**Fuentes de las filas 1 a 4 (rutas de acceso)**
- **Fila 1:** `routes/web.php:26` y `ExampleTest::test_la_raiz_redirige_a_home`.
- **Fila 2:** `AutenticacionTest::test_con_sesion_iniciada_el_login_redirige_a_home`.
- **Fila 3:** `AutenticacionTest::test_inicia_sesion_y_va_a_home`. El 302 → `/home` del middleware `guest` sale del código (`bootstrap/app.php:23`).
- **Fila 4:** `AutenticacionTest::test_cerrar_sesion` y `AuthController::logout`.

**Notas de la matriz**
- **Todas las rutas GET** aceptan también HEAD (`route:list`).
- **Métodos de actualización:** `categorias.update` acepta PUT y PATCH (es `Route::resource`); `productos.update` y `usuarios.update`, solo PUT.
- **Orden real del middleware** (verificado con `Router::resolveMiddleware`):
  1. `EncryptCookies`
  2. `AddQueuedCookiesToResponse`
  3. `StartSession`
  4. `ShareErrorsFromSession`
  5. `ValidateCsrfToken`
  6. `Authenticate` (`auth`)
  7. `SubstituteBindings`
  8. `UsuarioActivo` (`activo`)
  9. `VerificarRol` (`role:admin`)
- **Consecuencia del orden:** el *route model binding* (paso 7) corre **antes** de `activo` y `role:admin`.
  - Un encargado que pide `/productos/999/edit` (id inexistente) recibe **404**, no 403.
  - Comprobado en la traza: "Encargado pide editar un producto que NO existe" responde 404, y con un id existente, 403.
  - No hay prueba automática de esto.

### 3.4 URLs que no existen a propósito

Fuente: `RutasTest::test_las_urls_que_no_deben_existir_responden_404_o_405`. Todas responden 404 o 405.

| Petición | Por qué |
|---|---|
| `GET /productos/{id}`, `/categorias/{id}`, `/usuarios/{id}`, `/movimientos/1` | No hay `show` en ningún recurso (`routes/web.php:20`) |
| `DELETE /productos/{id}`, `DELETE /usuarios/{id}` | RN-03: se desactivan, no se eliminan |
| `GET /movimientos/1/edit`, `PUT`, `PATCH` y `DELETE /movimientos/1` | RN-09: los movimientos son inmutables |

---

## 4. Reglas de negocio e invariantes

Capas: **BD** (restricción en la base de datos), **FormRequest**, **Modelo**, **Controlador**, **Middleware**, **Vista** y **JS** (`public/js/app.js`; JS solo como ayuda: la validación real siempre está en el servidor).

### 4.1 Reglas de `docs/01` (RN-01 a RN-09) en el sistema

| ID | Regla (como funciona en el sistema) | Dónde se aplica | Fuente | Pruebas |
|---|---|---|---|---|
| RN-01 | No se registra una salida mayor al stock actual. Se puede sacar todo (queda 0) | Modelo, dentro de la transacción y con la fila bloqueada. JS solo como ayuda: el resumen dice "No alcanza: hay …" | `MovimientoInventario::registrar:84-90`; `public/js/app.js:204-210` | `MovimientoTest::test_rn01_no_se_registra_una_salida_mayor_al_stock`, `test_rn01_tambien_desde_el_modelo`, `test_se_puede_sacar_todo_el_stock` |
| RN-02 | Stock bajo si `stock_actual < stock_minimo` (mínimo propio de cada producto). Justo en el mínimo **no** es stock bajo | Modelo (scope y método) | `Producto::scopeStockBajo:50-53`, `Producto::enStockBajo:55-58`, `Producto::scopeEnAlerta:62-68` | `ProductoTest::test_rn02_el_scope_y_el_metodo_de_stock_bajo_coinciden`; `MovimientoTest::test_sin_quedar_bajo_el_minimo_no_hay_alerta` |
| RN-03 | Productos y usuarios no se eliminan: se desactivan y se reactivan | Rutas (sin `destroy`) + Controlador (`cambiarEstado`) + Vista (formulario "Desactivar" con confirmación) | `routes/web.php:19-22, 58-71`; `ProductoController::cambiarEstado:76-88`; `UsuarioController::cambiarEstado:62-80` | `RutasTest::test_las_urls_que_no_deben_existir_responden_404_o_405`; `ProductoTest::test_desactivar_y_volver_a_activar_un_producto`; `UsuarioTest::test_desactivar_pide_confirmacion_y_expulsa_la_sesion_abierta` |
| RN-04 | Ver los detalles debajo de la tabla | Modelo + Controlador | `MovimientoInventario::registrar:70-107`; `ProductoController::store:43-51` | `MovimientoTest::test_rn04_si_falla_guardar_el_movimiento_el_stock_no_cambia`, `test_rn04_en_mysql_la_fila_del_producto_se_bloquea_con_for_update`, `test_rn04_el_stock_siempre_es_la_suma_del_historial`; `ProductoTest::test_si_falla_el_movimiento_inicial_no_se_crea_el_producto` |
| RN-05 | Solo el administrador gestiona productos (crear, editar, estado), categorías, usuarios y reportes | Middleware `role:admin` (403) | `routes/web.php:55-78`; `VerificarRol::handle` | `RutasTest::test_el_encargado_recibe_403_en_las_rutas_de_admin`; `PermisosTest::test_el_encargado_recibe_403_en_todas_las_acciones_de_categorias`; `UsuarioTest::test_el_encargado_recibe_403_en_todo_usuarios`; `ReporteTest::test_solo_el_admin_ve_los_reportes` |
| RN-06 | El encargado solo registra movimientos y consulta (Inicio, Dashboard, Buscar, productos, historial) | Middleware (por exclusión) + Vista (los enlaces y botones salen según el rol) | `routes/web.php:40-52`; `config/navegacion.php`; `User::enlaces` | `RutasTest` (matriz); `InterfazTest::test_el_encargado_solo_ve_productos_y_movimientos`; `RutasTest::test_ningun_enlace_visible_lleva_a_403_ni_404` |
| RN-07 (sistema) | Nadie desactiva su propia cuenta ni se quita el rol de administrador; así siempre queda al menos un administrador activo (DIF-11) | FormRequest (rol) + Controlador (estado) + Vista (oculta los controles en la propia cuenta) | `UsuarioRequest::rules:40`, `UsuarioRequest::esSuPropiaCuenta:65-68`; `UsuarioController::cambiarEstado:67-71`; `usuarios/edit.blade.php:28-29`; `usuarios/partes/campos.blade.php:10-17` | `UsuarioTest::test_rn07_el_unico_admin_no_puede_quitarse_el_rol`, `test_rn07_el_unico_admin_no_puede_desactivarse`, `test_aunque_haya_otro_admin_nadie_se_desactiva_ni_se_quita_el_rol_a_si_mismo`, `test_la_hoja_de_tu_propia_cuenta_no_ofrece_cambiar_rol_ni_desactivar` |
| RN-08 | Cada producto tiene su propia unidad, elegida de una lista cerrada al crearlo | Enum + FormRequest (`Rule::enum`) + cast del modelo | `app/Enums/UnidadMedida.php`; `ProductoRequest::reglasDeUnidad:52-59`; `Producto::casts:26` | `ProductoTest::test_validaciones_al_crear_en_espanol`; `ProductoTest::test_la_unidad_se_escribe_en_singular_o_plural` |
| RN-09 | Los movimientos no se editan ni se eliminan; un error se corrige con un movimiento inverso | Rutas (no existen) + Modelo (eventos `updating` y `deleting` lanzan `LogicException`) | `routes/web.php:22`; `MovimientoInventario::booted:34-38` | `MovimientoTest::test_rn09_un_movimiento_no_se_edita_ni_se_elimina`; `RutasTest::test_las_urls_que_no_deben_existir_responden_404_o_405` |

**RN-04 en detalle**
- Cada movimiento se registra en una transacción que:
  1. Vuelve a leer el producto con `lockForUpdate()`.
  2. Valida.
  3. Actualiza `stock_actual`.
  4. Inserta el movimiento.
- Si algo falla, no queda nada.
- El stock inicial de un producto nuevo también entra como movimiento, dentro de la transacción que crea el producto.

### 4.2 Reglas nuevas encontradas en el sistema (RN-10 en adelante)

| ID | Regla | Dónde se aplica | Fuente | Pruebas |
|---|---|---|---|---|
| RN-10 | Un producto inactivo no admite movimientos. La hoja "Nuevo movimiento" solo ofrece productos activos | Modelo (error en `producto_id`) + Controlador (lista solo activos) | `MovimientoInventario::registrar:74-78`; `MovimientoController::create:33` | `MovimientoTest::test_un_producto_inactivo_no_admite_movimientos`, `test_la_hoja_solo_ofrece_productos_activos` |
| RN-11 | La cantidad de un movimiento es obligatoria, numérica, mayor que 0, de máximo 99.999.999,99 y con hasta 2 decimales. Acepta coma decimal ("12,5") | FormRequest (forma) + Modelo (> 0, como respaldo) | `MovimientoRequest::prepareForValidation:22-26`, `MovimientoRequest::rules:33`; `MovimientoInventario::registrar:80-82` | `MovimientoTest::test_validaciones_en_espanol` |
| RN-12 | Ver los detalles debajo de la tabla | Modelo (`$fillable` sin `stock_actual`) + FormRequest (`stock_inicial` prohibido al editar) + Modelo (`registrar()`) | `app/Models/Producto.php:14-21`; `ProductoRequest::rules:47`; `MovimientoInventario::registrar` | `ProductoTest::test_no_se_puede_fijar_el_stock_actual_desde_el_formulario`, `test_al_editar_no_se_acepta_stock_inicial`; `MovimientoTest::test_rn04_el_stock_siempre_es_la_suma_del_historial`; `BaseDeDatosTest::test_el_seeder_crea_una_granja_de_demostracion_coherente` |
| RN-13 | Si un producto ya tiene movimientos, su unidad no se puede cambiar. Las cantidades de productos con unidades distintas nunca se suman. El README lo incluye en RN-08 | FormRequest + Vista (muestra "Unidad fija") + Controlador del reporte | `ProductoRequest::reglasDeUnidad:52-59`; `productos/partes/campos.blade.php:11-21`; `ReporteController::stockPorCategoria:20-30` | `ProductoTest::test_con_movimientos_la_unidad_ya_no_cambia`; `ReporteTest::test_stock_por_categoria_no_suma_unidades_distintas` |
| RN-14 | El nombre de una categoría es obligatorio, de hasta 100 caracteres y único. Se le quitan los saltos de línea y los espacios repetidos | BD (`UNIQUE`) + FormRequest | migración `create_categorias_table:16`; `CategoriaRequest` | `CategoriaTest::test_el_nombre_es_obligatorio`, `test_el_nombre_no_puede_pasar_de_100_caracteres`, `test_no_se_repiten_nombres`, `test_el_nombre_se_guarda_sin_saltos_de_linea_ni_espacios_de_mas`, `test_actualizar_con_su_mismo_nombre_esta_permitido` |
| RN-15 | Una categoría con productos (activos o inactivos) no se elimina. El README lo incluye en RN-03 | Controlador + Vista (botón con `aria-disabled` y el motivo) + BD (la llave foránea sin `ON DELETE` impide el borrado como respaldo) | `CategoriaController::destroy:55-62`; `categorias/index.blade.php:57-74`; migración `create_productos_table:17` | `CategoriaTest::test_rn03_no_se_elimina_una_categoria_con_productos`, `test_con_productos_el_boton_eliminar_se_ve_deshabilitado_y_dice_cuantos_tiene`, `test_eliminar_una_categoria_sin_productos` |
| RN-16 | Ver los detalles debajo de la tabla | FormRequest | `ProductoRequest` | `ProductoTest::test_validaciones_al_crear_en_espanol` |
| RN-17 | Ver los detalles debajo de la tabla | FormRequest + Controlador + cast `hashed` + BD (`UNIQUE` en `email`) | `UsuarioRequest`; `UsuarioController::store:33`, `UsuarioController::update:51-54`; `User::casts:48` | `UsuarioTest::test_validaciones_en_espanol`, `test_el_correo_es_unico_sin_importar_mayusculas`, `test_al_editar_la_contrasena_vacia_no_la_cambia`, `test_crear_un_usuario_con_contrasena_inicial` |
| RN-18 | Bloqueo de login: 3 intentos fallidos por *correo + IP* dentro de 60 s bloquean esa clave hasta que vence la ventana. Un login correcto reinicia el contador | Controlador + `RateLimiter` (tabla `cache`) | `AuthController::login:29-49` | `AutenticacionTest::test_tras_tres_intentos_fallidos_se_bloquea_aunque_la_contrasena_sea_correcta`, `test_el_bloqueo_vence_despues_de_un_minuto`, `test_el_bloqueo_es_por_correo_e_ip`, `test_un_inicio_de_sesion_correcto_reinicia_el_contador` |
| RN-19 | Una cuenta inactiva no inicia sesión; si tenía la sesión abierta, se cierra en su siguiente petición | Controlador (login) + Middleware `activo` | `AuthController::login:53-57`; `UsuarioActivo::handle` | `AutenticacionTest::test_un_usuario_inactivo_no_puede_iniciar_sesion`, `test_un_usuario_desactivado_con_la_sesion_abierta_es_expulsado`; `MovimientoTest::test_una_cuenta_desactivada_no_puede_registrar` |
| RN-20 | Ver los detalles debajo de la tabla | Modelo (`scopeEnAlerta`) + Controladores + Vista | `Producto::scopeEnAlerta`; `InicioController:16`; `DashboardController:17-21`; `ReporteController::stockBajo`; `productos/index.blade.php:70-71` | `DashboardTest::test_los_indicadores_son_iguales_para_los_dos_roles`; `ReporteTest::test_stock_bajo_usa_la_misma_regla_y_orden_que_el_dashboard` |
| RN-21 | Ver los detalles debajo de la tabla | FormRequest + Controlador | `FiltrosHistorialRequest`; `RangoFechasRequest`; `MovimientoController::historial:78-81`; `DashboardController:23-27`; `ReporteController::masMovidos:46-49` | `HistorialTest::test_las_fechas_son_inclusivas_en_hora_de_bogota`, `test_filtros_invalidos_vuelven_al_historial_con_el_error`; `DashboardTest::test_movimientos_de_hoy_usa_el_dia_calendario_de_bogota`; `ReporteTest::test_mas_movidos_rango_inclusivo_en_ambos_bordes`, `test_mas_movidos_rango_invalido_vuelve_con_el_error` |
| RN-22 | Ver los detalles debajo de la tabla | Controlador + FormRequest | `ReporteController::masMovidos`; `RangoFechasRequest::desde`, `RangoFechasRequest::hasta` | `ReporteTest::test_mas_movidos_*` (7 pruebas) |
| RN-23 | Ver los detalles debajo de la tabla | Controlador + macro de consulta | `BusquedaController`; `AppServiceProvider::boot:29-37` | `BusquedaTest` (8 pruebas) |

**Detalles de RN-12 a RN-23**

- **RN-12 (stock solo por movimientos):**
  - `stock_actual` solo cambia mediante movimientos.
  - Invariante: `stock_actual = Σ entradas − Σ salidas` del producto.
  - `MovimientoInventario::registrar()` es el único punto que lo modifica.
- **RN-16 (producto):**
  - Nombre obligatorio, de hasta 150 caracteres y único (solo en la aplicación, DIF-14).
  - Categoría existente y unidad de la lista.
  - Stock mínimo obligatorio, ≥ 0, de máximo 99.999.999,99 y con hasta 2 decimales; acepta coma.
  - Stock inicial opcional, con el mismo formato, **solo al crear** (al editar está prohibido).
- **RN-17 (usuario):**
  - Nombre obligatorio, de hasta 255 caracteres.
  - Correo válido y único sin distinguir mayúsculas: se guarda en minúsculas y sin espacios.
  - Rol de la lista.
  - Contraseña de 8 a 255 caracteres, obligatoria al crear; al editar, vacía significa "no cambiar". Se guarda con hash.
  - Las cuentas nuevas nacen activas.
- **RN-20 (alertas solo de activos):**
  - Solo los productos **activos** cuentan como alerta (Dashboard, Inicio, reporte de stock bajo).
  - El listado no marca "Stock bajo" a un producto inactivo.
  - Las alertas se ordenan por mayor faltante y, después, por nombre.
- **RN-21 (fechas):**
  - "Hasta" no puede ser anterior a "Desde".
  - Los rangos son inclusivos: desde las 00:00 de "desde" hasta antes de las 00:00 del día siguiente a "hasta".
  - "Hoy" es el día calendario de `America/Bogota`.
- **RN-22 (reporte "Más movidos"):**
  - Muestra los 10 productos con más movimientos en el rango, con desempate por nombre.
  - Por defecto, el rango son los últimos 30 días con hoy incluido.
  - Incluye productos inactivos, con su etiqueta.
  - Las entradas y salidas se suman en la unidad de cada producto.
- **RN-23 (búsqueda):**
  - Mínimo 2 caracteres y máximo 10 resultados por grupo, cada uno con "Ver todos".
  - El encargado solo busca productos (por nombre o por nombre de categoría); el administrador también categorías (por nombre) y usuarios (por nombre o correo).
  - `%` y `_` se buscan como texto.

### 4.3 Invariantes técnicos

| ID | Invariante | Dónde se garantiza | Fuente |
|---|---|---|---|
| INV-01 | `productos.stock_actual ≥ 0` | Aplicación (RN-01). La BD no tiene `CHECK`: la columna es `DECIMAL(10,2)` con signo | `MovimientoInventario::registrar:85`; migración `create_productos_table:19` |
| INV-02 | Todo movimiento referencia un producto y un usuario existentes | BD: llaves foráneas `NOT NULL` `movimientos_inventario_producto_id_foreign` y `movimientos_inventario_usuario_id_foreign`, con `ON DELETE RESTRICT` (sin borrado en cascada) | migración `create_movimientos_inventario_table:16-17`; `information_schema.REFERENTIAL_CONSTRAINTS` (`02` §A.2) |
| INV-03 | Todo producto pertenece a una categoría existente | BD: llave foránea `NOT NULL` `productos_categoria_id_foreign` hacia `categorias`, con `ON DELETE RESTRICT` | migración `create_productos_table:17`; `02` §A.2 |
| INV-04 | El correo de usuario es único | BD (`users_email_unique`) + FormRequest | migración `users:17`; `UsuarioRequest:39` |
| INV-05 | El nombre de categoría es único | BD (`categorias_nombre_unique`) + FormRequest | migración `create_categorias_table:16`; `CategoriaRequest:36` |
| INV-06 | Siempre hay al menos un administrador activo | Consecuencia de RN-07: solo un administrador activo gestiona usuarios y nunca puede desactivarse a sí mismo. La BD no lo restringe. Ver V-08 | `UsuarioRequest`; `UsuarioController::cambiarEstado` |
| INV-07 | `movimientos_inventario.fecha` nunca es nula | BD (`DEFAULT CURRENT_TIMESTAMP`) + Modelo (`fecha ?? now()`) | migración `create_movimientos_inventario_table:20`; `MovimientoInventario::registrar:105` |
| INV-08 | `tipo` ∈ {`entrada`, `salida`} | BD (`ENUM`) + FormRequest (`Rule::in`) + Modelo (`InvalidArgumentException`) | migración `:18`; `MovimientoRequest:31`; `MovimientoInventario::registrar:66-68` |
| INV-09 | `rol` ∈ {`admin`, `encargado`} | BD (`ENUM`) + FormRequest (`Rule::in(array_keys(User::ROLES))`) | migración `2026_09_12_043713:15`; `UsuarioRequest:40` |
| INV-10 | La contraseña nunca se guarda en texto plano y no se cifra dos veces | Cast `'password' => 'hashed'`; los controladores no llaman a `Hash::make` | `User::casts:48`; `UsuarioTest::test_al_editar_con_contrasena_nueva_se_cambia_y_se_puede_entrar_con_ella` |
| INV-11 | Toda petición que escribe lleva token CSRF; si falta o vence, responde 419 | Middleware `ValidateCsrfToken` (grupo `web`) + `@csrf` en cada formulario POST | `SeguridadTest::test_todo_formulario_que_escribe_lleva_token_csrf`; `PermisosTest::test_el_419_tiene_vista_propia_en_espanol` |
| INV-12 | Después de registrar un movimiento solo se vuelve a una pantalla de la lista fija (`productos`, `dashboard`), nunca a una URL recibida | Controlador | `MovimientoController::VOLVER:19`, `MovimientoController::volver:95-98` | `MovimientoTest::test_volver_solo_acepta_claves_de_la_lista_y_nunca_una_url_externa` |
| INV-13 | Ninguna vista imprime HTML sin escapar ni carga scripts externos o inline | Vistas Blade (`{{ }}`); scripts del mismo origen | `SeguridadTest::test_ninguna_vista_imprime_html_sin_escapar`; `IcosaedroTest::test_ninguna_vista_carga_scripts_externos_ni_inline` |

---

## 5. Glosario

| Término del negocio | Significado | Equivalente en el sistema (clase · tabla · ruta) |
|---|---|---|
| Producto | Artículo que se guarda en el almacén (alimento, vacuna, insumo) | `App\Models\Producto` · `productos` · `productos.*` |
| Categoría | Agrupación de productos (p. ej. "Alimento balanceado") | `App\Models\Categoria` · `categorias` · `categorias.*` |
| Movimiento de inventario | Registro de una entrada o una salida | `App\Models\MovimientoInventario` · `movimientos_inventario` · `movimientos.*` |
| Entrada | Movimiento que suma al stock (compra, reposición, stock inicial) | `tipo = 'entrada'` |
| Salida | Movimiento que resta del stock (consumo) | `tipo = 'salida'` |
| Stock actual | Cantidad disponible de un producto, en su unidad | `productos.stock_actual` (`DECIMAL(10,2)`) |
| Stock mínimo (umbral) | Cantidad por debajo de la cual hay alerta; es propia de cada producto | `productos.stock_minimo` |
| Stock bajo | `stock_actual < stock_minimo` | `Producto::stockBajo()` (scope), `Producto::enStockBajo()`; filtro `?estado=stock-bajo`; etiqueta "Stock bajo" |
| Producto en alerta | Producto **activo** en stock bajo | `Producto::enAlerta()` (scope) |
| Faltante | Cuánto le falta para llegar al mínimo (0 si no está en stock bajo) | `Producto::faltante()` |
| Unidad de medida | Unidad en la que se cuenta un producto | enum `App\Enums\UnidadMedida` · `productos.unidad_medida` |
| Stock inicial | Cantidad con la que se crea un producto; entra como movimiento de entrada | campo `stock_inicial` del formulario; no es columna |
| Producto activo o inactivo | Inactivo: no admite movimientos y no genera alertas, pero conserva su historial | `productos.activo` · `productos.cambiar-estado` |
| Usuario o cuenta | Persona que usa el sistema | `App\Models\User` · `users` · `usuarios.*` |
| Rol | Administrador o Encargado de inventario | `users.rol` (`admin`, `encargado`), `User::ROLES`, middleware `role` |
| Cuenta activa o desactivada | Desactivada: no puede iniciar sesión | `users.activo` · middleware `activo` · `usuarios.cambiar-estado` |
| Sesión | Acceso abierto de una persona; expira por inactividad | tabla `sessions`; `SESSION_LIFETIME` |
| Bloqueo de acceso | Espera de 60 s tras 3 intentos fallidos | `RateLimiter` · tabla `cache` |
| Inicio | Pantalla de bienvenida con accesos por rol | `home` · `InicioController` · `home.blade.php` |
| Dashboard | Indicadores y productos en alerta | `dashboard` · `DashboardController` |
| Movimientos de hoy | Movimientos con fecha del día calendario actual (Bogotá) | indicador `movimientosHoy` |
| Historial | Lista de movimientos con filtros | `movimientos.index` · `FiltrosHistorialRequest` |
| Buscar | Búsqueda global agrupada | `search` · `BusquedaController` |
| Ver todos | Enlace de Buscar al listado filtrado | `productos.index?q=`, `categorias.index?q=`, `usuarios.index?q=` |
| Reporte | Informe en pantalla, solo para el administrador | `reportes.*` · `ReporteController` |
| Más movidos | Top 10 de productos por número de movimientos en un rango | `reportes.mas-movidos` · `RangoFechasRequest` |
| Imprimir | Abre el diálogo de impresión del navegador | `data-imprimir` → `window.print()` (`public/js/app.js:240-246`) |
| Hoja | Formulario de crear o editar que se abre encima del listado, como un diálogo | `components/hoja.blade.php`; rutas `*.create` y `*.edit` |
| Mensaje flash | Mensaje que se muestra una sola vez tras una acción | sesión `success`, `error`, `aviso` (`layouts/app.blade.php:49-59`) |
| Aviso de stock bajo | Mensaje tras registrar un movimiento si el producto queda o sigue bajo su mínimo | flash `aviso` (`MovimientoController::store:59-65`) |
| Volver | Pantalla a la que regresa la hoja de movimiento | parámetro `volver` (`productos` o `dashboard`) |
| Icosaedro | Elemento 3D decorativo (RNF-08), sin función de negocio | componente `x-icosaedro` |

---

## 6. Trazabilidad

### 6.1 Requisitos funcionales → casos de uso → rutas → pruebas

| RF | Prioridad | Estado en el sistema | UC | Rutas | Pruebas que lo cubren |
|---|---|---|---|---|---|
| RF-01 Autenticación por correo y contraseña, con rol | Alta | Cubierto | UC-01, UC-02, UC-03 | `raiz`, `login`, `login.entrar`, `logout` | `AutenticacionTest` (12); `InterfazTest::test_el_login_esta_en_espanol_y_tiene_etiquetas`, `test_el_error_de_credenciales_se_muestra_junto_al_campo`; `SeguridadTest::test_el_login_con_error_vuelve_al_login_aunque_el_referer_sea_externo`; `BaseDeDatosTest::test_las_cuentas_de_demostracion_inician_sesion`; `ExampleTest::test_la_raiz_redirige_a_home` |
| RF-02 Recuperación de contraseña por correo | Media | **Fuera de alcance v1** (DIF-20) | — | — | — |
| RF-03 Dashboard con indicadores | Alta | Cubierto, con ajustes (DIF-16) | UC-05 | `dashboard` | `DashboardTest` (6 del Dashboard) |
| RF-04 CRUD de productos (solo admin) | Alta | Cubierto, sin eliminar (RN-03) | UC-07, UC-09, UC-10, UC-11 | `productos.index`, `.create`, `.store`, `.edit`, `.update`, `.cambiar-estado` | `ProductoTest` (23); `RutasTest` |
| RF-05 CRUD de categorías (solo admin) | Alta | Cubierto | UC-12, UC-13, UC-14, UC-15 | `categorias.index`, `.create`, `.store`, `.edit`, `.update`, `.destroy` | `CategoriaTest` (18); `PermisosTest::test_el_admin_entra_a_categorias`, `test_el_encargado_recibe_403_en_todas_las_acciones_de_categorias` |
| RF-06 Registro de entradas y salidas | Alta | Cubierto | UC-16, UC-17 (y UC-18, UC-19) | `movimientos.create`, `movimientos.store` | `MovimientoTest` (23) |
| RF-07 Alerta de stock bajo | Alta | Cubierto, ampliado (DIF-15) | UC-19, UC-04, UC-05, UC-07, UC-25 | `movimientos.store`, `home`, `dashboard`, `productos.index`, `reportes.stock-bajo` | `MovimientoTest::test_una_salida_que_deja_el_stock_bajo_el_minimo_muestra_la_alerta`, `test_sin_quedar_bajo_el_minimo_no_hay_alerta`, `test_una_entrada_que_no_alcanza_el_minimo_avisa_que_sigue_bajo`; `DashboardTest::test_las_alertas_van_del_mayor_al_menor_faltante_con_acceso_para_registrar_una_entrada`, `test_inicio_muestra_una_linea_discreta_con_los_productos_en_stock_bajo`; `ProductoTest::test_el_listado_muestra_stock_unidad_categoria_y_badge_de_stock_bajo` |
| RF-08 Historial filtrable | Media | Cubierto, también por producto y rango (DIF-17) | UC-08 | `movimientos.index` | `HistorialTest` (7); `MovimientoTest::test_el_historial_va_del_mas_reciente_al_mas_antiguo_y_se_pagina`, `test_sin_movimientos_muestra_el_estado_vacio` |
| RF-09 Gestión de usuarios | Media | Cubierto | UC-20, UC-21, UC-22, UC-23 | `usuarios.index`, `.create`, `.store`, `.edit`, `.update`, `.cambiar-estado` | `UsuarioTest` (13) |
| RF-10 Reportes básicos | Media | Cubierto (DIF-19) | UC-24, UC-25, UC-26 | `reportes.index`, `.stock-por-categoria`, `.stock-bajo`, `.mas-movidos` | `ReporteTest` (14) |
| RF-11 Exportar reportes a PDF o Excel | Baja | **Fuera de alcance v1** (DIF-21); se reemplaza por imprimir | (UC-27) | — | `ReporteTest::test_cada_reporte_tiene_imprimir_y_la_barra_no_se_imprime` |
| **RF-12** (nuevo) Inicio con accesos por rol y aviso de stock bajo | — | Cubierto | UC-04 | `home`, `raiz` | `InterfazTest::test_el_admin_ve_todas_las_tarjetas_de_acceso`, `test_el_encargado_solo_ve_productos_y_movimientos`; `DashboardTest::test_inicio_muestra_una_linea_discreta_con_los_productos_en_stock_bajo` |
| **RF-13** (nuevo) Búsqueda agrupada según el rol | — | Cubierto | UC-06 | `search` | `BusquedaTest` (8); `InterfazTest::test_la_busqueda_conserva_el_texto_y_filtra_opciones_por_rol` |
| **RF-14** (nuevo) Cerrar sesión | — | Cubierto | UC-03 | `logout` | `AutenticacionTest::test_cerrar_sesion` |
| **RF-15** (nuevo) Imprimir reportes | — | Cubierto | UC-27 | (sin ruta: JavaScript) | `ReporteTest::test_cada_reporte_tiene_imprimir_y_la_barra_no_se_imprime` |
| **RF-16** (nuevo) Volver a activar productos y cuentas | — | Cubierto | UC-11, UC-23 | `productos.cambiar-estado`, `usuarios.cambiar-estado` | `ProductoTest::test_desactivar_y_volver_a_activar_un_producto`; `UsuarioTest::test_desactivar_pide_confirmacion_y_expulsa_la_sesion_abierta` |

El número entre paréntesis es la cantidad de métodos `test_*` de la clase (Grep `public function test_` en `tests/`).

### 6.2 Requisitos de usuario → casos de uso

| RU | Necesidad (resumen) | UC |
|---|---|---|
| RU-01 | Administrador: gestionar productos y categorías | UC-07, UC-09, UC-10, UC-11, UC-12, UC-13, UC-14, UC-15 |
| RU-02 | Administrador y encargado: registrar entradas y salidas | UC-16, UC-17 (UC-18, UC-19) |
| RU-03 | Administrador y encargado: ver de un vistazo el stock bajo | UC-04, UC-05, UC-07, UC-19, UC-25 |
| RU-04 | Administrador: reportes | UC-24, UC-25, UC-26, UC-27 |
| RU-05 | Administrador: cuentas y roles | UC-20, UC-21, UC-22, UC-23 |
| RU-06 | Todos: iniciar sesión de forma segura | UC-01, UC-02, UC-03 (UC-28, UC-29) |
| RU-07 | Administrador y encargado: historial de un producto | UC-08 |

### 6.3 Reglas de negocio → casos de uso

| RN | UC donde se aplica |
|---|---|
| RN-01 | UC-17, UC-18 |
| RN-02 | UC-04, UC-05, UC-07, UC-19, UC-25 |
| RN-03 | UC-11, UC-23 (y la ausencia de "Eliminar producto o usuario") |
| RN-04 | UC-09 (stock inicial), UC-16, UC-17 |
| RN-05 | UC-30 (y todos los UC solo de admin) |
| RN-06 | UC-30; UC-04 (accesos por rol); UC-06 (grupos por rol); UC-07 (sin acciones de admin) |
| RN-07 | UC-22, UC-23 |
| RN-08 | UC-09, UC-10 |
| RN-09 | UC-16, UC-17 (postcondición), UC-08 (solo lectura) |
| RN-10 | UC-16, UC-17, UC-11 |
| RN-11 | UC-16, UC-17 |
| RN-12 | UC-09, UC-10, UC-16, UC-17 |
| RN-13 | UC-10, UC-24 |
| RN-14 | UC-13, UC-14 |
| RN-15 | UC-15 |
| RN-16 | UC-09, UC-10 |
| RN-17 | UC-21, UC-22 |
| RN-18 | UC-01, UC-02 |
| RN-19 | UC-01, UC-29 |
| RN-20 | UC-04, UC-05, UC-07, UC-25 |
| RN-21 | UC-05, UC-08, UC-26 |
| RN-22 | UC-26 |
| RN-23 | UC-06 |

### 6.4 Casos de uso de `docs/02` (CU) → casos de uso de este paquete (UC)

| CU (docs/02) | UC | Comentario |
|---|---|---|
| CU-01 Iniciar sesión | UC-01 (+ UC-02 `<<extend>>`) | Destino tras el login: DIF-05. Bloqueo: DIF-06 |
| CU-02 Registrar producto | UC-09 (+ UC-16 `<<extend>>` por stock inicial) | DIF-13, DIF-14 |
| CU-03 Editar o desactivar producto | UC-10 y UC-11 (`<<extend>>` de UC-10) | Se puede volver a activar (DIF-08) |
| CU-04 Registrar entrada de stock | UC-16 (+ UC-19 `<<extend>>`) | — |
| CU-05 Registrar salida de stock | UC-17 (+ UC-18 `<<include>>`, + UC-19 `<<extend>>`) | — |
| CU-06 Consultar stock e historial | UC-07 y UC-08 | DIF-17, DIF-18 |
| CU-07 Gestionar usuarios | UC-20, UC-21, UC-22, UC-23 | RN-07: DIF-11 |
| CU-08 Generar reportes | UC-24, UC-25, UC-26 (+ UC-27 `<<extend>>`) | No hay exportación (DIF-19) |
| (nuevo) | UC-03, UC-04, UC-05, UC-06, UC-12, UC-13, UC-14, UC-15, UC-28, UC-29, UC-30 | DIF-10, DIF-22, DIF-30 |

### 6.5 Requisitos no funcionales (sin caso de uso)

| RNF | Evidencia en el sistema |
|---|---|
| RNF-01 Responsivo de 320 a 1920 px | README §introducción ("responsive de 320 a 1920 px"). No hay prueba automática |
| RNF-02 Contraste WCAG AA | README §introducción ("con contraste WCAG AA"). No hay prueba automática |
| RNF-03 Sistema de diseño | `resources/css/input.css` (`@theme`); componentes en `resources/views/components/` |
| RNF-04 Sesión de 30 min | `.env.example:33`; `AutenticacionTest::test_la_plantilla_del_env_fija_la_sesion_en_30_minutos`. En este equipo el valor efectivo es 120 (ver V-03) |
| RNF-05 Contraseñas con hash | `User::casts:48` (`hashed`, bcrypt); `UsuarioTest::test_crear_un_usuario_con_contrasena_inicial` |
| RNF-06 Carga ≤ 3 s | Sin prueba automática. Las pruebas N+1 limitan las consultas: `ProductoTest::test_el_listado_no_hace_una_consulta_por_producto`, `DashboardTest::test_el_dashboard_no_hace_una_consulta_por_producto`, `HistorialTest::test_el_historial_no_hace_una_consulta_por_movimiento`, `ReporteTest::test_*_n_mas_1` y similares |
| RNF-07 Feedback visual | `public/js/app.js:3-38` (spinner y botón deshabilitado al enviar); mensajes flash (`InterfazTest::test_el_layout_muestra_los_mensajes_flash`) |
| RNF-08 Icosaedro 3D | `IcosaedroTest` (6). Es decorativo: no tiene caso de uso |

---

## 7. Lista de [VERIFICAR]

### 7.1 Abiertos

| ID | Qué falta confirmar | Por qué no se pudo confirmar | Dónde aparece |
|---|---|---|---|
| V-03 | Valor de `SESSION_LIFETIME` en el `.env` de cada equipo (RNF-04) | El `.env` no se lee entero por regla del proyecto. `.env.example` trae 30. **En este equipo el valor efectivo es 120** (`php artisan config:show session.lifetime`). En el portátil, sin verificar | DIF-28; §6.5 |
| V-06 | Qué pasa si una carrera agrega un producto a una categoría entre el conteo y el borrado (UC-15) | No hay prueba de concurrencia. Por el esquema, la llave foránea `productos_categoria_id_foreign` (`ON DELETE RESTRICT`) rechazaría el `DELETE` con un error de integridad (respuesta 500) | `01-casos-de-uso.md` UC-15; `03-secuencias.md` SEQ-10 |
| V-07 | Duplicados de nombre de producto por una carrera entre dos creaciones simultáneas | La unicidad solo está en la aplicación (DIF-14); no hay prueba de concurrencia | DIF-14; UC-09 |
| V-08 | INV-06 ante dos administradores que se desactivan mutuamente **a la vez** | Cada petición solo verifica "no es tu propia cuenta"; no hay bloqueo ni verificación de "queda otro administrador activo". En peticiones secuenciales no ocurre: el primero en ser desactivado es expulsado antes de actuar. No hay prueba | INV-06; UC-23 |
| V-09 | Si con un id inexistente debe responder 404 antes que 403 en rutas de admin (encargado o cuenta inactiva) | Es efecto del orden del middleware del framework (binding antes de `activo` y `role`); no hay prueba ni requisito | §3.3; `01-casos-de-uso.md` §3 |
| V-10 | Errores de formato en el login (campos vacíos o correo inválido): vuelven a `url()->previous()` (Referer o la URL guardada en sesión), no a `route('login')` como los demás errores del login | En el uso normal es `/login` y el token CSRF impide un envío desde otro sitio. `SeguridadTest` solo cubre el error de credenciales | `01-casos-de-uso.md` UC-01 E1; `03-secuencias.md` SEQ-01 |

V-06 a V-10 no son errores de esta documentación. Son comportamientos deducidos del código que ninguna prueba cubre. Quedan como insumo para la auditoría pendiente; no se cambió nada de la aplicación.

### 7.2 Cerrados en la revisión 2

| ID | Qué era | Cómo se cerró | Resultado |
|---|---|---|---|
| V-01 | Definición original de los actores Cliente y Repartidor | Aclaración del usuario (2026-10-12) | En el análisis original de la materia eran subclases de usuario, junto con Administrador. No existen en el sistema real ni en `docs/`, y quedan fuera de alcance (DIF-01, DIF-27) |
| V-02 | De dónde salían las "subclases como en docs/03" | Aclaración del usuario: fue un error de la consigna | Las subclases vienen del análisis original, no de `docs/03`. `docs/` no tiene subclases y el sistema usa un solo `User` con `rol` enum (DIF-27; `02-clases.md` §D.1) |
| V-04 | Nombres de los índices de las llaves foráneas | `SHOW CREATE TABLE`, `php artisan db:table --json` e `information_schema` en MariaDB 10.4.32 (`inventario_avicola_test`) | Ver los detalles debajo de la tabla |
| V-05 | Texto exacto en MySQL del `for update` y del savepoint | Traza con `DB::listen` en MariaDB 10.4.32 (`inventario_avicola_test`), un PDO que anota las llamadas de transacción y los contadores `Com_*` de la sesión | Ver los detalles debajo de la tabla |

**V-04 en detalle**
- Los índices de las llaves foráneas se llaman como la restricción: `productos_categoria_id_foreign`, `movimientos_inventario_producto_id_foreign` y `movimientos_inventario_usuario_id_foreign`.
- Las tres llaves son `ON UPDATE RESTRICT` y `ON DELETE RESTRICT`.
- Todas las tablas son InnoDB con `utf8mb4_unicode_ci`.
- Detalle en `02-clases.md` §A.

**V-05 en detalle**
- Bloqueo: ``select * from `productos` where `productos`.`id` = ? limit 1 for update``.
- Savepoint: `PDO::exec("SAVEPOINT trans2")`. El servidor contó `Com_savepoint +1` y `Com_release_savepoint +0`.
- Si falla dentro del savepoint: `ROLLBACK TO SAVEPOINT trans2` y después `PDO::rollBack()`.
- Inicio y fin de transacción: `PDO::beginTransaction()` y `PDO::commit()` / `PDO::rollBack()`.
- Detalle en `03-secuencias.md` §0.2.
