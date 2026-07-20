<?php
/**
 * Gestión Operativa del Despacho - Precios por Actividad y Tipo de Empresa
 * Pequeño, Mediano, Grande
 * @author Sistema EXA | @version 1.0
 */
if (!empty($_GET['debug'])) { ini_set('display_errors', 1); error_reporting(E_ALL); }
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/aud_log_despacho_1.0.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

$obBD_conexion = new Class_Log_Conexion_Despacho($Ses_Dat_Dis);
$obBD_con1 = new Class_Log_Datos_Despacho();
$Ses_Emp_Cod = isset($Ses_Emp_Cod) ? intval($Ses_Emp_Cod) : 0;

// Ajax: Listar actividades con precios
if (!empty($_REQUEST['listarActividadesPrecios'])) {
    $arr = $obBD_con1->getArrayConsulta(67, array('Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion);
    utf8_encode_deep($arr);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(array('rows' => $arr));
    exit;
}

// Ajax: Guardar precios (los 3 a la vez por actividad)
if (!empty($_REQUEST['guardarPreciosActividad'])) {
    header('Content-Type: application/json; charset=UTF-8');
    $resp = array('success' => false);
    $act = intval(isset($_POST['Act_Cod']) ? $_POST['Act_Cod'] : 0);
    $peq = floatval(str_replace(',', '.', isset($_POST['Precio_Pequeno']) ? $_POST['Precio_Pequeno'] : 0));
    $med = floatval(str_replace(',', '.', isset($_POST['Precio_Mediano']) ? $_POST['Precio_Mediano'] : 0));
    $gra = floatval(str_replace(',', '.', isset($_POST['Precio_Grande']) ? $_POST['Precio_Grande'] : 0));
    if ($act <= 0) {
        $resp['message'] = 'Actividad inválida.';
        echo json_encode($resp);
        exit;
    }
    $obBD_con1->setError(0, '');
    $obBD_con1->operacionobBD(68, array('Act_Cod' => $act, 'Tipo_Empresa' => 'PEQUENO', 'Precio' => $peq), $obBD_conexion);
    if ($obBD_con1->Error != 0) {
        $resp['message'] = $obBD_con1->getMsgError() ?: 'Error al guardar precio Pequeño.';
        echo json_encode($resp);
        exit;
    }
    $obBD_con1->operacionobBD(68, array('Act_Cod' => $act, 'Tipo_Empresa' => 'MEDIANO', 'Precio' => $med), $obBD_conexion);
    if ($obBD_con1->Error != 0) {
        $resp['message'] = $obBD_con1->getMsgError() ?: 'Error al guardar precio Mediano.';
        echo json_encode($resp);
        exit;
    }
    $obBD_con1->operacionobBD(68, array('Act_Cod' => $act, 'Tipo_Empresa' => 'GRANDE', 'Precio' => $gra), $obBD_conexion);
    if ($obBD_con1->Error != 0) {
        $resp['message'] = $obBD_con1->getMsgError() ?: 'Error al guardar precio Grande.';
        echo json_encode($resp);
        exit;
    }
    $resp['success'] = true;
    $resp['message'] = 'Precios guardados correctamente.';
    echo json_encode($resp);
    exit;
}

$lista_actividades_precios = array();
try {
    $lista_actividades_precios = $obBD_con1->getArrayConsulta(67, array('Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion);
} catch (Exception $e) {
    // Tabla aud_actividad_precios puede no existir aún
}
$embed = !empty($_GET['embed']);
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo isset($Ses_Sys_Nom) ? $Ses_Sys_Nom : 'EXA'; ?> - Precios por Actividad</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <?php require_once('../../mascaras/model1/estilos/estilos.php'); ?>
    <link href="aud_zoom.css" rel="stylesheet" type="text/css">
    <script type="text/javascript" src="../../Librerias/jquery.min/jquery-1.11.3.min.js"></script>
    <script type="text/javascript" src="../../mascaras/model1/js/bootstrap.min.js"></script>
    <style>
        .despacho-precios-container { padding: 20px; background: #E8F0F7; min-height: 100vh; }
        .exa-header {
            background: linear-gradient(135deg, #2C5D94 0%, #3d7bb8 50%, #5A9BD4 100%);
            color: white;
            padding: 10px 20px;
            border-radius: 10px;
            box-shadow: 0 4px 14px rgba(15,118,110,0.3);
            margin-bottom: 20px;
        }
        .exa-header h3 { margin: 0; font-size: 18px; font-weight: 600; }
        .config-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08), 0 4px 12px rgba(0,0,0,0.04);
        }
        .config-header {
            background: linear-gradient(135deg, #2C5D94 0%, #3d7bb8 100%);
            color: white;
            padding: 6px 14px;
            border-radius: 10px 10px 0 0;
            margin: -20px -20px 20px -20px;
            font-size: 14px;
        }
        .config-header h4 { margin: 0; font-size: 14px; font-weight: 600; }
        .aud-tabla { width: 100%; border-collapse: collapse; font-size: 13px; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.06); background: white; }
        .aud-tabla thead th {
            background: linear-gradient(135deg, #72A1CF 0%, #8EB7DD 100%);
            color: white;
            padding: 10px 14px;
            text-align: left;
            font-weight: 600;
            font-size: 12px;
        }
        .aud-tabla thead th.col-precio { text-align: right; width: 120px; }
        .aud-tabla tbody td { padding: 8px 14px; border-bottom: 1px solid #e2e8f0; }
        .aud-tabla tbody tr:hover { background-color: #D1E6F4; }
        .aud-tabla input.precio-input {
            width: 100px;
            text-align: right;
            padding: 6px 10px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 13px;
        }
        .aud-tabla input.precio-input:focus {
            border-color: #2C5D94;
            outline: none;
            box-shadow: 0 0 0 2px rgba(44,93,148,0.2);
        }
        .btn-guardar-precios {
            padding: 4px 12px;
            font-size: 12px;
            border-radius: 6px;
            background: linear-gradient(135deg, #5CB85C 0%, #4cae4c 100%);
            color: white;
            border: none;
            cursor: pointer;
        }
        .btn-guardar-precios:hover { opacity: 0.9; }
        .ser-grupo { background: #f8fafc; font-weight: 600; color: #2C5D94; }
        .nav-links { margin-bottom: 16px; }
        .nav-links a { color: #2C5D94; text-decoration: none; margin-right: 16px; }
        .nav-links a:hover { text-decoration: underline; }
        body.embed-precios .despacho-precios-container { padding: 10px 0; }
    </style>
</head>
<body<?php if ($embed) echo ' class="embed-precios"'; ?>>
<div id="set1" class="container-fluid despacho-precios-container">
    <?php if (!$embed): ?>
    <div class="exa-header">
        <h3><span class="glyphicon glyphicon-usd"></span> Precios por Actividad - Tipo de Empresa</h3>
    </div>

    <div class="nav-links">
        <a href="aud_mod_despacho_admin_1.0.php"><span class="glyphicon glyphicon-cog"></span> Administración</a>
        <a href="aud_mod_despacho_contratos_1.0.php"><span class="glyphicon glyphicon-file"></span> Contratos</a>
    </div>
    <?php endif; ?>

    <div class="config-card">
        <div class="config-header"><h4><span class="glyphicon glyphicon-list"></span> Todas las actividades - Precios Pequeño / Mediano / Grande</h4></div>
        <p class="text-muted" style="margin-bottom: 16px; padding: 12px 16px; background: #DEE7EF; border-radius: 8px; border-left: 4px solid #2C5D94;">
            <span class="glyphicon glyphicon-info-sign"></span> Defina el precio de cada actividad según el tipo de empresa del cliente (Pequeño, Mediano, Grande). Asigne el tipo de empresa a cada cliente en <strong>Administración > Clientes del Despacho</strong>.
        </p>
        <table id="gridPrecios" class="aud-tabla">
            <thead>
                <tr>
                    <th>Servicio</th>
                    <th>Actividad</th>
                    <th>Tipo</th>
                    <th class="col-precio">Precio Pequeño</th>
                    <th class="col-precio">Precio Mediano</th>
                    <th class="col-precio">Precio Grande</th>
                    <th style="width: 90px; text-align: center;">Guardar</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $ser_ant = '';
            foreach ($lista_actividades_precios as $r):
                $ser = isset($r['Ser_Nombre']) ? $r['Ser_Nombre'] : '';
                $ser_cls = ($ser !== $ser_ant) ? ' ser-grupo' : '';
                $ser_ant = $ser;
                $peq = isset($r['Precio_Pequeno']) ? number_format((float)$r['Precio_Pequeno'], 2, '.', '') : '0.00';
                $med = isset($r['Precio_Mediano']) ? number_format((float)$r['Precio_Mediano'], 2, '.', '') : '0.00';
                $gra = isset($r['Precio_Grande']) ? number_format((float)$r['Precio_Grande'], 2, '.', '') : '0.00';
            ?>
            <tr data-act="<?php echo (int)$r['Act_Cod']; ?>">
                <td class="<?php echo $ser_cls; ?>"><?php echo htmlspecialchars($ser); ?></td>
                <td><?php echo htmlspecialchars($r['Act_Nombre']); ?></td>
                <td><?php echo htmlspecialchars($r['Act_Tipo']); ?></td>
                <td class="col-precio"><input type="text" class="precio-input precio-pequeno" value="<?php echo $peq; ?>" data-act="<?php echo (int)$r['Act_Cod']; ?>" /></td>
                <td class="col-precio"><input type="text" class="precio-input precio-mediano" value="<?php echo $med; ?>" data-act="<?php echo (int)$r['Act_Cod']; ?>" /></td>
                <td class="col-precio"><input type="text" class="precio-input precio-grande" value="<?php echo $gra; ?>" data-act="<?php echo (int)$r['Act_Cod']; ?>" /></td>
                <td style="text-align: center;"><button type="button" class="btn-guardar-precios btn-guardar-fila" data-act="<?php echo (int)$r['Act_Cod']; ?>"><span class="glyphicon glyphicon-floppy-disk"></span></button></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($lista_actividades_precios)): ?>
            <tr><td colspan="7" style="padding: 24px; text-align: center; color: #64748b;">No hay actividades. Ejecute los scripts SQL de migración (aud_sql_regimen_actividades.sql y aud_sql_regimen_general_precios.sql).</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script type="text/javascript">
$(function () {
    var urlBase = <?php echo json_encode($_SERVER['PHP_SELF']); ?>;

    $(document).on('click', '.btn-guardar-fila', function () {
        var act = $(this).data('act');
        public $row = $(this).closest('tr');
        var peq = parseFloat($row.find('.precio-pequeno').val().replace(',', '.')) || 0;
        var med = parseFloat($row.find('.precio-mediano').val().replace(',', '.')) || 0;
        var gra = parseFloat($row.find('.precio-grande').val().replace(',', '.')) || 0;
        public $btn = $(this);
        $btn.prop('disabled', true);
        $.post(urlBase, {
            guardarPreciosActividad: 1,
            Act_Cod: act,
            Precio_Pequeno: peq,
            Precio_Mediano: med,
            Precio_Grande: gra
        }, function (r) {
            if (r && r.success) {
                $row.css('background', '#ecfdf5');
                setTimeout(function () { $row.css('background', ''); }, 800);
            } else {
                alert(r && r.message ? r.message : 'Error al guardar.');
            }
        }, 'json').fail(function () {
            alert('Error de conexión.');
        }).always(function () {
            $btn.prop('disabled', false);
        });
    });
});
</script>
</body>
</html>
