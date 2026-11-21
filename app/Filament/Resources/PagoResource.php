<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PagoResource\Pages;
use App\Support\Formato;
use App\Models\Pago;
use App\Models\Venta;
use App\Support\Permisos;
use App\Support\TienePermisos;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PagoResource extends Resource
{
    // Permisos por rol (ver app/Support/Permisos.php)
    use TienePermisos;
    protected static string $modulo = 'pagos';

    protected static ?string $model = Pago::class;

    protected static ?string $slug = 'pagos';
    protected static ?string $modelLabel = 'pago';
    protected static ?string $pluralModelLabel = 'pagos';
    protected static ?string $navigationGroup = 'Ventas';
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?int $navigationSort = 2;

    public const COLORES_METODO = [
        'efectivo'      => 'success',
        'tarjeta'       => 'info',
        'yape'          => 'primary',
        'plin'          => 'primary',
        'transferencia' => 'warning',
    ];

    public const ICONOS_METODO = [
        'efectivo'      => 'heroicon-m-banknotes',
        'tarjeta'       => 'heroicon-m-credit-card',
        'yape'          => 'heroicon-m-device-phone-mobile',
        'plin'          => 'heroicon-m-device-phone-mobile',
        'transferencia' => 'heroicon-m-building-library',
    ];

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Registrar pago')
                ->description('Solo se listan ventas pendientes. Al cubrir el total, la venta pasa a "Pagada".')
                ->icon('heroicon-o-banknotes')
                ->schema([
                    Forms\Components\Select::make('venta_id')
                        ->label('Venta')
                        ->options(fn () => Venta::with('cliente')
                            ->where('estado', 'pendiente')
                            ->orderByDesc('fecha_venta')
                            ->get()
                            ->mapWithKeys(fn (Venta $v) => [
                                $v->id => "{$v->numero_comprobante} · {$v->cliente->nombre_completo} · saldo " . Formato::soles($v->saldo()),
                            ]))
                        ->required()
                        ->searchable()
                        ->live()
                        ->afterStateUpdated(fn (?string $state, Set $set) => $set('monto', Venta::find($state)?->saldo()))
                        ->columnSpanFull(),
                    Forms\Components\ToggleButtons::make('metodo_pago')
                        ->label('Método de pago')
                        ->options(Pago::METODOS)
                        ->icons(self::ICONOS_METODO)
                        ->default('efectivo')
                        ->inline()
                        ->required()
                        ->live()
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('monto')
                        ->label('Monto')
                        ->prefix('S/')
                        ->numeric()
                        ->required()
                        ->minValue(0.01)
                        ->maxValue(fn (Get $get) => Venta::find($get('venta_id'))?->saldo())
                        ->validationMessages(['max' => 'El monto no puede superar el saldo (S/ :max).']),
                    Forms\Components\TextInput::make('numero_operacion')
                        ->label('N° de operación')
                        ->placeholder('Código Yape, voucher, N° de transferencia…')
                        ->maxLength(30)
                        ->required(fn (Get $get) => $get('metodo_pago') !== 'efectivo')
                        ->disabled(fn (Get $get) => $get('metodo_pago') === 'efectivo'),
                    Forms\Components\DateTimePicker::make('fecha_pago')
                        ->label('Fecha de pago')
                        ->default(now())
                        ->required()
                        ->native(false)
                        ->displayFormat('d/m/Y H:i')
                        ->seconds(false),
                ])->columns(3),
        ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            InfolistSection::make('Pago')
                ->schema([
                    TextEntry::make('venta.numero_comprobante')->label('Comprobante')->weight(FontWeight::Bold),
                    TextEntry::make('venta.cliente.nombre_completo')->label('Cliente'),
                    TextEntry::make('metodo_pago')
                        ->label('Método')
                        ->badge()
                        ->formatStateUsing(fn (string $state) => Pago::METODOS[$state] ?? $state)
                        ->color(fn (string $state) => self::COLORES_METODO[$state] ?? 'gray')
                        ->icon(fn (string $state) => self::ICONOS_METODO[$state] ?? null),
                    TextEntry::make('numero_operacion')->label('N° de operación')->copyable()->placeholder('—'),
                    TextEntry::make('monto')
                        ->formatStateUsing(fn ($state) => Formato::soles($state))
                        ->weight(FontWeight::Bold)
                        ->color('success'),
                    TextEntry::make('fecha_pago')->label('Fecha')->dateTime('d/m/Y H:i'),
                ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['venta.cliente']))
            ->defaultSort('fecha_pago', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('fecha_pago')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('venta.numero_comprobante')
                    ->label('Comprobante')
                    ->fontFamily('mono')
                    ->weight(FontWeight::SemiBold)
                    ->url(fn (Pago $record) => VentaResource::getUrl('view', ['record' => $record->venta_id]))
                    ->color('primary')
                    ->searchable(),
                Tables\Columns\TextColumn::make('venta.cliente.nombres')
                    ->label('Cliente')
                    ->formatStateUsing(fn (Pago $record) => $record->venta->cliente->nombre_completo),
                Tables\Columns\TextColumn::make('metodo_pago')
                    ->label('Método')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => Pago::METODOS[$state] ?? $state)
                    ->color(fn (string $state) => self::COLORES_METODO[$state] ?? 'gray')
                    ->icon(fn (string $state) => self::ICONOS_METODO[$state] ?? null),
                Tables\Columns\TextColumn::make('numero_operacion')
                    ->label('Operación')
                    ->placeholder('—')
                    ->searchable(),
                Tables\Columns\TextColumn::make('monto')
                    ->formatStateUsing(fn ($state) => Formato::soles($state))
                    ->weight(FontWeight::SemiBold)
                    ->alignEnd()
                    ->sortable()
                    ->summarize(Sum::make()->label('Total')->formatStateUsing(fn ($state) => Formato::soles($state))),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('metodo_pago')->label('Método')->options(Pago::METODOS),
                VentaResource::filtroFechas('fecha_pago'),
            ])
            ->filtersFormColumns(2)
            ->actions([
                Tables\Actions\ViewAction::make()->iconButton()->color('info')->tooltip('Ver pago'),
                Tables\Actions\DeleteAction::make()
                    ->iconButton()
                    ->tooltip('Eliminar pago')
                    ->modalDescription('La venta volverá a quedar pendiente si el pago era necesario para cubrir el total.')
                    ->hidden(fn (Pago $record) => $record->venta?->estado === 'anulada'),
            ])
            ->actionsColumnLabel('Acciones')
            ->emptyStateIcon('heroicon-o-banknotes')
            ->emptyStateHeading('Aún no hay pagos');
    }

    // Vendedora y cajera: solo los cobros de hoy
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return \App\Support\Permisos::puede('historial')
            ? parent::getEloquentQuery()
            : parent::getEloquentQuery()->whereDate('fecha_pago', today());
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPagos::route('/'),
            'create' => Pages\CreatePago::route('/create'),
        ];
    }
}
