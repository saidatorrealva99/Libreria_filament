<?php

namespace App\Filament\Resources\CierreCajaResource\RelationManagers;

use App\Filament\Resources\VentaResource;
use App\Models\Venta;
use App\Support\Formato;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Ventas registradas en el turno (caja) de esta persona.
 */
class VentasRelationManager extends RelationManager
{
    protected static string $relationship = 'ventas';

    protected static ?string $title = 'Ventas atendidas en este turno';

    protected static ?string $icon = 'heroicon-o-shopping-cart';

    public function isReadOnly(): bool
    {
        return true;
    }

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return (string) $ownerRecord->ventas()->count();
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $q) => $q->with(['cliente', 'empleado']))
            ->defaultSort('fecha_venta')
            ->columns([
                Tables\Columns\TextColumn::make('fecha_venta')->label('Hora')->dateTime('H:i'),
                Tables\Columns\TextColumn::make('numero_comprobante')->label('Comprobante')->fontFamily('mono')->weight(FontWeight::SemiBold),
                Tables\Columns\TextColumn::make('cliente.nombres')->label('Cliente')
                    ->formatStateUsing(fn (Venta $record) => $record->cliente?->nombre_completo ?? $record->cliente?->nombres),
                Tables\Columns\TextColumn::make('total')
                    ->formatStateUsing(fn ($state) => Formato::soles($state))
                    ->alignEnd()
                    ->summarize(Sum::make()->label('Total')->formatStateUsing(fn ($state) => Formato::soles($state))),
                Tables\Columns\TextColumn::make('estado')->badge()
                    ->formatStateUsing(fn (string $state) => Venta::ESTADOS[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) { 'pagada' => 'success', 'anulada' => 'danger', default => 'warning' }),
            ])
            ->actions([
                Tables\Actions\Action::make('ver')->iconButton()->icon('heroicon-o-eye')->color('info')
                    ->url(fn (Venta $record) => VentaResource::getUrl('view', ['record' => $record])),
            ])
            ->paginated([10, 25, 'all'])
            ->emptyStateHeading('Sin ventas en este turno');
    }
}
