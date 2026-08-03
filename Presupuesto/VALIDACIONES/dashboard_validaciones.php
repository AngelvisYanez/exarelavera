<?php
/**
 * dashboard_validaciones.php
 * Reglas de validaci�n, limpieza y tipado de los filtros de entrada para el Dashboard Presupuestario en EXA PPTO.
 */

/**
 * Limpia y valida los filtros ingresados por el usuario para el Dashboard.
 * Aplica reglas estrictas de saneamiento y asigna valores por defecto consistentes.
 *
 * @param array $params Datos crudos del request ($_GET, $_POST o $_REQUEST).
 * @return array Filtros saneados y garantizados con tipos de datos correctos.
 */
function ppto_dashboard_validar_filtros($params) {
    $filtros = array();

    // 1. Validar Empresa (emp_id): Debe ser un entero positivo. Por defecto 1 o sesi�n.
    if (isset($params['emp_cod']) && trim($params['emp_cod']) !== '') {
        $filtros['emp_id'] = (int)$params['emp_cod'];
    } elseif (isset($params['emp_id']) && trim($params['emp_id']) !== '') {
        $filtros['emp_id'] = (int)$params['emp_id'];
    } else {
        $filtros['emp_id'] = isset($_SESSION['Ses_Emp_Cod']) ? (int)$_SESSION['Ses_Emp_Cod'] : 1;
    }

    // 2. Validar A�o (anio): Debe ser un entero de 4 d�gitos entre 2000 y 2100. Por defecto a�o actual.
    if (isset($params['ani']) && trim($params['ani']) !== '') {
        $filtros['anio'] = (int)$params['ani'];
    } elseif (isset($params['anio']) && trim($params['anio']) !== '') {
        $filtros['anio'] = (int)$params['anio'];
    } else {
        $filtros['anio'] = (int)date('Y');
    }
    if ($filtros['anio'] < 2000 || $filtros['anio'] > 2100) {
        $filtros['anio'] = (int)date('Y');
    }

    // 3. Validar Periodo: vista anual|acumulado|mes (igual que Cuadro presupuestario).
    $vista_periodo = isset($params['periodo_vista']) ? strtolower(trim($params['periodo_vista'])) : '';
    if ($vista_periodo === '' && isset($params['cuadro_vista'])) {
        $vista_periodo = strtolower(trim($params['cuadro_vista']));
    }
    if (!in_array($vista_periodo, array('anual', 'acumulado', 'mes'), true)) {
        $vista_periodo = 'acumulado';
    }
    $filtros['periodo_vista'] = $vista_periodo;

    $filtros['mes'] = null;
    $mes_raw = null;
    if (isset($params['mes']) && trim($params['mes']) !== '') {
        $mes_raw = (int)$params['mes'];
    } elseif (isset($params['cuadro_mes']) && trim($params['cuadro_mes']) !== '') {
        $mes_raw = (int)$params['cuadro_mes'];
    }
    if ($mes_raw !== null && $mes_raw >= 1 && $mes_raw <= 12) {
        $filtros['mes'] = $mes_raw;
    }
    // Anual: no corta por mes en ledger/forecast.
    if ($filtros['periodo_vista'] === 'anual') {
        $filtros['mes'] = null;
    }

    // 4. Validar Versi�n Presupuestaria (ppe_id): Entero positivo. Opcional.
    $filtros['ppe_id'] = null;
    if (isset($params['ver']) && trim($params['ver']) !== '') {
        $filtros['ppe_id'] = (int)$params['ver'];
    } elseif (isset($params['ppe_id']) && trim($params['ppe_id']) !== '') {
        $filtros['ppe_id'] = (int)$params['ppe_id'];
    }

    // 5. Validar Proyecto (proy_id): Alfanum�rico o NULL.
    $filtros['proy_id'] = null;
    if (isset($params['proy_id']) && trim($params['proy_id']) !== '') {
        $clean_proy = preg_replace('/[^a-zA-Z0-9_-]/', '', trim($params['proy_id']));
        if (!empty($clean_proy)) {
            $filtros['proy_id'] = $clean_proy;
        }
    }

    // 6. Validar Partida Espec�fica (ppa_id): Entero positivo. Opcional.
    $filtros['ppa_id'] = null;
    if (isset($params['ppa_id']) && trim($params['ppa_id']) !== '') {
        $filtros['ppa_id'] = (int)$params['ppa_id'];
    }

    // 7. Vista tabla partidas: jerarquica (grupos + detalle) o plana (solo detalle con valores).
    $vista = isset($params['vista_partidas']) ? trim($params['vista_partidas']) : 'jerarquica';
    $filtros['vista_partidas'] = ($vista === 'plana') ? 'plana' : 'jerarquica';

    // 8. Modo de lectura: gerente (simple) o tecnico (completo).
    $modo = isset($params['modo_ux']) ? trim($params['modo_ux']) : 'gerente';
    $filtros['modo_ux'] = ($modo === 'tecnico') ? 'tecnico' : 'gerente';
    if ($filtros['modo_ux'] === 'gerente') {
        $filtros['vista_partidas'] = 'jerarquica';
    }

    return $filtros;
}
