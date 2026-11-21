<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProveedorResource\Pages;
use App\Models\Proveedor;
use App\Support\Formato;
use App\Support\Permisos;
use App\Support\TienePermisos;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProveedorResource extends Resource
{
    // Permisos por rol (ver app/Support/Permisos.php)
    use TienePermisos;
    protected static string $modulo = 'proveedores';

    protected static ?string $model = Proveedor::class;

    protected static ?string $slug = 'proveedores';
    protected static ?string $modelLabel = 'proveedor';
    protected static ?string $pluralModelLabel = 'proveedores';
    protected static ?string $navigationGroup = 'Compras';
    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?int $navigationSort = 2;

    /** Campos reutilizables (también para crear un proveedor desde Compras) */
    public static function campos(): array
    {
        return [
            Forms\Components\TextInput::make('ruc')
                ->label('RUC')
                ->required()
                ->length(11)
                ->regex('/^(10|15|17|20)[0-9]{9}$/')
                ->validationMessages(['regex' => 'RUC de 11 dígitos que empieza con 10, 15, 17 o 20.'])
                ->unique('proveedores', 'ruc', ignoreRecord: true),
            Forms\Components\TextInput::make('razon_social')
                ->label('Razón social')
                ->required()
                ->maxLength(150),
            Forms\Components\TextInput::make('contacto')
                ->label('Persona de contacto')
                ->maxLength(100)
                ->prefixIcon('heroicon-m-user'),
            Forms\Components\TextInput::make('telefono')
                ->label('Teléfono')
                ->tel()
                ->maxLength(9)
                ->regex('/^[0-9]{7,9}$/')
                ->validationMessages(['regex' => 'Solo números, de 7 a 9 dígitos.'])
                ->prefixIcon('heroicon-m-phone'),
            Forms\Components\TextInput::make('email')
                ->label('Correo')
                ->email()->regex(\App\Support\Reglas::EMAIL)->validationMessages(['regex' => \App\Support\Reglas::MSG_EMAIL])
                ->maxLength(120)
                ->prefixIcon('heroicon-m-envelope'),
            Forms\Components\TextInput::make('direccion')
                ->label('Dirección')
                ->maxLength(200)
                ->prefixIcon('heroicon-m-map-pin'),
        ];
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Datos del proveedor')
                ->icon('heroicon-o-truck')
                ->schema([
                    ...self::campos(),
                    Forms\Components\Toggle::make('estado')->label('Activo')->default(true)->onColor('success'),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->withCount(['compras' => fn ($q) => $q->registradas()])
                ->withSum(['compras' => fn ($q) => $q->registradas()], 'total'))
            ->defaultSort('razon_social')
            ->columns([
                Tables\Columns\TextColumn::make('razon_social')
                    ->label('Proveedor')
                    ->weight(FontWeight::SemiBold)
                    ->description(fn (Proveedor $record) => "RUC {$record->ruc}")
                    ->searchable(['razon_social', 'ruc'])
                    ->sortable(),
                Tables\Columns\TextColumn::make('contacto')
                    ->description(fn (Proveedor $record) => $record->telefono)
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('email')
                    ->label('Correo')
                    ->icon('heroicon-m-envelope')
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('compras_count')
                    ->label('Compras')
                    ->badge()
                    ->color(fn (int $state) => $state > 0 ? 'success' : 'gray')
                    ->alignCenter()
                    ->sortable(),
                Tables\Columns\TextColumn::make('compras_sum_total')
                    ->label('Total comprado')
                    ->default(0)
                    ->formatStateUsing(fn ($state) => Formato::soles($state))
                    ->alignEnd()
                    ->sortable(),
                Tables\Columns\ToggleColumn::make('estado')
                    ->label('Activo')
                    ->disabled(fn () => ! Permisos::puede('proveedores', 'editar'))
                    ->onColor('success')
                    ->alignCenter(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->iconButton()->color('warning')->tooltip('Editar'),
                Tables\Actions\DeleteAction::make()
                    ->iconButton()
                    ->tooltip('Eliminar')
                    ->before(function (Tables\Actions\DeleteAction $action, Proveedor $record) {
                        if ($record->compras()->exists()) {
                            Notification::make()->danger()->title('No se puede eliminar')
                                ->body('Este proveedor tiene compras registradas. Puedes desactivarlo.')->send();
                            $action->cancel();
                        }
                    }),
            ])
            ->emptyStateIcon('heroicon-o-truck')
            ->emptyStateHeading('Aún no hay proveedores');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListProveedores::route('/'),
            'create' => Pages\CreateProveedor::route('/create'),
            'edit'   => Pages\EditProveedor::route('/{record}/edit'),
        ];
    }
}
