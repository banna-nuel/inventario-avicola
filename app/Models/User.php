<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'rol',
        'activo'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean',
        ];
    }

    // Roles posibles (columna users.rol) con su nombre para mostrar
    public const ROLES = [
        'admin' => 'Administrador',
        'encargado' => 'Encargado de inventario',
    ];

    // Atajo para las vistas y los permisos: auth()->user()->esAdmin()
    public function esAdmin(): bool
    {
        return $this->rol === 'admin';
    }

    // Enlaces de config/navegacion.php ('barra' o 'modulos') que este usuario puede ver según su rol
    public function enlaces(string $grupo): array
    {
        return array_values(array_filter(
            config("navegacion.{$grupo}"),
            fn (array $enlace) => in_array($this->rol, $enlace['roles'], true)
        ));
    }
}
