<?php 	
/**
* Descripci?n: Permite registrar facturas de compra, retenciones, y autom?ticamente se genera 
* el comprobante de egreso/diario y los cheques
* Fecha de actualizaci?n:	2009-09-20  Desarrollador: Lewis Chimarro
* Fecha de actualizaci?n:	2010-08-05  Desarrollador: Lewis Chimarro
* Fecha de actualizaci?n:	2011-11-10  Desarrollador: Lewis Chimarro
* Fecha de actualizaci?n:	2012-08-10  Desarrollador: Lewis Chimarro
* Fecha de actualizaci?n:	2013-02-08  Desarrollador: Lewis Chimarro
* Fecha de actualizaci?n:	2013-05-15  Desarrollador: Jose Cumbicos
* Fecha de actualizaci?n:	2014-06-19  Desarrollador: Jose Cumbicos
*/
require_once('../../administrador/LOGICA/seguridad.php'); 
require_once('../LOGICA/fac_log_compras.php'); 
require_once('../../componentes/LOGICA/logica.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');	
require_once('../../Librerias/postclass.php');

/**
* Creacion del Objeto de conexion 
*/
$obBD_conexion = new Class_Log_Conexion_Comt($Ses_Dat_Dis);
/**
* Creacion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Comt; 	  
/** 
* Creaci?n del objeto para evitar el reenvio 
*/
$thisPost = new Post_Block;

$hoy = date("Y-m-d");

if(isset($Check_Iva)){ 
    $responce['varIvas']=(/*!empty($Tic_Sri)&&('0'.$Tic_Sri)*1==4&&*/$Cop_Fec>'2016-05-31');
    if($responce['varIvas'])
        $responce['ivas']  = $obBD_con1->getArrayConsulta(1105, '', $obBD_conexion);
    else
        $responce['ivas']  = $obBD_con1->getArrayConsulta(1100, $Cop_Fec, $obBD_conexion); 
    $responce['total']=count($responce['ivas']);
    $responce['options']='';
    foreach ($responce['ivas'] AS $row){
        $responce['options']=$responce['options'].'<option value="'.$row['Iva_Cod'].'">'.$row['Iva_Por'].' %</option>';
    }
    $responce['success']=true;
    echo json_encode($responce);exit();
}
if(isset($liquida)){ 
    $responce['total']  = $obBD_con1->getRowConsulta(1080, $PrvCod.'*'.$SriCod.'*'.$PecFei.'*'.$PecFef, $obBD_conexion); 
    $responce['success']=true;
    echo json_encode($responce);exit();
}
if(isset($valChe)){ 
    $valid=true;$contar=array();
    foreach($valChe as $che){
        $conteo = $obBD_con1->getRowConsulta(1079, $che['cod'].'*'.$che['num'], $obBD_conexion);
        $ultimo = $obBD_con1->getRowConsulta(1078, $che['cod'], $obBD_conexion);
    if($conteo['conteo']==0){$valid=($valid&&true);/*$contar['msg']=$contar['msg'].'*'.$che['num'];*/} else {$valid=($valid&&false);$contar['msg']=$contar['msg'].' El Cheque No. '.$che['num'].' de '.$che['ban'].' ya existe, sigue: '.($ultimo['Che_Num']+1)."\n";}
    }  
    $contar['success']=true;
    $contar['valid']=$valid;
    echo json_encode($contar);exit();
}

if($Ses_Prs_Cod=='1')
{				
	//$Cop_Cod='1443'; //74
	//require_once("../COMPONENTES/tesXmlRetencionElectronica_1.0.php");		
	//echo $claveAcceso; 
}



/* Llamado a componente ajax */
//require_once("../../componentes/FRONT/ajax_con_costos.php"); esta pendiente por mejorar

/**
*	Consultamos si lleva contabilidad
*/
$llevarContabilidad = $obBD_con1->getRowConsulta(1041, $Ses_Emp_Cod,$obBD_conexion);

/**
* Llamado a componente ajax 
*/
 if($llevarContabilidad['Cof_Con']=='S'){
    require_once("../COMPONENTES/ajaxComBusRentaIva_1.0.php");
 }else{
    require_once("../COMPONENTES/ajaxComBusRentaIva.php");
 }

/**
* Llamado a componente ajax para las cuentas contables
*/
require_once("../../componentes/FRONT/ajaxConBuscarcta.php"); 

/**
* Llamado a componente ajax para verificar si las compras estan repetidas por proveedor
*/
require_once("../COMPONENTES/ajaxComConNumCom.php"); 

/**
* Llamado a componente ajax cargado en un combo para cheques
*/
require_once('../../componentes/FRONT/ajaxConBuscarctaCombo.php');
/**
* Llamado a componente ajax para verficar si existe el numero de retencio 
*/
require_once("../COMPONENTES/ajaxComConNumRet.php");  
/**
* Llamado de componentes Ice
*/
require_once('../COMPONENTES/ajax_tesComRubrosIce.php');
/** 
* Definici?n de un valor constante para la variable tipo_compr 
*/

define(tipo_compr, 6); //Tipo de comprobante de la retencion 
define(nota_venta, 2); //Tipo de comprobante de la nota de venta
define(tiquetes, 8); //Tipo de comprobante de la TIQUETES O VALES EMITIDOS POR MAQUINAS REGISTRADOR


/*
* consultamos tipo de documento para /notas de credito
*/
if(isset($ajax_CodDoc))
{
	/**
	* Consulta del tipo de comprobante
	*/
	if($Tic_Sri!='')
	{
		$row_rs_CodDoc = $obBD_con1->getRowConsulta(1054, $Tic_Sri,$obBD_conexion);
		if($row_rs_CodDoc['Tic_Cod']!="")
		{
			echo  "&nbsp;".$row_rs_CodDoc['Tic_Des'];
		}else{
			echo "Ninguno";
		}
	}
	
exit();	
}
/**
* Cargado de Informaci?n a trav?s de AJAX de las cuentas contables
*/
if (isset($ajax_cuenta))
{
	/**
	* Consulta las cuentas 
	*/
	$row_rs_buscli = $obBD_con1->getRowConsulta(249, $ajax_cuenta.'*'.$Ses_Emp_Cod.'*'.$Pla_Cod,$obBD_conexion);

	if(count($row_rs_buscli) > 0)
	{		$cuenta=$row_rs_buscli['Pld_Des'];
			$codigo=$row_rs_buscli['Pld_Cod'];
	}else{	$cuenta="Cuenta Inexistente";
			$codigo=0;		}	
	if (isset($cuenta))
	{ $return_value='<?xml version="1.0" standalone="yes"?><cuenta><descripcion>'.utf8_encode($cuenta).'</descripcion><codigo>'.$codigo.'</codigo></cuenta>';
	} 	header('Content-Type: text/xml'); 
		echo $return_value;
		exit();
}//Fin del if (isset($ajax_cuenta))

/**
* Condicion para evaluar cuando mostrar los periodos contables 
*/
if (isset($hdd_Pec_Cod))
{ 	
	/**
	* Evitar el reenvio de formularios 
	*/	
	if (isset($_POST['postID'])&&!empty($_POST['postID'])&&$thisPost->postBlock($_POST['postID'])) { 	
	   if(isset($hdd_save) && !isset($hdd_volver))
	   {
	   	   /**
		   * creacion del objeto mysql para las inserciones 
		   */
		   $obBD_ins1 =  new Class_Log_Datos_Comt;
		   $obBD_ins2 =  new Class_Log_Datos_Comt;
                   $hoy = date("Y-m-d");
		   /**
		   * inicio de la transaccion 
		   */
                   
           $mese = explode('-', $Cop_Fec);					
		   $Cop_Sec= $obBD_ins1->codigoSecMensualAuto($Pec_Cod, $mese[1], $obBD_conexion);
           $Tic_Sri=$obBD_ins1->getRowConsulta(1076,$Tic_Cod, $obBD_conexion);
		   $infoTicDes=$Tic_Sri['Tic_Des'];
           $NCRED=false;
           if(isset($Tic_Sri['Tic_Sri'])&&$Tic_Sri['Tic_Sri']*1==4) $NCRED=true;
                   
		   $obBD_ins1->inicio_transaccion($obBD_conexion->conexion);
		  
		   /**
		   * Inserci?n la cabecera de la factura de compra
		   */
		   $obBD_ins1->operacionobBD(1083, $Tic_Cod.'*'.$codigo.'*'.$Ciu_Cod.'*'.trim($Cop_Num).'*'.trim($Cop_Aut).'*'.$Cop_Fec.'*'.$hoy.'*'.trim($Cop_Obs).'*'.$Cop_Cad.'*'.$Cop_Imp.'*'.$Tri_Cod.'*'.$Cop_Des.'*'.$Pec_Cod.'*'.$hdd_TipoSri.'*'.$Cop_Ntd.'*'.$Cop_Nns.'*'.$Cop_Nna.'*'.$Vnd_Cod.'*'.$Cop_Sec, $obBD_conexion);  
		   $Cop_Cod = $obBD_ins1->insercionid ($obBD_conexion->conexion);
		   
		   /**
		   *    Operacion Se accede desde Caja chica para relacionar compras y el vale de caja
		   *	la variable [$Ses_Rcb_Cod] proviene desde cch_alt_liquidacion_1.0.php caja chica 
		   */		  
		   if(isset($Ses_Rcb_Cod) && $Ses_Rcb_Cod > 0)
		   {
			   /**
			   *	Consultamos los valores del vale para su modificacion
			   */
			   $row_rs_recibo = $obBD_con1->getRowConsulta(1044, $Ses_Rcb_Cod, $obBD_conexion); 
			   			  
			   /**
			   *	Actualizamos el VALE DE CAJA agregando el "Cambio" respectivo
			   */
			   $obBD_ins1->operacionobBD(1045, ($row_rs_recibo['Rcb_Tot'] - $Val_Pcc).'*'.$Ses_Rcb_Cod, $obBD_conexion);
			  			   
			   /**
			   *	Guardamos y relacionamos compras con Vales de caja chica
			   */
			   $obBD_ins1->operacionobBD(1042, $Ses_Rcb_Cod.'*'.$Cop_Cod, $obBD_conexion); 			   			   			    
		   }  //fin del if(isset($Ses_Rcb_Cod) && $Ses_Rcb_Cod > 0)

		   
		   /**
		   *   CONTROL AUTOMATICO PARA GENERAR COMPROBANTES DE EGRESO/DIARIO    
		   *   1 Contado - SI Retenci?n = NO COMPROBANTE EGRESO
		   *   1 Contado - NO Retenci?n = SI COMPROBANTE EGRESO *-*
		   *   2 Contado - SI Retenci?n = NO COMPROBANTE DIARIO
		   *   2 Contado - No Retenci?n = SI COMPROBANTE DIARIO *-* 
		   */		
		   
		   /**
		   *	If que restringe el guardado de ciertos DATOS cuando la empresa no lleva contabilidad
		   */
		   if($llevarContabilidad['Cof_Con']=='S'){
		   if ($Hdd_Ret == 'N' || $Hdd_Ret == 'S' )
		   {	
		   		/**
				* Inserci?n del Comprobante 
				* Campo del codigo del proveedor 
				*/
				$campo="Prv_Cod"; 				
				/**
				* Consulta para determinar si el comprobante es de Egreso o de Diario 
				*/
				$row_rs_form_compr = $obBD_con1->getRowConsulta(251, $For_Cod, $obBD_conexion); 			

				if (count($row_rs_form_compr) > 0)
				{	
					/**
					* Tipo de comprobante Egreso/Diario 
					*/
					$op = $row_rs_form_compr['Tia_Cod'];
					/**
					* Mes del comprobante 
					*/
					$mes = explode('-', $Com_Fec);
					/**
					* Concepto Standar del  Comprobante de Egreso/Diario 
					*/
					$Com_Con = $Cop_Obs;
					/**
					* Consulta el numero del comprobante de Egreso/Diario 
					*/
					$Com_Num= $obBD_ins1->codigoComprAuto($op, $Pec_Cod, $mes[1], $obBD_conexion); 	

					
					/**
					* grabo el comprobante de compra 
					*/	
					$obBD_ins1->operacionobBD(250, $Pec_Cod.'*'.$codigo.'*'.$Com_Num.'*'.$Com_Fec.'*'.trim($Com_Con).'*'.$op.'*'.$t_rubros.'*'.trim($Cop_Obs).' FACT:'.trim($Cop_Num).'*'.$campo, $obBD_conexion);
					$Com_Cod= $obBD_ins1->insercionid($obBD_conexion->conexion);
										
					
					/**
					* Registra el Iva en caso que sea mayor a cero y el tipo de documento sea diferente de una nota de venta
					*/
					if ($t_iva > 0 and ($Tic_Cod != nota_venta and $Tic_Cod != tiquetes))
					{					
						/**
						* Control para determinar la cuenta del IVA PAGADO del plan de cuentas actual  
						*/
						//$iva_pagado = $iva_hdd;
                                                $iva_pagado =$iva_pag_par;
					}//Fin del if ($t_iva > 0)
				}//Fin del if ($total_rs_form_compr > 0)
				else
				{ ?><script language="javascript">
					alert("Debe configurar el tipo de comprobante: ?Egreso/Diario?");
					</script>					
				<?Php
				}//Fin del else if ($total_rs_form_compr > 0)	
		   }//Fin del if (!($For_Cod == 1 and $Hdd_Ret == 'S'))	
		}// fin del if($llevarContabilidad['Cof_Con']=='S')
		
		$cant=0;
		$ind_rta=0;
		/**
		* defino un contador indice de autoincremento 
		*/
		$indi_auto=0;
		/**
		* Indice para contar la cantidad de arreglos del haber en el comprobante para la renta e iva 
		*/
		$new_clave_r = 0;//Renta
		$new_clave_i = 0;//Iva
		/**
		* Inicializa el arreglo para acumular las retenciones e iva 
		*/
		$array_cnta_renta[0] = 0;
		$array_cnta_iva[0] = 0;
		$Kar_Int=0;
                $iva_al_costo=0;
	    if($llevarContabilidad['Cof_Con']=='S') $auxBan=24; else $auxBan=22;
		foreach ($datos as $puntero => $item)
		{  $cant++;
			  $param[]=$item;
                          
			  if($cant==$auxBan)
			  {  
				 $cant=0;
                                 
				 /**
				 * Grabado del detalle de la factura de compra 
				 * ICE 
				 */
				
				 if ($param[6] == "0*0")//Revisar de donde sale 0*0
				 {	$param[6] = 'NULL';	 }	
				 
				 /**
				 *  Asignar valor Null
				 */
				 if ($param[12] == "")
				 {	$param[12] = 'NULL';	 }	
				 
				 /**
				 * Descuenta 
				 */	
				 if ($param[4] == "")
				 {	$param[4] = 0;		}			
				/* ICE */
                                 if($param[22] == "")
                                {	$param[22] = 'NULL';		}			     
				/**
				* Inserta datos en el detalle de la compra
				*/				
                                if(isset($param[23])&&$param[23]=='S')
                                    $obBD_ins1->operacionobBD(1110,$Cop_Cod.'*'.$param[0].'*'.$param[5].'*'.trim($param[1]).'*'.$param[2].'*'.$param[3].'*'.$param[4].'*'.$param[8].'*'.$param[22].'*'.$param[13].'*'.$param[12].'*'.$param[21].'*S', $obBD_conexion);
                                else
                                    $obBD_ins1->operacionobBD(720,$Cop_Cod.'*'.$param[0].'*'.$param[5].'*'.trim($param[1]).'*'.$param[2].'*'.$param[3].'*'.$param[4].'*'.$param[8].'*'.$param[22].'*'.$param[13].'*'.$param[12].'*'.$param[21], $obBD_conexion);
                                    
				/**
				* Control para I N V E N T A R I O S 
				*/
				/**
				* Verifica que sea un producto tipo bien
				*/
				$row_rs_adquisicio = $obBD_con1->getRowConsulta(1037, $param[21], $obBD_conexion);
				
				/**
				* Pregunta si es de tipo bien el producto B 
				*/
				if (count($row_rs_adquisicio) <> 0)
				{
					/**
					* Pregunta si descuento es vacio 
					*/
					if ($param[6]=="")
					{
						$desc_var=0;
					}
					else
					{
					$desc_var=$param[6]; 
					}
					
					//control para evitar que el inventario no sea alterado cuando el comprobante de venta es de tipo Tic_Sri=00					
					if($Tic_Sri['Tic_Sri']!='00') 
                    {
						$Kar_Int++;
						/**
						* Actualiza el kardex 
						*/
						$obBD_ins1->operacionobBD(1072, '0'.'*'.'0'.'*'.$Vnd_Cod.'*'.$Cop_Cod.'*'.$param[21].'*'.$Cop_Fec.'*'.'00:00:00'.'*'.((!$NCRED)?$param[0]:($param[0]*-1)).'*'.'0'.'*'.'0'.'*'.$param[2].'*'.'0'.'*'.((!$NCRED)?$param[3]:($param[3]*-1)).'*'.$param[4].'*'.$param[5].'*'.'0'.'*'.$Kar_Int, $obBD_conexion);
										
						/**
						* Consulta el Stock 
						*/
						$row_rs_conpro = $obBD_con1->getRowConsulta(1206, $param[21], $obBD_conexion);
						
						/**
						* Actualizo el Stock 
						*/
						//if(!$NCRED)
							$obBD_ins1->operacionobBD(1204, $row_rs_conpro['Stock'].'*'.$param[21].'*'.$Ses_Suc_Cod, $obBD_conexion);
					}
					
				}
				/***********************************************************************/
				/*    CONTROL AUTOMATICO PARA GENERAR COMPROBANTES DE EGRESO/DIARIO    */
				/***********************************************************************/	
				/**	1 Contado - SI Retenci?n = NO COMPROBANTE2 EGRESO
				*	1 Contado - NO Retenci?n = SI COMPROBANTE EGRESO *-*
				*	2 Contado - SI Retenci?n = NO COMPROBANTE DIARIO
				*	2 Contado - No Retenci?n = SI COMPROBANTE DIARIO *-* 
				*/				
			if ($Hdd_Ret == 'N' || $Hdd_Ret == 'S')		
			{	
				/**
				* Entra solo cuando el tipo de documento sea igual a una nota de venta
				* y suma el total del iva a la cuenta asignada al registro de la compra
				*/
				$rubro_d = 0; //Inicializa en cero para cuando hay mas de 1 registro
				if (($Tic_Cod != nota_venta and $Tic_Cod != tiquetes) or $param[11] == 0)//$param[11] => porcentaje de iva)
				{		
					/**
					* Variable para pasar el valor del registro de la compra 
					*/		
					$rubro_d = $param[3];
				}//Fin del if ($Tic_Cod == nota_venta)
				else
				{
					/**
					* Variable para pasar el valor del registro de la compra + iva (iva_pagado) 
					*/
					$rubro_d = $param[3] + round((($param[3] * $param[11])/100),2); //antes estaba ->$t_iva;
				}//Fin del if ($Tic_Cod != nota_venta)
				
					
				
				/**
			    *	If que restringe el guardado de ciertos DATOS cuando la empresa no lleva contabilidad
			    */
				if($llevarContabilidad['Cof_Con']=='S'){
					
					/**
					* Grabado del detalle del comprobante de Egreso/Diario 
					*/
					/**
					* Cuentas del DEBE Activo, Costo o Gasto 
					*/
					$obBD_ins1->operacionobBD(256, $Com_Cod.'*'.(!$NCRED?'D':'H').'*'.$rubro_d.'*'.''.'*'.''.'*'.$param[12], $obBD_conexion);		
                                        //  Para ver el iva al costo
                                       if(isset($param[23])&&$param[23]=='S'){
                                          $iva_al_costo=$iva_al_costo+round((($param[3])*($param[11])/100),2);                                          
                                          $obBD_ins1->operacionobBD(256, $Com_Cod.'*'.(!$NCRED?'D':'H').'*'.round((($param[3])*($param[11])/100),2).'*'.''.'*'.''.'*'.$param[12], $obBD_conexion);		
                                       }
				}								
			}//Fin del if (!($For_Cod == 1 and $Hdd_Ret == 'S'))
			 /***********************************************************************/													
			 /***********************************************************************/
			 /*				GRABADO  DEL DETALLE DE LA RETENCI?N				   */
			 /***********************************************************************/
			 /**
			 * det_compra incrementa en 1 | det_retenc en 1 
			 */
			 /**
			 * Grabado en caso que sea RENTA
			 */
			 if($param[17]!="" || $param[18]!="")
			 {  
			 	/**
				* indice que indica que hay renta 
				*/
				$ind_rta++;
				if($ind_rta==1){ /* inicio if($ind_rta==1){  */
				/********************************/
				/*                              */
				/*  INSERCI?N DE LA RETENCI?N   */
				/*                              */
				/********************************/				
				
				/* Generamos la clave de acceso si la empresa posee emite COMPROBANTES ELECTRONICOS*/
				
				/**
				*  Consultamos informacion de la empresa
				*/
				$rs_infoCliente = $obBD_con1->getRowConsulta(1061, $Aut_Cod, $obBD_conexion);
				/**
				*  Consultamos informacion del proveedor
				*/
				$rs_infoEmpresa = $obBD_con1->getRowConsulta(1060, $Ses_Suc_Cod, $obBD_conexion);
				
				/* Preguntamos si la empresa Genera comprobantes electronicos*/
				if($rs_infoEmpresa['Cof_Gce']=='S') 
				{
						$ceroDoc="";
						for($i=strlen($Ret_Int); $i<=9-1; $i++)
						{
							$ceroDoc=$ceroDoc."0";
						}				
						$TipoAmbienteCE=$rs_infoEmpresa['Cof_Fac'];
						$TipoEmisionCE=$rs_infoEmpresa['Cof_Fte'];
						
						/*Control para generar la clave de acceso de tipo  1=Normal  2=Indisponibilidad de Sistema WebService SRI*/
						if($rs_infoEmpresa['Cof_Fte']=='1')
						{	
							/*clave de acceso de tipo emision NORMAL*/
							$cadena=date("dmY",strtotime($Cop_Fec))."07".$rs_infoEmpresa['Emp_Ruc'].$rs_infoEmpresa['Cof_Fac'].$rs_infoEmpresa['Suc_Sri'].$rs_infoCliente['Pun_Sri'].$ceroDoc.$Ret_Int."12345678".$rs_infoEmpresa['Cof_Fte'];
						}else{
							/*preguntamos si el txt aun posee numeros para usar*/
							if(count(file($Ses_Emp_Cod."/".$rs_infoEmpresa['Cof_Clv']))!=0)
							{	
								$file = file($Ses_Emp_Cod."/".$rs_infoEmpresa['Cof_Clv']);
								/*clave de acceso de tipo emision INDISPONIBILIDAD DEL SISTEMA*/
								$cadena=date("dmY",strtotime($Cop_Fec))."07".$rs_infoEmpresa['Emp_Ruc'].$rs_infoEmpresa['Cof_Fac'].substr($file[0], 14, 23).$rs_infoEmpresa['Cof_Fte'];
							}else{					
								/*clave de acceso de tipo emision NORMAL*/
								$cadena=date("dmY",strtotime($Cop_Fec))."07".$rs_infoEmpresa['Emp_Ruc'].$rs_infoEmpresa['Cof_Fac'].$rs_infoEmpresa['Suc_Sri'].$rs_infoCliente['Pun_Sri'].$ceroDoc.$Ret_Int."12345678".$rs_infoEmpresa['Cof_Fte'];							    				
								$TipoAmbienteCE=$rs_infoEmpresa['Cof_Fac'];
								$TipoEmisionCE="1";												
							}
						}
									
						$factor = 2;
						$suma = 0;
						for($i = strlen($cadena) - 1; $i >= 0; $i--) {
							$suma += $factor * $cadena[$i];
							$factor = $factor % 7 == 0 ? 2 : $factor + 1;
						}
						$dv = 11 - $suma % 11;				
						$dv = $dv == 11 ? 0 : ($dv == 10 ? "1" : $dv);
						$claveAcceso=$cadena.$dv;
				} //fin if($rs_infoEmpresa['Cof_Gce']=='S') 
				
				
				/**
				* Grabo en la base de datos la cabecera de la retenci?n 
				*/
                                if(!isset($Ret_Fec)||$Ret_Fec=='') $Ret_Fec=$Cop_Fec;
				$obBD_ins2->operacionobBD(491, $Cop_Cod.'*'.$Ret_Int.'*'.$Ret_Fec.'*'.trim($Cop_Obs).'*'.tipo_compr.'*'.$Vnd_Cod.'*'.$Aut_Cod.'*'.$claveAcceso, $obBD_conexion);	
				$Ret_Cod=$obBD_ins2->insercionid($obBD_conexion->conexion);
                                if(isset($Ret_Asu) && $Ret_Asu=='S' && $Ret_Cod>0){                            
                                    $obBD_ins1->operacionobBD(1104,$Ret_Cod.'*'.'S',$obBD_conexion); 
                                }else{
                                    $obBD_ins1->operacionobBD(1104,$Ret_Cod.'*'.'N',$obBD_conexion); 
                                } 
				/*********************************/
				/* FIN INSERCI?N DE LA RETENCI?N */
				/*********************************/
				} /* fin if($ind_rta==1)  */
			 }	

			/**
			* Preguntamos si existe un descuento al total del importe 
			*/
			if($Cop_Des>0){ /* inicio if($desc_total>0){ */
				 /**
				 * Calculamos el porcentaje de descuento total 
				 */
				 $des_indivi=($param[3]*$Cop_Des)/100;
			 } /* fin if($desc_total>0){ */
			 else
			 {  
			 	/**
				* Calculamos el porcentaje de descuento individual 
				*/
				$des_indivi=($param[3]*$param[4])/100;
			 }
			 /**
			 * Dismunic?n de descuento individual al importe 
			 */	
			 $renta_grav=$param[3]-$des_indivi;				 
			 
			 /**
			 * Grabado en caso que sea RENTA
			 */
			 if($param[17]!="") 	
			 {	 
				/**
				* incremento en 1 
				*/
				$indi_auto++;
				
				if($llevarContabilidad['Cof_Con']=='S'){
					/**
					* Consultar los codigos de RENTA 
					*/
					$row_renta_sri=$obBD_con1->getRowConsulta(1077, $param[17].'*'.$Pla_Cod.'*'.$Ses_Emp_Cod, $obBD_conexion);
				}else{
					//echo '<br>'.$llevarContabilidad['Cof_Con'];
					$row_renta_sri=$obBD_con1->getRowConsulta(1043, $param[17], $obBD_conexion);
				}
				/**
				* Inserta el detalle de la retenci?n
				*/				
				 $obBD_ins2->operacionobBD(492, $Ret_Cod.'*'.$renta_grav.'*'.$row_renta_sri['Ren_Cod'].'*'.'R'.'*'.$param[13].'*'.$param[8], $obBD_conexion);
				 /**
				 * C?lculo porcentaje RENTA de retenci?n para almacenar como base del comprobante de retenci?n 
				 */
				 $reten_bas_compr=round((($renta_grav*$param[19])/100),2);
				/**
				* Control para A G R U P A R los valores de la retencion 
				*/
				if (in_array($row_renta_sri['Pld_Cod'],$array_cnta_renta))
				{
					/**
					* Devuelve el indice del elemento que se encuentra en el arreglo 
					*/
					$clave = array_search($row_renta_sri['Pld_Cod'], $array_cnta_renta); 
					$array_val_renta[$clave] = $array_val_renta[$clave] + $reten_bas_compr;						
				}//Fin del if (in_array($row_renta_sri['Pld_Cod'],$array_cuentas))
				else					
				{
					/**
					* Crea otra posicion agregar una nueva cuenta del plan de cuentas 
					*/
					$array_cnta_renta[$new_clave_r] = $row_renta_sri['Pld_Cod'];
					$array_val_renta[$new_clave_r] = $array_val_renta[$new_clave_r] + $reten_bas_compr;						
					$new_clave_r++;
				}//Fin del else if (in_array($row_renta_sri['Pld_Cod'],$array_cuentas))
			}//Fin del if($param[17]!="") 
			
			/**
			* Grabado en caso que sea I.V.A. 
			*/
			if($param[18]!="")
			{	/**
				* incremento en 1 
				*/
				$indi_auto++; 
				
				if($llevarContabilidad['Cof_Con']=='S'){
					/**
					* Consultar los codigos de RENTA 
					*/
					$row_renta_sri=$obBD_con1->getRowConsulta(1077, $param[18].'*'.$Pla_Cod.'*'.$Ses_Emp_Cod, $obBD_conexion);
				}else{
					//echo '<br>'.$llevarContabilidad['Cof_Con'];
					$row_renta_sri=$obBD_con1->getRowConsulta(1043, $param[18], $obBD_conexion);
				}			
				
				/**
				* Iva grabada sobre el importe menos descuento 
				*/
				$grava_iva=$renta_grav*$param[11]/100;
				/**
				* renta_grav toma el valor del porcentaje de retenci?n 
				*/
				$renta_grav=$grava_iva;
				$obBD_ins1->operacionobBD(492, $Ret_Cod.'*'.$renta_grav.'*'.$row_renta_sri['Ren_Cod'].'*'.'I'.'*'.$param[13].'*'.$param[8], $obBD_conexion);
				/**
				* C?lculo porcentaje IVA de retenci?n para almacenar como base del comprobante de retenci?n 
				*/
				$reten_iva_compr=round((($renta_grav*$param[20])/100),2);				
				/**
				* Control para A G R U P A R los valores del iva 
				*/
				if (in_array($row_renta_sri['Pld_Cod'],$array_cnta_iva))
				{
					/**
					* Devuelve el indice del elemento que se encuentra en el arreglo 
					*/
					$clave = array_search($row_renta_sri['Pld_Cod'], $array_cnta_iva); 
					$array_val_iva[$clave] = $array_val_iva[$clave] + $reten_iva_compr;						
				}//Fin del if (in_array($row_renta_sri['Pld_Cod'],$array_cnta_iva))
				else					
				{
					$array_cnta_iva[$new_clave_i] = $row_renta_sri['Pld_Cod'];
					$array_val_iva[$new_clave_i] = $array_val_iva[$new_clave_i] + $reten_iva_compr;						
					$new_clave_i++;
				}//Fin del else if (in_array($row_renta_sri['Pld_Cod'],$array_cnta_iva))					
			 }
			 /**
			 *  FIN GRABADO RETENCION 	
			 */
                         //var_dump($param);
			 unset($param);					
			 unset($cant);				 
			 }//Fin del if($cant==15)
                         
		}//Fin del foreach ($datos as $puntero => $item)
				
		/**
	    *	If que restringe el guardado de ciertos DATOS cuando la empresa no lleva contabilidad
	    */
		if($llevarContabilidad['Cof_Con']=='S'){
			/**
			* Recorrido para el grabado de la renta en los comprobantes 
			*/
			for ($i=0;$i<=count($array_cnta_renta)-1;$i++)
			{
				/**
				* Cuentas del HABER del proveedor - Asignado automaticamente 
				*/
                                if($array_cnta_renta[$i]!=0) // Agregado x erik xq graba un Pld_Cod=0
				$obBD_ins1->operacionobBD(256, $Com_Cod.'*'.(!$NCRED?'H':'D').'*'.round($array_val_renta[$i],2).'*'.''.'*'.''.'*'.$array_cnta_renta[$i], $obBD_conexion);	 				
			}//Fin del for ($i=0;$i<=count()-1;$i++)
				
			/**
			* Recorrido para el grabado del iva en los comprobantes 
			*/
			for ($i=0;$i<=count($array_cnta_iva)-1;$i++)
			{			
				if ($array_val_iva[$i]!= "")
				{
					/**
					* Cuentas del HABER del proveedor - Asignado automaticamente 
					*/
					$obBD_ins1->operacionobBD(256,$Com_Cod.'*'.(!$NCRED?'H':'D').'*'.round($array_val_iva[$i],2).'*'.''.'*'.''.'*'.$array_cnta_iva[$i],$obBD_conexion); 
				}				
			}//Fin del for ($i=0;$i<=count()-1;$i++)
                        /* en caso de retenciones asumidas */
                        if(!$NCRED && isset($Ret_Asu) && $Ret_Asu=='S' && $Ret_Pld_Cod!=='')
                            $obBD_ins1->operacionobBD(256,$Com_Cod.'*'.'D'.'*'.round($Riv_Tot,2).'*'.''.'*'.''.'*'.$Ret_Pld_Cod,$obBD_conexion); 
		}//fin del if($llevarContabilidad['Cof_Con']=='S')
		
		/***********************************************************************/
		/*    CONTROL AUTOMATICO PARA GENERAR COMPROBANTES DE EGRESO/DIARIO    */
		/***********************************************************************/
		/** 1 Contado - SI Retenci?n = NO COMPROBANTE EGRESO
		 *  1 Contado - NO Retenci?n = SI COMPROBANTE EGRESO *-*
		 *  2 Contado - SI Retenci?n = NO COMPROBANTE DIARIO
		 *  2 Contado - No Retenci?n = SI COMPROBANTE DIARIO *-* 
		 */				
		   if ($Hdd_Ret == 'N' || $Hdd_Ret == 'S')		
			{										
				/**
				* Grabado del detalle del comprobante de Egreso/Diario 
				*/	
				if ($t_iva > 0 and ($Tic_Cod != nota_venta and $Tic_Cod != tiquetes))//Registra el Iva en caso que sea mayor a cero y el tipo de documento sea diferente de una nota de venta
				{
					
					/**
				    *	If que restringe el guardado de ciertos DATOS cuando la empresa no lleva contabilidad
				    */
					if($llevarContabilidad['Cof_Con']=='S'){	
						/**
						* Cuentas del DEBE Iva Pagado - Asignado automaticamente 
						*/	
                                            if(($t_iva*1-$iva_al_costo)>0)
						$obBD_ins1->operacionobBD(256, $Com_Cod.'*'.(!$NCRED?'D':'H').'*'.($t_iva*1-$iva_al_costo).'*'.''.'*'.''.'*'.$iva_pagado, $obBD_conexion);																
					}
				}//Fin del if ($t_iva > 0)
				/**
				* Control para determinar la cuenta del HABER, Si es Contado o Credito 
				*/
				if ($For_Cod == 2||$For_Cod == '2')//Pago a C R E D I T O
				{
					/* Determina cuenta unica del proveedor en el plan de cuentas */
//					$rs_ccpp_prove= $obBD_con1->consulta(sentencias_comf(253, $obBD_con1->parametros($Pla_Cod)), $obBD_conexion->conexion); 		
//					$row_rs_ccpp_prove = $obBD_con1->registros();
//					$total_rs_ccpp_prove = $obBD_con1->numregistros(); 
					/* Control para que no grabe en caso de no existir configurada la 
					cuenta en el plan de cuentas */
//					if ($total_rs_ccpp_prove > 0) 						
//					{
//						$ccpp_prove = $row_rs_ccpp_prove['Pld_Cod'].'*'.$row_rs_ccpp_prove['Ccp_Cxp'];
//					}//Fin del if ($total_rs_ccpp_prove > 0) 	
//					else
//					{
//						/* Asigna CERO (0) para que se realice un ROLLBAKC */
//						$ccpp_prove = 0;
//					}//Fin del else if ($total_rs_ccpp_prove > 0) 	

//					-$Riv_Tot
					$total_proveedores=$t_rubros-$Riv_Tot;
                                        if(!$NCRED && isset($Ret_Asu) && $Ret_Asu='S')
                                                $total_proveedores=$t_rubros;
					/**
					* Divide la el valor de ccpp_prove 
					*/
					$array_ccpp_prove = explode('*', $ccpp_prove);
					
					/**
				    *	If que restringe el guardado de ciertos DATOS cuando la empresa no lleva contabilidad
				    */
					if($llevarContabilidad['Cof_Con']=='S'){
						/**
						* Cuentas del HABER del proveedor - Asignado automaticamente 
						*/		
						$obBD_ins1->operacionobBD(256, $Com_Cod.'*'.(!$NCRED?'H':'D').'*'.$total_proveedores.'*'.''.'*'.''.'*'.$array_ccpp_prove[0],$obBD_conexion);
                                                if($NCRED){
                                                       $CCPP=$obBD_ins1->getArrayConsulta(1095,$Cop_Nns.'*'.$codigo, $obBD_conexion); 
                                                       if(count($CCPP)==1)
                                                           $obBD_ins1->operacionobBD(1096,$CCPP[0]['Cpp_Cod'].'*1*'. $Com_Cod.'*'.$Cop_Fec.'*'.$total_proveedores.'*N/CREDITO '.$Cop_Num,$obBD_conexion);
                                                }
					}
				/**
				* Evalua si se debe o no registrar la cuenta por pagar, en el caso de caja_chica no se registra 
				*/			
				if ($array_ccpp_prove[1] == 'S')
				{
					/**
				    *	If que restringe el guardado de ciertos DATOS cuando la empresa no lleva contabilidad
				    */
					if($llevarContabilidad['Cof_Con']=='S'){
						/**
						* Control para guardar la CC x PP a PROVEEDORES 
						*/
                                            if(!$NCRED)
						$obBD_ins1->operacionobBD(255, $Com_Cod.'*'.$Cop_Cod.'*'.$Cpp_Ven.'*'.trim($Cpp_Obs), $obBD_conexion);					
					}
				}//Fin del if ($array_ccpp_prove[1] == 'S')
			}//Fin del if ($For_Cod == 2)
			else//Pago a C O N T A D O
			{	
				/** 1 Contado - SI Retenci?n = NO COMPROBANTE EGRESO     -> NO Cheque
				  * 1 Contado - NO Retenci?n = SI COMPROBANTE EGRESO *-* -> SI Cheque
				  * 2 Contado - SI Retenci?n = NO COMPROBANTE DIARIO     -> NO Cheque
				  * 2 Contado - No Retenci?n = SI COMPROBANTE DIARIO *-* -> SI Cheque 
				  */
				/**
				* Determinar cuenta de los bancos 
				*/
				$cant=0;
				 				 
				/**
			    * 	If que restringe el guardado de ciertos DATOS cuando la empresa no lleva contabilidad
			    */ 
				if($llevarContabilidad['Cof_Con']=='S'){
				/**
				* Variable para el control del guardado de la informacion de los anticipos
				*/
				$cruce_anti = false;
                if(isset($datos_ch))
				$auxFlag=0;
				foreach ($datos_ch as $puntero => $item)
				{	$cant++;
					$param[]=$item;
					if ($cant==6)
						{	$cant=0;
							/**
							* Sepacion del banco y la cuenta del plan de cuentas 
							*/
							$Asi_Cod = explode('*',$param[0]);
								
							/*Verifico si la venta a contado posee una cta de tipo caja chica */
							$rsRepConfig=$obBD_ins1->getRowConsulta(1106,$Asi_Cod[1].'*RC', $obBD_conexion); 						
							$total_rsRepConfig=$rsRepConfig['Pld_Cod'] > 0? 1 : 0;
							if($total_rsRepConfig==1 && $auxFlag==0)
							{
								$obBD_ins1->operacionobBD(1107, $Cop_Cod, $obBD_conexion);
								$auxFlag=1;
							}
							
							/**
							* Cuentas del HABER del proveedor - Asignado automaticamente 
							*/
							$obBD_ins1->operacionobBD(256, $Com_Cod.'*'.(!$NCRED?'H':'D').'*'.$param[2].'*'.''.'*'.''.'*'.$Asi_Cod[1], $obBD_conexion);										
							$asiento = $obBD_ins1->insercionid($obBD_conexion->conexion);
																				
							/**
							* Guardado de los cheques 
							*/
															//var_dump($Asi_Cod);
							if($Asi_Cod[2]=="B"&&$param[1]!='') 
							 {									
								
								$obBD_ins1->operacionobBD(307, $codigo.'*'.$Asi_Cod[0].'*'.$asiento.'*'.$param[1].'*'.$param[2].'*'.$param[4].'*'.$param[3].'*'.$param[5], $obBD_conexion);
								
								
							 }/* Fin guardado Cheques */
							 elseif ($Asi_Cod[2] == 'A')
							 {
								$cruce_anti = true;
								// Consulta el detalle de los anticipos a proveedor 
								$rs_det_anticipos = $obBD_con1->consulta(sentencias_comf(193, $obBD_con1->parametros($codigo.'*'.$Asi_Cod[1])), $obBD_conexion->conexion);
								$row_rs_det_anticipos = $obBD_con1->registros();
								$total_rs_det_anticipos = $obBD_con1->numregistros();
																	
								/**
								* Grabado en det_anticipo de cada anticipo 
								*/
								$obBD_ins1->operacionobBD(195, $Asi_Cod[0].'*'.$Cop_Cod.'*'.$param[2], $obBD_conexion);
																	
							 }//Fin del elseif ($Asi_Cod[2] == 'A')
							unset($param);
						}//Fin del if ($cant==7)
				}//Fin del foreach ($datos_ch as $puntero => $item)
				}// Fin del if($llevarContabilidad['Cof_Con']=='S')
			}//Fin del else if ($For_Cod == 2)

			/**
		    *	If que restringe el guardado de ciertos DATOS cuando la empresa no lleva contabilidad
		    */
			if($llevarContabilidad['Cof_Con']=='S'){
				/**
				* Control para enlazar las COMPRAS con los COMPROBANTES 
				*/
				$obBD_ins1->operacionobBD(254, $Com_Cod.'*'.$Cop_Cod, $obBD_conexion);	
			}
			
			/* Control para los anticipos */
			if ($cruce_anti == true)
			{
										
			}//Fin del if ($cruce_anti == true)					 						
		}//Fin del if (!($For_Cod == 1 and $Hdd_Ret == 'S'))
		
		/*
		*   GUARDAMOS AL CLIENTE COMO USUARIO DEL SISTEMA SOLO PARA FACTURAS ELECTRONICAS
		*/
		if ($rs_infoEmpresa['Cof_Gce']=="S") /* Verifico si tiene autorizacion para generar F.E.*/
		{   
		    /* Consultamos si existe usuario */
			$row_rs_usuario = $obBD_con1->getRowConsulta(1065, $Ses_Suc_Cod.'*'.$PrsCedPrv,$obBD_conexion);					
			$total_usuario=$row_rs_usuario['Suc_Cod'] > 0? 1 : 0;
			if($total_usuario==0)
			{
				//echo "11";
				/* creamos el usuario en la base local Prs_Cod,Suc_Cod,Usu_Ced,Usu_Pal,Usu_Tip,Usu_Est,Usu_Cad */
				$obBD_ins1->operacionobBD(1062,$PrsCodPrv.'*'.$Ses_Suc_Cod.'*'.$PrsCedPrv.'*'.$PrsCedPrv.'*N',$obBD_conexion);
				$UsuCodPrv = $obBD_ins1->insercionid($obBD_conexion->conexion);
				
				/* Consultamos si existe el perfil "Clientes" */
				$row_rs_perfil = $obBD_con1->getRowConsulta(1074, $Ses_Emp_Cod,$obBD_conexion);					
				$total_rs_perfil=$row_rs_perfil['Per_Cod'] > 0? 1 : 0;
				if($total_rs_perfil!=0)
				{				
					/* asignamos el perfil "Clientes" para el cliente */
					$obBD_ins1->operacionobBD(1075,$UsuCodPrv.'*'.$row_rs_perfil['Per_Cod'],$obBD_conexion);				
				}
			}
		}
		
		/**
		* grabar auditoria
		*/
		//$obBD_ins1->grabarAuditoria($_SERVER['PHP_SELF'], $Ses_Usu_Cod, $obBD_conexion);
		
		/**
		* fin de la transacci?n 
		*/
		$obBD_ins1->fin_transaccion($obBD_conexion->conexion);
		//echo ' ver q pasa <br/><br/><br/><br/>'.$obBD_ins1->MsgError;
		/*
		*   GUARDAMOS AL CLIENTE COMO USUARIO DEL SISTEMA SOLO PARA FACTURAS ELECTRONICAS
		*/
		if ($rs_infoEmpresa['Cof_Gce']=="S") /* Verifico si tiene autorizacion para generar F.E.*/
		{					
			/*
			*  Conexion a la base Master
			*/
			$obBD_conexion_master = new Class_Log_Conexion_Comt;
			$obBD_ins1_master = new Class_Log_Datos_Comt;
			$obBD_con1_master = new Class_Log_Datos_Comt;
			
			/* Busco codigo de la empresa en la tabla data*/
			$row_rs_DatEmp = $obBD_con1_master->getRowConsulta(1064, $Ses_Emp_Cod,$obBD_conexion_master);			
			/* Busco si existe ya el usuario en la master */
			$row_rs_existeUsu = $obBD_con1_master->getRowConsulta(1066, $Ses_Usu_Cod.'*'.$row_rs_DatEmp['Dat_Cod'].'*'.$PrsCedPrv,$obBD_conexion_master);			
			$total_existeUsu=$row_rs_usuario['Suc_Cod'] > 0? 1 : 0;
			if($total_existeUsu==0)
			{	
				/* Inicio de la transaccion	*/
				$obBD_ins1_master->inicio_transaccion($obBD_conexion_master->conexion);																
				/* creamos el usuario en la base master */
				$obBD_ins1_master->operacionobBD(1063,$Ses_Suc_Cod.'*'.$row_rs_DatEmp['Dat_Cod'].'*'.$PrsCedCli,$obBD_conexion_master);
				$obBD_ins1_master->fin_transaccion_nomsn($obBD_conexion_master->conexion);
			}
		}
		
		/**
		*  Si la transaccion fue correcta generamos el xml para Retencion Electronica
		*/		
		if ($obBD_conexion->Error==0)
		{
			if ($rs_infoEmpresa['Cof_Gce']=="S") /* Verifico si tiene autorizacion para generar F.E.*/
			{				
				if ($PrsCorPrv!='')
				{
					/* Envio Notificacion por Correo Electronico al cliente */												
					$row_tipo_compr = $obBD_con1->getRowConsulta(1076, '6',$obBD_conexion);
					$fechaEmi = explode("-",$Cop_Fec);
					$copias = '';
					$msgHtml = '
					<html xmlns="http://www.w3.org/1999/xhtml">		
					 <style type="text/css">		 				
						.texto_encabezado{
							font-family:Arial, Helvetica, sans-serif;
							font-size:20px;
							color:#3C753F;
							font-style: normal;			
							font-weight: bold;
						}
						.texto_negrita{
							font-family: Verdana, Geneva, sans-serif;
							font-size:30px;
							color:#3C753F;
							font-weight: bold;
							font-style: normal;
							font-variant:normal;
						}
						.texto_pie{
							font-family:Verdana, Geneva, sans-serif;
							font-size: 10px;								
							font-style: normal;
							font-variant:normal;
							color:#666;
						}
						.texto{
							font-family: tahoma, new york, times, serif;
							font-size: 12px;
							color:#333;
						}
						.texto_titulo{
							font-family: Verdana, Geneva, sans-serif;
							font-size: 14px;
							color:#666;
						}
						.dos a {
							font-family: Tahoma, Geneva, sans-serif;
							font-size: 14px;
							
							background-color: #060;
							text-decoration: none;
							color: #FFF;
							border: 1px solid #0F0;	
						}
						.dos a:hover {
							font-family: Tahoma, Geneva, sans-serif;
							font-size: 14px;
							
							background-color: #090;
							text-decoration: none;
							color: #FFF;
							border: 1px solid #0F0;
						}  			
					 </style>		 
					 <meta http-equiv="accion" content="5;url=http://exa.ofsercont.com/index.php">
					</head>
					
					<body>
					<form name="form1" method="post" target="_new" action="http://exa.ofsercont.com/index.php">	
					<table width="597" border="0" cellpadding="0" cellspacing="0" align="center">
					  <tr>
						<td width="597" height="119" valign="top" background="http://exa.ofsercont.com/mascaras/model1/imagenes/128x128/banner_mail.png"><table width="100%" height="64" border="0" cellpadding="0" cellspacing="0">
						  <tr>
							<td width="13%" height="45">&nbsp;</td>
							<td colspan="2" align="left" ><span class="texto_negrita">F</span><span class="texto_encabezado">acturaci&oacute;n</span> <span class="texto_negrita">E</span><span class="texto_encabezado">lectr&oacute;nica</span></td>
							<td width="4%">&nbsp;</td>
						  </tr>
						  <tr>
							<td height="19">&nbsp;</td>
							<td width="37%">&nbsp;</td>
							<td width="46%">&nbsp;</td>
							<td>&nbsp;</td>
						  </tr>
						</table></td>
					  </tr>
					  <tr>
						<td valign="top" bgcolor="#D8E7C3"><table width="100%" border="0" cellspacing="0" cellpadding="0">
						  <tr>
							<td height="9">&nbsp;</td>
							<td colspan="2">&nbsp;</td>
							<td>&nbsp;</td>
						  </tr>
						  <tr>
							<td width="2%" height="9">&nbsp;</td>
							<td colspan="2" class="texto_titulo"><strong>'.$Ses_Emp_Nom.'</strong></td>
							<td width="2%">&nbsp;</td>
						  </tr>
						  <tr>
							<td height="47">&nbsp;</td> 
							<td colspan="2" class="texto">Ha generado el siguente comprobante electr&oacute;nico a, '.$PrsNomPrv.' con c&eacute;dula '.$PrsCedPrv.'.<br><br>
							<strong>Tipo:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </strong>'.$row_tipo_compr["Tic_Des"].' <br>
							<strong>Fecha de Emisi&oacute;n:&nbsp;</strong>'.$fechaEmi[2].' de '.mes($fechaEmi[1],1).' '.$fechaEmi[0].'<br>
							<strong>Secuencia:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</strong>'.$rs_infoEmpresa["Suc_Sri"].'-'.$rs_infoCliente["Pun_Sri"].'-'.$ceroDoc.$Ret_Int.'<br>
							<strong>Clave de Acceso:&nbsp;&nbsp;&nbsp;</strong>'.$claveAcceso.'<br>
							</td>
							<td width="2%">&nbsp;</td>
						  </tr>
						  <tr>
							<td height="1">&nbsp;</td>
							<td width="18%" class="texto">&nbsp;</td>
							<td width="78%" class="texto">&nbsp;</td>
							<td width="2%">&nbsp;</td>
						  </tr>
						</table></td>
					  </tr>
					  <tr>
						<td height="19" valign="top" bgcolor="#D8E7C3">&nbsp;</td>
					  </tr>
					  <tr>
						<td valign="top" bgcolor="#D8E7C3"><table width="100%" border="0" cellspacing="0" cellpadding="0">
						  <tr>
							<td width="2%" height="63" class="texto">&nbsp;</td>
							<td width="96%" class="texto">Para descargar su Comprobante Electr&oacute;nico debe seguir los siguentes pasos:<br>
							  <strong>Paso 1:</strong> Click en el bot&oacute;n <strong>Siguiente</strong><br>
							  <strong>Paso 2:</strong> Ingresar el usuario(Cedula/R.u.c.) y contrase&ntilde;a(Cedula/R.u.c.)<br>
							  <strong>Paso 3:</strong> Click en el bot&oacute;n <strong>Entrar</strong></td>
							<td width="2%">&nbsp;</td>
						  </tr>
						</table></td>
					</tr>
					<tr>
						<td valign="top" bgcolor="#D8E7C3"><p>&nbsp;</p></td>
					  </tr>
					<tr>
					   <td align="center" bgcolor="#D8E7C3"><div class="dos"><a target="_new" href="http://exa.ofsercont.com/">&nbsp;Siguiente&nbsp;</a></div></td>
					  </tr>
					  <tr>
						<td height="19" valign="top" bgcolor="#D8E7C3">&nbsp;</td>
					  </tr>
					  <tr>
						<td valign="top" bgcolor="#D8E7C3">&nbsp;</td>
					  </tr>
					  <tr>
						<td height="47" valign="top" bgcolor="#D8E7C3"><p>&nbsp;</p></td>
					  </tr>
					  <tr>
						<td align="center" valign="top" bgcolor="#D8E7C3" class="texto_pie"><strong>Ofsercont S.A.</strong></td>
					  </tr>
					  <tr>
						<td align="center" valign="top" bgcolor="#D8E7C3" class="texto_pie">administracion@ofsercont.com</td>
					  </tr>
					  <tr>
						<td align="center" valign="top" bgcolor="#D8E7C3" class="texto_pie"><strong>Telf:</strong> 2980779 &nbsp;&nbsp; <strong>Cel:</strong>0993814444</td>
					  </tr>
					  <tr>
						<td align="center" valign="top" bgcolor="#D8E7C3"><span class="texto_pie"><strong>Direcci&oacute;n:</strong> Cdla. La Aurora calle ceibos e./3era y 4ta este</span></td>
					  </tr>
					  <tr>
						<td align="center" valign="top" bgcolor="#D8E7C3"><p class="texto_pie">Machala-El Oro</p></td>
					  </tr>
					   <tr>
						<td height="56" valign="top" bgcolor="#D8E7C3"><table width="100%" border="0" cellspacing="0" cellpadding="0">
						  <tr>
							<td height="55" colspan="3" background="http://exa.ofsercont.com/mascaras/model1/imagenes/128x128/banner_pie.png">&nbsp;</td>
							<td width="23%">&nbsp;</td>
						  </tr>
						 </table></td>
					  </tr>
					</table>
					</form>
					</body>
					</html>';							
					require '../../Librerias/PHPMail/class.phpmailer.php';
					// Crear una nueva  instancia de PHPMailer habilitando el tratamiento de excepciones
					$mail = new PHPMailer(true); 
					// Configuramos el protocolo SMTP con autenticación
					$mail->IsSMTP();
					$mail->SMTPAuth = true;
					$mail->IsHTML(true);
					// Configuración del servidor SMTP
					$mail->Port = 25;
					$mail->Host = 'ofsercont.com';
					$mail->Username = "facturacion.electronica@ofsercont.com";
					$mail->Password = "p.123456";
					// Configuración cabeceras del mensaje
					$mail->From = "facturacion.electronica@ofsercont.com";
					$mail->FromName = $Ses_Emp_Nom;
					$mail->AddAddress(trim($PrsCorPrv),strtoupper($PrsNomPrv));
					//$mail->AddAddress("destino2@correo.com","Nombre 2");
					//$mail->AddCC("copia1@correo.com","Nombre copia 1");
					//$mail->AddBCC("copia1@correo.com","Nombre copia 1");
					$mail->Subject = "Comprobante Electrónico";
					// Creamos en una variable el cuerpo, contenido HMTL, del correo
					
					//$body  = "Proebando los correos con un tutorial<br>";
					//$body .= "hecho por <strong>Developando</strong>.<br>";
					//$body .= "<font color='red'>Visitanos pronto</font>";
					$mail->Body = $msgHtml;
					// Ficheros adjuntos
					//$mail->AddAttachment("misImagenes/foto1.jpg", "developandoFoto.jpg");
					//$mail->AddAttachment("files/proyecto.zip", "demo-proyecto.zip");
					// Enviar el correo
					$mail->Send();	
				}
				/*Se genera la retencion solo cuando se escoge tipo docuemnto FACTURA */
				if($Tic_Cod=='1')
				{
					include("../COMPONENTES/tesXmlRetencionElectronica_1.0.php");
				}
			}
		}
		
		/**
		* Consulta del reporte para impresion 
		*/
		$pagina = $_SERVER['PHP_SELF'];
		$reportes = $obBD_con1->reportes($pagina, $Ses_Emp_Cod, $obBD_conexion);		
		
		/**
	    *	If que restringe el guardado de ciertos DATOS cuando la empresa no lleva contabilidad
	    */
		if($llevarContabilidad['Cof_Con']=='S'){	
			$hdd_comprobante = $reportes[1];		
		}
		$hdd_retencion = $reportes[2];
		if($Tic_Sri='3')
		{
			$hdd_liquidacion = $reportes[3];
		}
	}//Fin del if ($thisPost->postBlock($_POST['postID']))
}//Fin del if(isset($hdd_save) && !isset($hdd_volver))

/**
* Busqueda de los datos del cliente 
*/
if ($txt_busqueda != "")
{	
	if ($op_opciones == "d")
		{	
			/**
			* Consulta por apellido 
			*/
			$rs_buscar = $obBD_con1->getArrayConsulta(701, trim($txt_busqueda).'*'.$Ses_Emp_Cod, $obBD_conexion);
		}
		else
		{
			/**
			* consulta por cedula 
			*/
			$rs_buscar = $obBD_con1->getArrayConsulta(702, trim($txt_busqueda).'*'.$Ses_Emp_Cod, $obBD_conexion);
		}  
}
else
{		
	if (isset($codigo))
	{		
		/**
		* Consulta de los porcentajes del I.C.E. (Impuesto a los consumos especiales) 
		*/
		$rs_ice=$obBD_con1->getArrayConsulta(707, '', $obBD_conexion);
		/** 
		* Recorrido y asignaci?n de los porcentajes del I.C.E. a un arreglo 
		*/
		foreach($rs_ice as $row_rs_ice)
		{
			$ice_cod[]=$row_rs_ice['Ice_Int'];
			$ice_por[]=$row_rs_ice['Ice_Por'];
		}
		$ice_cod = 'Array(\'' . @implode('\', \'', $ice_cod) . '\')';
		$ice_por = 'Array(\'' . @implode('\', \'', $ice_por) . '\')';		
		/**
		* Consulta datos de los proveedores
		*/
		$row_rs_proveedore = $obBD_con1->getRowConsulta(708, $codigo, $obBD_conexion);	
		/**
		* Consulta de las ciudades
		*/
		$rs_ciudad = $obBD_con1->getArrayConsulta(709, '', $obBD_conexion);	
		/** 
		* Consulta el sustento 
		*/
		$rs_sustento = $obBD_con1->getArrayConsulta(711, '', $obBD_conexion);	
		/**
		* Consulta el tipo de comprobante 
		*/
		$rs_tip_compr = $obBD_con1->getArrayConsulta(729, '', $obBD_conexion);	
	}//fin if (isset($codigo))
} //fin del if ($txt_busqueda != "")

	/**
	* Carga el periodo contable seleccionado 
	*/
	$row_rs_periodo = $obBD_con1->getRowConsulta(189, $Pec_Cod, $obBD_conexion);
	$anio=substr($row_rs_periodo['Pec_Fei'], 0,4);
	$Pec_Fei=$row_rs_periodo['Pec_Fei'];
	$Pec_Fef=$row_rs_periodo['Pec_Fef'];
	$Pla_Cod = $row_rs_periodo['Pla_Cod'];	
	/**
	* Descripcion del periodo contable 
	*/
	$periodo = "en el periodo contable ".substr($row_rs_periodo['Pec_Fei'], 0,4);					
}//Fin del if (!isset($hdd_Pec_Cod))
 
/**
* Consulta del vendedor en base al codigo de la persona
*/
$row_rs_vendedor = $obBD_con1->getRowConsulta(24, $Ses_Prs_Cod.'*'.$Ses_Suc_Cod, $obBD_conexion);
$total_rs_vendedor=count($row_rs_vendedor);

/**
* Consultamos la configuracion de la empresa Facturacion electronica
*/
$rs_infEmpFacElec = $obBD_con1->getRowConsulta(1049, $Ses_Suc_Cod, $obBD_conexion);

/**
* Consulta las autorizaciones de las retenciones
*/	
$row_rs_autorizacion=$obBD_con1->getRowConsulta(378, $row_rs_vendedor['Pun_Cod'].'*'.tipo_compr, $obBD_conexion);
if($rs_infEmpFacElec['Cof_Gce']=='S')
{
	$infoRetencion="Comprobante Electr&oacute;nico";
}else{
	$infoRetencion=$row_rs_autorizacion['Aut_Sri'];
}

/**
* Consulto si ya existe un codigo generado en las retenciones basado en una autorizacion otorgada por el SRI 
*/
$num_existe_gencod=$obBD_con1->getArrayConsulta(518, $row_rs_autorizacion['Aut_Cod'], $obBD_conexion);
/**
* Consulto el n?mero inicial del comprobante de retenci?n desde la autorizaci?n 
*/
if(count($num_existe_gencod)>0)
{ 
	/**
	* Consulta el maximo numero de retenciones en base a la autorizacion
	*/
	$row_max_codig=$obBD_con1->getRowConsulta(511, $row_rs_autorizacion['Aut_Cod'], $obBD_conexion);
	unset($Ret_Id_Man);
	$Ret_Id_Man = ($row_max_codig['Ret_Ide'])+1;	
 }
 else
 {  
 	unset($Ret_Id_Man);
  	$Ret_Id_Man=$row_rs_autorizacion['Aut_Ini'];
 }/* fin if($num_existe_gencod>0){  */
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<HTML>
	<HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
		<?php require_once("../../mascaras/model1/estilos/estilos.php");?>
                <?php if($llevarContabilidad['Cof_Con']=='S'){?>
		<script language="javascript" src="../VALIDACIONES/fac_val_compras_new.js?x=5"></script>
                <?php }else{?>
                <script language="javascript" src="../VALIDACIONES/fac_val_compras.js?x=4"></script>
                <?php } ?>
        <script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>
        <!--Librerias para interfaz -->       
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>         
        <!--Librerias para modal -->       
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.modals.js"></script> 
	    <!--Librerias para calendario -->       
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.datepicker.js"></script>          
         <script type="text/javascript" src="../../Librerias/masked/jquery.maskedinput-1.2.2.js"></script>        
        <script language="javascript" src="../VALIDACIONES/XML.js"></script>
       <script>
		$(function() { 
			//var imagen = "../../mascaras/model1/imagenes/32x32/calendar.gif";
			/* Campo 1 */
			$( "#Cop_Fec" ).datepicker({
				changeMonth: true, changeYear: true,
				/* Permite asignar una imagen */
				/*showOn: "button", buttonImage: imagen, buttonImageOnly: true,*/ dateFormat: "yy-mm-dd"});			
                        $( "#Ret_Fec" ).datepicker({
				changeMonth: true, changeYear: true,
				/* Permite asignar una imagen */
				/*showOn: "button", buttonImage: imagen, buttonImageOnly: true,*/ dateFormat: "yy-mm-dd"});			
			
			/* Campo 2 */
			$( "#Cop_Cad" ).datepicker({
				changeMonth: true, changeYear: true,
				/* Permite asignar una imagen */
				/*showOn: "button", buttonImage: imagen, buttonImageOnly: true,*/ dateFormat: "yy-mm-dd" });			

			/* Campo 3 */
			$( "#Com_Fec" ).datepicker({
				changeMonth: true, changeYear: true,
				/* Permite asignar una imagen */
				/*showOn: "button", buttonImage: imagen, buttonImageOnly: true,*/ dateFormat: "yy-mm-dd" });			

			/* Campo 4 */
			$( "#Cop_Imp" ).datepicker({
				changeMonth: true, changeYear: true,
				/* Permite asignar una imagen*/
				/*showOn: "button", buttonImage: imagen, buttonImageOnly: true,*/ dateFormat: "yy-mm-dd" });			

			/* Campo 5 */
			$( "#Cpp_Ven" ).datepicker({
				changeMonth: true, changeYear: true, 
				/* Permite asignar una imagen */
				/*showOn: "button", buttonImage: imagen, buttonImageOnly: true,*/ dateFormat: "yy-mm-dd" });			
		}); 		
        </script>   
        
         <script type="text/javascript"> 
        $(function() {
			$('#set1 *').tooltip({showURL: false});
		}); 

		/**
		* Control de mascaras
		*/
		jQuery(function($){
			$("#Cop_Num").mask("999-999-999999999",{placeholder:"_"});
			$("#Cop_Nns").mask("999-999-999999999",{placeholder:"_"});
		});	
					             			
		</script>         
		<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
	</HEAD>
<BODY <?Php if($total_rs_vendedor>0){ if (isset($codigo) && $codigo >0){ ?> onLoad="if (document.getElementById('total_anticipos').value > 0){ setInterval('parpadeo(\'txt_blink\')',500) }" <?php }} ?>>
<div id="set1">
<?php	//setInterval('parpadeo(\'txt_blink\')',500)
/**
* Control para la impresi?n de reportes
*/ 

if (isset($hdd_save)  && !isset($hdd_volver))
{	
	$tabla="proveedore"; 	
	?>    
	<script language="javascript">
	//Reporte Detalle de Compras
	windows('../FRONT/fac_pri_fac_detallecompras_1.0.php?com_codigo=<? echo $Cop_Cod; ?>','',800,800,'no','yes','yes','yes');
	<? if(isset($hdd_comprobante)){?>			
		windows('<?php echo $hdd_comprobante;?>?Com_Num=<? echo $Com_Num; ?>&codigo=<? echo $Com_Cod; ?>&tabla=<? echo $tabla; ?>&tipo=<? echo $op; ?>&campo=<? echo $campo; ?>&Pec_Cod=<?php echo $Pec_Cod;?>','',800,800,'no','yes','yes','yes'); 	
	<?Php 
	}
	/**
	* Solo muestra el reporte cuando se genera la retencion 
	*/
        
	if (isset($Ret_Cod))	
	{?>windows('<?php echo $hdd_retencion; ?>?Ret_Cod=<?Php echo $Ret_Cod; ?>', '', 800,800,'no', 'yes', 'yes', 'yes');<?Php }//Fin del if (isset($Ret_Cod))
    if (isset($hdd_liquidacion))	
	{?>windows('<?php echo $hdd_liquidacion; ?>?Cop_Cod=<?Php echo $Cop_Cod; ?>', '', 800,800,'no', 'yes', 'yes', 'yes');<?Php }//Fin del if (isset($Ret_Cod))    
		
	?>
	</script>
<?  unset($codigo); 
}//Fin del if (isset($hdd_save)  && !isset($hdd_volver))
?>

<table width="100%" border="0" cellpadding="0" cellspacing="0" class="table">
  <tr class="BarraTitulo">
	  <td>&raquo; Registrar Documentos de Compras <?Php echo $periodo; ?></td>
	  <td>&nbsp;&raquo; PUNTO DE IMPRESI&Oacute;N: <?Php echo $row_rs_vendedor['Pun_Des']; ?></td>
  </tr>
  <tr height="400">
  <td colspan="2" valign="top">
<?Php 
if(count($row_rs_vendedor)>0)  
 {  
	/**
	* tipo_compr | 6 | c?digo interno de la retenci?n  
	*/
  	$row_autorizacion_sri=$obBD_con1->getRowConsulta(517, $Ses_Prs_Cod.'*'.tipo_compr.'*'.$Ses_Suc_Cod, $obBD_conexion);
 /**
 * Verificar si hay una autorizacion activa 
 */
 if(count($row_autorizacion_sri)>0) 
 {
   /**
   * Verifica que el n?mero de la Retencion ente en el Rango de la autorizacion otorgado por el SRI
   */
   if($Ret_Id_Man >= $row_autorizacion_sri['Aut_Ini'] && $Ret_Id_Man <= $row_autorizacion_sri['Aut_Fin'])
   {
/**
* Condicion para evaluar cuando mostrar los periodos contables 
*/
if (!isset($hdd_Pec_Cod) && !isset($hdd_volver))
{
?>
	<form name="form1" method="post" action="<?Php echo $_SERVER['PHP_SELF']?>">		
		<?Php  include("../../componentes/FRONT/comConPeriodoCont.php");?>                            
	</form>
<?Php
}//Fin del if (!isset($hdd_Pec_Cod))
else
{	
?>
	<form name="form1" method="post" action="<?Php echo $_SERVER['PHP_SELF']?>">		
	<input name="hdd_Pec_Cod" id="hdd_Pec_Cod" type="hidden" value="">
	<input name="Pec_Cod" id="Pec_Cod" type="hidden" value="<?Php  echo $Pec_Cod; ?>">
		<?php include("../../componentes/FRONT/com_con_persona.php"); ?>
	<input name="Pec_Ann" id="Pec_Ann" type="hidden" value="<?Php  echo substr($row_rs_periodo['Pec_Fei'], 0,4); ?>">
        
                <table width="100%" border="0" cellspacing="0" cellpadding="0">
                <tr>
                    <td align="center"><? if(($Ses_Dat_Dis=='exa' || $Ses_Dat_Dis=='aaa' || $Ses_Dat_Dis=='orquideas')&& !isset($hdd_volver)  && isset($hdd_save)){echo "<br><span class='Texto_grande' style='color:blue;'>".$infoTicDes."</span></br><br><span class='Texto_grande' style='color:blue;'>".mes($mese[1],1)."</span><br><span class='Texto_grande' style='color:teal;'><b style='color:darkgoldenrod;'>Sec:</b> ".$Cop_Sec.'</span><br><span class="Texto_grande"><b style="color: darkgoldenrod;">Cod:</b>'.$Cop_Cod."</span>";}?></td>
                </tr>
                </table>     
	</form>
<?Php
if(isset($txt_busqueda))
{ ?>
  <br>
<FIELDSET>
<LEGEND>
<label class="Titulos2">Resultados de la busqueda</label>
</LEGEND>
    <table width="100%" border="0" cellpadding="0" cellspacing="0" class="fixedHeader03" >
    <thead>
	  <tr>
	  	  <th width="10%">C&oacute;d. Int.</th>
          <th width="12%">C&eacute;dula/R.U.C.</th>
          <th width="61%">Proveedor</th>
          <th width="8%">Ob. Conta.</th>
          <th width="8%">C. Espec.</th>
          <th width="1%">&nbsp;</th>
      </tr>
    </thead>
    <tbody>  
	  <?Php 
	if(count($rs_buscar) != 0)
	{	  
	  foreach($rs_buscar as $row_rs_buscar)
	  { 
	  	if($row_rs_buscar['Prv_Est']=='Inactivo')
	    { $rojo='#FF0000'; $anulada++; }else{$rojo='';}
	  ?>
	  <form method="post" name="pasar" action="<?Php echo $_SERVER['PHP_SELF']; ?>">	
	  	<tr>
	    <td width="10%" align="center"><font color="<?php echo $rojo; ?>"><?Php echo $row_rs_buscar['Prv_Cod'];?></font></td>
		<td width="12%" align="center"><font color="<?php echo $rojo; ?>"><?Php $Cedula=$row_rs_buscar['Prs_Ced'];  echo $Cedula."&nbsp"; ?></font></td>
		<td align="center"><font color="<?php echo $rojo; ?>"><div align="left"><?Php echo marcar_cadena($txt_busqueda, $row_rs_buscar['Prs_Ape']." ".$row_rs_buscar['Prs_Nom'], '#FFFF00', 1);  ?></div></font></td>
          <td align="center"><?php if($row_rs_buscar['Prv_Con']==='S'){ ?><img src="../../mascaras/model1/imagenes/ok-s.gif" width="16" height="16" type="image" /><?php } ?></td>
                <td align="center"><?php if($row_rs_buscar['Prv_Esp']==='S'){ ?><img src="../../mascaras/model1/imagenes/ok-s.gif" width="16" height="16" type="image" /><?php } ?></td>
		<td width="1%" align="center">	
        <? if($row_rs_buscar['Prv_Est']=='Activo'){?>
        <button type="button" class="btn btn-success btn-mini" title="Elegir" onClick="this.form.submit()">
        	<i class=" icon-arrow-right icon-white"></i>
        </button>	
        <input name="codigo" id="codigo" type="hidden" value="<?Php echo $row_rs_buscar['Prv_Cod'];?>">
		<input name="volver_busqueda" id="volver_busqueda" type="hidden" value="<?Php echo $txt_busqueda;?>">
		<input name="volver_op" id="volver_op" type="hidden" value="<?Php echo $op_opciones;?>">						   		
		<input name="hdd_Pec_Cod" id="hdd_Pec_Cod" type="hidden" value="">
		<input name="Pec_Cod" id="Pec_Cod" type="hidden" value="<?Php  echo $Pec_Cod; ?>">
		<input name="Pec_Ann" id="Pec_Ann" type="hidden" value="<?Php echo $Pec_Ann;?>">
	    <input name="Pec_Fei" id="Pec_Fei" type="hidden" value="<?Php echo $Pec_Fei;?>">
	    <input name="Pec_Fef" id="Pec_Fef" type="hidden" value="<?Php echo $Pec_Fef;?>">
        <input name="Ses_Rcb_Cod" id="Ses_Rcb_Cod" type="hidden" value="<?Php echo $Ses_Rcb_Cod;?>">      
        <? }?>
		</td>		
	  </tr>
	  </form>
	  <?Php } //FIn del foreach
  	}//FIn del if($total_rs_buscar != 0)
	else
	{ ?>
		<tr>
          <td>&nbsp;</td>
		  <td >&nbsp;</td>
		  <td><?Php echo error_alerta("No hay resultados que mostrar para ".strtoupper($txt_busqueda)." ".$periodo, 2); ?></td>
		  <td>&nbsp;</td>
		</tr>	   
	<?Php
	}//Fin del else if($total_rs_buscar != 0) ?>
    </tbody>
 </table>
  <?Php echo barra_estado(count($rs_buscar)); ?>
</FIELDSET>
<?Php
}//Fin del if(isset($txt_busqueda))
?>
 <form action="<?Php echo $_SERVER['PHP_SELF']; ?>" method="post" name="form2" id="form2">
	<input name="hdd_Pec_Cod" id="hdd_Pec_Cod" type="hidden" value="">
	<input name="Pec_Cod" id="Pec_Cod" type="hidden" value="<?Php  echo $Pec_Cod; ?>">
	<input name="Pec_Fei" id="Pec_Fei" type="hidden" value="<?php echo $row_rs_periodo['Pec_Fei']; ?>">
	<input name="Pec_Fef" id="Pec_Fef" type="hidden" value="<?php echo $row_rs_periodo['Pec_Fef']; ?>">	
	<input name="Pec_Ann" id="Pec_Ann" type="hidden" value="<?php echo $row_rs_periodo['Ann']; ?>">	
  <?Php       
		/**
		* Creacion del campo REPOST
		*/
		$thisPost->startPost(); 
 		?> 	
<?Php

 if ($codigo > 0 && !(isset($hdd_save)))
 { 
	/**
	* Consulta para determinar la cuenta del IVA PAGADO del plan de cuentas actual  
	*/
	$row_rs_ivap= $obBD_con1->getRowConsulta(252, $Pla_Cod, $obBD_conexion); 	
	if (count($row_rs_ivap) || $llevarContabilidad['Cof_Con']=='N')
	{
	?>
		<input name="iva_hdd" id="iva_hdd" type="hidden" value="<?php echo $row_rs_ivap['Pld_Cod']; ?>">
 		
 <FIELDSET>
<LEGEND>
<label class="Titulos2">Datos del Proveedor </label>
</LEGEND>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td width="15%" class="Etiqueta1">C?dula/R.U.C.: </td>
    <td colspan="3" class="LetraNegra">&nbsp;<?Php echo $row_rs_proveedore['Prs_Ced'] ?>
    <input type="hidden" id="PrsCodPrv" name="PrsCodPrv" value="<?Php echo $row_rs_proveedore['Prs_Cod'] ?>" />
    <input type="hidden" id="PrsCedPrv" name="PrsCedPrv" value="<?Php echo $row_rs_proveedore['Prs_Ced'] ?>" />
    <input type="hidden" id="PrsCorPrv" name="PrsCorPrv" value="<?Php echo $row_rs_proveedore['Prs_Cor'] ?>" />
    <div style="float:right; margin-right: 60px">
        Obligado a LLevar Contabilidad: 
        <div style="height: 10px;width: 20px;display: inline-block">
        <?php if($row_rs_proveedore['Prv_Con']==='S'){ ?><img src="../../mascaras/model1/imagenes/ok-s.gif" width="16" height="16" type="image" style="margin-top: -5px;position: absolute"  /><?php }else{ ?>
        <img src="../../mascaras/model1/imagenes/32x32/deshabilitar.gif" width="20" height="16" type="image" style="margin-top: -2px;position: absolute" /><?php } ?>
        </div>
    </div>
    </td>
	</tr>
  <tr>
    <td class="Etiqueta1">Proveedor: </td>
    <td class="LetraNegra">&nbsp;<?Php echo $row_rs_proveedore['Prs_Nom']." ".$row_rs_proveedore['Prs_Ape']; ?>
    <input type="hidden" id="PrsNomPrv" name="PrsNomPrv" value="<?Php echo $row_rs_proveedore['Prs_Nom']." ".$row_rs_proveedore['Prs_Ape']; ?>" />    
    <div style="float:right; margin-right: 60px">
        Contribuyente Especial: 
        <div style="height: 10px;width: 20px;display: inline-block">
        <?php if($row_rs_proveedore['Prv_Esp']==='S'){ ?><img src="../../mascaras/model1/imagenes/ok-s.gif" width="16" height="16" type="image" style="margin-top: -5px;position: absolute"  /><?php }else{ ?>
        <img src="../../mascaras/model1/imagenes/32x32/deshabilitar.gif" width="20" height="16" type="image" style="margin-top: -2px;position: absolute" /><?php } ?>
        </div>
    </div>
    </td>
	</tr>
	<tr>
    <td class="Etiqueta1">Direcci?n :</td>
    <td class="LetraNegra">&nbsp;<?php echo $row_rs_proveedore['Prs_Dir']?>
      <input type="hidden" id="Prv_Cod" name="Prv_Cod" value="<?Php echo  $row_rs_proveedore['Prv_Cod'] ?>"></td>
	</tr>
</table>
</FIELDSET>
<FIELDSET>
<LEGEND>
<label class="Titulos2">Datos de la Compra </label>
</LEGEND>
 <input type="hidden" name="Pla_Cod" id="Pla_Cod" value="<?Php echo $Pla_Cod; ?>">
     <?php 
     if($hoy>'2016-05-31')
            $row_ivas  = $obBD_con1->getArrayConsulta(1105, '', $obBD_conexion);
     else
        $row_ivas = $obBD_con1->getArrayConsulta(1100, $hoy.'*1', $obBD_conexion);
     ?>
     
     <script>
         
       
        var ivas_rows=<?php echo json_encode($row_ivas); ?>;
        var iva_select=<?php if(isset($row_ivas[0]))echo json_encode($row_ivas[0]);else echo 'null' ?>;
          
            
         var codigos=<?php if (count($rs_tip_compr) > 0) echo json_encode($rs_tip_compr); else echo 'new Array()';?>,liquida={limite:false,maximo:13000,actual:0}; 
         function checkFechaIva(data){
             var TicCod=$('#Tic_Cod').val();
             $.post( "<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>",{Cop_Fec:data,Check_Iva:true,Tic_Cod:TicCod,Tic_Sri:getSriCod(TicCod)}, function( response ) {
                            if(response['success']===true){                                 
                                if(response['total']>0){
                                    ivas_rows=response['ivas'];
                                    iva_select=response['ivas'][0];
                                    if(response['varIvas'])
                                        $('#selectIvas').html(response['options']).show();
                                    else
                                        $('#selectIvas').html('').hide();
                                    updateIva(7,6);
                                }else{ alert('Error al buscar el IVA para la fecha indicada');}
                            }                                   
                        },'json').fail(function (){
                            alert('Error al buscar el IVA para la fecha indicada');
                        });
         }
         function changeIvas(data){
             for(var i=0;i<ivas_rows.length;i++)
                if(ivas_rows[i]["Iva_Cod"]===data){
                    iva_select=ivas_rows[i];
                    updateIva(7,6);
                }
         }
         function checkLiquidacion(data){
             $.post( "<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>",data, function( response ) {
                            if(response['success']===true&&response['total']!==null){                                 
                                if((response['total']['total'])*1>=11000){
                                    liquida['actual']=(response['total']['total'])*1;liquida['limite']=true;
                                    $('#imgTipoCo').attr('src','../../mascaras/model1/imagenes/32x32/cancel.gif');
                                    $('#lblTipoCo').html('El total de las Liquidaciones es '+response['total']['total']+'. Limite 13000.');
                                }
                            }                                   
                        },'json');
                    }
	function checkImportacion(TicCod){
            $('#Cop_Num').unmask(); 
            $('#Cop_Nns').unmask(); 
            if(getSriCod(TicCod)==='17'){
                $("#Cop_Num").mask("999-9999-99-99999999",{placeholder:"_"});
				$("#Cop_Nns").mask("999-9999-99-99999999",{placeholder:"_"});
            }else{
                $("#Cop_Num").mask("999-999-999999999",{placeholder:"_"});
				$("#Cop_Nns").mask("999-999-999999999",{placeholder:"_"});
            }
         }
         function getSriCod(TicCod){
             for(var i=0;i<codigos.length;i++)
                if(codigos[i]["Tic_Cod"]===TicCod)
                    return  codigos[i]["Tic_Sri"];
         }
         <?php if(isset($row_ivas[0])){ ?>
                $( document ).ready(function() {
                    $('.iva_por').html('<?php echo $row_ivas[0]['Iva_Por'] ?>');
                });
         <?php } ?> 
     </script>
<FIELDSET>
<LEGEND>
<label class="Titulos2"> Generales </label>
</LEGEND>
  <table width="100%"  border="0" cellpadding="0" cellspacing="0">
  <tr>
  <td width="155" class="Etiqueta1"><span class="Asterisco">* </span>Tipo  documento: </td>
  <td width="877" colspan="3" class="LetraNegra">
    <select name="Tic_Cod" id="Tic_Cod" 
            onChange="if(getSriCod(this.value)==='3'){checkLiquidacion({liquida:true,PrvCod:document.getElementById('Prv_Cod').value,SriCod:getSriCod(this.value),PecFei:'<?php echo $Pec_Fei; ?>',PecFef:'<?php echo $Pec_Fef; ?>'});}else{$('#imgTipoCo').removeAttr('src');$('#lblTipoCo').html('');}if(this.value==4 ||  this.value==5){document.getElementById('NotasCredito').className = 'muestra';}else{document.getElementById('NotasCredito').className = 'oculta'} ;ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_con_numcom=1&Prv_Cod=' + document.getElementById('Prv_Cod').value +'&Cop_Num=' + document.getElementById('Cop_Num').value +'&Tic_Cod=' + this.value,'div_con_num_com');checkImportacion(this.value); /*if(getSriCod($('#Tic_Cod').val())==='4') checkFechaIva($('#Cop_Fec').val());*/ ">
      <option value="">Seleccione...</option>
      <?Php foreach($rs_tip_compr as $row_rs_tip_compr)
	  { ?>
      <option value="<?php echo $row_rs_tip_compr['Tic_Cod']?>"><?php echo $row_rs_tip_compr['Tic_Des'];?></option>
      <?php
	  } ?>
    </select> <img id="imgTipoCo" alt="" /> <span id="lblTipoCo" style="color:red;"></span>
  </td>
  </tr>
     <tr>
       <td class="Etiqueta1"><span class="Asterisco">* </span>Tipo de Sustento tributario: </td>
       <td colspan="3" class="LetraNegra">
         <select name="Tri_Cod" id="Tri_Cod">
           <option value="">Seleccione...</option>
          <?Php foreach($rs_sustento as $row_rs_sustento)
		  { ?>
           <option <?Php if ($row_rs_sustento['Tri_Cod'] == 2){ echo "selected"; } ?>  value="<?php echo $row_rs_sustento['Tri_Cod']?>" onClick="if (document.getElementById('Tri_Cod').value==1){document.getElementById('Cop_Imp').disabled=true; }"><?php echo $row_rs_sustento['Tri_Sri'].' - '.$row_rs_sustento['Tri_Des'];?></option>
           <?php
		  } ?>
         </select>
     </td>
       </tr>
	   </table>
	   <?php include("../COMPONENTES/comConNumcom.php"); ?>
 <table width="100%" border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td width="172" class="Etiqueta1"><span class="Asterisco">*</span> Autorizaci&oacute;n: </td>
    <td width="234"><span class="LetraNegra">
      <input name="Cop_Aut" type="text" id="Cop_Aut" size="30" maxlength="49"
   onBlur="if (document.getElementById('Cop_Aut').value!=''){ numerico(this); maximo_AutSri(this, '10','37','49');}">
      <label></label>
      </span></td>
    <td width="146"  class="Etiqueta1"><span class="Asterisco">*</span> Fecha de emisi&oacute;n: </td>
    <?Php
	$ann = explode('-', date("Y-m-d"));	
	?>
    <td width="480" valign="bottom" class="LetraNegra"><input name="Cop_Fec" type="text" id="Cop_Fec" value="<?php echo $row_rs_periodo['Ann'].'-'.$ann[1].'-'.$ann[2]; ?>" size="10" onKeyUp="mascara(this,'-',patron,true)" onBlur="validar_fecha2(this);"  onchange="checkFechaIva(this.value);" >      <img src="../../mascaras/model1/imagenes/32x32/info.gif"  title="Fecha de la emisi?n del documento por el Proveedor" /></td>
  </tr>
  <tr>
    <td class="Etiqueta1"><span class="Asterisco">*</span> Ciudad de emisi&oacute;n:</td>
    <td><span class="LetraNegra">
      <select name="Ciu_Cod" id="Ciu_Cod">
        <option value="">Seleccione...</option>
        <?Php foreach($rs_ciudad as $row_rs_ciudad)
		{ ?>
        <option value="<?php echo $row_rs_ciudad['Ciu_Cod']?>"><?php echo $row_rs_ciudad['Ciu_Des']?></option>
        <?php
		} 
		?>
      </select>
      <input type="hidden" name="Vnd_Cod" id="Vnd_Cod" value="<?Php echo $row_rs_vendedor['Vnd_Cod']; ?>" />
      <input type="hidden" name="Pun_Cod" id="Pun_Cod" value="<?Php echo $row_rs_vendedor['Pun_Cod'];  ?>" />
    </span></td>
    <td class="Etiqueta1"><span class="Asterisco">*</span> Fecha de impresi&oacute;n:</td>
    <td class="LetraNegra"><input name="Cop_Imp" type="text"  id="Cop_Imp" value="" size="10" onKeyUp="mascara(this,'-',patron,true)" onBlur="validar_fecha2(this);" /> 
	  <img src="../../mascaras/model1/imagenes/32x32/info.gif" title="Fecha de creaci?n del documento en la imprenta" /></td>
    </tr>
    <tr>
  <td  rowspan="2" class="Etiqueta1">Observaci&oacute;n: </td>
  <td rowspan="2"><textarea name="Cop_Obs" cols="35" rows="3" id="Cop_Obs" style="text-transform:uppercase" ></textarea></td>
  <td class="Etiqueta1"><span class="Asterisco">*</span> Fecha de caducidad:</td>
  <td>
		<table width="361" border="0" cellspacing="0" cellpadding="0">
		  <tr>
		    <td width="62"><div id="div_cadFecha" class="alertas3">
        <input name="Cop_Cad" type="text" id="Cop_Cad" value="" size="10" onKeyUp="mascara(this,'-',patron,true)" onBlur="validarCaducidad(this, $('#Cop_Fec').val()); validar_fecha2(this);"></div>        
         </td>
		    <td width="24">&nbsp;<img src="../../mascaras/model1/imagenes/32x32/info.gif" title="Fecha de caducidad del documento seg?n el SRI" /></td>
		    <td width="310">
            <div id="div_caducidad" style="color:#F00">La factura esta CADUCADA, verifique la fecha</div>
        <script language="javascript">
		document.getElementById('div_caducidad').className = 'oculta';
		</script></td>
		    </tr>
		  </table></td>
    </tr>
    <tr>
      <td class="Etiqueta1"><span class="Asterisco">*</span> Fecha Compr.:</td>
      <td><input name="Com_Fec" type="text" id="Com_Fec" value="<?Php echo $hoy; ?>" size="10" onKeyUp="mascara(this,'-',patron,true)" onBlur="validar_fecha2(this);"> 
        <img src="../../mascaras/model1/imagenes/32x32/info.gif" title="Fecha del comprobante contable de egreso o diario " /></td>
    </tr>
</table>
   	 </FIELDSET>
<FIELDSET>
<LEGEND>
<label class="Titulos2">Detalle de la compra </label>
</LEGEND>
<?php 
//Este control por el momento no se ha mejorado, esta pendiente
//include("../../componentes/FRONT/com_con_costos.php"); ?>
<br>
<!--<script language="javascript">
ShowHide('Tbl_Costos'); Esta pendiene por mejorar
</script>-->
  <table width="100%" border="0" cellpadding="0" cellspacing="0" class="">
	<thead>
	<tr class="Cabecera1" height="35">
		<th width="5%"><span class="Asterisco">*</span> Cant.</th>
		<th width="18%"><span class="Asterisco">*</span> Descripci?n</th>
		<th width="8%"><span class="Asterisco">*</span> P. Unitario </th>
		<th width="8%">Importe</th>
		<th width="5%">Desc.</th>
		<th width="7%"><span class="Asterisco">*</span> IVA</th>
		<th width="5%">ICE</th>
		<th width="1%">&nbsp;</th>
		<th width="9%">Adq</th>
		<th width="8%"><span class="Asterisco">*</span> Cuenta</th>
	     <th width="1%"></th>
		 <th width="10%">Descripci&oacute;n</th>
		 <th width="6%">Renta</th>
		 <th width="1%">&nbsp;</th>
		 <th width="1%">&nbsp;</th>
		 <th width="5%">IVA</th>
		 <th width="1%">&nbsp;</th>
		 <th width="1%">&nbsp;</th>
		 <th width="1%">&nbsp;</th>
	</tr>
    </thead>
    <tbody id="c_contenido" <? //echo focus_row("resaltar_text", "resaltar_back", "undo_resaltar_text", "Fondo");?> class="Fondo">
	</tbody>
    <tfoot>
	<tr >
	  <td>&nbsp;</td>
		<td align="right">SUBTOTAL:</td>
		<td>&nbsp;</td>
		<td align="center"><input name="t_subtotal" type="text" align="right" style="text-align:right" id="t_subtotal" size="8" maxlength="10" readonly="true" value="0" /></td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		</tr>
	<tr>
	  <td>&nbsp;</td>
	  <td align="right">TARIFA 0%</td>
	  <td>&nbsp;</td>
	  <td align="center"><input name="t_iva0" type="text" align="right" style="text-align:right" id="t_iva0" size="8" maxlength="8" readonly="true" value="0" /></td>
	    <td>&nbsp;</td>
	  <td>&nbsp;</td>
	  <td>&nbsp;</td>
	  <td>&nbsp;</td>
	  <td>&nbsp;</td>
	  <td>&nbsp;</td>
	  <td>&nbsp;</td>
	  <td>&nbsp;</td>
	  <td>&nbsp;</td>
	  <td>&nbsp;</td>
	  <td>&nbsp;</td>
	  <td>&nbsp;</td>
	  <td>&nbsp;</td>
	  <td>&nbsp;</td>
	  <td>&nbsp;</td>
	  </tr>
	<tr >
	  <td>&nbsp;</td>
	  <td align="right">TARIFA <span class='iva_por'>0</span>%</td>
	  <td>&nbsp;</td>
	  <td align="center"><input name="t_iva12" type="text" align="right" style="text-align:right" id="t_iva12" size="8" maxlength="8" readonly="true" value="0" /></td>
	    <td>&nbsp;</td>
	  <td>&nbsp;</td>
	  <td>&nbsp;</td>
	  <td>&nbsp;</td>
	  <td>&nbsp;</td>
	  <td>&nbsp;</td>
	  <td>&nbsp;</td>
	  <td>&nbsp;</td>
	  <td>&nbsp;</td>
	  <td>&nbsp;</td>
	  <td>&nbsp;</td>
	  <td>&nbsp;</td>
	  <td>&nbsp;</td>
	  <td>&nbsp;</td>
	  <td>&nbsp;</td>
	  </tr>
	<tr >
	  <td>&nbsp;</td>
		<td align="right"><span class='iva_por'>0</span>% I.V.A.</td>
		<td>&nbsp;</td>
		<td align="center"><input name="t_iva" align="right" style="text-align:right" type="text" id="t_iva" value="0" size="8" /></td>
                <td colspan="8">
                    <select id="selectIvas" class="" onChange="changeIvas(this.value);">
                        <?php foreach ($row_ivas as $value) {
                                    echo "<option value='$value[Iva_Cod]'>$value[Iva_Por]</option>";
                                } ?>
                    </select>
                    <script><?php if($hoy<'2016-05-31'){ ?> $('#selectIvas').hide(); <?php } ?></script>
                     <?php  if($llevarContabilidad['Cof_Con']=='S'){ ?>
                    <?php $row_rs_iva_pag = $obBD_con1->getArrayConsulta(1086, $Pla_Cod, $obBD_conexion);  ?>
                    <select name="iva_pag_par">
                        
                        <?php foreach($row_rs_iva_pag as $rowI){ ?>
                            <option value="<?php echo $rowI['Pld_Cod']; ?>"><?php echo $rowI['Pld_Des']; ?></option>
                        <?php } ?>
                    </select>
                     <?php } ?>
                </td>
		
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		</tr>
	<tr >
		<td>&nbsp;</td>
	  	<td align="right">I.C.E.</td>
	  	<td>&nbsp;</td>
	  	<td align="center"><input name="t_ice" type="text" align="right" style="text-align:right" id="t_ice" size="8" maxlength="8" readonly="true" value="0" /></td>
	  	  <td>&nbsp;</td>
	  	<td>&nbsp;</td>
	  	<td>&nbsp;</td>
	  	<td>&nbsp;</td>
	  	<td>&nbsp;</td>
	  	<td>&nbsp;</td>
	  	<td>&nbsp;</td>
	  	<td>&nbsp;</td>
	  	<td>&nbsp;</td>
	  	<td>&nbsp;</td>
	  	<td>&nbsp;</td>
	  	<td>&nbsp;</td>
	  	<td>&nbsp;</td>
	  	<td>&nbsp;</td>
	  	<td>&nbsp;</td>
	</tr>
	<tr >
	  <td>&nbsp;</td>
		<td align="right">DESCUENTO:		  </td>
		<td align="right"><input name="activar1" type="checkbox" id="activar1"  onclick="validar_text_com_ice()" value="checkbox" checked="CHECKED" disabled="disabled" />		  
		  <input name="Cop_Des" type="text" id="Cop_Des" size="2" maxlength="7" onBlur="numerico(this)" onKeyUp="validar_text_com_ice()" value="<?Php echo $Cop_Des;?>" readonly="true" /></td>
		<td align="center"><input name="t_descuento" type="text" align="right" style="text-align:right" id="t_descuento" size="8" maxlength="8" readonly="true" value="0" /></td>
		  <td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		</tr>
	<tr class="Cabecera1" height="35">
	  <td>&nbsp;</td>
		<td align="right">TOTAL:</td>
		<td>&nbsp;</td>
		<td align="center"><input name="t_rubros"    type="text"   align="right" style="text-align:right" id="t_rubros" size="8" maxlength="8" readonly="true" value="0" /></td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		</tr>
        </tfoot>  
	</table>
    <br>
    <table width="202" border="0" cellpadding="0" cellspacing="0">
	  <tr>	
		<td width="110" align="left"><input  id="nfilas" name="nfilas" type="hidden" value="0">
        <button type="button" class="btn btn-success fileinput-button" title="Agregar producto" id="button" name="button" onClick="multiple_capa('<? $_SERVER['PHP_SELF']; ?>',600,300,'cont_fon_prod','cont_cua_prod','Busqueda de Producto','cont_tit_prod')"> <i class="icon-plus icon-white"></i> <span>Producto</span> </button>	
		  </td>
		<td width="92">
		<!--<input type="button" class="Boton_Distribuir" title="Mostrar distribuir"  
onClick="ShowHide('Tbl_Costos')" align="left"> esta oendiente por mejorar--></td>	    
	  </tr>
	</table>
	<br><table width="600" border="0" cellspacing="0" cellpadding="0">
       <tr>
         <td>
		<?Php 
		/**
		* C= buscador con cargado en combos 
		*/
		$tipo_busc = 'F'; 
		$Capa = 'busqueda_f';
		$Nombre_Buscador = 'buscta';//Cuadro de texto
  		$Nombre_Opciones = 'op_opciones_cta';//Option		
		?>	         
         <div id="cont_fon_cta" class="bgtransparent" style="display:none" onClick="closeModal()"></div>
        <div id="cont_cua_cta"  class="bgmodal"   style="display:none">
        <div id="cont_cua_cta_titu"></div>
		 <?Php 		 
		include('../../componentes/FRONT/comConBuscarcta.php'); ?>
        </div>
        </td>
       </tr>
     </table>
	 <table width="600" border="0" cellspacing="0" cellpadding="0">
       <tr>
         <td>
		 <div id="cont_fon_iva" class="bgtransparent" style="display:none" onClick="closeModal()">
		</div>
		<div id="cont_cua_iva"  class="bgmodal"   style="display:none" >
		<div id="cont_cua_iva_titu"></div>
		 <?Php 
		 /**
		 * Codigo del periodo contable 
		 */
		 $Com_Pec_Cod = $Pec_Cod; ?>
		<?Php include('../COMPONENTES/tesComBusRentaIva.php'); ?>
      </div>
        </td>
       </tr>
     </table>
     <br />
     <table width="600" border="0" cellspacing="0" cellpadding="0">
       <tr>
         <td> 
         <div id="cont_fon_ice" class="bgtransparent" style="display:none" onClick="closeModal()"></div>
         <div id="cont_cua_ice" class="bgmodal" style="display:none">
         <div id="cont_cua_det_ice"></div>
         <?Php include('../COMPONENTES/tesComRubrosIce.php');?>
         </div>
         </td>
        </tr>
     </table>      
</fieldset>	
<table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
	<td width="50%" valign="top">
   
<FIELDSET>
<LEGEND>
<label class="Titulos2"> Formas de Pago </label>
</LEGEND>
<table width="100%" border="0" cellpadding="0" cellspacing="0" >
  <tr >
    <td colspan="3" valign="top" class="Etiqueta1">
    <div id="pagoSri">
        <table width="100%" border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td width="12%" align="right"><span class="Asterisco">* </span>Pago SRI:</td>
            <td width="88%" align="left">
            <? 
               $row_rs_TipoPagoCom = $obBD_con1->getArrayConsulta(1047,'', $obBD_conexion); 
            ?>
          <select name="TipoPag" id="TipoPag"  onchange="document.getElementById('hdd_TipoSri').value=this.value">
            <option value="">Seleccione...</option>
            <?Php foreach($row_rs_TipoPagoCom as $row_rs_TipoPago)
            { ?>
                <option value="<?Php echo $row_rs_TipoPago['Tpc_Cod'];?>"><?Php echo $row_rs_TipoPago['Tpc_Sri']."  -  ".$row_rs_TipoPago['Tpc_Des'];?></option>
            <? }?>
            </select>
         
            </td>
          </tr>
        </table>
       </div>
       <script language="javascript">
	   		document.getElementById('pagoSri').style.display='none'; 
	   </script>
    </td>
    </tr>
  <tr>
    <td width="12%" valign="top" class="Etiqueta1"><span class="Asterisco">* </span>Forma:</td>
    <td width="16%" valign="top" class="LetraNegra">
    <?Php 
	 /**
	 * cargar la forma de pago
	 */ 
	 $rs_pago = $obBD_con1->getArrayConsulta(16, '', $obBD_conexion);
	?>
	<select name="For_Cod" id="For_Cod" onChange="if(this.value!=''){compras_cheques();}">      
	  
	  <?Php foreach($rs_pago as $row_rs_pago)
	  {  ?>
      <option value="<?Php echo $row_rs_pago['For_Cod'];?>" <?Php if($row_rs_pago['For_Des']=='Contado'){ echo 'selected';}?>><?Php echo $row_rs_pago['For_Des'];?></option>
      <?Php 
	  //break; //esto quitar para habilitar la parte de credito
	  } ?>
    </select>
	<input type="hidden" id="hdd_TipoSri" name="hdd_TipoSri" value="1" /></td>
    <td width="72%" rowspan="5" class="LetraNegra"><table width="100%" border="0" cellpadding="0" cellspacing="0" id="Tbl_Cpp_Ven">
      <tr>
        <td class="Etiqueta1"><span class="Asterisco">*</span> Cuenta deudora:&nbsp;</td>
        <td><?Php	
		/**
		* Determina cuenta unica del proveedor en el plan de cuentas 
		*/
		$row_rs_ccpp_prove = $obBD_con1->getArrayConsulta(253, $Pla_Cod, $obBD_conexion); 		
  			?>
          <select name="ccpp_prove" id="ccpp_prove">
            <?Php foreach($row_rs_ccpp_prove as $row)
		  { ?>
            <option <?Php if ($row['Ccp_Def'] == 'D'){ echo "selected"; } ?> value="<?Php echo $row['Pld_Cod'].'*'.$row['Ccp_Cxp'];?>" ><?Php echo $row['Pld_Des'];?></option>
            <?Php 
		  } ?>
          </select></td>
      </tr>
      <tr>
        <td class="Etiqueta1"><span class="Asterisco">*</span> Fecha de vencimiento:&nbsp;</td>
        <td><input name="Cpp_Ven" type="text" id="Cpp_Ven" value="" size="10" onKeyUp="mascara(this,'-',patron,true)" onBlur="validar_fecha2(this)" /></td>
      </tr>
      <tr>
        <td class="Etiqueta1">Observaci&oacute;n:&nbsp;</td>
        <td><textarea name="Cpp_Obs" cols="28" rows="3" id="Cpp_Obs" style="text-transform:uppercase"></textarea></td>
      </tr>
    </table></td>
  </tr>
  <tr>
    <td width="12%" valign="top" class="Etiqueta1">&nbsp;</td>
    <td valign="top" class="LetraNegra">&nbsp;</td>
    </tr>
  <tr>
    <td valign="top" class="Etiqueta1">&nbsp;</td>
    <td valign="top" class="LetraNegra">&nbsp;</td>
    </tr>
  <tr>
    <td width="12%" valign="top" class="Etiqueta1">&nbsp;</td>
    <td valign="top" class="LetraNegra">&nbsp;</td>
    </tr>
  <tr>
    <td width="12%" valign="top" class="Etiqueta1">&nbsp;</td>
    <td valign="top" class="LetraNegra">&nbsp;</td>
    </tr>
  <tr>
    <td colspan="3" valign="top" class="Etiqueta1"></td>
    </tr>
</table>
</FIELDSET>	</td>
	<td width="50%" valign="top">
	<FIELDSET id="NotasCredito">
	<LEGEND>
	<label class="Titulos2">Datos de la Factura a Modificar</label>
	</LEGEND>	
    <table width="100%" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td width="28%" align="right"><span class="Etiqueta1">Cod. documento:</span></td>
        <td width="4%" align="left">
          <input name="Cop_Ntd" type="text" id="Cop_Ntd" size="3" maxlength="5" onBlur="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_CodDoc=1&Tic_Sri=' + this.value,'div_CodDoc');"/>
        </td>
        <td width="68%" colspan="2" class="Alertas"><em><div id="div_CodDoc"></div></em></td>
        </tr>
      <tr>
        <td width="28%" align="right"><span class="Etiqueta1">Num. Secuencia:</span></td>
        <td colspan="3" align="left"><span class="LetraNegra">
          <input name="Cop_Nns" type="text" id="Cop_Nns" size="15" maxlength="20" />
        </span></td>
        </tr>
      <tr>
        <td width="28%" align="right"><span class="Etiqueta1">Num. Autorizaci&oacute;n:</span></td>
        <td colspan="3" align="left"><span class="LetraNegra">
          <input name="Cop_Nna" type="text" id="Cop_Nna"  size="38" maxlength="49" />
        </span></td>
        </tr>
    </table>
    <script language="javascript">
		ShowHide('NotasCredito');		  
	</script>                                            
    </FIELDSET>
    <FIELDSET>
	<LEGEND>
	<label class="Titulos2">Datos de la retenci?n</label>
	</LEGEND>	
    <table width="100%" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td width="27%" class="Etiqueta1">C&oacute;d. Int.</td>
        <td class="LetraNegra">&nbsp;<?Php echo $row_rs_autorizacion['Aut_Cod']; ?>
          <input type="hidden" name="Aut_Cod" id="Aut_Cod" value="<?Php echo $row_rs_autorizacion['Aut_Cod']; ?>" /></td>
      </tr>
      <tr>
        <td width="27%" class="Etiqueta1">Autorizaci&oacute;n:</td>
        <td class="LetraNegra">&nbsp;<?Php echo $infoRetencion; ?></td>
      </tr>
    </table>
    <?Php include('../COMPONENTES/tesComNumRet.php'); ?>
        <table width="100%" border="0" cellpadding="0" cellspacing="0" style="margin-bottom: 2px;" >
      <tr>
        <td width="27%" class="Etiqueta1"><input name="Hdd_Ret" id="Hdd_Ret" type="hidden" value="N">
           Fecha Ret.:&nbsp;</td>
          <td align="left" class="Etiqueta1">
              <div align="left"><input id="Ret_Fec" name="Ret_Fec" type="text" size="15" value="<?php echo $row_rs_periodo['Ann'].'-'.$ann[1].'-'.$ann[2]; ?>" /></div>
          </td>    
      </tr>
    </table>    
<table width="100%" border="0" cellpadding="0" cellspacing="0" >
      <tr>
        <td width="27%" class="Etiqueta1"><input name="Hdd_Ret" id="Hdd_Ret" type="hidden" value="N">
           Renta:&nbsp;</td>
        <td align="left" class="Etiqueta1"><div align="left">
  <input name="Ren_Ren" id="Ren_Ren" type="text"  size="5" maxlength="8"  align="right" readonly="" value="0" style="text-align:right">
  &nbsp;+&nbsp; I.V.A:
<input name="Rei_Iva" id="Rei_Iva" type="text" class="" size="5" maxlength="8" readonly="" value="0"  style="text-align:right">&nbsp;=&nbsp;          
  <input name="Riv_Tot" id="Riv_Tot" type="text" class="" size="5" maxlength="8" readonly="" value="0" style="text-align:right">          
          (Valor retenido) </div></td>
        </tr>
        <tr>
        <td class="Etiqueta1" >Valor a pagar:&nbsp;</td>
        <td align="" class="Etiqueta1"><div align="left">
          <input name="Val_Pcc" id="Val_Pcc" type="text"  size="5" maxlength="8"  align="right" readonly="" value="0" style="text-align:right"  >
             <?php if($llevarContabilidad['Cof_Con']=='S'){ ?> 
              <?php $row_rs_RetPld = $obBD_con1->getArrayConsulta(1102, 'RA'.'*'.$Ses_Emp_Cod,$obBD_conexion); ?>
              <input type="text" name="Ret_Pld_Cod" value="<?php if(count($row_rs_RetPld)>0) echo $row_rs_RetPld[0]['Pld_Cod']; ?>" style="display: none" />
             <?php } ?> 
              <?php if($llevarContabilidad['Cof_Con']=='N'||($llevarContabilidad['Cof_Con']=='S'&&count($row_rs_RetPld)!=0)){ ?>  
              <div id="Reten_Asum" style="display:inline-block;"><div style="width:20px;display:inline-block;height: 16px;"><input id="Ret_Asu" name="Ret_Asu"  type="checkbox" value="S" style="position: absolute;margin-top: 5px;"/></div><b> Asumir Retención </b></div> 
              <?php } ?> 
            </div> 
            
        </td>
      </tr>
    </table>
	</FIELDSET>	</td>
  </tr>
</table>
<?Php
/**
* Asigno For_Cod=1 para poder pagar al contado 
*/
$For_Cod = 1;
/**
* Nombre del campo que posse el valor 
*/
$Hdd_Valor = 'Val_Pcc';
/**
* Nombre del campo de donde se toma la fecha para el cheque 
*/
$Hdd_Fecha = 'Com_Fec';		
?>
<? if($llevarContabilidad['Cof_Con']=='S'){
	  $contado=true; ?>
<?Php include('../COMPONENTES/tesComChequesCompra_1.0.php'); ?>
<? }?>
<br>

	<script language="javascript">
		ShowHide('Tbl_Cpp_Ven');		  
	</script>
    
	<script language="javascript">
		//ShowHide('Fie_Cheques');		  
	</script>
    
	<br><br>
<table width="260" border="0" cellpadding="0" cellspacing="0" class="Azul">
<tr><td width="110">
<button type="button" class="btn btn-inverse fileinput-button" title="Atr&aacute;s" onClick="campos_hide(this.form, '<?Php echo "txt_busqueda*op_opciones*hdd_Pec_Cod*hdd_volver"; ?>', 
  '<?Php echo $volver_busqueda.'*'.$volver_op.'*'.$hdd_Pec_Cod.'*'; ?>')">
               <i class=" icon-arrow-left icon-white"></i>
               <span>&nbsp;&nbsp;Atr&aacute;s&nbsp;&nbsp;</span>
       		 </button>
</td>
 		<td width="150">
		<?php // Tri_cod=1 No aplica si Tri_Cod=6 Gastos de viajes ?>
		  <input name="hdd_save" type="hidden" id="hdd_save" value="insertar"> 
          <input name="Ses_Rcb_Cod" id="Ses_Rcb_Cod" type="hidden" value="<?Php echo $Ses_Rcb_Cod;?>">
  		  <input name="codigo" id="codigo" type="hidden" value="<?Php echo $row_rs_proveedore['Prv_Cod'];?>">
          <input name="confi_fact" type="hidden" id="confi_fact" value="<? echo $llevarContabilidad['Cof_Con']?>"> 
          
  
   <button type="button" class="btn btn-primary start" title="Guardar documento de compra" name="btn_guardar" onClick="if(getSriCod($('#Tic_Cod').val())==='3'&&($('#t_rubros').val()*1+liquida['actual']*1)>13000){alert('Las liquidacioness de este Proveedor exceden el limite!');}else{if ((document.getElementById('Tri_Cod').value==1) || (document.getElementById('Tri_Cod').value==6)){ validar_facturacion_compra(this.form) }else{ if(document.getElementById('t_rubros').value<1000 ){validar_facturacion_compra(this.form);}else{ if(document.getElementById('hdd_TipoSri').value>='1'){validar_facturacion_compra(this.form);}else{alert('?Falta escoger Pago SRI!'); document.getElementById('TipoPag').focus();}}};}">
   <i class="icon-book icon-white"></i>
   <span>Guardar</span>
   </button>	            
  </td>
	</tr>
</table>
<?php 
	}
	else
	{
	  echo error_alerta("?No se ha configurado la cuenta contable para el Iva Pagado!<br>Soluci?n: Revise la configuraci?n del plan de cuentas",2);
	}
}//Fin del if ($codigo > 0 && !(isset($hdd_save))) ?>	
 </form>
 	<?php 
	 if (isset($hdd_save) && !isset($hdd_volver)) 
	{ 
			/**
		    *	If que restringe el guardado de ciertos DATOS cuando la empresa no lleva contabilidad
		    */
			if($llevarContabilidad['Cof_Con']=='S'){
				/**
				* Parametro para el componente
				*/
				$Com_Com_Cod = $Com_Cod;
				?>
				<?Php include('../COMPONENTES/tesComConCheque_1.0.php'); ?>	
        	<? }?>
        
	<? } // fin del if (isset($hdd_save) && !isset($hdd_volver)) ) ?> 
 <?Php
 	}//Fin del else if (!isset($hdd_Pec_Cod))
 
   }/*if($Ret_Id_Man>= $row_autorizacion_sri['Aut_Ini'] && $Ret_Id_Man<=$row_autorizacion_sri['Aut_Fin'])*/
   else
   {
 	 echo error_alerta("&iexcl;No se puede generar el Documento: [".$Ret_Id_Man."], la Autorizaci?n [".$row_autorizacion_sri['Aut_Sri']."] permite comprobantes de retenci&oacute;n entre [".$row_autorizacion_sri['Aut_Ini']."] y [".$row_autorizacion_sri['Aut_Fin']."]!", 2);
   }/*else if($Ret_Id_Man>= $row_autorizacion_sri['Aut_Ini'] && $Ret_Id_Man<=$row_autorizacion_sri['Aut_Fin'])*/
 }
 else
 {
	echo error_alerta (" No existen autorizaciones para RETENCION otorgadas por SRI, activas", 2);
 }
 }/* fin inicio if($total_rs_vendedor>0) */  
 else
 { ?><br>
 	 <?Php echo error_alerta(" Ud. NO esta autorizado para emitir Comprobantes de Compra<br>Soluci?n: Revise el registro del vendedor", 2); 
 }//FIn del else   	if($total_rs_vendedor>0)
 ?>
</table>
<br>
    <div id="cont_fon_prod" class="bgtransparent" style="display:none">
    </div>
    <div id="cont_cua_prod"  class="bgmodal"  style="display:none">
    <div id="cont_tit_prod"></div>
   <?php  if($llevarContabilidad['Cof_Con']=='S'){
     include('../COMPONENTES/tesConCtaCompras_1.0.php'); 
 }else{
      include('../COMPONENTES/tesConCtaCompras.php'); 	
 }?>
    </div>    	    
</div>
    <script>$('#Cop_Fec').change(function (){$('#Com_Fec').val($('#Cop_Fec').val());$('#Ret_Fec').val($('#Cop_Fec').val());});</script>
<script type="text/javascript" src="../VALIDACIONES/fac_par_compras.js"></script>
<script type="text/javascript" src="../../Librerias/textbox/main.js"></script>	  
            <script>
                $( document ).ready(function() {
                    $('#Ret_Asu').change(function (){
                        cal_tarifas(7,4);//Llamado del calculo de tarifas OJO ES 7 PARA INGRESAR
                        cal_iva_importe('Cop_Des',7,5,4); //Calculo del iva
                        cal_ice_importe('Cop_Des',10,5,4);
                        cal_total_com_ice();//Calculo del total
                        valor_renta_compra();                       
                    });
                    $('#Tic_Cod').change(function (){
                        $('#Ret_Asu').attr('checked', false).change();
                        if(('0'+$('#Tic_Cod').val())*1===1||('0'+$('#Tic_Cod').val())*1===3){
                            $("#Reten_Asum").show();                            
                        }else{    
                            $("#Reten_Asum").hide();                           
                        }
                    });
                    $("#Reten_Asum").hide();
                });                
            </script>
</BODY>
</HTML>
<?Php 	
/**
* cierro las conexiones 
*/
$obBD_con1->liberar();
$obBD_conexion->cerrar();
/**
* fin cierre las conexiones 
*/
?>