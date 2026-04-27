<?php
/**
 * Config SMTP exclusivo para el módulo de notificaciones de Relavera.
 * Edite estos valores según el servidor de correo que usará SOLO este módulo.
 */

// Habilitar/Deshabilitar el envío por SMTP de Relavera
define('RELAVERA_NOTIF_SMTP_ENABLED', true);

// Servidor SMTP de Gmail
define('RELAVERA_NOTIF_SMTP_HOST', 'smtp.gmail.com');
define('RELAVERA_NOTIF_SMTP_PORT', 587);
define('RELAVERA_NOTIF_SMTP_SECURE', 'tls');
define('RELAVERA_NOTIF_SMTP_AUTH', true);

// Usuario y contraseña
define('RELAVERA_NOTIF_SMTP_USERNAME', 'ecoparkmining.relavera@gmail.com');
define('RELAVERA_NOTIF_SMTP_PASSWORD', 'huftvjvoplddwhgl'); // sin espacios

define('RELAVERA_NOTIF_SMTP_TIMEOUT', 30);

// Remitente
define('RELAVERA_NOTIF_SMTP_FROM', 'ecoparkmining.relavera@gmail.com');
define('RELAVERA_NOTIF_SMTP_SENDER', 'ecoparkmining.relavera@gmail.com');
define('RELAVERA_NOTIF_SMTP_FROMNAME', 'Relavera');

// Asunto
define('RELAVERA_NOTIF_SMTP_SUBJECT_DEFAULT', 'Notificación Relavera');

//dtzx lwlm igxi qnvb