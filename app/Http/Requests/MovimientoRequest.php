<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validación del formulario de entrada/salida (CU-04 y CU-05).
 * Aquí se revisa la forma de los datos. Las reglas que dependen del stock en ese
 * momento (RN-01 y producto inactivo) se revisan en MovimientoInventario::registrar(),
 * dentro de la transacción y con la fila del producto bloqueada.
 */
class MovimientoRequest extends FormRequest
{
    // Los dos roles pueden registrar movimientos (routes/web.php)
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Acepta coma decimal ("12,5") además de punto
        $this->merge(['cantidad' => str_replace(',', '.', trim((string) $this->input('cantidad')))]);
    }

    public function rules(): array
    {
        return [
            'tipo' => ['required', Rule::in(['entrada', 'salida'])],
            'producto_id' => ['required', 'integer', 'exists:productos,id'],
            'cantidad' => ['required', 'numeric', 'gt:0', 'max:99999999.99', 'decimal:0,2'],
        ];
    }

    public function messages(): array
    {
        return [
            'tipo.required' => 'Elige si es una entrada o una salida.',
            'tipo.in' => 'Elige si es una entrada o una salida.',
            'producto_id.required' => 'Elige un producto.',
            'producto_id.integer' => 'Elige un producto de la lista.',
            'producto_id.exists' => 'Elige un producto de la lista.',
            'cantidad.required' => 'Escribe la cantidad.',
            'cantidad.numeric' => 'Escribe un número, por ejemplo 12,5.',
            'cantidad.gt' => 'La cantidad debe ser mayor que 0.',
            'cantidad.max' => 'El número es demasiado grande.',
            'cantidad.decimal' => 'Usa máximo 2 decimales.',
        ];
    }
}
