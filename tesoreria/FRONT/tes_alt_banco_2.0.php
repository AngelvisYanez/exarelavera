<?php	
/**
* @abstract Permite realizar la cancelacion de comprobantes por abonos
* @author Erik Niebla
* @version 1.0
* Fecha de creaci�n  2015-07-22
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tes_log_banco.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
	
/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Tip($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_Tip;

$hoy = date("Y-m-d");
$mes = date("m");

if(isset($cuenAjax)){ 
    /** 
    * Consultar el plan de cuenta activo
    */
    $row_rs_con_plan = $obBD_con1->getRowConsulta(41, $Ses_Emp_Cod, $obBD_conexion);	
    $Pla_Cod=$row_rs_con_plan['Pla_Cod'];
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
if(isset($guardarBanco)){
    $data=$_POST;
    $responce = $obBD_con1->operacionobBD(21, $data, $obBD_conexion);
    $obBD_con1->operacionobBD(21, $data, $obBD_conexion);
    for($x=1;$x<=$cont;$x++){
          if($ckh[$x]!=""){
              $obBD_con1->operacionobBD(11, $ckh[$x].'*'.$Ban_Cod, $obBD_conexion);
}
        }
    if($obBD_con1->Error==0) {
        $responce=array('success'=>false,'message'=>'No se pudo realizar la transaccion!','error'=>$obBD_con1->MsgError);
    }
    utf8_encode_deep($responce); 
    echo json_encode($responce);exit();
}
if(isset($listarCuentas)){

    $responce['rows'] = $obBD_con1->getArrayConsulta(41, $search.'*'.$Pla_cod.'*'.$op_opciones, $obBD_conexion);
    //ChromePhp::log($responce['rows']);
    echo json_encode($responce['rows']);
    exit();
}

$periodos = $obBD_con1->getArrayConsulta(21, $Ses_Emp_Cod, $obBD_conexion);
$periodo = $periodos[0];
?>
<!DOCTYPE html>
<HTML>
    <HEAD>		
        <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
        <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php")?>              
        <style></style>
    </HEAD>
<BODY>
 
    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Registrar Bancos</h3></div>
        
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">  
                <div class="row">
                    <div id="grilla">
                    <div class="col-sm-12">
                        <fieldset class="exa-fieldset">
                            <form id="empForm" class="form-horizontal normal"> 
                                <div class="form-group">
                                    <fieldset id="bancosForm" class="exa-fieldset">
                                <legend class="Titulos2">Datos a registrar</legend> 
                                <div class="form-group">
                                    <label class="col-sm-4 control-label label-sm required">Plan de cuentas:</label>
                                    <div class="col-sm-2">
            <?php
            /** Consultar los Planes de cuenta*/
            $row_rs_planc = $obBD_con1->getArrayConsulta(1, strtoupper($Ses_Emp_Cod), $obBD_conexion);
            ?>
              <select class="form-control" name="Pla_Cod" id="Pla_Cod">
                <option value=''>Seleccione...</option>
                <?php foreach($row_rs_planc as $datos){?>  
                <option value='<?php echo $datos['Pla_Cod']?>'><?php echo $datos['Pla_Obs']?></option>
                <?php }?>
                </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label label-sm required">Cuenta contable:</label>
                                    <div class="col-sm-2">
                                        <input name="Pld_Des" id="Pld_Des" class="form-control input-sm" >
                                        <input name="Pld_Cod" id="Pld_Cod" class="form-control input-sm" style="display: none;">
                                    </div>
                                    <button class="btn btn-success btn-sm" onclick="$('#cuenDialog').dialog('open');" title="Buscar cuenta" type="button"><i class="glyphicon glyphicon-list"></i> Cuenta contable</button>
                                </div>
                                <?php $row_rs_adqui = $obBD_con1->getArrayConsulta(16,'',$obBD_conexion); /* Consulta adquisicion  */ ?> 
                                <div class="form-group">
                                    <label class="col-sm-4 control-label label-sm required">Tipo cuenta:</label>
                                    <div class="col-sm-2">
                                        <select class="form-control" name="Ban_Tip" id="Ban_Tip">
                                            <option value=''>Seleccione...</option>             
                                            <option value='C'>Caja</option>
                                            <option value='B'>Banco</option>
                                            <option value='O'>Otros</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label label-sm required">Cuenta bancaria #:</label>
                                    <div class="col-sm-2">
                                        <input class="form-control" type="text" name="Ban_Cue" id="Ban_Cue">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label label-sm required">Observaciones:</label>
                                    <div class="col-sm-5">
                                        <input name="Ban_Obs" id="Ban_Obs" type="text" class="form-control input-sm" value="" maxlength="200" style="text-transform:uppercase" />
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label label-sm required">Tipo de pago:</label>
                                    <div class="col-sm-5">
                                        <?php
            /** 
            * Consultar los Planes de cuenta
            */
            $row_rs_tipo = $obBD_con1->getArrayConsulta(10,'', $obBD_conexion);     
            $i=0;
          ?>
              <?php foreach($row_rs_tipo as $datos){$i++;?>
              <input type="checkbox" id="ckh[<?php echo $i;?>]" name="ckh[<?php echo $i;?>]" value='<?php echo $datos['Pag_Cod']?>' />
              <?php echo $datos['tipo']?><br />
              <?php }?>
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
                    <div class="col-md-4">
                        <input name="Pec_Cod" type="hidden" value="<?php echo $periodo['Pec_Cod']?>" /> 
                        <input name="Pla_Cod" type="hidden" value="<?php echo $periodo['Pla_Cod']?>" />
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-md-2 control-label">B&uacute;squeda:</label>  
                    <div class="col-md-7" >
                        <div class="input-group">                        
                        <input name="search" onkeydown="if (event.keyCode === 13) this.form.submit()" type="text" size="50" maxlength="50" placeholder="Ingrese cuenta a buscar..." autofocus  class="form-control input-sm "/>
                        <span class="input-group-btn"><button type="button" onclick="this.form.submit();" class="btn btn-success btn-sm" title="Buscar cuenta" ><span class="glyphicon glyphicon-search"></span> <span>Buscar</span></button></span>
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
                    return  $.getGridButton(addCuenta,rowObject,'Agregar Cuenta'); 
                }
            }
        ]);
    </script>
<!-- FIN DEL DIALOGO CUENTAS-->
   <script type="text/javascript">

        function guardar(){
            var cod = $('#Pld_Cod').val();
            var num = $('#Ban_Cue').val();
            var obs = $('#Ban_Obs').val();
            var tip = $('#Ban_Tip').val();
            if(!cod=="" && !obs==""){
                $.post( "",{guardarBanco:true, Pld_Cod:cod, Ban_Cue:num, Ban_Obs:obs, Ban_Tip:tip }, function(response){
                        $.alert('Transaccion Realizada con �xito');
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
            $.saveDataJson("<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>",$.extend({Ren_Cod:$('#Ren_Cod').val()},cuenta), 
                function( r ) { $("#"+(r['tipo']==='C'?'compras':'ventas')).jqGrid().trigger("reloadGrid", [{ page: 1 }]); }
            );
        }
        function addCuenta(id){  
            $('#Pld_Des').val(id['Pld_Des']);
            $('#Pld_Cod').val(id['Pld_Cod']);
            $('#cuenDialog').dialog('close');
        }
        function saveCod(){
            $.saveDataJson( "<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>",$('#codForm').getData('saveCod'), 
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
                    { label: 'Descripci�n', name: 'Ren_Con', width: 180,align:"left" },
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