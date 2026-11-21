<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\ProductoResource;
use App\Models\Producto;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

/**
 * Productos que hay que reponer. Si se configura el umbral al 150 % del mínimo,
 * también avisa de los que están "por agotarse".
 */
class StockBajoTable extends BaseWidget
{
    protected static ?int $sort = 8;
    protected int|string|array $columnSpan = 1;
    protected static ?string $heading = 'Por reponer';

    // Reemplazado por ReposicionWidget (inventario inteligente). Se deja oculto.
    public static function canView(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Producto::query()
                    ->activos()
                    ->whereRaw('stock <= stock_minimo * 1.5')   // bajo o por agotarse
                    ->orderByRaw('stock - stock_minimo')
            )
            ->defaultPaginationPageOption(5)
            ->paginationPageOptions([5])
            ->columns([
                Tables\Columns\TextColumn::make('nombre')
                    ->label('Producto')
                    ->weight(FontWeight::SemiBold)
                    ->description(fn (Producto $record) => $record->codigo)
                    ->wrap(),
                Tables\Columns\TextColumn::make('stock')
                    ->badge()
                    ->color(fn (Producto $record) => $record->tieneStockBajo() ? 'danger' : 'warning')
                    ->formatStateUsing(fn (Producto $record) => "{$record->stock} / {$record->stock_minimo}")
                    ->tooltip('Stock actual / mínimo')
                    ->alignEnd(),
            ])
            ->recordUrl(fn (Producto $record) => ProductoResource::getUrl('edit', ['record' => $record]))
            ->emptyStateIcon('heroicon-o-check-circle')
            ->emptyStateHeading('Inventario en orden')
            ->emptyStateDescription('Ningún producto está cerca de su stock mínimo.');
    }
}
