<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Ajustes;
use App\Filament\Pages\Auth\EditProfile;
use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Dashboard;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Table;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->databaseTransactions()                 // si falla una venta, se deshace todo

            // ===== LOGIN PERSONALIZADO =====
            ->login(Login::class)
            ->profile(EditProfile::class, isSimple: false)   // "Mi cuenta": datos y cambio de contraseña
            ->userMenuItems([
                'profile' => MenuItem::make()->label('Mi cuenta y contraseña'),
                MenuItem::make()
                    ->label('Configuración de la tienda')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->url(fn () => Ajustes::getUrl())
                    ->visible(fn () => Ajustes::canAccess()),
            ])
            ->databaseNotifications()               // campanita: pedidos web nuevos
            ->databaseNotificationsPolling('10s')

            // ===== MARCA: vino + dorado, tipografía editorial =====
            ->colors([
                'primary' => Color::hex('#9f1d3a'),  // vino / burdeos
                'gray'    => Color::Stone,           // gris cálido (papel)
                'info'    => Color::Sky,
                'success' => Color::Emerald,
                'warning' => Color::Amber,
                'danger'  => Color::Red,
            ])
            ->brandName(config('app.name'))
            ->brandLogo(fn () => view('filament.brand'))
            ->brandLogoHeight('2.6rem')
            ->favicon(asset('favicon.svg'))
            ->font('DM Sans')

            // ===== ESTILOS DEL PANEL (sin compilar Tailwind) =====
            ->renderHook(PanelsRenderHook::HEAD_END, fn () => view('filament.panel-styles'))

            // ===== DICTADO POR VOZ: botón 🎤 en los cuadros de texto =====
            ->renderHook(PanelsRenderHook::BODY_END, fn () => new HtmlString('<script src="' . asset('js/dictado.js') . '?v=1" defer></script>'))

            // ===== ESTILOS SOLO DEL LOGIN =====
            ->renderHook(PanelsRenderHook::HEAD_END, fn () => view('filament.auth.login-styles'), scopes: Login::class)
            ->renderHook(
                PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
                fn () => new HtmlString('<p class="lib-login-back"><a href="' . url('/') . '">&larr; Volver al inicio</a></p>'),
                scopes: Login::class,
            )

            // ===== PIE DE PÁGINA =====
            ->renderHook(
                PanelsRenderHook::FOOTER,
                fn () => new HtmlString('<div class="lib-footer">' . e(config('app.name')) . ' · Sistema de ventas · Laravel 11 + Filament 3 · SENATI ' . date('Y') . '</div>'),
            )

            // ===== MENÚ =====
            ->navigationGroups([
                NavigationGroup::make('Ventas'),
                NavigationGroup::make('Compras'),
                NavigationGroup::make('Catálogos'),
                NavigationGroup::make('Reportes'),
                NavigationGroup::make('Configuración')->collapsed(),
            ])
            ->maxContentWidth(MaxWidth::Full)
            ->sidebarCollapsibleOnDesktop()

            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                \App\Http\Middleware\ExigirCajaAbierta::class,   // la vendedora/cajera primero abre la caja
            ]);
    }

    public function boot(): void
    {
        // Todas las tablas: 10 filas por página y filas cebra
        Table::configureUsing(function (Table $table): void {
            $table
                ->defaultPaginationPageOption(10)
                ->paginationPageOptions([5, 10, 25, 50, 'all'])
                ->striped();
        });
    }
}