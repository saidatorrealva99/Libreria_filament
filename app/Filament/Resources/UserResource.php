<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\Empleado;
use App\Models\Rol;
use App\Models\User;
use App\Support\TienePermisos;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    // Solo el administrador (ver app/Support/Permisos.php)
    use TienePermisos;
    protected static string $modulo = 'usuarios';

    protected static ?string $model = User::class;

    protected static ?string $slug = 'usuarios';
    protected static ?string $modelLabel = 'usuario';
    protected static ?string $pluralModelLabel = 'usuarios';
    protected static ?string $navigationGroup = 'Configuración';
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?int $navigationSort = 1;

    public const COLORES_ROL = [
        Rol::ADMIN      => 'danger',
        Rol::VENDEDOR   => 'primary',
        Rol::CAJERO     => 'success',
        Rol::ALMACENERO => 'warning',
    ];

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Cuenta de acceso')
                ->icon('heroicon-o-key')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nombre')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('email')
                        ->label('Correo electrónico')
                        ->email()
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),
                    Forms\Components\TextInput::make('password')
                        ->label('Contraseña')
                        ->password()
                        ->revealable()
                        ->confirmed()
                        ->minLength(8)
                        ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                        ->dehydrated(fn ($state) => filled($state))
                        ->required(fn (string $operation) => $operation === 'create')
                        ->helperText(fn (string $operation) => $operation === 'edit' ? 'Déjala en blanco para no cambiarla.' : null),
                    Forms\Components\TextInput::make('password_confirmation')
                        ->label('Confirmar contraseña')
                        ->password()
                        ->revealable()
                        ->dehydrated(false)
                        ->required(fn (string $operation) => $operation === 'create'),
                ])->columns(2),

            Forms\Components\Section::make('Rol y permisos')
                ->icon('heroicon-o-shield-check')
                ->description('El rol define qué módulos puede ver y usar.')
                ->schema([
                    Forms\Components\Select::make('rol_id')
                        ->label('Rol')
                        ->relationship('rol', 'nombre')
                        ->getOptionLabelFromRecordUsing(fn (Rol $record) => ucfirst($record->nombre))
                        ->required()
                        ->preload()
                        ->native(false),

                    // Un empleado solo puede tener un usuario
                    Forms\Components\Select::make('empleado_id')
                        ->label('Empleado vinculado')
                        ->relationship(
                            'empleado',
                            'nombres',
                            fn (Builder $query, ?User $record) => $query->whereDoesntHave(
                                'usuario',
                                fn ($q) => $q->when($record, fn ($q) => $q->where('id', '!=', $record->id))
                            )
                        )
                        ->getOptionLabelFromRecordUsing(fn (Empleado $record) => $record->nombre_completo . ' · ' . (Empleado::CARGOS[$record->cargo] ?? $record->cargo))
                        ->searchable(['nombres', 'apellidos'])
                        ->preload()
                        ->helperText('Si es vendedor, sus ventas se registran a su nombre.'),

                    Forms\Components\Toggle::make('activo')
                        ->label('Puede ingresar al sistema')
                        ->default(true)
                        ->onColor('success')
                        ->inline(false),
                ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['rol', 'empleado']))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Usuario')
                    ->weight(FontWeight::SemiBold)
                    ->description(fn (User $record) => $record->email)
                    ->searchable(['name', 'email'])
                    ->sortable(),

                Tables\Columns\TextColumn::make('rol.nombre')
                    ->label('Rol')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => ucfirst((string) $state))
                    ->color(fn (?string $state) => self::COLORES_ROL[$state] ?? 'gray')
                    ->placeholder('Sin rol'),

                Tables\Columns\TextColumn::make('empleado.nombres')
                    ->label('Empleado')
                    ->formatStateUsing(fn (User $record) => $record->empleado?->nombre_completo)
                    ->placeholder('—'),

                Tables\Columns\ToggleColumn::make('activo')
                    ->label('Activo')
                    ->onColor('success')
                    ->disabled(fn (User $record) => $record->id === \Illuminate\Support\Facades\Auth::id())   // no desactivarse a sí mismo
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('rol_id')
                    ->label('Rol')
                    ->relationship('rol', 'nombre'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->iconButton()
                    ->color('warning')
                    ->tooltip('Editar'),

                Tables\Actions\Action::make('resetPassword')
                    ->iconButton()
                    ->icon('heroicon-o-key')
                    ->color('gray')
                    ->tooltip('Resetear contraseña')
                    ->requiresConfirmation()
                    ->modalDescription('La contraseña quedará como 12345678.')
                    ->action(function (User $record) {
                        $record->update(['password' => Hash::make('12345678')]);
                        Notification::make()->success()->title('Contraseña reseteada a 12345678')->send();
                    }),

                Tables\Actions\DeleteAction::make()
                    ->iconButton()
                    ->tooltip('Eliminar')
                    ->hidden(fn (User $record) => $record->id === \Illuminate\Support\Facades\Auth::id()),   // no eliminarse a sí mismo
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit'   => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
