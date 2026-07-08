<?php	
/**
* @abstract Permite realizar la cancelacion de comprobantes por abonos
* @author Erik Niebla
* @version 1.0
* Fecha de creaciï¿½n  2015-07-22
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/con_log_relacion_cta.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require_once('../../Librerias/postclass.php');	
/**
* Creacion del Objeto de conexion
*/
$obBD_conexion = new Class_Log_Conexion_Con($Ses_Dat_Dis);
/**
* Creacion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Con;
/**
* Evita el reenvio 
*/
$thisPost = new Post_Block;

$hoy = date("Y-m-d");
$mes = date("m");

if(isset($productoAjax)){
    $contar = $obBD_con1->getRowConsulta(525,trim($search).'*'.$Ses_Emp_Cod.'*'.$op_opciones.'*'.$Pla_Cod.'*', $obBD_conexion);
    $pagination= pages($contar['total'], $page, $rows);
    $responce=$pagination['data'];
    $responce['rows'] = $obBD_con1->getArrayConsulta(525,trim($search).'*'.$Ses_Emp_Cod.'*'.$op_opciones.'*'.$Pla_Cod.'*'.$pagination['limits'], $obBD_conexion);
    utf8_encode_deep($responce['rows']);echo json_encode($responce);exit();
}
if(isset($listTipo)){ 
   $responce['rows'] = $obBD_con1->getArrayConsulta(521, $Pro_Cod.'*'.$Pla_Cod.'*'.$listTipo, $obBD_conexion);
   echo json_encode($responce);exit();
}
if(isset($cuenAjax)){ 
    $contar = $obBD_con1->getRowConsulta(522, $search.'*'.$Ses_Emp_Cod.'*'.$Pec_Cod.'*'.$op_opciones.'*', $obBD_conexion);	      
    $pagination= pages($contar['total'], $page, $rows);
    $responce=$pagination['data'];
    $responce['rows']=  $obBD_con1->getArrayConsulta(522, $search.'*'.$Ses_Emp_Cod.'*'.$Pec_Cod.'*'.$op_opciones.'*'.$pagination['limits'], $obBD_conexion);	    
    utf8_encode_deep($responce['rows']);echo json_encode($responce);exit();
}
if(isset($addCuenta)){ 
    $responce['tipo']=$addCuenta;
    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);              
    $obBD_con1->operacionobBD(524, $Pro_Cod.'*'.$Pld_Cod.'*'.$addCuenta, $obBD_conexion);	
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);   
    if($obBD_con1->Error==0){ $responce['success']=true;} else{$responce['success']=false;$responce['message']=$obBD_con1->MsgError;}
    echo json_encode($responce);
    exit();
}
if(isset($deleteCuenta)){ 
    $responce['tipo']=$deleteCuenta;
    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);              
    $obBD_con1->operacionobBD(523, $Pro_Cod.'*'.$Pld_Cod.'*'.$deleteCuenta, $obBD_conexion);	
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);   
    if($obBD_con1->Error==0){ $responce['success']=true;} else{$responce['success']=false;$responce['message']=$obBD_con1->MsgError;}
    echo json_encode($responce);
    exit();
}
?>
<!DOCTYPE html>
<HTML>
	<HEAD>		
                <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
                <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php")?>              
                <style>     
                    .ui-tabs.ui-widget-content {border: 0px;}                    
                    .ui-tabs ul {padding-left: 20px !important;background: transparent;border-radius: 0px;border-top: 0px;border-right: 0px;border-left: 0px;}
                    .ui-tabs .ui-tabs-panel {background: #eef2f9;padding: 10px 10px;border: 1px solid #4297d7;border-top: 0px;}
                </style>
	</HEAD>
<BODY>
     <?php if(isset($Pec_Cod)&&$Pec_Cod!=''){ 
           $Pec=  explode('*',$Pec_Cod);
           $Year = explode('-',$Pec[1]);
     } ?>
    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;   Registrar Relación Producto - Plan De Cuentas <?Php if(isset($Year[0])) echo 'Periodo '.$Year[0];?></h3></div>
        
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <?php if(isset($Pec_Cod)&&$Pec_Cod!=''){ ?>               
                <div class="row">
                    <div class="col-sm-6">  
                        <fieldset class="exa-fieldset">                           
                           <legend class="Titulos2">Productos</legend> <!-- Form Name -->
                           <form id="prodForm" class="form-horizontal normal" style="margin-bottom: 10px;" action="javascript:$('#list').Search('#prodForm','productoAjax')">
                               <input name="Pla_Cod" type="text" value="<?php echo $Pec[3]; ?>" style="display: none"/> 
                               <div class="form-group">
                                    <label class="col-xs-2 control-label label-xs">Filtrar Por:</label>  
                                    <div class="col-xs-10 radioset" >
                                          <input id="radp1" name="op_opciones" type="radio" value="t" checked="" onclick="setfocus(this.form.search);this.form.submit();" alt="" /><label for="radp1">&nbsp;&nbsp;Todos&nbsp;&nbsp;</label>
                                          <input id="radp2" name="op_opciones" type="radio" value="s" onclick="setfocus(this.form.search);this.form.submit();" alt="" /><label for="radp2">&nbsp;&nbsp;Relacionados&nbsp;&nbsp;</label>                          
                                          <input id="radp3" name="op_opciones" type="radio" value="n" onclick="setfocus(this.form.search);this.form.submit();" alt="" /><label for="radp3">&nbsp;&nbsp;No Relacionados&nbsp;&nbsp;</label>                          
                                    </div>                                                       
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-2 control-label">B&uacute;squeda:</label>  
                                    <div class="col-xs-7" >
                                        <div class="input-group">                        
                                        <input name="search" onkeydown="if (event.keyCode === 13) this.form.submit()" type="text" size="50" maxlength="50" placeholder="Ingrese Producto a buscar..." autofocus  class="form-control input-sm "/>
                                        <span class="input-group-btn"><button type="button" onclick="this.form.submit()" class="btn btn-success btn-sm" title="Buscar Producto" ><span class="glyphicon glyphicon-search"></span> <span>Buscar</span></button></span>
                                      </div><!-- /input-group --> 
                                    </div>                    
                                </div>
                           </form>
                                    <table id="list"></table>
                                    <div id="listPager"></div>
                           
                       </fieldset>

                    </div>
                    <div class="col-sm-6">
                       
                        <fieldset class="exa-fieldset">                           
                           <legend class="Titulos2">Parametrización Productos:</legend> <!-- Form Name -->
                           
                           <div class="form-horizontal normal">
                               <!-- Text input-->
                                <div class="form-group">
                                  <label class="col-sm-3 control-label label-sm" for="Cop_Num">Categoria:</label>  
                                  <div class="col-sm-4">                                    
                                      <input id="Pro_Cod" type="text" style="display: none" />
                                      <input id="Pro_Cat"  class="form-control input-xs"  type="text" readonly="">                                         
                                                                      
                                  </div>                                 
                                </div>  
                                <div class="form-group">
                                  <label class="col-sm-3 control-label label-sm" for="Cop_Num">Desc. Corta:</label>  
                                  <div class="col-sm-4">                                    
                                      <input id="Pro_Cor"  class="form-control input-xs"  type="text" readonly="">                                         
                                                                      
                                  </div>                                 
                                </div>  
                               <div class="form-group">
                                  <label class="col-sm-3 control-label label-sm" for="Cop_Num">Desc. Larga:</label>  
                                  <div class="col-sm-9">                                    
                                      <input id="Pro_Lar"  class="form-control input-xs"  type="text" readonly="">                                         
                                                                      
                                  </div>                                 
                                </div>  
                           </div>
                                     

                           <div class="">
                                <div id="tabs" class="no-header">
                                    <ul>
                                      <li><a href="#tabs-1">Inventario General</a></li>
                                      <li><a href="#tabs-2">Compras/Ventas</a></li>
                                      <li><a href="#tabs-3">Consumos</a></li>
                                    </ul>
                                    <div id="tabs-1">
                                      <div style="padding-bottom: 5px">
                                            <table id="inventar"></table>
                                            <div id="inventarPager"></div>
                                        </div>
                                                         <button disabled="" id="btnInventar" onclick="tipo='I';$('#cuenDialog').dialog('open');" title="Buscar Cuentas" type="button" class="btn btn-success btn-xs"><i class="glyphicon glyphicon-list"></i><span> Seleccionar Cuenta</span></button>
                                    </div>
                                    <div id="tabs-2">
                                        <div style="padding-bottom: 5px">
                                            <table id="compras"></table>
                                            <div id="comprasPager"></div>
                                        </div>
                                                         <button disabled id="btnCompra" onclick="tipo='C';$('#cuenDialog').dialog('open');" title="Buscar Cuentas" type="button" class="btn btn-success btn-xs"><i class="glyphicon glyphicon-list"></i><span> Seleccionar Cuenta</span></button>
                                        <br/><br/>
                                        <div style="padding-bottom: 5px">
                                            <table id="ventas"></table>
                                            <div id="ventasPager"></div>
                                        </div>
                                         <button disabled id="btnVenta" onclick="tipo='V';$('#cuenDialog').dialog('open');" title="Buscar Cuentas" type="button" class="btn btn-success fileinput-button btn-xs"><i class="glyphicon glyphicon-list"></i><span> Seleccionar Cuenta</span></button>
                                    </div>   
                                     <div id="tabs-3">
                                        <div style="padding-bottom: 5px">
                                            <table id="consumos"></table>
                                            <div id="consumosPager"></div>
                                        </div>
                                        <button disabled id="btnConsumo" onclick="tipo='S';$('#cuenDialog').dialog('open');" title="Buscar Cuentas" type="button" class="btn btn-success btn-xs"><i class="glyphicon glyphicon-list"></i><span> Seleccionar Cuenta</span></button>                                       
                                    </div>   
                                </div>
                           </div>   
                                <script>
                                $(function() {
                                    
                                     $("#list").jqGrid({
                                            url: '<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>',
                                            mtype: "GET", datatype: "json", regional : 'es',//ajaxRowOptions: { async: true },
                                            postData: $("#prodForm").getData("productoAjax"),
                                            autowidth : true, shrinkToFit: true, height: 295,responsive:true,caption:'Listado de Productos',hidegrid:false,
                                            cmTemplate: {sortable:false},
                                            colModel: [
                                                { label: 'Cód.Int.', name: 'Pro_Cod', key: true, width: 25,align:"center", hidden:false },  
                                                { label: 'Cod.Int.', name: 'Ite_Cod', width: 25,align:"center", hidden:true },  
                                                { label: 'Categoria', name: 'Cat_Des', width: 60,align:"center"},  
                                                { label: 'Desc. Corta', name: 'Ite_Cor', width: 60,align:"center" }, 
                                                { label: 'Desc. Larga', name: 'Ite_Lar', width: 160,align:"left" },
                                                { label: 'Marca', name: 'Mar_Des', width: 40 },                                                
                                                    { label:'&nbsp;', name: 'act1', width: 30, align: 'center',viewable: false,
                                                        formatter:function (cellvalue, options, rowObject) { 
                                                            return  '<span class="btn btn-success btn-xs" title="Seleccionar" type="button" onclick="selectEmp(\''+rowObject.Pro_Cod+'\');"><i class="glyphicon glyphicon-arrow-right"></i></span>';
                                                        }
                                                    },
                                                { label:'<center><i class="ui-icon ui-icon-circle-check"></i></center>', name: 'act2', width: 20, align: 'center',viewable: false, formatter: 'checkbox',formatoptions: { disabled: false },resizable:false, hidden:true}         
                                            ],                                                     
                                            rowNum: 30, pager: "#listPager", gridview: true, viewrecords: true, altRows: true, altclass: "myAltRowClass"                
                                        });
                                        $('#list').navGrid('#listPager',{ edit: false, add: false, del: false, search: false, refresh: true, view: true, position: "left", cloneToTop: false });
                                        $("#list").jqGrid('bindKeys'); 
                                        
                                    public $tabs=$( "#tabs" );
                                    $tabs.tabs({ selected: 0}); 
                                    $("#inventar").jqGrid({
                                        url: '<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>',
                                        mtype: "GET", datatype: "local", regional : 'es',                
                                        autowidth : true, shrinkToFit: true, height: 50,hidegrid:false,
                                        cmTemplate: {sortable:false},caption:'<b>INVENTARIO&raquo;</b> Cuentas Contables',
                                        colModel: [    
                                            { label: 'Pro Cod', name: 'Pro_Cod', width: 30,align:"center", hidden:false },
                                            { label: 'Cod. Int.', name: 'Pld_Cod', key: true, width: 30,align:"center", hidden:false }, 
                                            { label: 'Cuenta', name: 'Pld_Cdc', width: 50 ,align:"center"}, 
                                            { label: 'Descripci&oacute;n', name: 'Pld_Des', width: 90,align:"left" },
                                                { label:'&nbsp;', name: 'act1', width: 30, align: 'center',viewable: false,
                                                    formatter:function (cellvalue, options, rowObject) { 
                                                        return  '<span class="btn btn-danger btn-xs" title="Eliminar" type="button" onclick="deleteCuenta(\''+rowObject.Pld_Cod+'\',\'I\');"><i class="glyphicon glyphicon-trash"></i></span>';
                                                    }
                                                }
                                        ],                                                     
                                        rowNum: 1000000, pager: "", gridview: true, rownumbers: true, viewrecords: true, altRows: true, altclass: "myAltRowClass",
                                        loadComplete:function (){var ids = $("#inventar").jqGrid('getDataIDs'); if(ids.length===0) {$('#btnInventar').removeAttr('disabled');$tabs.tabs( "enable", 1 );$tabs.tabs( "option", "disabled", [ 0 ] );  $tabs.tabs( "option", "active", 1 );} else {$('#btnInventar').attr('disabled','disabled');$tabs.tabs( "enable", 0 ); $tabs.tabs( "option", "active", 0 );$tabs.tabs( "option", "disabled", [ 1 ] );} }
                                    }); 
                                    $tabs.tabs( "option", "active", 1 );
                                    $("#compras").jqGrid({
                                        url: '<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>',
                                        mtype: "GET", datatype: "local", regional : 'es',                
                                        autowidth : true, shrinkToFit: true, height: 50,hidegrid:false,
                                        cmTemplate: {sortable:false},caption:'<b>COMPRAS&raquo;</b> Cuentas Contables',
                                        colModel: [    
                                            { label: 'Pro Cod', name: 'Pro_Cod', width: 30,align:"center", hidden:false },
                                            { label: 'Cod. Int.', name: 'Pld_Cod', key: true, width: 30,align:"center", hidden:false }, 
                                            { label: 'Cuenta', name: 'Pld_Cdc', width: 50 ,align:"center"}, 
                                            { label: 'Descripci&oacute;n', name: 'Pld_Des', width: 90,align:"left" },
                                                { label:'&nbsp;', name: 'act1', width: 30, align: 'center',viewable: false,
                                                    formatter:function (cellvalue, options, rowObject) { 
                                                        return  '<span class="btn btn-danger btn-xs" title="Eliminar" type="button" onclick="deleteCuenta(\''+rowObject.Pld_Cod+'\',\'C\');"><i class="glyphicon glyphicon-trash"></i></span>';
                                                    }
                                                }
                                        ],                                                     
                                        rowNum: 1000000, pager: "", gridview: true, rownumbers: true, viewrecords: true, altRows: true, altclass: "myAltRowClass",
                                        loadComplete:function (){var ids = $("#compras").jqGrid('getDataIDs'); if(ids.length===0) $('#btnCompra').removeAttr('disabled'); else $('#btnCompra').attr('disabled','disabled'); }
                                    }); 
                                    $("#ventas").jqGrid({
                                        url: '<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>',
                                        mtype: "GET", datatype: "local", regional : 'es',                
                                        autowidth : true, shrinkToFit: true, height: 50,hidegrid:false,
                                        cmTemplate: {sortable:false},caption:'<b>VENTAS&raquo;</b> Cuentas Contables',
                                        colModel: [    
                                            { label: 'Pro Cod', name: 'Pro_Cod', width: 30,align:"center", hidden:false },
                                            { label: 'Cod. Int.', name: 'Pld_Cod', key: true, width: 30,align:"center", hidden:false }, 
                                            { label: 'Cuenta', name: 'Pld_Cdc', width: 50 ,align:"center"}, 
                                            { label: 'Descripci&oacute;n', name: 'Pld_Des', width: 90,align:"left" },
                                                { label:'&nbsp;', name: 'act1', width: 30, align: 'center',viewable: false,
                                                    formatter:function (cellvalue, options, rowObject) { 
                                                        return  '<span class="btn btn-danger btn-xs" title="Eliminar" type="button" onclick="deleteCuenta(\''+rowObject.Pld_Cod+'\',\'V\');"><i class="glyphicon glyphicon-trash"></i></span>';
                                                    }
                                                }
                                        ],                                                     
                                        rowNum: 1000000, pager: "", gridview: true, rownumbers: true, viewrecords: true, altRows: true, altclass: "myAltRowClass",
                                        loadComplete:function (){var ids = $("#ventas").jqGrid('getDataIDs'); if(ids.length===0) $('#btnVenta').removeAttr('disabled'); else $('#btnVenta').attr('disabled','disabled'); }
                                    }); 
                                    $tabs.tabs( "option", "active", 2 );
                                    $("#consumos").jqGrid({
                                        url: '<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>',
                                        mtype: "GET", datatype: "local", regional : 'es',                
                                        autowidth : true, shrinkToFit: true, height: 50,hidegrid:false,
                                        cmTemplate: {sortable:false},caption:'<b>CONSUMOS&raquo;</b> Cuentas Contables',
                                        colModel: [    
                                            { label: 'Pro Cod', name: 'Pro_Cod', width: 30,align:"center", hidden:false },
                                            { label: 'Cod. Int.', name: 'Pld_Cod', key: true, width: 30,align:"center", hidden:false }, 
                                            { label: 'Cuenta', name: 'Pld_Cdc', width: 50 ,align:"center"}, 
                                            { label: 'Descripci&oacute;n', name: 'Pld_Des', width: 90,align:"left" },
                                                { label:'&nbsp;', name: 'act1', width: 30, align: 'center',viewable: false,
                                                    formatter:function (cellvalue, options, rowObject) { 
                                                        return  '<span class="btn btn-danger btn-xs" title="Eliminar" type="button" onclick="deleteCuenta(\''+rowObject.Pld_Cod+'\',\'S\');"><i class="glyphicon glyphicon-trash"></i></span>';
                                                    }
                                                }
                                        ],                                                     
                                        rowNum: 1000000, pager: "", gridview: true, rownumbers: true, viewrecords: true, altRows: true, altclass: "myAltRowClass",
                                        loadComplete:function (){var ids = $("#consumos").jqGrid('getDataIDs'); if(ids.length===0) $('#btnConsumo').removeAttr('disabled'); else $('#btnConsumo').attr('disabled','disabled'); }
                                    }); 
                                    $tabs.tabs( "option", "active", 1 );
                                    $tabs.tabs( "option", "active", 0 );
                                });
                                </script>
                       </fieldset>                        
                    </div>
                </div> 
               <script type="text/javascript">
                    var tipo='';
                    function deleteCuenta(a2,a3){
                        var data={Pro_Cod:$('#Pro_Cod').val(),Pld_Cod:a2,deleteCuenta:a3};
                        $.createDialogConfirm('¿Está seguro que desea eliminar esta relación?',data,deleteCta);
                    }
                    function deleteCta(data){   
//                        //var data={Pro_Cod:$('#Pro_Cod').val(),Pld_Cod:data['Pld_cod'],deleteCuenta:data['Pld_cod']};
                        $.post( "<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>",data, function( response ) {
                            if(response['success']===true){
                                $.alert("Transaccion Realizada con &Eacute;xito!");                          
                                if(response['tipo']==='C')
                                    $("#compras").jqGrid().trigger("reloadGrid", [{ page: 1 }]);
                                if(response['tipo']==='V')
                                    $("#ventas").jqGrid().trigger("reloadGrid", [{ page: 1 }]);
                                if(response['tipo']==='I')
                                    $("#inventar").jqGrid().trigger("reloadGrid", [{ page: 1 }]);
                                if(response['tipo']==='S')
                                    $("#consumos").jqGrid().trigger("reloadGrid", [{ page: 1 }]);
                            }else{$.alert(response['message']);}
                         },'json').fail(function(error) { $.alert();});
                    }
                    function addCuenta(a2){ 
                         $('#cuenDialog').dialog('close');
                         if($('#Pro_Cod').val()===''){$.alert('Seleccione un Producto!');return;}
                         var data={Pro_Cod:$('#Pro_Cod').val(),Pld_Cod:a2,addCuenta:tipo};                         
                         $.post( "<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>",data, function( response ) {
                             if(response['success']===true){
                                 $.alert("Transaccion Realizada con &Eacute;xito!");                          
                                 if(response['tipo']==='C')
                                     $("#compras").jqGrid().trigger("reloadGrid", [{ page: 1 }]);
                                 if(response['tipo']==='V')
                                     $("#ventas").jqGrid().trigger("reloadGrid", [{ page: 1 }]);
                                 if(response['tipo']==='I')
                                     $("#inventar").jqGrid().trigger("reloadGrid", [{ page: 1 }]);
                                 if(response['tipo']==='S')
                                     $("#consumos").jqGrid().trigger("reloadGrid", [{ page: 1 }]);

                             }else{$.alert(response['message']);}
                          },'json').fail(function(error) { $.alert();});
                     }
                     function selectEmp(id){          
                          var dataFromRow={};
                          dataFromRow['Pla_Cod']='<?php echo $Pec[3]; ?>'; 
                          dataFromRow['Pro_Cod']=id;
                          
                          dataFromRow['listTipo']='C';
                          $("#compras").jqGrid('setGridParam',{datatype:'json',postData: dataFromRow}).trigger("reloadGrid", [{ page: 1 }]);
                          dataFromRow['listTipo']='V';
                          $("#ventas").jqGrid('setGridParam',{datatype:'json',postData: dataFromRow}).trigger("reloadGrid", [{ page: 1 }]);
                          dataFromRow['listTipo']='I';
                          $("#inventar").jqGrid('setGridParam',{datatype:'json',postData: dataFromRow}).trigger("reloadGrid", [{ page: 1 }]);
                          dataFromRow['listTipo']='S';
                          $("#consumos").jqGrid('setGridParam',{datatype:'json',postData: dataFromRow}).trigger("reloadGrid", [{ page: 1 }]);
                          setProduct($("#list").jqGrid('getRowData', id));
                     }
                     function setProduct(data){   
                         $('#Pro_Cod').val(data['Pro_Cod']);
                         $('#Pro_Cat').val(data['Cat_Des']);
                         $('#Pro_Cor').val(data['Ite_Cor']);
                         $('#Pro_Lar').val(data['Ite_Lar']);
                     }
                </script>
<!--INICIO DEL DIALOGO BUSCAR CUENTA--> 
    <div id="cuenDialog" title="B&uacute;squeda de Cuentas">  
        <form class="form-horizontal normal"> 
        <fieldset>
		<legend>Filtros</legend>
                <div class="form-group">
                    <label class="col-xs-2 control-label label-xs">Filtrar Por:</label>  
                    <div class="col-xs-5 radioset" >
                          <input id="radc1" name="op_opciones" type="radio" value="d" checked="" onclick="setfocus(this.form.search)" alt="" /><label for="radc1">&nbsp;&nbsp;Descripci&oacute;n&nbsp;&nbsp;</label>
                          <input id="radc2" name="op_opciones" type="radio" value="c" onclick="setfocus(this.form.search)" alt="" /><label for="radc2">&nbsp;&nbsp;C&oacute;digo&nbsp;&nbsp;</label>                          
                    </div>                   
                    <div class="col-xs-4"> <label class="control-label label-xs">Plan de Cuentas:</label>                       
                        <input name="periodo" type="text" size="6" readonly style="text-align: center;display: inline-block;width: auto;" class="form-control input-xs" value="<?php echo $Year[0]; ?>" /> 
                        <input name="Pec_Cod" type="hidden" value="<?php echo $Pec[0]; ?>" /> 
                    </div>    
                </div>
                <div class="form-group">
                    <label class="col-xs-2 control-label">B&uacute;squeda:</label>  
                    <div class="col-xs-7" >
                        <div class="input-group">                        
                        <input name="search" onkeydown="if (event.keyCode === 13) this.form.submit()" type="text" size="50" maxlength="50" placeholder="Ingrese cuenta a buscar..." autofocus  class="form-control input-sm "/>
                        <span class="input-group-btn"><button type="button" onclick="this.form.submit()" class="btn btn-success btn-sm" title="Buscar cuenta" ><span class="glyphicon glyphicon-search"></span> <span>Buscar</span></button></span>
                      </div><!-- /input-group --> 
                    </div>                    
                </div>
        </fieldset>  
       </form> 
    </div> 
<!-- FIN DEL DIALOGO CUENTAS-->    
<script>
        $(document).ready(function () { 

                                // DIALOG BUSCAR CUENTAS
                $.createSearchDialog('cuenDialog',[
                        { label: 'C&oacute;d.Int.', name: 'Pld_Cod', key: true, width: 15,align:"center",hidden:true },                                
                        { label: 'Codigo', name: 'Pld_Cdc', width: 45 },                      
                        { label: 'Cuenta', name: 'Pld_Des', width: 80, cellattr: function (rowId, tv, rawObject, cm, rdata) { return 'style="white-space: normal;"'; }},
                        { label: 'Grupo', name: 'Pld_Grupo', width: 110, cellattr: function (rowId, tv, rawObject, cm, rdata) { return 'style="white-space: normal;"'; }},
                        { label: 'Tipo', name: 'Pld_Tip', width: 30,align:"center" },
                        { label: 'Estado', name: 'Pld_Est', width: 30,align:"center"}, 
                                { label:'<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 30, align: 'center',viewable: false,
                                        formatter:function (cellvalue, options, rowObject) { 
                                                return  '<span class="btn btn-success btn-xs" title="Enviar al D&eacute;bito" onclick="addCuenta(\''+rowObject.Pld_Cod+'\');"><i class="glyphicon glyphicon-arrow-right"></i></span>&nbsp;'; 
                                        }
                                }
                ]);   

         });
    </script> 
            <?php }else{ /* isset(Pec_Cod) */?>
                <div class="row" style="height: 350px;">
                    <div class="col-sm-12">  
                        <fieldset class="exa-fieldset">                           
                           <legend class="Titulos2">Seleccione Periodo</legend> <!-- Form Name -->
                           <form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post" name= "form1" class="form-horizontal normal">	 
                               <div class="form-group">
                                  <label class="col-sm-2 control-label label-sm required" for="Pec_Cod">Periodo:</label>  
                                  <div class="col-sm-2">
                               <select name="Pec_Cod" id="Pec_Cod" onChange="javascript: asignar_fechas(this.value)" class="form-control input-sm" required="">
                                <?Php 
                                      $rs_periodos = $obBD_con1->getArrayConsulta(214,$Ses_Emp_Cod,$obBD_conexion);
                                      //$fecha=explode("-",$row_rs_periodos['Pec_Fei']); 
                                      //$periodo="En el periodo ".$fecha[0];
                                      if(count($rs_periodos)){
                                        foreach ($rs_periodos as $periodo){
                                        ?>
                                        <option value="<?Php echo $periodo['Pec_Cod'].'*'.$periodo['Pec_Fei'].'*'.$periodo['Pec_Fef'].'*'.$periodo['Pla_Cod']; ?>"><?Php echo $periodo['Periodo']; ?></option>
                                        <?php
                                        }
                                      }else{ ?><option value=""></option><?Php }//Fin del else if ($total_rs_periodos > 0) ?>	
                                </select>
                                      </div>
                               <button type="submit" class="btn btn-success btn-sm" title="Buscar">
                                            <i class="glyphicon glyphicon-search"></i>
                                            <span>Buscar</span>
                                </button>   	
                           </form>
                       </fieldset>

                    </div>
                </div>    
            <?php } ?>
        </div>
    </div>
  
  
</BODY>
</HTML>