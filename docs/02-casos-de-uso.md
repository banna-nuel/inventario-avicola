# Sistema de Gestión de Inventario — Casos de Uso

**Versión:** 1.0
**Basado en:** 01-requerimientos.md

---

## CU-01 Iniciar Sesión

- **Actores:** Administrador, Encargado de Inventario
- **Precondición:** El usuario tiene una cuenta registrada y activa.
- **Flujo principal:**
  1. El usuario ingresa correo y contraseña.
  2. El sistema valida credenciales.
  3. El sistema redirige al dashboard correspondiente según el rol.
- **Flujo alternativo:** Si las credenciales son incorrectas 3 veces seguidas, el sistema bloquea el acceso temporalmente.
- **Postcondición:** Sesión activa iniciada.

## CU-02 Registrar Producto

- **Actor:** Administrador
- **Precondición:** Sesión activa como Administrador.
- **Flujo principal:**
  1. El Administrador accede a "Productos" y selecciona "Nuevo producto".
  2. Ingresa nombre, categoría, unidad de medida, stock inicial y umbral mínimo.
  3. El sistema guarda el producto y lo muestra en el listado.
- **Flujo alternativo:** Si falta un campo obligatorio o el nombre ya existe, el sistema muestra un error y no guarda.
- **Postcondición:** Producto disponible para movimientos de inventario.

## CU-03 Editar / Desactivar Producto

- **Actor:** Administrador
- **Precondición:** El producto existe en el sistema.
- **Flujo principal:**
  1. El Administrador selecciona un producto del listado.
  2. Modifica los datos necesarios (incluyendo el umbral mínimo).
  3. El sistema guarda los cambios.
- **Flujo alternativo:** El Administrador puede desactivar un producto en lugar de eliminarlo, para conservar su historial de movimientos.
- **Postcondición:** Producto actualizado o desactivado.

## CU-04 Registrar Entrada de Stock

- **Actores:** Administrador, Encargado de Inventario
- **Precondición:** El producto existe y está activo.
- **Flujo principal:**
  1. El usuario selecciona "Nueva entrada", elige el producto y la cantidad.
  2. El sistema suma la cantidad al stock actual del producto.
  3. El sistema registra el movimiento con fecha, usuario y cantidad.
- **Postcondición:** Stock actualizado; movimiento visible en el historial.

## CU-05 Registrar Salida de Stock

- **Actores:** Administrador, Encargado de Inventario
- **Precondición:** El producto existe y tiene stock suficiente.
- **Flujo principal:**
  1. El usuario selecciona "Nueva salida", elige el producto y la cantidad.
  2. El sistema valida que haya stock suficiente.
  3. El sistema resta la cantidad del stock actual y registra el movimiento.
  4. Si el stock resultante queda por debajo del umbral mínimo del producto, el sistema genera una alerta visual.
- **Flujo alternativo:** Si la cantidad solicitada supera el stock disponible, el sistema rechaza la operación y muestra un error.
- **Postcondición:** Stock actualizado; alerta generada si corresponde.

## CU-06 Consultar Stock e Historial

- **Actores:** Administrador, Encargado de Inventario
- **Precondición:** Sesión activa.
- **Flujo principal:**
  1. El usuario accede al listado de productos.
  2. El sistema muestra stock actual, unidad y estado (normal / stock bajo) de cada producto.
  3. El usuario puede filtrar el historial de movimientos por producto, fecha o tipo (entrada/salida).
- **Postcondición:** Información de stock e historial visualizada.

## CU-07 Gestionar Usuarios

- **Actor:** Administrador
- **Precondición:** Sesión activa como Administrador.
- **Flujo principal:**
  1. El Administrador accede a "Usuarios".
  2. Crea, edita o desactiva una cuenta, asignando el rol (Administrador / Encargado).
- **Flujo alternativo:** No se permite desactivar la única cuenta de Administrador activa en el sistema.
- **Postcondición:** Cuenta creada, modificada o desactivada.

## CU-08 Generar Reportes

- **Actor:** Administrador
- **Precondición:** Sesión activa como Administrador.
- **Flujo principal:**
  1. El Administrador accede a "Reportes" y elige el tipo (stock por categoría, productos más movidos, stock bajo).
  2. El sistema genera el reporte con los datos actuales.
  3. Opcionalmente, el Administrador exporta el reporte a PDF o Excel.
- **Postcondición:** Reporte visualizado o exportado.

---

## Matriz de Trazabilidad (Requisito → Caso de Uso)

| Requisito | Caso de Uso |
|---|---|
| RF-01 | CU-01 |
| RF-04 | CU-02, CU-03 |
| RF-06 | CU-04, CU-05 |
| RF-07 | CU-05 |
| RF-08 | CU-06 |
| RF-09 | CU-07 |
| RF-10 | CU-08 |
