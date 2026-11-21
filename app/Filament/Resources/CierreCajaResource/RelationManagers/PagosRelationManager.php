<?php

namespace App\Filament\Resources\CierreCajaResource\RelationManagers;

use App\Models\Pago;
use App\Support\Formato;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Cobros que hizo esta persona en su turno (lo que se cuadra en el cierre).
 */
class PagosRelationManager extends RelationManager
{
    protected static string $relationship = 'pagos';

    protected static ?string $title = 'Cobros del turno';

    protected static ?string $icon = 'heroicon-o-banknotes';

    public function isReadOnly(): bool
    {
        return true;
    }

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return (string) $ownerRecord->pagos()->count();
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $q) => $q->with(['venta']))
            ->defaultSort('fecha_pago')
            ->columns([
                Tables\Columns\TextColumn::make('fecha_pago')->label('Hora')->dateTime('H:i'),
                Tables\Columns\TextColumn::make('venta.numero_comprobante')->label('Venta')->fontFamily('mono'),
                Tables\Columns\TextColumn::make('metodo_pago')->label('Método')->badge()
                    ->formatStateUsing(fn (string $state) => Pago::METODOS[$state] ?? $state)
                    ->color(fn (string $state) => $state === 'efectivo' ? 'success' : 'info'),
                Tables\Columns\TextColumn::make('numero_operacion')->label('N° operación')->placeholder('—'),
                Tables\Columns\TextColumn::make('monto')
                    ->formatStateUsing(fn ($state) => Formato::soles($state))
                    ->alignEnd()
                    ->summarize(Sum::make()->label('Total')->formatStateUsing(fn ($state) => Formato::soles($state))),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('metodo_pago')->label('Método')->options(Pago::METODOS),
            ])
            ->paginated([10, 25, 'all'])
            ->emptyStateHeading('Sin cobros en este turno');
    }
}
