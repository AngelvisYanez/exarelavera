<?php	
/**
* @abstract Permite realizar anulacion comprobantes por numero
* @author Erik Niebla
* @version 1.0
* Fecha de creaci�n  2015-07-22
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_retencion.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
/**
* Creacion del Objeto de conexion
*/
$obBD_conexion = new Class_Log_Conexion_Ret($Ses_Dat_Dis);
/**
* Creacion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Ret;

$hoy = date("Y-m-d");
$mes = date("m");

$row_rs_autorizaci = $obBD_con1->getArrayConsulta(568 /*561*/, $Ses_Emp_Cod.'*'.$Ses_Prs_Cod.'*'.$hoy, $obBD_conexion);

if(isset($save)){
    $data=filter_input_array(INPUT_POST);$num=array();$cont=0;
	$row_prvcod = $obBD_con1->getRowConsulta(566, $Ses_Emp_Cod, $obBD_conexion);
    if(empty($row_prvcod['Prv_Cod']))
        { $responce['success']=false; $responce['message']='Parametrice Proveedores (Varios Egresos)';}
    
    $row_rs_retenciones= $obBD_con1->getArrayConsulta(563,$data , $obBD_conexion);    
        
    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);    
    $obBD_con1->operacionobBD(565,$row_prvcod['Prv_Cod'], $obBD_conexion);
    $data['Cop_Cod']=$obBD_con1->insercionid($obBD_conexion->conexion);
    for($i=$Ini;$i<=$Fin;$i++){
        $ban=true;$data['Ret_Num']=$i;
        foreach ($row_rs_retenciones as $ret)
            if($ret['Ret_Num']*1==$i){
                 $ban=false; break;
            }
        if($ban) {$obBD_con1->operacionobBD(564,$data, $obBD_conexion);$cont++;}
        else array_push ($num, $i);
    }
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);
   
    $responce['message']='Se han anulado '.$cont.' Comprobantes de Retencion con Exito!';
    if($obBD_con1->Error==0) { $responce['success']=true; $responce['nums']=$num; }
    else {$responce['success']=false; $responce['message']=empty($responce['message'])?"No se logro realizar la transacción.":$responce['message']; $responce['error']=$obBD_con1->MsgError;}    
    utf8_encode_deep($responce);
    echo json_encode($responce);exit();
}

?>
<!DOCTYPE html>
<HTML>
	<HEAD>		
                <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
                <TITLE><?Php echo "Retención Anular Secuencia [EXA]"; ?></TITLE>
                <meta charset= "UTF-8"> 
                <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php")?>              
                <style>                     
                     
                </style>
	</HEAD>
<BODY>
 
    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Anulacion de Comprobantes de Retención</h3></div>
        
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <?php if(count($row_rs_autorizaci)!=1){?>
            <link rel="stylesheet" href="../../framework/jquery/bootstrap/info.tabs.css" />
            <div class="vcenter center" style="height:300px;margin-bottom: 25px;">
                <?php echo error_alerta("Usted no tiene Autorizaciones activas para los documentos de retención", 2, true);?>
            </div>    
            <?php }else{ ?>            
                <div class="row">
                    <div class="col-xs-3">                       
                    
                    </div>
                    <div class="col-xs-6">  
                        <form id="formRet" class="form-horizontal normal"  action="javascript:validacion()"  >
                        <fieldset class="exa-fieldset">                           
                           <legend class="Titulos2">Anulaciones</legend> <!-- Form Name -->
                                <!--<input type="text" value="<?php echo $hoy; ?>" style="display: none;" name='Ret_Fec' />-->
                                <input type="text" value="<?php echo $Ses_Emp_Cod; ?>" style="display: none;" name='Emp_Cod' />
                                <input type="text" value="<?php echo $row_rs_autorizaci[0]['Vnd_Cod']; ?>" style="display: none;" name='Vnd_Cod' />
                                <input type="text" value="<?php echo $row_rs_autorizaci[0]['Aut_Cod']; ?>" style="display: none;" name='Aut_Cod' />
                                
                                                           
                                
                                <!-- Text input-->
                                <div class="form-group">
                                  <label class="col-sm-3 control-label label-sm" for="Tic_Cod">Tipo de Doc.:</label>  
                                  <div class="col-sm-5">
                                      <select name="Tic_Cod" id="Tic_Cod"  class="form-control input-sm" disabled style="background-color: #eee !important;">                                          
                                          <?Php 
                                            $rs_docs = $obBD_con1->getArrayConsulta(562, '', $obBD_conexion);
                                            foreach($rs_docs as $row_rs_doc){ ?>
                                            <option value="<?php echo $row_rs_doc['Tic_Cod']?>" <?php if($row_rs_doc['Tic_Sri']*1==7) echo 'selected'; ?>><?php echo $row_rs_doc['Tic_Des']?></option>
                                            <?php } ?>
                                        </select>
                                  </div>								  	
                                </div>
								<div class="form-group">
								<label class="col-sm-3 control-label label-sm required" for="Tic_Cod">Proveedor:</label> 
								  <div class="col-sm-5"> 
								  <?php $row_prvEgreso = $obBD_con1->getRowConsulta(566, $Ses_Emp_Cod, $obBD_conexion);?>
								  <input id="Prov" name="Prov" value="<?php if(isset($row_prvEgreso['Prv_Cod'])){echo $row_prvEgreso['Prs_Ape'];}?>" required="" readonly class="form-control input-sm dateType" placeholder="Falta parametrizar Varios Egreso"  required> 				
								  </div>
                                </div>
                                <!-- Text input-->
                                <div class="form-group">
                                  <label class="col-sm-3 control-label label-sm required" for="Tic_Cod">Fecha Anulación:</label>  
                                  <div class="col-sm-4">
                                    <div class="input-group input-group-sm">                                          
                                        <input id="Ret_Fec" name="Ret_Fec" type="text" class="form-control" value="" required=""/> 
                                        <span class="input-group-addon" title="Fecha que tendrá la retención anulada."><i class="glyphicon glyphicon-info-sign blue"></i></span>
                                    </div>
                                      
                                  </div>
                                </div>
                                
                                <!-- Text input-->
                                <div class="form-group">
                                  <label class="col-sm-3 control-label label-sm">Secuencia:</label>    
                                  <label class="col-sm-2 control-label label-sm"><br><b class="">Serie:</b></label>  
                                  <div class="col-sm-2">     
                                      <label style="margin-bottom:0">Estableci.</label>
                                      <input id="Suc_Sri" name="Suc_Sri" value="<?php echo $row_rs_autorizaci[0]['Suc_Sri']; ?>" class="form-control input-sm"  type="text" readonly >
                                  </div>
                                  <div class="col-sm-2">     
                                      <label style="margin-bottom:0">Pun.Emis.</label>
                                          <input id="Pun_Sri" name="Pun_Sri" value="<?php echo $row_rs_autorizaci[0]['Pun_Sri']; ?>" class="form-control input-sm" type="text" readonly >
                                  </div>
                                </div>                                
                                
                                <!-- Text input-->
                                <div class="form-group">
                                  <label class="col-sm-3 control-label label-sm" ></label>  
                                  <label class="col-sm-2 control-label label-sm required" for="Ini">Desde:</label>  
                                  <div class="col-sm-4">                                    
                                          <input id="Ini" name="Ini" value="<?php echo $row_rs_autorizaci[0]['Aut_Ini']; ?>" class="form-control input-sm dateType" placeholder="000000000" required>                                          
                                  </div>                                 
                                </div>
                                <!-- Text input-->
                                <div class="form-group">
                                  <label class="col-sm-3 control-label label-sm"></label>  
                                  <label class="col-sm-2 control-label label-sm required" for="Fin">Hasta:</label>  
                                  <div class="col-sm-4">                                    
                                          <input id="Fin" name="Fin" value="<?php echo $row_rs_autorizaci[0]['Aut_Fin']; ?>"class="form-control input-sm dateType" placeholder="000000000"  required>                                          
                                  </div>                                 
                                </div>
                                
                                <!-- Text input-->
                                <div class="form-group">
                                  <label class="col-sm-3 control-label label-sm" for="Aut_Sri">Autorización:</label>  
                                  <div class="col-sm-5">                                    
                                      <input id="Aut_Sri" name="Aut_Sri" value="<?php echo $row_rs_autorizaci[0]['Aut_Sri']; ?>" class="form-control input-sm dateType" readonly >                                          
                                                                      
                                  </div>                                 
                                </div>

                                <!-- Textarea -->
                                <div class="form-group">
                                  <label class="col-sm-3 control-label" for="des_cuenta">Observación:</label>
                                  <div class="col-sm-9">                     
                                    <textarea class="form-control" name="Ret_Con"></textarea>
                                  </div>
                                </div>
                       
                                <div class="alert alert-xs alert-warning" role="alert">
                                    <strong>Atención:</strong> La autorización Inicia en <code><?php echo $row_rs_autorizaci[0]['Aut_Ini']; ?></code> y termina en <code><?php echo $row_rs_autorizaci[0]['Aut_Fin']; ?></code>.
                                </div>
                       </fieldset>
                            
                         <div class="form-group">  
                                <div class="col-sm-12 center">
                                    <button type="submit"  class="btn btn-primary"><span class="glyphicon glyphicon-floppy-disk"></span> Guardar</button>
                                    <button type="reset"   class="btn btn-danger"><span class="glyphicon glyphicon-remove"></span> Cancelar</button>                                
                                </div>
                            </div>
                        </form>     
                    </div>
                    <div class="col-xs-3">                       
                    
                    </div>
                    <div class="col-sm-12">
                           
                            <div class="form-group Titulos2">
                                <div class="col-sm-12"><hr/><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco (  <span class="required"></span> ) son campos obligatorios.</div>
                            </div>    
                    </div>
                </div>    
              
            
            <?php } ?>
        </div>
    </div>
   
    <script type="text/javascript" src="../../framework/jquery/jquery.plugins/MaskedInput/jquery.maskedinput.1.4.1.min.js"></script> 
   <script type="text/javascript">
       function validacion(){          
           if($('#Fin').val()*1>=$('#Ini').val()*1){
                if($('#Fin').val()*1>0&&$('#Ini').val()*1>0){
                    $.createDialogConfirm(null,null,saveForm);
                }else{$.alert('Los Rangos no pueden ser cero');}
           }else{$.alert('El rango Final debe ser Menor o Igual al Rango Inicial');}
       }
       function saveForm(){
                $('#Tic_Cod').removeAttr('disabled');
                $.post("<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>",$('#formRet').getData('save'), function(response){	
                    if(response['success']===true){
                        $('#formRet')[0].reset();
                        $.alert(response['message']);
                    }else{$.alert("No se logro guardar el Registro!");}
                },'json')
                .fail(function(error) {$.alert("El Servidor ha fallado en responder!");})
                .always(function(error) {$('#Tic_Cod').attr('disabled','disabled');});
           
       }     
       $( document ).ready(function() {            
           $('#Ret_Fec').createDatePickers({checkAvailability:true,hideMsg:false,clean:true}).dateLimits('<?php echo $row_rs_autorizaci[0]['Aut_Fci']; ?>','<?php echo $row_rs_autorizaci[0]['Aut_Cad']; ?>');           
       });
   </script>
   <script type="text/javascript" src="../../framework//jquery/jquery.plugins/MaskedInput//jquery.maskedinput.1.4.1.min.js"></script> 
</BODY>
</HTML>