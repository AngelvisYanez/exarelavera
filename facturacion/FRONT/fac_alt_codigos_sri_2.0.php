<?php	
/**
* @abstract Permite realizar la cancelacion de comprobantes por abonos
* @author Erik Niebla
* @version 1.0
* Fecha de creaci�n  2015-07-22
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_codigos_sri.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
	
/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Cod($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_Cod;

$hoy = date("Y-m-d");
$mes = date("m");

if(isset($cuenAjax)){ 
    /** 
    * Consultar el plan de cuenta activo
    */
    //$row_rs_con_plan = $obBD_con1->getRowConsulta(4, $Ses_Emp_Cod, $obBD_conexion);	
    //$Pla_Cod=$row_rs_con_plan['Pla_Cod'];
    $contar = $obBD_con1->getRowConsulta(18, $search.'*'.$Ses_Emp_Cod.'*'.$Pla_Cod.'*'.$op_opciones.'*', $obBD_conexion);	      
    $pagination= pages($contar['total'], $page, $rows);
    $responce=$pagination['data'];
    $responce['rows']=  $obBD_con1->getArrayConsulta(18, $search.'*'.$Ses_Emp_Cod.'*'.$Pla_Cod.'*'.$op_opciones.'*'.$pagination['limits'], $obBD_conexion);	    
    utf8_encode_deep($responce['rows']);echo json_encode($responce);exit();
}
if(isset($saveCod)){    
    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);              
    $obBD_con1->operacionobBD(20, $Ren_Cod.'*'.$Ren_Con.'*'.$Ren_Ret.'*'.$Adq_Cod, $obBD_conexion);	
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);   
    if($obBD_con1->Error==0){ $responce['success']=true;} else{$responce['success']=false;$responce['message']=$obBD_con1->MsgError;}
    echo json_encode($responce);
    exit();
}
if(isset($addCuenta)){ 
    $responce['tipo']=$addCuenta;
    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);              
    $obBD_con1->operacionobBD(19, $Ren_Cod.'*'.$Pld_Cod.'*'.$addCuenta, $obBD_conexion);	
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);   
    if($obBD_con1->Error==0){ $responce['success']=true;} else{$responce['success']=false;$responce['message']=$obBD_con1->MsgError;}
    echo json_encode($responce);
    exit();
}
if(isset($deleteCuenta)){ 
    $responce['tipo']=$deleteCuenta;
    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);              
    $obBD_con1->operacionobBD(17, $Ren_Cod.'*'.$Pld_Cod.'*'.$deleteCuenta, $obBD_conexion);	
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);   
    if($obBD_con1->Error==0){ $responce['success']=true;} else{$responce['success']=false;$responce['message']=$obBD_con1->MsgError;}
    echo json_encode($responce);
    exit();
}

if(isset($codAjax)){ 
    $responce['rows'] = $obBD_con1->getArrayConsulta(9, $search, $obBD_conexion);
    echo json_encode($responce);exit();
}
if(isset($listTipo)){ 
    //$row_rs_con_plan = $obBD_con1->getRowConsulta(4, $Ses_Emp_Cod, $obBD_conexion);	    
    $responce['rows'] = $obBD_con1->getArrayConsulta(12, $Ren_Cod.'*'.$Ses_Emp_Cod.'*'.$listTipo.'*'.$Pla_Cod, $obBD_conexion);
    echo json_encode($responce);exit();
}
if(isset($guardarCodigo)){
    $data=$_POST;
    $obBD_con1->operacionobBD(15, $data[Ren_Sri], $obBD_conexion);
    $responce = $obBD_con1->operacionobBD(220, $data, $obBD_conexion);
    if($obBD_con1->Error==0) {
        $responce=array('success'=>false,'message'=>'No se pudo realizar la transaccion!','error'=>$obBD_con1->MsgError);
    }
    utf8_encode_deep($responce); 
    echo json_encode($responce);exit();
}
$periodos = $obBD_con1->getArrayConsulta(21, $Ses_Emp_Cod, $obBD_conexion);
$periodo = $periodos[0];
?>
<!DOCTYPE html>
<HTML>
    <HEAD>		
        <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
        <TITLE><?Php echo "Param. Cod.Sri Registrar [EXA]"; ?></TITLE>
        <meta charset= "UTF-8">
        <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php")?>              
        <style></style>
    </HEAD>
<BODY>
 
    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Registrar C&oacute;digos SRI</h3></div>
        
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">  
                <div class="row">
                    <div id="grilla">
                    <div class="col-sm-12">
                        <fieldset class="exa-fieldset">
                            <form id="empForm" class="form-horizontal normal"> 
                                <div class="form-group">
                                    <fieldset class="exa-fieldset">
                                <legend class="Titulos2">Datos del Código</legend> 
                                <div class="form-group">
                                    <label class="col-sm-4 control-label label-sm required">Código SRI:</label>
                                    <div class="col-sm-2">
                                        <input type="text" name="Ren_Sri" id="Ren_Sri" class="form-control input-sm">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label label-sm required">Porcentaje(%):</label>
                                    <div class="col-sm-2">
                                        <input name="Ren_Por" id="Ren_Por" class="form-control input-sm">
                                    </div>
                                </div>
                                <?php $row_rs_adqui = $obBD_con1->getArrayConsulta(16,'',$obBD_conexion); /* Consulta adquisicion  */ ?> 
                                <div class="form-group">
                                    <label class="col-sm-4 control-label label-sm required">Bienes/Servicios:</label>
                                    <div class="col-sm-3">
                                        <select name="Adq_Cod" id="Adq_Cod" class="form-control input-sm">
                                            <option value="">Seleccione...</option>                
                                            <?php foreach($row_rs_adqui as $row){?>
                                                    <option value="<?php echo $row['Adq_Cod']; ?>" ><?php echo $row['Adq_Des'];?></option>
                                            <?php }?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label label-sm required">Renta/Iva:</label>
                                    <div class="col-sm-3">
                                        <select name="Ren_Ret" id="Ren_Ret" class="form-control input-sm">
                                            <option value="">Seleccione...</option>                
                                            <option value="R">Renta</option>
                                            <option value="I">Iva</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label label-sm required">Descripción:</label>
                                    <div class="col-sm-5">
                                        <input name="Ren_Con" id="Ren_Con" type="text" class="form-control input-sm" value="" maxlength="200" style="text-transform:uppercase" />
                                    </div>
                                </div>
                            </fieldset>  
                            <div class="form-group center">
                                <div class="col-sm-12">
                                    <button type="button" class="btn btn-success btn-sm" title="Guardar" onclick="guardar();"> <i class="glyphicon glyphicon-floppy-disk"></i> <span>Guardar</span> </button>
                                </div>
                            </div>
                            <div class="form-group center">
                                <div class="Titulos2"><hr><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco (  <span class="required"></span> ) son campos obligatorios.</div>
                                </div>
                            </div>                            
                            
                            </form>
                        </fieldset>        
                    </div>
                    </div> 
                </div>
            </div>   
        </div>
    </div>
    <div style="display: none">
    <form id="formDeudas">
        <input type="text" name="Pla_Cod" value="<?php echo $periodo['Pla_Cod']; ?>" />
        <input type="text" name="Pec_Cod" value="<?php echo $periodo['Pec_Cod']; ?>" />
    </form>
    </div>    
<!--INICIO DEL DIALOGO BUSCAR CUENTA--> 
    <div id="cuenDialog" title="B&uacute;squeda de Cuentas">  
        <form class="form-horizontal normal">       
        <fieldset>
		<legend>Filtros</legend>
                <div class="form-group">
                    <label class="col-md-2 control-label label-xs">Filtrar Por:</label>  
                    <div class="col-md-5 radioset" >
                          <input id="radc1" name="op_opciones" type="radio" value="d" checked="" onclick="setfocus(this.form.search)" alt="" /><label for="radc1">&nbsp;&nbsp;Descripci&oacute;n&nbsp;&nbsp;</label>
                          <input id="radc2" name="op_opciones" type="radio" value="c" onclick="setfocus(this.form.search)" alt="" /><label for="radc2">&nbsp;&nbsp;C&oacute;digo&nbsp;&nbsp;</label>                          
                    </div>                   
                    <div class="col-md-4"> <label class="control-label label-xs">Plan de Cuentas:</label>                       
                        <input name="periodo" type="text" size="6" value="<?php echo $periodo['Pla_Fec']?>" readonly style="text-align: center;display: inline-block;width: auto;" class="form-control input-xs" /> 
                        <input name="Pec_Cod" type="hidden" value="<?php echo $periodo['Pec_Cod']?>" /> 
                        <input name="Pla_Cod" type="hidden" value="<?php echo $periodo['Pla_Cod']?>" />
                    </div>    
                </div>
                <div class="form-group">
                    <label class="col-md-2 control-label">B&uacute;squeda:</label>  
                    <div class="col-md-7" >
                        <div class="input-group">                        
                        <input name="search" onkeydown="if (event.keyCode === 13) this.form.submit()" type="text" size="50" maxlength="50" placeholder="Ingrese cuenta a buscar..." autofocus  class="form-control input-sm "/>
                        <span class="input-group-btn"><button type="button" onclick="this.form.submit()" class="btn btn-success btn-sm" title="Buscar cuenta" ><span class="glyphicon glyphicon-search"></span> <span>Buscar</span></button></span>
                      </div><!-- /input-group --> 
                    </div>                    
                </div>
        </fieldset>  
       </form> 
    </div> 
    <script>
        $.createSearchDialog('cuenDialog',[
            { label: 'C&oacute;d.Int.', name: 'Pld_Cod', key: true, width: 15,align:"center",hidden:true },                                
            { label: 'Codigo', name: 'Pld_Cdc', width: 45 },                      
            { label: 'Cuenta', name: 'Pld_Des', width: 80, cellattr: function (rowId, tv, rawObject, cm, rdata) { return 'style="white-space: normal;"'; }},
            { label: 'Grupo', name: 'Pld_Grupo', width: 110, cellattr: function (rowId, tv, rawObject, cm, rdata) { return 'style="white-space: normal;"'; }},
            { label: 'Tipo', name: 'Pld_Tip', width: 30,align:"center" },
            { label: 'Estado', name: 'Pld_Est', width: 30,align:"center"}, 
                { label:'<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 30, align: 'center',viewable: false,
                    formatter:function (cellvalue, options, rowObject) { 
                        return  $.getGridButton(addCuenta,rowObject.Pld_Cod,'Agregar Cuenta'); 
                    }
                }
        ]);
    </script>
<!-- FIN DEL DIALOGO CUENTAS-->
   <script type="text/javascript">
        function guardar(){
            var cod = $('#Ren_Sri').val();
            var porc = $('#Ren_Por').val();
            var descrp = $('#Ren_Con').val();
            var adq = $('#Adq_Cod').val();
            var ren = $('#Ren_Ret').val();
            if(!cod=="" && !porc=="" && !descrp==""){
                $.post( "",{guardarCodigo:true, Ren_Sri:cod, Ren_Por:porc, Ren_Con:descrp, Adq_Cod:adq, Ren_Ret:ren }, function(response){
                        console.log(response);
                        $.alert('Transaccion Realizada con Éxito');
                        document.getElementById("empForm").reset();
                }).fail(function() { 
                    $.alert("El servidor ha fallado en responder!"); 
                //console.log(error); 
            });
            }else{
                $.alert("Llene todos los campos");
            }
        }

        var tipo='';
        function deleteCuenta(cuenta){  
            $.saveDataJson("<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>",$.extend({Ren_Cod:$('#Ren_Cod').val()},cuenta), 
                function( r ) { $("#"+(r['tipo']==='C'?'compras':'ventas')).jqGrid().trigger("reloadGrid", [{ page: 1 }]); }
            );
        }
        function addCuenta(a2){  
            $('#cuenDialog').dialog('close');
            $.saveDataJson( "<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>",{Ren_Cod:$('#Ren_Cod').val(),Pld_Cod:a2,addCuenta:tipo},
                function(r) { $("#"+(r['tipo']==='C'?'compras':'ventas')).jqGrid().trigger("reloadGrid", [{ page: 1 }]); }
            );
        }
        function saveCod(){  
            $.saveDataJson( "<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>",$('#codForm').getData('saveCod'), 
                function(r){ $("#list").jqGrid().trigger("reloadGrid", [{ page: 1 }]); }
            );
        }
        function selectEmp(id){          
            $('#grilla').moveComp('#formulario').updateGridsSizes();
            var dataFromRow= $("#list").jqGrid('getRowData', id),perio=$('#selectPecCod').find('option:selected').data();
            dataFromRow['Ren_Ret']=(dataFromRow['Ren_Ret']==='RENTA'?'R':'I');
            $('#codForm').setData(dataFromRow);            

            $("#compras").jqGrid('setGridParam',{datatype:'json',postData: {Ren_Cod:dataFromRow['Ren_Cod'],Pla_Cod:perio['placod'],listTipo:'C'}}).trigger("reloadGrid", [{ page: 1 }]);
            $("#ventas").jqGrid('setGridParam',{datatype:'json',postData: {Ren_Cod:dataFromRow['Ren_Cod'],Pla_Cod:perio['placod'],listTipo:'V'}}).trigger("reloadGrid", [{ page: 1 }]);            
        }
        $( document ).ready(function() {
            $("#list").createGrid({                
                postData: $("#empForm").getData("codAjax"), height: 250, rowNum: 1000000, pgbuttons: false, pgtext:null,
                colModel: [
                    { label: 'Cod. Int.', name: 'Ren_Cod', key: true, width: 20,align:"center", hidden:false },  
                    { label: 'Cod. Int.', name: 'Adq_Cod', width: 20,align:"center", hidden:true },  
                    { label: 'Cod. SRI', name: 'Ren_Sri', width: 35,align:"center" }, 
                    { label: 'Descripción', name: 'Ren_Con', width: 180,align:"left" },
                    { label: 'Porcentaje(%)', name: 'Ren_Por', width: 35,align:"right",formatter:'number',formatoptions: {suffix:' %'} },
                    { label: 'Bienes/Servicios', name: 'Ren_Tip', width: 50 ,align:"center" },
                    { label: 'Renta/IVA', name: 'Ren_Ret', width: 50 ,align:"center" },
                        { label:'&nbsp;', name: 'act1', width: 30, align: 'center',viewable: false,
                            formatter:function (cellvalue, options, rowObject) { return  $.getGridButton(selectEmp,rowObject.Ren_Cod,'Seleccionar'); }
                        }
                ]             
            },false,"#listPager",{refresh: true, view: true});
            
            $("#compras").createGrid({
                height: 50, caption:'<b>COMPRAS&raquo;</b> Cuentas Contables',
                colModel: [    
                    { label: 'Ren Cod', name: 'Ren_Cod', width: 30,align:"center", hidden:false },
                    { label: 'Cod. Int.', name: 'Pld_Cod', key: true, width: 30,align:"center", hidden:false }, 
                    { label: 'Cuenta', name: 'Pld_Cdc', width: 50 ,align:"center"}, 
                    { label: 'Descripci&oacute;n', name: 'Pld_Des', width: 90,align:"left" },
                        { label:'&nbsp;', name: 'act1', width: 30, align: 'center',viewable: false,
                            formatter:function (cellvalue, options, rowObject) { return  $.getGridButton(deleteCuenta,{Pld_Cod:rowObject.Pld_Cod,deleteCuenta:'C'},'Eliminar','remove',null,'danger'); }
                        }
                ],
                loadComplete:function (){var ids = $("#compras").jqGrid('getDataIDs'); if(ids.length===0) $('#btnCompra').removeAttr('disabled'); else $('#btnCompra').attr('disabled','disabled'); }
            },true); 
            $("#ventas").createGrid({
                height: 50, caption:'<b>VENTAS&raquo;</b> Cuentas Contables', 
                colModel: [    
                    { label: 'Ren Cod', name: 'Ren_Cod', width: 30,align:"center", hidden:false },
                    { label: 'Cod. Int.', name: 'Pld_Cod', key: true, width: 30,align:"center", hidden:false }, 
                    { label: 'Cuenta', name: 'Pld_Cdc', width: 50 ,align:"center"}, 
                    { label: 'Descripci&oacute;n', name: 'Pld_Des', width: 90,align:"left" },
                        { label:'&nbsp;', name: 'act1', width: 30, align: 'center',viewable: false,
                            formatter:function (cellvalue, options, rowObject) { return  $.getGridButton(deleteCuenta,{Pld_Cod:rowObject.Pld_Cod,deleteCuenta:'V'},'Eliminar','remove',null,'danger'); }
                        }
                ],
                loadComplete:function (){var ids = $("#ventas").jqGrid('getDataIDs'); if(ids.length===0) $('#btnVenta').removeAttr('disabled'); else $('#btnVenta').attr('disabled','disabled'); }
            },true);  
            
            $('#selectPecCod').on('change',function (){
                var data=$(this).find('option:selected').data();
                $('input[name="Pla_Cod"]').val(data['placod']);
                $('input[name="Pec_Cod"]').val(data['peccod']);
                $('input[name="periodo"]').val(data['anio']);
                $('#anio').html(data['anio']);
                $("#compras").jqGrid('setGridParam',{datatype:'json',postData: {Ren_Cod:$('#Ren_Cod').val(),Pla_Cod:data['placod'],listTipo:'C'}}).trigger("reloadGrid", [{ page: 1 }]);
                $("#ventas").jqGrid('setGridParam',{datatype:'json',postData: {Ren_Cod:$('#Ren_Cod').val(),Pla_Cod:data['placod'],listTipo:'V'}}).trigger("reloadGrid", [{ page: 1 }]);            
                $.Search('cuen');                
            });
       });
   </script>
</BODY>
</HTML>