<?php
/**
 * @abstract Permite realizar la cancelacion de comprobantes por abonos
 * @author Erik Niebla
 * @version 1.0
 * Fecha de creaci�n  2015-07-22
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/con_log_docs.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
/**
 * Creacion del Objeto de conexion
 */
$obBD_conexion = new Class_Log_Conexion_Doc($Ses_Dat_Dis);
/**
 * Creaci�n del Objeto para consultas
 */
$obBD_con1 = new Class_Log_Datos_Doc;

$hoy = date("Y-m-d");
$mes = date("m");


if(isset($verificarAnio)){
    
    $data=$_POST;
    $data["Emp_Cod"]=$Ses_Emp_Cod;
    $responce=$obBD_con1->getRowConsulta(27, $data, $obBD_conexion);
    (!empty($responce['Pec_Cod'])) ? $responce['state'] = true : $responce['state'] = false;
    $obBD_con1->echoJson($responce);
}

if(isset($ajaxComp)){ 
   $data=$_GET; $data["Emp_Cod"]=$Ses_Emp_Cod;   
   $responce=$obBD_con1->getPageGrid(1, $data, $obBD_conexion);
   echo json_encode($responce);exit();
}
if(isset($saveData)){ 
    $data=$_POST; 
    $mes=explode('-',$form['Com_Fec']);
    $old_mes=explode('-',$form['Old_Com_Fec']);
    if($form['Tia_Cod']!=$form['Old_Tia_Cod']||$mes[1]!=$old_mes[1])
        $data['form']['Com_Num']=$obBD_con1->getComNumAuto($Ses_Emp_Cod,$form['Tia_Cod'], $form['Com_Fec'], $obBD_conexion);
    else
        $data['form']['Com_Num']=$form['Old_Com_Num'];
    $codigo=$form['Tia_Abr'].'-'.$mes[1].'-'.$data['form']['Com_Num'];
    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
        $obBD_con1->operacionobBD(8,$data['form'],$obBD_conexion);
        if(!empty($asien))
        foreach ($asien as $row) {
             $obBD_con1->operacionobBD(9,$row,$obBD_conexion);
        }
        if(!empty($Cop_Cod)){
            $obBD_con1->operacionobBD(10,$Cop_Cod.'*'.$data['form']['Doc_Obs'],$obBD_conexion);            
        }
        if(!empty($Vet_Cod)){
            $obBD_con1->operacionobBD(13,$Vet_Cod.'*'.$data['form']['Doc_Obs'],$obBD_conexion); 
        }        
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);  
    if($obBD_con1->Error==0){        
        $responce=array('success'=>true, 'codigo'=>$codigo, 'link'=>baseUrl("../../contabilidad/FRONT/con_pri_compr_1.1.php")."?codigo=$Com_Cod&".(!empty($form['Prv_Cod'])?"tabla=proveedore&campo=Prv_Cod":"tabla=cliente&campo=Cli_Cod")."&tipo=$form[Tia_Cod]&Pec_Cod=$form[Pec_Cod]" );
    }else{ $responce=array('success'=>false, 'message'=>'Error al actualizar la informaci&oacute;n!', 'error'=>$obBD_con1->MsgError); }
    echo json_encode($responce);exit();
}
if(isset($saveForm)){
    /* Mes del comprobante */
    $data=$_POST;
    $data['Com_Gen']='M';
    $data['Com_Tip']=$Tia_Ini; 

    if(is_string($cuentas)){
       $cuentas=json_decode(stripslashes($cuentas),true);
    } 
       
    //if(substr($Com_Fec, 0, 7)!==substr($Old_Com_Fec, 0, 7)){
        $data['Com_Num']= $obBD_con1->getComNumPecAuto($Tia_Cod, $Pec_Cod, $Com_Fec, $obBD_conexion); // Secuencia de comprobante por mes y por tipo
    //}else $data['Com_Num']=$Old_Com_Num;
    $codigo=$Tia_Abr.'-'.substr($Com_Fec, 5, 2).'-'.$data['Com_Num'];
    /* Inicio de la transaccion */
    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);       
        $obBD_con1->operacionobBD(23,$data,$obBD_conexion); // Inserci�n del Comprobante       
        $Com_Cod = $obBD_con1->insercionid ($obBD_conexion->conexion);
        //$obBD_con1->operacionobBD(25,$Com_Cod,$obBD_conexion); // elimino asiento
        /* Recorre el arreglo de los datos de las cuentas seleccionadas */
        foreach ($cuentas as $row){                    
            $asiento=array('Com_Cod'=>$Com_Cod, 'Asi_Deh'=>$row['Det_Tip'], 'Asi_Con'=>$row['Pld_Des'], 'Asi_Glo'=>$row['Glosa'], 'Pld_Cod'=>$row['Pld_Cod'], 'Asi_Val'=>$row['Det_Tip']=='D'?$row['Debe']:$row['Haber'] );
            $obBD_con1->operacionobBD(24,$asiento,$obBD_conexion); //guardado de los asientos
        }       
    /* Finaliza la transacci�n */                
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);
    if($obBD_con1->Error==0){
        $responce=array('success'=>true, 'codigo'=>$codigo, 'link'=>"../../contabilidad/FRONT/con_pri_compr_1.1.php?codigo=$Com_Cod");      
    }else{ $responce['success']=false; $responce['error']=$obBD_con1->MsgError; }
    echo json_encode($responce); exit();
}
if(isset($loadData)){ 
    $data=filter_input_array(INPUT_GET);
    $responce['success']=true;    
    $responce['compro']=$obBD_con1->getRowConsulta(3, $data, $obBD_conexion);
    $responce['compro']['detalle']=$obBD_con1->getArrayConsulta(4, $data, $obBD_conexion);
    $responce['cheques']['detalle']=$obBD_con1->getArrayConsulta(21, $data['Com_Cod'], $obBD_conexion);
    $responce['cheques']['conteo']=count($responce['cheques']['detalle']);
    if(!empty($Cop_Cod)){
        $responce['compra']=$obBD_con1->getRowConsulta(5, $data, $obBD_conexion);
        $responce['compra']['detalle']=$obBD_con1->getArrayConsulta(6, $data, $obBD_conexion);
    }
    if(!empty($Vet_Cod)){
        $responce['venta']=$obBD_con1->getRowConsulta(11, $data, $obBD_conexion);
        $responce['venta']['detalle']=$obBD_con1->getArrayConsulta(12, $data, $obBD_conexion);
    }
    utf8_encode_deep($responce); echo json_encode($responce);exit();
}
if(isset($cuen2Ajax)||isset($cuenAjax)){ 
    $data=filter_input_array(INPUT_GET);$data['Emp_Cod']=$Ses_Emp_Cod;
    $responce=$obBD_con1->getPageGrid(7, $data, $obBD_conexion);
    echo json_encode($responce);exit();
}
/* Secci�n para cargar datos en el Jqgrid referente a los proveedores*/
if(isset($persAjax)){ 
   $data=$_GET; $data["Emp_Cod"]=$Ses_Emp_Cod;   
   $responce=$obBD_con1->getPageGrid(19, $data, $obBD_conexion);
   echo json_encode($responce);exit();
}
/*Secci�n para cargar datos en el Jqgrid referente a los proveedores*/
if(isset($provAjax)){ 
   $data=$_GET; $data["Emp_Cod"]=$Ses_Emp_Cod;   
   $responce=$obBD_con1->getPageGrid(17, $data, $obBD_conexion);
   echo json_encode($responce);exit();
}
/*Secci�n para cargar datos en el Jqgrid referente a los clientes*/
if(isset($cliAjax)){ 
   $data=$_GET; $data["Emp_Cod"]=$Ses_Emp_Cod;   
   $responce=$obBD_con1->getPageGrid(18, $data, $obBD_conexion); 
   echo json_encode($responce);exit();
}
?>
<!DOCTYPE html>
<HTML>
    <HEAD>		
        <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
        <TITLE><?Php echo "Comprobantes Duplicar [EXA]"; ?></TITLE>
        <meta charset= "UTF-8">
        <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
        <script type="text/javascript" src="../../framework/jquery/MonthPicker/jquery.mtz.monthpicker.min.js"></script>
        <?php $perio =  $obBD_con1->getRowConsulta(20, $Ses_Emp_Cod, $obBD_conexion); ?> 
        <script type="text/javascript">var alta=false, duplica=true, anula=false, noEdit=true, editing=false, listsearch, compNoEdit, docuView, dataSend=new Array(), pec_min='<?php echo (!empty($perio['menor'])?substr($perio['menor'], 0, 4):'2015'); ?>', pec_max='<?php echo (!empty($perio['mayor'])?substr($perio['mayor'], 0, 4):substr($hoy, 0, 4)); ?>', tipo; </script>   
        <script type="text/javascript" src="../VALIDACIONES/con_val_compr_2.js?x=c"></script>
        <style>                    
            #tabsSearch.ui-widget-content{background:none !important;}    .ui-tabs-panel{padding-bottom: 0 !important;}
        </style>
    </HEAD>
    <BODY>
        <div class="panel panel-main">
            <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Duplicar Comprobantes</h3></div>

            <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div id="main-panel">
                 <fieldset class="exa-fieldset">
                    <legend class="Titulos2">Filtros:</legend>
               <div id="tabsSearch" class="ui-tab-fix ui-tabs">
                <ul class="ui-tabs-nav ui-helper-reset ui-helper-clearfix">
                  <li><a href="#tabs-1">Por Comprobante</a></li>
                  <li><a href="#tabs-2">Por Compras</a></li>  
                  <li><a href="#tabs-3">Por Ventas</a></li>
                </ul>
                <div id="tabs-1">
                    <form id="formComp" class="form-horizontal normal"  action="javascript:listSearch.Search('#formComp','ajaxComp');"   >
                       <input name="order" type="hidden" value="" />
                       <input name="vets" type="hidden" value="" />
                       <input name="cops" type="hidden" value="" />
                       <input name="comp" type="hidden" value="true" />
                       <div class="row">                                  
                            <div class="col-xs-6">
                                <!-- static input-->
                                <div class="form-group">
                                  <label class="col-xs-3 control-label label-xs ">Proveedor/Cliente:</label>  
                                  <div class="col-xs-8" id="persona"> 
                                      <input type="text" name="Prs_Cod" value="" style="display: none" />  
                                      <input type="text" name="Prv_Cod" value="" style="display: none" />  
                                      <input type="text" name="Cli_Cod" value="" style="display: none" /> 
                                      <div class="input-group input-group-xs">
                                        <input id="Cli_Ced" name="Persona"  type="text" class="form-control" placeholder="Seleccione un Proveedor/Cliente ..." required readonly />
                                        <span class="input-group-btn">
                                            <button class="btn btn-success" onclick="$('#persDialog').dialog('open');" type="button"><span class="glyphicon glyphicon-check" title="Buscar Proveedor/Cliente"></span></button>
                                            <button class="btn btn-success" onclick="$('#persona').setData();$('#formComp').formSubmit();" type="button"><span class="glyphicon glyphicon-eject" title="Buscar Proveedor/Cliente"></span></button>
                                        </span>
                                      </div><!-- /input-group -->                       
                                  </div>                                  
                                </div>   
                                <div class="form-group">
                                  <label class="col-xs-3 control-label label-xs ">Tipo Comp.:</label>  
                                  <div class="col-xs-4">                                    
                                      <select  class="form-control input-xs" name="Tia_Ini" id="Tia_Ini" onchange="updateTiaCod(this.value,'Tia_Cod');updateNumCom()">
                                          <option value="">TODOS</option>
                                          <option value="I">INGRESO</option>
                                          <option value="E">EGRESO</option>
                                          <option value="D">DIARIO</option>
                                      </select>
                                  </div>                                  
                                </div>   
                                <div class="form-group">
                                  <label class="col-xs-3 control-label label-xs ">Tipo Asiento:</label>  
                                  <div class="col-xs-8">
                                      <?php  $tiasien =  $obBD_con1->getArrayConsulta(2, '', $obBD_conexion); ?>
                                      <select  class="form-control input-xs" name="Tia_Cod" id="Tia_Cod" onchange="updateNumCom()">
                                          <option value="" class="todos">TODOS</option>
                                          <?php foreach($tiasien as $row){  ?>
                                          <option value="<?php echo $row['Tia_Cod']; ?>" style="display:none" data-type="<?php echo $row['Tia_Ini']; ?>" data-abre="<?php echo $row['Tia_Abr']; ?>"><?php echo $row['Tia_Des']; ?></option>
                                          <?php } ?>
                                      </select>                             
                                  </div>                                  
                                </div>   
                            </div>          
                            <div class="col-xs-6">
                                <!-- static input-->
                                <div class="form-group">
                                  <label class="col-xs-4 control-label label-xs ">Filtrar por:</label>  
                                  <div class="col-xs-8"> 
                                    <div class="radioset">  
                                        <input id="radfil1" name="op_comp" type="radio" value="t" onchange="selectFiltro('t')" alt="" /><label for="radfil1">&nbsp;&nbsp;Todos&nbsp;&nbsp;</label>
                                        <input id="radfil2" name="op_comp" type="radio" value="a" onchange="selectFiltro('a')" alt="" checked="" /><label for="radfil2">&nbsp;&nbsp;Asiento&nbsp;&nbsp;</label>
                                        <input id="radfil3" name="op_comp" type="radio" value="r" onchange="selectFiltro('r')" alt="" /><label for="radfil3">&nbsp;&nbsp;Rango&nbsp;&nbsp;</label>                                     
                                    </div> 
                                  </div>                                  
                                </div> 
                                <div id='todos' class='filtros' style='display:none'>
                                    <div class="form-group"><div class="col-xs-3" style="height: 22px"></div></div>
                                    <div class="form-group">
                                       <div class="col-xs-9"></div>
                                      <div class="col-xs-3"><button type="submit" class="btn btn-xs btn-success" ><i class="glyphicon glyphicon-search"></i> Buscar</button></div>
                                        
                                    </div>
                                </div>
                                <div id='asien' class='filtros'>
                                    <!-- static input-->
                                    <div class="form-group">
                                      <label class="col-xs-4 control-label label-xs ">Periodo/Mes:</label>  
                                      <div class="col-xs-3">                                    
                                          <input type="text"  class="form-control input-xs" name="Month" id="Month"></span>                              
                                      </div>                                  
                                    </div>   
                                    <!-- static input-->
                                    <div class="form-group">
                                      <label class="col-xs-4 control-label label-xs ">Num. Comprobante:</label>  
                                      <div class="col-xs-3">                                    
                                          <div class="input-group input-group-xs">
                                            <span class="input-group-addon" id="Com_Num"> # </span>
                                            <input class="form-control input-xs" name="Com_Num"  type="text" style="text-align:right" onkeypress="return  validar_decimal(event)"  />
                                          </div>                               
                                      </div>
                                      <div class="col-xs-2"></div>
                                      <div class="col-xs-3"><button type="submit" class="btn btn-xs btn-success" ><i class="glyphicon glyphicon-search"></i> Buscar</button></div>
                                    </div> 
                                </div>
                                <div id='rangos' class='filtros' style='display:none'>
                                    <!-- static input-->
                                    <div class="form-group">
                                      <label class="col-xs-4 control-label label-xs ">Desde:</label>  
                                      <div class="col-xs-3">                                    
                                          <input type="text"  class="form-control input-xs" name="Asi_Ini" id="Asi_Ini"></span>                              
                                      </div>                                        
                                    </div>   
                                    
                                    <!-- static input-->
                                    <div class="form-group">
                                        <label class="col-xs-4 control-label label-xs ">Hasta:</label>  
                                        <div class="col-xs-3">                                    
                                            <input type="text"  class="form-control input-xs" name="Asi_Fin" id="Asi_Fin"></span>                              
                                        </div>    
                                        <div class="col-xs-2"></div>
                                       <div class="col-xs-3"><button type="submit" class="btn btn-xs btn-success" ><i class="glyphicon glyphicon-search"></i> Buscar</button></div>
                                    </div>  
                                </div>
                            </div>
                       </div> 
                       
                       
                    </form>                  
                </div>
                <div id="tabs-2" style="display: none;">
                   <form id="formCops" class="form-horizontal normal"  action="javascript:listSearch.Search('#formCops','ajaxComp');"   >
                       <input name="order" type="hidden" value="" />
                       <input name="vets" type="hidden" value="" />
                       <input name="cops" type="hidden" value="true" />
                       <input name="comp" type="hidden" value="" />
                       <div class="row"> 
                           <div class="col-xs-6">
                                <div class="form-group">
                                  <label class="col-xs-3 control-label label-xs ">Proveedor:</label>  
                                  <div class="col-xs-8" id="proveedor" > 
                                      <input type="hidden" name="Prv_Cod" id="Prv_Cod" value=""/>  
                                      <div class="input-group input-group-xs">                                                
                                        <input name="proveedor"  type="text" class="form-control" placeholder="Seleccione un Proveedor ..." required readonly />
                                        <span class="input-group-btn">
                                            <button class="btn btn-success" onclick="$('#provDialog').dialog('open');" type="button"><span class="glyphicon glyphicon-check" title="Buscar Proveedor"></span></button>
                                            <button class="btn btn-success" onclick="selectProv({});" type="button"><span class="glyphicon glyphicon-eject" title="Limpiar Campo"></span></button>
                                        </span>
                                       
                                      </div><!-- /input-group -->                      
                                  </div>                                  
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs">Tipo Doc.:</label>
                                    <div class="col-xs-8">
                                        <select name="Tic_Cod" class="form-control input-xs">
                                            <option value="" class="todos">TODOS</option>
                                            <?PHP $rs_tipo_comprobante=$obBD_con1->getArrayConsulta(14,'',$obBD_conexion);
                                            foreach ($rs_tipo_comprobante as $row){?>
                                            <option value="<?php echo $row["Tic_Cod"];?>"><?php echo $row["Tic_Des"];?></option>
                                            <?PHP }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs">Nro. Documento:</label>
                                    <div class="col-xs-8">
                                        <input type="text" name="Cop_Num" id="Cop_Num" class="form-control input-xs">
                                    </div>
                                </div>
                            </div>
                            <div class="col-xs-4">
                                <fieldset class="exa-fieldset">
                                    <legend class="Titulos2">Establecer Rango</legend>
                                    <div class="form-group">                                        
                                        <div class="col-xs-12">
                                            <div class="input-group input-group-sm"><span class="input-group-addon "><span class=""><input type="checkbox" name="chk_sr" id="chk_sr" class="check-big"></span></span><span class="input-group-addon alert-info">Desde</span><input type="text" name="Cop_Ini" id="Cop_Ini" class="form-control"/><span class="input-group-addon alert-info">Hasta</span><input type="text" name="Cop_Fin" id="Cop_Fin" class="form-control"/></div>
                                        </div> 
                                    </div>
                                </fieldset>
                            </div>
                            <div class="col-xs-2">
                                <div class="form-group">
                                    <div class="col-xs-3" style="padding-top: 20px;"><button type="submit" class="btn btn-xs btn-success" ><i class="glyphicon glyphicon-search"></i> Buscar</button></div>
                                </div>
                            </div>
                        </div>
                    </form>    
                </div>
                <div id="tabs-3" style="display: none;">
                   <form id="formVets" class="form-horizontal normal"  action="javascript:listSearch.Search('#formVets','ajaxComp');"   >
                        <input name="order" type="hidden" value="" />
                        <input name="vets" type="hidden" value="true" />
                        <input name="cops" type="hidden" value="" />
                        <input name="comp" type="hidden" value="" />
                        <div class="row"> 
                           <div class="col-xs-6">
                                <div class="form-group">
                                  <label class="col-xs-3 control-label label-xs ">Cliente:</label>  
                                  <div class="col-xs-8" id="cliente">     
                                      <input type="hidden" name="Cli_Cod"  value=""/>
                                      <div class="input-group input-group-xs" id="cliente">                                                
                                        <input name="cliente"  type="text" class="form-control" placeholder="Seleccione un Cliente ..." required readonly />
                                        <span class="input-group-btn">
                                            <button class="btn btn-success" onclick="$('#cliDialog').dialog('open');" type="button"><span class="glyphicon glyphicon-check" title="Buscar Cliente"></span></button>
                                            <button class="btn btn-success" onclick="selectClie({});" type="button"><span class="glyphicon glyphicon-eject" title="Limpiar Campo"></span></button>
                                        </span>
                                       
                                      </div><!-- /input-group -->                      
                                  </div>                                  
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs">Tipo Doc.:</label>
                                    <div class="col-xs-8">
                                        <select name="Tic_Cod" class="form-control input-xs">
                                            <option value="" class="todos">TODOS</option>
                                            <?PHP $rs_tipo_comprobante=$obBD_con1->getArrayConsulta(14,'',$obBD_conexion);
                                            foreach ($rs_tipo_comprobante as $row){?>
                                            <option value="<?php echo $row["Tic_Cod"];?>"><?php echo $row["Tic_Des"];?></option>
                                            <?PHP }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs">Nro. Documento:</label>
                                    <div class="col-xs-8">
                                        <input type="text" name="Vet_Num" id="Vet_Num" class="form-control input-xs">
                                    </div>
                                </div>
                            </div>
                            <div class="col-xs-4">
                                <fieldset class="exa-fieldset">
                                    <legend class="Titulos2">Establecer Rango</legend>
                                    <div class="form-group">                                        
                                        <div class="col-xs-12">
                                            <div class="input-group input-group-sm"><span class="input-group-addon "><span class=""><input type="checkbox" name="chk_sr1" id="chk_sr1" class="check-big"></span></span><span class="input-group-addon alert-info">Desde</span><input type="text" name="Ven_Ini" id="Ven_Ini" class="form-control"/><span class="input-group-addon alert-info">Hasta</span><input type="text" name="Ven_Fin" id="Ven_Fin" class="form-control"/></div>
                                        </div> 
                                    </div>                                    
                                </fieldset>
                            </div>
                            <div class="col-xs-2">
                                <div class="form-group" style="padding-top: 20px;">
                                    <div class="col-xs-3"><button type="submit" class="btn btn-xs btn-success" ><i class="glyphicon glyphicon-search"></i> Buscar</button></div>
                                </div>
                            </div>  
                        </div>
                    </form>    
                </div>
              </div>
                </fieldset> 
                <div style="min-height: 350px;">
                    <table id="listsearch"></table>
                    <div id="listsearchPager"></div>
                    <div class="Titulos2"><span id="plan-footer"><strong>Leyenda:</strong> <span class="glyphicon glyphicon-remove red"></span> Anulados/Inactivos | <span class="glyphicon glyphicon-arrow-right white" style="background-color: #ff892a!important;height: 12px;width: 14px;text-align: center;"></span> Generacion Automatica(Valores no editables)</span></div>
                </div>    
            </div>        
           
            <div id="modificar-panel" style="display: none;">
                <div class="row">
                    <div class="col-sm-12">
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Comprobante de <span id="title_comp">Ingreso</span></legend>	
                            <form name="formCompConta" id="formCompConta" method="post" action="javascript:validaComp()" class="form-horizontal normal">                
                                <input type="hidden" value="" name="Pec_Cod" id="Pec_Cod" />
                                <input type="hidden" value="" name="Old_Pec_Cod" id="Old_Pec_Cod" />
                                
                                <!--<input type="hidden" value="" name="Com_Cod" />-->                               
                                <input type="hidden" value="" name="Old_Com_Fec" id="Old_Com_Fec"/>                                
                                <div class="col-xs-6">
                                    <div class="form-group">
                                      <label class="col-sm-4 control-label label-xs ">Tipo&nbsp;Comprobante:</label>  
                                      <div class="col-sm-3"> 
                                          <select class="form-control input-xs readOnly" name="Tia_Ini" disabled="">
                                            <option value="I">INGRESO</option>
                                            <option value="E">EGRESO</option>
                                            <option value="D">DIARIO</option>
                                          </select>                                          
                                      </div>                                  
                                    </div>
                                    <div class="form-group">
                                        <label class="col-xs-4 control-label label-xs required">Tipo&nbsp;Asiento:</label> 
                                        <div class="col-xs-8">                                            
                                            <select class="form-control input-xs"  id="Tia_Cod_Comp" name="Tia_Cod"  class="isSelectMenu" required>
                                                <option value="">Seleccione...</option>
                                                <?php foreach($tiasien as $row){  ?>
                                                <option value="<?php echo $row['Tia_Cod']; ?>" style="display:none" data-type="<?php echo $row['Tia_Ini']; ?>" data--tia_-cod="<?php echo $row['Tia_Cod']; ?>"  data--tia_-abr="<?php echo $row['Tia_Abr']; ?>" data--tia_-des="<?php echo $row['Tia_Des']; ?>" ><?php echo $row['Tia_Des']; ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group persona cliente">
                                        <label class="col-xs-4 control-label label-xs required">Cliente:</label> 
                                        <div class="col-xs-8"> 
                                            <input type="hidden" id="cod_cli" name="Cli_Cod" value="" data-name='Cli_Cod' /> 
                                            <div class="input-group input-group-xs">
                                                <input id="lblClie" name="cliente" data-name="cliente" onkeydown='if (event.keyCode === 13) buscaCliente();' onchange="if($('#lblClie').val()==='')$('#cod_cli').val('');" class="form-control varios clearable" placeholder="Ingrese Cliente"  />
                                                <span class="input-group-btn"><a onclick="$('#cliDialog').dialog('open')" title="B&uacute;squeda de Clientes" class="btn btn-success btn-mini"><i class="glyphicon glyphicon-check"></i></a></span>
                                            </div><!-- /input-group -->  
                                        </div>
                                    </div> 
                                    <div class="form-group persona proveedor" style="display: none;">
                                        <label class="col-xs-4 control-label label-xs required">Proveedor:</label> 
                                        <div class="col-xs-8">
                                            <input type="hidden" id="cod_pvr" name="Prv_Cod" value="" data-name="Prv_Cod" /> 
                                            <div class="input-group input-group-xs">
                                                <input id="lblProvee" name="proveedor" data-name="proveedor" onkeydown='if (event.keyCode === 13) buscaProvee();' onchange="if($('#lblProvee').val()==='')$('#cod_pvr').val('');" class="form-control varios clearable" placeholder="Ingrese Proveedor"  />
                                                <span class="input-group-btn"><a onclick="$('#provDialog').dialog('open')" title="B&uacute;squeda de Proveedores" class="btn btn-success btn-mini"><i class="glyphicon glyphicon-check"></i></a></span>
                                            </div><!-- /input-group -->  
                                        </div>
                                    </div> 
                                    <div class="form-group">
                                        <label class="col-xs-4 control-label label-xs">Concepto:</label> 
                                        <div class="col-xs-8"><textarea class="form-control input-xs" name="Com_Con" id="Con_Con" cols="73" style="text-transform:uppercase" onkeypress="return  validar_injections(event)" required></textarea></div>
                                    </div> 
                                    <div class="form-group">
                                        <label class="col-xs-4 control-label label-xs">Observación:</label> 
                                        <div class="col-xs-8"><textarea class="form-control input-xs" name="Com_Obs" cols="73" style="text-transform:uppercase" onkeypress="return  validar_injections(event)"></textarea></div>
                                    </div> 
                                </div>
                                <div class="col-xs-6">
                                    <div class="form-group">
                                        <label class="col-xs-2 control-label label-xs required">Fecha:</label> 
                                        <div class="col-xs-5"><input id="Com_Fec" name="Com_Fec" onchange="if(duplica)verificarAnio();" type="text" style="text-align: center" size="10" maxlength="10" class="form-control input-xs" required /></div>
                                    </div> 
                                    
                                    <div class="form-group">
                                        <label class="col-xs-2 control-label label-xs required">Valor:</label> 
                                        <div class="col-xs-5">
                                            <div class="input-group input-group-xs"><span class="input-group-addon">$</span><input class="form-control input-xs" name="Com_Val" id="Com_Val"  onchange=" updateValores()" type="text" size="10" maxlength="12" style="text-align:right" onkeypress="return  validar_decimal(event)" required placeholder="0.00" /></div></div>
                                    </div>                        
                                </div>
                            </form>
                        </fieldset>
                    </div>
                    <div class="col-sm-12">
                        <div id="compGrilla" style="padding-top: 6px; padding-bottom: 6px; ">   
                            <table id="compAsien"></table>
                            <div id="compAsienPager"></div>
                        </div>
                        <button onclick="editing=false; $('#modificar-panel').moveComp('#main-panel').updateGridsSizes();" class="btn btn-sm btn-inverse" title="Volver Atrás"><i class="glyphicon glyphicon-arrow-left"></i><span>&nbsp;&nbsp;Atrás&nbsp;&nbsp;</span></button><span>&nbsp;</span>
                        <button onclick="$('#formCompConta').formSubmit();" type="button" class="btn btn-success btn-sm" title="Guardar Cambios" ><i class="glyphicon glyphicon-floppy-disk"></i><span> Guardar</span></button>
                    </div>
                </div>
            </div>
            </div>
        </div>
        
        <script>
            function SelectCta(cta){  addFilaCuenta($.getDialogGrid("#cuenDialog").jqGrid('getRowData', cta['Pld_Cod']), cta['tipo'] );  } 
            function SelectCta2(data){ compNoEdit.changeRow($('input[name=Asi_Cod]').val(),$.extend(data,{act1:'Yes'})); $('#cuen2Dialog').dialog('close'); }
            function selectPerss(data){ $('#persona').setData(data); $('#persDialog').dialog('close'); $('#formComp').formSubmit(); }
            function selectProv(prov){ if(editing)$(".persona.proveedor").setData(prov); else{ $("#proveedor").setData(prov);  $('#formCops').formSubmit(); } $("#provDialog").dialog("close"); }        
            function selectClie(clie){ if(editing)$(".persona.cliente").setData(clie); else{ $("#cliente").setData(clie); $('#formVets').formSubmit(); } $("#cliDialog").dialog("close"); }   
            function buscaCliente(){ $.SearchOrDialogArray("#cliDialog",selectClie, {'search':$('#lblClie').val(),'op_opciones':'c'}); selectClie({}); }  
            function buscaProvee(){ $.SearchOrDialogArray("#provDialog",selectProv,{'search':$('#lblProvee').val(),'op_opciones':'c'}); selectProv({}); }
        </script>

    <!--INICIO DEL DIALOGO BUSCAR CUENTA--> 
    <div id="cuenDialog" title="B&uacute;squeda de Cuentas" style="display: none"></div>
    <div id="cuen2Dialog" title="B&uacute;squeda de Cuentas"></div> 
    <div id="chequesDialog" title="Cheques"></div>
    <!--INICIO DEL DIALOGO BUSCAR CLIENTES--> 
    <div id="cliDialog" title="B&uacute;squeda de Clientes"></div>
    <!--INICIO DEL DIALOGO BUSCAR PROVEEDORES--> 
    <div id="provDialog" title="B&uacute;squeda de Proveedores"></div>
    <!--INICIO DEL DIALOGO BUSCAR PROVEEDORES--> 
    <div id="persDialog" title="B&uacute;squeda de Proveedores/Clientes"></div>
    <!--INICIO DEL DIALOGO SUCCESS--> 
    <div id="successDialog" title="Mensaje del Sistema" style="display: none;">
        <center>
            <b style="font-size:14px;">Se ha Duplicado con Exito!</b>
            <h4><b class="blue">Asiento: </b><span class="orange" id="successCodigo">dd-55-55</span></h4>
            <button id="btnImpCompr" type="button" class="btn btn-info" onclick="$.imprimirUrl($(this).data('url'))"><i class="glyphicon glyphicon-print"></i> Imprimir Comprobante</button>
        </center>
    </div>      
    <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=1"></script>
    <link type="text/css" rel="stylesheet" href="../../mascaras/model1/estilos/print.css" media="print" />  
  </BODY>
</HTML>