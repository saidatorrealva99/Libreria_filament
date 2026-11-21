<?php

namespace App\Models;

use App\Models\Concerns\RegistraActividad;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CierreCaja extends Model
{
    use RegistraActividad;

    protected $table = 'cierres_caja';

    protected $fillable = [
        'fecha', 'estado', 'abierta_at', 'cerrada_at', 'user_apertura_id', 'fondo_inicial', 'efectivo_sistema', 'tarjeta_sistema', 'yape_sistema', 'plin_sistema',
        'transferencia_sistema', 'total_sistema', 'numero_pagos', 'efectivo_contado', 'diferencia', 'observacion', 'user_id',
    ];

    protected $casts = [
        'fecha'            => 'date',
        'abierta_at'       => 'datetime',
        'cerrada_at'       => 'datetime',
        'fondo_inicial'    => 'decimal:2',
        'efectivo_sistema' => 'decimal:2',
        'total_sistema'    => 'decimal:2',
        'efectivo_contado' => 'decimal:2',
        'diferencia'       => 'decimal:2',
    ];

    public function abrio(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_apertura_id');
    }

    /** La caja ABIERTA del usuario que está trabajando (cada persona tiene la suya) */
    public static function abiertaHoy(?int $userId = null): ?self
    {
        $userId ??= \Illuminate\Support\Facades\Auth::id();

        return $userId ? static::where('user_apertura_id', $userId)->where('estado', 'abierta')->latest('id')->first() : null;
    }

    /** La caja de hoy del usuario (abierta o ya cerrada) */
    public static function deHoy(?int $userId = null): ?self
    {
        $userId ??= \Illuminate\Support\Facades\Auth::id();

        return $userId ? static::where('user_apertura_id', $userId)->whereDate('fecha', today())->latest('id')->first() : null;
    }

    /** Ventas registradas en este turno */
    public function ventas(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Venta::class, 'cierre_caja_id');
    }

    /** Cobros hechos en este turno */
    public function pagos(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Pago::class, 'cierre_caja_id');
    }

    /**
     * Lo cobrado en ESTA caja (por la persona que la abrió), por método.
     *
     * @return array{efectivo: float, tarjeta: float, yape: float, plin: float, transferencia: float, total: float, pagos: int}
     */
    public function resumen(): array
    {
        $porMetodo = $this->pagos()
            ->whereHas('venta', fn ($q) => $q->where('estado', '!=', 'anulada'))
            ->selectRaw('metodo_pago, SUM(monto) as total, COUNT(*) as n')
            ->groupBy('metodo_pago')
            ->get()
            ->keyBy('metodo_pago');

        $r = [];
        foreach (array_keys(Pago::METODOS) as $m) {
            $r[$m] = round((float) ($porMetodo[$m]->total ?? 0), 2);
        }
        $r['total'] = round(array_sum($r), 2);
        $r['pagos'] = (int) $porMetodo->sum('n');

        return $r;
    }

    public static function abrir(float $fondo): self
    {
        return static::create([
            'fecha'            => today(),
            'estado'           => 'abierta',
            'abierta_at'       => now(),
            'user_apertura_id' => \Illuminate\Support\Facades\Auth::id(),
            'fondo_inicial'    => round($fondo, 2),
        ]);
    }

    /** Arqueo: compara lo contado con lo que el sistema registró y cierra la caja */
    public function cerrar(float $contado, ?string $observacion): void
    {
        $r = $this->resumen();   // solo lo que cobró esta persona en su turno

        $this->update([
            'estado'                => 'cerrada',
            'cerrada_at'            => now(),
            'user_id'               => \Illuminate\Support\Facades\Auth::id(),
            'efectivo_sistema'      => $r['efectivo'],
            'tarjeta_sistema'       => $r['tarjeta'],
            'yape_sistema'          => $r['yape'],
            'plin_sistema'          => $r['plin'],
            'transferencia_sistema' => $r['transferencia'],
            'total_sistema'         => $r['total'],
            'numero_pagos'          => $r['pagos'],
            'efectivo_contado'      => round($contado, 2),
            'diferencia'            => round(round($contado, 2) - ((float) $this->fondo_inicial + $r['efectivo']), 2),
            'observacion'           => $observacion,
        ]);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Lo que el sistema registró como cobrado ese día (pagos de ventas no anuladas), por método.
     *
     * @return array{efectivo: float, tarjeta: float, yape: float, plin: float, transferencia: float, total: float, pagos: int}
     */
    public static function resumenDelDia(Carbon|string $fecha): array
    {
        $dia = Carbon::parse($fecha);

        $porMetodo = Pago::query()
            ->whereBetween('fecha_pago', [$dia->copy()->startOfDay(), $dia->copy()->endOfDay()])
            ->whereHas('venta', fn ($q) => $q->where('estado', '!=', 'anulada'))
            ->selectRaw('metodo_pago, SUM(monto) as total, COUNT(*) as n')
            ->groupBy('metodo_pago')
            ->get()
            ->keyBy('metodo_pago');

        $r = [];
        foreach (array_keys(Pago::METODOS) as $m) {
            $r[$m] = round((float) ($porMetodo[$m]->total ?? 0), 2);
        }
        $r['total'] = round(array_sum($r), 2);
        $r['pagos'] = (int) $porMetodo->sum('n');

        return $r;
    }

    public function estadoCuadre(): string
    {
        if ($this->estado === 'abierta') {
            return 'Abierta';
        }

        $d = (float) $this->diferencia;

        return match (true) {
            abs($d) < 0.01 => 'Cuadrada',
            $d > 0         => 'Sobrante',
            default        => 'Faltante',
        };
    }
}
