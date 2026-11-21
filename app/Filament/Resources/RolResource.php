<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RolResource\Pages;
use App\Models\Rol;
use App\Support\Permisos;
use App\Support\TienePermisos;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class RolResource extends Resource
{
    // Solo el administrador (ver app/Support/Permisos.php)
    use TienePermisos;
    protected static string $modulo = 'roles';

    protected static ?string $model = Rol::class;

    protected static ?string $slug = 'roles';
    protected static ?string $modelLabel = 'rol';
    protected static ?string $pluralModelLabel = 'roles';
    protected static ?string $navigationGroup = 'Configuración';
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?int $navigationSort = 2;

    // Los 4 roles son fijos: el código depende de sus nombres.
    // Solo se puede cambiar la descripción.
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Rol')
                ->schema([
                    Forms\Components\TextInput::make('nombre')
                        ->disabled()
                        ->dehydrated(false),
                    Forms\Components\TextInput::make('descripcion')
                        ->label('Descripción')
                        ->maxLength(150),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->withCount('usuarios'))
            ->paginated(false)
            ->columns([
                Tables\Columns\TextColumn::make('nombre')
                    ->label('Rol')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => ucfirst($state))
                    ->color(fn (string $state) => UserResource::COLORES_ROL[$state] ?? 'gray'),

                Tables\Columns\TextColumn::make('descripcion')
                    ->label('Descripción')
                    ->weight(FontWeight::Medium),

                // Lista de módulos a los que tiene acceso (sale de Permisos::MAPA)
                Tables\Columns\TextColumn::make('modulos')
                    ->label('Puede ver')
                    ->state(fn (Rol $record) => $record->nombre === Rol::ADMIN
                        ? ['Todo el sistema']
                        : collect(Permisos::MAPA)
                            ->filter(fn ($acciones) => in_array($record->nombre, $acciones['ver'] ?? [], true))
                            ->keys()
                            ->map(fn ($m) => ucfirst(str_replace('_', ' ', $m)))
                            ->values()
                            ->all())
                    ->badge()
                    ->color('gray')
                    ->wrap(),

                Tables\Columns\TextColumn::make('usuarios_count')
                    ->label('Usuarios')
                    ->badge()
                    ->color('info')
                    ->alignCenter(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->iconButton()
                    ->color('warning')
                    ->tooltip('Editar descripción'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoles::route('/'),
            'edit'  => Pages\EditRol::route('/{record}/edit'),
        ];
    }
}
