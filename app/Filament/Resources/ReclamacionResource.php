<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReclamacionResource\Pages;
use App\Models\Reclamacion;
use App\Support\Permisos;
use App\Support\TienePermisos;
use Filament\Actions as PageActions;
use Filament\Forms;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

/**
 * Libro de Reclamaciones (panel). Las hojas no se crean, editan ni eliminan aquí:
 * las registra el consumidor y se conservan. El personal solo las responde.
 */
class ReclamacionResource extends Resource
{
    use TienePermisos;
    protected static string $modulo = 'reclamaciones';

    protected static ?string $model = Reclamacion::class;

    protected static ?string $slug = 'libro-reclamaciones';
    protected static ?string $modelLabel = 'hoja de reclamación';
    protected static ?string $pluralModelLabel = 'Libro de reclamaciones';
    protected static ?string $navigationLabel = 'Libro de reclamaciones';
    protected static ?string $navigationGroup = 'Ventas';
    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?int $navigationSort = 5;

    public const COLORES_ESTADO = ['pendiente' => 'warning', 'en_proceso' => 'info', 'atendido' => 'success'];

    public static function canCreate(): bool { return false; }
    public static function canEdit(Model $record): bool { return false; }
    public static function canDelete(Model $record): bool { return false; }
    public static function canDeleteAny(): bool { return false; }

    public static function getNavigationBadge(): ?string
    {
        $n = Reclamacion::where('estado', '!=', 'atendido')->count();

        return $n ? (string) $n : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return Reclamacion::where('estado', '!=', 'atendido')->whereDate('vence_at', '<=', now()->addDays(3))->exists() ? 'danger' : 'warning';
    }

    /** Texto y color del plazo */
    public static function plazo(Reclamacion $r): array
    {
        if ($r->atendido()) {
            return ['Respondido ' . $r->respondido_at->format('d/m/Y'), 'success'];
        }

        $dias = $r->diasRestantes();

        return match (true) {
            $dias < 0  => ['Vencido hace ' . abs($dias) . ' día(s) hábil(es)', 'danger'],
            $dias === 0 => ['Vence hoy', 'danger'],
            $dias <= 3 => ["Quedan {$dias} día(s) hábil(es)", 'danger'],
            $dias <= 7 => ["Quedan {$dias} días hábiles", 'warning'],
            default    => ["Quedan {$dias} días hábiles", 'gray'],
        };
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('codigo')
                    ->label('N° hoja')
                    ->fontFamily('mono')
                    ->weight(FontWeight::Bold)
                    ->description(fn (Reclamacion $record) => $record->created_at->format('d/m/Y H:i'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('nombres')
                    ->label('Consumidor')
                    ->formatStateUsing(fn (Reclamacion $record) => $record->consumidor)
                    ->description(fn (Reclamacion $record) => "{$record->tipo_documento} {$record->numero_documento}")
                    ->searchable(['nombres', 'apellidos', 'numero_documento']),
                Tables\Columns\TextColumn::make('tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => Reclamacion::TIPOS[$state])
                    ->color(fn (string $state) => $state === 'reclamo' ? 'danger' : 'warning'),
                Tables\Columns\TextColumn::make('bien_descripcion')
                    ->label('Bien')
                    ->limit(35)
                    ->tooltip(fn (Reclamacion $record) => $record->bien_descripcion)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('estado')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => Reclamacion::ESTADOS[$state])
                    ->color(fn (string $state) => self::COLORES_ESTADO[$state]),
                Tables\Columns\TextColumn::make('vence_at')
                    ->label('Plazo (15 días hábiles)')
                    ->formatStateUsing(fn (Reclamacion $record) => self::plazo($record)[0])
                    ->color(fn (Reclamacion $record) => self::plazo($record)[1])
                    ->icon('heroicon-m-clock')
                    ->description(fn (Reclamacion $record) => $record->atendido() ? null : 'Límite: ' . $record->vence_at->format('d/m/Y'))
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('estado')->options(Reclamacion::ESTADOS),
                Tables\Filters\SelectFilter::make('tipo')->options(Reclamacion::TIPOS),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->iconButton()->color('info')->tooltip('Ver hoja'),
                self::accionResponder(Tables\Actions\Action::make('responder'))->iconButton(),
            ])
            ->emptyStateIcon('heroicon-o-book-open')
            ->emptyStateHeading('Sin reclamos ni quejas')
            ->emptyStateDescription('Las hojas que registren los clientes en la tienda online aparecerán aquí.');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            InfoSection::make('Plazo de atención')
                ->schema([
                    TextEntry::make('estado')->badge()
                        ->formatStateUsing(fn (string $state) => Reclamacion::ESTADOS[$state])
                        ->color(fn (string $state) => self::COLORES_ESTADO[$state]),
                    TextEntry::make('created_at')->label('Registrado')->dateTime('d/m/Y H:i'),
                    TextEntry::make('vence_at')->label('Fecha límite')->date('d/m/Y'),
                    TextEntry::make('plazo')->label('Situación')
                        ->state(fn (Reclamacion $record) => self::plazo($record)[0])
                        ->color(fn (Reclamacion $record) => self::plazo($record)[1])
                        ->weight(FontWeight::Bold),
                ])->columns(4),
            InfoSection::make('1. Consumidor')
                ->schema([
                    TextEntry::make('consumidor')->label('Nombre')->weight(FontWeight::SemiBold),
                    TextEntry::make('numero_documento')->label('Documento')
                        ->formatStateUsing(fn (Reclamacion $record) => "{$record->tipo_documento} {$record->numero_documento}"),
                    TextEntry::make('telefono')->label('Teléfono')->icon('heroicon-m-phone'),
                    TextEntry::make('email')->label('Correo')->icon('heroicon-m-envelope')->copyable(),
                    TextEntry::make('direccion')->label('Domicilio')->columnSpan(2),
                    TextEntry::make('apoderado')->label('Padre, madre o tutor')->visible(fn (Reclamacion $record) => $record->menor_edad),
                ])->columns(3),
            InfoSection::make('2. Bien contratado')
                ->schema([
                    TextEntry::make('bien_tipo')->label('Tipo')->formatStateUsing(fn ($state) => ucfirst($state)),
                    TextEntry::make('monto')->formatStateUsing(fn ($state) => $state !== null ? 'S/ ' . number_format((float) $state, 2) : '—')->placeholder('—'),
                    TextEntry::make('pedido_referencia')->label('Boleta / pedido')->placeholder('—'),
                    TextEntry::make('bien_descripcion')->label('Descripción')->columnSpanFull(),
                ])->columns(3),
            InfoSection::make(fn (Reclamacion $record) => '3. ' . Reclamacion::TIPOS[$record->tipo])
                ->schema([
                    TextEntry::make('detalle')->columnSpanFull(),
                    TextEntry::make('pedido')->label('Pedido del consumidor')->columnSpanFull(),
                ]),
            InfoSection::make('4. Respuesta del proveedor')
                ->visible(fn (Reclamacion $record) => $record->atendido())
                ->schema([
                    TextEntry::make('respuesta')->columnSpanFull(),
                    TextEntry::make('respondido_at')->label('Fecha')->dateTime('d/m/Y H:i'),
                    TextEntry::make('respondidoPor.name')->label('Respondió')->placeholder('—'),
                ])->columns(2),
        ]);
    }

    public static function accionResponder(Tables\Actions\Action|PageActions\Action $action)
    {
        return $action
            ->label('Responder')
            ->icon('heroicon-o-chat-bubble-left-right')
            ->color('success')
            ->tooltip('Responder')
            ->visible(fn (Reclamacion $record) => ! $record->atendido() && Permisos::puede('reclamaciones', 'responder'))
            ->modalHeading(fn (Reclamacion $record) => "Responder hoja N° {$record->codigo}")
            ->modalDescription(fn (Reclamacion $record) => "Se enviará a {$record->email}. Plazo: " . self::plazo($record)[0] . '.')
            ->form([
                Forms\Components\Textarea::make('respuesta')
                    ->label('Respuesta al consumidor')
                    ->required()
                    ->minLength(20)
                    ->rows(7)
                    ->placeholder('Estimado(a) cliente: Lamentamos el inconveniente… Como solución…'),
            ])
            ->action(function (Reclamacion $record, array $data) {
                $record->update([
                    'estado'         => 'atendido',
                    'respuesta'      => $data['respuesta'],
                    'respondido_at'  => now(),
                    'respondido_por' => \Illuminate\Support\Facades\Auth::id(),
                ]);

                try {
                    $url = URL::signedRoute('reclamaciones.hoja', ['codigo' => $record->codigo]);
                    Mail::raw(
                        "Hola {$record->consumidor},\n\nRespuesta a tu hoja N° {$record->codigo}:\n\n{$data['respuesta']}\n\nVer la hoja: {$url}\n\n" . config('libreria.razon_social'),
                        fn ($m) => $m->to($record->email)->subject("Respuesta a tu reclamo N° {$record->codigo}")
                    );
                } catch (\Throwable $e) {
                    Log::warning('No se pudo enviar la respuesta del reclamo: ' . $e->getMessage());
                }

                Notification::make()->success()->title('Respuesta registrada')->body('El consumidor puede verla al consultar su hoja.')->send();
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReclamaciones::route('/'),
            'view'  => Pages\ViewReclamacion::route('/{record}'),
        ];
    }
}
