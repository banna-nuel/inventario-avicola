# Sistema de Gestión de Inventario — Documento de Requerimientos

**Versión:** 1.0
**Alcance:** Módulo de inventario (fase inicial). No incluye pedidos, entregas, pagos ni notificaciones — se evaluará agregarlos en fases posteriores.

---

## 1. Contexto

Sistema web para el control de inventario de una empresa avícola. Permite registrar productos, controlar entradas y salidas de stock, y alertar cuando el stock de un producto cae por debajo de su umbral mínimo configurado.

## 2. Actores

| Rol | Descripción |
|---|---|
| **Administrador** | Control total: gestiona productos, usuarios, categorías, reportes y movimientos de inventario. |
| **Encargado de Inventario** | Solo puede registrar entradas/salidas de stock y consultar el inventario actual. No gestiona productos, usuarios ni reportes. |

## 3. Requisitos de Usuario

Necesidades expresadas desde la perspectiva de cada actor, antes de bajarlas a comportamiento específico del sistema (eso se detalla en la sección de Requisitos Funcionales).

| ID | Actor | Necesidad |
|---|---|---|
| RU-01 | Administrador | Quiero gestionar el catálogo de productos y categorías para mantener el inventario organizado. |
| RU-02 | Administrador / Encargado | Quiero registrar entradas y salidas de stock fácilmente para que el inventario refleje la realidad del almacén. |
| RU-03 | Administrador / Encargado | Quiero ver de un vistazo qué productos tienen stock bajo, para reabastecer a tiempo y no quedarme sin producto. |
| RU-04 | Administrador | Quiero generar reportes del estado del inventario para tomar decisiones informadas sobre compras y ventas. |
| RU-05 | Administrador | Quiero gestionar las cuentas de usuario y sus roles, para controlar quién puede hacer qué dentro del sistema. |
| RU-06 | Todos | Quiero iniciar sesión de forma segura, para que la información del negocio esté protegida. |
| RU-07 | Administrador / Encargado | Quiero consultar el historial de movimientos de un producto, para entender su rotación en el tiempo. |

## 4. Requisitos Funcionales

| ID | Prioridad | Descripción |
|---|---|---|
| RF-01 | Alta | Autenticación de usuarios por correo y contraseña, diferenciando rol (Administrador / Encargado). |
| RF-02 | Media | Recuperación de contraseña por correo. |
| RF-03 | Alta | Dashboard con indicadores: total de productos, productos con stock bajo, movimientos del día. |
| RF-04 | Alta | CRUD de productos: nombre, categoría, unidad de medida (libras, unidades, kg, etc.), stock actual, umbral mínimo de stock (configurable por producto). *Solo Administrador.* |
| RF-05 | Alta | CRUD de categorías de producto. *Solo Administrador.* |
| RF-06 | Alta | Registro de movimientos de inventario: entradas y salidas de stock, con fecha, cantidad, producto y usuario que lo registró. *Administrador y Encargado.* |
| RF-07 | Alta | Alerta automática (visual, en dashboard/listado) cuando el stock de un producto cae por debajo de su umbral mínimo. |
| RF-08 | Media | Historial de movimientos por producto, filtrable por fecha y tipo (entrada/salida). |
| RF-09 | Media | Gestión de usuarios: crear/editar/desactivar cuentas y asignar rol. *Solo Administrador.* |
| RF-10 | Media | Reportes básicos: stock actual por categoría, productos con más movimientos, productos con stock bajo. *Solo Administrador.* |
| RF-11 | Baja | Exportar reportes a PDF o Excel. |

## 5. Reglas de Negocio

| ID | Regla |
|---|---|
| RN-01 | No se puede registrar una salida de stock por una cantidad mayor al stock actual disponible del producto. |
| RN-02 | El umbral de stock bajo es configurable por producto de forma individual; no existe un valor único global. Un producto está en stock bajo cuando `stock_actual < stock_minimo`. |
| RN-03 | Un producto o usuario con movimientos asociados no se elimina físicamente — se marca como inactivo (`activo = false`), para preservar el historial. |
| RN-04 | Todo registro de movimiento de inventario debe ejecutarse dentro de una transacción que también actualice el `stock_actual` del producto, para evitar desincronización entre ambos datos. |
| RN-05 | Solo el rol Administrador puede gestionar productos, categorías, usuarios y reportes. |
| RN-06 | El rol Encargado de Inventario solo puede registrar movimientos (entradas/salidas) y consultar stock/historial. |
| RN-07 | No se permite desactivar la única cuenta de Administrador activa en el sistema. |
| RN-08 | Cada producto define su propia unidad de medida al momento de crearse; no se comparte una unidad global entre productos. |
| RN-09 | Los movimientos de inventario son inmutables: no se editan ni se eliminan. Una corrección se registra con un movimiento inverso. |

## 6. Requisitos No Funcionales

| ID | Descripción |
|---|---|
| RNF-01 | Interfaz responsiva: funcional desde 320px (móvil) hasta 1920px (escritorio). |
| RNF-02 | Contraste mínimo WCAG AA (4.5:1) en toda la interfaz. |
| RNF-03 | Sistema de diseño consistente (tipografía, colores, iconografía) en todas las pantallas. |
| RNF-04 | Sesión de usuario expira tras 30 minutos de inactividad. |
| RNF-05 | Contraseñas almacenadas con hash (bcrypt, vía Laravel por defecto). |
| RNF-06 | Tiempo de carga de cualquier módulo ≤ 3 segundos. |
| RNF-07 | Feedback visual (loading, confirmación, error) ante cualquier acción del usuario. |
| RNF-08 | Inicio (versión compacta) y el login (versión grande) incluyen un elemento visual 3D interactivo (icosaedro alámbrico en gris oscuro sobre el fondo claro) que reacciona a mouse, scroll, click y arrastre táctil, con Three.js autoalojado (sin CDN) — ver sección 8. No va en el Dashboard. Es un requisito de presentación, no de funcionalidad del sistema. |

## 7. Restricciones Técnicas

- **Backend:** Laravel (PHP)
- **Base de datos:** MySQL
- **Frontend:** Blade + Tailwind CSS
- **Navegadores soportados:** Chrome, Firefox, Edge
- Sin app móvil nativa.

## 8. Paleta de Colores y Estilo Visual

**Dirección de diseño:** minimalista, monocromático, sin color — blanco, negro y escala de grises únicamente.

| Uso | Color |
|---|---|
| Fondo | `#000000` (negro puro) |
| Superficie / tarjetas | `#111111` – `#1A1A1A` (grises muy oscuros) |
| Bordes / separadores | `#333333` |
| Texto principal | `#FFFFFF` (blanco) |
| Texto secundario | `#888888` – `#AAAAAA` (gris medio) |
| Estados (alerta, éxito) | Se resuelven con intensidad/contraste (blanco brillante para alerta, gris tenue para neutral) en vez de color, manteniendo la paleta monocromática |

> **Actualización (v1):** la paleta oscura de esta sección se descartó; el diseño final sigue el Figma (tema claro, monocromático, con un acento naranja mínimo). Ver el README del proyecto.

**Elemento visual distintivo (RNF-08), actualizado:** icosaedro alámbrico 3D en gris oscuro (`#1C1C1C`) sobre el fondo claro (el blanco sobre negro quedó descartado junto con el tema oscuro). Va en **Inicio** (compacto, en el encabezado) y en el **login** (grande), con un solo componente reutilizable `x-icosaedro`; **no va en el Dashboard**. Three.js está **autoalojado** en `public/js/vendor` (versión, origen, SHA-256 y licencia en `THIRD_PARTY.md`), sin CDN ni scripts inline. Comportamiento:
- Rotación continua y lenta cuando no hay interacción.
- Con el puntero encima, la rotación sigue al cursor con suavizado.
- El scroll de la página le da un impulso de rotación, sin bloquear el scroll.
- El click da un impulso que se amortigua. En pantallas táctiles, arrastrar en horizontal lo gira.
- Con "reducir movimiento" queda estático; sin WebGL se muestra un icosaedro estático en SVG.
- Se pausa con la pestaña oculta o fuera de pantalla, y se carga después de la página (no afecta RNF-06).

Es decorativo: no recibe foco y los lectores de pantalla lo ignoran.

## 9. Fuera de Alcance (fase inicial)

- Gestión de pedidos y clientes
- Asignación y seguimiento de repartidores
- Procesamiento de pagos
- Centro de notificaciones

*Estos módulos existen en el análisis original de la materia "Análisis y Diseño de Sistemas de Información" y podrán incorporarse en fases posteriores.*
