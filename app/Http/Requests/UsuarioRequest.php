<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Validación de crear y editar usuarios (CU-07, solo admin).
 * - La contraseña es obligatoria al crear (mínimo 8); al editar, vacía = no se cambia.
 * - RN-07: un administrador no puede quitarse el rol a sí mismo. Como solo un admin activo
 *   gestiona usuarios, así nunca se queda el sistema sin administrador activo.
 */
class UsuarioRequest extends FormRequest
{
    // El permiso lo da el middleware role:admin (routes/web.php)
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Nombre en una línea; correo en minúsculas y sin espacios (así "Ana@x.com" y "ana@x.com" son el mismo)
        $this->merge([
            'name' => trim(preg_replace('/\s+/', ' ', (string) $this->input('name'))),
            'email' => Str::lower(trim((string) $this->input('email'))),
        ]);
    }

    public function rules(): array
    {
        $usuario = $this->route('usuario');   // null al crear

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($usuario)],
            'rol' => ['required', Rule::in($this->esSuPropiaCuenta() ? ['admin'] : array_keys(User::ROLES))],
            'password' => [$usuario ? 'nullable' : 'required', 'string', 'min:8', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Escribe el nombre.',
            'name.max' => 'El nombre no puede tener más de 255 caracteres.',
            'email.required' => 'Escribe el correo.',
            'email.email' => 'Escribe un correo válido, por ejemplo ana@granja.com.',
            'email.max' => 'El correo no puede tener más de 255 caracteres.',
            'email.unique' => 'Ya existe un usuario con ese correo.',
            'rol.required' => 'Elige un rol.',
            'rol.in' => $this->esSuPropiaCuenta()
                ? 'No puedes quitarte el rol de administrador.'
                : 'Elige un rol de la lista.',
            'password.required' => 'Escribe una contraseña inicial.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.max' => 'La contraseña no puede tener más de 255 caracteres.',
        ];
    }

    // ¿El admin está editando su propia cuenta?
    private function esSuPropiaCuenta(): bool
    {
        return $this->route('usuario')?->is($this->user()) ?? false;
    }
}
