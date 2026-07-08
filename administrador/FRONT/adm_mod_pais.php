<?php	
/**
* @abstract Permite realizar la cancelacion de comprobantes por abonos
* @author Erik Niebla
* @version 1.0
* Fecha de creaci�n  2015-07-22
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require('../LOGICA/adm_log_pais.php');
/**
* Creacion del Objeto de conexion
*/
$obBD_conexion = new Class_Log_Conexion_Pais($Ses_Dat_Dis);
/**
* Creacion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Pais;


$hoy = date("Y-m-d");
$mes = date("m");

if(isset($planAjax)){     
    $paises = $obBD_con1->getArrayConsulta(1,"", $obBD_conexion);
    $regiones=$obBD_con1->getArrayConsulta(2,"", $obBD_conexion);
    $provincias=$obBD_con1->getArrayConsulta(3,"", $obBD_conexion);
    $ciudades=$obBD_con1->getArrayConsulta(4,"", $obBD_conexion);
    $g1=  array_merge($paises,$regiones);
    $g2=array_merge($g1,$provincias);
    $g3=array_merge($g2,$ciudades);
    $responce=$g3;
    utf8_encode_deep($responce);
    echo json_encode($responce);exit();
}
if(isset($gridAjax)){
    $responce['rows']=array();
    switch ($tipo){        
        case 'P':
            $responce['rows'] = $obBD_con1->getArrayConsulta(13,$parent, $obBD_conexion);  
            break;
        case 'R':
           $responce['rows'] = $obBD_con1->getArrayConsulta(14,$parent, $obBD_conexion);
            break;
        case 'V':
            $responce['rows'] = $obBD_con1->getArrayConsulta(15,$parent, $obBD_conexion);
            break;   
    }    
    utf8_encode_deep($responce);
    echo json_encode($responce);exit();
}
if(isset($save)){   
    $responce['success']=false;$responce['message']="No se ha logrado realizar la Transaccion"; 
    $data=filter_input_array(INPUT_POST);
    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
    switch ($tipo){
        case 'P':
            $obBD_con1->operacionobBD(9,$data, $obBD_conexion);
            break;
        case 'R':
            $obBD_con1->operacionobBD(10,$data, $obBD_conexion);    
            break;
        case 'V':
            $obBD_con1->operacionobBD(11,$data, $obBD_conexion);    
            break;
        case 'C':
            $obBD_con1->operacionobBD(12,$data, $obBD_conexion);    
            break;            
    }
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);
    if($obBD_con1->Error==0){ $responce['success']=true; }   
    echo json_encode($responce);
    exit();
}
if(isset($anula)){         
        $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
        $obBD_con1->grabarv_registros(sentencias_pais(16,$obBD_con1->parametros($anula)), $obBD_conexion->conexion);
        $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);       
        if($obBD_con1->Error==0) $responce['success']=true; else $responce['success']=false;$responce['message']=$obBD_con1->MsgError;
	echo json_encode($responce);exit();
}
?>
<!DOCTYPE html>
<HTML>
	<HEAD>		
                <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
                <TITLE><?Php echo "País Modificar [EXA]"; ?></TITLE>
                <meta charset= "UTF-8">
                <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php")?>
                <link rel="stylesheet" href="../../framework/jquery/jquery.jstree/themes/default/style.min.css" />
                <script src="../../framework/jquery/jquery.jstree/jstree.min.js"></script>

                <style>
                     
                     
                </style>
	</HEAD>
<BODY>
 
    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Modificación de Paises/Regiones/Provincias/Ciudades</h3></div>
        
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div class="">
                <div class="row">
                    <div class="col-sm-6">  
                        
                        <fieldset class="exa-fieldset">                           
                           <legend class="Titulos2">Paises/Regiones/Provincias/Ciudades</legend> <!-- Form Name -->

                           <div class="panel panel-success exa-panel">
                               <div class="panel-heading"><i class="fa fa-list-ol"></i>&nbsp;&nbsp;<span id="plan-tittle">Listado</span>
                                   <div class="pull-right">                                       
                                       <button type="button" onclick="$('#DialogP').dialog('open');" class="btn btn-success btn-xs btn-add btn-P" style="display:none"><span class="fa fa-pencil"></span><b> Editar Pais</b></button>
                                       <button type="button" onclick="$('#DialogR').dialog('open');" class="btn btn-success btn-xs btn-add btn-R" style="display:none"><span class="fa fa-pencil"></span><b> Editar Region</b></button>
                                       <button type="button" onclick="$('#DialogV').dialog('open');" class="btn btn-success btn-xs btn-add btn-V" style="display:none"><span class="fa fa-pencil"></span><b> Editar Provincia</b></button>
                                       <button type="button" onclick="$('#DialogC').dialog('open');" class="btn btn-success btn-xs btn-add btn-C" style="display:none"><span class="fa fa-pencil"></span><b> Editar Ciudad</b></button>
                                       <button type="button" onclick="$.createDialogConfirm('¿Está seguro que desea anular la Ciudad: <b>'+ciudad+'</b>?',null,anulaCiudad);" class="btn btn-danger btn-xs btn-add btn-C" style="display:none"><span class="fa fa-trash-o"></span><b> Anular Ciudad</b></button>
                                   </div>
                               </div>
                            <div class="panel-body">
                                <div class="scrollable-tree" style="height: 273px"><div id="using_json_2"></div></div>
                            </div> 
                               <div class="panel-footer">Seleccione un <span class="green">ITEM</span>.</div>   
                          </div>
                        </fieldset>
                    </div>
                    <div class="col-sm-6">                   
                        <div>
                            <table id="comp"></table>
                        </div>                        
                    </div>
                </div>    
              
            </div>   
        </div>
    </div>

    <div id="DialogP" title="Editar Pais"> 
        <div class="row">
            <div class="form-horizontal normal col-sm-12" >
                <fieldset>
                    <legend><label class="Titulos2">Datos del Pais</label></legend>
                    <form id="formP" class="form-horizontal normal"  action="javascript:$.createDialogConfirm(null,'formP',savePais)"  >
                    <div class="form-group">
                       <label class="col-sm-3 control-label label-sm required">Nombre País:</label>  
                       <div class="col-sm-9" >
                           <input type="text" name="tipo" value="P" style="display: none" />
                           <input type="text" id='P_Pas_Cod' name="Pas_Cod" value="" style="display: none" />
                           <input type="text" class="form-control input-sm" id='P_Pas_Nom' name="Pas_Nom" value="" required />
                       </div>
                     </div>
                     <div class="form-group">
                       <label class="col-sm-3 control-label label-sm required">Nacionalidad:</label>  
                       <div class="col-sm-9" >
                           <input type="text" class="form-control input-sm" id='P_Pas_Nac' name="Pas_Nac" value="" required />
                       </div>
                     </div>
                     <div class="form-group">
                        <label class="col-sm-3 control-label">Acción:</label>
                        <div class="col-sm-9">
                            <button type="submit"  class="btn btn-primary"><span class="glyphicon glyphicon-floppy-disk"></span> Guardar</button>
                            <button type="button" onclick="$('#DialogP').dialog('close');"  class="btn btn-danger"><span class="glyphicon glyphicon-remove"></span> Cancelar</button>
                        </div>
                    </div>
                    </form>    
                    <div class="form-group Titulos2">
                        <div class="col-sm-12"><hr/><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco (  <span class="required"></span> ) son campos obligatorios.</div>
                    </div>  
                 </fieldset>    
            </div>
        </div>
    </div>    
    <div id="DialogR" title="Editar Region"> 
        <div class="row">
            <div class="form-horizontal normal col-sm-12" >
                <fieldset>
                    <legend><label class="Titulos2">Datos de la Región:</label></legend>
                    <form id="formR" class="form-horizontal normal"  action="javascript:$.createDialogConfirm(null,'formR',savePais)"  >
                    <div class="form-group">
                       <label class="col-sm-3 control-label label-sm required">Nombre País:</label>  
                       <div class="col-sm-9" >
                           <input type="text" name="tipo" value="R" style="display: none" />                           
                           <input type="text" class="form-control input-sm" id="R_Pas_Nom" name="Pas_Nom" value="" readonly />
                       </div>
                     </div>
                     <div class="form-group">
                       <label class="col-sm-3 control-label label-sm required">Región:</label>  
                       <div class="col-sm-9" >
                           <input type="text" name="Reg_Cod" id="R_Reg_Cod" value="" style="display: none" />   
                           <input type="text" class="form-control input-sm" name="Reg_Nom" id="R_Reg_Nom" value="" required autofocus />
                       </div>
                     </div>
                     <div class="form-group">
                        <label class="col-sm-3 control-label">Acción:</label>
                        <div class="col-sm-9">
                            <button type="submit"  class="btn btn-primary"><span class="glyphicon glyphicon-floppy-disk"></span> Guardar</button>
                            <button type="button" onclick="$('#DialogR').dialog('close');"  class="btn btn-danger"><span class="glyphicon glyphicon-remove"></span> Cancelar</button>
                        </div>
                    </div>
                    </form>    
                    <div class="form-group Titulos2">
                        <div class="col-sm-12"><hr/><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco (  <span class="required"></span> ) son campos obligatorios.</div>
                    </div>  
                 </fieldset>    
            </div>
        </div>
    </div>          
    <div id="DialogV" title="Editar Provincia"> 
        <div class="row">
            <div class="form-horizontal normal col-sm-12" >
                <fieldset>
                    <legend><label class="Titulos2">Datos de la Provincia:</label></legend>
                    <form id="formV" class="form-horizontal normal"  action="javascript:$.createDialogConfirm(null,'formV',savePais)"  >
                     <div class="form-group">
                       <label class="col-sm-3 control-label label-sm required">País:</label>  
                       <div class="col-sm-9" >  
                           <input type="text" class="form-control input-sm" id="V_Pas_Nom" value="" readonly />
                       </div>
                     </div>
                    <div class="form-group">
                       <label class="col-sm-3 control-label label-sm required">Región:</label>  
                       <div class="col-sm-9" >                                                     
                           <input type="text" class="form-control input-sm" id="V_Reg_Nom" name="Reg_Nom" value="" readonly />
                       </div>
                     </div>
                     <div class="form-group">
                       <label class="col-sm-3 control-label label-sm required">Provincia:</label>  
                       <div class="col-sm-9" >
                           <input type="text" name="tipo" value="V" style="display: none" /> 
                           <input type="text" id="V_Pro_Cod" name="Pro_Cod" value="" style="display: none" />
                           <input type="text" class="form-control input-sm" name="Pro_Nom" id="V_Pro_Nom" value="" required autofocus />
                       </div>
                     </div>
                     <div class="form-group">
                        <label class="col-sm-3 control-label">Acción:</label>
                        <div class="col-sm-9">
                            <button type="submit"  class="btn btn-primary"><span class="glyphicon glyphicon-floppy-disk"></span> Guardar</button>
                            <button type="button" onclick="$('#DialogV').dialog('close');"  class="btn btn-danger"><span class="glyphicon glyphicon-remove"></span> Cancelar</button>
                        </div>
                    </div>
                    </form>    
                    <div class="form-group Titulos2">
                        <div class="col-sm-12"><hr/><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco (  <span class="required"></span> ) son campos obligatorios.</div>
                    </div>  
                 </fieldset>    
            </div>
        </div>
    </div>       
    <div id="DialogC" title="Registrar Nueva Ciudad"> 
        <div class="row">
            <div class="form-horizontal normal col-sm-12" >
                <fieldset>
                    <legend><label class="Titulos2">Datos de la Ciudad:</label></legend>
                    <form id="formC" class="form-horizontal normal"  action="javascript:$.createDialogConfirm(null,'formC',savePais)"  >
                    <div class="form-group">
                       <label class="col-sm-3 control-label label-sm required">País:</label>  
                       <div class="col-sm-9" >
                           <input type="text" class="form-control input-sm" id="C_Pas_Nom" value="" readonly />
                       </div>
                     </div>    
                    <div class="form-group">
                       <label class="col-sm-3 control-label label-sm required">Provincia:</label>  
                       <div class="col-sm-9" >                           
                           <input type="text" class="form-control input-sm" id="C_Pro_Nom" name="Pro_Nom" value="" readonly />
                       </div>
                     </div>
                     <div class="form-group">
                       <label class="col-sm-3 control-label label-sm required">Ciudad:</label>  
                       <div class="col-sm-9" >
                           <input type="text" name="tipo" value="C" style="display: none" />
                           <input type="text" id="C_Ciu_Cod" name="Ciu_Cod" value="" style="display: none" />
                           <input type="text" class="form-control input-sm" name="Ciu_Des" id="C_Ciu_Des" value="" required autofocus />
                       </div>
                     </div>
                     <div class="form-group">
                        <label class="col-sm-3 control-label">Acción:</label>
                        <div class="col-sm-9">
                            <button type="submit"  class="btn btn-primary"><span class="glyphicon glyphicon-floppy-disk"></span> Guardar</button>
                            <button type="button" onclick="$('#DialogC').dialog('close');"  class="btn btn-danger"><span class="glyphicon glyphicon-remove"></span> Cancelar</button>
                        </div>
                    </div>
                    </form>    
                    <div class="form-group Titulos2">
                        <div class="col-sm-12"><hr/><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco (  <span class="required"></span> ) son campos obligatorios.</div>
                    </div>  
                 </fieldset>    
            </div>
        </div>
    </div>       
   <script type="text/javascript">      
       public $treeview=$('#using_json_2'),gridComp=$("#comp"),ciudad='',ciu_cod='';   
       function savePais(form){
            var data=$('#'+form).getData('save');
            $.post( "<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>",data, function( response ) {
                if(response['success']===true){
                    $.alert("Transaccion Realizada con &Eacute;xito!");                          
                    $treeview.jstree(true).refresh();
                    $('#Dialog'+form.replace('form','')).dialog('close');
                    gridComp.jqGrid().trigger("reloadGrid", [{ page: 1 }]); 
                }else{$.alert(response['message']);}
             },'json').fail(function(error) { $.alert();});
        }   
        function anulaCiudad(){  
            $.post( "<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>",{anula:ciu_cod}, function( response ) {
                if(response['success']===true){ 
                    $.alert("La Ciudad se ha Anulado con Exito!");
                    $treeview.jstree(true).refresh();
                    gridComp.jqGrid().trigger("reloadGrid", [{ page: 1 }]); 
                }else{$.alert(response['message']);}                                   
            },'json').fail(function(error) { $.alert("El Servidor ha fallado en responder!"); });
        }
   
    gridComp.jqGrid({
            url: '<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>',
            mtype: "GET", datatype: "local", regional : 'es',hidegrid:false,//ajaxRowOptions: { async: true },                             
            autowidth : true, shrinkToFit: true, height: 350,caption:'&nbsp;',responsive:true,
            cmTemplate: {sortable:false,title: false},
            colModel: [
                { label: 'Cód.Int.', name: 'id', key: true, width: 25,align:"center" },
                { label: 'Descripción', name: 'nombre', width: 150 }                
            ],                                     
            rowNum: 10000000, pager: "", gridview: true, rownumbers: true, viewrecords: true, altRows: true, altclass: "myAltRowClass",pgbuttons: false,pgtext: null
        });
    
    $treeview.jstree({'core' : {'data': { 'url': '<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>?planAjax=true' ,"dataType": "json" }}})
       .on('select_node.jstree', function (e, data) {
           var text=data.node.text,tipo=data.node.original.tipo,id=data.node.id.split("_")[1];
           gridComp.clearGrid();
           $('.btn-add').hide();
           $('.btn-'+tipo).show();
           $('#form'+tipo)[0].reset();
                switch(tipo) {
                     case 'P':
                         $('#P_Pas_Cod').val(id);$('#P_Pas_Nom').val(text);$('#P_Pas_Nac').val(data.node.original.Pas_Nac);
                         text='&nbsp;&nbsp;Regiones del Pais: <u><b>'+text+'</b></u>';
                         break;
                     case 'R':
                         $('#R_Reg_Cod').val(id);$('#R_Reg_Nom').val(text);$('#R_Pas_Nom').val(data.instance.get_node(data.node.parent).text);
                         text='&nbsp;&nbsp;Provincias de la Region: <u><b>'+text+'</b></u>, del Pais: <u><b>'+data.instance.get_node(data.node.parent).text+'</b></u>';
                         break;
                     case 'V':
                         $('#V_Pas_Nom').val(data.instance.get_node('P_'+data.node.original.Pas_Cod).text);
                         $('#V_Reg_Nom').val(data.instance.get_node(data.node.parent).text);
                         $('#V_Pro_Cod').val(id);$('#V_Pro_Nom').val(text);
                         text='&nbsp;&nbsp;Ciudades de la Provincia: <u><b>'+text+'</b></u>';
                         break;  
                    case 'C':
                         ciu_cod=id;ciudad=text;
                         $('#C_Pas_Nom').val(data.instance.get_node('P_'+data.node.original.Pas_Cod).text);
                         $('#C_Pro_Nom').val(data.instance.get_node(data.node.parent).text);
                         $('#C_Ciu_Cod').val(id);$('#C_Ciu_Des').val(text);
                         text='&nbsp;&nbsp;Ciudades de la Provincia: <u><b>'+data.instance.get_node(data.node.parent).text+'</b></u>';
                         break;       
                    default:

                 }
             if(tipo!=='C'){gridComp.jqGrid('setGridParam',{datatype:'json',postData: {gridAjax:true,parent:id,tipo:tipo}}).trigger("reloadGrid", [{ page: 1 }]);}
             else {gridComp.jqGrid('setGridParam',{datatype:'json',postData: {gridAjax:true,parent:data.node.parent.split("_")[1],tipo:'V'}}).trigger("reloadGrid", [{ page: 1 }]);}
             gridComp.jqGrid('setCaption',text);
      });
      $(document).ready(function() {            
            $.createDialog('#DialogP',240,550); 
            $.createDialog('#DialogR',240,550); 
            $.createDialog('#DialogV',275,550); 
            $.createDialog('#DialogC',275,550); 
      });
    /*.on('loaded.jstree', function() {
        $treeview.jstree('open_all');
    });*/
   </script>
</BODY>
</HTML>