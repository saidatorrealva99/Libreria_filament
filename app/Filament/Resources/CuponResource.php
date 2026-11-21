<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CuponResource\Pages;
use App\Models\Cupon;
use App\Support\Formato;
use App\Support\TienePermisos;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CuponResource extends Resource
{
    use TienePermisos;
    protected static string $modulo = 'cupones';

    protected static ?string $model = Cupon::class;

    protected static ?string $slug = 'cupones';
    protected static ?string $modelLabel = 'cupón';
    protected static ?string $pluralModelLabel = 'cupones';
    protected static ?string $navigationGroup = 'Ventas';
    protected static ?string $navigationIcon = 'heroicon-o-ticket';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Cupón de descuento')
                ->description('Los clientes lo escriben en el carrito de la tienda online.')
                ->icon('heroicon-o-ticket')
                ->schema([
                    Forms\Components\TextInput::make('codigo')
                        ->label('Código')
                        ->required()
                        ->maxLength(20)
                        ->regex('/^[A-Za-z0-9]{3,20}$/')
                        ->validationMessages(['regex' => 'Solo letras y números, de 3 a 20 (ej. ESCOLAR10).'])
                        ->unique(ignoreRecord: true)
                        ->extraInputAttributes(['style' => 'text-transform:uppercase;font-family:monospace;font-weight:700'])
                        ->placeholder('ESCOLAR10'),
                    Forms\Components\TextInput::make('descripcion')->label('Descripción')->maxLength(150)->placeholder('Campaña escolar 2027'),
                    Forms\Components\ToggleButtons::make('tipo')
                        ->options(Cupon::TIPOS)
                        ->default('porcentaje')
                        ->inline()
                        ->live()
                        ->required(),
                    Forms\Components\TextInput::make('valor')
                        ->label(fn (Get $get) => $get('tipo') === 'monto' ? 'Descuento (S/)' : 'Descuento (%)')
                        ->numeric()
                        ->required()
                        ->minValue(0.01)
                        ->maxValue(fn (Get $get) => $get('tipo') === 'porcentaje' ? 100 : 9999)
                        ->suffix(fn (Get $get) => $get('tipo') === 'monto' ? 'S/' : '%'),
                    Forms\Components\TextInput::make('minimo_compra')->label('Compra mínima')->prefix('S/')->numeric()->minValue(0)->default(0),
                    Forms\Components\TextInput::make('usos_maximos')->label('Usos máximos')->numeric()->integer()->minValue(1)
                        ->helperText('Vacío = ilimitado.'),
                    Forms\Components\DatePicker::make('vence_at')->label('Vence el')->native(false)->displayFormat('d/m/Y')->minDate(now()->startOfDay()),
                    Forms\Components\Toggle::make('estado')->label('Activo')->default(true)->onColor('success')->inline(false),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(Cupon::query())   // consulta explícita de la tabla
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('codigo')
                    ->label('Código')
                    ->fontFamily('mono')
                    ->weight(FontWeight::Bold)
                    ->copyable()
                    ->description(fn (Cupon $record) => $record->descripcion)
                    ->searchable(),
                Tables\Columns\TextColumn::make('valor')
                    ->label('Descuento')
                    ->formatStateUsing(fn (Cupon $record) => $record->etiqueta())
                    ->badge()
                    ->color('warning'),
                Tables\Columns\TextColumn::make('minimo_compra')
                    ->label('Mínimo')
                    ->formatStateUsing(fn ($state) => (float) $state > 0 ? Formato::soles($state) : '—'),
                Tables\Columns\TextColumn::make('usos')
                    ->formatStateUsing(fn (Cupon $record) => $record->usos . ($record->usos_maximos ? " / {$record->usos_maximos}" : ''))
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('descontado')
                    ->label('Descontado')
                    ->state(fn (Cupon $record) => (float) \App\Models\Venta::where('cupon_id', $record->id)->where('estado', '!=', 'anulada')->sum('descuento'))
                    ->formatStateUsing(fn ($state) => Formato::soles($state)),
                Tables\Columns\TextColumn::make('vence_at')
                    ->label('Vence')
                    ->date('d/m/Y')
                    ->placeholder('Sin fecha')
                    ->color(fn (Cupon $record) => $record->vence_at?->endOfDay()->isPast() ? 'danger' : null),
                Tables\Columns\ToggleColumn::make('estado')->label('Activo')->onColor('success')
                    ->disabled(fn () => ! \App\Support\Permisos::puede('cupones', 'editar')),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->iconButton()->color('warning'),
                Tables\Actions\DeleteAction::make()->iconButton()
                    ->before(function (Tables\Actions\DeleteAction $action, Cupon $record) {
                        if ($record->ventas()->exists()) {
                            Notification::make()->danger()->title('No se puede eliminar')
                                ->body('Este cupón ya se usó en ventas. Desactívalo.')->send();
                            $action->cancel();
                        }
                    }),
            ])
            ->emptyStateIcon('heroicon-o-ticket')
            ->emptyStateHeading('Aún no hay cupones')
            ->emptyStateDescription('Crea uno, por ejemplo ESCOLAR10 con 10% de descuento.');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCupones::route('/'),
            'create' => Pages\CreateCupon::route('/create'),
            'edit'   => Pages\EditCupon::route('/{record}/edit'),
        ];
    }
}
