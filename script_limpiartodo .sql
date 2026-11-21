-- =====================================================================
--  LIBRERÍA · ELIMINAR TODOS LOS REGISTROS (excepto el administrador)
--  Base de datos: libreria_filament
--
--  - Borra todos los datos y reinicia los ID (vuelven a empezar en 1).
--  - Se conserva: el usuario admin@libreria.pe (queda con ID 1),
--    los roles, la configuración de la tienda y la tabla migrations.
--  Cómo usar: phpMyAdmin > libreria_filament > SQL > pegar todo > Continuar
--  (funciona con la casilla de claves foráneas marcada o sin marcar)
-- =====================================================================

SET NAMES utf8mb4;

-- 1. Tablas de sistema
DELETE FROM `sessions`;
DELETE FROM `cache`;
DELETE FROM `cache_locks`;
DELETE FROM `password_reset_tokens`;
DELETE FROM `job_batches`;

-- 2. Datos del negocio (primero las tablas hijas)
DELETE FROM `actividades`;
DELETE FROM `notifications`;
DELETE FROM `jobs`;
DELETE FROM `failed_jobs`;
DELETE FROM `movimientos_inventario`;
DELETE FROM `reclamaciones`;
DELETE FROM `pagos`;
DELETE FROM `detalle_ventas`;
DELETE FROM `ventas`;
DELETE FROM `cierres_caja`;
DELETE FROM `detalle_compras`;
DELETE FROM `compras`;
DELETE FROM `productos`;
DELETE FROM `cupones`;
DELETE FROM `zonas_envio`;
DELETE FROM `proveedores`;
DELETE FROM `clientes`;
DELETE FROM `autores`;
DELETE FROM `categorias`;

-- 3. Usuarios: queda solo el administrador, con ID 1
UPDATE `users` SET `empleado_id` = NULL;
DELETE FROM `users` WHERE `email` <> 'admin@libreria.pe';
DELETE FROM `empleados`;
UPDATE `users` SET `id` = 1,
       `rol_id` = (SELECT id FROM roles WHERE nombre = 'administrador'),
       `password` = '$2y$12$8AfoaZ4Dub3MyB2S9i1RROtqLqfJGT0jxjEYyoRgcCXUdYqcgKUue',   -- 12345678
       `activo` = 1
WHERE `email` = 'admin@libreria.pe';

-- Si no existía el administrador, se crea (contraseña 12345678)
INSERT INTO `users` (`id`, `name`, `email`, `password`, `rol_id`, `empleado_id`, `activo`, `created_at`, `updated_at`)
SELECT 1, 'Administrador', 'admin@libreria.pe', '$2y$12$8AfoaZ4Dub3MyB2S9i1RROtqLqfJGT0jxjEYyoRgcCXUdYqcgKUue', (SELECT id FROM roles WHERE nombre = 'administrador'), NULL, 1, NOW(), NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `users` WHERE `email` = 'admin@libreria.pe');

-- 4. Reiniciar los ID (el próximo registro será el 1)
ALTER TABLE `actividades` AUTO_INCREMENT = 1;
ALTER TABLE `jobs` AUTO_INCREMENT = 1;
ALTER TABLE `failed_jobs` AUTO_INCREMENT = 1;
ALTER TABLE `movimientos_inventario` AUTO_INCREMENT = 1;
ALTER TABLE `reclamaciones` AUTO_INCREMENT = 1;
ALTER TABLE `pagos` AUTO_INCREMENT = 1;
ALTER TABLE `detalle_ventas` AUTO_INCREMENT = 1;
ALTER TABLE `ventas` AUTO_INCREMENT = 1;
ALTER TABLE `cierres_caja` AUTO_INCREMENT = 1;
ALTER TABLE `detalle_compras` AUTO_INCREMENT = 1;
ALTER TABLE `compras` AUTO_INCREMENT = 1;
ALTER TABLE `productos` AUTO_INCREMENT = 1;
ALTER TABLE `cupones` AUTO_INCREMENT = 1;
ALTER TABLE `zonas_envio` AUTO_INCREMENT = 1;
ALTER TABLE `proveedores` AUTO_INCREMENT = 1;
ALTER TABLE `clientes` AUTO_INCREMENT = 1;
ALTER TABLE `autores` AUTO_INCREMENT = 1;
ALTER TABLE `categorias` AUTO_INCREMENT = 1;
ALTER TABLE `empleados` AUTO_INCREMENT = 1;
ALTER TABLE `users` AUTO_INCREMENT = 2;

-- Listo: vuelve a iniciar sesión con admin@libreria.pe / 12345678
