<?php

namespace App\Models;

use App\Support\DiasHabiles;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * Hoja del Libro de Reclamaciones virtual.
 */
class Reclamacion extends Model
{
    // Bitácora: registra quién crea, cambia o elimina
    use \App\Models\Concerns\RegistraActividad;

    protected $table = 'reclamaciones';

    public const PLAZO_DIAS_HABILES = 15;

    protected $fillable = [
        'tipo_documento', 'numero_documento', 'nombres', 'apellidos', 'telefono', 'email', 'direccion',
        'menor_edad', 'apoderado', 'bien_tipo', 'bien_descripcion', 'monto', 'pedido_referencia',
        'tipo', 'detalle', 'pedido', 'estado', 'respuesta', 'respondido_at', 'respondido_por',
        'acepta_terminos', 'ip',
    ];

    protected $casts = [
        'menor_edad'      => 'boolean',
        'acepta_terminos' => 'boolean',
        'monto'           => 'decimal:2',
        'vence_at'        => 'date',
        'respondido_at'   => 'datetime',
    ];

    public const TIPOS = [
        'reclamo' => 'Reclamo',
        'queja'   => 'Queja',
    ];

    public const TIPOS_AYUDA = [
        'reclamo' => 'Disconformidad con el producto o servicio adquirido.',
        'queja'   => 'Malestar por la atención recibida, no relacionado al producto.',
    ];

    public const ESTADOS = [
        'pendiente'  => 'Pendiente',
        'en_proceso' => 'En proceso',
        'atendido'   => 'Atendido',
    ];

    public const DOCUMENTOS = [
        'DNI'       => 'DNI',
        'CE'        => 'Carné de extranjería',
        'PASAPORTE' => 'Pasaporte',
        'RUC'       => 'RUC',
    ];

    /** Correlativo por año y fecha límite de respuesta (15 días hábiles) */
    protected static function booted(): void
    {
        static::creating(function (Reclamacion $r) {
            $r->anio     = (int) now()->format('Y');
            $r->numero   = (int) DB::table('reclamaciones')->where('anio', $r->anio)->lockForUpdate()->max('numero') + 1;
            $r->codigo   = sprintf('%06d-%d', $r->numero, $r->anio);
            $r->vence_at = DiasHabiles::sumar(now(), self::PLAZO_DIAS_HABILES);
            $r->estado ??= 'pendiente';
        });
    }

    public function respondidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'respondido_por');
    }

    protected function consumidor(): Attribute
    {
        return Attribute::get(fn () => trim("{$this->nombres} {$this->apellidos}"));
    }

    public function atendido(): bool
    {
        return $this->estado === 'atendido';
    }

    /** Días hábiles que quedan para responder (negativo = vencido) */
    public function diasRestantes(): int
    {
        return DiasHabiles::entre(now(), $this->vence_at);
    }

    public function vencido(): bool
    {
        return ! $this->atendido() && $this->vence_at->endOfDay()->isPast();
    }
}
