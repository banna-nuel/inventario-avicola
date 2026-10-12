# UML · 02 — Clases

**Fuentes:**
- `database/migrations/*` (esquema).
- `app/Models/*`, `app/Enums/UnidadMedida.php`.
- `app/Http/{Controllers,Requests,Middleware}/*`, `bootstrap/app.php`, `routes/web.php`.

**Convenciones:** las de `00-contexto-y-reglas.md`.
**Visibilidad UML:** `+` pública · `#` protegida · `-` privada · subrayado = estática (aquí se marca como `«static»`).

---

## A. Esquema real de la base de datos

### A.0 Cómo se obtuvo

- **Columnas, nulabilidad, valores por defecto, llaves y restricciones únicas:** salen de las migraciones actuales. Ninguna migración posterior las cambia, salvo `2026_09_12_043713` (agrega `rol` y `activo` a `users`) y `2026_10_11_120000` (agrega el índice por `fecha`).
- **Tipos MySQL:** son los que genera la gramática MySQL de Laravel 12 para cada método del *schema builder*. Por ejemplo, `id()` genera `BIGINT UNSIGNED AUTO_INCREMENT` y `boolean()` genera `TINYINT(1)`.
- **Motor:** MySQL/MariaDB de XAMPP. Se usa el motor por defecto (`engine = null` en `config/database.php`), es decir InnoDB, con `utf8mb4` / `utf8mb4_unicode_ci` (README, paso 4).
- **[VERIFICAR] V-04:** no se pudo correr `php artisan db:table` ni `model:show` porque MySQL (XAMPP) estaba apagado. Los nombres de los índices que InnoDB crea para las llaves foráneas se dedujeron de la convención: el índice toma el nombre de la restricción.

### A.1 Tablas del dominio

#### `users` (modelo `User`)

| Columna | Tipo MySQL | Nulo | Por defecto | Clave / índice | Origen y notas |
|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED AUTO_INCREMENT` | No | — | **PK** | `0001_01_01_000000_create_users_table.php:15` |
| `name` | `VARCHAR(255)` | No | — | — | `:16` |
| `email` | `VARCHAR(255)` | No | — | **UNIQUE** `users_email_unique` | `:17`. La app lo guarda en minúsculas (`UsuarioRequest`) |
| `email_verified_at` | `TIMESTAMP` | Sí | `NULL` | — | `:18`. Sin uso en la app |
| `password` | `VARCHAR(255)` | No | — | — | `:19`. Hash bcrypt (cast `hashed`) |
| `remember_token` | `VARCHAR(100)` | Sí | `NULL` | — | `:20` (`rememberToken()`). Sin uso: no hay "recordarme" |
| `created_at` | `TIMESTAMP` | Sí | `NULL` | — | `:21` (`timestamps()`) |
| `updated_at` | `TIMESTAMP` | Sí | `NULL` | — | `:21` |
| `rol` | `ENUM('admin','encargado')` | No | `'encargado'` | — | `2026_09_12_043713_add_rol_and_activo_to_users_table.php:15` |
| `activo` | `TINYINT(1)` (boolean) | No | `1` | — | `2026_09_12_043713…:16` |

#### `categorias` (modelo `Categoria`)

| Columna | Tipo MySQL | Nulo | Por defecto | Clave / índice | Origen |
|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED AUTO_INCREMENT` | No | — | **PK** | `2026_09_12_043733_create_categorias_table.php:15` |
| `nombre` | `VARCHAR(100)` | No | — | **UNIQUE** `categorias_nombre_unique` | `:16` |
| `created_at` | `TIMESTAMP` | Sí | `NULL` | — | `:17` |
| `updated_at` | `TIMESTAMP` | Sí | `NULL` | — | `:17` |

#### `productos` (modelo `Producto`)

| Columna | Tipo MySQL | Nulo | Por defecto | Clave / índice | Origen y notas |
|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED AUTO_INCREMENT` | No | — | **PK** | `2026_09_12_043741_create_productos_table.php:15` |
| `nombre` | `VARCHAR(150)` | No | — | — (**sin** UNIQUE: la unicidad está solo en `ProductoRequest`, DIF-14) | `:16` |
| `categoria_id` | `BIGINT UNSIGNED` | No | — | **FK** → `categorias.id` (`productos_categoria_id_foreign`); índice de la FK | `:17` (`foreignId()->constrained('categorias')`) |
| `unidad_medida` | `VARCHAR(20)` | No | — | — | `:18`. Valores del enum PHP `UnidadMedida` (la BD no lo restringe) |
| `stock_actual` | `DECIMAL(10,2)` (con signo) | No | `0.00` | — | `:19`. Solo lo cambia `MovimientoInventario::registrar()` (RN-12) |
| `stock_minimo` | `DECIMAL(10,2)` | No | — | — | `:20` |
| `activo` | `TINYINT(1)` (boolean) | No | `1` | — | `:21` |
| `created_at` | `TIMESTAMP` | Sí | `NULL` | — | `:22` |
| `updated_at` | `TIMESTAMP` | Sí | `NULL` | — | `:22` |

#### `movimientos_inventario` (modelo `MovimientoInventario`)

| Columna | Tipo MySQL | Nulo | Por defecto | Clave / índice | Origen y notas |
|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED AUTO_INCREMENT` | No | — | **PK** | `2026_09_12_043748_create_movimientos_inventario_table.php:15` |
| `producto_id` | `BIGINT UNSIGNED` | No | — | **FK** → `productos.id` (`movimientos_inventario_producto_id_foreign`); índice de la FK | `:16` |
| `usuario_id` | `BIGINT UNSIGNED` | No | — | **FK** → `users.id` (`movimientos_inventario_usuario_id_foreign`); índice de la FK | `:17`. Se llama `usuario_id` aunque la tabla es `users` (DIF-02) |
| `tipo` | `ENUM('entrada','salida')` | No | — | — | `:18` |
| `cantidad` | `DECIMAL(10,2)` | No | — | — | `:19`. Siempre positiva; el signo lo da `tipo` |
| `fecha` | `DATETIME` | No | `CURRENT_TIMESTAMP` | **INDEX** `movimientos_inventario_fecha_index` | `:20` (`useCurrent()`); índice en `2026_10_11_120000_add_fecha_index_to_movimientos_inventario_table.php:17` |
| `created_at` | `TIMESTAMP` | Sí | `NULL` | — | `:21` |
| `updated_at` | `TIMESTAMP` | Sí | `NULL` | — | `:21`. Nunca cambia después de crear (RN-09) |

### A.2 Llaves foráneas

| Restricción | Columna | Referencia | ON DELETE | ON UPDATE | Efecto |
|---|---|---|---|---|---|
| `productos_categoria_id_foreign` | `productos.categoria_id` | `categorias.id` | (no definido → RESTRICT / NO ACTION en InnoDB) | (no definido) | No se puede borrar una categoría con productos. Además lo controla `CategoriaController::destroy` (RN-15) |
| `movimientos_inventario_producto_id_foreign` | `movimientos_inventario.producto_id` | `productos.id` | (no definido → RESTRICT) | (no definido) | No se puede borrar un producto con movimientos. La app nunca borra productos (RN-03) |
| `movimientos_inventario_usuario_id_foreign` | `movimientos_inventario.usuario_id` | `users.id` | (no definido → RESTRICT) | (no definido) | No se puede borrar un usuario con movimientos. La app nunca borra usuarios (RN-03) |

No hay borrado en cascada en ninguna llave foránea. Fuente: las migraciones usan `constrained()` sin `cascadeOnDelete()`. Los nombres de las restricciones son la convención de Laravel (`{tabla}_{columna}_foreign`). Ver V-04.

### A.3 Índices y restricciones únicas

| Tabla | Índice | Columnas | Tipo | Para qué |
|---|---|---|---|---|
| `users` | `PRIMARY` | `id` | PK | — |
| `users` | `users_email_unique` | `email` | UNIQUE | INV-04 |
| `categorias` | `PRIMARY` | `id` | PK | — |
| `categorias` | `categorias_nombre_unique` | `nombre` | UNIQUE | INV-05 |
| `productos` | `PRIMARY` | `id` | PK | — |
| `productos` | índice de la FK (`productos_categoria_id_foreign`) | `categoria_id` | INDEX (lo crea InnoDB para la FK) | Filtro por categoría y conteos. [VERIFICAR] V-04 |
| `movimientos_inventario` | `PRIMARY` | `id` | PK | — |
| `movimientos_inventario` | índice de la FK de `producto_id` | `producto_id` | INDEX (InnoDB) | Historial por producto. [VERIFICAR] V-04 |
| `movimientos_inventario` | índice de la FK de `usuario_id` | `usuario_id` | INDEX (InnoDB) | [VERIFICAR] V-04 |
| `movimientos_inventario` | `movimientos_inventario_fecha_index` | `fecha` | INDEX | Historial, "movimientos de hoy", reporte "Más movidos" (comentario en la migración `2026_10_11_120000`; `BaseDeDatosTest::test_los_movimientos_tienen_indice_por_fecha`) |

### A.4 Tablas de infraestructura (framework, sin clase de dominio)

| Tabla | Columnas | ¿La usa la app? |
|---|---|---|
| `sessions` | `id VARCHAR(255)` PK · `user_id BIGINT UNSIGNED NULL` con INDEX, **sin** FK · `ip_address VARCHAR(45) NULL` · `user_agent TEXT NULL` · `payload LONGTEXT` · `last_activity INT` con INDEX | Sí, con `SESSION_DRIVER=database` (`.env.example:32`): se lee y se escribe en cada petición (`03` §0) |
| `cache` | `key VARCHAR(255)` PK · `value MEDIUMTEXT` · `expiration INT` con INDEX | Sí, con `CACHE_STORE=database` (`.env.example:42`): contador de intentos del login (`RateLimiter`, UC-02) |
| `cache_locks` | `key` PK · `owner VARCHAR(255)` · `expiration INT` con INDEX | No se observó uso |
| `password_reset_tokens` | `email VARCHAR(255)` PK · `token VARCHAR(255)` · `created_at TIMESTAMP NULL` | **No** (RF-02 fuera de alcance, DIF-20) |
| `jobs`, `job_batches`, `failed_jobs` | Colas de Laravel (`0001_01_01_000002_create_jobs_table.php`) | **No**: la app no despacha trabajos |
| `migrations` | Registro de migraciones de Laravel | Solo el framework |

En el diagrama de clases de dominio se omiten estas tablas (ver D.6).

---

## B. Clases de dominio

### B.0 Herencia y traits

| Clase | Hereda de | Implementa (por herencia) | Traits | Factory |
|---|---|---|---|---|
| `App\Models\User` | `Illuminate\Foundation\Auth\User`, que a su vez hereda de `Illuminate\Database\Eloquent\Model` | `Authenticatable`, `Authorizable`, `CanResetPassword` (contratos de Laravel) | `HasFactory`, `Notifiable` (propios); `Authenticatable`, `Authorizable`, `CanResetPassword`, `MustVerifyEmail` (heredados) | `UserFactory` (estados `admin()`, `encargado()`, `unverified()`) |
| `App\Models\Categoria` | `Illuminate\Database\Eloquent\Model` | — | `HasFactory` | `CategoriaFactory` |
| `App\Models\Producto` | `Illuminate\Database\Eloquent\Model` | — | `HasFactory` | `ProductoFactory` (estado `inactivo()`) |
| `App\Models\MovimientoInventario` | `Illuminate\Database\Eloquent\Model` | — | — (sin factory) | — |
| `App\Enums\UnidadMedida` | (enum de PHP 8.1, respaldado por `string`) | `BackedEnum` (implícito) | — | — |

Fuentes: `app/Models/User.php:11-14`; `vendor/laravel/framework/src/Illuminate/Foundation/Auth/User.php:14-19`; `app/Models/Categoria.php:8-10`; `app/Models/Producto.php:10-12`; `app/Models/MovimientoInventario.php:12`; `app/Enums/UnidadMedida.php:13`.

### B.1 `User` — tabla `users`

**Atributos.** En Eloquent son atributos dinámicos públicos; se leen y escriben como `$user->name`.

| Atributo | Tipo (PHP → UML) | Vis. | Origen | Notas |
|---|---|---|---|---|
| `id` | `int` → Integer | + | `users.id` | PK |
| `name` | `string` → String | + | `users.name` | |
| `email` | `string` → String | + | `users.email` | Único |
| `email_verified_at` | `?Carbon` → DateTime [0..1] | + | `users.email_verified_at` | Cast `datetime`; sin uso |
| `password` | `string` → String | + (oculto en serialización) | `users.password` | Cast `hashed` |
| `remember_token` | `?string` → String [0..1] | + (oculto) | `users.remember_token` | Sin uso |
| `rol` | `string` → Rol (enumeración implícita, B.6) | + | `users.rol` | Sin cast: es texto (`'admin'` o `'encargado'`) |
| `activo` | `bool` → Boolean | + | `users.activo` | Cast `boolean` |
| `created_at`, `updated_at` | `?Carbon` → DateTime [0..1] | + | timestamps | |

**Constantes y propiedades de configuración**

| Miembro | Vis. | Valor | Fuente |
|---|---|---|---|
| `ROLES` (constante de clase) | + «static» | `['admin' => 'Administrador', 'encargado' => 'Encargado de inventario']` | `User.php:54-57` |
| `$fillable` | # | `['name', 'email', 'password', 'rol', 'activo']` | `User.php:21-27` |
| `$hidden` | # | `['password', 'remember_token']` | `User.php:34-37` |
| `casts()` | # | `email_verified_at` → `datetime`; `password` → `hashed`; `activo` → `boolean` | `User.php:44-51` |

**Métodos**

| Firma | Vis. | Responsabilidad | Fuente |
|---|---|---|---|
| `esAdmin(): bool` | + | `true` si `rol === 'admin'`. La usan las vistas, `BusquedaController` y `MovimientoController` | `User.php:60-63` |
| `enlaces(string $grupo): array` | + | Devuelve los enlaces de `config/navegacion.php` (`'barra'` o `'modulos'`) que puede ver su rol. Arma la barra flotante y las tarjetas de Inicio | `User.php:66-72` |
| `casts(): array` | # | Declara los casts | `User.php:44-51` |

**Relaciones:** **ninguna declarada**. No existe `User::movimientos()`. La relación con los movimientos solo se navega desde `MovimientoInventario::usuario()` (ver B.7, R3).

### B.2 `Categoria` — tabla `categorias`

| Atributo | Tipo | Vis. | Origen | Notas |
|---|---|---|---|---|
| `id` | Integer | + | `categorias.id` | PK |
| `nombre` | String | + | `categorias.nombre` | Único; hasta 100 caracteres |
| `created_at`, `updated_at` | DateTime [0..1] | + | timestamps | |
| *`productos_count`* | Integer | + (derivado, solo en consultas) | `withCount('productos')` | Lo calcula `CategoriaController::listado`, `BusquedaController::categorias`. No es columna |

| Miembro | Vis. | Valor / firma | Responsabilidad | Fuente |
|---|---|---|---|---|
| `$fillable` | # | `['nombre']` | — | `Categoria.php:12-14` |
| `productos()` | + | `: HasMany<Producto>` (en el código no declara el tipo de retorno) | Los productos de la categoría, activos e inactivos | `Categoria.php:16-18` |

### B.3 `Producto` — tabla `productos`

| Atributo | Tipo (PHP → UML) | Vis. | Origen | Notas |
|---|---|---|---|---|
| `id` | Integer | + | `productos.id` | PK |
| `nombre` | String | + | `productos.nombre` | Hasta 150 caracteres; único solo en la app |
| `categoria_id` | Integer | + | `productos.categoria_id` | FK |
| `unidad_medida` | `UnidadMedida` (enum) | + | `productos.unidad_medida` | Cast a enum: `$p->unidad_medida` es `UnidadMedida::Kilogramos`, no un texto |
| `stock_actual` | `string` con 2 decimales → Decimal | + (**no asignable**: no está en `$fillable`) | `productos.stock_actual` | Cast `decimal:2` (en PHP es texto, por ejemplo `'120.50'`). Solo lo cambia `MovimientoInventario::registrar()` |
| `stock_minimo` | Decimal | + | `productos.stock_minimo` | Cast `decimal:2` |
| `activo` | Boolean | + | `productos.activo` | Cast `boolean` |
| `created_at`, `updated_at` | DateTime [0..1] | + | timestamps | |
| *`movimientos_count`*, *`entradas`*, *`salidas`* | Integer, Decimal, Decimal | + (derivados, solo en el reporte) | `withCount` y `withSum` en `ReporteController::masMovidos` | No son columnas |

| Miembro | Vis. | Valor / firma | Responsabilidad | Fuente |
|---|---|---|---|---|
| `$fillable` | # | `['nombre', 'categoria_id', 'unidad_medida', 'stock_minimo', 'activo']` | `stock_actual` queda fuera **a propósito** (RN-12) | `Producto.php:14-21` |
| `casts()` | # | `unidad_medida` → `UnidadMedida::class`; `stock_actual` y `stock_minimo` → `decimal:2`; `activo` → `boolean` | — | `Producto.php:23-31` |
| `categoria()` | + | `: BelongsTo<Categoria>` | Su categoría (FK `categoria_id`) | `Producto.php:33-36` |
| `movimientos()` | + | `: HasMany<MovimientoInventario>` | Su historial (FK `producto_id`) | `Producto.php:38-41` |
| `scopeStockBajo(Builder $query): void` | + «scope» | Se usa como `Producto::stockBajo()` | Filtra `stock_actual < stock_minimo` (RN-02) en SQL | `Producto.php:50-53` |
| `enStockBajo(): bool` | + | — | La misma regla para un producto ya cargado | `Producto.php:55-58` |
| `scopeEnAlerta(Builder $query): void` | + «scope» | Se usa como `Producto::enAlerta()` | Activos en stock bajo, ordenados por mayor faltante y luego por nombre (RN-20). La usan el Dashboard y el reporte de stock bajo | `Producto.php:62-68` |
| `faltante(): float` | + | — | `max(0, stock_minimo − stock_actual)` | `Producto.php:71-74` |

### B.4 `MovimientoInventario` — tabla `movimientos_inventario`

| Atributo | Tipo | Vis. | Origen | Notas |
|---|---|---|---|---|
| `id` | Integer | + | `movimientos_inventario.id` | PK |
| `producto_id` | Integer | + | FK | |
| `usuario_id` | Integer | + | FK → `users.id` | Quién lo registró |
| `tipo` | `string` → TipoMovimiento (enumeración implícita, B.6) | + | `movimientos_inventario.tipo` | Sin cast |
| `cantidad` | Decimal (cast `decimal:2`) | + | `movimientos_inventario.cantidad` | > 0 |
| `fecha` | DateTime (cast `datetime`) | + | `movimientos_inventario.fecha` | Cuándo ocurrió |
| `created_at`, `updated_at` | DateTime [0..1] | + | timestamps | |

| Miembro | Vis. | Valor / firma | Responsabilidad | Fuente |
|---|---|---|---|---|
| `$table` | # | `'movimientos_inventario'` | Nombre de la tabla; si no, Laravel buscaría `movimiento_inventarios` | `MovimientoInventario.php:15` |
| `$fillable` | # | `['producto_id', 'usuario_id', 'tipo', 'cantidad', 'fecha']` | — | `:17-23` |
| `casts()` | # | `cantidad` → `decimal:2`; `fecha` → `datetime` | — | `:25-31` |
| `booted(): void` | # «static» | — | Registra los eventos `updating` y `deleting`; ambos lanzan `LogicException` (RN-09: inmutable) | `:34-38` |
| `producto()` | + | `: BelongsTo<Producto>` | Producto del movimiento | `:40-43` |
| `usuario()` | + | `: BelongsTo<User>` (FK `usuario_id`) | Usuario que lo registró | `:45-48` |
| `registrar(Producto $producto, string $tipo, string\|int\|float $cantidad, User $usuario, ?Carbon $fecha = null): self` | + «static» | Ver los detalles debajo de la tabla | **Única** forma de cambiar `productos.stock_actual` (RN-04, RN-01, RN-10, RN-12) | `:64-108` |

**Detalle de `registrar()`**
1. Comprueba que `tipo` sea `entrada` o `salida`; si no, lanza `InvalidArgumentException`.
2. Abre `DB::transaction`.
3. Vuelve a leer el producto con `lockForUpdate()`.
4. Valida:
   - Producto activo.
   - Cantidad mayor que 0.
   - En las salidas, cantidad ≤ stock.
   - Si algo falla, lanza `ValidationException` y la transacción se deshace.
5. Hace `increment` o `decrement` de `stock_actual`.
6. Crea el movimiento con `fecha ?? now()`.
7. Devuelve el movimiento creado.

### B.5 Enum `UnidadMedida` (`App\Enums\UnidadMedida`, respaldado por `string`)

| Caso | Valor guardado en la BD | Singular |
|---|---|---|
| `Libras` | `'libras'` | libra |
| `Kilogramos` | `'kilogramos'` | kilogramo |
| `Unidades` | `'unidades'` | unidad |
| `Cubetas` | `'cubetas'` | cubeta |
| `Bultos` | `'bultos'` | bulto |
| `Litros` | `'litros'` | litro |
| `Dosis` | `'dosis'` | dosis |

| Método | Vis. | Responsabilidad | Fuente |
|---|---|---|---|
| `opciones(): array` | + «static» | `['libras' => 'Libras', …]` para los selectores | `UnidadMedida.php:24-32` |
| `singular(): string` | + | Nombre en singular | `:34-45` |
| `nombrePara(string\|int\|float $cantidad): string` | + | Singular si la cantidad es 1; si no, plural | `:48-51` |
| `cantidad(string\|int\|float $cantidad): string` | + | Texto listo para mostrar: `"1.250,5 kilogramos"`, `"1 libra"` | `:54-57` |
| `numero(string\|int\|float $cantidad): string` | + «static» | Número con coma decimal y punto de miles, sin ceros de más | `:60-65` |

Pruebas: `ProductoTest::test_la_unidad_se_escribe_en_singular_o_plural`.

### B.6 Enumeraciones implícitas (no son clases PHP)

Conviene dibujarlas como `«enumeration»` para que el diagrama sea legible. En el código no existe una clase con estos nombres.

| Enumeración (nombre sugerido) | Literales | Dónde se define en realidad |
|---|---|---|
| `Rol` | `admin`, `encargado` | `ENUM` de `users.rol` (migración `2026_09_12_043713:15`); `User::ROLES` (etiquetas); `UsuarioRequest::rules:40` (`Rule::in`) |
| `TipoMovimiento` | `entrada`, `salida` | `ENUM` de `movimientos_inventario.tipo`; `MovimientoRequest::rules:31`; `FiltrosHistorialRequest::rules:28`; `MovimientoInventario::registrar:66-68` |
| `EstadoFiltroProducto` | `''` (Activos, por defecto), `stock-bajo`, `inactivos`, `todos` | `ProductoController::ESTADOS:25` (constante privada) |
| `GrupoBusqueda` | `productos`, `categorias`, `usuarios` | `BusquedaController::__invoke:26` (`$permitidos`) |
| `DestinoVolver` | `productos` → `productos.index`, `dashboard` → `dashboard` | `MovimientoController::VOLVER:19` |

### B.7 Relaciones entre clases de dominio

| ID | Origen | Destino | Tipo UML sugerido | Mult. en el origen | Mult. en el destino | Rol o nombre | Llave foránea | Navegabilidad en el código | Regla de borrado |
|---|---|---|---|---|---|---|---|---|---|
| R1 | `Categoria` | `Producto` | Asociación ("clasifica") | 1 | 0..* | `Categoria::productos()` ↔ `Producto::categoria()` | `productos.categoria_id` (NOT NULL) | Bidireccional | BD: RESTRICT. App: no se elimina si tiene productos (RN-15). Los productos nunca se eliminan |
| R2 | `Producto` | `MovimientoInventario` | Asociación ("tiene"); se puede dibujar como composición conceptual (D.3) | 1 | 0..* | `Producto::movimientos()` ↔ `MovimientoInventario::producto()` | `movimientos_inventario.producto_id` (NOT NULL) | Bidireccional | BD: RESTRICT. App: los productos no se eliminan (RN-03) y los movimientos tampoco (RN-09) |
| R3 | `User` | `MovimientoInventario` | Asociación ("registra") | 1 | 0..* | `MovimientoInventario::usuario()`; **no** existe `User::movimientos()` | `movimientos_inventario.usuario_id` (NOT NULL) | **Unidireccional**: `MovimientoInventario` → `User` | BD: RESTRICT. App: los usuarios no se eliminan (RN-03) |
| R4 | `Producto` | `UnidadMedida` | Atributo tipado por enumeración (`unidad_medida: UnidadMedida`) | — | 1 | `unidad_medida` | — | `Producto` → `UnidadMedida` | — |
| R5 | `MovimientoInventario` | `TipoMovimiento` | Atributo tipado por enumeración implícita | — | 1 | `tipo` | — | — | — |
| R6 | `User` | `Rol` | Atributo tipado por enumeración implícita | — | 1 | `rol` | — | — | — |
| R7 | `MovimientoInventario` | `Producto`, `User` | Dependencia `«use»` (parámetros de `registrar()`) | — | — | `registrar(Producto, …, User)` | — | — | — |

Los nombres de rol "clasifica", "tiene" y "registra" son los del diagrama ER de `docs/03` §1.

---

## C. Capa de aplicación

No se incluyen clases internas de Laravel, salvo las bases de las que se hereda: `Controller`, `FormRequest` y los middleware del framework citados por su alias.

### C.1 Controladores

Todos heredan de `App\Http\Controllers\Controller` (abstracta y vacía, `app/Http/Controllers/Controller.php`). Ningún método declara el tipo de retorno en el código. Entre paréntesis va el tipo real que devuelven: `View` o `RedirectResponse`.

| Controlador · método (visibilidad) | Ruta | UC | Entrada | Usa (dominio) | Devuelve |
|---|---|---|---|---|---|
| `AuthController` · `- const MAX_INTENTOS = 3`, `- const SEGUNDOS_BLOQUEO = 60` | — | UC-01, UC-02 | — | — | — |
| `AuthController::showLoginForm()` (+) | `login` | UC-01 | — | — | View `auth.login` |
| `AuthController::login(Request $request)` (+) | `login.entrar` | UC-01, UC-02 | `email`, `password` (validación en el controlador) | `User` (vía `Auth::validate` y `Auth::getLastAttempted`), `RateLimiter` | Redirect `intended(home)` o `login` con errores |
| `AuthController::logout(Request $request)` (+) | `logout` | UC-03 | — | — | Redirect `login` |
| `InicioController::__invoke()` (+, invocable) | `home` | UC-04 | — | `Producto` (`where activo`, `stockBajo()->count()`) | View `home` |
| `DashboardController::__invoke()` (+, invocable) | `dashboard` | UC-05 | — | `Producto` (`enAlerta`, `count`), `MovimientoInventario` (`count` de hoy) | View `dashboard` |
| `BusquedaController::__invoke(Request $request)` (+, invocable); `- productos(string $q): array`; `- categorias(string $q): array`; `- usuarios(string $q): array`; `- const MINIMO_CARACTERES = 2`, `- const POR_GRUPO = 10` | `search` | UC-06 | `q`, `tipo` | `Producto`, `Categoria`, `User` | View `search` |
| `ProductoController::index(Request $request)` (+) | `productos.index` | UC-07 | `q`, `categoria`, `estado`, `page` | `Producto`, `Categoria` | View `productos.index` |
| `ProductoController::create(Request $request)` (+) | `productos.create` | UC-09 | filtros | `Producto`, `Categoria` | View `productos.create` (listado + hoja) |
| `ProductoController::store(ProductoRequest $request)` (+) | `productos.store` | UC-09 (+ UC-16) | `ProductoRequest` | `Producto`, `MovimientoInventario::registrar`, `DB::transaction` | Redirect `productos.index` + `success` |
| `ProductoController::edit(Request $request, Producto $producto)` (+) | `productos.edit` | UC-10 | `{producto}` | `Producto` (`movimientos()->count()`), `Categoria` | View `productos.edit` |
| `ProductoController::update(ProductoRequest $request, Producto $producto)` (+) | `productos.update` | UC-10 | `ProductoRequest`, `{producto}` | `Producto` | Redirect `productos.index` + `success` |
| `ProductoController::cambiarEstado(Request $request, Producto $producto)` (+) | `productos.cambiar-estado` | UC-11 | `activo` (validación en el controlador) | `Producto` | Redirect `productos.index` + `success` |
| `ProductoController` · `- listado(Request): array`, `- filtros(Request): array`, `- const FILTROS`, `- const ESTADOS` | — | — | — | `Producto`, `Categoria` | — |
| `MovimientoController::index(FiltrosHistorialRequest $request)` (+) | `movimientos.index` | UC-08 | `FiltrosHistorialRequest` | `MovimientoInventario`, `Producto` | View `movimientos.index` |
| `MovimientoController::create(FiltrosHistorialRequest $request)` (+) | `movimientos.create` | UC-16, UC-17 | filtros + `producto_id`, `tipo`, `volver` | `MovimientoInventario`, `Producto` | View `movimientos.create` (historial + hoja) |
| `MovimientoController::store(MovimientoRequest $request)` (+) | `movimientos.store` | UC-16, UC-17, UC-18, UC-19 | `MovimientoRequest` + `volver` | `Producto` (`findOrFail`, `refresh`, `enStockBajo`), `MovimientoInventario::registrar`, `UnidadMedida::cantidad` | Redirect (`movimientos.index`, `productos.index` o `dashboard`) + `success` [+ `aviso`] |
| `MovimientoController` · `- historial(FiltrosHistorialRequest): array`, `- volver(mixed): ?string`, `- const VOLVER` | — | — | — | — | — |
| `CategoriaController::index(Request $request)` (+) | `categorias.index` | UC-12 | `q` | `Categoria` | View `categorias.index` |
| `CategoriaController::create()` (+) | `categorias.create` | UC-13 | — | `Categoria` | View `categorias.create` |
| `CategoriaController::store(CategoriaRequest $request)` (+) | `categorias.store` | UC-13 | `CategoriaRequest` | `Categoria` | Redirect + `success` |
| `CategoriaController::edit(Categoria $categoria)` (+) | `categorias.edit` | UC-14 | `{categoria}` | `Categoria` | View `categorias.edit` |
| `CategoriaController::update(CategoriaRequest $request, Categoria $categoria)` (+) | `categorias.update` | UC-14 | `CategoriaRequest` | `Categoria` | Redirect + `success` |
| `CategoriaController::destroy(Categoria $categoria)` (+) | `categorias.destroy` | UC-15 | `{categoria}` | `Categoria` (`productos()->count()`, `delete`) | Redirect + `success` o `error` |
| `CategoriaController` · `- listado(string $q = '')` | — | — | — | `Categoria` (`withCount`) | — |
| `UsuarioController::index(Request $request)` (+) | `usuarios.index` | UC-20 | `q`, `page` | `User` | View `usuarios.index` |
| `UsuarioController::create(Request $request)` (+) | `usuarios.create` | UC-21 | filtros | `User` | View `usuarios.create` |
| `UsuarioController::store(UsuarioRequest $request)` (+) | `usuarios.store` | UC-21 | `UsuarioRequest` | `User::create` (+ `activo = true`) | Redirect + `success` |
| `UsuarioController::edit(Request $request, User $usuario)` (+) | `usuarios.edit` | UC-22 (UC-23) | `{usuario}` | `User` (`is()` para "su propia cuenta") | View `usuarios.edit` |
| `UsuarioController::update(UsuarioRequest $request, User $usuario)` (+) | `usuarios.update` | UC-22 | `UsuarioRequest` | `User` | Redirect + `success` |
| `UsuarioController::cambiarEstado(Request $request, User $usuario)` (+) | `usuarios.cambiar-estado` | UC-23 | `activo` (validación en el controlador) | `User` | Redirect + `success` o `error` (RN-07) |
| `UsuarioController` · `- listado(Request): array`, `- filtros(Request): array`, `- const FILTROS` | — | — | — | `User` | — |
| `ReporteController::index()` (+) | `reportes.index` | UC-24, UC-25, UC-26 | — | — | View `reportes.index` |
| `ReporteController::stockPorCategoria()` (+) | `reportes.stock-por-categoria` | UC-24 | — | `Categoria` (`with productos` activos) | View |
| `ReporteController::stockBajo()` (+) | `reportes.stock-bajo` | UC-25 | — | `Producto::enAlerta()` | View |
| `ReporteController::masMovidos(RangoFechasRequest $request)` (+) | `reportes.mas-movidos` | UC-26 | `RangoFechasRequest` | `Producto` (`whereHas`, `withCount`, `withSum`) | View |
| *closure* de `raiz` (`routes/web.php:26`) | `raiz` | UC-01 (A2), UC-04 | — | — | Redirect `home` |

### C.2 Form Requests

Todas heredan de `Illuminate\Foundation\Http\FormRequest`. `authorize()` siempre devuelve `true`: el permiso lo da el middleware.

| Clase | La usa | `prepareForValidation()` | Reglas (resumen) | Otros miembros | Fuente |
|---|---|---|---|---|---|
| `CategoriaRequest` | `CategoriaController::store`, `CategoriaController::update` | `nombre`: una línea, sin espacios repetidos | `nombre`: obligatorio, texto, hasta 100, único en `categorias` (ignora la propia al editar) | `messages()` | `app/Http/Requests/CategoriaRequest.php` |
| `ProductoRequest` | `ProductoController::store`, `ProductoController::update` | Ver los detalles debajo de la tabla | Ver los detalles debajo de la tabla | `- reglasDeUnidad(?Producto): array`; `messages()` | `app/Http/Requests/ProductoRequest.php` |
| `MovimientoRequest` | `MovimientoController::store` | `cantidad`: sin espacios; coma → punto | `tipo`: obligatorio, `entrada` o `salida`. `producto_id`: obligatorio, entero, existe en `productos`. `cantidad`: obligatoria, numérica, mayor que 0, hasta 99.999.999,99, 0 a 2 decimales | `messages()` | `app/Http/Requests/MovimientoRequest.php` |
| `FiltrosHistorialRequest` | `MovimientoController::index`, `MovimientoController::create` | — | `producto`: opcional, entero, existe en `productos`. `tipo`: opcional, `entrada` o `salida`. `desde`: opcional, `Y-m-d`. `hasta`: opcional, `Y-m-d` y, si hay `desde`, posterior o igual | `# $redirectRoute = 'movimientos.index'`; `+ filtros(): array` (solo los filtros con valor); `messages()` | `app/Http/Requests/FiltrosHistorialRequest.php` |
| `UsuarioRequest` | `UsuarioController::store`, `UsuarioController::update` | `name`: una línea. `email`: minúsculas, sin espacios | `name`: obligatorio, hasta 255. `email`: obligatorio, correo válido, hasta 255, único en `users` (ignora el propio). `rol`: obligatorio; en **su propia cuenta** solo `admin`, si no `admin` o `encargado`. `password`: obligatoria al crear y opcional al editar; texto de 8 a 255 | `- esSuPropiaCuenta(): bool`; `messages()` (el texto de `rol.in` cambia con RN-07) | `app/Http/Requests/UsuarioRequest.php` |
| `RangoFechasRequest` | `ReporteController::masMovidos` | — | `desde`: opcional, `Y-m-d`. `hasta`: opcional, `Y-m-d` y, si hay `desde`, posterior o igual | `# $redirectRoute = 'reportes.mas-movidos'`; `- const DIAS_POR_DEFECTO = 30`; `+ desde(): Carbon` (si falta, hoy − 29 días); `+ hasta(): Carbon` (si falta, hoy); `messages()` | `app/Http/Requests/RangoFechasRequest.php` |

**`ProductoRequest` en detalle**
- **`prepareForValidation()`:**
  - `nombre` en una línea.
  - `stock_minimo`: coma → punto.
  - `stock_inicial`: coma → punto, si viene.
- **Reglas:**
  - `nombre`: obligatorio, texto, hasta 150, único en `productos` (ignora el propio al editar).
  - `categoria_id`: obligatorio, entero, existe en `categorias`.
  - `unidad_medida`:
    - Sin movimientos: obligatoria y del enum `UnidadMedida`.
    - Con movimientos: opcional (`sometimes`), pero solo puede ser la unidad actual.
  - `stock_minimo`: obligatorio, numérico, ≥ 0, hasta 99.999.999,99, de 0 a 2 decimales.
  - `stock_inicial`:
    - Al crear: opcional, con el mismo formato.
    - Al editar: **prohibido**.

**Validación en el controlador (sin Form Request)**
- `AuthController::login`: `email` obligatorio y con formato de correo; `password` obligatoria.
- `ProductoController::cambiarEstado` y `UsuarioController::cambiarEstado`: `activo` obligatorio y booleano.

### C.3 Middleware

| Alias | Clase | Qué verifica | Qué responde si falla | Dónde se registra | Rutas |
|---|---|---|---|---|---|
| `auth` | `Illuminate\Auth\Middleware\Authenticate` (framework) | Que haya un usuario en la sesión. Lo carga de `users` por id en cada petición | 302 → `route('login')`; guarda la URL pedida | `bootstrap/app.php:22` (`redirectGuestsTo`) | Grupo protegido (`routes/web.php:38`) |
| `guest` | `Illuminate\Auth\Middleware\RedirectIfAuthenticated` (framework) | Que **no** haya sesión | 302 → `route('home')` | `bootstrap/app.php:23` (`redirectUsersTo`) | `login`, `login.entrar` (`routes/web.php:29`) |
| `activo` | `App\Http\Middleware\UsuarioActivo` · `+ handle(Request $request, Closure $next): Response` | `users.activo` del usuario autenticado | `Auth::logout()`, invalida la sesión, regenera el token y responde 302 → `login` con el error "Tu cuenta fue desactivada…" | `bootstrap/app.php:17` | Grupo protegido |
| `role` | `App\Http\Middleware\VerificarRol` · `+ handle(Request $request, Closure $next, string ...$roles): Response` | `users.rol` ∈ `$roles` (en la app: `role:admin`) | `abort(403)` → vista `errors/403` | `bootstrap/app.php:16` | Grupo de administrador (`routes/web.php:55`) |
| (grupo `web`) | `EncryptCookies`, `AddQueuedCookiesToResponse`, `StartSession`, `ShareErrorsFromSession`, `ValidateCsrfToken`, `SubstituteBindings` (framework) | Cookies, sesión, errores, token CSRF (419) y *route model binding* (404) | 419 / 404 | Laravel | Todas |

Orden real: `00` §3.3.

### C.4 Otras clases de la aplicación

| Clase | Hereda de | Para qué | ¿En el diagrama? |
|---|---|---|---|
| `App\Providers\AppServiceProvider` | `Illuminate\Support\ServiceProvider` | `boot()` registra los macros `whereContiene(columna, texto)` y `orWhereContiene` en el *query builder*: `LIKE` con `%` y `_` escapados (RN-23). Los usan `BusquedaController`, `CategoriaController`, `ProductoController` y `UsuarioController` | Solo en la vista de aplicación, como dependencia, si se quiere |
| `App\View\Components\Icosaedro` | `Illuminate\View\Component` | Componente `x-icosaedro` (RNF-08): calcula el SVG estático. `+ $tamano: string`, `+ $lineas: array`, `+ render()`. Métodos privados de cálculo | **No** (presentación) |
| `config/navegacion.php` | (archivo de configuración) | Enlaces de la barra y de Inicio por rol; lo lee `User::enlaces()` | Opcional, como nota |

### C.5 Dependencias capa de aplicación → dominio

✔ = el controlador o la clase usa esa clase del dominio.

| Clase | `User` | `Categoria` | `Producto` | `MovimientoInventario` | `UnidadMedida` |
|---|---|---|---|---|---|
| `AuthController` | ✔ (vía `Auth`) | | | | |
| `InicioController` | | | ✔ | | |
| `DashboardController` | | | ✔ | ✔ | |
| `BusquedaController` | ✔ | ✔ | ✔ | | |
| `ProductoController` | ✔ (`$request->user()`, para el stock inicial) | ✔ | ✔ | ✔ (`registrar`) | |
| `MovimientoController` | ✔ (`$request->user()`) | | ✔ | ✔ | ✔ (`$producto->unidad_medida->cantidad()`) |
| `CategoriaController` | | ✔ | | | |
| `UsuarioController` | ✔ | | | | |
| `ReporteController` | | ✔ | ✔ | | |
| `ProductoRequest` | | | ✔ | | ✔ (`Rule::enum`) |
| `UsuarioRequest` | ✔ (`User::ROLES`) | | | | |
| `UsuarioActivo`, `VerificarRol` | ✔ (`$request->user()`) | | | | |

---

## D. Notas para el modelador

### D.1 Rol: atributo de `User` o subclases

**Así está el sistema:** **un** modelo `User` con el atributo `rol` (texto `admin` o `encargado`) y el método `esAdmin()`.
- Los permisos se deciden en el middleware `role` y en las vistas.
- `docs/03` hace lo mismo: `rol` es un atributo `enum` de USERS. No tiene subclases (DIF-27, V-02).

| Opción | Cómo se dibuja | Consecuencias |
|---|---|---|
| **A. Atributo (recomendada, fiel al código)** | `User` con `rol: Rol` («enumeration» Rol {admin, encargado}) y `+ esAdmin(): bool` | Ver los detalles debajo de la tabla |
| B. Subclases | `Usuario` (abstracta) con las hijas `Administrador` y `EncargadoInventario` | Ver los detalles debajo de la tabla |

**Opción A en detalle**
- Coincide con la tabla `users` y con `UsuarioController::update`, que cambia el rol con un simple `update`.
- El cambio de rol se aplica en la siguiente petición.
- Las diferencias de permisos no se ven en el diagrama de clases: se documentan con una nota o se ven en el diagrama de casos de uso.

**Opción B en detalle**
- Muestra las diferencias de comportamiento, pero **no corresponde** a la implementación: hay una sola tabla y una sola clase, y el rol es **mutable**.
- Con subclases, cambiar el rol obligaría a cambiar la clase del objeto, lo que UML no permite sin reclasificar.
- Si se elige, hay que agregar la nota: "implementado como atributo `rol` en una sola tabla (*single table*)".

Sugerencia: usar la generalización de actores **solo en el diagrama de casos de uso** y la opción A en el de clases.

### D.2 `stock_actual` y su regla de actualización

- Es un atributo **guardado** (desnormalizado), pero **conceptualmente derivado**: `stock_actual = Σ entradas − Σ salidas` (RN-12; lo comprueba `BaseDeDatosTest::test_el_seeder_crea_una_granja_de_demostracion_coherente`).
- **Cómo dibujarlo:**
  - Atributo normal `stock_actual: Decimal {readOnly}`.
  - Restricción (OCL o nota): `{stock_actual = movimientos->select(tipo='entrada').cantidad->sum() - movimientos->select(tipo='salida').cantidad->sum()}` y `{stock_actual >= 0}`.
  - Nota: "solo lo modifica `MovimientoInventario::registrar()`, dentro de una transacción con bloqueo de fila".
- **No conviene** la notación de derivado (`/stock_actual`). En UML, la barra indica que se calcula al consultarlo, y aquí está guardado en la tabla.
- `{readOnly}` desde fuera es fiel a `$fillable`: no se puede asignar en masa. Ojo: sí cambia, pero solo por `increment` y `decrement` en `registrar()`.

### D.3 `Producto` – `MovimientoInventario`: asociación o composición

- **Asociación 1 — 0..\* (recomendada):** refleja la llave foránea `RESTRICT` (no hay borrado en cascada) y que ni productos ni movimientos se eliminan.
- **Composición (rombo negro en `Producto`):** se puede justificar en lo conceptual (un movimiento no existe sin su producto: `producto_id NOT NULL`). Pero en UML la composición implica que, al destruir el todo, se destruyen las partes, y el sistema impide destruir el producto. Si se usa, conviene anotarlo.
- `Categoria` – `Producto` **no** es composición ni agregación fuerte: los productos se pueden cambiar de categoría (UC-10) y la categoría no se borra mientras tenga productos.

### D.4 Métodos estáticos, *scopes* y eventos

- `MovimientoInventario::registrar()` es una **operación de clase** (estática): en UML va subrayada. Devuelve el `MovimientoInventario` creado.
- Los *scopes* se definen como `scopeStockBajo(Builder)` y `scopeEnAlerta(Builder)`, pero se usan como `Producto::stockBajo()` y `Producto::enAlerta()`. Se sugiere dibujarlos como `+ stockBajo(): Builder «scope»` y `+ enAlerta(): Builder «scope»`.
- `booted()` registra dos *listeners* que lanzan `LogicException`. En UML se puede expresar como una restricción `{inmutable}` (o `{readOnly}` en todos los atributos) de `MovimientoInventario`, con una nota de RN-09.

### D.5 Tipos PHP → tipos UML

| PHP / Eloquent | UML |
|---|---|
| `int` (ids, FK) | Integer |
| `string` | String |
| `bool` / cast `boolean` | Boolean |
| cast `decimal:2` (en PHP es texto, por ejemplo `'120.50'`) | Decimal |
| cast `datetime`, timestamps (`Carbon`) | DateTime |
| `?Tipo` / columna `NULL` | Tipo con multiplicidad `[0..1]` |
| `UnidadMedida` (enum PHP) | «enumeration» `UnidadMedida` |
| `BelongsTo` / `HasMany` | Extremos de asociación (no se dibujan como atributos) |

### D.6 Qué conviene omitir en cada diagrama

| Omitir | Por qué |
|---|---|
| `Model`, `Authenticatable`, `FormRequest`, `Component` como clases completas | Son del framework. Basta con un estereotipo (`«Eloquent»`) o una sola clase base con el nombre |
| `email_verified_at`, `remember_token` | No se usan en la app |
| `created_at`, `updated_at` | Opcional: son iguales en todas las tablas; se puede poner una nota "todas con timestamps" |
| Factories, seeders, `AppServiceProvider`, `Icosaedro` | No son dominio ni flujo de negocio |
| Tablas `sessions`, `cache`, `jobs`, `password_reset_tokens`, `migrations` | Infraestructura (A.4) |
| Los atributos derivados (`productos_count`, `movimientos_count`, `entradas`, `salidas`) | Solo existen en consultas concretas. Si se muestran, como nota |

### D.7 Tres vistas sugeridas

1. **Vista de dominio:**
   - Clases: `User`, `Categoria`, `Producto` y `MovimientoInventario`, con atributos, `$fillable` como nota si se quiere, y métodos de B.1 a B.4.
   - Relaciones: R1, R2 y R3 con multiplicidades y nombres de rol.
   - Restricciones: `{stock_actual = Σ…}` y `{inmutable}`.
   - Los tipos `UnidadMedida`, `Rol` y `TipoMovimiento` como referencia.
2. **Vista de aplicación:**
   - Los 9 controladores, los 6 Form Requests y los 2 middleware propios (`UsuarioActivo`, `VerificarRol`).
   - Dependencias `«use»` hacia las clases del dominio (C.5).
   - Herencia de `Controller` y `FormRequest`.
   - Opcionalmente, notas con las rutas (`productos.store`, …) junto a cada método.
3. **Vista de enumeraciones:**
   - `UnidadMedida` (con sus 7 literales y sus métodos).
   - Las implícitas `Rol`, `TipoMovimiento`, `EstadoFiltroProducto`, `GrupoBusqueda` y `DestinoVolver` (B.6), marcadas como "implícitas (no son clases PHP)".

### D.8 Nombres que pueden confundir

- La **clase** es `User` y la **tabla** es `users`, pero la FK se llama `usuario_id`, el método `MovimientoInventario::usuario()` y las rutas y controlador usan `usuarios` (DIF-02). En el diagrama se puede mostrar `User` con la nota «tabla `users`».
- `MovimientoInventario` (clase) ↔ `movimientos_inventario` (tabla, fijada con `$table`).
- "Encargado de inventario" (etiqueta) ↔ `encargado` (valor guardado).
