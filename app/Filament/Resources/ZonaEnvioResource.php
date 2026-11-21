<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ZonaEnvioResource\Pages;
use App\Models\ZonaEnvio;
use App\Support\Formato;
use App\Support\TienePermisos;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Distritos de delivery con su costo (Configuración).
 */
class ZonaEnvioResource extends Resource
{
    use TienePermisos;
    protected static string $modulo = 'zonas';

    protected static ?string $model = ZonaEnvio::class;

    protected static ?string $slug = 'zonas-delivery';
    protected static ?string $modelLabel = 'zona de delivery';
    protected static ?string $pluralModelLabel = 'zonas de delivery';
    protected static ?string $navigationLabel = 'Delivery por distrito';
    protected static ?string $navigationGroup = 'Configuración';
    protected static ?string $navigationIcon = 'heroicon-o-map';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('distrito')->required()->maxLength(60)->unique(ignoreRecord: true)
                ->regex("/^[\pL][\pL .'-]{2,59}$/u")->validationMessages(['regex' => 'Solo letras y espacios.']),
            Forms\Components\TextInput::make('costo')->label('Costo de envío')->prefix('S/')->numeric()->minValue(0)->maxValue(200)->required(),
            Forms\Components\TextInput::make('tiempo')->label('Tiempo de entrega')->maxLength(40)->default('24 a 48 horas')->required(),
            Forms\Components\Toggle::make('estado')->label('Activo')->default(true)->onColor('success')->inline(false),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('distrito')
            ->columns([
                Tables\Columns\TextColumn::make('distrito')->weight(FontWeight::SemiBold)->searchable()->sortable()->icon('heroicon-m-map-pin'),
                Tables\Columns\TextColumn::make('costo')->formatStateUsing(fn ($state) => (float) $state > 0 ? Formato::soles($state) : 'Gratis')->sortable(),
                Tables\Columns\TextColumn::make('tiempo')->label('Tiempo'),
                Tables\Columns\TextColumn::make('ventas_count')->counts('ventas')->label('Pedidos')->badge()->alignCenter(),
                Tables\Columns\ToggleColumn::make('estado')->label('Activo')->onColor('success'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->iconButton()->color('warning'),
                Tables\Actions\DeleteAction::make()->iconButton()
                    ->before(function (Tables\Actions\DeleteAction $action, ZonaEnvio $record) {
                        if ($record->ventas()->exists()) {
                            Notification::make()->danger()->title('No se puede eliminar')->body('Tiene pedidos. Desactívala.')->send();
                            $action->cancel();
                        }
                    }),
            ])
            ->emptyStateHeading('Sin zonas de delivery')
            ->emptyStateDescription('Agrega distritos para ofrecer delivery en la tienda online.');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageZonasEnvio::route('/')];
    }
}
