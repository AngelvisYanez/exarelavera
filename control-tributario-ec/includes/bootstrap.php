<?php
/**
 * Bootstrap — Control Tributario EC (EXA)
 */
if (session_id() === '') {
    session_start();
}

define('CTE_ROOT', dirname(__DIR__));
define('CTE_UPLOAD', CTE_ROOT . '/uploads/tmp');

if (!is_dir(CTE_UPLOAD)) {
    @mkdir(CTE_UPLOAD, 0755, true);
}

require_once CTE_ROOT . '/includes/funciones.php';
require_once CTE_ROOT . '/calculos/calculos_vencimientos.php';
require_once CTE_ROOT . '/calculos/calculos_iva.php';
require_once CTE_ROOT . '/calculos/calculos_ir.php';
require_once CTE_ROOT . '/calculos/calculos_iess.php';

/** Inicializa estructura de sesión */
function cte_init_session() {
    if (!isset($_SESSION['contribuyente'])) {
        $_SESSION['contribuyente'] = array();
    }
    if (!isset($_SESSION['declaraciones'])) {
        $_SESSION['declaraciones'] = array();
    }
    if (!isset($_SESSION['iess'])) {
        $_SESSION['iess'] = array('empleados' => array(), 'periodos' => array());
    }
    if (!isset($_SESSION['datos_manuales'])) {
        $_SESSION['datos_manuales'] = cte_default_datos_manuales();
    }
    if (!isset($_SESSION['comprobantes'])) {
        $_SESSION['comprobantes'] = array();
    }
}

function cte_default_datos_manuales() {
    $meses = array();
    for ($m = 1; $m <= 12; $m++) {
        $meses[$m] = array(
            'ventas_0' => 0, 'nc_ventas_15' => 0, 'nc_ventas_0' => 0,
            'compras_0' => 0, 'activos_fijos' => 0, 'importaciones' => 0,
            'ret_iva_20' => 0, 'ret_iva_30' => 0, 'ret_iva_70' => 0, 'ret_iva_100' => 0,
            'ret_ir_303' => 0, 'ret_ir_303a' => 0, 'ret_ir_304' => 0, 'ret_ir_307' => 0,
            'ret_ir_310' => 0, 'ret_ir_312' => 0, 'ret_ir_322' => 0, 'ret_ir_332' => 0,
            'ret_ir_343' => 0, 'ret_ir_344' => 0, 'ret_ir_346' => 0,
            'depreciaciones' => 0, 'gastos_no_deducibles' => 0,
            'form_103_presentado' => 0, 'ats_presentado' => 0,
        );
    }
    return array(
        'meses' => $meses,
        'ir_anual' => array(
            'rendimientos_financieros' => 0,
            'otros_ingresos' => 0,
            'intereses_bancarios' => 0,
            'otros_servicios' => 0,
            'otros_gastos_deducibles' => 0,
            'gastos_personales' => 0,
            'credito_tributario_anterior' => 0,
        ),
        'tabla_progresiva' => array(),
    );
}

function cte_limpiar_sesion() {
    unset(
        $_SESSION['contribuyente'],
        $_SESSION['declaraciones'],
        $_SESSION['iess'],
        $_SESSION['datos_manuales'],
        $_SESSION['comprobantes']
    );
    cte_init_session();
}

cte_init_session();
