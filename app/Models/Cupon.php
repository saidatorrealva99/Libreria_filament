<?php

namespace App\Models;

use App\Models\Concerns\RegistraActividad;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cupon extends Model
{
    use RegistraActividad;

    protected $table = 'cupones';

    protected $fillable = ['codigo', 'descripcion', 'tipo', 'valor', 'minimo_compra', 'usos_maximos', 'usos', 'vence_at', 'estado'];

    protected $casts = [
        'valor'         => 'decimal:2',
        'minimo_compra' => 'decimal:2',
        'vence_at'      => 'date',
        'estado'        => 'boolean',
    ];

    public const TIPOS = ['porcentaje' => 'Porcentaje (%)', 'monto' => 'Monto fijo (S/)'];

    protected static function booted(): void
    {
        static::saving(fn (Cupon $c) => $c->codigo = strtoupper(trim($c->codigo)));
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class);
    }

    public static function buscar(?string $codigo): ?self
    {
        return filled($codigo) ? static::where('codigo', strtoupper(trim($codigo)))->first() : null;
    }

    /** null si se puede usar; si no, el motivo */
    public function motivoInvalido(float $subtotal): ?string
    {
        return match (true) {
            ! $this->estado                                          => 'Este cupón no está activo.',
            $this->vence_at && $this->vence_at->endOfDay()->isPast() => 'Este cupón venció el ' . $this->vence_at->format('d/m/Y') . '.',
            $this->usos_maximos !== null && $this->usos >= $this->usos_maximos => 'Este cupón ya alcanzó su límite de usos.',
            $subtotal < (float) $this->minimo_compra                 => 'Este cupón es para compras desde S/ ' . number_format((float) $this->minimo_compra, 2) . '.',
            default                                                  => null,
        };
    }

    public function descuentoPara(float $subtotal): float
    {
        $descuento = $this->tipo === 'porcentaje'
            ? $subtotal * (float) $this->valor / 100
            : (float) $this->valor;

        return round(min($descuento, $subtotal), 2);
    }

    public function etiqueta(): string
    {
        return $this->tipo === 'porcentaje'
            ? rtrim(rtrim(number_format((float) $this->valor, 2), '0'), '.') . '% de descuento'
            : 'S/ ' . number_format((float) $this->valor, 2) . ' de descuento';
    }
}
