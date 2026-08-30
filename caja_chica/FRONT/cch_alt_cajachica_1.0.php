<?php 
/**
* Descripcion:          Modulo de Caja Chica
* Fecha de creacion:    Septiembre 5, 2017
* Desarrollador:	Asael Tello
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/cch_log_cajachica_1.0.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require_once('../../Librerias/postclass.php');	

/** 
* Creacion del Objeto de conexion 
*/
$obBD_conexion = new Class_Log_Conexion_Cch($Ses_Dat_Dis);
$obBD_con1 =  new Class_Log_Datos_Cch;
//$obBD_con1->debug(true);
    
    /*
     * Save Caja
     */
     if(isset($save))
     {        
		$data = $_POST; 
		$obBD_con1->validaCierrePeriodo('comprobantes','Com_Fec','Com_Cod',$data['Com_Fec'],null,$obBD_conexion);
        $obBD_con1->inicio_transaccion($obBD_conexion);
        
        $Pec_Cod = $obBD_con1->getPerioCont($Ses_Emp_Cod, $data['Cch_Fec'], $obBD_conexion); // get periodo contable        
        $Com_Num = $obBD_con1->getComNumAuto($Ses_Emp_Cod, $data['Tia_Cod'], $data['Cch_Fec'], $obBD_conexion); // get Numero Automatico comp        
        
        $obBD_con1->operacionobBD(9, array("Pec_Cod" => $Pec_Cod['Pec_Cod'], "Prv_Cod" => $data['Prv_Cod'], "Usu_Cod" => $Ses_Usu_Cod, "Com_Num" => $Com_Num, "Cch_Fec" => $data['Cch_Fec'], "Cch_Val" => $data['Cch_Val'], "Cch_Obs" => $data['Cch_Obs'], "Tia_Cod" => $data['Tia_Cod']),$obBD_conexion); //save comprobante
        $Com_Cod = $obBD_con1->insercionid($obBD_conexion); //get last code Com_Cod        
        
        if ($data['opn'] === "C") //Cheque
        {
            list ($Ban_Cod, $Pld_Cod_Ban) = explode("*", $data['Ban_Cod']);            
            $obBD_con1->operacionobBD(11, array("Com_Cod" => $Com_Cod, "Asi_Deh" => "D", "Cch_Val" => $data['Cch_Val'], "Pld_Cod" => $data['Pld_Cod']),$obBD_conexion); //save asiento DEBE
            $obBD_con1->operacionobBD(11, array("Com_Cod" => $Com_Cod, "Asi_Deh" => "H", "Cch_Val" => $data['Cch_Val'], "Pld_Cod" => $Pld_Cod_Ban),$obBD_conexion); //save asiento HABER
            $Asi_Cod = $obBD_con1->insercionid($obBD_conexion); //get Asi_Cod haber            
            $obBD_con1->operacionobBD(12, array("Prv_Cod" => $data['Prv_Cod'], "Ban_Cod" => $Ban_Cod, "Asi_Cod" => $Asi_Cod, "Che_Num" => $data['Che_Num'], "Cch_Fec" => $data['Cch_Fec'], "Cch_Val" => $data['Cch_Val'], "Cch_Obs" => $data['Cch_Obs'], "Che_Ben" => $data['Che_Ben']),$obBD_conexion); //save cheque
        }
        else //Efectivo
        {
            $obBD_con1->operacionobBD(11, array("Com_Cod" => $Com_Cod, "Asi_Deh" => "D", "Cch_Val" => $data['Cch_Val'], "Pld_Cod" => $data['Pld_Cod']),$obBD_conexion); //save asiento DEBE
            $obBD_con1->operacionobBD(11, array("Com_Cod" => $Com_Cod, "Asi_Deh" => "H", "Cch_Val" => $data['Cch_Val'], "Pld_Cod" => $data['Pld_Efe']),$obBD_conexion); //save asiento HABER            
        }
        
        //save caja
        $obBD_con1->operacionobBD(1,array("Usu_Cod" => $Ses_Usu_Cod, "Emp_Cod" => $Ses_Emp_Cod, "Com_Cod" => $Com_Cod, "Cch_Fec" => $data["Cch_Fec"], "Cch_Val" => $data["Cch_Val"], "Cch_Obs" => $data["Cch_Obs"]),$obBD_conexion);
        $Cch_Cod = $obBD_con1->insercionid($obBD_conexion); //get last code        
        $obBD_con1->operacionobBD(5,array("Cch_Cod" => $Cch_Cod, "Emp_Cod" => $Ses_Emp_Cod),$obBD_conexion); //update states

        if ($obBD_con1->fin_transaccion_nomsn($obBD_conexion))
        { 
            $responce['success'] = true;            
        }
        else
        {
            $responce['success'] = false;
            $responce['message'] = "No se ha logrado realizar la Transacción";
        }

        $obBD_con1->echoJson($responce); 
     }

    /*
     * Modify Caja
     */
     if(isset($modify))
     {         
        $data = $_POST;        
        $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
        //update caja_chica
        $obBD_con1->operacionobBD(17, array("Cch_Fec" => $data['data']['Cch_Fec'], "Cch_Val" => $data['data']['Cch_Val'], "Cch_Obs" => $data['data']['Cch_Obs'] , "Cch_Cod" => $data['data']['Cch_Cod']),$obBD_conexion); 
        //update comprobante
        $obBD_con1->operacionobBD(18, array("Cch_Fec" => $data['data']['Cch_Fec'], "Cch_Val" => $data['data']['Cch_Val'], "Cch_Obs" => $data['data']['Cch_Obs'],  "Tia_Cod" => $data['data']['Tia_Cod'], "Com_Cod" => $data['data']['Com_Cod']),$obBD_conexion);         
        list ($Ban_Cod, $Pld_Cod_Ban) = explode("*", $data['data']['Ban_Cod']);

        $a = $obBD_con1->getRowConsulta(22,array("Com_Cod" => $data['data']['Com_Cod'], "filtro" => 'D'),$obBD_conexion); //get Asi_Cod debe
        $b = $obBD_con1->getRowConsulta(22,array("Com_Cod" => $data['data']['Com_Cod'], "filtro" => 'H'),$obBD_conexion); //get Asi_Cod haber        

        if ($data['data']['opn'] === "C") // cheque
        {
            //update asientos
            $obBD_con1->operacionobBD(19, array("Pld_Cod" => $data['data']['Pld_Cod'], "Cch_Val" => $data['data']['Cch_Val'], "Asi_Cod" => $a['Asi_Cod'] ),$obBD_conexion); //debe
            $obBD_con1->operacionobBD(19, array("Pld_Cod" => $Pld_Cod_Ban, "Cch_Val" => $data['data']['Cch_Val'], "Asi_Cod" => $b['Asi_Cod'] ),$obBD_conexion); //haber

            if ($data['data']['Che_Cod']!== "") // modifica el cheque
            {                
                //update cheque
                $obBD_con1->operacionobBD(20, array("Ban_Cod" => $Ban_Cod, "Cch_Val" => $data['data']['Cch_Val'], "Che_Num" => $data['data']['Che_Num'], "Cch_Fec" => $data['data']['Cch_Fec'], "Cch_Obs" => $data['data']['Cch_Obs'], "Che_Ben" => $data['data']['Che_Ben'], "Asi_Cod" => $b['Asi_Cod'] ),$obBD_conexion);
            }
            else // crea el nuevo cheque
            {
                //save cheque                
                $obBD_con1->operacionobBD(12, array("Prv_Cod" => $data['data']['Prv_Cod'], "Ban_Cod" => $Ban_Cod, "Asi_Cod" => $b['Asi_Cod'], "Che_Num" => $data['data']['Che_Num'], "Cch_Fec" => $data['data']['Cch_Fec'], "Cch_Val" => $data['data']['Cch_Val'], "Cch_Obs" => $data['data']['Cch_Obs'], "Che_Ben" => $data['data']['Che_Ben']),$obBD_conexion);
            }
        }
        else //efectivo
        {            
            if ($data['data']['Che_Cod']!== "") // modifica cheque a efectivo
            {
                //elimina asientos y por ende cheque
                $obBD_con1->operacionobBD(21,$data['data']['Com_Cod'],$obBD_conexion);
                //crea asientos                
                $obBD_con1->operacionobBD(11, array("Com_Cod" => $data['data']['Com_Cod'], "Asi_Deh" => "D", "Cch_Val" => $data['data']['Cch_Val'], "Pld_Cod" => $data['data']['Pld_Cod']),$obBD_conexion); // DEBE
                $obBD_con1->operacionobBD(11, array("Com_Cod" => $data['data']['Com_Cod'], "Asi_Deh" => "H", "Cch_Val" => $data['data']['Cch_Val'], "Pld_Cod" => $data['data']['Pld_Efe']),$obBD_conexion); // HABER

            }
            else // efectivo
            {
                //update asientos
                $obBD_con1->operacionobBD(19, array("Pld_Cod" => $data['data']['Pld_Cod'], "Cch_Val" => $data['data']['Cch_Val'], "Asi_Cod" => $a['Asi_Cod'] ),$obBD_conexion); //debe
                $obBD_con1->operacionobBD(19, array("Pld_Cod" => $data['data']['Pld_Efe'], "Cch_Val" => $data['data']['Cch_Val'], "Asi_Cod" => $b['Asi_Cod'] ),$obBD_conexion); //haber
            }
        }
        

        if ($obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion))
        { 
            $responce['success'] = true;
            $response['fila']['act3']=''; //add a fila
        }
        else
        { 
            $responce['success'] = false;
            $responce['message'] = "No se ha logrado realizar la Transacción";
        }
        $obBD_con1->echoJson($responce); 
     }
     
     /*
      * Get Data for Modify
      */
     if(isset($get))
     {   
         $data = $obBD_con1->getArrayConsulta(16, $_GET['Com_Cod'], $obBD_conexion);
            if (!empty($data[0]))
            {
                $data['success'] = true;            
            }
            else
            {
                $data['success'] = false;            
            }
            $obBD_con1->echoJson($data);
     }

    /*
     * Get # de Cheque by Banco
     */
    if(isset($getCheque))
    {        
        $data = $obBD_con1->getArrayConsulta(13, $_POST['Ban_Cod'], $obBD_conexion);
        if (!empty($data[0]))
        {
            $data['success'] = true;            
        }
        else
        {
            $data['success'] = false;            
        }
        $obBD_con1->echoJson($data);
    }
    
    /*
     * Get Pld for Cheque
     */
    if(isset($getPldCaja))
    {
        $data = $obBD_con1->getArrayConsulta(6, $Ses_Emp_Cod , $obBD_conexion);        
        if (!empty($data[0]))
        {
            $data['success'] = true;            
        }
        else
        {
            $data['success'] = false;            
			$data['message'] = "!No ha parametrizado la cuenta caja Chica CC!";            
        }
        $obBD_con1->echoJson($data);
    }
    
    /*
     * Get Pld for Efectivo
     */
    if(isset($getPldEfectivo))
    {
        $data['row'] = $obBD_con1->getArrayConsulta(14, $Ses_Emp_Cod , $obBD_conexion);        
        if (!empty($data['row']))
        {
            $data['success'] = true;            
        }
        else
        {
            $data['success'] = false;            
        }
        $obBD_con1->echoJson($data);
    }
    
    /*
     * Get Prv_Code
     */
    if(isset($getPrv))
    {        
        $data = $obBD_con1->getArrayConsulta(10, $Ses_Emp_Cod , $obBD_conexion);        
        if (!empty($data[0]))
        {
            $data['success'] = true;            
        }
        else
        {
            $data['success'] = false;            
        }
        $obBD_con1->echoJson($data);
    }
    
    /*
     * Get data by Sucursal
     */
    if(isset($searchAll))
    {
        $obBD_con1->debug(true);
		$data = $obBD_con1->getArrayConsulta(4, $Ses_Emp_Cod, $obBD_conexion);
        // Grid necesita este array
        $obBD_con1->echoJson(array(
            'rows'=>$data,
            'total'=>1,
            'records'=>count($data),
            'success'=>true
        ));
    }
    
    /*
     * Enable or Disable state of Autorizacion
     */
    if(isset($setEstado))
    {
        $obBD_con1->inicio_transaccion($obBD_conexion);
        $obBD_con1->operacionobBD(513,$_GET,$obBD_conexion);
        if($_GET[Aut_Est] === "A") // desactiva todos los demas
        {
            $obBD_con1->operacionobBD(519,$_GET,$obBD_conexion);
        }
        
        if ($obBD_con1->fin_transaccion_nomsn($obBD_conexion))
        {
            $response['success'] = true;
        }
        else
        { 
            $response['success'] = false; 
            $response['message'] = "No se ha logrado realizar la Transaccion";
        }
        $obBD_con1->echoJson($response);
    }
    
    if(isset($validaCheque))
    {
        $data = $obBD_con1->getArrayConsulta(15, array("Ban_Cod" => $_POST['Ban_Cod'], "Che_Num" => $_POST['Che_Num']), $obBD_conexion);        
        if ($data[0]['contador'] === "0")
        {
            $data['success'] = true;            
        }
        else
        {
            $data['success'] = false;
            $data['message'] = "Nro de Cheque repetido";
        }
        $obBD_con1->echoJson($data);
    }
    
    /**
    * Busca los Bancos Existentes
    */
    $rs_busBancos = $obBD_con1->getArrayConsulta(8, $Ses_Emp_Cod, $obBD_conexion);
    
    /**
    * Busca los asientos de tipo EGRESO
    */
    $rs_TipAsiento = $obBD_con1->getArrayConsulta(7, 'E', $obBD_conexion);
?>

<!DOCTYPE html>
<HTML>
    <HEAD>
        <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
        <TITLE><?Php echo "Caja Chica Registrar [EXA]"; ?></TITLE>
        <meta charset= "UTF-8">
        <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>       
        <script type="text/javascript" src="../VALIDACIONES/cch_val_cajachica_1.0.js?a=4"></script>
    </HEAD>
    <BODY>
        <div class="panel panel-main">
            <div class="panel-heading exa-header"><h3 class="panel-title">&raquo; Caja Chica</h3></div>
            <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                <div id="lista" class="row">
                    <div class="col-sm-12">
                        <div id="tabsSearch" class="ui-tab-fix ui-tabs">
                            <ul class="ui-tabs-nav ui-helper-reset ui-helper-clearfix">
                              <li><a href="#tabs-1">Registrar Existentes</a></li>                              
                            </ul>
                            <div id="tabs-1" style="min-height: 450px;">
                                
                                <div>
                                    <table id="tableResult">                                        
                                    </table>
                                    <div id="tableResultPager">                                         
                                    </div>
                                    <BR>
                                    <div class="col-sm-2">
                                        <button id="btnNueva" name="btnNueva" class="btn btn-sm btn-primary"><i class="glyphicon glyphicon-plus"></i>   Agregar</button>
                                    </div>
                                </div>
                            </div>                          
                        </div>
                                  
                    </div>
                </div>                
            </div>
        </div>
        
        <!-- MODAL CAJA CHICA-->
        <div id="modalCaja" title="">
            <form id ="frmModCaja" name="frmModCaja" class="form-horizontal" autocomplete="off">
                <fieldset>
                <div class="form-group Titulos2">
                    <div class="col-sm-12"><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco (  <span class="required"></span> ) son campos obligatorios.<hr/></div>
                </div>         
               
                <div class="col-sm-1"></div>
                
                <div class="col-sm-10">
                    <fieldset class="exa-fieldset">                           
                    <legend class="Titulos2">Caja Chica</legend> <!-- Form Name -->
                    <!-- Text input-->
                    <div class="form-group">
                        <label class="col-sm-3 control-label label-sm" for="Cop_Num"></label>
                        <div class="col-sm-5"> 				
                            <input id="opn" name="opn" type="radio" value="C" onClick="
                            $('#chequeG').css('display','');$('#bancosG').css('display','');$('#cuentaG').css('display','');$('#beneG').css('display','');
                            $('#PldEfeG').css('display','none');
                            $('#bancos').attr('required');
                            $('#Che_Num').attr('required');
                            $('#docu').attr('required');"> &nbsp; <label>Cheque</label>&nbsp;&nbsp;&nbsp;													
                            <input id="opm" name="opn" type="radio" value="E" onClick="
                            $('#chequeG').css('display','none'); 
                            $('#bancosG').css('display','none'); 
                            $('#btnBusca').css('display','none') ;
                            $('#beneG').css('display','none');
                            $('#cuentaG').css('display','');
                            $('#PldEfeG').css('display','');

                            $('#bancos').removeAttr('required');
                            $('#Che_Num').removeAttr('required');
                            $('#docu').removeAttr('required');" > &nbsp; <label>Efectivo</label>
                        </div>											
                    </div>
                    
                    <!-- Fecha -->
                    <div class="form-group" id="fechaG" >
                      <label class="col-sm-3 control-label label-sm required" for="Cch_Fec">Fecha:</label>  
                      <div class="col-sm-5">                                    
                            <input id="Cch_Fec" name="Cch_Fec" class="form-control input-sm" type="text" required>	
                            <img class="imgMsg" /><label class="lblMsg"></label>
                      </div>                                 
                    </div>
                    
                    <!-- Cuenta -->
                    <div class="form-group" id="cuentaG" style="display: none">
                      <label class="col-sm-3 control-label label-sm required" for="Che_Num">Caja Chica:</label>  
                      <div class="col-sm-5">                                    
                            <input id="Pld_Cod_Des" name="Pld_Cod_Des" class="form-control input-sm" type="text" required>	
                            <img class="imgMsg" /><label class="lblMsg"></label>
                      </div>                                 
                    </div>
                    
                    <!-- select bancos-->
                    <div class="form-group" id="bancosG" style="display: none">
                    <label class="col-sm-3 control-label label-sm required" for="banco">Banco:</label>  
                    <div class="col-sm-5">
                        <select name="Ban_Cod" id="Ban_Cod" class="form-control input-sm" required>
                              <option value="0">Seleccione...</option>
                                <?Php 													
                                    foreach($rs_busBancos as $row){ ?>
                                    <option value="<?php echo $row['Ban_Cod'].'*'.$row['Pld_Cod']?>"><?php echo $row['Pld_Des']?></option>
                                <?php } ?>
                        </select>
                        <img class="imgMsg" /><label class="lblMsg"></label>
                    </div>
                    </div>
                    
                    <!-- Text input Numero Cheque-->
                    <div class="form-group" id="chequeG" style="display: none">
                      <label class="col-sm-3 control-label label-sm required" for="Che_Num">No. cheque:</label>  
                      <div class="col-sm-5">
                          <div class="input-group input-group-sm">
                            <input id="Che_Num" name="Che_Num" class="form-control input-sm" type="text" onkeypress="return validar_numeric(event);" required>
                            <span class="input-group-addon validate" ><i></i></span>
                            
                          </div>
                      </div>                                 
                    </div>																									

                    <!-- Beneficiario del Cheque -->
                    <div class="form-group" id="beneG">
                      <label class="col-sm-3 control-label label-sm required" for="cod_cuenta">Emitido a:</label>  
                      <div class="col-sm-5">                            
                            <input id="Che_Ben" name="Che_Ben" type="text" class="form-control" placeholder="" required />
                            <img class="imgMsg" /><label class="lblMsg"></label>
                      </div>                                  
                    </div>
                    
                    <!-- select PLD FOR EFECTIVO -->
                    <div class="form-group" id="PldEfeG" style="display: none">
                        <label class="col-sm-3 control-label label-sm required" for="Tia_Cod">No name:</label>  
                        <div class="col-sm-4">
                            <select name="Pld_Efe" id="Pld_Efe" class="form-control input-sm" required>                                
                            </select>
                            <img class="imgMsg" /><label class="lblMsg"></label>
                        </div>
                    </div>

                    <!-- select tipo asiento-->
                    <div class="form-group" id="tipoG" >
                        <label class="col-sm-3 control-label label-sm required" for="Tia_Cod">Tipo de Egreso:</label>  
                        <div class="col-sm-4">
                            <select name="Tia_Cod" id="Tia_Cod" class="form-control input-sm" required>
                                  <option value="0">Seleccione...</option>
                                    <?Php 													
                                    foreach($rs_TipAsiento as $row){ ?>
                                    <option value="<?php echo $row['Tia_Cod']?>"><?php echo $row['Tia_Des'];?></option>
                                    <?php } ?>
                            </select>
                            <img class="imgMsg" /><label class="lblMsg"></label>
                        </div>
                        
                        <label class="col-sm-2 control-label label-sm required" for="Cch_Val">Valor Efe./Che.:</label>  
                        <div class="col-sm-3">                                    
                            <input id="Cch_Val" name="Cch_Val" class="form-control input-sm" placeholder="0.00" type="text" onkeypress="return validar_decimal(event);" required>					  
                        </div> 
                    </div>
                    
                    <!-- Observaci�n-->
                    <div class="form-group" id="observacionG" >
                      <label class="col-md-3 control-label label-xs" for="Cch_Obs">Observación:</label>  
                      <div class="col-md-5">                      
                          <textarea id="Cch_Obs" name="Cch_Obs" rows="4" cols="30"></textarea>
                      </div>
                    </div>
                    
                    <!-- Cod_Pld-->
                    <div>
                        <input type="text" id="Pld_Cod" name="Pld_Cod" hidden="true">
                    </div>            

                     <!-- Prv_Cod-->
                    <div>
                        <input type="text" id="Prv_Cod" name="Prv_Cod" hidden="true">
                    </div>                   

                    <!-- Cch_Cod-->
                    <div>
                        <input type="text" id="Cch_Cod" name="Cch_Cod" hidden="true">
                    </div>

                    <!-- Che_Cod-->
                    <div>
                        <input type="text" id="Che_Cod" name="Che_Cod" hidden="true">
                    </div>

                    <!-- Com_Cod-->
                    <div>
                        <input type="text" id="Com_Cod" name="Com_Cod" hidden="true">
                    </div>
                    
                    </fieldset>
                </div>              
                
                <!-- Button -->
                <div class="form-group">
                  <label class="col-md-4 control-label" for="btnAccion"></label>
                  <div class="col-md-8">
                      <button id="btnAccion" name="btnAccion" class="btn btn-sm btn-primary" type="button"></button>
                  </div>
                </div>

                </fieldset>
            </form>
            
        </div>
    </BODY>
</HTML>