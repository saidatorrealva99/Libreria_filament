<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AutorResource\Pages;
use App\Support\Acciones;
use App\Models\Autor;
use App\Support\Permisos;
use App\Support\TienePermisos;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;

class AutorResource extends Resource
{
    // Permisos por rol (ver app/Support/Permisos.php)
    use TienePermisos;
    protected static string $modulo = 'autores';

    protected static ?string $model = Autor::class;

    protected static ?string $slug = 'autores';
    protected static ?string $modelLabel = 'autor';
    protected static ?string $pluralModelLabel = 'autores';
    protected static ?string $navigationGroup = 'Catálogos';
    protected static ?string $navigationIcon = 'heroicon-o-pencil-square';
    protected static ?int $navigationSort = 1;
    protected static ?string $recordTitleAttribute = 'apellidos';

    public const NACIONALIDADES = [
        'Peruana', 'Argentina', 'Chilena', 'Colombiana', 'Mexicana', 'Española',
        'Uruguaya', 'Ecuatoriana', 'Boliviana', 'Venezolana', 'Estadounidense',
        'Británica', 'Francesa', 'Alemana', 'Italiana', 'Rusa', 'Japonesa',
    ];

    /** Campos reutilizables (también se usan para crear un autor desde Productos). */
    public static function campos(): array
    {
        return [
            Forms\Components\TextInput::make('nombres')
                ->label('Nombres')
                ->required()
                ->maxLength(100),
            Forms\Components\TextInput::make('apellidos')
                ->label('Apellidos')
                ->required()
                ->maxLength(100),
            Forms\Components\TextInput::make('nacionalidad')
                ->label('Nacionalidad')
                ->maxLength(60)
                ->datalist(self::NACIONALIDADES)
                ->placeholder('Ej. Peruana'),
            Forms\Components\DatePicker::make('fecha_nacimiento')
                ->label('Fecha de nacimiento')
                ->native(false)
                ->displayFormat('d/m/Y')
                ->maxDate(now())
                ->placeholder('Opcional'),
        ];
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Datos del autor')
                ->description('Escritor o autor de los libros del catálogo.')
                ->icon('heroicon-o-pencil-square')
                ->schema(self::campos())
                ->columns(2),
        ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            InfolistSection::make('Autor')
                ->schema([
                    TextEntry::make('nombre_completo')
                        ->label('Nombre completo')
                        ->weight(FontWeight::Bold)
                        ->size(TextEntry\TextEntrySize::Large),
                    TextEntry::make('nacionalidad')
                        ->badge()
                        ->color('info')
                        ->placeholder('No registrada'),
                    TextEntry::make('fecha_nacimiento')
                        ->label('Nacimiento')
                        ->date('d/m/Y')
                        ->icon('heroicon-m-cake')
                        ->placeholder('No registrada'),
                    TextEntry::make('productos_count')
                        ->label('Libros en catálogo')
                        ->state(fn (Autor $record) => $record->productos()->count())
                        ->badge()
                        ->color('success'),
                ])->columns(2),
            InfolistSection::make('Libros del autor')
                ->schema([
                    TextEntry::make('productos.nombre')
                        ->hiddenLabel()
                        ->badge()
                        ->color('gray')
                        ->placeholder('Aún no tiene libros registrados.'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->withCount('productos'))
            ->defaultSort('apellidos')
            ->columns([
                Tables\Columns\TextColumn::make('nombre_completo')
                    ->label('Autor')
                    ->weight(FontWeight::SemiBold)
                    ->searchable(['nombres', 'apellidos'])
                    ->sortable(['apellidos', 'nombres']),
                Tables\Columns\TextColumn::make('nacionalidad')
                    ->badge()
                    ->color('info')
                    ->placeholder('—')
                    ->sortable(),
                Tables\Columns\TextColumn::make('fecha_nacimiento')
                    ->label('Nacimiento')
                    ->date('d/m/Y')
                    ->placeholder('—')
                    ->sortable(),
                Tables\Columns\TextColumn::make('productos_count')
                    ->label('Libros')
                    ->badge()
                    ->color(fn (int $state) => $state > 0 ? 'success' : 'gray')
                    ->alignCenter()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Registrado')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('nacionalidad')
                    ->options(fn () => Autor::query()
                        ->whereNotNull('nacionalidad')
                        ->distinct()
                        ->orderBy('nacionalidad')
                        ->pluck('nacionalidad', 'nacionalidad')
                        ->all()),
            ])
            ->actions([
                Acciones::ver(),
                Acciones::editar(),
                Acciones::eliminar(['productos' => 'libros']),
            ])
            ->actionsColumnLabel('Acciones')
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Acciones::eliminarMasivo(['productos' => 'libros']),
                ]),
            ])
            ->emptyStateIcon('heroicon-o-pencil-square')
            ->emptyStateHeading('Aún no hay autores')
            ->emptyStateDescription('Registra el primer autor para asociarlo a tus libros.');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListAutores::route('/'),
            'create' => Pages\CreateAutor::route('/create'),
            'edit'   => Pages\EditAutor::route('/{record}/edit'),
        ];
    }
}
