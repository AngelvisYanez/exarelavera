<?php	
/**
* @abstract Permite realizar la cancelacion de comprobantes por abonos
* @author Erik Niebla
* @version 1.0
* Fecha de creaci�n  2015-07-22
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/con_log_planc_2.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
	
/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Con($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_Con;

$hoy = date("Y-m-d");
$mes = date("m");

if(isset($cuenAjax)){ 
    $contar = $obBD_con1->getRowConsulta(330, $search.'*'.$Ses_Emp_Cod.'*'.$Pec_Cod.'*'.$op_opciones.'*', $obBD_conexion);	      
    $pagination= pages($contar['total'], $page, $rows);
    $responce=$pagination['data'];
    $responce['rows']=  $obBD_con1->getArrayConsulta(330, $search.'*'.$Ses_Emp_Cod.'*'.$Pec_Cod.'*'.$op_opciones.'*'.$pagination['limits'], $obBD_conexion);	    
    utf8_encode_deep($responce['rows']);echo json_encode($responce);exit();
}
if(isset($pagado)){     
    $responce['rows']=  $obBD_con1->getArrayConsulta(331, $Pla_Cod, $obBD_conexion);	    
    utf8_encode_deep($responce['rows']);echo json_encode($responce);exit();
}

if(isset($cobrado)){     
    $responce['rows']=  $obBD_con1->getArrayConsulta(332, $Pla_Cod, $obBD_conexion);	    
    utf8_encode_deep($responce['rows']);echo json_encode($responce);exit();
}
if(isset($addCuenta)){ 
    $responce['tipo']=$addCuenta;
    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);   
        if($addCuenta==='cobrado')
            $obBD_con1->operacionobBD(333, $Pld_Cod, $obBD_conexion);
        else 
            $obBD_con1->operacionobBD(334, $Pld_Cod, $obBD_conexion);
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);   
    if($obBD_con1->Error==0){ $responce['success']=true; }else{ $responce=array(success=>false,message=>'No se pudo guardar el parametro!',error=>$obBD_con1->MsgError); }
    echo json_encode($responce); exit();
}
/*Elimina un iva*/
if(isset($deleteCuenta)){ 
    $responce['tipo']=$deleteCuenta;
    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
        if($deleteCuenta=='cobrado')
            $obBD_con1->operacionobBD(317, $Pld_Cod, $obBD_conexion);
        else
            $obBD_con1->operacionobBD(320, $Pld_Cod, $obBD_conexion);	        
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);
    if($obBD_con1->Error==0){ $responce['success']=true; }else{ $responce=array(success=>false,message=>'No se pudo Eliminar el parametro!',error=>$obBD_con1->MsgError); }
    echo json_encode($responce); exit();
}
$periodos = $obBD_con1->getArrayConsulta(329, $Ses_Emp_Cod, $obBD_conexion);
$periodo = $periodos[0];
?>
<!DOCTYPE html>
<HTML>
    <HEAD>		
        <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
        <TITLE><?Php echo "Parametrización Iva [EXA]"; ?></TITLE>
        <meta charset= "UTF-8">
        <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php")?>              
        <style></style>
    </HEAD>
<BODY>
 
    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Parametrización de IVA Pagado/Cobrado (Periodo <span id="anio"><?php echo $periodo['Pla_Fec'] ?></span>)</h3></div>
        
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">            
                <div class="row">
                    <div class="col-sm-4"></div>
                    <div class="col-sm-4">
                        <div class="form-horizontal normal">
                            <div class="form-group">
                                <label class="col-sm-5 control-label label-sm">Periodo Contable:</label>
                                <div class="col-sm-7">
                                    <select id="selectPecCod" class="form-control input-sm">
                                        <?php foreach ($periodos AS $row){ echo "<option value='$row[Pec_Cod]' data-placod='$row[Pla_Cod]' data-peccod='$row[Pec_Cod]' data-anio='$row[Pla_Fec]'>Periodo $row[Pla_Fec]</option>"; } ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-4"></div>
                    <div class="col-sm-6">  
                        <div style="min-height: 250px;">
                           <table id="list_1"></table>
                           <div id="listPager_1"></div>
                           <button id="BtnCobrado" onclick="tipo='cobrado';$('#cuenDialog').dialog('open');" title="Buscar Cuentas" type="button" class="btn btn-success btn-sm" style="margin-top: 10px;"><i class="glyphicon glyphicon-check"></i><span> Agregar Cuenta</span></button>
                        </div>
                    </div>
                    <div class="col-sm-6">                       
                        <div style="min-height: 250px;">                          
                           <table id="list_2"></table>
                           <div id="listPager_2"></div>
                           <button onclick="tipo='pagado';$('#cuenDialog').dialog('open');" title="Buscar Cuentas" type="button" class="btn btn-success btn-sm" style="margin-top: 10px;"><i class="glyphicon glyphicon-check"></i><span> Agregar Cuenta</span></button>    
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
                        formatter:function (cellvalue, options, rowObject) { return  $.getGridButton(addCuenta,rowObject.Pld_Cod,'Agregar Cuenta'); }
                    }
        ]); 
    </script>
<!-- FIN DEL DIALOGO CUENTAS-->
   <script type="text/javascript">
       var tipo='';
       function addCuenta(a2){       
            $('#cuenDialog').dialog('close');          
            if((tipo==='cobrado'&&$("#list_1").existsId(a2))||(tipo==='pagado'&&$("#list_2").existsId(a2))) {$.alert('La Cuenta ya esta Registrada!'); return;}
            $.saveDataJson("<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>", {Pld_Cod:a2,addCuenta:tipo},
                function(r){ $("#list_"+(r['tipo']==='cobrado'?'1':'2')).jqGrid().trigger("reloadGrid", [{ page: 1 }]); }
            );
        }
        function deleteCuenta(data){
            //$.alert('Para realizar cambios comuniquese con el administrador!');
            $.saveDataJson("<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>",{Pld_Cod:data['Pld_Cod'],deleteCuenta:data['tipo']}, 
                  function( r ) { $("#list_"+(r['tipo']==='cobrado'?'1':'2')).jqGrid().trigger("reloadGrid", [{ page: 1 }]);  }
             );
        }
       $( document ).ready(function() {
            $("#list_1").createGrid({                
                postData: $("#formDeudas").getData("cobrado"), height: 200, rowNum: 10000000, pgbuttons: false,pgtext: null, caption:'IVA Cobrado',
                colModel: [
                    { label: 'Cód.Int.', name: 'Pld_Cod', key: true, width: 40,align:"center", hidden:false },                                
                    { label: 'Cod. Cuenta', name: 'Pld_Cdc', width: 100 },                      
                    { label: 'Cuenta Contable', name: 'Pld_Des', width: 300  },                               
                        { label:'&nbsp;', name: 'act1', width: 30, align: 'center',viewable: false,
                            formatter:function (cellvalue, options, rowObject) { return  $.getGridButton(deleteCuenta,{Pld_Cod:rowObject.Pld_Cod,tipo:'cobrado'},'Eliminar','remove',null,'danger'); }
                        }
                ],
                loadComplete: function(){ if($(this).jqGrid('getDataIDs').length===0) $('#BtnCobrado').removeAttr('disabled'); else $('#BtnCobrado').attr('disabled','disabled'); }
            },false,"#listPager_1",{refresh: true,view: true});                                    
            
            $("#list_2").createGrid({                
                postData: $("#formDeudas").getData("pagado"), height: 200, rowNum: 10000000, pgbuttons: false,pgtext: null, caption:'IVA Pagado',
                colModel: [
                    { label: 'Cód.Int.', name: 'Pld_Cod', key: true, width: 40,align:"center", hidden:false },                                
                    { label: 'Cod. Cuenta', name: 'Pld_Cdc', width: 100 },                      
                    { label: 'Cuenta Contable', name: 'Pld_Des', width: 300  },                               
                        { label:'&nbsp;', name: 'act1', width: 30, align: 'center',viewable: false,
                            formatter:function (cellvalue, options, rowObject) { return  $.getGridButton(deleteCuenta,{Pld_Cod:rowObject.Pld_Cod,tipo:'pagado'},'Eliminar','remove',null,'danger'); }
                        }
                ]
            },false,"#listPager_2",{refresh: true, view: true});
            
            $('#selectPecCod').on('change',function (){
                var data=$(this).find('option:selected').data();
                $('input[name="Pla_Cod"]').val(data['placod']);
                $('input[name="Pec_Cod"]').val(data['peccod']);
                $('input[name="periodo"]').val(data['anio']);
                $('#anio').html(data['anio']);
                $("#list_1").Search("#formDeudas","cobrado");
                $("#list_2").Search("#formDeudas","pagado");
                $.Search('cuen');                
            });
       });
   </script>
</BODY>
</HTML>