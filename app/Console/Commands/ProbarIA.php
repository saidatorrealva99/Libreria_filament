<?php

namespace App\Console\Commands;

use App\Support\AsistenteIA;
use Illuminate\Console\Command;

/**
 * Revisa paso a paso que la IA (Gemini) esté bien configurada.
 *
 *   php artisan ia:probar
 */
class ProbarIA extends Command
{
    protected $signature = 'ia:probar';

    protected $description = 'Comprueba la conexión con Gemini y explica cómo arreglar cada problema';

    public function handle(): int
    {
        $this->newLine();
        $this->line('<options=bold>Revisión del asistente con IA (Gemini)</>');
        $this->newLine();

        // 0. Motor: IA propia (sin internet)
        if (AsistenteIA::motor() === 'local') {
            $this->info('✔ Motor: IA PROPIA (funciona sin internet ni claves)');
            $this->line('… Pregunta de prueba: "¿Qué debo reponer?"');
            $this->newLine();
            $this->line(\App\Support\IALocal::responder('¿Qué debo reponer?', 'panel'));
            $this->newLine();
            $this->info('✔ ¡La IA propia respondió! El Asistente y Lucía (tienda) ya funcionan.');
            $this->line('   (Para usar Gemini: IA_MOTOR=gemini o IA_MOTOR=auto en el .env)');

            return self::SUCCESS;
        }

        // 1. Clave
        $clave = (string) config('libreria.ia.key');
        if ($clave === '') {
            $this->error('✘ 1. No hay clave. Falta GEMINI_API_KEY en el archivo .env');
            $this->line('   → Sácala gratis en https://aistudio.google.com/apikey y agrega al final del .env:');
            $this->line('     GEMINI_API_KEY=tu_clave');
            $this->line('   → Luego ejecuta: php artisan optimize:clear');

            return self::FAILURE;
        }
        $this->info('✔ 1. Clave encontrada: ' . substr($clave, 0, 6) . str_repeat('•', 10) . substr($clave, -4));

        if (str_contains($clave, ' ') || str_contains($clave, '"')) {
            $this->warn('   ⚠ La clave tiene espacios o comillas. Déjala así: GEMINI_API_KEY=AIza... (sin comillas ni espacios)');
        }

        // 2. Modelo
        $this->info('✔ 2. Modelo: ' . (config('libreria.ia.modelo') ?: 'gemini-flash-latest'));

        // 3. Certificado SSL de PHP (problema típico de XAMPP)
        $cainfo = ini_get('curl.cainfo');
        if (! $cainfo || ! file_exists($cainfo)) {
            $this->warn('⚠ 3. PHP no tiene configurado el certificado SSL (curl.cainfo). Si la prueba falla por SSL:');
            $this->line('   → Abre ' . (php_ini_loaded_file() ?: 'C:\\xampp\\php\\php.ini') . ' y busca curl.cainfo');
            $this->line('   → Déjalo así (quita el ; del inicio):');
            $this->line('     curl.cainfo="C:\\xampp\\apache\\bin\\curl-ca-bundle.crt"');
            $this->line('     openssl.cafile="C:\\xampp\\apache\\bin\\curl-ca-bundle.crt"');
            $this->line('   → Guarda, cierra la terminal de "php artisan serve" y vuelve a ejecutarlo.');
        } else {
            $this->info('✔ 3. Certificado SSL: ' . $cainfo);
        }

        // 4. Prueba real
        $this->line('… 4. Preguntando a Gemini (unos segundos)');
        try {
            $respuesta = AsistenteIA::generarJson(
                'Responde solo con JSON.',
                [['text' => 'Devuelve {"ok": true, "saludo": "un saludo corto en español para una librería"}']]
            );
        } catch (\Throwable $e) {
            $this->error('✘ 4. Falló: ' . $e->getMessage());

            return self::FAILURE;
        }

        $this->info('✔ 4. ¡Gemini respondió! → ' . ($respuesta['saludo'] ?? json_encode($respuesta, JSON_UNESCAPED_UNICODE)));
        $this->newLine();
        $this->line('Todo listo: prueba el Asistente IA del panel y la Lista escolar de la tienda.');

        return self::SUCCESS;
    }
}
