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

if(isset($getDefaults)){ 
    $obBD_con1->getRolDefaults($_GET, $obBD_conexion);      
}
if(isset($rolesAjax)){ 
    $data=$_GET;       
    $responce['rows'] = $obBD_con1->getArrayConsulta(16, $data, $obBD_conexion);
    foreach ($responce['rows'] as &$v) {
        $com=$obBD_con1->getRowConsulta(29, $v['Rol_Cod'].'*'.'RL', $obBD_conexion);
        $v['Com_Cod']=$com['Com_Cod'];
        $com_provi=$obBD_con1->getRowConsulta(29, $v['Rol_Cod'].'*'.'AS', $obBD_conexion);
        $v['Com_Cod_Provi']=$com_provi['Com_Cod'];
        if($v['Usu_Cod']!=null){
            $usua=$obBD_con1->getRowConsulta(48, $v['Usu_Cod'], $obBD_conexion);
            $v['Usuario']=$usua['Usuario'];
        }
        $sel=$obBD_con1->select()->from("det_an_rol",array('total'=>'COUNT(*)'))->join('antici_rol','antici_rol.Ant_Cod=det_an_rol.Ant_Cod',array())->where("Rol_Cod=? AND Ant_Tip='B' AND Ant_Est='A'",$v['Rol_Cod']);                
        $abonos=$obBD_con1->getRowConsulta(null, $sel, $obBD_conexion,false); 
        if(isset($abonos['total']) && $abonos['total']*1 > 0 ) $v['Pagos']='S';
        //$obBD_con1->echoLog($abonos);
    } unset($v);
    $responce['records'] = count($responce['rows']);
    $responce['success']=true;
    $obBD_con1->echoJson($responce);
}
if(isset($getRolDetail)){          
    $responce = $obBD_con1->getGridRol($Map_Cod,$obBD_conexion);
    $rol_pago=$obBD_con1->getRowConsulta(16,array('Rol_Cod'=>$Rol_Cod), $obBD_conexion);  
    $responce['Rol_Cod']=$Rol_Cod;
    $responce['personal'] = $obBD_con1->getListRoles(array('Rol_Cod'=>$Rol_Cod), $obBD_conexion,false);
    $responce['grid']['caption']=$rol_pago['Rol_Con'];
    array_push($responce['grid']['colModel'],array('label'=>'&nbsp;','name'=>'edit','width'=>60,'align'=>'center','viewable'=>false,'title'=>false,'hidden'=>true));
    $responce['success']=true;
    $responce['edit']=false; //unset($responce['rol']);
    $obBD_con1->echoJson($responce);
}
if(isset($deleteRol)){ 
    $configs = $obBD_con1->getRowConsulta(23, $Ses_Emp_Cod,$obBD_conexion);
    $Rol_Cod=$rol['Rol_Cod']; $Com_Cod=$rol['Com_Cod']; $Com_Cod_Provi=$rol['Com_Cod_Provi']; $edit=false;
    //var_dump($configs); exit();
    try{
        $obBD_ins1 =  new Class_Log_Datos_Rol;
        $obBD_conexionIns = new Class_Log_Conexion_Rol($Ses_Dat_Dis);
        //$obBD_ins1->debug(true);
        $obBD_ins1->inicio_transaccion($obBD_conexionIns->conexion);
            $obBD_ins1->operacionobBD('rol_pagos.update',array('Rol_Est'=>'I','where'=>array('Rol_Cod'=>$Rol_Cod)),$obBD_conexionIns); //anula el rol de pagos            
            if($configs['Cof_Con']=='S'){ 
                if(!empty($Com_Cod))
                    $obBD_ins1->operacionobBD('comprobantes.update',array('Com_Est'=>'I','where'=>array('Com_Cod'=>$Com_Cod)),$obBD_conexionIns); //anula el comprobante 
                if(!empty($Com_Cod_Provi))
                    $obBD_ins1->operacionobBD('comprobantes.update',array('Com_Est'=>'I','where'=>array('Com_Cod'=>$Com_Cod_Provi)),$obBD_conexionIns); //anula el comprobante  provisiones
                
            }
        $obBD_ins1->fin_transaccion_nomsn($obBD_conexionIns);    
    }catch(Exception $e){ $obBD_ins1->rollBack_nomsn($obBD_conexionIns); $responce=array('success'=>false, 'message'=>$e->getMessage()); $obBD_con1->echoJson($responce); }
    if($obBD_ins1->Error==0){ $responce=array('success'=>true, 'Rol_Cod'=>$Rol_Cod,'Com_Cod'=>$Com_Cod,'Com_Cod_Provi'=>$Com_Cod_Provi,'edit'=>false); } else{ $responce=array('success'=>false,'message'=>"No se ha logrado realizar la Transaccion",'error'=>$obBD_ins1->MsgError);}  
    $obBD_con1->echoJson($responce);
}
?>
<!DOCTYPE html>
<HTML>
    <HEAD>		
        <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
        <TITLE><?Php echo "Rol Pago Anular [EXA]"; ?></TITLE>
        <meta charset= "UTF-8">
        <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php"); ?>  
        <script type="text/javascript" src="../../framework/plugins/moment.min.js"></script>
        <script type="text/ecmascript" src="../VALIDACIONES/rhu_val_roles.js?x=500"></script>
        <style></style>
    </HEAD>
<BODY>
 
    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Modificar de Roles</h3></div>
        
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div id="main-search">
              <div class="row">  
                  <form id="formSearchRol" action="javascript:searchRoles();">
                    <div class="col-xs-3">  
                        <fieldset class="exa-fieldset ">                           
                            <legend class="Titulos2">Plantilla Rol</legend>
                            <div class="form-horizontal normal">                                 
                                <div class="form-group">
                                    <label class="col-sm-3 control-label label-xs">Area:</label>  
                                    <div class="col-sm-9"> 
                                        <select id="Are_Cod" name="Are_Cod" class="form-control input-xs" >
                                            <option value="">TODAS</option>
                                            <?php $rs_area = $obBD_con1->getArrayConsulta(11,$Ses_Emp_Cod, $obBD_conexion);                                            
                                                foreach ($rs_area as $row){  
                                                     ?><option value="<?php echo $row['Are_Cod']; ?>"><?php echo $row['Are_Des']; ?></option><?php
                                                }
                                            ?>
                                        </select>
                                    </div>                                  
                                </div> 
                                <div class="form-group">
                                    <label class="col-sm-3 control-label label-xs">Plantilla:</label>  
                                    <div class="col-sm-9"> 
                                        <select id="Map_Cod" name="Map_Cod" class="form-control input-xs">
                                            <option value="">TODAS</option>
                                            <?php $rs_maps = $obBD_con1->getArrayConsulta(10,$Ses_Emp_Cod, $obBD_conexion);                                            
                                                foreach ($rs_maps as $row){  
                                                     ?><option value="<?php echo $row['Map_Cod']; ?>"><?php echo $row['Map_Des']; ?></option><?php
                                                }
                                            ?>
                                        </select>
                                    </div>                                  
                                </div>
                            </div>
                        </fieldset>
                    </div>   
                    <div class="col-xs-7">  
                        <fieldset class="exa-fieldset">                           
                            <legend class="Titulos2">Datos Generales</legend>
                            <div class="form-horizontal normal">
                                <div class="form-group">
                                    <label class="col-sm-2 control-label label-xs">Periodo:</label>  
                                    <div class="col-sm-3"> 
                                        <select id="Pec_Cod" name="Pec_Cod" class="form-control input-xs" onchange="" required="">                                            
                                            <?php $rs_perio = $obBD_con1->getArrayConsulta(12,$Ses_Emp_Cod, $obBD_conexion);                                            
                                                foreach ($rs_perio as $row){  
                                                     ?><option value="<?php echo $row['Pec_Cod']; ?>" data-year="<?php echo $row['Periodo']; ?>">Periodo <?php echo $row['Periodo']; ?></option><?php
                                                }
                                            ?>
                                            <option value="ALL" selected >TODOS</option>
                                            <option value="RANGE" >POR RANGO</option>
                                        </select>
                                    </div>                                     
                                </div> 
                                <div class="form-group date-ranges">
                                    <label class="col-sm-2 control-label label-xs ">Desde:</label>
                                    <div class="col-sm-3">     
                                        <input name="ini" type="text" id="ini" class="form-control input-xs" disabled="" />
                                    </div>
                                    <label class="col-sm-2 control-label label-sm ">Hasta:</label>
                                    <div class="col-sm-3">                                    
                                        <input name="fin" type="text" id="fin" class="form-control input-xs" disabled="" />
                                    </div>                                   
                                </div>
                            </div>
                        </fieldset>
                    </div>   
                    
                    <div class="col-xs-2 center vcenter" style="height: 70px;"><button type="submit" class="btn btn-success"><i class="glyphicon glyphicon-search"></i> Buscar</button></div>                    
                    
                </form>
                <div class="col-xs-12" style="min-height: 250px;"><table id="comp"></table><div id="listPager"></div></div>    
              </div>
            </div>  
            
            <div id="rol-sdetail" style="display: none;">
              <div class="row"> 
                  <form id="formRol" action="javascript:" class="detalle">                      
                  <div class="col-xs-3">
                    <fieldset class="exa-fieldset">                           
                        <legend class="Titulos2">Plantilla Rol</legend>
                        <div class="form-horizontal normal">
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Area:</label>  
                                <div class="col-xs-9"> 
                                    <span name="Are_Des" class="form-control input-xs"></span>
                                </div>                                  
                            </div> 
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Plantilla:</label>  
                                <div class="col-xs-9"> 
                                    <span name="Map_Des" class="form-control input-xs"></span>
                                </div>   
                            </div>
                        </div>
                    </fieldset> 
                  </div>
                  <div class="col-xs-3">  
                        <fieldset class="exa-fieldset">                           
                            <legend class="Titulos2">Datos Generales</legend>
                            <div class="form-horizontal normal">
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs">Periodo:</label>  
                                    <div class="col-xs-9"> 
                                         <span name="Anio" class="form-control input-xs"></span>
                                    </div>                                  
                                </div> 
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs">Tipo:</label>  
                                    <div class="col-xs-9"> 
                                        <select id="Rol_Tip" name="Rol_Tip" class="form-control input-xs readOnly datatrigger" onchange="updateDias()" disabled="">                                            
                                            <option value="M" data-dias="30" data-period="12" >Mensual</option>
                                            <option value="Q" data-dias="15" data-period="24">Quincenal</option>
                                            <option value="BS" data-dias="14">Bi Semanal</option>
                                            <option value="S" data-dias="7">Semanal</option>
                                        </select>
                                    </div>                                  
                                </div> 
                            </div>
                        </fieldset>
                    </div>   
                  <div class="col-xs-3">  
                      <input name="Pec_Cod" type="text" style="display: none">
                      <input name="Are_Cod" type="text" style="display: none">
                      <input name="Rol_Cod" type="text" style="display: none">
                      <input name="Com_Cod" type="text" style="display: none">
                      <input name="Com_Cod_Provi" type="text" style="display: none">
                      <input name="Map_Cod" type="text" style="display: none">
                  </div>    
                  <div class="col-xs-3">
                    <fieldset class="exa-fieldset">                           
                        <legend class="Titulos2">Rol</legend>
                        <div class="form-horizontal normal">
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Numero:</label>  
                                <div class="col-xs-3"> 
                                    <span name="Rol_Num" class="form-control input-xs databind" style="text-align: right;"></span>
                                </div>                                  
                            </div> 
                            <div class="form-group">
                                <div class="col-xs-12"> 
                                    <div class="input-group input-group-xs">
                                        <span class="input-group-addon bold alert-info">Desde:</span>
                                        <input name="Rol_Fei" type="text" class="form-control span" style="text-align: right;" readonly="" tabindex="-1">
                                        <span class="input-group-addon bold alert-info">Hasta:</span>
                                        <input name="Rol_Fef" type="text" class="form-control span" style="text-align: right;" readonly="" tabindex="-1">            
                                    </div>
                                </div>    
                            </div>
                        </div>
                    </fieldset> 
                  </div>
                  <div class="col-xs-12">
                        <div class="form-horizontal normal">
                        <div class="form-group">
                            <label class="col-xs-2 control-label label-xs">Concepto:</label>  
                            <div class="col-xs-10"> 
                                <input type="text" name="Rol_Con" class="form-control input-xs" />
                            </div>                                  
                        </div>
                        </div>
                    </div>    
                  </form>
                  <div class="col-xs-12" id="gridContainer" style="padding-bottom: 8px; min-height: 300px;"><table id="rol"></table><div id="rolPager"></div></div>    
                  <div class="col-xs-12">
                      <button class="btn btn-inverse" onclick="$('#rol-sdetail').moveComp('#main-search').updateGridsSizes();" ><i class="glyphicon glyphicon-arrow-left"></i> Atr&aacute;s</button>
                      <button type="button" onclick="anulaRol()" class="btn btn-danger btn-save"><span class="glyphicon glyphicon-trash"></span> Anular</button>
                  </div>    
              </div> 
            </div>    
        </div>
    </div>
    <!--INICIO DEL DIALOGO IMPRIMIR --> 
    <div id="successDialog"  title="Mensaje del Sistema">  
        <center><h4>El Comprobante se ha registrado con Exito!</h4></center>
        <center>             
            <button type="button" id="impRoles" onclick="$.imprimirUrl($(this).data('url'))" style="display: inline;" title="Imprimir Rol de Pagos" class="btn btn-primary"><i class="glyphicon glyphicon-print"></i> Rol Pagos</button>
            <button type="button" id="impCompr" onclick="$.imprimirUrl($(this).data('url'))" style="display: inline;" title="Imprimir Comprobante de Rol" class="btn btn-primary"><i class="glyphicon glyphicon-print"></i> Comprob. Rol </button>
            <button type="button" id="impComprProv" onclick="$.imprimirUrl($(this).data('url'))" style="display: inline;" title="Imprimir Comprobante de Provisiones" class="btn btn-primary"><i class="glyphicon glyphicon-print"></i> Comprob. Provision </button>
        </center>        
    </div>
    <script type="text/javascript">        
      $(document).ready(function() {  
            createSearchGrid([ 
                { label:'&nbsp;', name:'act1', width:30, align:'center',viewable:false, title:false, formatter:'gridButton', formatoptions:{action:detallarRoles, conditional: function (o) { return o.Pagos !== 'S' && o.Rol_Est === 'A'; }, caseFalse: function (o) { if(o.Rol_Est!=="A") return $.createIcon('remove red',false,'title="Inactivo/Anulado!"'); return $.createIcon('lock orange',false,'title="Contiene Pagos!"'); } } } 
            ]);              
      });  
   </script>
   <div id="proviDetaDialog" title="Provisiones"></div>
   <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
</BODY>
</HTML>