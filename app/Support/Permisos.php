<?php

namespace App\Support;

use App\Models\Rol;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Tabla de permisos del sistema: qué rol puede hacer qué en cada módulo.
 * El ADMINISTRADOR siempre puede todo (no hace falta ponerlo).
 */
class Permisos
{
    private const V = Rol::VENDEDOR;
    private const C = Rol::CAJERO;
    private const A = Rol::ALMACENERO;

    public const MAPA = [
        // módulo         acción      => roles permitidos
        // La vendedora y la cajera atienden la caja: abren, venden, cobran y cierran.
        // Solo ven las ventas DEL DÍA ('historial' = ver ventas de días anteriores: solo admin).
        'ventas'      => ['ver' => [self::V, self::C], 'crear' => [self::V, self::C], 'cobrar' => [self::V, self::C], 'anular' => []],
        'historial'   => ['ver' => []],
        'pagos'       => ['ver' => [self::V, self::C], 'crear' => [self::V, self::C], 'eliminar' => []],
        'cupones'     => ['ver' => [], 'crear' => [], 'editar' => [], 'eliminar' => []],
        'cierres'     => ['ver' => [self::V, self::C], 'crear' => [self::V, self::C]],

        // Inicio (dashboard): números del negocio solo para el admin; inventario también para el almacenero
        'dashboard_negocio'    => ['ver' => []],
        'dashboard_inventario' => ['ver' => [self::A]],
        'asistente'            => ['ver' => []],
        'actividades' => ['ver' => []],
        'clientes'    => ['ver' => [self::V, self::C], 'crear' => [self::V], 'editar' => [self::V], 'eliminar' => []],
        'productos'   => ['ver' => [self::V, self::C, self::A], 'crear' => [self::A], 'editar' => [self::A], 'eliminar' => [], 'reponer' => [self::A]],
        'movimientos' => ['ver' => [self::A]],
        'reclamaciones' => ['ver' => [self::V, self::C], 'responder' => []],
        'reposicion'  => ['ver' => [self::A]],
        'compras'     => ['ver' => [self::A], 'crear' => [self::A], 'anular' => []],
        'proveedores' => ['ver' => [self::A], 'crear' => [self::A], 'editar' => [self::A], 'eliminar' => []],
        'categorias'  => ['ver' => [self::V, self::C, self::A], 'crear' => [self::A], 'editar' => [self::A], 'eliminar' => []],
        'autores'     => ['ver' => [self::V, self::C, self::A], 'crear' => [self::A], 'editar' => [self::A], 'eliminar' => []],
        'empleados'   => ['ver' => []],
        'usuarios'    => ['ver' => []],
        'roles'       => ['ver' => []],
        'ajustes'     => ['ver' => []],
        'zonas'       => ['ver' => [], 'crear' => [], 'editar' => [], 'eliminar' => []],

        // Reportes
        'reporte_ventas'     => ['ver' => []],
        'reporte_clientes'   => ['ver' => []],
        'reporte_caja'       => ['ver' => []],
        'reporte_inventario' => ['ver' => [self::A]],
    ];

    /** ¿Este usuario atiende la caja? (debe abrirla al empezar el día) */
    public static function atiendeCaja(): bool
    {
        /** @var User|null $u */
        $u = Auth::user();

        return $u && ! $u->esAdmin() && self::puede('cierres', 'crear');
    }

    /**
     * Permisos::puede('ventas', 'crear')
     */
    public static function puede(string $modulo, string $accion = 'ver'): bool
    {
        /** @var User|null $usuario */
        $usuario = Auth::user();

        if (! $usuario) {
            return false;
        }

        if ($usuario->esAdmin()) {
            return true;
        }

        return in_array($usuario->rol?->nombre, self::MAPA[$modulo][$accion] ?? [], true);
    }
}