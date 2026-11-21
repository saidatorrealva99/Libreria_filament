<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VentaResource\Pages;
use App\Support\Formato;
use App\Models\Cliente;
use App\Models\Empleado;
use App\Models\Pago;
use App\Models\Producto;
use App\Models\Venta;
use Closure;
use Filament\Actions as PageActions;
use App\Support\Permisos;
use App\Support\TienePermisos;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class VentaResource extends Resource
{
    // Permisos por rol (ver app/Support/Permisos.php)
    use TienePermisos;
    protected static string $modulo = 'ventas';

    protected static ?string $model = Venta::class;

    protected static ?string $slug = 'ventas';
    protected static ?string $modelLabel = 'venta';
    protected static ?string $pluralModelLabel = 'ventas';
    protected static ?string $navigationGroup = 'Ventas';
    protected static ?string $navigationLabel = 'Ventas';
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?int $navigationSort = 1;
    protected static ?string $recordTitleAttribute = 'numero_comprobante';

    public const COLORES_ESTADO = ['pendiente' => 'warning', 'pagada' => 'success', 'anulada' => 'danger'];
    public const ICONOS_ESTADO  = ['pendiente' => 'heroicon-m-clock', 'pagada' => 'heroicon-m-check-badge', 'anulada' => 'heroicon-m-x-circle'];

    public static function getNavigationBadge(): ?string
    {
        $n = Venta::where('estado', 'pendiente')->count();

        return $n > 0 ? (string) $n : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Ventas pendientes de pago';
    }

    /* =====================================================================
     *  FORMULARIO (solo creación: una venta emitida no se edita, se anula)
     * ===================================================================== */
    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Group::make([
                Forms\Components\Section::make('Comprobante')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Forms\Components\ToggleButtons::make('tipo_comprobante')
                            ->label('Tipo')
                            ->options(['boleta' => 'Boleta', 'factura' => 'Factura'])
                            ->icons(['boleta' => 'heroicon-m-receipt-percent', 'factura' => 'heroicon-m-building-office'])
                            ->default('boleta')
                            ->inline()
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (?string $state, Set $set) => $set(
                                'numero_comprobante',
                                Venta::siguienteNumero($state ?? 'boleta')
                            )),
                        Forms\Components\TextInput::make('numero_comprobante')
                            ->label('Número')
                            ->default(fn () => Venta::siguienteNumero('boleta'))
                            ->readOnly()
                            ->helperText('Se asigna automáticamente.'),
                        Forms\Components\DateTimePicker::make('fecha_venta')
                            ->label('Fecha y hora')
                            ->default(now())
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y H:i')
                            ->seconds(false),
                        Forms\Components\Select::make('empleado_id')
                            ->label('Vendedor')
                            ->options(fn () => Empleado::activos()
                                ->orderBy('nombres')
                                ->get()
                                ->mapWithKeys(fn (Empleado $e) => [$e->id => "{$e->nombre_completo} · " . (Empleado::CARGOS[$e->cargo] ?? $e->cargo)]))
                            ->required()
                            ->searchable()
                            // El vendedor logueado queda como vendedor de la venta
                            ->default(fn () => \Illuminate\Support\Facades\Auth::user()?->empleado_id)
                            ->disabled(fn () => ! \Illuminate\Support\Facades\Auth::user()?->esAdmin() && (bool) \Illuminate\Support\Facades\Auth::user()?->empleado_id)
                            ->dehydrated(),
                        Forms\Components\Select::make('cliente_id')
                            ->label('Cliente')
                            ->relationship('cliente', 'nombres')
                            ->getOptionLabelFromRecordUsing(fn (Cliente $record) => $record->etiqueta)
                            ->searchable(['nombres', 'apellidos', 'numero_documento'])
                            ->preload()
                            ->required()
                            ->live()
                            ->createOptionForm(ClienteResource::campos())
                            ->createOptionModalHeading('Nuevo cliente')
                            ->rules([
                                fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                    if ($get('tipo_comprobante') === 'factura'
                                        && Cliente::find($value)?->tipo_documento !== 'RUC') {
                                        $fail('Para emitir factura el cliente debe tener RUC.');
                                    }
                                },
                            ])
                            ->columnSpan(2),
                    ])->columns(4),

                Forms\Components\Section::make('Productos')
                    ->icon('heroicon-o-book-open')
                    ->schema([
                        // Escáner de código de barras (pistola lectora, cámara o teclado). Solo al crear.
                        Forms\Components\View::make('filament.forms.escaner')
                            ->visibleOn('create'),
                        Forms\Components\Repeater::make('detalles')
                            ->relationship()
                            ->hiddenLabel()
                            ->schema([
                                Forms\Components\Select::make('producto_id')
                                    ->label('Producto')
                                    ->options(fn () => Producto::activos()
                                        ->where('stock', '>', 0)
                                        ->orderBy('nombre')
                                        ->get()
                                        ->mapWithKeys(fn (Producto $p) => [$p->id => "{$p->codigo} · {$p->nombre} ({$p->stock} und.)"]))
                                    ->required()
                                    ->searchable()
                                    ->live()
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                    ->afterStateUpdated(function (?string $state, Get $get, Set $set) {
                                        $set('precio_unitario', Producto::find($state)?->precioPara((int) ($get('cantidad') ?: 1)) ?? 0);
                                        self::recalcularLinea($get, $set);
                                    })
                                    ->columnSpan(['md' => 6]),
                                Forms\Components\TextInput::make('cantidad')
                                    ->label('Cant.')
                                    ->numeric()
                                    ->integer()
                                    ->required()
                                    ->default(1)
                                    ->minValue(1)
                                    ->maxValue(fn (Get $get) => Producto::find($get('producto_id'))?->stock)
                                    ->validationMessages(['max' => 'Solo hay :max unidades en stock.'])
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        // El precio cambia si llega al descuento por cantidad
                                        if ($p = Producto::find($get('producto_id'))) {
                                            $set('precio_unitario', $p->precioPara((int) $get('cantidad')));
                                        }
                                        self::recalcularLinea($get, $set);
                                    })
                                    ->columnSpan(['md' => 2]),
                                Forms\Components\TextInput::make('precio_unitario')
                                    ->label('P. unit.')
                                    ->prefix('S/')
                                    ->readOnly()
                                    ->columnSpan(['md' => 2]),
                                Forms\Components\TextInput::make('subtotal')
                                    ->label('Importe')
                                    ->prefix('S/')
                                    ->readOnly()
                                    ->columnSpan(['md' => 2]),
                            ])
                            ->columns(12)
                            ->defaultItems(1)
                            ->minItems(1)
                            ->required()
                            ->reorderable(false)
                            ->addActionLabel('Agregar producto')
                            ->live()
                            ->afterStateUpdated(fn (Get $get, Set $set) => self::recalcularTotales($get, $set))
                            // El precio y el importe se toman de la BD, no del navegador
                            ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                                $precio = Producto::findOrFail($data['producto_id'])->precioPara((int) $data['cantidad']);
                                $data['precio_unitario'] = $precio;
                                $data['subtotal'] = round($precio * (int) $data['cantidad'], 2);

                                return $data;
                            }),
                    ]),
            ])->columnSpan(['lg' => 2]),

            Forms\Components\Group::make([
                Forms\Components\Section::make('Resumen')
                    ->icon('heroicon-o-calculator')
                    ->schema([
                        Forms\Components\TextInput::make('subtotal')
                            ->label('Subtotal')
                            ->prefix('S/')
                            ->default('0.00')
                            ->readOnly(),
                        Forms\Components\TextInput::make('descuento')
                            ->label('Descuento')
                            ->prefix('S/')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->maxValue(fn (Get $get) => (float) $get('subtotal'))
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Get $get, Set $set) => self::recalcularTotales($get, $set)),
                        Forms\Components\TextInput::make('total')
                            ->label('Total a pagar')
                            ->prefix('S/')
                            ->default('0.00')
                            ->readOnly()
                            ->extraInputAttributes(['style' => 'font-weight:700;font-size:1.15rem']),
                    ]),

                Forms\Components\Section::make('Pago')
                    ->icon('heroicon-o-banknotes')
                    ->description('Registra el cobro ahora o déjalo pendiente.')
                    ->schema([
                        Forms\Components\Toggle::make('registrar_pago')
                            ->label('Cobrar ahora (monto total)')
                            ->default(true)
                            ->live()
                            ->dehydrated(false),
                        Forms\Components\Select::make('pago_metodo')
                            ->label('Método de pago')
                            ->options(Pago::METODOS)
                            ->default('efectivo')
                            ->native(false)
                            ->live()
                            ->required(fn (Get $get) => (bool) $get('registrar_pago'))
                            ->visible(fn (Get $get) => (bool) $get('registrar_pago'))
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('pago_operacion')
                            ->label('N° de operación')
                            ->placeholder('Código Yape, voucher…')
                            ->maxLength(30)
                            ->required(fn (Get $get) => $get('registrar_pago') && $get('pago_metodo') !== 'efectivo')
                            ->visible(fn (Get $get) => $get('registrar_pago') && $get('pago_metodo') !== 'efectivo')
                            ->dehydrated(false),
                    ]),
            ])->columnSpan(['lg' => 1]),
        ])->columns(3);
    }

    /** Importe de una línea del detalle (se llama desde dentro del repeater) */
    public static function recalcularLinea(Get $get, Set $set): void
    {
        $importe = (int) $get('cantidad') * (float) $get('precio_unitario');
        $set('subtotal', number_format($importe, 2, '.', ''));

        self::recalcularTotales($get, $set, '../../');
    }

    /** Subtotal, descuento y total de la venta */
    public static function recalcularTotales(Get $get, Set $set, string $ruta = ''): void
    {
        $subtotal = collect($get($ruta . 'detalles') ?? [])
            ->sum(fn ($item) => (int) ($item['cantidad'] ?? 0) * (float) ($item['precio_unitario'] ?? 0));

        $descuento = min((float) $get($ruta . 'descuento'), $subtotal);

        $set($ruta . 'subtotal', number_format($subtotal, 2, '.', ''));
        $set($ruta . 'total', number_format($subtotal - $descuento, 2, '.', ''));
    }

    /* =====================================================================
     *  VISTA DETALLADA
     * ===================================================================== */
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            InfolistSection::make('Comprobante')
                ->icon('heroicon-o-document-text')
                ->schema([
                    TextEntry::make('numero_comprobante')
                        ->label('Número')
                        ->weight(FontWeight::Bold)
                        ->size(TextEntry\TextEntrySize::Large)
                        ->copyable(),
                    TextEntry::make('tipo_comprobante')
                        ->label('Tipo')
                        ->badge()
                        ->formatStateUsing(fn (string $state) => ucfirst($state))
                        ->color(fn (string $state) => $state === 'factura' ? 'info' : 'gray'),
                    TextEntry::make('fecha_venta')->label('Fecha')->dateTime('d/m/Y H:i'),
                    TextEntry::make('estado')
                        ->badge()
                        ->formatStateUsing(fn (string $state) => Venta::ESTADOS[$state] ?? $state)
                        ->color(fn (string $state) => self::COLORES_ESTADO[$state] ?? 'gray')
                        ->icon(fn (string $state) => self::ICONOS_ESTADO[$state] ?? null),
                    TextEntry::make('cliente.nombre_completo')
                        ->label('Cliente')
                        ->icon('heroicon-m-user')
                        ->helperText(fn (Venta $record) => "{$record->cliente->tipo_documento} {$record->cliente->numero_documento}")
                        ->columnSpan(2),
                    TextEntry::make('empleado.nombre_completo')
                        ->label('Vendedor')
                        ->icon('heroicon-m-briefcase')
                        ->placeholder('Tienda online')
                        ->columnSpan(2),
                ])->columns(4),

            InfolistSection::make('Pedido web')
                ->icon('heroicon-o-globe-alt')
                ->visible(fn (Venta $record) => $record->esWeb())
                ->schema([
                    TextEntry::make('codigo_pedido')->label('Código de pedido')->weight(FontWeight::Bold)->copyable(),
                    TextEntry::make('estado_pedido')
                        ->label('Estado del pedido')
                        ->state(fn (Venta $record) => $record->estadoPedido())
                        ->badge()
                        ->color('primary'),
                    TextEntry::make('metodo_declarado')
                        ->label('Pago elegido')
                        ->formatStateUsing(fn (?string $state) => Venta::METODOS_WEB[$state] ?? $state),
                    TextEntry::make('operacion_declarada')
                        ->label('N° de operación declarado')
                        ->placeholder('—')
                        ->copyable(),
                    TextEntry::make('entregado_at')
                        ->label('Entregado')
                        ->dateTime('d/m/Y H:i')
                        ->placeholder('Aún no'),
                    TextEntry::make('tipo_entrega')
                        ->label('Entrega')
                        ->badge()
                        ->formatStateUsing(fn (Venta $record) => $record->esDelivery() ? '🛵 Delivery · ' . $record->zona?->distrito : '🏬 Recojo en tienda')
                        ->color(fn (Venta $record) => $record->esDelivery() ? 'warning' : 'gray'),
                    TextEntry::make('direccion_entrega')
                        ->label('Dirección de entrega')
                        ->visible(fn (Venta $record) => $record->esDelivery())
                        ->helperText(fn (Venta $record) => $record->referencia_entrega ? 'Ref.: ' . $record->referencia_entrega : null)
                        ->columnSpan(2),
                    TextEntry::make('costo_envio')
                        ->label('Costo de envío')
                        ->visible(fn (Venta $record) => $record->esDelivery())
                        ->formatStateUsing(fn ($state) => Formato::soles($state)),
                    TextEntry::make('cupon.codigo')
                        ->label('Cupón')
                        ->badge()
                        ->color('success')
                        ->placeholder('—'),
                ])->columns(5),

            InfolistSection::make('Detalle de productos')
                ->icon('heroicon-o-book-open')
                ->schema([
                    RepeatableEntry::make('detalles')
                        ->hiddenLabel()
                        ->schema([
                            TextEntry::make('producto.nombre')
                                ->label('Producto')
                                ->weight(FontWeight::SemiBold)
                                ->helperText(fn ($record) => $record->producto->codigo)
                                ->columnSpan(3),
                            TextEntry::make('cantidad')->label('Cant.'),
                            TextEntry::make('precio_unitario')
                                ->label('P. unit.')
                                ->formatStateUsing(fn ($state) => Formato::soles($state)),
                            TextEntry::make('subtotal')
                                ->label('Importe')
                                ->weight(FontWeight::Bold)
                                ->formatStateUsing(fn ($state) => Formato::soles($state)),
                        ])
                        ->columns(6),
                ]),

            InfolistSection::make('Totales y pagos')
                ->icon('heroicon-o-calculator')
                ->schema([
                    TextEntry::make('subtotal')->formatStateUsing(fn ($state) => Formato::soles($state)),
                    TextEntry::make('descuento')->formatStateUsing(fn ($state) => Formato::soles($state))->color('danger'),
                    TextEntry::make('costo_envio')->label('Delivery')->formatStateUsing(fn ($state) => Formato::soles($state))
                        ->visible(fn (Venta $record) => (float) $record->costo_envio > 0),
                    TextEntry::make('total')
                        ->formatStateUsing(fn ($state) => Formato::soles($state))
                        ->weight(FontWeight::Bold)
                        ->size(TextEntry\TextEntrySize::Large)
                        ->color('primary'),
                    TextEntry::make('saldo')
                        ->label('Saldo pendiente')
                        ->state(fn (Venta $record) => $record->estado === 'anulada' ? 0 : $record->saldo())
                        ->formatStateUsing(fn ($state) => Formato::soles($state))
                        ->color(fn ($state) => $state > 0 ? 'warning' : 'success'),
                    RepeatableEntry::make('pagos')
                        ->label('Pagos registrados')
                        ->schema([
                            TextEntry::make('fecha_pago')->label('Fecha')->dateTime('d/m/Y H:i'),
                            TextEntry::make('metodo_pago')
                                ->label('Método')
                                ->badge()
                                ->formatStateUsing(fn (string $state) => Pago::METODOS[$state] ?? $state),
                            TextEntry::make('numero_operacion')->label('Operación')->placeholder('—'),
                            TextEntry::make('monto')->formatStateUsing(fn ($state) => Formato::soles($state)),
                        ])
                        ->columns(4)
                        ->placeholder('Sin pagos registrados')
                        ->columnSpanFull(),
                ])->columns(4),
        ]);
    }

    /* =====================================================================
     *  LISTADO
     * ===================================================================== */
    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['cliente', 'empleado']))
            ->defaultSort('fecha_venta', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('numero_comprobante')
                    ->label('Comprobante')
                    ->weight(FontWeight::Bold)
                    ->fontFamily('mono')
                    ->description(fn (Venta $record) => ucfirst($record->tipo_comprobante))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('canal')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => $state === 'web' ? 'Web' : 'Tienda')
                    ->color(fn (string $state) => $state === 'web' ? 'info' : 'gray')
                    ->icon(fn (string $state) => $state === 'web' ? 'heroicon-m-globe-alt' : 'heroicon-m-building-storefront')
                    ->description(fn (Venta $record) => $record->esWeb()
                        ? $record->codigo_pedido . ($record->esDelivery() ? ' · 🛵 ' . $record->zona?->distrito : ' · 🏬 recojo')
                        : null),
                Tables\Columns\TextColumn::make('fecha_venta')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('cliente.nombres')
                    ->label('Cliente')
                    ->formatStateUsing(fn (Venta $record) => $record->cliente->nombre_completo)
                    ->description(fn (Venta $record) => "{$record->cliente->tipo_documento} {$record->cliente->numero_documento}")
                    ->searchable(query: fn (Builder $query, string $search) => $query->whereHas('cliente', fn (Builder $q) => $q
                        ->where('nombres', 'like', "%{$search}%")
                        ->orWhere('apellidos', 'like', "%{$search}%")
                        ->orWhere('numero_documento', 'like', "%{$search}%"))),
                Tables\Columns\TextColumn::make('empleado.nombres')
                    ->label('Vendedor')
                    ->formatStateUsing(fn (Venta $record) => $record->empleado?->nombre_completo ?? 'Tienda online')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->formatStateUsing(fn ($state) => Formato::soles($state))
                    ->weight(FontWeight::SemiBold)
                    ->alignEnd()
                    ->sortable(),
                Tables\Columns\TextColumn::make('estado')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => Venta::ESTADOS[$state] ?? $state)
                    ->color(fn (string $state) => self::COLORES_ESTADO[$state] ?? 'gray')
                    ->icon(fn (string $state) => self::ICONOS_ESTADO[$state] ?? null)
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('estado')->options(Venta::ESTADOS),
                Tables\Filters\SelectFilter::make('tipo_comprobante')
                    ->label('Tipo')
                    ->options(['boleta' => 'Boleta', 'factura' => 'Factura']),
                Tables\Filters\SelectFilter::make('empleado_id')
                    ->label('Vendedor')
                    ->relationship('empleado', 'nombres')
                    ->getOptionLabelFromRecordUsing(fn (Empleado $record) => $record->nombre_completo)
                    ->preload(),
                self::filtroFechas('fecha_venta'),
            ])
            ->filtersFormColumns(2)
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->iconButton()
                    ->color('info')
                    ->tooltip('Ver venta'),
                self::accionImprimir(Tables\Actions\Action::make('imprimir'))->iconButton(),
                self::accionVerificar(Tables\Actions\Action::make('verificar'))->iconButton(),
                self::accionEntregar(Tables\Actions\Action::make('entregar'))->iconButton(),
                self::accionRechazar(Tables\Actions\Action::make('rechazar'))->iconButton(),
                self::accionPagar(Tables\Actions\Action::make('pagar'))->iconButton(),
                self::accionAnular(Tables\Actions\Action::make('anular'))->iconButton(),
                self::accionWhatsapp(Tables\Actions\Action::make('whatsapp'))->iconButton(),
            ])
            ->actionsColumnLabel('Acciones')
            ->emptyStateIcon('heroicon-o-shopping-cart')
            ->emptyStateHeading('Aún no hay ventas')
            ->emptyStateDescription('Registra la primera venta con el botón "Nueva venta".');
    }

    /** Filtro "desde / hasta" reutilizable */
    public static function filtroFechas(string $columna): Tables\Filters\Filter
    {
        return Tables\Filters\Filter::make('rango_fechas')
            ->form([
                Forms\Components\DatePicker::make('desde')->label('Desde')->native(false)->displayFormat('d/m/Y'),
                Forms\Components\DatePicker::make('hasta')->label('Hasta')->native(false)->displayFormat('d/m/Y'),
            ])
            ->columns(2)
            ->columnSpan(2)
            ->query(fn (Builder $query, array $data) => $query
                ->when($data['desde'] ?? null, fn (Builder $q, $f) => $q->whereDate($columna, '>=', $f))
                ->when($data['hasta'] ?? null, fn (Builder $q, $f) => $q->whereDate($columna, '<=', $f)))
            ->indicateUsing(function (array $data): array {
                $ind = [];
                if ($data['desde'] ?? null) {
                    $ind[] = 'Desde ' . \Carbon\Carbon::parse($data['desde'])->format('d/m/Y');
                }
                if ($data['hasta'] ?? null) {
                    $ind[] = 'Hasta ' . \Carbon\Carbon::parse($data['hasta'])->format('d/m/Y');
                }

                return $ind;
            });
    }

    /* =====================================================================
     *  ACCIONES (se usan en la tabla y en la página "Ver venta")
     * ===================================================================== */
    /* ===== Pedidos web ===== */

    // ✔ La cajera revisa el código de Yape/Plin y confirma el pago
    public static function accionVerificar(Tables\Actions\Action|PageActions\Action $action)
    {
        return $action
            ->label('Verificar pago')
            ->icon('heroicon-o-check-badge')
            ->color('success')
            ->tooltip('Verificar pago web')
            ->visible(fn (Venta $record) => $record->esWeb() && $record->estado === 'pendiente'
                && $record->operacion_declarada && Permisos::puede('ventas', 'cobrar'))
            ->requiresConfirmation()
            ->modalHeading(fn (Venta $record) => "Verificar pago · {$record->codigo_pedido}")
            ->modalDescription(fn (Venta $record) => 'El cliente declaró ' . (Venta::METODOS_WEB[$record->metodo_declarado] ?? $record->metodo_declarado)
                . " con el código {$record->operacion_declarada} por " . Formato::soles($record->saldo())
                . '. Revisa que el pago haya llegado antes de confirmar.')
            ->modalSubmitActionLabel('Sí, el pago llegó')
            ->action(function (Venta $record) {
                $record->pagos()->create([
                    'metodo_pago'      => in_array($record->metodo_declarado, ['yape', 'plin']) ? $record->metodo_declarado : 'efectivo',
                    'monto'            => $record->saldo(),
                    'numero_operacion' => $record->operacion_declarada,
                    'fecha_pago'       => now(),
                ]);
                $record->refresh();

                Notification::make()->success()
                    ->title("Pedido {$record->codigo_pedido} pagado")
                    ->body('Ya está listo para que el cliente lo recoja.')
                    ->send();
            });
    }

    // 📦 El cliente recogió su pedido
    public static function accionEntregar(Tables\Actions\Action|PageActions\Action $action)
    {
        return $action
            ->label('Marcar entregado')
            ->icon('heroicon-o-hand-raised')
            ->color('primary')
            ->tooltip('Marcar como entregado')
            ->visible(fn (Venta $record) => $record->esWeb() && $record->estado === 'pagada' && ! $record->entregado_at
                && (Permisos::puede('ventas', 'cobrar') || Permisos::puede('ventas', 'crear')))
            ->requiresConfirmation()
            ->modalHeading(fn (Venta $record) => "¿Entregar el pedido {$record->codigo_pedido}?")
            ->action(function (Venta $record) {
                $record->update(['entregado_at' => now()]);
                Notification::make()->success()->title('Pedido entregado')->send();
            });
    }

    // ✖ Pago falso o pedido no recogido: se cancela y el stock vuelve
    public static function accionRechazar(Tables\Actions\Action|PageActions\Action $action)
    {
        return $action
            ->label('Rechazar pedido')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->tooltip('Rechazar pedido web')
            ->visible(fn (Venta $record) => $record->esWeb() && $record->estado === 'pendiente' && Permisos::puede('ventas', 'cobrar'))
            ->requiresConfirmation()
            ->modalHeading(fn (Venta $record) => "¿Rechazar el pedido {$record->codigo_pedido}?")
            ->modalDescription('El pedido se cancela y los productos vuelven al stock.')
            ->modalSubmitActionLabel('Sí, rechazar')
            ->action(function (Venta $record) {
                $record->anular();
                Notification::make()->warning()->title("Pedido {$record->codigo_pedido} rechazado")->body('El stock fue restituido.')->send();
            });
    }

    /** Mensaje de WhatsApp según el estado de la venta o del pedido web */
    public static function mensajeWhatsapp(Venta $venta): string
    {
        $nombre = trim(explode(' ', (string) $venta->cliente?->nombres)[0] ?? '') ?: 'cliente';
        $tienda = config('app.name');
        $total  = 'S/ ' . number_format((float) $venta->total, 2);

        if (! $venta->esWeb()) {
            return "Hola {$nombre} 👋, gracias por tu compra en {$tienda}. "
                . "Tu comprobante {$venta->numero_comprobante} por {$total} está registrado. ¡Te esperamos pronto! 📚";
        }

        return match ($venta->estadoPedido()) {
            'Listo para enviar'  => "Hola {$nombre} 👋, tu pago del pedido *{$venta->codigo_pedido}* de {$tienda} fue confirmado ✅\n"
                . "🛵 Lo enviamos a: {$venta->direccion_entrega}, {$venta->zona?->distrito} ({$venta->zona?->tiempo}).\nTotal: {$total}. ¡Gracias por tu compra!",
            'Listo para recoger' => "Hola {$nombre} 👋, tu pedido *{$venta->codigo_pedido}* de {$tienda} ya está *listo para recoger* ✅\n"
                . "📍 " . config('libreria.direccion') . "\n🕘 " . config('libreria.horario') . "\n"
                . "Total: {$total}. Muestra este código al recoger.",
            'Verificando pago'   => "Hola {$nombre} 👋, recibimos tu pedido *{$venta->codigo_pedido}* en {$tienda}. "
                . "Estamos verificando tu pago de {$total} y te avisamos en cuanto esté listo. 🙌",
            'Pendiente de pago'  => $venta->esDelivery()
                ? "Hola {$nombre} 👋, tu pedido *{$venta->codigo_pedido}* en {$tienda} por {$total} está registrado. "
                    . "🛵 Te lo llevamos a {$venta->direccion_entrega}, {$venta->zona?->distrito} y pagas al recibir."
                : "Hola {$nombre} 👋, tu pedido *{$venta->codigo_pedido}* en {$tienda} por {$total} está registrado. "
                    . "Puedes pagarlo al recoger en tienda. 📍 " . config('libreria.direccion'),
            'Entregado'          => "Hola {$nombre} 👋, gracias por recoger tu pedido *{$venta->codigo_pedido}*. ¡Que lo disfrutes! 📚 — {$tienda}",
            default              => "Hola {$nombre}, te escribimos de {$tienda} sobre tu pedido {$venta->codigo_pedido}.",
        };
    }

    public static function accionWhatsapp(Tables\Actions\Action|PageActions\Action $action)
    {
        return $action
            ->label('Avisar por WhatsApp')
            ->icon('heroicon-o-chat-bubble-oval-left-ellipsis')
            ->color('success')
            ->tooltip('Avisar al cliente por WhatsApp')
            // Solo celulares peruanos (9 dígitos que empiezan con 9)
            ->visible(fn (Venta $record) => $record->estado !== 'anulada' && (bool) preg_match('/^9[0-9]{8}$/', (string) $record->cliente?->telefono))
            ->url(fn (Venta $record) => 'https://wa.me/51' . $record->cliente?->telefono . '?text=' . rawurlencode(self::mensajeWhatsapp($record)))
            ->openUrlInNewTab();
    }

    public static function accionImprimir(Tables\Actions\Action|PageActions\Action $action)
    {
        return $action
            ->label('Imprimir')
            ->icon('heroicon-o-printer')
            ->color('gray')
            ->tooltip('Imprimir comprobante')
            ->url(fn (Venta $record) => route('comprobante', $record))
            ->openUrlInNewTab();
    }

    public static function accionPagar(Tables\Actions\Action|PageActions\Action $action)
    {
        return $action
            ->label('Registrar pago')
            ->icon('heroicon-o-banknotes')
            ->color('success')
            ->tooltip('Registrar pago')
            ->visible(fn (Venta $record) => $record->estado === 'pendiente' && Permisos::puede('ventas', 'cobrar'))
            ->modalHeading(fn (Venta $record) => "Registrar pago · {$record->numero_comprobante}")
            ->modalDescription(fn (Venta $record) => 'Saldo pendiente: ' . Formato::soles($record->saldo()))
            ->modalWidth('lg')
            ->fillForm(fn (Venta $record) => ['metodo_pago' => 'efectivo', 'monto' => $record->saldo()])
            ->form([
                Forms\Components\Select::make('metodo_pago')
                    ->label('Método')
                    ->options(Pago::METODOS)
                    ->required()
                    ->native(false)
                    ->live(),
                Forms\Components\TextInput::make('monto')
                    ->label('Monto')
                    ->prefix('S/')
                    ->numeric()
                    ->required()
                    ->minValue(0.01)
                    ->maxValue(fn (Venta $record) => $record->saldo()),
                Forms\Components\TextInput::make('numero_operacion')
                    ->label('N° de operación')
                    ->maxLength(30)
                    ->required(fn (Get $get) => $get('metodo_pago') !== 'efectivo')
                    ->visible(fn (Get $get) => $get('metodo_pago') !== 'efectivo'),
            ])
            ->action(function (Venta $record, array $data) {
                $record->pagos()->create($data + ['fecha_pago' => now()]);
                $record->refresh();

                Notification::make()
                    ->success()
                    ->title('Pago registrado')
                    ->body($record->estado === 'pagada' ? 'La venta quedó pagada.' : 'Saldo: ' . Formato::soles($record->saldo()))
                    ->send();
            });
    }

    public static function accionAnular(Tables\Actions\Action|PageActions\Action $action)
    {
        return $action
            ->label('Anular')
            ->icon('heroicon-o-no-symbol')
            ->color('danger')
            ->tooltip('Anular venta')
            ->visible(fn (Venta $record) => $record->estado !== 'anulada' && Permisos::puede('ventas', 'anular'))
            ->requiresConfirmation()
            ->modalHeading(fn (Venta $record) => "¿Anular {$record->numero_comprobante}?")
            ->modalDescription('Los productos vuelven al stock. Esta acción no se puede deshacer.')
            ->modalSubmitActionLabel('Sí, anular')
            ->action(function (Venta $record) {
                $record->anular();

                Notification::make()
                    ->warning()
                    ->title("Venta {$record->numero_comprobante} anulada")
                    ->body('El stock de los productos fue restituido.')
                    ->send();
            });
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['numero_comprobante'];
    }

    // Vendedora y cajera: solo las ventas de hoy (y pedidos web pendientes)
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->visibles();
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListVentas::route('/'),
            'create' => Pages\CreateVenta::route('/create'),
            'view'   => Pages\ViewVenta::route('/{record}'),
        ];
    }
}
