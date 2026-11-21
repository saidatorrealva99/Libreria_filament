<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Empleado extends Model
{
    // Bitácora: registra quién crea, cambia o elimina
    use \App\Models\Concerns\RegistraActividad;

    protected $table = 'empleados';

    protected $fillable = [
        'dni',
        'nombres',
        'apellidos',
        'cargo',
        'email',
        'telefono',
        'sueldo',
        'fecha_contratacion',
        'estado',
    ];

    protected $casts = [
        'sueldo'             => 'decimal:2',
        'fecha_contratacion' => 'date',
        'estado'             => 'boolean',
    ];

    public const CARGOS = [
        'administrador' => 'Administrador',
        'vendedor'      => 'Vendedor',
        'cajero'        => 'Cajero',
        'almacenero'    => 'Almacenero',
    ];

    protected function nombreCompleto(): Attribute
    {
        return Attribute::get(fn () => trim("{$this->nombres} {$this->apellidos}"));
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class);
    }

    // Cuenta de usuario del empleado (si tiene)
    public function usuario(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('estado', true);
    }
}
