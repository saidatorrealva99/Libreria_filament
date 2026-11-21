<?php

namespace App\Filament\Pages;

use App\Filament\ReporteWidgets\VentasPorDiaChart;
use App\Filament\ReporteWidgets\VentasPorVendedorChart;
use App\Filament\ReporteWidgets\VentasResumen;
use App\Filament\Resources\VentaResource;
use App\Models\Empleado;
use App\Models\Venta;
use App\Support\Formato;
use App\Support\ReporteBase;
use Filament\Forms;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Count;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReporteVentas extends ReporteBase
{
    protected static string $modulo = 'reporte_ventas';
    protected static ?string $navigationLabel = 'Ventas por periodo';
    protected static ?string $title = 'Reporte de ventas';
    protected static ?string $slug = 'reportes/ventas';
    protected static ?int $navigationSort = 1;

    // Widgets arriba de la tabla => columnas que ocupa cada uno (de 3)
    public function getWidgetsReporte(): array
    {
        return [
            VentasResumen::class          => 3,
            VentasPorDiaChart::class      => 2,
            VentasPorVendedorChart::class => 1,
        ];
    }

    /**
     * Consulta de ventas con los filtros del reporte.
     * La usan la tabla y los widgets, así siempre muestran lo mismo.
     */
    public static function consulta(array $filtros = []): Builder
    {
        $desde    = $filtros['fechas']['desde'] ?? null;
        $hasta    = $filtros['fechas']['hasta'] ?? null;
        $empleado = $filtros['empleado_id']['value'] ?? null;
        $tipo     = $filtros['tipo_comprobante']['value'] ?? null;

        return Venta::query()
            ->validas()
            ->when($desde, fn (Builder $q) => $q->whereDate('fecha_venta', '>=', $desde))
            ->when($hasta, fn (Builder $q) => $q->whereDate('fecha_venta', '<=', $hasta))
            ->when($empleado, fn (Builder $q) => $q->where('empleado_id', $empleado))
            ->when($tipo, fn (Builder $q) => $q->where('tipo_comprobante', $tipo));
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Venta::query()->with(['cliente', 'empleado'])->validas())
            ->defaultSort('fecha_venta', 'desc')
            ->paginationPageOptions([25, 50, 'all'])
            ->defaultPaginationPageOption(25)
            ->columns([
                Tables\Columns\TextColumn::make('fecha_venta')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('numero_comprobante')
                    ->label('Comprobante')
                    ->fontFamily('mono')
                    ->weight(FontWeight::SemiBold)
                    ->url(fn (Venta $record) => VentaResource::getUrl('view', ['record' => $record]))
                    ->searchable()
                    ->summarize(Count::make()->label('Ventas')),

                Tables\Columns\TextColumn::make('cliente.nombres')
                    ->label('Cliente')
                    ->formatStateUsing(fn (Venta $record) => $record->cliente->nombre_completo),

                Tables\Columns\TextColumn::make('empleado.nombres')
                    ->label('Vendedor')
                    ->formatStateUsing(fn (Venta $record) => $record->empleado?->nombre_completo ?? 'Tienda online'),

                Tables\Columns\TextColumn::make('subtotal')
                    ->formatStateUsing(fn ($state) => Formato::soles($state))
                    ->alignEnd()
                    ->summarize(Sum::make()->label('Subtotal')->formatStateUsing(fn ($state) => Formato::soles($state))),

                Tables\Columns\TextColumn::make('descuento')
                    ->formatStateUsing(fn ($state) => Formato::soles($state))
                    ->alignEnd()
                    ->summarize(Sum::make()->label('Descuentos')->formatStateUsing(fn ($state) => Formato::soles($state))),

                Tables\Columns\TextColumn::make('total')
                    ->formatStateUsing(fn ($state) => Formato::soles($state))
                    ->weight(FontWeight::Bold)
                    ->alignEnd()
                    ->sortable()
                    ->summarize(Sum::make()->label('Total')->formatStateUsing(fn ($state) => Formato::soles($state))),
            ])
            ->filters([
                Tables\Filters\Filter::make('fechas')
                    ->form([
                        Forms\Components\DatePicker::make('desde')->native(false)->displayFormat('d/m/Y'),
                        Forms\Components\DatePicker::make('hasta')->native(false)->displayFormat('d/m/Y'),
                    ])
                    ->columns(2)
                    ->columnSpan(2)
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['desde'] ?? null, fn ($q, $f) => $q->whereDate('fecha_venta', '>=', $f))
                        ->when($data['hasta'] ?? null, fn ($q, $f) => $q->whereDate('fecha_venta', '<=', $f))),

                Tables\Filters\SelectFilter::make('empleado_id')
                    ->label('Vendedor')
                    ->options(fn () => Empleado::orderBy('nombres')->get()->pluck('nombre_completo', 'id')->all()),

                Tables\Filters\SelectFilter::make('tipo_comprobante')
                    ->label('Tipo')
                    ->options(['boleta' => 'Boleta', 'factura' => 'Factura']),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(4);
    }
}
