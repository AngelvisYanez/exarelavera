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

if(isset($rolesAjax)){ 
    $data=$_GET;       
    $responce['rows'] = $obBD_con1->getArrayConsulta(16, $data, $obBD_conexion);
    $responce['records'] = count($responce['rows']);
    $responce['success']=true;
    $obBD_con1->echoJson($responce);    
}
if(isset($getRolDetail)){  
    $rol_pago=$obBD_con1->getRowConsulta(16,array('Rol_Cod'=>$Rol_Cod), $obBD_conexion);
    $responce = array_merge($obBD_con1->getGridRol('',$obBD_conexion,false),$rol_pago);
    $responce['grid']['button_provi']=false;
    $responce['rol_config']=$obBD_con1->getRowConsulta(8,array('Map_Cod'=>$rol_pago['Map_Cod']), $obBD_conexion);
    
    $responce['personal'] = $obBD_con1->getListRoles(array('Rol_Cod'=>$Rol_Cod), $obBD_conexion,false,true);
    $responce['grid']['caption']=$rol_pago['Rol_Con'];        
    $responce['success']=true;
    $responce['edit']=false; //unset($responce['rol']);
    
    $tota=$obBD_con1->getRowConsulta(46,$responce['Map_Cod'].'*'.'total_rol', $obBD_conexion);
    $responce['Cam_Cod']=$tota['Cam_Cod'];
    foreach($responce['grid']['colModel'] AS &$col){
        $col['editable']=false;
        switch($col['name']){
            case 'total_rol':                
                break;
            case 'Tic_Des':
                $col['classes']='bgNoColor';               
                break;
            case 'anticipos_ant':
                $col['hidden']=true;         
                break;
            default :
                if(isset($col['Cam_Cod'])) $col['hidden']=true;
        }        
    } unset($col);
    $responce['grid']['colModel']=array_merge($responce['grid']['colModel'],array(
        array('label'=>'Total Rol','name'=>'total_rol','width'=>75,'align'=>'right','formatter'=>'numeric'),
        array('label'=>'Abonos Anteriores','name'=>'abonos_rol_p','width'=>75,'align'=>'right','formatter'=>'numeric'),
        array('label'=>'Saldo Anterior','name'=>'saldo_ant','width'=>75,'align'=>'right','formatter'=>'numeric'),
        array('label'=>'<i class="glyphicon glyphicon-info-sign"></i>','labelLong'=>'Anticipos/Descuentos/Prestamos','name'=>'anticipos_ant','width'=>25,'formatter'=>'gridButtonInfos','align'=>'center','classes'=>'bgNoColor'),
        array('label'=>'Pago(s)','name'=>'anticipo_val','width'=>75,'align'=>'right','formatter'=>'anticipos','formatoptions'=>array('defaultValue'=>''),'summaryRound'=>2,'summaryRoundType'=>"round"/*,'editable'=>false, 'editoptions'=>array('dataInit'=> "styleInput")*/),
        array('label'=>'Observacion','name'=>'anticipo_obs','width'=>125,'formatter'=>'textboxExa','classes'=>'bgNoColor'),
        array('label'=>'Anticipo','name'=>'anticipo_val_saved','width'=>75,'align'=>'right','formatter'=>'numeric','hidden'=>true),
        array('label'=>'Saldo','name'=>'saldo_rol','width'=>75,'formatter'=>'saldos','align'=>'right','classes'=>'columnHighlight1','summaryRound'=>2),        
        array('label'=>'<i class="glyphicon glyphicon-print"></i>','labelLong'=>'Imprimir','name'=>'imprimir','width'=>50,'align'=>'center','formatter'=>'gridButton','formatoptions'=>array('action'=>"printRecibo",'icon'=>'print','conditional'=>'issetRecibo'),'classes'=>'columnHighlight3','hidden'=>true),
    ));
    foreach($responce['personal'] AS &$per){
        $per['abonos_rol_p']=$obBD_con1->getArrayConsulta(45, $per['Con_Cod'].'*'.$Rol_Cod, $obBD_conexion);
    } unset($per);
    $obBD_con1->echoJson($responce); 
}
if(isset($validaCheNum)){    
    $conteo = $obBD_con1->getRowConsulta(40, $Ban_Cod.'*'.$Che_Num, $obBD_conexion);
    $contar = $obBD_con1->getRowConsulta(39, $Ban_Cod, $obBD_conexion);
    $contar['success']=$conteo['conteo']==0;  
    $contar['message']="El numero $Che_Num ya se encuentra registrado!";
    if($contar['Che_Num']*1<$max*1){
        $conteo = $obBD_con1->getRowConsulta(40, $Ban_Cod.'*'.$max, $obBD_conexion);
        $contar['Che_Num']=$conteo['conteo']==0?$max:'';
    }
    $obBD_con1->echoJson($contar);
}

if(isset($setPagoEmpleado)){
    $responce['rows'] = $obBD_con1->getRowConsulta(56, $Con_Cod, $obBD_conexion);
    if($responce['rows'] != null ){
        $responce['success']=true;
    }
    else{
        $responce['success']=false;
    }

    $obBD_con1->echoJson($responce);  
}


if(isset($saveAnticipos)){ 
    $configs = $obBD_con1->getRowConsulta(23, $Ses_Emp_Cod,$obBD_conexion);
    $pec = $obBD_con1->getPerioCont($Ses_Emp_Cod,$Ant_Fec,$obBD_conexion);
    
    $Com_Cod=NULL; $return=array();
    try{        
        if(empty($contratos)||count($contratos)==0) throw new Exception('Debe existis al menos un abono!');
        
        $obBD_ins1 =  new Class_Log_Datos_Rol;   
//        $obBD_ins1->debug(true);
//        $obBD_con1->debug(true);
        $obBD_conexionIns = new Class_Log_Conexion_Rol($Ses_Dat_Dis);
        $obBD_ins1->inicio_transaccion($obBD_conexionIns);
            if($configs['Cof_Con']=='S'){ 
                if(empty($pec)||empty($pec['Pec_Cod'])) throw new Exception('No se encontro el periodo contable!');
                /* PARA EL COMPROBANTE CONTABLE */
                $Com_Fec=$Ant_Fec; $meseCom = explode('-', $Com_Fec); $campo='Prv_Cod'; // campos para el asiento
                $Tia_Asi = $obBD_con1->getRowConsulta(26, "E*RL", $obBD_conexion);   
                if(!isset($Tia_Asi['Tia_Cod'])||empty($Tia_Asi['Tia_Cod'])) throw new Exception('Revisar el tipo de asiento: <u>Roles de Pago</u>!');
                $Com_Num= $obBD_con1->getComNumAuto($Ses_Emp_Cod, $Tia_Asi['Tia_Cod'], $Com_Fec, $obBD_conexion)*1; // Secuencia de comprobante por mes y por tipo                
                $Prv_Cod=$obBD_con1->getProveeClie($Ses_Emp_Cod, $campo, $obBD_conexion);
            }           
            foreach($contratos as $j=>&$c){                
                $obBD_ins1->operacionobBD(32,array_merge($c,array('Ant_Fec'=>$Ant_Fec,'Ant_Tip'=>'B')),$obBD_conexionIns); // inserta los valores de cada anticipo
                $c['Ant_Cod']=$obBD_ins1->insercionid($obBD_conexionIns); 
                array_push($return, array('Con_Cod'=>$c['Con_Cod'],'anticipo_val_saved'=>$c['Ant_Val'],'imprimir'=>array('Ant_Cod'=>$c['Ant_Cod'])));
                               
                if($configs['Cof_Con']=='S'){       
                    /* Cabecera del Comprobante */
                    $obBD_ins1->operacionobBD(24, $pec['Pec_Cod'].'*'.$Prv_Cod.'*'.$Com_Num.'*'.$Com_Fec.'*'.trim($c['Ant_Obs']).'*'.$Tia_Asi['Tia_Cod'].'*'.$c['Ant_Val'].'*'.'ABONOS A ROL'.$Ant_Obs.'*'.$campo, $obBD_conexionIns);
                    $Com_Cod = $obBD_ins1->insercionid ($obBD_conexionIns);                    
                    $obBD_ins1->operacionobBD(33, $Com_Cod.'*'.$c['Ant_Cod'], $obBD_conexionIns); // relacion anticipos comprobante
                    /* Detalle Comprobante */
                    $cuenta = $obBD_con1->getRowConsulta(22, $Cam_Cod.'*'.$Are_Cod.'*'.$pec['Pec_Cod'], $obBD_conexion); 
                    if(!isset($cuenta['Pld_Cod'])||empty($cuenta['Pld_Cod'])) throw new Exception('Revisar la parametrizacion contable del campo: <u>Total Rol</u>!');
                    $obBD_ins1->operacionobBD(27, $Com_Cod.'*'.'D'.'*'.$c['Ant_Val'].'*'.$cuenta['Pld_Des'].'*'.'Abonos a Rol'.'*'.$cuenta['Pld_Cod'], $obBD_conexionIns,true);  // inserta asiento          
                    $c['cheque']=array();
                    foreach($c['Ant_Det'] as &$p){
                        $obBD_ins1->operacionobBD(27, $Com_Cod.'*'.'H'.'*'.$p['Pag_Val'].'*'.$p['Pld_Des'].'*'.$p['Pag_Des'].'*'.($p['Pag_Cod']*1==1?$p['Pag_Pld']:$p['Ban_Cod_Data']['Ban_Pld']), $obBD_conexionIns);  // inserta asiento          
                        $p['Asi_Cod'] = $obBD_ins1->insercionid ($obBD_conexionIns);
                        if($p['Pag_Cod']*1==3){
                            $obBD_ins1->operacionobBD(41, $Prv_Cod.'*'.$p['Ban_Cod'].'*'.$p['Asi_Cod'].'*'.$p['Che_Num'].'* *'.$p['Pag_Val'].'*'.$c['Ant_Obs'].'*'.$Ant_Fec.'*1*'.$c['Personal'], $obBD_conexionIns);  // inserta cheque          
                            array_push($c['cheque'],array('Banco'=>$p['Ban_Cod_Data']['Ban_Cod_Txt'],'Cheque'=>$p['Che_Num'],'link'=>"?codigo2=1&asi=$p[Asi_Cod]&ban=$p[Ban_Cod]&pro=$Prv_Cod"));
                        }
                    } unset($p);
                    $return[$j]['imprimir']=array_merge($return[$j]['imprimir'],array('Com_Cod'=>$Com_Cod,'Com_Num'=>$Com_Num,'cheque'=>$c['cheque'],'Codigo'=>$Tia_Asi['Tia_Abr'].'-'.$meseCom[1].'-'.$Com_Num));
                    $Com_Num++; //para que no se repitan los numeros
                }
                foreach($c['Ant_Det'] as $i=>$p){
                   $obBD_ins1->operacionobBD(38,array('Ant_Int'=>$i++,'Ant_Cod'=>$c['Ant_Cod'],'Ant_Val'=>$p['Pag_Val'],'Pag_Cod'=>$p['Pag_Cod'],'Asi_Cod'=>$p['Asi_Cod'],'Rol_Cod'=>$Rol_Cod),$obBD_conexionIns);
                } 
            } unset($c);
            
        $obBD_ins1->fin_transaccion_nomsn($obBD_conexionIns);    
    }catch(Exception $e){ $obBD_ins1->rollBack_nomsn($obBD_conexionIns); $responce=array('success'=>false, 'message'=>$e->getMessage()); $obBD_con1->echoJson($responce);  }
    if($obBD_ins1->Error==0){ $responce=array('success'=>true, 'saved'=> $return ); } else{ $responce=array('success'=>false,'message'=>"No se ha logrado realizar la Transaccion",'error'=>$obBD_ins1->MsgError);}  
    $reporte=$obBD_con1->reportesExa("/con_alt_compr__._.php", $Ses_Emp_Cod, $obBD_conexion);
    $responce['Com_Link']="".(!empty($reportes[1])?$reportes[1]:baseUrl("../../contabilidad/FRONT/con_pri_compr_2.1.php"))."?codigo=";
    $responce['Ant_Link']=baseUrl("../../rrhh/FRONT/rhu_pri_anticipo.php")."?Ant_Cod=";
    $obBD_con1->echoJson($responce);    
}
$configs = $obBD_con1->getRowConsulta(23, $Ses_Emp_Cod,$obBD_conexion);
if($configs['Cof_Con']=='S'){  $Pec_Cod = $obBD_con1->getRowConsulta(36,$Ses_Emp_Cod.'*'.$hoy,$obBD_conexion); }
?>
<!DOCTYPE html>
<HTML>
    <HEAD>		
        <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
        <TITLE><?Php echo "Rrhh Abono Registrar [EXA]"; ?></TITLE>
        <meta charset="UTF-8">
        <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php"); ?> 
        <script type="text/ecmascript" src="../VALIDACIONES/rhu_val_roles.js?x=500"></script>
        <style></style>
    </HEAD>
<BODY>
 
    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Gestión de Roles</h3></div>
        
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div id="main-search">
              <div class="row">  
                  <form id="formSearchRol" action="javascript:searchRoles();">
                    <div class="col-xs-3">  
                        <fieldset class="exa-fieldset ">                           
                            <legend class="Titulos2">Plantilla Rol</legend>
                            <div class="form-horizontal normal">                                 
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs">Area:</label>  
                                    <div class="col-xs-9"> 
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
                                    <label class="col-xs-3 control-label label-xs">Plantilla:</label>  
                                    <div class="col-xs-9"> 
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
                                    <label class="col-xs-2 control-label label-xs">Periodo:</label>  
                                    <div class="col-xs-3"> 
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
                                    <label class="col-xs-2 control-label label-xs ">Desde:</label>
                                    <div class="col-xs-3">     
                                        <input name="ini" type="text" id="ini" class="form-control input-xs" disabled="" />
                                    </div>
                                    <label class="col-xs-2 control-label label-sm ">Hasta:</label>
                                    <div class="col-xs-3">                                    
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
                  <div class="col-xs-3 detalle">
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
                  <div class="col-xs-3 detalle">  
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
                                            <option value="S" data-dias="7">Semanal</option>
                                        </select>
                                    </div>                                  
                                </div> 
                            </div>
                        </fieldset>
                    </div>   
                  <div class="col-xs-3 detalle">  
                  </div>    
                  <div class="col-xs-3 detalle">
                    <fieldset class="exa-fieldset">                           
                        <legend class="Titulos2">Rol</legend>
                        <div class="form-horizontal normal">
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Numero:</label>  
                                <div class="col-xs-3"> 
                                    <span name="Rol_Num" class="form-control input-xs" style="text-align: right;"></span>
                                </div>                                  
                            </div> 
                            <div class="form-group">
                                <div class="col-xs-12"> 
                                    <div class="input-group input-group-xs">
                                        <span class="input-group-addon bold alert-info">Desde:</span>
                                        <span name="Rol_Fei" class="form-control" ></span>
                                        <span class="input-group-addon bold alert-info">Hasta:</span>
                                        <span name="Rol_Fef" class="form-control"> </span>                                           
                                    </div>
                                </div>    
                            </div>

                            <div id="pagarTodo" class="form-group" style="display: none;">
                                <div class="col-xs-4"> 
                                    <div class="input-group input-group-xs">
                                        <button class="btn-sm btn-success" onclick="pagarTodoTransaccion();">Pagar con Transferencias</button>                                
                                    </div>
                                </div> 
                                <div class="col-xs-8"> 
                                    <div class="input-group input-group-xs">
                                        <?php $rs_banco = $obBD_con1->getArrayConsulta(35, $Ses_Emp_Cod.'*'.$Pec_Cod['Pla_Cod'], $obBD_conexion); ?>
                                        <select id="Ban_Cod_Pagar" name="Ban_Cod_Pagar" class="form-control input-xs readOnly getData" data-trigger="" required="">                        
                                           <?php foreach($rs_banco as $row){ 
                                               echo "<option value='$row[Ban_Cod]' data--ban_-pld='$row[Pld_Cod]' data--ban_-cue='$row[Ban_Cue]'>$row[Pld_Des]</option>";
                                            } ?>
                                        </select>
                                    </div> 
                                </div>
                            </div>

                        </div>
                    </fieldset> 
                  </div>

                  <div class="col-xs-3">
                        <fieldset class="exa-fieldset">                           
                        <legend class="Titulos2">Registrar Abonos Rol</legend>  
                        <form class="form-horizontal normal" id="formAnticipo" action="javascript:validaAnt();">
                            <input type="hidden" name='Are_Cod' value="" />
                            <input type="hidden" name='Cam_Cod' value="" />
                            <input type="hidden" name='Rol_Cod' value="" />
                            <input type="hidden" name='Map_Cod' value="" />
                        <div class="form-group">
                            <label class="col-xs-3 control-label label-xs required">Fecha:</label>  
                            <div class="col-xs-9"> 
                                <input type="text" id="Ant_Fec" name="Ant_Fec" class="form-control input-xs" required="" />
                            </div>  
                        </div> 
                        <?php if($configs['Cof_Con']=='S'){ ?>               
                        <div class="form-group cuenta_pago_gl">
                            <label class="col-xs-3 control-label label-xs">Cuenta:</label>  
                            <div class="col-xs-8">                                    
                                <select id="Global_Pag_Pld" name="Pag_Pld" class="form-control input-xs readOnly getData" required="">
                                    <?php 
                                        $cuentas_1 = $obBD_con1->getArrayConsulta(37, $Pec_Cod['Pla_Cod'].'*'.'1', $obBD_conexion);
                                        if(count($cuentas_1)>1) echo "<option value='' selected=''>Seleccione...</option>";
                                        foreach ($cuentas_1 AS $row) echo '<option value="'.$row['Pld_Cod'].'" ">'.$row['Pld_Des'].'</option>';
                                    ?>
                                </select>
                            </div>
                        </div>
                        <?php } ?> 
                        <div class="form-group">
                            <label class="col-xs-3 control-label label-xs">Concepto:</label>  
                            <div class="col-xs-9"> 
                                <textarea name="Ant_Obs" class="form-control input-xs" ></textarea>
                            </div>                                  
                        </div>
                        </form>
                      </fieldset> 
                  </div>    
                  <div class="col-xs-9" id="gridContainer" style="padding-bottom: 8px; min-height: 300px;">
					<div  style="padding-bottom: 8px;"><table id="rol"></table><div id="rolPager"></div></div>
					<button class="btn btn-sm btn-info" onclick="printR('#rol');" ><i class="glyphicon glyphicon-print"></i> Imprimir Saldos</button>
				  </div>    
                  <div class="col-xs-12">
                      <button class="btn btn-sm btn-inverse" onclick="$('#rol-sdetail').moveComp('#main-search').updateGridsSizes();$('#btnGuardar').removeAttr('disabled');" ><i class="glyphicon glyphicon-arrow-left"></i> Atr&aacute;s</button>
                      <button id="btnGuardar" type="button" onclick="$('#formAnticipo').formSubmit();" class="btn btn-success btn-save"><span class="glyphicon glyphicon-floppy-disk"></span> Guardar</button>
                  </div>    
              </div> 
            </div>    
        </div>
    </div>

    <script type="text/javascript">        
      $(document).ready(function() {           
            createSearchGrid([
                { label:'&nbsp;', name: 'act1', width: 20, align: 'center',viewable: false,title: false,
                    formatter:function (cv, opt, rObj) { 
                        return rObj.Rol_Est==='A'?$.getGridButton(detallarRoles,rObj):$.createIcon('remove red');
                    }
                } 
            ]);

            $("#pagarTodo").hide();
      });  
   </script>
   <div id="imprimirRoles" style="display: none;width: 1200px;"></div>
   <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>   
   <div id="proviDetaDialog" title="Provisiones"></div>
   <script type="text/javascript">
    var efectivo=true, tipos_pago=[],bancos=[];  
    function setRolGridCaption(caption){ 
        efectivo=true;
        if($.varValid(caption)){
            caption+='<div class="pull-right pagoRolExtra">';
            caption+='<b>Pagos:</b>&nbsp;<select id="pagoExtra" class="readOnly" onchange="efectivo=this.value===\'\';changeTipoPagos();">';
            caption+='<option value="">EFECTIVO</option><option value="1">GESTIONAR</option></select>';
            caption+='</div>';
        }
        return caption;
    }
    function setPeriodo(){
        
    }
    function changeTipoPagos(){        
        var ids=$grid.jqGrid('getDataIDs'), aux=$.jgrid.inlineEdit; $.jgrid.inlineEdit={focusField:false};
        $.each(ids,function (i,id){
            $grid.changeRow(id,{anticipo_val:[],saldo_ant:null,saldo_rol:null},null,true);
        }); $.jgrid.inlineEdit=aux; 
        updateTotales();
        $('.cuenta_pago_gl')[efectivo?'show':'hide']().find(':input').attr('required',efectivo);

        if(efectivo == true){
            $("#pagarTodo").hide(); 
        }
        else{
            $("#pagarTodo").show(); 
        }
    }
    function validaKeyUpAnt(el){        
        if(isNaN('0'+el.value)||el.value===''){ $(el).val('').focus(); return false; }
        else updateSaldosAnt({rowId:el.getAttribute('rowid')}); 
    }
    function validaChangeAnt(el){
        if(!isNaN(el.value)) $(el).val($.toFixed('0'+el.value)).trigger('keyup');
    }
    function gestionAnticipos(id,Ant_Det){  
        $('#Pagos_Con_Cod').val(id);
        $('#anticipos_rol_grid').setRows(Ant_Det);
        setSaldoPago(id);
        $('#anticipos_gestion').dialog('open');
        setPagoEmpleado(id);
    }

    function setPagoEmpleado(id){
        
        $.post("<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8')?>",{'Con_Cod':id,'setPagoEmpleado':true},function(response){
            if(response['success']){
                if(response['rows'].Pag_Con_For == 'T'){
                    $('.cuen_ban,.che_num,.banco,.bancos,.obs_credito').find(':input').removeAttr('required').end().hide().setData({});
                    var opcion = $("#Pag_Cod option:contains('Transferencia')");
                    $('#Pag_Cod').val(opcion.val());
                    $('#Pag_Cod').trigger('change');


                    var emp =(<?php echo json_encode($Ses_Emp_Cod); ?>);

                    if(emp == 429){
                        $('#Pag_Cod').attr('disabled',true);
                    }else{
                        $('#Pag_Cod').attr('disabled',false);
                    }

                    $('#Pag_Con_Tip').val(response['rows'].Pag_Con_Tip);
                    $('#Pag_Con_Cue').val(response['rows'].Pag_Con_Cue);
                    $('#Bak_Cod').val(response['rows'].Bak_Cod);
                    $('.banco_empleado, .tipo_empleado, .numero_empleado').show();
                }
                if(response['rows'].Pag_Con_For == 'C'){
                    $('.cuen_ban,.che_num,.banco,.bancos,.obs_credito').find(':input').removeAttr('required').end().hide().setData({});
                    var opcion = $("#Pag_Cod option:contains('Cheque')");
                    $('#Pag_Cod').val(opcion.val());
                    $('#Pag_Cod').trigger('change');
                    
                    if(emp == 429){
                        $('#Pag_Cod').attr('disabled',true);
                    }else{
                        $('#Pag_Cod').attr('disabled',false);
                    }
                    
                    $('.banco_empleado, .tipo_empleado, .numero_empleado').hide();

                }
                if(response['rows'].Pag_Con_For == 'E'){
                    $('.cuen_ban,.che_num,.banco,.bancos,.obs_credito').find(':input').removeAttr('required').end().hide().setData({});
                    var opcion = $("#Pag_Cod option:contains('Transferencia')");
                    $('#Pag_Cod').val(opcion.val());
                    $('#Pag_Cod').trigger('change');


                    if(emp == 429){
                        $('#Pag_Cod').attr('disabled',true);
                    }else{
                        $('#Pag_Cod').attr('disabled',false);
                    }

                    $('.banco_empleado, .tipo_empleado, .numero_empleado').hide();
                }
            }
            else{
                $('.cuen_ban,.che_num,.banco,.bancos,.obs_credito').find(':input').removeAttr('required').end().hide().setData({}); 
                var opcion = $("#Pag_Cod option:contains('Efectivo')");
                $('#Pag_Cod').val(opcion.val());
                $('#Pag_Cod').trigger('change');
                $('#Pag_Cod').attr('disabled',false);
                $('.banco_empleado, .tipo_empleado, .numero_empleado').hide();
                
            }

        },'json');
    }

    function pagarTodoTransaccion(){
        var filasConIdYRole = $("#rol tr[id][role='row']");
        var valoresDeIdYData = [];

        filasConIdYRole.each(function() {
          var idValor = $(this).attr("id");
          var dataOriginalData = $(this).find("span[data-originaldata]").data("originaldata");
          valoresDeIdYData.push({
            id: idValor,
            originalData: dataOriginalData
          });
        });


        for (var i = 0; i < valoresDeIdYData.length; i++) {
            (function(index) {
                var idValor = valoresDeIdYData[index]['id'];
                var antDet = valoresDeIdYData[index]['originalData'];

                var promesa = setPagoEmpleadoPromise(idValor);
                promesa.then(function(response) {
                    if(response['transferencia']){
                        var seleccionado = $("#Ban_Cod_Pagar option:selected").val();
                        $("#Ban_Cod").val(seleccionado);

                        $('#Pagos_Con_Cod').val(idValor);
                        $('#anticipos_rol_grid').setRows(antDet);

                        var fila=$grid.getRowData(idValor);
                        $('#Pag_Val').val(fila['saldo_rol']); 
                        if(fila['saldo_rol'] > 0){
                            addPago();
                        }
                    }
                }).catch(function(error) {
                    console.error('Error en setPagoEmpleado:', error);
                });
            })(i); // Pasa el valor actual de i a la funci�n an�nima
        }

    }


    // Crear una funci�n que devuelve una promesa para setPagoEmpleado
    function setPagoEmpleadoPromise(id) {
      return new Promise(function(resolve, reject) {
        $.post("<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8')?>", {'Con_Cod': id, 'setPagoEmpleado': true}, function(response) {
          if (response['success']) {
            if(response['rows'].Pag_Con_For == 'T')
            {
                    $('.cuen_ban,.che_num,.banco,.bancos,.obs_credito').find(':input').removeAttr('required').end().hide().setData({});
                    var opcion = $("#Pag_Cod option:contains('Transferencia')");
                    $('#Pag_Cod').val(opcion.val());
                    $('#Pag_Cod').trigger('change');
                    $('#Pag_Cod').attr('disabled',true);

                    $('#Pag_Con_Tip').val(response['rows'].Pag_Con_Tip);
                    $('#Pag_Con_Cue').val(response['rows'].Pag_Con_Cue);
                    $('#Bak_Cod').val(response['rows'].Bak_Cod);
                    $('.banco_empleado, .tipo_empleado, .numero_empleado').show();
                    response['transferencia'] = true;
            }
            else{
                response['transferencia'] = false;
            }
            resolve(response);
          } else {
                $('.cuen_ban,.che_num,.banco,.bancos,.obs_credito').find(':input').removeAttr('required').end().hide().setData({}); 
                var opcion = $("#Pag_Cod option:contains('Efectivo')");
                $('#Pag_Cod').val(opcion.val());
                $('#Pag_Cod').trigger('change');
                $('#Pag_Cod').attr('disabled',false);
                $('.banco_empleado, .tipo_empleado, .numero_empleado').hide();
            reject("Error en setPagoEmpleado");
          }
        }, 'json');
      });
    }



    function setSaldoPago(id){
        var fila=$grid.getRowData(id);
        $('#Pag_Val').val(fila['saldo_rol']);       
    }
    function issetRecibo(o){
        return ($.isObject(o.imprimir));
    }
    function getPagosButton(id,cv){    
        var obj, pags=0, tot=0,valid=($.varValid(cv)&&cv!=='');
        if(Array.isArray(cv)) $.each(cv,function(i,v) { tot+=(v['Pag_Val']*1); pags++; });
        obj=$('<div class="input-group input-group-xs btn-rol-aux"><span type="text" class="form-control" title="'+$.numFormat(tot,'currency')+': Posee '+pags+' pagos">'+$.numFormat(tot,'currency')+'</span><span class="input-group-btn Ant_Det" ><button type="button" onclick="gestionAnticipos('+id+',$(this).parent().data(\'originaldata\'));" class="btn btn-info" title="Gestionar Pagos" tabindex="-1"><i class="glyphicon glyphicon-plus"></i></button></span></div>');
        obj.find('.input-group-btn').attr('data-originaldata',$.jsonParser(cv));
        return obj.prop('outerHTML');
    }
    $.fn.fmatter.anticipos=function(cv,opts,cObjt){          
        return efectivo? '<input  value="'+($.isNum(cv)?cv:'')+'" type="text" onchange="validaChangeAnt(this);" onkeyup="validaKeyUpAnt(this);" onkeypress="return validar_decimal(event);"  name="anticipo_val" rowid="'+opts['rowId']+'" role="textbox" class="editable inline-edit-cell ui-widget-content ui-corner-all form-control input-xs Ant_Det" placeholder="0.00" style="width: 96%; text-align: right;" data-originaldata="[]" />':
               getPagosButton(opts['rowId'],cv);
    };
    $.fn.fmatter.anticipos.unformat=function(cv,opts,el){ var Ant_Val=efectivo? $.round($(el).find('input').val()): $.numUnformat($(el).text(),'currency'); return {efectivo:efectivo,Ant_Val:Ant_Val,Ant_Det:$(el).find('.Ant_Det').data('originaldata')}; };
    $(function() {        
        $('#Ant_Fec').createDatePickers({checkAvailability:true});        
        $('#anticipos_gestion').createDialog({ width:600, height:500, icon:'fa-money' });
        $('#anticipos_rol_grid').createGrid({
            caption:'PAGOS:',height:220,width:570,responsive:false,footerrow:true,
            colModel:[
                { label: 'Cód.Int.', name: 'index', key: true, width: 25, align:"center", hidden:true    }, 
                { label: 'Pago', name: 'Pag_Des', width: 30, align:"center", classes: 'bgNoColor bgNoRight' }, 
                { label: 'Cuenta ', name: 'Pld_Des', width: 75, classes: 'bgNoColor bgNoRight' },
                { label: 'Num.Che. ', name: 'Che_Num', width: 25, align:"center", classes: 'bgNoColor' }, 
                { label: 'Valor', name: 'Pag_Val', width: 30, align: 'right', formatter:'currency', formatoptions: {defaultValue:''},summaryType: "sum",summaryRound:2},
                { label: '<i class="glyphicon glyphicon-remove"></i>', name: 'Eliminar', width: 20, align:"center", classes: 'bgNoColor', formatter:'gridButton', formatoptions: {action:"var pag_ant=$('#anticipos_rol_grid'); pag_ant.jqGrid('delRowData',$(this).data('originaldata')); pag_ant.gridUpdate(); updateRolAntDet($('#Pagos_Con_Cod').val());", icon:'remove', type:'danger', data:function(o){ return o.index; } } },                    
                $.originalRow()
            ],
            loadComplete: function(){ $(this).setGridSummary(['Pag_Val'],{ Cam_Des:"<div style='text-align:right;'>TOTAL:</div>"}); }
        },true);
        //$('#anticipos_gestion').createDialog({ width:600, height:500 });
        $('#Ban_Cod').on('change',function (){ 
            $('#Vet_Cue').val($(this).find('option:selected').data('Ban_Cue'));
        });
        $('#Pag_Cod').on('change',function (){ 
            var text=$(this).find('option:selected').text().toUpperCase();
            $('.cuen_ban,.che_num,.banco,.bancos,.obs_credito').find(':input').removeAttr('required').end().hide().setData({}); 
            $('#btnAddPago').attr('disabled',false);
            switch(text){
                case 'CHEQUE':
                    $('.che_num').show().find(':input').attr('required','required'); 
                case 'DEPOSITO':
                case 'TRANSFERENCIA':  
                    $('.banco,.cuen_ban').show().find(':input').attr('required','required'); 
                    $('.cuenta_pago').hide().find(':input').removeAttr('required');  
                    $('#Ban_Cod').trigger('change');                    
                    break;
                default: 
                    $('.cuenta_pago').show().find(':input').attr('required','required');
                    break;
            }
        }).trigger('change');
    });
    function updateTotales(){
        var ids=$grid.jqGrid('getDataIDs');
        $.each(ids,function (i,id){
            updateSaldosAnt({rowId:id},true);
        });
        updateSaldosAnt();
    }
    function updateSaldosAnt(obj,ret){ 
        var rol=$grid;
        if($.varValid(obj)){
            var fila=$.extend(true,rol.jqGrid('getRowData', obj['rowId']),{} /*rol.find('tr#'+obj['rowId']).getDataForced()*/);
            fila['saldo_ant']=$.round(fila['total_rol'])-$.round(fila['abonos_rol_p']);
            //rol.find('tr#'+obj['rowId']+' td input[name=anticipo_val]').val(fila['saldo_ant']);
            fila['saldo_rol']=$.round(fila['total_rol'])-$.round(fila['abonos_rol_p'])-$.round(fila['anticipo_val']['Ant_Val']);
            rol.setCell(obj['rowId'],'saldo_ant',fila['saldo_ant'],null);
            rol.setCell(obj['rowId'],'saldo_rol',fila['saldo_rol'],null);            
            if(ret===true) return;
            //rol.changeRow(obj['rowId'],{saldo_rol:$.round(fila['total_rol'])-$.round(fila['anticipo_val']['Ant_Val'])}); 
        }
        var saldo_ant=$.round(rol.jqGrid('getCol','saldo_ant',false,'sum')),    
            saldo_rol=$.round(rol.jqGrid('getCol','saldo_rol',false,'sum'));
        rol.setGridSummary(['total_rol','abonos_rol_p','saldo_ant','saldo_rol'],{anticipo_val:saldo_ant-saldo_rol,Tic_Des:'<div style="text-align:right;">TOTALES:</div>'},false,function(v){ return $.isNum(v)?$.numFormat(v,'currency'):v; });        
    }
    function validaAnt(){
        var rol=$grid, data=$.extend($('#formAnticipo').getData('saveAnticipos'),{contratos:[]}), filas=rol.getGridBatch(), total=0;;
        rol.startGridEdit();       
        $.each(filas,function (i,v){
            if($.round(v['anticipo_val']['Ant_Val'])>0){
                if(efectivo) v['anticipo_val']['Ant_Det']=[{Pag_Cod:1,Pag_Des:'Efectivo',Pag_Val:v['anticipo_val']['Ant_Val'],Pag_Pld:data['Pag_Pld'],Pld_Des:data['Pag_Pld_Data']['Pag_Pld_Txt']}];
                data['contratos'].push($.extend({Con_Cod:v['Con_Cod'],Ant_Obs:v['anticipo_obs'],Personal:v['Prs_Ape']+' '+v['Prs_Nom']},v['anticipo_val']));                
                
            }
        });
        if(!data['contratos'].length) return $.alert('Debe ingresar al menos un Abono!',null,'remove');
        var sum=$grid.getGridSummary(['saldo_ant','saldo_rol']);
        data['total_ant']=sum['saldo_ant']-sum['saldo_rol'];
        //console.log(data);
        $.createDialogConfirm('¿Est&aacute; seguro que desea guardar el <u>Rol de Pagos</u>?',data,saveAnt);
    }
    function saveAnt(data){
        $.saveDataJson("",data,
            function(resp) {  
                $('#rol').stopGridEdit();                
                $('#impCompr').data('url',resp['Com_Link']); 
                $('#impAntic').data('url',resp['Ant_Link']);
                $grid.gridColUpdate('hide',['anticipos_ant','anticipo_val','anticipo_obs']);
                $grid.gridColUpdate('show',['anticipo_val_saved','imprimir']);
                $('#btnGuardar').attr('disabled','disabled');
                $('#pagoExtra').attr('disabled',true);
                $('#btnReset').show();
                $.each(resp['saved'], function(i,v){ $grid.changeRow(v['Con_Cod'],v,null,true); });
                $.alert('Los Anticipos se guardaron con Exito!');
                return false;
            }
        ); 
    }
    function resetAll(){
        $grid.gridColUpdate('hide',['anticipo_val_saved','imprimir']);
        $grid.gridColUpdate('show',['anticipos_ant','anticipo_val','anticipo_obs']);        
        $('#btnGuardar').removeAttr('disabled');
        $('#btnReset').hide();
        $('#formRol').setData({});
        $('#formAnticipo').setData({});
        $('#pagoExtra').attr('disabled',false);
        $('#rol').clearGrid(true);
        $("#Pec_Cod").val($("#Pec_Cod option:first").val());
    }
    function printRecibo(o){
        var imp=o.imprimir;
        $('#impCompr').data('Com_Cod',imp['Com_Cod']);
        $('#impAntic').data('Ant_Cod',imp['Ant_Cod']);
        $('#successDialog').dialog('open');
    }
    function addPago(){
        var pagos=$('#anticipos_rol_grid'), next=pagos.nextIndex(), pago=$.extend($('#pagosForm').getData(),{index:next,Pag_Des:$('#Pag_Cod option:selected').text(),Pld_Des:$('#Pag_Cod').val()*1===1?$('#Pag_Pld option:selected').text():$('#Ban_Cod option:selected').text()});
        if(pago['Pag_Val'].toNum()==0) return $.alert("El Pago no puede estar en cero!");
        pago['Pag_Des']=$('#Pag_Cod option:selected').text();
        pago['Pld_Des']=pago['Pag_Cod']*1===1?$('#Pag_Pld option:selected').text():$('#Ban_Cod option:selected').text();
        pagos.jqGrid('addRowData',next,pago,'last');
        pagos.jqGrid('highlightRow',next).loadUpdate();
        // $('#pagosForm').setData({Pag_Cod:1});
        updateRolAntDet($('#Pagos_Con_Cod').val());
    }
    function updateRolAntDet(id){
        var pagos=$('#anticipos_rol_grid'), Ant_Det=pagos.getOriginalData();
        //console.log(id,Ant_Det);
        $grid.changeRow(id,{anticipo_val:Ant_Det,saldo_rol:null},null,true); 
        updateSaldosAnt({rowId:id});
        setSaldoPago(id);
    }
    function validaCheNum(el){
        $('#Vet_Che').fieldValid();
        $('#btnAddPago').attr('disabled',true);
        var num=('0'+el.value)*1, exist, max=num, pagos=$grid.getGridColumn('anticipo_val'), data=$('#pagosForm').getData('validaCheNum');
        $.each(pagos,function (i,v){
            if($.varValid(v.Ant_Det)&&v.Ant_Det.length>0)  $.each(v.Ant_Det,function (j,p){ var Che_Num=p.Che_Num*1; max=Che_Num>max?Che_Num:max; if(p.Pag_Cod*1===3&&Che_Num===num){ exist=p; } });
        });
        data['max']=max+1;
        if(!!exist) return $('#Vet_Che').fieldValid(false,'El Cheque ya fue usado en este rol!').val();
        $('#Vet_Che').getValidationJson('',data,function(r){
            if(r['success']===true)  $('#btnAddPago').attr('disabled',false); else{
                $('#Vet_Che').val(r['Che_Num']);
                if(r['Che_Num'].trim()!=='') $('#btnAddPago').attr('disabled',false);
            }
        });
    }
    function detallarExtras(d,r){
        $('#formAnticipo').setData(r); 
        var ids=$grid.jqGrid('getDataIDs');
        $.each(ids,function (i,id){   
            var fila=$.extend(true,$grid.jqGrid('getRowData', id),{} /*rol.find('tr#'+obj['rowId']).getDataForced()*/);
            $grid.find('tr#'+id+' td input[name=anticipo_val]').val($.round(fila['total_rol'])-$.round(fila['abonos_rol_p']));
        });
    }
    </script>
    <div id="anticipos_gestion" title="Pagos del Anticipo">
        <fieldset class="exa-fieldset" style="min-height: 167px;">
            <legend class="Titulos2">Pago Anticipo</legend>
            <input type="text" id="Pagos_Con_Cod" name="Con_Cod" value="" class="hidden" >
            <form id="pagosForm" class="form-horizontal normal" action="javascript:addPago()"> 
                
                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs required">Tipo:</label>  
                    <div class="col-xs-6" >
                        <?php $rs_tipo = $obBD_con1->getArrayConsulta(34, '', $obBD_conexion); ?>
                        <select id="Pag_Cod" name="Pag_Cod" class="form-control input-xs readOnly getData" data-trigger="" required="">                        
                           <?php                       
                           foreach($rs_tipo as $row){ 
                               $PD=trim($row['Pag_Des']);
                               if(!startsWith(strtoupper($PD),'TARJETA')&&!endsWith(strtoupper($PD),'PAGAR')&&!startsWith(strtoupper($PD),'CRUCE')) 
                                       echo "<option value='$row[Pag_Cod]' ".">$PD</option>";
                            } ?>
                        </select>
                    </div>
                </div> 
                <?php if($configs['Cof_Con']=='S'){ ?>               
                <div class="form-group cuenta_pago">
                    <label class="col-xs-3 control-label label-xs">Cuenta:</label>  
                    <div class="col-xs-8">                                    
                        <select id="Pag_Pld" name="Pag_Pld" class="form-control input-xs readOnly getData" required="">
                            <?php 
                                $cuentas_1 = $obBD_con1->getArrayConsulta(37, $Pec_Cod['Pla_Cod'].'*'.'1', $obBD_conexion);
                                if(count($cuentas_1)>1) echo "<option value='' selected=''>Seleccione...</option>";
                                foreach ($cuentas_1 AS $row) echo '<option value="'.$row['Pld_Cod'].'" ">'.$row['Pld_Des'].'</option>';
                            ?>
                        </select>
                    </div>
                </div>
                <?php } ?> 

                <div class="form-group banco">
                    <label class="col-xs-3 control-label label-xs required">Banco:</label>  
                    <div class="col-xs-8" >
                        <?php $rs_banco = $obBD_con1->getArrayConsulta(35, $Ses_Emp_Cod.'*'.$Pec_Cod['Pla_Cod'], $obBD_conexion); ?>
                        <select id="Ban_Cod" name="Ban_Cod" class="form-control input-xs readOnly getData" data-trigger="" required="">                        
                           <?php foreach($rs_banco as $row){ 
                               echo "<option value='$row[Ban_Cod]' data--ban_-pld='$row[Pld_Cod]' data--ban_-cue='$row[Ban_Cue]'>$row[Pld_Des]</option>";
                            } ?>
                        </select>
                    </div>
                </div>
                <div class="form-group cuen_ban" style="display: none;">
                    <label class="col-xs-3 control-label label-xs required">Cta&nbsp;Banco:</label>                                     
                    <div class="col-xs-6">
                        <input type="text" id="Vet_Cue" onchange="" class="form-control input-xs readOnly" readonly="">
                    </div>
                </div>

                <div class="form-group banco_empleado" style="display: none;">
                    <label class="control-label col-xs-3 label-xs">Banco:</label>
                    <div class="col-xs-8">
                        <select id="Bak_Cod" name="Bak_Cod" class="form-control input-xs readOnly" disabled="true">
                                <?php 
                                $bancos = $obBD_con1->getArrayConsulta(55,"", $obBD_conexion);   
                                    foreach ($bancos AS $banco){
                                        echo "<option value='$banco[Bak_Cod]'>$banco[Bak_Des]</option>";
                                    }
                                ?>                                                                
                        </select>
                    </div>
                </div>

                <div class="form-group tipo_empleado" style="display: none;">
                    <label class="control-label col-xs-3 label-xs">Tipo de cuenta:</label>
                    <div class="col-xs-8">
                        <select id="Pag_Con_Tip" name="Pag_Con_Tip" class="form-control input-xs readOnly" disabled="true">
                                <option value="N">Ninguno</option>
                                <option value="A">Ahorros</option>
                                <option value="C">Corriente</option>                                                                
                        </select>
                    </div>
                </div>


                <div class="form-group numero_empleado" style="display: none;">
                    <label class="control-label col-xs-3 label-xs">N&uacute;mero de cuenta:</label>
                    <div class="col-xs-8">
                        <input type="text" id="Pag_Con_Cue" name="Pag_Con_Cue" class="form-control input-xs txtRight"onkeypress="return validar_decimal(event);"  class="form-control input-xs txtRight readOnly" readonly="true" required="" /> 
                    </div>
                </div>


                <div class="form-group che_num" style="display: none;">
                    <label class="col-xs-3 control-label label-xs required">Número:</label>                                     
                    <div class="col-xs-6">
                        <div class="input-group input-group-xs">                          
                            <input type="text" id="Vet_Che" name="Che_Num" onchange="validaCheNum(this)" onkeypress="return validar_numeric(event);" class="form-control input-xs">
                            <span class="input-group-addon validate"><i class="glyphicon glyphicon-ok green"></i></span>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs required">Valor:</label>                                     
                    <div class="col-xs-6">
                        <div class="input-group input-group-xs">
                            <span class="input-group-addon"><i class="fa fa-usd"></i></span>
                            <input type="text" id="Pag_Val" name="Pag_Val" onchange="" class="form-control input-xs" required="">                            
                        </div>
                    </div>
                </div>
                <div class="form-group center">
                    <button id="btnAddPago" class="btn btn-xs btn-success" ><i class="glyphicon glyphicon-plus"></i>Agregar Tipo Pago</button>
                </div>
            </form> 
        </fieldset>
        <div class="condensed"><table id="anticipos_rol_grid"></table></div>
    </div>
    <div id="detallesRol">
        <style>#detalleRolTabs .panels-area div[id^="tabs"]{padding: 10px 0 0 0;}</style>
        <div class="form-horizontal normal">
            <div class="form-group">
                <label class="col-xs-3 control-label label-xs">Personal:</label>  
                <div class="col-xs-9"><input type="text" id="detaPersona" name="detaPersona" class="form-control input-xs" readonly="" tabindex="-1"/></div>  
            </div>
        </div>
        <div id="detalleRolTabs" class="ui-tab-fix noBg" style="margin-top: 0px;">
            <ul>
                <li><a href="#tabs-1">Abonos Anteriores</a></li>                                
            </ul>
            <div class="panels-area form-horizontal normal ">
                <div id="tabs-1"><table id="abonos_rol_p"></table></div>                             
            </div>
        </div>    
    </div>
     <!--INICIO DEL DIALOGO IMPRIMIR --> 
    <div id="successDialog"  title="Mensaje del Sistema" style="display: none;">  
        <center><h4>El Comprobante se ha registrado con Exito!</h4></center>
        <center>
            <button type="button" id="impAntic" onclick="$.imprimirUrl($(this).data('url')+$(this).data('Ant_Cod'))" style="display: inline;" title="Imprimir Comprobante de Anticipos" class="btn btn-primary"><i class="glyphicon glyphicon-print"></i> Recibo Anticipo </button>
            <button type="button" id="impCompr" onclick="$.imprimirUrl($(this).data('url')+$(this).data('Com_Cod'))" style="display: inline;" title="Imprimir Comprobante de Anticipos" class="btn btn-primary"><i class="glyphicon glyphicon-print"></i> Comprob. Anticipo </button>
        </center>
    </div>
	<script>
	function printR(grid) {
                $('#tablaReporte').html($(grid).jqGrid('exportGridInnerHTML',{print:true, generated:false, caption:false, footer:true, bodyBorder:false, removeHiddens:true, removeCols:[8,9,10,11]}));
                $('#titleReporte').html("SALDO SUELDOS POR PAGAR");
                $('#formatoReporte').printElement({pageTitle:"<?Php echo $Ses_Sys_Nom; ?>",printMode:'popup',overrideElementCSS:[{ href:'../../mascaras/model1/estilos/print.css',media:'print'}]});                
            }
            function exportR(grid) {
                var temp=$('<div>'+$('#formatoExportar').html()+'</div>');
                temp.append($(grid).jqGrid('exportGridHTML',{generated:false,caption:true,bodyBorder:false,footer:true,sepEnd:true}));                
                $.downloadFile($.exportarExcelBlob(temp.html(),'Digitacion'),'digitacion_'+$.getDate()+'.xls');    
            }
        </script>
        <div id="formatoReporte" style="display: none;">
	  <div style="width: 1000px;">
            <?php echo $obBD_con1->getReportHeader($Ses_Suc_Cod, 'REPORTE DE REGISTROS', '<span id="titleReporte"></span>',$obBD_conexion); ?>
            <table id="tablaReporte" cellspacing="0" cellpadding="0" style="border-collapse: collapse;table-layout: fixed;"></table>            
            <?php echo $obBD_con1->getReportFooter($Ses_Suc_Cod,$Ses_Usu_Cod,$obBD_conexion); ?>
	  </div>
        </div>  
        <div id="formatoExportar" style="width: 700px;display: none;">
            <?php echo $obBD_con1->getReportHeader($Ses_Suc_Cod, 'REPORTE DE REGISTROS', '<span class="title_grid"></span>',$obBD_conexion,false,5); ?>
        </div>
	<script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=1"></script>
</BODY>
</HTML>