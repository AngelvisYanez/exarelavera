<?php	
/**
* @abstract Permite
* @author Erik Niebla
* @version 1.0
* Fecha de creación  2016-11-24
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

//var_dump($obBD_con1->getFormula(21,NULL,NULL,$obBD_conexion));
if(isset($getPlantilla)){ 
    //if(empty($Map_Cod)){ echo json_encode(array(success=>false,message=>'No se ha seleccionado la Plantilla del Rol de Pagos!'));exit(); }
    $grid=$obBD_con1->getGridRol($Map_Cod,$obBD_conexion);
    foreach($grid['grid']['colModel'] AS &$col){
        switch($col['name']){
            case 'Tic_Des':
                $col['classes']='bgNoColor';               
                break;
            case 'total_rol':               
                //$col['label']='Total Rol';
                break;
            case 'anticipos_rol_p':
                $col['labelLong']='ANTICIPOS ANTERIORES';                
                break;
            //default :
                //if(isset($col['Cam_Cod'])) $col['hidden']=true;
        }        
    } unset($col);
    $grid['grid']['colModel']=array_merge($grid['grid']['colModel'],array(        
        array('label'=>'Pagos','name'=>'anticipo_val','width'=>75,'align'=>'right','formatter'=>'anticipos','formatoptions'=>array('defaultValue'=>''),'summaryRound'=>2,'summaryRoundType'=>"round"/*,'editable'=>false, 'editoptions'=>array('dataInit'=> "styleInput")*/),
        //array('label'=>'Observacion','name'=>'anticipo_obs','width'=>125,'formatter'=>'textboxExa','classes'=>'bgNoColor'),
        array('label'=>'Pagos','name'=>'anticipo_val_saved','width'=>75,'align'=>'right','formatter'=>'numeric','hidden'=>true),        
        array('label'=>'<i class="glyphicon glyphicon-print"></i>','labelLong'=>'Imprimir','name'=>'imprimir','width'=>50,'align'=>'center','formatter'=>'gridButton','formatoptions'=>array('action'=>"printRecibo",'icon'=>'print','conditional'=>'issetRecibo'),'classes'=>'columnHighlight3','hidden'=>true),
    ));
    $obBD_con1->echoJson($grid);    
}
if(isset($getRoles)){ 
    $response=array('success'=>true, 'Rol_Tip'=>$Rol_Tip, 'numbers'=>array()); 
    $roles=$obBD_con1->getArrayConsulta(13,$_GET, $obBD_conexion); 
    foreach($roles as $v) {            
        array_push($response['numbers'],$v['Rol_Num']*1);
    }
    $obBD_con1->echoJson($response);
}
if(isset($getDefaults)){     
    $obBD_con1->getRolDefaults($Ses_Emp_Cod,$Rol_Fei,$Rol_Fef,$obBD_conexion);    
}
if(isset($getData)){ 
    $obBD_con1->getRolArea($_GET, $hoy, $obBD_conexion);
} 
if(isset($saveRol)){ 
    $configs = $obBD_con1->getRowConsulta(23, $Ses_Emp_Cod,$obBD_conexion);
    $Ant_Fec=$rol['Rol_Fef'];
    $pec = $obBD_con1->getPerioCont($Ses_Emp_Cod,$Ant_Fec,$obBD_conexion);
    //var_dump($configs); exit();
    $Com_Cod=NULL; $return=array();
    $obBD_ins1 =  new Class_Log_Datos_Rol;
    //$obBD_ins1->debug(true);
    $obBD_conexionIns = new Class_Log_Conexion_Rol($Ses_Dat_Dis);
    try{
        if(empty($data)||count($data)==0) throw new Exception('El Rol no puede estar vacio!');
        if($configs['Cof_Con']=='S'){ 
            if(empty($pec)||empty($pec['Pec_Cod'])) throw new Exception('No se encontro el periodo contable!');
            /* PARA EL COMPROBANTE CONTABLE */
            $Com_Fec=$Ant_Fec; $meseCom = explode('-', $Com_Fec); $campo='Prv_Cod'; // campos para el asiento
            $Tia_Asi = $obBD_con1->getRowConsulta(26, "E*RL", $obBD_conexion);   
            if(!isset($Tia_Asi['Tia_Cod'])||empty($Tia_Asi['Tia_Cod'])) throw new Exception('Revisar el tipo de asiento: <u>Roles de Pago</u>!');
            $Com_Num= $obBD_con1->getComNumAuto($Ses_Emp_Cod, $Tia_Asi['Tia_Cod'], $Com_Fec, $obBD_conexion)*1; // Secuencia de comprobante por mes y por tipo                
            $Prv_Cod=$obBD_con1->getProveeClie($Ses_Emp_Cod, $campo, $obBD_conexion);                  
        }        
        $obBD_ins1->inicio_transaccion($obBD_conexionIns);
            $obBD_ins1->operacionobBD(14,$rol,$obBD_conexionIns); //crea el rol de pagos
            $Rol_Cod=$obBD_ins1->insercionid($obBD_conexionIns);            
            foreach($data as $d) {
                $is_rol=(!empty($d['dias'])||$d['dias']*1>0);
                if($is_rol){
                    foreach($fields as $f) { //echo $f['Cam_Var']. '<br/>';
                        $obBD_ins1->operacionobBD(15,array('Rol_Cod'=>$Rol_Cod,'Cam_Cod'=>$f['Cam_Cod'],'Con_Cod'=>$d['Con_Cod'],'Rol_Val'=>$d[$f['Cam_Var']]),$obBD_conexionIns); // inserta los valores de cada campo del rol
                    }
                    //$obBD_ins1->echoLog($d);
                    //$obBD_ins1->echoLog($extra_fields);
                    foreach($extra_fields as $k=>$ef) {
                        if(isset($d[$k.'_data']))$obBD_ins1->echoLog($d[$k.'_data']);
                        if(isset($d[$k.'_data'])&&!empty($d[$k.'_data'])&& count($d[$k.'_data'])>0){
                            foreach($d[$k.'_data'] as $e) {
                                $obBD_ins1->operacionobBD(44,array_merge(array('Rol_Cod'=>$Rol_Cod,'id'=>$e[$ef['id_field']]),$ef),$obBD_conexionIns);
                            }
                        }
                    }

                    if($configs['Cof_Con']=='S'){ 
                        /* Cabecera del Comprobante */
                        $obBD_ins1->operacionobBD(24, $pec['Pec_Cod'].'*'.$Prv_Cod.'*'.$Com_Num.'*'.$Com_Fec.'*'.trim($rol['Rol_Con']).'*'.$Tia_Asi['Tia_Cod'].'*'.$d['anticipo_val']['Ant_Val'].'*'.'ROLES: '.$rol['Rol_Con'].'*'.$campo, $obBD_conexionIns);
                        $Com_Cod = $obBD_ins1->insercionid ($obBD_conexionIns);
                        $obBD_ins1->operacionobBD(25, $Com_Cod.'*'.$Rol_Cod, $obBD_conexionIns); // relacion rol comprobante // relacion ROL comprobante
                        /* Detalle Comprobante */
                        $cuenta = $obBD_con1->getRowConsulta(22, $rol['Cam_Cod'].'*'.$rol['Are_Cod'].'*'.$pec['Pec_Cod'], $obBD_conexion); 
                        if(!isset($cuenta['Pld_Cod'])||empty($cuenta['Pld_Cod'])) throw new Exception('Revisar la parametrizacion contable del campo: <u>'.$v['Cam_Des'].'</u>!');
                        $obBD_ins1->operacionobBD(27, $Com_Cod.'*'.'D'.'*'.$d['anticipo_val']['Ant_Val'].'*'.$cuenta['Pld_Des'].'*'.'Roles'.'*'.$cuenta['Pld_Cod'], $obBD_conexionIns);  // inserta asiento          
                        $d['cheque']=array();
                        foreach($d['anticipo_val']['Ant_Det'] as &$p){
                            $obBD_ins1->operacionobBD(27, $Com_Cod.'*'.'H'.'*'.$p['Pag_Val'].'*'.$p['Pld_Des'].'*'.$p['Pag_Des'].'*'.($p['Pag_Cod']*1==1?$p['Pag_Pld']:$p['Ban_Cod_Data']['Ban_Pld']), $obBD_conexionIns);  // inserta asiento          
                            $p['Asi_Cod'] = $obBD_ins1->insercionid ($obBD_conexionIns);
                            if($p['Pag_Cod']*1==3){
                                $obBD_ins1->operacionobBD(41, $Prv_Cod.'*'.$p['Ban_Cod'].'*'.$p['Asi_Cod'].'*'.$p['Che_Num'].'* *'.$p['Pag_Val'].'*'.$p['Ant_Obs'].'*'.$Com_Fec.'*1*'.$p['Personal'], $obBD_conexionIns);  // inserta cheque          
                                array_push($c['cheque'],array('Banco'=>$p['Ban_Cod_Data']['Ban_Cod_Txt'],'Cheque'=>$p['Che_Num'],'link'=>"?codigo2=1&asi=$p[Asi_Cod]&ban=$p[Ban_Cod]&pro=$Prv_Cod"));
                            }
                        } unset($p);
                        //$return[$j]['imprimir']=array_merge($return[$j]['imprimir'],array('Com_Cod'=>$Com_Cod,'Com_Num'=>$Com_Num,'cheque'=>$c['cheque'],'Codigo'=>$Tia_Asi['Tia_Abr'].'-'.$meseCom[1].'-'.$Com_Num));
                        $Com_Num++; //para que no se repitan los numeros
                    }
                }
            }
            
        $obBD_ins1->fin_transaccion_nomsn($obBD_conexionIns);    
    }catch(Exception $e){ $obBD_ins1->rollBack_nomsn($obBD_conexionIns); $responce=array('success'=>false, 'message'=>'No se logro guardar el Rol de Pagos', 'error'=>$e->getMessage()); $obBD_con1->echoJson($responce);  }
    if($obBD_ins1->Error==0){ $responce=array('success'=>true, 'Rol_Cod'=>$Rol_Cod, 'Com_Cod'=>isset($Com_Cod)?$Com_Cod:NULL,'Com_Cod_Provi'=>isset($Com_Cod_Provi)?$Com_Cod_Provi:NULL); } else{ $responce=array('success'=>false,'message'=>"No se ha logrado realizar la Transaccion",'error'=>$obBD_ins1->MsgError);}  
    $responce['Com_Link']=baseUrl("../../contabilidad/FRONT/con_pri_compr_1.1.php")."?codigo=";
    $responce['Rol_Link']=baseUrl("../../rrhh/FRONT/rhu_alt_rol_gestion.php")."?printAjax=1&echo=1&Rol_Cod=$Rol_Cod";
    $responce['Rol_Ind_Link']=baseUrl("../../rrhh/FRONT/rhu_alt_rol_gestion.php")."?printRolIndAjax=1&echo=1&Rol_Cod=$Rol_Cod";
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
$configs = $obBD_con1->getRowConsulta(23, $Ses_Emp_Cod,$obBD_conexion);
if($configs['Cof_Con']=='S'){  $Pec_Cod = $obBD_con1->getRowConsulta(36,$Ses_Emp_Cod.'*'.$hoy,$obBD_conexion); }
?>
<!DOCTYPE html>
<HTML>
<HEAD>		
    <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
    <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php")?> 
    <script type="text/javascript" src="../../framework/jquery/MonthPicker/jquery.mtz.monthpicker.js"></script>
    <script type="text/javascript" src="../../framework/plugins/moment.min.js"></script>
    <script type="text/ecmascript" src="../VALIDACIONES/rhu_val_roles.js?x=600"></script>
    <style>          
    </style>
</HEAD>
<BODY> 
    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo; Registrar Anticipos</h3></div>        
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            
            <div>
                <div class="row">   
                    <form id="formRol" class="form-horizontal normal"  action="javascript:validaAnt();">
                    <div class="col-xs-3">  
                        <fieldset class="exa-fieldset ">                           
                            <legend class="Titulos2">Plantilla Rol</legend>                           
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs required">Area:</label>  
                                    <div class="col-xs-9"> 
                                        <select id="Are_Cod" name="Are_Cod" class="form-control input-xs" onchange="if(this.value!=='') getDataGrid(this.value); else $grid.clearGridData();" required="">
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
                                    <label class="col-xs-3 control-label label-xs required">Plantilla:</label>  
                                    <div class="col-xs-9"> 
                                        <select id="Map_Cod" name="Map_Cod" class="form-control input-xs" onchange="if(this.value!=='') recreateGrid(this.value)" required="">
                                            <option value="">Seleccione...</option>
                                            <?php $rs_maps = $obBD_con1->getArrayConsulta(10,$Ses_Emp_Cod, $obBD_conexion);                                            
                                                foreach ($rs_maps as $row){  
                                                     ?><option value="<?php echo $row['Map_Cod']; ?>"><?php echo $row['Map_Des']; ?></option><?php
                                                }
                                            ?>
                                        </select>
                                    </div>                                  
                                </div>                                
                           
                        </fieldset>
                    </div>   
                    <div class="col-xs-3">  
                        <fieldset class="exa-fieldset">                           
                            <legend class="Titulos2">Datos Generales</legend>
                            
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs required">Periodo:</label>  
                                    <div class="col-xs-9"> 
                                        <select id="Pec_Cod" name="Pec_Cod" class="form-control input-xs" onchange="setRoles();setPeriodo();" required="">
                                            <?php $rs_perio = $obBD_con1->getArrayConsulta(12,$Ses_Emp_Cod, $obBD_conexion);                                            
                                                foreach ($rs_perio as $row){  
                                                     ?><option value="<?php echo $row['Pec_Cod']; ?>" data-year="<?php echo $row['Periodo']; ?>">Periodo <?php echo $row['Periodo']; ?></option><?php
                                                }
                                            ?>
                                        </select>
                                    </div>                                  
                                </div> 
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs required">Tipo:</label>  
                                    <div class="col-xs-9"> 
                                        <select id="Rol_Tip" name="Rol_Tip" class="form-control input-xs readOnly datatrigger" onchange="updateDias()" disabledt="">                                            
                                            <option value="M" data-dias="30" data-period="12" >Mensual</option>
                                            <option value="Q" data-dias="15" data-period="24">Quincenal</option>
                                            <option value="BS" data-dias="14">Bi Semanal</option>
                                            <option value="S" data-dias="7">Semanal</option>
                                        </select>
                                    </div>                                  
                                </div> 
                           
                        </fieldset>
                    </div>   
                    <div class="col-xs-3">                         
                        <fieldset class="exa-fieldset ranges M Q S BS" style="display: none;">                           
                            <legend class="Titulos2">Rango</legend>
                                                          
                                <div class="form-group ranges M Q">
                                    <label class="col-xs-3 control-label label-xs required">Mes:</label>  
                                    <div class="col-xs-9">
                                      <div class="input-group input-group-xs">
                                        <input id="Month" name="Month" type="hidden">
                                        <span id="Mes" class="form-control"></span>
                                        <span class="input-group-btn">
                                            <button id="MonthButton" onclick="$('#Month').monthpicker('show','#Mes');" class="btn btn-success" type="button"><span class="glyphicon glyphicon-calendar" title="Seleccione Mes"></span></button>
                                        </span>
                                      </div>
                                    </div>                                
                                </div>  
                                <div class="form-group ranges Q">
                                    <label class="col-xs-3 control-label label-xs required">Quincena:</label>  
                                    <div class="col-xs-9">
                                        <select id="Rol_Q" class="form-control input-xs datatrigger" onchange="setRange();"> 
                                            <option value="0">Seleccione..</option>
                                            <option value="1">Primera Quincena</option>
                                            <option value="2">Segunda Quincena</option>                                           
                                        </select>
                                    </div>                                
                                </div> 
                                <div class="form-group ranges S BS">
                                    <label class="col-xs-3 control-label label-xs required">Semana:</label>  
                                    <div class="col-xs-9">
                                        <select id="Rol_S" class="form-control input-xs datatrigger" onchange="setSemana();"></select>
                                    </div>                                
                                </div> 
                           
                        </fieldset>  
                        
                    </div>
                    <div class="col-xs-3">
                        <fieldset class="exa-fieldset Rol_Range">                           
                            <legend class="Titulos2">Rol</legend>
                            
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs required">Numero:</label>  
                                    <div class="col-xs-9"> 
                                        <input type="number" id="Rol_Num" name="Rol_Num" class="form-control input-xs" readonly="" style="text-align: right;" value="1" min="1" step="1" required="" />
                                    </div>                                  
                                </div> 
                                <div class="form-group">
                                    <div class="col-xs-12"> 
                                        <div class="input-group input-group-xs">
                                            <span class="input-group-addon bold alert-info">Desde:</span>
                                            <input id="Rol_Fei" name="Rol_Fei" type="text" class="form-control span" style="text-align: right;" readonly="" tabindex="-1">
                                            <span class="input-group-addon bold alert-info">Hasta:</span>
                                            <input name="Rol_Fef" type="text" class="form-control span" style="text-align: right;" readonly="" tabindex="-1">                                            
                                        </div>
                                    </div>    
                                </div>
                            
                        </fieldset> 
                    </div>
                    <div class="col-xs-3">
                        <?php if($configs['Cof_Con']=='S'){ ?>   
                        
                        <div class="form-group">
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
                    </div>
                    <div class="col-xs-9">
                        
                        <div class="form-group">
                            <label class="col-xs-2 control-label label-xs">Concepto:</label>  
                            <div class="col-xs-10"> 
                                <input type="text" name="Rol_Con" class="form-control input-xs" />
                            </div>                                  
                        </div>
                        
                    </div>
                    </form>
                    <div class="col-xs-12" >
                        
                        <div style="min-height: 300px;">                            
                                <table id="rol"></table><div id="rolPager"></div>
                        </div>    
                       
                    </div>    
                </div>
                <div class="row">   
                    <div class="col-xs-12">
                        <button id="btnGuardar" type="button" onclick="validaRol()" class="btn btn-primary btn-save"><span class="glyphicon glyphicon-floppy-disk"></span> Guardar</button>
                        <button id="btnReset" type="button" onclick="resetAll();" class="btn btn-primary btn-new hidden" disabled=""><span class="glyphicon glyphicon-floppy-disk"></span> Nuevo</button> 
                    </div>
                    <div class="col-xs-12 Titulos2"><hr><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco ( &nbsp;<span class="required"></span> ) son campos obligatorios.</div>
                </div>   
            </div>
            
        </div>
    </div>
    <div id="" title="Provisiones"></div>
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
                <li><a href="#tabs-1">Anticipos</a></li>
                <li><a href="#tabs-2">Descuentos</a></li>
                <li><a href="#tabs-3">Prestamos</a></li>                
            </ul>
            <div class="panels-area form-horizontal normal ">
                <div id="tabs-1"><table id="anticipos_rol_p"></table></div>
                <div id="tabs-2"><table id="descuentos_rol_p"></table></div>
                <div id="tabs-3"><table id="prestamos_rol_p"></table></div>               
            </div>
        </div>    
    </div>
    <!--INICIO DEL DIALOGO IMPRIMIR --> 
    <div id="successDialog"  title="Mensaje del Sistema" style="display: none;">  
        <center><h4>El Comprobante se ha registrado con Exito!</h4></center>
        <center>
            <button type="button" id="impRoles" onclick="$.imprimirUrl($(this).data('url'))" style="display: inline;" title="Imprimir Rol Grupal" class="btn btn-primary"><i class="glyphicon glyphicon-print"></i> Rol Grupal</button>            
            <button type="button" id="impRolesInd" onclick="$.imprimirUrl($(this).data('url'))" style="display: inline;" title="Imprimir Roles Individual" class="btn btn-primary"><i class="glyphicon glyphicon-print"></i> Rol Individual</button>            
        </center>
    </div>

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
            $grid.changeRow(id,{anticipo_val:efectivo?$grid.jqGrid('getCell',id,'total_rol'):[],saldo_rol:null},null,true);
        }); $.jgrid.inlineEdit=aux; 
        updateSaldosAnt();
        $('.cuenta_pago_gl')[efectivo?'show':'hide']().find(':input').attr('required',efectivo);
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
        $('#anticipos_gestion').dialog('open');
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
        console.log(cv);
        return efectivo? '<input type="text" readOnly="" value="'+/*($.isNum(cv)?cv:'')*/'efectivo'+'" onchange="validaChangeAnt(this);" onkeyup="validaKeyUpAnt(this);" onkeypress="return validar_decimal(event);"  name="anticipo_val" rowid="'+opts['rowId']+'" role="textbox" class="editable inline-edit-cell ui-widget-content ui-corner-all form-control input-xs Ant_Det" placeholder="0.00" style="width: 96%; text-align: right;" data-originaldata="[]" />':
               getPagosButton(opts['rowId'],cv);
    };
    $.fn.fmatter.anticipos.unformat=function(cv,opts,el){ var Ant_Val=efectivo? $.round($(el).find('input').val()): $.numUnformat($(el).text(),'currency'); return {efectivo:efectivo,Ant_Val:Ant_Val,Ant_Det:$(el).find('.Ant_Det').data('originaldata')}; };
    $(function() {
        $('#Month').attr('data-monthplacer','#Mes').createMonthPicker({showYear:false, prepend:'Seleccione Mes',openOnFocus:false},setRange).monthpicker('setMonthActive',0);;
        $('#Ant_Fec').createDatePickers({checkAvailability:true});
        recreateGrid($('#Map_Cod').val());
        setRoles();
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
//    function styleInput(e,obj,opt){            
//        e.style.textAlign = 'right'; 
//        e.placeholder='0.00';
//        $(e).on('keyup',function (){
//            if(isNaN('0'+this.value)||this.value===''){ $(this).val('').focus(); return false; }
//            else updateSaldosAnt(obj); 
//        });
//        $(e).on('change',function (){ if(!isNaN(this.value)) this.value=$.toFixed('0'+this.value);  });
//    }
    function updateSaldosAnt(obj){ 
        var rol=$('#rol');
        if($.varValid(obj)){
            var fila=$.extend(true,rol.jqGrid('getRowData', obj['rowId']),{} /*rol.find('tr#'+obj['rowId']).getDataForced()*/);
            rol.setCell(obj['rowId'],'saldo_rol',$.round(fila['total_rol'])-$.round(fila['anticipo_val']['Ant_Val']),null);
            //rol.changeRow(obj['rowId'],{saldo_rol:$.round(fila['total_rol'])-$.round(fila['anticipo_val']['Ant_Val'])}); 
        }
        var total=$.round(rol.jqGrid('getCol','total_rol',false,'sum')),
            saldo=$.round(rol.jqGrid('getCol','saldo_rol',false,'sum')),
            antici=total-saldo;
        rol.jqGrid('footerData','set',{total_rol:$.numFormat(total,'currency'),anticipo_val:$.numFormat(antici,'currency'),saldo_rol:$.numFormat(saldo,'currency')},false);
    }
    function validaExtra(data){
        if(efectivo) if(!$.vv(data['rol']['Pag_Pld'])||data['rol']['Pag_Pld']===''){ $.alert('Seleccione la <u>Cuenta Pago</u>'); $('#Pag_Pld').focus(); return false; }
        return true;
        
    }
    function extraData(data){ 
        console.log(data);
        if(efectivo){            
            $.each(data['data'],function (i,v){
                v['anticipo_val']['Ant_Val']=v['total_rol'];
                v['anticipo_val']['Ant_Det']=[{Pag_Cod:1,Pag_Des:'Efectivo',Pag_Val:v['total_rol'],Pag_Pld:data['rol']['Pag_Pld'],Pld_Des:data['rol']['Pag_Pld_Data']['Pag_Pld_Txt']}];            
            });
        }
        $.each(data['fields'],function (i,v){
            if(v['Cam_Var']==='total_rol'){ data['rol']['Cam_Cod']=v['Cam_Cod']; return false; }
        });
        return data; 
    }
    function resetAll(){
        $grid.gridColUpdate('hide',['anticipo_val_saved','imprimir']);
        $grid.gridColUpdate('show',['anticipos_ant','anticipo_val','anticipo_obs']);        
        $('#btnGuardar').removeAttr('disabled');
        $('#btnReset').hide();
        $('#formRol').setData({});
        //$('#formAnticipo').setData({});
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
        pago['Pag_Des']=$('#Pag_Cod option:selected').text();
        pago['Pld_Des']=pago['Pag_Cod']*1===1?$('#Pag_Pld option:selected').text():$('#Ban_Cod option:selected').text();
        pagos.jqGrid('addRowData',next,pago,'last');
        pagos.jqGrid('highlightRow',next).loadUpdate();
        $('#pagosForm').setData({Pag_Cod:1});
        updateRolAntDet($('#Pagos_Con_Cod').val());
    }
    function updateRolAntDet(id){
        var pagos=$('#anticipos_rol_grid'), Ant_Det=pagos.getOriginalData();
        //console.log(id,Ant_Det);
        $grid.changeRow(id,{anticipo_val:Ant_Det,saldo_rol:null},null,true); 
        updateSaldosAnt({rowId:id});
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
    <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
</BODY>
</HTML>