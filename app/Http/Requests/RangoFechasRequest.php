<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

/**
 * Rango de fechas del reporte "Más movidos" (GET ?desde=&hasta=, formato AAAA-MM-DD).
 * Sin fechas, son los últimos 30 días (hoy incluido) en hora de Bogotá, la zona de la app.
 */
class RangoFechasRequest extends FormRequest
{
    private const DIAS_POR_DEFECTO = 30;

    // Si el rango no es válido, vuelve al reporte con los días por defecto y muestra el error
    protected $redirectRoute = 'reportes.mas-movidos';

    // El permiso lo da el middleware role:admin (routes/web.php)
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'desde' => ['nullable', 'date_format:Y-m-d'],
            // after_or_equal solo si hay "desde"; si no, Laravel compararía contra una fecha vacía
            'hasta' => array_filter(['nullable', 'date_format:Y-m-d', $this->filled('desde') ? 'after_or_equal:desde' : null]),
        ];
    }

    public function messages(): array
    {
        return [
            'desde.date_format' => 'Escribe una fecha válida.',
            'hasta.date_format' => 'Escribe una fecha válida.',
            'hasta.after_or_equal' => '«Hasta» no puede ser anterior a «Desde».',
        ];
    }

    // Primer día del rango (a las 00:00)
    public function desde(): Carbon
    {
        $desde = $this->validated('desde');

        return $desde ? Carbon::parse($desde)->startOfDay() : today()->subDays(self::DIAS_POR_DEFECTO - 1);
    }

    // Último día del rango, incluido (a las 00:00; la consulta llega hasta antes del día siguiente)
    public function hasta(): Carbon
    {
        $hasta = $this->validated('hasta');

        return $hasta ? Carbon::parse($hasta)->startOfDay() : today();
    }
}
