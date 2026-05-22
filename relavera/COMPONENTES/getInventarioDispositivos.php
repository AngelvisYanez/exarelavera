<?php
/**
 * Endpoint para obtener el listado de inventario de dispositivos en formato JSON para jqGrid
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/log_inventario_dispositivos.php');

$obBD_conexion = new Class_Log_Conexion_Inventario($Ses_Dat_Dis);
$obBD_con1 = new Class_Log_Datos_Inventario;

// Parámetros de jqGrid
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$rows = isset($_GET['rows']) ? intval($_GET['rows']) : 100;
$sidx = isset($_GET['sidx']) ? $_GET['sidx'] : 'InvDis_Cod';
$sord = isset($_GET['sord']) ? $_GET['sord'] : 'desc';

// Filtros iniciales
$params = array(
    'mac' => '',
    'nombre' => '',
    'estado' => '',
    'search' => ''
);

if (isset($_GET['filters'])) {
    $filters = json_decode($_GET['filters']);
    if ($filters && isset($filters->rules)) {
        foreach ($filters->rules as $rule) {
            if ($rule->field == 'mac_address') $params['mac'] = $rule->data;
            if ($rule->field == 'InvDis_Nom') $params['nombre'] = $rule->data;
            if ($rule->field == 'InvDis_Est') $params['estado'] = $rule->data;
        }
    }
} else {
    if (isset($_GET['busca_mac'])) $params['mac'] = $_GET['busca_mac'];
    if (isset($_GET['busca_nombre'])) $params['nombre'] = $_GET['busca_nombre'];
    if (isset($_GET['busca_estado'])) $params['estado'] = $_GET['busca_estado'];
}

// Contar total de registros
$row_count = $obBD_con1->getRowConsulta(6, $params, $obBD_conexion);
$count = isset($row_count['total']) ? intval($row_count['total']) : 0;

// Calcular paginación
$total_pages = ($count > 0) ? ceil($count / $rows) : 0;
if ($page > $total_pages && $total_pages > 0) $page = $total_pages;
$start = $rows * $page - $rows;
if ($start < 0) $start = 0;

$params['limits'] = " ORDER BY $sidx $sord LIMIT $start, $rows";

// Obtener datos
$data_rows = $obBD_con1->getArrayConsulta(1, $params, $obBD_conexion);

$responce = new stdClass();
$responce->page = $page;
$responce->total = $total_pages;
$responce->records = $count;
$responce->rows = array(); // Inicializar siempre como array vacío

if (is_array($data_rows)) {
    foreach ($data_rows as $row) {
        $responce->rows[] = array(
            'InvDis_Cod'  => $row['InvDis_Cod'],
            'mac_address' => $row['mac_address'],
            'InvDis_Nom'  => $row['InvDis_Nom'],
            'InvDis_Est'  => $row['InvDis_Est'],
            'InvDis_Fec'  => $row['InvDis_Fec'],
            'InvDis_Tipo' => $row['InvDis_Tipo'],
            'InvDis_Cupos'=> isset($row['InvDis_Cupos']) ? $row['InvDis_Cupos'] : 1,
            'InvDis_Des'  => $row['InvDis_Des']
        );
    }
}

header('Content-Type: application/json');
echo json_encode($responce);
?>
