<?php	 
/**
* @abstract Permite realizar la cancelacion de comprobantes por abonos
* @author Erik Niebla
* @version 1.0
* Fecha de creaci�n  2015-07-22
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/rhu_log_roles.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Rol($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_Rol;

$hoy = date("Y-m-d");
$mes = date("m");

if(isset($camposAjax)){
    $data=$_GET;       
    $total = $obBD_con1->getArrayConsulta(7,array('Map_Cod'=>$Map_Cod,'type'=>'T','var'=>'total_rol'), $obBD_conexion);   
    $campos = $obBD_con1->getArrayConsulta(7,array('Map_Cod'=>$Map_Cod,'type'=>array('I','E')), $obBD_conexion);    
    $provi = $obBD_con1->getArrayConsulta(7,array('Map_Cod'=>$Map_Cod,'type'=>'P'), $obBD_conexion);
    
    $responce['rows']=  array_merge(array_merge($campos,$total),$provi);
    $obBD_con1->echoJson($responce);    
}
if(isset($listCuenta)){
   if(!isset($Cam_Cod)) $responce['rows']=array(); 
   else
    $responce['rows'] = $obBD_con1->getArrayConsulta(19, $Cam_Cod.'*'.$Are_Cod.'*'.$Pla_Cod.'*'.$listTipo, $obBD_conexion);
   $obBD_con1->echoJson($responce); 
}
if(isset($cuenAjax)){ 
    $obBD_con1->getPageGridJson(18, $search.'*'.$Ses_Emp_Cod.'*'.$Pec_Cod.'*'.$op_opciones, $obBD_conexion, $page, $rows);   
}
if(isset($addCuenta)){ 
    $responce['tipo']=$addCuenta;
    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);              
    $obBD_con1->operacionobBD(20, $Cam_Cod.'*'.$Are_Cod.'*'.$Pld_Cod.'*'.$addCuenta, $obBD_conexion);	
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);   
    if($obBD_con1->Error==0){ $responce['success']=true;} else{$responce['success']=false;$responce['message']=$obBD_con1->MsgError;}
    $obBD_con1->echoJson($responce);    
}
if(isset($deleteCuenta)){ 
    $responce['tipo']=$deleteCuenta;
    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);              
    $obBD_con1->operacionobBD(21, $Cam_Cod.'*'.$Are_Cod.'*'.$Pld_Cod.'*'.$deleteCuenta, $obBD_conexion);	
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);   
    if($obBD_con1->Error==0){ $responce['success']=true;} else{$responce['success']=false;$responce['message']=$obBD_con1->MsgError;}
    $obBD_con1->echoJson($responce); 
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
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;   Registrar Relaci�n Rol Pagos - Plan De Cuentas <?Php if(isset($Year[0])) echo 'Periodo '.$Year[0];?></h3></div>
        
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <?php if(isset($Pec_Cod)&&$Pec_Cod!=''){ ?>               
                <div class="row">
                    <div class="col-sm-6">  
                        <fieldset class="exa-fieldset">                           
                           <legend class="Titulos2">Filtros</legend> <!-- Form Name -->
                           <form id="campoForm" class="form-horizontal normal" style="margin-bottom: 10px;" action="javascript:getRolPago();">
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
                                    <label class="col-xs-2 control-label label-xs required">Plantilla:</label>  
                                    <div class="col-xs-5"> 
                                        <select id="Map_Cod" name="Map_Cod" class="form-control input-xs" onchange="getRolPago()" required="">
                                            <option value="">Seleccione...</option>
                                            <?php $rs_maps = $obBD_con1->getArrayConsulta(10,$Ses_Emp_Cod, $obBD_conexion);                                            
                                                foreach ($rs_maps as $row){  
                                                     ?><option value="<?php echo $row['Map_Cod']; ?>"><?php echo $row['Map_Des']; ?></option><?php
                                                }
                                            ?>
                                        </select>
                                    </div>                                  
                                </div>   
                            </form>                              
                       </fieldset>
                       <div style="min-height: 300px;">
                            <table id="listCampos"></table>
                            <div id="listCamposPager"></div>
                        </div> 
                    </div>
                    <div class="col-sm-6">
                        <fieldset class="exa-fieldset">                           
                            <legend class="Titulos2">Datos campo:</legend> <!-- Form Name -->

                            <div id="dato" class="form-horizontal normal">
                                <!-- Text input-->
                                <div class="form-group">
                                   <label class="col-sm-3 control-label label-sm"  >Tipo:</label>  
                                   <div class="col-sm-4">                                    
                                       <input name="Cam_Type"  class="form-control input-xs datatitle"  type="text" readonly="">                                         

                                   </div>                                 
                                 </div>  
                                 <div class="form-group">
                                   <label class="col-sm-3 control-label label-sm" >Nombre:</label>  
                                   <div class="col-sm-4">    
                                       <input name="Cam_Des"  class="form-control input-xs datatitle"  type="text" readonly="">
                                   </div>  
                                   <label class="col-sm-1 control-label label-sm" >Var.:</label>  
                                   <div class="col-sm-3">    
                                       <input name="Cam_Var"  class="form-control input-xs datatitle"  type="text" readonly="">
                                   </div> 
                                 </div>                                   
                                <div class="form-group">
                                   <label class="col-sm-3 control-label label-sm" >Observaci�n:</label>  
                                   <div class="col-sm-9">                                    
                                       <input name="Cam_Obs"  class="form-control input-xs datatitle"  type="text" readonly="">                                         

                                   </div>                                 
                                 </div>  
                            </div>                                        
                        </fieldset>
                        <fieldset class="exa-fieldset">                           
                            <legend class="Titulos2">Parametrizaci�n Producto:</legend> <!-- Form Name -->
                            <form id="formCuenList" class="form-horizontal normal">
                                <input id="Cam_Cod" name="Cam_Cod" type="text" style="display: none" />
                                <input name="Pla_Cod" type="text" value="<?php echo $Pec[3]; ?>" style="display: none" />
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-sm required">Area:</label>  
                                    <div class="col-xs-5"> 
                                        <select id="Are_Cod" name="Are_Cod" class="form-control input-sm" onchange="if(this.value!=='') updateCuentas(); else $('#campoCuentas').clearGridData();" required="">
                                            <option value="">Seleccione...</option>
                                            <?php $rs_area = $obBD_con1->getArrayConsulta(11,$Ses_Emp_Cod, $obBD_conexion);                                            
                                                foreach ($rs_area as $row){  
                                                     ?><option value="<?php echo $row['Are_Cod']; ?>"><?php echo $row['Are_Des']; ?></option><?php
                                                }
                                            ?>
                                        </select>
                                    </div>                                  
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label label-sm" for="Par_Tip">Tipo Parametro:</label>  
                                    <div class="col-sm-9">                                    
                                        <select id="listTipo" name="listTipo" class="form-control input-sm readOnly" required="" onchange="updateCuentas()">
                                            <option value="">Seleccione...</option>
                                            <option value="G">General</option>
                                            <option value="D">Debe</option>
                                            <option value="H">Haber</option>
                                        </select>    

                                    </div>                                 
                                  </div>
                            </form>    
                            <div style="padding-bottom: 5px; padding-top: 5px">
                                    <table id="campoCuentas"></table>                                            
                                </div>
                                <button disabled="" id="btnAddCuenProd" onclick=";$('#cuenDialog').dialog('open');" title="Buscar Cuentas" type="button" class="btn btn-success btn-xs"><i class="glyphicon glyphicon-list"></i><span> Seleccionar Cuenta</span></button>
                        </fieldset>
                        <div class="alert alert-info"><i class="glyphicon glyphicon-info-sign"></i> <u><strong>NOTA:</strong></u>&nbsp; Solo se permite un <i>Cuenta Contable</i> por cada <i>Campo del Rol</i></div>             
                    </div>
                </div> 
               <script type="text/javascript">
                   var type={I:'INGRESO',E:'EGRESO',T:'TOTAL',P:'PROVISIONES'}, param={G:'GENERAL',D:'DEBE',H:'HABER'};
                   function getRolPago(){                        
                       if($('#Map_Cod').val()===""){ $('#listCampos').clearGrid(); return; }
                       $('#listCampos').Search('#campoForm','camposAjax');
                   }
                   function selectDato(id){  
                        var data=$("#listCampos").jqGrid('getRowData', id);
                        //data['Cam_Type']=type[data['Cam_Tip']];
                        $('#listTipo').find('option[value=G]').show();
                        if(data['Cam_Tip']!=='P') $('#listTipo').val('G').attr('disabled','disabled');
                        else $('#listTipo').val('').removeAttr('disabled').find('option[value=G]').hide();                        
                        $('#dato').setData(data); 
                        $('#Cam_Cod').val(id);
                        if($('#Are_Cod').val()===''||($('#listTipo').val()===''&&data['Cam_Tip']!=='P'))$("#campoCuentas").clearGrid(); else updateCuentas();
                    }                            
                    function updateCuentas(){    
                        $('#btnAddCuenProd').attr('disabled','disabled');
                        if($('#Cam_Cod').val()!==''&&$('#Are_Cod').val()!==''){
                            var data=$('#formCuenList').getData('listCuenta');
                            //console.log(data);
                            $("#campoCuentas").jqGrid('setGridParam',{datatype:'json',postData: data}).trigger("reloadGrid", [{ page: 1 }]);                                    
                        }
                    }
                    function addCuenta(a2){ 
                        $('#cuenDialog').dialog('close');
                        if($('#Are_Cod').val()===''){$.alert('Seleccione un <u>Area</u>!');return;}
                        if($('#Cam_Cod').val()===''){$.alert('Seleccione un <u>Campo</u>!');return;}
                        if($('#listTipo').val()===''){$.alert('Seleccione un <u>Parametro</u>!');return;}
                        var data={Cam_Cod:$('#Cam_Cod').val(),Pld_Cod:a2,Are_Cod:$('#Are_Cod').val(),addCuenta:$('#listTipo').val()};                         
                        $.saveDataJson( "<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>",data, function( response ) {                                                  
                                $("#campoCuentas").jqGrid('setGridParam',{datatype:'json'}).trigger("reloadGrid", [{ page: 1 }]);                             
                         });
                    }
                    function deleteCuenta(a2,a3){
                        var data={Cam_Cod:$('#Cam_Cod').val(),Are_Cod:$('#Are_Cod').val(),Pld_Cod:a2,deleteCuenta:a3};
                        $.createDialogConfirm('�Est� seguro que desea eliminar esta relaci�n?',data,deleteCta);
                    }
                    function deleteCta(data){   
                        $.saveDataJson( "<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>",data, function( response ) { 
                            $("#campoCuentas").jqGrid('setGridParam',{datatype:'json'}).trigger("reloadGrid", [{ page: 1 }]);                             
                        });
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
        public $listProds=$("#listCampos"), abr={I:'INGRESO',E:'EGRESO',T:'TOTALES',P:'PROVISIONES'};
        $listProds.createGrid({                                   
            height:315, caption:'Listado de Campos Rol', sortname:'Cam_Ord',
            colModel: [
                { label: 'Cód.Int.', name: 'Cam_Cod', key: true, width: 25,align:"center", hidden:false },  
                { label: 'Cod.Int.', name: 'Map_Cod', width: 25,align:"center", hidden:true },  
                { label: 'Tipo', name: 'Cam_Tip', width: 40, hidden:true }, 
                { label: 'Tipo', name: 'Cam_Type', width: 30,align:"center", formatter:function(cv, opts, rObj){ return type[rObj.Cam_Tip];} }, 
                { label: 'Orden', name: 'Cam_Ord', width: 25,align:"center", hidden:false },  
                { label: 'Nombre', name: 'Cam_Des', width: 100 },  
                { label: 'Var.', name: 'Cam_Var', width: 60,align:"center" }, 
                { label: 'Obs.', name: 'Cam_Obs', width: 75 },                
                    { label:'&nbsp;', name: 'act1', width: 30, align: 'center',viewable: false, formatter:function (cellvalue, options, rowObject) { return $.getGridButton(selectDato,rowObject.Cam_Cod); } },
                { label:'<center><i class="ui-icon ui-icon-circle-check"></i></center>', name: 'act2', width: 20, align: 'center',viewable: false, formatter: 'checkbox',formatoptions: { disabled: false },resizable:false, hidden:true}                                
            ],
            grouping:true, 
            groupingView : { 
               groupColumnShow:[false],
               groupField : ['Cam_Tip'],
               //groupOrder : ['asc'], 
               groupDataSorted : true 
            },loadComplete:function(){ $(this).find('tr[id*=ghead_0_]').each(function(){ $(this).find('span')[0].nextSibling.textContent=abr[$(this).text()]; }); }
        },true,"#listCamposPager").jqGrid("resizeGrid"); 
        $("#campoCuentas").createGrid({
            postData:{listTipo:'I',listCuenta:true}, height: 150,caption:'<b>&raquo;</b> Cuentas Contables',
            colModel: [ 
                { label: 'Tipo', name: 'Tipo', width: 30,align:"center", formatter:function(cellvalue, options, rowObject){ return param[rowObject.Rpl_Tip];} }, 
                { label: 'Cam. Cod.', name: 'Cam_Cod', width: 30,align:"center", hidden:false },
                { label: 'Pld. Cod.', name: 'Pld_Cod', key: true, width: 30,align:"center", hidden:false }, 
                { label: 'Pld. Tip.', name: 'Rpl_Tip', width: 30,align:"center", hidden:true }, 

                { label: 'Cuenta', name: 'Pld_Cdc', width: 50 ,align:"center"}, 
                { label: 'Descripci&oacute;n', name: 'Pld_Des', width: 90,align:"left" },
                    { label:'&nbsp;', name: 'act1', width: 30, align: 'center',viewable: false,
                        formatter:function (cellvalue, options, rowObject) { return  '<span class="btn btn-danger btn-xs" title="Eliminar" type="button" onclick="deleteCuenta(\''+rowObject.Pld_Cod+'\',\''+rowObject.Rpl_Tip+'\');"><i class="glyphicon glyphicon-trash"></i></span>'; }
                    }
            ],
            loadComplete:function (){var ids = $("#campoCuentas").jqGrid('getDataIDs'); if(ids.length===0) $('#btnAddCuenProd').removeAttr('disabled'); else $('#btnAddCuenProd').attr('disabled','disabled'); }
        },false);      
     });
</script> 
            <?php }            else{ /* isset(Pec_Cod) */?>
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
                                      $rs_periodos = $obBD_con1->getArrayConsulta(12,$Ses_Emp_Cod,$obBD_conexion);
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
            <?php } /* isset(Pec_Cod) */ ?>
        </div>
    </div>
  
  
</BODY>
</HTML>