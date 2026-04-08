# Enviar correo

Carpeta para envío de correo de **comprobantes electrónicos** usando la misma configuración y librerías que `facturacion/LOGICA/fac_log_electronica.php` (líneas 158-199): servidor **exacontable.com**, SMTP con TLS, puerto 587.

**Ubicación:** `htdocs/enviar_correo/` (fuera de la carpeta facturacion).

## Archivos

- **config_correo.php** – Configuración SMTP (host, usuario, contraseña, puerto, etc.).
- **ClaseEnviarCorreo.php** – Clase que usa PHPMailer con la config de exacontable.
- **enviar_comprobante.php** – Punto de entrada y función `enviar_correo_comprobante()` para usar desde facturación.

## Uso desde facturación (LOGICA)

En `fac_log_electronica.php` o en cualquier script dentro de `facturacion/LOGICA/`:

```php
require_once __DIR__ . '/../../enviar_correo/enviar_comprobante.php';

$enviado = enviar_correo_comprobante(
    $correo,           // destinatario(s), separados por coma
    $datos['Destinatario'],
    $body,              // HTML del correo
    $datos['Emp_Nom'],  // nombre remitente
    [                  // adjuntos opcionales
        ['ruta' => $ruta_xml, 'nombre' => $claveAcceso . '.xml'],
        ['ruta' => $ruta_pdf, 'nombre' => $claveAcceso . '.pdf'],
    ]
);
```

## Seguridad

Para no dejar la contraseña SMTP en el repositorio, puede crear en esta misma carpeta un archivo **config_correo_local.php** (y añadirlo al .gitignore) con:

```php
<?php
define('SMTP_PASSWORD', 'su_password_real');
```

Incluya ese archivo antes de `config_correo.php` o ajuste `config_correo.php` para cargarlo si existe.
