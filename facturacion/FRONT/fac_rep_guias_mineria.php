<?php	


/**
* @abstract Permite realizar guias de remision
* @author Erik Niebla
* @version 1.0
* Fecha de creacin  2015-07-22
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_guia_remi.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

if(isset($doc_xml)){   
    header('Location: '."../FRONT/$Ses_Emp_Cod/{$doc_xml}_A.xml");
}
/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_G_Remi($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_G_Remi;

$hoy = date("Y-m-d");
$mes = date("m");

if(isset($searchDocument)){
    $obBD_con1->getPageGridJson(16, $_GET, $obBD_conexion);
}
if(isset($docDetalle)){
    $rows=$obBD_con1->getArrayConsulta(18, $Gui_Cod, $obBD_conexion);
    $obBD_con1->echoJson(array('success'=>true,'rows'=>$rows));
}
if(isset($ajaxSubgrid)){    
    $obBD_con1->echoJson($obBD_con1->getPageGridFormat(19, str_replace('_','*',$ajaxSubgrid), $obBD_conexion));
}
/* Consulta los totales */
if (isset($ajaxTotales)) {
    try {
        $data = $_GET;
        $data['Emp_Cod'] = $Ses_Emp_Cod;
        
        // Inicializar respuesta por defecto
        $responce = array('page' => 1, 'total' => 0, 'records' => 0, 'rows' => array());
        
        // Validar que existan las variables necesarias
        if (empty($Ses_Emp_Cod)) {
            throw new Exception('No se ha definido el código de empresa');
        }
        
        // Si el filtro de rango está marcado pero las fechas están vacías, desmarcar el filtro
        if (isset($data['range']) && $data['range'] == 'S' && (empty($data['Fec_Ini']) || empty($data['Fec_Fin']))) {
            unset($data['range']);
        }
        
        // Debug temporal - ver los parámetros
        error_log("ajaxTotales - Data: " . print_r($data, true));
        
        $responce = $obBD_con1->getPageGrid(27, $data, $obBD_conexion);
        
        // Debug temporal - ver la respuesta
        error_log("ajaxTotales - Response records: " . (isset($responce['records']) ? $responce['records'] : 'NO DEFINIDO'));
        error_log("ajaxTotales - Response rows count: " . (isset($responce['rows']) ? count($responce['rows']) : 'NO DEFINIDO'));
        
        // Asegurar que la respuesta tenga la estructura correcta
        if (!isset($responce) || !is_array($responce)) {
            $responce = array('page' => 1, 'total' => 0, 'records' => 0, 'rows' => array());
        }
        
        if (!isset($responce['records'])) {
            $responce['records'] = 0;
        }
        if (!isset($responce['rows'])) {
            $responce['rows'] = array();
        }
        if (!isset($responce['page'])) {
            $responce['page'] = 1;
        }
        if (!isset($responce['total'])) {
            $responce['total'] = 0;
        }
        
        if ($responce['records'] > 0 && is_array($responce['rows'])) {
            foreach ($responce['rows'] as &$row) {
                // Formatear fechas básicas (solo las que necesitamos ahora)
                if (!empty($row['Gui_Fec'])) {
                    $fecha = explode('-', $row['Gui_Fec']);
                    if (count($fecha) == 3) {
                        $row['Gui_Fec'] = $fecha[2] . '/' . $fecha[1] . '/' . $fecha[0];
                    }
                }
                if (!empty($row['Gui_Fei'])) {
                    $fecha = explode('-', $row['Gui_Fei']);
                    if (count($fecha) == 3) {
                        $row['Gui_Fei'] = $fecha[2] . '/' . $fecha[1] . '/' . $fecha[0];
                    }
                }
                if (!empty($row['Gui_Fef'])) {
                    $fecha = explode('-', $row['Gui_Fef']);
                    if (count($fecha) == 3) {
                        $row['Gui_Fef'] = $fecha[2] . '/' . $fecha[1] . '/' . $fecha[0];
                    }
                }
            }
            unset($row);
        }
        
        // Asegurar que la respuesta tenga success: true para indicar éxito
        $responce['success'] = true;
        
        $obBD_con1->echoJson($responce);
    } catch (Exception $e) {
        $error_msg = $e->getMessage();
        error_log("Error en ajaxTotales: " . $error_msg);
        $obBD_con1->echoJson(array(
            'success' => false,
            'message' => 'Error: ' . $error_msg,
            'page' => 1, 'total' => 0, 'records' => 0, 'rows' => array()
        ));
    }
}
$rs_periodo = $obBD_con1->getArrayConsulta(17, $Ses_Emp_Cod, $obBD_conexion); 
$imprimir=$obBD_con1->reportesExa($_SERVER['PHP_SELF'], $Ses_Emp_Cod, $obBD_conexion);
?>

<!DOCTYPE html>
<HTML>
    <HEAD>		
        <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
        <TITLE><?Php echo "Guias Consultar [EXA]"; ?></TITLE>
        <meta charset="utf-8">
        <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php")?>
        <script type="text/javascript">
            var consultar = true;
            
            // Interceptamos la creación del grid para modificar las columnas antes de que se dibuje
            var originalCreateGrid = $.fn.createGrid;
            $.fn.createGrid = function(options, pager, pagerId, extra) {
                if (this.attr('id') === 'searchGrid') {
                    options.colModel = [
                        { label: 'ID', name: 'Grid_Id', width: 30, key: true, hidden: true },
                        { label: 'Cód. Int.', name: 'Gui_Cod', width: 30, align: "center", hidden: true },
                        { label: 'Código', name: 'Der_Min_Codigo', width: 80, align: 'center' },
                        { label: 'Nombre del Derecho Minero', name: 'Der_Min_Nombre', width: 150 },
                        { label: 'Tipo de Derecho Minero', name: 'Der_Min_Tipo', width: 120 },
                        { label: 'Titular / Operador Minero', name: 'Der_Min_Titular_Operador', width: 150 },
                        { label: 'Periodo de reporte', name: 'Periodo_Reporte', width: 100, align: "center",
                             formatter: function(cellval, opts, rowObject) {
                                var meses = ['', 'ENERO', 'FEBRERO', 'MARZO', 'ABRIL', 'MAYO', 'JUNIO', 'JULIO', 'AGOSTO', 'SEPTIEMBRE', 'OCTUBRE', 'NOVIEMBRE', 'DICIEMBRE'];
                                return meses[parseInt(rowObject.Mes_Num)] || '';
                            }
                        },
                        { label: 'Tipo de recurso mineral', name: 'Der_Min_Recurso', width: 100 },
                        { label: 'Mineral', name: 'Gde_Des', width: 150 },
                        { label: 'Cantidad', name: 'Gde_Can', width: 80, align: "center" },
                        { label: 'Motivo del traslado', name: 'Gui_Mot', width: 100 },
                        { label: 'Lugar de origen', name: 'Gui_Dor', width: 150 },
                        { label: 'Lugar de destino', name: 'Gui_Dde', width: 150 },
                        { label: 'Fecha', name: 'Gui_Fec', width: 90, align: 'center' },
                        { label: 'Guía de remisión', name: 'Secuencia', width: 120, align: "center",
                             formatter: function(cellval, opts, rowObject) {
                                return '<a href="fac_con_guia_remi_2.0.php?Gui_Cod=' + rowObject.Gui_Cod + '" target="_blank" style="color:blue; text-decoration:underline;">' + cellval + '</a>';
                            }
                        },
                        { label: 'Comprobante de venta o factura', name: 'Tic_Des', width: 150 },
                        { label: 'Placa de vehículo', name: 'Gui_Pla', width: 80, align: "center" },
                        { label: 'Identificación del destinatario', name: 'Dest_Ced', width: 100, align: "center" }
                    ];
                }
                return originalCreateGrid.apply(this, arguments);
            };
        </script>
        <script type="text/javascript" src="../VALIDACIONES/fac_val_guia_remi_2.0.js?x=1"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.5/jszip.min.js"></script>
        <style>

        </style>
    </HEAD>
<BODY>
    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo; Consultar Guias de Remisión</h3></div>
        
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body"> 
            <div id="documentoSearch" class="ui-tabs ui-tab-fix noPaddingH">

                <div id="tabs-1" class="ui-tabs-panel">
                <form id="serachDocDorm" class="form-horizontal normal" action="javascript:$('#searchGrid').Search('#serachDocDorm','searchDocument');" >
                    <div class="row">
                        <div class="col-xs-6">
                            <fieldset class="exa-fieldset">
                                <legend class="Titulos2">Búsqueda</legend>
                                <div class="form-group">
                                <label class="col-xs-2 control-label label-xs">Filtrar Por:</label>  
                                <div class="col-xs-10 radioset opt_search">
                                      <input id="radsc2" name="op_opciones" type="radio" value="c" checked="" onclick="setOpt(this.value); setfocus(this.form.search)" alt="" /><label for="radsc2">&nbsp;&nbsp;&nbsp;Identificación Destinatario&nbsp;&nbsp;&nbsp;</label>
                                      <input id="radsc3" name="op_opciones" type="radio" value="d" onclick="setOpt(this.value); setfocus(this.form.search)" alt="" /><label for="radsc3">&nbsp;&nbsp;No. Documento&nbsp;&nbsp;</label>
                                      <input id="radsc4" name="op_opciones" type="radio" value="min" onclick="setOpt(this.value); setfocus(this.form.search)" alt="" /><label for="radsc4">&nbsp;&nbsp;Derecho Minero&nbsp;&nbsp;</label>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-2 control-label">B&uacute;squeda:</label>  
                                <div class="col-xs-7" >
                                    <div class="input-group">                        
                                    <input name="search" onkeydown="if (event.keyCode === 13) this.form.submit()" type="text" size="50" maxlength="50" placeholder="Ingrese búsqueda..." autofocus  class="form-control input-sm clearable submit"/>
                                    <span class="input-group-btn"><button type="button" onclick="this.form.submit()" class="btn btn-success btn-sm" title="Buscar Documento"  tabindex="-1"><span class="glyphicon glyphicon-search"></span> <span>Buscar</span></button></span>
                                  </div><!-- /input-group --> 
                                </div><input type="text" tabindex="-1" style="display:none;" />                    
                            </div>
                            </fieldset>
                        </div>
                        <div class="col-xs-6">
                            <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Filtros</legend>
                                <div class="form-group">
                                    <label class="col-xs-2 control-label label-xs">Estado:</label>&nbsp;
                                    <span class="radioset">
                                        <input id="op_est3" name="op_est" type="radio" value="T" style="cursor:pointer"><label for="op_est3"> Todas </label>
                                        <input id="op_est1" name="op_est" type="radio" value="A" checked='checked' style="cursor:pointer"><label for="op_est1"> Activas </label>
                                        <input id="op_est2" name="op_est" type="radio" value="I" style="cursor:pointer"><label for="op_est2">Anuladas</label>
                                    </span>
                                </div>
                                
                                <div class="form-group">
                                    <label class="col-xs-2 control-label label-xs">Periodo:</label>  
                                    <div class="col-xs-3" >
                                        <select name="Pec_Cod" class="form-control input-xs search_pec getData ins" onchange="if(this.value==='') $('#Cmb_Mes').attr('disabled','disabled'); else $('#Cmb_Mes').removeAttr('disabled'); ">
                                            <?php foreach($rs_periodo as $row){ echo "<option value='$row[Pec_Cod]' data--Year='$row[Periodo]'>$row[Periodo]</option>"; } ?>
                                            <option value=""><< TODOS >></option>
                                        </select>
                                    </div> 
                                    <label class="col-xs-2 control-label label-xs">Mes:</label>  
                                    <div class="col-xs-3" >
                                        <select id="Cmb_Mes" name="Cmb_Mes" class="form-control input-xs search_pec">
                                            <option value=""><< TODOS >></option>
                                            <?Php  for ($i=1;$i<=12;$i++){ ?><option <?php if ($i == $mes){ echo "selected=''"; } ?> value="<?Php echo $i; ?>"><?php echo mes($i, 1); ?></option><?Php } ?>
                                        </select>
                                    </div>                                    
                                </div> 
                            </fieldset>
                        </div>
                    </div>    
                </form> 
                <div style="min-height: 270px;">
                    <table id="searchGrid"></table>
                    <table id="searchGridPager"></table>
                    <div class="Titulos2"><span id="plan-footer"><strong>Leyenda:</strong> <span class="glyphicon glyphicon-remove red"></span> Anulados/Inactivos | <span class="glyphicon glyphicon-info-sign orange"></span> Autorización Pendiente | <span class="glyphicon glyphicon-stop green"></span> Guia Remision Autorizada </div>
                </div>

                <?php
                $empresa = $obBD_con1->getRowConsulta('empresas.selectWhere', array('where' => array('Emp_Cod' => $Ses_Emp_Cod)), $obBD_conexion);
                $Emp_Nom = $empresa['Emp_Nom'];
                ?>
                <div>
                    <!-- label que genera la decarga del pdf -->
                    <button type="button" onclick="descargarExcelEjecutivo()" class="btn btn-primary btn-sm" title="Descargar Excel"><i class="glyphicon glyphicon-download-alt"></i> <span>Descargar Excel</span></button>
                </div>
                </div>
            </div>
                <script>
                    function descargarExcelEjecutivo() {
                        var html = generarReporteHTML("searchGrid", {
                            titulo: "<?php echo $Emp_Nom; ?>",
                            subtitulo: "Reporte de Guías de Remisión",
                            excluirColumnas: [0]
                        });

                        if (html) {
                            var fechaActual = new Date().toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' }).replace(/\//g, '-');
                            var blob = new Blob(["\ufeff" + html], { type: "application/vnd.ms-excel;charset=utf-8" });
                            var url = URL.createObjectURL(blob);
                            var a = document.createElement("a");
                            a.href = url;
                            a.download = "Reporte_Guias_Mineria_" + fechaActual + ".xls";
                            a.click();
                            setTimeout(function(){ window.URL.revokeObjectURL(url); }, 100);
                        }
                    }

                    function verDocument(doc){
                        $('.btn-guia').hide().data(doc);
                        $('#formGuiaConsult').setData(doc);
                        
                        if(!$.isEmpty(doc['Gui_Xml'])){
                            $('#clave').html('CLAVE:&nbsp;<i style="font-weight:normal;">'+doc['Gui_Xml']+'</i>');
                            $('.elect').show();
                            if(doc['Gui_Aut']!=='S') $('#btnXML').hide();                             
                        }else{
                            $('#clave').html('');
                            $('.impr').show();
                        }
                        $.getDataJson('',{docDetalle:true,Gui_Cod:doc['Gui_Cod']},function(resp){
                            $('#documentoSearch').moveComp('#documentoMain').updateGridsSizes();
                            $("#guiasConsult").setRows(resp['rows']);
                        });
                    }

                    // Funcion para poder descargar todos los PDF's del gridview
                    function descargarPDF() {
                        var rows = $("#searchGrid").jqGrid("getDataIDs");
                        var currentDomain = window.location.origin;

                        // Muestra el loader
                        if (rows.length > 0) {
                            document.getElementById("loader").style.display = "block";
                            var pdfUrls = [];

                            for (var i = 0; i < rows.length; i++) {
                                var rowData = $("#searchGrid").jqGrid("getRowData", rows[i]);
                                var id = rowData.Gui_Cod;
                                if (id) {
                                    pdfUrls.push(id);
                                }
                            }

                            var zip = new JSZip();
                            var promises = pdfUrls.map(function(pdfUrl, index) {
                                return new Promise(function(resolve) {
                                    var xhr = new XMLHttpRequest();
                                    var link = currentDomain + '/facturacion/COMPONENTES/tesPdfElectronicos.php?type=GUIAS&Doc_Cod=' + pdfUrl;
                                    xhr.open("GET", link, true);
                                    xhr.responseType = "blob";

                                    xhr.onload = function() {
                                        if (xhr.status === 200 && xhr.response.size > 0) {
                                            var blob = xhr.response;
                                            zip.file("guia_" + pdfUrl + ".pdf", blob);
                                            resolve();
                                        } else {
                                            console.error("Failed to fetch PDF for Doc_Cod:", pdfUrl);
                                            resolve(); // Resolve even if there's an error to continue processing
                                        }
                                    };

                                    xhr.onerror = function() {
                                        console.error("Error occurred while fetching PDF for Doc_Cod:", pdfUrl);
                                        resolve(); // Resolve to avoid blocking other promises
                                    };

                                    xhr.send();
                                });
                            });

                            Promise.all(promises).then(function() {
                                zip.generateAsync({
                                    type: "blob"
                                }).then(function(content) {
                                    var link = document.createElement("a");
                                    link.href = window.URL.createObjectURL(content);
                                    link.download = "GuiasPDF.zip"; // se le asigna un nombre junto a la extension del archivo comprimido
                                    link.click();

                                    // Oculta el loader cuando todas las promesas se resuelven
                                    document.getElementById("loader").style.display = "none";
                                });
                            });
                        }
                    }
                </script>
            </div>  
            <div id="documentoMain" style="visibility: hidden;">
                <div id="formGuiaConsult" class="row form-horizontal normal">
                    <div class="col-xs-6">
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Guia Remisión</legend>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Documento:</label>  
                                <div class="col-xs-6" ><span class="form-control input-xs">GUIA DE REMISIÓN</span></div>                                                                                
                            </div> 
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Numero:</label>  
                                <div class="col-xs-4" ><span name="Secuencia" class="form-control input-xs"></span></div>                                                                                
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Fec. Emision:</label>  
                                <div class="col-xs-4" ><span name="Gui_Fec" class="form-control input-xs" style="text-align: center;"></span></div>                                                                                
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Autorizacion:</label>  
                                <div class="col-xs-7" ><span name="Aut_Sri" class="form-control input-xs"></span></div> 
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Observacion:</label>  
                                <div class="col-xs-9" ><span name="Gui_Obs" class="form-control input-lg textarea"></span></div>                    
                            </div>
                        </fieldset>
                    </div>    
                    <div class="col-xs-6">
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Datos Transportista</legend>
                            <div class="form-group trasportista">
                                <label class="col-xs-3 control-label label-xs">Cédula/RUC:</label>  
                                <div class="col-xs-6"><span name="Prs_Ced" class="form-control input-xs"></span></div>                                        
                            </div>
                            <div class="form-group trasportista">
                                <label class="col-xs-3 control-label label-xs">R.Social:</label>  
                                <div class="col-xs-9" ><span name="transportista" class="form-control input-xs"></span></div>                                                                                
                            </div>
                            <div class="form-group trasportista">
                                <label class="col-xs-3 control-label label-xs">Placa:</label>  
                                <div class="col-xs-4" ><span name="Gui_Pla" class="form-control input-xs"></span></div>                                                                                
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Dir. Salida:</label>  
                                <div class="col-xs-9" ><span name="Gui_Dor" class="form-control input-lg textarea"></span></div>                    
                            </div>
                        </fieldset>
                    </div>
                </div>
                <table id="guiasConsult"></table>
                <table id="guiasConsultPager"></table>
                <div style="padding-top: 10px;">
                    <button type="button" class="btn btn-inverse btn-sm" onclick="$('#documentoMain').moveComp('#documentoSearch').updateGridsSizes();" ><i class="glyphicon glyphicon-arrow-left"></i> Volver</button>
                    <button id="btnXML" type="button" class="btn btn-info btn-sm btn-guia elect" onclick="viewXml($(this).data());" ><i class="glyphicon glyphicon-paperclip"></i> Ver XML</button>
                    <button id="btnPDF" type="button" class="btn btn-info btn-sm btn-guia elect" onclick="viewPdf($(this).data());" ><i class="glyphicon glyphicon-file"></i> Ver PDF</button>
                    <button type="button" class="btn btn-success btn-sm btn-guia impr" onclick="$.imprimirUrl('<?Php if(isset($imprimir['1'])) echo $imprimir['1']; ?>?Gui_Cod='+$(this).data('Gui_Cod'));" ><i class="glyphicon glyphicon-print"></i> Imprimir</button>
                </div>
            </div>
        </div>
    </div>

   <script type="text/javascript">
        function setFilter(cl, $t) {
            var ch = $t.is(':checked');
            $('span.' + cl)[ch ? 'addClass' : 'removeClass']('alert-info');
            $('span.' + cl)[!ch ? 'addClass' : 'removeClass']('alert-disabled');
            $('input.' + cl).prop('required', ch).prop('disabled', !ch);
            $('div.form-group.' + cl)[!ch ? 'hide' : 'show']();
        }

        function selectTrans(trans) {
            $('div.form-group.cedul').setData($.extend(trans, {
                op_opciones: 'c'
            })).find('.dialogSearch').addClass('x');
            $('#transDialog').dialog('close');
        }

        $(function() {
            $('#documentoSearch').createTabs();
            $("#tabs-1").show();
            $("#tabs-2").hide();
            $.createDateRange("#txt_fec_ini", "#txt_fec_fin");

            var ReportResumen = $("#ReportResumen");
            ReportResumen.createGrid({
                height: 230,
                autowidth: true,
                shrinkToFit: false,
                url: '',
                mtype: 'GET',
                datatype: 'local',
                stateCol: 'Gui_Est',
                caption: 'Resultados de la b&uacute;squeda ',
                data: [],
                colModel: [
                    { label: 'ID', name: 'Grid_Id', width: 30, key: true, hidden: true },
                    { label: 'Est.', name: 'Gui_Est', width: "30px", align: "center", hidden: true }, // Oculto visualmente
                    
                    { label: 'Código', name: 'Der_Min_Codigo', width: "80px", align: "center" },
                    { label: 'Nombre del Derecho Minero', name: 'Der_Min_Nombre', width: "150px" },
                    { label: 'Tipo de Derecho Minero', name: 'Der_Min_Tipo', width: "120px" },
                    { label: 'Titular / Operador Minero', name: 'Der_Min_Titular_Operador', width: "150px" },
                    { label: 'Periodo de reporte', name: 'Periodo_Reporte', width: "100px", align: "center",
                        formatter: function(cellval, opts, rowObject) {
                            var meses = ['', 'ENERO', 'FEBRERO', 'MARZO', 'ABRIL', 'MAYO', 'JUNIO', 'JULIO', 'AGOSTO', 'SEPTIEMBRE', 'OCTUBRE', 'NOVIEMBRE', 'DICIEMBRE'];
                            return meses[rowObject.Mes_Num] + ' ' + rowObject.Anio_Num;
                        }
                    },
                    { label: 'Tipo de recurso mineral', name: 'Der_Min_Recurso', width: "100px" },
                    { label: 'Mineral', name: 'Gde_Des', width: "200px" },
                    { label: 'Cantidad', name: 'Gde_Can', width: "80px", align: "center" },
                    { label: 'Motivo del traslado', name: 'Gui_Mot', width: "150px" },
                    { label: 'Lugar de origen', name: 'Gui_Dor', width: "150px" },
                    { label: 'Lugar de destino', name: 'Gui_Dde', width: "150px" },
                    { label: 'Fecha', name: 'Gui_Fec', width: "90px", align: "center" },
                    { label: 'Guía de remisión', name: 'Secuencia', width: "120px", align: "center",
                         cellattr: function(rowId, val, rawObject, cm, rdata) {
                            return 'style="' + excelFormats.text + '"';
                        }
                    },
                    { label: 'Comprobante de venta o factura', name: 'Tic_Des', width: "150px" },
                    { label: 'Placa de vehículo', name: 'Gui_Pla', width: "80px", align: "center" },
                    { label: 'Identificación del destinatario', name: 'Dest_Ced', width: "120px", align: "center",
                         cellattr: function(rowId, val, rawObject, cm, rdata) {
                            return 'style="' + excelFormats.text + '"';
                        }
                    }
                ],
                footerrow: true,
                rowNum: 1000,
                rowList: [1000, 5000, 10000, 15000, 20000],
                userDataOnFooter: true,
                totalPage: true
            }, false, "ReportResumenPager").jqGrid('setFrozenColumns').gridButtonsAdd([
                { caption: "Exportar Excel&nbsp;", buttonicon: "download-alt",
                    onClickButton: function() {
                        var html = generarReporteHTML("ReportResumen", {
                            <?php
                            $empresa = $obBD_con1->getRowConsulta('empresas.selectWhere', array('where' => array('Emp_Cod' => $Ses_Emp_Cod)), $obBD_conexion);
                            $Emp_Nom = $empresa['Emp_Nom'];
                            ?>
                            titulo: "<?php echo $Emp_Nom; ?>",
                            subtitulo: "Reporte de Guías de Remisión",
                            excluirColumnas: [0, 1, 22]
                        });

                        if (html) {
                            var fechaActual = new Date().toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' }).replace(/\//g, '-');
                            var blob = new Blob(["\ufeff" + html], { type: "application/vnd.ms-excel;charset=utf-8" });
                            var url = URL.createObjectURL(blob);
                            var a = document.createElement("a");
                            a.href = url;
                            a.download = "GuiasRemision-" + fechaActual + ".xls";
                            a.click();
                            URL.revokeObjectURL(url);
                        }
                    }
                },
                { caption: "Imprimir&nbsp;", buttonicon: "print",
                    onClickButton: function() {
                        var html = generarReporteHTML("ReportResumen", {
                            <?php
                            $empresa = $obBD_con1->getRowConsulta('empresas.selectWhere', array('where' => array('Emp_Cod' => $Ses_Emp_Cod)), $obBD_conexion);
                            $Emp_Nom = $empresa['Emp_Nom'];
                            ?>
                            titulo: "<?php echo $Emp_Nom; ?>",
                            subtitulo: "Reporte de Guías de Remisión",
                            excluirColumnas: [0, 1, 22]
                        });

                        if (html) {
                            var win = window.open('', '_blank');
                            win.document.write(html);
                            win.document.close();
                            win.focus();
                            win.print();
                        }
                    }
                }
            ]);
        });

        function generarReporteHTML(gridId, opciones) {
            var grid = $("#" + gridId);
            var gridData = grid.jqGrid('getRowData');

            if (gridData.length === 0) {
                $.alert('No hay datos para procesar.');
                return null;
            }

            var excludedIndexes = opciones.excluirColumnas || [];
            var titulo = opciones.titulo || "Reporte";
            var subtitulo = opciones.subtitulo || "Reporte de Guías de Remisión";
            var colModel = grid.jqGrid('getGridParam', 'colModel');

            var htmlContent = '<html><head><title>' + titulo + '</title>';
            htmlContent += '<style>';
            htmlContent += '@media print { tfoot {display: none !important;} }';
            htmlContent += 'body { font-family: "Calibri", "Arial", sans-serif; font-size: 11px; }';
            htmlContent += 'table {width: 100%; border-collapse: collapse; border: 1px solid #000;}';
            htmlContent += 'th, td {border: 0.5pt solid #000; padding: 5px; vertical-align: middle;}';
            htmlContent += 'th {background-color: #2F75B5; color: white; text-align: center; font-weight: bold; height: 30px;}';
            htmlContent += 'tr:nth-child(even) {background-color: #DDEBF7;}';
            htmlContent += '.ajustar-texto { word-break: break-word; white-space: normal; }';
            htmlContent += '.formato-texto { mso-number-format:"\\@"; }';
            htmlContent += '</style>';
            htmlContent += '</head><body>';

            htmlContent += '<h2 style="text-align: center;">' + titulo + '</h2>';
            htmlContent += '<h3 style="text-align: center;">' + subtitulo + '</h3>';
            if ($('input[name="Fec_Ini"]').val() || $('input[name="Fec_Fin"]').val()) {
                const formatDate = (date) => {
                    const [year, month, day] = date.split('-');
                    return `${day}-${month}-${year}`;
                };
                const formattedStartDate = $('input[name="Fec_Ini"]').val() ? formatDate($('input[name="Fec_Ini"]').val()) : '';
                const formattedEndDate = $('input[name="Fec_Fin"]').val() ? formatDate($('input[name="Fec_Fin"]').val()) : '';
                htmlContent += '<p style="text-align: center;"><strong>Desde:</strong> ' + formattedStartDate + ' &nbsp;&nbsp;&nbsp; <strong>Hasta:</strong> ' + formattedEndDate + '</p>';
            }

            htmlContent += '<table><thead><tr>';
            htmlContent += '<th>#</th>';

            var includedColumns = [];
            colModel.forEach(function(col, idx) {
                if (!col.hidden && excludedIndexes.indexOf(idx) === -1) {
                    htmlContent += '<th>' + col.label + '</th>';
                    includedColumns.push({
                        name: col.name,
                        isText: ['Dest_Ced', 'Gui_Aut', 'Vet_Aut', 'Secuencia', 'Vet_Secuencia', 'Der_Min_Codigo'].includes(col.name)
                    });
                }
            });

            htmlContent += '</tr></thead><tbody>';

            gridData.forEach(function(row, idx) {
                htmlContent += '<tr>';
                htmlContent += '<td>' + (idx + 1) + '</td>';

                includedColumns.forEach(function(col) {
                    var estilo = '';
                    if (['Gui_Aut', 'Vet_Aut', 'Dest_Ced', 'Secuencia', 'Vet_Secuencia', 'Der_Min_Codigo'].includes(col.name)) {
                        estilo = "class='formato-texto ajustar-texto' style='mso-number-format:\"\\@\";'";
                    } else if (['Gui_Dor', 'Gui_Dde', 'Gui_Mot', 'Gui_Obs', 'Der_Min_Nombre', 'Der_Min_Tipo', 'Der_Min_Titular_Operador', 'Der_Min_Recurso', 'Gde_Des', 'Gde_Can', 'Tic_Des', 'Gui_Pla'].includes(col.name)) {
                        estilo = 'class="ajustar-texto"';
                    } else if (col.isText) {
                        estilo = 'class="formato-texto"';
                    }
                    htmlContent += '<td ' + estilo + '>' + (row[col.name] || '') + '</td>';
                });

                htmlContent += '</tr>';
            });

            htmlContent += '</tbody></table>';
            htmlContent += '<div style="text-align: right; margin-top: 20px;">Generado el ' + new Date().toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' }).replace(/\//g, '-') + ' por EXA [Software Contable]</div>';
            htmlContent += '</body></html>';

            return htmlContent;
        }
   </script>
    <!--INICIO DEL DIALOGO BUSCAR TRANSPORTISTA-->
    <div id="transDialog" title="B&uacute;squeda de Transportista">
        <form class="form-horizontal normal"> </form>
    </div>
    <script>
        $(document).ready(function() {
            $('#transDialog').createSearchDialog({
                colModel: [{
                        label: 'C&oacute;d.Int.',
                        name: 'Gpe_Cod',
                        key: true,
                        width: 15,
                        align: "center",
                        hidden: true
                    },
                    {
                        label: 'C&eacute;dula/RUC',
                        name: 'Prs_Ced',
                        width: 80,
                        align: "center"
                    },
                    {
                        label: 'Transportista',
                        name: 'transportista',
                        width: 200
                    },
                    {
                        label: '<center><i class="ui-icon ui-icon-gear"></i></center>',
                        name: 'act1',
                        width: 20,
                        align: 'center',
                        viewable: false,
                        formatter: 'gridButton',
                        formatoptions: {
                            action: 'selectTrans',
                            data: ['Gpe_Cod', 'Prs_Ced', 'transportista']
                        }
                    }
                ]
            }, {
                title: 'Transportista',
                options: [{
                    label: '&nbsp;&nbsp;Transportista&nbsp;&nbsp;',
                    value: 'p'
                }, {
                    label: '&nbsp;&nbsp;C&eacute;dula/RUC&nbsp;&nbsp;',
                    value: 'c'
                }],
                consulta: 3,
                Emp_Cod: <?php echo $Ses_Emp_Cod; ?>,
                Gpe_Tip: 'T'
            });
        });
    </script>
</BODY>
</HTML>
