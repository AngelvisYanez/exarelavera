<?php
/**
 * Configuración SMTP para envío de correo (Comprobantes Electrónicos).
 * Basado en fac_log_electronica.php líneas 158-199 - servidor exacontable.com
 * 
 * IMPORTANTE: No subir la contraseña real al repositorio. Usar variable de entorno
 * o definir SMTP_PASSWORD en un archivo fuera del repo (ej. config_correo_local.php).
 */

if (!defined('ENVIAR_CORREO_CONFIG')) {
    define('ENVIAR_CORREO_CONFIG', true);
    /** Servidor SMTP (exacontable.com) */
    define('SMTP_HOST', 'smtp.gmail.com');
    /** Autenticación SMTP */
    define('SMTP_AUTH', true);
    /** Usuario / correo remitente */
    define('SMTP_USERNAME', 'exa.facturacion@gmail.com');
    /**
     * Contraseña SMTP.
     * Para mayor seguridad, cree un archivo config_correo_local.php en esta carpeta
     * con: define('SMTP_PASSWORD', 'su_password');
     */
    if (!defined('SMTP_PASSWORD')) {
        define('SMTP_PASSWORD', 'owdjkcjdxvftwbxg');
    }
    /** Seguridad: tls (recomendado para puerto 587) */
    define('SMTP_SECURE', 'tls');
    /** Puerto SMTP */
    define('SMTP_PORT', 587);
    /** Timeout en segundos */
    define('SMTP_TIMEOUT', 10);
    /** Mantener conexión viva */
    define('SMTP_KEEP_ALIVE', true);
    /** Correo remitente (debe coincidir con usuario) */
    define('SMTP_FROM', 'exa.facturacion@gmail.com');
    /** Sender (evita spam y retrasos) */
    define('SMTP_SENDER', 'exa.facturacion@gmail.com');
    /** Asunto por defecto */
    define('SMTP_SUBJECT_DEFAULT', 'Comprobante Electrónico');
}
