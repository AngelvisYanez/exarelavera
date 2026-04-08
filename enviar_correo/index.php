<?php
/**
 * Formulario para enviar correo (comprobante electrónico u otro).
 * Usa SMTP exacontable (config_correo.php).
 */
session_start();
$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo       = isset($_POST['correo']) ? trim($_POST['correo']) : '';
    $destinatario = isset($_POST['destinatario']) ? trim($_POST['destinatario']) : 'Destinatario';
    $fromName     = isset($_POST['from_name']) ? trim($_POST['from_name']) : 'Facturación';
    $asunto       = isset($_POST['asunto']) ? trim($_POST['asunto']) : 'Comprobante Electrónico';
    $bodyHtml     = isset($_POST['mensaje']) ? $_POST['mensaje'] : '';

    if (empty($correo) || strlen($correo) < 4) {
        $mensaje = 'Ingrese al menos un correo destinatario válido.';
        $tipo_mensaje = 'error';
    } else {
        require_once __DIR__ . '/ClaseEnviarCorreo.php';
        $adjuntos = array();
        if (!empty($_FILES['adjuntos']['name'][0])) {
            $total = count($_FILES['adjuntos']['name']);
            for ($i = 0; $i < $total; $i++) {
                if ($_FILES['adjuntos']['error'][$i] === UPLOAD_ERR_OK) {
                    $tmp = $_FILES['adjuntos']['tmp_name'][$i];
                    $nom = $_FILES['adjuntos']['name'][$i];
                    $adjuntos[] = array('ruta' => $tmp, 'nombre' => $nom);
                }
            }
        }
        $envio = new ClaseEnviarCorreo();
        $ok = $envio->enviarComprobante($correo, $destinatario, $bodyHtml ?: '<p>Comprobante electrónico adjunto.</p>', $fromName, $adjuntos, $asunto);
        if ($ok) {
            $mensaje = 'Correo enviado correctamente.';
            $tipo_mensaje = 'ok';
        } else {
            $mensaje = 'No se pudo enviar: ' . ($envio->ultimoError ?: 'Error desconocido.');
            $tipo_mensaje = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enviar correo - Comprobante electrónico</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Segoe UI, Arial, sans-serif; margin: 20px; max-width: 600px; }
        h1 { color: #333; font-size: 1.4rem; }
        .form-group { margin-bottom: 14px; }
        label { display: block; margin-bottom: 4px; font-weight: 600; color: #444; }
        input[type="text"], input[type="email"], textarea { width: 100%; padding: 8px 10px; border: 1px solid #aaa; border-radius: 4px; }
        textarea { min-height: 120px; resize: vertical; }
        .hint { font-size: 0.85rem; color: #666; margin-top: 2px; }
        .btn { padding: 10px 20px; background: #2563eb; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-size: 1rem; }
        .btn:hover { background: #1d4ed8; }
        .msg { padding: 12px; border-radius: 6px; margin-bottom: 16px; }
        .msg.ok { background: #dcfce7; color: #166534; border: 1px solid #86efac; }
        .msg.error { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
    </style>
</head>
<body>
    <h1>Enviar correo</h1>
    <p class="hint">Comprobante electrónico u otro mensaje. SMTP: exacontable.com</p>

    <?php if ($mensaje !== ''): ?>
        <div class="msg <?php echo $tipo_mensaje === 'ok' ? 'ok' : 'error'; ?>"><?php echo htmlspecialchars($mensaje); ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" action="">
        <div class="form-group">
            <label for="correo">Correo destinatario(s) *</label>
            <input type="text" id="correo" name="correo" value="<?php echo isset($_POST['correo']) ? htmlspecialchars($_POST['correo']) : ''; ?>" placeholder="ejemplo@correo.com, otro@correo.com" required>
            <span class="hint">Varios correos separados por coma.</span>
        </div>
        <div class="form-group">
            <label for="destinatario">Nombre del destinatario</label>
            <input type="text" id="destinatario" name="destinatario" value="<?php echo isset($_POST['destinatario']) ? htmlspecialchars($_POST['destinatario']) : ''; ?>" placeholder="Razón social o nombre">
        </div>
        <div class="form-group">
            <label for="from_name">Nombre del remitente</label>
            <input type="text" id="from_name" name="from_name" value="<?php echo isset($_POST['from_name']) ? htmlspecialchars($_POST['from_name']) : 'Facturación'; ?>" placeholder="Ej. Nombre de la empresa">
        </div>
        <div class="form-group">
            <label for="asunto">Asunto</label>
            <input type="text" id="asunto" name="asunto" value="<?php echo isset($_POST['asunto']) ? htmlspecialchars($_POST['asunto']) : 'Comprobante Electrónico'; ?>" placeholder="Comprobante Electrónico">
        </div>
        <div class="form-group">
            <label for="mensaje">Mensaje (HTML)</label>
            <textarea id="mensaje" name="mensaje" placeholder="Cuerpo del correo en HTML. Si lo deja vacío se usará un texto por defecto."><?php echo isset($_POST['mensaje']) ? htmlspecialchars($_POST['mensaje']) : ''; ?></textarea>
        </div>
        <div class="form-group">
            <label for="adjuntos">Adjuntos</label>
            <input type="file" id="adjuntos" name="adjuntos[]" multiple accept=".pdf,.xml,.zip">
            <span class="hint">Opcional. PDF, XML, etc. Múltiples archivos permitidos.</span>
        </div>
        <div class="form-group">
            <button type="submit" class="btn">Enviar correo</button>
        </div>
    </form>
</body>
</html>
