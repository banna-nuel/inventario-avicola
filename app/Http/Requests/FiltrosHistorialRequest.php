<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Filtros del historial de movimientos (GET ?producto=&tipo=&desde=&hasta=).
 * Las fechas llegan como AAAA-MM-DD (input type="date") y "hasta" no puede ser anterior a "desde".
 */
class FiltrosHistorialRequest extends FormRequest
{
    // Si un filtro no es válido, vuelve al historial sin filtros y muestra el error junto al campo.
    // (Volver a la página anterior podría repetir la misma URL inválida en un ciclo.)
    protected $redirectRoute = 'movimientos.index';

    // Los dos roles ven el historial (routes/web.php)
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'producto' => ['nullable', 'integer', 'exists:productos,id'],
            'tipo' => ['nullable', Rule::in(['entrada', 'salida'])],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            // after_or_equal solo si hay "desde"; si no, Laravel compararía contra una fecha vacía
            'hasta' => array_filter(['nullable', 'date_format:Y-m-d', $this->filled('desde') ? 'after_or_equal:desde' : null]),
        ];
    }

    public function messages(): array
    {
        return [
            'producto.integer' => 'Elige un producto de la lista.',
            'producto.exists' => 'Elige un producto de la lista.',
            'tipo.in' => 'Elige entrada o salida.',
            'desde.date_format' => 'Escribe una fecha válida.',
            'hasta.date_format' => 'Escribe una fecha válida.',
            'hasta.after_or_equal' => '«Hasta» no puede ser anterior a «Desde».',
        ];
    }

    // Solo los filtros que llegaron con valor: ['producto' => '3', 'desde' => '2026-10-01']
    public function filtros(): array
    {
        return array_filter($this->validated(), fn ($valor) => $valor !== null && $valor !== '');
    }
}
