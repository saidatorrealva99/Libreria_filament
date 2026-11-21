<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Autor extends Model
{
    // Bitácora: registra quién crea, cambia o elimina
    use \App\Models\Concerns\RegistraActividad;

    protected $table = 'autores';

    protected $fillable = [
        'nombres',
        'apellidos',
        'nacionalidad',
        'fecha_nacimiento',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
    ];

    // "Gabriel García Márquez"
    protected function nombreCompleto(): Attribute
    {
        return Attribute::get(fn () => trim("{$this->nombres} {$this->apellidos}"));
    }

    // Un autor tiene muchos productos (libros)
    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class);
    }
}
