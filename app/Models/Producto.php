<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Producto extends Model
{
    // Bitácora: registra quién crea, cambia o elimina
    use \App\Models\Concerns\RegistraActividad;

    protected array $bitacoraIgnorar = ['stock', 'costo'];

    protected $table = 'productos';

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'categoria_id',
        'autor_id',
        'isbn',
        'marca_editorial',
        'imagen',
        'precio_oferta',
        'oferta_hasta',
        'cantidad_mayor',
        'descuento_mayor',
        'precio_venta',
        'costo',
        'stock',
        'stock_minimo',
        'estado',
    ];

    protected $casts = [
        'precio_venta' => 'decimal:2',
        'costo'        => 'decimal:2',
        'precio_oferta'   => 'decimal:2',
        'oferta_hasta'    => 'date',
        'cantidad_mayor'  => 'integer',
        'descuento_mayor' => 'decimal:2',
        'stock'        => 'integer',
        'stock_minimo' => 'integer',
        'estado'       => 'boolean',
    ];

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(Autor::class);
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleVenta::class);
    }

    // URL pública de la imagen (o null si no tiene)
    public function getImagenUrlAttribute(): ?string
    {
        return $this->imagen ? Storage::disk('public')->url($this->imagen) : null;
    }

    // Margen de ganancia en % (precio vs. último costo de compra)
    public function margen(): ?float
    {
        if (! $this->costo || (float) $this->costo <= 0) {
            return null;
        }

        return round(((float) $this->precio_venta - (float) $this->costo) / (float) $this->precio_venta * 100, 1);
    }

    public function esLibro(): bool
    {
        return $this->autor_id !== null;
    }

    // Kárdex del producto
    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoInventario::class);
    }

    // Productos cuyo stock llegó al mínimo o menos
    public function scopeStockBajo(Builder $query): Builder
    {
        return $query->whereColumn('stock', '<=', 'stock_minimo');
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('estado', true);
    }

    public function tieneStockBajo(): bool
    {
        return $this->stock <= $this->stock_minimo;
    }

    /**
     * Siguiente código libre para un prefijo: LIB -> LIB-0009
     */
    public static function siguienteCodigo(string $prefijo): string
    {
        $prefijo = strtoupper($prefijo);

        $ultimo = static::where('codigo', 'like', "{$prefijo}-%")
            ->orderByDesc('codigo')
            ->value('codigo');

        $n = $ultimo ? ((int) substr($ultimo, 4)) + 1 : 1;

        return sprintf('%s-%04d', $prefijo, $n);
    }

    /* ======================= PRECIOS: ofertas y por cantidad ======================= */

    /** ¿Tiene una oferta activa hoy? */
    public function enOferta(): bool
    {
        return $this->precio_oferta !== null
            && (float) $this->precio_oferta < (float) $this->precio_venta
            && ($this->oferta_hasta === null || $this->oferta_hasta->endOfDay()->isFuture());
    }

    /** Precio de una unidad hoy (con oferta si la hay) */
    public function precioActual(): float
    {
        return (float) ($this->enOferta() ? $this->precio_oferta : $this->precio_venta);
    }

    /** % de la oferta respecto al precio normal */
    public function porcentajeOferta(): int
    {
        return $this->enOferta() ? (int) round((1 - (float) $this->precio_oferta / (float) $this->precio_venta) * 100) : 0;
    }

    public function tieneDescuentoPorCantidad(): bool
    {
        return $this->cantidad_mayor !== null && $this->descuento_mayor !== null;
    }

    /** ¿Esta cantidad alcanza el descuento por cantidad? */
    public function aplicaPorMayor(int $cantidad): bool
    {
        return $this->tieneDescuentoPorCantidad() && $cantidad >= $this->cantidad_mayor;
    }

    /** Precio unitario final para una cantidad (oferta + descuento por cantidad) */
    public function precioPara(int $cantidad): float
    {
        $precio = $this->precioActual();

        if ($this->aplicaPorMayor($cantidad)) {
            $precio *= 1 - (float) $this->descuento_mayor / 100;
        }

        return round($precio, 2);
    }

    /** Texto corto de la promoción por cantidad: "Desde 12 und.: 10% menos" */
    public function textoPorMayor(): ?string
    {
        return $this->tieneDescuentoPorCantidad()
            ? "Desde {$this->cantidad_mayor} und.: " . rtrim(rtrim(number_format((float) $this->descuento_mayor, 2), '0'), '.') . '% menos c/u'
            : null;
    }
}
