<?php
/**
 * dashboard_validaciones.php
 * Reglas de validaciï¿½n, limpieza y tipado de los filtros de entrada para el Dashboard Presupuestario en EXA PPTO.
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

    // 1. Validar Empresa (Emp_Cod): Debe ser un entero positivo. Por defecto 1 o sesiï¿½n.
    if (isset($params['emp_cod']) && trim($params['emp_cod']) !== '') {
        $filtros['Emp_Cod'] = (int)$params['emp_cod'];
    } elseif (isset($params['Emp_Cod']) && trim($params['Emp_Cod']) !== '') {
        $filtros['Emp_Cod'] = (int)$params['Emp_Cod'];
    } else {
        $filtros['Emp_Cod'] = isset($_SESSION['Ses_Emp_Cod']) ? (int)$_SESSION['Ses_Emp_Cod'] : 1;
    }

    // 2. Validar Aï¿½o (anio): Debe ser un entero de 4 dï¿½gitos entre 2000 y 2100. Por defecto aï¿½o actual.
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

    // 4. Validar Versiï¿½n Presupuestaria (Ppe_Cod): Entero positivo. Opcional.
    $filtros['Ppe_Cod'] = null;
    if (isset($params['ver']) && trim($params['ver']) !== '') {
        $filtros['Ppe_Cod'] = (int)$params['ver'];
    } elseif (isset($params['Ppe_Cod']) && trim($params['Ppe_Cod']) !== '') {
        $filtros['Ppe_Cod'] = (int)$params['Ppe_Cod'];
    }

    // 5. Validar Proyecto (Pro_Cod): proy_id INT (o codigo legacy alfanumerico).
    $filtros['Pro_Cod'] = null;
    if (isset($params['Pro_Cod']) && trim($params['Pro_Cod']) !== '') {
        $raw_proy = trim($params['Pro_Cod']);
        if (preg_match('/^\d+$/', $raw_proy)) {
            $filtros['Pro_Cod'] = (string)((int)$raw_proy);
        } else {
            $clean_proy = preg_replace('/[^a-zA-Z0-9_-]/', '', $raw_proy);
            if (!empty($clean_proy)) {
                $filtros['Pro_Cod'] = $clean_proy;
            }
        }
    }

    // 5b. Alcance: proyecto (default) | general | consolidado.
    $alcance = isset($params['alcance']) ? trim($params['alcance']) : '';
    if ($alcance !== 'general' && $alcance !== 'proyecto' && $alcance !== 'consolidado') {
        // Por defecto: vista por proyecto (Relavera/RCET).
        $alcance = 'proyecto';
    }
    if ($alcance === 'general') {
        $filtros['Pro_Cod'] = null;
    } elseif ($alcance === 'proyecto' && $filtros['Pro_Cod'] === null) {
        // Sin proyecto elegido: no inventar consolidado; el SQL devolvera vacio.
    } elseif ($alcance === 'consolidado') {
        // Consolidado ignora proyecto puntual (suma general + todos los proyectos).
        $filtros['Pro_Cod'] = null;
    }
    $filtros['alcance'] = $alcance;

    // 6. Validar Partida Especï¿½fica (Ppa_Cod): Entero positivo. Opcional.
    $filtros['Ppa_Cod'] = null;
    if (isset($params['Ppa_Cod']) && trim($params['Ppa_Cod']) !== '') {
        $filtros['Ppa_Cod'] = (int)$params['Ppa_Cod'];
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
