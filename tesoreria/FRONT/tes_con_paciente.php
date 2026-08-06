<?php
/**
 * Permite visualizar datos de Clientes
 *
 * @author car.87cod :)
 * @version 1.0
 * Fecha de actualizaci�n:	2012-04-26
 * @author lewis.chimarro
 * @version 1.0
 * Fecha de actualizaci�n:	2014-05-29 
 *
 * @package tesoreria.FRONT
 */

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tes_log_paciente.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/**
 * objeto para la conexion
 * @var Class_Log_Conexion_Tes
 */
$obBD_conexion = new Class_Log_Conexion_Cli($Ses_Dat_Dis);

/**
 * objeto para consultas
 * @var Class_Log_Datos_Tes
 */
$obBD_con1 =  new Class_Log_Datos_Cli;

/*Secci�n para listar los clientes registrados dentro de la empresa*/
if (isset($pacientesAjax)) {  
	$obBD_con1->getPageGridJson(28, $_GET, $obBD_conexion);    
}

?>
<!DOCTYPE HTML>
<HTML>
<HEAD>
<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
		<link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
        <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
        <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
        <script type="text/javascript" src="../../framework/jquery/chosen/chosenDesc/chosenDesc.js"></script>
        <script type="text/javascript" src="../VALIDACIONES/tes_val_cliente.js?a=12"></script>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	</HEAD>
<BODY>

<div class="panel panel-main">
            <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  CONSULTAR PACIENTES</h3></div>
            <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                <div id="lista" class="row">
                    <div class="col-md-12">
                        <form id="frm_bus" name="frm_bus" class="form-horizontal normal" action="javascript:$('#Lis_Cli').Search('#frm_bus','pacientesAjax');">
                            <fieldset class="exa-fieldset">
                                <legend class="Titulos2">B&uacute;squeda de pacientes</legend>
                                <div class="form-group">
                                    <label class="col-sm-2 control-label label-xs">Filtrar por:</label>
                                    <div class="col-sm-4 radioset">
                                        <input id="rad_ba1" name="op_opciones" type="radio" value="c" checked="" onclick="setfocus(this.form.search)"/><label for="rad_ba1">&nbsp;&nbsp;C&eacute;dula/R.U.C.&nbsp;&nbsp;</label>
                                        <input id="rad_ba2" name="op_opciones" type="radio" value="d" onclick="setfocus(this.form.search)"/><label for="rad_ba2">&nbsp;&nbsp;Cliente&nbsp;&nbsp;</label>
                                    </div>
                                    <div class="col-sm-4 radioset">
                                        <input id="rad_bb1" name="est_opciones" type="radio" value="a" checked="" onclick="setfocus(this.form.search)"/><label for="rad_bb1">Activo</label>
                                        <input id="rad_bb2" name="est_opciones" type="radio" value="i" onclick="setfocus(this.form.search)"/><label for="rad_bb2">Inactivo</label>
                                    </div>
                                </div>
						 
                                <div class="form-group">
                                    <label class="col-sm-2 control-label label-xs">B&uacute;squeda:</label>
                                    <div class="col-sm-5">
                                        <div class="input-group">
                                            <input type="text" id="search" name="search" onkeydown="if (event.keyCode === 13)
                                                this.form.submit()" class="form-control input-xs" placeholder="Ingrese &iacute;ndice de b&uacute;squeda" autofocus="">
                                            <span class="input-group-btn">
                                                <button class="btn btn-success btn-xs" type="button" title="Buscar Cliente" onclick="this.form.submit()"><span class="glyphicon glyphicon-search"></span> Buscar</button>
                                            </span>
                                        </div>
                                    </div>
                                </div>
<!--                                <div class="form-group">
                                    <label class="col-sm-2 control-label label-xs">Filtrar por estado:</label>
                                    
                                </div>-->
                            </fieldset>
                        </form>
                        <div style="min-height:300px;">
                            <table id="Lis_Cli"></table>
                            <div id="Pag_Cli"></div>
                            <div style="padding-top: 10px; padding-bottom: 0px;">
                                <button type="button" onclick="imprimir()" class="btn btn-primary btn-sm start" title="Imprimir registros"><i class="glyphicon glyphicon-print"></i> <span>Imprimir</span></button>
                                <button type="button" onclick="exportar()" class="btn btn-primary btn-sm start" title="Exportar registros"><i class="glyphicon glyphicon-download-alt"></i> <span>Exportar</s-printpan></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
		
    <!--kk-->
    <div id="imprimir" style="display: none;">
        <div style="width: 1030px;">
        <?php echo $obBD_con1->getReportHeader($Ses_Suc_Cod, 'REPORTE DE CLIENTES', '<span class="subtitle">Total de registros</span>', $obBD_conexion) ?>
        <table id="tablaReporte" cellspacing="0" cellpadding="0" style="width: 700px; border-collapse: collapse;table-layout: fixed;"></table>
        <?php echo $obBD_con1->getReportFooter($Ses_Suc_Cod,$Ses_Usu_Cod,$obBD_conexion); ?>
        </div>
    </div>
    <div id="exportar" style="display: none;">
        <?php echo $obBD_con1->getReportHeader($Ses_Suc_Cod, 'REPORTE DE CLIENTES', '<span class="subtitle">Total de registros</span>', $obBD_conexion, false, 5) ?>
        <table id="tablaExporta" cellspacing="0" cellpadding="0" style="width: 1030px; border-collapse: collapse;table-layout: fixed;"></table>
    </div>
    <!--kk-->
		<script>
                    function imprimir(){
                        $('#tablaReporte').html($('#Lis_Cli').jqGrid('exportGridInnerHTML',{footer:true,generated:false,removeHiddens:true,removeCols:[1]}));
                        $('#imprimir').printElement(); 
                    }
                    function exportar(){
                        $('#tablaExporta').html($('#Lis_Cli').jqGrid('exportGridInnerHTML',{footer:true,bodyBorder:false,removeHiddens:true,removeCols:[1]}));
                        $.downloadFile($.exportarExcelBlob($('#exportar').html(), 'Costos'), 'costos_' + $.getDate() + '.xls');
                    }
                    
		function cargarCliente(data){
			
		}
		$(function(){
                //Inicio Grid para presentar el detalle de factura
                $("#Lis_Cli").createGrid({
                    postData: $("#frm_bus").getData("pacientesAjax"), height: 295,
                    colModel: [
                        {label: 'Cod.', name: 'Pac_Cod', width: 30, align: "left"},
                        {label: 'C&eacute;dula/R.U.C.', name: 'Prs_Ced', width: 65, align: "left",
                            cellattr:function(){
                                return 'style="'+excelFormats.text+'"';
                            }
                        },
                        {label: 'Paciente', name: 'paciente', width: 200, align: "left"},
                        {label: 'Direcci&oacute;n', name: 'Prs_Dir', width: 200, align: "left"},
                        {label: 'Correo', name: 'Prs_Cor', width: 120, align: "left"},
			            {label: 'Tel&eacute;fono', name: 'Prs_Tel', width: 70, align: "left"},
                    ]
                }, false, "#Pag_Cli");
				
            });
		</script>
                <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
</BODY>
</HTML>
<?php
/**
* Cierre de las conexiones
*/
$obBD_con1->liberar(); 
$obBD_conexion->cerrar();
?>