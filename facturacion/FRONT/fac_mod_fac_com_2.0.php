<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?Php 
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_compras.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');	
require_once('../../Librerias/postclass.php');
//require_once('../../contabilidad/LOGICA/com_log_compr.php');
//require_once('../../componentes/LOGICA/com_log_compr.php');

/**
* Creacion del Objeto de conexion 
*/
$obBD_conexion = new Class_Log_Conexion_Comt($Ses_Dat_Dis);
/**
* Creacion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Comt; 	  
/**
* Creaci�n del objeto para evitar el reenvio 
*/
$thisPost = new Post_Block;
/**
* evitar el reenvio de formularios 
*/
$hoy = date("Y-m-d");
/**
* asigno el valor a la variable mes 
*/
$mes = date("m");

/**
* Llamado a componente ajax 
*/
require_once("../COMPONENTES/ajaxComBusRentaIva.php"); 
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
* Definici�n de un valor constante para la variable tipo_compr 
*/
define(tipo_compr, 6); //Tipo de comprobante de la retencion 
define(nota_venta, 2); //Tipo de comprobante de la nota de venta
define(tiquetes, 8); //Tipo de comprobante de la TIQUETES O VALES EMITIDOS POR MAQUINAS REGISTRADOR
//define(Tic_Cod,1);	  

/**
*	Consultamos si lleva contabilidad
*/
$llevarContabilidad = $obBD_con1->getRowConsulta(1041, $Ses_Emp_Cod,$obBD_conexion);
	  
/**
* Cargado de Informaci�n a trav�s de AJAX 
*/
if (isset($ajax_cuenta))
{		
	/**
	* En esta consulta debe botar un solo registro ya en un a�o contable normalmente se utiliza un plan de cuentas 
	*/
	$row_rs_cuenta_manual = $obBD_con1->getRowConsulta(248, $Pec_Cod, $obBD_conexion);
	$Pla_Cod = $row_rs_cuenta_manual['Pla_Cod'];	
	/**
	* Consulta las cuentas 
	*/
	$row_rs_buscli = $obBD_con1->getRowConsulta(249, $ajax_cuenta.'*'.$Ses_Emp_Cod.'*'.$Pla_Cod,$obBD_conexion);
	$row_rs_buscli = $obBD_con1->registros();
	$total_rs_buscli = $obBD_con1->numregistros();
	if ($total_rs_buscli > 0)
	{	$cuenta=$row_rs_buscli['Pld_Des'];
		$codigo=$row_rs_buscli['Pld_Cod'];
	}else{
		$cuenta="Cuenta Inexistente";
		$codigo=0;
	}	
	if(isset($cuenta))
	{	$return_value = '<?xml version="1.0" standalone="yes"?><cuenta><descripcion>'.utf8_encode($cuenta).'</descripcion><codigo>'.$codigo.'</codigo></cuenta>';  	
	}
	header('Content-Type: text/xml'); 
	echo $return_value;
	@$obBD_con1->free_result($rs_cuenta_manual);
	exit();
}//Fin del if (isset($ajax_cuenta))
	  
/**
* Condicion para evaluar cuando mostrar los periodos contables 
*/
if (isset($hdd_Pec_Cod))
{
 if ($thisPost->postBlock($_POST['postID']))
  { 
  if(isset($hdd_save) && !isset($hdd_volver)) 
  {
	  /**
	  * Actualizaci�n a los datos de la Cabecera de la factura 
	  */
	  /**
	  * Creacion del objeto mysql para las inserciones 
	  */
	  $obBD_ins1 =  new Class_Log_Datos_Comt;
	  /**
	  * Inicio de la transaccion 
	  */
	  $obBD_ins1->inicio_transaccion($obBD_conexion->conexion);
	  /**
	  * Actualiza la cabecera de compras
	  */
	  $obBD_ins1->operacionobBD(719, $Tic_Cod.'*'.$codigo.'*'.$Ciu_Cod.'*'.trim($Cop_Num).'*'.$Cop_Aut.'*'.$Cop_Fec.'*'.$hoy.'*'.trim($Cop_Des).'*'.trim($Cop_Obs).'*'.$Cop_Cad.'*'.$Cop_Imp.'*'.$Tri_Cod.'*'.$hdd_TipoSri.'*'.$Cop_Cod, $obBD_conexion);
				
	  /**
	  *	Consulta si una compra posee una Liquidacion con vales de caja chica y que este apta para su modificacion
	  */
	  $row_rs_recibo = $obBD_con1->getRowConsulta(1046, $Cop_Cod, $obBD_conexion);
	  $tot_rs_recibo=$row_rs_recibo['Cop_Cod'] > 0? 1 : 0;
		
	  if ($tot_rs_recibo != 0 || $row_rs_recibo['Liq_Est']=='L' ) 
	  {
		/**
		*	Actualizamos el VALE DE CAJA agregando el "Cambio" respectivo
		*/
		$obBD_ins1->operacionobBD(1045, ($row_rs_recibo['Rcb_Tot'] - $Val_Pcc).'*'.$row_rs_recibo['Rcb_Cod'], $obBD_conexion);
	  }	
		/***********************************************************************/
		/*    CONTROL AUTOMATICO PARA GENERAR COMPROBANTES DE EGRESO/DIARIO    */
		/***********************************************************************/	
		/* 1 Contado - SI Retenci�n = NO COMPROBANTE EGRESO
		1 Contado - NO Retenci�n = SI COMPROBANTE EGRESO *-*
		2 Contado - SI Retenci�n = NO COMPROBANTE DIARIO
		2 Contado - No Retenci�n = SI COMPROBANTE DIARIO *-* 
		*/		
		
		/**
	    *	If que restringe el guardado de ciertos DATOS cuando la empresa no lleva contabilidad
	    */
		if($llevarContabilidad['Cof_Con']=='S'){
		if ($Hdd_Ret == 'N' || $Hdd_Ret == 'S' )
		{	
			/**
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
				$var_mes = explode('-', $Com_Fec);
				/**
				* Concepto Standar del  Comprobante de Egreso/Diario 
				*/
				$Com_Con = $Cop_Obs;				
				$hdd_mes = explode('-', $Hdd_Com_Fec);				
				/**
				* Control para cambiar el codigo del comprobante cuando se cambia de fecha del comprobante 
				*/
				if ($var_mes[1] != $hdd_mes[1])
				{
					/**
					* Consulta el numero del comprobante de Egreso/Diario 
					*/
					$Com_Num= $obBD_ins1->codigoComprAuto($op, $Pec_Cod, $var_mes[1], $obBD_conexion); 	
				}//Fin del if ($mes[1] != $hdd_mes[1])
												
				/**
				* ACTUALIZA el comprobante de compra 
				*/	
				$obBD_ins1->operacionobBD(191, trim($Com_Con).'*'.trim($Cop_Obs).'*'.$Com_Cod.'*'.$Val_Pcc.'*'.$Com_Num.'*'.$Com_Fec, $obBD_conexion); 				

				/**
				* Registra el Iva en caso que sea mayor a cero y el tipo de documento sea diferente de una nota de venta 
				*/
				if ($t_iva > 0 and ($Tic_Cod != nota_venta and $Tic_Cod != tiquetes))
				{					
					/**
					* Consulta para determinar la cuenta del IVA PAGADO del plan de cuentas actual  
					*/
					$row_rs_ivap = $obBD_con1->getRowConsulta(252, $Pla_Cod, $obBD_conexion); 
					$iva_pagado = $row_rs_ivap['Pld_Cod'];
				}//Fin del if ($t_iva > 0)
			}//Fin del if ($total_rs_form_compr > 0)
			else
			{ ?><script language="javascript">
				alert("Debe configurar el tipo de comprobante: �Egreso/Diario?");
				</script>					
			<?Php
			}//Fin del else if (count($row_rs_form_compr) > 0)				
	 	}//Fin del if (!($For_Cod == 1 and $Hdd_Ret == 'S'))	
	 }// FIN DEL if($llevarContabilidad['Cof_Con']=='S')
		
	/**
	* Elimina en la base de datos del detalle de la compra 
	*/
	$obBD_ins1->operacionobBD(348, $Cop_Cod, $obBD_conexion);
	
	/**
	* Consulta el c�digo del asiento en base al c�digo del comprobante 
	*/
	//$rs_asiento_cheque=$obBD_con1->consulta(sentencias_comf(364, $obBD_con1->parametros($Com_Cod)), $obBD_conexion->conexion); ojojo revisar si esto va o no
	//$row_rs_asiento_cheque=$obBD_con1->registros(); 
	
	/**
    *	If que restringe el guardado de ciertos DATOS cuando la empresa no lleva contabilidad
    */ 		
	if($llevarContabilidad['Cof_Con']=='S'){
		/**
		* Elimina los asientos del comprobante /en cascada elimina los cheques del asiento 
		*/
		$obBD_ins1->operacionobBD(353, $Com_Cod, $obBD_conexion);   
		
		/* elimino los cheques relacionados al asiento contable */
		//$obBD_ins1->grabarv_registros(sentencias_comf(365, $obBD_ins1->parametros($row_rs_asiento_cheque['Asi_Cod'])), $obBD_conexion->conexion);  ojojo revisar si esto va o no
	}
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
	$obBD_ins1->operacionobBD(1040,$Cop_Cod,$obBD_conexion);	
	
	foreach ($datos as $puntero => $item)
	{  
	  $cant++;
	  //echo '-'.$cant;
	  $param[]=$item;				
	  if($cant==22)
	  {  
	  	$cant=0;
		/**
		* Grabado del detalle de la factura de compra 
		*/
		/**
		* ICE 
		*/
		if ($param[6] == "0*0" || $param[6] == "")
		{	$param[6] = 'NULL';	 }	
		/**
		* Descuenta 
		*/	
		 if ($param[4] == "")
		 {	$param[4] = 0; }
		 
		 
		 /**
	     *	If que restringe el guardado de ciertos DATOS cuando la empresa no lleva contabilidad
	     */
		 if($llevarContabilidad['Cof_Con']!='S'){
		 	/**
			*	Control para ingresar codigo plan de cuentas
			*/
			$param[12]='NULL';
			
		 }
		//$produc=$produc.$param[1].'*';
		/**
		* Inserta el detalle de compras
		*/				
		$obBD_ins1->operacionobBD(720, $Cop_Cod.'*'.$param[0].'*'.$param[5].'*'.trim($param[1]).'*'.$param[2].'*'.$param[3].'*'.$param[4].'*'.$param[8].'*'.$param[6].'*'.$param[13].'*'.$param[12].'*'.$param[21], $obBD_conexion);												
		/**
		* Control para I N V E N T A R I O S 
		*/
		$row_rs_adquisicio = $obBD_con1->getRowConsulta(1037, $param[21],$obBD_conexion);		
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
			
			/**
		    *	If que restringe el guardado de ciertos DATOS cuando la empresa no lleva contabilidad
		    */
			if($llevarContabilidad['Cof_Con']=='S'){
			/**
			* Actualiza el kardex 
			*/
			$obBD_ins1->operacionobBD(1072, '0'.'*'.'0'.'*'.$Vnd_Cod.'*'.$Cop_Cod.'*'.$param[21].'*'.$Cop_Fec.'*'.'00:00:00'.'*'.$param[0].'*'.'0'.'*'.'0'.'*'.$param[2].'*'.'0'.'*'.$param[3].'*'.$param[4].'*'.$param[5].'*'.'0', $obBD_conexion);								
			}
			/**
			* Consulta el Stock 
			*/
			$row_rs_conpro = $obBD_con1->getRowConsulta(1206, $param[21],$obBD_conexion);					
			
			/**
		    *	If que restringe el guardado de ciertos DATOS cuando la empresa no lleva contabilidad
		    */
			if($llevarContabilidad['Cof_Con']=='S'){
				/**
				* Actualizo el Stock 
				*/
				$obBD_ins1->operacionobBD(1204, $row_rs_conpro['Stock'].'*'.$param[0].'*'.$Ses_Suc_Cod, $obBD_conexion);					
			}
}
			/***********************************************************************/
			/*    CONTROL AUTOMATICO PARA GENERAR COMPROBANTES DE EGRESO/DIARIO    */
			/***********************************************************************/	
			/*	1 Contado - SI Retenci�n = NO COMPROBANTE EGRESO
				1 Contado - NO Retenci�n = SI COMPROBANTE EGRESO *-*
				2 Contado - SI Retenci�n = NO COMPROBANTE DIARIO
				2 Contado - No Retenci�n = SI COMPROBANTE DIARIO *-* 
			*/
			if ($Hdd_Ret == 'N' || $Hdd_Ret == 'S')		
			{	
				/**
				* Entra solo cuando el tipo de documento sea igual a una nota de venta
				* y suma el total del iva a la cuenta asignada al registro de la compra 
				*/
				$rubro_d = 0; //Inicializa en cero para cuando hay mas de 1 registro
				if (($Tic_Cod != nota_venta and $Tic_Cod != tiquetes) or $param[11] == 0)//$param[11] => porcentaje de iva
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
					$obBD_ins1->operacionobBD(256, $Com_Cod.'*'.'D'.'*'.$rubro_d.'*'.''.'*'.''.'*'.$param[12], $obBD_conexion);
				}
			}//Fin del if (!($For_Cod == 1 and $Hdd_Ret == 'S'))
			/***********************************************************************/
			/***********************************************************************/
			/***********************************************************************/													
			/***********************************************************************/
			/*			 	GRABADO  DEL DETALLE DE LA RETENCI�N				    */
			/***********************************************************************/
			/**
			* det_compra incrementa en 1 | det_retenc en 1 
			*/
			/**
			* Grabado en caso que sea RENTA
			*/	
			 if(trim($param[17])!="" || trim($param[18])!="")
			 {  
				/**
				* Indice que indica que hay renta 
				*/
				/**
				* Consulto el codigo de la retencion a modificar 
				*/
				$row_rs_retencion_modificar=$obBD_con1->getRowConsulta(718, $Cop_Cod, $obBD_conexion);
				/**
				* incremento en 1 
				*/
				$ind_rta++; 
				if($ind_rta==1)
				{ /* inicio if($ind_rta==1){  */
					/**
					* Consulta de la autorizacion para la retencion 
					*/
					$row_autorizacion_sri=$obBD_con1->getRowConsulta(517, $Ses_Prs_Cod.'*'.tipo_compr.'*'.$Ses_Suc_Cod, $obBD_conexion);
					$Aut_Cod=$row_autorizacion_sri['Aut_Cod']; 
					
					if(count($row_rs_retencion_modificar)!=0)
					{ 	
						/**
						* inicio isset($Hdd_Rete_Modificar)  
						*/
						/********************************/
						/*  INSERCI�N DE LA RETENCI�N   */
						/********************************/
						/**
						* Actualiza en la base de datos la cabecera de la retenci�n 
						*/
						$obBD_ins1->operacionobBD(354, $Cop_Cod.'*'.$Ret_Int.'*'.$Cop_Fec.'*'.trim($Cop_Obs).'*'.tipo_compr.'*'.$Vnd_Cod.'*'.$Aut_Cod.'*'.$Ret_Cod, $obBD_conexion); 
						/**
						* Eliminar el detalle de la retencion 
						*/
						$obBD_ins1->operacionobBD(355, $Ret_Cod, $obBD_conexion);    		
						/*********************************/
						/* FIN INSERCI�N DE LA RETENCI�N */
						/*********************************/
					}/* fin inicio isset($Hdd_Rete_Modificar) */
					else
					{  	
						/**
						* Grabo en la base de datos la cabecera de la retenci�n 
						*/
						$obBD_ins1->operacionobBD(491, $Cop_Cod.'*'.$Ret_Int.'*'.$Com_Fec.'*'.trim($Cop_Obs).'*'.tipo_compr.'*'.$Vnd_Cod.'*'.$Aut_Cod, $obBD_conexion);	
						$Ret_Cod=$obBD_ins1->insercionid ($obBD_conexion->conexion);
					}/* inicio isset($Hdd_Rete_Modificar)  */
				} /* fin if($ind_rta==1)  */
			 }	
			/***********************************************************/
			/* Preguntamos si existe un descuento al total del importe */
			/***********************************************************/				
			if($Cop_Des>0)
			 { 
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
			 * Dismunic�n de descuento individual al importe 
			 */	
			 $renta_grav=$param[3]-$des_indivi;				 
			/***********************************************************/
			/***********************************************************/
												 
			/**
			* Grabado en caso que sea RENTA
			*/
			if(trim($param[17])!="") 	
			{	 
				/**
				* incremento en 1 
				*/
				$indi_auto++;

				if($llevarContabilidad['Cof_Con']=='S'){
					/**
					* Consultar los codigos de RENTA 
					*/
					$row_renta_sri=$obBD_con1->getRowConsulta(360, $param[17], $obBD_conexion);
				}else{

					$row_renta_sri=$obBD_con1->getRowConsulta(1043, $param[17], $obBD_conexion);
				}
				
				$obBD_ins1->operacionobBD(492, $Ret_Cod.'*'.$renta_grav.'*'.$row_renta_sri['Ren_Cod'].'*'.'R'.'*'.$param[13].'*'.$param[8], $obBD_conexion);
				/**
				* C�lculo porcentaje RENTA de retenci�n para almacenar como base del comprobante de retenci�n 
				*/
				$reten_bas_compr=(($renta_grav*$param[19])/100); 									
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
			}//Fin del if(trim($param[17])!="") 
				
			/**
			* Grabado en caso que sea I.V.A. 
			*/
			if(trim($param[18])!="")
			{	
				/**
				* incremento en 1 
				*/
				$indi_auto++; 
				if($llevarContabilidad['Cof_Con']=='S'){
					/**
					* Consultar los codigos de RENTA 
					*/
					$row_renta_sri=$obBD_con1->getRowConsulta(360, $param[18], $obBD_conexion);
				}else{
					$row_renta_sri=$obBD_con1->getRowConsulta(1043, $param[18], $obBD_conexion);
				}
				/**
				* Iva grabada sobre el importe menos descuento 
				*/
				$grava_iva=$renta_grav*$param[11]/100; 
				/**
				* renta_grav toma el valor del porcentaje de retenci�n 
				*/
				$renta_grav=$grava_iva;
				$obBD_ins1->operacionobBD(492, $Ret_Cod.'*'.$renta_grav.'*'.$row_renta_sri['Ren_Cod'].'*'.'I'.'*'.$param[13].'*'.$param[8], $obBD_conexion);
				/**
				* C�lculo porcentaje IVA de retenci�n para almacenar como base del comprobante de retenci�n 
				*/
				$reten_iva_compr=(($renta_grav*$param[20])/100); 
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
			 }//FIn del if(trim($param[18])!="")
			 /**
			 * Grabado en caso que sea IVA 
			 */
			 /**
			 *  FIN GRABADO RETENCION 	
			 */
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
				$obBD_ins1->operacionobBD(256, $Com_Cod.'*'.'H'.'*'.$array_val_renta[$i].'*'.''.'*'.''.'*'.$array_cnta_renta[$i], $obBD_conexion);	 
			}//Fin del for ($i=0;$i<=count()-1;$i++)
			/**
			* Recorrido para el grabado del iva en los comprobantes 
			*/
			for ($i=0;$i<=count($array_cnta_iva)-1;$i++)
			{			
				/**
				* Cuentas del HABER del proveedor - Asignado automaticamente 
				*/
				$obBD_ins1->operacionobBD(256, $Com_Cod.'*'.'H'.'*'.$array_val_iva[$i].'*'.''.'*'.''.'*'.$array_cnta_iva[$i], $obBD_conexion); 
			}//Fin del for ($i=0;$i<=count()-1;$i++)
		}// fin del if($llevarContabilidad['Cof_Con']=='S')
		/***********************************************************************/
		/*    CONTROL AUTOMATICO PARA GENERAR COMPROBANTES DE EGRESO/DIARIO    */
		/***********************************************************************/
		/* 1 Contado - SI Retenci�n = NO COMPROBANTE EGRESO
		   1 Contado - NO Retenci�n = SI COMPROBANTE EGRESO *-*
		   2 Contado - SI Retenci�n = NO COMPROBANTE DIARIO
		   2 Contado - No Retenci�n = SI COMPROBANTE DIARIO *-* 
		*/
		if ($Hdd_Ret == 'N' || $Hdd_Ret == 'S')		
		{	
			/**
			* Grabado del detalle del comprobante de Egreso/Diario 
			*/	
			
			/**
			*	If que restringe el guardado de ciertos DATOS cuando la empresa no lleva contabilidad
			*/
			if($llevarContabilidad['Cof_Con']=='S'){
			/**
			* Registra el Iva en caso que sea mayor a cero y el tipo de documento sea diferente de una nota de venta
			*/
			if ($t_iva > 0 and ($Tic_Cod != nota_venta and $Tic_Cod != tiquetes))
			{				
				/**
				* Cuentas del DEBE Iva Pagado - Asignado automaticamente 
				*/	
				$obBD_ins1->operacionobBD(256, $Com_Cod.'*'.'D'.'*'.$t_iva.'*'.''.'*'.''.'*'.$iva_pagado, $obBD_conexion); 															
			}//Fin del if ($t_iva > 0)
			}// fin del if($llevarContabilidad['Cof_Con']=='S')
			
			/**
			* Control para determinar la cuenta del HABER, Si es Contado o Credito 
			*/
			if ($For_Cod == 2 && $llevarContabilidad['Cof_Con']=='S')//Pago a C R E D I T O
			{
				/**
				* Control para que no grabe en caso de no existir configurada la 
				* cuenta en el plan de cuentas 
				*/
				if ($total_rs_ccpp_prove > 0) 						
				{
					/*DS $ccpp_prove = $row_rs_ccpp_prove['Pld_Cod']; DS*/
				}//Fin del if ($total_rs_ccpp_prove > 0) 	
				else
				{
					/* Asigna CERO (0) para que se realice un ROLLBAKC */
					/*DS $ccpp_prove = 0; DS*/
				}//Fin del else if ($total_rs_ccpp_prove > 0) 	

				/**
				* Cuentas del HABER del proveedor - Asignado automaticamente 
				*/
				$total_proveedores=$Val_Pcc; 
				
				$obBD_ins1->operacionobBD(256, $Com_Cod.'*'.'H'.'*'.$total_proveedores.'*'.''.'*'.''.'*'.$ccpp_prove,$obBD_conexion);	
			
			}//Fin del if ($For_Cod == 2)
			else//Pago a C O N T A D O
			{	
				/**
				* 1 Contado - SI Retenci�n = NO COMPROBANTE EGRESO     -> NO Cheque
				* 1 Contado - NO Retenci�n = SI COMPROBANTE EGRESO *-* -> SI Cheque
				* 2 Contado - SI Retenci�n = NO COMPROBANTE DIARIO     -> NO Cheque
				* 2 Contado - No Retenci�n = SI COMPROBANTE DIARIO *-* -> SI Cheque 
				*/
				/**
				* Determinar cuenta de los bancos 
				*/
				$cant=0;
				
				
				/**
			    *	If que restringe el guardado de ciertos DATOS cuando la empresa no lleva contabilidad
			    */
				if($llevarContabilidad['Cof_Con']=='S'){
				foreach ($datos_ch as $puntero => $item)
				{	$cant++;
					$param[]=$item;
					if ($cant==6)
						{	$cant=0;
							/**
							* Sepacion del banco y la cuenta del plan de cuentas 
							*/
							$Asi_Cod = explode('*',$param[0]);
							/**
							* Cuentas del HABER del proveedor - Asignado automaticamente 
							*/
							$obBD_ins1->operacionobBD(256, $Com_Cod.'*'.'H'.'*'.$param[2].'*'.''.'*'.''.'*'.$Asi_Cod[1], $obBD_conexion);		
							$asiento = $obBD_ins1->insercionid($obBD_conexion->conexion); 
							/**
							* Guardado de los cheques 
							*/
							if($Asi_Cod[2]=="B") 
							{														
								$obBD_ins1->operacionobBD(307, $codigo.'*'.$Asi_Cod[0].'*'.$asiento.'*'.$param[1].'*'.$param[2].'*'.$param[4].'*'.$param[3].'*'.$param[5], $obBD_conexion); 
							}
							unset($param);
						}//Fin del if ($cant==7)
				}//Fin del foreach ($datos_ch as $puntero => $item)
				} //fin del if($llevarContabilidad['Cof_Con']=='S')
			}//Fin del else if ($For_Cod == 2)
			/* Control para enlazar las COMPRAS con los COMPROBANTES */
			/*DS $obBD_ins1->grabarv_registros(sentencias_comf(254,$obBD_ins1->parametros($Com_Cod.'*'.$Cop_Cod)), $obBD_conexion->conexion); DS*/				 						
		}//Fin del if (!($For_Cod == 1 and $Hdd_Ret == 'S'))			
		$obBD_ins1->fin_transaccion($obBD_conexion->conexion);
	}	  
}/*Fin del if ($thisPost->postBlock($_POST['postID']))*/

/**
* Cargado de los datos de la Cabecera1 
*/
if ($txt_busqueda != "") 
{   
	if ($op_opciones == "d")
	{	
		$rs_buscar = $obBD_con1->getArrayConsulta(713, trim($txt_busqueda).'*'.$Tic_Cod.'*'.$Pec_Cod.'*'.$cmb_mes, $obBD_conexion);
	}
	elseif($op_opciones == "ru")
	{
		$rs_buscar = $obBD_con1->getArrayConsulta(715, trim($txt_busqueda).'*'.$Tic_Cod.'*'.$Pec_Cod.'*'.$cmb_mes, $obBD_conexion);
	}
	else
	{
		$rs_buscar = $obBD_con1->getArrayConsulta(714, trim($txt_busqueda).'*'.$Tic_Cod.'*'.$Pec_Cod.'*'.$cmb_mes, $obBD_conexion);
	}/* fin if($op_opciones == "d")  */
	$total_rs_buscar=count($rs_buscar);
}
elseif(isset($codigo))
 { 	
 	/**
	* consulta datos de los proveedores
	*/
	$rs_proveed = $obBD_con1->getArrayConsulta(472, $codigo, $obBD_conexion);	
	/**
	* Consulta de las ciudades
	*/
	$rs_ciudad = $obBD_con1->getArrayConsulta(709, '', $obBD_conexion);	
	/**
	* Consulta de los porcentajes del I.C.E. (Impuesto a los consumos especiales) 
	*/
	$rs_ice=$obBD_con1->getArrayConsulta(707, '', $obBD_conexion);
	/** 
	* Recorrido y asignaci�n de los porcentajes del I.C.E. a un arreglo 
	*/
	foreach($rs_ice as $row_rs_ice)
	{
		$ice_cod[]=$row_rs_ice['Ice_Int'];
		$ice_por[]=$row_rs_ice['Ice_Por'];
	}
	$ice_cod = 'Array(\'' . @implode('\', \'', $ice_cod) . '\')';
	$ice_por = 'Array(\'' . @implode('\', \'', $ice_por) . '\')';
	
	/** 
	* Consulta el sustento 
	*/
	$rs_sustento = $obBD_con1->getArrayConsulta(711, '', $obBD_conexion);	
} //if (isset($codigo))
	
	/**
	* Carga el periodos contable actual 
	*/
	$row_rs_periodo = $obBD_con1->getRowConsulta(189, $Pec_Cod, $obBD_conexion);
	/**
	* Descripcion del periodo contable 
	*/
	$periodo = "en el periodo contable ".substr($row_rs_periodo['Pec_Fei'], 0,4);	
}//Fin del if (!isset($hdd_Pec_Cod))


if(isset($ajax_info)){

	include('../COMPONENTES/tesComDetalleCom.php');
exit();
}
	
/**
* Consulta del vendedor en base al codigo de la persona
*/
$row_rs_vendedor = $obBD_con1->getRowConsulta(24, $Ses_Prs_Cod.'*'.$Ses_Suc_Cod, $obBD_conexion);		
?>
<HTML>
	<HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
		<?php require_once("../../mascaras/model1/estilos/estilos.php");?>		
		<script language="javascript" src="../VALIDACIONES/fac_val_compras.js"></script>
        <script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>
        <!--Librerias para interfaz -->       
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>         
        <!--Librerias para modal -->       
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.modals.js"></script> 
	    <!--Librerias para calendario -->       
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.datepicker.js"></script>  
        <script language="javascript" src="../VALIDACIONES/XML.js"></script>		
        <script type="text/javascript"> 
        $(function() {
			$('#set1 *').tooltip({showURL: false});
		});              			
		</script>
         <script type="text/javascript" src="../../Librerias/masked/jquery.maskedinput-1.2.2.js"></script>
       <script>
		$(function() { 
			/* Campo 1 */
			$( "#Cop_Fec" ).datepicker();			
			$( "#Cop_Fec" ).change(function() {
			$( "#Cop_Fec" ).datepicker( "option", "dateFormat", "yy-mm-dd" );
		});			
			/* Campo 2 */
			$( "#Cop_Cad" ).datepicker();			
			$( "#Cop_Cad" ).change(function() {
			$( "#Cop_Cad" ).datepicker( "option", "dateFormat", "yy-mm-dd" );
		});			
			/* Campo 3 */
			$( "#Com_Fec" ).datepicker();			
			$( "#Com_Fec" ).change(function() {
			$( "#Com_Fec" ).datepicker( "option", "dateFormat", "yy-mm-dd" );
		});	
			/* Campo 4 */
			$( "#Cop_Imp" ).datepicker();			
			$( "#Cop_Imp" ).change(function() {
			$( "#Cop_Imp" ).datepicker( "option", "dateFormat", "yy-mm-dd" );
		});
			/* Campo 5 */
			$( "#Cpp_Ven" ).datepicker();			
			$( "#Cpp_Ven" ).change(function() {
			$( "#Cpp_Ven" ).datepicker( "option", "dateFormat", "yy-mm-dd" );
		});		
		}); 
		
		/**
		* Control de mascaras
		*/
		jQuery(function($){
			$("#Cop_Num").mask("999-999-999999999",{placeholder:"_"});
		});		
        </script>            
		</HEAD>
<BODY>
<div id="set1">
<?Php 
if (isset($hdd_save))
{
	/**
	* Consulta la retencion en caso de tenerla asiganda a la compra OJOJOJOJOJO
	*/
	$row_modifica_reten = $obBD_con1->getRowConsulta(717, $Cop_Exi_Ret, $obBD_conexion);	
	if(count($row_modifica_reten) > 0)
	{	
		$row_cargar_codigo_ren = $obBD_con1->getRowConsulta(718, $row_modifica_reten['Cop_Cod'], $obBD_conexion);	
	}
}
?>
<table width="98%" border="0" cellpadding="0" cellspacing="0" class="table">
  <tr class="BarraTitulo">
	  <td width="50%" align="left">&raquo; Modificar Documentos de Compras <?Php echo $periodo; ?></td>
	  <td width="45%" align="right">&raquo; PUNTO DE IMPRESI&Oacute;N: <?Php echo $row_rs_vendedor['Pun_Des']; ?></td>
  </tr>
	<tr>
        <td height="389" colspan="2" valign="top">
<?Php 
if(count($row_rs_vendedor)>0)
{  
/**
* Condicion para evaluar cuando mostrar los periodos contables 
*/
if (!isset($hdd_Pec_Cod) && !isset($hdd_volver))
{
?><form name="form1" method="post" action="<?Php echo $_SERVER['PHP_SELF']?>">		
		<?Php include("../../componentes/FRONT/comConPeriodoCont.php"); ?>
 </form>
<?Php
}//Fin del if (!isset($hdd_Pec_Cod))
else
{	
	/**
	* Consulta el tipo de comprobante 
	*/
	$rs_tip_compr = $obBD_con1->getArrayConsulta(729, '', $obBD_conexion);	
?>
	<form name="form1" method="post" action="<?Php echo $_SERVER['PHP_SELF']?>">		
	<input name="hdd_Pec_Cod" id="hdd_Pec_Cod" type="hidden" value="">
	<input name="Pec_Cod" id="Pec_Cod" type="hidden" value="<?Php  echo $Pec_Cod; ?>">
		<FIELDSET>
	<LEGEND>
    <label class="Titulos2">Buscar por:</label>
    </LEGEND>
	<table width="770" border="0" cellpadding="0" cellspacing="0">
      <tr>
        <td width="91" class="Etiqueta1"><span class="Asterisco">* </span>Tipo  documento:</td>
        <td colspan="4"><span class="LetraNegra">
          <select name="Tic_Cod" id="Tic_Cod">            
            <?Php foreach($rs_tip_compr as $row_rs_tip_compr)
	  			{ ?>
            <option <?php ?> value="<?php echo $row_rs_tip_compr['Tic_Cod']?>"><?php echo $row_rs_tip_compr['Tic_Des'];?></option><?php
	 		 } ?>
            </select>
        </span></td>
        </tr>
      <tr>
        <td colspan="2"><input name="op_opciones" type="radio" value="d" onClick="document.getElementById('cmb_mes').disabled=false; document.getElementById('Tic_Cod').disabled=false;  setfocus(form1.txt_busqueda)" style="cursor:pointer"  checked>
            <span class="Etiqueta1">Apellidos</span></td>
        <td width="79"><input name="op_opciones" type="radio" value="ru" onClick="document.getElementById('cmb_mes').disabled=false; document.getElementById('Tic_Cod').disabled=false;  setfocus(form1.txt_busqueda)" style="cursor:pointer" >
            <span class="Etiqueta1">RUC</span></td>
        <td width="144"><input type="radio" name="op_opciones" value="r" onClick="document.getElementById('cmb_mes').disabled=true;  
document.getElementById('Tic_Cod').disabled=true;        
        setfocus(form1.txt_busqueda)"  style="cursor:pointer"   >
          <span class="Etiqueta1">No. Documento </span></td>
        <td width="455">	
          <?php 
		/**
		* Parametro de la busqueda por fecha en compras 
		*/
		$Com_Fecha="AND MONTH(Cop_Fec)"; 
		?>		
          <?Php include('../../componentes/FRONT/com_con_meses.php');?></td>
      </tr>
    </table>
	<table width="610" height="36" border="0" cellpadding="0" cellspacing="0">
    <tr>
      <td width="405" height="28" class="BarraBusqueda"><span class="Asterisco">* </span>Busqueda:
        <input name="txt_busqueda" type="text" id="txt_busqueda" value="" size="40" maxlength="50" >&nbsp;                 
        </td>
      <td width="205" align="center"><button type="button" name="btn-buscar" id="btn-buscar" class="btn btn-success fileinput-button" title="Buscar compras" onclick="validar_requeridos(this.form, 'txt_busqueda', 0)">
            <i class="icon-search icon-white"></i>
            <span>Buscar</span>
            </button></td>
      </tr>
  </table>
</FIELDSET>
	</form>
  <?Php
if(isset($txt_busqueda) && !isset($hdd_mod))
{	
  ?>
	
<FIELDSET>
<LEGEND>
<label class="Titulos2">Resultados de la busqueda</label>
</LEGEND>
	<table width="100%"  border="1" cellpadding="0" cellspacing="0" class="fixedHeader03">
    <thead>
	  <tr>
	    <th width="5%">Cod. Int</th>
	    <th width="6%">Compr.</th>
	    <th width="13%">Tipo  documento </th>
	    <th width="13%">No. Documento.</th>
		<th width="8%">Fecha </th>	
        <th width="48%">Proveedor</th>
        <th width="2%">&nbsp;</th>
        <th width="5%">&nbsp;</th>
      </tr>
      </thead>
      <tbody>
	    <?Php 
	if(count($rs_buscar) != 0)
	{	
		$i=0;  
	  foreach($rs_buscar as $row_rs_buscar)
	  { 	
	  	$i++; 
		if($row_rs_buscar['Cop_Est']=='I')
		  { $rojo='#FF0000'; $anulada++; }else{$rojo='';}		
		
		/**
		*	Consulta si una compra posee una Liquidacion con vales de caja chica y que este apta para su modificacion
		*/
		$row_rs_recibo = $obBD_con1->getRowConsulta(1046, $row_rs_buscar['Cop_Cod'], $obBD_conexion);
		$tot_rs_recibo=$row_rs_recibo['Cop_Cod'] > 0? 1 : 0;
		
		/**
		* Consultar las fecha de comprobante 
		*/ 
		$row_rs_comprobante_compra=$obBD_con1->getRowConsulta(345, $row_rs_buscar['Cop_Cod'], $obBD_conexion);
		/**
		* consulto si la factura de compra ya tiene pagos realizados 
		*/
		$row_rs_existe_pagos=$obBD_con1->getArrayConsulta(357, $row_rs_comprobante_compra['Com_Cod'], $obBD_conexion);
		 if(count($row_rs_existe_pagos)>0)
		 {
			 $rojo = "#9CB8CF";
		 }
		?>
	   <form method="post" name="pasar" action="<?Php echo $_SERVER['PHP_SELF']; ?>">
	     <tr <? //echo focus_row("resaltar_text", "resaltar_back", "undo_resaltar_text", "Fondo");?>>
	       <td width="5%" align="center">
	         <FONT COLOR="<? echo $rojo;?>"><?Php  $Cop_Cod_Int=$row_rs_buscar['Cop_Cod']; echo $Cop_Cod_Int; ?></FONT>
	         </td>
	       <td width="6%" align="center"><FONT COLOR="<? echo $rojo;?>">
	         <?Php 
		/**
		* Consultar si la factura se registro de forma autom�tica y tiene un comprobante contable
		*/
		$rs_compra_manual_automatica=$obBD_con1->getRowConsulta(369, $row_rs_comprobante_compra['Com_Cod'], $obBD_conexion);

		if(count($rs_compra_manual_automatica)>0)
			echo "Si";
		else
			echo "No";																
		?>
	         </FONT></td>
	       <td width="13%" align="center"><font color="<? echo $rojo;?>"><?Php echo $row_rs_buscar['Tic_Des']; ?></font></td>
	       <td width="13%" align="center">
	         <FONT COLOR="<? echo $rojo;?>"><?Php  $Num_Fac=$row_rs_buscar['Cop_Num']; echo $Num_Fac; ?></FONT>
	         </td>
	       <td width="8%" align="center">
	         <FONT COLOR="<? echo $rojo;?>"><?Php  $Fec_Com=$row_rs_buscar['Cop_Fec']; echo $Fec_Com; ?></FONT>
	         </td>	
	       <td align="center">
	         <FONT COLOR="<? echo $rojo;?>"><div align="left"><?Php echo marcar_cadena($txt_busqueda,$row_rs_buscar['Prs_Ape']." ".$row_rs_buscar['Prs_Nom'], '#FFFF00', 1); ?></div></FONT>
	         </td>
	       <td width="2%" align="center"><button type="button" class="btn btn-info btn-mini" title="Detalle del registro" onClick="Muestra_Aparecer(); ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_info=1&amp;com_codigo=<? echo $row_rs_buscar['Cop_Cod'];?>&amp;Ses_Emp_Cod=<? echo $$Ses_Emp_Cod;?>','mostrar')"><i class="icon-info-sign icon-white"></i></button></td>
  <td width="5%" align="center">
    <?Php 
	$num_row_rs_existe_pagos=0;
	if(count($row_rs_existe_pagos)==0)
	{
	    if ($tot_rs_recibo == 0 || $row_rs_recibo['Liq_Est']=='L' ) 
	    {	
		if ($row_rs_buscar['Cop_Est'] == 'A') 
		{ ?>
    <button type="button" class="btn btn-success btn-mini" title="Elegir" onclick="this.form.submit()">
      <i class=" icon-arrow-right icon-white"></i>
      </button>	
    <input type="hidden" name="hdd_mod" id="hdd_mod" value="1">
    <input type="hidden" name="cmb_mes" id="cmb_mes" value="<?Php echo $cmb_mes; ?>"  >
    <input name="codigo" id="codigo" type="hidden" value="<?Php echo $row_rs_buscar['Cop_Cod'];?>">
    <input name="Prv_Cod" id="Prv_Cod" type="hidden" value="<?Php echo $row_rs_buscar['Prv_Cod'];?>">
    <input name="volver_busqueda" id="volver_busqueda" type="hidden" value="<?Php echo $txt_busqueda;?>">
    <input name="volver_op" id="volver_op" type="hidden" value="<?Php echo $op_opciones;?>">						   		
    <input name="hdd_Pec_Cod" id="hdd_Pec_Cod" type="hidden" value="">
    <input name="Pec_Cod" id="Pec_Cod" type="hidden" value="<?Php  echo $Pec_Cod; ?>">
    <input name="Com_Pec_Cod" id="Com_Pec_Cod" type="hidden" value="<?Php  echo $Pec_Cod; ?>">
    <?Php 
		}
		else 
		{ echo "&nbsp;"; } 
	   }else{
	      ?><img src="../../mascaras/model1/imagenes/32x32/encrypted.png" title="Posee Vale de Caja Chica #<? echo $row_rs_recibo['Rcb_Num'] ?> con estado LIQUIDADO" width="22" height="22"><?  
	   } // fin del if ($tot_rs_recibo != 0)
	}
	else
	{ 
		/* Contador */ $existe_pagos++; 
	?>
    <button type="button" class="btn btn-success btn-mini" title="Elegir" onclick="this.form.submit()">
      <i class=" icon-arrow-right icon-white"></i>
      </button>	
    <?php 
	} ?>	
    </td>
	       </tr>
	     </form>
	   <script language="javascript">
		ShowHide('detalle[<?Php echo $i; ?>]');
		ShowHide('menos[<?Php echo $i; ?>]');		 
		</script>	
	  <?Php } //while ($row_rs_buscar = $obBD_con1->fetch_assoc($rs_buscar));
	  }
		else { ?>
	   <tr>
	     <td align="center">&nbsp;</td>
	     <td align="center">&nbsp;</td>
	     <td align="center">&nbsp;</td>
	     <td align="center">&nbsp;</td>
	     <td align="center">&nbsp;</td>
	     <td align="center"><?Php echo error_alerta("No hay resultados que mostrar", 1);?></td>
	     <td align="center">&nbsp;</td>
	     <td align="center">&nbsp;</td>
        </tr>
		<?Php } ?>
        </table>   
</FIELDSET>
<?Php echo barra_estado($total_rs_buscar);  ?>
<br>
<?Php 
/* Si existe en la b�queda factura(s) con pagos realizados muestra la siguiente leyenda */
if($existe_pagos>0){ /* inicio if($existe_pagos>0){  */ ?>
<table width="371" cellpadding="0" cellspacing="0">
     <tr>
       <td><fieldset>
         <legend>
           <label class="Titulos2">Leyenda:</label>
         </legend>
         <table border="1" cellpadding="0" cellspacing="0">
           <tr>
             <td width="30" bgcolor="#FFFFFF" align="center"><img src="../../mascaras/model1/imagenes/32x32/dinero.png" width="22" height="22"></td>
             
             <td width="69" bgcolor="#9CB8CF">&nbsp;</td>
             <td width="229" class="Cuerpo_ajax" align="center"><strong>La compra mantiene pagos vigentes </strong></td>
           </tr>
         </table>
       </fieldset></td>
     </tr>
   </table>
 <?Php } /* fin if($existe_pagos>0){   */ ?>
 <?Php
	}
	?>
  <form action="<?Php echo $_SERVER['PHP_SELF']; ?>" method="post" name="form2">
   <?Php /* creo el objeto para evitar el reenvio del formulario */
		 $thisPost->startPost();   ?>
  <?Php 
 if ($codigo > 0 && !(isset($hdd_save)))
 	{ 	/**
		*  En esta consulta debe botar un solo registro ya en un a�o contable normalmente se utiliza un plan de cuentas 
		*/
		$row_rs_cuenta_manual = $obBD_con1->getRowConsulta(189,$Pec_Cod,$obBD_conexion);		
		$Pla_Cod = $row_rs_cuenta_manual['Pla_Cod'];		
		
		/**
		*  Obtengo el c�digo de la compra a buscar 
		*/	
		$Cop_Bus=$rs_proveed[0]['Cop_Cod'];
		
		/**
		*  consulto el codigo de la retencion a modificar 
		*/
		$row_rs_retencion_modificar=$obBD_con1->getRowConsulta(718,$Cop_Bus,$obBD_conexion);		
		$num_row_rs_retencion_modificar=$row_rs_retencion_modificar['Ret_Cod'] > 0? 1 : 0;			
	?>
	<input name="Pec_Cod" id="Pec_Cod" type="hidden" value="<?Php  echo $Pec_Cod; ?>">
	<input name="Pec_Ann" id="Pec_Ann" type="hidden" value="<?php echo $row_rs_periodo['Ann']; ?>">	
	<input name="Pec_Fei" id="Pec_Fei" type="hidden" value="<?php echo $row_rs_periodo['Pec_Fei']; ?>">
	<input name="Pec_Fef" id="Pec_Fef" type="hidden" value="<?php echo $row_rs_periodo['Pec_Fef']; ?>">	
	<input name="Pec_Ann" id="Pec_Ann" type="hidden" value="<?php echo $row_rs_periodo['Ann']; ?>">	
	<input name="hdd_Pec_Cod" id="hdd_Pec_Cod" type="hidden" value="">
<FIELDSET>
<LEGEND>
<label class="Titulos2">Datos del Proveedor </label>
</LEGEND>
<table width="85%" border="0">
  <tr>
    <td width="965"></td>
  </tr>
</table>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td width="15%" class="Etiqueta1" span>C&eacute;dula: </td>
    <td class="LetraNegra">&nbsp;<?Php echo $rs_proveed[0]['Prs_Ced'] ?></td>
    </tr>
  <tr>
    <td class="Etiqueta1" span>Proveedor: </td>
    <td class="LetraNegra">&nbsp;<?Php echo $rs_proveed[0]['Prs_Nom']." ".$rs_proveed[0]['Prs_Ape']."&nbsp"; ?>
      <input type="hidden" name="Prv_Cod" value="<?Php echo $Prv_Cod; ?>" ></td>
    </tr>
    <td class="Etiqueta1">Direcci&oacute;n: </td>
    <td class="LetraNegra">&nbsp;<?php echo $rs_proveed[0]['Prs_Dir']?></td>
    </tr>
</table>
</FIELDSET>
		<FIELDSET>
		<LEGEND>
		<label class="Titulos2">Datos de la Factura  </label>
		</LEGEND>
		 <FIELDSET>
		<LEGEND>
		<label class="Titulos2"> Generales </label>
		</LEGEND>
		  <table width="100%" border="0" cellpadding="0" cellspacing="0">		  
            <tr>
              <td height="24" class="Etiqueta1"><span class="Asterisco">* </span>Tipo de Documento:</td>
              <td colspan="3"><span class="LetraNegra">
			  <?Php 
			  /* Envia el par�metro SQL,  */
			  $sql = "AND Cop_Num<>";
			  //echo $sql;
			 
			   ?>
                <select name="Tic_Cod" id="Tic_Cod" onChange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_con_numcom=1&Prv_Cod=' + document.getElementById('Prv_Cod').value +'&Cop_Bus=<?Php echo $rs_proveed[0]['Cop_Num'];  ?>&Cop_Num=' + document.getElementById('Cop_Num').value +'&Tic_Cod=' + this.value + '&Ins_Mod=<?Php echo $sql; ?>','div_con_num_com'); "  >
                  <option ></option>
                  <?Php foreach($rs_tip_compr as $row_rs_tip_compr){ ?>
                  <option <?Php if($rs_proveed[0]['Tic_Cod'] == $row_rs_tip_compr['Tic_Cod']){echo "selected";}?>  value="<?php echo $row_rs_tip_compr['Tic_Cod']?>"><?php echo $row_rs_tip_compr['Tic_Des'];?></option>
                  <?php
				} //while ($row_rs_tip_compr = $obBD_con1->fetch_assoc($rs_tip_compr));
		?>
                </select>
              </span></td>
            </tr>
            <tr>
              <td height="24" class="Etiqueta1"><span class="Asterisco">* </span>Tipo de Sustento tributario  :</td>
              <td colspan="3"><span class="LetraNegra">
                <select name="Tri_Cod" id="Tri_Cod">
                  <?Php foreach($rs_sustento as $row_rs_sustento){ ?>
                  <option <?Php if($rs_proveed[0]['Tri_Cod'] == $row_rs_sustento['Tri_Cod']){?> selected <?Php }?>  value="<?php echo $row_rs_sustento['Tri_Cod']?>"><?php echo $row_rs_sustento['Tri_Sri'].' - '.$row_rs_sustento['Tri_Des'];?></option>
                  <?php
				} //while ($row_rs_sustento = $obBD_con1->fetch_assoc($rs_sustento));
		?>
                </select>
              </span></td>
            </tr>
            <tr>
              <td width="15%" height="24" class="Etiqueta1"><span class="Asterisco">* </span>N&ordm;. Documento :</td>
            <td><span class="LetraNegra">
              <input name="Cop_Num" type="text" id="Cop_Num" size="17" maxlength="17" value="<?Php echo $rs_proveed[0]['Cop_Num'] ?>" 
			  onblur="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_con_numcom=1&Prv_Cod=' + document.getElementById('Prv_Cod').value +'&Tic_Cod=' + document.getElementById('Tic_Cod').value +'&Cop_Bus=<?Php echo $rs_proveed[0]['Cop_Num'];  ?>&Cop_Num=' + this.value+ '&Ins_Mod=<?Php echo $sql; ?>','div_con_num_com');">
              <script language="javascript">
			  //document.getElementById("Cop_Num").value = '333-333-333333333';
			  </script>
              </span><span id="div_con_num_com"></span>
			      <input type="hidden" name="Vnd_Cod" id="Vnd_Cod" value="<?Php echo $row_rs_vendedor['Vnd_Cod']; ?>">
			  </td>
            <td class="Etiqueta1"><span class="Asterisco">*</span> Fecha de emisi&oacute;n:</td>
            <td><input name="Cop_Fec" type="text" id="Cop_Fec" value="<?Php echo $rs_proveed[0]['Cop_Fec']; ?>" size="10">
              <span class="LetraNegra"><img src="../../mascaras/model1/imagenes/32x32/info.gif"  title="Fecha de la emisi&oacute;n del documento por el Proveedor" /></span>              <script type="text/javascript">
		    Calendar.setup({
        	inputField     :    "Cop_Fec",     // id of the input field
		    ifFormat       :    "%Y-%m-%d",      // format of the input field
	        button         :    "calendario",  // trigger for the calendar (button ID)
	        align          :    "Bl",           // alignment (defaults to "Bl")
    	    singleClick    :    true,
			step           :    1
    		});
		        </script></td>
            </tr>
            <tr>
              <td height="24" class="Etiqueta1"><span class="Asterisco">*</span>Autorizaci&oacute;n</td>
            <td width="30%"><span class="LetraNegra">
              <input name="Cop_Aut" type="text" id="Cop_Aut" size="30" maxlength="40" value="<?Php echo $rs_proveed[0]['Cop_Aut'] ?>" onBlur="if (document.getElementById('Cop_Aut').value!=''){ numerico(this); minimo(this, 10)}">
            </span></td>
              <td width="12%" class="Etiqueta1"><span class="Asterisco">*</span> Fecha de impresi&oacute;n:</td>
              <td width="43%" class="LetraNegra"><span class="LetraNegra">
              <input name="Cop_Imp" type="text" id="Cop_Imp" value="<?Php echo $rs_proveed[0]['Cop_Imf'] ?>" size="10">
              <img src="../../mascaras/model1/imagenes/32x32/info.gif" title="Fecha de creaci�n del documento en la imprenta" alt="Ver calendario" name="calendario2" border="0" align="absmiddle" id="calendario2">
              <script type="text/javascript">
		    Calendar.setup({
        	inputField     :    "Cop_Imp",     // id of the input field
		    ifFormat       :    "%Y-%m-%d",      // format of the input field
	        button         :    "calendario2",  // trigger for the calendar (button ID)
	        align          :    "Bl",           // alignment (defaults to "Bl")
    	    singleClick    :    true,
			step           :    1
    		});
		      </script>
            </span></td>
            </tr>
            <tr>
              <td height="24" class="Etiqueta1"><span class="Asterisco">*</span> Ciudad:</td>
            <td><span class="LetraNegra">
            <select name="Ciu_Cod" id="Ciu_Cod">
              <?Php foreach($rs_ciudad as $row_rs_ciudad){ ?>
              <option value="<?php echo $row_rs_ciudad['Ciu_Cod']?>"  
				<?Php  if($rs_proveed[0]['Ciu_Cod']==$row_rs_ciudad['Ciu_Cod']){ ?> selected="selected" <?Php }  ?>   ><?php echo $row_rs_ciudad['Ciu_Des']?></option>
              <?php
			} //while ($row_rs_ciudad = $obBD_con1->fetch_assoc($rs_ciudad));
		?>
            </select>
            </span></td>
              <td class="Etiqueta1"><span class="Asterisco">*</span> Caducidad: </td>
              <td class="LetraNegra"><input name="Cop_Cad" type="text" id="Cop_Cad" value="<?Php echo $rs_proveed[0]['Cop_Cad'] ?>" size="10">
                <script type="text/javascript">
		    Calendar.setup({
        	inputField     :    "Cop_Cad",     // id of the input field
		    ifFormat       :    "%Y-%m-%d",      // format of the input field
	        button         :    "calendario3",  // trigger for the calendar (button ID)
	        align          :    "Bl",           // alignment (defaults to "Bl")
    	    singleClick    :    true,
			step           :    1
    		});
		        </script><img src="../../mascaras/model1/imagenes/32x32/info.gif" title="Fecha de caducidad del documento seg�n el SRI" alt="Ver calendario" name="calendario2" border="0" align="absmiddle" id="calendario6" /></td>
            </tr>
			    <tr>
  <td width="15%" height="26" class="Etiqueta1">Observaci&oacute;n:</td>
  <td rowspan="2"><textarea name="Cop_Obs" cols="50" id="Cop_Obs" style="text-transform:uppercase"><?Php echo $rs_proveed[0]['Cop_Obs'] ?></textarea></td>
  <td class="Etiqueta1"><span class="Asterisco">*</span> Fecha Compr.:</td>
  <td>
  <?Php
  /* Consultar las fecha de comprobante */ 
  	$row_rs_comprobante_compra=$obBD_con1->getRowConsulta(345,$rs_proveed[0]['Cop_Cod'],$obBD_conexion);
	//$row_rs_comprobante_compra = $obBD_con1->registros();
  ?>
  <input name="Com_Fec" type="text" id="Com_Fec"  size="10" onKeyUp="mascara(this,'-',patron,true)" value="<?Php echo $row_rs_comprobante_compra['Com_Fec']; ?>" onBlur="validar_fecha2(this)">    
  	   <script type="text/javascript">
		    Calendar.setup({
        	inputField     :    "Com_Fec",     // id of the input field
		    ifFormat       :    "%Y-%m-%d",      // format of the input field
	        button         :    "calendario5",  // trigger for the calendar (button ID)
	        align          :    "Bl",           // alignment (defaults to "Bl")
    	    singleClick    :    true,
			step           :    1
    		});
		</script>
        <span class="LetraNegra">
  <img src="../../mascaras/model1/imagenes/32x32/info.gif" title="Fecha del comprobante contable de egreso o diario" alt="Ver calendario" name="calendario2" border="0" align="absmiddle" id="calendario3" />
  		</span>	
        <input name="Hdd_Com_Fec" type="hidden" id="Hdd_Com_Fec" value="<?Php echo $row_rs_comprobante_compra['Com_Fec'];  ?>">
		<input name="Com_Num" type="hidden" id="Com_Num" value="<?Php echo $row_rs_comprobante_compra['Com_Num']; ?>">
        </td>
		    </tr>
			
            <tr>
              <td width="15%" class="Etiqueta1">&nbsp;</td>
<td height="37">&nbsp;</td>
<td height="37">&nbsp;</td>
            </tr>
         </table>
     <input name="Cop_Cod" type="hidden" id="Cop_Cod" value="<?php echo $rs_proveed[0]['Cop_Cod']; ?>">
		  <input name="codigo" type="hidden" id="codigo" value="<?php echo $rs_proveed[0]['Prv_Cod']; ?>">
	    <input type="hidden" name="Pla_Cod" id="Pla_Cod" value="<?Php echo $Pla_Cod; ?>">
		</FIELDSET>
			
<FIELDSET>
<LEGEND>
<label class="Titulos2">Detalle de la Factura </label>
</LEGEND>
  <input name="Cop_Exi_Ret" type="hidden" value="<?Php echo $rs_proveed[0]['Cop_Cod']; ?>">
  <table width="873" border="0" cellpadding="0" cellspacing="0">
    <tbody id="c_contenido">
		 <tr>
        <td width="34" class="Cabecera1">Cant.</td>
        <td width="175" class="Cabecera1">Descripci&oacute;n</td>
        <td width="54" class="Cabecera1">P. Unitario</td>
        <td width="50" class="Cabecera1"><div align="center">Importe</div></td>
        <td width="34" class="Cabecera1">Desc.</td>
        <td width="21" class="Cabecera1">IVA</td>
        <td width="109" class="Cabecera1">ICE</td>
        <td width="120" class="Cabecera1">Adq.</td>
        <td width="120" class="Cabecera1">Cuenta</td>
        <td width="120" class="Cabecera1">&nbsp;</td>
        <td width="120" class="Cabecera1">Descripci&oacute;n</td>
        <td width="120" class="Cabecera1">Renta</td>
        <td width="120" class="Cabecera1">&nbsp;&nbsp;&nbsp;</td>
        <td width="120" class="Cabecera1">&nbsp;&nbsp;</td>
        <td width="120" class="Cabecera1">I.V.A.</td>
        <td width="120" class="Cabecera1">&nbsp;&nbsp;</td>
        <td width="120" class="Cabecera1">&nbsp;&nbsp;</td>
        <td width="25" class="Cabecera1">&nbsp;</td>
        <td width="75" class="Cabecera1"></td>
        <td width="131"></td>
      </tr>
<?Php 	/* % de Descuento total */
        $Cop_Des_Total=$rs_proveed[0]['Cop_Des'];		
		$base_renta=0;
		$base_iva=0;
		/* inicio el contador cuenta rentas */
		$cuenta_renta=0;
		foreach($rs_proveed as $row_rs_proveed){
			$fila++;
			/* Iva Front*/
			$rs_iva_front=$obBD_con1->consulta(sentencias_comf(706, ''), $obBD_conexion->conexion);
			$row_rs_iva_front = $obBD_con1->registros();
			//$rs_iva_front = consultas_tes(203, '');
			//$row_rs_iva_front = mysqli_fetch_assoc($rs_iva_front);
			$c++;
	?>
		<input name="Item_Ide[<?Php echo $c; ?>]" id="Item_Ide[<?Php echo $c; ?>]" type="hidden" value="<?Php echo $row_rs_proveed['Cop_Int']; ?>">
      <tr>
        <td width="34" class="LetraNegra">
        <input name="datos[<?Php echo $fila; ?>,1]" type="text" id="datos[<?Php echo $fila; ?>,1]" value="<?Php echo $row_rs_proveed['Cop_Can']?>" size="4" maxlength="5" onBlur="numerico(this)" onKeyUp="cal_importe_ice_com(this, document.getElementById('datos[<?Php echo $fila; ?>,3]'), document.getElementById('datos[<?Php echo $fila; ?>,4]')); valor_renta_compra();" style="text-align:right" >        </td>
        
        <td width="175" class="LetraNegra">
  <input name="datos[<?Php echo $fila; ?>,2]" type="text" id="datos[<?Php echo $fila; ?>,2]" 
  value="<?Php echo $row_rs_proveed['Cop_Pro']?>" size="25" maxlength="45" class="LetraNegra" >        </td>
        <td width="53" align="right" class="LetraNegra">
  <input name="datos[<?Php echo $fila; ?>,3]" type="text" id="datos[<?Php echo $fila; ?>,3]" onBlur="numerico(this); valor_renta_compra();" 
  onKeyUp="cal_importe_ice_com(document.getElementById('datos[<?Php echo $fila; ?>,1]'), this, document.getElementById('datos[<?Php echo $fila; ?>,4]'));  valor_renta_compra();" 
  value="<?Php echo $row_rs_proveed['Cop_Pru']; ?>" size="8" maxlength="8"  style="text-align:right" ></td>
        <td width="50" align="right" class="LetraNegra">
        <input name="datos[<?Php echo $fila; ?>,4]" type="text" 
        id="datos[<?Php echo $fila; ?>,4]" value="<?Php echo formato_numero($row_rs_proveed['Cop_Imp'],2,1);?>" size="8" maxlength="8" readonly="true" style="text-align:right"  ></td>
        <td width="34" align="right" class="LetraNegra"><input name="datos[<?Php echo $fila; ?>,5]" type="text" id="datos[<?Php 
	  					echo $fila; ?>,5]" onBlur="numerico(this); valor_renta_compra();" 
	onKeyUp="cal_importe_ice_com(document.getElementById('datos[<?Php echo $fila; ?>,1]'), document.getElementById('datos[<?Php echo $fila; ?>,3]'), document.getElementById('datos[<?Php echo $fila; ?>,4]')); valor_renta_compra();" value="<?Php echo $row_rs_proveed['Cop_Dec']?>" size="2" maxlength="2"  readonly=""<?Php if(isset($ocul)){ /* inicio if(isset($ocul)){ */ if ($row_rs_proveed['Cop_Des'] != 0) { echo "readonly='true'"; } } /* fin if(isset($ocul)){ */ ?>></td>
        <td width="21" align="right" class="LetraNegra">
						
			<input name="datos[<?Php echo $fila; ?>,6]" id="datos[<?Php echo $fila; ?>,6]" value="<?Php echo $row_rs_proveed['Iva_Cod']; ?>" type="hidden">	
            <input name="temp" id="temp" value="<?Php echo $row_rs_proveed['Iva_Por']; ?>" style="text-align:right" size="1" readonly="false" type="text">
            
            
            		
			</td>
        <td width="109" align="right" class="LetraNegra">
			
<input name="datos[<?Php echo $fila; ?>,10]" id="datos[<?Php echo $fila; ?>,10]" value="<?Php echo $row_rs_proveed['Ice_Int']; ?>" size="5" maxlength="3" readonly="true" type="hidden">
 	<?Php 	
			$row_porciento=$obBD_con1->getRowConsulta(527,$row_rs_proveed['Cop_Int'], $obBD_conexion); 
			//$row_porciento=mysqli_fetch_assoc($rs_porciento_ice);
			
			/**
			*  consulta de los porcentajes del I.C.E. (Impuesto a los consumos especiales) 
			*/
	  		$rs_ice_por=$obBD_con1->getRowConsulta(707,'',$obBD_conexion);
	  		//$row_rs_ice = $obBD_con1->registros(); 	?>
	<select name="datos[<?Php echo $fila; ?>,9]" id="datos[<?Php echo $fila; ?>,9]"  >
	<option>-</option>
	<?Php  foreach($rs_ice_por as $row_rs_ice){ ?>
		<option value="<?Php echo $row_rs_ice['Ice_Int']; ?>" <?Php if($row_rs_ice['Ice_Int']==$row_porciento['Ice_Int']){ ?> selected="selected"  <?Php }  ?>   ><?Php echo $row_rs_ice['Ice_Por']; ?></option>
	<?Php  }//while($row_rs_ice=$obBD_con1->fetch_assoc($rs_ice_por));  ?>
	</select>
	
 </td>
        <td width="120" align="right" class="LetraNegra">
    
  <input size="5"   name="datos[<?Php echo $fila; ?>,16]" type="hidden"
 id="datos[<?Php echo $fila; ?>,16]" value="<? echo $row_rs_proveed['Adq_Cod']; ?>"   > 
 <input type="text"   name="datos[<?Php echo $fila; ?>,12]" id="datos[<?Php echo $fila; ?>,12]" size="1" readonly="readonly"  value="<? echo $row_rs_proveed['Adq_Cor']; ?>" />
 
 </td>
        <td width="120" align="left" class="LetraNegra">
		<?Php 
	/* Consulto la cuenta contable relacionada con el detalle de la compra */  
	$rs_cuenta_compra=$obBD_con1->consulta(sentencias_comf(343, $obBD_con1->parametros($row_rs_proveed['Cop_Int'].'*'.$row_rs_proveed['Cop_Cod'])), $obBD_conexion->conexion); 
	$row_rs_cuenta=$obBD_con1->registros();
	?>		
        <input name="datos[<?Php echo $fila; ?>,13]" type="text"  id="datos[<?Php echo $fila; ?>,13]" 
value="<?Php echo $row_rs_cuenta['Pld_Cdc']; ?>" size="5" onKeyUp="cargar_cuenta('<?Php echo $_SERVER['PHP_SELF']; ?>?Pec_Cod=<?Php echo $row_rs_proveed['Pec_Cod']; ?>&ajax_cuenta=',document.getElementById('datos[<?Php echo $fila; ?>,13]'),document.getElementById('datos[<?Php echo $fila; ?>,14]'),document.getElementById('datos[<?Php echo $fila; ?>,15]'));  valor_renta_compra();"   >
        </td>
        <td width="120" align="right" class="LetraNegra"><input name="datos[<?Php echo $fila; ?>,7]" type="hidden" id="datos[<?Php echo $fila; ?>,7]" 
value="<?Php echo $row_rs_proveed['Iva_Por']?>" size="3" maxlength="10" >
<input name="datos[<?Php echo $fila; ?>,15]" type="hidden" id="datos[<?Php echo $fila; ?>,15]" 
value="<?Php echo $row_rs_cuenta['Pld_Cod']?>" size="3" maxlength="10" >
		<?Php /*DS if(isset($ocul)){ /* inicio if(isset($ocul)){  */ ?>
        <input id="Btn_Buscta[<?Php echo $fila; ?>]" type="button" class="BotonEliminar" name="Btn_Buscta[<?Php echo $fila; ?>]" value="+" onClick="busca_cuenta_btn(form,this)">
		<?Php /* DS } /* fin if(isset($ocul)){  */  ?>
		</td>
        <td width="120" align="left" class="LetraNegra">
		<input type="hidden" name="datos[<?Php echo $fila; ?>,8]" id="datos[<?Php echo $fila; ?>,8]" value="<?Php echo $row_rs_proveed['Cop_Int']?>" >
		<input name="datos[<?Php echo $fila; ?>,14]" id="datos[<?Php echo $fila; ?>,14]" type="text" size="15" value="<?Php echo trim($row_rs_cuenta['Pld_Des']); ?>" readonly=""  >      </td>
       <td width="120" align="left" class="LetraNegra">
<?Php 
	/* Consulta los c�digos de retenci�n  */
	
		$row_rs_retencion_compra_renta=$obBD_con1->getRowConsulta(344,$row_rs_proveed['Cop_Int'].'*'.'R'.'*'.$row_rs_retencion_modificar['Ret_Cod'],$obBD_conexion);
		$num_row_rs_retencion_compra_renta=$row_rs_retencion_compra_renta['Ren_Cod'] > 0? 1 : 0;
		
		$porcentaje_renta=($row_rs_retencion_compra_renta['Ret_Bas']*$row_rs_retencion_compra_renta['Ren_Por'])/100;
		$base_renta=$base_renta+$porcentaje_renta;	
		$cuenta_renta=$cuenta_renta+$num_row_rs_retencion_compra_renta;
	
?>
		<input name="datos[<?Php echo $fila; ?>,17]" id="datos[<?Php echo $fila; ?>,17]" type="text" size="1" readonly=""  
		value="<?Php echo trim($row_rs_retencion_compra_renta['Ren_Sri']); ?>" onFocus="valor_renta_compra();"  ></td>
        <td width="120" align="left" class="LetraNegra">
		<?Php // if(isset($ocul)){ /* inicio if(isset($ocul)){  */ ?>
		<input name="Btn_RentaMas[<?Php echo $fila; ?>]" id="Btn_RentaMas[<?Php echo $fila; ?>]" type="button" class="BotonEliminar" value="+" onClick="busca_renta_btn(form,this)">
		<?Php //} /* fin if(isset($ocul)){  */  ?>
		</td>
        <td width="120" align="right" class="LetraNegra">
		<?Php  //if(isset($ocul)){ /* inicio if(isset($ocul)){  */ ?>
<input id="Btn_RentaMenos[<?Php echo $fila; ?>]" type="button" class="BotonEliminar" name="Btn_RentaMenos[<?Php echo $fila; ?>]" value="-" 
onClick="busca_renta_quita_btn(form,this)">
        <?Php //}/* fin if(isset($ocul)){  */  ?>
</td>
        <td width="120" align="left" class="LetraNegra">
		<?Php
		/* Consulta los datos de la retencion en caso que sea IVA */
		$row_rs_retencion_compra_iva=$obBD_con1->getRowConsulta(344,$row_rs_proveed['Cop_Int'].'*'.'I'.'*'.$row_rs_retencion_modificar['Ret_Cod'], $obBD_conexion);		
		$num_row_rs_retencion_compra_iva=$row_rs_retencion_compra_iva['Ren_Cod'] > 0? 1 : 0;				
		$cuenta_renta=$cuenta_renta+$num_row_rs_retencion_compra_iva;
		$cal_poriva=($row_rs_retencion_compra_iva['Ret_Bas']*$row_rs_retencion_compra_iva['Ren_Por'])/100;
		$base_iva=$base_iva+$cal_poriva;
		
		
		?>
        <input name="datos[<?Php echo $fila; ?>,18]" id="datos[<?Php echo $fila; ?>,18]" type="text" size="1" value="<?Php echo trim(
		$row_rs_retencion_compra_iva['Ren_Sri']); ?>" onFocus="valor_renta_compra();" readonly="" >
        <input name="datos[<?Php echo $fila; ?>,19]" id="datos[<?Php echo $fila; ?>,19]" type="hidden" size="3" value="<?Php echo 
		$row_rs_retencion_compra_renta['Ren_Cod']; ?>" >
        <input name="datos[<?Php echo $fila; ?>,20]" id="datos[<?Php echo $fila; ?>,20]" type="hidden" size="3" value="<?Php echo 
		$row_rs_retencion_compra_iva['Ren_Cod']; ?>" >
        <input name="datos[<?Php echo $fila; ?>,21]" id="datos[<?Php echo $fila; ?>,21]" type="hidden" size="3" value="<?Php echo 
		$row_rs_retencion_compra_renta['Ren_Por']; ?>" >
        <input name="datos[<?Php echo $fila; ?>,22]" id="datos[<?Php echo $fila; ?>,22]" type="hidden" size="3" value="<?Php echo 
		$row_rs_retencion_compra_iva['Ren_Por']; ?>" >
		
		</td>
        <td width="120" align="right" class="LetraNegra">
		<?Php // if(isset($ocul)){ /* inicio if(isset($ocul)){  */ ?>
<input id="Btn_IvaMas[<?Php echo $fila; ?>]" type="button" class="BotonEliminar" name="Btn_IvaMas[<?Php echo $fila; ?>]" value="+" onClick="busca_iva_btn(form,this)">
		<?Php // } /*  fin if(isset($ocul)){  */  ?>
</td>       <td width="120" align="right" class="LetraNegra">
		<?Php // if(isset($ocul)){ /* inicio if(isset($ocul)){  */ ?>
		<input id="Btn_IvaMenos[<?Php echo $fila; ?>]" type="button" class="BotonEliminar" name="Btn_IvaMenos[<?Php echo $fila; ?>]" value="-" onClick="busca_iva_quita_btn(form,this)">
		<?Php // }/* inicio if(isset($ocul)){  */ ?>
		</td><td width="25" align="right" class="LetraNegra">
		<?Php // if(isset($ocul)){ /* inicio if(isset($ocul)){  */ ?>
		<input id="quitar_fila" type="button" class="BotonEliminar" name="quitar_fila" value="X" onClick="quitar_fila_compra_ice(this); valor_renta_compra(); ">
		<?Php //} /* fin  if(isset($ocul)){ */ ?>
		</td><td width="75" align="right" class="LetraNegra">
          <input name="datos[<?Php echo $fila; ?>,8]" type="hidden" id="datos[<?Php echo $fila; ?>,8]" 
value="<?Php echo $row_rs_proveed['Cop_Int']?>" size="3" maxlength="10" ></td>
        <td width="131" align="right" class="LetraNegra"><input name="datos[<?Php echo $fila; ?>,23]" id="datos[<?Php echo $fila; ?>,23]" type="hidden" size="3" value="<?Php echo $row_rs_proveed['Pro_Cod']; ?>" >&nbsp;</td>
      </tr>
       <?Php 
	}//while ($row_rs_proveed = $obBD_con1->fetch_assoc($rs_proveed));?>
    <? 
	/*  Retorno los calculos de las facturas */	
	$cadena= $obBD_con1->calculosCompraIce($codigo, $obBD_conexion);
	$resultados = explode('*',$cadena);
	?>
    </tbody>
    <tr>
      <td class="LetraNegra">&nbsp;</td>
      <td colspan="2" class="Etiqueta1" align="right">SUBTOTAL:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
      <td align="right" class="LetraNegra"><input name="t_subtotal" type="text" align="right" id="t_subtotal" size="8" maxlength="8" readonly="true" value="<?Php echo formato_numero($resultados[0],2,1);  ?>" style="text-align:right" >      </td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
    </tr>
    <tr>
      <td class="LetraNegra">&nbsp;</td>
      <td colspan="2" class="Etiqueta1" align="right">TARIFA 0%:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
      <td align="right" class="LetraNegra"><input name="t_iva0" type="text" align="right" id="t_iva0" size="8" maxlength="8" readonly="true" value="<?Php echo formato_numero($resultados[1],2,1); ?>" style="text-align:right" >      </td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
    </tr>
    <tr>
      <td class="LetraNegra">&nbsp;</td>
      <td colspan="2" class="Etiqueta1" align="right">TARIFA 12%:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
      <td align="right" class="LetraNegra"><input name="t_iva12" type="text" align="right" id="t_iva12" size="8" maxlength="8" readonly="true" value="<?Php echo formato_numero($resultados[2],2,1); ?>" style="text-align:right" ></td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
    </tr>
    <tr>
      <td class="LetraNegra">&nbsp;</td>
      <td colspan="2" class="Etiqueta1" align="right">12% I.V.A.:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </td>
      <td  align="right" class="LetraNegra"><input name="t_iva" align="right" type="text" id="t_iva" value="<?Php echo formato_numero($resultados[3],2,1); ?>" size="8" style="text-align:right"></td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
    </tr>
    <tr>
      <td class="LetraNegra">&nbsp;</td>
      <td colspan="2" class="Etiqueta1" >I.C.E.:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
      <td align="right" class="LetraNegra"><input name="t_ice" align="right" type="text" id="t_ice" value="<?Php echo formato_numero($resultados[6],2,1); ?>" size="8" style="text-align:right"></td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
    </tr>
    <tr>
      <td class="LetraNegra">&nbsp;</td>
      <td colspan="2" class="LetraNegra" align="right"><span class="Etiqueta1">% DESCUENTO:
        <input name="activar1" type="checkbox" disabled="disabled" id="activar1"  onClick="validar_text_com()" value="checkbox" checked 
		<?php if ($Cop_Des_Total != 0) { echo "checked='checked'"; } ?>>
            <input name="Cop_Des" type="text" id="Cop_Des" size="2" maxlength="7" 
			value="<?Php echo $Cop_Des_Total; ?>" <?php if ($Cop_Des_Total == 0) { echo "readonly='true'"; } ?> onBlur="numerico(this)" onKeyUp="validar_text_com()">
      </span></td>
      <td align="right" class="LetraNegra"><input name="t_descuento" type="text" align="right" id="t_descuento" size="8" maxlength="8" readonly="true" value="<?Php echo formato_numero($resultados[4],2,1); ?>" style="text-align:right" ></td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
      	<td class="LetraNegra">&nbsp;</td>
      	<td class="LetraNegra">&nbsp;</td>
      	<td class="LetraNegra">&nbsp;</td>
      	<td class="LetraNegra">&nbsp;</td>
      	<td class="LetraNegra">&nbsp;</td>
      	<td class="LetraNegra">&nbsp;</td>
      	<td class="LetraNegra">&nbsp;</td>
      	<td class="LetraNegra">&nbsp;</td>
      	<td class="LetraNegra">&nbsp;</td>
      	<td class="LetraNegra">&nbsp;</td>
      	<td class="LetraNegra">&nbsp;</td>
    </tr>
	<tr class="Cabecera1" height="35">
		<td height="24" class="LetraNegra">&nbsp;</td>
      	<td colspan="2" class="Etiqueta1" align="right">TOTAL:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </td>
      <td align="right" class="LetraNegra"><input name="t_rubros" type="text" align="right" id="t_rubros" size="8" maxlength="8" readonly="true" 
	  value="<?php echo round($resultados[5],2); ?>" style="text-align:right"></td><td class="LetraNegra">&nbsp;</td><td class="LetraNegra"><input id="nfilas" name="nfilas" type="hidden" value="<? echo $fila; ?>"></td><td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td><td class="LetraNegra">&nbsp;</td><td class="LetraNegra">&nbsp;</td><td class="LetraNegra">&nbsp;</td><td class="LetraNegra">&nbsp;</td><td class="LetraNegra">&nbsp;</td>   <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
      <td class="LetraNegra">&nbsp;</td>
    </tr>
  </table>
  </fieldset>

	<?Php //  if(isset($ocul)){ /* inicio if(isset($ocul)){  */ ?>
		<table width="136" border="0" cellpadding="1" cellspacing="5">
	  <tr>
	    <td align="left">
		<button type="button" class="btn btn-success fileinput-button" title="Agregar producto" onClick=" multiple_capa('<? $_SERVER['PHP_SELF']; ?>',600,300,'cont_fon_prod','cont_cua_prod','Busqueda de Producto','cont_tit_prod')
        
        document.getElementById('Tbl_Cuentas').className = 'oculta'; document.getElementById('Tbl_Rentas').className = 'oculta'; 
        
        
        " name="button" id="button"><i class="icon-plus icon-white"></i> <span>Producto</span> </button>
		
<div id="cont_fon_prod" class="bgtransparent" style="display:none">
</div>
<div id="cont_cua_prod"  class="bgmodal"   style="display:none" >
<div id="cont_tit_prod"></div>
  <?Php  include('../COMPONENTES/tesConCtaCompras.php');?>	
</div>
	    </tr>
	  </table>
	<?Php //} /* fin if(isset($ocul)){ */ ?>
	<table width="600" border="0" cellspacing="0" cellpadding="0">
     <tr>
        <td>
		<?Php 
		 /* C= buscador con cargado en combos */
		$tipo_busc = 'F'; 
		$Capa = 'busqueda_f';
		$Nombre_Buscador = 'buscta';//Cuadro de texto
  		$Nombre_Opciones = 'op_opciones';//Option		
		?>	 
		  <div id="cont_fon_cta" class="bgtransparent" style="display:none">
</div>

<div id="cont_cua_cta"  class="bgmodal"   style="display:none" >
 <div id="cont_cua_cta_titu"></div>
		 <?Php include('../../componentes/FRONT/comConBuscarcta.php'); ?>
        </div></td>
       </tr>
     </table>  
	 <table width="600" border="0" cellspacing="0" cellpadding="0">
       <tr>
         <td>
		 <div id="cont_fon_iva" class="bgtransparent" style="display:none">
        </div>        
        <div id="cont_cua_iva"  class="bgmodal"   style="display:none" >
        <div id="cont_cua_iva_titu"></div>
                 <?Php 
                 /* Codigo del periodo contable */
                 $Com_Pec_Cod = $Pec_Cod;
               include('../COMPONENTES/tesComBusRentaIva.php'); ?>
        </div>
        
        </td>
       </tr>
     </table>
	 <script language="javascript" type="text/javascript">
	    ShowHide('Tbl_Cuentas'); 
		ShowHide('Tbl_Rentas'); 
	 </script> 
		</FIELDSET>
<table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
	<td width="50%" valign="top">
<?Php 
	/** 
	* cargar la forma de pago
	*/ 
	$rs_pago = $obBD_con1->consulta(sentencias_comf(16, ''), $obBD_conexion->conexion);
	$row_rs_pago = $obBD_con1->registros();
	/* consulto la forma de pado del comprobante de compra de acuerdo al c�digo del comprobante */ 
	//echo $Cop_Bus;
	$rs_comprobante_forma=$obBD_con1->consulta(sentencias_comf(349, $obBD_con1->parametros($Cop_Bus)), $obBD_conexion->conexion);
	$row_rs_comprobante_forma=$obBD_con1->registros();
	/**
	* consulto los datos de ccxpp en base al c�digo del comprobante
	*/
	$rs_cuenta_pagar=$obBD_con1->consulta(sentencias_comf(350, $obBD_con1->parametros($row_rs_comprobante_forma['Com_Cod'])), $obBD_conexion->conexion);   
	$row_rs_cuenta_pagar=$obBD_con1->registros();	
?>
<FIELDSET>
<LEGEND>
<label class="Titulos2"> Formas de Pago </label>
</LEGEND>
<table width="100%" border="0" cellpadding="2" cellspacing="2" >
  <tr>
    <td colspan="3" valign="top" class="Etiqueta1"><div id="pagoSri">
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
              <option value="<?Php echo $row_rs_TipoPago['Tpc_Cod'];?>" <? if($rs_proveed[0]['Tpc_Cod']==$row_rs_TipoPago['Tpc_Cod']){echo 'selected';}?>><?Php echo $row_rs_TipoPago['Tpc_Sri']."  -  ".$row_rs_TipoPago['Tpc_Des'];?></option>
              <? }?>
            </select>
            </td>
        </tr>
      </table>
    </div>
    <? if(round($resultados[5],2)<100){?>	
		<script language="javascript">document.getElementById('pagoSri').style.display='none'; </script>
    <? }?>    
    </td>
    </tr>
  <tr>
    <td width="12%" valign="top" class="Etiqueta1"><span class="Asterisco">* </span>Forma:</td>
    <td width="16%" valign="top" class="LetraNegra">
	
  	<select name="For_Cod_2" id="For_Cod_2"  onChange="compras_cheques();" disabled="disabled" >
      <?Php do { ?>
      <option value="<?Php echo $row_rs_pago['For_Cod'];  ?>" 
	  <?Php if($row_rs_comprobante_forma['For_Cod']==$row_rs_pago['For_Cod']){ ?> selected="selected" <?Php } ?>><?Php echo $row_rs_pago['For_Des'];   ?></option>
      <?Php } while($row_rs_pago=$obBD_con1->fetch_assoc($rs_pago));  ?>
    </select>
    <input name="For_Cod" id="For_Cod" value="<?Php echo $row_rs_comprobante_forma['For_Cod']; ?>" type="hidden" >
	<input type="hidden" id="hdd_TipoSri" name="hdd_TipoSri" value="1" /></td> 
	<td width="72%" class="LetraNegra">
	<?Php if($row_rs_comprobante_forma['For_Cod']==2){ /* inicio if($For_Cod==1){  */ ?>
	 <?Php	/* Consulto el c�digo del detalle de la cuenta perteneciente al comprobante de compra */
				$rs_comprobante_asiento=$obBD_con1->consulta(sentencias_comf(362, $obBD_con1->parametros($Cop_Bus)), $obBD_conexion->conexion); 	
				$row_rs_comprobante_asiento=$obBD_con1->registros();
				
				/* Determina cuenta unica del proveedor en el plan de cuentas */
				$rs_ccpp_prove= $obBD_con1->consulta(sentencias_comf(253, $obBD_con1->parametros($Pla_Cod)), $obBD_conexion->conexion); 		
				$row_rs_ccpp_prove = $obBD_con1->registros();
				$total_rs_ccpp_prove = $obBD_con1->numregistros();     
				
  	?>			
	<table width="100%" border="0" cellpadding="0" cellspacing="0" id="Tbl_Cpp_Ven">
	   <tr>
        <td valign="top" class="Etiqueta1"><span class="Asterisco">*</span> Cuenta deudora:&nbsp;</td>
        <td valign="top">
		
		<select name="ccpp_prove_2" id="ccpp_prove_2" disabled="disabled" >          
          <?Php do{ ?>
          <option value="<?Php echo $row_rs_ccpp_prove['Pld_Cod'];?>" <?Php if($row_rs_comprobante_asiento['Pld_Cod']==$row_rs_ccpp_prove['Pld_Cod']){ ?> selected="selected" <?Php } ?>>
		  <?Php echo $row_rs_ccpp_prove['Pld_Des'];?></option>
          <?Php }while($row_rs_ccpp_prove=$obBD_con1->fetch_assoc($rs_ccpp_prove));?>
        </select>
		<input name="ccpp_prove" id="ccpp_prove" value="<?Php echo $row_rs_comprobante_asiento['Pld_Cod'];?>" type="hidden">
		</td>
      </tr>
	<?Php
	
	/* Si se encuentra en la tabla cuentas por pagar Proveedores Locales */
	if ($row_rs_comprobante_asiento['Ccp_Cxp'] == 'S'){?>
      <tr>
        <td width="36%" valign="top" class="Etiqueta1"><span class="Asterisco">*</span>
          <input type="hidden" name="Cpp_Cod" value="<?Php echo $row_rs_cuenta_pagar['Cpp_Cod']; ?>" >
           Fecha de vencimiento:&nbsp;</td>
        <td width="64%" valign="top">
          <input name="Cpp_Ven" type="text" id="Cpp_Ven" size="10" onKeyUp="mascara(this,'-',patron,true)" onBlur="validar_fecha2(this)"
		   value="<?Php echo $row_rs_cuenta_pagar['Cpp_Ven']; ?>"  >
        <?Php  if(isset($ocul)){ /* inicio if(isset($ocul)){  */ ?>  <img src="../../imagenes/calendario.jpg" alt="Ver calendario" style="cursor:pointer" name="calendario4" width= "25" height="17" border="0" align="absmiddle" id="calendario4">
          <script type="text/javascript">
		    Calendar.setup({
        	inputField     :    "Cpp_Ven",     // id of the input field
		    ifFormat       :    "%Y-%m-%d",      // format of the input field
	        button         :    "calendario4",  // trigger for the calendar (button ID)
	        align          :    "Bl",           // alignment (defaults to "Bl")
    	    singleClick    :    true,
			step           :    1
    		});
</script><?Php } /* fin inicio if(isset($ocul)){ */ ?></td></tr>
   
      <tr><td class="Etiqueta1">Observaci&oacute;n:&nbsp;</td><td>
<textarea name="Cpp_Obs"     cols="28" rows="3" id="Cpp_Obs" style="text-transform:uppercase"><?Php echo $row_rs_cuenta_pagar['Cpp_Obs']; ?></textarea></td></tr>
<?Php /* Fin si se encuentra en la tabla cuentas por pagar Proveedores Locales */ 
	} ?>
    </table>
	<?Php } /* fin if($For_Cod==1){*/ ?>	
	</td>
  </tr>
  <tr>
    <td colspan="3" valign="top" class="Etiqueta1"></td>
    </tr>
</table>
</FIELDSET>	</td>
	<td width="50%" valign="top">
<FIELDSET>
<LEGEND>
<label class="Titulos2">Datos de la retenci�n</label>
</LEGEND>	
<?Php 	
		/**
		*  Consultar la autorizaci�n 
		*/
		$rs_autorizacion=$obBD_con1->consulta(sentencias_comf(378, $obBD_con1->parametros($row_rs_vendedor['Pun_Cod'].'*'.'6')), $obBD_conexion->conexion);
		$row_rs_autorizacion=$obBD_con1->registros(); 
		
		/**
		* | 6 | c�digo interno de la retenci�n  
		*/
  	    $row_autorizacion_sri=$obBD_con1->getRowConsulta(517,$Ses_Prs_Cod.'*'.'6'.'*'.$Ses_Suc_Cod,$obBD_conexion);  				
		$num_autorizacion_sri=$row_autorizacion_sri['Vnd_Cod'] > 0? 1 : 0;		
		
  		if($num_autorizacion_sri>0) /* inicio if($num_autorizacion_sri>0){   */		
		{	
		    /**
			*  Consulto si ya existe un codigo generado en las retenciones basado en una autorizacion otorgada por el SRI 
			*/
		    $rs_existe_gencod=$obBD_con1->consulta(sentencias_comf(518, $obBD_con1->parametros($row_rs_autorizacion['Aut_Cod'])), $obBD_conexion->conexion);
		  	$num_existe_gencod=$obBD_con1->numregistros(); 
  	        
			/**
			*  Consulto el n�mero inicial del comprobante de retenci�n desde la autorizaci�n 
			*/
    		if($num_existe_gencod>0){ /* inicio if($num_existe_gencod>0){  */
	  			$rs_max_codigo=$obBD_con1->consulta(sentencias_comf(511, $obBD_con1->parametros($row_rs_autorizacion['Aut_Cod'])), $obBD_conexion->conexion);
			  	$row_max_codig=$obBD_con1->registros(); 
		  	    $num_rows_codi=$obBD_con1->numregistros(); 
				unset($Ret_Id_Man);
			    $Ret_Id_Man = ($row_max_codig['Ret_Ide'])+1;	
		    }else
	  		{  	unset($Ret_Id_Man);
		 		$Ret_Id_Man=$row_autorizacion_sri['Aut_Ini'];
			}/* fin if($num_existe_gencod>0){  */
		}/* fin inicio if($num_autorizacion_sri>0){ */	
		if ($num_row_rs_retencion_modificar!=0)
		{
			$autCodModificar = $row_rs_retencion_modificar['Aut_Cod'];	
		}else{
			$autCodModificar = $row_autorizacion_sri['Aut_Cod'];
		}
		?>
<?Php //if($num_row_rs_retencion_modificar>0){ /* inicio if($num_row_rs_retencion_modificar>0){  */  ?>
	<input type="hidden" name="Hdd_Reten_Modificar" id="Hdd_Reten_Modificar" value="<?Php echo $num_row_rs_retencion_modificar; ?>" >
<?Php //} /* if($num_row_rs_retencion_modificar>0){ */ ?>
<input type="hidden" name="Ret_Cod" id="Ret_Cod" value="<?Php echo $row_rs_retencion_modificar['Ret_Cod']; ?>" >
<?Php include('../COMPONENTES/tesComModNumRet.php');?>
<table width="100%" border="0" cellspacing="0" >
       <tr>
        <td width="27%" class="Etiqueta1" ><input name="Hdd_Ret" id="Hdd_Ret" type="hidden" value="N">
           Renta:&nbsp;</td>
        <td align="left" class="Etiqueta1"><div align="left">
  <input name="Ren_Ren" id="Ren_Ren" type="text"  size="5" maxlength="8"  align="right"  value="<?Php echo round($base_renta,3); ?>" style="text-align:right" readonly=""  >
  &nbsp;+&nbsp; I.V.A:
<input name="Rei_Iva" id="Rei_Iva" type="text" class="" size="5" maxlength="8"  value="<?Php echo round($base_iva,3); ?>"  style="text-align:right" readonly=""  >&nbsp;=&nbsp;  <?Php 
  $bases_ivas=round($base_renta,2)+round($base_iva,2); //$bases_ivas=$base_renta+$base_iva;
  ?>
  <input name="Riv_Tot" id="Riv_Tot" type="text" class="" size="5" maxlength="8" value="<?Php echo round($bases_ivas,4); ?>" style="text-align:right"  readonly="" >          
          (Valor Retenido) </div></td>
        </tr>
        <tr>
        <td class="Etiqueta1" ><input name="Com_Cod" type="hidden" value="<?Php echo $row_rs_comprobante_forma['Com_Cod']; ?>" >Valor a pagar:&nbsp;</td>
        <td align="" class="Etiqueta1"><div align="left">
<?Php  		/* valor a pagar */
			$valor_retenciones=round($bases_ivas,3);//Agregado round 2010-05-05 antes $valor_retenciones=round($base_renta,3)+round($base_iva,3);

	   		$valor_cheque=round($resultados[5],2)-$valor_retenciones; //Es necesario redondear $resultados[5] porque su valor se agrega decimales sin el programador requerirlo 
?><input name="Val_Pcc" id="Val_Pcc" type="text"  size="6" maxlength="8"  align="right" readonly="" value="<?Php echo round($valor_cheque,2); ?>" style="text-align:right"  >

        </div></td>
      </tr>
    </table>
	<?Php
	/* Consultar si la compra tiene retenciones modificadas */
	$rs_retencion_eliminado=$obBD_con1->consulta(sentencias_comf(382, $obBD_con1->parametros($Cop_Bus)), $obBD_conexion->conexion);
	$num_row_rs_retencion_eliminados=$obBD_con1->numregistros(); 
	if($num_row_rs_retencion_eliminados>0)
	{
	  echo error_alerta("La compra mantiene retenciones anuladas.",1);
	}
	?>
	</FIELDSET>
	</td>
  </tr>
</table>
<?Php
 /****Asigno For_Cod=1 */
  $For_Cod = $row_rs_comprobante_forma['For_Cod'];
 // $For_Cod = 1;
  /* Nombre del campo que */
  $Hdd_Valor = 'Val_Pcc';
  /* Nombre del campo de donde */
  $Hdd_Fecha = 'Com_Fec';
  
  if($cuenta_renta==0 && $num_row_rs_retencion_modificar>0 ) /* inicio if($cuenta_renta==0)  */
		{  		echo error_alerta("La compra no puede ser modificada por que se encuentra registrada con el formato anterior.",2);  		}	/* Fin if($cuenta_renta==0) */
?><table width="215" border="0" cellpadding="0" cellspacing="0" class="Azul">
 <tr>
 <td width="48%">
 
  <button  type="button" name="btn_atras" id="btn_atras" value="Enviar" class="btn btn-inverse fileinput-button" title="Atr&aacute;s"
  onClick="campos_hide(this.form, '<?Php echo "txt_busqueda*op_opciones*hdd_Pec_Cod*hdd_volver*cmb_mes"; ?>', 
  '<?Php echo $volver_busqueda.'*'.$volver_op.'*'.$hdd_Pec_Cod.'*'.''.'*'.$cmb_mes; ?>')"> <i class=" icon-arrow-left icon-white"></i>
               <span>&nbsp;&nbsp;Atr&aacute;s&nbsp;&nbsp;</span>
       		 </button></td>
 
 <td width="52%" height="23">
  
   <input name="hdd_save" type="hidden" id="hdd_save" value="insertar">   
<input name="confi_fact" type="hidden" id="confi_fact" value="<? echo $llevarContabilidad['Cof_Con']?>" />
<button name="btn_guardar" type="button" class="btn btn-primary start" id= "btn_guardar" 
   title= "Actualizar" onClick="if(document.getElementById('t_rubros').value<1000 ){validar_facturacion_compra(this.form);}else{if(document.getElementById('hdd_TipoSri').value!=''){validar_facturacion_compra(this.form);}else{alert('�Falta escoger Pago SRI!'); document.getElementById('TipoPag').focus();}}"  value="Actualizar" <?Php if($cuenta_renta==0 && $num_row_rs_retencion_modificar>0){ ?> disabled="disabled"  <?Php } ?> ><i class="icon-book icon-white"></i>
	          <span>Guardar</span>
	          </button>              													                
   </td>
 </tr>
 </table>
 <br />	
<?php }//Fin del if ($codigo > 0 && !(isset($hdd_save))) ?>	
 </form>
 <?Php
 }//Fin del else if (!isset($hdd_Pec_Cod))
 }/* fin inicio if($total_rs_vendedor>0) */
 else
 { ?><br>
 	 <?Php echo error_alerta("Ud. NO esta autorizado para emitir Comprobantes de Compra", 2);
 }//FIn del else   	if($total_rs_vendedor>0)
 ?>
  </table>
  <?Php
  /**
  *	If que restringe el guardado de ciertos DATOS cuando la empresa no lleva contabilidad
  */
  if($llevarContabilidad['Cof_Con']=='S'){
  	if($For_Cod==1){ ?>
		<?Php include('../COMPONENTES/tesComConCheque.php'); ?>	
<?Php } 
  }?>
<script type="text/javascript" src="../VALIDACIONES/fac_par_compras.js"></script>
<script type="text/javascript" src="../../Librerias/textbox/main.js"></script>  	
  <br />  
<div id="bgtransparent" class="bgtransparent" style="display:none" onClick="closeModal();"></div>
    <div id="bgmodal"  class="bgmodal" style="display:none" >
       <div id="ajax_modal">
        	 <div id="mostrar"></div>
       </div>
</div>
</div>
</BODY>
</HTML>
<?Php
/* Cierro las conexiones */
$obBD_con1->liberar();
$obBD_conexion->cerrar();
/* Fin cierro las conexion */
?>