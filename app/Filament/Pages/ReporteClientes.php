<?php

namespace App\Filament\Pages;

use App\Filament\ReporteWidgets\ClientesResumen;
use App\Filament\ReporteWidgets\TipoClienteChart;
use App\Filament\ReporteWidgets\TopClientesChart;
use App\Models\Cliente;
use App\Support\Formato;
use App\Support\ReporteBase;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReporteClientes extends ReporteBase
{
    protected static string $modulo = 'reporte_clientes';
    protected static ?string $navigationLabel = 'Mejores clientes';
    protected static ?string $title = 'Reporte de clientes';
    protected static ?string $slug = 'reportes/clientes';
    protected static ?int $navigationSort = 2;

    public function getWidgetsReporte(): array
    {
        return [
            ClientesResumen::class  => 3,
            TopClientesChart::class => 2,
            TipoClienteChart::class => 1,
        ];
    }

    /** Clientes con compras (no anuladas) y sus totales, según los filtros. */
    public static function consulta(array $filtros = []): Builder
    {
        $validas = fn (Builder $q) => $q->where('estado', '!=', 'anulada');
        $tipo    = $filtros['tipo_documento']['value'] ?? null;

        return Cliente::query()
            ->whereHas('ventas', $validas)
            ->withCount(['ventas' => $validas])
            ->withSum(['ventas' => $validas], 'total')
            ->withMax(['ventas' => $validas], 'fecha_venta')
            ->when($tipo, fn (Builder $q) => $q->where('tipo_documento', $tipo));
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(static::consulta())
            ->defaultSort('ventas_sum_total', 'desc')
            ->paginated(false)
            ->columns([
                Tables\Columns\TextColumn::make('ranking')
                    ->label('#')
                    ->rowIndex(),

                Tables\Columns\TextColumn::make('nombre_completo')
                    ->label('Cliente')
                    ->weight(FontWeight::SemiBold)
                    ->description(fn (Cliente $record) => "{$record->tipo_documento} {$record->numero_documento}")
                    ->searchable(['nombres', 'apellidos', 'numero_documento']),

                Tables\Columns\TextColumn::make('ventas_count')
                    ->label('Compras')
                    ->badge()
                    ->alignCenter()
                    ->sortable(),

                Tables\Columns\TextColumn::make('ventas_sum_total')
                    ->label('Total comprado')
                    ->formatStateUsing(fn ($state) => Formato::soles($state))
                    ->weight(FontWeight::Bold)
                    ->alignEnd()
                    ->sortable(),

                Tables\Columns\TextColumn::make('ticket')
                    ->label('Ticket promedio')
                    ->state(fn (Cliente $record) => $record->ventas_count ? $record->ventas_sum_total / $record->ventas_count : 0)
                    ->formatStateUsing(fn ($state) => Formato::soles($state))
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('ventas_max_fecha_venta')
                    ->label('Última compra')
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tipo_documento')
                    ->label('Tipo de cliente')
                    ->options(['DNI' => 'Persona (DNI)', 'RUC' => 'Empresa (RUC)', 'CE' => 'Extranjero (CE)']),
            ]);
    }
}
