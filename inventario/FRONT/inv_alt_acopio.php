<?php
/**
 * @abstract Permite realizar el registro de productores de fruta
 * @author Erik Niebla
 * @version 1.0
 * Fecha de creación  2018-05-18
 */
require_once('../../administrador/LOGICA/seguridad.php');
//require_once('../LOGICA/ban_log_productor.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/* Creacion del objeto mysql para las consultas */
$obBD_con1 = new MysqlDatos();
$obBD_con1->setConnection(new Class_Log_Conexion_Global($Ses_Dat_Dis));
$hoy = date("Y-m-d");

if(isset($usuaAjax)){
    $page=$obBD_con1->getPageGridJson('usuarios.selectWhere', array_merge(array('setWhere'=>array('isActive','setSucCod')),$_GET));
}
if(isset($acopioSearch)){
    $r=$obBD_con1->getPageGrid('acopio.selectWhere', array_merge($_GET,array('setWhere'=>array('setSucCod','orderByNom'/*'isActive'*/)) ));
    foreach($r['rows'] as &$v){
        $v['Usuarios']=$obBD_con1->getArrayConsulta('usuarios.selectWhere', array('Aco_Cod'=>$v['Aco_Cod'], 'setWhere'=>array('setAcopioUsuario','isActive')));
    } unset($v);
    $obBD_con1->echoJson($r);
}
if(isset($saveAcopio)){
    $resp=array(); 
    $obBD_con_set = new MysqlDatos();
    $obBD_con_set->setConnection(new Class_Log_Conexion_Global($Ses_Dat_Dis));
    //$obBD_con_set->debug(true);
    $obBD_con_set->inicioTransaccion();
    try{   
        if(isset($Aco_Cod)&&!empty($Aco_Cod)){
            $where=array('Aco_Cod'=>$Aco_Cod);
            $obBD_con_set->operacionobBD('acopio_usuario.deleteWhere', $where);  
            $obBD_con_set->operacionobBD('acopio.update', array('Aco_Des'=>$Aco_Des,'where'=>$where));  
        }else{
            $obBD_con_set->operacionobBD('acopio.insert', array('Suc_Cod'=>$Ses_Suc_Cod,'Act_Tip'=>$Act_Tip,'Aco_Des'=>$Aco_Des));  
            $Aco_Cod=$obBD_con_set->insercionid();
        }
        if(isset($Usuarios)&&is_array($Usuarios))
        foreach($Usuarios as $v)
            $obBD_con_set->operacionobBD('acopio_usuario.insert', array('Aco_Cod'=>$Aco_Cod,'Usu_Cod'=>$v['Usu_Cod']));
        $resp['Aco_Cod']=$Aco_Cod;
        $obBD_con_set->finTransaccionNoMsn($resp);
    } catch(Exception $e){ $obBD_con_set->rollBackNomsn($e->getMessage(),$resp); }
    $obBD_con_set->echoJson($resp);
}
$tipos=$obBD_con1->getArrayConsulta('acopio_tipo.selectWhere', array('Act_Est'=>'A'))
?>
<!DOCTYPE html>
<HTML>
<HEAD>		
    <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
    <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <style></style>
</HEAD>
<BODY>
<div class="panel panel-main">
    <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Gestion Bodegas</h3></div>
    <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
        <div id="searchDiv" class="row main">
            <div class="col-sm-12">
                <fieldset class="exa-fieldset">                           
                    <legend class="Titulos2">Filtros</legend> <!-- Form Name -->
                    <form id="formularioSearch" class="form-horizontal normal" action="javascript:acopioSearch.Search('#formularioSearch','acopioSearch');">
                         <div class="form-group">
                           <label class="control-label label-sm col-xs-2">Seleccione Periodo:</label>
                           <div class="col-xs-2">
                             
                           </div>
                           
                           <div class="col-xs-2">
                               <button type="submit" onclick="" class="btn btn-sm btn-success" ><i class="glyphicon glyphicon-search"></i> Buscar</button>
                           </div>    
                         </div>  
                      </form>                         
                 </fieldset>
            </div>
            <div class="col-sm-12">
                <div style="min-height: 300px; padding-bottom:8px; ">
                    <table id="acopioSearch"></table>
                    <div id="acopioSearchPager"></div>
                </div>
            </div>
        </div> 
        <div id="editDiv" class="row main"> 
            <div class="col-sm-6">
                <form id="formAcopio" action="javascript:saveAcopio()" class="form-horizontal normal">
                    <input name="Aco_Cod" class="hidden" />
                    <fieldset class="exa-fieldset">
                        <legend class="Titulos2">Datos Bodega</legend>
                        <div class="form-group">
                            <label class="control-label label-xs col-xs-2">Nombre:</label>
                            <div class="col-xs-8"><span name="Suc_Des" class="form-control input-xs datatitle" ></span></div>
                        </div>  
                        <div class="form-group">
                            <label class="control-label label-xs col-xs-2">Tipo:</label>
                            <div class="col-xs-5">
                                <select name="Act_Tip" id="Act_Tip" class="form-control input-xs readOnly" required="">
                                    <?php foreach ($tipos as $v) {
                                        echo "<option value='$v[Act_Tip]' data--act_-acu='$v[Act_Acu]' data--act_-gen='$v[Act_Gen]'>$v[Act_Des]</option>";
                                    }?>                                
                                </select>
                            </div>
                        </div>  
                        <div class="form-group">
                            <label class="control-label label-xs col-xs-2">Nombre:</label>
                            <div class="col-xs-8">
                                <input name="Aco_Des" class="form-control input-xs" />
                            </div>
                        </div>   
                    </fieldset>
                </form>
            </div>
            <div class="col-sm-6">
                <div style="min-height: 300px; padding-bottom:8px; ">
                    <table id="usuariosList"></table>
                    <div id="usuariosListPager"></div>
                </div>
            </div>                
            <div class="col-xs-12">
                <button class="btn btn-sm btn-inverse" onclick="$('#editDiv').moveComp('#searchDiv').updateGridsSizes();"><i class="glyphicon glyphicon-arrow-left"></i> Atrás</button>
                <button class="btn btn-sm btn-success" onclick="$('#formAcopio').formSubmit();" ><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>                        
            </div>
        </div> 
    </div>
</div>


<script type="text/javascript">
var acopioSearch,usuaList;
$(function(){
    acopioSearch=$('#acopioSearch');
    usuaList=$('#usuariosList');
    $('div.main.row').initDivs({
        searchDiv:function(){
            acopioSearch.createGrid({        
                height: 250,caption:'&nbsp;Bodegas', stateCol:'Aco_Est',// stateConfig:{G:'cellGreen2'},
                colModel: [
                    { label: 'Cód.Int.', name: 'Aco_Cod', key: true, width: 10, align:"center", hidden:false },
                    { label: 'Abr.', name: 'Act_Tip', width: 10, align:"center" },                      
                    { label: 'Tipo', name: 'Act_Des', width: 25  },
                    { label: 'Nombre', name: 'Aco_Des', width: 100 },
                    { label: 'Aco_Tip', name: 'Aco_Tip', width: 10, hidden:true  },
                    { label: 'Estado', name: 'Aco_Est', width: 10, formatter:'estado',formatoptions:{full:true}, align:'center'  },
                    { label: $.createIcon('pencil'), name: 'act01', width: 25, formatter:'gridButton', formatoptions:{action:'editAcopio', conditional:function(o){ return o.Aco_Est==='A'/*&&o.Act_Gen==='M'*/; } }  }
                ]
            },true,'#acopioSearchPager').gridButtonsAdd([
                {buttonicon:'plus',caption:'Agregar Bodega',onClickButton:addAcopio }
            ]);
        },
        editDiv:function(){
            usuaList.createGrid({        
                height: 150,caption:'&nbsp;Usuarios', stateCol:'Aco_Est',// stateConfig:{G:'cellGreen2'},
                colModel: [
                    { label: 'Cód.Int.', name: 'Usu_Cod', key: true, width: 10, align:"center", hidden:true },
                    { label: 'Cedula/Ruc', name: 'Prs_Ced', width: 10, align:"center" },
                    { label: 'Usuario', name: 'Usuario', width: 20, align:"center" },
                    { label: $.createIcon('remove'), name: 'act01', width: 5, formatter:'gridButton', formatoptions:{action:'removeUsuario',data:'Usu_Cod',type:'danger',icon:'remove',title:'Quitar Usuario'}  }
                ]
            },true,'#usuariosListPager').gridButtonsAdd([
                {buttonicon:'plus',caption:'Agregar Usuario',onClickButton:function(){ $('#usuaDialog').dialog('open'); } }
            ]);
        }
    });
    $('#usuaDialog').createSearchDialog({colModel:[
        { label: 'C&oacute;d.Int.', name: 'Usu_Cod', key: true, width: 15,align:"center",hidden:true },  
        { label: 'C&oacute;d.Int.', name: 'Prs_Cod', width: 15,align:"center",hidden:true },
        { label: 'Cédula/RUC', name: 'Prs_Ced', width: 50 },                      
        { label: 'Productor', name: 'Usuario', width: 100},
        { label:'&nbsp;', name: 'act1', width: 20, align: 'center',viewable: false, formatter:'gridButton', formatoptions:{action:'addUsuario'} }
    ]},{ title:'Usuario' },{ title:'Busqueda de Usuarios' }); 
    $('#searchDiv').show();
});
function addUsuario(data){    
    if(!usuaList.existsId(data.Usu_Cod)){
        usuaList.setRow(data);
        $('#usuaDialog').dialog('close');
    }else
        $.alert('El Usuario ya se encuentra en el listado!');
}
function removeUsuario(Usu_Cod){ usuaList.jqGrid('delRowData',Usu_Cod);}
function addAcopio(){ 
    //$('#formAcopio').setData({Suc_Des:<?php echo $Ses_Suc_Cod; ?>});
    $('#formAcopio').setData({});
    $('#searchDiv').moveComp('#editDiv').updateGridsSizes();
}
function editAcopio(data){
    console.log(data);
    $('#formAcopio').setData(data);
    $('#Act_Tip').prop('disabled',data.Act_Gen==='A');
    usuaList.setRows(data['Usuarios']);
    $('#searchDiv').moveComp('#editDiv').updateGridsSizes();    
}
function saveAcopio(){
    var data=$('#formAcopio').getData('saveAcopio');
    data['Usuarios']=usuaList.getGridBatch();
    console.log(data);
    $.createDialogConfirm('¿Est&aacute; seguro que desea remover el codigo?',data,function(d){
        $.saveDataJson('',d,function (r){            
            $('#editDiv').moveComp('#searchDiv').updateGridsSizes();
            acopioSearch.gridUpdate();
        });
    });
}
</script>

</BODY>
</HTML>