<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php	
/**
* Descripci�n: Permite crear guias de remision
* Desarrollador:	Jose Cumbicos 
* Fecha de actualizaci�n:	2015-07-01 
*/

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_fac_guia_remi.php');	  	
require_once('../../Librerias/procedimientos/almacenados_standar.php');	
require_once('../../Librerias/postclass.php');

/** 
* Creacion del Objeto de conexi�n 
*/
$obBD_conexion = new Class_Log_Conexion_Tes($Ses_Dat_Dis);
/* 
* Creaci�n del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Tes;
/** 
* Creaci�n del objeto para evitar el reenvio 
*/
$thisPost = new Post_Block;

if($Ses_Prs_Cod==1){
	//$Gui_Cod=3;	
	//include("../COMPONENTES/tesXmlGuiaRemisionElectronica_1.0.php");	
	//echo $claveAcceso."<br>";		
}
$hoy = date("Y-m-d");
$mes = date("m"); 

/**
* Consulta del vendedor en base al codigo de la persona
*/
$row_rs_vendedor = $obBD_con1->getRowConsulta(24, $Ses_Prs_Cod.'*'.$Ses_Suc_Cod, $obBD_conexion);
$Pun_Cod = $row_rs_vendedor['Pun_Cod'];

/**
* Consulta de la caja activa en base al vendedor
*/
//$row_rs_apercaja = $obBD_con1->getRowConsulta(1223, $selCajaAper, $obBD_conexion);
$row_rs_apercaja = $obBD_con1->getRowConsulta(25, $Pun_Cod, $obBD_conexion);


/** 
* Cargado AJAX de los resultados de la b�squeda del rubro 
*/	
if (isset($buscod))
{
	$busqueda = $busqueda;
	$codigo = $codigo;
	$Sem_Cod = $Sem_Cod;
	include("../COMPONENTES/tesComRubrosConsulta4.php");	
	//include("../../tesoreria/COMPONENTES/tesComRubrosConsulta2.php"); 
	exit();	
}//if (isset($buscod))

/**
* Ajax para consultar Destinatario
*/
if(isset($ajax_desti))
{	
	$row = $obBD_con1->getRowConsulta(1260,$Prs_Ced.'*'.$Emp_Cod,$obBD_conexion);
	?>	    
    <table width="100%" border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td width="30%" class="Etiqueta1"><span class="Asterisco">*</span> C&eacute;dula/R.U.C.:</td>
            <td width="20%" align="left" class="LetraNegra">&nbsp;<input name="Prs_Ced" type="text" id="Prs_Ced" size="10" maxlength="13" value="<?php echo $row['Prs_Ced']?>"/>
            <input name="Des_Cod" type="hidden" id="Des_Cod" value="<?php echo $row['Des_Cod']?>"/>
            <input name="Prs_Cor" type="hidden" id="Prs_Cor" value="<?php echo $row['Prs_Cor']?>"/>
            <input name="Prs_Cod" type="hidden" id="Prs_Cod" value="<?php echo $row['Prs_Cod']?>"/>
            </td>
            <td colspan="2">&nbsp;<button type="button" name="button" id="button" class="btn btn-success btn-mini" title="Buscar Destinatario" onclick="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_desti=1&Prs_Ced='+ document.getElementById('Prs_Ced').value +'&Emp_Cod=<?php echo $Emp_Cod;?>&op=1&titulo=<?php echo 'Destinatario';?>','destinatario')">
              <i class="icon-search icon-white"></i> 
            </button></td>
            </tr>
          <tr>
            <td class="Etiqueta1">Nombre/Raz&oacute;n Social:</td>
            <td colspan="3" class="LetraNegra">&nbsp;<input name="PrsNom" type="text" id="PrsNom" style="border:none" size="44" readonly="readonly" value="<?php echo $row['Prs_Ape'].' '.$row['Prs_Nom']?>"/></td>
            </tr>
          <tr>
            <td class="Etiqueta1">C&oacute;d. Establecimiento:</td>
            <td align="left" class="LetraNegra">&nbsp;<input name="Des_Sri" type="text" id="Des_Sri" style="border:none" size="10" maxlength="13" readonly="readonly" value="<?php echo $row['Des_Sri'];?>"/></td>
            <td width="16%" class="Etiqueta1">C&oacute;digo Aduana:</td>
            <td width="34%" align="left" class="LetraNegra">&nbsp;<input name="Des_Adu" type="text" id="Des_Adu" style="border:none; text-transform:uppercase" size="17" maxlength="13" readonly="readonly" value="<?php echo $row['Des_Adu'];?>"/></td>
            </tr>
          <tr>
            <td class="Etiqueta1"><span class="Asterisco">* </span>Direcci&oacute;n de Llegada:</td>
            <td colspan="3" align="left" class="LetraNegra">&nbsp;<input name="Prs_Dir" type="text" id="Prs_Dir" style="text-transform:uppercase" size="44"  value="<?php echo $row['Prs_Dir'];?>"/></td>
            </tr>
          <tr>
            <td class="Etiqueta1"><span class="Asterisco">* </span>Motivo:</td>
            <td colspan="3" align="left" class="LetraNegra">&nbsp;<input name="Gui_Mot" type="text" id="Gui_Mot" style="text-transform:uppercase" size="44" /></td>
            </tr>
        </table>
	<?php
	exit();
}

/**
* Ajax para consultar Destinatario
*/ 
if(isset($ajax_trans))
{
	$row = $obBD_con1->getRowConsulta(1261,$Prs_Ced.'*'.$Emp_Cod,$obBD_conexion);
	?>	
    <table width="100%" border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td width="31%" class="Etiqueta1"><span class="Asterisco">*</span> C&eacute;dula/R.U.C.:</td>
            <td width="11%" class="LetraNegra">
              <input name="PrsCed" type="text" id="PrsCed" size="10" maxlength="13" value="<?php echo $row['Prs_Ced']?>"/>
              <input name="Tra_Cod" type="hidden" id="Tra_Cod" value="<?php echo $row['Tra_Cod']?>"/>
            </td>
            <td width="58%">&nbsp;&nbsp;<button type="button" name="button" id="button" class="btn btn-success btn-mini" title="Buscar" onclick="ajax_datos('<?Php echo $_SERVER['PHP_SELF'];?>?ajax_trans=1&Prs_Ced='+ document.getElementById('PrsCed').value +'&Emp_Cod=<?php echo $Emp_Cod;?>&op=2&titulo=<?php echo 'Transportista';?>','transporte')">
              <i class="icon-search icon-white"></i>
            </button></td>
            </tr>
          <tr>
            <td class="Etiqueta1">Nombre/Raz&oacute;n Social:</td>
            <td colspan="2"><span class="LetraNegra">
              <input name="TraNom" type="text" id="TraNom" style="border:none" size="44" readonly="readonly" value="<?php echo $row['Prs_Ape'].' '.$row['Prs_Nom']?>"/>
            </span></td>
            </tr>
          <tr>
            <td class="Etiqueta1"><span class="Asterisco">*</span> Direcci&oacute;n de Salida:</td>
            <td colspan="2"><span class="LetraNegra">
              <input name="TraDir" type="text" id="TraDir" size="44" style="text-transform:uppercase" value=""/>
            </span></td>
            </tr>
           <tr>
            <td class="Etiqueta1"><span class="Asterisco">*</span> Placa:</td>
            <td><input name="Gui_Pla" type="text" id="Gui_Pla" style="text-transform:uppercase" size="10" /></td>
            <td>
                <table width="100%" border="0" cellspacing="0" cellpadding="0">
                  <tr>
                    <td width="16%" class="Etiqueta1"><span class="Asterisco">*</span> Salida:</td>
                    <td width="26%"><span class="LetraNegra">
                      <input name="Gui_Fsa" type="text" id="Gui_Fsa" size="6" onKeyUp="mascara(this,'-',patron,true)" onBlur="validar_fecha2(this);" />
                    </span></td>
                    <td width="19%" class="Etiqueta1"><span class="Asterisco">*</span> Llegada:</td>
                    <td width="39%"><span class="LetraNegra">
                      <input name="Gui_Far" type="text" id="Gui_Far" size="6" onKeyUp="mascara(this,'-',patron,true)" onBlur="validar_fecha2(this);" />
                    </span></td> 
                  </tr>
                </table>
            </td>
          </tr>
          <tr>          
            <td class="Etiqueta1"><span class="Asterisco">*</span> Ruta de Traslado:</td>
            <td colspan="2"><span class="LetraNegra">
              <input name="Gui_Rut" type="text" id="Gui_Rut" style="text-transform:uppercase" size="44" />
            </span>
          </tr>
        </table>
        
	<?php
	exit();
}

/** 
* Cargado del buscador de rubros 
*/
if (isset($ajax_rubro))
{   
	$car=0;
	?>
	<table width="100%" border="0" cellpadding="0" cellspacing="0" id="rubros_table">
      <tr>
        <td>
          <FIELDSET>
          <LEGEND>
          <label class="Titulos2">B&uacute;squeda del rubro</label>
          </LEGEND>
          		<?php include("../COMPONENTES/tesComRubrosFac3.php");?>
          </FIELDSET>
        </td>
       </tr>
      </table>
    <?php    	      		
	exit();		
}

/**
* Ajax para consultar Destinatario
*/
if(isset($ajax_busca))
{   
    include("../COMPONENTES/comConGuiaRemi.php");
	exit();
}


/** 
* Evitar el reenvio de formularios 
*/
if ($thisPost->postBlock($_POST['postID']))
{
	/** 
	* Almacena los datos modificados 
	*/
	if (isset($hdd_save) && !isset($hdd_volver))
	{	
		/** 
		* Creacion del objeto mysql para las inserciones 
		*/
		$obBD_ins1 =  new Class_Log_Datos_Tes;		
						
		/**
		* Inicio de la transaccion
		*/
		$obBD_ins1->inicio_transaccion($obBD_conexion->conexion);
		
		/**
		*  Consultamos informacion de la autorizacion
		*/
		$rs_infoCliente = $obBD_con1->getRowConsulta(81, $Aut_Cod, $obBD_conexion);
		
		/**
		*  proceso para generar clave de acceso del XML a la vez se gurdara en la cabecera de la factura
		*/
		$Gui_Aut="";
		$rs_infoEmpresa = $obBD_con1->getRowConsulta(1211, $Ses_Suc_Cod, $obBD_conexion);		
		$rs_infoTipCom = $obBD_con1->getRowConsulta(1241, $Tic_Cod, $obBD_conexion);
		if ($rs_infoEmpresa['Cof_Gce']=="S") /* Verifico si tiene autorizacion para generar F.E.*/
		{	
			$Gui_Aut="N"; //variable que nos indica que la factura electronica esta pendiente de envio al SRI
			for($i=strlen($Gui_Num); $i<=9-1; $i++)
			{ $ceroDoc=$ceroDoc."0";}
			$TipoAmbienteCE=$rs_infoEmpresa['Cof_Fac'];
			$TipoEmisionCE=$rs_infoEmpresa['Cof_Fte'];
			
			/*Control para generar la clave de acceso de tipo  1=Normal  2=Indisponibilidad de Sistema WebService SRI*/
			if($rs_infoEmpresa['Cof_Fte']=='1')
			{
				$cadena=date("dmY",strtotime($Gui_Fec)).str_pad($rs_infoTipCom['Tic_Sri'], 2, "0", STR_PAD_LEFT).$rs_infoEmpresa['Emp_Ruc'].$rs_infoEmpresa['Cof_Fac'].$rs_infoEmpresa['Suc_Sri'].$rs_infoCliente['Pun_Sri'].$ceroDoc.$Gui_Num."12345678".$rs_infoEmpresa['Cof_Fte'];
			}else{
				/*preguntamos si el txt aun posee numeros para usar*/
				if(count(file($Ses_Emp_Cod."/".$rs_infoEmpresa['Cof_Clv']))!=0)
				{	
					$file = file($Ses_Emp_Cod."/".$rs_infoEmpresa['Cof_Clv']);
					/*clave de acceso de tipo emision INDISPONIBILIDAD DEL SISTEMA*/
					$cadena=date("dmY",strtotime($Gui_Fec)).$rs_infoTipCom['Tic_Sri'].$rs_infoEmpresa['Emp_Ruc'].$rs_infoEmpresa['Cof_Fac'].substr($file[0], 14, 23).$rs_infoEmpresa['Cof_Fte'];
				}else{					
					/*clave de acceso de tipo emision NORMAL*/
					$cadena=date("dmY",strtotime($Gui_Fec)).$rs_infoTipCom['Tic_Sri'].$rs_infoEmpresa['Emp_Ruc'].$rs_infoEmpresa['Cof_Fac'].$rs_infoEmpresa['Suc_Sri'].$rs_infoCliente['Pun_Sri'].$ceroDoc.$Gui_Num."12345678".$rs_infoEmpresa['Cof_Fte'];
											
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
			$dv = $dv == 11 ? 0 : ($dv == 10 ? 1 : $dv);  //si el digito verificador 11 se cambia por (0), si es igual a 10 se cambia a (1)
			/* Agrego el codigo verificador al final de la clave de acceso*/
			$claveAcceso=$cadena.$dv;
			//$url=$Ses_Emp_Cod."/".$claveAcceso.".xml";
			$url=$claveAcceso;
		}
		
		/** 
		* insertamos la cabecera de la guia de remision
		*/
		$obBD_ins1->operacionobBD(1265, $Aut_Cod.'*'.$Des_Cod.'*'.$Tra_Cod.'*'.$Ses_Usu_Cod.'*'.$Gui_Num.'*'.$claveAcceso.'*'.$hoy.'*'.$Gui_Mot.'*'.$Gui_Pla.'*'.$Gui_Fsa.'*'.$Gui_Far.'*'.$Gui_Rut.'*'.$Prs_Dir.'*'.$Vet_Num.'*'.$Vet_Fec.'*'.$Vet_Aut.'*'.$TicSriVen.'*'.$TraDir, $obBD_conexion);	 
		$Gui_Cod = $obBD_ins1->insercionid($obBD_conexion->conexion);
				
		/**		
		* Recorrido del arreglo del detalle de la guias de remision
		*/			
		foreach ($datos as $puntero => $item)
		{
			$cant++;
			$param[]=$item;		
			if ($cant==5)
			{
				$cant=0;					
				$obBD_ins1->operacionobBD(1266, $Gui_Cod.'*'.$param[0].'*'.$param[2], $obBD_conexion);
				unset($param);
			}
		}
		/*
		*   GUARDAMOS AL CLIENTE COMO USUARIO DEL SISTEMA SOLO PARA FACTURAS ELECTRONICAS
		*/
		if ($rs_infoEmpresa['Cof_Gce']=="S") /* Verifico si tiene autorizacion para generar F.E.*/
		{   
		    /* Consultamos si existe usuario */
			$row_rs_usuario = $obBD_con1->getRowConsulta(1245, $Ses_Suc_Cod.'*'.$Prs_Ced,$obBD_conexion);					
			$total_usuario=$row_rs_usuario['Suc_Cod'] > 0? 1 : 0;
			if($total_usuario==0)
			{				
				/* creamos el usuario en la base local Prs_Cod,Suc_Cod,Usu_Ced,Usu_Pal,Usu_Tip,Usu_Est,Usu_Cad */
				$obBD_ins1->operacionobBD(1234,$Prs_Cod.'*'.$Ses_Suc_Cod.'*'.$Prs_Ced.'*'.$Prs_Ced.'*N',$obBD_conexion);				
				$UsuCodCli = $obBD_ins1->insercionid($obBD_conexion->conexion);
				
				/* Consultamos si existe usuario */
				$row_rs_perfil = $obBD_con1->getRowConsulta(1254, $Ses_Emp_Cod,$obBD_conexion);					
				$total_rs_perfil=$row_rs_perfil['Per_Cod'] > 0? 1 : 0;
				if($total_rs_perfil!=0)
				{				
					/* asignamos el perfil "Clientes" para el cliente */
					$obBD_ins1->operacionobBD(1255,$UsuCodCli.'*'.$row_rs_perfil['Per_Cod'],$obBD_conexion);				
				}
			}						
		}
		$obBD_ins1->fin_transaccion($obBD_conexion->conexion);		
		
		/*
		*   GUARDAMOS AL DESTINATARIO COMO USUARIO DEL SISTEMA SOLO PARA FACTURAS ELECTRONICAS
		*/
		if ($obBD_ins1->Error==0 && $rs_infoEmpresa['Cof_Gce']=="S") /* Verifico si tiene autorizacion para generar F.E.*/
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
			$row_rs_existeUsu = $obBD_con1_master->getRowConsulta(1246, $Ses_Usu_Cod.'*'.$row_rs_DatEmp['Dat_Cod'].'*'.$Prs_Ced,$obBD_conexion_master);			
			$total_existeUsu=$row_rs_usuario['Suc_Cod'] > 0? 1 : 0;
			if($total_existeUsu==0)
			{	
				/* Inicio de la transaccion	*/
				$obBD_ins1_master->inicio_transaccion($obBD_conexion_master->conexion);																
				/* creamos el usuario en la base master */
				$obBD_ins1_master->operacionobBD(1243,$Ses_Suc_Cod.'*'.$row_rs_DatEmp['Dat_Cod'].'*'.$Prs_Ced,$obBD_conexion_master);
				$obBD_ins1_master->fin_transaccion_nomsn($obBD_conexion_master->conexion);
			}
		}
				
		/**
		*  Si la transaccion fue correcta generamos el xml para Factura Electronica
		*/				
		if ($obBD_ins1->Error==0)
		{
			if ($rs_infoEmpresa['Cof_Gce']=="S") /* Verifico si tiene autorizacion para generar F.E.*/
			{
				/* Envio Notificacion por Correo Electronico al cliente */												
				$row_tipo_compr = $obBD_con1->getRowConsulta(1253, '06',$obBD_conexion);
				$fechaEmi = explode("-",$hoy);
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
						<td colspan="2" class="texto">Ha generado el siguente comprobante electr&oacute;nico a, '.$PrsNom.' con c&eacute;dula '.$Prs_Ced.'.<br><br>
						<strong>Tipo:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </strong>'.$row_tipo_compr["Tic_Des"].' <br>
						<strong>Fecha de Emisi&oacute;n:&nbsp;</strong>'.$fechaEmi[2].' de '.mes($fechaEmi[1],1).' '.$fechaEmi[0].'<br>
						<strong>Secuencia:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</strong>'.$rs_infoEmpresa["Suc_Sri"].'-'.$rs_infoCliente["Pun_Sri"].'-'.$ceroDoc.$Gui_Num.'<br>
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
						  <strong>Paso 1:</strong> Click en el bot&oacute;n <strong>Siguiente</strong><br>';
						  if($total_usuario!=0){ $msgHtml=$msgHtml.'<strong>Paso 2:</strong> Ingresar el usuario(Cedula/R.u.c.) y contrase&ntilde;a<br>';}else{$msgHtml=$msgHtml.'<strong>Paso 2:</strong> Ingresar el usuario(Cedula/R.u.c.) y contrase&ntilde;a(Cedula/R.u.c.)<br>';}							  
						  $msgHtml=$msgHtml.'<strong>Paso 3:</strong> Click en el bot&oacute;n <strong>Entrar</strong><br>';
						  if($total_usuario==0){ $msgHtml=$msgHtml.'<strong>Paso 4:</strong> Cambiar la contrase�a';}
						  $msgHtml=$msgHtml.'</td>
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
					// Configuramos el protocolo SMTP con autenticaci�n
					$mail->IsSMTP();
					$mail->SMTPAuth = true;
					$mail->IsHTML(true);
					// Configuraci�n del servidor SMTP
					$mail->Port = 25;
					$mail->Host = 'ofsercont.com';
					$mail->Username = "facturacion.electronica@ofsercont.com";
					$mail->Password = "p.123456";
					// Configuraci�n cabeceras del mensaje
					$mail->From = "facturacion.electronica@ofsercont.com";
					$mail->FromName = $Ses_Emp_Nom;
					$mail->AddAddress(trim($Prs_Cor),strtoupper($PrsNom));
					//$mail->AddAddress("destino2@correo.com","Nombre 2");
					//$mail->AddCC("copia1@correo.com","Nombre copia 1");
					//$mail->AddBCC("copia1@correo.com","Nombre copia 1");
					$mail->Subject = "Comprobante Electr�nico";
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

				/* Genera el Xml de la Fatura */
				include("../COMPONENTES/tesXmlGuiaRemisionElectronica_1.0.php");
			}
		}
		
	}//if (isset($hdd_save))
}//Fin del if ($thisPost->postBlock($_POST['postID']))	
	
/**
* Cargado de los datos de la cabecera 
*/
if ($txt_busqueda != "")
{	
	if ($op_opciones == "d")
	{
		//$rs_buscar = $obBD_con1->getArrayConsulta(324, trim($txt_busqueda).'*'.$Pun_Cod.'*'.$row_rs_apercaja['Caj_Fec'], $obBD_conexion);
		$rs_buscar = $obBD_con1->getArrayConsulta(1225, trim($txt_busqueda).'*'.$Pun_Cod, $obBD_conexion);
	}
	elseif ($op_opciones == "r")
	{
		//$rs_buscar = $obBD_con1->getArrayConsulta(325, trim($txt_busqueda).'*'.$Pun_Cod.'*'.$row_rs_apercaja['Caj_Fec'], $obBD_conexion);
		$rs_buscar = $obBD_con1->getArrayConsulta(1226, trim($txt_busqueda).'*'.$Pun_Cod, $obBD_conexion);
	} 
	elseif($op_opciones == "c")
	{		
		$rs_buscar = $obBD_con1->getArrayConsulta(1230, trim($txt_busqueda).'*'.$Pun_Cod, $obBD_conexion);
	}
	else
	{
		/** 
		* Consulta las facturas en base a la papeleta de deposito
		*/
		//$rs_buscar = $obBD_con1->getArrayConsulta(326, trim($txt_busqueda).'*'.$Pun_Cod.'*'.$row_rs_apercaja['Caj_Fec'], $obBD_conexion->conexion);		
		$rs_buscar = $obBD_con1->getArrayConsulta(1227, trim($txt_busqueda).'*'.$Pun_Cod, $obBD_conexion->conexion);
	}
 
}
else
{		
	if (isset($codigo))
	{
		/**
		* Consulta datos de los clientes
		*/
		$rs_guiaRemi = $obBD_con1->getArrayConsulta(37, $codigo, $obBD_conexion);
		$cliente = $rs_guiaRemi[0]['Vet_Cod'];
		$codAutoriza=$rs_guiaRemi[0]['Aut_Cod'];
		$Comprobante = $rs_guiaRemi[0]['Tic_Cod'];
				
	}//Fin del if (isset($codigo))
}//Fin del if ($txt_busqueda != "")	

if(isset($ajax_factura))
{
	$param=explode("-",$Vet_Num);
	$rs_buscar_fac = $obBD_con1->getRowConsulta(1267, $param[0].'*'.$param[1].'*'.(int)$param[2].'*'.$Suc_Cod, $obBD_conexion);
	$rs_guiaRemi = $obBD_con1->getArrayConsulta(37, $rs_buscar_fac['Vet_Cod'], $obBD_conexion);
	
?>	
    <FIELDSET>
	<LEGEND>
	<label class="Titulos2">Datos de la factura</label>
	</LEGEND>
	<table width="100%" border="0" cellpadding="0" cellspacing="0">
	  <tr>
	    <td width="14%" class="Etiqueta1">No. Documento:</td>
	    <td width="13%" class="LetraNegra">&nbsp;<input name="Vet_Num" id="Vet_Num" type="text"  size="13" value="<?php echo $Vet_Num;?>" maxlength="18" align="right" /></td>
	    <td colspan="4"><button type="button" name="button" id="button" class="btn btn-success btn-mini"  title="Buscar Factura" onclick="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_factura=1&Vet_Num='+ document.getElementById('Vet_Num').value +'&Suc_Cod=<?php echo $Suc_Cod;?>','div_factura')">
	      <i class="icon-search icon-white"></i> 
	      </button></td>
	    </tr>
	  <tr>
	    <td class="Etiqueta1">Fecha:</td>
	    <td class="LetraNegra">&nbsp;<input name="Vet_Fec" type="text" id="Vet_Fec" size="6" value="<?php echo $rs_buscar_fac['Caj_Fec']?>" style="border:none"/></td>
	    <td width="6%" class="LetraNegra"><span class="Etiqueta1">Autorizaci&oacute;n:</span></td>
	    <td width="66%" colspan="3" class="LetraNegra"><input name="Vet_Aut" type="text" id="Vet_Aut" style="border:none" size="39" readonly="readonly" value="<?php echo $rs_buscar_fac['Vet_Sri']?>" /></td>
	    </tr>
	  <tr>
	    <td class="Etiqueta1">Cliente:</td>
	    <td colspan="5" class="LetraNegra">&nbsp;<input name="Cliente" type="text" id="Cliente" size="67" value="<?php echo $rs_buscar_fac['Prs_Ape'].' '.$rs_buscar_fac['Prs_Nom']?>" style="border:none" readonly="readonly"/></td>
	    </tr>
	  </table> 
      <input name="Vet_Cod" type="hidden" id="Vet_Cod" value="<?php echo $rs_buscar_fac['Vet_Cod']; ?>">
      <input name="TicSriVen" type="hidden" id="TicSriVen" value="01">
	  <input name="codigo" type="hidden" id="codigo" value="<?Php echo $codigo; ?>">   
	</FIELDSET>				
	  
    <FIELDSET>
    <LEGEND>
    <label class="Titulos2">Detalle de la Gu&iacute;a de Remisi&oacute;n</label>
    </LEGEND>    
      <table width="100%" border="0" cellpadding="0" cellspacing="0" > 
      <thead>   
        <tr class="Cabecera1" height="35">
            <th width="7%">C&oacute;digo</th>	  
            <th width="11%">Cantidad</th>
            <th width="58%">Descripci�n</th>
            <th width="19%">Marca</th>
            <th width="5%">&nbsp;</th>
            </tr>
        </thead>
        <tbody id="c_contenido">	
     <?php 
	    $contador=0;
	    foreach($rs_guiaRemi as $row_rs_guia)
		{
		$contador++;	
		$fila++;
		?>	
		<tr>
		  <td>
		  <input name="datos[<?Php echo $fila; ?>,1]" type="text" id="datos[<?Php echo $fila; ?>,1]" value="<?Php echo $row_rs_guia['Pro_Cod']?>" readonly="readonly" size="2">
           <input name="datos[<?Php echo $fila; ?>,2]" type="hidden" id="datos[<?Php echo $fila; ?>,2]" value="0" readonly="readonly" size="2">	     
		  </td>	          
          <td><input name="datos[<?Php echo $fila; ?>,3]" type="text" onblur="contar_pro_guia();" style="text-align:center" id="datos[<?Php echo $fila; ?>,3]" value="<?Php echo $row_rs_guia['Vet_Can']?>" size="10">
          </td>		  
		  <td ><input name="datos[<?Php echo $fila; ?>,4]" type="text" id="datos[<?Php echo $fila; ?>,4]" value="<?Php echo $row_rs_guia['Ite_Lar']?>" size="80" readonly="true"></td>		            
		  <td align="center"><input name="datos[<?Php echo $fila; ?>,5]" type="text" id="datos[<?Php echo $fila; ?>,5]" value="<?Php echo $row_rs_guia['Mar_Des']?>" size="30" readonly="true">      
		  </td>   
		  <td align="center">
	    <input id="quitar_fila[<?Php echo $fila; ?>]" type="button" class="BotonEliminar" name="quitar_fila[<?Php echo $fila; ?>]" value="X" onClick="quitar_fila(this, <?Php echo count($rs_interes); ?>);"><!-- antes val_fac_fila    quitar_fila_mod !-->	  	    	                
	      </td> 
		</tr>    
		<?php }//Fin del foreach cliente ?>
        </tbody>  
        <tr class="Cabecera1" height="30" id="tr_total">
            <td align="center">Total:</td>
            <td align="left"><input type="text" style="background:#CCC; text-align:center" id="total_pro" readonly="readonly" name="total_pro" value="<?php echo $contador;?>" size="10"  /></td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
        </tr> 
       </table>            	        
      <input id="nfilas" name="nfilas" type="hidden" value="<?php echo $fila; ?>">
      <input id="nfilas_elim" name="nfilas_elim" type="hidden" value="">
      <br><br><br>     
    </FIELDSET>
<?php    
 exit();	
}


/*
*  Buscar Autorizacion
*/
$rs_autorizacion = $obBD_con1->getRowConsulta(1262,$Ses_Prs_Cod, $obBD_conexion);
$total_rs_autorizacion=$rs_autorizacion['Aut_Cod'] > 0? 1 : 0;

$rs_config_factura = $obBD_con1->getRowConsulta(1264,$Ses_Suc_Cod, $obBD_conexion);
if($rs_config_factura['Cof_Gce']=='S')
{
	$autNumSri= "Comprobante Electr&oacute;nico";
}else{
	$autNumSri=	$rs_autorizacion['Aut_Sri'];
}

/*
*  Buscar el maximo numero generado de laguia remision
*/
$rs_maxGuiaRemi = $obBD_con1->getRowConsulta(1263,$rs_autorizacion['Aut_Sri'], $obBD_conexion);
$total_rs_maxGuiaRemi=$rs_maxGuiaRemi['Gui_Num'] > 0? 1 : 0;
if($total_rs_maxGuiaRemi!=0)
{
	$numGuiaRemi= $rs_maxGuiaRemi['Gui_Num']+1;
}else{
	$numGuiaRemi=1; 
}


?>
<HTML><HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
		<?Php require_once("../../mascaras/model1/estilos/estilos.php"); ?>	
		<script type="text/javascript" src="../../Librerias/validaciones/validacion.js"></script>
    	<script type="text/javascript" src="../VALIDACIONES/fac_val_guia_remi.js"></script>
        <!--Librerias para interfaz -->       
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script> 
        <!--Librerias para modal -->       
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.modals.js"></script> 
        <script type="text/javascript" src="../../Librerias/masked/jquery.maskedinput-1.2.2.js"></script>        
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.datepicker.js"></script>
        <script type="text/javascript"> 
        function validaCadena(e)
		{	
			key = e.keyCode || e.which;						
			especial=['45','42','32'];
			aux=true;
			for(var i in especial)
			{		
				if(key==especial[i])
				{					
					aux=false;	
				}
			}
			if(!aux)
			{
				return false;	
			}
		}
		</script>
		<script type="text/javascript"> 
        $(function() {
			$('#set1 *').tooltip({showURL: false});
		}); 
		
		$(function() { 			
			$( "#Gui_Fsa" ).datepicker({  
				changeMonth: true, changeYear: true,
				/* Permite asignar una imagen */
				/*showOn: "button", buttonImage: imagen, buttonImageOnly: true,*/ dateFormat: "yy-mm-dd"});	
			$( "#Gui_Far" ).datepicker({
				changeMonth: true, changeYear: true,
				/* Permite asignar una imagen */
				/*showOn: "button", buttonImage: imagen, buttonImageOnly: true,*/ dateFormat: "yy-mm-dd"});		
		}); 
									
		/**
		* Control de mascaras
		*/
		jQuery(function($){
			$("#Vet_Num").mask("999-999-999999999",{placeholder:"_"});			
		});	
				
		</script>
        
		<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">  
	</HEAD>
<BODY>
<div id="set1">
<?php if (isset($Gui_Cod)){
	    /**
		* Consulta del reporte para impresion 
		*/
		$pagina = $_SERVER['PHP_SELF'];
		$reportes = $obBD_con1->reportes($pagina, $Ses_Emp_Cod, $obBD_conexion);
?>
<script type="text/javascript">windows('<?php echo $reportes[1];?>?Gui_Cod=<?Php echo $Gui_Cod;?>','', 800,600,'yes', 'yes', 'yes', 'no');</script>
<?php }//Fin del if (isset($hdd_save) && !isset($hdd_volver))?>

<?php 
if($total_rs_autorizacion!=0)
{
  if($numGuiaRemi<=$rs_autorizacion['Aut_Fin'])
  {
?>
 
 <table width="100%" border="0" cellpadding="0" cellspacing="0" class="table">
 <tr class="BarraTitulo">
  <td width="45%"><span>&raquo;</span> Registrar Gu&iacute;a de Remisi&oacute;n</td>
  <td width="39%"> &raquo;<strong>PUNTO DE IMPRESION:</strong> <?Php echo $rs_autorizacion['Pun_Des']; ?></td>
  <td width="16%" align="right">&nbsp;</td>
 </tr>   
 <tr>
    <td height="400" colspan="3" valign="top">
       

<form action="<?Php echo $_SERVER['PHP_SELF']; ?>" method="post" name="form2" id="form2">

	<table width="100%" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td colspan="2">
        <FIELDSET>
        <LEGEND>
        <label class="Titulos2">Datos del Destinatario </label>
        </LEGEND>	
		<div id="destinatario">
        <table width="100%" border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td width="30%" class="Etiqueta1"><span class="Asterisco">*</span>&nbsp;C&eacute;dula/R.U.C.:</td>
            <td width="20%" align="left" class="LetraNegra">&nbsp;<input name="Prs_Ced" type="text" id="Prs_Ced" size="10" maxlength="13"/></td>
            <td colspan="2">&nbsp;<button type="button" name="button" id="button" class="btn btn-success btn-mini" title="Buscar Destinatario" onclick="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_desti=1&Prs_Ced='+ document.getElementById('Prs_Ced').value +'&Emp_Cod=<?php echo $Ses_Emp_Cod;?>&op=1&titulo=<?php echo 'Destinatario';?>','destinatario')">
              <i class="icon-search icon-white"></i> 
            </button></td>
            </tr>
          <tr>
            <td class="Etiqueta1">Nombre/Raz&oacute;n Social:</td>
            <td colspan="3" class="LetraNegra">&nbsp;<input name="PrsNom" type="text" id="PrsNom" style="border:none" size="44" readonly="readonly" /></td>
            </tr>
          <tr>
            <td class="Etiqueta1">C&oacute;d. Establecimiento:</td>
            <td align="left" class="LetraNegra">&nbsp;<input name="Des_Sri" type="text" id="Des_Sri" style="border:none" size="10" maxlength="13" readonly="readonly" /></td>
            <td width="16%" class="Etiqueta1">C&oacute;digo Aduana:</td>
            <td width="34%" align="left" class="LetraNegra">&nbsp;<input name="Des_Adu" type="text" id="Des_Adu" style="border:none" size="17" maxlength="13" readonly="readonly" /></td>
            </tr>
          <tr>
            <td class="Etiqueta1"><span class="Asterisco">* </span>Direcci&oacute;n de Llegada:</td>
            <td colspan="3" align="left" class="LetraNegra">&nbsp;<input name="Prs_Dir" type="text" id="Prs_Dir" style="text-transform:uppercase" size="44" /></td>
            </tr>
          <tr>
            <td class="Etiqueta1"><span class="Asterisco">* </span>Motivo:</td>
            <td colspan="3" align="left" class="LetraNegra">&nbsp;<input name="Gui_Mot" type="text" id="Gui_Mot" size="44" /></td>
            </tr>
        </table>
        </div>
        </FIELDSET>
        </td>
        <td width="49%">
        <FIELDSET>
        <LEGEND>
        <label class="Titulos2">Datos del Transporte </label>
        </LEGEND>	
		<div id="transporte">
        <table width="100%" border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td width="31%" class="Etiqueta1"><span class="Asterisco">* </span>C&eacute;dula/R.U.C.:</td>
            <td width="11%"><span class="LetraNegra">
              <input name="PrsCed" type="text" id="PrsCed" size="10" maxlength="13" />
            </span></td>
            <td width="58%">&nbsp;&nbsp;<button type="button" name="button" id="button" class="btn btn-success btn-mini" title="Ver detalle" onclick="ajax_datos('<?Php echo $_SERVER['PHP_SELF'];?>?ajax_trans=1&Prs_Ced='+ document.getElementById('PrsCed').value +'&Emp_Cod=<?php echo $Ses_Emp_Cod;?>&op=2&titulo=<?php echo 'Transportista';?>','transporte')">
              <i class="icon-search icon-white"></i>
            </button></td>
            </tr>
          <tr>
            <td class="Etiqueta1">Nombre/Raz&oacute;n Social:</td>
            <td colspan="2"><span class="LetraNegra">
              <input name="TraNom" type="text" id="TraNom" style="border:none" size="44" readonly="readonly" />
            </span></td>
            </tr>
          <tr>
            <td class="Etiqueta1"><span class="Asterisco">*</span> Direcci&oacute;n de Salida:</td>
            <td colspan="2"><span class="LetraNegra">
              <input name="TraDir" type="text" id="TraDir" size="44" />
            </span></td>
            </tr>
          <tr>
            <td class="Etiqueta1"><span class="Asterisco">*</span> Placa:</td>
            <td><input name="Gui_Pla" type="text" id="Gui_Pla" style="text-transform:uppercase" size="10" onpaste="return false" onkeypress="return validaCadena(event);"/></td>
            <td><table width="100%" border="0" cellspacing="0" cellpadding="0">
              <tr>
                <td width="16%" class="Etiqueta1"><span class="Asterisco">*</span> Salida:</td>
                <td width="26%"><span class="LetraNegra">
                  <input name="Gui_Fsa" type="text" id="Gui_Fsa" size="6" onKeyUp="mascara(this,'-',patron,true)" onBlur="validar_fecha2(this);" />
                </span>                                          
                </td>
                <td width="19%" class="Etiqueta1"><span class="Asterisco">*</span> Llegada:</td>
                <td width="39%"><span class="LetraNegra">
                  <input name="Gui_Far" type="text" id="Gui_Far" size="6" onKeyUp="mascara(this,'-',patron,true)" onBlur="validar_fecha2(this);" />
                </span></td>
              </tr>
            </table></td>
          </tr>
          <tr>
            <td class="Etiqueta1"><span class="Asterisco">*</span> Ruta de Traslado:</td>
            <td colspan="2"><span class="LetraNegra">
              <input name="Gui_Rut" type="text" id="Gui_Rut" style="text-transform:uppercase" size="44" />
            </span></td>
          </tr>
        </table>
        </div>
        </FIELDSET>
        </td>
      </tr>
      </table>


<?php
/**
* Creacion del campo repost 
*/
$thisPost->startPost();
?>    


<FIELDSET>
<LEGEND>
<label class="Titulos2">Gu&iacute;as de Remisi&oacute;n </label>
</LEGEND>	
    <FIELDSET>
    <LEGEND>
    <label class="Titulos2">Datos de la Gu&iacute;a de Remisi&oacute;n </label>
    </LEGEND>
    <?php  ?>
    <table width="100%" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td width="14%" class="Etiqueta1"><span class="Asterisco">*</span> No. Documento: </td>
        <td width="15%" class="LetraNegra">&nbsp;<input name="Gui_Num" type="text" id="Gui_Num" size="10" maxlength="13" style="text-align:right" value="<?php echo $numGuiaRemi;?>" /></td>
        <td width="11%" class="Etiqueta1">Fecha Emisi&oacute;n:</td>
        <td width="10%" class="LetraNegra">&nbsp;
          <?php echo date("Y-m-d");?>
          <input type="hidden" id="Gui_Fec" name="Gui_Fec" value="<?php echo date("d-m-Y")?>" /></td>
        <td width="12%"class="Etiqueta1">Autorizaci&oacute;n:</td>
        <td width="38%" class="LetraNegra">&nbsp;
          <?php echo $autNumSri;?>
          <input type="hidden" id="Tic_Cod" name="Tic_Cod" value="<?php echo $rs_autorizacion['Tic_Cod']?>" />
          <input type="hidden" id="Aut_Cod" name="Aut_Cod" value="<?php echo $rs_autorizacion['Aut_Cod']?>" />
          </td>
      </tr>
      </table>
    
    </FIELDSET>

    <div id="div_factura">
    <FIELDSET>
	<LEGEND>
	<label class="Titulos2">Datos de la factura</label>
	</LEGEND>
	<table width="100%" border="0" cellpadding="0" cellspacing="0">
	  <tr>
	    <td width="14%" class="Etiqueta1">No. Documento:</td>
	    <td width="13%" class="LetraNegra">&nbsp;<input name="Vet_Num" id="Vet_Num" type="text"  size="13" maxlength="15" align="right" /></td>
	    <td colspan="4"><button type="button" name="button" id="button" class="btn btn-success btn-mini" title="Buscar Factura" onclick="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_factura=1&Vet_Num='+ document.getElementById('Vet_Num').value +'&Suc_Cod=<?php echo $Ses_Suc_Cod;?>','div_factura')">
	      <i class="icon-search icon-white"></i> 
	      </button></td>
	    </tr>
	  <tr>
	    <td class="Etiqueta1">Fecha:</td>
	    <td class="LetraNegra">&nbsp;<input name="Vet_Fec" type="text" id="Vet_Fec" size="6" onkeyup="mascara(this,'-',patron,true)" onblur="validar_fecha2(this);" style="border:none" readonly="readonly"/></td>
	    <td width="6%" class="LetraNegra"><span class="Etiqueta1">Autorizaci&oacute;n:</span></td>
	    <td width="66%" colspan="3" class="LetraNegra"><input name="Vet_Aut" type="text" id="Vet_Aut" style="border:none" size="39" readonly="readonly" /></td>
	    </tr>
	  <tr>
	    <td class="Etiqueta1">Cliente:</td>
	    <td colspan="5" class="LetraNegra">&nbsp;<input name="CliNom" type="text" id="CliNom" size="67" style="border:none" readonly="readonly"/></td>
	    </tr>
	  </table> 
      <input name="Vet_Cod" type="hidden" id="Vet_Cod" value="<?php echo $rs_guiaRemi[0]['Vet_Cod']; ?>">
	  <input name="codigo" type="hidden" id="codigo" value="<?Php echo $codigo; ?>">   
	</FIELDSET>				
	  
    <FIELDSET>
    <LEGEND>
    <label class="Titulos2">Detalle de la Gu&iacute;a de Remisi&oacute;n</label>
    </LEGEND>    
      <table width="100%" border="0" cellpadding="0" cellspacing="0" > 
      <thead>   
        <tr class="Cabecera1" height="35">
            <th width="7%">C&oacute;digo</th>	  
            <th width="11%">Cantidad</th>
            <th width="58%">Descripci�n</th>
            <th width="19%">Marca</th>
            <th width="5%">&nbsp;</th>
            </tr>
        </thead>
        <tbody id="c_contenido">	
        
        </tbody>  
        <tr class="Cabecera1" height="30" id="tr_total">
            <td align="center">Total:</td>
            <td align="left">
            <input type="text" style="background:#CCC; text-align:center" id="total_pro" readonly="readonly" name="total_pro" value="" size="10"  /></td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
        </tr> 
       </table>
    
       <script type="text/javascript">ShowHide('tr_total');</script>  	
        
      <input id="nfilas" name="nfilas" type="hidden" value="<?php echo $fila; ?>">
      <input id="nfilas_elim" name="nfilas_elim" type="hidden" value="">
      <br><br><br>
      
    </FIELDSET>
</div>
<table width="18%" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td width="47%">
        <button type="button" name="button2" id="button2" class="btn btn-success fileinput-button" title="Buscar Item" onclick="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_rubro=1&ajax_Cli_Cod=<?php echo $codigo_cli; ?>','ajax_modal');"><i class="icon-plus icon-white"></i><span>Items</span></button>
        </td>
        <td width="53%">&nbsp;</td>
      </tr>
    </table>
</fieldset>
<br>
<table width="303" border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td width="106">&nbsp;
    <button type="button" class="btn btn-inverse fileinput-button" title="Atr�s" onClick="campos_hide(this.form, '<?Php echo "txt_busqueda*op_opciones*hdd_volver*cmb_anio*cmb_mes*Tic_Cod"; ?>', '<?Php echo $volver_busqueda.'*'.$volver_op.'*1*'.$volver_anio.'*'.$volver_mes.'*'.$Tic_Cod; ?>')">
                    <i class=" icon-arrow-left icon-white"></i>
                    <span>&nbsp;&nbsp;Atr&aacute;s&nbsp;&nbsp;</span>
       </button>
    </td>
    <td width="197">
    <button type="button" class="btn btn-primary start" title="Guardar" onclick="this.form.submit();">
           <i class="icon-book icon-white"></i>
           <span>Guardar</span>
    </button>             
      <input name="hdd_save" type="hidden" id="hdd_save" value="insertar">
      <input name="txt_busqueda" id="txt_busqueda" type="hidden" value="<?Php echo $volver_busqueda;?>">
      <input name="op_opciones" id="op_opciones" type="hidden" value="<?Php echo $volver_op;?>">
      <input name="cmb_anio" id="volver_anio" type="hidden" value="<?Php echo $volver_anio;?>">				
      <input name="cmb_mes" id="cmb_mes" type="hidden" value="<?Php echo $volver_mes;?>">
      <input name="totPro" id="totPro" type="hidden" value="<?Php echo count($rs_guiaRemi);?>">            
      </td>
  </tr>
</table>
<br>
<div id="bgtransparent" class="bgtransparent" style="display:none" onclick="closeModal()">
</div>
<div id="bgmodal"  class="bgmodal"  style="display:none">		
	<div id="ajax_modal"></div>
</div>
</form>
  </td>
  </tr>  
</table>
<?php 
  }else{  
	 echo error_alerta("&iexcl;No se puede generar el Documento: [".$numGuiaRemi."], la Autorizaci�n [".$rs_autorizacion['Aut_Sri']."] permite comprobantes de retenci&oacute;n entre [".$rs_autorizacion['Aut_Ini']."] y [".$rs_autorizacion['Aut_Fin']."]!", 2); 
  } //if($numGuiaRemi<=$rs_autorizacion['Aut_Fin'])
}else{
	 echo error_alerta(" No existen autorizaciones para GU&Iacute;AS DE REMISI&Oacute;N otorgadas por SRI, activas", 2);
 }//if($total_rs_autorizacion!=0)?>
</div>

<!-- Librerias para el tratamiento de la interfaz - cajas de texto -->
<script type="text/javascript" src="../VALIDACIONES/fac_par_guia_remi.js"></script>
<script type="text/javascript" src="../../Librerias/textbox/main.js"></script>
</BODY>
</HTML>
<?php
@$obBD_conexion->cerrar();
?>