<?php
/* 
 * Script para corregir la secuencia de Com_Num en la tabla comprobantes
 * Versión 2.1 - Bypass de error de sesión y selector manual
 */

// Forzar codificación UTF-8 para evitar caracteres extraños
header('Content-Type: text/html; charset=utf-8');

// Requerir archivos de configuración y conexión
require_once('../../administrador/LOGICA/seguridad.php');
require_once('log_man_ant_1.0.php');

$obBD_conexion = new Class_Log_Conexion_Manifiesto($Ses_Dat_Dis);
$obBD_con1 = new Class_Log_Datos_Manifiesto;

// Obtener parámetros del formulario
$tipo_abr = isset($_POST['tipo_abr']) ? $_POST['tipo_abr'] : 'IN';
$pec_cod = isset($_POST['pec_cod']) ? $_POST['pec_cod'] : (isset($_SESSION['Ses_Pec_Cod']) ? $_SESSION['Ses_Pec_Cod'] : '');
$ejecutar = isset($_POST['ejecutar']) && $_POST['ejecutar'] == 'S';

// 1. Obtener todos los periodos activos para el selector
$emp_cod = isset($_SESSION['Ses_Emp_Cod']) ? $_SESSION['Ses_Emp_Cod'] : '';
$where_emp = $emp_cod != '' ? " INNER JOIN plan_cuenta pc ON perio_cont.Pla_Cod = pc.Pla_Cod WHERE pc.Emp_Cod = '$emp_cod' AND perio_cont.Pec_Est = 'A' " : " WHERE Pec_Est = 'A' ";

$sqlPeriodos = "SELECT Pec_Cod, YEAR(Pec_Fei) as Anio, pc.Emp_Cod FROM perio_cont $where_emp ORDER BY Pec_Fei DESC";
$resPeriodos = $obBD_con1->consulta($sqlPeriodos, $obBD_conexion->conexion);
$periodos = array();
if ($resPeriodos) {
    while($p = $obBD_con1->fetch_assoc($resPeriodos)) { $periodos[] = $p; }
}

// 2. Obtener tipos de asiento disponibles
$sqlTipos = "SELECT Tia_Abr, Tia_Des FROM tipo_asien WHERE Tia_Est = 'A' ORDER BY Tia_Des";
$resTipos = $obBD_con1->consulta($sqlTipos, $obBD_conexion->conexion);
$tipos = array();
if ($resTipos) {
    while($t = $obBD_con1->fetch_assoc($resTipos)) { $tipos[] = $t; }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Corrección de Secuencia Contable</title>
    <style>
        body { font-family: sans-serif; margin: 20px; line-height: 1.5; }
        .error { color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 4px; margin-bottom: 20px; }
        .warning { color: #856404; background-color: #fff3cd; border: 1px solid #ffeeba; padding: 15px; border-radius: 4px; margin-bottom: 20px; }
        .success { color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 4px; margin-bottom: 20px; }
        table { border-collapse: collapse; width: 100%; font-size: 12px; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        tr:hover { background-color: #f1f1f1; }
        .btn { padding: 10px 20px; cursor: pointer; border-radius: 4px; border: none; font-weight: bold; }
        .btn-primary { background: #007bff; color: white; }
        .btn-success { background: #28a745; color: white; }
        .header-box { background: #eee; padding: 15px; border-radius: 4px; margin-bottom: 20px; }
        select { padding: 5px; border-radius: 4px; }
    </style>
</head>
<body>

<h2>Mantenimiento: Corrección de Secuencia de Comprobantes</h2>

<?php if (empty($pec_cod)): ?>
    <div class="warning">
        <strong>Aviso:</strong> No se detectó un periodo contable en su sesión. Por favor, <b>seleccione uno manualmente</b> en el menú de abajo para continuar.
    </div>
<?php endif; ?>

<div class="header-box">
    <form method="POST">
        <label>Tipo de Comprobante:</label>
        <select name="tipo_abr">
            <?php foreach($tipos as $t): ?>
                <option value="<?php echo $t['Tia_Abr']; ?>" <?php echo $tipo_abr == $t['Tia_Abr'] ? 'selected' : ''; ?>>
                    <?php echo $t['Tia_Abr'] . " - " . $t['Tia_Des']; ?>
                </option>
            <?php endforeach; ?>
        </select>
        
        <label style="margin-left: 20px;">Periodo Contable:</label>
        <select name="pec_cod" required>
            <option value="">-- Seleccione un Periodo --</option>
            <?php foreach($periodos as $p): ?>
                <option value="<?php echo $p['Pec_Cod']; ?>" <?php echo $pec_cod == $p['Pec_Cod'] ? 'selected' : ''; ?>>
                    Periodo <?php echo $p['Anio']; ?> (ID: <?php echo $p['Pec_Cod']; ?>)
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit" class="btn btn-primary" style="margin-left: 20px;">Cargar / Vista Previa</button>
        
        <?php if($pec_cod && !$ejecutar): ?>
            <input type="hidden" name="ejecutar" value="S">
            <button type="submit" class="btn btn-success" style="margin-left: 10px;" onclick="return confirm('¿Está seguro de aplicar los cambios? Se recomienda tener un respaldo de la base de datos.')">Aplicar Cambios Reales</button>
        <?php endif; ?>
    </form>
</div>

<?php
if ($pec_cod) {
    // Obtener Tia_Cod
    $sqlTia = "SELECT Tia_Cod, Tia_Des FROM tipo_asien WHERE Tia_Abr = '$tipo_abr' AND Tia_Est = 'A' LIMIT 1";
    $resTia = $obBD_con1->consulta($sqlTia, $obBD_conexion->conexion);
    $rowTia = $obBD_con1->fetch_assoc($resTia);
    
    if ($rowTia) {
        $tia_cod = $rowTia['Tia_Cod'];
        echo "<h3>Resultados para: " . $rowTia['Tia_Des'] . " ($tipo_abr)</h3>";

        // Obtener comprobantes
        $sqlCom = "SELECT Com_Cod, Com_Num, Com_Fec, Com_Con, Com_Est
                   FROM comprobantes 
                   WHERE Tia_Cod = '$tia_cod' AND Pec_Cod = '$pec_cod'
                   ORDER BY Com_Fec ASC, Com_Cod ASC";

        $resCom = $obBD_con1->consulta($sqlCom, $obBD_conexion->conexion);

        $total = 0;
        $corregidos = 0;
        $count = 1;

        if ($resCom) {
            echo "<table>";
            echo "<tr><th>ID (Com_Cod)</th><th>Fecha</th><th>Concepto</th><th>Estado</th><th>Núm. Actual</th><th>Núm. Sugerido</th><th>Acción</th></tr>";

            while ($row = $obBD_con1->fetch_assoc($resCom)) {
                $total++;
                $com_cod = $row['Com_Cod'];
                $old_num = $row['Com_Num'];
                $color_est = $row['Com_Est'] == 'A' ? '' : 'color: red;';
                
                $accion = "Correcto";
                $bg = "";

                if ($old_num != $count) {
                    if ($ejecutar) {
                        $sqlUpdate = "UPDATE comprobantes SET Com_Num = $count WHERE Com_Cod = $com_cod";
                        if ($obBD_con1->consulta($sqlUpdate, $obBD_conexion->conexion)) {
                            $accion = "<span style='color: green; font-weight: bold;'>¡ACTUALIZADO!</span>";
                            $corregidos++;
                        } else {
                            $accion = "<span style='color: red;'>ERROR</span>";
                        }
                    } else {
                        $accion = "<span style='color: orange; font-weight: bold;'>Requiere Corrección</span>";
                        $corregidos++;
                    }
                    $bg = "background: #fff9e6;";
                }

                echo "<tr style='$bg $color_est'>";
                echo "<td>$com_cod</td><td>{$row['Com_Fec']}</td><td>" . mb_convert_encoding($row['Com_Con'], 'UTF-8', 'ISO-8859-1') . "</td><td>{$row['Com_Est']}</td><td>$old_num</td><td><b>$count</b></td><td>$accion</td>";
                echo "</tr>";
                
                $count++;
            }
            echo "</table>";

            echo "<div class='success' style='margin-top: 20px;'>";
            echo "<strong>Resumen:</strong> " . ($ejecutar ? "Se actualizaron " : "Se detectaron ") . " <b>$corregidos</b> registros de un total de <b>$total</b>.";
            echo "</div>";
        } else {
            echo "<div class='error'>No se encontraron comprobantes para los criterios seleccionados.</div>";
        }
    } else {
        echo "<div class='error'>Error: El tipo de comprobante seleccionado no es válido.</div>";
    }
}
?>
</body>
</html>
