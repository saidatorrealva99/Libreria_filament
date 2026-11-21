<?php

namespace App\Models;

use App\Models\Concerns\RegistraActividad;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Distrito con su costo de delivery.
 */
class ZonaEnvio extends Model
{
    use RegistraActividad;

    protected $table = 'zonas_envio';

    protected $fillable = ['distrito', 'costo', 'tiempo', 'estado'];

    protected $casts = ['costo' => 'decimal:2', 'estado' => 'boolean'];

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class);
    }

    public function scopeActivas(Builder $q): Builder
    {
        return $q->where('estado', true)->orderBy('distrito');
    }

    public function bitacoraNombre(): string
    {
        return $this->distrito;
    }
}
