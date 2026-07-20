<?php
	require_once ('../../auditoria/LOGICA/aud_log_auditoria.php');	
	require_once ("tes_sql_ccpp.php");
	/******************************************************/
/******************************************************/
/*   Clase para conexion a la capa de acceso a datos  */
/******************************************************/
/******************************************************/
class Class_Log_Conexion_Tes extends MysqlConexion{
}//Fin de clase Class_Log_Conexion
/******************************************************/
/******************************************************/
/******************************************************/
/******************************************************/
/*  Clase para los datos a la capa de acceso a datos  */
/******************************************************/
/******************************************************/
class Class_Log_Datos_Tes extends MysqlDatos{
/******************************************************/
/******************************************************/
	/**
	* Realiza una consulta en la base de datos -  STARDARD
	*
	* @param int $sen_sql numero de la sql
	* @param string $param cadena de valores para el filtrado de la busqueda
	* @param Class_Log_Conexion_Cli $obBD para realizar la conexcion correspondiente
	* @return result si existen datos de retorno
	*/
	function consultasobBD($sen_sql,$param, $obBD = null)
	{
		$Par_Sql= $this->parametros($param);
		return $this->consulta(sentencias_tes($sen_sql,$Par_Sql), $obBD->conexion);
	}
	/**
	* Realiza una consulta en la base de datos -  STARDARD
	*
	* @param int $sen_sql numero de la sql
	* @param string $param cadena de valores para el filtrado de la busqueda
	* @param Class_Log_Conexion_Cli $obBD para realizar la conexcion correspondiente
	* @return result si existen datos de retorno
	*/
	function operacionobBD($sen_sql,$param, $obBD = null)
	{
		$Par_Sql= $this->parametros($param);
		return $this->grabarv_registros(sentencias_tes($sen_sql,$Par_Sql), $obBD->conexion);
	}
	/* Consulta individual que genera un conexion por consulta */
	function consultas_tes($sen_sql,$paras)
	{
		$Par_Sql=explode('*',$paras);
		$obBD = new base_mysql;
		return $obBD->consulta(sentencias_tes($sen_sql,$Par_Sql));
	}
	/* Consulta utilizada dentro de los guardados con conexiones abiertas */
	function consultasv_tes($sen_sql,$paras,$conectar2)
	 {
		$Par_Sql=explode('*',$paras);
		$obBD = new base_mysql;//<-------No se debe permitir la creacion de nuevos objetos
		return $obBD->consultav(sentencias_tes($sen_sql,$Par_Sql),$conectar2);
	 }
	/**
	 * Ejecuta cualquier consulta a la base de datos -  STARDARD
	 * @param int $sen_sql numero de la sql
	 * @param string $param cadena de valores para el filtrado de la busqueda
	 * @param Class_Log_Conexion_Cli $obBD para realizar la conexcion correspondiente
	 * @return array $row fila de datos
	 */
	function getRowConsulta($sen_sql,$param,$obBD = null)
	{
		$result = $this->consultasobBD($sen_sql,$param,$obBD);
		$row =  $this->fetch_assoc($result);
		$this->free_result($result);
		return $row;
	}
	/**
	 * Ejecuta cualquier consulta a la base de datos -  STARDARD
	 * @param int $sen_sql numero de la sql
	 * @param string $param cadena de valores para el filtrado de la busqueda
	 * @param Class_Log_Conexion_pac $obBD para realizar la conexcion correspondiente
	 * @param Class_Log_Datos_Cli $obDT para la abtraccion de los datos
	 * @return array $array arreglo de datos asociados
	 */ 
	function getArrayConsulta($sen_sql,$param,$obBD = null)
	{
		$result = $this->consultasobBD($sen_sql,$param,$obBD);
		$array = array();
		while($row_rs = $this->fetch_assoc($result))
		{
			$array[] = $row_rs;
		}
		$this->free_result($result);
		return $array;
	}
	function open_trans_tes()
	{
		$obBD = new base_mysql;
		return $obBD->inicio_trans();
	}
	function insercionesv_tes($sen_sql,$paras,$conectar2)
	{
		$Par_Sql=explode('*',$paras);
		$obBD = new base_mysql;
		if ($obBD->grabarv(sentencias_tes($sen_sql,$Par_Sql),$conectar2)!=1)
		{
			$_SESSION['Error']=1;
		}
	}
	function close_trans_tes($conectar2)
	{
		$obBD = new base_mysql;
		$obBD->fin_trans($conectar2);
	}
	/* Devuelve el numero de una factura */
	function num_factura($num, $pun)
	{
		/* Consulta el numero de una factura en base a el codigo interno */
		$rs_facturas = consultas_tes (31, $num);
		$row_rs_facturas = mysqli_fetch_assoc ($rs_facturas);
		/* Conslta del numero otorgado por SRI del punto de impresion y de la sucursal */
		$rs_puntos = consultas_tes (32, $pun);
		$row_rs_puntos = mysqli_fetch_assoc ($rs_puntos);
		////OJO FALTA AGREGAR CEROS A LA IZQUIERDA
		/* Concatenacion del numero de la factura */
		$cadena = $row_rs_puntos['Pun_Sri'].'-'.$row_rs_puntos['Suc_Sri'].'-'.$row_rs_facturas['Vet_Num'];
		return $cadena;
	}
	/*Funcion que realiza los calculos de totales de facturas*/
	function calculos($Vet_Cod)
	{
		/* Opciones para el retorno 
		0 = SUBTOTAL
		1 = TARIFA 0
		2 = TARIFA 12
		3 = IVA
		4 = DESCUENTO
		5 = TOTAL */
		$rs_calculos = consultas_tes(39, $Vet_Cod);
		$row_rs_calculos = mysqli_fetch_assoc ($rs_calculos);
		do{
			/* % de Descuento total */
			$Vet_Des = $row_rs_calculos['Vet_Des'];
			/* Calculo del total de la factura */
			$subtotal= $subtotal + $row_rs_calculos['Vet_Imp'];
			/* Calculo de las tarifas */
			if ($row_rs_calculos['Iva_Por'] == 0)
			{
				$tarifa_0 = $tarifa_0 + $row_rs_calculos['Vet_Imp'];
				/*Descuento individual */
				$des_0 = $des_0 + round(($row_rs_calculos['Vet_Imp'] * $row_rs_calculos['Vet_Dec'])/100,2);
			}
			else
			{
				$tarifa_12 = $tarifa_12 + $row_rs_calculos['Vet_Imp'];
				/*Descuento individual */
				$des_12 = $des_12 + ($row_rs_calculos['Vet_Imp'] * $row_rs_calculos['Vet_Dec'])/100;			
				$iva_12 = $row_rs_calculos['Iva_Por'];
			}						
		}while ($row_rs_calculos = mysqli_fetch_assoc ($rs_calculos));
		/* Suma del descuento */
		$des = $des_0 + $des_12;
		$subtotal = $subtotal - $des;
		/* calculo del iva con descuento individual */
		$iva = (($tarifa_12 - $des_12) * $iva_12)/100;
		/* Calculo del descuento total */
		if ($Vet_Des != 0)
		{
			$des = ($subtotal * $Vet_Des) / 100;
			$des_12 = ($tarifa_12 * $Vet_Des) / 100;
			$iva = (($tarifa_12 - $des_12) * $iva_12)/100;		
		}
		/*Calculo del total */
		$total = $subtotal - round($des,2) + $iva;
		return $subtotal.'*'.$tarifa_0.'*'.$tarifa_12.'*'.$iva.'*'.$des.'*'.$total;
	}	
	function codigo_compr_auto($op, $Pec_Cod, $mes, $obBD_con1, $obBD_conexion)
    {		 
		/* Consulta de la configuraci�n de la tabla contabilidad */
		$rs_confi_cont = $obBD_con1->consulta(sentencias_con(666, ''), $obBD_conexion->conexion);
		$row_rs_confi_cont = $obBD_con1->registros();
		/* Si la configuracion dice S, es porq se lleva un secuencia
		numerica en base al periodo contable */
		if ($row_rs_confi_cont['Sri_Num']=='S')
		{
			$rs_numcom = $obBD_con1->consulta(sentencias_con(667, $obBD_con1->parametros($op.'*'.$Pec_Cod)), 
							$obBD_conexion->conexion);
			$row_rs_numcom = $obBD_con1->registros();
			$total_rs_numcom = $obBD_con1->numregistros();
			// Revisar la condici�n (todo funciona correctamente pero con artificio)
			if (($total_rs_numcom > 0) && ($row_rs_numcom['Com_Num'] != ''))
				{
					$Com_Num=$row_rs_numcom['Com_Num'];
				} else {
					$Com_Num=1;
				}
		}//Fin del if ($row_rs_confi_cont['Sri_Num']=='S')
		else
		{	
			/* numerica en base al periodo contable y mensualmente */
			if ($row_rs_confi_cont['Sri_Num']=='M')
			{		
				$rs_numcom = $obBD_con1->consulta(sentencias_con(668, $obBD_con1->parametros($op.'*'.$Pec_Cod.'*'.$mes)), 
				$obBD_conexion->conexion);
				$row_rs_numcom = $obBD_con1->registros();
				$total_rs_numcom = $obBD_con1->numregistros();
				// Revisar la condici�n (todo funciona correctamente pero con artificio)
				if (($total_rs_numcom > 0) && ($row_rs_numcom['Com_Num'] != ''))
					{
						$Com_Num=$row_rs_numcom['Com_Num'];
					} else {
						$Com_Num=1;
					}				
			}//Fin del if ($row_rs_confi_cont['Sri_Num']=='M')
			else
			{			
				/* Usado por la UTSAM */			
				$Com_Num = $obBD_con1->codigo_compr($op,$obBD_con1, $obBD_conexion);
			}//Fin del else if ($row_rs_confi_cont['Sri_Num']=='M')
		}//Fin del else if ($row_rs_confi_cont['Sri_Num']=='S')  
	return $Com_Num;
  	}//Fin del codigo_compr_auto($op, $Pec_Cod, $mes, $obBD_con1, $obBD_conexion)
	/* Incremento del numero manual de los comprobantes  */
	function codigo_compr($Comtip,$obBD_con1, $obBD_conexion)
	{		
		//**********************************************************
		$rs_Max = $obBD_con1->consulta(sentencias_con(669, $obBD_con1->parametros($Comtip)),$obBD_conexion->conexion);
		$row_Max = $obBD_con1->registros();
		//$rs_Max=consultas_rol(669, $Comtip);
		//$row_Max=mysqli_fetch_assoc($rs_Max);	
		$maximo = $row_Max['Num'];	
		$maximo = $maximo + 1;
		//**********************************************************
		if ($Comtip == 2)//Comprobantes de egreso
		{
		    for ($i=strlen($maximo);$i<7;$i++)//Antes habia 7
		    {
			    $ceros = $ceros.'0';
		    }
		} 
		else{
		    for ($i=strlen($maximo);$i<6;$i++)//Antes habia 7
		    {
			    $ceros = $ceros.'0';
		    }
		}		
	    $max= $ceros.$maximo;		
		return $max;				
	} 
	/* Calcula el total de las ventas */
	function calculos_ventas($ini, $fin, $obBD_con1, $obBD_conexion, $tipo, $tipo_compr, $Pun_Cod)
	{
		/* Opciones para el retorno 
		0 = SUBTOTAL
		1 = TARIFA 0
		2 = TARIFA 12
		3 = IVA
		4 = DESCUENTO
		5 = TOTAL */
		/* Control para el punto de impresion */
		if ($Pun_Cod > 0)
		{
			$Pun_Cod = " AND caja_aper.Pun_Cod = ".$Pun_Cod;
		}
		else
		{
			$Pun_Cod = "";
		}//Fin del if ($Pun_Cod > 0)
		/* Consulta del total de las ventas */
		$rs_ventas = $obBD_con1->consulta(sentencias_tes(235, $obBD_con1->parametros($ini.'*'.$fin.'*'.$tipo.'*'.$tipo_compr.'*'.$Pun_Cod)), 
							$obBD_conexion->conexion);
		$row_rs_ventas = $obBD_con1->registros();
		$total_rs_ventas = $obBD_con1->numregistros();	
		$mover = false;
		$subtotal = 0;
		$tarifa_0 = 0;
		$tarifa_12 = 0;
		$iva = 0;
		$des = 0;
		$total = 0;
		if ($row_rs_ventas['Iva_Sri'] == 2)//2 es el valor en la tabla del sri
		{			
			$subtotal = $row_rs_ventas['Importe'];
			$tarifa_12 = $row_rs_ventas['Importe'];
			$iva = $row_rs_ventas['Iva'];
			$des = $row_rs_ventas['Descuento'];
			$total = $row_rs_ventas['Total'] + $iva;
			/* En caso de existir 2 registros de total de ventas se mueve el apuntador de la tabla */
			$mover = true;					
		}//Fin del if ($row_rs_ventas['Iva_Sri'] == 2)
		if ($mover == true)
		{
			/* Vuelve al fin del puntero la consulta creditos */
			$row_rs_ventas = first_last($rs_ventas, $row_rs_ventas, $total_rs_ventas);			  
		}//Fin del if ($mover == true)
		if ($row_rs_ventas['Iva_Sri'] == 0)//2 es el valor en la tabla del sri
		{				
			$subtotal = $subtotal + $row_rs_ventas['Importe']; //Suma los dos importes de la consulta
			$tarifa_0 = $row_rs_ventas['Importe'];
			$des = $des + $row_rs_ventas['Descuento'];
			$total = $total + $row_rs_ventas['Total'];
		}//Fin del if ($row_rs_ventas['Iva_Sri'] == 0)
		return $subtotal.'*'.$tarifa_0.'*'.$tarifa_12.'*'.$iva.'*'.$des.'*'.$total;
	}//Fin del function calculos_ventas($ini, $fin)
	/* Consulta el total de las ventas por carrera */
	function calculos_ventas_carreras($ini, $fin, $obBD_con1, $obBD_conexion, $tipo, $tipo_compr, $Car_Int, $Pun_Cod)
	{
		/* Opciones para el retorno 
		0 = SUBTOTAL
		1 = TARIFA 0
		2 = TARIFA 12
		3 = IVA
		4 = DESCUENTO
		5 = TOTAL */
		/* Control para el punto de impresion */
		if ($Pun_Cod > 0)
		{
			$Pun_Cod = " AND caja_aper.Pun_Cod = ".$Pun_Cod;
		}
		else
		{
			$Pun_Cod = "";
		}//Fin del if ($Pun_Cod > 0)
		/* Consulta del total de las ventas */
		$rs_ventas = $obBD_con1->consulta(sentencias_tes(177, $obBD_con1->parametros($ini.'*'.$fin.'*'.$tipo.'*'.
									$tipo_compr.'*'.$Car_Int.'*'.$Pun_Cod)), 
							$obBD_conexion->conexion);
		$row_rs_ventas = $obBD_con1->registros();
		$total_rs_ventas = $obBD_con1->numregistros();	
		$mover = false;
		$subtotal = 0;
		$tarifa_0 = 0;
		$tarifa_12 = 0;
		$iva = 0;
		$des = 0;
		$total = 0;
		if ($row_rs_ventas['Iva_Sri'] == 2)//2 es el valor en la tabla del sri
		{			
			$subtotal = $row_rs_ventas['Importe'];
			$tarifa_12 = $row_rs_ventas['Importe'];
			$iva = $row_rs_ventas['Iva'];
			$des = $row_rs_ventas['Descuento'];
			$total = $row_rs_ventas['Total'] + $iva;
			/* En caso de existir 2 registros de total de ventas se mueve el apuntador de la tabla */
			$mover = true;					
		}//Fin del if ($row_rs_ventas['Iva_Sri'] == 2)
		if ($mover == true)
		{
			/* Vuelve al fin del puntero la consulta creditos */
			$row_rs_ventas = first_last($rs_ventas, $row_rs_ventas, $total_rs_ventas);			  
		}//Fin del if ($mover == true)
		if ($row_rs_ventas['Iva_Sri'] == 0)//2 es el valor en la tabla del sri
		{				
			$subtotal = $subtotal + $row_rs_ventas['Importe']; //Suma los dos importes de la consulta
			$tarifa_0 = $row_rs_ventas['Importe'];
			$des = $des + $row_rs_ventas['Descuento'];
			$total = $total + $row_rs_ventas['Total'];
		}//Fin del if ($row_rs_ventas['Iva_Sri'] == 0)
		return $subtotal.'*'.$tarifa_0.'*'.$tarifa_12.'*'.$iva.'*'.$des.'*'.$total;
	}//Fin del function calculos_ventas_carreras($ini, $fin)
    /** C�lculos compras con I.C.E. ***********/
	function calculos_compra_ice($Cop_Cod)
	{
		/* Opciones para el retorno 
		0 = SUBTOTAL
		1 = TARIFA 0
		2 = TARIFA 12
		3 = IVA
		4 = DESCUENTO
		5 = TOTAL 
		6= ICE
		*/
		$rs_calculos_comp = consultas_tes(473, $Cop_Cod);
		$row_rs_calculos_comp = mysqli_fetch_assoc ($rs_calculos_comp);
		$Imp_Ice=0;
		$total=0;
		$Ice_Comp=0;
		do{
			/* % de Descuento total */
			$Cop_Des = $row_rs_calculos_comp['Cop_Des'];
			/* Calculo del total de la factura */
			$subtotal= $subtotal + $row_rs_calculos_comp['Cop_Imp'];
			/* Calculo de las tarifas */
			if ($row_rs_calculos_comp['Iva_Por'] == 0)
			{
				$tarifa_0 = $tarifa_0 + $row_rs_calculos_comp['Cop_Imp'];
				/*Descuento individual */
				$des_0 = $des_0 + ($row_rs_calculos_comp['Cop_Imp'] * $row_rs_calculos_comp['Cop_Dec'])/100;
			}
			else
			{
				$tarifa_12 = $tarifa_12 + $row_rs_calculos_comp['Cop_Imp'];
				/*Descuento individual */
				$des_12 = $des_12 + ($row_rs_calculos_comp['Cop_Imp'] * $row_rs_calculos_comp['Cop_Dec'])/100;			
				$iva_12 = $row_rs_calculos_comp['Iva_Por'];
			}
			 $rs_porciento_ice=consultas_tes(527,$row_rs_calculos_comp['Cop_Int']); 
			 $row_porciento=mysqli_fetch_assoc($rs_porciento_ice);
			if($Cop_Des==0) {
			if ($row_porciento['Ice_Por']!=NULL && $row_porciento['Ice_Por']>0)
			{ 
			$Ice_Comp=$Ice_Comp+$row_rs_calculos_comp['Cop_Imp'];
			$des_ice = ($row_rs_calculos_comp['Cop_Imp'] * $row_rs_calculos_comp['Cop_Dec'])/100;
			$bas_ice= (($Ice_Comp-$des_ice)*$row_porciento['Ice_Por'])/100;
            $Imp_Ice=$bas_ice;
			}
			}else
			{
			if ($row_porciento['Ice_Por']!=NULL && $row_porciento['Ice_Por']>0)
			{
			$Ice_Comp=$Ice_Comp+$row_rs_calculos_comp['Cop_Imp'];
			$des_ice = ($row_rs_calculos_comp['Cop_Imp'] * $row_rs_calculos_comp['Cop_Des'])/100;
			$bas_ice= (($Ice_Comp-$des_ice)*$row_porciento['Ice_Por'])/100;
            $Imp_Ice=$bas_ice;
			}
			}
		if(isset($rs_porciento_ice)){
			}
		}while ($row_rs_calculos_comp = mysqli_fetch_assoc ($rs_calculos_comp));
		/* Suma del descuento */
		$des = $des_0 + $des_12;
		/* calculo del iva con descuento individual */
		$iva = (($tarifa_12 - $des_12) * $iva_12)/100;
		/* Calculo del descuento total */
		if ($Cop_Des != 0)
		{
			$des = ($subtotal * $Cop_Des) / 100;
			$des_12 = ($tarifa_12 * $Cop_Des) / 100;
			$iva = (($tarifa_12 - $des_12) * $iva_12)/100;		
		}
		/*Calculo del total */
		//$total = (number_format($subtotal,2) - number_format($des,2)) + (number_format($iva,2) + number_format($Imp_Ice,2));
		$total = ($subtotal - $des) + ($iva + $Imp_Ice);		
		return $subtotal.'*'.$tarifa_0.'*'.$tarifa_12.'*'.$iva.'*'.$des.'*'.formato_numero($total,2,1).'*'.$Imp_Ice;
	}	
	 /* Funcion calculos compras */
	function calculos_compra($Cop_Cod)
	{	/* Opciones para el retorno 
		0 = SUBTOTAL
		1 = TARIFA 0
		2 = TARIFA 12
		3 = IVA
		4 = DESCUENTO
		5 = TOTAL */
		$rs_calculos_comp = consultas_tes(473, $Cop_Cod);
		$row_rs_calculos_comp = mysqli_fetch_assoc ($rs_calculos_comp);
		do{
			/* % de Descuento total */
			$Cop_Des = $row_rs_calculos_comp['Cop_Des'];
			/* Calculo del total de la factura */
			$subtotal= $subtotal + $row_rs_calculos_comp['Cop_Imp'];
				/* Calculo de las tarifas */
			if ($row_rs_calculos_comp['Iva_Por'] == 0)
			{
				$tarifa_0 = $tarifa_0 + $row_rs_calculos_comp['Cop_Imp'];
				/*Descuento individual */
				$des_0 = $des_0 + ($row_rs_calculos_comp['Cop_Imp'] * $row_rs_calculos_comp['Cop_Dec'])/100;
			}
			else
			{
				$tarifa_12 = $tarifa_12 + $row_rs_calculos_comp['Cop_Imp'];
				/*Descuento individual */
				$des_12 = $des_12 + ($row_rs_calculos_comp['Cop_Imp'] * $row_rs_calculos_comp['Cop_Dec'])/100;			
				$iva_12 = $row_rs_calculos_comp['Iva_Por'];
			}						
		}while ($row_rs_calculos_comp = mysqli_fetch_assoc ($rs_calculos_comp));
		/* Suma del descuento */
		$des = $des_0 + $des_12;
		/* calculo del iva con descuento individual */
		$iva = (($tarifa_12 - $des_12) * $iva_12)/100;
		/* Calculo del descuento total */
		if ($Cop_Des != 0)
		{
			$des = ($subtotal * $Cop_Des) / 100;
			$des_12 = ($tarifa_12 * $Cop_Des) / 100;
			$iva = (($tarifa_12 - $des_12) * $iva_12)/100;		
		}
		/** Calculo del total ********/
		$total = ($subtotal - $des) + $iva;
		return $subtotal.'*'.$tarifa_0.'*'.$tarifa_12.'*'.$iva.'*'.$des.'*'.$total;
		if(isset($rs_calculos_comp)){
		}
	}		
    /* Funci�n que calcula el total de las notas de compras */
	function calculos_nota_compra($Cop_Cod)
	{
		$rs_calculos_comp = consultas_tes(540, $Cop_Cod);
		$row_rs_calculos_comp = mysqli_fetch_assoc ($rs_calculos_comp);
		$subtotal=0;
        do{
			/* Calculo del total de la factura */
			$subtotal= $subtotal + $row_rs_calculos_comp['Cop_Imp'];
				/* Calculo de las tarifas */
			if ($row_rs_calculos_comp['Iva_Por'] == 0)
			{
				$tarifa_0 = $tarifa_0 + $row_rs_calculos_comp['Cop_Imp'];
			}
			else
			{
				$tarifa_12 = $tarifa_12 + $row_rs_calculos_comp['Cop_Imp'];
				/*Descuento individual */
				$iva_12 = $row_rs_calculos_comp['Iva_Por'];
			}						
		}while ($row_rs_calculos_comp = mysqli_fetch_assoc ($rs_calculos_comp));
		/* calculo del iva con descuento individual */
		$iva = ($tarifa_12  * $iva_12)/100;
	//	$tarifa_12 =$tarifa_12  + $iva;
		/** Calculo del total ********/
		$total = $tarifa_0+$tarifa_12+ $iva;
		return $subtotal.'*'.$tarifa_0.'*'.$tarifa_12.'*'.$iva.'*'.$total;
	}
	/* Funcion que muestra una imagen */
	function boton_act_inac_tes($estado)
	{
		if ($estado == 'A')
		{
			echo "<img src='../../imagenes/deshabilitar.gif' height='20' width='20' border='0' title='Desactivar'>";
		}
		else
		{
			echo "";
		}
	}
	/* Funcion que muestra una imagen cuando esta en estado Inactivo */
	function boton_activo($estado)
	{
		if ($estado == 'I')
		{
			echo "<img src='../../imagenes/check.jpg' height='20' width='20' border='0' title='Activar'>";
		}
		else
		{
			echo "";
		}
	}
	/*funcion que devuelve el codigo automatico del rubro */
	function codigo_auto($Pro_Ide)
	{
		   /* Consulta el numero maximop del producto*/
			$rs_producto= consultas_tes (45, $Pro_Ide);
		    $row_rs_producto= mysqli_fetch_assoc ($rs_producto);
			$maximo = $row_rs_producto['maximo'];
			$Num=substr($maximo,1,4);
			$Fmat=$Num+1;
			switch(strlen($Fmat))
			{
				case 1:
				$Num="000".$Fmat;
				break;
				case 2:
				$Num="00".$Fmat;
				break;
				case 3:
	            $Num="0".$Fmat;
				break;
				case 4:
	            $Num=$Fmat;
				break;
			}
		    $max= $Pro_Ide.$Num;
		return $max;
		} 
	/*funcion que devuelve el codigo automatico de la factura */	
	function codigo_siguiente($Aut, $Num_Ini, $obBD_con1, $obBD_conexion)
	{
		/* Incremento del numero manual de la factura dependiendo de la autorizacion*/
		//**********************************************************		
		$rs_Max = $obBD_con1->consulta(sentencias_tes(27, $obBD_con1->parametros($Aut)), $obBD_conexion->conexion);
		$row_Max = $obBD_con1->fetch_assoc($rs_Max);	
		if ($row_Max['Num'] > 0)
		{
			$maximo = $row_Max['Num'];					
			$maximo++;			
		}//Fin del if ($row_Max['Num'] > 0)
		else
		{
			$maximo = $Num_Ini;
		}//Fin del else if ($row_Max['Num'] > 0)
		return $maximo;
		@$obBD_con1->free_result($rs_Max);
	} //Fin del codigo_siguiente($Aut, $Num_Ini, $obBD_con1, $obBD_conexion)
	/*Agrega la serie a numeros de factura que solo contienen el secuencia */
	function establecimiento($codigo)
	{
		if ($codigo != "")
		{
			$estab = explode('-',$codigo);
			if (count($estab) == 1)	
			{
				unset($estab);
				$estab[0] = "001";
				$estab[1] = "001";
				$estab[2] = $codigo;				
			}
		}
		return $estab;
	}
	/* Elimina cualquier tipo de letra en un codigo de retencion */	
	function cod_air($codigo)
	{
		$air = substr($codigo,0,3);
		return $air;
	}
	/* Elimina cualquier tipo de letra en un codigo del iva */	
	function cod_iva($codigo)
	{
		$air = substr($codigo,0,1);
		return $air;
	}	
 /* Genera las deudas desde la tabla costos para los estudiantes con matricula activa en el periodo actual */
 function generar_deudas($obBD_con1, $obBD_conexion, $Cli_Cod)
 {
    $hoy = date("Y-m-d");
     //$hoy = "2009-06-02";
    /****Consulta el codigo de matricula activa configurado por defecto en la tabla confimat  
    ojo ojo no se utiliza
    */
    /*$rs_confimatri = $obBD_con1->consulta(sentencias_tes(660,''), $obBD_conexion->conexion);
    $rs_confimatri = $obBD_con1->registros();
    $total_rs_confimatri = $obBD_con1->numregistros();*/
    /* Consulta de las matriculas que tiene el cliente en las diferentes modalidades */
    $rs_matriculas = $obBD_con1->consulta(sentencias_tes(168, $obBD_con1->parametros($hoy.'*'.$Cli_Cod.'*'.'A')), 
      $obBD_conexion->conexion); //Antes $rs_confimatri['Con_Mac']
    $row_rs_matriculas = $obBD_con1->registros();
    $total_rs_matriculas = $obBD_con1->numregistros();
    /* Si el cliente posee matriculas procede a buscar los costos en la fecha actual */
    if ($total_rs_matriculas > 0)
    {
     /* Cracion del objeto mysql para las inserciones */
     $obBD_ins1 =  new Class_Log_Datos_Tes;
     /***********************************************/
     /****************Inicio de la transaccion***********************/
     $obBD_ins1->inicio_transaccion($obBD_conexion->conexion);
     /***************************************************************/           
    do{
     $Nge_Cod = $row_rs_matriculas['Nge_Cod'];
     /*************************************************************************/
     /* C O N T R O L   D E   P E N S I O N E S   Y   O T R O S   R U B R O S */
     /*************************************************************************/   
     /* Consulta todos los costos menores o iguales a la fecha indicada */
     $rs_costos = $obBD_con1->consulta(sentencias_tes(169, $obBD_con1->parametros($row_rs_matriculas['Sem_Cod'].'*'.$hoy.
     '*'.'N')), $obBD_conexion->conexion);
     $row_rs_costos = $obBD_con1->registros();
     $total_rs_costos = $obBD_con1->numregistros();
     if ($total_rs_costos > 0)
     {   
   do{
    $Pro_Cod = $row_rs_costos['Pro_Cod'];   
    $Cos_Pre = $row_rs_costos['Cos_Pre'];
    $Cos_Fec = $row_rs_costos['Cos_Fec'];
    $Asi_Int = $row_rs_costos['Asi_Int']; 
    /***************************************************************/
    /**** Control de beca reasignadas ****/
    /***************************************************************/
    /* Consultar si Bec_Cod esta NULL*/
    $rs_deuda_existe = $obBD_con1->consulta(sentencias_tes(383, $obBD_con1->parametros($Pro_Cod.'*'.$Nge_Cod.'*'.$Cli_Cod)), $obBD_conexion->conexion);
    $row_rs_existe=$obBD_con1->registros();
    $num_row_rs_existe=$obBD_con1->numregistros();
    if($num_row_rs_existe>0)
    {    /* Consultar si el producto se encuentra en la tabla becas */
      $rs_deuda_asignada=$obBD_con1->consulta(sentencias_tes(384, $obBD_con1->parametros($row_rs_matriculas['Mat_Int'].'*'.$Pro_Cod)), $obBD_conexion->conexion);
      $row_rs_existe_deuda=$obBD_con1->registros();
      $num_row_rs_existe_deuda=$obBD_con1->numregistros();
      if($num_row_rs_existe_deuda>0)
      {
       /* Baja de la deuda registrada en la tabla deudas*/
    $obBD_ins1->grabarv_registros(sentencias_tes(385, $obBD_ins1->parametros($Pro_Cod.'*'.$Nge_Cod.'*'.$Cli_Cod.'*'.$Asi_Int)),$obBD_conexion->conexion); 
      }//FIn del if($num_row_rs_existe_deuda>0)       
    }//Fin del if($num_row_rs_existe>0)
    /***************************************************************/
    /**** Fin control de beca reasignadas ****/
    /***************************************************************/
    /* Consulta de la posible beca y su porcentaje que tenga el estudiante ACTUAL*/
    $rs_becas = $obBD_con1->consulta(sentencias_tes(73, $obBD_con1->parametros($Cli_Cod.'*'.$Pro_Cod.'*'.
      $row_rs_matriculas['Sem_Cod'])), $obBD_conexion->conexion); 
    $row_rs_becas = $obBD_con1->registros();
    $total_rs_becas = $obBD_con1->numregistros();
    if ($total_rs_becas > 0)
    {
     $Bec_Cod = $row_rs_becas['Bec_Cod'];
    }
    else
    {
     $Bec_Cod = 'NULL';
    }    
    /* Consulta, si el costo actual ya se encuentra registrado en Deudas */
    $rs_existe_deudas = $obBD_con1->consulta(sentencias_tes(170, $obBD_con1->parametros
     ($row_rs_matriculas['Sem_Cod'].'*'.$Pro_Cod.'*'.$Cli_Cod.'*'.$Asi_Int)), 
     $obBD_conexion->conexion);    
    $total_existe_deudas = $obBD_con1->numregistros();
    //echo $total_existe_deudas."<br>";
    /* Si el Asi_Int es diferente de cero, entonces significa que el costo para dicho semestre y 
    asignatura, es modular */     
    if ($Asi_Int != 0)
    {
     /* Consulta la existencia de la asignatura del cliente en el semestre determinado y de tipo Normal */       
     $rs_asignatura = $obBD_con1->consulta(sentencias_tes(185, $obBD_con1->parametros($row_rs_matriculas['Sem_Cod'].'*'.$Cli_Cod.'*'.$Asi_Int
      .'*'.'N')), $obBD_conexion->conexion);  
     $total_rs_asignatura = $obBD_con1->numregistros(); 
     /* Si la cantidad es igual a cero, significa que no tiene registrada la asigantura */   
     if ($total_rs_asignatura == 0)
     {
      /* Se inicializa en 1 la variable para que no se le cargue la deuda */
      $total_existe_deudas = 1;
     }//Fin del if ($total_rs_asignatura == 0)
    }//Fin del if ($Asi_Int != 0)
    /* Si es igual a cero (0) significa que no esta ingresada esa deuda */
    if ($total_existe_deudas == 0)
    {
     /* Inserta las deudas generadas automaticamente */
     $obBD_ins1->grabarv_registros(sentencias_tes(171, $obBD_ins1->parametros($Pro_Cod.'*'.$Nge_Cod.'*'.
     $Cli_Cod.'*'.$Cos_Pre.'*'.$hoy.'*'.$Cos_Fec.'*'.$Bec_Cod.'*'.'0'.'*'.$Asi_Int)), 
     $obBD_conexion->conexion);      
    }//Fin del if ($total_existe_deudas == 0)
   }while($row_rs_costos = $obBD_con1->fetch_assoc($rs_costos));   
   }//Fin del if ($total_rs_costos > 0)
   /********************************************/
   /* C O N T R O L   DE   M A T R I C U L A S */
   /********************************************/   
   /* Consulta todos los costos menores o iguales a la fecha indicada */
   $rs_costos_matr = $obBD_con1->consulta(sentencias_tes(196, $obBD_con1->parametros($row_rs_matriculas['Pem_Cod'].'*'.$hoy."*".$row_rs_matriculas['Sem_Cod'])),$obBD_conexion->conexion);
   $row_rs_costos_matr = $obBD_con1->registros();
   $total_rs_costos_matr = $obBD_con1->numregistros();
   if ($total_rs_costos_matr > 0)
   {   
    do{
     $Pro_Cod = $row_rs_costos_matr['Pro_Cod'];
     $Cos_Pre = $row_rs_costos_matr['Cos_Pre'];
     $Asi_Int = 0;
     $Cos_Fec = $hoy; //La matricula vence el mismo dia de registro
     $Bec_Cod = 'NULL'; //No hay becas en rubros de matriculas
     /* Consulta, si el costo actual ya se encuentra registrado en Deudas */
     $rs_existe_deudas = $obBD_con1->consulta(sentencias_tes(170,$obBD_con1->parametros($row_rs_matriculas['Sem_Cod'].'*'.$Pro_Cod.'*'.$Cli_Cod.'*'.$Asi_Int)),$obBD_conexion->conexion);    
     $total_existe_deudas = $obBD_con1->numregistros();
     /* Si es igual a cero (0) significa que no esta ingresada esa deuda */
     if ($total_existe_deudas == 0)
     {
      /* Inserta las deudas generadas automaticamente */
      $obBD_ins1->grabarv_registros(sentencias_tes(171, $obBD_ins1->parametros($Pro_Cod.'*'.$Nge_Cod.'*'.
         $Cli_Cod.'*'.$Cos_Pre.'*'.$hoy.'*'.$Cos_Fec.'*'.$Bec_Cod.'*'.'0'.'*'.$Asi_Int)), 
         $obBD_conexion->conexion);      
     }//Fin del if ($total_existe_deudas == 0)
    }while($row_rs_costos_matr = $obBD_con1->fetch_assoc($rs_costos_matr));  
   }//Fin del if ($total_rs_costos_matr > 0)
  }while($row_rs_matriculas = $obBD_con1->fetch_assoc($rs_matriculas));  
   /****************************************************************/
   $obBD_ins1->fin_transaccion_nomsn($obBD_conexion->conexion);
   /***************************************************************/                       
  }//Fin del if ($total_rs_matriculas > 0)
 }//Fin de function generar_deudas($obBD_conexion, $obBD_con1, Cli_Cod)
	/* Funcion que genera el interes automaticamente */
	function interes($obBD_con1, $obBD_conexion, $Cli_Cod, $Pro_Cod, $Nge_Cod, $Asi_Int, $saldo)
	{
		$hoy = date("Y-m-d");
		/* Consulta si el rubro o producto acepta interes */
		$rs_si_interes = $obBD_con1->consulta(sentencias_tes(51, $obBD_con1->parametros($Pro_Cod)), $obBD_conexion->conexion);
		$total_rs_si_interes = $obBD_con1->numregistros();
		/* Si esto es verdadero significa que va a verificar si se debe generar el interes */
		if ($total_rs_si_interes > 0)
		{
			/* Cracion del objeto mysql para las inserciones */
			$obBD_ins1 =  new Class_Datos;
			/***********************************************/
			/****************Inicio de la transaccion***********************/
			$obBD_ins1->inicio_transaccion($obBD_conexion->conexion);
			/***************************************************************/											
			/* Consulta los dias de mora que tiene un rubro */
			$rs_mora = $obBD_con1->consulta(sentencias_tes(54, $obBD_con1->parametros($Cli_Cod.'*'.$Pro_Cod.'*'.$Nge_Cod.
									'*'.$Asi_Int)), $obBD_conexion->conexion);
			$row_rs_mora = $obBD_con1->registros();
			/* Si es menor a cero significa que se debe contar los dias de prorroga para el cobro del interes */
			if ($row_rs_mora['Mora'] < 0)
			{
				/* Consulta de los dias de prorroga del interes y calculo del interes */
				$rs_interes = $obBD_con1->consulta(sentencias_tes(56, ''), $obBD_conexion->conexion);
				$row_rs_interes = $obBD_con1->registros();
				do{
					/* Se suma los dias de prorroga $row_rs_interes['Por_Dia'] ya $row_rs_mora['Mora'] es negativo */
					$dias_mora = $row_rs_mora['Mora'] + $row_rs_interes['Int_Dia'];
					/* Si aun de los dias de prorroga el valor es negativo entonces de debe calcular el interes */
					if ($dias_mora < 0)
					{
						/* Consulta los rubros recursivos (INTERES) */
						$rs_existe = $obBD_con1->consulta(sentencias_tes(57, $obBD_con1->parametros($Cli_Cod.'*'.
													$Nge_Cod.'*'.$Asi_Int.'*'.$Pro_Cod)), $obBD_conexion->conexion);
						$row_rs_existe = $obBD_con1->registros();
						$total_rs_existe = $obBD_con1->numregistros();
						$Bec_Cod = 'NULL';
						if ($total_rs_existe == 0) //Si entra aqui es porque no esta creado el interes 
						{
							$porc_int = abs($dias_mora) * $row_rs_interes['Int_Por'];
							$interes = ($saldo * $porc_int) / 100;							
							/* Inserta las deudas INTERES por primera vez */
							$obBD_ins1->grabarv_registros(sentencias_tes(171, $obBD_ins1->parametros($row_rs_interes['Pro_Cod']
								.'*'.$Nge_Cod.'*'.$Cli_Cod.'*'.$interes.'*'.$hoy.'*'.$row_rs_mora['Deu_Fec'].'*'.$Bec_Cod.'*'.
								$Pro_Cod.'*'.$Asi_Int)), $obBD_conexion->conexion);																			
						}//Fin del if ($total_rs_existe == 0)
						else
						{
							/* Control para saber si debe calcular el interes un dia despues del 
							ultimo calculo */
							if ($row_rs_existe['Dias_Mora'] < 0)
							{
								$porc_int = abs($row_rs_existe['Dias_Mora']) * $row_rs_interes['Int_Por'];
								$interes = ($saldo * $porc_int) / 100;							
								$interes_anterior = $row_rs_existe['Deu_Val'];
								$acum_interes = $interes_anterior + $interes; 
								/* Actualiza el interes */
								$obBD_ins1->grabarv_registros(sentencias_tes(64, $obBD_ins1->parametros($acum_interes.'*'.
									$hoy.'*'.$Nge_Cod.'*'.$Cli_Cod.'*'.$Asi_Int.'*'.$row_rs_interes['Pro_Cod'].'*'.$Pro_Cod)),
									$obBD_conexion->conexion);		
							}
						}//Fin Else if ($total_rs_existe == 0)
					}//Fin del if ($dias_mora < 0)				
				}while($row_rs_interes=mysqli_fetch_assoc($rs_interes));
			}//Fin del if ($row_rs_mora['Mora'] < 0)
			/****************************************************************/
			$obBD_ins1->fin_transaccion_nomsn($obBD_conexion->conexion);
			/***************************************************************/																							
		}//Fin del if ($total_rs_si_interes > 0)
	}
	/* Funcion que devuelve el numero maximo de la retencion */
	function numero_retencion($Aut_Cod, $obBD_con1, $obBD_conexion)
	{
	  /* Consulta el maximo numero de retencion realizado en la tabla retencion */ 
	  $rs_max_codigo = $obBD_con1->consulta(sentencias_tes(520, $obBD_con1->parametros($Aut_Cod)), $obBD_conexion->conexion);
	  $row_rs_max_codigo = $obBD_con1->registros();
	  $total_rs_max_codigo = $obBD_con1->numregistros();
	   /* Evalua en caso de haber generado al menos una retencion con la autorizacion actual */
	   if ($total_rs_max_codigo > 0)
	   {
		  $Ret_Id = $row_rs_max_codigo['Cop_Num']+1;
	   }//Fin del if($total_rs_max_codigo > 0)
	   else
	   {
	     /* En caso de no encontrar retenciones realizadas devuelve 0 */	 
		 $Ret_Id = 0;
	   }//Fin del else if($total_rs_max_codigo > 0) �
	   return $Ret_Id;
	}//Fin del function numeros_retencion()
//funcion para obtener el campo que necesito para calcular la formula
 function formula_rol($fila, $campo, $param, $req, $obBD_con1, $obBD_conexion)
 {
 //if($req=="S"){
   //echo $fila." ".$campo;
   //print_r($param);
   $omitir = "";
   for ($j=0; $j<=count($param)-1; $j++)
   {
		$omitir = $omitir.' AND Cam_Cod != '.$param[$j]; 
   }
	/* Consulta la cantidad de veces que este involucrado un campo en una formula*/
   $rs_formulas = $obBD_con1->consulta(sentencias_tes(853, $obBD_con1->parametros($campo.$omitir)), $obBD_conexion->conexion);
   $row_rs_formulas = $obBD_con1->registros();
   $total_rs_formulas = $obBD_con1->numregistros(); 
 do{//Inicio del $row_rs_formulas
   $rs_grupos = $obBD_con1->consulta(sentencias_tes(849, $obBD_con1->parametros($row_rs_formulas['Cam_Cod'])), $obBD_conexion->conexion);
   $row_rs_grupos = $obBD_con1->registros();
   $total_rs_grupos = $obBD_con1->numregistros(); 
   unset($valor);
   if($total_rs_grupos >0)
   {
    $valor[]="document.getElementById('hdd_ing_egr[".$fila.",".$row_rs_formulas['Cam_Cod']."]').value = ";
   do{
   // almacenar grupo
   $Grupo= $row_rs_grupos['Grp_Cod'];
   $rs_campos = $obBD_con1->consulta(sentencias_tes(850, $obBD_con1->parametros($row_rs_grupos['Cam_Cod'].'*'.$Grupo)), $obBD_conexion-> 
   conexion);
   $row_rs_campos = $obBD_con1->registros();
   $total_rs_campos = $obBD_con1->numregistros(); 
   //if($total_rs_grupos >0)
   //{
    $valor[]=$row_rs_grupos['Ope_Ope']."(";
    do{
     $Cam_Rec=$row_rs_campos['Cam_Rec'];
	 /* Control para asignar 2 decimales a los valores y 3 a los porcentajes internos */
	 if ($row_rs_campos['Cam_Vis']=='S')
	 {
		$decimales = 4;	 
	 }
	 else
	 {
		$decimales =5;		 
	 }
     $valor[] = $row_rs_campos['Ope_Ope']."redondear(document.getElementById('hdd_ing_egr[".$fila.",".$Cam_Rec."]').value,".$decimales.")";     
   //  $valor[] = $row_rs_campos['Ope_Ope']."(document.getElementById('hdd_ing_egr[".$fila.",".$Cam_Rec."]').value")";    
    }while($row_rs_campos = $obBD_con1->fetch_assoc($rs_campos));
   // $valor[]=");"; 
     $valor[]=")";
  }while($row_rs_grupos = $obBD_con1->fetch_assoc($rs_grupos));
  $valor[]=";";
  }//fin del if($total_rs_grupos >0)
  //$cadena="";
  for( $i = 0; $i < count($valor); $i++)
     {
          $cadena= $cadena.$valor[$i];
     }
  //if ($cadena != ""){ 
   //$cadena = "document.getElementById('hdd_ing_egr[".$fila.",".$row_rs_formulas['Cam_Cod']."]').value = ".$cadena.";"; 
  //}//Fin del if ($cadena != "")
 //break;
 }while($row_rs_formulas=$obBD_con1->fetch_assoc($rs_formulas));
 return  $cadena;
//}//fin del campo requerido
 }// fin de la formula function formula_rol(campo, $fila)
//funcion anterior
//funcion para obtener el campo que necesito para calcular la formula
 //function formula_rol($fila, $campo, $param, $obBD_con1, $obBD_conexion)
// {
//   //echo $fila." ".$campo;
//   //print_r($param);
//   $omitir = "";
//   for ($j=0; $j<=count($param)-1; $j++)
//   {
//		$omitir = $omitir.' AND Cam_Cod != '.$param[$j]; 
//   }
//
//	/* Consulta la cantidad de veces que este involucrado un campo en una formula*/
//   $rs_formulas = $obBD_con1->consulta(sentencias_tes(853, $obBD_con1->parametros($campo.$omitir)), $obBD_conexion->conexion);
//   $row_rs_formulas = $obBD_con1->registros();
//   $total_rs_formulas = $obBD_con1->numregistros(); 
//  
// do{//Inicio del $row_rs_formulas
// 
//   $rs_grupos = $obBD_con1->consulta(sentencias_tes(849, $obBD_con1->parametros($row_rs_formulas['Cam_Cod'])), $obBD_conexion->conexion);
//   $row_rs_grupos = $obBD_con1->registros();
//   $total_rs_grupos = $obBD_con1->numregistros(); 
//   unset($valor);
//   do{
//   // almacenar grupo
//   $Grupo= $row_rs_grupos['Grp_Cod'];
//   $rs_campos = $obBD_con1->consulta(sentencias_tes(850, $obBD_con1->parametros($row_rs_grupos['Cam_Cod'].'*'.$Grupo)), $obBD_conexion-> 
//   conexion);
//   $row_rs_campos = $obBD_con1->registros();
//   $total_rs_campos = $obBD_con1->numregistros(); 
//   if($total_rs_grupos >0)
//   {
//    $valor[]="document.getElementById('hdd_ing_egr[".$fila.",".$row_rs_formulas['Cam_Cod']."]').value = ";
//    $valor[]=$row_rs_grupos['Ope_Ope']."(";
//    do{
//     $Cam_Rec=$row_rs_campos['Cam_Rec'];
//	 /* Control para asignar 2 decimales a los valores y 3 a los porcentajes internos */
//	 if ($row_rs_campos['Cam_Vis']=='S')
//	 {
//		$decimales = 3;	 
//	 }
//	 else
//	 {
//		$decimales =4;		 
//	 }
//     $valor[] = $row_rs_campos['Ope_Ope']."redondear(document.getElementById('hdd_ing_egr[".$fila.",".$Cam_Rec."]').value,".$decimales.")";     
//   //  $valor[] = $row_rs_campos['Ope_Ope']."(document.getElementById('hdd_ing_egr[".$fila.",".$Cam_Rec."]').value")";    
//    }while($row_rs_campos = $obBD_con1->fetch_assoc($rs_campos));
//   $valor[]=");"; 
//   }//fin del if($total_rs_grupos >0)
//   
//  }while($row_rs_grupos = $obBD_con1->fetch_assoc($rs_grupos));
//  //$cadena="";
//  for( $i = 0; $i < count($valor); $i++)
//     {
//          $cadena= $cadena.$valor[$i];
//     }
// 
//  //if ($cadena != ""){ 
//   //$cadena = "document.getElementById('hdd_ing_egr[".$fila.",".$row_rs_formulas['Cam_Cod']."]').value = ".$cadena.";"; 
//  //}//Fin del if ($cadena != "")
// //break;
// }while($row_rs_formulas=$obBD_con1->fetch_assoc($rs_formulas));
// 
// return  $cadena;
// }// fin de la formula function formula_rol(campo, $fila)
//funcionanterior
 //funcion para obtener calculos
 function configura_campo($param, $obBD_con1, $obBD_conexion)
 { 	
 	/* C O N T R O L   P A R A   F U N C I O N E S   D E   T I P O   V A L U E */
	/* Consulta de las funciones asociadas al campo. V=value */
	$rs_modulo= $obBD_con1->consulta(sentencias_tes(885, $obBD_con1->parametros($param[0].'*'.'V')), $obBD_conexion->conexion);			
    $row_rs_modulo = $obBD_con1->registros();
    $total_rs_modulo = $obBD_con1->numregistros();
	/* Entra es porque existe un modulo del cual toma datos */
	if ($total_rs_modulo > 0)
	{
			$fn = trim($row_rs_modulo['Mro_Fun']);
			if (is_callable($fn)) {
				$retorno['Value'][] = call_user_func($fn, $param, $obBD_con1, $obBD_conexion);
			} else {
				$retorno['Value'][] = null;
			}		
	}//Fin del if ($total_rs_modulo > 0)	
	/* C O N T R O L   P A R A   F U N C I O N E S   D E   T I P O   E V E N T O */
	/* Consulta de las funciones asociadas al campo E=evento */
	$rs_modulo = $obBD_con1->consulta(sentencias_tes(885, $obBD_con1->parametros($param[0].'*'.'E')), $obBD_conexion->conexion);			
    $row_rs_modulo = $obBD_con1->registros();
    $total_rs_modulo = $obBD_con1->numregistros();
	/* Entra es porque existe un modulo del cual toma datos */
	if ($total_rs_modulo > 0)
	{
		$i=0;
		do{
			$fn = trim($row_rs_modulo['Mro_Fun']);
			if (is_callable($fn)) {
				$valor = call_user_func($fn, $param, $obBD_con1, $obBD_conexion);
			} else {
				$valor = null;
			}		
			if ($valor > 0)
			{
				$retorno['Event'][]	= $valor;
			}
		}while($row_rs_modulo = $obBD_con1->fetch_assoc($rs_modulo));	
	}//Fin del if ($total_rs_modulo > 0)	
	return $retorno;
 }
 function sueldos($param, $obBD_con1, $obBD_conexion)
 {
	/* Consulta el sueldo de los empleados */
 	$rs_sueldos= $obBD_con1->consulta(sentencias_tes(886, $obBD_con1->parametros($param[1])), $obBD_conexion->conexion);			
    $row_rs_sueldos = $obBD_con1->registros();
    $total_rs_sueldos = $obBD_con1->numregistros();
	if ($total_rs_sueldos > 0)
	{
		$sueldo = $row_rs_sueldos['Sue_Val'];
	}
	else
	{	
		$sueldo = 0;
	}//Fin del if ($total_rs_sueldos > 0)
	return $sueldo;
 }
 function fondos_reserva($param, $obBD_con1, $obBD_conexion)
 {
	/* Consulta el sueldo de los empleados */
 	$rs_fondos = $obBD_con1->consulta(sentencias_tes(884, $obBD_con1->parametros($param[1])), $obBD_conexion->conexion);			
    $row_rs_fondos = $obBD_con1->registros();
    $total_rs_fondos = $obBD_con1->numregistros();
		/* N significa que no desea acumular los fondos */
		if ($row_rs_fondos['Afi_Fnd'] == 'N' or $total_rs_fondos == 0)
		{
			/* Consulta el sueldo de los empleados */
 			$rs_confi_rol = $obBD_con1->consulta(sentencias_tes(887, $obBD_con1->parametros($param[0])), $obBD_conexion->conexion);			
		    $row_rs_confi_rol = $obBD_con1->registros();
			$fondos = $row_rs_confi_rol['Apo_Fnd'];
		}
	return $fondos; //Campo al cual no se le va a permitir que aparezca la formula
 }
/**
	* Formato standar para reportes
	* @param int $sucursal C�digo de la sucursal
	* @param string $titulo T�tulo del reporte
	* @param string $subtitulo Subtitulo del reporte
	*/
	function cabeceraReporteStandar($sucursal, $titulo, $subtitulo,$obBD)
	{
		/* Consulta de la cabecera del reporte */
		$row_institucion = $this->getRowConsulta(22, $sucursal, $obBD);
		/* Consulta la provicia y pais de la sucursal */
		$row_provincia = $this->getRowConsulta(21, $row_institucion['Ciu_Cod'], $obBD);
		?>
				<table width="80%" border="0" cellpadding="0" cellspacing="0">
				  <tr align="center">
				    <td width="5%" rowspan="5" valign="top"><img src="<?php echo $row_institucion['Emp_Log']; ?>" width="83" height="67" /></td>
				    <td width="75%" class="TITULO_REPORTE_2"><?Php echo $row_institucion['Emp_Nom']; ?></td>
				  </tr>
				  <tr align="center">
				    <td valign="top" class="Texto_Reporte"><div align="center"><strong>R.U.C.:</strong> &nbsp;<?php echo $row_institucion['Emp_Ruc']; ?>&nbsp;		      <strong>TELEFONO:</strong>&nbsp;<?php echo $row_institucion['Suc_Te1']; ?></div></td>
			      </tr>
				  <tr align="center">
				    <td valign="top" class="Texto_Reporte"><div align="center"><strong>DIRECCION:</strong>&nbsp;<?php echo $row_institucion['Suc_Dir']; ?></div></td>
			      </tr>
				  <tr align="center">
				    <td valign="top" class="Texto_Reporte"><div align="center"><strong>E-MAIL:</strong> &nbsp;<?php echo $row_institucion['Suc_Cor']; ?></div></td>
			      </tr>
				  <tr align="center">
				    <td align="center" valign="top" class="Texto_Reporte"><div align="center"><?Php 
					if (count($row_provincia) > 0)
					{
						$provincia = " - ".$row_provincia['Pro_Nom'].' - '.$row_provincia['Pas_Nom'];
					}
					else
					{
						$provincia = "";					
					}
					echo $row_institucion['Ciu_Des'].$provincia;?></div></td>
		  		  </tr>
				  <tr align="center">
				    <td colspan="2" valign="top"><hr /></td>
		  		  </tr>
				  <tr align="center">
				    <td colspan="2" valign="top" class="TITULO_REPORTE"><?php echo $titulo; ?></td>
		  		  </tr>
				  <tr align="center">
				    <td colspan="2" valign="top" class="TITULO_REPORTE"><?php echo $subtitulo; ?></td>
			      </tr>
			    </table>
		<?php
			} 
			/**
			 * Formato standar para reportes
			 * @param int $sucursal C�digo de la sucursal
			 * @param string $usuario C�digo del usuario 
			 */	
			function pieReporteStandar($sucursal, $usuario, $obBD)
			{ 
				/* Consulta de la cabecera del reporte */
				$row_institucion = $this->getRowConsulta(22, $sucursal, $obBD);	
				/* Consulta los datos del usuario */
				$row_usuario = $this->getRowConsulta(23, $usuario, $obBD);
				$fecha=explode("-",date("Y-m-d"));	
		   	    $fechaHoy =	$row_institucion['Ciu_Des'].", ".$fecha[2]." de ".mes($fecha[1],1)." ".$fecha[0] ;	
			?>
				<table width="80%" border="0" cellpadding="0" cellspacing="0">
		   		  <tr align="center">
				    <td valign="top"><hr /></td>
		  		  </tr>
				  <tr align="center">
				    <td width="75%" valign="top" class="Texto_Reporte"><div align="center"><strong>FECHA IMPRESI&Oacute;N:</strong> &nbsp;<?php echo $fechaHoy; ?>&nbsp;		      <strong>USUARIO:</strong>&nbsp;<?php echo $row_usuario['Prs_Ape'].' '.$row_usuario['Prs_Nom'] ; ?></div></td>
			      </tr>
			    </table>
		<?php
			}
}
?>