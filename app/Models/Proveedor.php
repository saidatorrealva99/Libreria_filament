<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Proveedor extends Model
{
    // Bitácora: registra quién crea, cambia o elimina
    use \App\Models\Concerns\RegistraActividad;

    protected $table = 'proveedores';

    protected $fillable = ['ruc', 'razon_social', 'contacto', 'telefono', 'email', 'direccion', 'estado'];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // Un proveedor tiene muchas compras
    public function compras(): HasMany
    {
        return $this->hasMany(Compra::class);
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('estado', true);
    }
}
