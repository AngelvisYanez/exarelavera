<?php
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tes_log_cccc.php');

$obBD_conexion = new Class_Log_Conexion_Cccc($Ses_Dat_Dis);
$conn = $obBD_conexion->conexion;

if(isset($_GET['ajaxCompr'])){
    $modulo = isset($_GET['modulo']) ? $_GET['modulo'] : 'Ccxcc';
    $tipo_error = isset($_GET['tipo_error']) ? $_GET['tipo_error'] : 'Todos';
    $ini = isset($_GET['ini']) ? $_GET['ini'] : date("Y-m-d");
    $fin = isset($_GET['fin']) ? $_GET['fin'] : date("Y-m-d");

    $rows = array();
    
    if ($modulo == 'Ccxcc') {
        $sql = "
            SELECT 
                CONCAT(tia.Tia_Abr,'-',IF(CHAR_LENGTH(MONTH(c.Com_Fec))=1, CONCAT('0',CAST(MONTH(c.Com_Fec) AS char)),CAST(MONTH(c.Com_Fec) AS char)),'-',CAST(c.Com_Num AS char)) AS no_compr,
                c.Com_Fec AS fecha_emis,
                CONCAT(s.Suc_Sri, '-', aut.Pun_Sri, '-', CAST(LPAD(v.Vet_Num, 9, '0') AS char)) AS no_documento,
                p.Prs_Ced AS ruc_cedula,
                CONCAT(p.Prs_Ape, ' ', p.Prs_Nom) AS cliente_proveedor,
                s.Suc_Des AS sucursal,
                (SELECT ROUND(MAX(Asi_Val), 2) FROM asientos WHERE Com_Cod = c.Com_Cod AND Asi_Deh = 'D') AS total,
                (SELECT ROUND(SUM(d.Cpc_Val), 2) FROM det_ccpp_c d JOIN comprobantes cp ON d.Com_Cod = cp.Com_Cod WHERE d.Cpc_Cod = cb.Cpc_Cod AND cp.Com_Est = 'A') AS abono,
                (SELECT MAX(cp.Com_Fec) FROM det_ccpp_c d JOIN comprobantes cp ON d.Com_Cod = cp.Com_Cod WHERE d.Cpc_Cod = cb.Cpc_Cod AND cp.Com_Est = 'A') AS fecha_abono
            FROM comprobantes c
            JOIN tipo_asien tia ON c.Tia_Cod = tia.Tia_Cod
            JOIN ccpp_cobrar cb ON c.Com_Cod = cb.Com_Cod
            JOIN ventas v ON cb.Vet_Cod = v.Vet_Cod
            JOIN cliente cli ON v.Cli_Cod = cli.Cli_Cod
            JOIN persona p ON cli.Prs_Cod = p.Prs_Cod
            JOIN caja_aper ca ON v.Caj_Cod = ca.Caj_Cod
            JOIN puntos_imp pi ON ca.Pun_Cod = pi.Pun_Cod
            JOIN sucursal s ON pi.Suc_Cod = s.Suc_Cod
            LEFT JOIN autorizaci aut ON v.Aut_Cod = aut.Aut_Cod
            WHERE c.Com_Est = 'A' AND (v.Vet_Est = 'A' OR v.Vet_Est = 'E') AND s.Emp_Cod = '$Ses_Emp_Cod' AND c.Com_Fec BETWEEN '$ini' AND '$fin'
        ";
    } else { // Ccxpp
        $sql = "
            SELECT 
                CONCAT(tia.Tia_Abr,'-',IF(CHAR_LENGTH(MONTH(c.Com_Fec))=1, CONCAT('0',CAST(MONTH(c.Com_Fec) AS char)),CAST(MONTH(c.Com_Fec) AS char)),'-',CAST(c.Com_Num AS char)) AS no_compr,
                c.Com_Fec AS fecha_emis,
                v.Cop_Num AS no_documento,
                p.Prs_Ced AS ruc_cedula,
                CONCAT(p.Prs_Ape, ' ', p.Prs_Nom) AS cliente_proveedor,
                e.Emp_Nom AS sucursal,
                (SELECT ROUND(MAX(Asi_Val), 2) FROM asientos WHERE Com_Cod = c.Com_Cod AND Asi_Deh = 'H') AS total,
                (SELECT ROUND(SUM(d.Pag_Val), 2) FROM det_ccpp_p d JOIN comprobantes cp ON d.Com_Cod = cp.Com_Cod WHERE d.Cpp_Cod = cb.Cpp_Cod AND cp.Com_Est = 'A') AS abono,
                (SELECT MAX(cp.Com_Fec) FROM det_ccpp_p d JOIN comprobantes cp ON d.Com_Cod = cp.Com_Cod WHERE d.Cpp_Cod = cb.Cpp_Cod AND cp.Com_Est = 'A') AS fecha_abono
            FROM comprobantes c
            JOIN tipo_asien tia ON c.Tia_Cod = tia.Tia_Cod
            JOIN ccpp_pagar cb ON c.Com_Cod = cb.Com_Cod
            JOIN compras v ON cb.Cop_Cod = v.Cop_Cod
            JOIN proveedore prov ON v.Prv_Cod = prov.Prv_Cod
            JOIN persona p ON prov.Prs_Cod = p.Prs_Cod
            JOIN empresas e ON prov.Emp_Cod = e.Emp_Cod
            WHERE c.Com_Est = 'A' AND v.Cop_Est = 'A' AND prov.Emp_Cod = '$Ses_Emp_Cod' AND c.Com_Fec BETWEEN '$ini' AND '$fin'
        ";
    }

    $res = mysqli_query($conn, $sql);
    if($res) {
        while($row = mysqli_fetch_assoc($res)) {
            $total = (float)$row['total'];
            $abono = (float)$row['abono'];
            $saldo = round($total - $abono, 2);
            $fecha_emis = $row['fecha_emis'];
            $fecha_abono = $row['fecha_abono'];
            
            $has_fecha = false;
            $diff_dias = 0;
            if ($fecha_abono && $fecha_emis) {
                $ts_abono = strtotime($fecha_abono);
                $ts_emis = strtotime($fecha_emis);
                if ($ts_abono < $ts_emis) {
                    $diff_dias = round(($ts_emis - $ts_abono) / (60 * 60 * 24));
                    $has_fecha = true;
                }
            }
            
            // 2. Saldo descuadrado (Solo si el abono supera el total, es decir, saldo negativo)
            $has_saldo = false;
            if ($abono > $total && abs($total - $abono) > 0.009) {
                 $has_saldo = true;
            }
            
            $include = false;
            if ($tipo_error == 'Todos' && ($has_fecha || $has_saldo)) $include = true;
            else if ($tipo_error == 'Fecha' && $has_fecha) $include = true;
            else if ($tipo_error == 'Saldo' && $has_saldo) $include = true;
            
            if ($include) {
                $row['saldo'] = $saldo;
                if ($saldo != 0) {
                    $row['saldo'] = "<span style='color:red; font-weight:bold'>" . number_format($saldo, 2) . "</span>";
                } else {
                    $row['saldo'] = number_format($saldo, 2);
                }
                $row['total'] = number_format($total, 2);
                $row['abono'] = number_format($abono, 2);
                $doc_pad = str_pad($row['no_documento'], 9, "0", STR_PAD_LEFT);
                $ruc_cliente = $row['ruc_cedula'];
                $url_ccxcc = "tes_con_cccc_pagados.php?auto_ruc={$ruc_cliente}&auto_doc={$doc_pad}";
                $url_ccxpp = "tes_con_ccpp_1.0.php?auto_ruc={$ruc_cliente}&auto_doc={$doc_pad}";
                $url_consulta = ($modulo == 'Ccxcc') ? $url_ccxcc : $url_ccxpp;
                
                $color_lupa = "";
                $title_lupa = "";
                if ($has_fecha && $has_saldo) {
                    $color_lupa = "color: #DAA520;"; // Amarillo Mostaza
                    $title_lupa = "Fecha Inconsistente ({$diff_dias} días) y Saldo Descuadrado";
                } else if ($has_fecha) {
                    $color_lupa = "color: #337ab7;"; // Azul
                    $title_lupa = "Fecha Inconsistente ({$diff_dias} días)";
                } else if ($has_saldo) {
                    $color_lupa = "color: #d9534f;"; // Rojo
                    $title_lupa = "Saldo Descuadrado";
                }

                $lupa = "<a href='#' onclick=\"window.open('{$url_consulta}','_blank'); return false;\" title='{$title_lupa}' style='text-decoration:none; display:inline-block; font-size: 16px;'><span class='glyphicon glyphicon-search' style='cursor:pointer; {$color_lupa}'></span></a>";
                
                $row['error_badges'] = $lupa;
                $row['no_documento'] = $doc_pad;
                $rows[] = $row;
            }
        }
    }
    
    $responce['rows'] = $rows;
    $responce['success'] = true;
    $responce['records'] = count($rows);
    if(function_exists('utf8_encode_deep')) utf8_encode_deep($responce['rows']);
    echo json_encode($responce); exit();
}
?>
<!DOCTYPE html>
<HTML>
    <HEAD>      
        <TITLE>Consultar Comprobantes con Inconsistencia</TITLE>
        <meta charset= "UTF-8">
        <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php")?>              
    </HEAD>
<BODY>
    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo; Consultar Comprobantes con Inconsistencia</h3></div>
        
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                <div class="row">
                    <div class="col-xs-12">
                       <form id="formCompr" class="form-horizontal normal" action="javascript:ejecutarFiltro();">
                                <div class="row">
                                    <div class="col-xs-5">
                                      <fieldset class="exa-fieldset">                           
                                        <legend class="Titulos2">Configuración:</legend>
                                        <div class="form-group">
                                          <label class="col-sm-4 control-label label-sm">Módulo:</label>  
                                          <div class="col-sm-8"> 
                                              <select id="modulo" name="modulo" class="form-control input-sm" required>
                                                  <option value="Ccxcc">Ccxcc (Cuentas por Cobrar)</option>
                                                  <option value="Ccxpp">Ccxpp (Cuentas por Pagar)</option>
                                              </select>
                                          </div>                                  
                                        </div>
                                        <div class="form-group">
                                          <label class="col-sm-4 control-label label-sm">Tipo de error:</label>  
                                          <div class="col-sm-8"> 
                                              <select id="tipo_error" name="tipo_error" class="form-control input-sm" required>
                                                  <option value="Todos">Todos</option>
                                                  <option value="Fecha">Fecha inconsistente</option>
                                                  <option value="Saldo">Saldo descuadrado</option>
                                              </select>
                                          </div>                                  
                                        </div>
                                      </fieldset> 
                                    </div>
                                    <div class="col-xs-7">
                                        <fieldset class="exa-fieldset">                           
                                            <legend class="Titulos2">Filtros:</legend>
                                            <div class="row">
                                                <div class="col-xs-12">
                                                    <div class="form-group">
                                                        <label class="col-sm-2 control-label label-xs">Desde:</label>
                                                        <div class="col-sm-4">     
                                                            <input name="ini" type="text" id="ini" class="form-control input-sm" required>
                                                        </div>
                                                        <label class="col-sm-2 control-label label-xs">Hasta:</label>
                                                        <div class="col-sm-4">                                    
                                                            <input name="fin" type="text" id="fin" class="form-control input-sm" required>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <div class="col-xs-12 text-right">
                                                          <button type="submit" class="btn btn-sm btn-success" title="Ejecutar Búsqueda" style="margin-right: 15px;"><span class="glyphicon glyphicon-search"></span> &nbsp;Filtrar</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </fieldset> 
                                     </div>    
                                </div>    
                        </form>  
                    </div>
                    <div class="col-sm-12" style="min-height: 450px; margin-top: 15px;">
                        <table id="kardex"></table>
                        <div id="kardexPager"></div>
                        <div style="margin-top: 10px; font-size: 12px; color: #555;">
                            <strong>Leyenda:</strong> &nbsp;&nbsp;
                            <span class='glyphicon glyphicon-search' style='color: #337ab7;'></span> Fecha Inconsistente &nbsp;&nbsp;
                            <span class='glyphicon glyphicon-search' style='color: #d9534f;'></span> Saldo Descuadrado &nbsp;&nbsp;
                            <span class='glyphicon glyphicon-search' style='color: #DAA520;'></span> Ambos (Fecha y Saldo)
                        </div>
                        <script>
                             $(document).ready(function () {
                                $.createDateRange('#ini','#fin');
                                var date = new Date();
                                var firstDay = new Date(date.getFullYear(), date.getMonth(), 1);
                                $('#ini').datepicker("setDate", firstDay);
                                $('#fin').datepicker("setDate", date);

                                window.ejecutarFiltro = function() {
                                    var iniDate = $('#ini').datepicker('getDate');
                                    var finDate = $('#fin').datepicker('getDate');
                                    if(iniDate && finDate) {
                                        var diffTime = Math.abs(finDate - iniDate);
                                        var diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                                        if(diffDays > 366) {
                                            alert('Por razones de rendimiento, por favor seleccione un rango de fechas no mayor a 1 año.');
                                            return false;
                                        }
                                    }
                                    $('#kardex').jqGrid('setCaption', 'Desde '+ $('#ini').val()+' Hasta '+$('#fin').val());
                                    $('#kardex').Search('#formCompr','ajaxCompr');
                                };

                                var kardexGrid=$("#kardex");
                                kardexGrid.jqGrid({
                                    url: '<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>',
                                    mtype: "GET", datatype: "local", regional : 'es',
                                    autowidth : true, shrinkToFit: true, height: 270, responsive:true,
                                    caption:' ', hidegrid:false,
                                    emptyrecords: "Sin Registros que Mostrar",
                                    loadonce: true,
                                    cmTemplate: {sortable:true},
                                    colModel: [                               
                                        { label: 'No. Compr.', name: 'no_compr', width: 40, align:'center' }, 
                                        { label: 'Fecha emis.', name: 'fecha_emis', width: 40, align:'center' }, 
                                        { label: 'Fecha abono', name: 'fecha_abono', width: 40, align:'center' },
                                        { label: 'No. Documento', name: 'no_documento', width: 60, align:"center" },
                                        { label: 'RUC / C.I.', name: 'ruc_cedula', width: 60, align:"center" },
                                        { label: 'Cliente / Proveedor', name: 'cliente_proveedor', width: 100 }, 
                                        { label: 'Sucursal', name: 'sucursal', width: 100 }, 
                                        { label: 'Total', name: 'total', width: 40, align:"right"},
                                        { label: 'Abono', name: 'abono', width: 40, align:"right"},
                                        { label: 'Saldo', name: 'saldo', width: 40, align:"right"},
                                        { label: 'Error', name: 'error_badges', width: 100, align:"center"}
                                    ],       
                                    rowNum: 50, rowList: [50, 100, 200, 500], pager: "#kardexPager", gridview: true, rownumbers: true, viewrecords: true
                                });                                  
                                kardexGrid.navGrid('#kardexPager',{ edit: false, add: false, del: false, search: false, refresh: true, view: false, position: "left", cloneToTop: false });
                            }); 
                        </script>    
                    </div>  
                </div> 
        </div>
    </div>
</BODY>
</HTML>
