<?php
/**
* @abstract Permite realizar el registro de áreas,departamentos y subdepartamentos
* @author José Ambuludí
* @version 1.0
* Fecha de creación  2016-10-21
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/act_log_param_dep.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');


$obBD_conexion = new Class_Log_Conexion_act_dep($Ses_Dat_Dis);
$obBD_con1 =  new Class_Log_Datos_act_dep;

if(isset($areaAjax)){ 
    $area = $obBD_con1->getArrayConsulta(1,$Ses_Emp_Cod, $obBD_conexion);
    $departamento=$obBD_con1->getArrayConsulta(2,$Ses_Emp_Cod, $obBD_conexion);
    $arbol=  array_merge($area,$departamento);
    $response=$arbol;
    echo json_encode($response);exit();
}

//Complemento ajax para obtener el nuevo código
if (isset($ajaxCodigo))
{
    if($case=='D'){$num=4;}else{$num=6;}
    $maximo = $obBD_con1->getRowConsulta($num, $Are_Cod.'*'.$Ses_Emp_Cod, $obBD_conexion);          
    $responce=$maximo;
    $responce['next']=('0'.$maximo['max'])*1+1;
    echo json_encode($responce);
    exit();
}

//Cargar cuentas contables
if(isset($cuenAjax)){ 
    $contar = $obBD_con1->getRowConsulta(31, $search.'*'.$Ses_Emp_Cod.'*'.$Pec_Cod.'*'.$op_opciones.'*', $obBD_conexion);         
    $pagination= pages($contar['total'], $page, $rows);
    $responce=$pagination['data'];
    $responce['rows']=  $obBD_con1->getArrayConsulta(31, $search.'*'.$Ses_Emp_Cod.'*'.$Pec_Cod.'*'.$op_opciones.'*'.$pagination['limits'], $obBD_conexion);     
    utf8_encode_deep($responce['rows']);echo json_encode($responce);exit();
}

if(isset($cargarCuenta)){     
    $responce['rows']= $obBD_con1->getArrayConsulta(33, $Dep_Cod, $obBD_conexion);
    utf8_encode_deep($responce['rows']); 
    echo json_encode($responce);
    exit();
}

//Guardar relacion de codigo de departamento con cuenta contable
if(isset($addCuenta)){ 

    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);   
    $obBD_con1->operacionobBD(32, array('Dep_Cod' => $Dep_Cod, 'Pla_Cod'=> $Pld_Cod), $obBD_conexion);
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);   
    if($obBD_con1->Error==0){ 
        $responce['success']=true; 
    }
    else{
     $responce=array(success=>false,message=>'No se pudo realizar la transaccion!',error=>$obBD_con1->MsgError); 
    }
    echo json_encode($responce); 
    exit();
}

if(isset($eliminarCuenta)){ 
    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);   
    $obBD_con1->operacionobBD(34, $Depar_Cod, $obBD_conexion);
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);   

    if($obBD_con1->Error==0){ 
        $responce['success']=true; 
    }
    else{
     $responce=array(success=>false,message=>'No se pudo realizar la transaccion!',error=>$obBD_con1->MsgError); 
    }

    echo json_encode($responce); 
    exit();
}



$periodos = $obBD_con1->getArrayConsulta(30, $Ses_Emp_Cod, $obBD_conexion);
$periodo = $periodos[0];

?>
<!DOCTYPE html>
<HTML>
    <HEAD>
        <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
        <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php")?>
        <link rel="stylesheet" href="../../framework/jquery/jquery.jstree/themes/default/style.min.css" />
        <script src="../../framework/jquery/jquery.jstree/jstree.min.js"></script>
        <link rel="stylesheet" href="../../framework/jquery/summernote/summernote.css">
        <script type="text/javascript" src="../../framework/jquery/summernote/summernote.min.js"></script>
        <script src="../../framework/jquery/summernote/lang/summernote-es-ES.js"></script>
    </HEAD>
    <BODY>
        <div class="panel panel-main">
            <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Configurar Departamentos</h3></div>
            <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                <div class="row">
                    <div class="col-md-12">  
                        <fieldset class="exa-fieldset">                           
                            <legend class="Titulos2">Formulario de Registro</legend>
                            <div class="col-md-6">
                                <div class="panel panel-success exa-panel">
                                    <div class="panel-heading"><i class="fa fa-list-ol"></i>&nbsp;&nbsp;<span>&Aacute;rbol de &aacute;reas</span>
                                    </div>
                                    <div class="panel-body">
                                        <div class="scrollable-tree" style="height: 400px;"><div id="using_json_2"></div></div>
                                    </div> 
                                    <div id="foot" class="panel-footer"><span id="plan-footer"><strong>Leyenda:</strong> <span class="glyphicon glyphicon-home red"></span> &Aacute;reas | <span class="glyphicon fa fa-users blue"></span> Departamentos </span></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <fieldset class="exa-fieldset">                           
                                    <legend class="Titulos2">Registro de cuenta de depreciaci&oacute;n</legend>
                                        <input type="hidden" id="Dep_Cod" name="Dep_Cod">
                                        <div style="min-height: 250px;">
                                           <table id="list_1"></table>
                                           <div id="listPager_1"></div>
                                           <button onclick="$('#cuenDialog').dialog('open');" title="Buscar Cuentas" type="button" class="btn btn-success btn-sm" style="margin-top: 10px;"><i class="glyphicon glyphicon-check"></i><span> Agregar Cuenta</span></button>
                                        </div>
                                </fieldset>
                            </div>
                        </fieldset>
                    </div>
                </div>
            </div>
        </div>

        <div style="display: none">
            <form id="formCuentas">
                <input type="text" id="Dep_Cod" name="Dep_Cod" value=""/>
            </form>
        </div>    

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
                            <input name="periodo" type="text" size="6" value="<? echo $periodo['Pla_Fec']?>" readonly style="text-align: center;display: inline-block;width: auto;" class="form-control input-xs" /> 
                            <input name="Pec_Cod" type="hidden" value="<? echo $periodo['Pec_Cod']?>" /> 
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


        <script type="text/javascript">
            $(function(){
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

                //Llamo a la función updateTipoActivo desde aqui para que cargue el jstree al momento de cargar la página
                updateTipoActivo();

            });
            

            $("#list_1").createGrid({    
                height: 200, rowNum: 10000000, pgbuttons: false,pgtext: null, caption:'Cuenta Depreciación',            
                colModel: [
                    { label: 'Dep.Cód', name: 'Dep_Cod', width: 40, align:"center", hidden:false },
                    { label: 'Cód.Int.', name: 'Pld_Cod', key: true, width: 40,align:"center", hidden:false },                                
                    { label: 'Cod. Cuenta', name: 'Pld_Cdc', width: 100 },                      
                    { label: 'Cuenta Contable', name: 'Pld_Des', width: 250  },                               
                    { label:'&nbsp;', name: 'act1', width: 30, align: 'center',viewable: false,
                        formatter:function (cellvalue, options, rowObject) { return  $.getGridButton(deleteCuenta,{Dep_Cod:rowObject.Dep_Cod},'Eliminar','remove',null,'danger'); }
                    }
                ]
            },true,"#listPager_1",{refresh: true,view: true});

            //Variable para manejo del arbol jstree
            var $treeview=$('#using_json_2');     
            var Dep_Cod=0,
            Dep_Des='';

            function updateTipoActivo(){
                $treeview.jstree(true).settings.core.data = {'url': '<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>?areaAjax=true',"dataType": "json" };
                $treeview.jstree(true).refresh();
            } 

            $treeview.jstree({'core' : {'data': {}},
                'types' : {
                    "A"  : {"icon" : "glyphicon glyphicon-home red"},
                    "D"  : {"icon" : "glyphicon fa fa-users blue"},
                    "SD" : {"icon" : "glyphicon fa fa-briefcase green"},
                    "C"  : {"icon" : "glyphicon glyphicon-user black"}
                },"plugins": ["types"]}).on('select_node.jstree', function (e, data) { 
                    var type=data.node.type,Are_Cod;
                    limpiar(true);
                    $('#dep_des').val('');
                    
                    if(type==='D'){
                        Dep_Cod=data.node.id;
                        $.get('<?php echo filter_input(INPUT_SERVER,'PHP_SELF',FILTER_SANITIZE_STRING)?>',{'Dep_Cod':Dep_Cod,'cargarCuenta':true},
                            function(response){
                                if(Dep_Cod){
                                    $("#list_1").jqGrid("addRowData", 0, response["rows"]);
                                }
                            },'json').fail(function() { $.alert("El Servidor ha fallado en responder!");});
                    }
                    else{
                        Dep_Cod = null;
                    }
                });

            function limpiar(value)
            {
                $("#list_1").jqGrid("clearGridData");
            }

             function addCuenta(a2){ 
                 $('#cuenDialog').dialog('close');
                 if(!Dep_Cod){$.alert('Seleccione un Departamento!');return;}                       
                 $.saveDataJson("<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>",{Pld_Cod:a2 ,Dep_Cod:Dep_Cod ,addCuenta:true},
                    function(r){
                        $("#list_1").jqGrid("clearGridData");
                        $.get('<?php echo filter_input(INPUT_SERVER,'PHP_SELF',FILTER_SANITIZE_STRING)?>',{'Dep_Cod':Dep_Cod,'cargarCuenta':true},
                            function(response){
                                if(Dep_Cod){
                                    $("#list_1").jqGrid("addRowData", 0, response["rows"]);
                                }
                            },'json').fail(function() { $.alert("El Servidor ha fallado en responder!");});
                        $("#list_1").jqGrid().trigger("reloadGrid");
                    }
                );
            }


            function deleteCuenta(Dep_Cod)
            {
                var data={eliminarCuenta:true, Depar_Cod:Dep_Cod};
                $.createDialogConfirm('¿Está seguro que desea eliminar esta cuenta?',data,deleteCta);
            }
            function deleteCta(data)
            {   
                $.post( "<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>",data, function(response) {
                    if(response['success']===true){
                        $.alert("Transaccion Realizada con &Eacute;xito!"); 
                        $("#list_1").jqGrid("clearGridData");                         
                    }else{$.alert(response['message']);}
                 },'json').fail(function(error) { $.alert();});
            }

        </script>
    </BODY>
</HTML>
