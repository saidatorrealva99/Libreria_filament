<?php

namespace App\Http\Controllers;

use App\Filament\Resources\ReclamacionResource;
use App\Models\Reclamacion;
use App\Models\Rol;
use App\Models\User;
use App\Support\Reglas;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;

/**
 * Libro de Reclamaciones virtual (parte pública).
 */
class ReclamacionController extends Controller
{
    private const DOCUMENTO_REGEX = [
        'DNI'       => ['/^[0-9]{8}$/', 'El DNI tiene 8 dígitos.'],
        'CE'        => ['/^[0-9]{9,12}$/', 'El carné de extranjería tiene de 9 a 12 dígitos.'],
        'PASAPORTE' => ['/^[A-Za-z0-9]{6,12}$/', 'Pasaporte: de 6 a 12 letras o números.'],
        'RUC'       => ['/^(10|15|17|20)[0-9]{9}$/', 'RUC de 11 dígitos que empieza con 10, 15, 17 o 20.'],
    ];

    public function formulario()
    {
        return view('tienda.reclamaciones.formulario', [
            'siguiente' => sprintf('%06d-%d', (int) Reclamacion::where('anio', now()->year)->max('numero') + 1, now()->year),
        ]);
    }

    public function guardar(Request $request)
    {
        $tipoDoc = $request->input('tipo_documento');
        [$regex, $mensaje] = self::DOCUMENTO_REGEX[$tipoDoc] ?? self::DOCUMENTO_REGEX['DNI'];

        $data = $request->validate([
            'tipo_documento'    => ['required', Rule::in(array_keys(Reclamacion::DOCUMENTOS))],
            'numero_documento'  => ['required', 'regex:' . $regex],
            'nombres'           => ['required', 'string', 'max:100', 'regex:' . ($tipoDoc === 'RUC' ? Reglas::RAZON : Reglas::NOMBRE)],
            'apellidos'         => [Rule::requiredIf($tipoDoc !== 'RUC'), 'nullable', 'string', 'max:100', 'regex:' . Reglas::NOMBRE],
            'telefono'          => ['required', 'regex:/^[0-9]{7,9}$/'],
            'email'             => ['required', 'email', 'max:120', 'regex:' . Reglas::EMAIL],
            'direccion'         => ['required', 'string', 'max:200'],
            'menor_edad'        => ['nullable', 'boolean'],
            'apoderado'         => ['required_if:menor_edad,1', 'nullable', 'string', 'max:150', 'regex:' . Reglas::NOMBRE],
            'bien_tipo'         => ['required', Rule::in(['producto', 'servicio'])],
            'bien_descripcion'  => ['required', 'string', 'max:200'],
            'monto'             => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'pedido_referencia' => ['nullable', 'string', 'max:30'],
            'tipo'              => ['required', Rule::in(array_keys(Reclamacion::TIPOS))],
            'detalle'           => ['required', 'string', 'min:15', 'max:2000'],
            'pedido'            => ['required', 'string', 'min:5', 'max:1000'],
            'acepta_terminos'   => ['accepted'],
        ], [
            'numero_documento.regex' => $mensaje,
            'nombres.regex'          => $tipoDoc === 'RUC' ? Reglas::MSG_RAZON : Reglas::MSG_NOMBRE,
            'apellidos.regex'        => Reglas::MSG_NOMBRE,
            'apoderado.regex'        => Reglas::MSG_NOMBRE,
            'email.regex'            => Reglas::MSG_EMAIL,
            'email.email'            => Reglas::MSG_EMAIL,
            'telefono.regex'         => 'El teléfono debe tener de 7 a 9 dígitos.',
            'apoderado.required_if'  => 'Si eres menor de edad, escribe el nombre de tu padre, madre o tutor.',
            'detalle.min'            => 'Cuéntanos un poco más (mínimo 15 caracteres).',
            'acepta_terminos.accepted' => 'Debes aceptar para poder registrar tu hoja.',
        ]);

        $data['menor_edad']      = (bool) ($data['menor_edad'] ?? false);
        $data['apoderado']       = $data['menor_edad'] ? $data['apoderado'] : null;
        $data['apellidos']       = $tipoDoc === 'RUC' ? null : $data['apellidos'];
        $data['numero_documento'] = strtoupper($data['numero_documento']);
        $data['acepta_terminos'] = true;
        $data['ip']              = $request->ip();

        $hoja = DB::transaction(fn () => Reclamacion::create($data));

        $url = URL::signedRoute('reclamaciones.hoja', ['codigo' => $hoja->codigo]);

        $this->enviarCopia($hoja, $url);
        $this->avisarAlPersonal($hoja);

        return redirect($url)->with('nuevo', true);
    }

    /** Hoja de reclamación (constancia). Solo con enlace firmado o desde el panel. */
    public function hoja(Request $request, string $codigo)
    {
        abort_unless($request->hasValidSignature() || \Illuminate\Support\Facades\Auth::check(), 403);

        return view('tienda.reclamaciones.hoja', [
            'r' => Reclamacion::where('codigo', $codigo)->firstOrFail(),
        ]);
    }

    public function consulta()
    {
        return view('tienda.reclamaciones.consulta');
    }

    public function buscar(Request $request)
    {
        $data = $request->validate([
            'codigo'           => ['required', 'string', 'max:15'],
            'numero_documento' => ['required', 'string', 'max:15'],
        ]);

        $hoja = Reclamacion::where('codigo', trim($data['codigo']))
            ->where('numero_documento', strtoupper(trim($data['numero_documento'])))
            ->first();

        if (! $hoja) {
            return back()->withInput()->with('error', 'No encontramos una hoja con ese número y documento.');
        }

        return redirect(URL::signedRoute('reclamaciones.hoja', ['codigo' => $hoja->codigo]));
    }

    /** Copia al correo del consumidor (si el correo no está configurado, queda en el log) */
    private function enviarCopia(Reclamacion $hoja, string $url): void
    {
        try {
            Mail::raw(
                "Hola {$hoja->consumidor},\n\n"
                . "Registramos tu " . strtolower(Reclamacion::TIPOS[$hoja->tipo]) . " N° {$hoja->codigo} el " . $hoja->created_at->format('d/m/Y H:i') . ".\n"
                . "Te responderemos a más tardar el " . $hoja->vence_at->format('d/m/Y') . " (15 días hábiles).\n\n"
                . "Ver tu hoja: {$url}\n\n"
                . config('libreria.razon_social') . ' · RUC ' . config('libreria.ruc'),
                fn ($m) => $m->to($hoja->email)->subject("Libro de Reclamaciones · Hoja N° {$hoja->codigo}")
            );
        } catch (\Throwable $e) {
            Log::warning('No se pudo enviar la copia del reclamo: ' . $e->getMessage());
        }
    }

    private function avisarAlPersonal(Reclamacion $hoja): void
    {
        $destinatarios = User::where('activo', true)
            ->whereHas('rol', fn ($q) => $q->where('nombre', Rol::ADMIN))
            ->get();

        if ($destinatarios->isEmpty()) {
            return;
        }

        Notification::make()
            ->title('Nueva hoja en el Libro de Reclamaciones')
            ->body("{$hoja->codigo} · " . Reclamacion::TIPOS[$hoja->tipo] . " de {$hoja->consumidor}. Responder hasta el " . $hoja->vence_at->format('d/m/Y') . '.')
            ->icon('heroicon-o-book-open')
            ->iconColor('danger')
            ->actions([
                NotificationAction::make('ver')
                    ->label('Ver hoja')
                    ->url(ReclamacionResource::getUrl('view', ['record' => $hoja], panel: 'admin'))
                    ->markAsRead(),
            ])
            ->sendToDatabase($destinatarios);
    }
}
