<?php
/**
 * @abstract Permite realizar la cancelacion de comprobantes por abonos
 * @author Erik Niebla
 * @version 1.0
 * Fecha de creaci�n  2015-07-22
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/con_log_prod_plan.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
/**
 * Creacion del Objeto de conexion
 */
$obBD_conexion = new Class_Log_Conexion_Cons($Ses_Dat_Dis);
/**
 * Creaci�n del Objeto para consultas
 */
$obBD_con1 =  new Class_Log_Datos_Cons;

$hoy = date("Y-m-d");
$mes = date("m");

// Productos
if(isset($productoAjax)){
    $contar = $obBD_con1->getRowConsulta(7,trim($search).'*'.$Ses_Emp_Cod.'*'.$op_opciones.'*'.$Pla_Cod.'*', $obBD_conexion);
    $pagination= pages($contar['total'], $page, $rows);
    $responce=$pagination['data'];
    $responce['rows'] = $obBD_con1->getArrayConsulta(7,trim($search).'*'.$Ses_Emp_Cod.'*'.$op_opciones.'*'.$Pla_Cod.'*'.$pagination['limits'], $obBD_conexion);
    utf8_encode_deep($responce['rows']);echo json_encode($responce);exit();
}
if(isset($listTipo)){ 
   $responce['rows'] = $obBD_con1->getArrayConsulta(8, $Pro_Cod.'*'.$Pla_Cod.'*'.$listTipo.'*'.$Con_Cod, $obBD_conexion);
   echo json_encode($responce);exit();
}
if(isset($cuenProdAjax)||isset($cuenCatAjax)){ 
    $contar = $obBD_con1->getRowConsulta(9, $search.'*'.$Ses_Emp_Cod.'*'.$Pec_Cod.'*'.$op_opciones.'*'. $tipo_doc_param, $obBD_conexion);	      
    $pagination= pages($contar['total'], $page, $rows);
    $responce=$pagination['data'];
    $responce['rows']=  $obBD_con1->getArrayConsulta(9, $search.'*'.$Ses_Emp_Cod.'*'.$Pec_Cod.'*'.$op_opciones.'*'.$pagination['limits']. '*' . $tipo_doc_param, $obBD_conexion);	    
    utf8_encode_deep($responce['rows']);echo json_encode($responce);exit();
}




if(isset($addCuenta)){ 
    $responce['tipo']=$Tip_Pld;
    if($Tip_Pld!='O'&&$Tip_Pld!='G') $Con_Cod='';
    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);              
    $obBD_con1->operacionobBD(11, $Pro_Cod.'*'.$Pld_Cod.'*'.$Tip_Pld.'*'.$Con_Cod, $obBD_conexion);	
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);   
    if($obBD_con1->Error==0){ $responce['success']=true;} else{$responce['success']=false;$responce['message']=$obBD_con1->MsgError;}
    echo json_encode($responce);exit();
}
if(isset($deleteCuenta)){ 
    $responce['tipo']=$deleteCuenta;
    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);              
    $obBD_con1->operacionobBD(10, $Pro_Cod.'*'.$Pld_Cod.'*'.$Tip_Pld.'*'.$Con_Cod, $obBD_conexion);	
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);   
    if($obBD_con1->Error==0){ $responce['success']=true;$responce['message']="La <u>Parametrización</u> se ha eliminado con exito!";} else{$responce['success']=false;$responce['message']=$obBD_con1->MsgError;}
    utf8_encode_deep($responce); echo json_encode($responce);exit();
}
// Categorias
if(isset($categAjax)){
    $contar = $obBD_con1->getRowConsulta(12,$Cat_Cod.'*'.$Ses_Emp_Cod.'*'.$op_opciones.'*'.$Pla_Cod.'*', $obBD_conexion);
    $pagination= pages($contar['total'], $page, $rows);
    $responce=$pagination['data'];
    $responce['rows'] = $obBD_con1->getArrayConsulta(12,$Cat_Cod.'*'.$Ses_Emp_Cod.'*'.$op_opciones.'*'.$Pla_Cod.'*'.$pagination['limits'], $obBD_conexion);
    utf8_encode_deep($responce['rows']);echo json_encode($responce);exit();
}
if(isset($listTipoCat)){ 
   $responce['rows'] = $obBD_con1->getArrayConsulta(13, $Cat_Cod.'*'.$Pla_Cod.'*'.$listTipoCat.'*'.$Con_Cod, $obBD_conexion);
   echo json_encode($responce);exit();
}
if(isset($addCuentaCat)){ 
    if(empty($addCuentaCat)){$responce['success']=false;$responce['message']='Seleccione tipo de Parametro!';echo json_encode($responce);exit();}
    $responce['tipo']=$addCuenta;
    $obBD_con1->inicio_transaccion($obBD_conexion->conexion); 
    foreach ($productos AS $Pro_Cod){
        $obBD_con1->operacionobBD(14, $Pro_Cod.'*'.$Pld_Tip.'*'.$Con_Cod, $obBD_conexion);	
        $obBD_con1->operacionobBD(11, $Pro_Cod.'*'.$Pld_Cod.'*'.$Pld_Tip.'*'.$Con_Cod, $obBD_conexion);	
    }
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);   
    if($obBD_con1->Error==0){ $responce['success']=true;} else{$responce['success']=false;$responce['message']=$obBD_con1->MsgError;}
    echo json_encode($responce);exit();
}
 $listOptions = <<<EOF
    <option value="">Seleccione...</option>
    <optgroup label="Inventarios">
        <option value="C">Compras</option>
        <option value="V">Ventas</option>
    </optgroup>    
    <optgroup label="Ajustes">
        <option value="N">Ingresos</option>
        <option value="E">Egresos</option>
    </optgroup>
    <optgroup label="Consumos" id="optCons">
        <option value="G">Gastos</option>
        <option value="O">Costos</option>
    </optgroup>
EOF;
?>
<!DOCTYPE html>
<HTML>
    <HEAD>		
        <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
        <TITLE><?Php echo "Parametrización Producto [EXA]"; ?></TITLE>
        <meta charset= "UTF-8">
        <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
        <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>                   
        <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script> 
        <script type="text/javascript" src="../../framework/jquery/chosen/chosenDesc/chosenDesc.js"></script> 
        <style>                    
            
        </style>
        <script>    
            var $tabs;
            $(function() {  
                $tabs=$( "#tabs" );
                $tabs.createTabs();
            });
        </script>  
    </HEAD>
    <BODY>
        <?php if(isset($Pec_Cod)&&$Pec_Cod!=''){ 
           $Pec=  explode('*',$Pec_Cod);
           $Year = explode('-',$Pec[1]);
        } ?>
        <div class="panel panel-main">
            <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Registrar Relación Producto - Plan De Cuentas <?Php if(isset($Year[0])) echo 'Periodo '.$Year[0];?></h3></div>

                <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                <?php if(empty($Pec_Cod)){ ?> 
                <div class="row" style="height: 350px;">
                    <div class="col-sm-12">  
                        <fieldset class="exa-fieldset">                           
                           <legend class="Titulos2">Seleccione Periodo</legend> <!-- Form Name -->
                           <form action="<?php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>" method="post" name= "form1" class="form-horizontal normal">	 
                               <div class="form-group">
                                  <label class="col-sm-2 control-label label-sm required" for="Pec_Cod">Periodo:</label>  
                                  <div class="col-sm-2">
                               <select name="Pec_Cod" id="Pec_Cod" onChange="javascript: asignar_fechas(this.value)" class="form-control input-sm" required="">
                                <?Php 
                                      $rs_periodos = $obBD_con1->getArrayConsulta(15,$Ses_Emp_Cod,$obBD_conexion);
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
                <?php }else{ ?>    
                <div id="main-panel">    
                   <div id="tabs" class="ui-tab-fix">
                    <ul>                      
                      <li><a href="#tabs-2">Por Producto</a></li> 
                      <li><a href="#tabs-1">Por Categoria</a></li>
                    </ul>
                    <div id="tabs-2">
                        <div class="row">
                            <div class="col-sm-6">  
                                <fieldset class="exa-fieldset">                           
                                   <legend class="Titulos2">Productos</legend> <!-- Form Name -->
                                   <form id="prodForm" class="form-horizontal normal" style="margin-bottom: 10px;" action="javascript:$('#listProds').Search('#prodForm','productoAjax')">
                                       <input name="Pla_Cod" type="text" value="<?php echo $Pec[3]; ?>" style="display: none"/> 
                                       <div class="form-group">
                                            <label class="col-xs-2 control-label label-xs">Filtrar:</label>  
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
                                            <table id="listProds"></table>
                                            <div id="listProdsPager"></div>

                               </fieldset>

                            </div>
                            <div class="col-sm-6">
                                <fieldset class="exa-fieldset">                           
                                    <legend class="Titulos2">Datos Producto:</legend> <!-- Form Name -->

                                    <div class="form-horizontal normal" id='Pro_desc'>
                                        <!-- Text input-->
                                         <div class="form-group">
                                           <label class="col-sm-3 control-label label-sm" for="Cop_Num">Categoria:</label>  
                                           <div class="col-sm-9">    
                                               <span data-field='Cat_Des' class="form-control input-xs"  type="text" readonly="">
                                           </div>                                 
                                         </div>  
                                         <div class="form-group">
                                           <label class="col-sm-3 control-label label-sm" for="Cop_Num">Desc. Corta:</label>  
                                           <div class="col-sm-4">                                    
                                               <span  data-field='Ite_Cor' class="form-control input-xs"  type="text" readonly="">                                         

                                           </div>                                 
                                         </div>  
                                        <div class="form-group">
                                           <label class="col-sm-3 control-label label-sm" for="Cop_Num">Desc. Larga:</label>  
                                           <div class="col-sm-9">                                    
                                               <span data-field='Ite_Lar'  class="form-control input-xs"  type="text" readonly="">                                         

                                           </div>                                 
                                         </div>  
                                    </div>                                        
                                </fieldset>
                                <fieldset class="exa-fieldset">                           
                                    <legend class="Titulos2">Parametrización Producto:</legend> <!-- Form Name -->
                                    <form id="formCuenList" class="form-horizontal normal">
                                        <input id="Pro_Cod" name="Pro_Cod" type="text" style="display: none" data-field="Pro_Cod" />
                                        <input name="Pla_Cod" type="text" value="<?php echo $Pec[3]; ?>" style="display: none" />
                                        <div class="form-group">
                                            <label class="col-sm-2 control-label label-sm" for="Par_Tip">Param.:</label>  
                                            <div class="col-sm-3">                                    
                                                <select id="listTipo" name="listTipo" class="form-control input-sm" required="" onchange="updateCuentas()" data-field="Tip_Pld">
                                                    <?php echo $listOptions; ?>
                                                </select>  
                                            </div>   
                                            <label class="col-sm-2 control-label label-sm cons consProd" for="Par_Tip">C.Consumo:</label>  
                                            <div class="col-sm-5 cons consProd">                                    
                                                <select id="consumos" name="Con_Cod" class="form-control input-sm" required="" onchange="updateCuentas()" data-field="Con_Cod">
                                                    <option value=''>Seleccione...</option>
                                                    <?Php 
                                                        $rs_consumos = $obBD_con1->getArrayConsulta(6,$Ses_Emp_Cod,$obBD_conexion);
                                                        if(count($rs_consumos)){
                                                          foreach ($rs_consumos as $row){
                                                          ?>
                                                          <option value="<?Php echo $row['Con_Cod']; ?>"><?Php echo $row['Con_Des']; ?></option>
                                                          <?php
                                                          }
                                                        } ?>
                                                </select>  
                                            </div>   
                                          </div>
                                    </form>    
                                    <div style="padding-bottom: 5px; padding-top: 5px">
                                            <table id="prodCuentas"></table>                                            
                                        </div>
                                        <button disabled="" id="btnAddCuenProd" onclick="tipDocumentParam('PROD');$('#cuenProdDialog').dialog('open');" title="Buscar Cuentas" type="button" class="btn btn-success btn-xs"><i class="glyphicon glyphicon-list"></i><span> Seleccionar Cuenta</span></button>
                                </fieldset>
                                <div class="alert alert-info"><i class="glyphicon glyphicon-info-sign"></i> <u><strong>NOTA:</strong></u>&nbsp; Solo se permite un <i>Cuenta Contable</i> por cada <i>Tipo de Parametro</i></div>
                            </div>
                         </div>  
                        <script type="text/javascript">                            
                            function selectProd(id){         
                                var data=($("#listProds").jqGrid('getRowData', id)); 
                                $('#Pro_desc').setData(data,true,'field');
                                $('#formCuenList').setData(data,true,'field');                                 
                                updateCuentas();
                            }                            
                            function updateCuentas(){    
                                $('#btnAddCuenProd').attr('disabled','disabled');
                                var tipo=$('#listTipo').val();
                                if(tipo==='G'||tipo==='O'){ $('.consProd').show(); }else{ $('.consProd').hide(); }
                                //if(tipo==='') {$("#prodCuentas").jqGrid('showCol',["Tipo"]);} else{ $("#prodCuentas").jqGrid('hideCol',["Tipo"]);} $("#prodCuentas").trigger("resize");
                                if($('#Pro_Cod').val()!==''){
                                    var data=$('#formCuenList').getData(); //console.log(data);                                    
                                    $("#prodCuentas").jqGrid('setGridParam',{datatype:'json',postData: data}).trigger("reloadGrid", [{ page: 1 }]);                                    
                                }
                            }
                            function addCuentaProd(a2){ 
                                $('#cuenProdDialog').dialog('close');
                                if($('#listTipo').val()===''){$.alert('Seleccione un <u>Tipo Parametro</u>!');return;}
                                if($('#Pro_Cod').val()===''){$.alert('Seleccione un <u>Producto</u>!');return;}
                                if($('#listTipo').val()==='G'||$('#listTipo').val()==='O')if($('#consumos').val()===''){$.alert('Seleccione un Centro de <u>Consumo</u>!');return;}
                                var data={Pro_Cod:$('#Pro_Cod').val(),Pld_Cod:a2,Tip_Pld:$('#listTipo').val(),Con_Cod:$('#consumos').val(),addCuenta:true};                         
                                $.saveDataJson("<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>",data,function (){ $("#prodCuentas").jqGrid('setGridParam',{datatype:'json'}).trigger("reloadGrid", [{ page: 1 }]); } /*success*/);
                            }
                            function deleteCuenta(data){                                                                
                                data['deleteCuenta']=true;
                                $.createDialogConfirm('¿Está seguro que desea eliminar esta relación?',data,function (){$.saveDataJson("<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>",data,function (){ $("#prodCuentas").jqGrid('setGridParam',{datatype:'json'}).trigger("reloadGrid", [{ page: 1 }]); } /*success*/);});
                            }                            
                            $(function() {  
                                var $listProds=$("#listProds");
                                $listProds.createGrid({                                   
                                    height:295,postData: $("#prodForm").getData("productoAjax"),caption:'Listado de Productos',
                                    colModel: [
                                        { label: 'Cód.Int.', name: 'Pro_Cod', key: true, width: 25,align:"center", hidden:false },  
                                        { label: 'Cod.Int.', name: 'Ite_Cod', width: 25,align:"center", hidden:true },  
                                        { label: 'Categoria', name: 'Cat_Des', width: 60,align:"center"},  
                                        { label: 'Desc. Corta', name: 'Ite_Cor', width: 60,align:"center" }, 
                                        { label: 'Desc. Larga', name: 'Ite_Lar', width: 160,align:"left" },
                                        { label: 'Marca', name: 'Mar_Des', width: 40 },                                                
                                            { label:'&nbsp;', name: 'act1', width: 30, align: 'center',viewable: false,
                                                formatter:function (cellvalue, options, rowObject) { 
                                                    return $.getGridButton(selectProd,rowObject.Pro_Cod);
                                                    //return  '<span class="btn btn-success btn-xs" title="Seleccionar" type="button" onclick="selectProd(\''+rowObject.Pro_Cod+'\');"><i class="glyphicon glyphicon-arrow-right"></i></span>';
                                                }
                                            },
                                        { label:'<center><i class="ui-icon ui-icon-circle-check"></i></center>', name: 'act2', width: 20, align: 'center',viewable: false, formatter: 'checkbox',formatoptions: { disabled: false },resizable:false, hidden:true}         
                                    ]
                                },false,"#listProdsPager");                                

                                $("#prodCuentas").createGrid({
                                    postData:{listTipo:'I'}, height: 150,caption:'<b>&raquo;</b> Cuentas Contables',
                                    colModel: [                                         
                                        { label: 'Tipo', name: 'Tipo', width: 30,align:"center",classes:'bold cellOrange1', 
                                            formatter:function(cellvalue, options, rowObject){  
                                                var str='';
                                                switch(rowObject.Tip_Pld){
                                                    //case 'I': str='Inventario';break;
                                                    case 'C': str='Compras';break;
                                                    case 'V': str='Ventas';break;
                                                    case 'G': str='Gastos';break;
                                                    case 'O': str='Costos';break;
                                                    case 'N': str='Ingresos';break;
                                                    case 'E': str='Egresos';break;
                                                }
                                                return str;
                                            }
                                        }, 
                                        { label: 'Consumo', name: 'Con_Des', width: 30,align:"center",classes:'columnHighlight1'}, 
                                        { label: 'Con. Cod.', name: 'Con_Cod', width: 30,align:"center", hidden:true },
                                        { label: 'Pro. Cod.', name: 'Pro_Cod', width: 30,align:"center", hidden:false },
                                        { label: 'Pld. Cod.', name: 'Pld_Cod', key: true, width: 30,align:"center", hidden:false }, 
                                        { label: 'Pld. Tip.', name: 'Tip_Pld', width: 30,align:"center", hidden:true }, 

                                        { label: 'Cuenta', name: 'Pld_Cdc', width: 50 ,align:"center"}, 
                                        { label: 'Descripci&oacute;n', name: 'Pld_Des', width: 90,align:"left" },
                                            { label:'&nbsp;', name: 'act1', width: 30, align: 'center',viewable: false,
                                                formatter:function (cellvalue, options, rowObject) { 
                                                    return $.getGridButton(deleteCuenta,rowObject,'Eliminar','trash',null,'danger');
                                                    //return  '<span class="btn btn-danger btn-xs" title="Eliminar" type="button" onclick="deleteCuenta(\''+rowObject.Pld_Cod+'\',\''+rowObject.Tip_Pld+'\');"><i class="glyphicon glyphicon-trash"></i></span>';
                                                }
                                            }
                                    ],
                                    loadComplete:function (){var ids = $("#prodCuentas").jqGrid('getDataIDs'); if(ids.length===0&&$('#listTipo').val()!=='') $('#btnAddCuenProd').removeAttr('disabled'); else $('#btnAddCuenProd').attr('disabled','disabled'); }
                                });  
                            },true);
                        </script>
                        
                    </div>                    
                    
                    <div id="tabs-1">
                        <div class="row">
                            <div class="col-sm-6">  
                                <fieldset class="exa-fieldset">                           
                                   <legend class="Titulos2">Productos</legend> <!-- Form Name -->
                                   <form id="catForm" class="form-horizontal normal" style="margin-bottom: 10px;" action="javascript:$('#listProdCat').Search('#catForm','categAjax')">
                                       <input name="Pla_Cod" type="text" value="<?php echo $Pec[3]; ?>" style="display: none"/> 
                                       <div class="form-group">
                                            <label class="col-xs-2 control-label label-xs">Filtrar:</label>  
                                            <div class="col-xs-10 radioset" >
                                                  <input id="radpc1" name="op_opciones" type="radio" value="t" checked="" onclick="this.form.submit();" alt="" /><label for="radpc1">&nbsp;&nbsp;Todos&nbsp;&nbsp;</label>
                                                  <input id="radpc2" name="op_opciones" type="radio" value="s" onclick="this.form.submit();" alt="" /><label for="radpc2">&nbsp;&nbsp;Relacionados&nbsp;&nbsp;</label>                          
                                                  <input id="radpc3" name="op_opciones" type="radio" value="n" onclick="this.form.submit();" alt="" /><label for="radpc3">&nbsp;&nbsp;No Relacionados&nbsp;&nbsp;</label>                          
                                            </div>                                                       
                                        </div>
                                        <div class="form-group">
                                            <label class="col-xs-2 control-label">Categoria:</label>  
                                            <div class="col-xs-10" >
                                               <?php $row_rs_categ = $obBD_con1->getArrayConsulta(16, $Ses_Emp_Cod, $obBD_conexion); ?>
                                                <select name="Cat_Cod" id="Cat_Cod" class="form-control input-sm" data-placeholder="Selecciona una categoria...">
                                                    <option value=""></option>
                                                    <?Php foreach ($row_rs_categ as $row) { ?>
                                                        <option value="<?Php echo $row['Cat_Cod']; ?>" ><?Php echo /* strtoupper($row['Par_Cat_Des']).' � '. */$row['Cat_Des']; ?></option>
                                                    <?Php } ?>
                                                </select>
                                            </div>                    
                                        </div>
                                   </form>
                                            <table id="listProdCat"></table>
                                            <div id="listProdCatPager"></div>

                               </fieldset>
                            </div>
                            <div class="col-sm-6">
                                <fieldset class="exa-fieldset">                           
                                    <legend class="Titulos2">Parametrización Categoria:</legend> <!-- Form Name -->
                                    <form id="formCuenCat" class="form-horizontal normal">                                                                                
                                        <input name="Pla_Cod" type="text" value="<?php echo $Pec[3]; ?>" style="display: none" />
                                        <div class="form-group">
                                            <label class="col-sm-2 control-label label-sm" for="Par_Tip">Categoría:</label>  
                                            <div class="col-sm-10">
                                                <input id="Cat_Des" type="text" value="" class="form-control input-sm" readonly="" />
                                                <input id="Cat_Cod_Cta" name="Cat_Cod" type="text" value="" style="display: none" />
                                            </div>
                                        </div>    
                                        <div class="form-group">
                                            <label class="col-sm-2 control-label label-sm" for="Par_Tip">Param.:</label>  
                                            <div class="col-sm-3">                                    
                                                <select id="listTipoCat" name="listTipoCat" class="form-control input-sm" required="" onchange="updateCuentasCat()">
                                                    <?php echo $listOptions; ?>
                                                </select>    

                                            </div> 
                                            <label class="col-sm-2 control-label label-sm cons consCat" for="Par_Tip">C.Consumo:</label>  
                                            <div class="col-sm-5 cons consCat">                                    
                                                <select id="consumosCat" name="Con_Cod" class="form-control input-sm" required="" onchange="updateCuentasCat()" data-field="Con_Cod">
                                                    <option value=''>Seleccione...</option>
                                                    <?Php 
                                                        $rs_consumoscat = $obBD_con1->getArrayConsulta(6,$Ses_Emp_Cod,$obBD_conexion);
                                                        if(count($rs_consumoscat)){
                                                          foreach ($rs_consumoscat as $row){
                                                          ?>
                                                          <option value="<?Php echo $row['Con_Cod']; ?>"><?Php echo $row['Con_Des']; ?></option>
                                                          <?php
                                                          }
                                                        } ?>
                                                </select>  
                                            </div>  
                                          </div>
                                    </form>    
                                    <div style="padding-bottom: 5px; padding-top: 5px">
                                            <table id="catCuentas"></table>                                            
                                        </div>
                                        <button disabled="" id="btnAddCuenCat" onclick="tipDocumentParam('CATG');$('#cuenCatDialog').dialog('open');" title="Buscar Cuentas" type="button" class="btn btn-success btn-xs"><i class="glyphicon glyphicon-list"></i><span> Seleccionar Cuenta</span></button>
                                </fieldset>
                                <div class="alert alert-warning"><i class="glyphicon glyphicon-alert"></i> <u><strong>ALERTA:</strong></u>&nbsp; Este cambio afectará a todos los productos seleccionados dentro de la categoría</i></div>
                                <div class="alert alert-info"><i class="glyphicon glyphicon-info-sign"></i> <u><strong>NOTA:</strong></u>&nbsp; Solo se permite un <i>Cuenta Contable</i> por cada <i>Tipo de Parametro</i></div>
                            </div>
                        </div> 
                        <script type="text/javascript">   
                            //$('#listTipoCat').find('#optCons').attr('disabled','disabled');
                            function updateCuentasCat(){    
                                $('#btnAddCuenCat').attr('disabled','disabled');
                                var tipo=$('#listTipoCat').val();
                                if(tipo==='G'||tipo==='O'){ $('.consCat').show(); }else{ $('.consCat').hide(); }
                                if($('#Cat_Cod_Cta').val()!==''){
                                    var data=$('#formCuenCat').getData();  
                                    data['Cat_Cod']=$('#Cat_Cod').val();
                                    $("#catCuentas").jqGrid('setGridParam',{datatype:'json',postData: data}).trigger("reloadGrid", [{ page: 1 }]);                                    
                                    if((($("#listTipoCat").val()==='G'||$("#listTipoCat").val()==='O')&&$('#consumosCat').val()!=='')||($("#listTipoCat").val()!==''&&$("#listTipoCat").val()!=='G'&&$("#listTipoCat").val()!=='O'))
                                        $('#btnAddCuenCat').removeAttr('disabled');
                                }
                            }
                            function addCuentaCat(a2){ 
                                $('#cuenCatDialog').dialog('close');
                                if($('#listTipoCat').val()===''){$.alert('Seleccione un <u>Tipo Parametro</u>!');return;}
                                if($('#Cat_Cod').val()===''){$.alert('Seleccione una <u>Categoria</u>!');return;}                                
                                var data={Cat_Cod:$('#Cat_Cod_Cta').val(),Pld_Cod:a2,addCuentaCat:true,Pld_Tip:$('#listTipoCat').val(),Con_Cod:$('#consumosCat').val(),productos:$("#listProdCat").jqGrid('getDataIDs')};                                
                                if(data['productos'].length===0){$.alert('No hay productos en la <u>Categoria</u> seleccionada!');return;}
                                $.createDialogConfirm('<b>¿Está seguro que desea registrar la parametrización?</b><br/> <b>NOTA:</b>Se sobreescribirá pa información.',data,
                                    function (){ $.saveDataJson("<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>",data,function (){ $("#catCuentas").jqGrid('setGridParam',{datatype:'json'}).trigger("reloadGrid", [{ page: 1 }]); } /*success*/); }
                                );
                            }                            
                            $(function() {
                                //$tabs.tabs( "option", "active", 1 );
                                $("#Cat_Cod").createChosen('input-sm').on('change', function(e) { $("#listProdCat").Search('#catForm','categAjax'); $("#Cat_Cod_Cta").val(this.value);$("#Cat_Des").val($(this).find('option:selected').text());$("#listTipoCat").val('');$("#consumosCat").val('');updateCuentasCat(); });                                
                                var $listProds=$("#listProdCat");
                                $listProds.createGrid({
                                    height: 295,caption:'Listado de Productos en la Categoria',
                                    colModel: [
                                        { label: 'Cód.Int.', name: 'Pro_Cod', key: true, width: 25,align:"center", hidden:false },  
                                        { label: 'Cod.Int.', name: 'Ite_Cod', width: 25,align:"center", hidden:true },  
                                        { label: 'Categoria', name: 'Cat_Des', width: 60,align:"center"},  
                                        { label: 'Desc. Corta', name: 'Ite_Cor', width: 60,align:"center" }, 
                                        { label: 'Desc. Larga', name: 'Ite_Lar', width: 160,align:"left" },
                                        { label: 'Marca', name: 'Mar_Des', width: 40 },                                                
                                        { label:'<center><i class="ui-icon ui-icon-circle-check"></i></center>', name: 'act1', width: 20, align: 'center',viewable: false, formatter: 'checkbox',formatoptions: { disabled: false },resizable:false, hidden:true}         
                                    ]
                                },true);
                                
                                $("#catCuentas").createGrid({
                                    height: 150,caption:'<b>&raquo;</b> Cuentas Contables',
                                    colModel: [                                         
                                        { label: 'Tipo', name: 'Tipo', width: 35,align:"center",classes:'bold cellOrange1', 
                                            formatter:function(cellvalue, options, rowObject){  
                                                var str='';
                                                switch(rowObject.Tip_Pld){
                                                    //case 'I': str='Inventario';break;
                                                    case 'C': str='Compras';break;
                                                    case 'V': str='Ventas';break;
                                                    case 'G': str='Gastos';break;
                                                    case 'O': str='Costos';break;
                                                    case 'N': str='Ingresos';break;
                                                    case 'E': str='Egresos';break;
                                                }
                                                return str;
                                            }
                                        },  
                                        { label: 'Consumo', name: 'Con_Des', width: 35,align:"center",classes:'columnHighlight1'}, 
                                        { label: 'Cat. Cod.', name: 'Cat_Cod', width: 30,align:"center", hidden:true },                                        
                                        { label: 'Pro. Cod.', name: 'Pro_Cod', width: 25,align:"center", hidden:true },
                                        { label: 'Pld. Cod.', name: 'Pld_Cod', key: false, width: 30,align:"center", hidden:false }, 
                                        { label: 'Pld. Tip.', name: 'Tip_Pld', width: 25,align:"center", hidden:true },                                         
                                        { label: 'Con. Cod.', name: 'Con_Cod', width: 30,align:"center", hidden:true },
                                        { label: 'Cuenta', name: 'Pld_Cdc', width: 50 ,align:"center"}, 
                                        { label: 'Descripci&oacute;n', name: 'Pld_Des', width: 90,align:"left" }/*,
                                            { label:'&nbsp;', name: 'act1', width: 30, align: 'center',viewable: false,hidden:true,
                                                formatter:function (cellvalue, options, rowObject) { 
                                                    return  '<span class="btn btn-danger btn-xs" title="Eliminar" type="button" onclick="deleteCuenta(\''+rowObject.Pld_Cod+'\',\''+rowObject.Tip_Pld+'\');"><i class="glyphicon glyphicon-trash"></i></span>';
                                                }
                                            }*/
                                    ]
                                },true); 
                                //$tabs.tabs( "option", "active", 0 );
                            });
                            $('.cons').hide();
                        </script>
                    </div>      
                  </div>

                </div> 
                
                    
                <!--INICIO DEL DIALOGO BUSCAR CUENTA--> 
                <div id="cuenProdDialog" title="B&uacute;squeda de Cuentas">  
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
                             <input type="hidden" name="tipo_doc_param" id="tipo_doc_param">
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
                <script>
                    $(document).ready(function () { 
                        // DIALOG BUSCAR CUENTAS
                        $.createSearchDialog('cuenProdDialog',[
                            { label: 'C&oacute;d.Int.', name: 'Pld_Cod', key: true, width: 15,align:"center",hidden:true },                                
                            { label: 'Codigo', name: 'Pld_Cdc', width: 45 },                      
                            { label: 'Cuenta', name: 'Pld_Des', width: 80, cellattr: function (rowId, tv, rawObject, cm, rdata) { return 'style="white-space: normal;"'; }},
                            { label: 'Grupo', name: 'Pld_Grupo', width: 110, cellattr: function (rowId, tv, rawObject, cm, rdata) { return 'style="white-space: normal;"'; }},
                            { label: 'Tipo', name: 'Pld_Tip', width: 30,align:"center" },
                            { label: 'Estado', name: 'Pld_Est', width: 30,align:"center"}, 
                                { label:'<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 30, align: 'center',viewable: false,
                                        formatter:function (cellvalue, options, rowObject) { 
                                                return  '<span class="btn btn-success btn-xs" title="Enviar al D&eacute;bito" onclick="addCuentaProd(\''+rowObject.Pld_Cod+'\');"><i class="glyphicon glyphicon-arrow-right"></i></span>&nbsp;'; 
                                        }
                                }
                        ]);
                     });
                </script> 
                <!-- FIN DEL DIALOGO CUENTAS-->    
                <!--INICIO DEL DIALOGO BUSCAR CUENTA--> 
                <div id="cuenCatDialog" title="B&uacute;squeda de Cuentas">  
                    <form class="form-horizontal normal">
                    <input type="hidden" name="tipo_doc_param" id="tipo_doc_param1"> 
                    <fieldset>
                            <legend>Filtros</legend>
                            <div class="form-group">
                                <label class="col-xs-2 control-label label-xs">Filtrar Por:</label>  
                                <div class="col-xs-5 radioset" >
                                      <input id="radcc1" name="op_opciones" type="radio" value="d" checked="" onclick="setfocus(this.form.search)" alt="" /><label for="radcc1">&nbsp;&nbsp;Descripci&oacute;n&nbsp;&nbsp;</label>
                                      <input id="radcc2" name="op_opciones" type="radio" value="c" onclick="setfocus(this.form.search)" alt="" /><label for="radcc2">&nbsp;&nbsp;C&oacute;digo&nbsp;&nbsp;</label>                          
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
                <script>
                    $(document).ready(function () { 
                        // DIALOG BUSCAR CUENTAS
                        $.createSearchDialog('cuenCatDialog',[
                            { label: 'C&oacute;d.Int.', name: 'Pld_Cod', key: true, width: 15,align:"center",hidden:true },                                
                            { label: 'Codigo', name: 'Pld_Cdc', width: 45 },                      
                            { label: 'Cuenta', name: 'Pld_Des', width: 80, cellattr: function (rowId, tv, rawObject, cm, rdata) { return 'style="white-space: normal;"'; }},
                            { label: 'Grupo', name: 'Pld_Grupo', width: 110, cellattr: function (rowId, tv, rawObject, cm, rdata) { return 'style="white-space: normal;"'; }},
                            { label: 'Tipo', name: 'Pld_Tip', width: 30,align:"center" },
                            { label: 'Estado', name: 'Pld_Est', width: 30,align:"center"}, 
                                { label:'<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 30, align: 'center',viewable: false,
                                        formatter:function (cellvalue, options, rowObject) { 
                                                return  '<span class="btn btn-success btn-xs" title="Enviar al D&eacute;bito" onclick="addCuentaCat(\''+rowObject.Pld_Cod+'\');"><i class="glyphicon glyphicon-arrow-right"></i></span>&nbsp;'; 
                                        }
                                }
                        ]);   
                     });
                    //Cargar el tipo de documento que se va a parametrizar  
                    function tipDocumentParam(tipo) {
                        let valor = "";
                        if (tipo === "CATG") {
                            valor = $("#formCuenCat #listTipoCat").val();
                            $("#cuenCatForm #tipo_doc_param1").val(valor);
                        }
                        if (tipo === "PROD") {
                            valor = document.getElementById("listTipo").value;
                            $("#tipo_doc_param").val(valor);
                        }
                        $.Search('cuenCat');
                        $.Search('cuenProd');
                    }
                </script> 
                <!-- FIN DEL DIALOGO CUENTAS-->    
                <?php } ?>    
            </div>
        </div>
       <script>
                   
       </script>     
    </BODY>
</HTML>