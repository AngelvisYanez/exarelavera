<?php
/**
 * Pantalla para calcular décimo cuarto a partir del consolidado IESS (.xlsx)
 * y exportar en los mismos formatos de RRHH (plantilla Excel y CSV IESS).
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/rhu_log_roles.php');

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Rol($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_Rol;

// Endpoint para obtener datos adicionales desde la BD (género, ocupación, forma de pago, mensualización, sueldo básico desde rol_defaults)
if (isset($_POST['getDatosIessCuarto'])) {
    header('Content-Type: application/json; charset=utf-8');
    $cedulas = isset($_POST['cedulas']) ? json_decode($_POST['cedulas'], true) : array();
    $fechasPeriodos = isset($_POST['fechas']) ? json_decode($_POST['fechas'], true) : array();
    if (!is_array($cedulas) || count($cedulas) === 0) {
        echo json_encode(array('success' => false, 'rows' => array()));
        exit;
    }
    // Sanitizar y armar lista para IN - asegurar que sean solo números
    $lista = array();
    foreach ($cedulas as $c) {
        // Limpiar la cédula: solo números, sin espacios ni caracteres especiales
        $c = preg_replace('/[^0-9]/', '', (string)$c);
        $c = trim($c);
        if ($c !== '' && strlen($c) >= 9) { // Las cédulas ecuatorianas tienen al menos 9 dígitos
            $lista[] = "'" . $c . "'";
        }
    }
    if (count($lista) === 0) {
        echo json_encode(array('success' => false, 'rows' => array(), 'error' => 'No hay cédulas válidas'));
        exit;
    }
    $inCed = implode(',', $lista);
    
    // Debug: log de cédulas recibidas
    error_log("DEBUG PHP - Cédulas recibidas (raw): " . print_r($cedulas, true));
    error_log("DEBUG PHP - Lista procesada: " . print_r($lista, true));
    error_log("DEBUG PHP - Lista SQL IN: " . $inCed);
    error_log("DEBUG PHP - Número de cédulas en lista: " . count($lista));
    
    // También mostrar en la respuesta para debug en consola
    $debugInfo = array(
        'cedulas_recibidas' => $cedulas,
        'lista_procesada' => $lista,
        'sql_in' => $inCed
    );
    
    // Obtener sueldo básico desde rol_defaults solo para la fecha final (dateRolfin)
    $fechaFinal = isset($_POST['fechaFinal']) ? $_POST['fechaFinal'] : '';
    $sueldoBasFinal = 0;
    if ($fechaFinal) {
        $defaults = $obBD_con1->getArrayConsulta(30, array($fechaFinal), $obBD_conexion);
        if (is_array($defaults) && count($defaults) > 0) {
            foreach ($defaults as $d) {
                if (isset($d['Rde_Var']) && $d['Rde_Var'] === 'sueldo_bas') {
                    $sueldoBasFinal = isset($d['Rde_Val']) ? floatval($d['Rde_Val']) : 0;
                    break;
                }
            }
        }
    }
    
    // Consulta para obtener género (Prs_Sex de persona) y mensualización (Afi_Dcu de afiliacion)
    // PER.Prs_Ced -> PER.Prs_Cod -> PR.Per_Cod -> CL.Con_Cod -> AF.Afi_Dcu
    $sql = "SELECT PER.Prs_Ced, PER.Prs_Sex, PER.Prs_Ape, PER.Prs_Nom, 
            TC.Tic_Des AS car_des, 
            PG.Pag_Con_Cue, 
            COALESCE(AF.Afi_Dcu, '') AS Afi_Dcu,
            CL.Con_Cod
            FROM persona PER
            INNER JOIN personal PR ON PR.Prs_Cod = PER.Prs_Cod
            INNER JOIN contratos_lab CL ON CL.Per_Cod = PR.Per_Cod AND CL.Con_Est='A'
            LEFT JOIN afiliacion AF ON AF.Con_Cod = CL.Con_Cod AND AF.Afi_Est='A'
            LEFT JOIN pago_contrato PG ON PG.Con_Cod = CL.Con_Cod AND PG.Pag_Con_Est='A'
            LEFT JOIN tiposcargo TC ON CL.Tic_Cod = TC.Tic_Cod
            WHERE PER.Prs_Ced IN ($inCed)
            GROUP BY PER.Prs_Ced";
    
    error_log("DEBUG - SQL ejecutado: " . $sql);
    $rows = $obBD_con1->getArrayConsultaSql($sql, $obBD_conexion);
    $numRows = is_array($rows) ? count($rows) : 0;
    error_log("DEBUG - Filas devueltas ANTES de sueldos: " . $numRows);
    if ($numRows > 0) {
        error_log("DEBUG - Primera fila ejemplo: " . print_r($rows[0], true));
    } else {
        error_log("DEBUG - No se devolvieron filas. Verificar si las cédulas existen en la BD.");
    }
    
    // Ahora obtener los sueldos por separado para cada contrato
    if (is_array($rows) && count($rows) > 0) {
        $conCodList = array();
        foreach ($rows as $row) {
            if (isset($row['Con_Cod']) && !empty($row['Con_Cod'])) {
                $conCodList[] = intval($row['Con_Cod']);
            }
        }
        if (count($conCodList) > 0) {
            $conCodList = array_unique($conCodList);
            $inConCod = implode(',', $conCodList);
            $sqlSueldos = "SELECT Con_Cod, Sue_Bas, Sue_Val FROM sueldos WHERE Con_Cod IN ($inConCod) ORDER BY Con_Cod, Sue_Fec DESC";
            $sueldosRows = $obBD_con1->getArrayConsultaSql($sqlSueldos, $obBD_conexion);
            
            // Crear mapa de sueldos por contrato (tomar el más reciente)
            $mapSueldos = array();
            if (is_array($sueldosRows)) {
                foreach ($sueldosRows as $sRow) {
                    $conCod = isset($sRow['Con_Cod']) ? intval($sRow['Con_Cod']) : 0;
                    if ($conCod > 0 && !isset($mapSueldos[$conCod])) {
                        $mapSueldos[$conCod] = array(
                            'Sue_Bas' => (isset($sRow['Sue_Bas']) && $sRow['Sue_Bas'] !== null && $sRow['Sue_Bas'] !== '') ? $sRow['Sue_Bas'] : 'N',
                            'Sue_Val' => isset($sRow['Sue_Val']) ? floatval($sRow['Sue_Val']) : 0
                        );
                    }
                }
            }
            
            // Agregar sueldos a las filas
            for ($i = 0; $i < count($rows); $i++) {
                $conCod = isset($rows[$i]['Con_Cod']) ? intval($rows[$i]['Con_Cod']) : 0;
                if ($conCod > 0 && isset($mapSueldos[$conCod])) {
                    $rows[$i]['Sue_Bas'] = $mapSueldos[$conCod]['Sue_Bas'];
                    $rows[$i]['sueldo_bas_valor'] = $mapSueldos[$conCod]['Sue_Val'];
                } else {
                    $rows[$i]['Sue_Bas'] = 'N';
                    $rows[$i]['sueldo_bas_valor'] = 0;
                }
                if (isset($rows[$i]['Con_Cod'])) {
                    unset($rows[$i]['Con_Cod']); // Remover Con_Cod del resultado final
                }
            }
        } else {
            // Si no hay contratos, establecer valores por defecto
            foreach ($rows as $key => $row) {
                $rows[$key]['Sue_Bas'] = 'N';
                $rows[$key]['sueldo_bas_valor'] = 0;
                if (isset($rows[$key]['Con_Cod'])) {
                    unset($rows[$key]['Con_Cod']);
                }
            }
        }
    }
    
    error_log("DEBUG PHP - Filas devueltas DESPUÉS de sueldos: " . (is_array($rows) ? count($rows) : 0));
    
    // Incluir información de debug en la respuesta (solo si hay problemas)
    $response = array(
        'success' => true, 
        'rows' => $rows, 
        'sueldo_bas_final' => $sueldoBasFinal
    );
    
    // Si no hay filas, incluir debug info para diagnosticar
    if (count($rows) === 0) {
        $response['debug'] = $debugInfo;
        $response['debug']['sql'] = $sql;
    }
    
    echo json_encode($response);
    exit;
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <title><?php echo "Décimo cuarto desde IESS [EXA]"; ?></title>
    <?php require_once("../../mascaras/model1/estilos/jqgrid5.php"); ?>
    <!-- SheetJS para leer archivos XLSX en el navegador -->
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <style>
        .progress { margin-top:5px; }
    </style>
</head>
<body>
    <div class="panel panel-main">
        <div class="panel-heading exa-header">
            <h3 class="panel-title">&raquo; Cálculo décimo cuarto desde consolidado IESS</h3>
        </div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <!-- Datos generales -->
            <input type="hidden" id="razonSocialEmpresa" value="<?php echo isset($Ses_Emp_Des) ? $Ses_Emp_Des : (isset($Ses_Emp_Nom) ? $Ses_Emp_Nom : ''); ?>">
            <input type="hidden" id="rucEmpresa" value="<?php echo isset($Ses_Emp_Ruc) ? $Ses_Emp_Ruc : ''; ?>">

            <div class="row">
                <div class="col-xs-6 form-horizontal normal">
                    <fieldset class="exa-fieldset">
                        <legend class="Titulos2">Archivos IESS</legend>
                        <div class="form-group">
                            <label class="col-xs-3 control-label label-xs required">Consolidado:</label>
                            <div class="col-xs-9">
                                <input type="file" id="fileIess" accept=".xls,.xlsx" class="form-control input-xs" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-3 control-label label-xs">Formas de pago:</label>
                            <div class="col-xs-9">
                                <input type="file" id="fileIessPago" accept=".xls,.xlsx" class="form-control input-xs" />
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-xs-12 text-right">
                                <button type="button" id="btnCargarIess" class="btn btn-success btn-xs">
                                    <i class="glyphicon glyphicon-refresh"></i> Cargar
                                </button>
                            </div>
                        </div>
                    </fieldset>
                </div>
                <div class="col-xs-6 form-horizontal normal">
                    <fieldset class="exa-fieldset">
                        <legend class="Titulos2">Rango de Fechas</legend>
                        <div class="form-group">
                            <label class="col-xs-3 control-label label-xs">Desde:</label>
                            <div class="col-xs-9">
                                <input type="date" id="dateRolini" name="dateRolini" class="form-control input-xs" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-3 control-label label-xs">Hasta:</label>
                            <div class="col-xs-9">
                                <input type="date" id="dateRolfin" name="dateRolfin" class="form-control input-xs" />
                            </div>
                        </div>
                    </fieldset>
                </div>
            </div>

            <div class="row">
                <div class="col-xs-12" style="min-height:250px;">
                    <table id="gridDecimoCuartoIess"></table>
                    <div id="pagerDecimoCuartoIess"></div>
                </div>
            </div>
        </div>
    </div>

    <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
    <script type="text/ecmascript" src="../VALIDACIONES/rhu_val_decimo_cuarto_iess.js"></script>
</body>
</html>
