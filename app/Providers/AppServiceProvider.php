<?php

namespace App\Providers;

use App\Http\Responses\LogoutResponse;
use App\Models\Actividad;
use App\Models\Ajuste;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;   // 🆕
use Carbon\Carbon;
use Filament\Http\Responses\Auth\Contracts\LogoutResponse as LogoutResponseContract;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Cerrar sesión → página de inicio
        $this->app->bind(LogoutResponseContract::class, LogoutResponse::class);
    }

    public function boot(): void
    {
        // 🆕 Si entran por el link público de Cloudflare, todo se carga con https (estilos, scripts, formularios)
        if (! $this->app->runningInConsole() && str_contains(request()->getHost(), 'trycloudflare.com')) {
            URL::forceScheme('https');
        }

        // Fechas en español: "lunes, 16 de noviembre", "hace 2 horas"
        Carbon::setLocale('es');

        // Datos de la librería guardados desde el panel (Configuración → Datos de la librería)
        Ajuste::aplicar();

        // Bitácora de sesiones (el usuario que inicia sesión es App\Models\User)
        Event::listen(Login::class, function (Login $e) {
            /** @var \App\Models\User $u */
            $u = $e->user;
            Actividad::registrar('inició sesión', 'Sesión', $u->id, "{$u->name} inició sesión", null, $u->id);
        });
        Event::listen(Logout::class, function (Logout $e) {
            /** @var \App\Models\User|null $u */
            $u = $e->user;
            if ($u) {
                Actividad::registrar('cerró sesión', 'Sesión', $u->id, "{$u->name} cerró sesión", null, $u->id);
            }
        });
        Event::listen(Failed::class, function (Failed $e) {
            Actividad::registrar('login fallido', 'Sesión', null, 'Intento fallido de inicio de sesión con ' . ($e->credentials['email'] ?? '¿?'));
        });
    }
}