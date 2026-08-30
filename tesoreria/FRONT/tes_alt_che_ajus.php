<?php	
/**
* @abstract Permite realizar la cancelacion de comprobantes por abonos
* @author Erik Niebla
* @version 1.0
* Fecha de creaci�n  2015-07-22
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require('../LOGICA/tes_log_cheque_2.0.php');
/**
* Creacion del Objeto de conexion
*/
$obBD_conexion = new Class_Log_Conexion_Che($Ses_Dat_Dis);
/**
* Creacion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Che;


$hoy = date("Y-m-d");
$mes = date("m");

//if(isset($saveBene)){      
//    $responce['id']='';
//    $obBD_con1->inicio_transaccion($obBD_conexion);
//    $obBD_con1->grabarv_registros(sentencias_che(363,$obBD_con1->parametros($apel.'*'.$nomb)),$obBD_conexion);
//    $ultimo = $obBD_con1->insercionid($obBD_conexion);
//    $obBD_con1->grabarv_registros(sentencias_che(364,$obBD_con1->parametros($ultimo.'*'.$Ses_Emp_Cod)),$obBD_conexion);
//    $responce['id'] = $obBD_con1->insercionid ($obBD_conexion);
//    $obBD_con1->fin_transaccion_nomsn($obBD_conexion);       
//    if($obBD_con1->Error==0) $responce['success']=true; else $responce['success']=false;$responce['message']=$obBD_con1->MsgError;
//    $obBD_con1->echoJson($responce);
//}
if(isset($beneAjax)){ 
    $obBD_con1->getPageGridJson(351, $search.'*'.$Ses_Emp_Cod.'*'.$op_opciones, $obBD_conexion, $page, $rows);
}
if(isset($valida)){ 
    $contar = $obBD_con1->getArrayConsulta(376, $Ban_Cod.'*'.$Che_Num, $obBD_conexion);	      
    $ban=true; foreach ($contar as $v)  if($v['conteo']*1>0) {$ban=false; break; }
    echo json_encode(array('success'=>$ban,'message'=>'El <u>Cheque</u> ya existe!','cheque'=>true));exit();
}
if(isset($save)){        
    $data=$_POST;
    $codigos=explode('*',$bancos);
    $data['Ban_Cod']=$codigos[0];$data['Pld_Cod']=$codigos[1];
    $contar = $obBD_con1->getArrayConsulta(376, $data['Ban_Cod'].'*'.$Che_Num, $obBD_conexion);	      
    foreach ($contar as $v)  if($v['conteo']*1>0) { echo json_encode(array('success'=>false,'message'=>'El <u>Cheque</u> ya existe!','cheque'=>true));exit(); }    
    $obBD_con1->inicio_transaccion($obBD_conexion);    
        $obBD_con1->operacionobBD(371,$data, $obBD_conexion);  
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    if($obBD_con1->Error==0){ $responce['success']=true; } else{ $responce['error']=$obBD_con1->MsgError; $responce['success']=false; $responce['message']="No se ha logrado realizar la Transaccion"; }  
    $obBD_con1->echoJson($responce);
}
?>
<!DOCTYPE html>
<HTML>
	<HEAD>		
                <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
                <TITLE><?Php echo "Cheques Saldo Inicial [EXA]"; ?></TITLE>
                <meta charset= "UTF-8">
                <link rel="stylesheet" href="../../framework/jquery/bootstrap/popover/jquery.flyout.min.css" />
                <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php")?>                    
                <script type="text/javascript" src="../../framework/jquery/bootstrap/popover/jquery.flyout.js"></script>
                <style>
                </style>
	</HEAD>
<BODY>
 
    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Registro de Cheques sin Comprobantes</h3></div>
        
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div class="">
                <div class="row">
                    <div class="col-sm-1"> </div>
                    <div class="col-sm-10 col-xs-12">  
                        
                        <fieldset class="exa-fieldset">                           
                           <legend class="Titulos2">Formulario de Registro</legend> <!-- Form Name -->
                           <form id="formCheque" class="form-horizontal" role="form" action="javascript:saveCons();">
                                <div class="form-group">                                                
                                    <label class="col-sm-3 control-label label-xs required">Banco:</label> 
                                    <div class="col-sm-5">
                                        <select name="bancos" id="bancos" class="form-control input-xs" required >
                                            <option value="">Seleccione Banco...</option>
                                            <?Php 
                                           $rs_periodos = $obBD_con1->getArrayConsulta(370,$Ses_Emp_Cod, $obBD_conexion);                               
                                           if (count($rs_periodos) > 0){
                                                   foreach($rs_periodos as $row){?>
                                                           <option value="<?Php echo $row['Ban_Cod'].'*'.$row['Pld_Cod']; ?>"><?Php echo $row['Pld_Des']; ?></option>	
                                                   <?php }//while($row_rs_periodos = $obBD_con1->fetch_assoc($rs_periodos));
                                           }//Fin del if ($total_rs_periodo > 0)                                
                                           ?>
                                        </select>
                                    </div>
                                </div> 
                                <div class="form-group">
                                    <label class="col-sm-3 control-label label-xs required">Fecha Cheque:</label>  
                                    <div class="col-sm-2">
                                        <input id="chefec" name="Che_Fec" type="text" style="text-align: center" size="10" maxlength="10"  class="form-control input-xs" required />
                                    </div>
                                </div> 
                                <div class="form-group">
                                    <label class="col-sm-3 control-label label-xs required">Fecha Cobro:</label>  
                                    <div class="col-sm-2">
                                        <input id="checob" name="Che_Cob" type="text" style="text-align: center" size="10" maxlength="10" class="form-control input-xs" disabled="" required />
                                    </div>
                                    <div class="col-sm-3"><label><input type="checkbox" id="Che_Est" name="Che_Est" value="C" offval="A" />  Cobrado</label> </div>
                                    
                                </div> 

                                <div class="form-group">
                                    <label class="col-sm-3 control-label label-xs required">No. Cheque:</label>  
                                    <div class="col-sm-2">                                                        
                                        <input class="form-control input-xs"  style="text-align: center" name="Che_Num" id="NumChe" type="text" size="10" onkeypress="return  validar_numeric(event)"  required />
                                    </div>    
                                    <div class="col-sm-5 msgDiv">
                                        <img class="imgMsg" /><label class="lblMsg">ver</label>
                                    </div>
                                </div> 
                                <div class="form-group">
                                    <label class="col-sm-3 control-label label-xs required">Beneficiario:</label><input id="Bene_Id" name="Prv_Cod" type="text" style="display: none" value="" />  
                                    <div class="col-sm-9">
                                        <div class="input-group">
                                            <input id="apellido" name="apellido" class="form-control input-xs" type="text" size="32" placeholder="Apellidos" style="text-transform:uppercase" readOnly />
                                            <span class="input-group-btn" style="width:0px;"></span>
                                            <input  id="nombre" name="nombre" class="form-control input-xs" type="text" size="32" placeholder="Nombres" style="text-transform:uppercase" readOnly />
                                            <span class="input-group-btn">
                                                <button id="btnBene" onclick="$('#beneDialog').dialog('open');return false;" title="Seleccionar Beneficiario" class="btn btn-success btn-xs pull-right"><i class="glyphicon glyphicon-check"></i></button>
                                            </span>
                                        </div>
                                    </div>                                      
                                </div> 
                                <div class="form-group">
                                    <label class="col-sm-3 control-label label-xs required">Valor:</label>  
                                    <div class="col-sm-3">
                                        <div class="input-group input-group-xs">
                                            <span class="input-group-addon"> $ </span>
                                            <input class="form-control input-xs" name="Che_Val" id="Com_Val_Egre"  type="text" size="10" maxlength="12" style="text-align:right" onkeypress="return  validar_decimal(event)" required />
                                        </div>    
                                    </div>                                                
                                </div> 
                                <div class="form-group">
                                    <label class="col-sm-3 control-label label-xs">Observación:</label>  
                                    <div class="col-sm-5">
                                        <textarea class="form-control" id="Che_Obs" name="Che_Obs"></textarea>
                                    </div>
                                </div> 
                                  <div class="form-group">
                                    <label class="col-sm-3 control-label">Acción:</label>
                                    <div class="col-sm-8">
                                        <button type="submit"  class="btn btn-primary"><span class="glyphicon glyphicon-floppy-disk"></span> Guardar</button>
                                        <button type="button" onclick="resetFormC();"  class="btn btn-danger"><span class="glyphicon glyphicon-remove"></span> Cancelar</button>
                                    </div>
                                </div>                                            
                                        
                                         
                           </form>
                           <div class="form-group Titulos2">
                                <div class="col-sm-12"><hr/><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco (  <span class="required"></span> ) son campos obligatorios.</div>
                            </div> 
                        </fieldset>
                        
                            <div>
                                <table id="comp"></table><div id="listPager"></div>
                            </div>
                         
                    </div>                   
                </div>    
              
            </div>   
        </div>
    </div>
    
    
    
<!--INICIO DEL DIALOGO BUSCAR BEnfICIARIO--> 
    <div id="beneDialog"  title="B&uacute;squeda de Beneficiarios">  
       <form class="form-horizontal normal"> 
        <fieldset>
		<legend>Filtros</legend>
                <div class="form-group">
                    <label class="col-sm-2 control-label label-xs">Filtrar Por:</label>  
                    <div class="col-sm-8 radioset" >
                          <input id="radb1" name="op_opciones" type="radio" value="d" checked="" onclick="setfocus(this.form.search)" alt="" /><label for="radb1">&nbsp;&nbsp;Apellido&nbsp;&nbsp;</label>
                          <input id="radb2" name="op_opciones" type="radio" value="c" onclick="setfocus(this.form.search)" alt="" /><label for="radb2">&nbsp;&nbsp;Cédula/R.U.C.&nbsp;&nbsp;</label>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-2 control-label">B&uacute;squeda:</label>  
                    <div class="col-sm-7" >                 
                      <div class="input-group">                        
                        <input name="search" onkeydown="if (event.keyCode === 13) this.form.submit()" type="text" size="50" maxlength="50" placeholder="Ingrese proveedor a buscar..." autofocus class="form-control input-sm" />
                        <span class="input-group-btn">
                          <button type="button" onclick="this.form.submit()" class="btn btn-sm btn-success" title="Buscar Beneficiario" ><span class="glyphicon glyphicon-search"> </span> Buscar</button>
                        </span>
                      </div><!-- /input-group -->                          
                    </div><input type="text" style="display:none"/>
<!--                    <a onclick="$('#beneApe').val('');$('#beneNom').val('');$('#addBenef').dialog('open');" title="Agregar Beneficiario" class="btn btn-sm btn-success"><i class="glyphicon glyphicon-plus"></i></a>-->
                </div>
        </fieldset>  
       </form>  
    </div>
<!-- FIN DEL DIALOGO  BUSCAR BEnfICIARIO-->

<!-- CREA BEdefICIARIO DIALOG -->
<!--    <div id="addBenef"  title="Crear Beneficiario">  
        <form action="javascript:saveBene();" class="form-horizontal normal"> 
        <fieldset>
		<legend><label class="Titulos2">Datos Beneficiario</label></legend>
            <div class="form-group">
                <label class="col-sm-2 control-label">Beneficiario:</label>  
                <div class="col-sm-5" ><input id="beneApe" name="apellido" class="form-control input-sm" type="text" size="32" placeholder="Apellidos" style="text-transform:uppercase" required autofocus/></div>    
                <div class="col-sm-5" ><input id="beneNom" name="nombre" class="form-control input-sm" type="text" size="32" placeholder="Nombres" style="text-transform:uppercase" />   </div>    
            </div>
            <div class="form-group center">
                <button type="submit" class="btn btn-success btn-sm" title="Guardar Proveedor" >
                    <i class="glyphicon glyphicon-floppy-disk"></i> <span>Guardar</span>
                </button><span>&nbsp;</span>
                <button type="button" onclick="$('#addBenef').dialog('close');" class="btn btn-inverse btn-sm" title="Cancelar" >
                    <i class="glyphicon glyphicon-remove"></i> <span>Cancelar</span>
                </button>            
            </div>
        </fieldset>
         </form> 
    </div> -->

    <script type="text/javascript">
    $(document).ready(function() {
        $.createDatePickers("input[name='Che_Fec']");
        $.createDatePickers("input[name='Che_Cob']");       
        $('#NumChe').clearMsg();
        $('#btnBene').createFlyout('Seleccione un beneficiario',{placement:'top_right'});
        $('#Che_Est').on('change',function (){ if($(this).prop('checked')) $('#checob').removeAttr('disabled'); else $('#checob').attr('disabled','disabled'); });
        $('#NumChe').on('change',validaCheque);
        $('#bancos').on('change',validaCheque);
    });
   function saveCons(){
        if($('#Bene_Id').val()===''){ $('#btnBene').flyout('show'); return;}        
        $.createDialogConfirm('¿esta seguro que desea guardar el <u>Cheque</u> fuera de los periodos establecidos en el sistema?',$('#formCheque').getData('save'),function (data){
           $.saveDataJson("<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>",data, resetFormC); 
        });        
    }  
    function resetFormC(){ $('#formCheque')[0].reset();$('#Che_Est').trigger('change');$('#NumChe').clearMsg(); }
    function validaCheque(){
        if($('#NumChe').val()===''||$('#bancos').val()===''){ $('#NumChe').clearMsg(); return;}
        var ban=($('#bancos').val().split('*'))[0];
        $.get( "<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>",{valida:true,Che_Num:$('#NumChe').val(),Ban_Cod:ban}, function( response ) {
            if(!response['success']){
                $('#NumChe').alertMsg('El numero de Cheque ya existe!');
            }else $('#NumChe').alertMsg();
        },'json').fail(function(error) { $('#NumChe').clearMsg(); });
    }
    // DIALOG BUSCAR BENEFICIARIO   
//    $.createDialog('#addBenef',150,550); 
    $.createSearchDialog('beneDialog',[
           { label: 'C&oacute;d.Int.', name: 'Prv_Cod', key: true, width: 20,align:"center",hidden:true,viewable: true },                                
           { label: 'C&eacute;dula/R.U.C.', name: 'Prs_Ced', width: 50 },                      
           { label: 'Beneficiario', name: 'proveedor', width: 190, cellattr: function (rowId, tv, rawObject, cm, rdata) { return 'style="white-space: normal;"'; }},                   
           { label: 'Apellidos', name: 'Prs_Ape',hidden:true},
           { label: 'Nombres', name: 'Prs_Nom',hidden:true},                                        
               { label:'<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 18, align: 'center',viewable: false,
                   formatter:function (cellvalue, options, rowObject) { 
                      var clic='$( "#apellido" ).val("'+rowObject.Prs_Ape+'" );$( "#nombre" ).val( "'+rowObject.Prs_Nom+'" );$( "#Bene_Id" ).val( "'+rowObject.Prv_Cod+'" );$( "#beneDialog" ).dialog("close");';                               
                      return  '<span class="btn btn-success btn-xs" title="Seleccionar" onclick=\''+clic+'\'><i class="glyphicon glyphicon-arrow-right"></span>'; 
                   }
               }
       ]);  
//   function saveBene() {
//       $.post( "<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>",{saveBene:true,apel:$('#beneApe').val(),nomb:$('#beneNom').val()}, function( response ) {
//          if(response['success']===true){
//              $('#apellido').val($('#beneApe').val());$('#nombre').val($('#beneNom').val());$('#Bene_Id').val(response['id']);
//              $('#addBenef').dialog('close');$('#beneDialog').dialog('close');
//          }else{$.alert(response['message']);}                                   
//       },'json').fail(function(error) { $.alert("El Servidor ha fallado en responder!"); });
//   }
   </script>
</BODY>
</HTML>