<?php

namespace App\View\Components;

use Illuminate\View\Component;

/**
 * Icosaedro decorativo que se transforma en huevo (RNF-08): <x-icosaedro tamano="compacto" /> en
 * Inicio y <x-icosaedro tamano="grande" /> en el login.
 *
 * Es un componente "con clase": la clase prepara los datos y la vista
 * (resources/views/components/icosaedro.blade.php) solo los dibuja.
 * Aquí se calculan los dos SVG estáticos:
 * - el icosaedro, que se ve mientras carga el 3D;
 * - el huevo, que es el estado final sin WebGL o con "reducir movimiento".
 * public/js/icosaedro.js los reemplaza por el 3D, que empieza como icosaedro y se vuelve huevo.
 */
class Icosaedro extends Component
{
    // Tienen que coincidir con public/js/icosaedro.js para que los SVG y el 3D se vean iguales
    private const ROTACION_X = 0.4;          // radianes
    private const ROTACION_Y = 0.6;
    private const DISTANCIA_CAMARA = 4;
    private const CAMPO_DE_VISION = 35;      // grados

    // Huevo: cada punto (x, y, z) de la esfera de radio 1 va a
    // x' = x·(1 − k·y), y' = ys·y, z' = z·(1 − k·y), todo multiplicado por la escala
    private const HUEVO_ASIMETRIA = 0.2;     // k: ensancha la mitad de abajo y afina la de arriba
    private const HUEVO_ALARGAMIENTO = 1.32; // ys: alto:ancho ≈ 1,3:1
    private const HUEVO_ESCALA = 0.82;       // del mismo tamaño en pantalla que el icosaedro
    private const HUEVO_INCLINACION = 0.2;   // radianes: se ve un poco desde arriba (no se vuelca)

    private const TRAMOS_POR_ARISTA = 8;     // tramos rectos con los que cada arista se curva sobre el huevo
    private const PUNTOS_SILUETA = 24;       // por cada lado del contorno del huevo

    /**
     * Aristas del icosaedro listas para el SVG: [x1, y1, x2, y2, opacidad] en el viewBox -1..1.
     * (Ojo: la vista recibe las propiedades Y los métodos públicos de la clase; por eso los
     * métodos de cálculo son privados, para que ninguno se llame igual que una variable.)
     */
    public array $lineas;

    /** Las 30 aristas curvadas sobre el huevo: [camino SVG, opacidad] */
    public array $arcos;

    /** Contorno del huevo visto desde la cámara (camino SVG cerrado) */
    public string $silueta;

    public function __construct(public string $tamano = 'compacto')
    {
        $this->lineas = $this->proyectarAristas();
        $this->arcos = $this->proyectarArcos();
        $this->silueta = self::camino(array_map(
            fn ($punto) => self::proyectar($punto, self::HUEVO_INCLINACION, 0),
            self::contornoDelHuevo(self::HUEVO_INCLINACION)
        )).'Z';
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

    // Gira un punto (primero en Y y luego en X, como Three.js) y lo proyecta con perspectiva: [x, y, z]
    private static function proyectar(array $punto, float $rotacionX, float $rotacionY): array
    {
        $escala = 1 / tan(deg2rad(self::CAMPO_DE_VISION / 2));
        [$senoX, $cosenoX] = [sin($rotacionX), cos($rotacionX)];
        [$senoY, $cosenoY] = [sin($rotacionY), cos($rotacionY)];

        $x = $punto[0] * $cosenoY + $punto[2] * $senoY;
        $z1 = -$punto[0] * $senoY + $punto[2] * $cosenoY;
        $y = $punto[1] * $cosenoX - $z1 * $senoX;
        $z = $punto[1] * $senoX + $z1 * $cosenoX;
        $factor = $escala / (self::DISTANCIA_CAMARA - $z);

        return [$x * $factor, -$y * $factor, $z];   // en SVG la Y crece hacia abajo
    }

    private function proyectarAristas(): array
    {
        $puntos = array_map(fn ($v) => self::proyectar($v, self::ROTACION_X, self::ROTACION_Y), self::vertices());

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

    // Lleva un punto de la esfera de radio 1 a la superficie del huevo (extremo ancho abajo)
    private static function aHuevo(array $punto): array
    {
        $ancho = self::HUEVO_ESCALA * (1 - self::HUEVO_ASIMETRIA * $punto[1]);

        return [$punto[0] * $ancho, $punto[1] * self::HUEVO_ESCALA * self::HUEVO_ALARGAMIENTO, $punto[2] * $ancho];
    }

    // Cada arista se parte en tramos y cada punto se lleva al huevo, como en la transformación del 3D
    private function proyectarArcos(): array
    {
        $vertices = self::vertices();

        return array_map(function ($par) use ($vertices) {
            [$a, $b] = [$vertices[$par[0]], $vertices[$par[1]]];
            $puntos = [];

            for ($i = 0; $i <= self::TRAMOS_POR_ARISTA; $i++) {
                $t = $i / self::TRAMOS_POR_ARISTA;
                $recta = [$a[0] + ($b[0] - $a[0]) * $t, $a[1] + ($b[1] - $a[1]) * $t, $a[2] + ($b[2] - $a[2]) * $t];
                $largo = sqrt($recta[0] ** 2 + $recta[1] ** 2 + $recta[2] ** 2);
                $esfera = array_map(fn ($c) => $c / $largo, $recta);
                $puntos[] = self::proyectar(self::aHuevo($esfera), self::HUEVO_INCLINACION, self::ROTACION_Y);
            }

            // Igual que en el icosaedro: los arcos de atrás, más claros
            $profundidad = (array_sum(array_column($puntos, 2)) / count($puntos) + 1) / 2;

            return [self::camino($puntos), round(0.3 + 0.7 * $profundidad, 2)];
        }, self::aristas());
    }

    /**
     * Contorno del huevo visto desde la cámara: los puntos donde la visual roza la superficie.
     * Es la línea que hace que se lea como huevo. Devuelve los puntos en el sistema del huevo.
     *
     * El huevo es de revolución: el anillo de ángulo v (0 arriba, π abajo) tiene radio
     * r = S·sen v·(1 − k·cos v) y altura Y = S·ys·cos v. Un punto del anillo es parte del contorno si
     * la visual desde la cámara es perpendicular a la normal; con la cámara en (0, h, ρ) eso pasa en
     * los dos puntos del anillo a un ángulo acos(condicion(v) / ρ) del plano que mira a la cámara.
     * Solo hay contorno donde |condicion| ≤ ρ (entre vMin y vMax). public/js/icosaedro.js hace la misma cuenta.
     */
    private static function contornoDelHuevo(float $inclinacion): array
    {
        [$k, $ys, $s] = [self::HUEVO_ASIMETRIA, self::HUEVO_ALARGAMIENTO, self::HUEVO_ESCALA];
        // La cámara vista desde el huevo (deshaciendo su inclinación): altura h y distancia ρ al eje
        $h = self::DISTANCIA_CAMARA * sin($inclinacion);
        $rho = self::DISTANCIA_CAMARA * cos($inclinacion);

        $radio = fn ($v) => $s * sin($v) * (1 - $k * cos($v));
        $altura = fn ($v) => $s * $ys * cos($v);
        // r + (normal vertical / normal radial) · (Y − h): con la normal del perfil en v
        $condicion = fn ($v) => $radio($v) + ($s * (cos($v) - $k * cos(2 * $v))) * ($altura($v) - $h) / ($s * $ys * sin($v));

        // Bisección entre un ángulo con contorno (dentro) y otro sin contorno (fuera)
        $borde = function ($dentro, $fuera) use ($condicion, $rho) {
            for ($i = 0; $i < 50; $i++) {
                $medio = ($dentro + $fuera) / 2;
                if (abs($condicion($medio)) <= $rho) {
                    $dentro = $medio;
                } else {
                    $fuera = $medio;
                }
            }

            return $dentro;
        };
        $vMin = $borde(M_PI / 2, 1e-6);
        $vMax = $borde(M_PI / 2, M_PI - 1e-6);

        $ida = [];
        $vuelta = [];
        for ($i = 0; $i <= self::PUNTOS_SILUETA; $i++) {
            // Más puntos cerca de las puntas, donde el contorno se curva más
            $v = $vMin + ($vMax - $vMin) * (1 - cos(M_PI * $i / self::PUNTOS_SILUETA)) / 2;
            $desvio = acos(max(-1, min(1, $condicion($v) / $rho)));
            [$r, $y] = [$radio($v), $altura($v)];
            $ida[] = [-$r * sin($desvio), $y, $r * cos($desvio)];
            $vuelta[] = [$r * sin($desvio), $y, $r * cos($desvio)];
        }

        // Un lado de arriba a abajo y el otro de vuelta, sin repetir las puntas
        return array_merge($ida, array_slice(array_reverse($vuelta), 1, -1));
    }

    // Camino SVG que une los puntos proyectados ("M x y L x y ..."); 3 decimales: menos de 0,2 px
    private static function camino(array $puntos): string
    {
        return 'M'.implode('L', array_map(fn ($p) => round($p[0], 3).' '.round($p[1], 3), $puntos));
    }
}
