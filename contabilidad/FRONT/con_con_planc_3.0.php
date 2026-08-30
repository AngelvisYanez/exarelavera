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
require_once('../LOGICA/con_log_planc_2.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/**
* Creacion del Objeto de conexion
*/
$obBD_conexion = new Class_Log_Conexion_Con($Ses_Dat_Dis);
/**
* Creacion del objeto mysql para las consultas
*/
$obBD_con1 =  new Class_Log_Datos_Con;

/*Secci�n para listar plan decuentas de la empresa*/
if (isset($planAjax)) {
$obBD_con1->getPageGridJson(352,$_GET, $obBD_conexion,true);
}

/*Secci�n para listar plan de cuentas repetido de la empresa*/
if (isset($planRepAjax)) {
$obBD_con1->getPageGridJson(354,$_GET, $obBD_conexion,true);
}
?>
<!DOCTYPE HTML>
<HTML>
<HEAD>
  <TITLE><?php echo $Ses_Sys_Nom; ?></TITLE>
  <?php
  $mask_model = 'model1';
  require_once("../../mascaras/unified-loader.php");
  ?>
</HEAD>
<BODY>

<div class="panel panel-main">
          <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Consultar Plan de cuentas</h3></div>
          <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
              <div id="lista" class="row">
                  <div class="col-md-12">
                      <form id="frm_bus" name="frm_bus" class="form-horizontal normal" action="javascript:$('#Lis_Pla').Search('#frm_bus','planAjax');">
                          <fieldset class="exa-fieldset">
                              <legend class="Titulos2">B&uacute;squeda de Plan de Cuentas</legend>
                              <div class="col-sm-6">
                                  <div class="form-group">
                                  <label class="col-sm-2 control-label label-xs">Filtrar por:</label>
                                  <div class="col-sm-4 radioset">
                                      <input id="rad_ba1" name="pc_opciones" type="radio" value="a" checked="" onclick="setfocus(this.form.search)"/><label for="rad_ba1">Descripci&oacute;n</label>
                                      <input id="rad_ba2" name="pc_opciones" type="radio" value="b" onclick="setfocus(this.form.search)"/><label for="rad_ba2">C&oacute;digo</label>
                                  </div>
                              </div>

                              <div class="form-group">
                                  <label class="col-sm-2 control-label label-xs">B&uacute;squeda:</label>
                                  <div class="col-sm-8">
                                      <div class="input-group">
                                          <input id="search" type="text" id="search" name="search" onkeydown="if (event.keyCode === 13)
                                              this.form.submit()" class="form-control input-xs" placeholder="Ingrese &iacute;ndice de b&uacute;squeda" autofocus="">
                                          <span class="input-group-btn">
                                              <button id="btsearch" class="btn btn-success btn-xs" type="button" title="Buscar Cliente" onclick="this.form.submit()"><span class="glyphicon glyphicon-search"></span> Buscar</button>
                                          </span>
                                      </div>
                                  </div>
                              </div>
                              </div>

                              <div class="exa-fieldset col-sm-3">
                                  <?php
                                  $row_plan = $obBD_con1->getRowConsulta(353, $Ses_Emp_Cod,$obBD_conexion);

                                  ?>

                                  <legend class="Titulos2" style="text-align: center;"><?php echo $row_plan['Pla_Obs'];?></legend>

                                  <label class="col-sm-7 label-xs">C&oacute;digo: <?php echo $row_plan['Pla_Cod'];?> <p style="margin-left: 10%;display: inline;">Estado: <?php echo $row_plan['Pla_Est'];?></p> </label>
                                  <label class="col-sm-7 label-xs">Fecha: <?php echo $row_plan['Pla_Fec'];?></label>
                                  <?php
                                  $row_rep_plan = $obBD_con1->getRowConsulta(355, $Ses_Emp_Cod,$obBD_conexion);
                                  if ($row_rep_plan['cuenta']!=0){
                                  ?>
                                  <div class="col-sm-12 label-xs">
                                      <label class="text-danger">Existen <?php echo $row_rep_plan['cuenta'];?> codigos repetidos</label>
                                      <button onclick="verRepetidos()" type="button" class="btn btn-primary btn-xs" title="ver"><span class="glyphicon glyphicon-eye-open" aria-hidden="true"></span></button>
                                      <button onclick="ocultarRepetidos()" id="ocultar" type="button" class="btn btn-danger btn-xs hidden" title="ocultar"><span class="glyphicon glyphicon-remove" aria-hidden="true"></span></button>
                                  </div>
                                  <?php } ?>
                              </div>
                          </fieldset>
                      </form>
                      <div style="min-height:500px;" class="" id="tot_reg">
                          <table id="Lis_Pla"></table>
                          <div id="Pag_Pla"></div>
                          <div class="Titulos2"><span id="plan-footer"><strong>Leyenda:</strong> <span class="glyphicon glyphicon-stop red"></span> Anulados/Inactivos </div>
                          <div style="padding-top: 10px; padding-bottom: 0px;">
                              <button type="button" onclick="imprimir('Lis_Pla')" class="btn btn-primary btn-sm start" title="Imprimir registros"><i class="glyphicon glyphicon-print"></i> <span>Imprimir</span></button>
                              <button type="button" onclick="exportar('Lis_Pla')" class="btn btn-primary btn-sm start" title="Exportar registros"><i class="glyphicon glyphicon-download-alt"></i> <span>Exportar</s-printpan></button>
                              <form action="con_pri_planc_2.0.php" method="post" name= "form1" target="_blank" style="display: inline;">
                                  <button type="button" class="btn btn-primary btn-sm start" title="Imprimir Plan de Cuentas" onclick="this.form.submit()"><i class="glyphicon glyphicon-align-left"></i> <span>Imprimir Todos</span> </button>
                                  <input type="hidden" name="codigo" id="codigo" value="<?php echo $row_plan['Pla_Cod']; ?>" />
                              </form>
                          </div>
                      </div>
                      <div style="min-height:500px; visibility: hidden;" class="" id="rep_reg">
                          <form id="frm_rep" name="frm_rep" class="form-horizontal normal" action="javascript:$('#Lis_Rep').Search('#frm_rep','planRepAjax');">
                             <input type="hidden" name="codigo" id="codigo" value="<?php echo $row_plan['Pla_Cod']; ?>" />
                          </form>
                          <table id="Lis_Rep"></table>
                          <div id="Pag_Rep"></div>
                          <div class="Titulos2"><span id="plan-footer"><strong>Leyenda:</strong> <span class="glyphicon glyphicon-stop red"></span> Anulados/Inactivos </div>
                          <div style="padding-top: 10px; padding-bottom: 0px;">
                              <button type="button" onclick="imprimir('Lis_Rep')" class="btn btn-primary btn-sm start" title="Imprimir registros"><i class="glyphicon glyphicon-print"></i> <span>Imprimir</span></button>
                              <button type="button" onclick="exportar('Lis_Rep')" class="btn btn-primary btn-sm start" title="Exportar registros"><i class="glyphicon glyphicon-download-alt"></i> <span>Exportar</s-printpan></button>
                              <form action="con_pri_planc_2.0.php" method="post" name= "form1" target="_blank" style="display: inline;">
                                  <button type="button" class="btn btn-primary btn-sm start" title="Imprimir Plan de Cuentas" onclick="this.form.submit()"><i class="glyphicon glyphicon-align-left"></i> <span>Imprimir</span> </button>
                                  <input type="hidden" name="codigo" id="codigo" value="<?php echo $row_plan['Pla_Cod']; ?>" />
                              </form>
                          </div>
                      </div>
                  </div>
              </div>
          </div>
      </div>
  <!-- contenedores  -->
  <div id="imprimir" style="display: none;">
      <div style="width: 1030px;;">
          <div style="margin-left:5%;">
              <?php echo $obBD_con1->getReportHeader($Ses_Suc_Cod, '<p style="margin-left:10%;">REPORTE DE PLAN DE CUENTAS</p>', '<span style="margin-left:10%;" class="subtitle">Total de registros</span>', $obBD_conexion,false,1) ?>
          </div>
          <table id="tablaReporte" cellspacing="0" cellpadding="0" style="width: 700px; border-collapse: collapse;table-layout: fixed;"></table>
          <?php echo $obBD_con1->pieReporteStandar($Ses_Suc_Cod,$Ses_Usu_Cod,$obBD_conexion,false,1); ?>
      </div>
  </div>
  <div id="exportar" style="display: none;">
      <?php echo $obBD_con1->getReportHeader($Ses_Suc_Cod, '<p style="margin-left:10%;">REPORTE DE PLAN DE CUENTAS</p>', '<span style="margin-left:10%;" class="subtitle">Total de registros</span>', $obBD_conexion,false,5) ?>
      <table id="tablaExporta" cellspacing="0" cellpadding="0" style="width: 1030px; border-collapse: collapse;table-layout: fixed;"></table>
  </div>
  <!--kk-->

	<script>
  // funcion para ver tabla con registros trepetidos
	function verRepetidos(){
		$("#search").prop('disabled', true);
		$("#btsearch").prop('disabled', true);

		$("#ocultar").removeClass("hidden");
                $("#tot_reg").moveComp("#rep_reg").updateGridsSizes();
		//$("#tot_reg").addClass("hidden");
		//$("#rep_reg").removeClass("hidden");
		
	}

	// funcion para ocultar tabla con registros repetidos
	function ocultarRepetidos(){
		$("#search").prop('disabled', false);
		$("#btsearch").prop('disabled', false);
		$("#ocultar").addClass("hidden");
                $("#rep_reg").moveComp("#tot_reg").updateGridsSizes();
		//$("#tot_reg").removeClass("hidden");
		//$("#rep_reg").addClass("hidden");
	}
	function imprimir(tab_id){
            var contenido= document.getElementById(tab_id).innerHTML;
            var contenidoOriginal = document.body.innerHTML;
		$('#tablaReporte').html($('#'+tab_id).jqGrid('exportGridInnerHTML',{footer:true,generated:false,removeHiddens:true,removeCols:[1]}));
		$('#imprimir').printElement();
	}

	//              funcion para exportar Excel con registros de la tabla actual
	function exportar(tab_id){
		$('#tablaExporta').html($('#'+tab_id).jqGrid('exportGridInnerHTML',{footer:true,bodyBorder:false,removeHiddens:true,removeCols:[1]}));
		$.downloadFile($.exportarExcelBlob($('#exportar').html(), 'Plan_cuentas'), 'Plan_cuentas_' + $.getDate() + '.xls');
	}
	$(function(){
            //$("#rep_reg").addClass("hidden");
		//Inicio Grid para presentar registros del plan de cuentas de la empresa
		$("#Lis_Pla").createGrid({
			postData: $("#frm_bus").getData("planAjax"), height: 400,
			colModel: [
				{label: 'Cod. Int.', name: 'Pld_Cod', width: 50, align: "left"},
				{label: 'C&oacute;digo', name: 'Pld_Cdc', width: 50, align: "left"},
				{label: 'Cuenta Contable', name: 'Pld_Des', width: 250, align: "left"},
				{label: 'Tipo', name: 'Pld_Tip', width: 50, align: "left"},
				{label: 'Estado', name: 'Pld_Est', width: 50, align: "left"}
			],
			rowNum:2500,
			loadComplete: function(planAjax){
                            $('#Lis_Pla tr').each(function () {
                                if($(this).find("td").eq(5).text()==="Inactivo"){
                                    $(this).addClass("cellRed2");
                                    $(this).addClass("myAltRowClass");
                                }
                            });
			}
		}, false, "#Pag_Pla");
                $("#Lis_Rep").createGrid({
			postData: $("#frm_rep").getData("planRepAjax"), height: 400,
			colModel: [
				{label: 'Cod. Int.', name: 'Pld_Cod', width: 50, align: "left"},
				{label: 'C&oacute;digo', name: 'Pld_Cdc', width: 50, align: "left"},
				{label: 'Cuenta Contable', name: 'Pld_Des', width: 250, align: "left"},
				{label: 'Tipo', name: 'Pld_Tip', width: 50, align: "left"},
				{label: 'Estado', name: 'Pld_Est', width: 50, align: "left"}
			],
			rowNum:400,
			loadComplete: function(planRepAjax){
				$('#Lis_Rep tr').each(function () {
                                if($(this).find("td").eq(5).text()==="Inactivo"){
                                    $(this).addClass("cellRed2");
                                    $(this).addClass("myAltRowClass");
                                }
                            });
			}
		}, false, "#Pag_Rep");
                $("#rep_reg").css('visibility','').hide();
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
