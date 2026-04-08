<?php	


/**
* @abstract Permite realizar guias de remision
* @author Erik Niebla
* @version 1.0
* Fecha de creaci�n  2015-07-22
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
$rs_periodo = $obBD_con1->getArrayConsulta(17, $Ses_Emp_Cod, $obBD_conexion);
$rs_sucursal = $obBD_con1->getArrayConsulta(25, $Ses_Emp_Cod, $obBD_conexion);
$imprimir=$obBD_con1->reportesExa($_SERVER['PHP_SELF'], $Ses_Emp_Cod, $obBD_conexion);
?>

<!DOCTYPE html>
<HTML>
    <HEAD>		
        <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
        <TITLE><?Php echo "Guias Consultar [EXA]"; ?></TITLE>
        <meta charset="utf-8">
        <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php")?>
        <script type="text/javascript">var consultar=true;</script>
        <script type="text/javascript" src="../VALIDACIONES/fac_val_guia_remi_2.0.js?x=2"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.5/jszip.min.js"></script>
        <style>

        </style>
    </HEAD>
<BODY>
    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo; Consultar Guias de Remisión</h3></div>
        
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body"> 
            <div id="documentoSearch">
                <form id="serachDocDorm" class="form-horizontal normal" action="javascript:$('#searchGrid').Search('#serachDocDorm','searchDocument');" >
                    <div class="row">
                        <div class="col-xs-6">
                            <fieldset class="exa-fieldset">
                                <legend class="Titulos2">Búsqueda</legend>
                                <div class="form-group">
                                <label class="col-xs-2 control-label label-xs">Filtrar Por:</label>  
                                <div class="col-xs-10 radioset opt_search">
                                    <input id="radsc1" name="op_opciones" type="radio" value="p" checked="" onclick="setOpt(this.value); setfocus(this.form.search)" alt="" /><label for="radsc1">&nbsp;&nbsp;&nbsp;Transportista&nbsp;&nbsp;&nbsp;</label>
                                    <input id="radsc2" name="op_opciones" type="radio" value="c" onclick="setOpt(this.value); setfocus(this.form.search)" alt="" /><label for="radsc2">&nbsp;&nbsp;&nbsp;C&eacute;dula/RUC&nbsp;&nbsp;&nbsp;</label>
                                    <input id="radsc3" name="op_opciones" type="radio" value="d" onclick="setOpt(this.value); setfocus(this.form.search)" alt="" /><label for="radsc3">&nbsp;&nbsp;No. Documento&nbsp;&nbsp;</label>
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
                                    <label class="col-xs-3 control-label label-xs">Sucursal:</label>
                                    <div class="col-xs-9" >
                                        <select name="Suc_Cod" class="form-control input-xs">
                                            <option value=""><< TODOS >></option>
                                            <?php foreach($rs_sucursal as $row){ echo "<option value='$row[Suc_Cod]'>$row[Suc_Des]</option>"; } ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-4 control-label label-xs">Periodo:</label>  
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

                <div>
                    <!-- label que genera la decarga del pdf -->
                    <button type="button" onclick="descargarPDF()" class="btn btn-success btn-sm" title="Descargar pdfs"><i class="glyphicon glyphicon-download-alt"></i> <span>Descargar PDF's</span></button>
                </div>
                <script>
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
                        </fielset>
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
                                <label class="col-xs-3 control-label label-xs">Fechas:</label>  
                                <div class="col-xs-9" >
                                    <div class="input-group input-group-xs">
                                        <span class="input-group-addon bold">Salida:</span>
                                        <span name="Gui_Fei" class="form-control span" style="text-align: center;" ></span>
                                        <span class="input-group-addon bold">Arrivo:</span>
                                        <span name="Gui_Fef" class="form-control span" style="text-align: center;" ></span>
                                    </div>
                                </div>    
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Dir. Salida:</label>  
                                <div class="col-xs-9" ><span name="Gui_Dor" class="form-control input-lg textarea"></span></div>                    
                            </div>
                        </fielset>
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

    <script type="text/javascript"></script>
    <script type="text/javascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
</BODY>
</HTML>