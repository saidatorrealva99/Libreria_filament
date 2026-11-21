<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmpleadoResource\Pages;
use App\Support\Acciones;
use App\Support\Formato;
use App\Models\Empleado;
use App\Support\Permisos;
use App\Support\TienePermisos;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EmpleadoResource extends Resource
{
    // Permisos por rol (ver app/Support/Permisos.php)
    use TienePermisos;
    protected static string $modulo = 'empleados';

    protected static ?string $model = Empleado::class;

    protected static ?string $slug = 'empleados';
    protected static ?string $modelLabel = 'empleado';
    protected static ?string $pluralModelLabel = 'empleados';
    protected static ?string $navigationGroup = 'Catálogos';
    protected static ?string $navigationIcon = 'heroicon-o-briefcase';
    protected static ?int $navigationSort = 5;
    protected static ?string $recordTitleAttribute = 'apellidos';

    public const COLORES_CARGO = [
        'administrador' => 'danger',
        'vendedor'      => 'primary',
        'cajero'        => 'success',
        'almacenero'    => 'warning',
    ];

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Datos personales')
                ->icon('heroicon-o-user')
                ->schema([
                    Forms\Components\TextInput::make('dni')
                        ->label('DNI')
                        ->required()
                        ->length(8)
                        ->regex('/^[0-9]{8}$/')
                        ->validationMessages(['regex' => 'El DNI debe tener 8 dígitos.'])
                        ->unique(ignoreRecord: true),
                    Forms\Components\TextInput::make('nombres')
                        ->label('Nombres')
                        ->required()
                        ->maxLength(100),
                    Forms\Components\TextInput::make('apellidos')
                        ->label('Apellidos')
                        ->required()
                        ->maxLength(100),
                    Forms\Components\TextInput::make('email')
                        ->label('Correo')
                        ->email()
                        ->required()
                        ->maxLength(120)
                        ->unique(ignoreRecord: true)
                        ->prefixIcon('heroicon-m-envelope'),
                    Forms\Components\TextInput::make('telefono')
                        ->label('Celular')
                        ->tel()
                        ->maxLength(9)
                        ->regex('/^9[0-9]{8}$/')
                        ->validationMessages(['regex' => 'Celular de 9 dígitos que empieza con 9.'])
                        ->prefixIcon('heroicon-m-device-phone-mobile'),
                ])->columns(3),

            Forms\Components\Section::make('Datos laborales')
                ->icon('heroicon-o-briefcase')
                ->schema([
                    Forms\Components\Select::make('cargo')
                        ->label('Cargo')
                        ->options(Empleado::CARGOS)
                        ->default('vendedor')
                        ->required()
                        ->native(false),
                    Forms\Components\TextInput::make('sueldo')
                        ->label('Sueldo')
                        ->prefix('S/')
                        ->numeric()
                        ->required()
                        ->minValue(0.01),
                    Forms\Components\DatePicker::make('fecha_contratacion')
                        ->label('Fecha de contratación')
                        ->required()
                        ->native(false)
                        ->displayFormat('d/m/Y')
                        ->default(now())
                        ->maxDate(now()),
                    Forms\Components\Toggle::make('estado')
                        ->label('Activo')
                        ->helperText('Los empleados inactivos no aparecen al registrar ventas.')
                        ->default(true)
                        ->onColor('success')
                        ->inline(false),
                ])->columns(4),
        ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            InfolistSection::make('Empleado')
                ->schema([
                    TextEntry::make('nombre_completo')->label('Nombre')->weight(FontWeight::Bold),
                    TextEntry::make('cargo')
                        ->badge()
                        ->formatStateUsing(fn (string $state) => Empleado::CARGOS[$state] ?? $state)
                        ->color(fn (string $state) => self::COLORES_CARGO[$state] ?? 'gray'),
                    TextEntry::make('dni')->label('DNI')->copyable(),
                    TextEntry::make('email')->label('Correo')->icon('heroicon-m-envelope'),
                    TextEntry::make('telefono')->label('Celular')->icon('heroicon-m-device-phone-mobile')->placeholder('—'),
                    IconEntry::make('estado')->label('Activo')->boolean(),
                ])->columns(3),
            InfolistSection::make('Desempeño')
                ->schema([
                    TextEntry::make('sueldo')->formatStateUsing(fn ($state) => Formato::soles($state)),
                    TextEntry::make('fecha_contratacion')->label('Contratado')->date('d/m/Y'),
                    TextEntry::make('n_ventas')
                        ->label('Ventas realizadas')
                        ->state(fn (Empleado $record) => $record->ventas()->validas()->count())
                        ->badge(),
                    TextEntry::make('monto_ventas')
                        ->label('Monto vendido')
                        ->state(fn (Empleado $record) => Formato::soles($record->ventas()->validas()->sum('total')))
                        ->weight(FontWeight::Bold)
                        ->color('success'),
                ])->columns(4),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount(['ventas' => fn ($q) => $q->validas()]))
            ->defaultSort('apellidos')
            ->columns([
                Tables\Columns\TextColumn::make('nombre_completo')
                    ->label('Empleado')
                    ->weight(FontWeight::SemiBold)
                    ->description(fn (Empleado $record) => $record->email)
                    ->searchable(['nombres', 'apellidos', 'email'])
                    ->sortable(['apellidos', 'nombres']),
                Tables\Columns\TextColumn::make('dni')
                    ->label('DNI')
                    ->fontFamily('mono')
                    ->searchable(),
                Tables\Columns\TextColumn::make('cargo')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => Empleado::CARGOS[$state] ?? $state)
                    ->color(fn (string $state) => self::COLORES_CARGO[$state] ?? 'gray')
                    ->sortable(),
                Tables\Columns\TextColumn::make('telefono')
                    ->label('Celular')
                    ->icon('heroicon-m-device-phone-mobile')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('sueldo')
                    ->formatStateUsing(fn ($state) => Formato::soles($state))
                    ->alignEnd()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('fecha_contratacion')
                    ->label('Contratado')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('ventas_count')
                    ->label('Ventas')
                    ->badge()
                    ->color(fn (int $state) => $state > 0 ? 'success' : 'gray')
                    ->alignCenter()
                    ->sortable(),
                Tables\Columns\ToggleColumn::make('estado')
                    ->disabled(fn () => ! Permisos::puede('empleados', 'editar'))
                    ->label('Activo')
                    ->onColor('success')
                    ->alignCenter(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('cargo')->options(Empleado::CARGOS),
                Tables\Filters\TernaryFilter::make('estado')
                    ->label('Estado')
                    ->trueLabel('Activos')
                    ->falseLabel('Inactivos'),
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
            ->emptyStateIcon('heroicon-o-briefcase')
            ->emptyStateHeading('Aún no hay empleados');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListEmpleados::route('/'),
            'create' => Pages\CreateEmpleado::route('/create'),
            'edit'   => Pages\EditEmpleado::route('/{record}/edit'),
        ];
    }
}
