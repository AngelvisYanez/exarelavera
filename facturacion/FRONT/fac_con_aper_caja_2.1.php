<?php	  
/**
* Descripcion: Registro de Reposicion Caja Chica
* Fecha de actualizacion:	20-07-2016
* Desarrollador:	Jose Cumbicos
*/	
require_once('../../administrador/LOGICA/seguridad.php');	  
require_once('../LOGICA/fac_log_aper_caja.php');  	  
require_once('../../Librerias/procedimientos/almacenados_standar.php');		  
require_once('../../Librerias/postclass.php');

/**
* Creaciï¿½n del Objeto de conexion 
*/
$obBD_conexion = new Class_Log_Conexion_Tes($Ses_Dat_Dis);
/**
* Creaciï¿½n del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Tes;
/**
* Llamado de la libreria para evitar el reenvio de datos 
*/
$thisPost = new Post_Block;

$hoy = date("Y-m-d");

/**
/* Buscamos la facturas pendientes por reponer
*/
if(isset($cajasAjax)){ 	
    $responce['rows'] = $obBD_con1->getArrayConsulta(27, $Pun_Cod, $obBD_conexion);
    utf8_encode_deep($responce['rows']);
    $responce['records']=count($responce['rows']);
    echo json_encode($responce);exit();
}

if(isset($provAjax)){
    $contar = $obBD_con1->getRowConsulta(4, $search.'*'.$Ses_Emp_Cod.'*'.$op_opciones.'*', $obBD_conexion);	      
    $pagination= pages($contar['total'], $page, $rows);
    $responce=$pagination['data'];
    $responce['rows'] = $obBD_con1->getArrayConsulta(4, $search.'*'.$Ses_Emp_Cod.'*'.$op_opciones.'*'.$pagination['limits'], $obBD_conexion);
	utf8_encode_deep($responce['rows']);
    echo json_encode($responce);exit();
}

/**
/* Buscamos los vendedores
*/
$rs_vendedores = $obBD_con1->getArrayConsulta(26, $Ses_Suc_Cod, $obBD_conexion);

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<HTML><HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom;?></TITLE>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<?Php require_once("../../mascaras/model1/estilos/jqgrid5.php")?>         
        <script type="text/javascript"> 
        $(function() {
			$('#set1 *').tooltip({showURL: false});
		});              			
		</script>		
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	</HEAD>
<BODY>
<div class="panel panel-main">
	<div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Consulta de Cajas</h3></div>
		<div class="panel-body ui-widget-content ui-corner-bottom exa-body">
				<form id="formReposi" class="form-horizontal normal"  action="javascript:$('#list').Search('#formReposi','cajasAjax') "  >
						
							<div class="col-sm-12">											
								<fieldset class="exa-fieldset">                           
								<legend class="Titulos2">Datos de la caja</legend>
									<!-- Text input Fecha-->
									<div class="form-group">
									  <label class="col-sm-2 control-label label-sm required" for="fecha">Vendedor:</label>  
									  <div class="col-sm-3">                                    
                                        <select name="Pun_Cod" id="Pun_Cod" onChange="" class="form-control input-sm-3" required>
                                              <option value="">Seleccione...</option>
                                            <?Php 													
                                            foreach($rs_vendedores as $row){ ?>
                                            <option value="<?php echo $row['Pun_Cod']?>" data-Prs_Cod="<?php echo $row['Prs_Cod'];?>">&raquo;&nbsp;<?php echo $row['Prs_Ape'].' '.$row['Prs_Nom'];?></option>
                                            <?php } ?>
                                        </select>
									  </div>    
                                      <button type="submit"  class="btn btn-success btn-sm" title="Buscar cuenta" ><span class="glyphicon glyphicon-search"></span> <span> Buscar</span></button>                                                                   
									</div>	                                   									
								</fieldset>
							</div>																					
							<div class="col-sm-4"></div>
							<div class="col-sm-12">
								<fieldset class="exa-fieldset">                           
									<legend class="Titulos2">Historial de cajas</legend> <!-- Form Name -->
									<div style="min-height: 350px"> 
										<table id="list"></table>
										<div id="listPager"></div>
									</div>
								</fieldset>
							</div>
							

				</form>
		</div>
</div>
   
    <script type="text/javascript">
        
       $( document ).ready(function() {
            $("#Cop_Num").mask("999-999-999999999",{placeholder:"_"});
            $.createDatePickers('.dateType');
            //$('#Cop_Fec').datepicker( "option", "maxDate", '<?php echo $maximo; ?>');
       });
    </script>
<!-- FIN DEL DIALOGO PROVEEDOR-->

<script type="text/javascript" src="../../framework/jquery/jquery.plugins/MaskedInput/jquery.maskedinput.1.4.1.min.js"></script>
<script>
    
	function resetForm(){
		$("select[name='bancos']").val('');
		$("select[name='Tia_Cod']").val('');
		$("#Che_Num").val('');
		$("#docu").val('');		
	}
	
	function reporte_resumen(data){		
		window.open('./fac_pri_fac_caja.php?Caj_Cod='+data['Caj_Cod']); 	
	}
	function reporte_detalle(data){		
		window.open('./fac_pri_fac_caja_detalle.php?Caj_Cod='+data['Caj_Cod']); 	
	}
	function reporte_Forma_pago(data){		
		
		//window.open('./fac_pri_fac_caja_detalle_pagos.php?Caj_Cod='+data['Caj_Cod']); 
		window.open('./fac_pri_fac_caja_detalle_Pago.php?Caj_Cod=' + data['Caj_Cod']);
	}
	function reporte_Forma_pago_ticket(data) {
		window.open('./fac_pri_fac_caja_detalle_ticket.php?Caj_Cod=' + data['Caj_Cod']);
	}
	
	$(document).ready(function () {
		var jgrid=$("#list");
		$("#chngroup").change(function(){
			var vl = $(this).val();
			if(vl) {
					if(vl === "clear") {jgrid.jqGrid('groupingRemove',true);} 
					else {jgrid.jqGrid('groupingGroupBy',vl);}
			}
	});
	jgrid.jqGrid({
		url: '<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>',
		mtype: "GET", datatype: "json", regional : 'es',//ajaxRowOptions: { async: true },
		postData: $("#formReposi").getData("ajaxSubgrid"),
		autowidth : true, shrinkToFit: true, height: 250,caption:'Cajas existentes',responsive:true,hidegrid:false,
		colModel: [				
			{ label: 'Cod. Int.', name: 'Caj_Cod',key: true, width: 15,align:"center"},			
			{ label: 'Prs_Cod', name: 'Prs_Cod', hidden: true},		
			{ label: 'Fecha de Apertura', name: 'Caj_Fec', width: 35,align:"center"},			
			{ label: 'Fecha de Cierre', name: 'Caj_Fef', width: 35,align:"center"},   
			{ label: 'Observaci&oacute;n', width: 120,name: 'Caj_Obs',align:"left", sorttype:"date"},								
			{ label: 'Estado', name: 'Caj_Est', width: 30,align:"center"},				
			{ label: 'Monto Ini.', name: 'Caj_Exi', width: 35, viewable: true, align: 'right',summaryTpl: "{0}", summaryType: "sum",summaryRound:'2', summaryRoundType: 'round',formatter: 'currency',formatoptions:{defaultvalue:'0'}},
			{ label:'<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 15, align: 'center',viewable: false,
					formatter:function (cellvalue, options, rowObject) { var clic='reporte_resumen('+$.jsonParser(rowObject)+')';
						return  '<span class="btn btn-success btn-sm" title="Imprimir resumen" onclick=\''+clic+'\'><i class="glyphicon glyphicon-print"></span>'; 
					}
				},
			{ label:'<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 15, align: 'center',viewable: false,
				formatter:function (cellvalue, options, rowObject) { var clic='reporte_detalle('+$.jsonParser(rowObject)+')';
					return  '<span class="btn btn-success btn-sm" title="Reporte detallado" onclick=\''+clic+'\'><i class="glyphicon glyphicon-print"></span>'; 
				}
			},

			{ label:'<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 15, align: 'center',viewable: false,
				formatter:function (cellvalue, options, rowObject) { var clic='reporte_Forma_pago('+$.jsonParser(rowObject)+')';
					return  '<span class="btn btn-success btn-sm" title="Reporte detallado forma de pago" onclick=\''+clic+'\'><i class="glyphicon glyphicon-print"></span>'; 
				}
			},
			{ label:'<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 15, align: 'center',viewable: false,
				formatter:function (cellvalue, options, rowObject) { var clic='reporte_Forma_pago_ticket('+$.jsonParser(rowObject)+')';
					return  '<span class="btn btn-success btn-sm" title="Reporte detallado forma de pago (Ticket)" onclick=\''+clic+'\'><i class="fa fa-ticket"></i></span>'; 
				}
			},

		],                                                     
		rowNum: 100000000,pager: "#listPager", gridview: true, rownumbers: false, viewrecords: true, altRows: true, altclass: "myAltRowClass",pginput : false,pgbuttons: false,  pgtext: "Mostrando {0} Documentos.",
		footerrow: true, userDataOnFooter: false,							
		loadComplete: function () {                       
			jgrid.jqGrid('footerData', 'set', { Caj_Exi:jgrid.jqGrid('getCol','Caj_Exi',false,'sum')});							   						
		}                          			
	});                        		
});  
	
	 
	
</script>

<!-- Librerias para el tratamiento de la interfaz - cajas de texto -->
<!--<script type="text/javascript" src="../VALIDACIONES/fac_par_aju.js?x=2"></script>-->
<!--<script type="text/javascript" src="../../Librerias/textbox/main.js"></script>   -->
</BODY>
</HTML>
<?php
/**
* Cierra las conexiones 
*/
$obBD_con1->liberar();
$obBD_conexion->cerrar();	
?>