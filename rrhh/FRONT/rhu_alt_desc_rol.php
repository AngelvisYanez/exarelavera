<?php	
/**
* @abstract Permite
* @author Erik Niebla
* @version 1.0
* Fecha de creaci�n  2016-11-24
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
        $col['editable']=false;
        switch($col['name']){
            case 'Tic_Des':
                $col['classes']='bgNoColor';               
                break;
            case 'total_rol':               
                //$col['label']='Total Rol';
                break;
            case 'descuentos_rol_p':
                $col['labelLong']='DESCUENTOS ANTERIORES';                
                break;
            default :
                if(isset($col['Cam_Cod'])) $col['hidden']=true;
        }        
    } unset($col);
    $grid['grid']['colModel']=array_merge($grid['grid']['colModel'],array(        
        array('label'=>'Descuento','name'=>'anticipo_val','width'=>75,'align'=>'right','formatter'=>'anticipos','formatoptions'=>array('defaultValue'=>''),'summaryRound'=>2,'summaryRoundType'=>"round"/*,'editable'=>false, 'editoptions'=>array('dataInit'=> "styleInput")*/),
        array('label'=>'Observacion','name'=>'anticipo_obs','width'=>125,'formatter'=>'textboxExa','classes'=>'bgNoColor'),
        array('label'=>'Descuento','name'=>'anticipo_val_saved','width'=>75,'align'=>'right','formatter'=>'numeric','hidden'=>true),
        array('label'=>'Saldo','name'=>'saldo_rol','width'=>50,'formatter'=>'saldos','align'=>'right','classes'=>'columnHighlight1','summaryRound'=>2),        
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
    $obBD_con1->getRolDefaults($_GET, $obBD_conexion);
//    $response=array( 'success'=>true, 'defaults'=>array(), 'anticipos_rol_p'=>array(), 'descuentos_rol_p'=>array(), 'prestamos_rol_p'=>array() );     
//    if(empty($Rol_Fei)) $obBD_con1->echoJson($response);    
//    /* valores default */
//    $defaults=$obBD_con1->getArrayConsulta(30, $Rol_Fei, $obBD_conexion);
//    foreach ($defaults as $d){ $response['defaults'][$d['Rde_Var']]=$d['Rde_Val']; }
//    $response['defaults']['sueldo_bas_medio']=''.(($response['defaults']['sueldo_bas']*1)/2);
//    /* anticipos descuentos */    
//    $anticipos=$obBD_con1->getArrayConsulta(31, $Ses_Emp_Cod.'*'.$Rol_Fei.'*'.$Rol_Fef.'*', $obBD_conexion,true);
//    $descuentos=$obBD_con1->getArrayConsulta(31, $Ses_Emp_Cod.'*'.$Rol_Fei.'*'.$Rol_Fef.'**'.'D', $obBD_conexion,true);
//    if(!empty($anticipos)) $response['anticipos_rol_p']=$anticipos;   
//    if(!empty($descuentos)) $response['descuentos_rol_p']=$descuentos;
//    $obBD_con1->echoJson($response);    
}
if(isset($getData)){ 
    $defaults=$obBD_con1->getArrayConsulta(30, $hoy, $obBD_conexion);
    $response=array(
        'success'=>true,
        'rows'=>$obBD_con1->getArrayConsulta(9,$_GET, $obBD_conexion),
        'defaults'=>array()
    );   
    foreach ($defaults as $d) {
        $response['defaults'][$d['Rde_Var']]=$d['Rde_Val'];
    }
    $response['defaults']['sueldo_bas_medio']=''.(($response['defaults']['sueldo_bas']*1)/2);
    foreach ($response['rows'] as &$v) {
        $v=array_merge($v,$response['defaults']);
        $v['Prs_Ced']=substr($v['Prs_Ced'], 0, 10);
        $v['tiempo_parcial']=($v['Ded_Hrs']*1<8)?'S':'N';
        if(!empty($v['Afi_Fei'])){
            
        }
    } unset($v);
    $obBD_con1->echoJson($response);
} 
if(isset($saveAnticipos)){ 
    $configs = $obBD_con1->getRowConsulta(23, $Ses_Emp_Cod,$obBD_conexion);
    $pec = $obBD_con1->getPerioCont($Ses_Emp_Cod,$Ant_Fec,$obBD_conexion);
    
    $Com_Cod=NULL; $return=array();
    try{        
        if(empty($contratos)||count($contratos)==0) throw new Exception('Debe existis al menos un descuento!');
        
        $obBD_ins1 =  new Class_Log_Datos_Rol;        
        $obBD_conexionIns = new Class_Log_Conexion_Rol($Ses_Dat_Dis);
        $obBD_ins1->inicio_transaccion($obBD_conexionIns);
                     
            foreach($contratos as $j=>&$c){                
                //$obBD_ins1->echoLog(array_merge($c,array('Ant_Fec'=>$Ant_Fec)));
                $obBD_ins1->operacionobBD(32,array_merge($c,array('Ant_Fec'=>$Ant_Fec,'Ant_Tip'=>'D')),$obBD_conexionIns); // inserta los valores de cada anticipo
                $c['Ant_Cod']=$obBD_ins1->insercionid($obBD_conexionIns); 
                array_push($return, array('Con_Cod'=>$c['Con_Cod'],'anticipo_val_saved'=>$c['Ant_Val'],'imprimir'=>array('Ant_Cod'=>$c['Ant_Cod'])));
                               
                foreach($c['Ant_Det'] as $i=>$p){
                   $obBD_ins1->operacionobBD(38,array('Ant_Int'=>$i++,'Ant_Cod'=>$c['Ant_Cod'],'Ant_Val'=>$p['Pag_Val'],'Pag_Cod'=>$p['Pag_Cod'],'Asi_Cod'=>NULL),$obBD_conexionIns);
                } 
            } unset($c);
            
        $obBD_ins1->fin_transaccion_nomsn($obBD_conexionIns);    
    }catch(Exception $e){ $obBD_ins1->rollBack_nomsn($obBD_conexionIns); $responce=array('success'=>false, 'message'=>$e->getMessage()); $obBD_con1->echoJson($responce);  }
    if($obBD_ins1->Error==0){ $responce=array('success'=>true, 'saved'=> $return ); } else{ $responce=array('success'=>false,'message'=>"No se ha logrado realizar la Transaccion",'error'=>$obBD_ins1->MsgError);}      
    $responce['Ant_Link']=baseUrl("../../rrhh/FRONT/rhu_pri_anticipo.php")."?Ant_Cod=";
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
    <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
    <TITLE><?Php echo "Rrhh Descuento Registrar [EXA]"; ?></TITLE>
    <meta charset="UTF-8">
    <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php")?> 
    <script type="text/javascript" src="../../framework/jquery/MonthPicker/jquery.mtz.monthpicker.js"></script>
    <script type="text/javascript" src="../../framework/plugins/moment.min.js"></script>
    <script type="text/ecmascript" src="../VALIDACIONES/rhu_val_roles.js?x=600"></script>
    <style>          
    </style>
</HEAD>
<BODY> 
    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo; Registrar Descuentos</h3></div>        
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            
            <div>
                <div class="row">   
                    <form id="formRol">
                    <div class="col-xs-3">  
                        <fieldset class="exa-fieldset ">                           
                            <legend class="Titulos2">Plantilla Rol</legend>
                            <div class="form-horizontal normal">
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
                            </div>
                        </fieldset>
                    </div>   
                    <div class="col-xs-3">  
                        <fieldset class="exa-fieldset">                           
                            <legend class="Titulos2">Datos Generales</legend>
                            <div class="form-horizontal normal">
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
                            </div>
                        </fieldset>
                    </div>   
                    <div class="col-xs-3">                         
                        <fieldset class="exa-fieldset ranges M Q S BS" style="display: none;">                           
                            <legend class="Titulos2">Rango</legend>
                            <div class="form-horizontal normal">                                
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
                            </div>
                        </fieldset>  
                        
                    </div>
                    <div class="col-xs-3">
                        <fieldset class="exa-fieldset Rol_Range">                           
                            <legend class="Titulos2">Rol</legend>
                            <div class="form-horizontal normal">
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
                            </div>
                        </fieldset> 
                    </div>
                    <div class="col-xs-12">
                        
                    </div>
                    </form>
                    <div class="col-xs-12" >
                        <div class="row">
                            <div class="col-xs-3">
                              <fieldset class="exa-fieldset">                           
                                <legend class="Titulos2">Registrar Descuentos</legend>  
                                <form class="form-horizontal normal" id="formAnticipo" action="javascript:validaAnt();">
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
                            <div class="col-xs-9" id="gridContainer" style="padding-bottom: 8px; min-height: 400px;">
                                <table id="rol"></table><div id="rolPager"></div>
                            </div>    
                        </div>
                    </div>    
                </div>
                <div class="row">   
                    <div class="col-xs-12">
                        <button id="btnGuardar" type="button" onclick="$('#formAnticipo').formSubmit();" class="btn btn-success btn-save"><span class="glyphicon glyphicon-floppy-disk"></span> Guardar</button>
                        <button id="btnReset" type="button" onclick="resetAll();" class="btn btn-primary btn-new" style="display: none;"><span class="glyphicon glyphicon-floppy-disk"></span> Nuevo</button>                        
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
                <div id="tabs-2"><table id="descuentos_rol_p"></table></div>
                <div id="tabs-1"><table id="anticipos_rol_p"></table></div>                
                <div id="tabs-3"><table id="prestamos_rol_p"></table></div>               
            </div>
        </div>    
    </div>
    <!--INICIO DEL DIALOGO IMPRIMIR --> 
    <div id="successDialog"  title="Mensaje del Sistema" style="display: none;">  
        <center><h4>El Comprobante se ha registrado con Exito!</h4></center>
        <center>
            <button type="button" id="impAntic" onclick="$.imprimirUrl($(this).data('url')+$(this).data('Ant_Cod'))" style="display: inline;" title="Imprimir Comprobante de Descuento" class="btn btn-primary"><i class="glyphicon glyphicon-print"></i> Recibo Anticipo </button>
            <button type="button" id="impCompr" onclick="$.imprimirUrl($(this).data('url')+$(this).data('Com_Cod'))" style="display: inline;" title="Imprimir Comprobante de Descuento" class="btn btn-primary"><i class="glyphicon glyphicon-print"></i> Comprob. Anticipo </button>
        </center>
    </div>

    <script type="text/javascript">
    var efectivo=true, tipos_pago=[],bancos=[];  
    function setRolGridCaption(caption){ 
        efectivo=true;
        if($.varValid(caption)){
            caption+='<div class="pull-right pagoRolExtra hidden">';
            caption+='<b>Pagos:</b>&nbsp;<select id="pagoExtra" class="readOnly" onchange="efectivo=this.value===\'\';changeTipoPagos();" disabled="">';
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
            $grid.changeRow(id,{anticipo_val:[],saldo_rol:null},null,true);
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
        return efectivo? '<input type="text" onchange="validaChangeAnt(this);" onkeyup="validaKeyUpAnt(this);" onkeypress="return validar_decimal(event);"  name="anticipo_val" rowid="'+opts['rowId']+'" role="textbox" class="editable inline-edit-cell ui-widget-content ui-corner-all form-control input-xs Ant_Det" placeholder="0.00" style="width: 96%; text-align: right;" data-originaldata="[]" />':
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
    function validaAnt(){
        var rol=$('#rol'), data=$.extend($('#formAnticipo').getData('saveAnticipos'),{contratos:[],Are_Cod:$('#Are_Cod').val(),Cam_Cod:$.arrayGetItem(fields,'Cam_Var','anticipos_rol_p')['Cam_Cod']}), filas=rol.getGridBatch();
        rol.startGridEdit();       
        $.each(filas,function (i,v){
            if($.round(v['anticipo_val']['Ant_Val'])>0){
                if(efectivo) v['anticipo_val']['Ant_Det']=[{Pag_Cod:1,Pag_Des:'Efectivo',Pag_Val:v['anticipo_val']['Ant_Val'],Pag_Pld:data['Pag_Pld'],Pld_Des:data['Pag_Pld_Data']['Pag_Pld_Txt']}];
                data['contratos'].push($.extend({Con_Cod:v['Con_Cod'],Ant_Obs:v['anticipo_obs'],Personal:v['Prs_Ape']+' '+v['Prs_Nom']},v['anticipo_val']));
            }
        });
        if(!data['contratos'].length) return $.alert('Debe ingresar al menos un anticipo!',null,'remove');
        console.log(data);
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
                $.alert('Los descuentos se guardaron con Exito!');
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