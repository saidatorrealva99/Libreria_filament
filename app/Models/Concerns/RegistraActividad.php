<?php

namespace App\Models\Concerns;

use App\Models\Actividad;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

/**
 * Registra en la bitácora cuando un registro se crea, cambia o elimina.
 *
 * En el modelo se puede definir:
 *   protected array $bitacoraIgnorar = ['stock'];   // campos que no se registran
 *   public function bitacoraNombre(): string { … }  // cómo se llama el registro en la bitácora
 *
 * Usa los eventos de Eloquent: "eloquent.created: App\Models\Venta", etc.
 */
trait RegistraActividad
{
    protected static function bootRegistraActividad(): void
    {
        // Los seeders y comandos no llenan la bitácora
        if (app()->runningInConsole()) {
            return;
        }

        $clase = static::class;

        // Se creó un registro
        Event::listen("eloquent.created: {$clase}", function (Model $m) {
            Actividad::registrar('creó', $m->bitacoraModulo(), $m->getKey(), $m->bitacoraModulo() . ' ' . $m->bitacoraNombre());
        });

        // Se modificó un registro: guarda el antes y el después de cada campo
        Event::listen("eloquent.updated: {$clase}", function (Model $m) {
            $ignorar = array_merge(['updated_at', 'created_at', 'password', 'remember_token'], $m->bitacoraIgnorar ?? []);
            $cambios = [];

            foreach ($m->getChanges() as $campo => $nuevo) {
                if (in_array($campo, $ignorar, true)) {
                    continue;
                }
                $antes = $m->getOriginal($campo);
                $cambios[$campo] = [$antes instanceof \DateTimeInterface ? (string) $antes : $antes, $nuevo];
            }

            if (! $cambios) {
                return;
            }

            // Una venta que pasa a "anulada" se registra como anulación
            $accion = ($cambios['estado'][1] ?? null) === 'anulada' ? 'anuló' : 'actualizó';

            Actividad::registrar(
                $accion,
                $m->bitacoraModulo(),
                $m->getKey(),
                $m->bitacoraModulo() . ' ' . $m->bitacoraNombre() . ($accion === 'actualizó' ? ': ' . implode(', ', array_keys($cambios)) : ''),
                $cambios,
            );
        });

        // Se eliminó un registro
        Event::listen("eloquent.deleted: {$clase}", function (Model $m) {
            Actividad::registrar('eliminó', $m->bitacoraModulo(), $m->getKey(), $m->bitacoraModulo() . ' ' . $m->bitacoraNombre());
        });
    }

    public function bitacoraModulo(): string
    {
        return Str::headline(class_basename($this));
    }

    public function bitacoraNombre(): string
    {
        foreach (['numero_comprobante', 'codigo', 'numero_documento', 'nombre', 'razon_social', 'name'] as $campo) {
            if (filled($this->getAttribute($campo))) {
                return (string) $this->getAttribute($campo);
            }
        }

        return '#' . $this->getKey();
    }
}