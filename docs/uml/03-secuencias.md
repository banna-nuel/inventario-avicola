# UML · 03 — Secuencias

**Fuentes:** el código citado en cada escenario y una **traza real de consultas SQL** tomada con `DB::listen` (§0).
**Convenciones:** las de `00-contexto-y-reglas.md`. Los casos de uso (UC) están en `01-casos-de-uso.md`; las clases, en `02-clases.md`.

---

## 0. Cómo se obtuvo el orden real y qué pasa en toda petición

### 0.1 Método de la traza

Se usó un test **temporal**, fuera del repositorio y sin commitear: `TrazaSqlTest.php`, en la carpeta temporal de la sesión de trabajo. Lo que hace:

| Aspecto | Cómo se hizo |
|---|---|
| Base de datos | **SQLite en memoria** (`phpunit.xml`). MySQL de XAMPP estaba apagado, así que no se pudo usar `inventario_avicola_test`. **Nunca** se usó la base de desarrollo. Ver V-05 |
| Transacciones | `DatabaseMigrations` en lugar de `RefreshDatabase`, para que la prueba no envuelva todo en otra transacción: las transacciones de la app quedan en el nivel 1, como en producción |
| Sesión y caché | Como en `.env.example`: `session.driver = database` y `cache.default = database`. Sin la limpieza al azar de sesiones (`session.lottery = [0, 100]`), para que la traza no cambie entre corridas |
| Petición realista | Cada actor (Ana, Laura, Carlos) es un "navegador" con su propia cookie de sesión. Antes de cada petición se olvida lo que quedó en memoria (`Auth::forgetGuards()`, `forgetInstance('auth.driver')`, `session.store->flush()`), como cuando la app arranca de cero en cada petición. Se envía el `Referer` de la pantalla del formulario |
| Qué se registró | Cada consulta con `DB::listen` (SQL, valores y nivel de transacción). Los eventos `TransactionBeginning`, `TransactionCommitted` y `TransactionRolledBack`. El código HTTP, la redirección y los mensajes flash de cada respuesta |
| Datos | Sintéticos: administradora Laura (#1); encargada Ana (#2); categorías Alimento (#1) y Vacunas (#2); Maíz amarillo (#1: 100 kg, mínimo 50) y Soya (#2: 10 kg, mínimo 20); luego Carlos (#3) |

Diferencias entre la traza en SQLite y MySQL o MariaDB:
1. **`lockForUpdate()`:** en MySQL agrega ` for update` al final del `select`. SQLite lo ignora.
   - El README registra que `MovimientoTest::test_rn04_en_mysql_la_fila_del_producto_se_bloquea_con_for_update` lo comprobó en MySQL en la Fase 9: `select * from productos where id = ? limit 1 for update`.
2. **Inicio de transacción:** `START TRANSACTION` (`PDO::beginTransaction`). `COMMIT` y `ROLLBACK` también van por PDO. **No aparecen en `DB::listen`**: aquí se marcan con los eventos.
3. **Transacción anidada:** Laravel ejecuta `SAVEPOINT trans2` directamente por PDO, sin pasar por `DB::listen`.
   - Al confirmarla **no** ejecuta `RELEASE SAVEPOINT`: solo baja el contador (`vendor/…/Database/Concerns/ManagesTransactions.php:52-57, 170-173`).
   - Si falla, ejecuta `ROLLBACK TO SAVEPOINT trans2`.
4. **Comillas:** los identificadores van entre comillas invertidas (`` ` ``) en MySQL. Aquí se muestran sin comillas.
5. **Caché:** `insert or ignore` de SQLite es `insert ignore` en MySQL.

[VERIFICAR] V-05: correr la misma traza contra `inventario_avicola_test` cuando MySQL esté encendido.

**Notación de las trazas:** `[n0]` = fuera de transacción · `[n1]` = dentro de la transacción principal · `[n2]` = dentro de la anidada (savepoint). `← …` = valores enviados.

### 0.2 Participantes estándar (nombres de las líneas de vida)

| Participante | Qué es | Fuente |
|---|---|---|
| `Ana : Encargado` / `Laura : Administrador` | Actor | — |
| `Navegador` | Página Blade y `public/js/app.js` (botón deshabilitado, confirmaciones, resumen de stock, imprimir) | `public/js/app.js` |
| `Ruta <nombre>` | Router de Laravel; resuelve la ruta y su middleware | `routes/web.php` |
| `web` | Grupo de middleware: `EncryptCookies` → `StartSession` → `ShareErrorsFromSession` → `ValidateCsrfToken` → `SubstituteBindings` | Laravel |
| `auth` | `Authenticate`: carga al usuario de la sesión | `bootstrap/app.php:22` |
| `activo` | `UsuarioActivo` | `app/Http/Middleware/UsuarioActivo.php` |
| `role:admin` | `VerificarRol` | `app/Http/Middleware/VerificarRol.php` |
| `XxxRequest` | Form Request: se valida antes de entrar al controlador | `app/Http/Requests/*` |
| `XxxController` | Controlador | `app/Http/Controllers/*` |
| `Producto`, `MovimientoInventario`, `Categoria`, `User` | Modelos Eloquent | `app/Models/*` |
| `RateLimiter` | Contador de intentos del login (usa la tabla `cache`) | `AuthController` |
| `BD` | MySQL: tablas del dominio + `sessions` + `cache` | migraciones |

### 0.3 SEQ-00 — Patrón común de toda petición web

Fragmento reutilizable: en los demás escenarios aparece como **`ref SEQ-00`**.

| # | Desde → Hacia | Mensaje | Respuesta y notas |
|---|---|---|---|
| 1 | Actor → Navegador | Acción: clic en un enlace o envío de un formulario | En los formularios, `app.js` deshabilita el botón y muestra el texto de carga (FC-7) |
| 2 | Navegador → Ruta | Petición HTTP con la cookie de sesión cifrada. En POST, PUT, PATCH y DELETE va también `_token` (y `_method` para PUT, PATCH y DELETE) | — |
| 3 | Ruta → `web` (`StartSession`) → BD | Lee la sesión: `select * from sessions where id = ? limit 1` | Si la cookie no existe o la sesión venció, empieza una sesión nueva |
| 4 | `web` (`ValidateCsrfToken`) | Compara `_token` con el de la sesión (solo en escrituras) | **alt** [no coincide o venció] → **419** (`errors/419`) |
| 5 | `web` → `auth` → BD | Rutas protegidas: carga al usuario, `select * from users where id = ? limit 1` | **alt** [no hay usuario en la sesión] → **302 → `/login`** (FC-1) |
| 6 | `web` (`SubstituteBindings`) → BD | Rutas con `{producto}`, `{categoria}` o `{usuario}`: `select * from <tabla> where id = ? limit 1` | **alt** [no existe] → **404** (FC-5). Corre **antes** de `activo` y `role` (`00` §3.3) |
| 7 | → `activo` | Lee `$user->activo` (sin consulta) | **alt** [`false`] → expulsión (SEQ-09, parte B) |
| 8 | → `role:admin` | Lee `$user->rol` (sin consulta) | **alt** [no es `admin`] → **403** (SEQ-15) |
| 9 | Ruta → `XxxRequest` | Si el método del controlador lo pide: `prepareForValidation()` → `rules()` → consultas de validación (`unique`, `exists`) | **alt** [falla] → **302** a la URL anterior (Referer o `_previous.url` de la sesión) o a `$redirectRoute`, con los errores y lo escrito (`old`) |
| 10 | Ruta → Controlador → Modelos → BD | Lo propio del escenario | — |
| 11 | Controlador → Ruta | `View` (200) o `redirect()` (302) con mensaje flash (`success`, `error`, `aviso`) | — |
| 12 | `web` (`StartSession`) → BD | Guarda la sesión: `update sessions set payload = ?, last_activity = ?, user_id = ?, ip_address = ?, user_agent = ? where id = ?` | Si la sesión es nueva: primero `select` y después `insert into sessions (…)` |
| 13 | Ruta → Navegador | Respuesta HTTP y `Set-Cookie` | En un GET, `StartSession` guarda además la URL actual (`_previous.url`), adonde vuelven los errores de validación |

Comprobado en la traza (petición "Inicio (Ana)"):
```text
[n0] select * from sessions where id = ? limit 1                 -- paso 3
[n0] select * from users where id = ? limit 1   ← 2              -- paso 5 (auth)
[n0] select count(*) as aggregate from productos where activo = ? and stock_actual < stock_minimo   ← true   -- paso 10 (InicioController)
[n0] update sessions set payload = ?, last_activity = ?, user_id = ?, ip_address = ?, user_agent = ? where id = ?   -- paso 12
→ HTTP 200
```

---

## 1. Lista de escenarios

| SEQ | Qué muestra | UC | SQL confirmado con `DB::listen` |
|---|---|---|---|
| SEQ-00 | Patrón común de toda petición (sesión, CSRF, auth, binding, activo, rol, validación) | todos | Sí |
| SEQ-01 | Iniciar sesión con éxito | UC-01 | Sí |
| SEQ-02 | Intentos fallidos y bloqueo del login | UC-01, UC-02 | Sí |
| SEQ-03 | Iniciar sesión con una cuenta desactivada | UC-01 (E4) | Sí |
| SEQ-04 | Registrar una entrada de stock | UC-16 | **Sí (obligatorio)** |
| SEQ-05 | Registrar una salida que deja el stock bajo el mínimo (alerta) | UC-17, UC-18, UC-19 | **Sí (obligatorio)** |
| SEQ-06 | Registrar una salida con stock insuficiente (ROLLBACK) | UC-17, UC-18 (E2) | **Sí (obligatorio)** |
| SEQ-07 | Registrar un movimiento de un producto inactivo (ROLLBACK) | UC-16 (E2) | **Sí** |
| SEQ-08 | Crear un producto con stock inicial (transacción anidada) | UC-09 + UC-16 | **Sí (obligatorio)** |
| SEQ-09 | Desactivar un usuario que tiene la sesión abierta, y su expulsión | UC-23, UC-29 | Sí |
| SEQ-10 | Eliminar una categoría con productos (y sin productos) | UC-15 | Sí |
| SEQ-11 | Consultar el historial con filtros (válidos e inválidos) | UC-08 | Sí |
| SEQ-12 | Ver el Dashboard | UC-05 | Sí |
| SEQ-13 | Buscar según el rol | UC-06 | Sí |
| SEQ-14 | Generar el reporte de más movidos | UC-26 | Sí |
| SEQ-15 | Encargado en una ruta de administrador (403, o 404 si el id no existe) | UC-30 | Sí |
| SEQ-16 | Cerrar sesión | UC-03 | Sí |
| SEQ-17 | Editar un producto cuya unidad ya no se puede cambiar | UC-10 (RN-13) | Sí |
| SEQ-18 | El administrador intenta quitarse el rol o desactivarse (RN-07) | UC-22, UC-23 | Sí |

---

## 2. Escenarios

### SEQ-01 Iniciar sesión con éxito (UC-01)

**Datos:** Ana (`ana@granja.test`, encargada activa). Su navegador ya abrió `/login`, así que tiene una sesión de invitado.

**Participantes:** `Ana : Encargado` · `Navegador` · `Ruta login.entrar` · `web` · `guest` · `AuthController` · `RateLimiter` · `Auth` (`SessionGuard`) · `BD`

| # | Desde → Hacia | Mensaje | Respuesta y notas |
|---|---|---|---|
| 1 | Ana → Navegador | Escribe correo y contraseña; pulsa "Entrar" | El botón muestra "Entrando…" |
| 2 | Navegador → Ruta | `POST /login {_token, email: "ana@granja.test", password}` | — |
| 3 | `web` → BD | `select * from sessions …` (sesión de invitado) | Existe |
| 4 | `web` | Validar CSRF | Correcto |
| 5 | Ruta → `guest` | ¿Hay usuario autenticado? | No → continúa. **alt** [sí] → 302 → `/home` (fin) |
| 6 | Ruta → `AuthController` | `login(request)` | — |
| 7 | `AuthController` | `$request->validate([email: required\|email, password: required])` | **alt** [falla] → 302 a la URL anterior con errores (V-10); fin |
| 8 | `AuthController` | `clave = Str::transliterate(lower(email) . '\|' . ip)` | — |
| 9 | `AuthController` → `RateLimiter` → BD | `tooManyAttempts(clave, 3)` → `select * from cache where key in (?)` | `false`. **alt** [`true`] → SEQ-02 (bloqueo) |
| 10 | `AuthController` → `Auth` → BD | `Auth::validate(credenciales)` → `select * from users where email = ? limit 1`; `Hash::check` | `true`. **alt** [`false`] → SEQ-02 (intento fallido) |
| 11 | `AuthController` → `Auth` | `getLastAttempted()` | `usuario` (sin consulta) |
| 12 | `AuthController` | ¿`usuario.activo`? | `true`. **alt** [`false`] → SEQ-03 |
| 13 | `AuthController` → `RateLimiter` → BD | `clear(clave)` → `delete from cache where key in (?, ?)` (contador y su clave auxiliar) y `delete from cache where key in (?, ?)` (temporizador `:timer` y su clave auxiliar) | — |
| 14 | `AuthController` → `Auth` → BD | `Auth::login(usuario)` → guarda el id en la sesión y `regenerate(true)` → `delete from sessions where id = ?` | Se destruye la sesión de invitado (protege contra la fijación de sesión) (`SessionGuard::updateSession`, `vendor/…/Auth/SessionGuard.php:594-599`) |
| 15 | `AuthController` | `$request->session()->regenerate()` | Otro id de sesión, sin consulta |
| 16 | `AuthController` → Ruta | `redirect()->intended(route('home'))` | 302 |
| 17 | `web` → BD | Guarda: `select * from sessions where id = ?` (id nuevo, no existe) y `insert into sessions (…, user_id = 2, …)` | — |
| 18 | Ruta → Navegador | **302 → `/home`** (o la URL protegida que se había pedido) | Cookie con la sesión nueva |
| 19 | Navegador → Ruta `home` | `GET /home` | `ref SEQ-00` + `InicioController` (UC-04) |

**Fragmentos:** `alt` en los pasos 5, 7, 9, 10 y 12 (condiciones de la tabla).
**Transacción:** ninguna en el dominio.

**SQL real** (traza "Login correcto (Ana)"):
```text
[n0] select * from sessions where id = ? limit 1
[n0] select * from cache where key in (?)                         -- RateLimiter::tooManyAttempts → attempts()
[n0] select * from users where email = ? limit 1   ← 'ana@granja.test'
[n0] delete from cache where key in (?, ?)                        -- RateLimiter::clear: contador
[n0] delete from cache where key in (?, ?)                        -- RateLimiter::clear: temporizador
[n0] delete from sessions where id = ?                            -- Auth::login → regenerate(true)
[n0] select * from sessions where id = ? limit 1                  -- al guardar la sesión nueva
[n0] insert into sessions (payload, last_activity, user_id, ip_address, user_agent, id) values (?, ?, ?, ?, ?, ?)   ← …, 2, …
→ HTTP 302 → /home
```

**Pruebas:** `AutenticacionTest::test_inicia_sesion_y_va_a_home`, `test_con_sesion_iniciada_el_login_redirige_a_home`, `test_los_mensajes_de_validacion_salen_en_espanol`.

### SEQ-02 Intentos fallidos y bloqueo del login (UC-01 + UC-02)

**Datos:** Carlos (`carlos@granja.test`) se equivoca 3 veces y después escribe la contraseña correcta. Todo ocurre en menos de 60 s, desde la misma IP.

**Participantes:** `Carlos : Encargado` · `Navegador` · `Ruta login.entrar` · `web` · `AuthController` · `RateLimiter` · `Auth` · `BD`

**`loop` [intentos 1 a 3, contraseña incorrecta]**

| # | Desde → Hacia | Mensaje | Respuesta y notas |
|---|---|---|---|
| 1 | Navegador → Ruta | `POST /login {email, password: "incorrecta"}` | — |
| 2 | `web` → BD | `select * from sessions …` | — |
| 3 | `AuthController` → `RateLimiter` → BD | `tooManyAttempts(clave, 3)` → `select * from cache where key in (?)` | `false` (0, 1 o 2 intentos) |
| 4 | `AuthController` → `Auth` → BD | `validate()` → `select * from users where email = ? limit 1` | `false` (la contraseña no coincide) |
| 5 | `AuthController` → `RateLimiter` | `hit(clave, 60)` | — |
| 5.1 | `RateLimiter` → BD | `add(clave:timer, vence, 60)` → `select * from cache where key in (?)` | **opt** [no existe: primer intento] → `insert ignore into cache (key, value, expiration)` con la hora en que vence (+60 s) |
| 5.2 | `RateLimiter` → BD | `add(clave, 0, 60)` → `select * from cache where key in (?)` | **opt** [primer intento] → `insert ignore into cache …` (`'i:0;'`) |
| 5.3 | `RateLimiter` → BD | `increment(clave)`: **transacción propia del caché** con `select * from cache where key = ? limit 1 [for update]` y `update cache set value = ? where key = ?` | El contador queda en 1, 2 o 3 |
| 6 | `AuthController` → Navegador | `redirect()->route('login')->withErrors(email: "Estas credenciales no coinciden con nuestros registros.")->onlyInput('email')` | **302 → `/login`** |
| 7 | `web` → BD | Guarda la sesión (`update sessions …`; en el primer intento de un navegador nuevo, `select` + `insert`) | — |

**4.º intento (contraseña correcta, pero bloqueado)**

| # | Desde → Hacia | Mensaje | Respuesta y notas |
|---|---|---|---|
| 8 | Navegador → Ruta | `POST /login {email, password: correcta}` | — |
| 9 | `web` → BD | `select * from sessions …` | — |
| 10 | `AuthController` → `RateLimiter` → BD | `tooManyAttempts(clave, 3)` → `select … cache` (intentos = 3) y `select … cache` (¿existe `:timer`?) | `true` |
| 11 | `AuthController` → `RateLimiter` → BD | `availableIn(clave)` → `select … cache` (lee `:timer`) | 59 segundos |
| 12 | `AuthController` → Navegador | `redirect()->route('login')->withErrors(email: "Demasiados intentos de acceso. Por favor intente nuevamente en 59 segundos.")` | **302 → `/login`**. **No** se consulta `users` ni se comprueba la contraseña |
| 13 | `web` → BD | `update sessions …` | — |

**Fragmentos:**
- `loop` (3 veces).
- `opt` en 5.1 y 5.2 (solo en el primer intento).
- `alt` del paso 10: [bloqueado] (pasos 11 y 12) / [no bloqueado] (sigue como SEQ-01).

**Transacción:** solo la interna del caché en 5.3. No es del dominio, pero bloquea la fila del contador (`DatabaseStore::incrementOrDecrement`, `vendor/…/Cache/DatabaseStore.php:277`).

**SQL real** (trazas "intento 1", "intento 3" y "4.º intento"):
```text
-- intento 1
[n0] select * from sessions where id = ? limit 1
[n0] select * from cache where key in (?)                         -- attempts
[n0] select * from users where email = ? limit 1   ← 'carlos@granja.test'
[n0] select * from cache where key in (?)                         -- add(timer): ¿existe?
[n0] insert or ignore into cache (key, value, expiration) values (?, ?, ?)   [MySQL: insert ignore]
[n0] select * from cache where key in (?)                         -- add(contador): ¿existe?
[n0] insert or ignore into cache (key, value, expiration) values (?, ?, ?)   ← …, 'i:0;', …
>>> INICIA TRANSACCIÓN (nivel 1)                                   -- increment del caché
[n1] select * from cache where key = ? limit 1                    [MySQL: … for update]
[n1] update cache set value = ? where key = ?   ← 'i:1;'
<<< COMMIT (queda nivel 0)
[n0] select * from sessions where id = ? limit 1
[n0] insert into sessions (…) values (…)
→ HTTP 302 → /login
-- intento 3 (igual, sin los insert; el contador pasa a 'i:3;')
-- 4.º intento
[n0] select * from sessions where id = ? limit 1
[n0] select * from cache where key in (?)                         -- attempts = 3
[n0] select * from cache where key in (?)                         -- has(timer)
[n0] select * from cache where key in (?)                         -- availableIn
[n0] update sessions set … where id = ?
→ HTTP 302 → /login   errores: email "Demasiados intentos de acceso. Por favor intente nuevamente en 59 segundos."
```

**Pruebas:** `AutenticacionTest::test_tras_tres_intentos_fallidos_se_bloquea_aunque_la_contrasena_sea_correcta`, `test_el_bloqueo_vence_despues_de_un_minuto`, `test_el_bloqueo_es_por_correo_e_ip`, `test_un_inicio_de_sesion_correcto_reinicia_el_contador`.

### SEQ-03 Iniciar sesión con una cuenta desactivada (UC-01, E4)

**Datos:** Ana ya fue desactivada (SEQ-09) y escribe la contraseña correcta.

| # | Desde → Hacia | Mensaje | Respuesta y notas |
|---|---|---|---|
| 1 | Navegador → Ruta `login.entrar` | `POST /login {email: ana@…, password: correcta}` | — |
| 2 | `web` → BD | `select * from sessions …` | — |
| 3 | `AuthController` → `RateLimiter` → BD | `tooManyAttempts` → `select … cache` | `false` |
| 4 | `AuthController` → `Auth` → BD | `validate()` → `select * from users where email = ? limit 1` | `true` (la contraseña es correcta) |
| 5 | `AuthController` → `Auth` | `getLastAttempted()` | Ana (`activo = false`) |
| 6 | `AuthController` → Navegador | `redirect()->route('login')->withErrors(email: "Tu cuenta está desactivada. Habla con un administrador.")->onlyInput('email')` | **302 → `/login`**. **No** hay `hit` (no suma intento) ni `clear`, y **no** se inicia sesión |
| 7 | `web` → BD | `update sessions …` | — |

**SQL real:** traza "Login de Ana con la cuenta desactivada". Son las consultas de los pasos 2, 3, 4 y 7, en ese orden.
**Pruebas:** `AutenticacionTest::test_un_usuario_inactivo_no_puede_iniciar_sesion`.

### SEQ-04 Registrar una entrada de stock (UC-16)

**Datos:** Ana (encargada) registra una entrada de 20,5 kg de Maíz amarillo (#1: 100 kg, mínimo 50) desde la hoja "Nuevo movimiento" del historial.

**Participantes:** `Ana : Encargado` · `Navegador` · `Ruta movimientos.store` · `web` · `auth` · `activo` · `MovimientoRequest` · `MovimientoController` · `Producto` · `MovimientoInventario` · `BD`

| # | Desde → Hacia | Mensaje | Respuesta y notas |
|---|---|---|---|
| 1 | Ana → Navegador | Elige "Entrada" y "Maíz amarillo", escribe `20,5` y pulsa "Registrar" | El JS muestra "Quedarán 120,5 kilogramos" y el botón pasa a "Registrando…" |
| 2 | Navegador → Ruta | `POST /movimientos {_token, tipo: "entrada", producto_id: 1, cantidad: "20,5"}` | — |
| 3 | Ruta → `web` → BD | `ref SEQ-00` pasos 3 y 4: `select * from sessions …`; CSRF correcto | — |
| 4 | `web` → `auth` → BD | `select * from users where id = ?` (2) | Ana |
| 5 | → `activo` | `Ana.activo` | `true` |
| 6 | Ruta → `MovimientoRequest` | `prepareForValidation()`: `cantidad` "20,5" → "20.5" | — |
| 7 | `MovimientoRequest` → BD | Reglas: `tipo ∈ {entrada, salida}`; `producto_id exists` → `select count(*) … from productos where id = ?` (1); `cantidad numeric\|gt:0\|max\|decimal:0,2` | Correcto. **alt** [falla] → 302 → `/movimientos/create` con errores (ver la nota) |
| 8 | Ruta → `MovimientoController` | `store(request)` | — |
| 9 | `MovimientoController` → `Producto` → BD | `Producto::findOrFail(1)` → `select * from productos where productos.id = ? limit 1` | `$producto` |
| 10 | `MovimientoController` → `MovimientoInventario` | `registrar($producto, 'entrada', '20.5', $Ana)` «static» | — |
| 11 | `MovimientoInventario` | `in_array($tipo, ['entrada', 'salida'])` | Correcto (si no, `InvalidArgumentException`) |
| 12 | `MovimientoInventario` → BD | `DB::transaction(…)` → **START TRANSACTION** | **Inicia la transacción (nivel 1)** |
| 13 | `MovimientoInventario` → `Producto` → BD | `Producto::lockForUpdate()->findOrFail(1)` → `select * from productos where productos.id = ? limit 1 for update` | **🔒 Bloqueo de la fila** `productos.id = 1` hasta el COMMIT |
| 14 | `MovimientoInventario` | ¿`producto.activo`? | `true`. **alt** [`false`] → SEQ-07 |
| 15 | `MovimientoInventario` | ¿`cantidad > 0`? | `true` |
| 16 | `MovimientoInventario` → `Producto` → BD | `$producto->increment('stock_actual', '20.5')` → `update productos set stock_actual = stock_actual + 20.5, updated_at = ? where id = ?` | Primero el stock y después el movimiento: si el `insert` falla, el ROLLBACK deshace también el stock (`registrar:92-93`) |
| 17 | `MovimientoInventario` → BD | `self::create([producto_id: 1, usuario_id: 2, tipo: 'entrada', cantidad: '20.5', fecha: now()])` → `insert into movimientos_inventario …` | **alt** [el insert falla] → ROLLBACK → 500 (`MovimientoTest::test_rn04_si_falla_guardar_el_movimiento_el_stock_no_cambia`) |
| 18 | `MovimientoInventario` → BD | Fin del closure → **COMMIT** | **Termina la transacción; se libera el bloqueo** |
| 19 | `MovimientoInventario` → `MovimientoController` | `return $movimiento` | — |
| 20 | `MovimientoController` → `Producto` → BD | `$producto->refresh()` → `select * from productos where id = ? limit 1` | `stock_actual = 120.50` |
| 21 | `MovimientoController` | `volver(null)` → destino `movimientos.index`. Mensaje con `UnidadMedida::cantidad()` | — |
| 22 | `MovimientoController` | **opt** [`$producto->enStockBajo()`] → `with('aviso', …)` (UC-19) | 120,5 ≥ 50: **no** ocurre (ver SEQ-05) |
| 23 | `MovimientoController` → Ruta | `redirect()->route('movimientos.index')->with('success', "Entrada de 20,5 kilogramos de «Maíz amarillo» registrada. Stock: 120,5 kilogramos.")` | 302 |
| 24 | `web` → BD | `update sessions …` (guarda el flash) | — |
| 25 | Ruta → Navegador | **302 → `/movimientos`** | El navegador pide el historial y lo muestra con el mensaje (SEQ-11) |

**Fragmentos**
- `alt` [validación de forma] en el paso 7. Traza "Movimiento con datos inválidos": `select count(*) … productos where id = ?` (999), luego `update sessions` y 302 → `/movimientos/create` con 3 errores. **No** se abre transacción.
- `alt` [producto inactivo] en el paso 14 (SEQ-07).
- `opt` [stock bajo] en el paso 22.

**Transacción y bloqueo:** empieza en el paso 12, bloquea en el paso 13 y termina en el paso 18. Solo en esa parte hay escrituras de dominio.

**SQL real, en orden** (traza "Entrada 20,5 kg de Maíz (Ana)"):
```text
[n0] select * from sessions where id = ? limit 1
[n0] select * from users where id = ? limit 1   ← 2                                  -- auth
[n0] select count(*) as aggregate from productos where id = ?   ← 1                  -- MovimientoRequest: exists
[n0] select * from productos where productos.id = ? limit 1   ← 1                    -- controlador: findOrFail
>>> INICIA TRANSACCIÓN (nivel 1)                                                       [MySQL: START TRANSACTION]
[n1] select * from productos where productos.id = ? limit 1   ← 1                    [MySQL: … limit 1 for update]  🔒
[n1] update productos set stock_actual = stock_actual + 20.5, updated_at = ? where id = ?   ← '2026-10-12 16:46:35', 1
[n1] insert into movimientos_inventario (producto_id, usuario_id, tipo, cantidad, fecha, updated_at, created_at) values (?, ?, ?, ?, ?, ?, ?)   ← 1, 2, 'entrada', '20.5', '2026-10-12 16:46:35', …
<<< COMMIT (queda nivel 0)
[n0] select * from productos where id = ? limit 1   ← 1                              -- refresh()
[n0] update sessions set payload = ?, last_activity = ?, user_id = ?, ip_address = ?, user_agent = ? where id = ?
→ HTTP 302 → /movimientos   flash success: "Entrada de 20,5 kilogramos de «Maíz amarillo» registrada. Stock: 120,5 kilogramos."
```

**Nota sobre `increment`:** la cantidad (`+ 20.5`) va **escrita en el SQL**, no como parámetro (`?`). Así lo hace el `increment()` de Laravel con valores numéricos.

**Pruebas:** `MovimientoTest::test_una_entrada_suma_al_stock_y_queda_en_el_historial`, `test_rn04_en_mysql_la_fila_del_producto_se_bloquea_con_for_update`, `test_rn04_si_falla_guardar_el_movimiento_el_stock_no_cambia`.

### SEQ-05 Registrar una salida que deja el stock bajo el mínimo (UC-17 + UC-18 + UC-19)

**Datos:** Ana saca 80 kg de Maíz (120,5 kg; mínimo 50). La hoja se abrió desde Productos (`volver=productos`).

Es igual que SEQ-04, con estas diferencias:

| # | Desde → Hacia | Mensaje | Respuesta y notas |
|---|---|---|---|
| 2 | Navegador → Ruta | `POST /movimientos {_token, tipo: "salida", producto_id: 1, cantidad: "80", volver: "productos"}` | — |
| 15b | `MovimientoInventario` | **`<<include>>` UC-18:** ¿`80 > stock_actual (120.50)`? | No → continúa. **alt** [sí] → SEQ-06 |
| 16 | `MovimientoInventario` → BD | `$producto->decrement('stock_actual', '80')` → `update productos set stock_actual = stock_actual - 80, …` | — |
| 21 | `MovimientoController` | `volver('productos')` → destino `productos.index` | Solo se aceptan claves de `VOLVER` (INV-12) |
| 22 | `MovimientoController` | **opt** [`$producto->enStockBajo()`: 40,5 < 50] → `with('aviso', "«Maíz amarillo» quedó en stock bajo: 40,5 kilogramos (mínimo 50 kilogramos).")` | **Ocurre: es UC-19.** Se evalúa en memoria sobre el producto ya recargado, sin otra consulta |
| 25 | Ruta → Navegador | **302 → `/productos`** con `success` y `aviso` | El listado muestra los dos mensajes (`role="alert"`) |

**Transacción y bloqueo:** igual que SEQ-04. La validación de RN-01 (15b) ocurre **dentro** de la transacción y **con la fila bloqueada**: si dos personas sacan a la vez, la segunda espera y valida contra el stock ya actualizado.

**SQL real, en orden** (traza "Salida 80 kg de Maíz: queda bajo el mínimo"):
```text
[n0] select * from sessions where id = ? limit 1
[n0] select * from users where id = ? limit 1   ← 2
[n0] select count(*) as aggregate from productos where id = ?   ← 1
[n0] select * from productos where productos.id = ? limit 1   ← 1
>>> INICIA TRANSACCIÓN (nivel 1)
[n1] select * from productos where productos.id = ? limit 1   ← 1                    [MySQL: for update]  🔒
[n1] update productos set stock_actual = stock_actual - 80, updated_at = ? where id = ?   ← …, 1
[n1] insert into movimientos_inventario (producto_id, usuario_id, tipo, cantidad, fecha, updated_at, created_at) values (…)   ← 1, 2, 'salida', '80', …
<<< COMMIT (queda nivel 0)
[n0] select * from productos where id = ? limit 1   ← 1
[n0] update sessions set … where id = ?
→ HTTP 302 → /productos
  flash success: "Salida de 80 kilogramos de «Maíz amarillo» registrada. Stock: 40,5 kilogramos."
  flash aviso:   "«Maíz amarillo» quedó en stock bajo: 40,5 kilogramos (mínimo 50 kilogramos)."
```

**Pruebas:** `MovimientoTest::test_una_salida_que_deja_el_stock_bajo_el_minimo_muestra_la_alerta`, `test_sin_quedar_bajo_el_minimo_no_hay_alerta`, `test_desde_productos_la_hoja_llega_con_el_producto_elegido_y_vuelve_al_listado`.

### SEQ-06 Registrar una salida con stock insuficiente (UC-17 + UC-18, E2)

**Datos:** Ana intenta sacar 500 kg de Maíz (hay 40,5 kg).

| # | Desde → Hacia | Mensaje | Respuesta y notas |
|---|---|---|---|
| 1 | Ana → Navegador | Salida de `500` | El JS avisa "No alcanza: hay 40,5 kilogramos." Es solo una ayuda: igual deja enviar |
| 2 | Navegador → Ruta | `POST /movimientos {tipo: "salida", producto_id: 1, cantidad: "500"}` | — |
| 3–9 | (igual que SEQ-04) | Sesión, auth, activo, `MovimientoRequest` (correcto: 500 es un número válido), `findOrFail` | — |
| 10 | `MovimientoController` → `MovimientoInventario` | `registrar($producto, 'salida', '500', $Ana)` | — |
| 12 | `MovimientoInventario` → BD | START TRANSACTION | **Inicia la transacción** |
| 13 | `MovimientoInventario` → BD | `select * from productos where productos.id = ? limit 1 for update` | 🔒 bloqueo |
| 14–15 | `MovimientoInventario` | Activo correcto; cantidad > 0 correcto | — |
| 15b | `MovimientoInventario` | **UC-18:** ¿`500 > 40.50`? | **Sí** → `throw ValidationException(cantidad: "No hay stock suficiente: «Maíz amarillo» tiene 40,5 kilogramos.")` |
| 16' | `DB::transaction` → BD | Atrapa la excepción → **ROLLBACK** → la vuelve a lanzar | **Termina la transacción sin cambios; se libera el bloqueo** |
| 17' | Manejador de excepciones de Laravel | `ValidationException` → `redirect(url()->previous())->withInput()->withErrors()` | 302 a la hoja (`/movimientos/create`, desde el Referer o `_previous.url`) |
| 18' | `web` → BD | `update sessions …` (errores y `old`) | — |
| 19' | Ruta → Navegador | **302 → `/movimientos/create`** | La hoja vuelve con el error bajo "Cantidad" y lo escrito |

**Fragmentos:** `alt` [stock insuficiente] desde 15b (rama 16' a 19') frente a [alcanza] (SEQ-05). El controlador **no** llega a `refresh()` ni arma el mensaje flash: la excepción corta el método `store()`.

**SQL real, en orden** (traza "Salida 500 kg de Maíz: stock insuficiente"):
```text
[n0] select * from sessions where id = ? limit 1
[n0] select * from users where id = ? limit 1   ← 2
[n0] select count(*) as aggregate from productos where id = ?   ← 1
[n0] select * from productos where productos.id = ? limit 1   ← 1
>>> INICIA TRANSACCIÓN (nivel 1)
[n1] select * from productos where productos.id = ? limit 1   ← 1                    [MySQL: for update]  🔒
<<< ROLLBACK (queda nivel 0)                                                          -- no hubo update ni insert
[n0] update sessions set … where id = ?
→ HTTP 302 → /movimientos/create   errores: cantidad "No hay stock suficiente: «Maíz amarillo» tiene 40,5 kilogramos."
```

**Pruebas:** `MovimientoTest::test_rn01_no_se_registra_una_salida_mayor_al_stock`, `test_rn01_tambien_desde_el_modelo`, `test_rn04_el_stock_siempre_es_la_suma_del_historial`.

### SEQ-07 Registrar un movimiento de un producto inactivo (UC-16, E2; RN-10)

**Datos:** Soya (#2) fue desactivada (UC-11). Ana envía una entrada de 5 desde una pantalla desactualizada. La hoja actual ya no ofrece productos inactivos.

Es igual que SEQ-06, salvo el paso 14:

| # | Desde → Hacia | Mensaje | Respuesta y notas |
|---|---|---|---|
| 14 | `MovimientoInventario` | ¿`producto.activo`? (leído con la fila bloqueada) | `false` → `throw ValidationException(producto_id: "«Soya» está inactivo y no admite movimientos.")` |
| 16' | `DB::transaction` → BD | **ROLLBACK** | Sin cambios |
| 19' | Ruta → Navegador | **302 → `/movimientos/create`** con el error en "Producto" | — |

**SQL real** (traza "Entrada a un producto inactivo (Ana)"):
```text
[n0] select * from sessions where id = ? limit 1
[n0] select * from users where id = ? limit 1   ← 2
[n0] select count(*) as aggregate from productos where id = ?   ← 2                  -- la regla exists no mira "activo"
[n0] select * from productos where productos.id = ? limit 1   ← 2
>>> INICIA TRANSACCIÓN (nivel 1)
[n1] select * from productos where productos.id = ? limit 1   ← 2                    [MySQL: for update]
<<< ROLLBACK (queda nivel 0)
[n0] update sessions set … where id = ?
→ HTTP 302 → /movimientos/create   errores: producto_id "«Soya» está inactivo y no admite movimientos."
```

**Pruebas:** `MovimientoTest::test_un_producto_inactivo_no_admite_movimientos`, `test_la_hoja_solo_ofrece_productos_activos`.

### SEQ-08 Crear un producto con stock inicial (UC-09 + UC-16 por `<<extend>>`)

**Datos:** Laura (administradora) crea "Sorgo": categoría Alimento (#1), unidad bultos, mínimo 5, stock inicial "12,5".

**Participantes:** `Laura : Administrador` · `Navegador` · `Ruta productos.store` · `web` · `auth` · `activo` · `role:admin` · `ProductoRequest` · `ProductoController` · `Producto` · `MovimientoInventario` · `BD`

| # | Desde → Hacia | Mensaje | Respuesta y notas |
|---|---|---|---|
| 1 | Laura → Navegador | Completa la hoja "Nuevo producto" y pulsa "Guardar" | El botón muestra "Guardando…" |
| 2 | Navegador → Ruta | `POST /productos {_token, nombre: "Sorgo", categoria_id: 1, unidad_medida: "bultos", stock_minimo: "5", stock_inicial: "12,5"}` | — |
| 3 | `web` → BD | `select * from sessions …`; CSRF correcto | — |
| 4 | `auth` → BD | `select * from users where id = ?` (1) | Laura |
| 5 | `activo`, `role:admin` | `activo = true`, `rol = admin` | Correcto. **alt** [encargado] → 403 (SEQ-15) |
| 6 | Ruta → `ProductoRequest` | `prepareForValidation()`: nombre en una línea; "5" → "5"; "12,5" → "12.5" | — |
| 7 | `ProductoRequest` → BD | `unique(productos.nombre)` → `select count(*) … from productos where nombre = ?` ("Sorgo") | 0 |
| 8 | `ProductoRequest` → BD | `exists(categorias.id)` → `select count(*) … from categorias where id = ?` (1) | 1. **alt** [falla 7 u 8 u otra regla] → 302 → `/productos/create` con errores (traza "Crear producto con nombre repetido": solo los pasos 7 y 8 y `update sessions`) |
| 9 | Ruta → `ProductoController` | `store(request)` | — |
| 10 | `ProductoController` → BD | `DB::transaction(…)` → **START TRANSACTION** | **Inicia la transacción principal (nivel 1)** |
| 11 | `ProductoController` → `Producto` → BD | `Producto::create(Arr::except($datos, 'stock_inicial'))` → `insert into productos (nombre, categoria_id, unidad_medida, stock_minimo, updated_at, created_at) …` | Producto #3. `stock_actual` toma el valor por defecto 0 y `activo` el valor por defecto 1 |
| 12 | `ProductoController` | **opt** [`stock_inicial > 0`]: 12,5 > 0 | Ocurre: es el `<<extend>>` de UC-16 |
| 13 | `ProductoController` → `MovimientoInventario` | `registrar($producto, 'entrada', '12.5', $Laura)` | — |
| 14 | `MovimientoInventario` → BD | `DB::transaction(…)` anidada → **SAVEPOINT trans2** | **Inicia la transacción anidada (nivel 2)** |
| 15 | `MovimientoInventario` → BD | `select * from productos where productos.id = ? limit 1 for update` (3) | 🔒 Bloqueo de la fila del producto nuevo |
| 16 | `MovimientoInventario` | Activo correcto; cantidad > 0 correcto (entrada: no valida RN-01) | — |
| 17 | `MovimientoInventario` → BD | `update productos set stock_actual = stock_actual + 12.5, …` | — |
| 18 | `MovimientoInventario` → BD | `insert into movimientos_inventario (…)` (3, 1, 'entrada', '12.5', …) | — |
| 19 | `MovimientoInventario` | Fin del closure anidado | **Termina el nivel 2:** no hay SQL (Laravel no ejecuta `RELEASE SAVEPOINT`; solo baja el contador) |
| 20 | `ProductoController` → BD | Fin del closure principal → **COMMIT** | **Termina la transacción principal; se confirman el producto y el movimiento y se libera el bloqueo** |
| 21 | `ProductoController` → Ruta | `redirect()->route('productos.index', filtros)->with('success', "Producto «Sorgo» creado.")` | **No** se evalúa el stock bajo (no hay `aviso` al crear) |
| 22 | `web` → BD | `update sessions …` | — |
| 23 | Ruta → Navegador | **302 → `/productos`** | — |

**Fragmentos**
- `opt` [`stock_inicial > 0`] cubre los pasos 13 a 19. Sin stock inicial (traza "Crear producto sin stock inicial"): START TRANSACTION, `insert into productos`, COMMIT.
- `alt` [falla al guardar el movimiento] dentro del `opt`: `ROLLBACK TO SAVEPOINT trans2`, la excepción sube y el `DB::transaction` principal hace **ROLLBACK**. El **producto tampoco queda creado** y la respuesta es 500 (`ProductoTest::test_si_falla_el_movimiento_inicial_no_se_crea_el_producto`).

**Transacciones:**
- Principal: empieza en el paso 10 y termina en el 20.
- Anidada (savepoint): empieza en el 14 y termina en el 19.
- Bloqueo: en el paso 15, hasta el COMMIT del paso 20.

**SQL real, en orden** (traza "Crear producto con stock inicial 12,5"):
```text
[n0] select * from sessions where id = ? limit 1
[n0] select * from users where id = ? limit 1   ← 1                                  -- auth (Laura)
[n0] select count(*) as aggregate from productos where nombre = ?   ← 'Sorgo'       -- ProductoRequest: unique
[n0] select count(*) as aggregate from categorias where id = ?   ← 1                 -- ProductoRequest: exists
>>> INICIA TRANSACCIÓN (nivel 1)                                                        -- ProductoController::store   [MySQL: START TRANSACTION]
[n1] insert into productos (nombre, categoria_id, unidad_medida, stock_minimo, updated_at, created_at) values (?, ?, ?, ?, ?, ?)   ← 'Sorgo', 1, 'bultos', '5', …
>>> INICIA TRANSACCIÓN (nivel 2)                                                        -- registrar()   [MySQL: SAVEPOINT trans2]
[n2] select * from productos where productos.id = ? limit 1   ← 3                     [MySQL: for update]  🔒
[n2] update productos set stock_actual = stock_actual + 12.5, updated_at = ? where id = ?   ← …, 3
[n2] insert into movimientos_inventario (producto_id, usuario_id, tipo, cantidad, fecha, updated_at, created_at) values (…)   ← 3, 1, 'entrada', '12.5', …
<<< COMMIT (queda nivel 1)                                                              -- sin SQL: no hay RELEASE SAVEPOINT
<<< COMMIT (queda nivel 0)                                                              -- COMMIT real
[n0] update sessions set … where id = ?
→ HTTP 302 → /productos   flash success: "Producto «Sorgo» creado."
```

**Pruebas:** `ProductoTest::test_el_stock_inicial_entra_como_un_movimiento_de_entrada`, `test_crear_un_producto_sin_stock_inicial`, `test_si_falla_el_movimiento_inicial_no_se_crea_el_producto`, `test_validaciones_al_crear_en_espanol`.

### SEQ-09 Desactivar un usuario que tiene la sesión abierta (UC-23 + UC-29)

**Datos:** Laura (administradora) desactiva a Ana (#2). Ana tiene una sesión abierta en otro navegador.

**Participantes:** `Laura : Administrador` · `Navegador de Laura` · `Ruta usuarios.cambiar-estado` · `web` · `auth` · `activo` · `role:admin` · `UsuarioController` · `User` · `BD` · `Ana : Encargado` · `Navegador de Ana` · `Ruta home` · `UsuarioActivo`

**Parte A — la administradora desactiva la cuenta**

| # | Desde → Hacia | Mensaje | Respuesta y notas |
|---|---|---|---|
| 1 | Laura → Navegador de Laura | En la hoja "Editar usuario" de Ana, pulsa "Desactivar cuenta" | — |
| 2 | Navegador de Laura | `window.confirm("¿Desactivar a «Ana…»? No podrá iniciar sesión hasta que la actives de nuevo.")` | **alt** [cancela] → no se envía nada (fin) |
| 3 | Navegador → Ruta | `PATCH /usuarios/2/estado {_token, _method: PATCH, activo: "0"}` | — |
| 4 | `web` → BD | `select * from sessions …` (sesión **de Laura**) | — |
| 5 | `auth` → BD | `select * from users where id = ?` (1) | Laura |
| 6 | `web` (`SubstituteBindings`) → BD | `{usuario}` → `select * from users where id = ? limit 1` ('2') | Ana |
| 7 | `activo`, `role:admin` | Laura activa y administradora | Correcto |
| 8 | Ruta → `UsuarioController` | `cambiarEstado(request, Ana)` | — |
| 9 | `UsuarioController` | `$request->validate(['activo' => 'required\|boolean'])`; `$activo = false` | — |
| 10 | `UsuarioController` | ¿`!$activo && $usuario->is($request->user())`? | No (Ana no es Laura). **alt** [es su propia cuenta] → SEQ-18 |
| 11 | `UsuarioController` → `User` → BD | `$usuario->update(['activo' => false])` → `update users set activo = ?, updated_at = ? where id = ?` | La sesión de Ana **no se toca** |
| 12 | `UsuarioController` → Ruta | `redirect()->route('usuarios.index')->with('success', "Usuario «…» desactivado: ya no puede iniciar sesión y, si la tenía abierta, se cerrará en su siguiente acción.")` | — |
| 13 | `web` → BD | `update sessions …` (sesión de Laura) | — |
| 14 | Ruta → Navegador de Laura | **302 → `/usuarios`** | — |

**Parte B — siguiente petición de Ana (cualquier ruta protegida; aquí, `GET /home`)**

| # | Desde → Hacia | Mensaje | Respuesta y notas |
|---|---|---|---|
| 15 | Ana → Navegador de Ana | Clic en "Inicio" (u otra acción) | — |
| 16 | Navegador de Ana → Ruta `home` | `GET /home` con la cookie de **su** sesión | — |
| 17 | `web` → BD | `select * from sessions where id = ?` (la sesión de Ana sigue existiendo) | — |
| 18 | `auth` → BD | `select * from users where id = ? limit 1` (2) | Ana, con `activo = false` (se lee de la BD en cada petición) |
| 19 | → `activo` (`UsuarioActivo::handle`) | ¿`$request->user()->activo`? | `false` |
| 20 | `UsuarioActivo` → `Auth` | `Auth::logout()` | **opt** [`remember_token` no vacío] → `update users set remember_token = ? where id = ?` (ver la nota) |
| 21 | `UsuarioActivo` → BD | `$request->session()->invalidate()` → `delete from sessions where id = ?` | **Se borra la sesión de Ana** |
| 22 | `UsuarioActivo` | `$request->session()->regenerateToken()` | Token CSRF nuevo |
| 23 | `UsuarioActivo` → Ruta | `redirect()->route('login')->withErrors(['email' => 'Tu cuenta fue desactivada. Habla con un administrador.'])` | `InicioController` **no se ejecuta** |
| 24 | `web` → BD | `select * from sessions where id = ?` (id nuevo) e `insert into sessions (…, user_id = NULL, …)` | Sesión de invitado con el error |
| 25 | Ruta → Navegador de Ana | **302 → `/login`** | El login muestra el error junto al correo |

**Nota del paso 20:** en la traza aparece `update users set remember_token = ?` porque las cuentas del *factory* traen un `remember_token`. `SessionGuard::logout()` solo lo renueva si no está vacío (`vendor/…/Auth/SessionGuard.php:660-668`). Las cuentas creadas por la app (`UsuarioController::store`) o por el seeder lo tienen en `NULL`, porque la app no usa "recordarme". En producción **normalmente no ocurre**, por eso es `opt`.

**SQL real** (trazas "Admin desactiva a Ana…" y "Siguiente petición de Ana…"):
```text
-- Parte A
[n0] select * from sessions where id = ? limit 1                       -- sesión de Laura
[n0] select * from users where id = ? limit 1   ← 1                   -- auth
[n0] select * from users where id = ? limit 1   ← '2'                 -- route model binding {usuario}
[n0] update users set activo = ?, updated_at = ? where id = ?   ← false, …, 2
[n0] update sessions set … where id = ?
→ HTTP 302 → /usuarios   flash success: "Usuario «Ana María Pérez» desactivado: …"
-- Parte B
[n0] select * from sessions where id = ? limit 1                       -- sesión de Ana
[n0] select * from users where id = ? limit 1   ← 2                   -- auth: activo = 0
[n0] update users set remember_token = ? where id = ?   ← …, 2        -- opt (solo si tenía remember_token)
[n0] delete from sessions where id = ?                                 -- invalidate()
[n0] select * from sessions where id = ? limit 1                       -- sesión nueva de invitado
[n0] insert into sessions (payload, last_activity, user_id, ip_address, user_agent, id) values (…)   ← …, null, …
→ HTTP 302 → /login   errores: email "Tu cuenta fue desactivada. Habla con un administrador."
```

**Pruebas:** `UsuarioTest::test_desactivar_pide_confirmacion_y_expulsa_la_sesion_abierta`; `AutenticacionTest::test_un_usuario_desactivado_con_la_sesion_abierta_es_expulsado`; `MovimientoTest::test_una_cuenta_desactivada_no_puede_registrar`.

### SEQ-10 Eliminar una categoría con productos, y sin productos (UC-15)

**Datos:** Alimento (#1) tiene 4 productos. "Vacunas aviares" (#2) no tiene ninguno.

| # | Desde → Hacia | Mensaje | Respuesta y notas |
|---|---|---|---|
| 1 | Laura → Navegador | En Categorías, junto a una categoría… | **alt** [la categoría tiene productos]: el botón papelera está **deshabilitado** (`aria-disabled`, motivo "Tiene N productos") y no hay formulario. **No hay petición** (fin), salvo una pantalla desactualizada o una petición directa, que siguen en el paso 3 · [no tiene]: pulsa la papelera |
| 2 | Navegador | `window.confirm("¿Eliminar la categoría «X»?")` | **alt** [cancela] → fin |
| 3 | Navegador → Ruta `categorias.destroy` | `DELETE /categorias/{id} {_token, _method: DELETE}` | — |
| 4 | `web`, `auth` → BD | `select * from sessions …`; `select * from users where id = ?` | — |
| 5 | `SubstituteBindings` → BD | `{categoria}` → `select * from categorias where id = ? limit 1` | **alt** [no existe] → 404 |
| 6 | `activo`, `role:admin` | — | Correcto |
| 7 | Ruta → `CategoriaController` | `destroy($categoria)` | — |
| 8 | `CategoriaController` → `Categoria` → BD | `$categoria->productos()->count()` → `select count(*) … from productos where productos.categoria_id = ? and productos.categoria_id is not null` | N |
| 9 | `CategoriaController` | **alt** [N > 0] | `redirect()->route('categorias.index')->with('error', "No se puede eliminar «Alimento» porque tiene 4 productos. Primero cámbialos de categoría.")` → **302 → `/categorias`** |
| 10 | `CategoriaController` → `Categoria` → BD | **alt** [N = 0] → `$categoria->delete()` → `delete from categorias where id = ?` | `redirect()->route('categorias.index')->with('success', "Categoría «Vacunas aviares» eliminada.")` → **302 → `/categorias`** |
| 11 | `web` → BD | `update sessions …` | — |

**Transacción:** ninguna. El conteo y el borrado no van en una transacción. Si una carrera agrega un producto entre los pasos 8 y 10, la llave foránea (RESTRICT) rechaza el `DELETE` y la respuesta es 500 (V-06).

**SQL real** (trazas "Eliminar categoría CON productos" y "SIN productos"):
```text
-- con productos
[n0] select * from sessions where id = ? limit 1
[n0] select * from users where id = ? limit 1   ← 1
[n0] select * from categorias where id = ? limit 1   ← '1'
[n0] select count(*) as aggregate from productos where productos.categoria_id = ? and productos.categoria_id is not null   ← 1
[n0] update sessions set … where id = ?
→ HTTP 302 → /categorias   flash error: "No se puede eliminar «Alimento» porque tiene 4 productos. Primero cámbialos de categoría."
-- sin productos
[n0] select * from sessions where id = ? limit 1
[n0] select * from users where id = ? limit 1   ← 1
[n0] select * from categorias where id = ? limit 1   ← '2'
[n0] select count(*) as aggregate from productos where productos.categoria_id = ? and productos.categoria_id is not null   ← 2
[n0] delete from categorias where id = ?   ← 2
[n0] update sessions set … where id = ?
→ HTTP 302 → /categorias   flash success: "Categoría «Vacunas aviares» eliminada."
```

**Pruebas:** `CategoriaTest::test_rn03_no_se_elimina_una_categoria_con_productos`, `test_eliminar_una_categoria_sin_productos`, `test_con_productos_el_boton_eliminar_se_ve_deshabilitado_y_dice_cuantos_tiene`.

### SEQ-11 Consultar el historial con filtros (UC-08)

**Datos:** Ana filtra por producto Maíz (#1), tipo `salida`, desde 2026-10-05 hasta 2026-10-12.

| # | Desde → Hacia | Mensaje | Respuesta y notas |
|---|---|---|---|
| 1 | Ana → Navegador | En "Filtrar historial" elige producto, tipo y fechas; pulsa "Filtrar" | "Filtrando…" |
| 2 | Navegador → Ruta `movimientos.index` | `GET /movimientos?producto=1&tipo=salida&desde=2026-10-05&hasta=2026-10-12` | — |
| 3 | `ref SEQ-00` | Sesión y `auth` | — |
| 4 | Ruta → `FiltrosHistorialRequest` → BD | Reglas: `producto exists` → `select count(*) … from productos where id = ?` ('1'); `tipo` en la lista; fechas `Y-m-d`; `hasta ≥ desde` | **alt** [falla] → 302 → `/movimientos` (`$redirectRoute`, sin filtros) con el error; fin (ver abajo) |
| 5 | Ruta → `MovimientoController` | `index(request)` → `historial(request)` → `$request->filtros()` | Solo los filtros con valor |
| 6 | `MovimientoController` → `MovimientoInventario` → BD | `with(['producto', 'usuario'])->when(…)->latest('fecha')->latest('id')->paginate(20)` → (a) `select count(*) …` (total) | — |
| 7 | `MovimientoInventario` → BD | **opt** [total > 0] → (b) `select * from movimientos_inventario where … order by fecha desc, id desc limit 20 offset 0` | Si el total es 0, no se ejecuta |
| 8 | `MovimientoInventario` → BD | Carga anticipada: `select * from productos where productos.id in (…)` y `select * from users where users.id in (…)` | Sin N+1 (`HistorialTest::test_el_historial_no_hace_una_consulta_por_movimiento`) |
| 9 | `MovimientoController` → `Producto` → BD | `Producto::orderBy('nombre')->pluck('nombre', 'id')` → `select nombre, id from productos order by nombre asc` | Para el selector del filtro (incluye inactivos) |
| 10 | `MovimientoController` → Navegador | View `movimientos.index` (**200**) | Panel de filtros abierto, título "Historial de Maíz amarillo" |

**Fechas:** "desde" = `2026-10-05 00:00:00`; "hasta" se convierte en `< 2026-10-13 00:00:00`, para que el último día quede incluido. Hora de Bogotá (RN-21).

**SQL real** (traza "Historial con filtros (Ana)"):
```text
[n0] select * from sessions where id = ? limit 1
[n0] select * from users where id = ? limit 1   ← 2
[n0] select count(*) as aggregate from productos where id = ?   ← '1'                                  -- FiltrosHistorialRequest: exists
[n0] select count(*) as aggregate from movimientos_inventario where producto_id = ? and tipo = ? and fecha >= ? and fecha < ?   ← '1', 'salida', '2026-10-05 00:00:00', '2026-10-13 00:00:00'
[n0] select * from movimientos_inventario where producto_id = ? and tipo = ? and fecha >= ? and fecha < ? order by fecha desc, id desc limit 20 offset 0   ← (los mismos)
[n0] select * from productos where productos.id in (1)
[n0] select * from users where users.id in (2)
[n0] select nombre, id from productos order by nombre asc
[n0] update sessions set … where id = ?
→ HTTP 200
-- alt: filtro inválido (traza "Historial con filtro inválido (hasta < desde)")
[n0] select * from sessions where id = ? limit 1
[n0] select * from users where id = ? limit 1   ← 2
[n0] update sessions set … where id = ?
→ HTTP 302 → /movimientos   errores: hasta "«Hasta» no puede ser anterior a «Desde»."
```

**Pruebas:** `HistorialTest::test_filtra_por_producto_y_por_tipo`, `test_las_fechas_son_inclusivas_en_hora_de_bogota`, `test_filtros_invalidos_vuelven_al_historial_con_el_error`, `test_la_paginacion_conserva_los_filtros_y_hay_limpiar_filtros`.

### SEQ-12 Ver el Dashboard (UC-05)

| # | Desde → Hacia | Mensaje | Respuesta y notas |
|---|---|---|---|
| 1 | Usuario → Navegador | Ícono "Dashboard" de la barra | — |
| 2 | Navegador → Ruta `dashboard` | `GET /dashboard` | — |
| 3 | `ref SEQ-00` | Sesión, `auth`, `activo` | — |
| 4 | Ruta → `DashboardController` | `__invoke()` | — |
| 5 | `DashboardController` → `Producto` → BD | `Producto::with('categoria')->enAlerta()->get()` → `select * from productos where activo = ? and stock_actual < stock_minimo order by stock_minimo - stock_actual desc, nombre asc` | `$alertas` |
| 6 | `Producto` → BD | Carga anticipada: `select * from categorias where categorias.id in (…)` | — |
| 7 | `DashboardController` → `Producto` → BD | `Producto::where('activo', true)->count()` → `select count(*) … from productos where activo = ?` | "Productos activos" |
| 8 | `DashboardController` | `$alertas->count()` | "Activos en stock bajo" (en memoria, sin consulta) |
| 9 | `DashboardController` → `MovimientoInventario` → BD | `where('fecha', '>=', today())->where('fecha', '<', today()->addDay())->count()` | "Movimientos de hoy" (día de Bogotá) |
| 10 | `DashboardController` → Navegador | View `dashboard` (**200**) | **alt** [sin alertas] → "Sin alertas por ahora" |

**SQL real** (traza "Dashboard (Ana)"):
```text
[n0] select * from sessions where id = ? limit 1
[n0] select * from users where id = ? limit 1   ← 2
[n0] select * from productos where activo = ? and stock_actual < stock_minimo order by stock_minimo - stock_actual desc, nombre asc   ← true
[n0] select * from categorias where categorias.id in (1)
[n0] select count(*) as aggregate from productos where activo = ?   ← true
[n0] select count(*) as aggregate from movimientos_inventario where fecha >= ? and fecha < ?   ← '2026-10-12 00:00:00', '2026-10-13 00:00:00'
[n0] update sessions set … where id = ?
→ HTTP 200
```

**Pruebas:** `DashboardTest::test_los_indicadores_son_iguales_para_los_dos_roles`, `test_movimientos_de_hoy_usa_el_dia_calendario_de_bogota`, `test_el_dashboard_no_hace_una_consulta_por_producto`.

### SEQ-13 Buscar según el rol (UC-06)

| # | Desde → Hacia | Mensaje | Respuesta y notas |
|---|---|---|---|
| 1 | Usuario → Navegador | Escribe "ma" y pulsa Enter | — |
| 2 | Navegador → Ruta `search` | `GET /search?q=ma[&tipo=…]` | — |
| 3 | `ref SEQ-00` | Sesión, `auth`, `activo` | — |
| 4 | Ruta → `BusquedaController` | `__invoke(request)`: `q = trim(q)`; `permitidos = esAdmin() ? [productos, categorias, usuarios] : [productos]`; `tipo` solo si está en `permitidos` | — |
| 5 | `BusquedaController` | **alt** [`mb_strlen(q) < 2`] → no hay consultas; la vista dice "Escribe al menos 2 caracteres" (o "Escribe para buscar" si está vacío) | — |
| 6 | `BusquedaController` | **loop** [por cada grupo permitido (o solo `tipo`)] | — |
| 6.1 | → `Producto` → BD | Grupo productos: `count(*)` y `select … order by nombre limit 10` (por nombre **o** por nombre de su categoría: `exists (select … categorias …)`, `LIKE … escape '!'`); carga de categorías | Ambos roles |
| 6.2 | → `Categoria` → BD | **opt** [administrador]: `count(*)` y `select categorias.*, (select count(*) from productos …) as productos_count … limit 10` | Solo el administrador |
| 6.3 | → `User` → BD | **opt** [administrador]: `count(*)` y `select * from users where (name like ? or email like ?) order by name limit 10` | Solo el administrador |
| 7 | `BusquedaController` → Navegador | View `search` (**200**) con los grupos, los totales y "Ver todos" | — |

**SQL real**

Encargado (traza `Buscar "ma" (encargado)`):
```text
[n0] select * from sessions where id = ? limit 1
[n0] select * from users where id = ? limit 1   ← 2
[n0] select count(*) as aggregate from productos where (nombre like ? escape '!' or exists (select * from categorias where productos.categoria_id = categorias.id and nombre like ? escape '!'))   ← '%ma%', '%ma%'
[n0] select * from productos where (…mismo filtro…) order by nombre asc limit 10   ← '%ma%', '%ma%'
[n0] select * from categorias where categorias.id in (1)
[n0] update sessions set … where id = ?
→ HTTP 200
```

Administrador (traza `Buscar "ma" (admin)`): las mismas consultas de productos, y además:
```text
[n0] select count(*) as aggregate from categorias where nombre like ? escape '!'   ← '%ma%'
[n0] select categorias.*, (select count(*) from productos where categorias.id = productos.categoria_id) as productos_count from categorias where nombre like ? escape '!' order by nombre asc limit 10
[n0] select count(*) as aggregate from users where (name like ? escape '!' or email like ? escape '!')   ← '%ma%', '%ma%'
[n0] select * from users where (name like ? escape '!' or email like ? escape '!') order by name asc limit 10
```

Otras dos trazas:
- **Administrador con `tipo=usuarios`:** solo las 2 consultas de usuarios.
- **`q = "m"` (1 carácter):** ninguna consulta de búsqueda.

**Pruebas:** `BusquedaTest::test_el_encargado_solo_busca_productos_por_nombre_o_categoria`, `test_el_encargado_no_puede_pedir_usuarios_ni_categorias_con_el_filtro`, `test_el_admin_busca_productos_categorias_y_usuarios_agrupados`, `test_hacen_falta_al_menos_2_caracteres`, `test_el_porcentaje_y_el_guion_bajo_se_buscan_como_texto`, `test_maximo_10_por_grupo_y_ver_todos_lleva_al_listado_filtrado`.

### SEQ-14 Generar el reporte de más movidos (UC-26)

| # | Desde → Hacia | Mensaje | Respuesta y notas |
|---|---|---|---|
| 1 | Laura → Navegador | Reportes → "Más movidos" (o cambia el rango y pulsa "Generar") | — |
| 2 | Navegador → Ruta `reportes.mas-movidos` | `GET /reportes/mas-movidos[?desde=…&hasta=…]` | — |
| 3 | `ref SEQ-00` | Sesión, `auth`, `activo`, `role:admin` | **alt** [encargado] → 403 |
| 4 | Ruta → `RangoFechasRequest` | Reglas: `Y-m-d` y `hasta ≥ desde` (sin consultas) | **alt** [falla] → 302 → `/reportes/mas-movidos` (sin fechas) con el error |
| 5 | Ruta → `ReporteController` | `masMovidos(request)`: `desde = request.desde()` (por defecto, hoy − 29 días), `hasta = request.hasta()` (por defecto, hoy) | — |
| 6 | `ReporteController` → `Producto` → BD | Una consulta con subconsultas: `whereHas(movimientos en el rango)`, `withCount(movimientos en el rango)`, `withSum(entradas)`, `withSum(salidas)`, ordenada por `movimientos_count` desc y `nombre`, `limit 10` | Top 10 |
| 7 | `Producto` → BD | Carga anticipada de las categorías | — |
| 8 | `ReporteController` → Navegador | View `reportes.mas-movidos` (**200**) | **alt** [vacío] → "Sin movimientos en este rango" |
| 9 | **opt** Laura → Navegador | "Imprimir" → `window.print()` (UC-27) | **Sin petición** al servidor |

**SQL real** (traza "Reporte más movidos (sin fechas: últimos 30 días)"; hoy = 2026-10-12):
```text
[n0] select * from sessions where id = ? limit 1
[n0] select * from users where id = ? limit 1   ← 1
[n0] select productos.*,
       (select count(*) from movimientos_inventario where productos.id = movimientos_inventario.producto_id and fecha >= ? and fecha < ?) as movimientos_count,
       (select sum(movimientos_inventario.cantidad) from movimientos_inventario where productos.id = movimientos_inventario.producto_id and fecha >= ? and fecha < ? and tipo = ?) as entradas,
       (select sum(movimientos_inventario.cantidad) from movimientos_inventario where productos.id = movimientos_inventario.producto_id and fecha >= ? and fecha < ? and tipo = ?) as salidas
     from productos
     where exists (select * from movimientos_inventario where productos.id = movimientos_inventario.producto_id and fecha >= ? and fecha < ?)
     order by movimientos_count desc, nombre asc limit 10
     ← '2026-09-13 00:00:00', '2026-10-13 00:00:00' (en cada subconsulta), 'entrada', 'salida'
[n0] select * from categorias where categorias.id in (1)
[n0] update sessions set … where id = ?
→ HTTP 200
```

Con un rango inválido (traza correspondiente): solo sesión y `auth`, luego `update sessions` y **302 → `/reportes/mas-movidos`** con el error en "hasta".

**Pruebas:** `ReporteTest::test_mas_movidos_*` (7 pruebas), en particular `test_mas_movidos_no_hace_una_consulta_por_producto`.

### SEQ-15 Encargado en una ruta de administrador (UC-30; FC-3 y FC-5)

| # | Desde → Hacia | Mensaje | Respuesta y notas |
|---|---|---|---|
| 1 | Ana → Navegador | Escribe la URL a mano (la interfaz no le muestra esos enlaces) | — |
| 2 | Navegador → Ruta | `GET /usuarios`, o `GET /productos/{id}/edit` | — |
| 3 | `web`, `auth` → BD | `select * from sessions …`; `select * from users where id = ?` (2) | — |
| 4 | **opt** [la ruta tiene `{modelo}`] `SubstituteBindings` → BD | `select * from productos where id = ? limit 1` | **alt** [no existe] → **404** (fin) |
| 5 | → `activo` | — | Correcto |
| 6 | → `role:admin` (`VerificarRol`) | ¿`in_array('encargado', ['admin'])`? | No → `abort(403)` |
| 7 | Ruta → Navegador | **403** con la vista `errors/403` en español | El controlador no se ejecuta |

**SQL real:**
- **"Encargado pide /usuarios (403)":** `select sessions`, `select users`, `update sessions` → 403.
- **"…editar un producto que existe":** además `select * from productos where id = ? limit 1` ('1') → 403.
- **"…que NO existe":** igual, con ('999') → **404**.

**Pruebas:** `RutasTest::test_el_encargado_recibe_403_en_las_rutas_de_admin`; `PermisosTest::test_el_403_tiene_vista_propia_en_espanol`. El caso del 404 no tiene prueba (V-09).

### SEQ-16 Cerrar sesión (UC-03)

| # | Desde → Hacia | Mensaje | Respuesta y notas |
|---|---|---|---|
| 1 | Usuario → Navegador | Botón "Cerrar sesión" del encabezado | — |
| 2 | Navegador → Ruta `logout` | `POST /logout {_token}` | Ruta **sin** `auth` |
| 3 | `web` → BD | `select * from sessions …`; CSRF correcto | — |
| 4 | Ruta → `AuthController` | `logout(request)` | — |
| 5 | `AuthController` → `Auth` → BD | `Auth::logout()` → `user()` carga al usuario de la sesión (`select * from users where id = ?`) | **opt** [`remember_token` no vacío] → `update users set remember_token = ?` (ver SEQ-09, paso 20) |
| 6 | `AuthController` → BD | `session()->invalidate()` → `delete from sessions where id = ?` | — |
| 7 | `AuthController` | `session()->regenerateToken()` | — |
| 8 | `AuthController` → Ruta | `redirect()->route('login')` | — |
| 9 | `web` → BD | `select * from sessions` (id nuevo) e `insert into sessions (…, user_id = NULL, …)` | — |
| 10 | Ruta → Navegador | **302 → `/login`** | — |

**SQL real:** traza "Cerrar sesión (admin)". Son los mensajes 3, 5, 6 y 9, en ese orden.
**Pruebas:** `AutenticacionTest::test_cerrar_sesion`.

### SEQ-17 Editar un producto cuya unidad ya no se puede cambiar (UC-10; RN-13)

| # | Desde → Hacia | Mensaje | Respuesta y notas |
|---|---|---|---|
| 1 | Laura → Navegador | En la hoja "Editar producto" de Maíz pulsa "Guardar cambios" | La hoja muestra "Unidad fija…" y no tiene el selector de unidad. En esta traza se envía `unidad_medida = libras` a mano para mostrar la validación |
| 2 | Navegador → Ruta `productos.update` | `PUT /productos/1 {nombre, categoria_id: 1, unidad_medida: "libras", stock_minimo: "60"}` | — |
| 3 | `web`, `auth` → BD | Sesión; `select users` (1) | — |
| 4 | `SubstituteBindings` → BD | `select * from productos where id = ? limit 1` ('1') | — |
| 5 | `activo`, `role:admin` | — | Correcto |
| 6 | Ruta → `ProductoRequest` → BD | Al armar `rules()`: `reglasDeUnidad($producto)` → `$producto->movimientos()->exists()` → `select exists(select * from movimientos_inventario where … producto_id = ? …) as exists` | `true` → `unidad_medida: sometimes\|in:[kilogramos]` |
| 7 | `ProductoRequest` → BD | `unique` (ignora el propio): `select count(*) … from productos where nombre = ? and id <> ?`; `exists`: `select count(*) … from categorias where id = ?` | — |
| 8 | `ProductoRequest` | ¿`libras ∈ [kilogramos]`? | No → **alt** [falla] → **302 → `/productos/1/edit`** con "La unidad no se puede cambiar porque el producto ya tiene movimientos." |
| 9 | **alt** [válido: sin `unidad_medida` en la petición] `ProductoController::update` → `Producto` → BD | `$producto->update($validated)` → `update productos set stock_minimo = ?, updated_at = ? where id = ?` | **302 → `/productos`** con "Producto «Maíz amarillo» actualizado." `stock_actual` no se toca |

**SQL real** (trazas "Editar Maíz cambiando la unidad" y "Editar Maíz (stock mínimo 60)"):
```text
[n0] select * from sessions where id = ? limit 1
[n0] select * from users where id = ? limit 1   ← 1
[n0] select * from productos where id = ? limit 1   ← '1'
[n0] select exists(select * from movimientos_inventario where movimientos_inventario.producto_id = ? and movimientos_inventario.producto_id is not null) as exists   ← 1
[n0] select count(*) as aggregate from productos where nombre = ? and id <> ?   ← 'Maíz amarillo', '1'
[n0] select count(*) as aggregate from categorias where id = ?   ← 1
-- alt inválido: update sessions → 302 /productos/1/edit (error en unidad_medida)
-- alt válido:
[n0] update productos set stock_minimo = ?, updated_at = ? where id = ?   ← '60', …, 1
[n0] update sessions set … where id = ?
→ HTTP 302 → /productos   flash success: "Producto «Maíz amarillo» actualizado."
```

**Pruebas:** `ProductoTest::test_con_movimientos_la_unidad_ya_no_cambia`, `test_actualizar_un_producto`.

### SEQ-18 El administrador intenta quitarse el rol o desactivarse (UC-22, UC-23; RN-07)

La interfaz no ofrece estas acciones en la cuenta propia (`UsuarioTest::test_la_hoja_de_tu_propia_cuenta_no_ofrece_cambiar_rol_ni_desactivar`). La secuencia muestra la protección del servidor ante una petición manipulada.

| # | Desde → Hacia | Mensaje | Respuesta y notas |
|---|---|---|---|
| 1 | Navegador → Ruta `usuarios.update` | `PUT /usuarios/1 {name, email, rol: "encargado", password: ""}` (Laura es la #1) | — |
| 2 | `web`, `auth`, `SubstituteBindings` → BD | Sesión; `select users` (1); `select * from users where id = ?` ('1') | — |
| 3 | Ruta → `UsuarioRequest` → BD | `unique(email)` (ignora el propio): `select count(*) … from users where email = ? and id <> ?` | — |
| 4 | `UsuarioRequest` | `esSuPropiaCuenta()` = `true` → `rol: in:[admin]`; "encargado" no está | **302 → `/usuarios/1/edit`** con "No puedes quitarte el rol de administrador." |
| 5 | Navegador → Ruta `usuarios.cambiar-estado` | `PATCH /usuarios/1/estado {activo: "0"}` | — |
| 6 | `web`, `auth`, `SubstituteBindings` → BD | Sesión; `select users` (1); `select users` ('1') | — |
| 7 | `UsuarioController::cambiarEstado` | `validate(activo)`; ¿`!$activo && $usuario->is($request->user())`? | **Sí** → `redirect()->route('usuarios.index')->with('error', 'No puedes desactivar tu propia cuenta.')` → **302**. **No** hay `update users` |

**SQL real:** trazas "Admin intenta quitarse el rol (RN-07)" y "Admin intenta desactivarse (RN-07)". Son las consultas de los pasos 2 y 3, y 6; ninguna escritura en `users`.
**Pruebas:** `UsuarioTest::test_rn07_el_unico_admin_no_puede_quitarse_el_rol`, `test_rn07_el_unico_admin_no_puede_desactivarse`, `test_aunque_haya_otro_admin_nadie_se_desactiva_ni_se_quita_el_rol_a_si_mismo`.

---

## 3. Notas para el modelador (secuencias)

1. **SEQ-00 como `ref`:** se recomienda dibujarlo una vez y en los demás usar un fragmento `ref SEQ-00`. Solo se dibujan explícitos los pasos de middleware que **importan** en el escenario:
   - `auth` y `activo` en SEQ-09.
   - `role:admin` en SEQ-15.
   - `guest` en SEQ-01.
2. **Transacciones:** se pueden marcar con una nota o con un fragmento que encierre los mensajes, por ejemplo `critical` o un rectángulo con la etiqueta "transacción (RN-04)". El bloqueo (`lockForUpdate`) va en el `select … for update`, con una nota "🔒 fila bloqueada hasta COMMIT". En SEQ-08, el fragmento interno (savepoint) va anidado dentro del principal.
3. **`ROLLBACK` en SEQ-06 y SEQ-07:** el mensaje de retorno de `registrar()` es una **excepción** (`ValidationException`), no un valor. En UML se puede dibujar como mensaje de retorno con la etiqueta `«exception» ValidationException`. Después sigue el manejador de Laravel, que responde 302.
4. **`<<include>>` y `<<extend>>` en las secuencias:**
   - UC-18 (validar stock) es el `alt` del paso 15b.
   - UC-19 (alerta) es el `opt` del paso 22.
   - UC-16 dentro de UC-09 es el `opt` [`stock_inicial > 0`] de SEQ-08.
   - UC-02 (bloqueo) es el `alt` [`tooManyAttempts`] de SEQ-01 y SEQ-02.
5. **Lo que puede omitirse:**
   - Las consultas a `sessions` (salvo en SEQ-01, SEQ-09 y SEQ-16, donde la sesión es el tema).
   - Las del `RateLimiter` en detalle: basta "RateLimiter → BD (tabla cache): consultar, sumar o borrar contador".
   - `ShareErrorsFromSession` y `EncryptCookies`.
6. **Datos de ejemplo:** los valores (Maíz, 20,5 kg, Ana, Laura) son los de la traza; sirven para etiquetar los mensajes. Los ids reales los asigna la BD.
