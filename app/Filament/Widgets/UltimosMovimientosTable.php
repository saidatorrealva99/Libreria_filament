<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\MovimientoResource;
use App\Models\MovimientoInventario;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

/**
 * Últimas entradas y salidas del kárdex (ventas, compras, mermas, devoluciones).
 */
class UltimosMovimientosTable extends BaseWidget
{
    // Solo para quien puede ver estos números (ver app/Support/Permisos.php)
    public static function canView(): bool
    {
        return \App\Support\Permisos::puede('dashboard_inventario');
    }

    protected static ?int $sort = 9;
    protected int|string|array $columnSpan = ['md' => 2, 'xl' => 2];
    protected static ?string $heading = 'Últimos movimientos de inventario';

    public function table(Table $table): Table
    {
        return $table
            ->query(MovimientoInventario::query()->with('producto')->latest('created_at')->latest('id'))
            ->defaultPaginationPageOption(5)
            ->paginationPageOptions([5])
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->since()
                    ->tooltip(fn (MovimientoInventario $record) => $record->created_at->format('d/m/Y H:i')),
                Tables\Columns\TextColumn::make('producto.nombre')
                    ->label('Producto')
                    ->weight(FontWeight::SemiBold)
                    ->description(fn (MovimientoInventario $record) => $record->referencia)
                    ->limit(34),
                Tables\Columns\TextColumn::make('motivo')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => MovimientoInventario::MOTIVOS[$state] ?? $state)
                    ->color(fn (string $state) => MovimientoResource::COLORES_MOTIVO[$state] ?? 'gray'),
                Tables\Columns\TextColumn::make('cantidad')
                    ->label('Cant.')
                    ->formatStateUsing(fn (MovimientoInventario $record) => ($record->tipo === 'entrada' ? '+' : '−') . $record->cantidad)
                    ->color(fn (MovimientoInventario $record) => $record->tipo === 'entrada' ? 'success' : 'danger')
                    ->weight(FontWeight::Bold)
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('stock_nuevo')->label('Stock')->alignEnd(),
            ])
            ->headerActions([
                Tables\Actions\Action::make('kardex')
                    ->label('Ver kárdex')
                    ->link()
                    ->icon('heroicon-m-arrow-right')
                    ->iconPosition('after')
                    ->url(fn () => MovimientoResource::canViewAny() ? MovimientoResource::getUrl('index') : null)
                    ->visible(fn () => MovimientoResource::canViewAny()),
            ])
            ->emptyStateHeading('Sin movimientos todavía');
    }
}
