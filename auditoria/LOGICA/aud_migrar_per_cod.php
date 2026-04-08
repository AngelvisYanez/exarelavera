<?php
/**
 * Migración: Añadir Per_Cod a aud_tareas_asignadas
 * Ejecutar UNA VEZ desde: localhost/auditoria/LOGICA/aud_migrar_per_cod.php
 * Luego eliminar o no volver a ejecutar.
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../../DATA/MysqlConexion.php');
require_once('../LOGICA/aud_log_auditoria.php');

header('Content-Type: text/html; charset=utf-8');
echo "<h2>Migración Per_Cod - aud_tareas_asignadas</h2>";

$obBD = new Class_Log_Conexion_Global($Ses_Dat_Dis);
$conn = $obBD->conexion;

if (!$conn) {
    echo "<p style='color:red'>Error de conexión a la base de datos.</p>";
    exit;
}

// Verificar si la columna Per_Cod ya existe
$check = mysqli_query($conn, "SHOW COLUMNS FROM aud_tareas_asignadas LIKE 'Per_Cod'");
if (mysqli_num_rows($check) > 0) {
    echo "<p style='color:green'>La columna Per_Cod ya existe. No es necesario migrar.</p>";
    exit;
}

// Ejecutar migración
$sql1 = "ALTER TABLE aud_tareas_asignadas ADD COLUMN Per_Cod INT NULL AFTER Tar_Cod";
$sql2 = "ALTER TABLE aud_tareas_asignadas MODIFY COLUMN Usu_Cod INT NULL";

if (mysqli_query($conn, $sql1)) {
    echo "<p>✓ Columna Per_Cod añadida correctamente.</p>";
} else {
    echo "<p style='color:red'>Error al añadir Per_Cod: " . mysqli_error($conn) . "</p>";
    exit;
}

if (mysqli_query($conn, $sql2)) {
    echo "<p>✓ Columna Usu_Cod modificada (nullable).</p>";
} else {
    echo "<p style='color:orange'>Advertencia al modificar Usu_Cod: " . mysqli_error($conn) . "</p>";
}

echo "<p style='color:green'><strong>Migración completada.</strong> Ya puede asignar tareas desde el dashboard.</p>";
echo "<p><a href='../FRONT/aud_mod_dashboard_tareas_1.0.php'>Ir al Dashboard de Tareas</a></p>";
$obBD->cerrar();
?>
