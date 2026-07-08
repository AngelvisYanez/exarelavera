<?php	
/**
* @abstract Permite registrar los cheques 
* @author Erik Niebla
* @version 1.0
* Fecha de creaci�n  2015-07-08
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/con_log_planc_2.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require_once('../../Librerias/postclass.php');	
/**
* Creacion del Objeto de conexion
*/
$obBD_conexion = new Class_Log_Conexion_Con($Ses_Dat_Dis);
/** 
* Cracion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Con;
/**
* Evita el reenvio 
*/
$thisPost = new Post_Block;

$hoy = date("Y-m-d");
$mes = date("m");

$rs_bancos = $obBD_con1->getArrayConsulta(324,$Ses_Emp_Cod, $obBD_conexion);
if(isset($gridAjax)){ 	
        $rs_buscar = $obBD_con1->getArrayConsulta(325, $Ses_Emp_Cod.'*'.$bancos.'*'.$search,$obBD_conexion);                
        $responce['page'] = 1;$responce['total'] = 1;$responce['records'] = count($rs_buscar); 
        $responce['rows']=$rs_buscar;utf8_encode_deep($responce['rows']);
	echo json_encode($responce);
	exit();
}
if(isset($delete)){  
        $rs_buscar = $obBD_con1->getRowConsulta(328, $Pld_Cod,$obBD_conexion);  
        $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
        if((($rs_buscar['total'])*1)==0)
            $obBD_con1->grabarv_registros(sentencias_con(327,$obBD_con1->parametros($Pld_Cod)), $obBD_conexion->conexion);                
        else
            $obBD_con1->grabarv_registros(sentencias_con(326,$obBD_con1->parametros($Pld_Cod)), $obBD_conexion->conexion);    
        $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);       
        if($obBD_con1->Error==0)$responce['success']=true;
	else $responce['success']=false; $responce['message']=$obBD_con1->MsgError;
        echo json_encode($responce);
	exit();
}
if(isset($cuentas)){  
    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
    foreach ($cuentas as $Pld_Cod){
        $rs_buscar = $obBD_con1->getRowConsulta(328, $Pld_Cod,$obBD_conexion);  
        
        if((($rs_buscar['total'])*1)==0)
            $obBD_con1->grabarv_registros(sentencias_con(327,$obBD_con1->parametros($Pld_Cod)), $obBD_conexion->conexion);                
        else
            $obBD_con1->grabarv_registros(sentencias_con(326,$obBD_con1->parametros($Pld_Cod)), $obBD_conexion->conexion);    
              
    }
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion); 
        
    if($obBD_con1->Error==0)$responce['success']=true;
    else $responce['success']=false; $responce['message']=$obBD_con1->MsgError;
    echo json_encode($responce);
    exit();
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<HTML>
	<HEAD>
		<!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
        <TITLE><?Php echo "Plan Cuenta Anular"; ?></TITLE>
        <meta charset= "Utf-8">
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<?Php require_once("../../mascaras/model1/estilos/basic.php"); ?>
                <?Php require_once("../../mascaras/model1/estilos/jqgrid.php")?>                 
	</HEAD>
<BODY>
<div id="set1">
    <table width="100%" border="0" cellpadding="0" cellspacing="0" class="table">
      <tr class="BarraTitulo"><td height="10">&raquo; Anular Cuentas<?Php echo $periodo; ?></td></tr>
      <tr>
      <td height="389" align="left" valign="top">
        <form action="javascript:$('#list').Search('#form1','gridAjax');" method="post" name= "form1" id= "form1">
            <FIELDSET>
		<LEGEND>
                    <label class="Titulos2">Seleccionar Bancos</label>
		</LEGEND>
		<table width="600" height="36" border="0" cellpadding="0" cellspacing="0" >
			<tr>
			  <td width="77" class="BarraBusqueda"><div align="right" >P&eacute;riodo:</div></td>
			  <td width="190" class="BarraBusqueda">
                              <select name="bancos" id="bancos" style="width: 180px;">
                                  <option value="" >Seleccione un Periodo..</option>
<?php
    
    if (count($rs_bancos) > 0) 
    { 
        foreach ($rs_bancos as $row){  
?>
                                  <option value="<?php echo $row['Pla_Cod']; ?>">Periodo: <?php echo $row['Ann']; ?></option>
<?php
        }
    }
?>
                              </select>
                          </td>
                          <td width="100" class="BarraBusqueda"><input type="text" name="search" placeholder="Ingrese Cuenta a Buscar..." class="clearable submit" /></td>
			  <td width="118"><div align="center">	
                                <input name="txt_busqueda" type="hidden" id="txt_busqueda" />
                                <button type="submit" class="btn btn-success" title="Buscar Cheques" > <i class="icon-search icon-white"></i> <span>Buscar</span> </button>
                            </div>
                          </td>
			</tr>
                 </table>
            </FIELDSET>
        </form>
            <FIELDSET>
		<LEGEND>
			<label class="Titulos2">Resultados de la busqueda</label>
		</LEGEND>
                <div>   
                                    <table id="list"></table>
                                    <div id="listPager"></div>
                </div>
                <div style="padding-top: 10px">
                    <button type="button" class="btn btn-danger start" onclick="confirmaCuentas();" title="Eliminar el Grupo de Cuentas"> <i class="icon-trash icon-white"></i> <span>Desactivar</span></button>
                </div>
                 <script type="text/javascript">  
                     function borraCuenta(id)
                        {
                            $.post( "<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>",{Pld_Cod:id,delete:true}, function( response ) {
                                if(response['success']===true){
                                    $.alert("Transaccion Realizada con &Eacute;xito!");$('#list').Search('#form1','gridAjax');
                                }else{$.alert(response['message']);}
                            },'json').fail(function(error) { $.alert("El Servidor ha fallado en responder!"); });
                        }
                    $(document).ready(function () {   
                        public $grid=$("#list");
                        $grid.jqGrid({
                            url: '<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>',
                            mtype: "GET", datatype: "local", regional : 'es',//ajaxRowOptions: { async: true },
                            //postData: $("#form1").getData("ajaxGrid"),
                            autowidth : true, shrinkToFit: true, height: 270,
                            cmTemplate: {sortable:false},
                            colModel: [
                                { label:'<center><i class="ui-icon ui-icon-circle-check"></i></center>', name: 'act', width: 10, align: 'center',viewable: false, formatter: 'checkbox',
                                    formatoptions: { disabled: false },resizable:false
                                },
                                { label: 'C&oacute;d.Int.', name: 'Pld_Cod', key: true, width: 20,align:"center"},                                
                                { label: 'C&oacute;digo', name: 'Pld_Cdc', width: 50 },                      
                                { label: 'Cuenta', name: 'Pld_Des', width: 200 },
                                { label: 'Tipo', name: 'Pld_Tip', width: 20,align:"center"  },                      
                                    { label:'<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 18, align: 'center',viewable: false,
                                        formatter:function (cellvalue, options, rowObject) { 
                                           var clic='$.createDialogConfirm("Esta seguro que desea <b><u>Eliminar</u></b> la Cuenta <b>'+rowObject.Pld_Des+'</b>.","'+rowObject.Pld_Cod+'",borraCuenta);';
                                            return  '<span class="btn btn-danger btn-mini" title="Eliminar" onclick=\''+clic+'\'><i class="icon-trash icon-white"></span>'; 
                                        }
                                    }
                            ],                                                     
                            rowNum: 10000000, pager: "#listPager", gridview: true, rownumbers: true, viewrecords: true, altRows: true, altclass: "myAltRowClass",pgbuttons: false,pgtext: null                          
                        });                        
                        $grid.navGrid('#listPager',{ edit: false, add: false, del: false, search: false, refresh: true, view: true, position: "left", cloneToTop: false })
                            .navSeparatorAdd("#listPager")
                            .jqGrid('navButtonAdd',"#listPager",{ caption:"Desmarcar Todo&nbsp;", buttonicon:"ui-icon-radio-off", onClickButton:function(){$grid.selectAllByComlumn('act',false);}, position: "last", title:"", cursor: "pointer"});
                        $grid.jqGrid('bindKeys'); 
                    });  
                    function confirmaCuentas(){
                            var cuentas=new Array();
                            var strCuentas='';
                            var grid=$('#list'),rows= grid.jqGrid('getRowData');
                            var ban=false;
                            for(var i=0;i<rows.length;i++){                                
                                if(rows[i].act==="Yes") 
                                {
                                    cuentas.push(rows[i]['Pld_Cod']);
                                    strCuentas=strCuentas+(ban?' - ':'')+'<u>'+rows[i]['Pld_Cdc']+'</u>';
                                    if(!ban) ban=!ban;
                                }
                            }
                            if(cuentas.length===0) $.alert('Debe seleccionar al menos una Cuenta!');
                            else $.createDialogConfirm('Esta seguro que desea <b><u>Eliminar</u></b> las <b>Cuentas Seleccionadas</b><br/> -> ('+strCuentas+')',cuentas,deleteCuentas);
                            //console.log(cuentas);
                    }
                    function deleteCuentas(cuentas){
                            $.post( "<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>",{cuentas:cuentas}, function( response ) {
                                if(response['success']===true){
                                    $.alert("Transaccion Realizada con &Eacute;xito!");$('#list').Search('#form1','gridAjax');
                                }else{$.alert(response['message']);}
                            },'json').fail(function(error) { $.alert("El Servidor ha fallado en responder!"); });
                    }
               </script>
	</FIELDSET>
        </td>
      </tr>
    </table>
   	
</div>	
</BODY>
</HTML>