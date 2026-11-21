<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductoResource\Pages;
use App\Support\Acciones;
use App\Support\Formato;
use App\Models\Autor;
use App\Models\Categoria;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use Filament\Notifications\Notification;
use App\Support\Permisos;
use App\Support\TienePermisos;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ProductoResource extends Resource
{
    // Permisos por rol (ver app/Support/Permisos.php)
    use TienePermisos;
    protected static string $modulo = 'productos';

    protected static ?string $model = Producto::class;

    protected static ?string $slug = 'productos';
    protected static ?string $modelLabel = 'producto';
    protected static ?string $pluralModelLabel = 'productos';
    protected static ?string $navigationGroup = 'Catálogos';
    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?int $navigationSort = 3;
    protected static ?string $recordTitleAttribute = 'nombre';

    // Insignia roja en el menú con los productos con stock bajo
    public static function getNavigationBadge(): ?string
    {
        $n = Producto::activos()->stockBajo()->count();

        return $n > 0 ? (string) $n : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Productos con stock bajo';
    }

    /** Prefijo del código según la categoría: LIB, UTI, PAP, ART, OFI, MOC, TEC */
    public static function prefijoPorCategoria(?int $categoriaId): string
    {
        $nombre = Str::lower(Str::ascii((string) Categoria::find($categoriaId)?->nombre));

        return match (true) {
            str_contains($nombre, 'util')     => 'UTI',
            str_contains($nombre, 'papel')    => 'PAP',
            str_contains($nombre, 'arte')     => 'ART',
            str_contains($nombre, 'oficina')  => 'OFI',
            str_contains($nombre, 'mochila')  => 'MOC',
            str_contains($nombre, 'tecnolog') => 'TEC',
            default                           => 'LIB',
        };
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Group::make([
                Forms\Components\Section::make('Identificación')
                    ->description('Código interno y datos que verá el cliente.')
                    ->icon('heroicon-o-identification')
                    ->schema([
                        Forms\Components\TextInput::make('codigo')
                            ->label('Código')
                            ->required()
                            ->maxLength(20)
                            ->unique(ignoreRecord: true)
                            ->placeholder('LIB-0001')
                            ->regex('/^[A-Z]{3}-[0-9]{4}$/')
                            ->validationMessages(['regex' => 'Formato AAA-0000 (ej. LIB-0001, UTI-0001).'])
                            ->extraInputAttributes(['style' => 'text-transform:uppercase'])
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (?string $state, Set $set) => $set('codigo', Str::upper((string) $state)))
                            ->suffixAction(
                                Forms\Components\Actions\Action::make('generarCodigo')
                                    ->icon('heroicon-m-sparkles')
                                    ->tooltip('Generar código según la categoría')
                                    ->action(fn (Get $get, Set $set) => $set(
                                        'codigo',
                                        Producto::siguienteCodigo(self::prefijoPorCategoria($get('categoria_id')))
                                    ))
                            ),
                        Forms\Components\TextInput::make('nombre')
                            ->label('Nombre / Título')
                            ->required()
                            ->maxLength(150)
                            ->columnSpan(2),
                        Forms\Components\Textarea::make('descripcion')
                            ->label('Descripción')
                            ->maxLength(255)
                            ->rows(2)
                            ->columnSpanFull(),
                    ])->columns(3),

                Forms\Components\Section::make('Clasificación')
                    ->description('Autor e ISBN solo aplican a libros.')
                    ->icon('heroicon-o-squares-2x2')
                    ->schema([
                        Forms\Components\Select::make('categoria_id')
                            ->label('Categoría')
                            ->relationship('categoria', 'nombre', fn (Builder $query) => $query->where('estado', true))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->createOptionForm(CategoriaResource::campos())
                            ->createOptionModalHeading('Nueva categoría'),
                        Forms\Components\Select::make('autor_id')
                            ->label('Autor')
                            ->relationship('autor', 'apellidos')
                            ->getOptionLabelFromRecordUsing(fn (Autor $record) => $record->nombre_completo)
                            ->searchable(['nombres', 'apellidos'])
                            ->preload()
                            ->placeholder('Sin autor (útiles / papelería)')
                            ->createOptionForm(AutorResource::campos())
                            ->createOptionModalHeading('Nuevo autor'),
                        Forms\Components\TextInput::make('isbn')
                            ->label('ISBN')
                            ->maxLength(17)
                            ->unique(ignoreRecord: true)
                            ->placeholder('978-6124215037')
                            ->regex('/^97[89][0-9-]{10,14}$/')
                            ->validationMessages(['regex' => 'El ISBN debe empezar con 978 o 979.'])
                            ->requiredWith('autor_id')
                            ->nullable(),
                        Forms\Components\TextInput::make('marca_editorial')
                            ->label('Editorial / Marca')
                            ->maxLength(100)
                            ->placeholder('Ej. Alfaguara, Standford'),
                    ])->columns(2),
            ])->columnSpan(['lg' => 2]),

            Forms\Components\Group::make([
                Forms\Components\Section::make('Ofertas y descuentos')
                    ->icon('heroicon-o-tag')
                    ->description('Se aplican solos en la tienda online y en "Nueva venta".')
                    ->collapsible()
                    ->schema([
                        Forms\Components\TextInput::make('precio_oferta')
                            ->label('Precio de oferta')
                            ->prefix('S/')
                            ->numeric()
                            ->minValue(0.01)
                            ->lt('precio_venta')
                            ->validationMessages(['lt' => 'La oferta debe ser menor que el precio de venta.'])
                            ->helperText(fn (Forms\Get $get) => filled($get('precio_oferta')) && (float) $get('precio_venta') > 0
                                ? '−' . round((1 - (float) $get('precio_oferta') / (float) $get('precio_venta')) * 100) . '% de descuento'
                                : 'Vacío = sin oferta.')
                            ->live(onBlur: true),
                        Forms\Components\DatePicker::make('oferta_hasta')
                            ->label('Oferta válida hasta')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->helperText('Vacío = hasta que la quites.'),
                        Forms\Components\TextInput::make('cantidad_mayor')
                            ->label('Descuento por cantidad: desde')
                            ->suffix('unidades')
                            ->numeric()->integer()->minValue(2)
                            ->requiredWith('descuento_mayor'),
                        Forms\Components\TextInput::make('descuento_mayor')
                            ->label('…obtiene un descuento de')
                            ->suffix('%')
                            ->numeric()->minValue(1)->maxValue(50)
                            ->requiredWith('cantidad_mayor')
                            ->helperText('Ej.: desde 12 unidades, 10% menos c/u.'),
                    ])->columns(2),

                Forms\Components\Section::make('Precio e inventario')
                    ->icon('heroicon-o-currency-dollar')
                    ->schema([
                        Forms\Components\TextInput::make('precio_venta')
                            ->label('Precio de venta')
                            ->prefix('S/')
                            ->numeric()
                            ->required()
                            ->minValue(0.01)
                            ->step(0.10)
                            ->live(onBlur: true),
                        Forms\Components\TextInput::make('costo')
                            ->label('Costo (última compra)')
                            ->visible(fn () => \App\Support\Permisos::puede('productos', 'editar'))
                            ->prefix('S/')
                            ->numeric()
                            ->minValue(0.01)
                            ->helperText('Se actualiza solo al registrar una compra.'),
                        Forms\Components\TextInput::make('stock')
                            ->label('Stock actual')
                            ->numeric()
                            ->integer()
                            ->required()
                            ->minValue(0)
                            ->default(0)
                            ->suffix('und.'),
                        Forms\Components\TextInput::make('stock_minimo')
                            ->label('Stock mínimo')
                            ->helperText('Se avisará cuando el stock llegue a este valor.')
                            ->numeric()
                            ->integer()
                            ->required()
                            ->minValue(0)
                            ->default(5)
                            ->suffix('und.'),
                        Forms\Components\FileUpload::make('imagen')
                            ->label('Portada / foto')
                            ->image()
                            ->imageEditor()
                            ->disk('public')
                            ->directory('productos')
                            ->maxSize(2048)
                            ->helperText('JPG o PNG, máx. 2 MB. Se muestra en la tienda online.'),
                        Forms\Components\Toggle::make('estado')
                            ->label('Disponible para la venta')
                            ->default(true)
                            ->onColor('success'),
                    ]),
            ])->columnSpan(['lg' => 1]),
        ])->columns(3);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            InfolistSection::make(fn (Producto $record) => $record->nombre)
                ->description(fn (Producto $record) => $record->descripcion)
                ->schema([
                    TextEntry::make('codigo')->label('Código')->badge()->color('gray')->copyable(),
                    TextEntry::make('categoria.nombre')->label('Categoría')->badge()->color('primary'),
                    TextEntry::make('autor.nombre_completo')->label('Autor')->icon('heroicon-m-pencil')->placeholder('—'),
                    TextEntry::make('marca_editorial')->label('Editorial / Marca')->placeholder('—'),
                    TextEntry::make('isbn')->label('ISBN')->copyable()->placeholder('—'),
                    IconEntry::make('estado')->label('Disponible')->boolean(),
                ])->columns(3),
            InfolistSection::make('Precio e inventario')
                ->schema([
                    TextEntry::make('precio_venta')
                        ->label('Precio')
                        ->formatStateUsing(fn ($state) => Formato::soles($state))
                        ->weight(FontWeight::Bold)
                        ->color('primary'),
                    TextEntry::make('stock')
                        ->label('Stock')
                        ->suffix(' und.')
                        ->badge()
                        ->color(fn (Producto $record) => $record->tieneStockBajo() ? 'danger' : 'success'),
                    TextEntry::make('stock_minimo')->label('Stock mínimo')->suffix(' und.'),
                    TextEntry::make('vendidos')
                        ->label('Unidades vendidas')
                        ->state(fn (Producto $record) => (int) $record->detalles()
                            ->whereHas('venta', fn ($q) => $q->validas())
                            ->sum('cantidad')),
                ])->columns(4),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['categoria', 'autor']))
            ->defaultSort('codigo')
            ->columns([
                Tables\Columns\ImageColumn::make('imagen')
                    ->label('')
                    ->state(fn (Producto $record) => $record->imagen_url)
                    ->height(48)
                    ->width(34)
                    ->extraImgAttributes(['style' => 'object-fit:cover;border-radius:4px'])
                    ->defaultImageUrl(asset('portada.svg')),
                Tables\Columns\TextColumn::make('codigo')
                    ->label('Código')
                    ->badge()
                    ->color('gray')
                    ->fontFamily('mono')
                    ->copyable()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('nombre')
                    ->label('Producto')
                    ->weight(FontWeight::SemiBold)
                    ->description(fn (Producto $record) => $record->autor?->nombre_completo ?? $record->marca_editorial)
                    ->wrap()
                    ->searchable(['nombre', 'isbn', 'marca_editorial'])
                    ->sortable(),
                Tables\Columns\TextColumn::make('categoria.nombre')
                    ->label('Categoría')
                    ->badge()
                    ->color('primary')
                    ->sortable(),
                Tables\Columns\TextColumn::make('precio_venta')
                    ->label('Precio')
                    ->formatStateUsing(fn (Producto $record) => Formato::soles($record->precioActual()))
                    ->description(fn (Producto $record) => $record->enOferta() ? 'Antes ' . Formato::soles($record->precio_venta) . ' · -' . $record->porcentajeOferta() . '%' : $record->textoPorMayor())
                    ->color(fn (Producto $record) => $record->enOferta() ? 'danger' : null)
                    ->icon(fn (Producto $record) => $record->enOferta() ? 'heroicon-m-tag' : null)
                    ->alignEnd()
                    ->sortable(),
                Tables\Columns\TextColumn::make('margen')
                    ->label('Margen')
                    ->visible(fn () => \App\Support\Permisos::puede('productos', 'editar'))   // costo y margen: no para vendedores
                    ->state(fn (Producto $record) => $record->margen())
                    ->formatStateUsing(fn ($state) => $state . '%')
                    ->placeholder('—')
                    ->badge()
                    ->color(fn ($state) => $state === null ? 'gray' : ($state >= 30 ? 'success' : ($state >= 15 ? 'warning' : 'danger')))
                    ->tooltip(fn (Producto $record) => $record->costo ? 'Costo: S/ ' . number_format((float) $record->costo, 2) : 'Sin compras registradas')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('stock')
                    ->label('Stock')
                    ->badge()
                    ->color(fn (Producto $record) => $record->tieneStockBajo() ? 'danger' : 'success')
                    ->icon(fn (Producto $record) => $record->tieneStockBajo() ? 'heroicon-m-exclamation-triangle' : null)
                    ->tooltip(fn (Producto $record) => "Mínimo: {$record->stock_minimo} und.")
                    ->alignCenter()
                    ->sortable(),
                Tables\Columns\TextColumn::make('isbn')
                    ->label('ISBN')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\ToggleColumn::make('estado')
                    ->disabled(fn () => ! Permisos::puede('productos', 'editar'))
                    ->label('Disponible')
                    ->onColor('success')
                    ->alignCenter(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('categoria_id')
                    ->label('Categoría')
                    ->relationship('categoria', 'nombre')
                    ->preload(),
                Tables\Filters\SelectFilter::make('autor_id')
                    ->label('Autor')
                    ->relationship('autor', 'apellidos')
                    ->getOptionLabelFromRecordUsing(fn (Autor $record) => $record->nombre_completo)
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('stock_bajo')
                    ->label('Solo stock bajo')
                    ->toggle()
                    ->query(fn (Builder $query) => $query->stockBajo()),
                Tables\Filters\TernaryFilter::make('estado')
                    ->label('Disponibilidad')
                    ->trueLabel('Disponibles')
                    ->falseLabel('No disponibles'),
            ])
            ->filtersFormColumns(2)
            ->actions([
                // 📦 Ingresar mercadería o registrar merma (queda en el kárdex)
                Tables\Actions\Action::make('movimiento')
                    ->iconButton()
                    ->icon('heroicon-o-arrows-up-down')
                    ->color('success')
                    ->tooltip('Ingresar / dar de baja stock')
                    ->visible(fn () => Permisos::puede('productos', 'reponer'))
                    ->modalHeading(fn (Producto $record) => "Movimiento de stock · {$record->nombre}")
                    ->modalDescription(fn (Producto $record) => "Stock actual: {$record->stock} und. (mínimo {$record->stock_minimo})")
                    ->modalWidth('lg')
                    ->form([
                        Forms\Components\ToggleButtons::make('motivo')
                            ->label('Tipo de movimiento')
                            ->options(['compra' => 'Ingreso (compra)', 'merma' => 'Baja (merma / daño)'])
                            ->icons(['compra' => 'heroicon-m-arrow-down-tray', 'merma' => 'heroicon-m-trash'])
                            ->colors(['compra' => 'success', 'merma' => 'danger'])
                            ->default('compra')
                            ->inline()
                            ->required(),
                        Forms\Components\TextInput::make('cantidad')
                            ->label('Cantidad')
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->required()
                            ->suffix('und.'),
                        Forms\Components\TextInput::make('referencia')
                            ->label('Referencia')
                            ->placeholder('N° de guía, factura del proveedor…')
                            ->maxLength(60),
                        Forms\Components\TextInput::make('nota')
                            ->label('Nota')
                            ->maxLength(200),
                    ])
                    ->action(function (Producto $record, array $data, Tables\Actions\Action $action) {
                        try {
                            $mov = MovimientoInventario::registrar(
                                $record->id, $data['motivo'], (int) $data['cantidad'], $data['referencia'] ?? null, null, $data['nota'] ?? null
                            );

                            Notification::make()
                                ->success()
                                ->title($mov->tipo === 'entrada' ? 'Ingreso registrado' : 'Baja registrada')
                                ->body("Stock: {$mov->stock_anterior} → {$mov->stock_nuevo} und.")
                                ->send();
                        } catch (\RuntimeException $e) {
                            Notification::make()->danger()->title('No se pudo registrar')->body($e->getMessage())->send();
                            $action->halt();
                        }
                    }),

                // 📋 Ver kárdex del producto
                Tables\Actions\Action::make('kardex')
                    ->iconButton()
                    ->icon('heroicon-o-clipboard-document-list')
                    ->color('gray')
                    ->tooltip('Ver kárdex')
                    ->visible(fn () => Permisos::puede('movimientos', 'ver'))
                    ->url(fn (Producto $record) => MovimientoResource::getUrl('index', [
                        'tableFilters' => ['producto_id' => ['value' => $record->id]],
                    ])),

                Acciones::ver()->modalWidth('4xl'),
                Acciones::editar(),
                Acciones::eliminar(['detalles' => 'ventas']),
            ])
            ->actionsColumnLabel('Acciones')
            ->bulkActions([
                Tables\Actions\BulkAction::make('etiquetas')
                    ->label('Imprimir etiquetas')
                    ->icon('heroicon-o-qr-code')
                    ->color('gray')
                    ->form([
                        \Filament\Forms\Components\TextInput::make('copias')->label('Copias por producto')->numeric()->integer()->minValue(1)->maxValue(10)->default(1)->required(),
                    ])
                    ->action(fn (\Illuminate\Support\Collection $records, array $data) => redirect()->route('etiquetas', [
                        'ids'    => $records->pluck('id')->implode(','),
                        'copias' => $data['copias'],
                    ]))
                    ->deselectRecordsAfterCompletion(),
                Tables\Actions\BulkActionGroup::make([
                    Acciones::eliminarMasivo(['detalles' => 'ventas']),
                ]),
            ])
            ->emptyStateIcon('heroicon-o-book-open')
            ->emptyStateHeading('Aún no hay productos');
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['codigo', 'nombre', 'isbn'];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListProductos::route('/'),
            'create' => Pages\CreateProducto::route('/create'),
            'edit'   => Pages\EditProducto::route('/{record}/edit'),
        ];
    }
}
