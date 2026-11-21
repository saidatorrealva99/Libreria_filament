<?php

namespace App\Support;

use Filament\Notifications\Notification;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Acciones de tabla con el mismo estilo en todos los módulos:
 * iconos de color distinto (ver = azul, editar = ámbar, eliminar = rojo)
 * y eliminación segura cuando el registro tiene datos relacionados.
 */
class Acciones
{
    public static function ver(): ViewAction
    {
        return ViewAction::make()
            ->iconButton()
            ->color('info')
            ->tooltip('Ver detalles')
            ->modalWidth('3xl');
    }

    public static function editar(): EditAction
    {
        return EditAction::make()
            ->iconButton()
            ->color('warning')
            ->tooltip('Editar');
    }

    /**
     * @param  array<string, string>  $relaciones  ['productos' => 'productos', 'ventas' => 'ventas']
     */
    public static function eliminar(array $relaciones = []): DeleteAction
    {
        return DeleteAction::make()
            ->iconButton()
            ->tooltip('Eliminar')
            ->before(function (DeleteAction $action, Model $record) use ($relaciones) {
                if ($motivo = static::motivoBloqueo($record, $relaciones)) {
                    Notification::make()
                        ->danger()
                        ->title('No se puede eliminar')
                        ->body("Este registro tiene {$motivo} asociados. Puedes desactivarlo en su lugar.")
                        ->persistent()
                        ->send();

                    $action->cancel();
                }
            });
    }

    /**
     * Elimina solo los registros sin datos relacionados e informa cuántos se omitieron.
     */
    public static function eliminarMasivo(array $relaciones = []): DeleteBulkAction
    {
        return DeleteBulkAction::make()
            ->action(function (Collection $records) use ($relaciones) {
                $eliminados = 0;
                $omitidos   = 0;

                foreach ($records as $record) {
                    if (static::motivoBloqueo($record, $relaciones)) {
                        $omitidos++;
                        continue;
                    }
                    $record->delete();
                    $eliminados++;
                }

                Notification::make()
                    ->title("Eliminados: {$eliminados}")
                    ->body($omitidos ? "{$omitidos} registro(s) no se eliminaron porque tienen datos relacionados." : null)
                    ->color($omitidos ? 'warning' : 'success')
                    ->icon($omitidos ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-check-circle')
                    ->send();
            })
            ->deselectRecordsAfterCompletion();
    }

    protected static function motivoBloqueo(Model $record, array $relaciones): ?string
    {
        foreach ($relaciones as $relacion => $texto) {
            if ($record->{$relacion}()->exists()) {
                return $texto;
            }
        }

        return null;
    }
}
