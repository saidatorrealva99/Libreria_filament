<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClienteResource\Pages;
use App\Support\Acciones;
use App\Support\Formato;
use App\Models\Cliente;
use App\Support\Permisos;
use App\Support\TienePermisos;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Unique;

class ClienteResource extends Resource
{
    // Permisos por rol (ver app/Support/Permisos.php)
    use TienePermisos;
    protected static string $modulo = 'clientes';

    protected static ?string $model = Cliente::class;

    protected static ?string $slug = 'clientes';
    protected static ?string $modelLabel = 'cliente';
    protected static ?string $pluralModelLabel = 'clientes';
    protected static ?string $navigationGroup = 'Catálogos';
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?int $navigationSort = 4;
    protected static ?string $recordTitleAttribute = 'nombres';

    /** Reglas de formato por tipo de documento (iguales a los CHECK de la BD) */
    public const FORMATOS = [
        'DNI' => ['regex' => '/^[0-9]{8}$/',                 'mensaje' => 'El DNI debe tener 8 dígitos.',                        'max' => 8],
        'RUC' => ['regex' => '/^(10|15|17|20)[0-9]{9}$/',    'mensaje' => 'El RUC tiene 11 dígitos y empieza con 10, 15, 17 o 20.', 'max' => 11],
        'CE'  => ['regex' => '/^[0-9]{9,12}$/',              'mensaje' => 'El carné de extranjería tiene de 9 a 12 dígitos.',    'max' => 12],
    ];

    /** Campos reutilizables (también se usan para crear un cliente desde Ventas). */
    public static function campos(): array
    {
        return [
            Forms\Components\ToggleButtons::make('tipo_documento')
                ->label('Tipo de documento')
                ->options(['DNI' => 'DNI', 'RUC' => 'RUC', 'CE' => 'Carné ext.'])
                ->icons(['DNI' => 'heroicon-m-identification', 'RUC' => 'heroicon-m-building-office', 'CE' => 'heroicon-m-globe-americas'])
                ->default('DNI')
                ->inline()
                ->required()
                ->live()
                ->afterStateUpdated(function (?string $state, Forms\Set $set) {
                    if ($state === 'RUC') {
                        $set('apellidos', null);   // una empresa no tiene apellidos
                    }
                }),
            Forms\Components\TextInput::make('numero_documento')
                ->label('Número de documento')
                ->required()
                ->maxLength(fn (Get $get) => self::FORMATOS[$get('tipo_documento') ?? 'DNI']['max'])
                ->rules([
                    fn (Get $get): \Closure => function (string $attribute, $value, \Closure $fail) use ($get) {
                        $formato = self::FORMATOS[$get('tipo_documento') ?? 'DNI'];
                        if (! preg_match($formato['regex'], (string) $value)) {
                            $fail($formato['mensaje']);
                        }
                    },
                ])
                ->validationMessages(['unique' => 'Ya existe un cliente con este documento.'])
                ->unique(
                    ignoreRecord: true,
                    modifyRuleUsing: fn (Unique $rule, Get $get) => $rule->where('tipo_documento', $get('tipo_documento')),
                ),
            Forms\Components\TextInput::make('nombres')
                ->label(fn (Get $get) => $get('tipo_documento') === 'RUC' ? 'Razón social' : 'Nombres')
                ->required()
                ->maxLength(100),
            Forms\Components\TextInput::make('apellidos')
                ->label('Apellidos')
                ->maxLength(100)
                ->required(fn (Get $get) => $get('tipo_documento') !== 'RUC')
                ->regex(\App\Support\Reglas::NOMBRE)
                ->validationMessages(['regex' => \App\Support\Reglas::MSG_NOMBRE])
                ->hidden(fn (Get $get) => $get('tipo_documento') === 'RUC'),
            Forms\Components\TextInput::make('email')
                ->label('Correo electrónico')
                ->email()
                ->regex(\App\Support\Reglas::EMAIL)
                ->maxLength(120)
                ->unique(ignoreRecord: true)
                ->validationMessages(['regex' => \App\Support\Reglas::MSG_EMAIL, 'email' => \App\Support\Reglas::MSG_EMAIL, 'unique' => 'Ya existe un cliente con este correo.'])
                ->prefixIcon('heroicon-m-envelope'),
            Forms\Components\TextInput::make('telefono')
                ->label('Teléfono')
                ->tel()
                ->maxLength(9)
                ->regex('/^[0-9]{7,9}$/')
                ->validationMessages(['regex' => 'Solo números, de 7 a 9 dígitos.'])
                ->prefixIcon('heroicon-m-phone'),
            Forms\Components\TextInput::make('direccion')
                ->label('Dirección')
                ->maxLength(200)
                ->prefixIcon('heroicon-m-map-pin')
                ->columnSpanFull(),
        ];
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Datos del cliente')
                ->description('Para emitir factura el cliente debe tener RUC.')
                ->icon('heroicon-o-user')
                ->schema(self::campos())
                ->columns(2),
        ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            InfolistSection::make('Cliente')
                ->schema([
                    TextEntry::make('nombre_completo')
                        ->label('Nombre / Razón social')
                        ->weight(FontWeight::Bold)
                        ->columnSpanFull(),
                    TextEntry::make('numero_documento')
                        ->label('Documento')
                        ->prefix(fn (Cliente $record) => "{$record->tipo_documento} ")
                        ->copyable(),
                    TextEntry::make('email')->label('Correo')->icon('heroicon-m-envelope')->placeholder('No registrado'),
                    TextEntry::make('telefono')->label('Teléfono')->icon('heroicon-m-phone')->placeholder('No registrado'),
                    TextEntry::make('direccion')->label('Dirección')->icon('heroicon-m-map-pin')->placeholder('No registrada'),
                ])->columns(2),
            InfolistSection::make('Historial de compras')
                ->schema([
                    TextEntry::make('compras')
                        ->label('Compras')
                        ->state(fn (Cliente $record) => $record->ventas()->validas()->count())
                        ->badge(),
                    TextEntry::make('gastado')
                        ->label('Total comprado')
                        ->state(fn (Cliente $record) => Formato::soles($record->ventas()->validas()->sum('total')))
                        ->weight(FontWeight::Bold)
                        ->color('success'),
                    TextEntry::make('ultima')
                        ->label('Última compra')
                        ->state(fn (Cliente $record) => $record->ventas()->validas()->max('fecha_venta'))
                        ->dateTime('d/m/Y H:i')
                        ->placeholder('Sin compras'),
                ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->withCount(['ventas' => fn ($q) => $q->validas()])
                ->withSum(['ventas' => fn ($q) => $q->validas()], 'total'))
            ->defaultSort('nombres')
            ->columns([
                Tables\Columns\TextColumn::make('nombre_completo')
                    ->label('Cliente')
                    ->weight(FontWeight::SemiBold)
                    ->description(fn (Cliente $record) => $record->direccion)
                    ->searchable(['nombres', 'apellidos'])
                    ->sortable(['nombres', 'apellidos']),
                Tables\Columns\TextColumn::make('numero_documento')
                    ->label('Documento')
                    ->prefix(fn (Cliente $record) => "{$record->tipo_documento} · ")
                    ->fontFamily('mono')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Correo')
                    ->icon('heroicon-m-envelope')
                    ->placeholder('—')
                    ->searchable(),
                Tables\Columns\TextColumn::make('telefono')
                    ->label('Teléfono')
                    ->icon('heroicon-m-phone')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('ventas_count')
                    ->label('Compras')
                    ->badge()
                    ->color(fn (int $state) => $state > 0 ? 'success' : 'gray')
                    ->alignCenter()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ventas_sum_total')
                    ->label('Total comprado')
                    ->formatStateUsing(fn ($state) => Formato::soles($state))
                    ->default(0)
                    ->alignEnd()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tipo_documento')
                    ->label('Tipo de documento')
                    ->options(['DNI' => 'DNI', 'RUC' => 'RUC', 'CE' => 'Carné de extranjería']),
            ])
            ->actions([
                Acciones::ver(),
                Acciones::editar(),
                Acciones::eliminar(['ventas' => 'ventas']),
            ])
            ->actionsColumnLabel('Acciones')
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Acciones::eliminarMasivo(['ventas' => 'ventas']),
                ]),
            ])
            ->emptyStateIcon('heroicon-o-user-group')
            ->emptyStateHeading('Aún no hay clientes');
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['nombres', 'apellidos', 'numero_documento'];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListClientes::route('/'),
            'create' => Pages\CreateCliente::route('/create'),
            'edit'   => Pages\EditCliente::route('/{record}/edit'),
        ];
    }
}
