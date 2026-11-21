<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MovimientoResource\Pages;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Support\Permisos;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Kárdex: historial de entradas y salidas de stock (solo lectura).
 */
class MovimientoResource extends Resource
{
    protected static ?string $model = MovimientoInventario::class;

    protected static ?string $slug = 'kardex';
    protected static ?string $modelLabel = 'movimiento';
    protected static ?string $pluralModelLabel = 'kárdex de inventario';
    protected static ?string $navigationLabel = 'Kárdex';
    protected static ?string $navigationGroup = 'Catálogos';
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?int $navigationSort = 6;

    // Solo lectura: los movimientos los crean las ventas y el botón "Ingresar / dar de baja"
    public static function canViewAny(): bool
    {
        return Permisos::puede('movimientos', 'ver');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public const COLORES_MOTIVO = [
        'inicial'   => 'gray',
        'compra'    => 'success',
        'venta'     => 'info',
        'anulacion' => 'warning',
        'merma'     => 'danger',
        'devolucion' => 'danger',
    ];

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['producto', 'usuario']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('producto.nombre')
                    ->label('Producto')
                    ->weight(FontWeight::SemiBold)
                    ->description(fn (MovimientoInventario $record) => $record->producto->codigo)
                    ->searchable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('motivo')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => MovimientoInventario::MOTIVOS[$state] ?? $state)
                    ->color(fn (string $state) => self::COLORES_MOTIVO[$state] ?? 'gray')
                    ->icon(fn (MovimientoInventario $record) => $record->tipo === 'entrada' ? 'heroicon-m-arrow-down-tray' : 'heroicon-m-arrow-up-tray'),

                // + para entradas, − para salidas
                Tables\Columns\TextColumn::make('cantidad')
                    ->formatStateUsing(fn (MovimientoInventario $record) => ($record->tipo === 'entrada' ? '+' : '−') . $record->cantidad)
                    ->color(fn (MovimientoInventario $record) => $record->tipo === 'entrada' ? 'success' : 'danger')
                    ->weight(FontWeight::Bold)
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('stock_nuevo')
                    ->label('Stock')
                    ->formatStateUsing(fn (MovimientoInventario $record) => "{$record->stock_anterior} → {$record->stock_nuevo}")
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('referencia')
                    ->placeholder('—')
                    ->description(fn (MovimientoInventario $record) => $record->nota)
                    ->searchable(),

                Tables\Columns\TextColumn::make('usuario.name')
                    ->label('Registró')
                    ->placeholder('Sistema')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('producto_id')
                    ->label('Producto')
                    ->options(fn () => Producto::orderBy('nombre')->pluck('nombre', 'id')->all())
                    ->searchable(),

                Tables\Filters\SelectFilter::make('motivo')
                    ->options(MovimientoInventario::MOTIVOS),

                Tables\Filters\SelectFilter::make('tipo')
                    ->options(['entrada' => 'Entradas', 'salida' => 'Salidas']),

                Tables\Filters\Filter::make('fechas')
                    ->form([
                        Forms\Components\DatePicker::make('desde')->native(false)->displayFormat('d/m/Y'),
                        Forms\Components\DatePicker::make('hasta')->native(false)->displayFormat('d/m/Y'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['desde'] ?? null, fn ($q, $f) => $q->whereDate('created_at', '>=', $f))
                        ->when($data['hasta'] ?? null, fn ($q, $f) => $q->whereDate('created_at', '<=', $f))),
            ])
            ->emptyStateIcon('heroicon-o-clipboard-document-list')
            ->emptyStateHeading('Sin movimientos')
            ->emptyStateDescription('Aquí aparecerán los ingresos, ventas, anulaciones y mermas.');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMovimientos::route('/'),
        ];
    }
}
