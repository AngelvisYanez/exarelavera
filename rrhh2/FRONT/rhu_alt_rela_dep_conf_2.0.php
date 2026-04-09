<?	
/** 
* Descripción: Permite asignar codigos iva en compras y ventas
* Fecha de actualización:	2015-09-23
* Desarrollador:	Jose Cumbicos
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/rhu_log_rela_dep_conf.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');	  
require_once('../../Librerias/postclass.php');	  

/** 
* Creacion del Objeto de conexion 
*/
$obBD_conexion = new Class_Log_Conexion_rhu($Ses_Dat_Dis);
/**
* Creacion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_rhu;
/**
* Creación del objeto para evitar el reenvio 
*/
$thisPost = new Post_Block;  


/*Guarda el nuevo iva*/
if(isset($ajax_edit)){ 
    /**
	   * inicio de la transaccion 
	   */
	   $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
		
		if($hdd_save=='1')
		{			
			if(isset($lblDed_Cod))
			{
				//Actualiza
				$obBD_con1->operacionobBD(6, $lblDed_Des.'*'.$lblDed_Hrs.'*'.$lblDed_Cod, $obBD_conexion);
			}else{
				//Guarda Nuevo
				$obBD_con1->operacionobBD(4, $lblDed_Des.'*'.$lblDed_Hrs, $obBD_conexion);	
			}			
		}else{
			if(isset($lblReb_Cod))
			{ 
				//Actualiza
				$obBD_con1->operacionobBD(5, $lblReb_Des.'*'.$lblReb_Cod, $obBD_conexion);
			}else{
				//Guarda Nuevo
				$obBD_con1->operacionobBD(3, $lblReb_Des, $obBD_conexion);	
			}
		}
		$responce['tipo']=$hdd_save;
	   /**
	   * fin de la transacción 
	   */
	   $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);
	   
	   if($obBD_con1->Error==0){
			$responce['success']=true;
	   }else{
			$responce['success']=false;
        	$responce['message']=$obBD_con1->MsgError;
	   }
	   echo json_encode($responce);
exit();	
}



/*Elimina un iva*/
if(isset($elimina)){ 
    /**
	   * inicio de la transaccion 
	   */
	   $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
		
		if($tipo=='eliDedi')
		{
			$obBD_con1->operacionobBD(8, 'I*'.$elimina, $obBD_conexion);
		}
		if($tipo=='eliRelac')
		{
			$obBD_con1->operacionobBD(7, 'I*'.$elimina, $obBD_conexion);	
		}			
	   /**
	   * fin de la transacción 
	   */
	   $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);
	   
	   if($obBD_con1->Error==0){
			$responce['success']=true;
	   }else{
			$responce['success']=false;
        	$responce['message']=$obBD_con1->MsgError;
	   }
	   echo json_encode($responce);
exit();	
}

if(isset($cuenAjax)){ 
    $contar = $obBD_con1->getRowConsulta(330, $search.'*'.$Ses_Emp_Cod.'*'.$Pec_Cod.'*'.$op_opciones.'*', $obBD_conexion);	      
    $pagination= pages($contar['total'], $page, $rows);
    $responce=$pagination['data'];
    $responce['rows']=  $obBD_con1->getArrayConsulta(330, $search.'*'.$Ses_Emp_Cod.'*'.$Pec_Cod.'*'.$op_opciones.'*'.$pagination['limits'], $obBD_conexion);	    
    utf8_encode_deep($responce['rows']);echo json_encode($responce);exit();
}

if(isset($dedica)){ 
    
	$responce['rows']=  $obBD_con1->getArrayConsulta(2, $Ded_Cod, $obBD_conexion);	    
    utf8_encode_deep($responce['rows']);echo json_encode($responce);exit();
}

if(isset($relacion)){ 
    
	$responce['rows']=  $obBD_con1->getArrayConsulta(1, $Pla_Cod, $obBD_conexion);	    
    utf8_encode_deep($responce['rows']);echo json_encode($responce);exit();
}


$periodo = $obBD_con1->getRowConsulta(329, $Ses_Emp_Cod, $obBD_conexion);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<HTML>
	<HEAD>
    <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
		<?Php require_once("../../mascaras/model1/estilos/estilos.php"); ?>    
		<?Php require_once("../../mascaras/model1/estilos/basic.php"); ?>
                <?Php require_once("../../mascaras/model1/estilos/jqgrid.php")?>
	<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
	</HEAD>
<BODY>
<div id="set1">
<table width="100%" border="0"  cellpadding="0" cellspacing="0" class="table">
  <tr class="BarraTitulo">
	  <td height="10">&raquo; Configurar dependencias laborales</td>
  </tr>
  <tr>
      <td height="450"  valign="top"><table width="100%" border="0" cellspacing="0" cellpadding="0">
        <tr>
          <td width="50%" height="448" valign="top">
            <FIELDSET>
            <LEGEND>
            <label class="Titulos2">Dedicaci&oacute;n Laboral</label>
            </LEGEND>
            <table width="100%" border="0" cellspacing="0" cellpadding="0">
              <tr>
                <td colspan="2" >
                <div>   
                        <table id="list_1"></table>
                        <div id="listPager_1"></div>
                </div>
				<script>
					function verDetalle(rowId)
					{
						var datosDet=$('#list_1').jqGrid ('getRowData', rowId);
						
						//console.log(datosDet);						                                 
						$("#lblDed_Cod").val(datosDet['Ded_Cod']);
						$("#lblDed_Des").val(datosDet['Ded_Des']);
						$("#lblDed_Hrs").val(datosDet['Ded_Hrs']);
						
						$('#dediLaboral').dialog('open');                        
					}
					 
					$(document).ready(function () { 
					    $.createDialog('#dediLaboral',250,550); 
                        $.datepicker.setDefaults($.datepicker.regional["es"]);
                        $("#list_1").jqGrid({
                            url: '<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>',
                            mtype: "GET", datatype: "json", regional : 'es',//ajaxRowOptions: { async: true },
							postData: {dedica:true,Ded_Cod:''},                          
                            autowidth : true, shrinkToFit: true, height: 270,
                            cmTemplate: {sortable:false},
                            colModel: [
							    { label: 'Cód.Int.', name: 'Ded_Cod', key: true, width: 15,align:"center", hidden:true }, 
                                { label: 'Cód. Int.', name: 'Ded_Cod', width: 45 },                      
                                { label: 'Descripción', name: 'Ded_Des', width: 160 },                               
								{ label: 'Horas', name: 'Ded_Hrs', width: 60 ,align:"center" },                               
								
								{ label:'&nbsp;', name: 'act2', width: 25, align: 'center',viewable: false,
									formatter:function (cellvalue, options, rowObject) { 
										return  '<span class="btn btn-primary btn-mini" title="Editar" type="button" onclick=" verDetalle(\''+rowObject.Ded_Cod+'\');"><i class=" icon-pencil icon-white"></i></span>';                                            
									}
								},
								{ label:'&nbsp;', name: 'act1', width: 25, align: 'center',viewable: false,
									formatter:function (cellvalue, options, rowObject) { 
										return  '<span class="btn btn-danger btn-mini" title="Eliminar" type="button" onclick="deleteRows(\''+rowObject.Ded_Cod+'\',\'eliDedi\');"><i class="icon-trash icon-white"></i></span>';                                            
									}
								}
								
                            ],                                                     
                            rowNum: 10000000, pager: "#listPager_1", gridview: true, rownumbers: true, viewrecords: true, altRows: true, altclass: "myAltRowClass",pgbuttons: false,pgtext: null,
                            
                        });                        
                        $('#list_1').navGrid('#listPager_1',{ edit: false, add: false, del: false, search: false, refresh: true, view: true, position: "left", cloneToTop: false });
                        $("#list_1").jqGrid('bindKeys'); 
                    });
					
				</script>
                </td>
                </tr>
              <tr>
                <td>               
                <button id="BtnCobrado" onclick="tipo='cobrado';$('#cuenDialog').dialog('open');" title="Buscar Cuentas" type="button" class="btn btn-success fileinput-button"><i class="icon-list-alt icon-white"></i><span>Agregar</span></button>               
                </td>
                <td>&nbsp;</td>
              </tr>            
            </table>
            </FIELDSET>
        </td>
        <td valign="top">
            <FIELDSET>
            <LEGEND>
            <label class="Titulos2">Relaci&oacute;n Laboral</label>
            </LEGEND>
            <table width="100%" border="0" cellspacing="0" cellpadding="0">
              <tr>
                <td colspan="2" width="50%">
                <div>   
                        <table id="list_2"></table>
                        <div id="listPager_2"></div>
                </div>
				<script>
					var tipo="";
					function saveRows(form) 
					{ 
						
                       $.post( "<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>",$('#'+form).getData('ajax_edit'),function(response)
					   {
							if(response['success']===true)
							{
                                $.alert("Transaccion Realizada con &Eacute;xito!");                               
								if(response['tipo']=='1'){
								$("#list_1").trigger('reloadGrid'); }else{$("#list_2").trigger('reloadGrid');}
                            }else{
								$.alert(response['message']);
							}
                        },'json').fail(function(error) { $.alert("El Servidor ha fallado en responder!");});
						$('#dediLaboral').dialog('close');
					}
                   
					function deleteRows(codigo,tipo) 
					{ 						
                       $.post( "<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>",{elimina:codigo,tipo:tipo},function(response)
					   {
							if(response['success']===true)
							{
                                $.alert("Transaccion Realizada con &Eacute;xito!");
                                if(tipo=='eliDedi')
								{$("#list_1").trigger('reloadGrid');}else{$("#list_2").trigger('reloadGrid');}											
                            }else{
								$.alert(response['message']);
							}
                        },'json').fail(function(error) { $.alert("El Servidor ha fallado en responder!");});						
					}					
					
					function verDetalleRel(rowId)
					{
						var datosDet=$('#list_2').jqGrid ('getRowData', rowId);
						
						//console.log(datosDet);						                                 
						$("#lblReb_Cod").val(datosDet['Reb_Cod']);
						$("#lblReb_Des").val(datosDet['Reb_Des']);														
						$('#relacioLaboral').dialog('open');                        
					}
					
					$(document).ready(function () { 					
						$.createDialog('#relacioLaboral',250,550);																                        
                        $("#list_2").jqGrid({                            
							url: '<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>',                            
							mtype: "GET", datatype: "json", regional : 'es',//ajaxRowOptions: { async: true },
                            postData: {relacion:true,Reb_Cod:''},
                            autowidth : true, shrinkToFit: true, height: 270,
                            cmTemplate: {sortable:false},																					
                            colModel: [
							    { label: 'Cód.Int.', name: 'Reb_Cod', key: true, width: 15,align:"center", hidden:true },                                
								{ label: 'Descripción', name: 'Reb_Des', width: 300  },                               
								{ label:'&nbsp;', name: 'act2', width: 25, align: 'center',viewable: false,
									formatter:function (cellvalue, options, rowObject) { 
										return  '<span class="btn btn-primary btn-mini" title="Editar" type="button" onclick=" verDetalleRel(\''+rowObject.Reb_Cod+'\');"><i class=" icon-pencil icon-white"></i></span>';                                            
									}
								},
								{ label:'&nbsp;', name: 'act1', width: 25, align: 'center',viewable: false,
									formatter:function (cellvalue, options, rowObject) { 
										return  '<span class="btn btn-danger btn-mini" title="Eliminar" type="button" onclick="deleteRows(\''+rowObject.Reb_Cod+'\',\'eliRelac\');"><i class="icon-trash icon-white"></i></span>';                                            
									}
								}
                            ],                                                     
                            rowNum: 10000000, pager: "#listPager_2", gridview: true, rownumbers: true, viewrecords: true, altRows: true, altclass: "myAltRowClass",pgbuttons: false,pgtext: null, 
                        });                        
                        $('#list_2').navGrid('#listPager_2',{ edit: false, add: false, del: false, search: false, refresh: true, view: true, position: "left", cloneToTop: false });
                        $("#list_2").jqGrid('bindKeys'); 
                    });
				</script>
                </td>
                </tr>
              <tr>
                <td><button onclick="tipo='pagado';$('#cuenDialog').dialog('open');" title="Buscar Cuentas" type="button" class="btn btn-success fileinput-button"><i class="icon-list-alt icon-white"></i><span>Agregar</span></button></td>
                <td>&nbsp;</td>
              </tr>            
            </table>
            </FIELDSET>
          </td>
        </tr>
        </table> 
 		
      </td>
  </tr>
</table>

<!--INICIO DEL DIALOGO DEDICACION LABORAL --> 
    <div id="dediLaboral" title="Dedicación laboral">         
        <div>
        <div style="width: 100%;display: inline;float:left;" align="left">
            <form id="frmEdit" action="javascript: saveRows('frmEdit')"> 
            <fieldset>
                <legend><label class="Titulos2">Datos dedicación laboral</label></legend>
                  <div class="row">
                    <div class="segmento">Cód. Int.:</div><div class="datasegmento">
                      <input id="hdd_save" name="hdd_save"  type="hidden" value="1" />
                      <input id="lblDed_Cod" name="lblDed_Cod" type="text" class="label medium ui-widget-content ui-corner-all" readonly /></div>
                    </div>    
                  <div class="row">
                    <div class="segmento">Descripción:</div><div class="datasegmento">
                      <input id="lblDed_Des" name="lblDed_Des" type="text" class="label ui-widget-content ui-corner-all"/></div>
                    </div>                        
                  <div class="row">
                    <div class="segmento">Horas laborables:</div><div class="datasegmento"><input id="lblDed_Hrs" name="lblDed_Hrs" type="text" class="label medium ui-widget-content ui-corner-all"/></div>
                  </div>
            </fieldset> 
            <br />
            <div class="row">
            	<button type="submit" class="btn btn-primary start" id="btnguarda" name="btnguarda" title="Guardar" >
                       <i class="icon-book icon-white"></i>
                       <span>Guardar</span>
                </button>                
            </div>
           </form>
        </div>                          
        </div>
    </div>

<!--INICIO DEL DIALOGO RELACION LABORAL --> 
    <div id="relacioLaboral" title="Relación laboral">         
        <div>
        <div style="width: 100%;display: inline;float:left;" align="left">
            <form id="frmEdit2" action="javascript: saveRows('frmEdit2')"> 
            <fieldset>
                <legend><label class="Titulos2">Datos dedicación laboral</label></legend>
                  <div class="row">
                    <div class="segmento">Cód. Int.:</div><div class="datasegmento">
                      <input id="hdd_save" name="hdd_save"  type="hidden" value="2" />
                      <input id="lblReb_Cod" name="lblReb_Cod" type="text" class="label medium ui-widget-content ui-corner-all" readonly /></div>
                    </div>    
                  <div class="row">
                    <div class="segmento">Descripción:</div><div class="datasegmento">
                      <input id="lblReb_Des" name="lblReb_Des" type="text" class="label ui-widget-content ui-corner-all"/></div>
                    </div>                                          
            </fieldset> 
            <br />
            <div class="row">
            	<button type="submit" class="btn btn-primary start" id="btnguarda" name="btnguarda" title="Guardar" >
                       <i class="icon-book icon-white"></i>
                       <span>Guardar</span>
                </button>                
            </div>
           </form>
        </div>                          
        </div>
    </div>


	  
</div>
</BODY>
</HTML>
<?php
$obBD_conexion->cerrar();
?>