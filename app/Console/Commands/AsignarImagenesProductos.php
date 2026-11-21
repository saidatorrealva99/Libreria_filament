<?php

namespace App\Console\Commands;

use App\Models\Producto;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Asigna las fotos de los productos en un solo paso.
 *
 * 1. Guarda cada foto con el CÓDIGO del producto como nombre:  LIB-0001.jpg, UTI-0002.png …
 * 2. Ponlas en:  storage/app/public/productos/
 * 3. Ejecuta:    php artisan productos:imagenes
 *
 * Con --lista solo muestra qué nombre debe tener la foto de cada producto.
 */
class AsignarImagenesProductos extends Command
{
    protected $signature = 'productos:imagenes {--lista : Solo muestra el nombre de archivo que corresponde a cada producto}';

    protected $description = 'Asigna a cada producto la foto que tenga su código como nombre (LIB-0001.jpg)';

    private const EXTENSIONES = ['jpg', 'jpeg', 'png', 'webp'];

    public function handle(): int
    {
        $carpeta = storage_path('app/public/productos');
        File::ensureDirectoryExists($carpeta);

        // Fotos encontradas: CODIGO => nombre de archivo
        $fotos = collect(File::files($carpeta))
            ->filter(fn ($f) => in_array(strtolower($f->getExtension()), self::EXTENSIONES, true))
            ->mapWithKeys(fn ($f) => [strtoupper(pathinfo($f->getFilename(), PATHINFO_FILENAME)) => $f->getFilename()]);

        $productos = Producto::with('categoria')->orderBy('codigo')->get();

        if ($this->option('lista')) {
            $this->info("Guarda cada foto en: {$carpeta}");
            $this->table(
                ['Archivo que debes poner', 'Producto', 'Categoría', '¿Ya tiene foto?'],
                $productos->map(fn (Producto $p) => [
                    $p->codigo . '.jpg',
                    $p->nombre,
                    $p->categoria?->nombre,
                    isset($fotos[$p->codigo]) ? '✔ ' . $fotos[$p->codigo] : ($p->imagen ? '✔ (subida en el panel)' : '—'),
                ])->all()
            );

            return self::SUCCESS;
        }

        $asignadas = 0;
        foreach ($productos as $p) {
            if (! isset($fotos[$p->codigo])) {
                continue;
            }
            $p->forceFill(['imagen' => 'productos/' . $fotos[$p->codigo]])->saveQuietly();
            $asignadas++;
        }

        $sinFoto = $productos->filter(fn (Producto $p) => ! isset($fotos[$p->codigo]) && ! $p->imagen);

        $this->info("✔ {$asignadas} producto(s) con foto asignada.");

        if ($sinFoto->isNotEmpty()) {
            $this->warn("Faltan fotos para {$sinFoto->count()} producto(s):");
            $this->table(['Archivo que falta', 'Producto'], $sinFoto->map(fn ($p) => [$p->codigo . '.jpg', $p->nombre])->all());
        }

        if (! file_exists(public_path('storage'))) {
            $this->warn('Ejecuta también:  php artisan storage:link   (para que las fotos se vean en la web)');
        }

        return self::SUCCESS;
    }
}
