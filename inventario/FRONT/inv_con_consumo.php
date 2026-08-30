<?php	
/**
* @abstract Permite realizar movimientos de inventario
* @author Santiago R. - Alejandro C.
* @version 1.0
* Fecha de creaci�n  2020-08-07
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/inv_log_consumo.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

//

/**
* Creacion del Objeto de conexion
*/
$obBD_conexion = new Class_Log_Conexion_Con($Ses_Dat_Dis);
/**
* Creacion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Con;


$hoy = date("Y-m-d");
$mes = date("m");

if(isset($productos)){

	$param[0]=$Ses_Emp_Cod;
	$param[2]=$ini;
	$param[3]=$fin;

	//ChromePhp::log('php' . $Con_Cod);

	if($Con_Cod == 'nombre'){
		$array=$obBD_con1->getArrayConsulta(37, $param, $obBD_conexion);  
    	$responce['rows'] = array_values($array);
	}
	else{
		$array=$obBD_con1->getArrayConsulta(36, $param, $obBD_conexion);  
    	$responce['rows'] = array_values($array);
	}

	
    utf8_encode_deep($responce['rows']);
    echo json_encode($responce);exit();
}
?>


<!DOCTYPE html>
<HTML>
	<HEAD>		
                <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
                <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php"); ?>              
	</HEAD>
<BODY>
    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo; Consultar Consumos</h3></div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            
                <div class="row">                   
                    <div class="col-xs-12">
						<form id="formFiltros" class="form-horizontal normal">
							<fieldset class="exa-fieldset">                           
								<legend class="Titulos2">Filtros:</legend> <!-- Form Name -->
								
								<div class="row">

									<!-- CENTROS DE CONSUMO -->
									<div class="col-xs-6">
										<div class="form-group">
												<label class="col-sm-3 control-label label-xs ">Filtrar: </label>
												<div class="col-sm-6"> 
												<select name="Con_Cod" class="form-control input-xs" id="Con_Cod">
													<option value="nombre">Por producto</option>
													<option value="con_des">Por centro de consumo</option>
												</select>
												</div>
										</div>
									</div>	

									

									<!-- FFECHA -->
									<div class="col-xs-6">	
										<div class="form-group">
											<label class="col-sm-2 control-label label-xs ">Desde:</label>
											<div class="col-sm-3">     
												<input name="ini" type="text" id="ini" class="form-control input-sm">      
											</div>

											<label class="col-sm-2 control-label label-xs ">Hasta:</label>
											<div class="col-sm-3">                                    
												<input name="fin" type="text" id="fin" class="form-control input-sm">                              
											</div>

											<div class="col-xs-2">
											  <div class=""><button type="button" id="Con_Cods" onclick="kardexGrid.setGridParam({postData:$('#formFiltros').getData('productos')}); kardexGrid.trigger('reloadGrid', [{page:1}]); setCaption(); "class="btn btn-sm btn-success" title="Ejecutar B�squeda"><span class="glyphicon glyphicon-search"></span> &nbsp;Filtrar</button></div>
											</div>
										  </div>
									</div>
								</div>
							</fieldset> 
						</form>
					 </div>  


					 <!-- TABLA DE CONSULTA -->
                    <div class="col-xs-12" style="min-height: 450px;">                       
                        <table id="prods"></table>
                        <div id="prodsPager"></div>
                        

                        <script>
                            var kardexGrid=$("#prods"); 
                            $('#Con_Cod').change(function(){
								var vl = $(this).val();
								if(vl){
								 if(vl == "s") {
								 	
						        } else {
						           kardexGrid.setGridParam({postData:$('#formFiltros').getData('productos')}); 
						           kardexGrid.trigger('reloadGrid', [{page:1}]);
						           jQuery('#prods').jqGrid('groupingGroupBy', vl);
						           document.getElementById("Con_Cods").click();
						        }
							}
						   });


                            $(document).ready(function () {
                                $.createDialog('#successDialog',150,550);
								$.createDateRange('#ini','#fin');
								$('#ini').val('2000-01-01');
								$('#fin').datepicker("setDate", new Date()); 
								
                                kardexGrid.createGrid({
                                    url: '<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>',
                                    mtype: "GET", datatype: "json", regional : 'es',//ajaxRowOptions: { async: true },
                                    postData: $('#formFiltros').getData('productos'),
                                    autowidth : true, shrinkToFit: true, height: 270,responsive:true,footerRow:true,
                                    caption:'',hidegrid:false,
                                    cmTemplate: {sortable:false /*,editrules: {edithidden: true}*/},
                                    colModel: [                               
                                        { label: 'Lote', name: 'con_des',  width: 25,align:'center'},

                                        { label: 'Producto',name: 'nombre', width: 40,classes:'columnHighlight3',align:'left'},

                                        { label: 'Precio Unitario',name: 'precio', width: 40,classes:'columnHighlight2',align:'right',formatter:'currency', formatoptions: {prefix:'$', thousandsSeparator:',',decimalSeparator:'.',defaultValue:'',decimalPlaces: 4}},

                                        { label: 'Cantidad',name: 'cantidad', width: 40,classes:'columnHighlight3', summaryType:'sum', align:'right',formatter:'number', formatoptions: {prefix:'', thousandsSeparator:',',decimalSeparator:'.',defaultValue:''}},

                                        { label: 'Importe',name: 'total', width: 40,classes:'columnHighlight2', summaryType:'sum', align:'right',formatter:'currency', formatoptions: {prefix:'$', thousandsSeparator:',',decimalSeparator:'.',defaultValue:'',decimalPlaces: 4}}, 
										                                  
                                    ],  
                    				 grouping:true, groupingView:{groupField:['nombre'], groupSummary:[true], showSummaryOnHide:[true] } ,footerrow: true, userDataOnFooter: false, rowNum: 10000000, pager: "#prodsPager", gridview: true, rownumbers: true, viewrecords: true, pgbuttons: false,pgtext: null,
									loadComplete:function (){ $(this).setGridSummary(['cantidad','total'],{nombre:'<div style="text-align:right">TOTALES:</div>',Kar_Pre:'',Kar_Prs:'',Kar_Fec:'',Kar_Det:'',Doc:''});}
                                },true,"#prodsPager").gridButtonsAdd([
									{buttonicon:'print',caption:'Imprimir',onClickButton:function(){ printR('#prods'); }},
									{buttonicon:'download-alt',caption:'Descargar',onClickButton:function(){ exportR('#prods'); }}
								]); 

                            });
                            
                        </script>    
                    </div>  
                </div> 
        </div>
    </div>
    <script>

 		function setCaption(){
            var caption='';
            caption="Listado de productos - ";
            caption=caption+$('#Con_Cod').find(':selected').text();
            $('#prods').jqGrid('setCaption', caption);
        }

        function printR(grid) {
			$('#tablaReporte').html($(grid).jqGrid('exportGridInnerHTML',{generated:false, caption:false, footer:true, bodyBorder:false}));
			$('#titleReporte').html($(grid).getCaption());
			$('#formatoReporte').printElement({pageTitle:"<?Php echo $Ses_Sys_Nom; ?>",printMode:'popup',overrideElementCSS:[{ href:'../../mascaras/model1/estilos/print.css',media:'print'}]});                
		}
		function exportR(grid) {
			var temp=$('<div>'+$('#formatoExportar').html()+'</div>');
			temp.append($(grid).jqGrid('exportGridHTML',{generated:false,caption:true,bodyBorder:false,footer:true,sepEnd:true}));                
			$.downloadFile($.exportarExcelBlob(temp.html(),'Digitacion'),'digitacion_'+$.getDate()+'.xls');    
		}
    </script>
	<div id="formatoReporte" style="display: none;">
	  <div style="width: 1030px;">	  		 
            <?php echo $obBD_con1->getReportHeader($Ses_Suc_Cod, "REPORTE CENTRO DE COSUMO", '<span id="titleReporte"></span>',$obBD_conexion); ?>
            <table id="tablaReporte" cellspacing="0" cellpadding="0" style="border-collapse: collapse;table-layout: fixed;"></table>            
            <?php echo $obBD_con1->getReportFooter($Ses_Suc_Cod,$Ses_Usu_Cod,$obBD_conexion); ?>
	  </div>
        </div>  
        <div id="formatoExportar" style="width: 700px;display: none;">
            <?php echo $obBD_con1->getReportHeader($Ses_Suc_Cod, 'REPORTE CENTRO DE CONSUMO', '<span class="title_grid"></span>',$obBD_conexion,false,6); ?>
        </div>
	<script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=1"></script>
</BODY>
</HTML>