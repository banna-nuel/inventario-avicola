<?php

namespace App\Http\Requests;

use App\Enums\UnidadMedida;
use App\Models\Producto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validación de crear y editar productos (CU-02 y CU-03).
 * Al crear se puede indicar un stock inicial; al editar no, porque el stock
 * solo cambia con movimientos de entrada o salida.
 */
class ProductoRequest extends FormRequest
{
    // El permiso lo da el middleware role:admin (routes/web.php)
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Nombre en una línea y sin espacios de más; números con coma decimal ("12,5") también valen
        $this->merge([
            'nombre' => trim(preg_replace('/\s+/', ' ', (string) $this->input('nombre'))),
            'stock_minimo' => str_replace(',', '.', (string) $this->input('stock_minimo')),
        ]);

        if ($this->filled('stock_inicial')) {
            $this->merge(['stock_inicial' => str_replace(',', '.', (string) $this->input('stock_inicial'))]);
        }
    }

    public function rules(): array
    {
        $producto = $this->route('producto');   // null al crear

        $numero = ['numeric', 'min:0', 'max:99999999.99', 'decimal:0,2'];

        return [
            'nombre' => ['required', 'string', 'max:150', Rule::unique('productos', 'nombre')->ignore($producto)],
            'categoria_id' => ['required', 'integer', 'exists:categorias,id'],
            'unidad_medida' => $this->reglasDeUnidad($producto),
            'stock_minimo' => ['required', ...$numero],
            'stock_inicial' => $producto ? ['prohibited'] : ['nullable', ...$numero],
        ];
    }

    // Si el producto ya tiene movimientos, su unidad queda fija: el historial está en esa unidad
    private function reglasDeUnidad(?Producto $producto): array
    {
        if ($producto && $producto->movimientos()->exists()) {
            return ['sometimes', Rule::in([$producto->unidad_medida->value])];
        }

        return ['required', Rule::enum(UnidadMedida::class)];
    }

    public function messages(): array
    {
        $numero = fn (string $campo) => [
            "{$campo}.numeric" => 'Escribe un número, por ejemplo 12,5.',
            "{$campo}.min" => 'No puede ser negativo.',
            "{$campo}.max" => 'El número es demasiado grande.',
            "{$campo}.decimal" => 'Usa máximo 2 decimales.',
        ];

        return [
            'nombre.required' => 'Escribe el nombre del producto.',
            'nombre.max' => 'El nombre no puede tener más de 150 caracteres.',
            'nombre.unique' => 'Ya existe un producto con ese nombre.',
            'categoria_id.required' => 'Elige una categoría.',
            'categoria_id.integer' => 'Elige una categoría de la lista.',
            'categoria_id.exists' => 'Elige una categoría de la lista.',
            'unidad_medida.required' => 'Elige la unidad de medida.',
            'unidad_medida.enum' => 'Elige una unidad de la lista.',
            'unidad_medida.in' => 'La unidad no se puede cambiar porque el producto ya tiene movimientos.',
            'stock_minimo.required' => 'Escribe el stock mínimo (puede ser 0).',
            'stock_inicial.prohibited' => 'El stock solo cambia con movimientos de entrada o salida.',
            ...$numero('stock_minimo'),
            ...$numero('stock_inicial'),
        ];
    }
}
