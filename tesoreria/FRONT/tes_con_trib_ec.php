<?php
/**
 * Control Tributario EC — Punto de integración EXA
 * 
 * Este archivo se accede desde el menú de EXA:
 * tesoreria/FRONT/tes_con_trib_ec.php
 * 
 * Redirige al módulo completo de Control Tributario EC.
 */
require_once('../../administrador/LOGICA/seguridad.php');

// La sesión de EXA ya está activa gracias a seguridad.php
// Ahora incluir el bridge y redirigir
require_once('../LOGICA/tes_log_104.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

// Datos de la empresa actual
$empresa_nombre = isset($_SESSION['Ses_Emp_Nom']) ? $_SESSION['Ses_Emp_Nom'] : '';
$empresa_ruc = isset($_SESSION['Ses_Emp_Ruc']) ? $_SESSION['Ses_Emp_Ruc'] : '';
$anio = isset($_GET['anio']) ? intval($_GET['anio']) : intval(date('Y'));

// Pre-cargar sesión de control tributario
$_SESSION['ct_ruc'] = $empresa_ruc;
$_SESSION['ct_nombre'] = $empresa_nombre;
$_SESSION['ct_anio'] = $anio;
$_SESSION['ct_regimen'] = 'pn';

// Cargar parámetros
$ct_root = realpath(__DIR__ . '/../../control-tributario-ec');
if ($ct_root && file_exists($ct_root . '/config/parametros.php')) {
    $_SESSION['ct_parametros'] = include $ct_root . '/config/parametros.php';
}

// Inicializar estructura de datos
if (!isset($_SESSION['declaraciones'])) $_SESSION['declaraciones'] = [];
if (!isset($_SESSION['iess'])) $_SESSION['iess'] = ['empleados' => [], 'periodos' => []];
if (!isset($_SESSION['datos_manuales'])) {
    $meses = [];
    for ($m = 1; $m <= 12; $m++) {
        $meses[$m] = [
            'ventas_0' => 0, 'nc_ventas_15' => 0, 'nc_ventas_0' => 0,
            'compras_0' => 0, 'activos_fijos' => 0, 'importaciones' => 0,
            'ret_iva_20' => 0, 'ret_iva_30' => 0, 'ret_iva_70' => 0, 'ret_iva_100' => 0,
            'ret_ir_303' => 0, 'ret_ir_303a' => 0, 'ret_ir_304' => 0, 'ret_ir_307' => 0,
            'ret_ir_310' => 0, 'ret_ir_312' => 0, 'ret_ir_322' => 0, 'ret_ir_332' => 0,
            'ret_ir_343' => 0, 'ret_ir_344' => 0, 'ret_ir_346' => 0,
            'depreciaciones' => 0, 'gastos_no_deducibles' => 0,
            'form_103_presentado' => 0, 'ats_presentado' => 0,
        ];
    }
    $_SESSION['datos_manuales'] = [
        'meses' => $meses,
        'ir_anual' => [
            'rendimientos_financieros' => 0, 'otros_ingresos' => 0,
            'intereses_bancarios' => 0, 'otros_servicios' => 0,
            'otros_gastos_deducibles' => 0, 'gastos_personales' => 0,
            'credito_tributario_anterior' => 0,
        ],
        'tabla_progresiva' => [],
    ];
}
if (!isset($_SESSION['comprobantes'])) $_SESSION['comprobantes'] = [];

// Redirigir al módulo
header('Location: /control-tributario-ec/?from_exa=1');
exit;
