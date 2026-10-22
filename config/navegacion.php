<?php

/*
 * Navegación de la app: una sola definición para la barra flotante y las tarjetas de Home.
 *
 * - ruta: nombre de la ruta (routes/web.php); el enlace se arma con route('...')
 * - roles: quién ve el enlace. Debe coincidir con el middleware de la ruta
 *   (tests/Feature/RutasTest.php abre cada enlace con cada rol y falla si da 403 o 404).
 *
 * En las vistas se usa con auth()->user()->enlaces('barra') o ->enlaces('modulos').
 */

return [

    // Barra flotante (abajo): Inicio, Dashboard y Buscar, para los dos roles
    'barra' => [
        ['ruta' => 'home', 'etiqueta' => 'Inicio', 'icono' => 'casa', 'roles' => ['admin', 'encargado']],
        ['ruta' => 'dashboard', 'etiqueta' => 'Dashboard', 'icono' => 'panel', 'roles' => ['admin', 'encargado']],
        ['ruta' => 'search', 'etiqueta' => 'Buscar', 'icono' => 'buscar', 'roles' => ['admin', 'encargado']],
    ],

    // Tarjetas de acceso en Home (matriz de permisos: docs/uml/00-contexto-y-reglas.md, sección 3.3)
    'modulos' => [
        ['ruta' => 'productos.index', 'etiqueta' => 'Productos', 'icono' => 'caja', 'roles' => ['admin', 'encargado'],
            'descripcion' => 'Stock actual, unidades y alertas'],
        ['ruta' => 'movimientos.index', 'etiqueta' => 'Movimientos', 'icono' => 'movimientos', 'roles' => ['admin', 'encargado'],
            'descripcion' => 'Entradas, salidas e historial'],
        ['ruta' => 'categorias.index', 'etiqueta' => 'Categorías', 'icono' => 'etiqueta', 'roles' => ['admin'],
            'descripcion' => 'Organiza los productos'],
        ['ruta' => 'usuarios.index', 'etiqueta' => 'Usuarios', 'icono' => 'usuarios', 'roles' => ['admin'],
            'descripcion' => 'Cuentas, roles y acceso'],
        ['ruta' => 'reportes.index', 'etiqueta' => 'Reportes', 'icono' => 'grafica', 'roles' => ['admin'],
            'descripcion' => 'Stock por categoría y rotación'],
    ],

];
