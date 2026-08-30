<?php
/**
* Permite consultar cuentas y duplicados de los planes
 * de cuenta de la empresa.
*
* @author torresjorgev
* @version 3.1
* Fecha de actualizaci�n: 2018-04-20
* @package contabilidad.FRONT
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
    $obBD_con1->getPageGridJson(365,$_GET, $obBD_conexion);
    exit();
}

/*Secci�n para listar plan de cuentas repetido de la empresa*/
if (isset($getDuplicateds)){	
	$row_rep_plan=array();
    $row_rep_plan = $obBD_con1->getRowConsulta(367, $_GET, $obBD_conexion);
	$obBD_con1->echoLog($row_rep_plan);
    $row_rep_plan['success']=true;
    $row_rep_plan['rows']=$obBD_con1->getArrayConsulta(368,$_GET, $obBD_conexion);
    $obBD_con1->echoJson($row_rep_plan);
    exit();
}

//if (isset($changePlan)){
//    $plan = $obBD_con1->getRowConsulta(366, $_GET, $obBD_conexion);
//    $plan['success']=true;
//    $obBD_con1->echoJson($plan);
//    exit();
//}
//if (isset($planesAjax)){
//    $r=array('success'=>true, 'page'=>1, 'total'=>1, 'rows'=> $obBD_con1->getArrayConsulta(364,$_GET, $obBD_conexion) );
//    $r['records']=count($r['rows']);
//    $obBD_con1->echoJson($r);
//}

?>
<!DOCTYPE html>
<HTML>
<HEAD>
  <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
  <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php"); ?>
</HEAD>
<BODY>
<div class="panel panel-main">
    <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Consultar Plan de cuentas</h3></div>
    <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
        <div class="row">
			<div class="col-xs-12">
                <form id="frm_bus" name="frm_bus" class="form-horizontal normal" action="javascript:$('#Lis_Pla').Search('#frm_bus','planAjax');">
                    <fieldset class="exa-fieldset">
                        <legend class="Titulos2">B&uacute;squeda de Plan de Cuentas</legend>
                        <div class="col-xs-6">
                            <div class="form-group">
                                <label class="col-xs-2 control-label label-xs">Filtrar por:</label>
                                <div class="col-xs-4 radioset">
                                    <input id="rad_ba1" name="pc_opciones" type="radio" value="a" checked="" onclick="setfocus(this.form.search)"/><label for="rad_ba1">Descripci&oacute;n</label>
                                    <input id="rad_ba2" name="pc_opciones" type="radio" value="b" onclick="setfocus(this.form.search)"/><label for="rad_ba2">C&oacute;digo</label>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-2 control-label label-xs">B&uacute;squeda:</label>
                                <div class="col-xs-8">
                                    <div class="input-group">
                                        <input id="search" type="text" id="search" name="search" onkeydown="if (event.keyCode === 13)
                                            this.form.submit()" class="form-control input-xs" placeholder="Ingrese &iacute;ndice de b&uacute;squeda" autofocus="">
                                        <span class="input-group-btn">
                                            <button id="btsearch" class="btn btn-success btn-xs" type="button" title="Buscar Cuenta" onclick="this.form.submit()"><span class="glyphicon glyphicon-search"></span> Buscar</button>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="exa-fieldset col-xs-6">
                            <div  class="form-horizontal normal" >
                                <div class="form-group row">
                                    <div class="col-xs-12">
                                        <?php
                                        $row_plan = $obBD_con1->getArrayConsulta(364, $Ses_Emp_Cod."* AND Pla_Est='A'",$obBD_conexion);
                                        echo '<select name="sel_planes" id="sel_planes" class="form-control input-xs">';
                                        foreach($row_plan as $plan){
                                            echo '<option value="'.$plan['Pla_Cod'].'" data--pla_-obs="'.$plan['Pla_Obs'].'" data--pla_-cod="'.$plan['Pla_Cod'].'" data--pla_-est="'.$plan['Pla_Est'].'" data--pla_-fec="'.$plan['Pla_Fec'].'">'.$plan['Pla_Obs'].'</option>';
                                        }
                                        echo '</select>';
                                        ?>
                                    </div>
                                </div>
                                <div id="prueba">
                                    <div class="form-group row">
                                        <label class="col-xs-3 label-xs">C&oacute;digo: </label>
                                        <div class="col-xs-9">
                                            <span name="Pla_Cod" class="form-control input-xs">
                                                <?php echo $row_plan[0]['Pla_Cod'] ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-xs-3 label-xs">Fecha: </label>
                                        <div class="col-xs-3">
                                            <span name="Pla_Fec" class="form-control input-xs">
                                                <?php echo $row_plan[0]['Pla_Fec'] ?>
                                            </span>
                                        </div>
                                        <label class="col-xs-2 label-xs">Estado: </label>
                                        <div class="col-xs-4">
                                            <span name="Pla_Est" class="form-control input-xs">
                                                <?php echo $row_plan[0]['Pla_Est'] ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <div class="col-xs-12 label-xs" id="divRepetidos" style="display: none;">
                                            <label class="text-danger">Existen <span id="cuentaCodigosRep"></span> codigos repetidos</label>
                                            <button onclick="verRepetidos()" type="button" class="btn btn-primary btn-xs" title="ver"><span class="glyphicon glyphicon-eye-open" aria-hidden="true"></span></button>
                                            <button onclick="ocultarRepetidos()" id="ocultar" type="button" class="btn btn-danger btn-xs hidden" title="ocultar"><span class="glyphicon glyphicon-remove" aria-hidden="true"></span></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </fieldset>
                </form>
			</div>	
            <div class="col-xs-12" style="min-height:500px;" id="tot_reg">
                    <table id="Lis_Pla"></table>
                    <div id="Pag_Pla"></div>
                    <div class="Titulos2"><span id="plan-footer"><strong>Leyenda:</strong> <span class="glyphicon glyphicon-stop red"></span> Anulados/Inactivos </div>                         
			</div>
			<div class="col-xs-12" style="min-height:500px; visibility: hidden;" id="rep_reg">
                    <table id="Lis_Rep"></table>
                    <div id="Pag_Rep"></div>
                    <div class="Titulos2"><span id="plan-footer"><strong>Leyenda:</strong> <span class="glyphicon glyphicon-stop red"></span> Anulados/Inactivos </div>
			</div>
		</div>
	</div>
</div>
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
	
	$(()=>{
	   
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
			},
		}, false, "#Pag_Pla").gridButtonsAdd([null,
            {buttonicon:'print', title: 'Imprimir Cuentas', caption:'Imprimir',onClickButton:function(){ printR('#Lis_Pla'); }},
            {buttonicon:'download-alt', title: 'Descargar Cuentas', caption:'Descargar',onClickButton:function(){ exportR('#Lis_Pla'); }},
            {buttonicon:'download', title: 'Imprimir Plan de Cuentas', caption:'Imprimir',onClickButton:function(){ printP(event); }}
        ]);
        $("#Lis_Rep").createGrid({
            height: 400,
            colModel: [
                {label: 'Cod. Int.', name: 'Pld_Cod', width: 50, align: "left"},
                {label: 'C&oacute;digo', name: 'Pld_Cdc', width: 50, align: "left"},
                {label: 'Cuenta Contable', name: 'Pld_Des', width: 250, align: "left"},
                {label: 'Tipo', name: 'Pld_Tip', width: 50, align: "left"},
                {label: 'Estado', name: 'Pld_Est', width: 50, align: "left"}
            ],
            rowNum:100000,
            pgbuttons: false,
            pgtext: "",
            pginput: false,
            loadComplete: function(planRepAjax){
                $('#Lis_Rep tr').each(function () {
                    if($(this).find("td").eq(5).text()==="Inactivo"){
                        $(this).addClass("cellRed2");
                        $(this).addClass("myAltRowClass");
                    }
                });
            }
        }, false, "#Pag_Rep").gridButtonsAdd([null,
            {buttonicon:'print', title: 'Imprimir Cuentas', caption:'Imprimir',onClickButton:function(){ printR('#Lis_Rep'); }},
            {buttonicon:'download-alt', title: 'Descargar Cuentas', caption:'Descargar',onClickButton:function(){ exportR('#Lis_Rep'); }},
            {buttonicon:'download', title: 'Imprimir Plan de Cuentas', caption:'Imprimir',onClickButton:function(){ printP(event); }}
        ]);
        $("#rep_reg").css('visibility','').hide();
        $("#sel_planes").trigger("change");
	});

	$("#sel_planes").on("change", function(event){
        $('#Lis_Pla').Search('#frm_bus','planAjax');
        var data = $(event.currentTarget).find(":selected").data();
        $('#prueba').setData(data);
        $.getDataJson("", {getDuplicateds: true, codigo: data.Pla_Cod}, function(data){
            $("#Lis_Rep").setRows(data.rows);
             $("#divRepetidos").show;
            $("#cuentaCodigosRep").text(data.cuenta);
            if(data.cuenta > 0){
                $("#divRepetidos").css("display", "block");
            }else{
                $("#divRepetidos").css("display", "none");
            }
        });
        return false;
    });
    </script>
    <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
    <script>
        function printR(grid) {
            $('#tablaReporte').html($(grid).jqGrid('exportGridInnerHTML',{generated:false, caption:false, footer:true, bodyBorder:false, removeHiddens:true, removeCols:[1]}));
            $('#titleReporte').html($(grid).getCaption());
            $('#formatoReporte').printElement({pageTitle:"<?Php echo $Ses_Sys_Nom; ?>",printMode:'iframe',overrideElementCSS:[{ href:'../../mascaras/model1/estilos/print.css',media:'print'}]});
        }
        function exportR(grid) {
            var temp=$('<div>'+$('#formatoExportar').html()+'</div>');
            temp.append($(grid).jqGrid('exportGridHTML',{generated:false,caption:true,bodyBorder:false,footer:true,sepEnd:true}));
            $.downloadFile($.exportarExcelBlob(temp.html(),'Plan de Cuentas'),'Plan_Cuentas_'+$.getDate()+'.xls');
        }
        function printP(event){
            $.post("con_pri_planc_2.0.php", {codigo: $("span[name=Pla_Cod]").text()}, (data)=>{
                var wnd = window.open("about:blank", "", "");
                wnd.document.write(data);
                wnd.document.close();
            });
        }
    </script>
    <div id="formatoReporte" style="display: none;">
        <div style="width: 1030px;">
            <?php echo $obBD_con1->getReportHeader($Ses_Suc_Cod, 'REPORTE DE REGISTROS', '<span id="titleReporte"></span>',$obBD_conexion); ?>
            <table id="tablaReporte" cellspacing="0" cellpadding="0" style="border-collapse: collapse;table-layout: fixed;"></table>
            <?php echo $obBD_con1->getReportFooter($Ses_Suc_Cod,$Ses_Usu_Cod,$obBD_conexion); ?>
        </div>
    </div>
    <div id="formatoExportar" style="width: 700px;display: none;">
        <?php echo $obBD_con1->getReportHeader($Ses_Suc_Cod, 'REPORTE DE REGISTROS', '<span class="title_grid"></span>',$obBD_conexion,false,5); ?>
    </div>
</BODY>
</HTML>	