<?php

namespace App\Filament\Pages;

use App\Support\Periodo;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Illuminate\Contracts\Support\Htmlable;

class Dashboard extends BaseDashboard
{
    // Filtro de fechas global: todas las tarjetas y gráficos lo usan
    use HasFiltersForm;

    protected static ?string $navigationLabel = 'Inicio';
    protected static ?string $navigationIcon = 'heroicon-o-home';

    // Sin título arriba: el banner de bienvenida hace de título
    public function getTitle(): string|Htmlable
    {
        return '';
    }

    // 3 columnas en pantallas grandes
    public function getColumns(): int|string|array
    {
        return ['md' => 2, 'xl' => 3];
    }

    public function filtersForm(Form $form): Form
    {
        return $form->schema([
            Section::make()
                ->visible(fn () => \App\Support\Permisos::puede('dashboard_negocio'))
                ->compact()
                ->extraAttributes(['class' => 'lib-filtro'])
                ->schema([
                    Select::make('periodo')
                        ->inlineLabel()
                        ->label('Periodo')
                        ->prefixIcon('heroicon-m-calendar-days')
                        ->options(Periodo::OPCIONES)
                        ->default('mes')
                        ->selectablePlaceholder(false)
                        ->live(),
                    DatePicker::make('desde')
                        ->inlineLabel()
                        ->label('Desde')
                        ->native(false)
                        ->displayFormat('d/m/Y')
                        ->maxDate(now())
                        ->default(now()->startOfMonth())
                        ->visible(fn (Get $get) => $get('periodo') === 'personalizado'),
                    DatePicker::make('hasta')
                        ->inlineLabel()
                        ->label('Hasta')
                        ->native(false)
                        ->displayFormat('d/m/Y')
                        ->maxDate(now())
                        ->default(now())
                        ->visible(fn (Get $get) => $get('periodo') === 'personalizado'),
                ])
                ->columns(['md' => 3]),
        ]);
    }
}
