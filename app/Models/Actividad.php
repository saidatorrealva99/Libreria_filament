<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bitácora: quién hizo qué y cuándo. Solo se escribe desde el sistema (no se edita ni elimina).
 */
class Actividad extends Model
{
    protected $table = 'actividades';

    public const UPDATED_AT = null;

    protected $fillable = ['user_id', 'accion', 'modulo', 'registro_id', 'descripcion', 'cambios', 'ip'];

    protected $casts = ['cambios' => 'array'];

    public const COLORES = [
        'creó'           => 'success',
        'actualizó'      => 'info',
        'eliminó'        => 'danger',
        'anuló'          => 'danger',
        'inició sesión'  => 'gray',
        'cerró sesión'   => 'gray',
        'login fallido'  => 'warning',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public static function registrar(string $accion, string $modulo, ?int $registroId, string $descripcion, ?array $cambios = null, ?int $userId = null): void
    {
        try {
            static::create([
                'user_id'     => $userId ?? \Illuminate\Support\Facades\Auth::id(),
                'accion'      => $accion,
                'modulo'      => $modulo,
                'registro_id' => $registroId,
                'descripcion' => mb_substr($descripcion, 0, 255),
                'cambios'     => $cambios ?: null,
                'ip'          => request()?->ip(),
            ]);
        } catch (\Throwable) {
            // La bitácora nunca debe impedir una operación
        }
    }
}
