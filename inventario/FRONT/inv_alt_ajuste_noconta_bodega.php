<?php	
/**
* @abstract Permite realizar movimientos de inventario
* @author Erik Niebla
* @version 1.0
* Fecha de creaci�n  2015-07-22
* Fecha actualizacion: 2022-11-10 
* @author Lewis Chimarro
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

$hoy = date("Y-m-d");
$mes = date("m");

if(isset($proAjax)){
    //$responce=$obBD_con1->getPageGrid(1, $search.'*'.$Ses_Emp_Cod.'*'.$op_opciones, $obBD_conexion, $page, $rows,true);
    $responce=$obBD_con1->getPageGrid(39, $search.'*'.$Ses_Emp_Cod.'*'.$op_opciones.'*'.$Suc_Cod, $obBD_conexion, $page, $rows,true);
    $ini = "9999-01-01";
    foreach ($responce['rows'] as &$row) {
        $stock = $obBD_con1->getRowConsulta(2, $Ses_Suc_Cod.'*'.$row['Pro_Cod'], $obBD_conexion);      
        
      //CALCULO DESDE KARDEX
      $saldoInicial = $obBD_con1->getArrayConsulta(10488,$ini.'*'.$row['Pro_Cod'], $obBD_conexion);
      if(count($saldoInicial)>0){ 
        $x=COUNT($saldoInicial);
        for($i=1;$i<$x;$i++){
            if($saldoInicial[$i]['Kar_Sal']*1!=0){
                $saldoInicial[$i]['Kar_Pre']=$saldoInicial[$i-1]['Promedio'];
                $saldoInicial[$i]['Kar_Ime']=$saldoInicial[$i]['Kar_Pre']*$saldoInicial[$i]['Kar_Sal'];
            }
            $saldoInicial[$i]['Stock']=$saldoInicial[($i-1)]['Stock']*1+$saldoInicial[$i]['Kar_Can']*1-$saldoInicial[$i]['Kar_Sal'];
            $saldoInicial[$i]['Saldo']= ($saldoInicial[($i-1)]['Saldo']*1) + ($saldoInicial[$i]['Kar_Ims']*1) - ($saldoInicial[$i]['Kar_Ime']);
            $saldoInicial[$i]['Promedio']=($saldoInicial[$i]['Stock']!=0?$saldoInicial[$i]['Saldo']/$saldoInicial[$i]['Stock']:$saldoInicial[($i-1)]['Promedio']);
            }
            $kardex1[0]['Promedio'] = $saldoInicial[$x-1]['Promedio'];
            $kardex1[0]['Saldo'] = $saldoInicial[$x-1]['Saldo'];
            $kardex1[0]['Stock'] = $saldoInicial[$x-1]['Stock'];           
      }else{
        $kardex1[0]['Promedio']=0;$kardex1[0]['Saldo']=0;$kardex1[0]['Stock']=0;
      }

        $row['Aju_Imp']=$row['Aju_Pru']= round($kardex1[0]['Promedio'],5); 
        $row['Stk_Can']=$kardex1[0]['Stock']; 
        $row['Ite_Lar']=$row['Ite_Lar'].' - '.$row['Ite_Cor'].' - '.$row['Pro_Obs'];         
    }
    $obBD_con1->echoJson($responce);
}

if(isset($TiaSelect)){
    $rs_tpaj= $obBD_con1->getArrayConsulta(3, $Ses_Emp_Cod.'*'.$TiaSelect, $obBD_conexion);
    echo "<option value=''>Selecione...</option>";
    foreach ($rs_tpaj as $row) 
        echo mb_convert_encoding("<option value='$row[Tia_Cod]'>$row[Tia_Des]</option>", 'UTF-8', 'ISO-8859-1');        
    exit();
    
}
if(isset($updateNumber)){
    $responce['next'] = $obBD_con1->getRowConsulta(20, $Aju_Tip.'*'.$Ses_Emp_Cod, $obBD_conexion);
    $responce['success']=true;
    $obBD_con1->echoJson($responce);
}

/*if(isset($descripcion)){
   $desc = $obBD_con1->getRowConsulta(27,$Tia_Cod, $obBD_conexion);  
   return $desc;
}*/

// consulta de las configuraciones de la empresa
$configuraciones = $obBD_con1->getRowConsulta(4, $Ses_Emp_Cod,$obBD_conexion);


/* Guardar el Formulario */
if(isset($saveForm)){
    $hoy=$Aju_Fec;
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
    $tranf = $obBD_con1->getRowConsulta(28, $Tia_Cod, $obBD_conexion);
    if($tranf['tia_des']=='Transferencia') 
    {
    $control= 0;
      do{
      //if ($Bod_Ori>0) console.log('prueba de videos');  
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
          
          
          //ChromePhp::log($tranf);
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
                  $kardexie['Bod_Cod']=$Bod_Des;
              }else{
                  $kardexie['Kar_Sal']=$row['Aju_Can'];
                  $kardexie['Kar_Pre']=$row['Aju_Pru'];
                  $kardexie['Kar_Ime']=$row['Aju_Imp'];
                  $kardexie['Bod_Cod']=$Bod_Ori;
              }
              // graba el detalle del kardex 
              //$obBD_ins1->operacionobBD(16, $kardexie, $obBD_conexion_Ins);
              
              $Com_Val=$Com_Val+($row['Aju_Imp']*1);
              //actualiza stock
              $obBD_con1->updatenoStockProd($Ses_Suc_Cod,$kardexie,true,$obBD_conexion,$obBD_conexion_Ins,$kardexie['Bod_Cod']);
             
          }
           $control++;
           $Tia_IoE='I';
           
        }while ($control<=1); 
      }else{
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
          $bod_pri = $obBD_con1->getRowConsulta(27, $Ses_Suc_Cod, $obBD_conexion);
          
          //ChromePhp::log($bod_pri);
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
                  $kardexie['Bod_Cod']=$Bod;
              }else{
                  $kardexie['Kar_Sal']=$row['Aju_Can'];
                  $kardexie['Kar_Pre']=$row['Aju_Pru'];
                  $kardexie['Kar_Ime']=$row['Aju_Imp'];
                  $kardexie['Bod_Cod']=$Bod;
              }
              // graba el detalle del kardex 
              //$obBD_ins1->operacionobBD(16, $kardexie, $obBD_conexion_Ins);
              
              $Com_Val=$Com_Val+($row['Aju_Imp']*1);
              //actualiza stock
              $obBD_con1->updateBodegaStockProd($Ses_Suc_Cod,$kardexie,true,$obBD_conexion,$obBD_conexion_Ins,$Bod);
             
          }
      }    

      $responce['linkAjust']="../../facturacion/FRONT/fac_pri_aju_1.0.php?Aju_Cod=$Aju_Cod";

    
    $obBD_ins1->fin_transaccion_nomsn($obBD_conexion_Ins->conexion);
    if($obBD_ins1->Error==0) $responce['success']=true; else {$responce['success']=false; $responce['message']=$obBD_ins1->MsgError;}
    $obBD_ins1->echoJson($responce);
}
if(!isset($Mov_Tip)) $Mov_Tip="";


$bodegas = $obBD_con1->getArrayConsulta(26, array('usu_cod'=>$Ses_Usu_Cod,'Emp_Cod'=>$Ses_Suc_Cod),$obBD_conexion);

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
        <script type="text/ecmascript" src="../VALIDACIONES/fac_val_ajuste.js"></script>
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
                            <div class="form-group" >
                              <label class="col-sm-3 control-label label-xs required">Fecha:</label>  
                              <div class="col-sm-5"> 
                                  <input type="text" id='Aju_Fec' name="Aju_Fec" class="form-control input-xs" />
                               </div>                                  
                            </div> 
                            <div class="form-group">
                              <label class="col-sm-3 control-label label-xs required">Movimiento:</label>  
                              <div class="col-sm-9">                                       
                                  <select name="Tia_IoE" id="Tia_IoE" class="form-control input-xs" required="" onchange="alertStock();/*updateNumber(this.value)*/;">
                                      <option value="">Seleccione..</option>
                                      <option value="I">Ingreso</option>
                                      <option value="E">Egreso</option>
                                  </select>
                              </div>                                  
                            </div>   

                            <div class="form-group">
                              <label class="col-sm-3 control-label label-xs required">Concepto:</label>  
                              <div class="col-sm-9">                                       
                                  <select name="Tia_Cod" id="Tia_Cod" class="form-control input-xs" required="">
                                      <option value="">Seleccione..</option>                                      
                                  </select>
                              </div>                                  
                            </div>
                            
                            <div class="" style="display: none" id="ingegre">
                            <div class="form-group">
                              <label class="col-sm-3 control-label label-xs required">Bodega:</label>  
                              <div class="col-sm-9">                                       
                                  <select name="Bod" id="Bod" class="form-control input-xs" required="">
                                      <?php 
                                        foreach ($bodegas as $bod) {
                                            echo "<option value='{$bod['Bod_Cod']}' data-tipo='{$bod['Bod_Tip']}'>{$bod['Bod_Nom']}</option>";
                                        }
                                        ?>                                    
                                  </select>
                              </div>                                  
                            </div>
                           </div> 

                            <div class="" style="display: none" id="transferencia">
                            <div class="form-group">
                              <label class="col-sm-3 control-label label-xs required">Bodega Origen:</label>  
                              <div class="col-sm-9">                                       
                                  <select name="Bod_Ori" id="Bod_Ori" class="form-control input-xs" required="">
                                      <?php 
                                        foreach ($bodegas as $bod) {
                                            echo "<option value='{$bod['Bod_Cod']}' data-tipo='{$bod['Bod_Tip']}'>{$bod['Bod_Nom']}</option>";
                                        }
                                        ?>                                    
                                  </select>
                              </div>                                  
                            </div>

                            <div class="form-group">
                              <label class="col-sm-3 control-label label-xs required">Bodega Destino:</label>  
                              <div class="col-sm-9">                                       
                                  <select name="Bod_Des" id="Bod_Des" class="form-control input-xs" required="">
                                      <?php 
                                        foreach ($bodegas as $bod) {
                                            echo "<option value='{$bod['Bod_Cod']}' data-tipo='{$bod['Bod_Tip']}'>{$bod['Bod_Nom']}</option>";
                                        }
                                        ?>                                      
                                  </select>
                              </div>                                  
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
                              <label class="col-sm-3 control-label label-xs ">Descripci&oacute;n:</label>  
                              <div class="col-sm-9"> 
                                  <textarea name="Aju_Det" class="form-control input-xs" ></textarea>
                              </div>                                  
                            </div>   
                            <!-- static input-->
                            <div class="form-group">
                              <label class="col-sm-3 control-label label-xs ">Observaci&oacute;n:</label>  
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
<!--INICIO DEL DIALOGO BUSCAR CUENTA--> 
    <div id="proDialog" title="B&uacute;squeda de Productos">  
        <form class="form-horizontal normal"> 
            <fieldset class="exa-fieldset">
                <legend class="Titulos2">Filtros</legend>
                <div class="form-group">
                    <label class="col-md-2 control-label label-xs">Filtrar Por:</label>  
                    <div class="col-md-5 radioset" >
                          <input id="radc1" name="op_opciones" type="radio" value="d" checked="" onclick="setfocus(this.form.search)" alt="" /><label for="radc1">&nbsp;&nbsp;Descripci&oacute;n&nbsp;&nbsp;</label>
                          <input id="radc2" name="op_opciones" type="radio" value="c" onclick="setfocus(this.form.search)" alt="" /><label for="radc2">&nbsp;&nbsp;C&oacute;digo&nbsp;&nbsp;</label>                          
                    </div>                  
                       
             </div>

<div class="form-group">
    <label class="col-md-2 control-label label-xs">Bodega:</label>  
    <div class="col-md-5" >
        <?php 
            $bodegas = $obBD_con1->getArrayConsulta(38, array('Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion); 
        ?>
        <select name="Bod_Cod" id="Bod_Cod" class="form-control input-xs" required="">
            <?php 
                foreach ($bodegas as $bodega) { 
                    $selected = $bodega['Bod_Cod'] == $Ses_Bod_Cod ? 'selected' : '';
                    echo '<option value="' . $bodega['Bod_Cod'] . '" ' . $selected . '>';
                    echo $bodega['Bod_Des'];
                    echo '</option>';
                } 
            ?>
        </select>                    
    </div>
</div>
                <div class="form-group">
                    <label class="col-md-2 control-label">B&uacute;squeda:</label>  
                    <div class="col-md-7" >
                        <div class="input-group">
                          
                        <input type="hidden" id="codigoBodega" name="bodega"  value="">  

                        <input name="search" onkeydown="if (event.keyCode === 13) this.form.submit()" type="text" size="50" maxlength="50" placeholder="Ingrese b&uacute;squeda..." autofocus  class="form-control input-sm "/>
                        <span class="input-group-btn"><button type="button" id="btnbuscar" onclick="this.form.submit()" class="btn btn-success btn-sm" title="Buscar Producto" ><span class="glyphicon glyphicon-search"></span> <span>Buscar</span></button></span>
                      </div><!-- /input-group --> 
                    </div>                    
                </div>
        </fieldset>  
       </form> 
    </div> 
<!-- FIN DEL DIALOGO CUENTAS--> 
<script>
            function addFilaMat(data){ //console.log(data);
                var grid=$("#prods");
                if(!grid.existsId(data['Pro_Cod'])/*&& data['Pro_Cod']!==$('#Fin_Cod').val()*/){
                    data['Aju_Can']=1;                 
                    grid.jqGrid("addRowData", data["Pro_Cod"], data, "last");        
                    grid.startGridEdit();
                    kardexGrid.setGridSummary(['Aju_Imp'],{Aju_Pru: '<div style="text-align:right;">TOTAL:</div>'});
                }else{ $.alert('Ya se encuentra en el listado!'); }
            }                   
</script> 

<script>
    $("#Tia_Cod").change(function(){
       var elt = document.getElementById("Tia_Cod");
       var tipo = elt.options[elt.selectedIndex].text;
      if(tipo=='Transferencia'){
        $("#transferencia").css({"display":"block"})
      }else{
        $("#transferencia").css({"display":"none"})
      }
    }); 
</script>

<script>
    $("#Tia_Cod").change(function(){
       var elt = document.getElementById("Tia_Cod");
       var tipo = elt.options[elt.selectedIndex].text;
      if(tipo=='Ajuste Inventario' || tipo=='Consumo'){
        $("#ingegre").css({"display":"block"})
      }else{
        $("#ingegre").css({"display":"none"})
      }
    }); 
</script>
<script>
    $("#Tia_Cod").change(function(){
      var elt = document.getElementById("Tia_Cod");
      var tipo = elt.options[elt.selectedIndex].text;
      if(tipo=='Transferencia'){
       var bod = $("#Bod_Ori").val();
       $("#codigoBodega").val(bod);
      } 
    }); 
    $("#Bod_Ori").change(function(){
    var bod = $("#Bod_Ori").val();
    $("#codigoBodega").val(bod); 
    }); 
</script>

<script>
    $("#Tia_Cod").change(function(){
      var elt = document.getElementById("Tia_Cod");
      var tipo = elt.options[elt.selectedIndex].text;
      if(tipo=='Consumo' || tipo=='Ajuste Inventario'){
       var bod = $("#Bod").val();
       $("#codigoBodega").val(bod);
      } 
    }); 
    $("#Bod").change(function(){
    var bod = $("#Bod").val();
    $("#codigoBodega").val(bod); 
    $('#proDialog').dialog('open');
    $('#btnbuscar').click();
    }); 
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