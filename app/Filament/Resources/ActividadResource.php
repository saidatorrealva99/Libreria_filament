<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActividadResource\Pages;
use App\Models\Actividad;
use App\Support\TienePermisos;
use Filament\Forms;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Bitácora de actividad (solo lectura, solo administrador).
 */
class ActividadResource extends Resource
{
    use TienePermisos;
    protected static string $modulo = 'actividades';

    protected static ?string $model = Actividad::class;

    protected static ?string $slug = 'bitacora';
    protected static ?string $modelLabel = 'actividad';
    protected static ?string $pluralModelLabel = 'Bitácora de actividad';
    protected static ?string $navigationLabel = 'Bitácora';
    protected static ?string $navigationGroup = 'Configuración';
    protected static ?string $navigationIcon = 'heroicon-o-finger-print';
    protected static ?int $navigationSort = 9;

    public static function canCreate(): bool { return false; }
    public static function canEdit(Model $record): bool { return false; }
    public static function canDelete(Model $record): bool { return false; }
    public static function canDeleteAny(): bool { return false; }

    public static function table(Table $table): Table
    {
        return $table
            ->query(Actividad::query()->with('usuario'))   // consulta explícita de la tabla
            ->defaultSort('created_at', 'desc')
            ->poll('30s')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Cuándo')
                    ->since()
                    ->description(fn (Actividad $record) => $record->created_at->format('d/m/Y H:i:s'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('usuario.name')
                    ->label('Quién')
                    ->default(fn (Actividad $record) => $record->modulo === 'Sesión' ? '—' : 'Cliente web / sistema')
                    ->weight(FontWeight::SemiBold)
                    ->icon('heroicon-m-user-circle'),
                Tables\Columns\TextColumn::make('accion')
                    ->label('Acción')
                    ->badge()
                    ->color(fn (string $state) => Actividad::COLORES[$state] ?? 'gray'),
                Tables\Columns\TextColumn::make('descripcion')
                    ->label('Detalle')
                    ->wrap()
                    ->searchable(),
                Tables\Columns\TextColumn::make('modulo')->label('Módulo')->badge()->color('gray')->toggleable(),
                Tables\Columns\TextColumn::make('ip')->label('IP')->fontFamily('mono')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user_id')->label('Usuario')
                    ->options(fn () => \App\Models\User::orderBy('name')->pluck('name', 'id')->all()),
                Tables\Filters\SelectFilter::make('accion')->label('Acción')
                    ->options(array_combine(array_keys(Actividad::COLORES), array_map('ucfirst', array_keys(Actividad::COLORES)))),
                Tables\Filters\SelectFilter::make('modulo')->label('Módulo')
                    ->options(fn () => Actividad::select('modulo')->distinct()->orderBy('modulo')->pluck('modulo', 'modulo')->all()),
                Tables\Filters\Filter::make('fecha')
                    ->form([
                        Forms\Components\DatePicker::make('desde')->native(false)->displayFormat('d/m/Y'),
                        Forms\Components\DatePicker::make('hasta')->native(false)->displayFormat('d/m/Y'),
                    ])
                    ->query(fn (Builder $q, array $data) => $q
                        ->when($data['desde'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
                        ->when($data['hasta'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '<=', $d))),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->iconButton()->color('info')
                    ->visible(fn (Actividad $record) => ! empty($record->cambios)),
            ])
            ->emptyStateIcon('heroicon-o-finger-print')
            ->emptyStateHeading('Sin actividad registrada');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            InfoSection::make()->schema([
                TextEntry::make('usuario.name')->label('Quién')->placeholder('Cliente web / sistema'),
                TextEntry::make('accion')->label('Acción')->badge()->color(fn (string $state) => Actividad::COLORES[$state] ?? 'gray'),
                TextEntry::make('created_at')->label('Cuándo')->dateTime('d/m/Y H:i:s'),
                TextEntry::make('descripcion')->label('Detalle')->columnSpanFull(),
            ])->columns(3),
            InfoSection::make('Cambios (antes → después)')
                ->schema([
                    KeyValueEntry::make('cambios')
                        ->hiddenLabel()
                        ->keyLabel('Campo')
                        ->valueLabel('Antes → después')
                        ->state(fn (Actividad $record) => collect($record->cambios ?? [])
                            ->map(fn ($v) => is_array($v) && array_is_list($v)
                                ? self::texto($v[0] ?? null) . '  →  ' . self::texto($v[1] ?? null)
                                : self::texto($v))
                            ->all()),
                ]),
        ]);
    }

    private static function texto(mixed $valor): string
    {
        return match (true) {
            $valor === null, $valor === '' => '∅',
            is_bool($valor)                => $valor ? 'sí' : 'no',
            is_array($valor)               => json_encode($valor, JSON_UNESCAPED_UNICODE),
            default                        => (string) $valor,
        };
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActividades::route('/'),
        ];
    }
}
