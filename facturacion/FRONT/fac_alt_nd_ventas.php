<?php	
/**
* @abstract Permite realizar la cancelacion de comprobantes por abonos
* @author Erik Niebla
* @version 1.0
* Fecha de creaci�n  2015-07-22
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_fac_ven.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require_once('../../Librerias/postclass.php');	
/**
* Creacion del Objeto de conexion
*/
$obBD_conexion = new Class_Log_Conexion_Tes($Ses_Dat_Dis);
/**
* Creacion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Tes;
/**
* Evita el reenvio 
*/
$thisPost = new Post_Block;

$hoy = date("Y-m-d");
$mes = date("m");

/* Busqueda de Clientes */
if(isset($cliAjax)){
    $contar = $obBD_con1->getRowConsulta(1287, $search.'*'.$Ses_Emp_Cod.'*'.$op_opciones.'*', $obBD_conexion);	      
    $pagination= pages($contar['total'], $page, $rows);
    $responce=$pagination['data'];
    $responce['rows'] = $obBD_con1->getArrayConsulta(1287, $search.'*'.$Ses_Emp_Cod.'*'.$op_opciones.'*'.$pagination['limits'], $obBD_conexion);
    utf8_encode_deep($responce['rows']);
    echo json_encode($responce);exit();
}
/* Busqueda de Notas de Venta */
if(isset($ajaxND_Ventas)){ 
    $data=filter_input_array(INPUT_GET);
    $data["Suc_Cod"]=$Ses_Suc_Cod;  
    $responce['rows'] = $obBD_con1->getArrayConsulta(1285,$data, $obBD_conexion);
    utf8_encode_deep($responce['rows']);
    $responce['records']=count($responce['rows']);
    echo json_encode($responce);exit();
}
$row_tipo_compr = $obBD_con1->getArrayConsulta(1036, '', $obBD_conexion);	
foreach ($row_tipo_compr as $row)
    if($row['Tic_Sri']=='01')
    {$Tic_Cod=$row['Tic_Cod'];break;}
$row_rs_vendedor = $obBD_con1->getRowConsulta(24, $Ses_Prs_Cod.'*'.$Ses_Suc_Cod,$obBD_conexion);
$rs_infoEmpresa = $obBD_con1->getRowConsulta(1211, $Ses_Suc_Cod, $obBD_conexion);
/* Cargar la cuentas contables para pagos en efectivo */
if(isset($cuentas)){ 
    $responce['success']=true;
    $row_rs_bancos = $obBD_con1->getArrayConsulta(1288, '1*'.$Pla_Cod.'*'.$Ses_Emp_Cod, $obBD_conexion);
    $responce['html']= '<option value="">Seleccione...</option>';
    foreach ($row_rs_bancos as $row){
        $responce['html']=$responce['html'].'<option value="'.$row['Ban_Cod'].'*'.$row['Pld_Cod'].'">'.$row['Ban_Des'].'</option>';
    }    
    utf8_encode_deep($responce);
    echo json_encode($responce);exit();
}
/* Valida Numero de Factura */
if(isset($valVetNum)){ 
    $responce['Vet_Num']=$valVetNum*1;$responce['exist']=false;$responce['valid']=false;
    $row_rs_autorizaci = $obBD_con1->getRowConsulta(30, $Tic_Cod.'*'.$row_rs_vendedor['Pun_Cod'].'*'.$Caj_Fec, $obBD_conexion); 
//    foreach ($row_rs_autorizaci as $row) {
        $row_rs_buscaNumVenta= $obBD_con1->getRowConsulta(1222, $row_rs_autorizaci['Aut_Sri'].'*'.$valVetNum,$obBD_conexion);
	$total_rs_buscaNumVenta=$row_rs_buscaNumVenta['Vet_Cod'] > 0? 1 : 0;
        if($total_rs_buscaNumVenta==1)
            {$responce['exist']=true;}
//    }
//    foreach ($row_rs_autorizaci as $row) {
        if($row_rs_autorizaci['Aut_Ini']*1<=$valVetNum && $row_rs_autorizaci['Aut_Fin']*1>=$valVetNum)
        {$responce['valid']=true;}
        else{$responce['message']='El rango esta entre <b>'.$row_rs_autorizaci['Aut_Ini'].'</b> y <b>'.$row_rs_autorizaci['Aut_Fin'].'</b>.';}
//    }
    $responce['success']=true;
    echo json_encode($responce);exit();
}
/* Guardar La Factuta*/
if(isset($saveForm)){ 
    $obBD_ins1 =  new Class_Log_Datos_Tes;    
    $Tia_Cod = '7';
    $op = '7';$mes = explode('-', $Caj_Fec);$responce['Com_Cod']='';
    $tabla="cliente"; $campo="Cli_Cod";$Vet_Des=0;$Num_Ret='';$Ret_Fec='';$Num_Aut='';$hora=date('H:i:s');$url='';
    $Vnd_Cod=$row_rs_vendedor['Vnd_Cod'];$Pun_Cod=$row_rs_vendedor['Pun_Cod'];
    
        /* SACO EL NUMERO DE COMPROBANTE y AUTORIZACION*/
        $periodoCont=$obBD_con1->getRowConsulta(1267,$Ses_Emp_Cod.'*'.$Caj_Fec,$obBD_conexion);
        $Pec=$periodoCont['Pec_Cod'];    
        $Com_Num= $obBD_ins1->codigoComprAuto($op, $Pec, $mes[1], $obBD_conexion);
        $row_rs_autorizaci = $obBD_con1->getRowConsulta(30, $Tic_Cod.'*'.$Pun_Cod.'*'.$Caj_Fec, $obBD_conexion); 
    
            /*Busca, si la fecha ingresada pertenese a una caja */
            $row_rs_buscaCaja= $obBD_con1->getRowConsulta(1219, $Pun_Cod.'*'.$Caj_Fec,$obBD_conexion);
            $total_rs_buscaCaja=$row_rs_buscaCaja['Caj_Cod'] > 0? 1 : 0;		
            if($total_rs_buscaCaja==1){
                    $Caj_Cod=$row_rs_buscaCaja['Caj_Cod'];
            }else{			
                    $obBD_ins1->inicio_transaccion($obBD_conexion->conexion);			
                    $obBD_ins1->operacionobBD(1221,$Caj_Fec."*".date('H:i:s')."*".$Pun_Cod."*C",$obBD_conexion); /*Creamos la caja*/						
                    $Caj_Cod=$obBD_ins1->insercionid($obBD_conexion->conexion);
                    $obBD_ins1->fin_transaccion_nomsn($obBD_conexion->conexion);
            }
                
    
    $obBD_ins1->inicio_transaccion($obBD_conexion->conexion);
        
                /**
		*  Consultamos informacion de la autorizacion
		*/
		$rs_infoCliente = $obBD_con1->getRowConsulta(81, $row_rs_autorizaci['Aut_Cod'], $obBD_conexion);
		
		/**
		*  proceso para generar clave de acceso del XML a la vez se gurdara en la cabecera de la factura
		*/
		$Vet_Aut="";		
                $rs_infoTipCom = $obBD_con1->getRowConsulta(1241, $Tic_Cod, $obBD_conexion);		
		if ($rs_infoEmpresa['Cof_Gce']=="S") /* Verifico si tiene autorizacion para generar F.E.*/
		{	
			$Vet_Aut="N"; //variable que nos indica que la factura electronica esta pendiente de envio al SRI
			for($i=strlen($Vet_Num); $i<=9-1; $i++)
			{ $ceroDoc=$ceroDoc."0";}
			 $cadena=date("dmY",strtotime($Caj_Fec)).$rs_infoTipCom['Tic_Sri'].$rs_infoEmpresa['Emp_Ruc'].$rs_infoEmpresa['Cof_Fac'].$rs_infoEmpresa['Suc_Sri'].$rs_infoCliente['Pun_Sri'].$ceroDoc.$Vet_Num."12345678"."1";			
			$factor = 2;
			$suma = 0;
			for($i = strlen($cadena) - 1; $i >= 0; $i--) {
				$suma += $factor * $cadena[$i];
				$factor = $factor % 7 == 0 ? 2 : $factor + 1;
			}
			$dv = 11 - $suma % 11;				
			$dv = $dv == 11 ? 0 : ($dv == 10 ? 1 : $dv);  //si el digito verificador 11 se cambia por (0), si es igual a 10 se cambia a (1)
			/* Agrego el codigo verificador al final de la clave de acceso*/
			$claveAcceso=$cadena.$dv;
			//$url=$Ses_Emp_Cod."/".$claveAcceso.".xml";
			$url=$claveAcceso;
		}
    
        /**
        * Inserci�n de la cabecera de la factura 
        */
        $obBD_ins1->operacionobBD(20, $Tic_Cod.'*'.$Cli_Cod.'*'.$Ciu_Cod.'*'.$Caj_Cod.'*'.$Vnd_Cod.'*'.$Vet_Num.'*'.$Vet_Obs.'*'.$row_rs_autorizaci['Aut_Cod'].'*'.$Vet_Des.'*'.$hora.'*'.$url.'*'.$Vet_Aut.'*'.$Num_Ret.'*'.$Ret_Fec.'*'.$Num_Aut, $obBD_conexion);	
        $Vet_Cod = $obBD_ins1->insercionid($obBD_conexion->conexion);
        
        /**
        * Insercion de los tipos de pago	
        * Tipo de pago 1	
        */
        $Vet_Cue='';$Vet_Che=0;
        $Banco=  explode('*', $Ban_Cod);
        $Bak_Cod = 1; //Variable siempre fija en 1 porque no es usa otros bancos
        $obBD_ins1->operacionobBD(315, $Vet_Cod.'*'.$Bak_Cod.'*'.($Ban_Cod=='NULL'?$Ban_Cod:$Banco[0]).'*'.$Pag_Cod.'*'.$Vet_Cue.'*'.$Vet_Che.'*'.$Vet_Tot.'*'.'1', $obBD_conexion);	
        /**
        * Inserta el detalle de la venta, junto con notasgenet, contrato e indice
        */
        $Vet_Ite=0;
        foreach($list as $prod){
            $Vet_Ite++;
            $obBD_ins1->operacionobBD(1228, $Vet_Cod.'*'.$prod['Pro_Cod'].'*'.$prod['Vet_Can'].'*'.$prod['Iva_Cod'].'*'.	
            $prod['Vet_Pru'].'*'.$prod['Importe'].'*0*0*0*0*0*0*1*NULL*NULL*'.$Vet_Ite, $obBD_conexion);
            
                                /**
				* Control para I N V E N T A R I O S 
				*/
				$row_rs_adquisicio = $obBD_con1->getRowConsulta(1037, $prod['Pro_Cod'], $obBD_conexion);
				/** 
				* Pregunta si es de tipo bien el producto B 
				*/
				if (count($row_rs_adquisicio) <> 0)
				{
					$desc_var=0;
					/**  Actualiza el kardex */
					$obBD_ins1->operacionobBD(1072, $Vet_Cod.'*'.'0'.'*'.$Vnd_Cod.'*'.'0'.'*'.$prod['Pro_Cod'].'*'.$Caj_Fec.'*'.$hora.'*'.'0'.'*'.$prod['Vet_Can'].'*'.$prod['Vet_Pru'].'*'.'0'.'*'.$prod['Importe'].'*'.'0'.'*'.$desc_var.'*'.$prod['Iva_Cod'].'*'.'0', $obBD_conexion);										
					/** * Consulta el Stock */
					$row_rs_conpro = $obBD_con1->getRowConsulta(1206, $prod['Pro_Cod'],$obBD_conexion);					
					/*** Actualizo el Stock */
					$obBD_ins1->operacionobBD(1204, $row_rs_conpro['Stock'].'*'.$prod['Pro_Cod'].'*'.$Ses_Suc_Cod, $obBD_conexion);
				}//FIn del if (count($row_rs_adquisicio) <> 0)
				/**
				* F I N Control para I N V E N T A R I O S
				*/
        } 
        //Agregado para que grabe comprante de ingreso
                if($rs_infoEmpresa['Cof_Con']=="S")
                {
                    $Periodo=  explode('*',$Pec_Cod);$Pec=$Periodo[0];$Pec_Cod=$Periodo[0];
                    $obBD_ins1->operacionobBD(1260, $Pec.'*'.$Cli_Cod.'*'.$Com_Num.'*'.$Caj_Fec.'*'.trim($Vet_Obs).'*'.$op.'*'.$Vet_Tot.'*'.trim($Vet_Obs).'*'.$campo, $obBD_conexion);
                    $Com_Cod= $obBD_ins1->insercionid($obBD_conexion->conexion);
                    //echo $Com_Cod.'<br/>'.$Ses_Emp_Cod.'<br/>';  
                    $obBD_ins1->operacionobBD(1273, $Vet_Cod.'*'.$Com_Cod, $obBD_conexion);
                    
                    
                    $obBD_ins1->operacionobBD(1263, $Com_Cod.'*'.'D'.'*'.$Vet_Tot.'*'.trim($Vet_Obs).'*'.'Fact. No.'.$Vet_Num.'*'.$Banco[1],$obBD_conexion);
                        
                    foreach($list as $fila){
                            $row_rs_procuen = $obBD_con1->getRowConsulta(1264, $fila['Pro_Cod'],$obBD_conexion);//Cuenta del producto
                            $obBD_ins1->operacionobBD(1263, $Com_Cod.'*'.'H'.'*'.$fila['Importe'].'*'.trim($Vet_Obs).'*'.'Fact. No.'.$Vet_Num.' '.$fila['Ite_Lar'].'*'.$row_rs_procuen['Pld_Cod'],$obBD_conexion);
                    }
                    if(isset($Iva_Tot)&&($Iva_Tot*1)>0){
                        $row_rs_ivacuen = $obBD_con1->getRowConsulta(1265, $Periodo[1],$obBD_conexion);
                        $obBD_ins1->operacionobBD(1263, $Com_Cod.'*'.'H'.'*'.$Iva_Tot.'*'.trim($Vet_Obs).'*'.'Fact. No.'.$Vet_Num.'*'.$row_rs_ivacuen['Pld_Cod'],$obBD_conexion);
                    }
                    $responce['Com_Cod']=$Com_Cod; 
                    $responce['link']="../../contabilidad/FRONT/con_pri_compr_1.1.php?codigo=$Com_Cod&tabla=$tabla&campo=$campo&tipo=$Tia_Cod&Pec_Cod=$Pec_Cod";
                }
    $obBD_ins1->operacionobBD(1289,str_replace(",", " OR ventas.Vet_Cod=", "(ventas.Vet_Cod=".$ND_Ventas.")") , $obBD_conexion);            
    $obBD_ins1->fin_transaccion_nomsn($obBD_conexion->conexion);
    
    
    if($obBD_con1->Error==0) $responce['success']=true; else $responce['success']=false;$responce['message']=$obBD_con1->MsgError;
    
                /*
		*   GUARDAMOS AL CLIENTE COMO USUARIO DEL SISTEMA SOLO PARA FACTURAS ELECTRONICAS
		*/
		if ($rs_infoEmpresa['Cof_Gce']=="S") /* Verifico si tiene autorizacion para generar F.E.*/
		{					
			/*
			*  Conexion a la base Master
			*/
			$obBD_conexion_master = new Class_Log_Conexion_Tes;
			$obBD_ins1_master = new Class_Log_Datos_Tes;
			$obBD_con1_master = new Class_Log_Datos_Tes;
			
			/* Busco codigo de la empresa en la tabla data*/
			$row_rs_DatEmp = $obBD_con1_master->getRowConsulta(1244, $Ses_Emp_Cod,$obBD_conexion_master);			
			/* Busco si existe ya el usuario en la master */
			$row_rs_existeUsu = $obBD_con1_master->getRowConsulta(1246, $Ses_Usu_Cod.'*'.$row_rs_DatEmp['Dat_Cod'].'*'.$PrsCedCli,$obBD_conexion_master);			
			$total_existeUsu=$row_rs_usuario['Suc_Cod'] > 0? 1 : 0;
			if($total_existeUsu==0)
			{	
				/* Inicio de la transaccion	*/
				$obBD_ins1_master->inicio_transaccion($obBD_conexion_master->conexion);																
				/* creamos el usuario en la base master */
				$obBD_ins1_master->operacionobBD(1243,$Ses_Suc_Cod.'*'.$row_rs_DatEmp['Dat_Cod'].'*'.$PrsCedCli,$obBD_conexion_master);
				$obBD_ins1_master->fin_transaccion_nomsn($obBD_conexion_master->conexion);
			}
		}	
		/**	
		*  Si la transaccion fue correcta generamos el xml para Factura Electronica
		*/		
		if ($obBD_conexion->Error==0)
		{
			if ($rs_infoEmpresa['Cof_Gce']=="S") /* Verifico si tiene autorizacion para generar F.E.*/
			{		
				
						
				if($Tic_Cod==1)
				{
					/* Genera el Xml de la Fatura */
					include("../COMPONENTES/tesXmlFacturaElectronica_1.0.php");
				}
				if($Tic_Cod==4)
				{
					/* Genera el Xml de la Nota de Credito */
					include("../COMPONENTES/tesXmlNotasCreditoElectronica_1.0.php");
				}
			}
		}
                
    echo json_encode($responce);exit();
}

/**
* Consulta del vendedor en base al codigo de la persona
*/
$row_rs_autorizaci = $obBD_con1->getRowConsulta(30, $Tic_Cod.'*'.$row_rs_vendedor['Pun_Cod'].'*'.$hoy, $obBD_conexion);                            

?>
<!DOCTYPE html>
<HTML>
	<HEAD>		
                <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
                <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php")?>              
                <style>                     
                     .label-xs.required{padding-top: 4px !important;}
                </style>
	</HEAD>
<BODY>
 
    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Gestion de Notas de Venta No Contabilizadas</h3></div>
        
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">            
                <div class="row">
                    <?php if(isset($ND_Ventas)){ ?>
                    <div class="col-sm-12">  
                        <fieldset class="exa-fieldset">                           
                           <legend class="Titulos2">Facturar Notas de Venta</legend> <!-- Form Name -->
                          <div class="row">
                              <form id="factForm" class="form-horizontal normal" action="javascript:if($('#Cli_Cod').val()===''){$.alert('Seleccione Cliente');}else{if($('#total').val()==='0.00'){$.alert('El Total de la Factura no puede ser cero!');}else{$.createDialogConfirm(null,null,saveForm);}}">
                                  <input name="ND_Ventas" type="text" value="<?php echo $ND_Ventas; ?>" style="display: none"/>
                           <div class="col-sm-6">
                           <fieldset class="exa-fieldset">                           
                                <legend class="Titulos2">Datos de La Factura</legend> <!-- Form Name -->
                                
                                    <div class="form-group">  
                                      <label class="col-xs-2 control-label label-xs" for="Tic_Cod">Documento:</label>  
                                      <div class="col-xs-6">
                                            <select name="Tic_Cod" id="Tic_Cod" class="form-control input-xs" required>
                                              <?Php
                                              foreach($row_tipo_compr as $row)
                                              { if($row['Tic_Sri']=='01'){ $Tic_Cod=$row['Tic_Cod'];?>
                                              <option  <?Php if ($Tic_Cod == $row['Tic_Cod']){ echo "selected"; } ?> value="<?Php echo $row['Tic_Cod']; ?>"><?Php echo $row['Tic_Des']; ?></option>
                                              <?Php
                                              }}
                                              ?>
                                            </select>
                                      </div>                                 
                                    </div> 
                                    <div class="form-group">  
                                      <label class="col-xs-2 control-label label-xs required" for="Caj_Fec">Fecha:</label>  
                                      <div class="col-xs-3">                                    
                                          <input name="Caj_Fec" id="Caj_Fec" type="text" class="form-control input-xs isDatePicker" required="" onchange="validaVetNum()"/>
                                      </div>                                 
                                    </div>
                                    <div class="form-group">  
                                      <label class="col-xs-2 control-label label-xs" for="ciudad">Ciudad:</label>  
                                      <div class="col-xs-3">  
                                          <?php $row_rs_ciudad = $obBD_con1->getRowConsulta(26, $Ses_Usu_Cod, $obBD_conexion); ?>
                                              <span id="ciudad" class="form-control input-xs"><?Php echo $row_rs_ciudad['Ciu_Des']; ?></span>
                                              <input name="Ciu_Cod" type="text" value="<?Php echo $row_rs_ciudad['Ciu_Cod']; ?>" style="display: none" required />
                                      </div>                                 
                                    </div> 
                                    <div class="form-group">  
                                      <label class="col-xs-2 control-label label-xs required" for="Vet_Num">Secuencia:</label>  
                                      <div class="col-xs-3">    
                                          <?php 
                                            $Vet_Num='';
                                            
                                            if(count($row_rs_autorizaci) > 0){
                                                $Vet_Num = $obBD_con1->codigoSiguiente($row_rs_autorizaci['Aut_Cod'], $row_rs_autorizaci['Aut_Ini'], $obBD_conexion);	
                                            }
	
                                          ?>
                                          <input id="Vet_Num" name="Vet_Num" type="text" class="form-control input-xs" value="<?php echo $Vet_Num; ?>" style="text-align: right" onchange="validaVetNum()" required/>

                                      </div>  
                                      <div class="col-md-7 msgDiv">
                                        <?php if(count($row_rs_autorizaci) > 0){ ?>
                                        <img class="imgMsg" src="../../mascaras/model1/imagenes/ok-s.gif" /><label class="lblMsg"></label>
                                        <?php }else{ ?>
                                        <img class="imgMsg" src="../../mascaras/model1/imagenes/32x32/cancel.gif"><label class="lblMsg">No tiene <b>Autorizaci�n</b> para Facturar en <b><?php echo $hoy; ?></b></label>
                                        <?php } ?>
                                      </div>
                                    </div> 
                                    <!-- Textarea -->
                                    <div class="form-group">
                                      <label class="col-sm-2 control-label" for="Vet_Obs">Observaci�n:</label>
                                      <div class="col-sm-10">                     
                                        <textarea class="form-control" id="des_cuenta" name="Vet_Obs"></textarea>
                                      </div>
                                    </div>
                                
                           </fieldset>
                           </div>    
                           <div class="col-sm-6">
                           <fieldset class="exa-fieldset">                           
                                <legend class="Titulos2">Datos del Cliente</legend> <!-- Form Name -->
                                <!-- Text input-->
                               
                                    <div class="form-group">
                                      <label class="col-xs-2 control-label label-xs required" for="cliente">C�dula/R.U.C:</label>  
                                      <div class="col-xs-6">
                                            <div class="input-group input-group-xs">                                                
                                                <input type="text" id="Cli_Cod" name="Cli_Cod" value="" style="display: none" />
                                                <span id="cedula" class="form-control">Seleccione Cliente..</span>
                                                <span class="input-group-btn">
                                                    <button class="btn btn-success" onclick="$('#cliDialog').dialog('open');" type="button"><span class="glyphicon glyphicon-check" title="Buscar Clientes"></span></button>
                                                </span>
                                            </div><!-- /input-group -->                                          

                                      </div>
                                    </div>
                                    <div class="form-group">
                                      <label class="col-xs-2 control-label label-xs" for="cliente">Cliente:</label>  
                                      <div class="col-xs-10">                                    
                                            <span id="cliente" class="form-control input-xs"></span>

                                      </div>
                                    </div>
                                    <div class="form-group">  
                                      <label class="col-xs-2 control-label label-xs" for="direccion">Direcci�n:</label>  
                                      <div class="col-xs-10">                                    
                                              <span id="direccion" class="form-control input-xs"></span>

                                      </div>                                 
                                    </div>  
                                
                           </fieldset>
                            <fieldset class="exa-fieldset">                           
                                <legend class="Titulos2">Forma de Pago</legend> <!-- Form Name -->
                                
                                    <div class="form-group">  
                                      <label class="col-xs-2 control-label label-xs" for="For_Cod">Forma:</label>  
                                      <div class="col-xs-3">                                    
                                            <select name="For_Cod" class="form-control input-xs" required>
                                                <option value='1'>Contado</option>
                                            </select>    
                                      </div>
                                      <?php if($rs_infoEmpresa['Cof_Con']=='S'){ ?>
                                      <label class="col-xs-2 control-label label-xs" for="Pec_Cod">Periodo:</label>  
                                      <div class="col-xs-3">
                                            <?php
                                                $row_rs_periodos = $obBD_con1->getArrayConsulta(1259, $Ses_Emp_Cod, $obBD_conexion);
                                                $periodo = current($row_rs_periodos); 
                                            ?>
                                          <select name="Pec_Cod" class="form-control input-xs" onchange="setCuenta(this.value);" required>
                                                
                                                <?php 
                                                foreach ($row_rs_periodos as $row)
                                                {
                                                ?>
                                                <option value="<?Php echo $row['Pec_Cod'].'*'.$row['Pla_Cod'].'*'.$row['Pec_Fei'].'*'.$row['Pec_Fef']; ?>"><?Php echo $row['Periodo']; ?></option>
                                                <?php		
                                                } ?>
                                            </select>    
                                      </div> 
                                      <?php } ?>
                                    </div> 
                                    <div class="form-group">  
                                      <label class="col-xs-2 control-label label-xs" for="Pag_Cod">Tipo:</label>  
                                      <div class="col-xs-3">                                    
                                            <select name="Pag_Cod" class="form-control input-xs" required>
                                                <option value='1'>Efectivo</option>
                                            </select> 
                                      </div>  
                                      <label class="col-xs-2 control-label label-xs" for="Ban_Cod">Cuenta:</label>  
                                      <div class="col-xs-5">   
                                            <?php $row_rs_bancos = $obBD_con1->getArrayConsulta(1288, '1*'.$periodo['Pla_Cod'].'*'.$Ses_Emp_Cod, $obBD_conexion); ?>
                                            <select name="Ban_Cod" id="Ban_Cod" class="form-control input-xs" required>
                                               <?php if($rs_infoEmpresa['Cof_Con']=='S'){ ?>
                                                    <option value=''>Seleccione...</option>
                                                    <?php foreach ($row_rs_bancos as $row)
                                                    {
                                                    ?>
                                                    <option value="<?Php echo $row['Ban_Cod'].'*'.$row['Pld_Cod']; ?>"><?Php echo $row['Ban_Des']; ?></option>
                                                    <?php		
                                                    } 
                                                }else{ ?> 
                                                    <option value='NULL'>Ninguno</option>
                                               <?php } ?> 
                                            </select> 
                                      </div> 
                                    </div> 
                               
                           </fieldset>
                           </div>
                              </form>   
                           <div class="col-sm-12">
                            <table id="list486"></table>
                            <div id="plist486"></div>
                           </div> 
                              <div class="col-sm-12" style="padding-top:10px">
                                  
                                  <button type="button" class="btn btn-inverse btn-sm" title="Atr�s" onclick="window.history.back();" >
                                                <i class="glyphicon glyphicon-arrow-left"></i>
                                                <span>&nbsp;&nbsp;Atr&aacute;s&nbsp;&nbsp;</span>
                                   </button>
                                  <button id="btnGuardar" type="button" class="btn btn-primary btn-sm" title="Guardar" onclick="$('#factForm').formSubmit()">
                                       <i class="glyphicon glyphicon-floppy-disk"></i>
                                       <span>&nbsp;&nbsp;Guardar</span>
                                </button>
                                  
                             
                           </div>
                        </div>   
                           <?php  
                             $NDs=explode(',',$ND_Ventas);
                             $Vet_Cod=str_replace(",", " OR ventas.Vet_Cod=", "(ventas.Vet_Cod=".$ND_Ventas.")");
                             $responce['rows'] = $obBD_con1->getArrayConsulta(1286,$Vet_Cod, $obBD_conexion);                             
                           ?>
                           <script>                                
                                $(document).ready(function () { 
                                    
                                    $("#list486").jqGrid({
                                         data:<?php echo json_encode($responce['rows']); ?>,
                                         datatype: "local",                                        
                                         rowNum: 10000000,
                                         pgtext: ' ',   
                                         autowidth : true, shrinkToFit: true, height: 100,responsive:true,
                                         //colNames:['Inv No','Date', 'Client', 'Amount','Tax','Total','Notes'],
                                         colModel:[
                                                 {name:'Pro_Cod',label:'C�d. Int', width:60, sorttype:"int",align:'center'},
                                                 {name:'Iva_Cod',label:'CodIva', width:20,hidden:true},
                                                 {name:'Ite_Lar',label:'Producto', width:200},                                                 
                                                 {name:'Vet_Can',label:'Cant.', width:40, align:"right"},
                                                 {name:'Vet_Pru',label:'P. Unitario', width:60, align:"right", summaryRound: 4,formatter:"currency",
                                                    formatoptions: {prefix:'', thousandsSeparator:',',decimalSeparator:'.',decimalPlaces: 4, defaultValue: '0.0000'}},
                                                 {name:'Importe',label:'Importe', width:70,align:"right", summaryRound: 2,formatter:"currency",
                                                    formatoptions: {prefix:'', thousandsSeparator:',',decimalSeparator:'.', defaultValue: '0.00'}},
                                                 
                                                 {name:'Iva_Por',label:'IVA', width:20,align:"right"},
                                                 {name:'Pro_Obs',label:'Observaci�n', width:120}		
                                         ],
                                         pager: "#plist486",
                                         footerrow:true,
                                         viewrecords: true,hidegrid:false,                                                                                 
                                         caption: "Detalle Ventas",
                                         loadComplete: function (data) { 
                                                
                                         }
                                 });
                                 $("#list486").jqGrid('setGroupHeaders', {
                                   useColSpanStyle: true, 
                                   groupHeaders:[
                                         {startColumnName: 'Vet_Can', numberOfColumns: 4, titleText: '<em>Precio</em>'}
                                   ]	
                                 });
                                 var grid='list486', $footRow = $("#gbox_"+grid+" #gview_"+grid+" .ui-jqgrid-sdiv .footrow");
                                 $footRow.find('>td').css("border-right-color", "transparent");  
                                 $footRow.find('>td[aria-describedby="list486_Pro_Obs"]').css("border-right-color",'1px solid #789');
                                 var descHtml='<div class="footerFact"><label>Subtotal:</label><label>Tarifa 0%:</label><label>Tarifa 12%:</label><label>I.V.A.:</label><label>TOTAL:</label>';
                                 var tablaHtml='<div class="footerFact"><input id="subtotal" type="text"  readonly/><input  id="sinIva" type="text"  readonly/><input  id="conIva" type="text"  readonly/><input  id="iva" type="text"  readonly/><input  id="total" type="text"  readonly/>';
                                 $("#list486").jqGrid('footerData', 'set',{Vet_Pru:descHtml,Importe:tablaHtml},false);
                                 
                                 setTimeout(function (){updateTotal();},150);
                                 $.createDatePickers('.isDatePicker');
                                 $.createDialog('#successDialog',135,475); 
                                 <?php if($rs_infoEmpresa['Cof_Con']=='S'){ ?>
                                     $( "#Caj_Fec" ).datepicker( "option", "minDate", '<?php echo $periodo['Pec_Fei']; ?>' );
                                     $( "#Caj_Fec" ).datepicker( "option", "maxDate", '<?php echo $periodo['Pec_Fef']; ?>');
                                 <?php } ?>    
                              });
                              function setCuenta(value){
                                  var periodo=value.split("*");
                                  $( "#Caj_Fec" ).datepicker( "option", "minDate",periodo[2] );
                                  $( "#Caj_Fec" ).datepicker( "option", "maxDate",periodo[3] );
                                  $.get('<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>',{cuentas:true,Pla_Cod:periodo[1]}, function(response){
                                        $( "#Ban_Cod" ).html(response['html']);
                                  },'json').fail(function(error) { $.alert("El Servidor ha fallado en responder!");});
                              }
                              function updateTotal(){
                                    var grid=$('#list486'),rows= grid.jqGrid('getRowData');
                                    var max = rows.length,conIva=0,sinIva=0,subtotal=0,iva=0,total=0;
                                    for(var i=0;i<max;i++){       
                                        subtotal=subtotal+rows[i]['Importe']*1;
                                        if(rows[i]['Iva_Por']*1>0){
                                            conIva=conIva+rows[i]['Importe']*1;
                                            iva=iva+rows[i]['Importe']*rows[i]['Iva_Por']/100;
                                        }else {sinIva=sinIva+rows[i]['Importe']*1;}
                                    }
                                    total=subtotal+iva;
                                    
                                    $('#subtotal').val(subtotal.toFixed(2));
                                    $('#sinIva').val(sinIva.toFixed(2));
                                    $('#conIva').val(conIva.toFixed(2));
                                    $('#iva').val(iva.toFixed(2));
                                    $('#total').val(total.toFixed(2));
                              }
                              function validaVetNum(){  
                                    var numAnt=$("#Vet_Num").val();
                                    if(numAnt!==''&&numAnt!=='0'){                                       
                                        $.get('<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>',{'Caj_Fec':$('#Caj_Fec').val(),'valVetNum': numAnt}, function(response){
                                            if(response['success']===true){
                                                $("#Vet_Num").alertMsg();
                                                if(response['valid']===false){
                                                    $("#Vet_Num").val('').alertMsg('El N�mero de Factura <b>'+response['Vet_Num']+'</b> no esta <b>Autorizado</b>.');
                                                    $("#Vet_Num").focus();
                                                }
                                                if(response['exist']===true){
                                                    $("#Vet_Num").val('').alertMsg('El N�mero de Factura <b>'+response['Vet_Num']+'</b> ya esta <b>Registrado</b>.');
                                                    $("#Vet_Num").focus();
                                                }
                                            }else {numChe=0;$("#NumChe").val(numChe);$.alert("No se logro obtener n&uacutemero del cheque");}                                
                                        },'json').fail(function(error) { $.alert("El Servidor ha fallado en responder!");});;        
                                    }else{$("#Vet_Num").alertMsg('El N�mero de <b>Factura</b> es incorrecto.');}  
                                }
                                function saveForm(){
                                    var data=$('#factForm').getData('saveForm');
                                    data['Vet_Tot']=$('#total').val();
                                    data['Iva_Tot']=$('#iva').val();
                                    data['list'] = $("#list486").getGridBatch();
                                    $.post('<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>',data, function(response){
                                        if(response['success']===true){
                                            <?php if($rs_infoEmpresa['Cof_Con']=='S'){ ?>                                                
                                                $('#impCompr').attr('href',response['link']);
                                                $('#successDialog').dialog('open');
                                            <?php }else{ ?>
                                                $.alert('El Registro se Guardo Con Exito!');
                                            <?php } ?>
                                            $('#btnGuardar').attr('disabled','disabled');
                                        }else{
                                            $.alert('No se logro guardar el Registro!. '+response['message']);
                                        }
                                    },'json').fail(function(error) { $.alert("El Servidor ha fallado en responder!");});                                     
                                }
                           </script>
                           <style>
                            .footerFact{text-align:right;width: 100%;}
                            .footerFact input[type=text],.footerFact label{height:19px;width:95%;display: block;margin-bottom:2px !important;margin-top:2px !important;text-align:right;}
                            .footerFact label{height:19px;line-height:18px;}                            
                            .footerFact table{display: inline-block;width: 100%;}
                            .footerFact table tr td{border:0 !important;width: 100%;}
                            .footerFact table tr td input{width: 95%;text-align:right;}

                            </style>
                        </fieldset>
                    </div>
                    <!--INICIO DEL DIALOGO IMPRIMIR --> 
                        <div id="successDialog"  title="Mensaje del Sistema">  
                            <center><h4>El Comprobante se ha registrado con Exito!</h4></center>  
                            <center id="printCheque"></center>
                            <center> 
                                <button type="button" onclick="$('#successDialog').dialog('close');" class="btn btn-inverse btn-sm" style="display: inline;" ><i class="glyphicon glyphicon-remove"></i> <span>Cerrar</span></button>            
                                <a id="impCompr" target="_blank" href=""  style="display: inline;" title="Imprimir Comprobante"><span  class="btn btn-primary btn-sm"> <i class="glyphicon glyphicon-print"></i> <span>Imprimir</span></span> </a>               
                            </center>        
                        </div>
                    
                        <script type="text/javascript">
                           
                        </script>
                    <!-- FIN DEL DIALOGO CLIENTE-->
                    <?php } ?>
                    <?php if(!isset($ND_Ventas)){ ?>

                    <?php 
                        /**
                        * Evalua si el usuario es un vendedor 
                        */
                        if (count($row_rs_vendedor) > 0)
                        {                                
                           
                                               
                    ?>
                    
                        <form  id="formCompTemp" action="javascript:$('#list').Search('#formCompTemp','ajaxND_Ventas')" class="form-horizontal normal">
                           <div class="col-xs-6">
                                
                                    <fieldset class="exa-fieldset">
                                        <!-- Form Name -->
                                        <legend class="Titulos2">Seleccione Cliente</legend>
                                        <div class="form-group">
                                            <label class="col-xs-2 control-label label-xs" for="cliente">C�dula/R.U.C:</label>  
                                            <div class="col-xs-6">
                                                  <div class="input-group input-group-xs">                                                
                                                      <input type="text" id="Cli_Cod" name="Cli_Cod" value="" style="display: none" />
                                                      <span id="cedula" class="form-control">Seleccione Cliente..</span>
                                                      <span class="input-group-btn">
                                                          <button class="btn btn-success" onclick="$('#cliDialog').dialog('open');" type="button"><span class="glyphicon glyphicon-check" title="Buscar Clientes"></span></button>
                                                      </span>
                                                  </div><!-- /input-group -->                                          

                                            </div>
                                            <div class="col-md-1"><a onclick="clearCliente();" title="Quitar Proveedor" class="btn btn-success btn-xs pull-right"><i class="glyphicon glyphicon-new-window"></i></a></div> 
                                          </div>
                                          <div class="form-group">
                                            <label class="col-xs-2 control-label label-xs" for="cliente">Cliente:</label>  
                                            <div class="col-xs-10">                                    
                                                  <span id="cliente" class="form-control input-xs"></span>

                                            </div>
                                          </div>
                                          <div class="form-group">  
                                            <label class="col-xs-2 control-label label-xs" for="direccion">Direcci�n:</label>  
                                            <div class="col-xs-10">                                    
                                                    <span id="direccion" class="form-control input-xs"></span>

                                            </div>                                 
                                          </div>                                
                                    </fieldset>
                                
                            </div>
                            <div class="col-xs-6">
                                 
                                             
                                    <fieldset class="exa-fieldset">
                                        <!-- Form Name -->
                                        <legend class="Titulos2">Filtros</legend>
                                         <div class="col-sm-9">                                        
                                            
                                            <!-- Select Basic -->
                                            <div class="form-group">
                                              <label class="col-xs-2 control-label label-xs " for="Tic_Cod">Docum.:</label>
                                              
                                                <div class="col-xs-6">
                                                    <select name="Tic_Cod" id="Tic_Cod" class="form-control input-xs" required onchange="this.form.submit()">
                                                      <?Php
                                                      foreach($row_tipo_compr as $row)
                                                      { if($row['Tic_Sri']=='0'){ $Tic_Cod=$row['Tic_Cod'];?>
                                                      <option  <?Php if ($Tic_Cod == $row['Tic_Cod']){ echo "selected"; } ?> value="<?Php echo $row['Tic_Cod']; ?>"><?Php echo $row['Tic_Des']; ?></option>
                                                      <?Php
                                                      }}
                                                      ?>
                                                    </select>
                                              </div> 
                                              
                                            </div>
                                            <div class="form-group">  
                                                <label class="col-xs-2 control-label label-xs " for="Fec_Ini">Desde:</label>  
                                                <div class="col-xs-4">                                    
                                                    <input name="Fec_Ini" id="Fec_Ini" type="text" class="form-control input-xs"/>
                                                </div>                                 
                                                <label class="col-xs-2 control-label label-xs" for="Fec_Fin">Hasta:</label>  
                                                <div class="col-xs-4">                                    
                                                    <input name="Fec_Fin" id="Fec_Fin" type="text" class="form-control input-xs"/>
                                                </div>                                 
                                              </div>
                                             </div>
                                              <div class="col-md-3" style="padding-top: 10px;">
                                                  <div class=""><button type="button"  onclick="this.form.submit()" class="btn btn-sm btn-success" title="Ejecutar Búsqueda"><span class="glyphicon glyphicon-search"></span> &nbsp;Filtrar</button></div>
                                              </div>
                                    </fieldset>
                                 
                            </div>    
                        </form>
                    
                   
                    <div class="col-sm-12">  
                        <fieldset class="exa-fieldset">                           
                           <legend class="Titulos2">Listado de Notas de Venta</legend> <!-- Form Name -->
                           <table id="list"></table>
                           <div id="listPager"></div>
                        </fieldset>
                        <div style="" class="">                            
                            <button type="button" class="btn btn-sm btn-primary start" onclick="send();/*if($('#list').jqGrid('getCol', 'Pago', false, 'sum')===0){$.alert('El valor del Pago es Inválido');}else{SelectFact();}*/" title="Gestionar Notas de Venta"> <span class="glyphicon glyphicon-floppy-open"></span>&nbsp; <span>Facturar Notas de Venta</span></button>
                            <form id="formNDVentas" method="post" action="<?Php echo $_SERVER['PHP_SELF']; ?>">
                                <input id='ND_Ventas' name='ND_Ventas' value='' style="display: none" >
                            </form>
                            <script>
                                   function send(){
                                       var nd=new Array();
                                       var grid=$('#list'),rows= grid.jqGrid('getRowData');
                                        for(var i=0;i<rows.length;i++){                                
                                            if(rows[i].act==="Yes") 
                                            {nd.push(rows[i]['Vet_Cod']);}
                                        }                                         
                                       $('#ND_Ventas').val(nd.join(','));
                                       if($('#ND_Ventas').val()!=='')
                                        $('#formNDVentas')[0].submit();
                                       else
                                         $.alert('Debe seleccionar al menos una Nota de Venta!');  
                                   }
                            </script>
                        </div>
                    </div>
                    <script>
                        $(document).ready(function () { 
                        var compGrid=$("#list");
                        compGrid.jqGrid({
                            url: '<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>',
                            mtype: "GET", datatype: "local", regional : 'es',//ajaxRowOptions: { async: true },
                            //postData: $("#form1").getData("ajaxGrid"),
                            autowidth : true, shrinkToFit: true, height: 270,responsive:true,
                            cmTemplate: {sortable:false /*,editrules: {edithidden: true}*/},
                            colModel: [                               
                                { label: 'Cód.Int.', name: 'Vet_Cod', key: true, hidden:true,viewable:true }, 
                                { label: 'Fecha', name: 'Caj_Fec',align:"center", width: 40  },                                 
                                /*{label:'Pld_Cod.',name:"Pld_Cod",hidden:true},
                                {label:'Pld_Cdc.',name:"Pld_Cdc",hidden:true},
                                {label:'Pld_Des.',name:"Pld_Des",hidden:true},*/
                                { label: 'C�dula/R.U.C.', name: 'Prs_Ced', width: 55, align:"center"},
                                { label: 'Cliente', name: 'cliente', width: 100},
                                { label: 'Observaci�n', name: 'Vet_Obs', width: 80},
                                { label: 'Pago', name: 'Vet_Pag', width: 80,hidden:true},
                                { label: 'Valor', name: 'Vet_Tot', width: 40, align: 'right', decimalPlaces: '2', summaryRound: 2,formatter:"currency",
                                        formatoptions: {prefix:'', thousandsSeparator:',',decimalSeparator:'.'},summaryTpl: "Total: {0}",summaryType: "sum" 
                                },
                                { label: 'Descto.', name: 'Descuento', width: 30, align: 'right', decimalPlaces: '2', summaryRound: 2,formatter:"currency",
                                        formatoptions: {prefix:'', thousandsSeparator:',',decimalSeparator:'.'},summaryTpl: "Total: {0}",summaryType: "sum" 
                                }, 
                                { label: 'SubTotal', name: 'SubTotal', width: 45, align: 'right',  decimalPlaces: '2', summaryRound: 2,
                                        formatoptions: { thousandsSeparator:',',decimalSeparator:'.'},summaryTpl: "Total: {0}",summaryType: "sum" ,
                                        formatter: function (cellValue, options, rowObject) { return $.fn.fmatter.call(this, "number",(rowObject.Vet_Tot-rowObject.Descuento), options);}
                                }, 
                                { label: 'IVA', name: 'Iva', width: 35, align: 'right',  decimalPlaces: '2', summaryRound: 2,formatter:"currency",
                                        formatoptions: {prefix:'', thousandsSeparator:',',decimalSeparator:'.'},summaryTpl: "Total: {0}",summaryType: "sum" 
                                },                                 
                                { classes:'columnHighlight2',label: 'Total', name: 'Total', width: 50, align: 'right',  decimalPlaces: '2', summaryRound: 2,
                                        formatoptions: {thousandsSeparator:',',decimalSeparator:'.'},summaryTpl: "Total: {0}",summaryType: "sum" ,
                                        formatter: function (cellValue, options, rowObject) { return $.fn.fmatter.call(this, "currency",(rowObject.Vet_Pag*1+rowObject.Iva*1), options);}
                                }, 
                                { label:'<center><i class="ui-icon ui-icon-circle-check"></i></center>', name: 'act', width: 15, align: 'center',viewable: false, formatter: 'checkbox',
                                    formatoptions: { disabled: false },resizable:false
                                },
                                {classes:'columnHighlight1', label: 'No. Docum.', name: 'Fac_Num', width: 70, align:"center"}
                                 
                            ],     
                            footerrow: true, userDataOnFooter: false,
                            rowNum: 10000000, pager: "#listPager", gridview: true, rownumbers: true, viewrecords: true, pgbuttons: false,pgtext: null,                           
                            onSelectRow: function(rowid, e) { compGrid.resetSelection();},
                            loadComplete: function (data) { 
//                                 var grid=$(this), iCol = grid.getColumnIndexByName('act'), rows = this.rows, i, c = rows.length;
//                                updateTotals(grid);                                
//                                for (i = 0; i < c; i += 1) {                                    
//                                    $(rows[i].cells[iCol]).click(function (e) {                                        
//                                        updateSaldos(grid);updateTotals(grid);    
//                                    });
//                                }  
//                                var total = data.records;
//                                    for(var i=0;i<total;i++){       
//                                        if(data.rows[i]['vencimiento'] ==='Vencido')
//                                            $("#"+data.rows[i].Cpp_Cod).css("background", "#FADDDD");
//                                        if(data.rows[i]['vencimiento'] ==='Pagado')
//                                            $("#"+data.rows[i].Cpp_Cod).css("background", "#DDFAE2");
//                                       
//                                    }
                            },                            
                            subGridOptions: {
                                "plusicon"  : "ui-icon-triangle-1-e","minusicon" : "ui-icon-triangle-1-s","openicon"  : "ui-icon-arrowreturn-1-e","reloadOnExpand" : false,"selectOnExpand" : true
                            },subGrid: true,multiselect: false,
                            subGridRowExpanded: function(subgrid_id, row_id) {
                                var subgrid_table_id = subgrid_id+"_t";         
                                $("#"+subgrid_id).html("<table id='"+subgrid_table_id+"' class='scroll'></table>");
                                $("#"+subgrid_table_id).jqGrid({
                                        url:"<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>?ajaxSubgrid="+row_id,datatype: "json",regional : 'es',
                                        autowidth : true, shrinkToFit: true,cmTemplate: {sortable:false},//colNames: ['No','Item','Qty','Unit','Line Total'],
                                        colModel: [
                                                {label:'Cod.Int.',name:"Cpp_Cod",width:80,key:true,align:"center",hidden:true},
                                                {label:'Cod.Int.',name:"Com_Cod",width:80,key:true,align:"center",hidden:true},
                                                {label:'No. Compr.',name:"Com_Codigo",width:45,align:"center"},
                                                {label:'Fecha',name:"Pag_Fec",width:45,align:"center"},
                                                {label:'Valor',name:"Pag_Val",width:45, align: 'right', formatter:'currency', decimalPlaces: '2', formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.'}},
                                                {label:'Observación',name:"Pag_Obs",width:100},
                                                {label:'Tipo',name:"Pag_Des",width:50,align:"center"},                      
                                                    { label:'<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 18, align: 'center',viewable: false,
                                                        formatter:function (cellvalue, options, rowObject) { 
                                                            var clic='selectDetalle('+rowObject.Cpp_Cod+','+rowObject.Com_Cod+');';
                                                            return  '<span class="btn btn-info btn-xs" title="Seleccionar" onclick=\''+clic+'\'><i class="glyphicon glyphicon-info-sign"></span>'; 
                                                        }
                                                    }
                                        ],beforeSelectRow: function(rowid, e) {return false;},
                                        rowNum:10000000, pager: "",height: '100%'
                                });                                
                            }
                        });                        
                        compGrid.navGrid('#listPager',{ edit: false, add: false, del: false, search: false, refresh: true, view: false, position: "left", cloneToTop: false })
                            .jqGrid('navButtonAdd',"#listPager",{ caption:"Marcar Todo&nbsp;", buttonicon:"ui-icon-bullet", onClickButton:function(){compGrid.selectAllByComlumn('act',true);updateSaldos(compGrid);updateTotals(compGrid);}, position: "last", title:"", cursor: "pointer"})
                            .jqGrid('navButtonAdd',"#listPager",{ caption:"Desmarcar Todo&nbsp;", buttonicon:"ui-icon-radio-off", onClickButton:function(){compGrid.selectAllByComlumn('act',false);updateSaldos(compGrid);updateTotals(compGrid);}, position: "last", title:"", cursor: "pointer"});
//                            .jqGrid('navButtonAdd',"#listPager",{ caption: "Exportar &nbsp;",buttonicon: "ui-icon-arrowthickstop-1-s",title:"Exportar Excel",
//                                onClickButton: function() {
//                                    compGrid.jqGrid('exportGridExcel',{nombre:"Prueba",hoja:"HOJATEST"});	
//                                },position: "last"
//                            });
                        compGrid.jqGrid('bindKeys');
                        $('#ND_Ventas').val('');
                        $.createDateRange('#Fec_Ini','#Fec_Fin');
                        //clearFooter();    
                        //$('#rangeDates').addClass('disabled').find('input').attr('disabled','disabled');
                       //loadBancos();
                    }); 
                    </script>
                   <?php    
                        }else
                        {
                                echo error_alerta (" Ud. no es un Vendedor autorizado para emitir Facturas o Notas de Ventas", 2);
                        }//Fin de else del if ($total_rs_vendedor > 0) ?>
                    <?php } ?>
                </div>    
              
            
        </div>
    </div>
    <!--INICIO DEL DIALOGO BUSCAR CLIENTE--> 
    <div id="cliDialog" title="B�squeda de Clientes">  
      <form class="form-horizontal normal"> 
        <fieldset>
                <legend>Filtros</legend>
                <div class="form-group">
                    <label class="col-md-2 control-label label-xs">Filtrar Por:</label>  
                    <div class="col-md-8 radioset" >
                          <input id="rad1" name="op_opciones" type="radio" value="d" checked="" onclick="setfocus(this.form.search)" alt="" /><label for="rad1">&nbsp;&nbsp;Apellido&nbsp;&nbsp;</label>
                          <input id="rad2" name="op_opciones" type="radio" value="c" onclick="setfocus(this.form.search)" alt="" /><label for="rad2">&nbsp;&nbsp;C�dula/R.U.C.&nbsp;&nbsp;</label>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-md-2 control-label">B&uacute;squeda:</label>
                    <div class="col-md-7" >                 
                      <div class="input-group">                        
                        <input name="search" onkeydown="if (event.keyCode === 13) this.form.submit()" type="text" size="50" maxlength="50" placeholder="Ingrese cliente a buscar..." autofocus class="form-control input-sm " /><input type="text" style="display:none"/>
                        <span class="input-group-btn"><button type="button" onclick="this.form.submit()" class="btn btn-success btn-sm" title="Buscar Cliente" ><span class="glyphicon glyphicon-search"></span> <span> Buscar</span></button></span>
                      </div><!-- /input-group -->                          
                    </div>                    
                </div>
        </fieldset>  
       </form>    
    </div>
    <script type="text/javascript">
             $(document).ready(function() {               
                    $.createSearchDialog('#cliDialog',[
                            { label: 'C�d.Int.', name: 'Cli_Cod', key: true,hidden:true,viewable: true },                                
                            { label: 'C�dula/R.U.C.', name: 'Prs_Ced', width: 50 },                      
                            { label: 'Cliente', name: 'cliente', width: 190, cellattr: function (rowId, tv, rawObject, cm, rdata) { return 'style="white-space: normal;"'; }},                   
                            { label: 'Dirección', name: 'Prs_Dir',hidden:true,viewable: true },                      
                                { label:'<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 18, align: 'center',viewable: false,
                                    formatter:function (cellvalue, options, rowObject) { 
                                        var clic='selectCliente($("#cliGrid").jqGrid("getRowData",'+rowObject.Cli_Cod+'))';
                                        return  '<span class="btn btn-success btn-xs" title="Seleccionar" onclick=\''+clic+'\'><i class="glyphicon glyphicon-arrow-right"></span>'; 
                                    }
                                }
                        ]);  

            }); 
            function selectCliente(data){
                $('#Cli_Cod').val(data['Cli_Cod']);
                $('#cedula').html(data['Prs_Ced']);
                $('#cliente').html(data['cliente']);
                $('#direccion').html(data['Prs_Dir']);
                $('#cliDialog').dialog('close');
                <?php if(!isset($ND_Ventas)){ ?>
                    $('#list').Search('#formCompTemp','ajaxND_Ventas');
                <?php } ?>        
            }
            function clearCliente(){
                $('#Cli_Cod').val('');
                $('#cedula').html('');
                $('#cliente').html('');
                $('#direccion').html('');
                $('#list').Search('#formCompTemp','ajaxND_Ventas');                     
            }
    </script>
</BODY>
</HTML>