<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    // Bitácora: registra quién crea, cambia o elimina
    use \App\Models\Concerns\RegistraActividad;

    protected array $bitacoraIgnorar = ['email_verified_at'];

    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'rol_id', 'empleado_id', 'activo'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'activo'            => 'boolean',
        ];
    }

    // ===== Relaciones =====
    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class, 'rol_id');
    }

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }

    // ===== Permisos =====

    // Solo los usuarios activos y con rol pueden entrar al panel
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->activo && $this->rol_id !== null;
    }

    // $user->tieneRol('vendedor', 'cajero')
    public function tieneRol(string ...$roles): bool
    {
        return in_array($this->rol?->nombre, $roles, true);
    }

    public function esAdmin(): bool
    {
        return $this->tieneRol(Rol::ADMIN);
    }
}
