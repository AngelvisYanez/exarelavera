<?php	
/**
* @abstract Permite registrar los cheques 
* @author Erik Niebla
* @version 1.0
* Fecha de creaci�n  2015-07-08
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tes_log_cheque.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require_once('../../Librerias/postclass.php');	
/**
* Creacion del Objeto de conexion
*/
$obBD_conexion = new Class_Log_Conexion_Che($Ses_Dat_Dis);
/** 
* Cracion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Che;
/**
* Evita el reenvio 
*/
$thisPost = new Post_Block;

$hoy = date("Y-m-d");
$mes = date("m");

if(isset($gridAjax)){ 	
        if(isset($fechas)) $fechas=" AND Che_Fec BETWEEN '$txt_fec_ini 00:00:00' AND '$txt_fec_fin 23:59:59'"; else $fechas='';
        $rs_buscar = $obBD_con1->getArrayConsulta(340, $Ses_Emp_Cod.'*'.$bancos.'*'.$fechas,$obBD_conexion);        
        $responce['rows']=$rs_buscar;utf8_encode_deep($responce['rows']);
        $responce['page'] = 1;$responce['total'] = 1;
        $responce['records'] = count($rs_buscar);         	
	echo json_encode($responce);exit();
}
if(isset($save)){         
        $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
        foreach ($save as $row)        
                $obBD_con1->grabarv_registros(sentencias_che(341,$obBD_con1->parametros($row['estado'].'*'.$row['cobro'].'*'.$row['id'])), $obBD_conexion->conexion);                       		           
        $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);       
        if($obBD_con1->Error==0) $responce['success']=true; else $responce['success']=false;$responce['message']=$obBD_con1->MsgError;
	echo json_encode($responce);exit();
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<HTML>
	<HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
		<?Php require_once("../../mascaras/model1/estilos/basic.php"); ?>
                <?Php require_once("../../mascaras/model1/estilos/jqgrid.php")?> 
                <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
                <style>input:disabled{background: #717777;}</style>
	</HEAD>
<BODY>
<div id="set1">
    <table width="100%" border="0" cellpadding="0" cellspacing="0" class="table">
	<tr class="BarraTitulo">
	  <td height="10">&raquo; Registrar Cheques Cobrados/Protestados<?Php echo $periodo; ?></td>
        </tr>
      <tr>
      <td height="389" align="left" valign="top">
        <form action="" method="post" name= "form1" id= "form1">
            <FIELDSET>
		<LEGEND>
                    <label class="Titulos2">Seleccionar Bancos</label>
		</LEGEND>
		<table width="600" height="36" border="0" cellpadding="0" cellspacing="0" >
			<tr>
			  <td width="77" class="BarraBusqueda"><div align="right" >Banco:</div></td>
			  <td width="350" class="BarraBusqueda">
                              <select name="bancos" id="bancos" style="width: 381px;">
                                  <option value="" ><< TODOS >></option>
<?php
    $rs_bancos = $obBD_con1->getArrayConsulta(339,$Ses_Emp_Cod, $obBD_conexion);
    if (count($rs_bancos) > 0) 
    { 
        foreach ($rs_bancos as $row){  
?>
                                  <option value="<?php echo $row['Ban_Cod']; ?>"><?php echo $row['Pld_Des']." (Cta.#: ".$row['Ban_Cue'].")"; ?></option>
<?php
        }
    }
?>
                              </select>
                          </td>                        
			  <td width="118"><div align="center">	
                                <input name="txt_busqueda" type="hidden" id="txt_busqueda" />
                                <button type="button" class="btn btn-success" title="Buscar Cheques" onclick="$('#list').Search('#form1','gridAjax');setCaption();"> <i class="icon-search icon-white"></i> <span>Buscar</span> </button>
                            </div>
                          </td>
			</tr>
                    <tr>
                        <td colspan="3">
                            <table>
                                <tr>
                                    <td align="right" style="padding-left: 10px;">Desde:</td>
                                    <td><input name="txt_fec_ini" type="text" id="txt_fec_ini" size="10" class="text ui-corner-all" style="text-align: center;" disabled /></td>
                                    <td>&nbsp;&nbsp;&nbsp;Hasta:</td>
                                    <td><input name="txt_fec_fin" type="text" id="txt_fec_fin" size="10" class="text ui-corner-all" style="text-align: center;" disabled /></td>
                                    <td style="padding-left: 10px;"><input type="checkbox" id="fechas" name="fechas" /> Por Fecha</td>
                                </tr>
                            </table>
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
                 <script type="text/javascript">  
                    function saveRows() {
                        var batch = new Array();
                        var grid = $("#list");
                        var ids = grid.jqGrid('getDataIDs');
                        for (var i = 0; i < ids.length; i++) {                                
                            grid.jqGrid('saveRow', ids[i], false, 'clientArray');                              
                            var datos = grid.jqGrid('getRowData', ids[i]);                              
                            var data={};
                                data['id'] = ids[i];
                                data['estado'] = datos['Che_Est'];
                                data['cobro'] = datos['Che_Cob'];
                            if(data['cobro']!==""&&data['estado']!=="A"&&data['estado']!=="")
                                batch.push(data);                                   
                        }
                        if(batch.length>0){ 
                            $.post( "<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>",{save:batch}, function( response ) {
                                if(response['success']===true){
                                    $.alert("Transaccion Realizada con &Eacute;xito!");$('#list').Search('#form1','gridAjax');
                                }else{$.alert(response['message']);}                                   
                             },'json').fail(function(error) { $.alert("El Servidor ha fallado en responder!"); });
                        }else{$("#list").startGridEdit();$.alert("No has Realizado Cambios!");}
                    }
                    $(document).ready(function () { 
                        $.datepicker.setDefaults($.datepicker.regional["es"]);
                        public $list=$("#list");
                        $list.jqGrid({
                            url: '<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>',
                            mtype: "GET", datatype: "local", regional : 'es',//ajaxRowOptions: { async: true },
                            //postData: $("#form1").getData("ajaxGrid"),
                            autowidth : true, shrinkToFit: true, height: 270,caption:' ',hidegrid:false,
                            cmTemplate: {sortable:false},
                            colModel: [
                                { label: 'Cód.Int.', name: 'id', key: true, width: 15,align:"center", hidden:true },                                
                                { label: 'Beneficiario', name: 'proveedor', width: 200 },                      
                                { label: 'Banco', name: 'banco', width: 180  },
                                { label: 'No. Che.', name: 'Che_Num', width: 40, align:"center"},                                         
                                { label: 'Fecha', name: 'Che_Fec', width: 40 },                                
                                { label: 'Valor', name: 'Che_Val', width: 70, align: 'right', formatter:'currency',
                                        formatoptions: {prefix:'$ ', thousandsSeparator:'.'}
                                },
                                { label: 'Estado', name: 'Che_Est', width: 60,editable: true,viewable: false,title:false,
                                    edittype: "select",formatter:'select', editoptions: {value: "A:No Cobrado;C:Cobrado;P:Protestado"}
                                },
                                { label: 'Cobro', name: 'Che_Cob', width: 40,editable: true,edittype:"text",viewable: false,                                    
                                    editoptions: {
                                        dataInit: function (element) {
                                            $(element).datepicker({changeMonth: true,changeYear: true, dateFormat: 'yy-mm-dd',firstDay: 1});  
                                        }
                                    }
                                },
                                    { label:'&nbsp;', name: 'act1', width: 30, align: 'center',viewable: false,
                                        formatter:function (cellvalue, options, rowObject) { 
                                            return  '<span class="btn btn-info btn-mini" title="Ver" type="button" onclick="$(\'#list\').viewGridRow(\''+rowObject.id+'\');"><i class="icon-info-sign icon-white"></i></span>';                                            
                                        }
                                    }
                            ],  footerrow:true,                                                   
                            rowNum: 10000000, pager: "#listPager", gridview: true, rownumbers: true, viewrecords: true, altRows: true, altclass: "myAltRowClass",pgbuttons: false,pgtext: null,
                            loadComplete: function(){ $("#list").startGridEdit();$("#list").jqGrid('footerData', 'set',{Che_Fec:'<div style="text-align:right;">TOTAL:</div>',Che_Val:'0.00'});$('select[name=Che_Est]').attr('onChange','updateTotal()');}
                        });                        
                        $list.navGrid('#listPager',{ edit: false, add: false, del: false, search: false, refresh: true, view: true, position: "left", cloneToTop: false });
                        $list.jqGrid('bindKeys'); 
                        $.createDateRange('#txt_fec_ini','#txt_fec_fin');
                        $('#fechas').on('change',function(){ $('#txt_fec_ini').toggleAttr('disabled');$('#txt_fec_fin').toggleAttr('disabled');});
                        
                        /* SOLO COLORES */
                        var grid='list', $footRow = $("#gbox_"+grid+" #gview_"+grid+" .ui-jqgrid-sdiv .footrow");
                            $footRow.find('>td[aria-describedby="'+grid+'_proveedor"]').css("border-right-color", "transparent");                            
                            $footRow.find('>td[aria-describedby="'+grid+'_Che_Num"]').css("border-right-color", "transparent");                                 
                            $footRow.find('>td[aria-describedby="'+grid+'_Che_Est"]').css("border-right-color", "transparent");
                            $footRow.find('>td[aria-describedby="'+grid+'_Che_Cob"]').css("border-right-color", "transparent");
                            $footRow.find('>td[aria-describedby="'+grid+'_proveedor"]').css("background-color", "white");
                            $footRow.find('>td[aria-describedby="'+grid+'_banco"]').css("background-color", "white");                            
                            $footRow.find('>td[aria-describedby="'+grid+'_Che_Est"]').css("background-color", "white");
                            $footRow.find('>td[aria-describedby="'+grid+'_Che_Cob"]').css("background-color", "white");
                            $footRow.find('>td[aria-describedby="'+grid+'_act1"]').css("background-color", "white");
                    });  
                    function setCaption(){
                        var caption="Cheques Girados y No Cobrados";
                            if($('#bancos').val()!=='') caption=caption+' - '+ $('#bancos').find('option:selected').text();
                            if($('#fechas').is(':checked')) caption=caption+' - Desde '+$('#txt_fec_ini').val()+' Hasta '+$('#txt_fec_fin').val();
                            
                            //if($('#PrvCodBus').val()!=='') caption=caption+' - '+$('#lblProv').val();
                            $('#list').jqGrid('setCaption', caption);
                    }
                    function updateTotal(){  
                        var grid=$('#list');
                        var ids = grid.jqGrid('getDataIDs'),suma=0; 
                        for (var i = 0; i < ids.length; i++){
                            if($('#'+ids[i]+'_Che_Est').val()==='C')
                                suma=suma+(grid.jqGrid('getRowData', ids[i]).Che_Val*1);
                        }
                        grid.jqGrid('footerData', 'set',{Che_Val:suma});
                    }
               </script>
	</FIELDSET>
        </td>
      </tr>
      <tr>
          <td>
              <div style="padding:15px;">
                  <button type="button" class="btn btn-primary start" onclick="javascript:$.createDialogConfirm(null,null,saveRows)" title="Guardar Cheques Cobrados"> <i class="icon-book icon-white"></i> <span>Guardar</span></button>
              </div>
          </td>
      </tr>
    </table>
   	
</div>	
</BODY>
</HTML>