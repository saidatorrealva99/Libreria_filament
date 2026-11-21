<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompraResource\Pages;
use App\Models\Compra;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Support\Formato;
use App\Support\Permisos;
use App\Support\TienePermisos;
use Filament\Actions as PageActions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Unique;

class CompraResource extends Resource
{
    // Permisos por rol (ver app/Support/Permisos.php)
    use TienePermisos;
    protected static string $modulo = 'compras';

    protected static ?string $model = Compra::class;

    protected static ?string $slug = 'compras';
    protected static ?string $modelLabel = 'compra';
    protected static ?string $pluralModelLabel = 'compras';
    protected static ?string $navigationGroup = 'Compras';
    protected static ?string $navigationIcon = 'heroicon-o-inbox-arrow-down';
    protected static ?int $navigationSort = 1;

    /* ===================== FORMULARIO (solo crear) ===================== */
    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Documento del proveedor')
                ->icon('heroicon-o-document-text')
                ->schema([
                    Forms\Components\Select::make('proveedor_id')
                        ->label('Proveedor')
                        ->relationship('proveedor', 'razon_social', fn (Builder $q) => $q->activos())
                        ->getOptionLabelFromRecordUsing(fn (Proveedor $r) => "{$r->razon_social} · RUC {$r->ruc}")
                        ->searchable(['razon_social', 'ruc'])
                        ->preload()
                        ->required()
                        ->live()
                        ->createOptionForm(ProveedorResource::campos())
                        ->createOptionModalHeading('Nuevo proveedor')
                        ->columnSpan(2),
                    Forms\Components\TextInput::make('numero_documento')
                        ->label('N° factura / guía')
                        ->placeholder('F001-004521')
                        ->required()
                        ->maxLength(20)
                        // la misma factura del mismo proveedor no se registra dos veces
                        ->unique(modifyRuleUsing: fn (Unique $rule, Get $get) => $rule->where('proveedor_id', $get('proveedor_id')))
                        ->validationMessages(['unique' => 'Esta factura de este proveedor ya fue registrada.']),
                    Forms\Components\DatePicker::make('fecha_compra')
                        ->label('Fecha')
                        ->default(now())
                        ->maxDate(now())
                        ->required()
                        ->native(false)
                        ->displayFormat('d/m/Y'),
                    Forms\Components\TextInput::make('observacion')
                        ->label('Observación')
                        ->maxLength(200)
                        ->columnSpanFull(),
                ])->columns(4),

            Forms\Components\Section::make('Productos comprados')
                ->icon('heroicon-o-cube')
                ->description('El stock sube automáticamente y queda registrado en el kárdex.')
                ->schema([
                    Forms\Components\Repeater::make('detalles')
                        ->relationship()
                        ->hiddenLabel()
                        ->schema([
                            Forms\Components\Select::make('producto_id')
                                ->label('Producto')
                                ->options(fn () => Producto::activos()->orderBy('nombre')->get()
                                    ->mapWithKeys(fn (Producto $p) => [$p->id => "{$p->codigo} · {$p->nombre} (stock {$p->stock})"]))
                                ->required()
                                ->searchable()
                                ->live()
                                ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                ->afterStateUpdated(function (?string $state, Get $get, Set $set) {
                                    // Sugiere el último costo (o 65% del precio si nunca se compró)
                                    $p = Producto::find($state);
                                    $set('costo_unitario', $p?->costo ?? ($p ? round((float) $p->precio_venta * 0.65, 2) : null));
                                    self::recalcular($get, $set);
                                })
                                ->columnSpan(['md' => 6]),
                            Forms\Components\TextInput::make('cantidad')
                                ->label('Cant.')
                                ->numeric()->integer()->minValue(1)->default(1)->required()
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn (Get $get, Set $set) => self::recalcular($get, $set))
                                ->columnSpan(['md' => 2]),
                            Forms\Components\TextInput::make('costo_unitario')
                                ->label('Costo unit.')
                                ->prefix('S/')
                                ->numeric()->minValue(0.01)->required()
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn (Get $get, Set $set) => self::recalcular($get, $set))
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
                        ->reorderable(false)
                        ->addActionLabel('Agregar producto')
                        ->live()
                        ->afterStateUpdated(fn (Get $get, Set $set) => self::totalCompra($get, $set))
                        ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                            $data['subtotal'] = round((int) $data['cantidad'] * (float) $data['costo_unitario'], 2);

                            return $data;
                        }),

                    Forms\Components\TextInput::make('total')
                        ->label('Total de la compra')
                        ->prefix('S/')
                        ->default('0.00')
                        ->readOnly()
                        ->extraInputAttributes(['style' => 'font-weight:700;font-size:1.1rem']),
                ]),
        ]);
    }

    public static function recalcular(Get $get, Set $set): void
    {
        $set('subtotal', number_format((int) $get('cantidad') * (float) $get('costo_unitario'), 2, '.', ''));
        self::totalCompra($get, $set, '../../');
    }

    public static function totalCompra(Get $get, Set $set, string $ruta = ''): void
    {
        $total = collect($get($ruta . 'detalles') ?? [])
            ->sum(fn ($i) => (int) ($i['cantidad'] ?? 0) * (float) ($i['costo_unitario'] ?? 0));

        $set($ruta . 'total', number_format($total, 2, '.', ''));
    }

    /* ===================== VISTA ===================== */
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            InfoSection::make('Compra')
                ->schema([
                    TextEntry::make('proveedor.razon_social')->label('Proveedor')->weight(FontWeight::Bold)
                        ->helperText(fn (Compra $record) => 'RUC ' . $record->proveedor->ruc),
                    TextEntry::make('numero_documento')->label('Documento')->copyable(),
                    TextEntry::make('fecha_compra')->label('Fecha')->date('d/m/Y'),
                    TextEntry::make('estado')->badge()
                        ->color(fn (string $state) => $state === 'anulada' ? 'danger' : 'success')
                        ->formatStateUsing(fn (string $state) => ucfirst($state)),
                    TextEntry::make('usuario.name')->label('Registró')->placeholder('—'),
                    TextEntry::make('total')->formatStateUsing(fn ($state) => Formato::soles($state))
                        ->weight(FontWeight::Bold)->color('primary'),
                    TextEntry::make('observacion')->label('Observación')->placeholder('—')->columnSpanFull(),
                ])->columns(3),
            InfoSection::make('Detalle')
                ->schema([
                    RepeatableEntry::make('detalles')
                        ->hiddenLabel()
                        ->schema([
                            TextEntry::make('producto.nombre')->label('Producto')->weight(FontWeight::SemiBold)->columnSpan(3),
                            TextEntry::make('cantidad')->label('Cant.'),
                            TextEntry::make('costo_unitario')->label('Costo')->formatStateUsing(fn ($state) => Formato::soles($state)),
                            TextEntry::make('subtotal')->label('Importe')->formatStateUsing(fn ($state) => Formato::soles($state))->weight(FontWeight::Bold),
                        ])
                        ->columns(6),
                ]),
        ]);
    }

    /* ===================== LISTADO ===================== */
    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('proveedor')->withCount('detalles'))
            ->defaultSort('fecha_compra', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('fecha_compra')->label('Fecha')->date('d/m/Y')->sortable(),
                Tables\Columns\TextColumn::make('numero_documento')
                    ->label('Documento')
                    ->fontFamily('mono')
                    ->weight(FontWeight::SemiBold)
                    ->searchable(),
                Tables\Columns\TextColumn::make('proveedor.razon_social')
                    ->label('Proveedor')
                    ->description(fn (Compra $record) => 'RUC ' . $record->proveedor->ruc)
                    ->searchable(),
                Tables\Columns\TextColumn::make('detalles_count')->label('Productos')->badge()->alignCenter(),
                Tables\Columns\TextColumn::make('total')
                    ->formatStateUsing(fn ($state) => Formato::soles($state))
                    ->weight(FontWeight::Bold)
                    ->alignEnd()
                    ->sortable()
                    ->summarize(Sum::make()->label('Total')->formatStateUsing(fn ($state) => Formato::soles($state))),
                Tables\Columns\TextColumn::make('estado')
                    ->badge()
                    ->color(fn (string $state) => $state === 'anulada' ? 'danger' : 'success')
                    ->formatStateUsing(fn (string $state) => ucfirst($state)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('proveedor_id')->label('Proveedor')->relationship('proveedor', 'razon_social'),
                Tables\Filters\SelectFilter::make('estado')->options(['registrada' => 'Registrada', 'anulada' => 'Anulada']),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->iconButton()->color('info')->tooltip('Ver compra'),
                self::accionAnular(Tables\Actions\Action::make('anular'))->iconButton(),
            ])
            ->emptyStateIcon('heroicon-o-inbox-arrow-down')
            ->emptyStateHeading('Aún no hay compras')
            ->emptyStateDescription('Registra la mercadería que llega de tus proveedores.');
    }

    public static function accionAnular(Tables\Actions\Action|PageActions\Action $action)
    {
        return $action
            ->label('Anular compra')
            ->icon('heroicon-o-no-symbol')
            ->color('danger')
            ->tooltip('Anular compra')
            ->visible(fn (Compra $record) => $record->estado === 'registrada' && Permisos::puede('compras', 'anular'))
            ->requiresConfirmation()
            ->modalHeading(fn (Compra $record) => "¿Anular la compra {$record->numero_documento}?")
            ->modalDescription('La mercadería sale del stock como devolución al proveedor.')
            ->action(function (Compra $record, $action) {
                try {
                    $record->anular();
                } catch (\RuntimeException $e) {
                    Notification::make()->danger()->title('No se puede anular')
                        ->body($e->getMessage() . ' Parte de la mercadería ya se vendió.')->send();
                    $action->halt();
                }

                Notification::make()->warning()->title('Compra anulada')->body('El stock fue descontado.')->send();
            });
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCompras::route('/'),
            'create' => Pages\CreateCompra::route('/create'),
            'view'   => Pages\ViewCompra::route('/{record}'),
        ];
    }
}
