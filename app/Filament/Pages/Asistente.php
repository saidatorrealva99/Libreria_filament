<?php

namespace App\Filament\Pages;

use App\Support\AsistenteIA;
use Filament\Pages\Page;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Asistente IA del panel: el personal le pregunta en lenguaje natural
 * y responde con los datos reales del negocio.
 */
class Asistente extends Page
{
    protected static string $view = 'filament.pages.asistente';
    protected static ?string $navigationIcon = 'heroicon-o-sparkles';
    protected static ?string $navigationLabel = 'Asistente IA';
    protected static ?string $title = 'Asistente IA';
    protected static ?string $slug = 'asistente';
    protected static ?int $navigationSort = -1;   // justo debajo de Inicio

    // Conoce todos los números del negocio: solo administrador
    public static function canAccess(): bool
    {
        return \App\Support\Permisos::puede('asistente');
    }

    /** @var array<int, array{rol: string, texto: string}> */
    public array $mensajes = [];

    public string $pregunta = '';

    public const SUGERENCIAS = [
        '¿Cómo van las ventas este mes comparado con el anterior?',
        '¿Qué productos debo reponer primero?',
        '¿Cuáles son los 5 libros más vendidos?',
        '¿Qué productos no se están vendiendo?',
        '¿Qué vendedor vendió más este mes?',
        '¿Hay pedidos o ventas pendientes de pago?',
    ];

    public function preguntarSugerencia(int $i): void
    {
        $this->pregunta = self::SUGERENCIAS[$i] ?? '';
        $this->enviar();
    }

    /** Pregunta que espera respuesta (se responde en una segunda llamada) */
    public ?string $pendiente = null;

    // 1) Muestra al instante la pregunta del usuario…
    public function enviar(): void
    {
        $pregunta = trim($this->pregunta);

        if ($pregunta === '' || $this->pendiente) {
            return;
        }

        $this->mensajes[] = ['rol' => 'usuario', 'texto' => Str::limit($pregunta, 500)];
        $this->pendiente  = $pregunta;
        $this->pregunta   = '';

        $this->dispatch('asistente-respondio');
        $this->js('$wire.responder()');   // 2) …y luego pide la respuesta a la IA
    }

    public function responder(): void
    {
        if (! $this->pendiente) {
            return;
        }

        $historial = array_slice($this->mensajes, 0, -1);

        try {
            $respuesta = AsistenteIA::preguntar($this->pendiente, $historial, 'panel');
            $this->mensajes[] = ['rol' => 'ia', 'texto' => $respuesta];
        } catch (RuntimeException $e) {
            $this->mensajes[] = ['rol' => 'error', 'texto' => $e->getMessage()];
        }

        $this->pendiente = null;
        $this->dispatch('asistente-respondio');
    }

    public function limpiar(): void
    {
        $this->mensajes  = [];
        $this->pendiente = null;
    }

    /** Respuesta de la IA en Markdown → HTML seguro (escapa cualquier HTML) */
    public function html(string $texto): string
    {
        return Str::markdown($texto, ['html_input' => 'escape', 'allow_unsafe_links' => false]);
    }
}
