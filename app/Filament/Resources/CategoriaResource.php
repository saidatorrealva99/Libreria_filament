<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoriaResource\Pages;
use App\Support\Acciones;
use App\Models\Categoria;
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

class CategoriaResource extends Resource
{
    // Permisos por rol (ver app/Support/Permisos.php)
    use TienePermisos;
    protected static string $modulo = 'categorias';

    protected static ?string $model = Categoria::class;

    protected static ?string $slug = 'categorias';
    protected static ?string $modelLabel = 'categoría';
    protected static ?string $pluralModelLabel = 'categorías';
    protected static ?string $navigationGroup = 'Catálogos';
    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?int $navigationSort = 2;
    protected static ?string $recordTitleAttribute = 'nombre';

    public static function campos(): array
    {
        return [
            Forms\Components\TextInput::make('nombre')
                ->label('Nombre')
                ->required()
                ->maxLength(80)
                ->unique(ignoreRecord: true)
                ->placeholder('Ej. Novela, Útiles escolares'),
            Forms\Components\Toggle::make('estado')
                ->label('Activa')
                ->helperText('Las categorías inactivas no aparecen al registrar productos.')
                ->default(true)
                ->inline(false)
                ->onColor('success'),
            Forms\Components\Textarea::make('descripcion')
                ->label('Descripción')
                ->maxLength(255)
                ->rows(3)
                ->columnSpanFull(),
        ];
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Datos de la categoría')
                ->description('Agrupa los productos del catálogo (libros, útiles, papelería…).')
                ->icon('heroicon-o-tag')
                ->schema(self::campos())
                ->columns(2),
        ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            InfolistSection::make('Categoría')
                ->schema([
                    TextEntry::make('nombre')->weight(FontWeight::Bold),
                    IconEntry::make('estado')->label('Activa')->boolean(),
                    TextEntry::make('descripcion')
                        ->label('Descripción')
                        ->placeholder('Sin descripción')
                        ->columnSpanFull(),
                    TextEntry::make('productos_count')
                        ->label('Productos')
                        ->state(fn (Categoria $record) => $record->productos()->count())
                        ->badge()
                        ->color('success'),
                    TextEntry::make('created_at')->label('Registrada')->dateTime('d/m/Y H:i'),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->withCount('productos'))
            ->defaultSort('nombre')
            ->columns([
                Tables\Columns\TextColumn::make('nombre')
                    ->label('Categoría')
                    ->weight(FontWeight::SemiBold)
                    ->icon('heroicon-m-tag')
                    ->iconColor('primary')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('descripcion')
                    ->label('Descripción')
                    ->limit(55)
                    ->tooltip(fn (Categoria $record) => $record->descripcion)
                    ->color('gray')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('productos_count')
                    ->label('Productos')
                    ->badge()
                    ->color(fn (int $state) => $state > 0 ? 'success' : 'gray')
                    ->alignCenter()
                    ->sortable(),
                Tables\Columns\ToggleColumn::make('estado')
                    ->disabled(fn () => ! Permisos::puede('categorias', 'editar'))
                    ->label('Activa')
                    ->onColor('success')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Actualizada')
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('estado')
                    ->label('Estado')
                    ->trueLabel('Activas')
                    ->falseLabel('Inactivas'),
            ])
            ->actions([
                Acciones::ver(),
                Acciones::editar(),
                Acciones::eliminar(['productos' => 'productos']),
            ])
            ->actionsColumnLabel('Acciones')
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Acciones::eliminarMasivo(['productos' => 'productos']),
                ]),
            ])
            ->emptyStateIcon('heroicon-o-tag')
            ->emptyStateHeading('Aún no hay categorías');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCategorias::route('/'),
            'create' => Pages\CreateCategoria::route('/create'),
            'edit'   => Pages\EditCategoria::route('/{record}/edit'),
        ];
    }
}
