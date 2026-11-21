<?php

namespace App\Support;

/**
 * Formatos de texto compartidos por la tienda, el panel y los CHECK de la base de datos.
 * (public/js/validaciones.js usa las mismas expresiones para validar en vivo.)
 */
class Reglas
{
    /** Correo con dominio y extensión: nombre@gmail.com (no acepta "a@b") */
    public const EMAIL = '/^[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}$/';

    /** Nombres y apellidos: solo letras, espacios, apóstrofo, punto y guion */
    public const NOMBRE = "/^[\pL][\pL' .\-]{1,99}$/u";

    /** Razón social: letras, números y signos comunes */
    public const RAZON = "/^[\pL0-9][\pL0-9&'., ()\-]{2,149}$/u";

    public const MSG_EMAIL  = 'Escribe un correo válido, por ejemplo nombre@gmail.com.';
    public const MSG_NOMBRE = 'Solo letras y espacios (mínimo 2 letras).';
    public const MSG_RAZON  = 'Escribe la razón social (mínimo 3 caracteres).';
}
