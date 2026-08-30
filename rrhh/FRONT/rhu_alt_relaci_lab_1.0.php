<?php	
/**
* @abstract Permite realizar el registro de una relaci�n laboral
* @author Jos� Ambulud�
* @version 1.0
* Fecha de creaci�n  2016-11-08
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/rhu_log_relaci_lab.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
/**
* Creacion del Objeto de conexi�n
*/
$obBD_conexion = new Class_Log_Conexion_relaci($Ses_Dat_Dis);
/**
* Creacion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_relaci;
//Lista los dedica_lab registrados
if(isset($listarRelaci)){ 
    $responce = $obBD_con1->getArrayConsulta(2,$Ses_Suc_Cod, $obBD_conexion);
    echo json_encode($responce);exit();
}
if(isset($saveRelacion)){
    $response['success'] = false;
    $response['message'] = "No se ha logrado realizar la Transaccion";
    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
    foreach($campos as $valor){
        $existe=$obBD_con1->getRowConsulta(5,$valor['Reb_Cod'],$obBD_conexion);
        if(!empty($existe['Reb_Cod'])){
            $obBD_con1->operacionobBD(3,$valor['Reb_Cod'].'*'.$valor['Reb_Des'], $obBD_conexion);
        }else{
            $obBD_con1->operacionobBD(1,$Ses_Suc_Cod.'*'.$valor['Reb_Des'], $obBD_conexion);
        }
    }
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);
    if ($obBD_con1->Error == 0) { $response['success'] = true; }
    echo json_encode($response);
    exit();
}
if(isset($delete)){
    $response['success'] = false;
    $response['message'] = "No se ha logrado realizar la Transaccion";
    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
    $obBD_con1->operacionobBD(4,$Reb_Cod, $obBD_conexion); 
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);
    if ($obBD_con1->Error == 0) { $response['success'] = true; }
    echo json_encode($response);
    exit();
}
?>

<!DOCTYPE html>
<HTML>
    <HEAD>		
        <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
        <TITLE><?Php echo "Relación Laboral Registro [EXA]"; ?></TITLE>
        <meta charset="UTF-8">
        <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php")?>
        <style>
            #listPager_center{ display: none; }
            th.ui-th-column div{
                white-space:normal !important;
                height:auto !important;
                padding:2px;
            }
        </style>
    </HEAD>
<BODY>
    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Registro Relaci&oacute;n Laboral</h3></div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div class="row">
                <div class="col-md-3"></div>
                <div class="col-md-6 col-sm-12"> 
                    <fieldset class="exa-fieldset">                           
                        <legend class="Titulos2">Registro de Campos</legend> 
                        <div id="listado">
                            <table id="list"></table>
                            <div id="listPager"></div>
                        </div> 
                        <div style="text-align: center; padding: 8px;">
                            <button id="bt_guardar" name="bt_guardar" type="button" onclick="saveData();" class="btn btn-primary btn-xs"><span class="glyphicon glyphicon-floppy-disk"></span> Guardar</button>
                        </div>
                    </fieldset>
                </div>
            </div>
        </div>
    </div>
   
    <script type="text/javascript">
        $(function() {
            $("#list").jqGrid({
                datatype: "json", mtype: "GET",responsive:true,autowidth : true,height:250,
                postData:{listarRelaci:true},
                cmTemplate: {sortable:false},
                colModel: [  
                    { label: 'Cod', key:true,hidden:true, name: 'Reb_Cod', width: 150 },
                    { label: 'Descripci&oacute;n', name: 'Reb_Des', width: 230,align:'center',editable:true}, 
                    { label:'&nbsp;', name: 'act1', width: 30, align: 'center',viewable: false,
                        formatter:function (cellvalue, options, rowObject) {   
                            return  '<span id="eli'+options.rowId+'" class="btn btn-danger btn-xs" title="Eliminar" type="button" onclick="deleteFila(\''+options.rowId+'\')";><i class="glyphicon glyphicon-trash"></i></span>';
                        }
                    }
                ],
                gridComplete: function() {
                    var ids = jQuery("#list").jqGrid('getDataIDs');
                    for (var i = 0; i < ids.length; i++) {
                        var cl = ids[i];
                        $("#list").jqGrid('editRow',cl);
                    }
                },
                rowNum:10000000,pager: '#listPager',viewrecords: true,gridview: true,sortorder: "desc",rownumbers:true,altRows: true, altclass: "myAltRowClass"  
            });
            $("#list").jqGrid('navGrid',"#listPager",{edit:false,add:false,del:false,search:false,refresh:false})
            .navButtonAdd('#listPager',{
                caption:"Agregar campo",
                id:'btn_agr',
                buttonicon:"glyphicon glyphicon-plus", 
                title:'Agregar',
                onClickButton: function(){ 
                    var $this=$(this),id=($this.jqGrid('getCol','Ded_Cod',false,'max')+1)||0; 
                    $this.jqGrid('addRowData',id,{'Ded_Cod':id});     
                    $this.jqGrid('editRow',id);
                    $('#eli'+id).show();
                }, 
                position:"last"
            });  
        });
        //Funci�n para guardar los registros del jqgrid 
        function saveData(){
            $.post("<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8');?>",{'saveRelacion':true,'campos':$("#list").getGridBatch()},function(response){
                if(response['success']===true){
                    $.alert('Transaccion Realizada con &Eacute;xito!');
                    var ids = $("#list").jqGrid('getDataIDs');
                    for (var i = 0; i < ids.length; i++) {
                        var cl = ids[i];
                        $("#list").jqGrid('editRow',cl);
                    }
                }
            },'json').fail(function(){$alert();});
        }
        //Funci�n para eliminar un registro tanto del jqgrid 
        function deleteFila(index){
            $.createDialogConfirm('Desea ELIMINAR el registro seleccionado..!!',null,function(){
               $('#list').jqGrid('delRowData',index);
                $.post("<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8')?>",{delete:true,Reb_Cod:index},function(response){
                    if(response['success']===true){console.log('Elimino l�gicamente');}
                    else{$.alert(response['message']);}
                },'json').fail(function (){$.alert();}); 
            });
        }
    </script>
</BODY>
</HTML>

