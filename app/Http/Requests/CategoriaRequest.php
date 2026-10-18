<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validación de crear y editar categorías. Laravel la ejecuta antes de entrar al
 * controlador: si falla, vuelve al formulario con los errores y lo escrito.
 */
class CategoriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        // El permiso ya lo revisa el middleware role:admin en routes/web.php
        return true;
    }

    protected function prepareForValidation(): void
    {
        // El nombre se escribe en un textarea: quitamos saltos de línea y espacios repetidos
        $this->merge([
            'nombre' => trim(preg_replace('/\s+/', ' ', (string) $this->input('nombre'))),
        ]);
    }

    public function rules(): array
    {
        return [
            'nombre' => [
                'required',
                'string',
                'max:100',
                // Al editar, ignora la propia categoría (si no, "ya existe" con su mismo nombre)
                Rule::unique('categorias', 'nombre')->ignore($this->route('categoria')),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'Escribe el nombre de la categoría.',
            'nombre.max' => 'El nombre no puede tener más de 100 caracteres.',
            'nombre.unique' => 'Ya existe una categoría con ese nombre.',
        ];
    }
}
