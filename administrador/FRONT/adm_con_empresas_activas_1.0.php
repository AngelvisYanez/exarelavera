<?php
/**
 * Consulta de empresas activas con periodo contable
 * Muestra empresas activas que tienen periodo activo, con número de compras y ventas
 * VERSIÓN OPTIMIZADA
 *
 * @author Sistema
 * @version 3.0 - Optimizado
 * Fecha de actualización:	2025-01-XX
 *
 * @package administrador.FRONT
 */

require_once('../LOGICA/seguridad.php');
require_once('../LOGICA/adm_log_empresas_activas.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/**
 * objeto para la conexion
 * @var Class_Log_Conexion_Empresas_Activas
 */
$obBD_conexion = new Class_Log_Conexion_Empresas_Activas($Ses_Dat_Dis);

/**
 * objeto para consultas
 * @var Class_Log_Datos_Empresas_Activas
 */
$obBD_con1 = new Class_Log_Datos_Empresas_Activas;

// Bases de datos permitidas para consulta
$bases_permitidas = array('exa', 'servicios', 'gsl_chavez', 'agronuevo', 'coopsb');

// Obtener años disponibles (desde 2020 en adelante) - Solo de las bases permitidas
$periodos = array();
$anios_unicos = array();

// Consultar periodos solo de las bases permitidas
foreach($bases_permitidas as $base) {
    try {
        $obBD_conexion_dist = new Class_Log_Conexion_Empresas_Activas($base);
        $periodos_emp = $obBD_con1->getArrayConsulta(2, array("base_datos" => $base), $obBD_conexion_dist);
        foreach($periodos_emp as $p) {
            if(!in_array($p['Periodo'], $anios_unicos)) {
                $anios_unicos[] = $p['Periodo'];
                $periodos[] = $p;
            }
        }
        $obBD_conexion_dist->cerrar();
    } catch(Exception $e) {
        // Si hay error al conectar a una base, continuar con la siguiente
        continue;
    }
}

// Ordenar periodos descendente
usort($periodos, function($a, $b) {
    return $b['Periodo'] - $a['Periodo'];
});

// AJAX: Cargar datos del grid - VERSIÓN OPTIMIZADA
if(isset($LoadEmpresasAjax)){
    $parms = array(
        'Periodo' => isset($_GET['Periodo']) ? $_GET['Periodo'] : '',
        'search' => isset($_GET['search']) ? $_GET['search'] : '',
        'op_opciones' => isset($_GET['op_opciones']) ? $_GET['op_opciones'] : 'e'
    );
    
    // Bases de datos permitidas
    $bases_permitidas = array('exa', 'servicios', 'gsl_chavez', 'agronuevo', 'coopsb');
    
    // Obtener empresas activas desde exa_master, filtrando solo las bases permitidas
    $empresas = $obBD_con1->getArrayConsulta(1, $parms, $obBD_conexion);
    
    // Filtrar empresas solo de las bases permitidas
    $empresas_filtradas = array();
    foreach($empresas as $empresa) {
        if(!empty($empresa['Dat_Dis']) && in_array($empresa['Dat_Dis'], $bases_permitidas)) {
            $empresas_filtradas[] = $empresa;
        }
    }
    $empresas = $empresas_filtradas;
    
    // Agrupar empresas por base de datos para consultas batch
    $empresas_por_base = array();
    foreach($empresas as $empresa) {
        if(!empty($empresa['Dat_Dis']) && in_array($empresa['Dat_Dis'], $bases_permitidas)) {
            $base = $empresa['Dat_Dis'];
            if(!isset($empresas_por_base[$base])) {
                $empresas_por_base[$base] = array();
            }
            $empresas_por_base[$base][] = $empresa;
        }
    }
    
    $resultado = array();
    $compras_ventas_map = array();
    $periodos_activos_map = array();
    
    // Procesar por base de datos (reduce número de conexiones)
    foreach($empresas_por_base as $base_distribuida => $empresas_base) {
        $obBD_conexion_dist = new Class_Log_Conexion_Empresas_Activas($base_distribuida);
        
        // Obtener códigos de empresas de esta base
        $emp_cods = array();
        foreach($empresas_base as $emp) {
            $emp_cods[] = $emp['Emp_Cod'];
        }
        
        // Verificar periodos activos en batch (una sola consulta para todas las empresas)
        if(!empty($parms['Periodo']) && $parms['Periodo'] != 'T') {
            $periodos_activos = $obBD_con1->getArrayConsulta(4, array(
                'base_datos' => $base_distribuida,
                'Periodo' => $parms['Periodo'],
                'Emp_Cods' => $emp_cods
            ), $obBD_conexion_dist);
            
            // Crear mapa de empresas con periodo activo
            foreach($periodos_activos as $pa) {
                $periodos_activos_map[$pa['Emp_Cod']] = true;
            }
        }
        
        // Obtener compras y ventas en batch (una sola consulta para todas las empresas)
        if(!empty($parms['Periodo']) && $parms['Periodo'] != 'T') {
            $compras_ventas = $obBD_con1->getArrayConsulta(3, array(
                'base_datos' => $base_distribuida,
                'Periodo' => $parms['Periodo'],
                'Emp_Cods' => $emp_cods
            ), $obBD_conexion_dist);
            
            // Crear mapa de compras y ventas
            foreach($compras_ventas as $cv) {
                $compras_ventas_map[$cv['Emp_Cod']] = array(
                    'total_compras' => intval($cv['total_compras']),
                    'total_ventas' => intval($cv['total_ventas'])
                );
            }
        }
        
        $obBD_conexion_dist->cerrar();
    }
    
    // Construir resultado final
    foreach($empresas as $empresa) {
        // Si hay filtro de periodo, verificar que tenga periodo activo
        if(!empty($parms['Periodo']) && $parms['Periodo'] != 'T') {
            if(!isset($periodos_activos_map[$empresa['Emp_Cod']])) {
                continue; // No tiene periodo activo, saltar
            }
        }
        
        // Obtener compras y ventas del mapa
        $compras_ventas = isset($compras_ventas_map[$empresa['Emp_Cod']]) 
            ? $compras_ventas_map[$empresa['Emp_Cod']] 
            : array('total_compras' => 0, 'total_ventas' => 0);
        
        $resultado[] = array(
            'Emp_Cod' => $empresa['Emp_Cod'],
            'Emp_Nom' => $empresa['Emp_Nom'],
            'Emp_Ruc' => $empresa['Emp_Ruc'],
            'Emp_Cor' => $empresa['Emp_Cor'],
            'Emp_Est' => $empresa['Emp_Est'],
            'total_compras' => $compras_ventas['total_compras'],
            'total_ventas' => $compras_ventas['total_ventas']
        );
    }
    
    // Formato para jqGrid con paginación
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $rows = isset($_GET['rows']) ? intval($_GET['rows']) : 50;
    
    $total = count($resultado);
    $total_pages = ceil($total / $rows);
    $start = ($page - 1) * $rows;
    
    $resultado_paginado = array_slice($resultado, $start, $rows);
    
    $response = array(
        'page' => $page,
        'total' => $total_pages,
        'records' => $total,
        'rows' => $resultado_paginado
    );
    
    utf8_encode_deep($response);
    echo json_encode($response);
    exit();
}

?>
<!DOCTYPE html>
<HTML>
    <HEAD>
        <TITLE><?php echo $Ses_Sys_Nom; ?></TITLE>
        <meta charset="UTF-8">
        <?php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    </HEAD>

    <BODY>
        <div class="panel panel-main">
            <div class="panel-heading exa-header">
                <h3 class="panel-title">&raquo; Consultar Empresas Activas por Periodo</h3>
            </div>
            <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                <!-- AMBIENTE PRINCIPAL -->
                <div id="documentoSearch">
                    <div class="row">
                        <form name="searchEmpresas" id="searchEmpresas" class="form-horizontal normal" action="javascript:$('#empresasGrid').Search('#searchEmpresas','LoadEmpresasAjax');">
                            <div class="col-xs-5">
                                <fieldset class="exa-fieldset">
                                    <legend class="Titulos2">B&uacute;squeda</legend>
                                    <div class="form-group">
                                        <label class="col-xs-2 control-label label-xs">Filtrar Por:</label>
                                        <div class="col-xs-10 radioset opt_search">
                                            <input id="radsf1" name="op_opciones" type="radio" value="e" checked="" onclick="setfocus(this.form.search)" alt="" />
                                            <label for="radsf1">Empresa</label>
                                            <input id="radsf2" name="op_opciones" type="radio" value="c" onclick="setfocus(this.form.search)" alt="" />
                                            <label for="radsf2">RUC</label>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="col-xs-2 control-label">B&uacute;squeda:</label>
                                        <div class="col-xs-8">
                                            <div class="input-group">
                                                <input name="search" onkeydown="if (event.keyCode === 13) this.form.submit()" 
                                                    type="text" size="50" maxlength="50" placeholder="Ingrese b&uacute;squeda..." 
                                                    autofocus class="form-control input-xs clearable submit" />
                                                <span class="input-group-btn">
                                                    <button type="button" id="btnSearch" onclick="this.form.submit()" 
                                                        class="btn btn-success btn-xs" title="Buscar" tabindex="-1">
                                                        <span class="glyphicon glyphicon-search"></span>
                                                        <span>Buscar</span>
                                                    </button>
                                                </span>
                                            </div>
                                        </div>
                                        <input type="text" tabindex="-1" style="display:none;" />
                                    </div>
                                </fieldset>
                            </div>
                            <div class="col-sm-7">
                                <fieldset class="exa-fieldset">
                                    <legend class="Titulos2">Filtros</legend>
                                    <div class="form-group" style="margin-top: 10px; margin-left: 10px;">
                                        <label class="col-sm-1 control-label label-xs">A&ntilde;o:</label>
                                        <div class="col-sm-3" style="margin-right: 10px;">
                                            <select id="Periodo" name="Periodo" class="form-control input-xs" 
                                                style="text-align: center; width: 125px;" onchange="desbloquear();">
                                                <option value="T" selected><< Todos >></option>
                                                <?php
                                                foreach ($periodos as $p) {
                                                    $selected = '';
                                                    if(isset($_GET['Periodo']) && $_GET['Periodo'] == $p['Periodo']) {
                                                        $selected = 'selected';
                                                    }
                                                    echo "<option value='{$p['Periodo']}' $selected>A&ntilde;o {$p['Periodo']}</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                </fieldset>
                            </div>
                            <!-- Grid Principal de Empresas -->
                            <div class="col-sm-12" style="min-height: 350px; padding-bottom: 1px;">
                                <table id="empresasGrid"></table>
                                <div id="empresasGridPager"></div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <script src="../VALIDACIONES/adm_val_empresas_activas.js"></script>
        <?php
        // Cerrado y liberacion de las conexiones
        $obBD_con1->liberar();
        $obBD_conexion->cerrar();
        ?>
    </BODY>
</HTML>
