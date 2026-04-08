<?php	
/**
* @abstract Permite realizar movimientos de inventario
* @author Erik Niebla
* @version 1.0
* Fecha de creaciï¿½n  2015-07-22
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/con_log_docs.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Doc($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_Doc;
//$obBD_con1->debug(true);

$hoy = date("Y-m-d");
$mes = date("m");

/* cuscar cuentas contables */
if(isset($cuenAjax)){ 
    $data=$_GET ; $data['Emp_Cod']=$Ses_Emp_Cod;  
    $responce=$obBD_con1->getPageGridJson(7, $data, $obBD_conexion);    
}
/*Sección para cargar datos en el Jqgrid referente a los clientes*/
if(isset($cliAjax)){ 
   $data=$_GET; $data["Emp_Cod"]=$Ses_Emp_Cod;   
   $responce=$obBD_con1->getPageGridJson(18, $data, $obBD_conexion);
}
/*Sección para cargar datos en el Jqgrid referente a los proveedores*/
if(isset($provAjax)){ 
   $data=$_GET; $data["Emp_Cod"]=$Ses_Emp_Cod; $data["extra"]=" AND Prs_Ced!='0' ";   
   $responce=$obBD_con1->getPageGridJson(17, $data, $obBD_conexion);
}
if(isset($saveForm)){
    $obBD_con1->validaCierrePeriodo('comprobantes','Com_Fec','Com_Cod',$Com_Fec,null,$obBD_conexion);
    /* Mes del comprobante */
    $data=$_POST;
    $data['Com_Gen']='M';
    $data['Com_Num'] = $obBD_con1->getComNumPecAuto($Tia_Cod, $Pec_Cod, $Com_Fec, $obBD_conexion);
    $codigo=$Tia_Abr.'-'.substr($Com_Fec, 5, 2).'-'.$data['Com_Num'];
    /* Inicio de la transaccion */
    $obBD_con1->inicio_transaccion($obBD_conexion);    
        //$obBD_con1->echoLog($data);
        $obBD_con1->operacionobBD(2333,$data,$obBD_conexion); // Inserción del Comprobante
        //ChromePhp::log($obBD_con1);
        $Com_Cod = $obBD_con1->insercionid($obBD_conexion);
        /* Recorre el arreglo de los datos de las cuentas seleccionadas */
        foreach ($cuentas as $row){                    
            $asiento=array('Com_Cod'=>$Com_Cod, 'Asi_Deh'=>$row['Det_Tip'], 'Asi_Con'=>$row['Pld_Des'], 'Asi_Glo'=>$row['Glosa'], 'Pld_Cod'=>$row['Pld_Cod'], 'Asi_Val'=>$row['Det_Tip']=='D'?$row['Debe']:$row['Haber'] );
            $obBD_con1->operacionobBD(24,$asiento,$obBD_conexion); //guardado de los asientos
        }
       
    /* Finaliza la transacción */                
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    if($obBD_con1->Error==0){
        $responce=array('success'=>true, 'codigo'=>$codigo, 'link'=>"../../contabilidad/FRONT/con_pri_compr_2.1.php?codigo=$Com_Cod");
    }else{ $responce['success']=false; $responce['error']=$obBD_con1->MsgError; }
    $obBD_con1->echoJson($responce);
}
?>
<!DOCTYPE html>
<HTML>
    <HEAD>		
        <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
        <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php")?>  
        <style>#tabsInsert.ui-widget-content{background:none !important;} .ui-tabs-panel{padding-bottom: 0 !important;}.ui-tabs-nav{padding-top: 0 !important;}.ui-tabs .ui-tabs-panel{padding: 5px;}</style>
        <script>var gridCompAsien,tipo="Ingreso";</script>
        <script type="text/ecmascript" src="../VALIDACIONES/con_val_compr_2.js?x=b"></script>
    </HEAD>
<BODY>
    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo; Registrar Comprobantes</h3></div>        
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div>
                <div class="row">  
                    <div class="col-sm-12 form-horizontal normal">
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Periodo Contable</legend>
                            <div class="form-group">
                                <label class="col-xs-2 control-label label-xs required">Seleccione Periodo:</label> 
                                <div class="col-xs-2">
                                    <?php $row_rs_periodos = $obBD_con1->getArrayConsulta(22, $Ses_Emp_Cod, $obBD_conexion); ?>
                                    <select name="perio_cont" id="perio_cont" onchange="setPeriodo()"  class="form-control input-sm">
                                        <!--<option value="">Seleccione...</option>-->
<?php
  if (count($row_rs_periodos) > 0){ $periodo = current($row_rs_periodos);
      foreach ($row_rs_periodos as $row){ 
          echo "<option value='$row[Pec_Cod]' data--pec_-cod='$row[Pec_Cod]' data--pla_-cod='$row[Pla_Cod]' data--pec_-fei='$row[Pec_Fei]' data--pec_-fef='$row[Pec_Fef]'  data-periodo='$row[Periodo]'>$row[Periodo]</option>";
      }    
  } ?>
                                    </select>   
                                </div>

                            </div>    
                        </fieldset>
                    </div>
                </div> 
                <form id="periodoForm" class="hidden"><input name="periodo" type="text"><input type="text" name="Pec_Cod" /></form>                    
                <div class="row">  
                    <div class="col-sm-12">
                        <div id="tabsInsert" class="ui-tab-fix ui-tabs">
                            <ul class="ui-tabs-nav ui-helper-reset ui-helper-clearfix">
                                <li><a href="#tabs-1">Ingreso</a></li>
                                <li><a href="#tabs-2">Egreso</a></li>
                                <li><a href="#tabs-3">Diario</a></li>                                
                            </ul><div class="panels-area"><div id="tabs-1"></div><div id="tabs-3"></div><div id="tabs-2"></div></div>                            
                        </div> 
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Comprobante de <span id="title_comp">Ingreso</span></legend>	
                            <form name="formCompConta" id="formCompConta" method="post" action="javascript:validaComp()" class="form-horizontal normal">                
                                <input type="hidden" value="M" name="Com_Gen" />
                                <div class="col-xs-6">
                                    <div class="form-group">
                                        <label class="col-xs-4 control-label label-xs required">Tipo&nbsp;Asiento:</label> 
                                        <div class="col-xs-8">
                                            <?php  $tiasien =  $obBD_con1->getArrayConsulta(2, '', $obBD_conexion);  ?>
                                            <select class="form-control input-xs"  id="Tia_Cod_Comp" name="Tia_Cod"  class="isSelectMenu" required>
                                                <option value="">Seleccione...</option>
                                                <?php foreach($tiasien as $row){  ?>
                                                <option value="<?php echo $row['Tia_Cod']; ?>" style="display:none" data-type="<?php echo $row['Tia_Ini']; ?>" data--tia_-cod="<?php echo $row['Tia_Cod']; ?>"  data--tia_-abr="<?php echo $row['Tia_Abr']; ?>" data--tia_-des="<?php echo $row['Tia_Des']; ?>" ><?php echo $row['Tia_Abr'].' - '.$row['Tia_Des']; ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group persona cliente">
                                        <label class="col-xs-4 control-label label-xs required">Cliente:</label> 
                                        <div class="col-xs-8"> 
                                            <input type="hidden" id="cod_cli" name="Cli_Cod" value="" data-name='Cli_Cod' /> 
                                            <div class="input-group input-group-xs">
                                                <input id="lblClie" name="clientes" data-name="cliente" onkeydown='if (event.keyCode === 13) buscaCliente();' onchange="if($('#lblClie').val()==='')$('#cod_cli').val('');" class="form-control varios clearable" placeholder="Ingrese Cliente"  />
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
                                        <div class="col-xs-5"><input id="Com_Fec" name="Com_Fec" type="text" style="text-align: center" size="10" maxlength="10" class="form-control input-xs" required /></div>
                                        <label class="col-xs-2 control-label label-xs required">Tipo:</label> 
                                        <div class="col-xs-3">
                                            <select name="Com_Est" id="Com_Est" class="form-control input-xs">
                                                <option value="B">Borrador</option>
                                            </select>   
                                        </div> 
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
                </div>
                <div class="row">   
                    <div class="col-sm-12">
                        <div id="compGrilla" style="padding-top: 6px; ">   
                            <table id="compAsien"></table>
                            <div id="compAsienPager"></div>
                        </div>
                    </div>
                    <div class="col-sm-12" style="padding-top: 6px;">                    
                        <button onclick="$('#formCompConta').formSubmit();" title="Guardar Comprobante" type="button" class="btn btn-primary start" ><i class="glyphicon glyphicon-floppy-disk"></i><span> Guardar</span></button><span style="width: 15px;"></span>                        
                    </div>
                    <div class="col-sm-12 Titulos2"><hr><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco ( &nbsp;<span class="required"></span> ) son campos obligatorios.</div>
                    
                    <script type="text/javascript">
                        $(document).ready(function () {  
                            
                        });
                    </script>
                </div>   
            </div>
        </div>
    </div>
    <script type="text/javascript">
        function SelectCta(cta){  addFilaCuenta($.getDialogGrid("#cuenDialog").jqGrid('getRowData', cta['Pld_Cod']), cta['tipo'] );  } 
        function selectProv(prov){ $('.persona').setData(prov,null,'name'); $("#provDialog").dialog("close"); }        
        function selectClie(clie){ $('.persona').setData(clie,null,'name'); $("#cliDialog").dialog("close"); }   
        function buscaCliente(){ $.SearchOrDialogArray("#cliDialog",selectClie, {'search':$('#lblClie').val(),'op_opciones':'c'}); selectClie({}); }  
        function buscaProvee(){ $.SearchOrDialogArray("#provDialog",selectProv,{'search':$('#lblProvee').val(),'op_opciones':'c'}); selectProv({}); }
        function resetForm2(option){ gridCompAsien.clearGrid().updateGridDiario(); }
    </script>
    <!--INICIO DEL DIALOGO BUSCAR CUENTA--> 
    <div id="cuenDialog" title="B&uacute;squeda de Cuentas" style="display: none"></div>
    <!--INICIO DEL DIALOGO BUSCAR PROVEEDOR--> 
    <div id="provDialog"  title="B&uacute;squeda de Proveedores"></div>    
    <!--INICIO DEL DIALOGO BUSCAR CLIENTE--> 
    <div id="cliDialog"  title="B&uacute;squeda de Clientes"></div>    
    <!--INICIO DEL DIALOGO IMPRIMIR --> 
    <div id="successDialog" title="Mensaje del Sistema" style="display: none;">
        <center>
            <b style="font-size:14px;">Se ha registrado con Exito!</b>
            <h4><b class="blue">Asiento: </b><span class="orange" id="successCodigo">dd-55-55</span></h4>
            <button id="btnImpCompr" type="button" class="btn btn-info" onclick="$.imprimirUrl($(this).data('url'))"><i class="glyphicon glyphicon-print"></i> Imprimir Comprobante</button>
        </center>
    </div> 
    <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=1"></script>
</BODY>
</HTML>