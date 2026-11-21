<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rol extends Model
{
    protected $table = 'roles';

    protected $fillable = ['nombre', 'descripcion'];

    public const ADMIN      = 'administrador';
    public const VENDEDOR   = 'vendedor';
    public const CAJERO     = 'cajero';
    public const ALMACENERO = 'almacenero';

    // Un rol tiene muchos usuarios
    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class, 'rol_id');
    }
}
