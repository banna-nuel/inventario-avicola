# PROMPT — Inventario Avícola v1 (para Claude Code)

> **Antes de lanzar (lo hace el usuario):** copiar los 4 documentos de la documentación a `docs/` dentro del repo, pegar la URL de Figma abajo, y dejar el working tree limpio (todo commiteado).
>
> **FIGMA_URL:** `https://www.figma.com/make/aJXyudVhEGlSiqnBRbUAx4/Collections-App?t=pEwTczZ1nYwFFBil-1`
>
> **Cómo lanzarlo:** abrir `claude` en la raíz del proyecto y escribir: *"Lee PROMPT-V1-claude-code.md completo y empieza por la Fase 0."*

---

## 0. Rol y forma de trabajar

- Eres el **agente de desarrollo** del proyecto. Escribes el código y las pruebas. Otro agente (Claude en el chat) planifica, y el dueño del proyecto (Bannanuel) decide.
- Idioma: respuestas, mensajes de commit e interfaz de usuario en **español**. Respeta la nomenclatura ya existente (tablas y columnas en español `snake_case`; clases como `Categoria`, `Producto`, `MovimientoInventario`).
- Bannanuel está **aprendiendo Laravel y PHP**. Escribe código simple e idiomático, sin sobreingeniería ni abstracciones innecesarias, con comentarios breves en español solo donde la intención no sea obvia. Al cerrar cada fase entrega un resumen de 3–6 líneas: qué hiciste, qué conceptos de Laravel usaste y dónde mirar en el `git diff`.
- Trabaja **por fases** (sección 6). En cada fase: explorar → plan corto → implementar → probar → commit. No avances si las pruebas de la fase actual fallan.
- **Puntos de control** (detente y espera el "ok" del usuario): fin de Fase 0, fin de Fase 1, fin de Fase 5 y entrega final. En las demás fases continúa sin parar y deja el resumen al terminar cada una.
- Si algo es ambiguo o choca con lo documentado, **pregunta** en vez de inventar requisitos.
- Si no hay `FIGMA_URL`, pídela antes de empezar la Fase 1.

## 1. Restricciones innegociables

**Stack**
- Laravel 12, PHP 8.2+, MySQL (XAMPP), Blade, Tailwind CSS **v4.3.x standalone CLI**, JavaScript vanilla.
- **Prohibido npm/yarn/pnpm, Node packages y Vite.** No ejecutes `npm install` bajo ningún motivo.
- Tailwind: el ejecutable `tailwindcss.exe` está en la raíz y está en `.gitignore`. Compilar con `.\tailwindcss.exe -i resources\css\input.css -o public\css\app.css` (`--watch` en una terminal aparte durante el desarrollo, `--minify` para la entrega). `input.css` usa `@import "tailwindcss";`. **No existe `tailwind.config.js` ni comando `init` en v4**: la personalización va en `input.css` con `@theme`.
- Tailwind v4 **no escanea `vendor/`** (está en `.gitignore`). Si usas vistas del framework (por ejemplo la paginación), publícalas a `resources/views/vendor/` o agrega un `@source` explícito en `input.css`; si no, quedan sin estilos.
- JS externo: solo por CDN con **versión fija** y solo si hace falta (Three.js en la Fase 10). Íconos: SVG inline. Fuentes: si la del Figma no es una fuente del sistema, autoalójala en `public/fonts/`.
- Composer: permitido `laravel-lang/common` (traducciones al español). Cualquier otro paquete requiere preguntar primero.

**Seguridad y sistema**
- Prohibido editar el registro de Windows, variables de entorno del sistema o el PATH, instalar software global, o ejecutar comandos que afecten fuera de la carpeta del proyecto.
- Prohibido borrar archivos fuera del proyecto, `git push --force` y `git reset --hard` sin permiso explícito. Antes de borrar cualquier archivo existente del usuario, pregunta.
- No leas ni imprimas el `.env` completo y no lo subas a Git. Si agregas variables, actualiza `.env.example` y avisa al usuario.

**Base de datos**
- `inventario_avicola` es la base de **desarrollo**: `php artisan migrate:fresh --seed` está permitido sobre ella. No toques ninguna otra base.
- Las pruebas automáticas usan una **base separada** (por ejemplo `inventario_avicola_test`, o SQLite en memoria si la extensión está activa). Nunca corras tests contra la base de desarrollo.
- No edites migraciones ya existentes salvo para corregir un error. Los cambios de esquema van en **migraciones nuevas**, para que el usuario pueda sincronizar con su otra PC corriendo solo `php artisan migrate`.

**Git**
- Trabaja en la rama `feature/v1`. Commits pequeños (uno por feature o por fase), mensajes en español. **No hagas push**: lo hace el usuario.

**Entorno:** Windows 11, comandos compatibles con PowerShell, MySQL de XAMPP con usuario `root` sin contraseña, servidor con `php artisan serve` en `http://127.0.0.1:8000`.

## 2. Producto y documentación

Sistema web de **control de inventario para una empresa avícola**. Documentación en `docs/`:
`01-requerimientos.md`, `02-casos-de-uso.md`, `03-modelo-datos.md`, `04-arquitectura-tecnica.md`.

**Los docs 03 y 04 tienen partes desactualizadas. En cualquier conflicto mandan este prompt y el código existente.** Diferencias conocidas:
- La tabla de usuarios real es **`users`** (no `usuarios`), con las columnas extra `rol` (`admin`/`encargado`) y `activo`.
- **No se usa Laravel Breeze ni npm.** La autenticación es manual (`AuthController` con `Auth::attempt()`).
- La paleta oscura del doc 01 fue **descartada** (ver sección 3, Diseño).

## 3. Diseño (el Figma manda)

- Fuente de verdad visual: el archivo de Figma, accesible con el **MCP de Figma**. Lista los frames, mapéalos a rutas, y extrae colores, tipografías, espaciados, radios y sombras.
- Dirección: **extremadamente minimalista, tema claro, monocromático** (blanco, negro y grises). El naranja `#F5A623` se usa solo como acento mínimo (foco de inputs, ítem activo de la navegación, badge de stock bajo). Barra de navegación **flotante tipo píldora**, fija abajo, con Home / Dashboard / Search.
- Implementa los tokens del Figma como variables en `@theme` dentro de `input.css` y úsalos por nombre. Evita repetir colores hexadecimales sueltos por las vistas.
- Donde el Figma no tenga una pantalla (formularios CRUD, tablas, reportes, estados vacíos), **extiéndela con el mismo lenguaje visual**. Si hay conflicto entre tus skills de diseño y el Figma, gana el Figma.
- Construye componentes Blade reutilizables en `resources/views/components/` (botón, input, tarjeta, tabla, badge, alerta/flash, paginación, estado vacío).
- Requisitos transversales: responsive de 320 px a 1920 px, contraste mínimo WCAG AA (4.5:1), máximo 7 acciones primarias por pantalla, feedback visual en menos de 200 ms (estados `hover`/`focus`, botón deshabilitado y "cargando" al enviar, mensajes flash).

## 4. Estado actual del repositorio (ya hecho: no rehacer)

- Laravel 12 funcionando, MySQL conectado, Tailwind v4 standalone compilando.
- Migraciones corridas: `users` (+`rol`, +`activo`), `categorias`, `productos`, `movimientos_inventario`.
- Modelos con relaciones: `Categoria`, `Producto`, `MovimientoInventario` (`User` por defecto con `rol` y `activo` en `$fillable`).
- Login y logout manuales (`AuthController`); rutas `/login`, `/logout` públicas y `/home`, `/dashboard`, `/search` dentro del grupo `auth`. El login redirige a `/home`.
- `resources/views/layouts/app.blade.php` con la navegación en el footer; `home`, `dashboard` y `search` lo extienden (aún son esqueletos). `login.blade.php` está fuera del layout.
- `CategoriaController`: `index`, `create` y `store` hechos. Faltan `edit`, `update` y `destroy`. Ruta: `Route::resource('categorias', ...)->except(['show'])`.
- Usuario de prueba creado a mano: `admin@test.com` / `12345678`.

**Pendientes conocidos que debes resolver en la Fase 0:**
1. En `/categorias` las categorías creadas existen en la base de datos pero no se ven en el listado: **diagnostica y corrige**.
2. Mensajes de validación en inglés: instala `laravel-lang/common`, ejecuta `php artisan lang:add es` y fija `APP_LOCALE=es`.
3. Zona horaria `America/Bogota` y `SESSION_LIFETIME=30` (RNF-04: la sesión expira a los 30 minutos). Actualiza `.env.example` y avisa al usuario para que ajuste su `.env`.

## 5. Alcance de la v1, reglas de negocio y permisos

**Incluye:** autenticación con roles, CRUD de categorías y productos, movimientos de inventario con alerta de stock bajo, Home, Dashboard, Search, historial filtrable, gestión de usuarios y reportes en pantalla.

**No incluye (queda para versiones futuras):** recuperación de contraseña por correo (RF-02), exportación a PDF/Excel (RF-11), pedidos, entregas, pagos, notificaciones y multi-almacén.

**Reglas de negocio**
- **RN-01** No se registra una salida mayor al `stock_actual` del producto.
- **RN-02** El umbral de stock bajo es **por producto** (`stock_minimo`). Un producto está en stock bajo cuando `stock_actual < stock_minimo`. Impleméntalo una sola vez, como *scope* del modelo `Producto`.
- **RN-03** Productos y usuarios con historial **no se eliminan**: se desactivan (`activo = false`). Las categorías con productos no se pueden eliminar (mostrar error claro).
- **RN-04** Todo movimiento se registra dentro de una transacción (`DB::transaction`) que bloquea la fila del producto (`lockForUpdate`) y actualiza `stock_actual`.
- **RN-05/06** Permisos según la matriz de abajo.
- **RN-07** No se puede desactivar ni quitar el rol a la única cuenta de administrador activa.
- **RN-08** Cada producto define su propia unidad de medida.
- **RN-09 (decisión de planeación, modificable por el usuario)** Los movimientos son **inmutables**: no se editan ni se eliminan. Una corrección se hace con un movimiento inverso.
- Un producto inactivo no admite nuevos movimientos. La cantidad de un movimiento debe ser mayor que 0.
- Las cantidades son decimales (10,2). **Nunca sumes cantidades de productos con unidades distintas.**

**Permisos**

| Recurso | Administrador | Encargado |
|---|---|---|
| `/home`, `/dashboard`, `/search` | Sí | Sí |
| `/productos` (listado y stock) | Ver y gestionar | Solo ver |
| Crear, editar o desactivar productos | Sí | No |
| `/categorias` | Sí | No |
| `/movimientos` (registrar entrada/salida e historial) | Sí | Sí |
| `/usuarios` | Sí | No |
| `/reportes` | Sí | No |

Implementa los permisos con un middleware `role` (en Laravel 12 se registra en `bootstrap/app.php`). Un usuario sin permiso recibe 403, y los links a lo que no puede usar no se muestran. Un usuario con `activo = false` no puede iniciar sesión. Tras 3 intentos fallidos de login (por email e IP) se bloquea temporalmente con `RateLimiter` y un mensaje en español (CU-01).

## 6. Fases

**Fase 0 — Preparación y diagnóstico** *(punto de control)*
Verifica entorno (`php -v`, Composer, MySQL, compilación de Tailwind, `php artisan test` de base). Crea la rama `feature/v1`. Lee `docs/`. Explora el Figma y lista frames, tokens y pantallas faltantes. Resuelve los 3 pendientes de la sección 4. **Entrega** un reporte corto con hallazgos y el plan, y espera el "ok".

**Fase 1 — Sistema de diseño y layout** *(punto de control)*
Tokens en `@theme`, tipografías, layout base con la navegación flotante, componentes Blade reutilizables, rediseño del login y de los esqueletos de `home`, `dashboard` y `search` según el Figma. **Entrega** capturas con Playwright comparadas con el Figma.

**Fase 2 — Autenticación y roles**
Middleware `role`, bloqueo por intentos fallidos, usuarios inactivos sin acceso, 403 con vista propia, `SESSION_LIFETIME=30`. Pruebas de permisos.

**Fase 3 — Categorías**
Completa el CRUD (`edit`, `update`, `destroy`) con validación única en español, regla de no borrar con productos, mensajes flash y vistas con los componentes.

**Fase 4 — Productos**
CRUD completo (solo admin; el encargado solo ve el listado). Categoría, unidad de medida, `stock_actual` (solo lectura tras crear: cambia únicamente por movimientos), `stock_minimo`. Desactivar en vez de borrar. Listado paginado con búsqueda y filtros por categoría y estado, y badge de stock bajo.

**Fase 5 — Movimientos de inventario y alertas** *(punto de control)*
Formulario de entrada/salida con las reglas RN-01, RN-04 y RN-09, y alerta visual cuando el stock queda bajo el mínimo. Pruebas automáticas obligatorias de transacción, stock insuficiente, producto inactivo y permisos.

**Fase 6 — Home, Dashboard, Search e Historial**
- *Home:* bienvenida y resumen simple.
- *Dashboard (RF-03):* total de productos activos, productos en stock bajo, movimientos de hoy y la lista de productos en alerta.
- *Search:* búsqueda por texto en GET. El encargado busca productos (por nombre o categoría); el administrador busca además categorías y usuarios, con los resultados agrupados.
- *Historial:* `/movimientos` paginado con filtros por producto, tipo y rango de fechas, y acceso desde cada producto.

**Fase 7 — Gestión de usuarios**
CRUD de usuarios (solo admin), asignación de rol, activar/desactivar y la regla RN-07.

**Fase 8 — Reportes (solo admin, en pantalla)**
Stock actual agrupado por categoría (listando cada producto con su unidad, sin sumar unidades distintas), productos con stock bajo y productos con más movimientos en un rango de fechas. Con estilos de impresión sencillos.

**Fase 9 — Datos de demostración, pulido y QA** *(entrega final, punto de control)*
Seeder realista para negocio avícola: 1 admin y 1 encargado (`admin@test.com` y `encargado@test.com`, contraseña `12345678`, solo para desarrollo), categorías y unos 8–10 productos con unidades variadas (libras, kg, unidades), algunos en stock bajo, y unos 30 movimientos de los últimos 14 días **coherentes** con el `stock_actual`. Revisa estados vacíos, errores y 404/403/419. Evita consultas N+1 (`with`, `withCount`) y agrega índices donde corresponda (por ejemplo `movimientos_inventario.fecha`) en una migración nueva. Valida los requisitos transversales de la sección 3.

**Fase 10 — Opcional: icosaedro 3D interactivo (RNF-08)**
**Solo con el visto bueno explícito del usuario**, después de la entrega de la Fase 9. Icosaedro wireframe con Three.js por CDN (versión fija), con rotación lenta continua y reacción al movimiento del mouse, al scroll y al click. Debe respetar `prefers-reduced-motion` y no afectar el rendimiento de los módulos.

## 7. Pruebas y evidencia

- **PHPUnit (Feature tests)** obligatorios para: RN-01, RN-02, RN-03, RN-04, RN-07 y RN-09; permisos por rol; validaciones de categorías y productos; login bloqueado e inactivo.
- **Playwright (MCP):** flujos de punta a punta por rol contra `http://127.0.0.1:8000` con los datos del seeder, y capturas en 320, 768, 1440 y 1920 px comparadas con el Figma.
- **chrome-devtools (MCP):** sin errores en consola, tiempos de carga menores a 3 s (RNF-06) y revisión de contraste.
- No declares una fase terminada sin evidencia: salida de `php artisan test`, y capturas cuando haya cambios visuales.

## 8. Definición de terminado de la v1

- [ ] Los dos roles funcionan con la matriz de permisos (probado con Playwright y PHPUnit).
- [ ] Categorías, productos, movimientos, usuarios, Search, Dashboard e Historial funcionan de punta a punta.
- [ ] El stock nunca queda negativo ni desincronizado del historial (probado con tests de transacción).
- [ ] La alerta de stock bajo se ve en el dashboard, en el listado de productos y al registrar una salida.
- [ ] Interfaz 100 % en español (incluidos los mensajes de validación), fiel al Figma, responsive de 320 a 1920 px y con contraste AA.
- [ ] `php artisan test` en verde y `migrate:fresh --seed` funciona desde cero.
- [ ] Rama `feature/v1` con commits ordenados y sin secretos.

## 9. Entrega final

Crea o actualiza `README.md` con: requisitos, cómo levantar el proyecto desde cero (incluido el **portátil**: `composer install`, descargar `tailwindcss.exe`, copiar `.env.example` a `.env` y `php artisan key:generate`, crear la base, `php artisan migrate --seed`, compilar Tailwind, `php artisan serve`), credenciales de demostración, estructura del proyecto, decisiones tomadas y por qué, y lo que queda fuera de la v1. Cierra con un resumen para el usuario: qué está listo, qué quedó pendiente, y los cambios que hiciste respecto a lo documentado.
