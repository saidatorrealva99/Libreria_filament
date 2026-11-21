<?php

namespace App\Filament\Pages;

use App\Models\Ajuste;
use App\Support\Permisos;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * Configuración de la tienda: datos que salen en comprobantes, tienda online,
 * Libro de Reclamaciones y checkout (Yape/Plin). Solo administrador.
 */
class Ajustes extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'filament.pages.ajustes';
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'Datos de la librería';
    protected static ?string $title = 'Configuración de la tienda';
    protected static ?string $slug = 'configuracion';
    protected static ?string $navigationGroup = 'Configuración';
    protected static ?int $navigationSort = 0;

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return Permisos::puede('ajustes');
    }

    public function getSubheading(): ?string
    {
        return 'Estos datos aparecen en las boletas, la tienda online, el checkout y el Libro de Reclamaciones.';
    }

    public function mount(): void
    {
        $c = config('libreria');

        $this->form->fill([
            'nombre'              => config('app.name'),
            'razon_social'        => $c['razon_social'],
            'ruc'                 => $c['ruc'],
            'direccion'           => $c['direccion'],
            'telefono'            => $c['telefono'],
            'email'               => $c['email'],
            'whatsapp'            => $c['whatsapp'] ?? '',
            'horario'             => $c['horario'],
            'pago_movil_numero'   => $c['pago_movil']['numero'],
            'pago_movil_titular'  => $c['pago_movil']['titular'],
            'pago_movil_qr'       => str_starts_with($c['pago_movil']['qr'], 'storage/') ? substr($c['pago_movil']['qr'], 8) : null,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                Forms\Components\Section::make('Datos de la empresa')
                    ->description('Salen en la cabecera de boletas y facturas.')
                    ->icon('heroicon-o-building-storefront')
                    ->aside()
                    ->schema([
                        Forms\Components\TextInput::make('nombre')->label('Nombre comercial')->required()->maxLength(60)
                            ->helperText('El que ven los clientes, por ejemplo "Mundo de Letras".'),
                        Forms\Components\TextInput::make('razon_social')->label('Razón social')->required()->maxLength(150),
                        Forms\Components\TextInput::make('ruc')->label('RUC')->required()->length(11)
                            ->regex('/^(10|15|17|20)[0-9]{9}$/')
                            ->validationMessages(['regex' => 'RUC de 11 dígitos que empieza con 10, 15, 17 o 20.']),
                        Forms\Components\TextInput::make('direccion')->label('Dirección')->required()->maxLength(200),
                    ]),

                Forms\Components\Section::make('Atención al cliente')
                    ->description('Contacto que aparece en la tienda online.')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->aside()
                    ->schema([
                        Forms\Components\TextInput::make('telefono')->label('Teléfono')->required()->maxLength(30)->prefixIcon('heroicon-m-phone'),
                        Forms\Components\TextInput::make('email')->label('Correo')->email()->regex(\App\Support\Reglas::EMAIL)->validationMessages(['regex' => \App\Support\Reglas::MSG_EMAIL])->required()->maxLength(120)->prefixIcon('heroicon-m-envelope'),
                        Forms\Components\TextInput::make('whatsapp')->label('WhatsApp')->prefix('+')
                            ->regex('/^51[0-9]{9}$/')
                            ->validationMessages(['regex' => 'Escribe 51 seguido de los 9 dígitos del celular, sin espacios.'])
                            ->placeholder('51987654321')
                            ->helperText('Número del botón verde de la tienda.'),
                        Forms\Components\TextInput::make('horario')->label('Horario de atención')->required()->maxLength(120),
                    ]),

                Forms\Components\Section::make('Pagos con Yape / Plin')
                    ->description('Datos que ve el cliente al pagar un pedido web.')
                    ->icon('heroicon-o-device-phone-mobile')
                    ->aside()
                    ->schema([
                        Forms\Components\TextInput::make('pago_movil_numero')->label('Número de celular')->required()->maxLength(15),
                        Forms\Components\TextInput::make('pago_movil_titular')->label('Titular')->required()->maxLength(120),
                        Forms\Components\FileUpload::make('pago_movil_qr')
                            ->label('Código QR (opcional)')
                            ->image()
                            ->disk('public')
                            ->directory('ajustes')
                            ->maxSize(1024)
                            ->imagePreviewHeight('160')
                            ->helperText('Imagen PNG o JPG de tu QR de Yape. Máx. 1 MB.'),
                    ]),
            ]);
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('guardar')->label('Guardar cambios')->icon('heroicon-m-check')->submit('guardar'),
        ];
    }

    public function guardar(): void
    {
        $d = $this->form->getState();

        Ajuste::guardar([
            'nombre'             => $d['nombre'],
            'razon_social'       => $d['razon_social'],
            'ruc'                => $d['ruc'],
            'direccion'          => $d['direccion'],
            'telefono'           => $d['telefono'],
            'email'              => $d['email'],
            'whatsapp'           => $d['whatsapp'] ?? null,
            'horario'            => $d['horario'],
            'pago_movil.numero'  => $d['pago_movil_numero'],
            'pago_movil.titular' => $d['pago_movil_titular'],
            'pago_movil.qr'      => filled($d['pago_movil_qr'] ?? null) ? 'storage/' . $d['pago_movil_qr'] : 'img/yape-qr.png',
        ]);

        Notification::make()->success()->title('Configuración guardada')
            ->body('Los cambios ya se ven en la tienda y en los comprobantes.')->send();
    }
}
