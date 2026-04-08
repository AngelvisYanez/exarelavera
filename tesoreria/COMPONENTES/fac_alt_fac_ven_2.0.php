<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php 
/**
* Descripción: Alta de facturas de ventas
* Fecha de actualización:	2010-06-21 
* Desarrollador:	Jose Cumbicos
* Fecha de actualización: 2011-06-09
* Desarrollador: Nebil Oyola
* Fecha de actualización: 2012-02-17
* Desarrollador: Lewis Chimarro
* Fecha de actualizacion: 2014-06-03
* Desarrollador Jose Cumbicos
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_fac_ven.php');
require_once('../LOGICA/fac_log_deudas.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require_once('../../Librerias/postclass.php');	

/** 
* Creacion del Objeto de conexion 
*/
$obBD_conexion = new Class_Log_Conexion_Tes($Ses_Dat_Dis);
/**
* Cracion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Tes;	  
/** 
* Evitar el reenvio de datos 
*/
$thisPost = new Post_Block;

$hoy = date("Y-m-d");
$hora = date("H:i:s");
/**
* Declaracion de constante Tipo de comprobante - Factura
*/
//if (!isset($Tic_Cod))
//{ $Tic_Cod = 1; }

/** 
* Cargado del buscador de rubros 
*/
if (isset($ajax_rubro))
{ 
	/**
	* Carreras que el estudiante ha cursado 
	*/
	$row_rs_carrera = $obBD_con1->getArrayConsulta(78, $ajax_Cli_Cod.'*'.$Ses_Emp_Cod, $obBD_conexion);
?>
	<table width="100%" border="0" cellpadding="0" cellspacing="0" id="rubros_table">
      <tr>
        <td><FIELDSET>
          <LEGEND>
          <label class="Titulos2">B&uacute;squeda del item</label>
          </LEGEND>
          <?Php
		  if (count($row_rs_carrera) > 0)
		  {
			  /**
			  * Inicializa la variable para el cargado automatico de la matricula activa
			  */	
			  $row = current($row_rs_carrera);
			  $Sem_Cod = $row['Sem_Cod'];
			  $car = $row['Car_Int'];	
			  $codigo = $ajax_Cli_Cod; 
		  ?>
          <table width="550" border="0" cellpadding="0" cellspacing="0">
            <tr>
              <td width="64" align="right"><strong>Carrera:</strong></td>
              <td width="100" colspan="3">
			  <select name="Car_Int" id="Car_Int" onChange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?sem&codigo=<?php echo $ajax_Cli_Cod; ?>&car=' + Car_Int.value, 'busqueda_semestre')">
              <?Php
			  /** 
			  * Muestra un item mas cuando hay mas de 1 registro
			  */
			  if (count($row_rs_carrera) > 1)
			  {
			  ?>
                  <option value="">Seleccione...</option>
              <?Php
			  }

				  foreach ($row_rs_carrera as $row)
				  { ?>
                  <option value="<?php echo $row['Car_Int'];?>"> <?php echo $row['Car_Nom'];?> </option>
                 <?php 
				  } //Fin del foreach -> $row_rs_carrera ?>
              </select>
              &nbsp;</td>
            </tr>
          </table>
          <br />
          <?Php
		  } //Fin del if ($total_rs_carrera > 0)
		  ?>
          <div id="busqueda_semestre">
			<?php
			/**
			* Carga el buscador cuando el cliente no tiene carreras, o cuando posee solo una
			*/
			if (count($row_rs_carrera) <= 1)
    		{  
			include("../../tesoreria/COMPONENTES/tesComRubrosFac2.php"); }
			?>	            
          </div>
        </FIELDSET></td>
      </tr>
    </table>
<?php		
exit();	
}
/**
* Ajax que permite saber si escojio el tipo de Comprobante 
*/
if($ajax_mar_val==1)
{   
	/** 
	* Consulto el numero de autorizacion dependiendo el tipo y el punto de impresion 
	*/
	$row_rs_autorizaci = $obBD_con1->getRowConsulta(30, $Tic_Cod.'*'.$punto.'*'.$hoy, $obBD_conexion);
	
	if(count($row_rs_autorizaci) > 0)
	{
		$Vet_Num = $obBD_con1->codigoSiguiente($row_rs_autorizaci['Aut_Cod'], $row_rs_autorizaci['Aut_Ini'], $obBD_conexion);	
		echo  "<table width='100%' border='0' cellpadding='0' cellspacing='0'>
			 <tr>
			   <td><span class='Etiqueta1'><span class='Asterisco'>* </span>No Fact</span></td>
			   <td><input name='Vet_Num' type='text' id='Vet_Num' size='7' maxlength='7' value='".$Vet_Num."' style='text-align:right' onKeyPress='return validar_numeric(event)' readonly='true'>
			         <input name='Aut_Cod' type='hidden' id='Aut_Cod' value='".$row_rs_autorizaci['Aut_Cod']."'>
			   
			   </td>

			   <td><span class='Etiqueta1'>Autorizaci&oacute;n:</span></td>
			   <td align='left'>".$row_rs_autorizaci['Aut_Sri']."</td>		
			 </tr>
		   </table>";
	}
	else
	{
		echo "<span class='Alertas3'>No existes registro de autorización para el tipo de comprobante</span>";
	}
	exit();
}
/**
* Valida si un numero de papeleta esta repetido
*/
if(isset($docBanco))
{	
	if($num_Doc!="")
	{	
		$row_rs_numDocumento = $obBD_con1->getRowConsulta(990, $num_Doc.'*'.$Ses_Emp_Cod, $obBD_conexion); 
				
		if(count($row_rs_numDocumento) !=0)
		{	?><span class="Alertas3">&nbsp;<img src="../../mascaras/model1/imagenes/32x32/gtk-no.gif" width="20" height="20" type="image"/><?Php
			echo "&nbsp;&iexcl;Ya existe el n&uacute;mero de documento!"; ?></span><?Php	
		}else{
			?>&nbsp;<img src="../../mascaras/model1/imagenes/ok-s.gif"><?
		}
    }
	exit();
}
/** 
* Cargado AJAX de los tipos de pago 
*/
if (isset($cmb))
{
	$row_rs_facttipo = $obBD_con1->getArrayConsulta(17, $For_Cod, $obBD_conexion); 
	$facttipo = current($row_rs_facttipo);
	$Pag = $facttipo['Pag_Cod'];	
	?>	
	<input type="hidden" id="add_auxi" value="<?Php echo $row_rs_facttipo['Pag_Cod']; ?>">
	<select name="<?php echo $nom_pag; ?>" id="<?php echo $nom_pag; ?>" onChange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?cmb_tipo=1&Pag_Cod=' + this.value + '&nom_ban=<?php echo $nom_ban; ?>','<?php echo $div_banco; ?>')">
    	<? foreach($row_rs_facttipo as $row)
		{ ?>
    		<option  value="<?Php echo $row['Pag_Cod']; ?>"><?PHP echo $row['Pag_Des']; ?></option>
    	<?PHP 
		} //Fin del $row_rs_facttipo  ?>
    </select>
	<?php //echo $div_banco; ?>
	<script language="javascript">
		ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?cmb_tipo=1&Pag_Cod=<?Php echo $Pag; ?>', '<?php echo $div_banco; ?>');
	</script>	
	<?Php
	exit();
}//Fin del if (isset($cmb))
/**
* Cargado AJAX de los tipos de pago - banco 1-2 
*/
if (isset($cmb_tipo))
{
	/** 
	* Bancos correspondientes al plan de cuentas 
	*/
	$row_rs_bancos = $obBD_con1->getArrayConsulta(179, $Pag_Cod.'*'.$Ses_Emp_Cod, $obBD_conexion);
	?>
    <select name="<?Php echo $nom_ban; ?>" id="<?Php echo $nom_ban; ?>">
	   <option value="NULL">(Ninguno)</option>
	   <? 
	   foreach ($row_rs_bancos as $row)
	   {?>
			<option value="<?php echo $row['Ban_Cod']?>"><?php echo $row['Pld_Des']?></option>
	   <? 
	   } //Fin del $row_rs_bancos ?>
	</select>	
	<? 
	exit();
}//Fin del if (isset($cmb))
/** 
* Cargado AJAX de los resultados de la búsqueda del semestre 
*/
if (isset($sem))
{ 
   $hoy=$hoy;
   $codigo=$codigo;
   $car=$car;
   include("../../tesoreria/COMPONENTES/tesComRubrosFac2.php");
exit();
}//Fin del if (isset($sem))

/**
* Cargado AJAX de los resultados de la búsqueda
*/
if (isset($deudas))
{ 	
	$Com_Codigo = $ajax_Cli_Cod; 
	$Com_Tipo = 1;
?>	
	<?php include("../COMPONENTES/tes_com_deudas.php");?>	
<?
  exit();
}// Cierre de las deudas de los estudiantes 

/**
* Cargado Ajax de la proyección del Interes 
*/
if (isset($Ajax_Proye))
{
	/** 
	* Variables para el control del componente de interes 
	*/
	$Com_Cli_Cod = $Ajax_Cli_Cod;
	$Com_Pro_Cod = $Ajax_Pro_Cod; 	
	$Com_Nge_Cod = $Ajax_Nge_Cod;
	$Com_Asi_Int = $Ajax_Asi_Int;
	$Com_Saldo = $Ajax_Saldo;
	$Com_Int_Fec = $Ajax_Int_Fec;
	$Com_Saldo_Int = $Ajax_Saldo_Int;
	/**
	* Llamado del componente del interes 
	*/
	include("../COMPONENTES/tes_com_proyeccion.php");  
	exit();
}//Fin del if (isset($Ajax_Proye))

/** 
* Cargado AJAX de los resultados de la búsqueda del rubro 
*/
if (isset($buscod))
{
	$busqueda = $busqueda;
	$codigo = $codigo;
	$Sem_Cod = $Sem_Cod;
	include("../COMPONENTES/tesComRubrosConsulta2.php");
	exit();	
}//if (isset($buscod))

/**
* Consulta del vendedor en base al codigo de la persona
*/
$row_rs_vendedor = $obBD_con1->getRowConsulta(24, $Ses_Prs_Cod.'*'.$Ses_Suc_Cod,$obBD_conexion);
/**
* Consulta de la caja activa en base al vendedor
*/
$row_rs_apercaja = $obBD_con1->getRowConsulta(25, $row_rs_vendedor['Pun_Cod'],$obBD_conexion);
/**
* Consulta de la autorizacion para las facturas 
*/
$row_rs_autorizaci = $obBD_con1->getRowConsulta(30, $Tic_Cod.'*'.$row_rs_apercaja['Pun_Cod'].'*'.$hoy, $obBD_conexion);
$total_rs_autorizaci=$row_rs_autorizaci['Aut_Cod'] > 0? 1 : 0;

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
		/*
		* Inicio de la transaccion
		*/
		$obBD_ins1->inicio_transaccion($obBD_conexion->conexion);
		/**
		* Inserción de la cabecera de la factura 
		*/
		$obBD_ins1->operacionobBD(20, $Tic_Cod.'*'.$codigo.'*'.$Ciu_Cod.'*'.$Caj_Cod.'*'.$Vnd_Cod.'*'.	
		$Vet_Num.'*'.$Vet_Obs.'*'.$Aut_Cod.'*'.$Vet_Des.'*'.$hora, $obBD_conexion);	
		$Vet_Cod = $obBD_ins1->insercionid ($obBD_conexion->conexion);		
		/**
		* Insercion de los tipos de pago	
		* Tipo de pago 1	
		*/
		$Bak_Cod = 1; //Variable siempre fija en 1 porque no es usa otros bancos
		$obBD_ins1->operacionobBD(315, $Vet_Cod.'*'.$Bak_Cod.'*'.$Ban_Cod.'*'.$Pag_Cod.'*'.$Vet_Cue.'*'.$Vet_Che.'*'.$Vet_Tot.'*'.'1', $obBD_conexion);	
		/** 
		* Tipo de pago 2 
		*/
		if (isset($detalle))
		{
		$Bak_Cod2 = 1; //Variable siempre fija en 1 porque no es usa otros bancos			
		$obBD_ins1->operacionobBD(315, $Vet_Cod.'*'.$Bak_Cod2.'*'.$Ban_Cod2.'*'.$Pag_Cod2.'*'.$Vet_Cue2.'*'.$Vet_Che2.'*'.$Vet_Tot2.'*'.'2', $obBD_conexion);	
		}	
		/**
		* Recorrido del arreglo del detalle de la factura
		*/	
		foreach ($datos as $puntero => $item)
		{
			$cant++;
			$param[]=$item;
		
			if ($cant==14)
			{
				$cant=0;
				/**
				* Notas generales 
				*/
				if ($param[9] == "")
				{ $param[9] = 0; }								
				/**
				* Codigo de la asignatura 
				*/
				if ($param[11] == "")
				{ $param[11] = 0; }				
				/**
				* Codigo de la recursividad 
				*/
				if ($param[10] == "")
				{ $param[10] = 0; }		
				
				/**
				* Inserta el detalle de la venta, junto con notasgenet, contrato e indice
				*/				
				$obBD_ins1->operacionobBD(5, $Vet_Cod.'*'.$param[0].'*'.$param[2].'*'.$param[8].'*'.$param	
				[4].'*'.$param[5].'*'.$param[6].'*'.$param[9].'*'.$param[11].'*'.$param[10].'*'.$param[12].'*'.$param[13], $obBD_conexion);
				
				/**
				* Control para I N V E N T A R I O S 
				*/
				$row_rs_adquisicio = $obBD_con1->getRowConsulta(1037, $param[0], $obBD_conexion);
				/** 
				* Pregunta si es de tipo bien el producto B 
				*/
				if (count($row_rs_adquisicio) <> 0)
				{
					/** 
					* Pregunta si descuento es vacio 
					*/
					if ($param[6]=="")
					{ $desc_var=0; }
					else
					{ $desc_var=$param[6]; }

					/** 
					* Actualiza el kardex 
					*/
					$obBD_ins1->operacionobBD(1072, $Vet_Cod.'*'.'0'.'*'.$Vnd_Cod.'*'.'0'.'*'.$param[0].'*'.$row_rs_apercaja['Caj_Fec'].'*'.$hora.'*'.'0'.'*'.$param[2].'*'.$param[4].'*'.'0'.'*'.$param[5].'*'.'0'.'*'.$desc_var.'*'.$param[8].'*'.'0', $obBD_conexion);										
					/** 
					* Consulta el Stock 
					*/
					$row_rs_conpro = $obBD_con1->getRowConsulta(1206, $param[0],$obBD_conexion);					
					/**
					* Actualizo el Stock 
					*/
					$obBD_ins1->operacionobBD(1204, $row_rs_conpro['Stock'].'*'.$param[0].'*'.$Ses_Suc_Cod, $obBD_conexion);
				}//FIn del if (count($row_rs_adquisicio) <> 0)
				/**
				* F I N Control para I N V E N T A R I O S
				*/
				unset($param);
			}
		}
		$obBD_ins1->fin_transaccion($obBD_conexion->conexion);
		/** 
		* Consulta del reporte para impresion 
		*/
		$pagina = $_SERVER['PHP_SELF'];
		$reportes = $obBD_con1->reportes($pagina, $Ses_Emp_Cod,$obBD_conexion);
		
	}//Fin del if (isset($hdd_save))
}//Fin del if ($thisPost->postBlock($_POST['postID']))

/** 
* Busqueda de los datos del cliente 
*/
if ($txt_busqueda != "")
{
	if ($op_opciones == "d")
	{
		/** 
		* Consulta del cliente en base al apellido 
		*/
		$rs_buscar = $obBD_con1->getArrayConsulta(21, trim($txt_busqueda).'*'.$Ses_Emp_Cod,$obBD_conexion);
	}
	else 
	{
		/** 
		* Consulta del cliente en base de la cedula 
		*/
		$rs_buscar = $obBD_con1->getArrayConsulta(22, trim($txt_busqueda).'*'.$Ses_Emp_Cod,$obBD_conexion);
	}  
}
else
{		
	if (isset($codigo))
	{
		/** 
		* Incremento del numero manual de la factura dependiendo del punto de impresión
		*/
		$Vet_Num = $obBD_con1->codigoSiguiente($row_rs_autorizaci['Aut_Cod'], $row_rs_autorizaci['Aut_Ini'], $obBD_conexion);		
		/**
		* Consulta datos de los clientes 
		*/
		$row_rs_cliente = $obBD_con1->getRowConsulta(23, $codigo, $obBD_conexion);
		/**
		* Consulta de la ciudad de emisión de la factura 
		*/
		$row_rs_ciudad = $obBD_con1->getRowConsulta(26, $Ses_Usu_Cod, $obBD_conexion);
		/**
		* FUNCION QUE CARGA AUTOMATICAMENTE LOS RUBROS  ojojojojojojojoj  
		*/
		$obBD_con1->generarDeudas($codigo, $obBD_conexion);	
	}//Fin del if (isset($codigo))	
}//Fin del if ($txt_busqueda != "")	
?>
<HTML><HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
		<?Php require_once("../../mascaras/model1/estilos/estilos.php"); ?>	
		<script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>
    	<script language="javascript" src="../VALIDACIONES/fac_val_fac_ven.js"></script>
        <!--Librerias para interfaz -->       
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script> 
        <!--Librerias para modal -->       
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.modals.js"></script> 
        <script type="text/javascript"> 
        $(function() {
			$('#set1 *').tooltip({showURL: false});
		});              			
		</script>
        
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">       
	</HEAD>
    <BODY <? if ($total_rs_vendedor > 0){if ($total_rs_apercaja > 0){if ($total_rs_autorizaci > 0){if ($Vet_Num <= $row_rs_autorizaci['Aut_Fin']){ /*if ($codigo > 0){ */ ?> onLoad="setInterval('parpadeo(\'txt_blink\')',700);"<? /*}*/}}}}?> >	
    <div id="set1">
<?Php //
/** 
* Impresion automatica de la factura 
*/ 
if (isset($hdd_save) && !isset($hdd_volver))
{ 
?>
<script language="javascript">windows('<?Php echo $hdd_reportes;  ?>?Vet_Cod=<?Php echo $Vet_Cod; ?>','', 800,600,'yes', 'yes', 'yes', 'no');
</script> 
<?Php
}//Fin del if (isset($hdd_save))
/**
* Evalua si el usuario es un vendedor 
*/
if (count($row_rs_vendedor) > 0)
{
	/** 
	* Evalua si existe una caja activa 
	*/
	if (count($row_rs_apercaja) > 0)
	{
		/**
		* Evalua si existe una autorizacion activa 
		*/
		if (count($row_rs_autorizaci) > 0 or 1==1)
		{
			/** 
			* Evalua si el numero de factura esta en el rango de la autorizacion activa 
			*/
			if ($Vet_Num <= $row_rs_autorizaci['Aut_Fin'])
			{ 
?>	
<table width="100%" border="0" cellpadding="0" cellspacing="0" class="table">
 <tr class="BarraTitulo">
  <td width="45%" height="10">&raquo; Registrar Facturas de Ventas </td>  
  <td width="36%">&raquo; <strong>PUNTO DE IMPRESION:</strong> <?Php echo $row_rs_apercaja['Pun_Des']; ?></td>
  <td width="19%" align="right">&raquo; <strong>CAJA: </strong><?Php echo $row_rs_apercaja['Caj_Fec']; ?></td>
 </tr>
<tr>
  <td colspan="3">
  <?
	$a=0;
	if($hora >="18:00:00")
	{
	 	$a++;
	 	$alertas[$a]="&iexcl;Por favor cerrar la caja!";	 		
	}
	
	if ($total_rs_autorizaci!=0)
	{	
		if($hoy >= fechas_futuras($row_rs_autorizaci['Aut_Cad'], -$row_rs_autorizaci['Aut_Adv']) and 2==1)
		{
			$a++;
			$alertas[$a]="&iexcl;La Autorizaci&oacute;n se caducar&aacute; en ".$row_rs_autorizaci['Aut_Cad']."&iexcl;";		
		}	
	}
	
	if(($Vet_Num + $row_rs_autorizaci['Aut_Ads']) >= $row_rs_autorizaci['Aut_Fin'] and 2==1)
	{
		$a++;
		$resta=($row_rs_autorizaci['Aut_Fin'] - $Vet_Num);
		$alertas[$a]="&iexcl;La Autorizaci&oacute;n le queda ".$resta." facturas disponibles&iexcl;";  				
	}
  ?>  
  <table>
  	<tr>	  
	   <td>
		<? 
		 	if($a!=0)
			{
				echo blink("ALERTA(S) :","txt_blink","#FFFFFF","#FF0000");
			}else{
				echo blink(" ","txt_blink","#FFFFFF","#FF0000");
			}
		?>
		</td>
		<? for($j=1;$j<=$a;$j++){?>
			<td class="Texto_Reporte_Rojo"><h3><? echo str_repeat("&nbsp;", 10)."* ".$alertas[$j];?></h3></td>
		<? }?>	  
	</tr>	
  </table>	
  </td>
</tr>
<tr>
   <td colspan="3" height="400" valign="top">	
	<form action="<? echo $_SERVER['../LOGICA/PHP_SELF']; ?>" method="post" name= "form1" id="form1">
		<?Php include("../../componentes/FRONT/com_con_persona.php"); ?>
    </form>
<?Php  
if(isset($txt_busqueda))
{
?>
  <br>
<FIELDSET>
<LEGEND>
<label class="Titulos2">Resultados de la busqueda</label>
</LEGEND>
	<table width="100%" border="1" cellpadding="0" cellspacing="0" class="fixedHeader01">
    <thead>
	  <tr>
	  <th width="8%">Cód. Int. </th>
          <th width="8%">Cédula/R.U.C.</th>
          <th>Clientes</th>
          <th width="4%">&nbsp;</th>
      </tr>
     </thead>
     <tbody> 
	  <?Php 
	if(count($rs_buscar) != 0)
	{	  
	  foreach($rs_buscar as $row_rs_buscar)
	  { ?>
	  <tr>	  
	  <td align="center"><?Php echo $row_rs_buscar['Cli_Cod']; ?></td>
		<td align="center"><?Php echo $row_rs_buscar['Prs_Ced']; ?></td>
		<td align="left">&nbsp;<?Php echo marcarCadenaColor($txt_busqueda,$row_rs_buscar['Prs_Ape'].' '.$row_rs_buscar['Prs_Nom'],'#FFFF00', '#000', 1); ?></td>
		<td align="center"><?Php if ($row_rs_buscar['Cli_Est'] == 'Activo') { ?>
        <form name="form3" id="form3" method="post" action="<?php echo $_SERVER['../LOGICA/PHP_SELF'] ?>">
		<input name="codigo" id="codigo" type="hidden" value="<?Php echo $row_rs_buscar['Cli_Cod'];?>">
		<input name="volver_busqueda" id="volver_busqueda" type="hidden" value="<?Php echo $txt_busqueda;?>">
		<input name="volver_op" id="volver_op" type="hidden" value="<?Php echo $op_opciones;?>">						
        <button type="button" class="btn btn-success btn-mini" title="Elegir" onclick="this.form.submit()">
        	<i class=" icon-arrow-right icon-white"></i>
        </button>
        </form>
	<? } else { echo "&nbsp;"; } ?>	  
	  </td>
	  </tr>
	  <?Php }//Fin del foreach
  	}//FIn del if($total_rs_buscar != 0)
	else
	{ ?>
		<tr><td>&nbsp;</td>
		  <td>&nbsp;</td>
		  <td><?Php echo error_alerta("No hay resultados que mostrar", 1) ?></td>
		  <td>&nbsp;</td>
		</tr>	   
	<?Php 
	}//Fin del else if($total_rs_buscar != 0) ?>
    </tbody>
  </table>
</FIELDSET>
<?php
	echo barra_estado(count($rs_buscar));
}//Fin del if(isset($txt_busqueda)) ?>

 <form action="<?Php $_SERVER['../LOGICA/PHP_SELF']; ?>" method="post" name="form2" id="form2">
 <?Php if ($codigo > 0 && !(isset($hdd_save)))
 { 
	/**
	* Creacion del campo repost 
	*/
	$thisPost->startPost();
 ?>
 <FIELDSET>
<LEGEND>
<label class="Titulos2">Datos del Cliente </label>
</LEGEND>
<table width="95%" border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td width="12%" class="Etiqueta1">Cédula/R.U.C.:</td>
    <td width="88%" class="LetraNegra">&nbsp;<?Php echo $row_rs_cliente['Prs_Ced'] ?>
      <input name="codigo" id="codigo" type="hidden" value="<?Php echo $codigo;?>"></td>
  </tr>
  <tr>	
	<td width="12%" class="Etiqueta1">Cliente:</td>
    <td class="LetraNegra">&nbsp;<?Php echo $row_rs_cliente['Prs_Ape'].' '.$row_rs_cliente['Prs_Nom'] ?></td>
  </tr>
  <tr>
    <td width="12%" class="Etiqueta1">Dirección:</td>
    <td colspan="3" class="LetraNegra">&nbsp;<?php echo $row_rs_cliente['Prs_Dir']?></td>
	</tr>
	<?Php 
	//por ahora no se utiliza
	if ($total_rs_cliente_esc > 0) 
	{?>
	<tr>	
		<td class="Etiqueta1">Curso:</td>
		<td colspan="3" class="LetraNegra"><?php if ($row_rs_cliente_esc['Sem_Des'] == ""){echo $row_rs_cliente_esc['Car_Nom']." [".$row_rs_cliente_esc['Niv_Des']." &#8220;".$row_rs_cliente_esc['Sem_Par']."&#8221; ".$row_rs_cliente_esc['Sem_Sec']."-".$row_rs_cliente_esc['Mod_Des']."]"; } else { echo $row_rs_cliente_esc['Niv_Des'].' '.$row_rs_cliente_esc['Sem_Des']; }?>
		</td>
	</tr>
	  <?php 
		} //Fin del if ($total_rs_cliente_esc > 0) ?>
	  </table>    
</FIELDSET>	
	<?Php
	if ($row_rs_cliente['Cli_Ruf'] != "")
	{
	?>
	<FIELDSET>
	<LEGEND>
	<label class="Titulos2">Datos del Representante</label>
	</LEGEND>
	 <table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr>
	  <td width="11%" class="Etiqueta1">R.U.C:</td>
	  <td width="89%" class="LetraNegra">&nbsp;<? echo $row_rs_cliente['Cli_Ruf']; ?></td>
	</tr>
	<tr>  
	  <td class="Etiqueta1">Representante:</td>
	  <td colspan="3" class="LetraNegra">&nbsp;<? echo $row_rs_cliente['Cli_Fac']; ?></td>
	</tr>
	<tr>
	  <td class="Etiqueta1">Direcci&oacute;n:</td>
	  <td colspan="5" class="LetraNegra">&nbsp;<? echo $row_rs_cliente['Cli_Dir']; ?></td>
	  </tr>
		</table>
  </FIELDSET>
	<?php }//Fin del if ($total_rs_representante > 0) ?>	
<FIELDSET>
<LEGEND>
<label class="Titulos2">Datos de la Factura </label>
</LEGEND>
<FIELDSET>
<LEGEND>
<label class="Titulos2"> Generales </label>
</LEGEND>
 
 <table width="95%" border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td height="22" class="Etiqueta1"><span class="Asterisco">* </span>Tipo  documento:</td>
    <td colspan="3" class="LetraNegra">
    <?Php
		/**
		* Consulta los tipos de comprobantes
		*/
		$row_tipo_compr = $obBD_con1->getArrayConsulta(1036, '', $obBD_conexion);	
	?>
    <select name="Tic_Cod" id="Tic_Cod" onChange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_mar_val=1&punto=<? echo $row_rs_apercaja['Pun_Cod']; ?>&Tic_Cod='+ document.getElementById('Tic_Cod').value +'&hoy<? echo $hoy; ?>','contenedormarca')">
    <option  value="">Seleccione...</option>
    <?Php
	foreach($row_tipo_compr as $row)
	{ ?>
      <option  <?Php if ($Tic_Cod == $row['Tic_Cod']){ echo "selected"; } ?> value="<?Php echo $row['Tic_Cod']; ?>"><?Php echo $row['Tic_Des']; ?></option>
	<?Php
	}
	?>
    </select></td>
    <td class="LetraNegra">&nbsp;</td>
    </tr>
  <tr>
    <td width="12%" height="22" class="Etiqueta1">Fecha: </td>
    <td width="17%" class="LetraNegra">&nbsp;<?php echo $row_rs_apercaja['Caj_Fec'] ?></td>
    <td width="7%" class="Etiqueta1">Ciudad:</td>
    <td width="15%" class="LetraNegra">&nbsp;<?Php echo $row_rs_ciudad['Ciu_Des']; ?>      
      <input name="Caj_Cod" type="hidden" id="Caj_Cod" value="<?php echo $row_rs_apercaja['Caj_Cod'] ?>">
      <input name="Ciu_Cod" type="hidden" id="Ciu_Cod" value="<?Php echo $row_rs_ciudad['Ciu_Cod']; ?>">
</td>
    <td width="49%" class="LetraNegra">
    <div id="contenedormarca">
    <table width="100%" border="0" cellpadding="0" cellspacing="0">
      <tr>
        <td width="25%" class="Etiqueta1"><span class="Asterisco">* </span>No Secuencia:</td>
        <td width="8%"><input name="Vet_Num" type="text" id="Vet_Num" size="7" maxlength="7" value="<?Php echo $Vet_Num; ?>" style="text-align:right" onKeyPress="return validar_numeric(event)" readonly="true">
              <input name="Aut_Cod" type="hidden" id="Aut_Cod" value="<?Php echo $row_rs_autorizaci['Aut_Cod']; ?>">
        </td>
        <td width="27%" class="Etiqueta1">Autorizaci&oacute;n:</td>
        <td width="40%" class="LetraNegra"><? echo $row_rs_autorizaci['Aut_Sri']?></td>
        </tr>
      </table>
      </div>
     </td>
  </tr>
  <tr>
    <td width="12%" class="Etiqueta1">Observaci&oacute;n:</td>
    <td height="60" colspan="4">
      <span class="LetraNegra">
        <input name="Vnd_Cod" type="hidden" id="Vnd_Cod" value="<?php echo $row_rs_vendedor['Vnd_Cod']; ?>">
        </span></label>&nbsp;
      <textarea name="Vet_Obs" cols="80" rows="3" id="Vet_Obs"></textarea>
      </td>
  </tr>
</table>
</FIELDSET>
<FIELDSET>
<LEGEND>
<label class="Titulos2"> Formas de Pago </label>
</LEGEND>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td width="10%" class="Etiqueta1"><span class="Asterisco">* </span>Forma:</td>
    <td width="12%" class="LetraNegra">
	<?Php 
	 /**
	 * Cargar la forma de pago
	 */
	 $rs_pago = $obBD_con1->getArrayConsulta(16, '', $obBD_conexion);
	/**
	* Cargar tipo de pago 
	*/
	$rs_facttipo = $obBD_con1->getArrayConsulta(17, $rs_pago[0]['For_Cod'], $obBD_conexion);
	?>
      <select name="For_Cod" id="For_Cod" onChange=" ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?cmb=1&For_Cod=' + this.value + '&div_banco=div_banco&nom_pag=Pag_Cod&nom_ban=Ban_Cod&chk_ban=chk_bancos','combo'); ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?cmb=1&For_Cod=' + this.value + '&div_banco=div_banco2&nom_pag=Pag_Cod2&nom_ban=Ban_Cod2&chk_ban=chk_bancos2','combo2');" onBlur="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?cmb_tipo=1&Pag_Cod=' + document.getElementById('Pag_Cod').value + '&nom_ban=Ban_Cod', 'div_banco'); ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?cmb_tipo=1&Pag_Cod=' + document.getElementById('Pag_Cod2').value + '&nom_ban=Ban_Cod2', 'div_banco2')">
			<?Php 
			foreach($rs_pago as $row_rs_pago)
			{ ?>
              <option value="<?Php echo $row_rs_pago['For_Cod'];  ?>"><?Php echo $row_rs_pago['For_Des'];   ?></option>
			  <?Php 
			} //Fin del foreach
			?>
            </select></td>
		<?Php 
		/**
		* Bancos correspondientes al plan de cuentas 
		*/
		$rs_bancos = $obBD_con1->getArrayConsulta(179, $rs_facttipo[0]['Pag_Cod'].'*'.$Ses_Emp_Cod, $obBD_conexion);
	?>		 
		<td width="78%" align="left">
	  <label>
	  <input name="detalle" type="checkbox" id="detalle" onClick="ShowHide('cheque'); blanquear_pago2()" value="checkbox">
	  </label>
	  <span class="Titulos2">Agregar otro tipo de pago </span></td>
  </tr>
  </table>
  <table width="100%" border="0" cellspacing="0" cellpadding="0">
    <tr>
      <td width="50%" align="left" valign="top">
		<FIELDSET>
		<LEGEND>
			<label class="Titulos2">Tipo 1</label>
		</LEGEND>	  
	  <table width="100%" border="0" cellpadding="0" cellspacing="0">
        <tr>
          <td width="9%" class="Etiqueta1"><span class="Asterisco">* </span>Tipo:</td>
          <td width="17%" class="LetraNegra"><div id="combo">          
		  <select name="Pag_Cod" id="Pag_Cod" onChange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?cmb_tipo=1&Pag_Cod=' + this.value + '&nom_ban=Ban_Cod', 'div_banco')">          
		  <?Php 
		  foreach($rs_facttipo as $row_rs_facttipo)
		  { ?>
               	<option  value="<?Php echo $row_rs_facttipo['Pag_Cod']; ?>"><?PHP echo $row_rs_facttipo['Pag_Des']; ?></option>
          <?Php 
		  }//fin del foreach  ?>
          </select>
          </div></td>
          <td width="12%" class="Etiqueta1"><span class="Asterisco">* </span>Banco:</span></td>
          <td colspan="2" class="LetraNegra">              
			  <div id="div_banco">
              <select name="Ban_Cod" id="Ban_Cod">
                <option value="NULL">(Ninguno)</option>
                <?php 
				if (count($rs_bancos) >0)
				{
				  foreach($rs_bancos as $row_rs_bancos) 
				  { 
					?><option value="<?php echo $row_rs_bancos['Ban_Cod']?>"><?php echo $row_rs_bancos['Pld_Des']?></option><?php
				  }//Fin del foreach
				}//Fin del if (count($row_rs_bancos) >0)
				?>
              </select>
              </div>			  
		  </td>
          <td colspan="2" class="LetraNegra">&nbsp;</td>
          </tr>
        <tr>
          <td class="Etiqueta1">Cuenta No:</td>
          <td class="LetraNegra"><input name="Vet_Cue" type="text" id="Vet_Cue" onKeyPress="return validar_numeric(event)" value="<?php echo $Vet_Cue; ?>" style="text-align:right" size="15" maxlength="15"></td>
          <td class="Etiqueta1"><span class="Asterisco">* </span>Cheque/Papeleta No: </td>
          <td width="12%" class="Titulos2"><input name="Vet_Che" type="text" id="Vet_Che" onKeyPress="return validar_numeric(event)" value="<?php echo $Vet_Che+0; ?>" style=" text-align:right" size="10" maxlength="10" onBlur="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?docBanco=1&num_Doc=' + this.value + '&Ban_Cod='+ document.getElementById('Ban_Cod').value,'div_numDoc')"></td>
          <td width="7%" class="Alertas3"><div id="div_numDoc"></div></td>
          <td width="8%" class="Etiqueta1">Valor:</td>
          <td width="35%" class="Titulos2"><span class="LetraNegra">
            <input name="Vet_Tot" type="text" id="Vet_Tot" onKeyPress="return validar_decimal(event)" style="text-align:right" size="8">
          </span></td>
        </tr>
		</table>
</FIELDSET>	  </td>
      </tr>
    <tr>
      <td align="left" valign="top" id="cheque">
		<FIELDSET>
		<LEGEND>
			<label class="Titulos2">Tipo 2</label>
		</LEGEND>	  	  
	  <table width="100%" border="0" cellpadding="0" cellspacing="0">
        <tr>
          <td width="9%" class="Etiqueta1"><span class="Asterisco">* </span>Tipo:</td>
          <td width="17%" class="LetraNegra"><div id="combo2">
              <select name="Pag_Cod2" id="Pag_Cod2" onChange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?cmb_tipo=1&Pag_Cod=' + this.value + '&nom_ban=Ban_Cod2', 'div_banco2')">
                <?Php
				 foreach($rs_facttipo as $row_rs_facttipo)
				 { ?>
                <option  value="<?Php echo $row_rs_facttipo['Pag_Cod']; ?>"  
			     ><?Php echo $row_rs_facttipo['Pag_Des']; ?></option>
                <?Php 
				 } ?>
              </select>
          </div></td>
          <td width="11%" class="Etiqueta1"><span class="Asterisco">* </span>Banco:</span></td>
          <td colspan="2" class="LetraNegra">              		  
			  <div id="div_banco2">
              <select name="Ban_Cod2" id="Ban_Cod2">
                <option value="NULL">(Ninguno)</option>
                <?php
				if (count($rs_bancos) >0)
				{
				foreach($rs_bancos as $row_rs_bancos)
				{  
			?>
                <option value="<?php echo $row_rs_bancos['Ban_Cod']?>"><?php echo $row_rs_bancos['Pld_Des']?></option>
                <?php
				}//Fin del foreach
				}//Fin del if (count($row_rs_bancos) >0)
			?>
              </select>
			  </div>
			</td>
          <td colspan="2" class="LetraNegra">&nbsp;</td>
          </tr>
        <tr>
          <td class="Etiqueta1">Cuenta No:</td>
          <td class="LetraNegra"><input name="Vet_Cue2" type="text" id="Vet_Cue2" onKeyPress="return validar_numeric(event)" value="<?php echo $Vet_Cue; ?>" style="text-align:right" size="15" maxlength="15"></td>
          <td class="Etiqueta1"><span class="Asterisco">* </span>Cheque/Papeleta No: </td>
          <td width="13%" class="Titulos2"><input name="Vet_Che2" type="text" id="Vet_Che2" onKeyPress="return validar_numeric(event)" value="<?php echo $Vet_Che; ?>" style="text-align:right" size="10" maxlength="10" onBlur="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?docBanco=1&num_Doc=' + this.value + '&Ban_Cod='+ document.getElementById('Ban_Cod2').value,'div_numDoc2')"></td>
          <td width="7%" class="Alertas3"><div id="div_numDoc2"></div></td>
          <td width="8%" class="Etiqueta1">Valor:</td>
          <td width="35%" class="Titulos2"><span class="LetraNegra">
            <input name="Vet_Tot2" type="text" id="Vet_Tot2" onKeyPress="return validar_decimal(event)" style="text-align:right" size="8">
          </span></td>
        </tr>
		</table>
		</FIELDSET></td>
    </tr>
  </table>
  </FIELDSET>
<FIELDSET>
<LEGEND>
<label class="Titulos2">Detalle de la Factura</label>
</LEGEND>
  <table width="100%" border="0" cellpadding="0" cellspacing="0" class="fixedHeader02">	
  <thead>
	<tr>
	    <th width="7%">C&oacute;d.</th>	  
		<th width="7%">Cant.</th>
		<th width="52%">Descripción</th>
		<th width="11%">P. Unitario </th>
		<th width="9%">Importe</th>
		<th width="6%">Desc.</th>
		<th width="5%">IVA</th>
		<th width="3%">&nbsp;</th>
	</tr>
    </thead>
    <tbody id="c_contenido">
    </tbody>   
	<tr>
	  <td>&nbsp;</td>
		<td>
		  <td>&nbsp;</td>       
		  <td align="right">SUBTOTAL: </td>
		<td>
		<div align="center">
		   <input name="t_subtotal" type="text" align="left" id="t_subtotal" size="8" maxlength="8" readonly="true" style="text-align:right" onKeyPress="return validar_numeric(event)" value="0">
		</div></td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		</tr>
	<tr>
	  <td>&nbsp;</td>
	  <td>
	    <td>&nbsp;</td>
	    <td align="right">TARIFA 0%</td>
	  <td>
	  <div align="center">
	    <input name="t_iva0" type="text" align="right" id="t_iva0" size="8" maxlength="8" readonly="true" style="text-align:right" onKeyPress="return validar_numeric(event)" value="0">
	  </div></td>
	  <td>&nbsp;</td>
	  <td>&nbsp;</td>
	  <td>&nbsp;</td>
	  </tr>
	<tr>
	  <td>&nbsp;</td>
	  <td>
	    <td>&nbsp;</td>    
	    <td align="right">TARIFA 12%</td>
	  <td>
	  <div align="center"><input name="t_iva12" type="text" align="center" id="t_iva12" size="8" maxlength="8" readonly="true" style="text-align:right" onKeyPress="return validar_numeric(event)" value="0">  
	  </div>	</td>
	  <td>&nbsp;</td>
	  <td>&nbsp;</td>
	  <td>&nbsp;</td>
	  </tr>
	<tr>
	  <td>&nbsp;</td>
		<td>
		  <td>&nbsp;</td>
		  <td align="right">12% I.V.A.</td>
		<td>
		  <div align="center">
		    <label>
		    <input name="t_iva" type="text" id="t_iva" value="0"  style="text-align:right" onKeyPress="return validar_numeric(event)" size="8">
		    </label>
		  </div>			</td>
			
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		</tr>
	<tr>
	  <td>&nbsp;</td>
		<td>
		  <td align="right">&nbsp;</td>
		  <td align="right">DESCUENTO:
            <label>
              <input name="activar1" type="checkbox" id="activar1" value="checkbox"  onclick="validar_text(); asignar_total_fac()" checked="checked" />
            </label>
            <input name="Vet_Des" type="text" id="Vet_Des" size="2" maxlength="5" style="text-align:right" onblur="numerico(this)" onkeyup="validar_text(); asignar_total_fac()" value="<?Php echo $Vet_Des;?>" /></td>
          <td>
            <div align="center">
              <input name="t_descuento" type="text" align="right" id="t_descuento" size="8" maxlength="8" readonly="true" style="text-align:right" onKeyPress="return validar_numeric(event)" value="0">
            </div></td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		</tr>
     <tfoot>    
	<tr>
	  <td>&nbsp;</td>
		<td>
		  <td>&nbsp;</td>
		  <td align="right">TOTAL:</td>
		<td>
		  <div align="center">
		    <input name="t_rubros" type="text" Align="left" id="t_rubros" size="8" maxlength="8" readonly="true" style="text-align:right" onKeyPress="return validar_numeric(event)" value="0">
		    </div></td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		</tr>
        </tfoot>
	</table>
		<br>
	<table width="22%" border="0" cellpadding="0" cellspacing="0">
	  <tr>
		<td width="1%">&nbsp;</td>
		<td width="38%">
        <button type="button" name="button2" id="button2" class="btn btn-success fileinput-button" title="Buscar Item" 
        onclick="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_rubro=1&ajax_Cli_Cod=<?php echo $codigo ?>','ajax_modal');">
           <i class="icon-plus icon-white"></i>
           <span>Items</span>
           </button> 
        </td>
	    <td width="43%">
         <? if(1==0){ ?>
         <button type="button" name="button1" id="button1" class="btn btn-success fileinput-button" title="Ver Deudas" 
        onclick="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?deudas=1&ajax_Cli_Cod=<?php echo $codigo; ?>','ajax_modal');">
           <i class="icon-plus icon-white"></i>
           <span>Deudas</span>
           </button> 
        <? }?>           
        </td>
	    <td width="18%"><input name="cantmodal" id="cantmodal" type="hidden" value="2" /></td>
	    <input id="nfilas" name="nfilas" type="hidden" value="0">
	  </tr>
	</table>
	<br><input id="tipo_boton" name="tipo_boton" type="hidden" value="">
</fieldset>
</fieldset>
<br>	
 <table width="290" border="0" cellpadding="0" cellspacing="0" class="Azul">
 <tr>
   <td width="109">
   <button type="button" class="btn btn-inverse fileinput-button" title="Atrás" onClick="campos_hide(this.form, '<?Php echo "txt_busqueda*op_opciones*hdd_volver"; ?>', '<?Php echo $volver_busqueda.'*'.$volver_op.'*'; ?>')">
                    <i class=" icon-arrow-left icon-white"></i>
                    <span>&nbsp;&nbsp;Atr&aacute;s&nbsp;&nbsp;</span>
       </button></td>
 <td width="181">
 <button type="button" class="btn btn-primary start" title="Guardar" onclick="validar_facturacion(this.form)">
           <i class="icon-book icon-white"></i>
           <span>Guardar</span>
    </button>
     <input name="hdd_save" type="hidden" id="hdd_save" value="insertar"> </td>
 </tr>
 </table>
	&nbsp;
<div id="bgtransparent" class="bgtransparent" style="display:none" onClick="closeModal()"></div>
<div id="bgmodal"  class="bgmodal"   style="display:none">		
	<div id="ajax_modal"></div>
</div>
<?Php
	if (!(isset($cheque)))
	{
	?>
	<script language="javascript">
	 ShowHide('cheque');		  	 
	</script>
	<?Php
	}//Fin del if (!(isset($cheque)))
}//Fin del if ($codigo > 0 && !(isset($hdd_save)))
 ?>
 </form>
 </table>	   
</div>
<!-- Librerias para el tratamiento de la interfaz - cajas de texto -->
<script type="text/javascript" src="../VALIDACIONES/fac_par_fac_ven.js"></script>
<script type="text/javascript" src="../../Librerias/textbox/main.js"></script>   
<?Php
			}
			else
			{   
				echo error_alerta ("&iexcl;No se puede generar el Documento: [".$Vet_Num."], la Autorización [".$row_rs_autorizaci['Aut_Sri']."] permite facturas entre [".$row_rs_autorizaci['Aut_Ini']."] y [".$row_rs_autorizaci['Aut_Fin']."]!", 2);
			}
		}
		else
		{
			echo error_alerta (" No existen autorizaciones otorgadas por SRI, activas", 2);
		}
	}// Fin del if ($total_rs_apercaja > 0)
	else
	{
		echo error_alerta (" No se puede generar facturas debido a que no existe una Caja Activa", 2);
	}//FIn del else if ($total_rs_apercaja > 0)
}//Fin del if ($total_rs_vendedor > 0)
else
{
	echo error_alerta (" Ud. no es un Vendedor autorizado para emitir Facturas o Notas de Ventas", 2);
}//Fin de else del if ($total_rs_vendedor > 0) ?>
</BODY>
</HTML>
<?php
/**
* Cierra la conexión
*/
@$obBD_conexion->cerrar();
?>