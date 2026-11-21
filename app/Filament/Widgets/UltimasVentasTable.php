<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\VentaResource;
use App\Support\Formato;
use App\Models\Venta;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class UltimasVentasTable extends BaseWidget
{
    // Solo para quien puede ver estos números (ver app/Support/Permisos.php)
    public static function canView(): bool
    {
        return \App\Support\Permisos::puede('dashboard_negocio');
    }

    protected static ?int $sort = 6;
    protected int|string|array $columnSpan = ['md' => 2, 'xl' => 2];
    protected static ?string $heading = 'Últimas ventas';

    public function table(Table $table): Table
    {
        return $table
            ->query(Venta::query()->with(['cliente', 'empleado'])->latest('fecha_venta'))
            ->defaultPaginationPageOption(5)
            ->paginationPageOptions([5])
            ->columns([
                Tables\Columns\TextColumn::make('numero_comprobante')
                    ->label('Comprobante')
                    ->fontFamily('mono')
                    ->weight(FontWeight::Bold)
                    ->description(fn (Venta $record) => $record->fecha_venta->format('d/m/Y H:i')),
                Tables\Columns\TextColumn::make('cliente.nombres')
                    ->label('Cliente')
                    ->formatStateUsing(fn (Venta $record) => $record->cliente->nombre_completo)
                    ->description(fn (Venta $record) => 'Atendió: ' . ($record->empleado?->nombres ?? 'Tienda online')),
                Tables\Columns\TextColumn::make('total')
                    ->formatStateUsing(fn ($state) => Formato::soles($state))
                    ->weight(FontWeight::SemiBold)
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('estado')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => Venta::ESTADOS[$state] ?? $state)
                    ->color(fn (string $state) => VentaResource::COLORES_ESTADO[$state] ?? 'gray')
                    ->icon(fn (string $state) => VentaResource::ICONOS_ESTADO[$state] ?? null),
            ])
            ->actions([
                Tables\Actions\Action::make('ver')
                    ->iconButton()
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->tooltip('Ver venta')
                    ->url(fn (Venta $record) => VentaResource::getUrl('view', ['record' => $record])),
            ])
            ->actionsColumnLabel('Acciones')
            ->headerActions([
                Tables\Actions\Action::make('todas')
                    ->label('Ver todas')
                    ->link()
                    ->icon('heroicon-m-arrow-right')
                    ->iconPosition('after')
                    ->url(VentaResource::getUrl('index')),
            ])
            ->emptyStateHeading('Aún no hay ventas');
    }
}
