<?php

namespace App\View\Components;

use Illuminate\View\Component;

/**
 * Icosaedro decorativo (RNF-08): <x-icosaedro tamano="compacto" /> en Inicio y
 * <x-icosaedro tamano="grande" /> en el login.
 *
 * Es un componente "con clase": la clase prepara los datos y la vista
 * (resources/views/components/icosaedro.blade.php) solo los dibuja.
 * Aquí se calcula el icosaedro estático en SVG, que se ve mientras carga el 3D,
 * sin WebGL o con "reducir movimiento". public/js/icosaedro.js lo reemplaza por el 3D.
 */
class Icosaedro extends Component
{
    // Tienen que coincidir con public/js/icosaedro.js para que el SVG y el 3D se vean iguales
    private const ROTACION_X = 0.4;          // radianes
    private const ROTACION_Y = 0.6;
    private const DISTANCIA_CAMARA = 4;
    private const CAMPO_DE_VISION = 35;      // grados

    /**
     * Aristas listas para el SVG: [x1, y1, x2, y2, opacidad] en el viewBox -1..1.
     * (Ojo: la vista recibe las propiedades Y los métodos públicos de la clase; por eso los
     * métodos de cálculo son privados, para que ninguno se llame igual que una variable.)
     */
    public array $lineas;

    public function __construct(public string $tamano = 'compacto')
    {
        $this->lineas = $this->proyectarAristas();
    }

    public function render()
    {
        return view('components.icosaedro');
    }

    // Los 12 vértices del icosaedro: (0, ±1, ±φ) y sus rotaciones, con φ = número áureo; radio 1
    private static function vertices(): array
    {
        $phi = (1 + sqrt(5)) / 2;
        $crudos = [
            [-1, $phi, 0], [1, $phi, 0], [-1, -$phi, 0], [1, -$phi, 0],
            [0, -1, $phi], [0, 1, $phi], [0, -1, -$phi], [0, 1, -$phi],
            [$phi, 0, -1], [$phi, 0, 1], [-$phi, 0, -1], [-$phi, 0, 1],
        ];
        $radio = sqrt(1 + $phi ** 2);

        return array_map(fn ($v) => array_map(fn ($c) => $c / $radio, $v), $crudos);
    }

    // Las 30 aristas unen los pares de vértices que están a la distancia mínima (el lado)
    private static function aristas(): array
    {
        $vertices = self::vertices();
        $lado = 2 / sqrt(1 + ((1 + sqrt(5)) / 2) ** 2);
        $pares = [];

        foreach ($vertices as $i => $a) {
            foreach ($vertices as $j => $b) {
                if ($j > $i && abs(sqrt(($a[0] - $b[0]) ** 2 + ($a[1] - $b[1]) ** 2 + ($a[2] - $b[2]) ** 2) - $lado) < 0.001) {
                    $pares[] = [$i, $j];
                }
            }
        }

        return $pares;
    }

    // Gira cada vértice (primero en Y y luego en X, como Three.js) y lo proyecta con perspectiva
    private function proyectarAristas(): array
    {
        $escala = 1 / tan(deg2rad(self::CAMPO_DE_VISION / 2));
        [$senoX, $cosenoX] = [sin(self::ROTACION_X), cos(self::ROTACION_X)];
        [$senoY, $cosenoY] = [sin(self::ROTACION_Y), cos(self::ROTACION_Y)];

        $puntos = array_map(function ($v) use ($escala, $senoX, $cosenoX, $senoY, $cosenoY) {
            $x = $v[0] * $cosenoY + $v[2] * $senoY;
            $z1 = -$v[0] * $senoY + $v[2] * $cosenoY;
            $y = $v[1] * $cosenoX - $z1 * $senoX;
            $z = $v[1] * $senoX + $z1 * $cosenoX;
            $factor = $escala / (self::DISTANCIA_CAMARA - $z);

            return [$x * $factor, -$y * $factor, $z];   // en SVG la Y crece hacia abajo
        }, self::vertices());

        // Las aristas de atrás, más claras (como la niebla del 3D): da sensación de profundidad
        return array_map(function ($par) use ($puntos) {
            [$a, $b] = [$puntos[$par[0]], $puntos[$par[1]]];
            $profundidad = (($a[2] + $b[2]) / 2 + 1) / 2;   // 0 = atrás, 1 = adelante

            return [
                round($a[0], 4), round($a[1], 4), round($b[0], 4), round($b[1], 4),
                round(0.3 + 0.7 * $profundidad, 2),
            ];
        }, self::aristas());
    }
}
