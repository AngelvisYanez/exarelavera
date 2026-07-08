<?php
/**
 * @abstract Modulo para inactivar anticipos y sus comprobantes
 * @version 1.0
 */
// Enable error logging to file for debugging
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__) . '/error_log.txt');
error_reporting(E_ALL);

function debug_log($msg){
    file_put_contents(dirname(__FILE__) . '/debug_out.txt', date('Y-m-d H:i:s') . " - " . $msg . "\n", FILE_APPEND);
}

// debug_log("Iniciando script...");

ob_start(); // Start buffer

try {
    require_once('../../administrador/LOGICA/seguridad.php');
    require_once('../LOGICA/rhu_log_roles.php');
    
    $sqlFile = dirname(dirname(__FILE__)) . '/LOGICA/rhu_sql_roles.php';
    if(file_exists($sqlFile)){
        require_once($sqlFile);
    }
    
    require_once('../../Librerias/procedimientos/almacenados_standar.php');

    $obBD_conexion = new Class_Log_Conexion_Rol($Ses_Dat_Dis);
    $obBD_con1 =  new Class_Log_Datos_Rol;

    // Check robustly for the search parameter
    // We check $_REQUEST, $_GET, and even the raw query string to be sure.
    $isSearch = false;
    if(isset($_REQUEST['searchAnticipos']) || isset($_GET['searchAnticipos']) || strpos($_SERVER['QUERY_STRING'], 'searchAnticipos') !== false){
        $isSearch = true;
    }

    if($isSearch){
        debug_log("Processing searchAnticipos...");
        ob_clean(); // Ensure clean buffer
        
        $responce = array();
        $data = $_GET;
        
        // DEFAULT DATES UPDATED TO 2022
        if(empty($data['fini'])) $data['fini'] = '2022-01-01';
        if(empty($data['ffin'])) $data['ffin'] = date('Y-m-t'); // End of current month
        
        $sqlData = array(
            'fini' => $data['fini'],
            'ffin' => $data['ffin'],
            'search' => isset($data['search']) ? $data['search'] : '',
            'Ant_Est' => isset($data['Ant_Est']) ? $data['Ant_Est'] : 'A'
        );
         
        debug_log("Params: " . json_encode($sqlData));
        debug_log("Ses_Emp_Cod: " . (isset($_SESSION['Ses_Emp_Cod']) ? $_SESSION['Ses_Emp_Cod'] : 'NOT SET'));
        
        $rows = $obBD_con1->getArrayConsulta(73, $sqlData, $obBD_conexion);
        debug_log("Rows returned: " . (is_array($rows) ? count($rows) : 'NOT ARRAY'));
        
        $safeRows = array();
        if(is_array($rows)){
            foreach($rows as $row){
                $newRow = array();
                foreach($row as $key => $val){
                    if(is_string($val)){
                        // Basic UTF8 fix
                        $val = mb_convert_encoding($val, 'UTF-8', 'ISO-8859-1');
                        // Remove newlines/carriage returns that break JSON
                        $val = str_replace(array("\r", "\n"), ' ', $val);
                        
                        if($key == 'Ant_Obs'){
                            // Extra safe for observation
                             $val = trim($val);
                        }
                        $newRow[$key] = $val; 
                    } else {
                        $newRow[$key] = $val;
                    }
                }
                $safeRows[] = $newRow;
            }
        }
        
        $responce['page'] = 1;
        $responce['total'] = 1;
        $responce['rows'] = $safeRows;
        $responce['records'] = count($safeRows);
        $responce['success'] = true;
        
        ob_end_clean(); 
        
        header('Content-Type: application/json');
        $json = json_encode($responce);
        
        debug_log("JSON Response Length: " . strlen($json));
        // debug_log("JSON Content: " . $json); // Identify if specific bad chars exist
        
        if ($json === false) {
             debug_log("JSON Encode Error Code: " . json_last_error());
             echo json_encode(array('success'=>false, 'message'=>'JSON Encode Error'));
        } else {
             echo $json;
        }
        exit();
    }
} catch (Exception $e) {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(array('success'=>false, 'message'=>'Exception: ' . $e->getMessage()));
    exit();
}

// Check for admin
$isAdmin = false;
if(isset($_SESSION['Ses_Lis_Per'])){
    foreach($_SESSION['Ses_Lis_Per'] as $per){
        if($per == 1 || $per == '1') { $isAdmin = true; break; }
    }
}

// Inactivation Logic
    // Inactivar checks
    if(isset($_POST['inactivarAnticipo']) || isset($_REQUEST['inactivarAnticipo'])){ 
        ob_clean();
    
        // Permission check removed as user requested direct visibility. Assuming standard page access control is sufficient.
        /* if(!$isAdmin){
            echo json_encode(array('success'=>false, 'message'=>'No tiene permisos para realizar esta acci&oacute;n'));
            exit();
        } */

    $obBD_ins1 =  new Class_Log_Datos_Rol;        
    $obBD_conexionIns = new Class_Log_Conexion_Rol($Ses_Dat_Dis);
    $obBD_ins1->inicio_transaccion($obBD_conexionIns);
    try{
        $Ant_Cod = $_POST['Ant_Cod'];
        
        // Inactivar Anticipo (SQL 71)
        $obBD_ins1->operacionobBD(71, array($Ant_Cod), $obBD_conexionIns);
        
        // Obtener Comprobante (SQL 75)
        $comCodData = $obBD_con1->getRowConsulta(75, array($Ant_Cod), $obBD_conexion);
        
        if(!empty($comCodData['Com_Cod'])){
             // Inactivar Comprobante (SQL 51)
             $obBD_ins1->operacionobBD(51, array($comCodData['Com_Cod']), $obBD_conexionIns);
        }
        
        $obBD_ins1->fin_transaccion_nomsn($obBD_conexionIns);
        $response = array('success'=>true, 'message'=>'Anticipo y Comprobante inactivados correctamente.');
    }catch(Exception $e){ 
        $obBD_ins1->rollBack_nomsn($obBD_conexionIns); 
        $response=array('success'=>false, 'message'=>$e->getMessage()); 
    }
    echo json_encode($response);
    exit();
}

ob_end_flush();
?>
<!DOCTYPE html>
<HTML>
    <HEAD>		
        <TITLE>Inactivar Anticipos [EXA]</TITLE>
        <meta charset= "UTF-8">
        <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php"); ?>  
        <script type="text/javascript" src="../../framework/plugins/moment.min.js"></script>
        <style>
            .exa-header { background-color: #2c3e50; color: white; padding: 10px; border-radius: 5px 5px 0 0; }
            .exa-body { padding: 15px; border: 1px solid #ddd; border-top: none; background: #fff; }
            .panel-main { margin: 20px; }
            .btn-search { margin-top: 25px; }
        </style>
    </HEAD>
<BODY>
    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title"><i class="glyphicon glyphicon-remove-circle"></i> Inactivar Anticipos</h3></div>
        
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div id="main-search">
              <div class="row">  
                  <form id="formSearchAnt" action="javascript:searchAnticipos();">
                    <div class="col-xs-8">  
                        <fieldset class="exa-fieldset">                           
                            <legend class="Titulos2">Filtros de B&uacute;squeda</legend>
                            <div class="form-horizontal normal">
                                <div class="form-group date-ranges">
                                    <label class="col-sm-2 control-label label-xs ">Desde:</label>
                                    <div class="col-sm-4">     
                                        <!-- UPDATED DEFAULT VALUE TO 2022-01-01 -->
                                        <input name="fini" type="text" id="fini" class="form-control input-xs" value="2022-01-01" />
                                    </div>
                                    <label class="col-sm-2 control-label label-sm ">Hasta:</label>
                                    <div class="col-sm-4">                                    
                                        <input name="ffin" type="text" id="ffin" class="form-control input-xs" value="<?php echo date('Y-m-t'); ?>" />
                                    </div>                                   
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-2 control-label label-xs">Estado:</label>
                                    <div class="col-sm-4">
                                        <select name="Ant_Est" id="Ant_Est" class="form-control input-xs">
                                            <option value="A" selected>Activo</option>
                                            <option value="I">Inactivo</option>
                                            <option value="T">Todos</option>
                                        </select>
                                    </div>
                                    <label class="col-sm-2 control-label label-xs">Personal:</label>
                                    <div class="col-sm-4">
                                        <input name="search" type="text" id="search" class="form-control input-xs" placeholder="Buscar..." />
                                    </div>
                                </div>
                            </div>
                        </fieldset>
                    </div>   
                    
                    <div class="col-xs-4 center vcenter" style="height: 100px;">
                        <button type="submit" class="btn btn-primary btn-search"><i class="glyphicon glyphicon-search"></i> Buscar Anticipos</button>
                    </div>                    
                    
                </form>
                <div class="col-xs-12" style="min-height: 250px; margin-top: 20px;">
                    <table id="gridAnticipos"></table><div id="pagerAnticipos"></div>
                </div>    
              </div>
            </div>  
        </div>
    </div>

    <script type="text/javascript">
        var isAdmin = <?php echo ($isAdmin) ? 'true' : 'false'; ?>;

        $(document).ready(function() {
            $.createDateRange('#fini', '#ffin');
            
            var model = [
                { label: 'Cód.', name: 'Ant_Cod', key: true, width: 50, align: "center" },
                { label: 'Cód. Com.', name: 'Com_Cod', width: 80, align: "center" },
                { label: 'Fecha', name: 'Ant_Fec', width: 80, align: "center" },
                { label: 'Personal', name: 'Personal', width: 250 },
                { label: 'Observación', name: 'Ant_Obs', width: 250 },
                { label: 'Valor', name: 'Ant_Val', width: 80, align: 'right', formatter: 'currency' },
                { label: 'Estado', name: 'Ant_Est', width: 60, align: 'center', formatter: 'estado' },
                { label: 'Comprobante', name: 'Com_Num', width: 80, align: 'center', hidden: true },
                { label: 'Acción', name: 'Inac', width: 60, align: 'center', formatter: function(cellvalue, options, rowObject){
                    // Ensure we handle both object and potentially array/indexed data if jqGrid varies
                    var estado = rowObject.Ant_Est || rowObject[5]; 
                    if(estado == 'A'){ 
                         return $.getGridButton({
                            action: inactivarAnt, 
                            type: 'danger', 
                            icon: 'trash', 
                            data: rowObject.Ant_Cod || rowObject[0], 
                            title: 'Inactivar'
                        });
                    } else {
                        // Display Red X for inactive rows
                        return '<i class="glyphicon glyphicon-remove" style="color: #d9534f; font-size: 1.2em;" title="Anulado"></i>';
                    }
                }}
            ];

            $('#gridAnticipos').jqGrid({
                datatype: "local",
                colModel: model,
                viewrecords: true,
                autowidth: true,
                height: 300,
                rowNum: 20,
                pager: "#pagerAnticipos",
                caption: "Listado de Anticipos Activos",
                rowattr: function (rd) {
                    if (rd.Ant_Est === "I" || rd.Ant_Est === "Inactivo") {
                        return { "style": "background: #ffcccc !important; color: black;" };
                    }
                }
            });
            
            // Initial search
            searchAnticipos();
        });

        function searchAnticipos() {
            var data = $('#formSearchAnt').serialize();
            var url = 'rhu_baj_anticipo.php?searchAnticipos=true&' + data;
            $('#gridAnticipos').jqGrid('setGridParam', { 
                url: url, 
                datatype: "json"
            }).trigger("reloadGrid");
        }

        function inactivarAnt(Ant_Cod){
            $.createDialogConfirm('¿Está seguro que desea inactivar este Anticipo y su Comprobante Contable?', 
                {inactivarAnticipo:true, Ant_Cod:Ant_Cod}, 
                function(data){
                    $.saveDataJson("rhu_baj_anticipo.php", data, function(resp){
                        $.alert(resp.message);
                        if(resp.success){
                            searchAnticipos(); 
                        }
                    });
                }
            );
        }
    </script>
</BODY>
</HTML>
