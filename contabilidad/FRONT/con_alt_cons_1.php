<?php	
/**
* @abstract Permite realizar la cancelacion de comprobantes por abonos
* @author Erik Niebla
* @version 1.0
* Fecha de creaciï¿½n  2015-07-22
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require('../LOGICA/con_log_prod_plan.php');
/**
* Creacion del Objeto de conexion
*/
$obBD_conexion = new Class_Log_Conexion_Cons($Ses_Dat_Dis);
/**
* Creacion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Cons;


$hoy = date("Y-m-d");
$mes = date("m");

if(isset($consAjax)){ 
    $data=filter_input_array(INPUT_GET); 
    $data['Emp_Cod']=$Ses_Emp_Cod;
    $contar = $obBD_con1->getPageGridJson(4, $data, $obBD_conexion);
}
if(isset($update)){         
        $obBD_con1->inicio_transaccion($obBD_conexion);
            $obBD_con1->operacionobBD(5,$Con_Cod.'*'.$Con_Est, $obBD_conexion);            
        $obBD_con1->fin_transaccion_nomsn($obBD_conexion);       
        if($obBD_con1->Error==0)$responce['success']=true;
	  else $responce['success']=false; $responce['message']=$obBD_con1->MsgError;
      $obBD_con1->echoJson($responce);
}
if(isset($save)){        
    $data=$_POST;
    $obBD_con1->inicio_transaccion($obBD_conexion);
    if($Con_Cod==''||$oper=='add')
        $obBD_con1->operacionobBD(1,$Ses_Emp_Cod.'*'.$Con_Des, $obBD_conexion);        
    else
        $obBD_con1->operacionobBD(2,$Con_Cod.'*'.$Con_Des, $obBD_conexion);
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    if($obBD_con1->Error==0){ $responce['success']=true;  } else{$responce['success']=false;$responce['message']="No se ha logrado realizar la Transaccion";}  
    $obBD_con1->echoJson($responce);
}
?>
<!DOCTYPE html>
<HTML>
	<HEAD>		
                <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
                <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php")?>
                <link rel="stylesheet" href="../../framework/jquery/jquery.jstree/themes/default/style.min.css" />
                <script src="../../framework/jquery/jquery.jstree/jstree.min.js"></script>
                <link rel="stylesheet" href="../../framework/jquery/summernote/summernote.css">
                <script type="text/javascript" src="../../framework/jquery/summernote/summernote.js"></script>
                <style>
                     
                     
                </style>
	</HEAD>
<BODY>
 
    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Gestión de Centros de Consumo</h3></div>
        
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div class="">
                <div class="row">
                    <div class="col-sm-12">  
                        
                        <fieldset class="exa-fieldset">                           
                           <legend class="Titulos2">Búsqueda de Centros de Consumo</legend> <!-- Form Name -->
                           <form id="searchForm" class="form-inline" role="form" action="javascript:gridComp.Search('#searchForm','consAjax');">
                               <div class="form-group">
                                <label for="search">Buscar:</label>
                                    <div class="input-group input-group-sm">                                                
                                    <input type="text" value="" style="display: none" />
                                    <input id="docu" name="search" maxlength="13"  type="text" class="form-control clearable submit" placeholder="Ingrese palabra a buscar ..." autofocus style="width: 350px" />                                    
                                    <span class="input-group-btn">
                                        <button class="btn btn-success" type="submit"><span class="fa fa-search" title="Buscar Proveedor"></span></button>
                                    </span>
                                  </div><!-- /input-group -->
                              </div>
                              <div class="form-group">&nbsp;</div>
                              <button type="button" onclick="$('#formCons')[0].reset();$('#DialogCons').dialog('open');" title="Agregar Centro de Consumo" class="btn btn-success btn-sm"><i class="fa fa-plus"></i></button>
                                         
                           </form>
                        </fieldset>
                        
                            <div>
                                <table id="comp"></table><div id="listPager"></div>
                            </div>
                         
                    </div>
                </div>    
              
            </div>   
        </div>
    </div>

    <div id="DialogCons" title="Formulario Centro de Consumo"> 
        <div class="row">
            <div class="form-horizontal normal col-md-12" >
                <fieldset>
                    <legend><label class="Titulos2">Datos del Centro de Consumo</label></legend>
                    <form id="formCons" class="form-horizontal normal"  action="javascript:$.createDialogConfirm(null,'formPais',saveCons)"  >
                    <div class="form-group">
                       <label class="col-sm-4 control-label label-sm required">Centro de Consumo:</label>  
                       <div class="col-sm-8" >
                           <input type="text"  id="Con_Cod" name="Con_Cod" value="" style="display: none" />
                           <input type="text" class="form-control input-sm" name="Con_Des" id="Con_Des"  value="" required />
                       </div>
                     </div>                     
                     <div class="form-group">
                        <label class="col-sm-4 control-label">Acción:</label>
                        <div class="col-sm-8">
                            <button type="submit"  class="btn btn-primary"><span class="glyphicon glyphicon-floppy-disk"></span> Guardar</button>
                            <button type="button" onclick="$('#DialogCons').dialog('close');"  class="btn btn-danger"><span class="glyphicon glyphicon-remove"></span> Cancelar</button>
                        </div>
                    </div>
                    </form>    
                    <div class="form-group Titulos2">
                        <div class="col-md-12"><hr/><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco (  <span class="required"></span> ) son campos obligatorios.</div>
                    </div>  
                 </fieldset>    
            </div>
        </div>
    </div>    
    
   <script type="text/javascript">
       var gridComp=$("#comp");   
       function saveCons(){
            var data=$('#formCons').getData('save');
            $.post( "<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>",data, function( response ) {
                if(response['success']===true){
                    $.alert("Transaccion Realizada con &Eacute;xito!");
                    gridComp.jqGrid().trigger("reloadGrid", [{ page: 1 }]); 
                    $('#DialogCons').dialog('close');                     
                }else{$.alert(response['message']);}
             },'json').fail(function(error) { $.alert();});
        }   
        function setCons(data){
            $('#formCons')[0].reset();
            $('#Con_Cod').val(data['Con_Cod']);
            $('#Con_Des').val(data['Con_Des']);
            $('#DialogCons').dialog('open');
        }
        function borraConsumo(id){ updateConsumo(id,'I'); }
        function activaConsumo(id){ updateConsumo(id,'A'); }
        function updateConsumo(id,est){
            $.post( "<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>",{Con_Cod:id,Con_Est:est,update:true}, function( response ) {
                if(response['success']===true){
                    $.alert("Transaccion Realizada con &Eacute;xito!");gridComp.jqGrid().trigger("reloadGrid", [{ page: 1 }]);
                }else{$.alert(response['message']);}
            },'json').fail(function(error) { $.alert("El Servidor ha fallado en responder!"); });
        }
    gridComp.jqGrid({
            url: '<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>',
            mtype: "GET", datatype: "json", regional : 'es',hidegrid:false, ajaxRowOptions:{async:true}, 
            postData: $("#searchForm").getData("consAjax"),
            autowidth : true, shrinkToFit: true, height: 250,caption:'&nbsp;',responsive:true,
            cmTemplate: {sortable:false,title: false},
            colModel: [
                { label: 'Cód.Int.', name: 'Con_Cod', key: true, width: 25,align:"center" },
                { label: 'Descripción', name: 'Con_Des', width: 250,editable:true},
                { label: 'Estado', name: 'Con_Est', width: 50 ,align:"center"} ,
                    { label:'&nbsp;', name: 'act1', width: 35, align: 'center',viewable: false,title: false,
                        formatter:function (cellvalue, options, rowObject) { var action='';
                            if(rowObject['Con_Est']==='Activo')
                                action='<span class="btn btn-danger btn-xs" title="Anular" type="button" onclick="$.createDialogConfirm(\'Está seguro que desea anular este Centro de Consumo?\','+rowObject.Con_Cod+',borraConsumo)"><i class="fa fa-ban"></i></span>';
                            else
                                action='<span class="btn btn-info btn-xs" title="Activar" type="button" onclick="$.createDialogConfirm(\'Está seguro que desea activar este Centro de Consumo?\','+rowObject.Con_Cod+',activaConsumo)"><i class="fa fa-check"></i></span>';
                            return  '<span class="btn btn-success btn-xs" title="Editar" type="button" onclick="setCons(gridComp.jqGrid(\'getRowData\',\''+rowObject.Con_Cod+'\'));"><i class="fa fa-pencil"></i></span><span>&nbsp;&nbsp;</span>'+
                                     action; 
                        }
                    }    
            ],                                     
            rowNum: 10000000, pager: "listPager", gridview: true, rownumbers: true, viewrecords: true, altRows: true, altclass: "myAltRowClass",
            loadComplete: function(data){
                //console.log(data.rows[0]);
                var total = data.records;
                for(var i=0;i<total;i++){
                    if(data.rows[i]['Con_Est'] !=='Activo')
                        $("#"+data.rows[i].Con_Cod).css("background", "#FADDDD");
                }
            }
        });
        gridComp.navGrid('#listPager',{ edit: false, add: false, del: false, search: false, refresh: true, view: true, position: "left", cloneToTop: false });
        gridComp.jqGrid('inlineNav',"#listPager",{
            editParams: {extraparam:{save:true}},
            addParams:{position:"last",addRowParams:{   
                    extraparam:{save:true,Con_Cod:''},
                    aftersavefunc:function (){ $(this).trigger("reloadGrid"); }
                }}
            }
        );
        gridComp.jqGrid('bindKeys');
      $(document).ready(function() {           
            $.createDialog('#DialogCons',210,530);             
      });
    /*.on('loaded.jstree', function() {
        $treeview.jstree('open_all');
    });*/
   </script>
</BODY>
</HTML>