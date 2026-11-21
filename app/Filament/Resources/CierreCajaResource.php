<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CierreCajaResource\Pages;
use App\Models\CierreCaja;
use App\Models\Pago;
use App\Support\Formato;
use App\Support\TienePermisos;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

/**
 * Cierre de caja diario: compara lo que el sistema registró como cobrado
 * con el efectivo que se contó físicamente en la caja.
 */
class CierreCajaResource extends Resource
{
    use TienePermisos;
    protected static string $modulo = 'cierres';

    protected static ?string $model = CierreCaja::class;

    protected static ?string $slug = 'cierres-caja';
    protected static ?string $modelLabel = 'caja';
    protected static ?string $pluralModelLabel = 'Caja: apertura y cierre';
    protected static ?string $navigationLabel = 'Caja';
    protected static ?string $navigationGroup = 'Ventas';
    protected static ?string $navigationIcon = 'heroicon-o-lock-closed';
    protected static ?int $navigationSort = 3;

    // Un cierre no se modifica ni se borra
    public static function canEdit(Model $record): bool { return false; }
    public static function canDelete(Model $record): bool { return false; }
    public static function canDeleteAny(): bool { return false; }
    public static function canCreate(): bool { return false; }   // se abre con el botón "Abrir caja"

    /** Campos del modal "Cerrar caja" (arqueo) */
    public static function camposCierre(CierreCaja $caja): array
    {
        return [
            Forms\Components\Placeholder::make('resumen')
                ->label('Lo que cobraste en tu turno (según el sistema)')
                ->content(fn () => self::tablaResumen($caja->id)),
            Forms\Components\Hidden::make('caja_id')->default($caja->id),
            Forms\Components\Hidden::make('fondo_inicial')->default((float) $caja->fondo_inicial),
            Forms\Components\Hidden::make('fecha')->default($caja->fecha->toDateString()),
            Forms\Components\TextInput::make('efectivo_contado')
                ->label('Efectivo contado en la caja')
                ->helperText('Cuenta billetes y monedas, incluido el fondo inicial de ' . Formato::soles($caja->fondo_inicial) . '.')
                ->prefix('S/')->numeric()->minValue(0)->required()
                ->live(onBlur: true),
            Forms\Components\Placeholder::make('cuadre')
                ->hiddenLabel()
                ->content(fn (Get $get) => self::cuadre($get)),
            Forms\Components\Textarea::make('observacion')
                ->label('Observación')
                ->rows(2)
                ->maxLength(300)
                ->placeholder('Ej.: faltante por vuelto mal dado, se repone mañana.')
                ->required(fn (Get $get) => abs(self::diferencia($get)) >= 0.01)
                ->validationMessages(['required' => 'Explica brevemente por qué la caja no cuadra.']),
        ];
    }

    public static function accionAbrir(\Filament\Actions\Action $action): \Filament\Actions\Action
    {
        return $action
            ->label('Abrir caja')
            ->icon('heroicon-m-lock-open')
            ->color('success')
            ->visible(fn () => ! CierreCaja::deHoy() && \App\Support\Permisos::puede('cierres', 'crear'))
            ->modalHeading('Apertura de caja · ' . now()->translatedFormat('l d \\d\\e F'))
            ->modalDescription('Registra con cuánto sencillo empieza la caja. Desde ahora se podrán registrar cobros.')
            ->modalSubmitActionLabel('Abrir caja')
            ->form([
                Forms\Components\TextInput::make('fondo_inicial')
                    ->label('Fondo inicial (sencillo)')
                    ->prefix('S/')->numeric()->minValue(0)->maxValue(5000)->default(100)->required(),
            ])
            ->action(function (array $data) {
                CierreCaja::abrir((float) $data['fondo_inicial']);
                \Filament\Notifications\Notification::make()->success()->title('Caja abierta')
                    ->body('Fondo inicial: ' . Formato::soles($data['fondo_inicial']) . '. ¡Buenas ventas!')->send();
            });
    }

    public static function accionCerrar(Tables\Actions\Action|\Filament\Actions\Action $action, ?CierreCaja $caja = null)
    {
        return $action
            ->label('Cerrar caja')
            ->icon('heroicon-m-lock-closed')
            ->color('danger')
            ->visible(fn (?CierreCaja $record = null) => ($record ?? $caja ?? CierreCaja::abiertaHoy())?->estado === 'abierta'
                && \App\Support\Permisos::puede('cierres', 'crear'))
            ->modalHeading('Cierre de caja (arqueo)')
            ->modalSubmitActionLabel('Cerrar caja')
            ->form(fn (?CierreCaja $record = null) => self::camposCierre($record ?? $caja ?? CierreCaja::abiertaHoy()))
            ->action(function (array $data, ?CierreCaja $record = null) use ($caja) {
                $caja = $record ?? $caja ?? CierreCaja::abiertaHoy();
                $caja->cerrar((float) $data['efectivo_contado'], $data['observacion'] ?? null);
                \Filament\Notifications\Notification::make()
                    ->title('Caja cerrada: ' . $caja->estadoCuadre())
                    ->body('Diferencia: ' . Formato::soles($caja->diferencia))
                    ->color($caja->estadoCuadre() === 'Cuadrada' ? 'success' : 'warning')
                    ->success()
                    ->send();
            });
    }

    public static function diferencia(Get $get): float
    {
        if (blank($get('efectivo_contado'))) {
            return 0;
        }
        $sistema = self::resumenDe($get('caja_id'));

        return round((float) $get('efectivo_contado') - ((float) $get('fondo_inicial') + $sistema['efectivo']), 2);
    }

    /** Resumen de una caja (solo lo cobrado por la persona en ese turno) */
    private static function resumenDe(?int $cajaId): array
    {
        return CierreCaja::find($cajaId)?->resumen()
            ?? ['efectivo' => 0, 'tarjeta' => 0, 'yape' => 0, 'plin' => 0, 'transferencia' => 0, 'total' => 0, 'pagos' => 0];
    }

    private static function tablaResumen(?int $cajaId): HtmlString
    {
        $r = self::resumenDe($cajaId);
        $filas = '';
        foreach (Pago::METODOS as $clave => $nombre) {
            $filas .= '<tr><td style="padding:.35rem 0">' . e($nombre) . '</td><td style="text-align:right;font-variant-numeric:tabular-nums">' . Formato::soles($r[$clave]) . '</td></tr>';
        }

        return new HtmlString(
            '<table style="width:100%;max-width:420px;font-size:.9rem">' . $filas
            . '<tr style="border-top:1px solid rgba(120,113,108,.3);font-weight:700"><td style="padding:.5rem 0">Total cobrado (' . $r['pagos'] . ' pagos)</td><td style="text-align:right">' . Formato::soles($r['total']) . '</td></tr></table>'
        );
    }

    private static function cuadre(Get $get): HtmlString
    {
        if (blank($get('efectivo_contado'))) {
            return new HtmlString('<span style="color:#78716c">Cuenta el efectivo de la caja y escríbelo arriba.</span>');
        }

        $r        = self::resumenDe($get('caja_id'));
        $esperado = (float) $get('fondo_inicial') + $r['efectivo'];
        $dif      = self::diferencia($get);
        [$texto, $color, $fondo] = match (true) {
            abs($dif) < 0.01 => ['✔ La caja cuadra', '#15803d', '#ecfdf5'],
            $dif > 0         => ['▲ Sobrante de ' . Formato::soles($dif), '#b45309', '#fffbeb'],
            default          => ['▼ Faltante de ' . Formato::soles(abs($dif)), '#b91c1c', '#fef2f2'],
        };

        return new HtmlString(
            "<div style=\"border-radius:.75rem;padding:.9rem 1rem;background:{$fondo};color:{$color}\">"
            . '<div style="font-size:.85rem;color:#57534e">Efectivo esperado: fondo ' . Formato::soles($get('fondo_inicial') ?: 0)
            . ' + cobrado en efectivo ' . Formato::soles($r['efectivo']) . ' = <b>' . Formato::soles($esperado) . '</b></div>'
            . "<div style=\"font-size:1.25rem;font-weight:800;margin-top:.3rem\">{$texto}</div></div>"
        );
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('fecha', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('fecha')->date('l d/m/Y')->weight(FontWeight::SemiBold)->sortable()
                    ->description(fn (CierreCaja $record) => $record->abierta_at
                        ? 'Abrió ' . $record->abierta_at->format('H:i') . ($record->abrio ? ' · ' . $record->abrio->name : '')
                          . ($record->cerrada_at ? ' — cerró ' . $record->cerrada_at->format('H:i') : '')
                        : null),
                Tables\Columns\TextColumn::make('abrio.name')->label('Atendió')->icon('heroicon-m-user-circle')->weight(FontWeight::SemiBold)->placeholder('—'),
                Tables\Columns\TextColumn::make('ventas_count')->counts('ventas')->label('Ventas')->badge()->alignCenter(),
                Tables\Columns\TextColumn::make('estado')->badge()
                    ->formatStateUsing(fn (string $state) => $state === 'abierta' ? '🟢 Abierta' : 'Cerrada')
                    ->color(fn (string $state) => $state === 'abierta' ? 'success' : 'gray'),
                Tables\Columns\TextColumn::make('fondo_inicial')->label('Fondo')->formatStateUsing(fn ($state) => Formato::soles($state)),
                Tables\Columns\TextColumn::make('total_sistema')->label('Cobrado (sistema)')
                    ->formatStateUsing(fn ($state) => Formato::soles($state))
                    ->description(fn (CierreCaja $record) => $record->numero_pagos . ' pagos'),
                Tables\Columns\TextColumn::make('efectivo_sistema')->label('Efectivo esperado')
                    ->formatStateUsing(fn (CierreCaja $record) => Formato::soles((float) $record->fondo_inicial + (float) $record->efectivo_sistema)),
                Tables\Columns\TextColumn::make('efectivo_contado')->label('Contado')->formatStateUsing(fn ($state) => Formato::soles($state))->placeholder('—'),
                Tables\Columns\TextColumn::make('diferencia')
                    ->badge()
                    ->formatStateUsing(fn (CierreCaja $record) => $record->estadoCuadre() . ($record->estado === 'cerrada' && abs((float) $record->diferencia) >= 0.01 ? ' ' . Formato::soles(abs((float) $record->diferencia)) : ''))
                    ->color(fn (CierreCaja $record) => match ($record->estadoCuadre()) { 'Cuadrada' => 'success', 'Sobrante' => 'warning', 'Abierta' => 'gray', default => 'danger' }),
                Tables\Columns\TextColumn::make('usuario.name')->label('Cerró')->placeholder('—'),
            ])
            ->actions([
                self::accionCerrar(Tables\Actions\Action::make('cerrar')),
                Tables\Actions\ViewAction::make()->iconButton()->color('info'),
            ])
            ->emptyStateIcon('heroicon-o-lock-closed')
            ->emptyStateHeading('Aún no hay cierres de caja')
            ->emptyStateDescription('Al empezar el día abre la caja con el fondo inicial; al terminar, ciérrala con el arqueo.');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        $soles = fn ($state) => Formato::soles($state);

        return $infolist->schema([
            InfoSection::make('Turno')->schema([
                TextEntry::make('abrio.name')->label('Atendió')->weight(FontWeight::Bold)->icon('heroicon-m-user-circle'),
                TextEntry::make('abierta_at')->label('Abrió')->dateTime('d/m/Y H:i')->placeholder('—'),
                TextEntry::make('cerrada_at')->label('Cerró')->dateTime('d/m/Y H:i')->placeholder('Aún abierta'),
                TextEntry::make('estado')->badge()->color(fn (string $state) => $state === 'abierta' ? 'success' : 'gray'),
            ])->columns(4),
            InfoSection::make('Cobrado según el sistema')->schema([
                TextEntry::make('efectivo_sistema')->label('Efectivo')->formatStateUsing($soles),
                TextEntry::make('tarjeta_sistema')->label('Tarjeta')->formatStateUsing($soles),
                TextEntry::make('yape_sistema')->label('Yape')->formatStateUsing($soles),
                TextEntry::make('plin_sistema')->label('Plin')->formatStateUsing($soles),
                TextEntry::make('transferencia_sistema')->label('Transferencia')->formatStateUsing($soles),
                TextEntry::make('total_sistema')->label('Total')->formatStateUsing($soles)->weight(FontWeight::Bold),
            ])->columns(6),
            InfoSection::make('Cuadre del efectivo')->schema([
                TextEntry::make('fondo_inicial')->label('Fondo inicial')->formatStateUsing($soles),
                TextEntry::make('efectivo_contado')->label('Contado')->formatStateUsing($soles),
                TextEntry::make('diferencia')->badge()
                    ->formatStateUsing(fn (CierreCaja $record) => $record->estadoCuadre() . ' ' . Formato::soles(abs((float) $record->diferencia)))
                    ->color(fn (CierreCaja $record) => match ($record->estadoCuadre()) { 'Cuadrada' => 'success', 'Sobrante' => 'warning', default => 'danger' }),
                TextEntry::make('usuario.name')->label('Cerró')->placeholder('—'),
                TextEntry::make('observacion')->label('Observación')->placeholder('—')->columnSpanFull(),
            ])->columns(4),
        ]);
    }

    // Quien atiende la caja solo ve la caja de hoy
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return \App\Support\Permisos::puede('historial')
            ? parent::getEloquentQuery()
            : parent::getEloquentQuery()->where('user_apertura_id', \Illuminate\Support\Facades\Auth::id())->whereDate('fecha', today());
    }

    /** Historial del turno en la vista de la caja: ventas atendidas y cobros */
    public static function getRelations(): array
    {
        return [
            CierreCajaResource\RelationManagers\VentasRelationManager::class,
            CierreCajaResource\RelationManagers\PagosRelationManager::class,
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return CierreCaja::abiertaHoy() ? 'Abierta' : 'Cerrada';
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return CierreCaja::abiertaHoy() ? 'success' : 'danger';
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCierresCaja::route('/'),
            'view'   => Pages\ViewCierreCaja::route('/{record}'),
        ];
    }
}
