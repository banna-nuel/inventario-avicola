<?php

namespace App\Enums;

/**
 * Unidades de medida de los productos (RN-08): lista cerrada, definida solo aquí.
 * En la base de datos se guarda el valor ('libras', 'kilogramos'...) en productos.unidad_medida,
 * así nunca conviven variantes como "lb" y "libras".
 *
 * Un enum es un tipo con un número fijo de valores posibles. El modelo Producto lo usa
 * como cast: $producto->unidad_medida es UnidadMedida::Libras, no el texto 'libras'.
 */
enum UnidadMedida: string
{
    case Libras = 'libras';
    case Kilogramos = 'kilogramos';
    case Unidades = 'unidades';
    case Cubetas = 'cubetas';
    case Bultos = 'bultos';
    case Litros = 'litros';
    case Dosis = 'dosis';

    // Para los <select>: ['libras' => 'Libras', 'kilogramos' => 'Kilogramos', ...]
    public static function opciones(): array
    {
        $opciones = [];
        foreach (self::cases() as $unidad) {
            $opciones[$unidad->value] = ucfirst($unidad->value);
        }

        return $opciones;
    }

    public function singular(): string
    {
        return match ($this) {
            self::Libras => 'libra',
            self::Kilogramos => 'kilogramo',
            self::Unidades => 'unidad',
            self::Cubetas => 'cubeta',
            self::Bultos => 'bulto',
            self::Litros => 'litro',
            self::Dosis => 'dosis',
        };
    }

    // Nombre de la unidad según la cantidad: 1 → "libra"; 2,5 → "libras"
    public function nombrePara(string|int|float $cantidad): string
    {
        return (float) $cantidad == 1 ? $this->singular() : $this->value;
    }

    // Cantidad con su unidad, lista para mostrar: "1.250,5 kilogramos", "1 libra"
    public function cantidad(string|int|float $cantidad): string
    {
        return self::numero($cantidad).' '.$this->nombrePara($cantidad);
    }

    // Número con coma decimal y punto de miles, sin ceros de más: 1250.50 → "1.250,5"
    public static function numero(string|int|float $cantidad): string
    {
        $texto = number_format((float) $cantidad, 2, ',', '.');

        return rtrim(rtrim($texto, '0'), ',');
    }
}
