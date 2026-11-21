<?php

/*
|--------------------------------------------------------------------------
| Datos de la librería (aparecen en el comprobante y en la página de inicio)
|--------------------------------------------------------------------------
| Puedes cambiarlos aquí o en el archivo .env
*/

return [
    'razon_social' => env('LIBRERIA_RAZON_SOCIAL', 'Librería y Útiles S.A.C.'),
    'ruc'          => env('LIBRERIA_RUC', '20123456789'),
    'direccion'    => env('LIBRERIA_DIRECCION', 'Av. Alfredo Mendiola 3540, Independencia - Lima'),
    'telefono'     => env('LIBRERIA_TELEFONO', '(01) 555-0123'),
    'email'        => env('LIBRERIA_EMAIL', 'ventas@libreria.pe'),
    'whatsapp'     => env('LIBRERIA_WHATSAPP', '51987654321'),   // con código de país, sin + ni espacios
    'igv'          => 0.18,   // los precios YA incluyen IGV

    // Pago con Yape / Plin en la tienda online.
    // Si pones tu QR en public/img/yape-qr.png se mostrará en el checkout.
    'pago_movil' => [
        'numero'  => env('LIBRERIA_YAPE_NUMERO', '987 654 321'),
        'titular' => env('LIBRERIA_YAPE_TITULAR', 'Librería y Útiles S.A.C.'),
        'qr'      => 'img/yape-qr.png',
    ],

    // Horario de recojo que se muestra al cliente
    'horario' => env('LIBRERIA_HORARIO', 'Lunes a sábado de 9:00 a. m. a 8:00 p. m.'),

    // Asistente con IA (Google Gemini). La clave va en .env, nunca en el código.
       // ... lo que ya tenías ...

    'ia' => [
        'key'    => env('GEMINI_API_KEY'),
        'modelo' => env('GEMINI_MODEL', 'gemini-flash-latest'),
    ],
];