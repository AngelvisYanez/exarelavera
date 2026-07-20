<?php	  
/**
* Descripcion: Registro de Reposicion Caja Chica
* Fecha de actualizacion:	20-07-2016
* Desarrollador:	Jose Cumbicos
*/	
require_once('../../administrador/LOGICA/seguridad.php');	  
require_once('../LOGICA/cch_log_reposicion.php');  	  
require_once('../../Librerias/procedimientos/almacenados_standar.php');		  
require_once('../../Librerias/postclass.php');

/**
* Creaciï¿½n del Objeto de conexion 
*/
$obBD_conexion = new Class_Log_Conexion_Cch($Ses_Dat_Dis);
/**
* Creaciï¿½n del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Cch;
/**
* Llamado de la libreria para evitar el reenvio de datos 
*/
$thisPost = new Post_Block;

$hoy = date("Y-m-d");

/**
/* Buscamos la facturas pendientes por reponer
*/
//var_dump($ajaxSubgrid);
if(isset($ajaxSubgrid)){ 
	$responce['pages']=1;$responce['total']=1;
    $responce['rows'] = $obBD_con1->getArrayConsulta(35, $Ses_Emp_Cod, $obBD_conexion);
    utf8_encode_deep($responce['rows']);
    $responce['records']=count($responce['rows']);
    echo json_encode($responce);exit();
}

if(isset($ajaxBuscaNumChe)){	
	if($op_opciones=='n'){ //buscar x numero de reposicion
		if($search!=""){
			$responce['rows']=$obBD_con1->getArrayConsulta(30, $Ses_Emp_Cod.'*'.$search, $obBD_conexion);	
		}else{
			$responce['rows'] = $obBD_con1->getArrayConsulta(29, $Ses_Emp_Cod, $obBD_conexion);
		}	
	}else{ //busqueda por rango d efecha
		$responce['rows']=$obBD_con1->getArrayConsulta(22, $ini.'*'.$fin.'*'.$Ses_Emp_Cod, $obBD_conexion);	
	}
	if(count($responce['rows'])!=0)
		$responce['success']=true;
	else 
		$responce['success']=false;
	
	utf8_encode_deep($responce['rows']);
	echo json_encode($responce);exit();
}

if(isset($reposiAjax)){
	if($op_opciones=='n'){ //buscar x numero de reposicion
		$responce['rows']=$obBD_con1->getArrayConsulta(21, $search.'*'.$Ses_Emp_Cod, $obBD_conexion);	
	}else{ //busqueda por rango d efecha
		$responce['rows']=$obBD_con1->getArrayConsulta(22, $ini.'*'.$fin.'*'.$Ses_Emp_Cod, $obBD_conexion);	
	}
	$responce['success']=true;
	utf8_encode_deep($responce['rows']);
	echo json_encode($responce);exit();
}



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
	<div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Consultar Reposiciones</h3></div>
		<div class="panel-body ui-widget-content ui-corner-bottom exa-body">
				<form id="formReposi" class="form-horizontal normal"  action="javascript:$('#list').Search('#formReposi','ajaxBuscaNumChe'); "  >
						<div class="row">
						<div class="col-sm-12"></div>
							<div class="col-sm-7">
								<div title="B&uacute;squeda de Reposiciones">  
									<fieldset class="exa-fieldset">
									<legend class="Titulos2">Filtros</legend>
											<div class="form-group">
												<label class="col-md-2 control-label label-xs">Filtrar Por:</label>  
												<div class="col-md-6 radioset" >
													  <input id="radx" name="op_opciones" type="radio" value="n" checked="" onclick="$('#div_numCheque').toggleClass('hide'); $('#div_rangoFecha').toggleClass('hide'); setfocus(this.form.search)" alt="" /><label for="radx">&nbsp;&nbsp;Num. reposicion&nbsp&nbsp;&nbsp;</label>
													  <input id="rady" name="op_opciones" type="radio" value="f" onclick="$('#div_numCheque').toggleClass('hide'); $('#div_rangoFecha').toggleClass('hide'); setfocus(this.form.search)" alt="" /><label for="rady">&nbsp;&nbsp;Rango de Fechas&nbsp;&nbsp;</label>
												</div>
											</div>
											<div id="div_numCheque" class="form-group">
												<label class="col-md-2 control-label">B&uacute;squeda:</label>
												<div class="col-md-7" >                 
												  <div class="input-group">                        
													<input id="search" name="search" onkeydown="if (event.keyCode === 13) this.form.submit()" type="text" size="50" maxlength="50" placeholder="N&uacute;mero de reposici&oacute;n" autofocus class="form-control input-sm " /><input type="text" style="display:none"/>
													<span class="input-group-btn"><button type="submit" onclick="" class="btn btn-success btn-sm" title="Buscar cuenta" ><span class="glyphicon glyphicon-search"></span> <span> Buscar</span></button></span>
												  </div><!-- /input-group -->                          
												</div>                    
											</div>
											<div id="div_rangoFecha" class="form-group hide" >                                          
												   <label class="col-xs-2 control-label label-xs">Desde:</label>
												   <div class="col-xs-3" > <input id="ini" name="ini" class="form-control input-sm " placeholder="0000-00-00" type="text" required /></div>                      
												  <label class="col-xs-2 control-label label-xs">Hasta:</label>
												  <div class="col-xs-3" ><input id="fin" name="fin" class="form-control input-sm " placeholder="0000-00-00" type="text" required /></div>
												  <div class="col-xs-2" ><button type="button" onclick="this.form.submit()" class="btn btn-success btn-sm" title="Buscar cuenta" ><span class="glyphicon glyphicon-search"></span> <span> Buscar</span></button></div>
												  <!-- /input-group -->                          
																	
											</div>
									</fieldset>  										  
								</div>																			
							</div>
																									
							<div class="col-sm-4"></div>
							<div class="col-sm-12">
								<fieldset class="exa-fieldset">                           
									<legend class="Titulos2">Reposiciones existentes</legend> <!-- Form Name -->
									<div style="min-height: 350px"> 
										<table id="list"></table>
										<div id="listPager"></div>
									</div>
								</fieldset>
							</div>
							
							<div class="col-sm-12">
								<div class="form-group Titulos2">
                                <div class="col-sm-12"><hr/><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco (  <span class="required"></span> ) son campos obligatorios.</div>
								</div>  
							</div>
					</div>
				</form>
		</div>
</div>
    <!--INICIO DEL DIALOGO IMPRIMIR --> 
    <div id="successDialog"  title="Mensaje del Sistema">  
        <center><h4>Imprimir documentos!</h4></center>  
        <center id="printCheque"></center>
		<center> 
            <button type="button" onclick="$('#successDialog').dialog('close');" class="btn btn-inverse fileinput-button" style="display: inline;" >
                    <i class="icon-ban-circle icon-white"></i>
                    <span>Cerrar</span>
             </button>            
            <a id="impReposi" target="_blank" href=""  style="display: inline;" title="Imprimir informe de reposici&oacute;n"><span  class="btn btn-primary start"> <i class="icon-print icon-white"></i> <span>Reposici&oacute;n</span></span> </a>
			<a id="impCompr" target="_blank" href=""  style="display: inline;" title="Imprimir Comprobante Contable"><span  class="btn btn-primary start"> <i class="icon-print icon-white"></i> <span>Asiento</span></span> </a>               
        </center>        
    </div>
	<div id="modelo" style="display: none;">
		<table style="margin-bottom:40px;" cellpadding="1" border="1">
			<tr><td align="center" class="ui-widget-header" colspan="6"><label autofocus> Imprimir Cheque </label></td></tr>
			<tr><td align="center" class="ui-widget-content" colspan="6"><b id="nomBan">&nbsp;</b></td></tr>
			<tr>
			<td align="center"><a href="../../tesoreria/FRONT/tes_pri_cheque_mac_1.0.php{link}" target="_blank" title="Banco de Machala"><img src="../../mascaras/model1/imagenes/32x32/banco_machala.jpg" width="22" height="35"/></a></td>
			<td align="center"><a href="../../tesoreria/FRONT/tes_pri_cheque_pac_1.0.php{link}" target="_blank" title="Banco del Pacifico"><img src="../../mascaras/model1/imagenes/32x32/banco_pacifico.jpg" width="24" height="23"/></a></td>
			<td align="center"><a href="../../tesoreria/FRONT/tes_pri_cheque_rum_1.0.php{link}" target="_blank" title="Banco del Rumiñahui"><img src="../../mascaras/model1/imagenes/32x32/banco_ruminahui.jpg" width="30" height="15"/></a></td>
			<td align="center"><a href="../../tesoreria/FRONT/tes_pri_cheque_gua_1.0.php{link}" target="_blank" title="Banco del Guayaquil"><img src="../../mascaras/model1/imagenes/32x32/banco_guayaquil.JPG" width="36" height="18"/></a></td>
			<td align="center"><a href="../../tesoreria/FRONT/tes_pri_cheque_pch_1.0.php{link}" target="_blank" title="Banco del Pichincha"><img src="../../mascaras/model1/imagenes/32x32/banco_pichincha.JPG" width="36" height="30"/></a></td>
			<td align="center"><a href="../../tesoreria/FRONT/tes_pri_cheque_int_1.0.php{link}" target="_blank" title="Banco Internacional"><img src="../../mascaras/model1/imagenes/32x32/ban_int.jpg" width="32" height="32"/></a></td>
			</tr>
		</table>
		</div>
		
	<script type="text/javascript">
        
       $( document ).ready(function() {
            $("#Cop_Num").mask("999-999-999999999",{placeholder:"_"});
            //$.createDatePickers('.dateType');
			$.createDateRange('#ini','#fin');
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
	
	function buscaNumCheque(numero){
		$.get('<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>',{'Che_Num':numero,'ajaxBuscaNumChe':true}, function(response){
			if(response['success']===true){
				
			}else {numChe=0;$("#Che_Num").val(numChe);$.alert("No se logro obtener n&uacutemero del cheque");}
		},'json').fail(function(error) { $.alert("El Servidor ha fallado en responder!");});
	}
	
	function setCheques(Ban_Cod){ 
		 var datBan=Ban_Cod.split('*');
		 $.get('<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>',{'Ban_Cod':datBan[0],'numCheIni':true}, function(response){
			if(response['success']===true){
				var numChe=(response['Che_Num']*1)+1;
				$("#Che_Num").val(numChe).alertMsg();
			}else {numChe=0;$("#Che_Num").val(numChe);$.alert("No se logro obtener n&uacutemero del cheque");}
		},'json').fail(function(error) { $.alert("El Servidor ha fallado en responder!");});
	}
	
	function validaCheque(numero){  
		var valBanco=$("#bancos").val();
		var numAnt=$("#Che_Num").val();				 
		$.get('<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>',{'numero':numero,'valBanco':valBanco,'valChe': numAnt}, function(response){
			if(response['success']===true){
				if(response['valid']===false){
					numChe=(response['Che_Num']*1)+1;
					$("#Che_Num").val(numChe);$.alert('El Cheque <b>No. '+numAnt+'</b> ya existe.');
				}else{$("#Che_Num").alertMsg();}
			}else {numChe=0;$("#Che_Num").val(numChe);$.alert("No se logro obtener n&uacutemero del cheque");}                                
		},'json').fail(function(error) { $.alert("El Servidor ha fallado en responder!");});;        		 
	}
	
	function cargasDatosRepos(data){		
		if(typeof data==='undefined'){                                
			
			$("input[name='RepCod']").val('');
			$("#docu").val('');                                
			return false;
		}else{            
			if (data['Rep_Tip']==='Cheque'){
				$('#printCheque').css('display','');
				$('#successDialog').dialog("option", "height", 250);			
				$paramChe="?codigo2=1&asi="+data['Asi_Cod']+"&ban="+data['Ban_Cod']+"&pro="+data['Prv_Cod'];
				var html=$('#modelo').html();
				html = html.replace(/{link}/g,$paramChe);
				$('#printCheque').html(html);
			}else{
				$('#printCheque').css('display','none')
				$('#successDialog').dialog("option", "height", 150);			
			}
						
			$('#impCompr').attr('href','../../contabilidad/FRONT/con_pri_compr_2.1.php?codigo='+data['Com_Cod']+'&tabla=proveedore&campo=Prv_Cod&tipo='+data['Tia_Cod']+'&Pec_Cod='+data['Pec_Cod']);
			$('#impReposi').attr('href','cch_pri_reposicion_1.0.php?Rep_Cod='+data['Rep_Cod']);
			$('#successDialog').dialog('open');						
		}
	}
	
	function selectProvee(data){                           
		if(typeof data==='undefined'){                                
			$("input[name='Prv_Cod']").val('');
			$("#docu").val('');                                
			return false;
		}else{                            
			$("#docu").val(data['proveedor']);                             
			$("input[name='Prv_Cod']").val(data['Prv_Cod']);                                     
			$("#provDialog").dialog("close");
		}
	}
	
    function loadXML(){	             
		var formData = new FormData(document.getElementById("form3"));
		formData.append("uploadXML", true);
		$("#loader").show();
		//formData.append(f.attr("name"), $(this)[0].files[0]);
		$.ajax({
			url: "<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>",
			type: "post", dataType: "json", data: formData, cache: false, contentType: false, processData: false
		}).done(function(response){
			$("#loader").fadeOut("slow");
			if(response['success']===true){
				$("#list").jqGrid("clearGridData");  
				$("#list").jqGrid("setCaption",response['empresa']);
				$("#list").jqGrid('setGridParam',{rowNum:response['grid']['records']});
				$("#list").jqGrid('setGridParam', {data:response['grid']['rows'],page:1,records:response['grid']['records'],total:response['grid']['total'] }).trigger('reloadGrid');
				$("#form3").effect("highlight",{},500);
			}else{$("#list").jqGrid("clearGridData");$.alert(response['message']);}                                  
		}).fail(function(error) { $.alert("El Servidor ha fallado en responder! "); $("#loader").hide();});                              
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
		url: '<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>',
		mtype: "get", datatype: "json", regional : 'es',//ajaxRowOptions: { async: true },
		postData: $("#formReposi").getData("ajaxSubgrid"),
		autowidth : true, shrinkToFit: true, height: 250,caption:'Reposiciones',responsive:true,hidegrid:false,
		colModel: [				
			{ label: 'Cod.Int.', name: 'Rep_Cod',width: 15, key: true,viewable: true, align: 'center' },
			{ label: '# Reposici&oacuten', name: 'Rep_Num', width: 30, align: 'center' },   
			{ label: 'Fecha', name: 'Rep_Fec', width: 30, align: 'center' },                      
			{ label: 'Observaci&oacuten', name: 'Rep_Obs', width: 100, cellattr: function (rowId, tv, rawObject, cm, rdata) { return 'style="white-space: normal;"'; }},                   
			{ label: 'Tipo', name: 'Rep_Tip', width: 30,viewable: true, align: 'center'},                      
			{ label: 'Monto', name: 'Com_Val', width: 20,viewable: true, align: 'right',summaryTpl: "{0}", summaryType: "sum",summaryRound:'2', summaryRoundType: 'round'},                      
			{ label:'<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 10, align: 'center',viewable: false,formatter:function (cellvalue, options, rowObject) { 						
					var clic='cargasDatosRepos('+$.jsonParser(rowObject)+')';
					return  '<span class="btn btn-success btn-xs" title="Seleccionar" onclick=\''+clic+'\'><i class="glyphicon glyphicon-arrow-right"></span>'; 
				}}
		],                                                     
		rowNum: 100000000,pager: "#listPager", gridview: true, rownumbers: false, viewrecords: true, altRows: true, altclass: "myAltRowClass",pginput : false,pgbuttons: false,  pgtext: "Mostrando {0} Documentos.",
		footerrow: true, userDataOnFooter: false,							
		loadComplete: function () {                       
			jgrid.jqGrid('footerData', 'set', { Com_Val:jgrid.jqGrid('getCol','Com_Val',false,'sum')});							   
		}                          			
	});                        
		jgrid.navGrid('#listPager',{ edit: false, add: false, del: false, search: false, refresh: true, view: true, position: "left", cloneToTop: false });
		jgrid.jqGrid('bindKeys');
		$.createDialog('#successDialog',150,550);
		//$.createDialog('#reposiDialog',400, 700);
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