<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cliente extends Model
{
    // Bitácora: registra quién crea, cambia o elimina
    use \App\Models\Concerns\RegistraActividad;

    protected $table = 'clientes';

    protected $fillable = [
        'tipo_documento',
        'numero_documento',
        'nombres',
        'apellidos',
        'email',
        'telefono',
        'direccion',
    ];

    // Una empresa (RUC) no tiene apellidos
    protected static function booted(): void
    {
        static::saving(function (Cliente $cliente) {
            if ($cliente->tipo_documento === 'RUC') {
                $cliente->apellidos = null;
            }
        });
    }

    // Nombre completo o razón social
    protected function nombreCompleto(): Attribute
    {
        return Attribute::get(fn () => trim("{$this->nombres} {$this->apellidos}"));
    }

    // "DNI 70482915 · Lucía Ramos Quispe" (para los selects)
    protected function etiqueta(): Attribute
    {
        return Attribute::get(fn () => "{$this->tipo_documento} {$this->numero_documento} · {$this->nombre_completo}");
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class);
    }
}
