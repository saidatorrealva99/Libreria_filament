<?php

namespace App\Filament\Pages;

use App\Filament\Resources\CompraResource;
use App\Support\Formato;
use App\Support\Inventario;
use App\Support\Permisos;
use Filament\Actions\Action;
use Filament\Pages\Page;

/**
 * Inventario inteligente: días de cobertura, cantidad sugerida y clasificación de productos,
 * calculados con el ritmo real de ventas (análisis predictivo simple).
 */
class Reposicion extends Page
{
    protected static string $view = 'filament.pages.reposicion';
    protected static ?string $navigationIcon = 'heroicon-o-light-bulb';
    protected static ?string $navigationLabel = 'Reposición inteligente';
    protected static ?string $title = 'Reposición inteligente';
    protected static ?string $slug = 'reposicion';
    protected static ?string $navigationGroup = 'Compras';
    protected static ?int $navigationSort = 3;

    /** Pestaña activa: reponer | estrellas | sin_movimiento | todos */
    public string $vista = 'reponer';

    public string $buscar = '';

    public static function canAccess(): bool
    {
        return Permisos::puede('reposicion');
    }

    public static function getNavigationBadge(): ?string
    {
        $n = Inventario::reponer()->count();

        return $n ? (string) $n : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public function getSubheading(): ?string
    {
        return 'Calculado con las ventas de los últimos ' . Inventario::DIAS_ANALISIS . ' días: '
            . 'cuántos días te alcanza el stock y cuánto conviene comprar para cubrir ' . Inventario::DIAS_OBJETIVO . ' días.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('compra')
                ->label('Generar compra sugerida')
                ->icon('heroicon-m-inbox-arrow-down')
                ->url(CompraResource::getUrl('create', ['sugerido' => 1]))
                ->visible(fn () => Permisos::puede('compras', 'crear') && Inventario::reponer()->isNotEmpty()),
        ];
    }

    protected function getViewData(): array
    {
        $todos = Inventario::analisis();

        $filas = match ($this->vista) {
            'estrellas'      => $todos->where('clase', 'estrella')->sortByDesc('vendidas'),
            'sin_movimiento' => $todos->whereIn('clase', ['sin_movimiento', 'lento']),
            'todos'          => $todos,
            default          => $todos->where('sugerido', '>', 0),
        };

        if ($this->buscar !== '') {
            $q     = mb_strtolower($this->buscar);
            $filas = $filas->filter(fn ($f) => str_contains(mb_strtolower($f['producto']->nombre . ' ' . $f['producto']->codigo), $q));
        }

        $sinMov = $todos->whereIn('clase', ['sin_movimiento', 'lento']);

        return [
            'filas'  => $filas->values(),
            'conteo' => [
                'reponer'        => $todos->where('sugerido', '>', 0)->count(),
                'estrellas'      => $todos->where('clase', 'estrella')->count(),
                'sin_movimiento' => $sinMov->count(),
                'todos'          => $todos->count(),
            ],
            'kpis' => [
                ['Por reponer', $todos->where('sugerido', '>', 0)->count() . ' productos', 'Agotados o con menos de ' . Inventario::DIAS_ALERTA . ' días de stock'],
                ['Inversión sugerida', Formato::soles($todos->sum('inversion')), 'A costo de la última compra'],
                ['Productos estrella', $todos->where('clase', 'estrella')->count(), 'Top 20 % en unidades vendidas'],
                ['Capital inmovilizado', Formato::soles($sinMov->sum(fn ($f) => $f['producto']->stock * $f['costo'])), 'Stock lento o sin ventas (a costo)'],
            ],
        ];
    }
}
