<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;

/**
 * Se usa en los Resources: Filament pregunta estos métodos para
 * mostrar/ocultar el menú y los botones Crear, Editar y Eliminar.
 *
 * Cada Resource que lo use debe tener:
 *     protected static string $modulo = 'ventas';
 */
trait TienePermisos
{
    public static function canViewAny(): bool
    {
        return Permisos::puede(static::$modulo, 'ver');
    }

    public static function canView(Model $record): bool
    {
        return Permisos::puede(static::$modulo, 'ver');
    }

    public static function canCreate(): bool
    {
        return Permisos::puede(static::$modulo, 'crear');
    }

    public static function canEdit(Model $record): bool
    {
        return Permisos::puede(static::$modulo, 'editar');
    }

    public static function canDelete(Model $record): bool
    {
        return Permisos::puede(static::$modulo, 'eliminar');
    }

    public static function canDeleteAny(): bool
    {
        return Permisos::puede(static::$modulo, 'eliminar');
    }
}
