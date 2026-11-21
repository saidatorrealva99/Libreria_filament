<?php

namespace App\Filament\Pages;

use App\Filament\ReporteWidgets\InventarioPorCategoriaChart;
use App\Filament\ReporteWidgets\InventarioResumen;
use App\Filament\ReporteWidgets\StockCriticoChart;
use App\Models\Producto;
use App\Support\Formato;
use App\Support\ReporteBase;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReporteInventario extends ReporteBase
{
    protected static string $modulo = 'reporte_inventario';
    protected static ?string $navigationLabel = 'Inventario';
    protected static ?string $title = 'Reporte de inventario';
    protected static ?string $slug = 'reportes/inventario';
    protected static ?string $navigationIcon = 'heroicon-o-archive-box';
    protected static ?int $navigationSort = 4;

    public function getWidgetsReporte(): array
    {
        return [
            InventarioResumen::class           => 3,
            StockCriticoChart::class           => 2,
            InventarioPorCategoriaChart::class => 1,
        ];
    }

    /** Productos con sus unidades vendidas (ventas no anuladas), según los filtros. */
    public static function consulta(array $filtros = []): Builder
    {
        $categoria = $filtros['categoria_id']['value'] ?? null;
        $bajo      = $filtros['stock_bajo']['isActive'] ?? false;
        $validas   = fn (Builder $q) => $q->whereHas('venta', fn ($v) => $v->where('estado', '!=', 'anulada'));

        return Producto::query()
            ->with('categoria')
            ->withSum(['detalles as unidades_vendidas' => $validas], 'cantidad')
            ->withSum(['detalles as importe_vendido' => $validas], 'subtotal')
            ->when($categoria, fn (Builder $q) => $q->where('categoria_id', $categoria))
            ->when($bajo, fn (Builder $q) => $q->whereColumn('stock', '<=', 'stock_minimo'));
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(static::consulta())
            ->defaultSort('unidades_vendidas', 'desc')
            ->paginationPageOptions([25, 50, 'all'])
            ->defaultPaginationPageOption(25)
            ->columns([
                Tables\Columns\TextColumn::make('codigo')
                    ->label('Código')
                    ->fontFamily('mono')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('nombre')
                    ->label('Producto')
                    ->weight(FontWeight::SemiBold)
                    ->searchable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('categoria.nombre')
                    ->label('Categoría')
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('precio_venta')
                    ->label('Precio')
                    ->formatStateUsing(fn ($state) => Formato::soles($state))
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('stock')
                    ->badge()
                    ->color(fn (Producto $record) => $record->tieneStockBajo() ? 'danger' : 'success')
                    ->icon(fn (Producto $record) => $record->tieneStockBajo() ? 'heroicon-m-exclamation-triangle' : null)
                    ->formatStateUsing(fn (Producto $record) => "{$record->stock} / mín. {$record->stock_minimo}")
                    ->alignCenter()
                    ->sortable(),

                Tables\Columns\TextColumn::make('valor_stock')
                    ->label('Valor en stock')
                    ->state(fn (Producto $record) => $record->stock * (float) $record->precio_venta)
                    ->formatStateUsing(fn ($state) => Formato::soles($state))
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('unidades_vendidas')
                    ->label('Vendidas')
                    ->default(0)
                    ->alignCenter()
                    ->sortable(),

                Tables\Columns\TextColumn::make('importe_vendido')
                    ->label('Importe vendido')
                    ->default(0)
                    ->formatStateUsing(fn ($state) => Formato::soles($state))
                    ->weight(FontWeight::SemiBold)
                    ->alignEnd()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('categoria_id')
                    ->label('Categoría')
                    ->relationship('categoria', 'nombre')
                    ->preload(),

                Tables\Filters\Filter::make('stock_bajo')
                    ->label('Solo stock bajo')
                    ->toggle()
                    ->query(fn (Builder $query) => $query->whereColumn('stock', '<=', 'stock_minimo')),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(2);
    }
}
