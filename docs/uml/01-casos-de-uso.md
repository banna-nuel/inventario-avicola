# UML · 01 — Casos de uso

**Fuentes:** `routes/web.php`, controladores, Form Requests, middleware, vistas Blade, `public/js/app.js` y pruebas.
**Convenciones:** las del archivo `00-contexto-y-reglas.md`. Las reglas (RN, INV) y las diferencias (DIF) están definidas allí.

---

## 0. Priorización (para no dibujar los 30 casos de uso)

Este archivo documenta 30 casos de uso para que ninguna ruta quede sin cubrir. Para el diagrama alcanza con los 15 principales, más 4 de soporte pegados a ellos.

### 0.1 Los 15 casos de uso principales (objetivos de usuario)

| Orden | Óvalo en el diagrama | UC | Actor | Paquete | Por qué es principal |
|---|---|---|---|---|---|
| 1 | Registrar salida de stock | UC-17 | Usuario (ambos roles) | Inventario | Núcleo del negocio: RN-01, RN-04 y la alerta de stock bajo |
| 2 | Registrar entrada de stock | UC-16 | Usuario | Inventario | RF-06; también extiende a "Registrar producto" (stock inicial) |
| 3 | Consultar productos y stock | UC-07 | Usuario | Consulta | CU-06: stock, unidad y estado |
| 4 | Consultar historial de movimientos | UC-08 | Usuario | Consulta | RF-08: filtros por producto, tipo y fechas |
| 5 | Ver Dashboard | UC-05 | Usuario | Consulta | RF-03 y RF-07: indicadores y alertas |
| 6 | Iniciar sesión | UC-01 | Usuario (sin sesión) | Autenticación | RF-01 |
| 7 | Registrar producto | UC-09 | Administrador | Catálogo | CU-02 |
| 8 | Editar producto | UC-10 | Administrador | Catálogo | CU-03 |
| 9 | Desactivar o activar producto | UC-11 | Administrador | Catálogo | RN-03. Se dibuja como `<<extend>>` de UC-10 |
| 10 | Gestionar categorías | UC-12 a UC-15 (agrupados) | Administrador | Catálogo | RF-05: consultar, registrar, editar y eliminar en un solo óvalo |
| 11 | Registrar usuario | UC-21 | Administrador | Administración | CU-07 |
| 12 | Editar usuario | UC-22 | Administrador | Administración | CU-07 y RN-07 (no quitarse el rol) |
| 13 | Desactivar o activar usuario | UC-23 | Administrador | Administración | RN-03 y RN-07. Se dibuja como `<<extend>>` de UC-22 |
| 14 | Generar reporte | UC-24 a UC-26 (agrupados) | Administrador | Reportes | RF-10: stock por categoría, stock bajo y más movidos |
| 15 | Buscar | UC-06 | Usuario | Consulta | RF-13. El administrador ve más grupos (nota) |

### 0.2 De soporte: se dibujan pegados a un principal

| UC | Relación | Con | Condición |
|---|---|---|---|
| UC-18 Validar stock disponible | `<<include>>` | UC-17 | Siempre, en toda salida |
| UC-19 Mostrar alerta de stock bajo | `<<extend>>` | UC-16 y UC-17 | El producto queda o sigue bajo su mínimo |
| UC-02 Bloquear acceso por intentos fallidos | `<<extend>>` | UC-01 | 3 intentos fallidos por correo e IP en 60 s |
| UC-27 Imprimir reporte | `<<extend>>` | Generar reporte | El administrador pulsa "Imprimir" |

UC-16 también extiende a UC-09 (`<<extend>>` [`stock_inicial > 0`]). Es la misma flecha de §5.1, entre dos óvalos principales.

### 0.3 Se pueden omitir del diagrama

| UC | Por qué se puede omitir | Qué poner en su lugar |
|---|---|---|
| UC-28, UC-29, UC-30 (transversales) | Son mecanismos del sistema (middleware), no objetivos de un actor | Una nota en el límite del sistema: "todo caso de uso con sesión verifica sesión y cuenta activa; los de administrador, además, el rol (403)" |
| UC-03 Cerrar sesión, UC-04 Ver Inicio | Son navegación, de poco valor en el diagrama | Nada, o dibujarlos si sobra espacio |
| UC-12 Consultar categorías, UC-13, UC-14, UC-15 | Ya están dentro de "Gestionar categorías" | El óvalo agrupado |
| UC-20 Consultar usuarios | Es el listado desde el que se llega a UC-21 a UC-23 | Nada, o agrupar UC-20 a UC-22 como "Gestionar usuarios" |
| UC-24, UC-25, UC-26 por separado | Ya están en "Generar reporte" | El óvalo agrupado (o una generalización, §5.2) |

### 0.4 Agrupación por paquete

| Paquete | Principales | Soporte | Omitibles |
|---|---|---|---|
| Autenticación | UC-01 | UC-02 | UC-03, UC-28, UC-29 |
| Consulta | UC-05, UC-06, UC-07, UC-08 | — | UC-04 |
| Inventario | UC-16, UC-17 | UC-18, UC-19 | — |
| Catálogo | UC-09, UC-10, UC-11, Gestionar categorías (UC-12 a UC-15) | (UC-16 extiende a UC-09) | — |
| Administración | UC-21, UC-22, UC-23 | — | UC-20, UC-30 |
| Reportes | Generar reporte (UC-24 a UC-26) | UC-27 | — |

**Resultado:** 15 óvalos principales y 4 de soporte, con 2 actores (Administrador y Encargado de inventario), o 3 si se usa el actor abstracto *Usuario* (§1.1 y `00` §3.2).

---

## 1. Límite del sistema y paquetes

### 1.1 Límite del sistema

- **Rectángulo:** "Inventario Avícola (aplicación web)".
- **Adentro:** los casos de uso UC-01…UC-30.
- **Afuera:**
  - Los actores **Administrador** y **Encargado de inventario**, y opcionalmente el actor abstracto *Usuario*, del que ambos heredan (ver `00` §3.2).
  - No hay actores secundarios: el sistema no habla con otros sistemas.
- **Opcional:** el navegador y su diálogo de impresión pueden dibujarse como nota fuera del límite (UC-27).
- **Sin caso de uso:**
  - RNF-08 (icosaedro).
  - Las rutas del framework `/up` y `storage/{path}`.

### 1.2 Paquetes sugeridos

| Paquete | Casos de uso | Actores |
|---|---|---|
| Autenticación | UC-01 Iniciar sesión · UC-02 Bloquear acceso por intentos fallidos · UC-03 Cerrar sesión | Usuario (ambos roles) |
| Consulta | UC-04 Ver Inicio · UC-05 Ver Dashboard · UC-06 Buscar · UC-07 Consultar productos y stock · UC-08 Consultar historial de movimientos | Usuario (ambos roles) |
| Catálogo | UC-09 Registrar producto · UC-10 Editar producto · UC-11 Desactivar o activar producto · UC-12 Consultar categorías · UC-13 Registrar categoría · UC-14 Editar categoría · UC-15 Eliminar categoría | Administrador |
| Inventario | UC-16 Registrar entrada de stock · UC-17 Registrar salida de stock · UC-18 Validar stock disponible · UC-19 Mostrar alerta de stock bajo | Usuario (ambos roles) |
| Administración | UC-20 Consultar usuarios · UC-21 Registrar usuario · UC-22 Editar usuario · UC-23 Desactivar o activar usuario | Administrador |
| Reportes | UC-24 Generar reporte de stock por categoría · UC-25 Generar reporte de stock bajo · UC-26 Generar reporte de productos más movidos · UC-27 Imprimir reporte | Administrador |
| Transversal (opcional en el diagrama) | UC-28 Verificar sesión y cuenta activa · UC-29 Expulsar cuenta desactivada · UC-30 Verificar rol de administrador | — (los incluye el sistema) |

### 1.3 Matriz actor × caso de uso

**A** = Administrador · **E** = Encargado de inventario · "—" = no participa (403 o no aplica).

| UC | A | E | UC | A | E | UC | A | E |
|---|---|---|---|---|---|---|---|---|
| UC-01 | ✔ | ✔ | UC-11 | ✔ | — | UC-21 | ✔ | — |
| UC-02 | ✔ | ✔ | UC-12 | ✔ | — | UC-22 | ✔ | — |
| UC-03 | ✔ | ✔ | UC-13 | ✔ | — | UC-23 | ✔ | — |
| UC-04 | ✔ | ✔ | UC-14 | ✔ | — | UC-24 | ✔ | — |
| UC-05 | ✔ | ✔ | UC-15 | ✔ | — | UC-25 | ✔ | — |
| UC-06 | ✔ (3 grupos) | ✔ (solo productos) | UC-16 | ✔ | ✔ | UC-26 | ✔ | — |
| UC-07 | ✔ (con acciones) | ✔ (solo ver) | UC-17 | ✔ | ✔ | UC-27 | ✔ | — |
| UC-08 | ✔ | ✔ | UC-18 | (incluido) | (incluido) | UC-28 | (incluido) | (incluido) |
| UC-09 | ✔ | — | UC-19 | (extensión) | (extensión) | UC-29 | (extensión) | (extensión) |
| UC-10 | ✔ | — | UC-20 | ✔ | — | UC-30 | (incluido) | (incluido; da 403) |

Fuente: `RutasTest::matriz()` y `00` §3.3.

---

## 2. Índice de casos de uso

| UC | Nombre | Paquete | Actor principal | Tipo | Rutas | docs/02 |
|---|---|---|---|---|---|---|
| UC-01 | Iniciar sesión | Autenticación | Usuario (sin sesión) | Objetivo del actor | `raiz`, `login`, `login.entrar` | CU-01 |
| UC-02 | Bloquear acceso por intentos fallidos | Autenticación | (extiende UC-01) | Extensión | `login.entrar` | CU-01 (flujo alternativo) |
| UC-03 | Cerrar sesión | Autenticación | Usuario | Objetivo | `logout` | nuevo |
| UC-04 | Ver Inicio | Consulta | Usuario | Objetivo | `home` (y `raiz`) | nuevo |
| UC-05 | Ver Dashboard | Consulta | Usuario | Objetivo | `dashboard` | nuevo (RF-03) |
| UC-06 | Buscar | Consulta | Usuario | Objetivo | `search` | nuevo |
| UC-07 | Consultar productos y stock | Consulta | Usuario | Objetivo | `productos.index` | CU-06 (parte 1) |
| UC-08 | Consultar historial de movimientos | Consulta | Usuario | Objetivo | `movimientos.index` | CU-06 (parte 2) |
| UC-09 | Registrar producto | Catálogo | Administrador | Objetivo | `productos.create`, `productos.store` | CU-02 |
| UC-10 | Editar producto | Catálogo | Administrador | Objetivo | `productos.edit`, `productos.update` | CU-03 |
| UC-11 | Desactivar o activar producto | Catálogo | Administrador | Extensión de UC-10 | `productos.cambiar-estado` | CU-03 (flujo alternativo) |
| UC-12 | Consultar categorías | Catálogo | Administrador | Objetivo | `categorias.index` | nuevo (RF-05) |
| UC-13 | Registrar categoría | Catálogo | Administrador | Objetivo | `categorias.create`, `categorias.store` | nuevo (RF-05) |
| UC-14 | Editar categoría | Catálogo | Administrador | Objetivo | `categorias.edit`, `categorias.update` | nuevo (RF-05) |
| UC-15 | Eliminar categoría | Catálogo | Administrador | Objetivo | `categorias.destroy` | nuevo (RF-05) |
| UC-16 | Registrar entrada de stock | Inventario | Usuario | Objetivo (y extensión de UC-09) | `movimientos.create`, `movimientos.store` | CU-04 |
| UC-17 | Registrar salida de stock | Inventario | Usuario | Objetivo | `movimientos.create`, `movimientos.store` | CU-05 |
| UC-18 | Validar stock disponible | Inventario | (incluido por UC-17) | Inclusión | `movimientos.store` | CU-05 (paso 2) |
| UC-19 | Mostrar alerta de stock bajo | Inventario | (extiende UC-16 y UC-17) | Extensión | `movimientos.store` | CU-05 (paso 4) |
| UC-20 | Consultar usuarios | Administración | Administrador | Objetivo | `usuarios.index` | CU-07 |
| UC-21 | Registrar usuario | Administración | Administrador | Objetivo | `usuarios.create`, `usuarios.store` | CU-07 |
| UC-22 | Editar usuario | Administración | Administrador | Objetivo | `usuarios.edit`, `usuarios.update` | CU-07 |
| UC-23 | Desactivar o activar usuario | Administración | Administrador | Extensión de UC-22 | `usuarios.cambiar-estado` | CU-07 (flujo alternativo) |
| UC-24 | Generar reporte de stock por categoría | Reportes | Administrador | Objetivo | `reportes.index`, `reportes.stock-por-categoria` | CU-08 |
| UC-25 | Generar reporte de stock bajo | Reportes | Administrador | Objetivo | `reportes.index`, `reportes.stock-bajo` | CU-08 |
| UC-26 | Generar reporte de productos más movidos | Reportes | Administrador | Objetivo | `reportes.index`, `reportes.mas-movidos` | CU-08 |
| UC-27 | Imprimir reporte | Reportes | (extiende UC-24, UC-25 y UC-26) | Extensión | (sin ruta: JavaScript) | CU-08 paso 3 (reemplaza "exportar") |
| UC-28 | Verificar sesión y cuenta activa | Transversal | (incluido por todo UC con sesión) | Inclusión | todas las del grupo `auth` + `activo` | nuevo |
| UC-29 | Expulsar cuenta desactivada | Transversal | (extiende UC-28) | Extensión | todas las del grupo `auth` + `activo` | nuevo |
| UC-30 | Verificar rol de administrador | Transversal | (incluido por los UC de administrador) | Inclusión | todas las del grupo `role:admin` | nuevo (RN-05) |

---

## 3. Flujos comunes a todo caso de uso con sesión

Para no repetirlos en cada caso de uso, se citan como **FC-n**. Aplican a UC-04…UC-26.

| ID | Causa | Respuesta del sistema | Fuente |
|---|---|---|---|
| FC-1 | No hay sesión o expiró por inactividad (`SESSION_LIFETIME`) | Middleware `auth`: 302 → `/login`. Laravel guarda la URL pedida (si era GET) para volver a ella después del login (UC-01 paso 8) | `bootstrap/app.php:22`; `RutasTest::test_un_invitado_va_al_login_desde_cualquier_ruta_protegida` |
| FC-2 | La cuenta fue desactivada mientras tenía la sesión abierta | UC-29: cierra la sesión y manda al login con el aviso | `UsuarioActivo::handle` |
| FC-3 | Un encargado entra a una función de administrador | 403, con la vista `errors/403` en español (UC-30) | `VerificarRol::handle`; `PermisosTest::test_el_403_tiene_vista_propia_en_espanol` |
| FC-4 | Token CSRF ausente o vencido en un POST, PUT, PATCH o DELETE | 419, con la vista `errors/419` y el enlace "Iniciar sesión" | `resources/views/errors/419.blade.php`; `PermisosTest::test_el_419_tiene_vista_propia_en_espanol` |
| FC-5 | El id de la URL no existe (`{producto}`, `{categoria}`, `{usuario}`) | 404, con la vista `errors/404`. Ocurre **antes** de FC-2 y FC-3 por el orden del middleware (`00` §3.3, V-09) | `SubstituteBindings`; `CategoriaTest::test_una_categoria_que_no_existe_da_404`; `PermisosTest::test_el_404_tiene_vista_propia_en_espanol` |
| FC-6 | Error inesperado del servidor | 500, con la vista `errors/500` en español y sin detalles internos | `SeguridadTest::test_un_error_del_servidor_se_muestra_en_espanol_y_sin_detalles` |
| FC-7 | Doble clic al enviar un formulario | El navegador deshabilita el botón y muestra el texto de carga ("Guardando…", "Registrando…"). Es solo del lado del navegador | `public/js/app.js:3-38` |

Los errores de validación de un formulario (Form Request) siempre devuelven:
- **302** a la pantalla anterior, salvo que el Form Request defina otra (`$redirectRoute`).
- Los **errores junto a cada campo**.
- **Lo que se había escrito**, excepto la contraseña (`AutenticacionTest::test_un_error_conserva_el_correo_pero_no_la_contrasena`).

---

## 4. Casos de uso

### Paquete Autenticación

#### UC-01 Iniciar sesión

| Campo | Valor |
|---|---|
| Actor principal | Usuario (Administrador o Encargado de inventario) **sin sesión** |
| Actores secundarios | Ninguno |
| docs/02 | CU-01 |
| Disparador | Abrir `/` (`raiz`, que redirige a `/home` y, sin sesión, a `/login`), abrir `/login` (`login`), o cualquier ruta protegida sin sesión (FC-1). Botón **"Entrar"** del formulario: `POST /login` (`login.entrar`) |
| Precondiciones | Sin sesión iniciada (middleware `guest`). La persona tiene una cuenta |
| Postcondiciones (éxito) | Sesión autenticada con un ID de sesión nuevo. Se borra el contador de intentos de la clave *correo + IP*. Redirige a la URL protegida pedida antes o a Inicio (`/home`) |
| Postcondiciones (fallo) | Sigue sin sesión. Vuelve a `/login` con el error junto al campo correo y con el correo escrito (nunca la contraseña) |
| Reglas | RN-18, RN-19, RNF-04, RNF-05, INV-10 |
| Rutas y controlador | `raiz` (closure en `routes/web.php:26`); `login` → `AuthController::showLoginForm`; `login.entrar` → `AuthController::login` |
| Pruebas | `AutenticacionTest::test_inicia_sesion_y_va_a_home`, `test_los_mensajes_de_validacion_salen_en_espanol`, `test_un_error_conserva_el_correo_pero_no_la_contrasena`, `test_un_usuario_inactivo_no_puede_iniciar_sesion`, `test_con_sesion_iniciada_el_login_redirige_a_home`; `ExampleTest::test_la_raiz_redirige_a_home`; `InterfazTest::test_el_login_esta_en_espanol_y_tiene_etiquetas`, `test_el_error_de_credenciales_se_muestra_junto_al_campo`; `SeguridadTest::test_el_login_con_error_vuelve_al_login_aunque_el_referer_sea_externo`; `BaseDeDatosTest::test_las_cuentas_de_demostracion_inician_sesion` |

**Flujo principal**
1. El usuario abre `/login`. El sistema muestra el formulario: "Correo electrónico", "Contraseña" y el botón "Entrar". También muestra el icosaedro decorativo (RNF-08).
2. El usuario escribe correo y contraseña y pulsa "Entrar". El navegador envía el formulario con el token CSRF; el botón se deshabilita y muestra "Entrando…".
3. El sistema valida el formato: correo obligatorio con formato válido y contraseña obligatoria (`$request->validate`).
4. El sistema arma la clave de bloqueo con el correo en minúsculas y la IP, y comprueba que no esté bloqueada (UC-02).
5. El sistema busca la cuenta por correo y compara la contraseña con el hash **sin iniciar la sesión todavía** (`Auth::validate`).
6. El sistema comprueba que la cuenta esté activa (`activo = true`).
7. El sistema borra el contador de intentos de esa clave (`RateLimiter::clear`), inicia la sesión (`Auth::login`) y regenera el ID de sesión.
8. El sistema redirige a la URL protegida que se había pedido o, si no había, a Inicio (UC-04).

**Flujos alternativos**
- **A1 (paso 1), ya tiene sesión:** `GET` o `POST /login` responden 302 → `/home` (middleware `guest`, `bootstrap/app.php:23`).
- **A2 (paso 1), entra por la raíz:** `GET /` responde 302 → `/home`; sin sesión, 302 → `/login`.

**Flujos de error**
- **E1 (paso 3), campos vacíos o correo con formato inválido:**
  - 302 a la URL anterior (normalmente `/login`) con los errores por campo, por ejemplo "El campo correo electrónico es obligatorio." (traducciones de `laravel-lang`).
  - **No** cuenta como intento fallido. Ver V-10.
- **E2 (paso 4), clave bloqueada:** UC-02.
- **E3 (paso 5), correo inexistente o contraseña incorrecta:**
  - Suma un intento a la clave; el contador vence 60 s después del primer fallo.
  - 302 → `/login` con "Estas credenciales no coinciden con nuestros registros."
- **E4 (paso 6), cuenta desactivada con la contraseña correcta:**
  - 302 → `/login` con "Tu cuenta está desactivada. Habla con un administrador."
  - No suma intento ni borra el contador.
- **E5, token CSRF vencido:** FC-4.

#### UC-02 Bloquear acceso por intentos fallidos (`<<extend>>` de UC-01)

| Campo | Valor |
|---|---|
| Actor | El mismo de UC-01 (es una extensión) |
| docs/02 | CU-01, flujo alternativo |
| Punto de extensión en UC-01 | Paso 4, "verificar bloqueo" |
| Condición | `RateLimiter::tooManyAttempts($clave, 3)`: la clave *correo + IP* acumula 3 intentos fallidos y la ventana de 60 s sigue vigente |
| Postcondición | Sigue sin sesión. No se comprueba la contraseña (aunque sea correcta). El bloqueo dura hasta que vence la ventana de 60 s contada desde el primer fallo; después, el contador vuelve a 0 |
| Reglas | RN-18 |
| Fuente | `AuthController::login:29-40` (`MAX_INTENTOS = 3`, `SEGUNDOS_BLOQUEO = 60`) |
| Pruebas | `AutenticacionTest::test_tras_tres_intentos_fallidos_se_bloquea_aunque_la_contrasena_sea_correcta`, `test_el_bloqueo_vence_despues_de_un_minuto`, `test_el_bloqueo_es_por_correo_e_ip`, `test_un_inicio_de_sesion_correcto_reinicia_el_contador` |

**Flujo**
1. En el paso 4 de UC-01, el sistema detecta la clave bloqueada.
2. Calcula los segundos que faltan (`RateLimiter::availableIn`).
3. Responde 302 → `/login` con "Demasiados intentos de acceso. Por favor intente nuevamente en N segundos." y el correo escrito.

**Notas**
- El bloqueo es **por correo + IP**. No quedan bloqueadas:
  - Otra cuenta desde la misma IP.
  - La misma cuenta desde otra IP.
- El contador vive en la tabla `cache` (`CACHE_STORE=database`).

#### UC-03 Cerrar sesión

| Campo | Valor |
|---|---|
| Actor principal | Usuario |
| docs/02 | nuevo (RF-14) |
| Disparador | Botón **"Cerrar sesión"** (ícono de salida) del encabezado de todas las pantallas con sesión: formulario `POST /logout` (`logout`) |
| Precondiciones | Ninguna (la ruta no exige sesión, `routes/web.php:35`) |
| Postcondiciones | Sesión cerrada: se borra la fila de `sessions` y se regenera el token CSRF. El navegador queda en `/login` |
| Rutas y controlador | `logout` → `AuthController::logout` |
| Pruebas | `AutenticacionTest::test_cerrar_sesion` |

**Flujo principal**
1. El usuario pulsa "Cerrar sesión".
2. El navegador envía `POST /logout` con el token CSRF.
3. El sistema cierra la sesión (`Auth::logout`), invalida la sesión y regenera el token.
4. El sistema responde 302 → `/login`.

**Flujos alternativos**
- **A1, sin sesión:** el mismo resultado (302 → `/login`).

**Flujos de error**
- **E1:** FC-4.

### Paquete Consulta

#### UC-04 Ver Inicio

| Campo | Valor |
|---|---|
| Actor principal | Usuario |
| docs/02 | nuevo (RF-12) |
| Disparador | Al terminar UC-01; el título "Inventario Avícola" del encabezado; el ícono "Inicio" de la barra flotante; `GET /` o `GET /home` (`home`) |
| Precondiciones | Sesión con cuenta activa (UC-28) |
| Postcondiciones | Ninguna (solo lectura) |
| Reglas | RN-02, RN-06, RN-20 |
| Rutas y controlador | `home` → `InicioController::__invoke` (vista `home.blade.php`) |
| Pruebas | `InterfazTest::test_el_admin_ve_todas_las_tarjetas_de_acceso`, `test_el_encargado_solo_ve_productos_y_movimientos`; `DashboardTest::test_inicio_muestra_una_linea_discreta_con_los_productos_en_stock_bajo`; `PermisosTest::test_ambos_roles_entran_a_home_dashboard_y_search`; `IcosaedroTest::test_inicio_y_login_cargan_el_modulo_del_mismo_origen`; `RutasTest::test_ningun_enlace_visible_lleva_a_403_ni_404` |

**Flujo principal**
1. El usuario abre Inicio.
2. El sistema cuenta los productos **activos** en stock bajo.
3. El sistema muestra:
   - El icosaedro compacto.
   - "Hola, {nombre}" con el número de accesos.
   - La fecha y el rol.
   - La línea "N productos en stock bajo", que enlaza a `productos.index?estado=stock-bajo`.
   - Las tarjetas de acceso de su rol (`User::enlaces('modulos')`, `config/navegacion.php`):
     - Productos y Movimientos, para los dos roles.
     - Categorías, Usuarios y Reportes, solo para el administrador.

**Flujos alternativos**
- **A1, N = 0:** no se muestra la línea de aviso.
- **A2, elige una tarjeta:** pasa a UC-07, UC-08, UC-12, UC-20 o al índice de reportes (UC-24 a UC-26).

**Flujos de error:** FC-1, FC-2.

#### UC-05 Ver Dashboard

| Campo | Valor |
|---|---|
| Actor principal | Usuario (los dos roles ven los mismos datos) |
| docs/02 | nuevo (RF-03, RF-07) |
| Disparador | Ícono "Dashboard" de la barra flotante: `GET /dashboard` (`dashboard`) |
| Precondiciones | Sesión con cuenta activa |
| Postcondiciones | Ninguna |
| Reglas | RN-02, RN-20, RN-21 |
| Rutas y controlador | `dashboard` → `DashboardController::__invoke` |
| Pruebas | `DashboardTest::test_los_indicadores_son_iguales_para_los_dos_roles`, `test_movimientos_de_hoy_usa_el_dia_calendario_de_bogota`, `test_las_alertas_van_del_mayor_al_menor_faltante_con_acceso_para_registrar_una_entrada`, `test_desde_el_dashboard_la_hoja_llega_con_entrada_y_vuelve_al_dashboard`, `test_sin_alertas_muestra_el_estado_vacio`, `test_el_dashboard_no_hace_una_consulta_por_producto`; `IcosaedroTest::test_el_dashboard_no_tiene_icosaedro` |

**Flujo principal**
1. El usuario abre el Dashboard.
2. El sistema obtiene los productos en alerta (`Producto::enAlerta()`: activos con `stock_actual < stock_minimo`, del mayor al menor faltante y luego por nombre), con su categoría.
3. El sistema cuenta los productos activos.
4. El sistema cuenta los movimientos de hoy: `fecha ≥ hoy 00:00` y `< mañana 00:00`, en hora de Bogotá.
5. El sistema muestra:
   - Tres indicadores: "Productos activos", "Activos en stock bajo" (resaltado si es mayor que 0) y "Movimientos de hoy".
   - La tabla "Productos en alerta", con producto, categoría, stock "de {mínimo} {unidad}" y lo que falta.

**Flujos alternativos**
- **A1, no hay alertas:** estado vacío "Sin alertas por ahora".
- **A2, pulsa "Registrar entrada de {producto}":** pasa a UC-16 con el producto y el tipo `entrada` ya elegidos. Al guardar vuelve al Dashboard (`volver=dashboard`).
- **A3, pulsa el nombre de un producto:** pasa a UC-08, filtrado por ese producto.

**Flujos de error:** FC-1, FC-2.

#### UC-06 Buscar

| Campo | Valor |
|---|---|
| Actor principal | Usuario (los resultados dependen del rol) |
| docs/02 | nuevo (RF-13) |
| Disparador | Ícono "Buscar" de la barra flotante; formulario de búsqueda (Enter): `GET /search?q=&tipo=` (`search`) |
| Precondiciones | Sesión con cuenta activa |
| Postcondiciones | Ninguna |
| Reglas | RN-23, RN-06 |
| Rutas y controlador | `search` → `BusquedaController::__invoke` (privados: `productos()`, `categorias()`, `usuarios()`) |
| Pruebas | `BusquedaTest` (8 pruebas); `InterfazTest::test_la_busqueda_conserva_el_texto_y_filtra_opciones_por_rol` |

**Flujo principal**
1. El usuario abre Buscar. El sistema muestra la barra de búsqueda, los filtros por grupo y el estado "Escribe para buscar".
2. El usuario escribe al menos 2 caracteres y pulsa Enter.
3. El sistema decide qué grupos puede buscar según el rol:
   - Encargado: productos.
   - Administrador: productos, categorías y usuarios.
   - Si `tipo` es uno de esos grupos, busca solo ese.
4. Para cada grupo, el sistema cuenta el total y trae los primeros 10, ordenados por nombre:
   - **Productos:** por nombre o por nombre de su categoría. Incluye inactivos, con la etiqueta "Inactivo" o "Stock bajo".
   - **Categorías:** por nombre, con su número de productos.
   - **Usuarios:** por nombre o correo, con su rol y estado.
5. El sistema muestra los resultados agrupados, cada grupo con su total y el enlace "Ver todos".

**Flujos alternativos**
- **A1, `q` vacío:** "Escribe para buscar", sin consultar la base de datos.
- **A2, `q` de 1 carácter:** "Escribe al menos 2 caracteres", sin consultar la base de datos.
- **A3, sin coincidencias:** "Sin resultados".
- **A4, el encargado envía `tipo=usuarios` o `tipo=categorias`:** se ignora y busca solo productos.
- **A5, "Ver todos":** abre el listado filtrado.
  - UC-07: `productos.index?q=…&estado=todos`.
  - UC-12: `categorias.index?q=…`.
  - UC-20: `usuarios.index?q=…`.
- **A6, clic en un resultado:**
  - Producto: UC-08 (su historial).
  - Categoría: UC-14 (editar).
  - Usuario: UC-22 (editar).

**Flujos de error:** FC-1, FC-2.

#### UC-07 Consultar productos y stock

| Campo | Valor |
|---|---|
| Actor principal | Usuario. El encargado lo ve sin acciones de administrador |
| docs/02 | CU-06 (parte 1) |
| Disparador | Tarjeta "Productos" de Inicio; aviso de stock bajo de Inicio (`estado=stock-bajo`); "Ver todos" de Buscar: `GET /productos?q=&categoria=&estado=&page=` (`productos.index`) |
| Precondiciones | Sesión con cuenta activa |
| Postcondiciones | Ninguna |
| Reglas | RN-02, RN-06, RN-20 |
| Rutas y controlador | `productos.index` → `ProductoController::index` (privados: `listado()`, `filtros()`) |
| Pruebas | `ProductoTest::test_el_listado_muestra_stock_unidad_categoria_y_badge_de_stock_bajo`, `test_por_defecto_se_ocultan_los_inactivos`, `test_filtro_de_stock_bajo_y_por_categoria_y_busqueda`, `test_filtros_mal_escritos_en_la_url_se_ignoran`, `test_el_listado_se_pagina_de_15_en_15`, `test_el_listado_no_hace_una_consulta_por_producto`, `test_el_encargado_ve_el_listado_sin_acciones_de_administrador`, `test_rn02_el_scope_y_el_metodo_de_stock_bajo_coinciden`, `test_la_unidad_se_escribe_en_singular_o_plural` |

**Flujo principal**
1. El usuario abre Productos.
2. El sistema normaliza los filtros e ignora los que lleguen mal escritos.
3. El sistema consulta los productos con su categoría:
   - Por defecto, **solo activos**.
   - `q`: busca por nombre del producto o de su categoría.
   - `categoria`: una categoría.
   - `estado`: `stock-bajo` (activos en stock bajo), `inactivos` o `todos`.
   - Orden por nombre, 15 por página.
4. El sistema muestra la tabla:
   - Nombre, que enlaza al historial.
   - Categoría.
   - Stock con su unidad, en singular o plural.
   - Mínimo.
   - Etiquetas "Stock bajo" (solo productos activos) e "Inactivo".
   - Acciones: "Registrar movimiento" (productos activos, los dos roles) y "Editar" (solo administrador).
   - Botón "+" para un producto nuevo (solo administrador).

**Flujos alternativos**
- **A1, sin resultados con filtros:** "Sin resultados" y el botón "Quitar filtros".
- **A2, no hay productos:**
  - Administrador sin categorías: "Primero crea una categoría" y el botón "Nueva categoría" (UC-13).
  - Administrador con categorías: botón "Nuevo producto" (UC-09).
  - Encargado: solo un mensaje.
- **A3, "Registrar movimiento de {producto}":** pasa a UC-16 o UC-17 con el producto elegido y `volver=productos`.
- **A4, "Editar":** pasa a UC-10.
- **A5, nombre del producto:** pasa a UC-08.

**Flujos de error:** FC-1, FC-2.

#### UC-08 Consultar historial de movimientos

| Campo | Valor |
|---|---|
| Actor principal | Usuario |
| docs/02 | CU-06 (parte 2) |
| Disparador | Ver los detalles debajo de la tabla |
| Precondiciones | Sesión con cuenta activa |
| Postcondiciones | Ninguna. Los movimientos no se editan ni se eliminan (RN-09) |
| Reglas | RN-21, RN-09 |
| Rutas y controlador | `movimientos.index` → `MovimientoController::index` (`FiltrosHistorialRequest`; privado `historial()`) |
| Pruebas | `HistorialTest::test_filtra_por_producto_y_por_tipo`, `test_las_fechas_son_inclusivas_en_hora_de_bogota`, `test_filtros_invalidos_vuelven_al_historial_con_el_error`, `test_la_paginacion_conserva_los_filtros_y_hay_limpiar_filtros`, `test_cada_producto_enlaza_a_su_historial`, `test_el_boton_mas_lleva_el_producto_del_filtro_a_la_hoja`, `test_el_historial_no_hace_una_consulta_por_movimiento`; `MovimientoTest::test_el_historial_va_del_mas_reciente_al_mas_antiguo_y_se_pagina`, `test_sin_movimientos_muestra_el_estado_vacio` |

**Disparador:** `GET /movimientos?producto=&tipo=&desde=&hasta=&page=` (`movimientos.index`), desde:
- La tarjeta "Movimientos" de Inicio.
- El nombre de un producto (en el listado, el Dashboard, Buscar o los reportes).
- "Ver historial (N)" en la hoja "Editar producto".

**Flujo principal**
1. El usuario abre el historial, con o sin filtros.
2. El sistema valida los filtros (`FiltrosHistorialRequest`):
   - `producto`: entero y existente.
   - `tipo`: `entrada` o `salida`.
   - `desde` y `hasta`: con formato `AAAA-MM-DD`; "hasta" ≥ "desde".
3. El sistema consulta los movimientos con su producto y su usuario, aplicando los filtros:
   - Fechas inclusivas: desde las 00:00 de "desde" hasta antes de las 00:00 del día siguiente a "hasta".
   - Del más reciente al más antiguo (por `fecha` y luego por `id`).
   - 20 por página; la paginación conserva los filtros.
4. El sistema carga **todos** los productos (también los inactivos) para el selector del filtro.
5. El sistema muestra:
   - El panel "Filtrar historial [n]", abierto si hay filtros o errores.
   - La tabla: producto, tipo, usuario, cantidad con signo y unidad, y fecha.
   - Como título, "Historial de {producto}" si se filtró por producto.

**Flujos alternativos**
- **A1, cambia los filtros y pulsa "Filtrar":** vuelve al paso 2.
- **A2, "Limpiar filtros":** muestra el historial sin filtros.
- **A3, no hay movimientos:** "Aún no hay movimientos" y el botón "Nuevo movimiento".
- **A4, no hay resultados con esos filtros:** "Sin movimientos con estos filtros" y "Limpiar filtros".
- **A5, botón "+" (Nuevo movimiento):** pasa a UC-16 o UC-17, con el producto del filtro ya elegido.

**Flujos de error**
- **E1, filtro inválido:** producto inexistente, tipo desconocido, fecha mal escrita o "hasta" anterior a "desde". Responde 302 → `/movimientos` (sin filtros, `$redirectRoute`) con el error junto al campo.
- FC-1, FC-2.

### Paquete Catálogo

#### UC-09 Registrar producto

| Campo | Valor |
|---|---|
| Actor principal | Administrador |
| docs/02 | CU-02 |
| Disparador | Botón **"+" (Nuevo producto)** del listado, o "Nuevo producto" en el estado vacío o en la hoja de movimiento sin productos: `GET /productos/create` (`productos.create`) abre la hoja "Nuevo producto" sobre el listado. Botón **"Guardar"**: `POST /productos` (`productos.store`) |
| Precondiciones | Sesión de administrador activo (UC-30). Existe al menos una categoría: el selector solo ofrece las que existen |
| Postcondiciones (éxito) | Ver los detalles debajo de la tabla |
| Postcondiciones (fallo) | No se guarda nada: ni producto ni movimiento |
| Reglas | RN-04, RN-08, RN-12, RN-16 |
| Rutas y controlador | `productos.create` → `ProductoController::create`; `productos.store` → `ProductoController::store` (`ProductoRequest`) |
| Pruebas | `ProductoTest::test_crear_abre_la_hoja_con_los_selects_encima_del_listado`, `test_crear_un_producto_sin_stock_inicial`, `test_el_stock_inicial_entra_como_un_movimiento_de_entrada`, `test_si_falla_el_movimiento_inicial_no_se_crea_el_producto`, `test_no_se_puede_fijar_el_stock_actual_desde_el_formulario`, `test_validaciones_al_crear_en_espanol`, `test_si_la_validacion_falla_la_hoja_conserva_lo_escrito` |

**Postcondiciones (éxito)**
- El producto queda creado y activo (`DEFAULT 1`).
- Si no hubo stock inicial, `stock_actual = 0`.
- Si el stock inicial fue mayor que 0:
  - Existe **un movimiento de entrada** por esa cantidad, a nombre del administrador.
  - `stock_actual` es igual al stock inicial.
- Vuelve al listado con "Producto «X» creado." y los filtros que tenía.

**Flujo principal**
1. El administrador pulsa "Nuevo producto". El sistema abre la hoja con:
   - Nombre.
   - Categoría (selector).
   - Unidad de medida (selector con las 7 unidades).
   - Stock mínimo.
   - Stock inicial (opcional), con la ayuda "Si es mayor que 0, se registra como una entrada en el historial."
2. El administrador completa los campos y pulsa "Guardar". El botón muestra "Guardando…".
3. El sistema normaliza los datos (nombre en una línea; coma decimal → punto) y los valida (`ProductoRequest`, RN-16).
4. El sistema abre una transacción y crea el producto, sin stock inicial (`Arr::except($datos, 'stock_inicial')`).
5. **Punto de extensión "stock inicial":** si `stock_inicial > 0`, se ejecuta UC-16 (`MovimientoInventario::registrar(producto, 'entrada', stock_inicial, admin)`) dentro de la misma transacción, como transacción anidada.
6. El sistema confirma la transacción.
7. El sistema redirige al listado con el mensaje de éxito.

**Flujos alternativos**
- **A1, cierra la hoja (Esc o el enlace de cerrar):** vuelve al listado sin guardar.
- **A2, stock inicial vacío o 0:** no se registra movimiento (el paso 5 no ocurre).

**Flujos de error**
- **E1, validación:** 302 a la hoja con los errores junto a cada campo y lo escrito. Mensajes:
  - "Ya existe un producto con ese nombre."
  - "Elige una categoría de la lista."
  - "Elige una unidad de la lista."
  - "No puede ser negativo."
  - "Usa máximo 2 decimales."
  - "Escribe el stock mínimo (puede ser 0)."
- **E2, falla el registro del stock inicial:** la transacción deshace también el producto y responde 500 (FC-6). Prueba: `ProductoTest::test_si_falla_el_movimiento_inicial_no_se_crea_el_producto`.
- **E3, dos administradores crean a la vez el mismo nombre:** ver V-07 (la unicidad está solo en la aplicación).
- FC-1 a FC-4.

#### UC-10 Editar producto

| Campo | Valor |
|---|---|
| Actor principal | Administrador |
| docs/02 | CU-03 |
| Disparador | Ícono **"Editar {producto}"** (lápiz) del listado: `GET /productos/{producto}/edit` (`productos.edit`) abre la hoja "Editar producto". Botón **"Guardar cambios"**: `PUT /productos/{producto}` (`productos.update`) |
| Precondiciones | Sesión de administrador activo. El producto existe |
| Postcondiciones | Nombre, categoría, unidad (solo si el producto no tiene movimientos) y stock mínimo actualizados. `stock_actual` no cambia. Vuelve al listado con "Producto «X» actualizado." y sus filtros |
| Reglas | RN-08, RN-12, RN-13, RN-16 |
| Rutas y controlador | `productos.edit` → `ProductoController::edit`; `productos.update` → `ProductoController::update` (`ProductoRequest`) |
| Pruebas | `ProductoTest::test_editar_muestra_el_stock_actual_como_solo_lectura`, `test_actualizar_un_producto`, `test_al_editar_no_se_acepta_stock_inicial`, `test_con_movimientos_la_unidad_ya_no_cambia`, `test_los_filtros_se_conservan_al_abrir_y_cerrar_la_hoja` |

**Flujo principal**
1. El administrador pulsa "Editar" en un producto.
2. El sistema carga el producto y cuenta sus movimientos. Muestra la hoja con:
   - Nombre, categoría y stock mínimo.
   - La unidad. Si el producto ya tiene movimientos, en lugar del selector muestra el texto "Unidad fija: el producto ya tiene movimientos".
   - "Stock actual: N {unidad} · cambia solo con movimientos · Ver historial (N)".
3. El administrador modifica los datos y pulsa "Guardar cambios".
4. El sistema valida (`ProductoRequest`):
   - Nombre único, ignorando el propio producto.
   - Si tiene movimientos, la unidad solo puede ser la actual.
   - `stock_inicial` está prohibido.
5. El sistema actualiza solo los campos permitidos (`$fillable`); `stock_actual` no está entre ellos.
6. El sistema redirige al listado con el mensaje de éxito.

**Flujos alternativos**
- **A1, "Ver historial (N)":** pasa a UC-08, filtrado por el producto.
- **A2, "Desactivar producto" o "Activar producto":** pasa a UC-11 (`<<extend>>`).
- **A3, cierra la hoja:** vuelve al listado con sus filtros.

**Flujos de error**
- **E1, validación:** 302 a la hoja con los errores. Mensajes:
  - "Ya existe un producto con ese nombre."
  - "La unidad no se puede cambiar porque el producto ya tiene movimientos."
  - "El stock solo cambia con movimientos de entrada o salida."
- **E2, el producto no existe:** FC-5.
- FC-1 a FC-4.

#### UC-11 Desactivar o activar producto (`<<extend>>` de UC-10)

| Campo | Valor |
|---|---|
| Actor principal | Administrador |
| docs/02 | CU-03, flujo alternativo ("desactivar en lugar de eliminar") |
| Punto de extensión en UC-10 | Hoja "Editar producto" abierta (formulario de estado al pie, `productos/edit.blade.php:34-48`) |
| Condición | El administrador pulsa "Desactivar producto" (si el producto está activo) o "Activar producto" (si está inactivo) |
| Disparador | `PATCH /productos/{producto}/estado` con `activo=0` o `activo=1` (`productos.cambiar-estado`) |
| Postcondiciones | Ver los detalles debajo de la tabla |
| Reglas | RN-03, RN-10, RN-20 |
| Rutas y controlador | `productos.cambiar-estado` → `ProductoController::cambiarEstado` (validación en el controlador: `activo` obligatorio y booleano) |
| Pruebas | `ProductoTest::test_desactivar_y_volver_a_activar_un_producto`, `test_la_hoja_de_editar_ofrece_desactivar_con_confirmacion`; `MovimientoTest::test_un_producto_inactivo_no_admite_movimientos`, `test_la_hoja_solo_ofrece_productos_activos` |

**Postcondiciones**
- Cambia `productos.activo`. El historial queda intacto.
- Si quedó inactivo:
  - No admite movimientos (RN-10).
  - Sale del listado por defecto; solo aparece con los filtros "Inactivos" o "Todos".
  - No genera alertas.

**Flujo principal (desactivar)**
1. El administrador pulsa "Desactivar producto".
2. El navegador pide confirmación: "¿Desactivar «X»? No admitirá nuevos movimientos; su historial se conserva." (`data-confirmar` → `window.confirm`).
3. El administrador acepta y el navegador envía el formulario con `activo=0`.
4. El sistema valida `activo` y actualiza el producto.
5. El sistema redirige al listado con "Producto «X» desactivado: ya no admite movimientos y su historial se conserva."

**Flujos alternativos**
- **A1, cancela la confirmación:** no se envía nada.
- **A2, activar (`activo=1`):** sin confirmación. Mensaje: "Producto «X» activado."

**Flujos de error**
- **E1, `activo` ausente o no booleano:** 302 de vuelta con el error de validación.
- FC-1 a FC-5.

#### UC-12 Consultar categorías

| Campo | Valor |
|---|---|
| Actor principal | Administrador |
| docs/02 | nuevo (RF-05) |
| Disparador | Tarjeta "Categorías" de Inicio; "Ver todos" de Buscar: `GET /categorias?q=` (`categorias.index`) |
| Precondiciones | Sesión de administrador activo |
| Postcondiciones | Ninguna |
| Reglas | RN-15 (la interfaz deshabilita "Eliminar" si la categoría tiene productos) |
| Rutas y controlador | `categorias.index` → `CategoriaController::index` (privado `listado()`) |
| Pruebas | `CategoriaTest::test_el_listado_muestra_las_categorias_en_orden_con_su_numero_de_productos`, `test_sin_categorias_muestra_el_estado_vacio`, `test_con_productos_el_boton_eliminar_se_ve_deshabilitado_y_dice_cuantos_tiene`; `PermisosTest::test_el_admin_entra_a_categorias`; `BusquedaTest::test_ver_todos_de_categorias_y_usuarios_filtra_esos_listados` |

**Flujo principal**
1. El administrador abre Categorías.
2. El sistema lista las categorías en orden alfabético, cada una con su número de productos (activos e inactivos), en una sola consulta. Si llega `q`, filtra por nombre. No hay paginación.
3. El sistema muestra cada fila con sus acciones:
   - "Editar".
   - "Eliminar", o, si la categoría tiene productos, el botón deshabilitado con el motivo "Tiene N productos".

**Flujos alternativos**
- **A1, no hay categorías:** "Aún no hay categorías" y el botón "Nueva categoría" (UC-13).
- **A2, llega con `q`:** muestra "Categorías con «q» · Ver todas".

**Flujos de error:** FC-1 a FC-3.

#### UC-13 Registrar categoría

| Campo | Valor |
|---|---|
| Actor principal | Administrador |
| docs/02 | nuevo (RF-05) |
| Disparador | Botón **"+" (Nueva categoría)** del listado (o el del estado vacío, o "Nueva categoría" desde Productos sin categorías): `GET /categorias/create` (`categorias.create`) abre la hoja "Nueva categoría". Botón **"Guardar"** (o Enter en el campo del nombre): `POST /categorias` (`categorias.store`) |
| Precondiciones | Sesión de administrador activo |
| Postcondiciones | Categoría creada. Vuelve al listado con "Categoría «X» creada." |
| Reglas | RN-14 |
| Rutas y controlador | `categorias.create` → `CategoriaController::create`; `categorias.store` → `CategoriaController::store` (`CategoriaRequest`) |
| Pruebas | `CategoriaTest::test_crear_abre_la_hoja_encima_del_listado`, `test_guardar_una_categoria_nueva`, `test_el_nombre_se_guarda_sin_saltos_de_linea_ni_espacios_de_mas`, `test_el_nombre_es_obligatorio`, `test_el_nombre_no_puede_pasar_de_100_caracteres`, `test_no_se_repiten_nombres`, `test_si_la_validacion_falla_la_hoja_conserva_lo_escrito_y_muestra_el_error`, `test_con_la_hoja_abierta_todo_lo_de_atras_queda_inerte` |

**Flujo principal**
1. El administrador abre la hoja. El listado queda detrás, inerte.
2. Escribe el nombre.
3. Pulsa "Guardar" o Enter.
4. El sistema quita los saltos de línea y los espacios repetidos, y valida: nombre obligatorio, de hasta 100 caracteres y único.
5. El sistema crea la categoría.
6. El sistema redirige al listado con el mensaje de éxito.

**Flujos alternativos**
- **A1, cierra la hoja:** no se guarda nada.

**Flujos de error**
- **E1, validación:** la hoja vuelve con el error y lo escrito. Mensajes:
  - "Escribe el nombre de la categoría."
  - "El nombre no puede tener más de 100 caracteres."
  - "Ya existe una categoría con ese nombre."
- FC-1 a FC-4.

#### UC-14 Editar categoría

| Campo | Valor |
|---|---|
| Actor principal | Administrador |
| docs/02 | nuevo (RF-05) |
| Disparador | Ícono **"Editar {categoría}"** del listado o un resultado de Buscar: `GET /categorias/{categoria}/edit` (`categorias.edit`). Botón **"Guardar cambios"**: `PUT` o `PATCH /categorias/{categoria}` (`categorias.update`) |
| Precondiciones | Sesión de administrador activo. La categoría existe |
| Postcondiciones | Nombre actualizado; no se crea otra categoría. Mensaje: "Categoría «X» actualizada." |
| Reglas | RN-14 |
| Rutas y controlador | `categorias.edit` → `CategoriaController::edit`; `categorias.update` → `CategoriaController::update` (`CategoriaRequest`) |
| Pruebas | `CategoriaTest::test_editar_muestra_el_nombre_actual_y_cuantos_productos_la_usan`, `test_actualizar_cambia_el_nombre_sin_crear_otra_categoria`, `test_actualizar_con_su_mismo_nombre_esta_permitido`, `test_actualizar_con_el_nombre_de_otra_categoria_falla`, `test_una_categoria_que_no_existe_da_404` |

**Flujo principal**
1. El administrador abre la hoja: muestra el nombre actual y cuántos productos usan la categoría.
2. Cambia el nombre y pulsa "Guardar cambios".
3. El sistema valida: nombre único, ignorando la propia categoría.
4. El sistema actualiza la categoría.
5. El sistema redirige al listado con el mensaje de éxito.

**Flujos de error**
- **E1:** el nombre ya es de otra categoría: "Ya existe una categoría con ese nombre."
- **E2:** la categoría no existe (FC-5).
- FC-1 a FC-4.

#### UC-15 Eliminar categoría

| Campo | Valor |
|---|---|
| Actor principal | Administrador |
| docs/02 | nuevo (RF-05; regla RN-15) |
| Disparador | Ícono **"Eliminar {categoría}"** (papelera) del listado. Solo es un formulario si la categoría no tiene productos. Tras confirmar: `DELETE /categorias/{categoria}` (`categorias.destroy`) |
| Precondiciones | Sesión de administrador activo. Para que se elimine, la categoría no puede tener productos, ni activos ni inactivos |
| Postcondiciones (éxito) | Categoría eliminada físicamente. Mensaje: "Categoría «X» eliminada." |
| Postcondiciones (fallo) | La categoría sigue igual |
| Reglas | RN-15, INV-03 |
| Rutas y controlador | `categorias.destroy` → `CategoriaController::destroy` |
| Pruebas | `CategoriaTest::test_eliminar_una_categoria_sin_productos`, `test_rn03_no_se_elimina_una_categoria_con_productos`, `test_con_productos_el_boton_eliminar_se_ve_deshabilitado_y_dice_cuantos_tiene`, `test_una_categoria_que_no_existe_da_404` |

**Flujo principal**
1. El administrador pulsa la papelera de una categoría sin productos.
2. El navegador pide confirmación: "¿Eliminar la categoría «X»?".
3. El administrador acepta y el navegador envía `DELETE`.
4. El sistema cuenta los productos de la categoría, activos e inactivos.
5. Hay 0 productos: el sistema elimina la categoría.
6. El sistema redirige al listado con el mensaje de éxito.

**Flujos alternativos**
- **A1, cancela la confirmación:** no se envía nada.
- **A2, la categoría tiene productos:** en la interfaz, el botón está deshabilitado (`aria-disabled`) con el motivo "Tiene N productos", y no hay formulario.

**Flujos de error**
- **E1, la categoría tiene productos** (petición directa o pantalla desactualizada):
  - No se elimina.
  - 302 → `/categorias` con "No se puede eliminar «X» porque tiene N productos. Primero cámbialos de categoría."
- **E2:** la categoría no existe (FC-5).
- **E3, carrera entre el conteo y el borrado:** la llave foránea rechazaría el borrado y respondería 500 (FC-6). Ver V-06.
- FC-1 a FC-4.

### Paquete Inventario

#### UC-16 Registrar entrada de stock

| Campo | Valor |
|---|---|
| Actor principal | Usuario (los dos roles) |
| docs/02 | CU-04 |
| Disparador | Ver los detalles debajo de la tabla |
| Precondiciones | Sesión con cuenta activa. Existe al menos un producto **activo** |
| Postcondiciones (éxito) | Ver los detalles debajo de la tabla |
| Postcondiciones (fallo) | No cambia nada: ni el stock ni el historial |
| Reglas | RN-04, RN-09, RN-10, RN-11, RN-12, INV-12 |
| Rutas y controlador | `movimientos.create` → `MovimientoController::create`; `movimientos.store` → `MovimientoController::store` (`MovimientoRequest`) → `MovimientoInventario::registrar` |
| Pruebas | `MovimientoTest::test_una_entrada_suma_al_stock_y_queda_en_el_historial`, `test_los_dos_roles_registran_movimientos`, `test_validaciones_en_espanol`, `test_registrar_esta_deshabilitado_hasta_elegir_el_tipo`, `test_desde_productos_la_hoja_llega_con_el_producto_elegido_y_vuelve_al_listado`, `test_volver_solo_acepta_claves_de_la_lista_y_nunca_una_url_externa`, `test_un_producto_inactivo_no_admite_movimientos`, `test_la_hoja_solo_ofrece_productos_activos`, `test_rn04_si_falla_guardar_el_movimiento_el_stock_no_cambia`, `test_rn04_en_mysql_la_fila_del_producto_se_bloquea_con_for_update`, `test_rn04_el_stock_siempre_es_la_suma_del_historial`, `test_rn09_un_movimiento_no_se_edita_ni_se_elimina`, `test_un_invitado_no_puede_registrar`, `test_una_cuenta_desactivada_no_puede_registrar`; `DashboardTest::test_desde_el_dashboard_la_hoja_llega_con_entrada_y_vuelve_al_dashboard`; `HistorialTest::test_el_boton_mas_lleva_el_producto_del_filtro_a_la_hoja` |

**Disparador:** abrir la hoja **"Nuevo movimiento"** (`GET /movimientos/create`, `movimientos.create`) desde:
- El botón "+" del historial; lleva el producto del filtro.
- "Registrar movimiento de {producto}" en Productos (`?producto_id=X&volver=productos`).
- "Registrar entrada de {producto}" en el Dashboard (`?producto_id=X&tipo=entrada&volver=dashboard`).
- "Nuevo movimiento" en el estado vacío del historial.

Después, el botón **"Registrar"** envía `POST /movimientos` (`movimientos.store`).

Además, UC-16 **extiende** a UC-09 (stock inicial), sin hoja.

**Postcondiciones (éxito)**
- Queda guardado el movimiento: `tipo = 'entrada'`, la cantidad, `fecha` = ahora y `usuario_id` = la persona autenticada.
- `stock_actual` aumentó en la cantidad, en la **misma** transacción.
- 302 a la pantalla de origen (historial, Productos o Dashboard) con "Entrada de {cantidad y unidad} de «X» registrada. Stock: {stock y unidad}."

**Flujo principal**
1. El usuario abre la hoja. El sistema muestra:
   - El interruptor Entrada / Salida, sin ninguno marcado salvo que llegue `tipo`.
   - El selector con **solo los productos activos**, agrupados por categoría. Viene elegido si llega `producto_id` o hay un filtro de producto.
   - El campo de cantidad y el resumen de stock.
   - El botón "Registrar", deshabilitado hasta elegir el tipo ("Elige entrada o salida para registrar.").
2. El usuario elige "Entrada" y el producto y escribe la cantidad. El resumen (JS) muestra "Quedarán N {unidad}" y, si corresponde, "· bajo el mínimo (…)".
3. El usuario pulsa "Registrar". El botón muestra "Registrando…".
4. El sistema valida la forma de los datos (`MovimientoRequest`):
   - `tipo` ∈ {entrada, salida}.
   - Producto existente.
   - Cantidad numérica, mayor que 0, de máximo 99.999.999,99 y con hasta 2 decimales (acepta coma).
5. El controlador carga el producto y llama a `MovimientoInventario::registrar(producto, 'entrada', cantidad, usuario)`.
6. `registrar()`:
   1. Inicia una transacción.
   2. Vuelve a leer el producto **bloqueando su fila** (`lockForUpdate`).
   3. Comprueba que esté activo y que la cantidad sea mayor que 0.
   4. **Suma** la cantidad a `stock_actual`.
   5. Inserta el movimiento.
   6. Confirma la transacción.
7. El controlador vuelve a leer el producto y arma el mensaje de éxito, con la unidad en singular o plural.
8. **Punto de extensión "después de registrar":** si el producto sigue en stock bajo, se ejecuta UC-19.
9. El sistema redirige según `volver`, que solo acepta valores de una lista fija:
   - `productos` → Productos.
   - `dashboard` → Dashboard.
   - Si no, al historial.

**Flujos alternativos**
- **A1, cierra la hoja (Esc o el enlace de cerrar):** vuelve a la pantalla de origen: historial con sus filtros, Productos o Dashboard.
- **A2, no hay productos activos:** la hoja muestra "No hay productos activos" y, si es administrador, el botón "Nuevo producto" (UC-09).
- **A3, llega desde el Dashboard:** el tipo `entrada` ya está marcado y el botón habilitado; el foco va a la cantidad.

**Flujos de error**
- **E1, validación de forma:** 302 a la hoja con los errores y lo escrito. Mensajes:
  - "Elige si es una entrada o una salida."
  - "Elige un producto."
  - "Elige un producto de la lista."
  - "Escribe la cantidad."
  - "La cantidad debe ser mayor que 0."
  - "Escribe un número, por ejemplo 12,5."
  - "Usa máximo 2 decimales."
- **E2, producto inactivo** (por ejemplo, una pantalla desactualizada; RN-10):
  - `registrar()` lanza el error y la transacción se deshace (ROLLBACK).
  - 302 a la hoja con "«X» está inactivo y no admite movimientos."
- **E3, falla al guardar el movimiento:** ROLLBACK (el stock no cambia) y respuesta 500 (FC-6).
- **E4, `volver` con un valor desconocido o una URL:** se ignora y vuelve al historial (INV-12).
- FC-1, FC-2, FC-4.

#### UC-17 Registrar salida de stock

| Campo | Valor |
|---|---|
| Actor principal | Usuario (los dos roles) |
| docs/02 | CU-05 |
| Disparador | La misma hoja de UC-16, eligiendo **"Salida"**. "Registrar" envía `POST /movimientos` (`movimientos.store`) |
| Precondiciones | Sesión con cuenta activa. Producto activo. CU-05 pide "stock suficiente"; el sistema lo **valida** (UC-18) en lugar de exigirlo como precondición |
| Postcondiciones (éxito) | Movimiento de salida guardado. `stock_actual` bajó en la cantidad y puede llegar a 0. Mensaje: "Salida de {cantidad} de «X» registrada. Stock: {stock}." |
| Postcondiciones (fallo) | No cambia nada |
| Reglas | RN-01, RN-04, RN-09, RN-10, RN-11, RN-12 |
| Rutas y controlador | Los mismos que UC-16 |
| Pruebas | `MovimientoTest::test_una_salida_resta_del_stock`, `test_se_puede_sacar_todo_el_stock`, `test_rn01_no_se_registra_una_salida_mayor_al_stock`, `test_rn01_tambien_desde_el_modelo`, `test_una_salida_que_deja_el_stock_bajo_el_minimo_muestra_la_alerta`, `test_sin_quedar_bajo_el_minimo_no_hay_alerta`, `test_los_dos_roles_registran_movimientos` y las pruebas de RN-04 de UC-16 |

**Flujo principal:** igual que UC-16, con estas diferencias:
- **Paso 2:** si la cantidad supera el stock, el resumen (JS) dice "No alcanza: hay N …". Es solo una ayuda: igual se puede enviar.
- **Paso 6:** después de bloquear la fila y comprobar que el producto está activo y que la cantidad es mayor que 0, se ejecuta **`<<include>>` UC-18 (Validar stock disponible)**. Si alcanza, el sistema **resta** la cantidad de `stock_actual` e inserta el movimiento.
- **Paso 8:** UC-19 muestra "quedó en stock bajo".

**Flujos de error**
- **E1:** validación de forma, igual que en UC-16.
- **E2, stock insuficiente (UC-18):** ROLLBACK. 302 a la hoja con "No hay stock suficiente: «X» tiene {stock y unidad}."
- **E3:** producto inactivo, igual que en UC-16.
- FC-1, FC-2, FC-4.

#### UC-18 Validar stock disponible (`<<include>>` de UC-17)

| Campo | Valor |
|---|---|
| Actor | Ninguno: lo ejecuta siempre el sistema, dentro de UC-17 |
| docs/02 | CU-05, paso 2 |
| Cuándo | En **toda** salida, dentro de la transacción y con la fila del producto bloqueada |
| Reglas | RN-01, INV-01 |
| Fuente | `MovimientoInventario::registrar:84-90` |
| Pruebas | `MovimientoTest::test_rn01_no_se_registra_una_salida_mayor_al_stock`, `test_rn01_tambien_desde_el_modelo`, `test_se_puede_sacar_todo_el_stock`, `test_rn04_el_stock_siempre_es_la_suma_del_historial` |

**Flujo**
1. Con la fila bloqueada, el sistema compara la cantidad con `stock_actual`.
2. Si la cantidad es menor o igual al stock, continúa UC-17.
3. Si la cantidad es mayor, lanza un error de validación en "cantidad" y la transacción se deshace.

**Concurrencia:** si dos personas registran salidas del mismo producto a la vez, la segunda espera el bloqueo y valida contra el stock ya actualizado (comentario de `MovimientoInventario::registrar`, `app/Models/MovimientoInventario.php:55-57`).

#### UC-19 Mostrar alerta de stock bajo (`<<extend>>` de UC-16 y UC-17)

| Campo | Valor |
|---|---|
| Actor | Ninguno: es una extensión |
| docs/02 | CU-05, paso 4 (RF-07) |
| Punto de extensión | "Después de registrar", paso 8 de UC-16 y de UC-17 |
| Condición | `$producto->enStockBajo()` sobre el producto ya recargado: `stock_actual < stock_minimo`. Justo en el mínimo no hay alerta |
| Reglas | RN-02 |
| Fuente | `MovimientoController::store:59-65` |
| Pruebas | `MovimientoTest::test_una_salida_que_deja_el_stock_bajo_el_minimo_muestra_la_alerta`, `test_sin_quedar_bajo_el_minimo_no_hay_alerta`, `test_una_entrada_que_no_alcanza_el_minimo_avisa_que_sigue_bajo` |

**Flujo**
1. El sistema evalúa la condición.
2. Si se cumple, agrega a la redirección el mensaje flash `aviso`:
   - **Salida:** "«X» quedó en stock bajo: {stock} (mínimo {mínimo})."
   - **Entrada:** "«X» sigue en stock bajo: …"
3. La pantalla de destino muestra el aviso (`role="alert"`) junto al mensaje de éxito.

**Nota:** la alerta *permanente* (etiqueta "Stock bajo", tabla del Dashboard, línea de Inicio, reporte de stock bajo) no es este caso de uso: forma parte de UC-04, UC-05, UC-07 y UC-25.

### Paquete Administración

#### UC-20 Consultar usuarios

| Campo | Valor |
|---|---|
| Actor principal | Administrador |
| docs/02 | CU-07 |
| Disparador | Tarjeta "Usuarios" de Inicio; "Ver todos" de Buscar: `GET /usuarios?q=&page=` (`usuarios.index`) |
| Precondiciones | Sesión de administrador activo |
| Postcondiciones | Ninguna |
| Rutas y controlador | `usuarios.index` → `UsuarioController::index` (privados: `listado()`, `filtros()`) |
| Pruebas | `UsuarioTest::test_el_listado_muestra_rol_estado_y_marca_tu_cuenta`; `BusquedaTest::test_ver_todos_de_categorias_y_usuarios_filtra_esos_listados` |

**Flujo principal**
1. El administrador abre Usuarios.
2. El sistema lista los usuarios, 15 por página: primero los activos y después por nombre. Si llega `q`, busca por nombre o correo.
3. El sistema muestra el total ("N usuarios") y, por cada usuario:
   - Nombre, con "(tú)" en la cuenta propia.
   - Correo.
   - Rol ("Administrador" o "Encargado").
   - Estado (etiqueta "Inactivo").
   - Acción "Editar".
   - Además está el botón "+" para un usuario nuevo.

**Flujos alternativos**
- **A1, sin resultados:** "Sin resultados" y el botón "Ver todos".

**Flujos de error:** FC-1 a FC-3.

#### UC-21 Registrar usuario

| Campo | Valor |
|---|---|
| Actor principal | Administrador |
| docs/02 | CU-07 |
| Disparador | Botón **"+" (Nuevo usuario)**: `GET /usuarios/create` (`usuarios.create`) abre la hoja "Nuevo usuario". Botón **"Crear usuario"**: `POST /usuarios` (`usuarios.store`) |
| Precondiciones | Sesión de administrador activo |
| Postcondiciones | Usuario creado y **activo**, con la contraseña guardada con hash. Ya puede iniciar sesión. Mensaje: "Usuario «X» creado." |
| Reglas | RN-17, INV-04, INV-09, INV-10 |
| Rutas y controlador | `usuarios.create` → `UsuarioController::create`; `usuarios.store` → `UsuarioController::store` (`UsuarioRequest`) |
| Pruebas | `UsuarioTest::test_crear_un_usuario_con_contrasena_inicial`, `test_validaciones_en_espanol`, `test_el_correo_es_unico_sin_importar_mayusculas` |

**Flujo principal**
1. El administrador abre la hoja: "Nombre completo", "Correo electrónico", "Rol" (Administrador o Encargado de inventario) y "Contraseña inicial" (con la ayuda "Mínimo 8 caracteres. Compártela con la persona por un medio seguro.").
2. Completa los campos y pulsa "Crear usuario".
3. El sistema normaliza (nombre en una línea; correo en minúsculas y sin espacios) y valida (RN-17).
4. El sistema crea la cuenta con `activo = true`. La contraseña se cifra sola por el cast `hashed`.
5. El sistema redirige al listado con el mensaje de éxito.

**Flujos de error**
- **E1, validación:** la hoja vuelve con los errores. Mensajes:
  - "Escribe el nombre."
  - "Escribe el correo."
  - "Escribe un correo válido, por ejemplo ana@granja.com."
  - "Ya existe un usuario con ese correo."
  - "Elige un rol."
  - "Elige un rol de la lista."
  - "Escribe una contraseña inicial."
  - "La contraseña debe tener al menos 8 caracteres."
- FC-1 a FC-4.

#### UC-22 Editar usuario

| Campo | Valor |
|---|---|
| Actor principal | Administrador |
| docs/02 | CU-07 |
| Disparador | **"Editar"** en el listado o un resultado de Buscar: `GET /usuarios/{usuario}/edit` (`usuarios.edit`) abre la hoja "Editar usuario". Botón **"Guardar cambios"**: `PUT /usuarios/{usuario}` (`usuarios.update`) |
| Precondiciones | Sesión de administrador activo. El usuario existe |
| Postcondiciones | Nombre, correo y rol actualizados; contraseña nueva solo si se escribió. Mensaje: "Usuario «X» actualizado." Un cambio de rol se aplica en la **siguiente petición** de esa persona, porque el usuario se vuelve a leer de la BD en cada petición (UC-28) |
| Reglas | RN-07, RN-17 |
| Rutas y controlador | `usuarios.edit` → `UsuarioController::edit`; `usuarios.update` → `UsuarioController::update` (`UsuarioRequest`) |
| Pruebas | `UsuarioTest::test_al_editar_la_contrasena_vacia_no_la_cambia`, `test_al_editar_con_contrasena_nueva_se_cambia_y_se_puede_entrar_con_ella`, `test_el_admin_puede_cambiar_el_rol_de_otra_persona`, `test_rn07_el_unico_admin_no_puede_quitarse_el_rol`, `test_aunque_haya_otro_admin_nadie_se_desactiva_ni_se_quita_el_rol_a_si_mismo`, `test_la_hoja_de_tu_propia_cuenta_no_ofrece_cambiar_rol_ni_desactivar`, `test_el_correo_es_unico_sin_importar_mayusculas` |

**Flujo principal**
1. El administrador abre la hoja. Muestra nombre, correo, rol y "Nueva contraseña (opcional)" ("Déjala vacía para no cambiarla. Mínimo 8 caracteres.").
2. Modifica los datos y pulsa "Guardar cambios".
3. El sistema valida (`UsuarioRequest`):
   - Correo único, ignorando el propio.
   - Rol de la lista. Si es **su propia cuenta**, solo puede ser `admin`.
   - Contraseña opcional, de al menos 8 caracteres.
4. Si la contraseña llega vacía, se conserva la anterior.
5. El sistema actualiza la cuenta.
6. El sistema redirige al listado con el mensaje de éxito.

**Flujos alternativos**
- **A1, edita su propia cuenta:** la hoja no ofrece el selector de rol. Muestra "Es tu cuenta: no puedes quitarte el rol de administrador." y envía `rol=admin` en un campo oculto.
- **A2, cambia el rol de otra persona:** permitido, incluso pasar a otro administrador a encargado.
- **A3, escribe una contraseña nueva:** se guarda con hash y la anterior deja de servir.
- **A4, "Desactivar cuenta" o "Activar cuenta":** UC-23 (`<<extend>>`).

**Flujos de error**
- **E1:** validación, como en UC-21.
- **E2, intenta quitarse el rol de administrador** (petición manipulada): "No puedes quitarte el rol de administrador." (RN-07).
- **E3:** el usuario no existe (FC-5).
- FC-1 a FC-4.

#### UC-23 Desactivar o activar usuario (`<<extend>>` de UC-22)

| Campo | Valor |
|---|---|
| Actor principal | Administrador |
| docs/02 | CU-07, flujo alternativo (RN-03, RN-07) |
| Punto de extensión en UC-22 | Hoja "Editar usuario" abierta (sección de estado, `usuarios/edit.blade.php:26-46`) |
| Condición | Pulsa "Desactivar cuenta" (si está activa) o "Activar cuenta" (si está inactiva), y **no es su propia cuenta**. En la propia cuenta, la hoja muestra "Es tu cuenta: no puedes desactivarla." |
| Disparador | `PATCH /usuarios/{usuario}/estado` con `activo=0` o `activo=1` (`usuarios.cambiar-estado`) |
| Postcondiciones | Ver los detalles debajo de la tabla |
| Reglas | RN-03, RN-07, RN-19, INV-06 |
| Rutas y controlador | `usuarios.cambiar-estado` → `UsuarioController::cambiarEstado` (validación en el controlador: `activo` obligatorio y booleano) |
| Pruebas | `UsuarioTest::test_desactivar_pide_confirmacion_y_expulsa_la_sesion_abierta`, `test_rn07_el_unico_admin_no_puede_desactivarse`, `test_aunque_haya_otro_admin_nadie_se_desactiva_ni_se_quita_el_rol_a_si_mismo`; `AutenticacionTest::test_un_usuario_desactivado_con_la_sesion_abierta_es_expulsado` |

**Postcondiciones**
- Cambia `users.activo`.
- Si quedó desactivada:
  - No puede iniciar sesión.
  - Si tenía la sesión abierta, se cierra en su siguiente petición (UC-29).
  - Sus movimientos se conservan.
- Mensaje al desactivar: "Usuario «X» desactivado: ya no puede iniciar sesión y, si la tenía abierta, se cerrará en su siguiente acción."
- Mensaje al activar: "Usuario «X» activado."

**Flujo principal (desactivar)**
1. El administrador pulsa "Desactivar cuenta".
2. El navegador pide confirmación: "¿Desactivar a «X»? No podrá iniciar sesión hasta que la actives de nuevo."
3. El administrador acepta y el navegador envía `activo=0`.
4. El sistema valida `activo` y comprueba que no sea su propia cuenta.
5. El sistema actualiza la cuenta.
6. El sistema redirige al listado con el mensaje.

**Flujos alternativos**
- **A1, cancela la confirmación:** no se envía nada.
- **A2, activar:** sin confirmación.

**Flujos de error**
- **E1, es su propia cuenta** (petición manipulada): 302 → `/usuarios` con "No puedes desactivar tu propia cuenta." (RN-07).
- **E2, `activo` no booleano:** error de validación.
- **E3:** el usuario no existe (FC-5).
- **E4, dos administradores se desactivan uno al otro a la vez:** ver V-08.
- FC-1 a FC-4.

### Paquete Reportes

Los tres reportes empiezan igual:
1. El administrador abre **Reportes** desde la tarjeta de Inicio: `GET /reportes` (`reportes.index` → `ReporteController::index`).
2. Elige una de las tres tarjetas: "Stock por categoría", "Stock bajo" o "Más movidos".

Todos muestran:
- "Generado el {fecha, hora}".
- El botón **"Imprimir"** (UC-27).
- La flecha "Volver" al índice de reportes.

Prueba común: `ReporteTest::test_el_indice_tiene_tres_tarjetas_de_acceso`.

#### UC-24 Generar reporte de stock por categoría

| Campo | Valor |
|---|---|
| Actor principal | Administrador |
| docs/02 | CU-08 |
| Disparador | Tarjeta "Stock por categoría": `GET /reportes/stock-por-categoria` (`reportes.stock-por-categoria`) |
| Precondiciones | Sesión de administrador activo |
| Postcondiciones | Ninguna |
| Reglas | RN-13, RN-20 |
| Rutas y controlador | `reportes.index` → `ReporteController::index`; `reportes.stock-por-categoria` → `ReporteController::stockPorCategoria` |
| Pruebas | `ReporteTest::test_stock_por_categoria_no_suma_unidades_distintas`, `test_stock_por_categoria_no_hace_una_consulta_por_categoria`, `test_solo_el_admin_ve_los_reportes`, `test_cada_reporte_tiene_imprimir_y_la_barra_no_se_imprime` |

**Flujo principal**
1. El administrador abre el reporte desde el índice.
2. El sistema trae todas las categorías en orden alfabético, cada una con sus **productos activos** también en orden. Son dos consultas, sin N+1.
3. El sistema muestra, por categoría:
   - El número de productos. Nunca suma cantidades de unidades distintas.
   - Una tabla con cada producto, su stock en su unidad, su mínimo y la etiqueta "Stock bajo" si corresponde.
   - Cada producto enlaza a su historial.

**Flujos alternativos**
- **A1, no hay categorías:** "Aún no hay categorías".
- **A2, una categoría no tiene productos activos:** "Sin productos activos."
- **A3, "Imprimir":** UC-27.

**Flujos de error:** FC-1 a FC-3.

#### UC-25 Generar reporte de stock bajo

| Campo | Valor |
|---|---|
| Actor principal | Administrador |
| docs/02 | CU-08 |
| Disparador | Tarjeta "Stock bajo": `GET /reportes/stock-bajo` (`reportes.stock-bajo`) |
| Precondiciones | Sesión de administrador activo |
| Postcondiciones | Ninguna |
| Reglas | RN-02, RN-20 |
| Rutas y controlador | `reportes.stock-bajo` → `ReporteController::stockBajo` |
| Pruebas | `ReporteTest::test_stock_bajo_usa_la_misma_regla_y_orden_que_el_dashboard`, `test_stock_bajo_vacio_y_sin_n_mas_1` |

**Flujo principal**
1. El administrador abre el reporte.
2. El sistema obtiene los productos en alerta (`Producto::enAlerta()`, el mismo scope del Dashboard) con su categoría, del mayor al menor faltante.
3. El sistema los muestra, cada uno con enlace a su historial.

**Flujos alternativos**
- **A1, no hay productos en alerta:** estado vacío.
- **A2, "Imprimir":** UC-27.

**Flujos de error:** FC-1 a FC-3.

#### UC-26 Generar reporte de productos más movidos

| Campo | Valor |
|---|---|
| Actor principal | Administrador |
| docs/02 | CU-08 |
| Disparador | Tarjeta "Más movidos": `GET /reportes/mas-movidos` (`reportes.mas-movidos`). Formulario "Desde" / "Hasta (incluido)" con el botón **"Generar"**: `GET /reportes/mas-movidos?desde=&hasta=` |
| Precondiciones | Sesión de administrador activo |
| Postcondiciones | Ninguna |
| Reglas | RN-21, RN-22 |
| Rutas y controlador | `reportes.mas-movidos` → `ReporteController::masMovidos` (`RangoFechasRequest`) |
| Pruebas | `ReporteTest::test_mas_movidos_cuenta_movimientos_y_suma_entradas_y_salidas_en_su_unidad`, `test_mas_movidos_por_defecto_son_los_ultimos_30_dias_en_hora_de_bogota`, `test_mas_movidos_rango_inclusivo_en_ambos_bordes`, `test_mas_movidos_incluye_inactivos_con_su_badge_y_enlaza_al_historial_del_rango`, `test_mas_movidos_muestra_solo_el_top_10`, `test_mas_movidos_rango_invalido_vuelve_con_el_error`, `test_mas_movidos_no_hace_una_consulta_por_producto` |

**Flujo principal**
1. El administrador abre el reporte sin fechas. El rango por defecto son los últimos 30 días, con hoy incluido (`RangoFechasRequest::desde`, `RangoFechasRequest::hasta`).
2. El sistema valida las fechas: formato `AAAA-MM-DD` y "hasta" ≥ "desde".
3. En una sola consulta (con subconsultas), el sistema obtiene los 10 productos con al menos un movimiento en el rango. Para cada uno trae:
   - El número de movimientos.
   - El total de entradas y el total de salidas.
   - Los ordena por número de movimientos (de mayor a menor) y luego por nombre, con su categoría.
4. El sistema muestra la tabla: posición, producto (con la etiqueta "Inactivo" si corresponde), movimientos, entradas y salidas en la unidad del producto. Cada fila enlaza al historial de ese producto **en el mismo rango**.
5. El administrador puede cambiar el rango y pulsar "Generar" ("Generando…"); vuelve al paso 2.

**Flujos alternativos**
- **A1, no hay movimientos en el rango:** "Sin movimientos en este rango".
- **A2, "Imprimir":** UC-27. El formulario del rango no sale en la hoja impresa.

**Flujos de error**
- **E1, rango inválido:** 302 → `/reportes/mas-movidos` sin fechas (`$redirectRoute`, con el rango por defecto) y el error. Mensajes:
  - "«Hasta» no puede ser anterior a «Desde»."
  - "Escribe una fecha válida."
- FC-1 a FC-3.

#### UC-27 Imprimir reporte (`<<extend>>` de UC-24, UC-25 y UC-26)

| Campo | Valor |
|---|---|
| Actor principal | Administrador |
| docs/02 | CU-08 paso 3 ("exportar"): la exportación no existe y se reemplaza por imprimir (DIF-19) |
| Punto de extensión | "Reporte mostrado": encabezado del reporte (`reportes/partes/encabezado.blade.php:8-12`) |
| Condición | El administrador pulsa **"Imprimir"** |
| Postcondiciones | El navegador abre su diálogo de impresión. El servidor no recibe ninguna petición |
| Fuente | `public/js/app.js:240-246` (`data-imprimir` → `window.print()`); estilos `@media print` en `resources/css/input.css` |
| Pruebas | `ReporteTest::test_cada_reporte_tiene_imprimir_y_la_barra_no_se_imprime` |

**Flujo**
1. El administrador pulsa "Imprimir".
2. El JavaScript llama a `window.print()`.
3. El navegador muestra la vista previa. No se imprimen la barra de navegación, los botones ni el formulario del rango.

### Transversales (opcionales en el diagrama)

Son mecanismos del sistema, no objetivos de un actor. Ver la recomendación en §7.

#### UC-28 Verificar sesión y cuenta activa (`<<include>>` de todo caso de uso con sesión)

| Campo | Valor |
|---|---|
| Lo incluyen | UC-04 a UC-26 (todas las rutas del grupo `Route::middleware(['auth', 'activo'])`, `routes/web.php:38`) |
| Fuente | Middleware `auth` (`Illuminate\Auth\Middleware\Authenticate`, con `redirectGuestsTo` en `bootstrap/app.php:22`) y `activo` (`App\Http\Middleware\UsuarioActivo`) |
| Pruebas | `RutasTest::test_un_invitado_va_al_login_desde_cualquier_ruta_protegida`; `PermisosTest::test_un_invitado_va_al_login_desde_cualquier_pagina_protegida`; `MovimientoTest::test_un_invitado_no_puede_registrar` |

**Flujo**
1. `auth` lee el id de la sesión y carga al usuario **de la base de datos en cada petición**.
2. Si no hay sesión, responde como FC-1.
3. `activo` comprueba `users.activo`. Si es `false`, se ejecuta UC-29.
4. La petición continúa.

#### UC-29 Expulsar cuenta desactivada (`<<extend>>` de UC-28)

| Campo | Valor |
|---|---|
| Condición | El usuario autenticado tiene `activo = false`. Ocurre cuando lo desactivaron (UC-23) con la sesión abierta |
| Fuente | `UsuarioActivo::handle:19-26` |
| Pruebas | `AutenticacionTest::test_un_usuario_desactivado_con_la_sesion_abierta_es_expulsado`; `UsuarioTest::test_desactivar_pide_confirmacion_y_expulsa_la_sesion_abierta`; `MovimientoTest::test_una_cuenta_desactivada_no_puede_registrar` |

**Flujo**
1. El sistema cierra la sesión (`Auth::logout`).
2. Invalida la sesión, lo que borra su fila en `sessions`.
3. Regenera el token CSRF.
4. Responde 302 → `/login` con "Tu cuenta fue desactivada. Habla con un administrador."

La acción que se había pedido **no** se ejecuta.

#### UC-30 Verificar rol de administrador (`<<include>>` de los casos de uso de administrador)

| Campo | Valor |
|---|---|
| Lo incluyen | UC-09 a UC-15 y UC-20 a UC-26 (grupo `Route::middleware('role:admin')`, `routes/web.php:55`) |
| Fuente | `VerificarRol::handle` (alias `role`, `bootstrap/app.php:16`) |
| Pruebas | `RutasTest::test_el_encargado_recibe_403_en_las_rutas_de_admin`; `PermisosTest::test_el_encargado_recibe_403_en_todas_las_acciones_de_categorias`, `test_el_403_tiene_vista_propia_en_espanol`; `UsuarioTest::test_el_encargado_recibe_403_en_todo_usuarios`; `ReporteTest::test_solo_el_admin_ve_los_reportes` |

**Flujo**
1. Si `users.rol` no está en la lista de la ruta (`['admin']`), responde `abort(403)` con la vista `errors/403`.
2. Si está, la petición continúa.

Corre **después** del *route model binding* (FC-5, V-09).

---

## 5. Relaciones entre casos de uso

### 5.1 `<<include>>` y `<<extend>>`

| Base | Relación | Caso de uso | Condición o punto de extensión | Justificación en el código |
|---|---|---|---|---|
| UC-17 Registrar salida | `<<include>>` | UC-18 Validar stock disponible | Siempre, en toda salida | `MovimientoInventario::registrar:84-90`: `if ($tipo === 'salida' && cantidad > stock_actual) → ValidationException` |
| UC-01 Iniciar sesión | `<<extend>>` (desde UC-02) | UC-02 Bloquear acceso por intentos fallidos | Paso 4, "verificar bloqueo": `RateLimiter::tooManyAttempts($clave, 3)` | `AuthController::login:34-40` |
| UC-16 Registrar entrada | `<<extend>>` (desde UC-19) | UC-19 Mostrar alerta de stock bajo | "Después de registrar": `$producto->enStockBajo()` | `MovimientoController::store:59-65` |
| UC-17 Registrar salida | `<<extend>>` (desde UC-19) | UC-19 Mostrar alerta de stock bajo | La misma | `MovimientoController::store:59-65` |
| UC-09 Registrar producto | `<<extend>>` (desde UC-16) | UC-16 Registrar entrada de stock | "Stock inicial": `stock_inicial > 0` | `ProductoController::store:46-48` |
| UC-10 Editar producto | `<<extend>>` (desde UC-11) | UC-11 Desactivar o activar producto | Hoja "Editar producto" abierta; el administrador pulsa el botón de estado | `productos/edit.blade.php:34-48` |
| UC-22 Editar usuario | `<<extend>>` (desde UC-23) | UC-23 Desactivar o activar usuario | Hoja "Editar usuario" abierta y **no** es su propia cuenta | `usuarios/edit.blade.php:26-46`; `UsuarioController::cambiarEstado:67-71` |
| UC-24, UC-25, UC-26 | `<<extend>>` (desde UC-27) | UC-27 Imprimir reporte | "Reporte mostrado"; el administrador pulsa "Imprimir" | `reportes/partes/encabezado.blade.php:8-12`; `public/js/app.js:240-246` |
| UC-04 a UC-26 | `<<include>>` | UC-28 Verificar sesión y cuenta activa | Siempre | `routes/web.php:38` (`middleware(['auth', 'activo'])`) |
| UC-28 | `<<extend>>` (desde UC-29) | UC-29 Expulsar cuenta desactivada | `users.activo = false` | `UsuarioActivo::handle:19-26` |
| UC-09 a UC-15, UC-20 a UC-26 | `<<include>>` | UC-30 Verificar rol de administrador | Siempre | `routes/web.php:55` (`middleware('role:admin')`) |

**Sobre la flecha:**
- En `<<include>>`, la flecha va de la base al caso de uso incluido.
- En `<<extend>>`, va **del caso de uso que extiende a la base** (por ejemplo, UC-02 ──▷ UC-01).

### 5.2 Generalizaciones (opcionales)

| Padre (abstracto) | Hijos | Justificación |
|---|---|---|
| *Registrar movimiento de inventario* | UC-16, UC-17 | Usan la misma hoja, la misma ruta (`movimientos.store`) y el mismo método (`registrar()`); solo cambia `tipo`. UC-18 se incluye solo en UC-17 |
| *Generar reporte* | UC-24, UC-25, UC-26 | Comparten el índice `reportes.index`, el encabezado y "Imprimir"; UC-27 puede extender al padre en lugar de a los tres |
| Actor *Usuario* | Administrador, Encargado de inventario | `00` §3.2. Otra opción es Administrador ──▷ Encargado |

### 5.3 Lo que no conviene modelar como relación

- **Navegación entre pantallas** (Dashboard → Registrar entrada, Buscar → Ver todos, nombre de producto → historial): son disparadores alternativos ya anotados en cada caso de uso, no `<<extend>>`.
- **"Comprobar que la categoría no tenga productos"** en UC-15: es un paso y un flujo de error del caso de uso, no otro caso de uso.
- **Los errores de validación de forma** (Form Requests): son flujos de error de cada caso de uso.

---

## 6. Cobertura de rutas

Ninguna ruta de la aplicación queda sin caso de uso ni sin controlador. Las 32 rutas salen de `php artisan route:list --except-vendor`.

| # | Ruta (nombre) | Método y URI | Caso de uso | Controlador::método |
|---|---|---|---|---|
| 1 | `raiz` | GET `/` | UC-01 (A2), UC-04 | closure `routes/web.php:26` → `redirect()->route('home')` |
| 2 | `login` | GET `/login` | UC-01 | `AuthController::showLoginForm` |
| 3 | `login.entrar` | POST `/login` | UC-01, UC-02 | `AuthController::login` |
| 4 | `logout` | POST `/logout` | UC-03 | `AuthController::logout` |
| 5 | `home` | GET `/home` | UC-04 | `InicioController::__invoke` |
| 6 | `dashboard` | GET `/dashboard` | UC-05 | `DashboardController::__invoke` |
| 7 | `search` | GET `/search` | UC-06 | `BusquedaController::__invoke` |
| 8 | `productos.index` | GET `/productos` | UC-07 | `ProductoController::index` |
| 9 | `productos.create` | GET `/productos/create` | UC-09 | `ProductoController::create` |
| 10 | `productos.store` | POST `/productos` | UC-09 (+ UC-16) | `ProductoController::store` |
| 11 | `productos.edit` | GET `/productos/{producto}/edit` | UC-10 (y entrada a UC-11) | `ProductoController::edit` |
| 12 | `productos.update` | PUT `/productos/{producto}` | UC-10 | `ProductoController::update` |
| 13 | `productos.cambiar-estado` | PATCH `/productos/{producto}/estado` | UC-11 | `ProductoController::cambiarEstado` |
| 14 | `movimientos.index` | GET `/movimientos` | UC-08 | `MovimientoController::index` |
| 15 | `movimientos.create` | GET `/movimientos/create` | UC-16, UC-17 | `MovimientoController::create` |
| 16 | `movimientos.store` | POST `/movimientos` | UC-16, UC-17, UC-18, UC-19 | `MovimientoController::store` |
| 17 | `categorias.index` | GET `/categorias` | UC-12 | `CategoriaController::index` |
| 18 | `categorias.create` | GET `/categorias/create` | UC-13 | `CategoriaController::create` |
| 19 | `categorias.store` | POST `/categorias` | UC-13 | `CategoriaController::store` |
| 20 | `categorias.edit` | GET `/categorias/{categoria}/edit` | UC-14 | `CategoriaController::edit` |
| 21 | `categorias.update` | PUT o PATCH `/categorias/{categoria}` | UC-14 | `CategoriaController::update` |
| 22 | `categorias.destroy` | DELETE `/categorias/{categoria}` | UC-15 | `CategoriaController::destroy` |
| 23 | `usuarios.index` | GET `/usuarios` | UC-20 | `UsuarioController::index` |
| 24 | `usuarios.create` | GET `/usuarios/create` | UC-21 | `UsuarioController::create` |
| 25 | `usuarios.store` | POST `/usuarios` | UC-21 | `UsuarioController::store` |
| 26 | `usuarios.edit` | GET `/usuarios/{usuario}/edit` | UC-22 (y entrada a UC-23) | `UsuarioController::edit` |
| 27 | `usuarios.update` | PUT `/usuarios/{usuario}` | UC-22 | `UsuarioController::update` |
| 28 | `usuarios.cambiar-estado` | PATCH `/usuarios/{usuario}/estado` | UC-23 | `UsuarioController::cambiarEstado` |
| 29 | `reportes.index` | GET `/reportes` | UC-24, UC-25, UC-26 (paso 1) | `ReporteController::index` |
| 30 | `reportes.stock-por-categoria` | GET `/reportes/stock-por-categoria` | UC-24 | `ReporteController::stockPorCategoria` |
| 31 | `reportes.stock-bajo` | GET `/reportes/stock-bajo` | UC-25 | `ReporteController::stockBajo` |
| 32 | `reportes.mas-movidos` | GET `/reportes/mas-movidos` | UC-26 | `ReporteController::masMovidos` |

Casos de uso **sin ruta propia**:
- UC-27: es JavaScript del navegador.
- UC-28, UC-29 y UC-30: son middleware que corren dentro de las rutas protegidas.

---

## 7. Notas para el modelador (casos de uso)

1. **Granularidad:** este paquete separa crear, editar y estado para que coincidan con las rutas y las secuencias. La versión reducida recomendada para el diagrama está en §0 (15 principales y 4 de soporte). Si se prefiere un diagrama todavía más corto, se pueden agrupar:
   - "Gestionar categorías" = UC-12 a UC-15.
   - "Gestionar usuarios" = UC-20 a UC-23 (igual que CU-07).
   - "Gestionar productos" = UC-09 a UC-11.

   Las relaciones de §5.1 se conservan apuntando al caso de uso agrupado.
2. **Transversales (UC-28 a UC-30):** no son objetivos de un actor. Se recomienda **no** dibujar 20 flechas `<<include>>`. Hay dos opciones:
   - Una nota en el límite del sistema: "Todo caso de uso con sesión verifica sesión y cuenta activa; los de administrador, además, el rol (403)".
   - Un solo ejemplo dibujado.

   Si se dibujan, se usa §5.1 tal cual.
3. **Actores:** usar *Usuario* como actor abstracto para los casos de uso compartidos (UC-01 a UC-08, UC-16, UC-17) y asociar los demás solo al Administrador. Otra opción es la generalización Administrador ──▷ Encargado (`00` §3.2).
4. **UC-06 Buscar:** la diferencia por rol (el administrador busca además categorías y usuarios) cabe en una nota o en un flujo alternativo; no hace falta otro caso de uso.
5. **No dibujar:**
   - RNF-08 (icosaedro).
   - Las rutas `/up` y `storage/{path}`.
   - Las páginas de error (son respuestas de FC-3 a FC-6).
6. **Nombres:** los casos de uso están en infinitivo y en español, como en `docs/02`. En el diagrama se puede usar el nombre corto (por ejemplo, "Registrar salida") y conservar el ID.
