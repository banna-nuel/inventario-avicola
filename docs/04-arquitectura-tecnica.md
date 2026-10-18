# Sistema de Gestión de Inventario — Arquitectura Técnica

**Versión:** 1.0
**Stack:** Laravel 12 + MySQL + Blade + Tailwind CSS v4 (standalone CLI, sin npm)

---

## 1. Requisitos previos (Windows 11)

Ya tenés instalado: **XAMPP** (PHP + MySQL + Apache), **Composer** y el instalador de **Laravel**. No se usa npm en este proyecto.

- **Tailwind CSS Standalone CLI**: se descarga un único ejecutable (sin npm, sin node_modules) desde los releases oficiales de Tailwind, específico para Windows (`tailwindcss-windows-x64.exe`). Se guarda dentro del proyecto, por ejemplo como `tailwindcss.exe` en la raíz.
- Asegurate de que el servicio **MySQL de XAMPP** esté iniciado desde el panel de control de XAMPP antes de trabajar.

## 2. Crear el proyecto

Desde la terminal (CMD, PowerShell o la que uses), dentro de la carpeta donde quieras el proyecto:

```bash
composer create-project laravel/laravel inventario-avicola
cd inventario-avicola
```

Configurar `.env` con los datos de MySQL de XAMPP (por defecto: usuario `root`, sin contraseña, host `127.0.0.1`, puerto `3306`), y crear la base de datos vacía desde phpMyAdmin. Luego:

```bash
php artisan migrate
```

**Tailwind (sin npm):** colocá el ejecutable `tailwindcss.exe` en la raíz del proyecto, creá un archivo `resources/css/input.css` con:

```css
@import "tailwindcss";
```

> **Tailwind v4:** no existe `tailwind.config.js` ni el comando `init`; Tailwind detecta solo en qué archivos buscar clases, y la personalización (colores, fuentes) se hace con `@theme` dentro de `input.css`. No escanea `vendor/` (está en `.gitignore`).

Y corré esto mientras programás (queda compilando en segundo plano cada vez que guardás un cambio):

```bash
.\tailwindcss.exe -i resources\css\input.css -o public\css\app.css --watch
```

En tu layout Blade, enlazás el resultado directamente:

```html
<link rel="stylesheet" href="{{ asset('css/app.css') }}">
```

Para producción, se corre una vez con la bandera `--minify` en lugar de `--watch`.

**Autenticación (sin Breeze):** en vez de instalar un paquete, armamos un `AuthController` propio usando el sistema de autenticación que Laravel ya trae integrado (`Auth::attempt()`), con vistas de login escritas a mano en Blade + Tailwind. Lo construimos juntos cuando lleguemos a ese paso — es la misma lógica que usan Breeze o Fortify por dentro, solo que la escribís vos y la entendés completa.

## 3. Estructura de carpetas (lo que vas a usar)

```
inventario-avicola/
├── app/
│   ├── Http/Controllers/
│   │   ├── ProductoController.php
│   │   ├── CategoriaController.php
│   │   ├── MovimientoController.php
│   │   └── UsuarioController.php
│   └── Models/
│       ├── Producto.php
│       ├── Categoria.php
│       ├── MovimientoInventario.php
│       └── User.php  (viene por defecto, es tu tabla usuarios)
├── database/
│   └── migrations/
│       ├── xxxx_create_categorias_table.php
│       ├── xxxx_create_productos_table.php
│       └── xxxx_create_movimientos_inventario_table.php
├── resources/
│   └── views/
│       ├── layouts/
│       │   └── app.blade.php       (plantilla base: sidebar + header)
│       ├── productos/
│       │   ├── index.blade.php     (listado)
│       │   ├── create.blade.php    (formulario nuevo)
│       │   └── edit.blade.php
│       ├── movimientos/
│       │   ├── index.blade.php     (historial)
│       │   └── create.blade.php    (registrar entrada/salida)
│       └── dashboard.blade.php
└── routes/
    └── web.php
```

## 4. Convención de capas (MVC)

| Capa | Responsabilidad | Ejemplo |
|---|---|---|
| **Model** | Representa una tabla, define relaciones | `Producto::class` tiene `belongsTo(Categoria::class)` |
| **Controller** | Recibe la petición, valida, llama al Model, devuelve una vista | `ProductoController@store` valida y crea el producto |
| **View (Blade)** | Solo muestra HTML/datos, sin lógica de negocio | `productos/index.blade.php` |
| **Route** | Conecta una URL con un método del Controller | `Route::resource('productos', ProductoController::class)` |

**Regla simple:** la lógica de negocio (validar stock, actualizar cantidades) va en el Controller o en el Model — nunca en la vista Blade.

## 5. Rutas principales (`routes/web.php`)

```php
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);

    Route::resource('productos', ProductoController::class)
        ->middleware('role:admin');

    Route::resource('categorias', CategoriaController::class)
        ->middleware('role:admin');

    Route::get('/movimientos', [MovimientoController::class, 'index']);
    Route::get('/movimientos/nuevo', [MovimientoController::class, 'create']);
    Route::post('/movimientos', [MovimientoController::class, 'store']);

    Route::resource('usuarios', UsuarioController::class)
        ->middleware('role:admin');
});
```

*(El middleware `role:admin` es algo que armamos juntos cuando lleguemos a permisos — controla que solo el Administrador acceda a esas rutas.)*

## 6. Orden de desarrollo sugerido

1. Migraciones y Models (con sus relaciones)
2. Autenticación con roles (login manual + campo `rol`)
3. CRUD de Categorías (el más simple, para practicar el patrón)
4. CRUD de Productos
5. Registro de Movimientos (entrada/salida) + actualización de stock en transacción
6. Dashboard con indicadores y alertas de stock bajo
7. Historial filtrable
8. Gestión de Usuarios
9. Reportes

Este orden va de lo más simple a lo más complejo, y cada paso se apoya en el anterior — así vas practicando el patrón CRUD de Laravel antes de llegar a la parte con más lógica (movimientos y transacciones).
