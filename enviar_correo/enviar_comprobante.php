<?php
/**
 * Punto de entrada para envío de comprobante por correo.
 * Usa la misma lógica y librerías que fac_log_electronica.php (config exacontable, líneas 158-199).
 *
 * Uso desde facturación (carpeta facturacion/LOGICA):
 *   require_once __DIR__ . '/../../enviar_correo/enviar_comprobante.php';
 *   $enviado = enviar_correo_comprobante($correo, $destinatario, $bodyHtml, $fromName, $adjuntos);
 *
 * O usando la clase directamente:
 *   require_once __DIR__ . '/../../enviar_correo/ClaseEnviarCorreo.php';
 *   $obj = new ClaseEnviarCorreo();
 *   $enviado = $obj->enviarComprobante($correo, $destinatario, $bodyHtml, $fromName, $adjuntos);
 */

require_once __DIR__ . '/ClaseEnviarCorreo.php';

/**
 * Envía un correo de comprobante electrónico (SMTP exacontable).
 *
 * @param string      $correo       Destinatario(s), separados por coma
 * @param string      $destinatario  Nombre del destinatario
 * @param string      $bodyHtml      Cuerpo del correo en HTML
 * @param string      $fromName      Nombre del remitente (ej. Emp_Nom)
 * @param array       $adjuntos      Opcional. Array de ['ruta' => path, 'nombre' => nombre]
 * @param string|null $asunto        Opcional. Asunto del correo
 * @return bool true si se envió correctamente
 */
function enviar_correo_comprobante($correo, $destinatario, $bodyHtml, $fromName, $adjuntos = array(), $asunto = null)
{
    $envio = new ClaseEnviarCorreo();
    return $envio->enviarComprobante($correo, $destinatario, $bodyHtml, $fromName, $adjuntos, $asunto);
}
