<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

/**
 * Ajustes clave → valor. Al arrancar la app se copian sobre config('libreria.*')
 * (y 'nombre' sobre config('app.name')), así todo el sistema usa los valores del panel.
 */
class Ajuste extends Model
{
    protected $table = 'ajustes';
    protected $primaryKey = 'clave';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['clave', 'valor'];

    /** @return array<string, string|null> */
    public static function todos(): array
    {
        return static::pluck('valor', 'clave')->all();
    }

    public static function guardar(array $valores): void
    {
        foreach ($valores as $clave => $valor) {
            static::updateOrCreate(['clave' => $clave], ['valor' => $valor]);
        }

        self::aplicar();
    }

    /** Copia los ajustes guardados sobre la configuración en memoria */
    public static function aplicar(): void
    {
        try {
            if (! Schema::hasTable('ajustes')) {
                return;
            }

            foreach (self::todos() as $clave => $valor) {
                if ($valor === null || $valor === '') {
                    continue;
                }
                $clave === 'nombre'
                    ? config(['app.name' => $valor])
                    : config(["libreria.{$clave}" => $valor]);
            }
        } catch (\Throwable) {
            // Base de datos aún no disponible (por ejemplo, durante la instalación)
        }
    }
}
