<?php
/**
 * Puente de integración — Control Tributario EC ↔ EXA Contable
 * 
 * Este archivo conecta la sesión de EXA con la de control-tributario-ec.
 * Uso: require_once este archivo ANTES de cualquier lógica de control tributario.
 */
if (session_id() === '') {
    session_start();
}

// Verificar que EXA está autenticado
if (!isset($_SESSION['Ses_Usu_Cod']) || !isset($_SESSION['Ses_Emp_Cod'])) {
    header('Location: ' . dirname($_SERVER['SCRIPT_NAME']) . '/../index.php');
    exit;
}

// Mapear sesión de EXA → control-tributario-ec
define('CTE_ROOT', dirname(__DIR__) . '/control-tributario-ec');
define('CTE_UPLOAD', CTE_ROOT . '/uploads/tmp');

if (!is_dir(CTE_UPLOAD)) {
    @mkdir(CTE_UPLOAD, 0755, true);
}

require_once CTE_ROOT . '/includes/funciones.php';
require_once CTE_ROOT . '/calculos/calculos_vencimientos.php';
require_once CTE_ROOT . '/calculos/calculos_iva.php';
require_once CTE_ROOT . '/calculos/calculos_ir.php';
require_once CTE_ROOT . '/calculos/calculos_iess.php';

// Inicializar sesión de control tributario si no existe
if (!isset($_SESSION['ct_ruc'])) {
    $_SESSION['ct_ruc'] = '';
    $_SESSION['ct_nombre'] = '';
    $_SESSION['ct_regimen'] = 'pn';
    $_SESSION['ct_anio'] = intval(date('Y'));
    $_SESSION['ct_parametros'] = include CTE_ROOT . '/config/parametros.php';
}

// Cargar datos del contribuyente desde EXA si están disponibles
if (empty($_SESSION['ct_ruc']) && isset($_SESSION['Ses_Emp_Cod'])) {
    // Intentar obtener RUC de la empresa desde la BD de EXA
    $exa_ruc = '';
    $exa_nombre = '';
    
    // Los datos de empresa podrían estar en sesión de EXA o consultarse desde la BD
    if (isset($_SESSION['Ses_Emp_Nom'])) $exa_nombre = $_SESSION['Ses_Emp_Nom'];
    if (isset($_SESSION['Ses_Emp_Ruc'])) $exa_ruc = $_SESSION['Ses_Emp_Ruc'];
    
    if (!empty($exa_ruc)) {
        $_SESSION['ct_ruc'] = $exa_ruc;
        $_SESSION['ct_nombre'] = $exa_nombre;
        $_SESSION['contribuyente'] = [
            'ruc' => $exa_ruc,
            'razon_social' => $exa_nombre,
            'regimen' => $_SESSION['ct_regimen'],
            'anio' => $_SESSION['ct_anio'],
        ];
    }
}

// Función para obtener datos del contribuyente desde EXA
function cte_exa_get_empresa() {
    return [
        'ruc' => isset($_SESSION['ct_ruc']) ? $_SESSION['ct_ruc'] : '',
        'nombre' => isset($_SESSION['ct_nombre']) ? $_SESSION['ct_nombre'] : '',
        'regimen' => isset($_SESSION['ct_regimen']) ? $_SESSION['ct_regimen'] : 'pn',
        'anio' => isset($_SESSION['ct_anio']) ? $_SESSION['ct_anio'] : intval(date('Y')),
        'emp_cod' => isset($_SESSION['Ses_Emp_Cod']) ? $_SESSION['Ses_Emp_Cod'] : '',
        'usu_cod' => isset($_SESSION['Ses_Usu_Cod']) ? $_SESSION['Ses_Usu_Cod'] : '',
    ];
}
