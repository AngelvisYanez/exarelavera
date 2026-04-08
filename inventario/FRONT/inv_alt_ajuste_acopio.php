<?php	
/**
* @abstract Permite realizar movimientos de inventario
* @author Erik Niebla
* @version 1.0
* Fecha de creaciï¿½n  2015-07-22
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/inv_log_inventario.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
/**
* Creacion del Objeto de conexion
*/
$obBD_conexion = new Class_Log_Conexion_Inv($Ses_Dat_Dis);
/**
* Creacion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Inv;
$obBD_con1->setConnection($obBD_conexion);

$hoy = date("Y-m-d");
$mes = date("m");
    

//echo days_360("2016-01-01","2016-12-31");
/* Seleccionar El Producto a Producir */
if(isset($proAjax)){
    $responce=$obBD_con1->getPageGrid('producto.selectWhere', array_merge($_GET,array('setWhere'=>array('setSucCod'))) );            
    foreach ($responce['rows'] as &$row) {
        $stock = $obBD_con1->getArrayConsulta('acopio_stk.selectWhere', array('Pro_Cod'=>$row['Pro_Cod'], 'unsetCols'=>true, 'addCols'=>array('acopio_stk'=>'*')) );
        $row['Stocks']=(count($stock)==0?array():$stock); 
        $row['Stocks']=array(array('Aco_Cod'=>8,'Ast_Can'=>100));
    }
    $obBD_con1->echoJson($responce);
}
if(isset($updateNumber)){
    $responce['next'] = $obBD_con1->getRowConsulta(20, $Aju_Tip.'*'.$Ses_Emp_Cod, $obBD_conexion);
    $responce['success']=true;
    $obBD_con1->echoJson($responce);
}
if(isset($saveForm)){
    $resp=array();    
    
    $obBD_con_set = new MysqlDatos(new Class_Log_Conexion_Global($Ses_Dat_Dis));
    //$obBD_con_set->debug(true);
    $obBD_con_set->inicioTransaccion();
    try{       
        
        $obBD_con_set->finTransaccionNoMsn($resp);
    } catch(Exception $e){ $obBD_con_set->rollBackNomsn($e->getMessage(),$resp); }
    $obBD_con_set->echoJson($resp);
}


// consulta de las configuraciones de la empresa
$configuraciones = $obBD_con1->getRowConsulta(4, $Ses_Emp_Cod,$obBD_conexion);

/* Guardar el Formulario */
if(isset($saveForm)){
    //$hoy=$Aju_Fec;
    $Prv_Cod=$obBD_con1->getProveeClie($Ses_Emp_Cod,'Prv_Cod', $obBD_conexion);
    $Cli_Cod=$obBD_con1->getProveeClie($Ses_Emp_Cod,'Cli_Cod', $obBD_conexion);
    // Consulta del vendedor en base al codigo de la persona   
    $rs_vendedor = $obBD_con1->getRowConsulta(5, $Ses_Prs_Cod.'*'.$Ses_Suc_Cod, $obBD_conexion);
    if (count($rs_vendedor) == 0){ $responce=array('success'=>false,'message'=>" Ud. no esta autorizado para realizar ajustes ");echo json_encode($responce);exit(); }
        
    //consulto el codigo secuencial del Tac_Cod 
    $rs_codigo = $obBD_con1->getRowConsulta(6, $Tia_Cod.'*'.$Ses_Emp_Cod, $obBD_conexion);		
     
    $obBD_conexion_Ins = new Class_Log_Conexion_Inv($Ses_Dat_Dis);
    $obBD_ins1 =  new Class_Log_Datos_Inv;
    $obBD_ins1->debug(true);
    $obBD_con1->debug(true);
    $obBD_ins1->inicio_transaccion($obBD_conexion_Ins->conexion); 
        
        // Registrando la cabcera de Ajuste 
        $ajuste = array(
            'Aju_Fec'=>$hoy,
            'Aju_Hor'=>date ("H:i:s"),
            'Vnd_Cod'=>$rs_vendedor['Vnd_Cod'],
            'Prv_Cod'=>$Prv_Cod,
            'Tia_Cod'=>$Tia_Cod,
            'Aju_Num'=>$Aju_Num,
            'Aju_Sec'=>$rs_codigo['Aju_Sec'],
            'Aju_Det'=>$Aju_Det,
            'Aju_Obs'=>$Aju_Obs,
            'Aju_Tip'=>$Tia_IoE
        );
	$obBD_ins1->operacionobBD(7, $ajuste, $obBD_conexion_Ins);
        $Aju_Cod = $obBD_ins1->insercionid($obBD_conexion_Ins->conexion);
        
      
        // recorro los items
        $Com_Val=0;
        $kardex=array(
            'Kar_Fec'=>$hoy,
            'Kar_Hor'=>date ("H:i:s"),
            'Aju_Cod'=>$Aju_Cod,
            'Vnd_Cod'=>$rs_vendedor['Vnd_Cod'],
        );
        $Aju_Int=0;
        foreach ($saveForm AS $row){
            // graba detalle del ajuste
            if(empty($row['Aju_Imp'])) $row['Aju_Imp']=round($row['Aju_Can']*$row['Aju_Pru'],2);
            $row['Aju_Cod']=$Aju_Cod; $Aju_Int++; $row['Aju_Int']=$Aju_Int;
            $obBD_ins1->operacionobBD(8, $row, $obBD_conexion_Ins);
            
            $kardexie=$kardex;
            $kardexie['Pro_Cod']=$row['Pro_Cod'];$kardexie['Iva_Cod']=$row['Iva_Cod'];$kardexie['IoE']=$Tia_IoE;
            $kardexie['Kar_Int']=$Aju_Int;
            if($Tia_IoE=='I'){
                $kardexie['Kar_Can']=$row['Aju_Can'];
                $kardexie['Kar_Prs']=$row['Aju_Pru'];
                $kardexie['Kar_Ims']=$row['Aju_Imp'];
            }else{
                $kardexie['Kar_Sal']=$row['Aju_Can'];
                $kardexie['Kar_Pre']=$row['Aju_Pru'];
                $kardexie['Kar_Ime']=$row['Aju_Imp'];
            }
            // graba el detalle del kardex 
            //$obBD_ins1->operacionobBD(16, $kardexie, $obBD_conexion_Ins);
            
            $Com_Val=$Com_Val+($row['Aju_Imp']*1);
            //actualiza stock
            $obBD_con1->updateStockProd($Ses_Suc_Cod,$kardexie,true,$obBD_conexion,$obBD_conexion_Ins);
        }
        $responce['linkAjust']="../../facturacion/FRONT/fac_pri_aju_1.0.php?Aju_Cod=$Aju_Cod";
//        if($configuraciones['Cof_Con']=='S'){
//            $Pec_Cod=$obBD_con1->getPerioCont($Ses_Emp_Cod,$hoy, $obBD_conexion);
//            if(empty($Pec_Cod['Pec_Cod'])){
//                mysqli_rollback($obBD_conexion->conexion);
//                $responce['success']=false; $responce['message']='No Existe un periodo contable!';
//                echo json_encode($responce); exit();
//            }
//            $Com_Num=$obBD_con1->getComNumAuto($Ses_Emp_Cod,$Tia_Asi,$hoy, $obBD_conexion);
//            // cabecera del comprobant contable
//            $obBD_ins1->operacionobBD(10, $Pec_Cod['Pec_Cod'].'*'.$Prv_Cod.'*'.$Com_Num.'*'.$hoy.'*'.trim($Com_Con).'*'.$Tia_Asi.'*'.$Com_Val.'*'.trim($Aju_Obs).'*'.'Prv_Cod', $obBD_conexion_Inss);
//            $Com_Cod= $obBD_ins1->insercionid($obBD_conexion_Ins->conexion);
//            // Relacion ajuste comprobante
//            $obBD_ins1->operacionobBD(17,$Com_Cod.'*'.$Aju_Cod, $obBD_conexion_Ins);
//                        
//            $responce['linkCompr']="../../contabilidad/FRONT/con_pri_compr_1.1.php?codigo=$Com_Cod&tabla=proveedore&campo=Prv_Cod&tipo=$Tia_Asi&Pec_Cod=$Pec_Cod[Pec_Cod]";
//            $asientosDebe=array();
//            $asientosHaber=array();
//            $asientoPlan=array(
//                    'Com_Cod'=>$Com_Cod,
//                    'Asi_Con'=>$Com_Con,
//                    'Asi_Glo'=>'Ajus. No.'.$rs_codigo['Aju_Sec'],
//            );
//            foreach ($saveForm AS $row){
//               // if(empty($row['Aju_Imp'])) 
//                $row['Aju_Imp']=round($row['Aju_Can']*$row['Aju_Pru'],2);    
//                $cuentaDebe = $obBD_con1->getRowConsulta(12, $row['Pro_Cod'].'*'.($Tia_IoE=='E'?'E':'N'), $obBD_conexion);$addDebe=true;
//                $cuentaHaber = $obBD_con1->getRowConsulta(12, $row['Pro_Cod'].'*'.'C', $obBD_conexion);$addHaber=true;
//                if(empty($cuentaDebe['Pld_Cod'])||empty($cuentaHaber['Pld_Cod'])){
//                    mysqli_rollback($obBD_conexion->conexion);
//                    $responce['success']=false; $responce['message']='Revisar la parametrizacion contable de los productos';
//                    echo json_encode($responce); exit();
//                }
//                // cuenta debe
//                for($i=0,$z=count($asientosDebe);$i<$z;$i++){
//                    if($asientosDebe[$i]['Pld_Cod']==$cuentaDebe['Pld_Cod']){
//                        $asientosDebe[$i]['Asi_Val']=$asientosDebe[$i]['Asi_Val']+$row['Aju_Imp'];
//                        $addDebe=false;
//                    }
//                }
//                if($addDebe){
//                    $debe=array('Asi_Deh'=>($Tia_IoE=='E'?'D':'H'),'Asi_Val'=>$row['Aju_Imp'],'Pld_Cod'=>$cuentaDebe['Pld_Cod']);                    
//                    array_push($asientosDebe,array_merge($asientoPlan,$debe));
//                } 
//                // cuenta haber
//                for($i=0,$z=count($asientosHaber);$i<$z;$i++){
//                    if($asientosHaber[$i]['Pld_Cod']==$cuentaHaber['Pld_Cod']){
//                        $asientosHaber[$i]['Asi_Val']=$asientosHaber[$i]['Asi_Val']+$row['Aju_Imp'];
//                        $addHaber=false;
//                    }
//                }
//                if($addHaber){
//                    $haber=array('Asi_Deh'=>($Tia_IoE=='E'?'H':'D'),'Asi_Val'=>$row['Aju_Imp'],'Pld_Cod'=>$cuentaHaber['Pld_Cod']);                    
//                    array_push($asientosHaber,array_merge($asientoPlan,$haber));
//                } 
//            }
//            $asiento=  array_merge($asientosDebe,$asientosHaber);
//            //var_dump($asiento);
//            foreach ($asiento AS $row)
//                $obBD_ins1->operacionobBD(11,$row,$obBD_conexion_Ins); 
//            
//        }
    
    $obBD_ins1->fin_transaccion_nomsn($obBD_conexion_Ins->conexion);
    if($obBD_ins1->Error==0) $responce['success']=true; else {$responce['success']=false; $responce['message']=$obBD_ins1->MsgError;}
    $obBD_ins1->echoJson($responce);
}
if(!isset($Mov_Tip)) $Mov_Tip="";

$bodegas=$obBD_con1->getArrayConsulta('acopio.selectWhere', array('acopio.Act_Tip'=>'BD','setWhere'=>array('byMyUsuario')), $obBD_conexion);
$tipos=$obBD_con1->getArrayConsulta('ajuste_kardex_tipo.selectWhere', array('Ati_Est'=>'A'));
?>
<!DOCTYPE html>
<HTML>
    <HEAD>		
        <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
        <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php")?>            
        <style>  
            textarea { resize:vertical ; }
            .txtRight{text-align: right;}
            .ui-jqgrid .whiteI{background-color:white !important;}
        </style>  
        <script type="text/ecmascript" src="../VALIDACIONES/fac_val_ajuste_acopio.js"></script>
    </HEAD>
<BODY>
 
    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo; Ajuste de Inventario General</h3></div>
        
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            
                <div class="row">
                   
                    <div class="col-xs-4">
                        <form id="formKardex" class="form-horizontal normal"  action="javascript:validarForm();"  >
                             <input type="text" name="Mov_Tip" value="<?php echo $Mov_Tip; ?>" style="display: none" /> 
                        <fieldset class="exa-fieldset">                           
                           <legend class="Titulos2">Datos del Movimiento:</legend> <!-- Form Name -->
                           <!-- static input-->
                            <!--<div class="form-group" >
                              <label class="col-sm-3 control-label label-xs required">Fecha:</label>  
                              <div class="col-sm-5"> 
                                  <input type="text" id='Aju_Fec' name="Aju_Fec" class="form-control input-xs" />
                               </div>                                  
                            </div> -->
                            <!-- static input-->
                            <div class="form-group">
                              <label class="col-sm-3 control-label label-sm required">Bodega:</label>  
                              <div class="col-sm-9">                                   
                                  <select name="Aco_Cod" id="Aco_Cod" class="form-control input-sm" required="" onchange="kardexGrid.find('select[name=Aco_Cod]').val($(this).val()).trigger('change');">
                                        <option value="">Seleccione..</option>
                                        <?php foreach ($bodegas as $row) { ?>
                                            <option value="<?php echo $row['Aco_Cod']; ?>"><?php echo $row['Aco_Des']; ?></option> 
                                        <?php } ?>
                                  </select>
                              </div>                                  
                            </div>
                            <div class="form-group">
                              <label class="col-sm-3 control-label label-xs required">Movimiento:</label>  
                              <div class="col-sm-9">                                       
                                  <select name="Tia_IoE" id="Tia_IoE" class="form-control input-xs" required="" onchange="/*updateNumber(this.value)*/;">
                                      <option value="">Seleccione..</option>
                                      <option value="I">Ingreso</option>
                                      <option value="E">Egreso</option>
                                  </select>
                              </div>                                  
                            </div>   
                            
                            <div class="form-group">
                              <label class="col-sm-3 control-label label-xs required">Concepto:</label>  
                              <div class="col-sm-9">                                       
                                  <select name="Ati_Cod" id="Ati_Cod" class="form-control input-xs" required="">
                                      <option value="">Seleccione..</option>                                        
                                        <?php foreach ($tipos as $row) { echo "<option value=\"$row[Ati_Cod]\" style=\"display:none;\" Ati_Tip=\"$row[Ati_Tip]\">$row[Ati_Des]</option>";  } ?>
                                  </select>
                              </div>                                  
                            </div>                            
                            <div class="form-group">
                              <label class="col-sm-3 control-label label-xs">Nun.&nbsp;Docu.:</label>  
                              <div class="col-sm-9">   
                                  <input  id="Aju_Num"name="Aju_Num" type="text" class="form-control input-xs"  />                                 
                              </div>                                  
                            </div>
                            <!-- static input-->
                            <div class="form-group">
                              <label class="col-sm-3 control-label label-xs ">Descripción:</label>  
                              <div class="col-sm-9"> 
                                  <textarea name="Aju_Det" class="form-control input-xs" ></textarea>
                              </div>                                  
                            </div>   
                            <!-- static input-->
                            <div class="form-group">
                              <label class="col-sm-3 control-label label-xs ">Observación:</label>  
                              <div class="col-sm-9"> 
                                  <textarea name="Aju_Obs" class="form-control input-xs" ></textarea>
                              </div>                                  
                            </div> 
                            
                        </fieldset> 
                        <?php if($configuraciones['Cof_Con']=='S'){ ?>
<!--                          <fieldset class="exa-fieldset">                           
                           <legend class="Titulos2">Datos del Asiento:</legend>  Form Name      
                            <div class="form-group">
                              <label class="col-sm-3 control-label label-xs required">Tipo&nbsp;Asiento:</label>  
                              <div class="col-sm-9">   
                                  <?php $asien = $obBD_con1->getArrayConsulta(9, '', $obBD_conexion); ?>
                                  <select name="Tia_Asi" id="Tia_Asi" class="form-control input-xs" required="">
                                      <?php foreach ($asien as $row) { ?>
                                            <option value="<?php echo $row['Tia_Cod']; ?>"><?php echo $row['Tia_Des']; ?></option> 
                                      <?php } ?>
                                  </select>
                              </div>                                  
                            </div>
                            static input
                            <div class="form-group">
                              <label class="col-sm-3 control-label label-xs ">Concepto:</label>  
                              <div class="col-sm-9"> 
                                  <textarea name="Com_Con" class="form-control input-xs" ></textarea>
                              </div>                                  
                            </div>  
                           </fieldset>    -->
                            <?php } ?>    
                            <div class="form-group center">
                                <button class="btn btn-success btn-sm btn-frm" type="submit"><span class="glyphicon glyphicon-check" title="Guardar"></span> Guardar</button>
                                <button class="btn btn-success btn-sm btn-new" type="button" onclick="resetForm()" disabled><span class="glyphicon glyphicon-check" title="Nuevo Registro"></span> Nuevo</button>
                            </div>   
                        </form>    
                    </div>    
                    <div class="col-xs-8" style="min-height: 350px;">
                        <table id="prods"></table>
                        <div id="prodsPager"></div>                       
                        <script>
                            
                        </script>    
                    </div>  
                </div> 
        </div>
    </div>

<script>
            function addFilaMat(data){ //console.log(data);
                var grid=$("#prods");
                if(!grid.existsId(data['Pro_Cod'])/*&& data['Pro_Cod']!==$('#Fin_Cod').val()*/){
                    data['Aju_Can']=1;    
                    data['Aco_Cod']=$('#Aco_Cod').val();
                    grid.jqGrid("addRowData", data["Pro_Cod"], data, "last");        
                    //grid.startGridEdit();
                    //kardexGrid.setGridSummary(['Aju_Imp'],{Aju_Pru: '<div style="text-align:right;">TOTAL:</div>'});
                    alertStock();
                }else{ $.alert('Ya se encuentra en el listado!'); }
            }                   
</script>                
<script>
    
</script>
 
<!--INICIO DEL DIALOGO IMPRIMIR -->          
    <div id="successDialog"  title="Mensaje del Sistema">  
        <center><h4>El Ajuste se registrado con exito!</h4></center>
        <center> 
            <button type="button" onclick="$('#successDialog').dialog('close');" class="btn btn-inverse fileinput-button" style="display: inline;" >
                    <i class="icon-ban-circle icon-white"></i>
                    <span>Cerrar</span>
             </button>            
            <a id="impAjust" target="_blank" href=""  style="display: inline;" title="Imprimir Ajuste"><span  class="btn btn-primary start"> <i class="icon-print icon-white"></i> <span>Ajuste</span></span> </a>
<!--            <?php if($configuraciones['Cof_Con']=='S'){ ?>
            <a id="impCompr" target="_blank" href=""  style="display: inline;" title="Imprimir Comprobante"><span  class="btn btn-primary start"> <i class="icon-print icon-white"></i> <span>Comprobante</span></span> </a>
            <?php } ?>   -->
        </center>        
    </div>  

</BODY>
</HTML>