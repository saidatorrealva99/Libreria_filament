<?php

namespace App\Filament\Pages;

use App\Filament\ReporteWidgets\CajaPorDiaChart;
use App\Filament\ReporteWidgets\CajaPorMetodoChart;
use App\Filament\ReporteWidgets\CajaResumen;
use App\Filament\Resources\PagoResource;
use App\Models\Pago;
use App\Support\Formato;
use App\Support\ReporteBase;
use Filament\Forms;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReporteCaja extends ReporteBase
{
    protected static string $modulo = 'reporte_caja';
    protected static ?string $navigationLabel = 'Caja por método de pago';
    protected static ?string $title = 'Reporte de caja';
    protected static ?string $slug = 'reportes/caja';
    protected static ?int $navigationSort = 3;

    public function getWidgetsReporte(): array
    {
        return [
            CajaResumen::class        => 3,
            CajaPorDiaChart::class    => 2,
            CajaPorMetodoChart::class => 1,
        ];
    }

    /** Pagos de ventas no anuladas, según los filtros. */
    public static function consulta(array $filtros = []): Builder
    {
        $desde  = $filtros['fechas']['desde'] ?? null;
        $hasta  = $filtros['fechas']['hasta'] ?? null;
        $metodo = $filtros['metodo_pago']['value'] ?? null;

        return Pago::query()
            ->whereHas('venta', fn ($q) => $q->validas())
            ->when($desde, fn (Builder $q) => $q->whereDate('fecha_pago', '>=', $desde))
            ->when($hasta, fn (Builder $q) => $q->whereDate('fecha_pago', '<=', $hasta))
            ->when($metodo, fn (Builder $q) => $q->where('metodo_pago', $metodo));
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Pago::query()->with('venta.cliente')->whereHas('venta', fn ($q) => $q->validas()))
            ->defaultSort('fecha_pago', 'desc')
            ->groups([
                Group::make('metodo_pago')
                    ->label('Método')
                    ->getTitleFromRecordUsing(fn (Pago $record) => Pago::METODOS[$record->metodo_pago] ?? $record->metodo_pago)
                    ->collapsible(),
                Group::make('fecha_pago')
                    ->label('Día')
                    ->date()
                    ->collapsible(),
            ])
            ->defaultGroup('metodo_pago')
            ->paginated(false)
            ->columns([
                Tables\Columns\TextColumn::make('fecha_pago')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i'),

                Tables\Columns\TextColumn::make('venta.numero_comprobante')
                    ->label('Comprobante')
                    ->fontFamily('mono'),

                Tables\Columns\TextColumn::make('venta.cliente.nombres')
                    ->label('Cliente')
                    ->formatStateUsing(fn (Pago $record) => $record->venta->cliente->nombre_completo),

                Tables\Columns\TextColumn::make('metodo_pago')
                    ->label('Método')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => Pago::METODOS[$state] ?? $state)
                    ->color(fn (string $state) => PagoResource::COLORES_METODO[$state] ?? 'gray'),

                Tables\Columns\TextColumn::make('numero_operacion')
                    ->label('Operación')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('monto')
                    ->formatStateUsing(fn ($state) => Formato::soles($state))
                    ->weight(FontWeight::SemiBold)
                    ->alignEnd()
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
                        ->when($data['desde'] ?? null, fn ($q, $f) => $q->whereDate('fecha_pago', '>=', $f))
                        ->when($data['hasta'] ?? null, fn ($q, $f) => $q->whereDate('fecha_pago', '<=', $f))),

                Tables\Filters\SelectFilter::make('metodo_pago')
                    ->label('Método')
                    ->options(Pago::METODOS),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(3);
    }
}
