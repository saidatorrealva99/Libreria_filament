<?php

namespace App\Filament\Pages\Auth;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Auth\EditProfile as BaseEditProfile;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\Rules\Password;

/**
 * "Mi cuenta": datos del usuario, su rol y el cambio de contraseña
 * (pide la contraseña actual para mayor seguridad).
 */
class EditProfile extends BaseEditProfile
{
    public function getTitle(): string|Htmlable
    {
        return 'Mi cuenta';
    }

    public static function getLabel(): string
    {
        return 'Mi cuenta y contraseña';
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Datos de acceso')
                ->description('Tu nombre y el correo con el que inicias sesión.')
                ->icon('heroicon-o-user-circle')
                ->aside()
                ->schema([
                    $this->getNameFormComponent()->label('Nombre'),
                    $this->getEmailFormComponent()->label('Correo electrónico'),
                    Placeholder::make('rol')
                        ->label('Rol')
                        ->content(fn () => new HtmlString('<b>' . e($this->getUser()->rol?->nombre ?? '—') . '</b>'
                            . ($this->getUser()->empleado ? ' · ' . e($this->getUser()->empleado->nombres . ' ' . $this->getUser()->empleado->apellidos) : ''))),
                ]),

            Section::make('Cambiar contraseña')
                ->description('Déjalo en blanco si no quieres cambiarla. Mínimo 8 caracteres, con letras y números.')
                ->icon('heroicon-o-lock-closed')
                ->aside()
                ->schema([
                    TextInput::make('contrasena_actual')
                        ->label('Contraseña actual')
                        ->password()
                        ->revealable()
                        ->requiredWith('password')
                        ->rule('current_password')
                        ->validationMessages(['current_password' => 'La contraseña actual no es correcta.'])
                        ->dehydrated(false)
                        ->autocomplete('current-password'),
                    $this->getPasswordFormComponent()
                        ->label('Nueva contraseña')
                        ->rule(Password::min(8)->letters()->numbers()),
                    $this->getPasswordConfirmationFormComponent()
                        ->label('Repetir nueva contraseña'),
                ]),
        ]);
    }
}
