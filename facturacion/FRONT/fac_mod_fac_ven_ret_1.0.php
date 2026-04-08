<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?	
/**
* Descripción: Permite la modificación de facturas de ventas
* Fecha de actualización:	2010-07-04 
* Desarrollador:	Jose Cumbicos 
* Fecha de actualización:	2012-02-19 
* Desarrollador:	Lewis Chimarro
* Fecha de actualización:	2012-05-12
* Desarrollador:	Lewis Chimarro
* Fecha de actualización:	2012-11-26
* Desarrollador:	Lewis Chimarro
* Fecha de actualización:	2015-02-06
* Desarrollador:	Lewis Chimarro
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_fac_ven.php');	  
require_once('../LOGICA/fac_log_deudas.php');	
require_once('../../Librerias/procedimientos/almacenados_standar.php');	

/** 
* Creacion del Objeto de conexión 
*/
$obBD_conexion = new Class_Log_Conexion_Tes($Ses_Dat_Dis);
/* 
* Creación del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Tes;

$hoy = date("Y-m-d");
$mes = date("m"); 

/**
* Llamado a componente ajax 
*/
require_once("../COMPONENTES/ajaxVenBusRentaIva.php");

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
* Cargado del buscador de rubros 
*/
if (isset($ajax_rubro))
{ 
	/**
	* Carreras que el estudiante ha cursado 
	*/
	$row_rs_carrera = $obBD_con1->getArrayConsulta(78, $ajax_Cli_Cod, $obBD_conexion);
?>
<table width="100%" border="0" cellpadding="0" cellspacing="0" id="rubros_table">
      <tr>
        <td><FIELDSET>
          <LEGEND>
          <label class="Titulos2">B&uacute;squeda del rubro</label>
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
				 } //FIn del foreach -> $row_rs_carrera ?>
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
			* Carga el buscador cuando el cliente no tiene carreras 
			*/	  			
			if (count($row_rs_carrera) <= 1)
    		{    
	  			include("../COMPONENTES/tesComRubrosFac2.php");	      
				//include("../../tesoreria/COMPONENTES/tesComRubrosFac2.php");
		    }
			?>	            
          </div>
        </FIELDSET></td>
      </tr>
    </table>
<?php		
exit();	
}

/** 
* Ajax que permite buscar si el numero de cheque o papeleta deposito esta duplicado (presenta mensaje)
*/
if(isset($docBanco))
{
	if($num_Doc!="")
	{	
		$row_rs_numDocumento = $obBD_con1->getRowConsulta(990, $num_Doc.'*'.$Ses_Emp_Cod, $obBD_conexion); 
		
		if(count($row_rs_numDocumento)!=0)
		{	?><span class="Alertas3">&nbsp;<img src="../../mascaras/model1/imagenes/32x32/gtk-no.gif" width="20" height="20" type="image"/><?Php
			echo "&nbsp;&iexcl;Ya existe el n&uacute;mero de documento!"; ?></span><?Php	
		}else{
			?>&nbsp;<img src="../../mascaras/model1/imagenes/ok-s.gif"><?
		}		
    }	
	exit();
}

/** 
* AJAX Validando el Numero de factura 
*/
if($ajax_valid_Factnum==1)
{ 
  if($For_Cod!=$Numero && $For_Cod!="")
  {		
	$row_rs_secuenciaFact = $obBD_con1->getRowConsulta(988, $Aut_Cod, $obBD_conexion); 
	
	if($For_Cod >=$row_rs_secuenciaFact['Aut_Ini'] && $For_Cod <= $row_rs_secuenciaFact['Aut_Fin'])
	{
		$row_rs_secuenciaFact = $obBD_con1->getRowConsulta(989, $Aut_Cod.'*'.$For_Cod.'*'.$Tic_Cod, $obBD_conexion); 

		if(count($row_rs_secuenciaFact)==0)
		{
		?>
			<input name="Vet_Num" type="text" id="Vet_Num" style="text-align:right" size="7" maxlength="7" value="<?Php echo $For_Cod; ?>" onBlur="ajax_datos('<?Php echo $_SERVER['PHP_SELF'];?>?ajax_valid_Factnum=1&For_Cod=' + this.value + '&Aut_Cod='+ <? echo $Aut_Cod?> +	'&Tic_Cod='+ <? echo $Tic_Cod;?> +'&Numero='+ <? echo $Numero;?>,'div_numFact')"><span>&nbsp;<img src="../../mascaras/model1/imagenes/ok-s.gif"></span>
		<?
		}else{
		?>
			<input name="Vet_Num" type="text" id="Vet_Num" style="text-align:right" size="7" maxlength="7" value="<?Php echo $For_Cod; ?>" onBlur="ajax_datos('<?Php echo $_SERVER['PHP_SELF'];?>?ajax_valid_Factnum=1&For_Cod=' + this.value + '&Aut_Cod='+ <? echo $Aut_Cod?> +'&Tic_Cod='+ <? echo $Tic_Cod;?> +'&Numero='+ <? echo $Numero;?>,'div_numFact')"><span class="Texto_Reporte_Rojo">&nbsp;<? echo "&iexcl;El N&uacute;mero ya existe!";?></span>
		<?  
		}
	}else{
	?>	
  	<input name="Vet_Num" type="text" id="Vet_Num" style="text-align:right" size="7" maxlength="7" onBlur="ajax_datos('<?Php echo $_SERVER['PHP_SELF'];?>?ajax_valid_Factnum=1&For_Cod=' + this.value + '&Aut_Cod='+ <? echo $Aut_Cod?> +'&Tic_Cod='+ <? echo $Tic_Cod;?> +'&Numero='+ <? echo $Numero;?>,'div_numFact')"><span class="Texto_Reporte_Rojo">&nbsp;<? echo "&iexcl;N&uacute;mero $For_Cod, fuera de rango!"; ?></span>
    <? 
	}
  }else{
  ?>
  	<input name="Vet_Num" type="text" id="Vet_Num" style="text-align:right" size="7" maxlength="7" value="<?Php echo $For_Cod; ?>" onBlur= "ajax_datos('<?Php echo $_SERVER['PHP_SELF'];?>?ajax_valid_Factnum=1&For_Cod=' + this.value + '&Aut_Cod='+ <? echo $Aut_Cod?> + '&Tic_Cod='+ <? echo $Tic_Cod;?> +'&Numero='+ <? echo $Numero;?>,'div_numFact')">
  <?
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
		  <select name="<?php echo $nom_pag; ?>" id="<?php echo $nom_pag; ?>" onChange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?cmb_tipo=1&Pag_Cod=' + this.value + '&nom_ban=<?php echo $nom_ban; ?>&nom_div=<?php echo $nom_div; ?>&Vet_Che=<? echo $Vet_Che;?>', '<?php echo $div_banco; ?>')">
            <?Php
            foreach ($row_rs_facttipo as $row)
			{ ?>
	            <option  value="<?Php echo $row['Pag_Cod']; ?>"  
			     ><?PHP echo $row['Pag_Des']; ?></option>
            <?PHP 
			}//FIn del foreach -> $row_rs_facttipo  ?>
          </select>		 
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
	 <select name="<?Php echo $nom_ban; ?>" id="<?Php echo $nom_ban; ?>" onChange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?docBanco=1&num_Doc=' + document.getElementById('<? echo $Vet_Che?>').value + '&Ban_Cod='+ this.value,'<? echo $nom_div?>')">
		<option value="NULL">(Ninguno)</option>
		<?php 
		foreach ($row_rs_bancos as $row)
		{?>
			<option value="<?php echo $row['Ban_Cod']?>"><?php echo $row['Pld_Des']?></option>
		<?php 
		}//FIn del foreach -> $row_rs_bancos ?>
	 </select>	
	<?Php
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
   include("../COMPONENTES/tesComRubrosFac2.php");
   exit();
}//Fin del if (isset($sem))

/**
* Cargado AJAX de los resultados de la búsqueda
*/
if (isset($deudas))
{
	$Com_Codigo = $codigo; 
	$Com_Tipo = 1;
?>	
	<?php include("../COMPONENTES/tes_com_deudas.php");?>	
<?
	exit();
}

/** 
* Cargado AJAX de los resultados de la búsqueda del rubro 
*/	
if (isset($buscod))
{
	$busqueda = $busqueda;
	$codigo = $codigo;
	$Sem_Cod = $Sem_Cod;
	include("../COMPONENTES/tesComRubrosConsulta2.php");	
	//include("../../tesoreria/COMPONENTES/tesComRubrosConsulta2.php"); 
	exit();	
}//if (isset($buscod))

/**
* Ajax que carga los bancos
*/
if (isset($otroBanco))
{
	$row_rs_bancos = $obBD_con1->getArrayConsulta(179, $codBusq.'*'.$Ses_Emp_Cod, $obBD_conexion);
?>
	<select name="Ban_Cod2" id="Ban_Cod2" onChange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?docBanco=1&num_Doc=<? echo $Vet_Che2 ?>&Ban_Cod='+ this.value,'<? echo $Div;?>')">
       <option value="NULL">(Ninguno)</option>
       <?php 
	   foreach ($row_rs_bancos as $row)
	   { ?>
       <option <?Php if ($Ban_Cod2 == $row['Ban_Cod']){ echo "selected"; } ?> value="<?php echo $row['Ban_Cod']?>"><?php echo $row['Pld_Des']?>
	   </option>
       <?php 
	   }//Fin del foreach -> $row_rs_bancos ?>
    </select>
<?
	exit();
} //Fin if (isset($otroBanco))

/**
* Ajax para cargar el detalle de la venta
*/
if (isset($ajax_detalle))
{
	$com_codigo = $ajax_codigo;
 	include("../COMPONENTES/tesComDetalleVen.php"); 
	exit();
}

/** 
* CONTROL PARA EVITAR EL REENVIO DEL GUARDADO DE LA FACTURA 
*/
require_once('../../Librerias/postclass.php');	
/** 
* Creación del objeto para evitar el reenvio 
*/
$thisPost = new Post_Block;

/** 
* Evitar el reenvio de formularios 
*/
//if ($thisPost->postBlock($_POST['postID']))
//{
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
	* Actualizacion de la factura 
	*/
	$obBD_ins1->operacionobBD(34, $Vet_Num.'*'.$Vet_Obs.'*'.$Vet_Des.'*'.$Ret_Fec.'*'.$Num_Ret.'*'.$Num_Aut.'*'.$Vnd_Cod.'*'.$codigo, $obBD_conexion);	
	/** 
	* Borra los tipos de pago de la venta 
	*/
	$obBD_ins1->operacionobBD(322, $codigo, $obBD_conexion);	
	/** 
	* Insercion de	los tipos de pago 
	*/
	/** 
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
	* Borra el detalle de las facturas 
	*/
	$obBD_ins1->operacionobBD(44, $codigo, $obBD_conexion);
	/** 
	* Borra el registro del kardex 
	*/
	$obBD_ins1->operacionobBD(1039, $codigo, $obBD_conexion);	
	$Vet_Ite=0;
	foreach ($datos as $puntero => $item)
	{		
		$cant++;
		$param[]=$item;
	
		if ($cant==21)
		{   $Vet_Ite=$Vet_Ite+1;
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
			* Codigo de la renta 
			*/
			if ($param[17] == "")
			{ $param[17] = 'null'; }				
			
			/** 
			* Codigo del iva
			*/
			if ($param[18] == "")
			{ $param[18] = 'null'; }				
									
			/**
			* Inserta el detalle de la venta
			*/
			$obBD_ins1->operacionobBD(40, $param[2].'*'.$param[8].'*'. 
				$param[4].'*'.$param[5].'*'.$param[6].'*'.$codigo.'*'.$param[0].'*'.$param[9].'*'.$param[11].'*'.$param[10].'*'.$param[12].'*'.$param[13].'*'.$param[14].'*'.$param[17].'*'.$param[18].'*'.$Vet_Ite, $obBD_conexion); 

					
			/** 
			* Control para I N V E N T A R I O S 
			*/
			$row_rs_adquisicio = $obBD_con1->getRowConsulta(1037, $param[0], $obBD_conexion);

			/** 
			* Pregunta si es de tipo bien el producto B 
			*/
			if (count($row_rs_adquisicio)<>0)
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
				$obBD_ins1->operacionobBD(1035, $codigo.'*'.'0'.'*'.'0'.'*'.'0'.'*'.$param[0].'*'.$hoy.'*'.$hora.'*'.'0'.'*'.$param[2].'*'.$param[4].'*'.'0'.'*'.$param[5].'*'.'0'.'*'.$desc_var.'*'.$param[8], $obBD_conexion);
				
				/** 
				* Consulta el Stock 
				*/
				$row_rs_conpro = $obBD_con1->getRowConsulta(1206, $param[0], $obBD_conexion);
				/** 
				* Actualizo el Stock 
				*/
				$obBD_ins1->operacionobBD(1204, $row_rs_conpro['Stock'].'*'.$param[0].'*'.$Ses_Suc_Cod, $obBD_conexion);
			}							
			/* 
			* F I N Control para I N V E N T A R I O S 
			*/		
			unset($param);					
		}//Fin del if ($cant==14)
	}//Fin del foreach ($datos as $puntero => $item)
	
	$obBD_ins1->fin_transaccion($obBD_conexion->conexion);
}//if (isset($hdd_save))
//}//Fin del if ($thisPost->postBlock($_POST['postID']))	
	
/**
* Cargado de los datos de la cabecera 
*/
if ($txt_busqueda != "")
{	
	if ($op_opciones == "d")
	{
		$rs_buscar = $obBD_con1->getArrayConsulta(1225, trim($txt_busqueda).'*'.$Pun_Cod, $obBD_conexion);
	}
	elseif ($op_opciones == "r")
	{
		$rs_buscar = $obBD_con1->getArrayConsulta(1226, trim($txt_busqueda).'*'.$Pun_Cod, $obBD_conexion);
	} 
	else
	{
		/** 
		* Consulta las facturas en base a la papeleta de deposito
		*/
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
		$rs_cliente = $obBD_con1->getArrayConsulta(37, $codigo, $obBD_conexion);
		$cliente = $rs_cliente[0]['Vet_Cod'];
		$codAutoriza=$rs_cliente[0]['Aut_Cod'];
		$Comprobante = $rs_cliente[0]['Tic_Cod'];
		
		/*****************************************************/
		/*    FUNCION QUE CARGA AUTOMATICAMENTE LOS RUBROS   */
		/*****************************************************/
		$obBD_con1->generarDeudas($rs_cliente[0]['Cli_Cod'], $obBD_conexion);
		/****************************************************/						
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
        <script type="text/javascript" src="../../Librerias/masked/jquery.maskedinput-1.2.2.js"></script>
        <!--Librerias para modal -->       
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.modals.js"></script> 
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.datepicker.js"></script>
        <script type="text/javascript"> 
        $(function() {
			$('#set1 *').tooltip({showURL: false});
		}); 
		
		$(function() { 			
			$( "#Ret_Fec" ).datepicker({
				changeMonth: true, changeYear: true,
				/* Permite asignar una imagen */
				/*showOn: "button", buttonImage: imagen, buttonImageOnly: true,*/ dateFormat: "yy-mm-dd"});		
		}); 
				
		/**
		* Control de mascaras
		*/
		jQuery(function($){
			$("#Num_Ret").mask("999-999-999999999",{placeholder:"_"});			
		});					             			
		</script>
		<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">  
	</HEAD>
<BODY>
<div id="set1">
     <table width="100%" border="0" cellpadding="0" cellspacing="0" class="table">
	 <tr class="BarraTitulo">
	  <td width="45%"><span>&raquo;</span> Agregar retencion en ventas </td>
      <td width="39%">&raquo; <strong>PUNTO DE IMPRESION:</strong> <?Php echo $row_rs_apercaja['Pun_Des']; ?></td>
      <td width="16%" align="right">&raquo; <strong>CAJA: </strong><?Php echo $row_rs_apercaja['Caj_Fec']; ?></td>
	 </tr>
          
	 <tr>
        <td height="400" colspan="3" valign="top">
       
        <form name="form1" method="post" action="<?Php echo $_SERVER['PHP_SELF']?>">	
            <FIELDSET>
            <LEGEND>
            <label class="Titulos2">Buscar cliente por:</label>
            </LEGEND>
            <?Php
            /**
            * Muestra el mensaje de requerido
            */
            mensaje_requerido(); 
            ?>
            <table width="696" border="0" cellpadding="0" cellspacing="0">
              <tr>
                <td width="100"><input name="op_opciones" type="radio" value="d" onclick="document.getElementById('cmb_mes').disabled=false; 
                                                document.getElementById('cmb_anio').disabled=false; setfocus(this.form.txt_busqueda)" <?Php if ($op_opciones == "d" or !isset($op_opciones)){  echo "checked"; } ?> />
                  <span class="Etiqueta1">Apellidos</span></td>
                <td width="137"><input type="radio" name="op_opciones" value="r" onclick="document.getElementById('cmb_mes').disabled=true; 
                                                document.getElementById('cmb_anio').disabled=true; setfocus(this.form.txt_busqueda)" <?Php if ($op_opciones == "r"){  echo "checked"; } ?> />
                  <span class="Etiqueta1">No. Documento </span></td>
                <td width="134"><input type="radio" name="op_opciones" value="p" onclick="document.getElementById('cmb_mes').disabled=true; 
                                                document.getElementById('cmb_anio').disabled=true; setfocus(this.form.txt_busqueda)" <?Php if ($op_opciones == "p"){  echo "checked"; } ?> />
                  <span class="Etiqueta1">Dep&oacute;sito </span></td>
                <td width="325" class="LetraNegra">&nbsp;</td>
                </tr>
              </table>
            <table width="520" border="0" cellpadding="0" cellspacing="0">
              <tr>
                <td height="38" class="BarraBusqueda"><div align="left"><span class="Asterisco">* </span>Busqueda:
                  <input name="txt_busqueda" type="text" id="txt_busqueda" value="" size="40" maxlength="50">
                  &nbsp;&nbsp;&nbsp;
                  <button type="button" name="btn-buscar" id="btn-buscar" class="btn btn-success fileinput-button" title="Deudas" onclick="validar_requeridos(this.form, 'txt_busqueda', 0)">
                    <i class="icon-search icon-white"></i>
                    <span>Buscar</span>
                    </button> </div>  </td>
                </tr>
            </table>
            <input type="hidden" id="CajFec" name="CajFec" value="<? echo $CajFec;?>" />
            <input type="hidden" id="Pec_Cod" name="Pec_Cod" value="<? echo $Pec_Cod;?>" />
            <input type="hidden" id="Pla_Cod" name="Pla_Cod" value="<? echo $Pla_Cod;?>" />
            <input type="hidden" id="Vnd_Cod" name="Vnd_Cod" value="<? echo $row_rs_vendedor['Vnd_Cod'];?>" />            
            </FIELDSET>
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
	<table width="100%"  border="1" cellpadding="0" cellspacing="0" class="fixedHeader01">
    <thead>
	  <tr>
	      <th width="4%">C&oacute;d. Int.</th>
          <th width="4%">No. Documento</th>
          <th>Clientes</th>
          <th width="10%">Fecha</th>
          <th width="4%">&nbsp;</th>	  		  
          <th width="4%">&nbsp;</th>
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
		  	if($row_rs_buscar['Vet_Est']=='I')
	  		  { $rojo='#FF0000'; $anulada++; }else{$rojo='';}
	  ?>  		
	  <tr>
	    <td align="center"><FONT COLOR="<? echo $rojo;?>"><?Php echo $row_rs_buscar['Vet_Cod']; ?></FONT></td>
	    <td height="25" align="center"><FONT COLOR="<? echo $rojo;?>"><?Php echo $row_rs_buscar['Vet_Num']; ?></FONT></td>
	    <td><FONT COLOR="<? echo $rojo;?>"><?Php echo marcar_cadena($txt_busqueda,$row_rs_buscar['Prs_Ape'].' '.$row_rs_buscar['Prs_Nom'],'#FFFF00',1); ?></FONT></td>
	    <td align="center"><FONT COLOR="<? echo $rojo;?>"><?Php echo $row_rs_buscar['Caj_Fec']; ?></FONT></td>
	    <td align="center" ><button type="button" name="button<?Php echo $i+1; ?>" id="button<?Php echo $i+1; ?>" class="btn btn-info btn-mini" title="Ver detalle" onclick="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_detalle=1&ajax_codigo=<?Php echo $row_rs_buscar['Vet_Cod']; ?>', 'ajax_modal')">
	        <i class="icon-info-sign icon-white"></i>
	        </button>		
        </td>	
	    <td align="center" ><?Php if ($row_rs_buscar['Vet_Est'] == 'A') { ?>
        <form name="form3" id="form3" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
	      <input name="codigo" id="codigo" type="hidden" value="<?Php echo $row_rs_buscar['Vet_Cod']; ?>">
	      <input name="volver_busqueda" id="volver_busqueda" type="hidden" value="<?Php echo $txt_busqueda;?>">
	      <input name="volver_op" id="volver_op" type="hidden" value="<?Php echo $op_opciones;?>">
	      <input name="volver_anio" id="volver_anio" type="hidden" value="<?Php echo $cmb_anio;?>">				
	      <input name="volver_mes" id="volver_mes" type="hidden" value="<?Php echo $cmb_mes;?>">
	      <input name="volver_Tic_Cod" id="volver_Tic_Cod" type="hidden" value="<?Php echo $Tic_Cod;?>">
          <input type="hidden" id="Vnd_Cod" name="Vnd_Cod" value="<? echo $Vnd_Cod;?>" />
	      <button type="button" class="btn btn-success btn-mini" title="Elegir" onclick="this.form.submit()">
	        <i class=" icon-arrow-right icon-white"></i>
	        </button>	
            
          <input type="hidden" id="CajFec" name="CajFec" value="<? echo $CajFec;?>" />
          <input type="hidden" id="Pec_Cod" name="Pec_Cod" value="<? echo $Pec_Cod;?>" />
          <input type="hidden" id="Pla_Cod" name="Pla_Cod" value="<? echo $Pla_Cod;?>" /> 	
            </form>
	      <?Php } else { echo "&nbsp;"; } ?>		
        </td>
	    </tr>	  		
	  <?Php } 
  	}//Fin del if(count($rs_buscar) != 0)
	else
	{ ?>
	 <tr>
	 	<td>&nbsp;</td>
	 	<td>&nbsp;</td>
	 	<td><?Php 
  		echo error_alerta("No existen facturas que modificar para ".strtoupper($txt_busqueda)." en ".mes($msg_mes[1],1)." el ".$cmb_anio, 1); ?></td>
	 	<td>&nbsp;</td>
	 	<td>&nbsp;</td>
	 	<td>&nbsp;</td>
	 </tr>
	<?Php
	}//Fin del else if($total_rs_buscar != 0)
  ?>
  </table>
</FIELDSET>
<?Php
	echo barra_estado(count($rs_buscar));
}//Fin del if(isset($txt_busqueda))

if ($anulada > 0)
{		
	$com_leyenda[1]=$anulada;
}//Fin del if ($anulada > 0)
?>
<br/>
<?
require_once('../../componentes/FRONT/com_con_leyenda.php');
?>
  <form action="<?Php echo $_SERVER['form2']; ?>" method="post" name="form2" id="form2">
  <?Php 
  if ($codigo > 0 && !(isset($hdd_save)))  
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
	<table width="100%" border="0" cellpadding="0" cellspacing="0">
	  <tr>
		<td width="14%" class="Etiqueta1">C&eacute;dula/R.U.C.:</td>
		<td width="18%" class="LetraNegra">&nbsp;<?Php echo $rs_cliente[0]['Prs_Ced'] ?>
		 </td>
		<td width="9%" class="Etiqueta1">&nbsp;</td>
		<td class="LetraNegra">&nbsp;</td>
		</tr>
	  <tr>
	    <td class="Etiqueta1">Cliente:</td>
	    <td colspan="3" class="LetraNegra">&nbsp;<?Php echo $rs_cliente[0]['Prs_Ape'].' '.$rs_cliente[0]['Prs_Nom']; ?></td>
	    </tr>
	  <tr>
		<td width="14%" class="Etiqueta1">Direcci&oacute;n:</td>
		<td colspan="3" class="LetraNegra">&nbsp;<?php echo $rs_cliente[0]['Prs_Dir']?></td>
	  </tr>
	  </table>    
	  </FIELDSET>	
	<?Php
	if ($rs_cliente[0]['Cli_Ruf'] != "")
	{
	?>
	<FIELDSET>
	<LEGEND>
	<label class="Titulos2">Datos del Representante</label>
	</LEGEND>
	 <table width="100%" border="0" cellpadding="0" cellspacing="0">
	  <tr>
		<td width="14%" class="Etiqueta1">Cudela/R.U.C.:</td>
		<td class="LetraNegra">&nbsp;<? echo $rs_cliente[0]['Cli_Ruf']; ?></td>
	  </tr>
	  <tr>
	    <td class="Etiqueta1">Representante:</td>
	    <td class="LetraNegra">&nbsp;<? echo $rs_cliente[0]['Cli_Fac']; ?></td>
	    </tr>
	  <tr>
		<td class="Etiqueta1">Direcci&oacute;n:</td>
		<td class="LetraNegra">&nbsp;<? echo $rs_cliente[0]['Cli_Dir']; ?></td>
	  </tr>
	 </table>
	 </FIELDSET>
  <?php }//Fin del if ($rs_cliente[0]['Est_Ruf'] != "") ?>
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
		    <td class="Etiqueta1"><span class="Asterisco">* </span>Tipo documento:</td>
		    <td colspan="3" class="LetraNegra">&nbsp;
             <?Php
			 echo $rs_cliente[0]['Tic_Des'];
			 
	/**
	* Consulta los tipos de comprobantes
	*/
	//$row_tipo_compr = $obBD_con1->getArrayConsulta(1036, '', $obBD_conexion);	
	?>
      <!--<select name="Tic_Cod" id="Tic_Cod">
        <option  value="">Seleccione...</option>
        <?Php
	//foreach($row_tipo_compr as $row)
	//{ ?>
        <option  <?Php //if ($rs_cliente[0]['Tic_Cod'] == $row['Tic_Cod']){ echo "selected"; } ?> value="<?Php // echo $row['Tic_Cod']; ?>"><?Php //echo $row['Tic_Des']; ?></option>
        <?Php
	//}
	?>
      </select>-->
            
            </td>
		    <td class="Etiqueta1">&nbsp;</td>
		    <td class="LetraNegra">&nbsp;</td>
		    </tr>
		  <tr>
		  <td width="14%" class="Etiqueta1">Fecha:</td>
		  <td width="19%" class="LetraNegra">&nbsp;<?Php echo $rs_cliente[0]['Caj_Fec']; ?></td>
		   <td width="5%" class="Etiqueta1">Ciudad:</td>
		   <td width="23%" class="LetraNegra">&nbsp;<?Php echo $rs_cliente[0]['Ciu_Des']; ?>		   </td>
		   <td width="13%" class="Etiqueta1"><span class="Asterisco">* </span>No secuencia:</td>
		   <td width="26%" class="LetraNegra">
		   <div id="div_numFact">
			  <input name="Vet_Num" type="text" id="Vet_Num" size="7" maxlength="7" style="text-align:right" value="<?Php echo $rs_cliente[0]['Vet_Num']; ?>" onBlur=" ajax_datos('<?Php echo $_SERVER['PHP_SELF'];?>?ajax_valid_Factnum=1&For_Cod=' + this.value + '&Aut_Cod='+ <? echo $codAutoriza;?>+'&Tic_Cod='+ <? echo $Comprobante;?>+ '&Numero='+ <? echo $rs_cliente[0]['Vet_Num'];?>,'div_numFact')" onKeyPress="return validar_numeric(event)"> 
		   </div></td>
		  </tr>		  
		  <tr>
		   <td width="14%" class="Etiqueta1">Observaci&oacute;n:</td>
		  <td height="17" colspan="5">			
			<textarea name="Vet_Obs" cols="80" rows="3" id="Vet_Obs"><?Php echo $rs_cliente[0]['Vet_Obs']; ?></textarea></td>
		    </tr>
		</table>
		  <input name="Vet_Cod" type="hidden" id="Vet_Cod" value="<?php echo $rs_cliente[0]['Vet_Cod']; ?>">
		  <input name="codigo" type="hidden" id="codigo" value="<?Php echo $codigo; ?>">
		 </FIELDSET>
		<FIELDSET>
<LEGEND>
<label class="Titulos2"> Formas de Pago </label>
</LEGEND>
<table width="100%" border="0" cellpadding="0" cellspacing="0" >
  <tr>
    <td width="8%" class="Etiqueta1"><div align="right"><span class="Asterisco">* </span>Forma:</div></td>
    <td width="14%" class="LetraNegra">
      <?Php 
	  /**
	  * Consulta las formas de pago de la factura
	  */
	 $rs_pago_fac = $obBD_con1->getArrayConsulta(316, $codigo, $obBD_conexion);
	 $Pag_Cod = $rs_pago_fac[0]['Pag_Cod'];
	 $Bak_Cod = $rs_pago_fac[0]['Bak_Cod'];
	 $Ban_Cod = $rs_pago_fac[0]['Ban_Cod'];
	 $Vet_Cue = $rs_pago_fac[0]['Vet_Cue']; 
	 $Vet_Che = $rs_pago_fac[0]['Vet_Che'];
	 $Vet_Tot = $rs_pago_fac[0]['Vet_Tot'];
	 	 
	  /** 
	  * Contro para saber si hay mas de un tipo de pago 
	  */
	  if (count($rs_pago_fac) > 1)
	  {
		 $Pag_Cod2 = $rs_pago_fac[0]['Pag_Cod'];
		 $Bak_Cod2 = $rs_pago_fac[0]['Bak_Cod'];
		 $Ban_Cod2 = $rs_pago_fac[0]['Ban_Cod'];
		 $Vet_Cue2 = $rs_pago_fac[0]['Vet_Cue']; 
		 $Vet_Che2 = $rs_pago_fac[0]['Vet_Che'];
		 $Vet_Tot2 = $rs_pago_fac[0]['Vet_Tot'];		
	  }//Fin del if (count($rs_pago_fac) > 1)
	  
	/**
	* Cargar la forma de pago
	*/
	$rs_pago = $obBD_con1->getArrayConsulta(16, '', $obBD_conexion);
	/** 
	* Cargar tipo de pago 
	*/
	$rs_facttipo = $obBD_con1->getArrayConsulta(17, $rs_pago[0]['For_Cod'], $obBD_conexion); 
	?>
      <select name="For_Cod" id="For_Cod" onChange=" ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?cmb=1&For_Cod=' + this.value + '&div_banco=div_banco&nom_pag=Pag_Cod&nom_ban=Ban_Cod&chk_ban=chk_bancos&nom_div=div_numDoc&Vet_Che=Vet_Che', 'combo'); ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?cmb=1&For_Cod=' + this.value + '&div_banco=div_banco2&nom_pag=Pag_Cod2&nom_ban=Ban_Cod2&chk_ban=chk_bancos2&nom_div=div_numDoc2&Vet_Che=Vet_Che2', 'combo2')" onBlur="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?cmb_tipo=1&Pag_Cod=' + document.getElementById('Pag_Cod').value + '&nom_ban=Ban_Cod&nom_div=div_numDoc', 'div_banco'); ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?cmb_tipo=1&Pag_Cod=' + document.getElementById('Pag_Cod2').value + '&nom_ban=Ban_Cod2&nom_div=div_numDoc2', 'div_banco2')">
        <?Php 
		foreach($rs_pago as $row_rs_pago)
		{ ?>
        <option <?Php if($For_Cod==$row_rs_pago['For_Cod']){ echo "selected";}?> value="<?Php echo $row_rs_pago['For_Cod'];?>"><?Php echo $row_rs_pago['For_Des'];?> 
        </option>
        <?Php 
		} ?>
      </select>
    </td>
    <?Php 
		/** 
		* Bancos correspondientes al plan de cuentas 
		*/
		$rs_bancos = $obBD_con1->getArrayConsulta(179, $Pag_Cod.'*'.$Ses_Emp_Cod, $obBD_conexion);
	?>
    <td width="78%" align="left">
      <input name="detalle" type="checkbox" id="detalle" onClick="ShowHide('cheque'); ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?otroBanco=1&codBusq='+ document.getElementById('Pag_Cod2').value +'&num_Doc=' + document.getElementById('Vet_Che2').value + '&Div=div_numDoc2&Ban_Cod='+ this.value,'div_banco2'); blanquear_pago2()" value="checkbox" <?Php 
	if (count($rs_pago_fac) > 1){ echo "checked='checked'"; } ?> >
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
	  <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
          <td width="11%" class="Etiqueta1"><span class="Asterisco">* </span>Tipo:</td>
          <td width="21%" class="LetraNegra"><div id="combo">
              <select name="Pag_Cod" id="Pag_Cod" onChange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?cmb_tipo=1&Pag_Cod=' + this.value + '&nom_ban=Ban_Cod&nom_div=div_numDoc&Vet_Che=Vet_Che', 'div_banco')">
                <?Php
				foreach($rs_facttipo as $row_rs_facttipo)
				{ ?>
                <option <?Php if ($Pag_Cod == $row_rs_facttipo['Pag_Cod']){ echo "selected"; }  ?>  value="<?Php echo $row_rs_facttipo['Pag_Cod']; ?>"><?PHP echo $row_rs_facttipo['Pag_Des']; ?></option>
                <?Php 
				} ?>
              </select>
          </div></td>
          <td width="17%" class="Etiqueta1"><span class="Asterisco">* </span>Banco:</span></td>
          <td colspan="2" class="LetraNegra">
          <div id="div_banco">              
              <select name="Ban_Cod" id="Ban_Cod" onChange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?docBanco=1&num_Doc=' + document.getElementById('Vet_Che').value + '&Ban_Cod='+ this.value,'div_numDoc')">
                <option value="NULL">(Ninguno)</option>
                <?php
				if (count($rs_bancos) >0)
				{
					foreach($rs_bancos as $row_rs_bancos)
					{  ?>
                <option <?Php if ($Ban_Cod == $row_rs_bancos['Ban_Cod']){ echo "selected"; } ?> value="<?php echo $row_rs_bancos['Ban_Cod']?>"><?php echo $row_rs_bancos['Pld_Des']?></option>
                <?php
					} 
				}//Fin del if (count($row_rs_bancos) >0)
			?>
              </select></div>
          <td colspan="2" class="LetraNegra">&nbsp;</td>
          </tr>
        <tr>
          <td class="Etiqueta1">Cuenta No:</td>
          <td class="LetraNegra"><input name="Vet_Cue" type="text" id="Vet_Cue" onKeyPress="return validar_numeric(event)" value="<?php echo $Vet_Cue; ?>" style="text-align:right" size="15" maxlength="15"></td>
          <td class="Etiqueta1"><span class="Asterisco">* </span>Cheque/Papeleta No: </td>
          <td width="9%" class="Titulos2"><input name="Vet_Che" type="text" id="Vet_Che" onKeyPress="return validar_numeric(event)" value="<?php echo $Vet_Che; ?>" style="text-align:right" size="10" maxlength="10" onBlur="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?docBanco=1&num_Doc=' + this.value + '&Ban_Cod='+ document.getElementById('Ban_Cod').value,'div_numDoc')"></td>
          <td width="6%" class="Alertas3"><div id="div_numDoc"></div></td>
          <td width="6%" class="Etiqueta1">Valor:</td>
          <td width="30%" class="Titulos2"><span class="LetraNegra">
            <input name="Vet_Tot" type="text" id="Vet_Tot" onKeyPress="return validar_decimal(event)" style="text-align:right" size="8" value="<?php echo formato_numero($Vet_Tot,2,1); ?>">
          </span></td>
        </tr>
		</table>
		<?Php
	  /** 
	  * Bancos 2 correspondientes al plan de cuentas 
	  */	  		 	  
	  ?>
	    </FIELDSET>	  
        </td>
      </tr>
    <tr>
      <td align="left" valign="top" id="cheque">
		<FIELDSET>
		<LEGEND>
			<label class="Titulos2">Tipo 2</label>
		</LEGEND>	  	  
	  <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
          <td width="11%" class="Etiqueta1"><span class="Asterisco">* </span>Tipo:</td>
          <td width="21%" class="LetraNegra"><div id="combo2">
              <select name="Pag_Cod2" id="Pag_Cod2" onChange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?cmb_tipo=1&Pag_Cod=' + this.value + '&nom_ban=Ban_Cod2&nom_div=div_numDoc2&Vet_Che=Vet_Che2', 'div_banco2')">
                <?Php
				foreach($rs_facttipo as $row_rs_facttipo)
				{ ?>
                <option <?Php if ($Pag_Cod2 == $row_rs_facttipo['Pag_Cod']){ echo "selected"; }  ?>  value="<?Php echo $row_rs_facttipo['Pag_Cod']; ?>"  
			     ><?Php echo $row_rs_facttipo['Pag_Des']; ?></option>
                <?Php 
				} ?>
              </select>
          </div></td>
          <td width="17%" class="Etiqueta1"><span class="Asterisco">* </span>Banco:</span></td>
          <td colspan="2" class="LetraNegra">
		  <div id="div_banco2">                       
              <select name="Ban_Cod2" id="Ban_Cod2" onChange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?docBanco=1&num_Doc=' + document.getElementById('Vet_Che2').value + '&Ban_Cod='+ this.value,'div_numDoc2')">
                <option value="NULL">(Ninguno)</option>
                <?php 
				if (count($rs_bancos) >0)
				{
					foreach($rs_bancos as $row_rs_bancos)
					{ ?>
                <option <?Php if ($Ban_Cod2 == $row_rs_bancos['Ban_Cod']){ echo "selected"; } ?> value="<?php echo $row_rs_bancos['Ban_Cod']?>"><?php echo $row_rs_bancos['Pld_Des']?></option>
                <?Php 
					} 
				}//Fin del if (count($row_rs_bancos) >0)
				?>
              </select>
		  </div>
		  </td>
          <td colspan="2" class="LetraNegra">&nbsp;</td>
          </tr>
        <tr>
          <td class="Etiqueta1">Cuenta No:</td>
          <td class="LetraNegra"><input name="Vet_Cue2" type="text" id="Vet_Cue2" onKeyPress="return validar_numeric(event)" value="<?php echo $Vet_Cue2; ?>" style="text-align:right" size="15" maxlength="15"></td>
          <td class="Etiqueta1"><span class="Asterisco">* </span>Cheque/Papeleta No: </td>
          <td width="9%" class="Titulos2"><input name="Vet_Che2" type="text" id="Vet_Che2" onKeyPress="return validar_numeric(event)" value="<?php echo $Vet_Che2; ?>" style="text-align:right" size="10" maxlength="10" onBlur="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?docBanco=1&num_Doc=' + this.value + '&Ban_Cod='+ document.getElementById('Ban_Cod2').value,'div_numDoc2')"></td>
          <td width="6%" class="Alertas3"><div id="div_numDoc2"></div></td>
          <td width="6%" class="Etiqueta1">Valor:</td>
          <td width="30%" class="Titulos2"><span class="LetraNegra">
            <input name="Vet_Tot2" type="text" id="Vet_Tot2" onKeyPress="return validar_decimal(event)" style="text-align:right" size="8" value="<?php echo formato_numero($Vet_Tot2,2,1); ?>">
          </span></td>
        </tr>
		</table>
		</FIELDSET>		
        </td>
    </tr>
  </table>
  </FIELDSET>
<FIELDSET>
<LEGEND>
<label class="Titulos2">Detalle de la Factura</label>
</LEGEND>
  <table width="100%" border="0" cellpadding="0" cellspacing="0"> 
  <thead>   
	<tr class="Cabecera1" height="35">
	    <th width="8%">C&oacute;d.</th>	  
		<th width="8%">Cant.</th>
		<th width="34%">Descripción</th>
		<th width="15%">P. Unitario </th>
		<th width="15%">Importe</th>
		<th width="8%">Desc.</th>
		<th width="8%">IVA</th>
		<th width="4%">Medida</th>
        <th width="4%">Renta</th>
        <th width="4%">&nbsp;</th>
        <th width="4%">&nbsp;</th>        
        <th width="4%">I.V.A.</th>
        <th width="4%">&nbsp;</th>
        <th width="4%">&nbsp;</th>        
		<th width="4%">&nbsp;</th>
		</tr>
    </thead>
	<tbody id="c_contenido">
	<?Php 
	/** 
	* Codigo del cliente 
	*/
	$codigo_cli = $rs_cliente[0]['Cli_Cod'];
	/** 
	* % de Descuento total 
	*/
	$Vet_Des = $rs_cliente[0]['Vet_Des'];		
	
	foreach($rs_cliente as $row_rs_cliente)
	{
		$fila++;
	?>	
	<tr>
	  <td>
	    <input name="datos[<?Php echo $fila; ?>,1]" type="text" id="datos[<?Php echo $fila; ?>,1]" 
				value="<?Php echo $row_rs_cliente['Pro_Cod']?>" readonly="readonly" size="2">
	    <input name="datos[<?Php echo $fila; ?>,2]" type="hidden" id="datos[<?Php echo $fila; ?>,2]" 
			value="<?Php echo $row_rs_cliente['Pro_Ide']?>" size="3" maxlength="5" readonly="true">
	    </td>			  
	  <td align="center">
	  		<input name="datos[<?Php echo $fila; ?>,3]" readonly="readonly" type="text" id="datos[<?Php echo $fila; ?>,3]" 
			value="<?Php echo $row_rs_cliente['Vet_Can']?>" size="4" maxlength="4"  onBlur="numerico(this);" 
			onKeyUp="cal_importe(this, document.getElementById('<?Php echo "datos[".$fila.",5]"; ?>'), 
			document.getElementById('<?Php echo "datos[".$fila.",6]"; ?>'), document.getElementById('<?Php echo "datos[".$fila.",15]"; ?>')); asignar_total_fac(); cal_retencionVenta();" style="text-align:right"></td>
	  <td>
	  		<input name="datos[<?Php echo $fila; ?>,4]" type="text" id="datos[<?Php echo $fila; ?>,4]" 
		  	value="<?Php echo $row_rs_cliente['Ite_Lar']?>" size="45" maxlength="25" readonly="readonly">	  </td>
	  <td align="center">
	  <?Php 
	  /** 
	  * Consulta el valor maximo de la deuda a pagar para la modificacion
	  */
	  $row_rs_deuda = $obBD_con1->getRowConsulta(99, $row_rs_cliente['Cli_Cod'].'*'.$row_rs_cliente['Nge_Cod'].'*'.$row_rs_cliente['Pro_Cod'].'*'.$row_rs_cliente['Asi_Int'].'*'.$row_rs_cliente['Cnt_Cod'].'*'.$row_rs_cliente['Vet_Int'], $obBD_conexion);
 	  /** 
	  * Consulta los rubro del intereses en base al acta de notas, contrato e indice
	  */
	  $rs_interes = $obBD_con1->getArrayConsulta(75, $codigo.'*'.
				$row_rs_cliente['Nge_Cod'].'*'.$row_rs_cliente['Asi_Int'].'*'.$row_rs_cliente['Pro_Cod'].'*'.$row_rs_cliente['Cnt_Cod'].'*'.$row_rs_cliente['Vet_Int'], $obBD_conexion);
	  ?>	  
	  		<input name="datos[<?Php echo $fila; ?>,5]" type="text" readonly="readonly" id="datos[<?Php echo $fila; ?>,5]" 
			value="<?Php echo formato_numero($row_rs_cliente['Vet_Pru'],2,1); ?>" size="10" maxlength="8" 
			onBlur="numerico(this); cien(this, '<?Php echo $row_rs_deuda['Deu_Val']; ?>'); positivo(this);" 
			onKeyUp="cal_importe(document.getElementById('<?Php echo "datos[".$fila.",3]"; ?>'), this, 
			document.getElementById('<?Php echo "datos[".$fila.",6]"; ?>'), document.getElementById('<?Php echo "datos[".$fila.",15]"; ?>')); asignar_total_fac(); cal_retencionVenta();" style="text-align:right">	  
            </td>   
	  <td align="center">
	  		<input name="datos[<?Php echo $fila; ?>,6]" type="text" id="datos[<?Php echo $fila; ?>,6]" 
			value="<?Php echo formato_numero($row_rs_cliente['Vet_Imp'],2,1); ?>" style="text-align:right" size="8" maxlength="8" readonly="true">	  </td>
	  <td align="center">
	  		<input name="datos[<?Php echo $fila; ?>,7]" type="text" id="datos[<?Php echo $fila; ?>,7]" 
			value="<?Php echo $row_rs_cliente['Vet_Dec']?>" style="text-align:right" size="2" maxlength="2" <?Php if ($row_rs_cliente['Vet_Des'] != 0) 
			{ echo "readonly='true'"; }?> onBlur="numerico(this)" onKeyUp=
			"cal_importe(document.getElementById('<?Php echo "datos[".$fila.",3]"; ?>'), document.getElementById('<?Php echo "datos[".$fila.",5]"; ?>'), 
			document.getElementById('<?Php echo "datos[".$fila.",6]"; ?>'), document.getElementById('<?Php echo "datos[".$fila.",15]"; ?>')); asignar_total_fac()">	  </td>
	  <td align="center">
	  		<input name="datos[<?Php echo $fila; ?>,8]" type="text" id="datos[<?Php echo $fila; ?>,8]" 
			value="<?Php echo $row_rs_cliente['Iva_Por']?>" style="text-align:right" size="2" maxlength="3" readonly="true">
            <input name="datos[<?Php echo $fila; ?>,9]" type="hidden" id="datos[<?Php echo $fila; ?>,9]" value="<?Php echo 
			$row_rs_cliente['Iva_Cod']?>">
	    
	    <input name="datos[<?Php echo $fila; ?>,10]" type="hidden" 
			id="datos[<?Php echo $fila; ?>,10]" value="<?Php echo $row_rs_cliente['Nge_Cod']; ?>">
	    
	    <input name="datos[<?Php echo $fila; ?>,11]" type="hidden" id="datos[<?Php echo $fila; ?>,11]" value="<?Php 
			 echo $row_rs_cliente['Vet_Rec']; ?>">
	    
	    <input name="datos[<?Php echo $fila; ?>,12]" type="hidden" id="datos[<?Php echo $fila; ?>,12]" value="<?Php 
			echo $row_rs_cliente['Asi_Int']?>">
            
<input name="datos[<?Php echo $fila; ?>,13]" type="hidden" id="datos[<?Php echo $fila; ?>,13]" value="<?Php 
			echo $row_rs_cliente['Cnt_Cod']?>">
            
            <input name="datos[<?Php echo $fila; ?>,14]" type="hidden" id="datos[<?Php echo $fila; ?>,14]" value="<?Php 
			echo $row_rs_cliente['Vet_Int']?>">            
            	  </td>
	  <td align="center"><input name="datos[<?Php echo $fila; ?>,15]" type="text" id="datos[<?Php echo $fila; ?>,15]" 
			value="<?Php echo formato_numero($row_rs_cliente['Vet_Uni'],2,1); ?>" size="4" maxlength="8" onblur="numerico(this); positivo(this);" 
			onkeyup="cal_importe(document.getElementById('<?Php echo "datos[".$fila.",3]"; ?>'), document.getElementById('<?Php echo "datos[".$fila.",5]"; ?>'), 			document.getElementById('<?Php echo "datos[".$fila.",6]"; ?>'), document.getElementById('<?Php echo "datos[".$fila.",15]"; ?>')); asignar_total_fac()" style="text-align:right" /></td>
      <td><span class="LetraNegra">
        <?Php 
		
		
		/* Consulta los c&oacute;digos de retención  */	
		$row_rs_retencion_compra_renta=$obBD_con1->getRowConsulta(344,$row_rs_cliente['Vet_Cod'].'*'.$row_rs_cliente['Pro_Cod'].'*'.$row_rs_cliente['Ren_Cod'],$obBD_conexion);
		$num_row_rs_retencion_compra_renta=$row_rs_retencion_compra_renta['Ren_Cod'] > 0? 1 : 0;
		
		$porcentaje_renta=($row_rs_retencion_compra_renta['Ret_Bas']*$row_rs_retencion_compra_renta['Ren_Por'])/100;
		$base_renta=$base_renta+$porcentaje_renta;	
		$cuenta_renta=$cuenta_renta+$num_row_rs_retencion_compra_renta;
	
?>
      </span>        
      <input name="datos[<?Php echo $fila; ?>,16]" id="datos[<?Php echo $fila; ?>,16]" type="text" size="1" readonly="" value="<?Php echo trim($row_rs_retencion_compra_renta['Ren_Sri']); ?>" onFocus="valor_renta_compra();"  ></td>
      <td><span class="LetraNegra">
      <input name="Btn_RentaMas[<?Php echo $fila; ?>]" id="Btn_RentaMas[<?Php echo $fila; ?>]" type="button" class="BotonEliminar" value="+" onclick="busca_renta_btn(form,this)" />
      </span></td>
      <td><span class="LetraNegra">
        <input id="Btn_RentaMenos[<?Php echo $fila; ?>]" type="button" class="BotonEliminar" name="Btn_RentaMenos[<?Php echo $fila; ?>]" value="-" 
onclick="busca_renta_quita_btn(form,this)" />
      </span></td>
      <td>
        <?Php
		/* Consulta los datos de la retencion en caso que sea IVA */
		$row_rs_retencion_compra_iva=$obBD_con1->getRowConsulta(345,$row_rs_cliente['Vet_Cod'].'*'.$row_rs_cliente['Pro_Cod'].'*'.$row_rs_cliente['Ren_Iva'], $obBD_conexion);
		$num_row_rs_retencion_compra_iva=$row_rs_retencion_compra_iva['Ren_Cod'] > 0? 1 : 0;				
		$cuenta_renta=$cuenta_renta+$num_row_rs_retencion_compra_iva;	
		$cal_poriva=($row_rs_retencion_compra_iva['Val_Ret']*$row_rs_retencion_compra_iva['Ren_Por'])/100;
		$base_iva=$base_iva+$cal_poriva;		
		
		?>
        <input name="datos[<?Php echo $fila; ?>,17]" id="datos[<?Php echo $fila; ?>,17]" type="text" size="1" value="<?Php echo trim(
		$row_rs_retencion_compra_iva['Ren_Sri']); ?>" onFocus="valor_renta_compra();" readonly="" >
        <input name="datos[<?Php echo $fila; ?>,18]" id="datos[<?Php echo $fila; ?>,18]" type="hidden" size="3" value="<?Php echo 
		$row_rs_retencion_compra_renta['Ren_Cod']; ?>" >
        <input name="datos[<?Php echo $fila; ?>,19]" id="datos[<?Php echo $fila; ?>,19]" type="hidden" size="3" value="<?Php echo 
		$row_rs_retencion_compra_iva['Ren_Iva']; ?>" >
        <input name="datos[<?Php echo $fila; ?>,20]" id="datos[<?Php echo $fila; ?>,20]" type="hidden" size="3" value="<?Php echo 
		$row_rs_retencion_compra_renta['Ren_Por']; ?>" >
        <input name="datos[<?Php echo $fila; ?>,21]" id="datos[<?Php echo $fila; ?>,21]" type="hidden" size="3" value="<?Php echo 
		$row_rs_retencion_compra_iva['Ren_Por']; ?>" >
      </td>
      <td><span class="LetraNegra">
        <input id="Btn_IvaMas[<?Php echo $fila; ?>]" type="button" class="BotonEliminar" name="Btn_IvaMas[<?Php echo $fila; ?>]" value="+" onclick="busca_iva_btn(form,this)" />
      </span></td>
      <td><span class="LetraNegra">
        <input id="Btn_IvaMenos[<?Php echo $fila; ?>]" type="button" class="BotonEliminar" name="Btn_IvaMenos[<?Php echo $fila; ?>]" value="-" onclick="busca_iva_quita_btn(form,this)" />
      </span></td>
	  <td align="center">
	    <input id="quitar_fila[<?Php echo $fila; ?>]" type="button" class="BotonEliminar" name="quitar_fila[<?Php echo $fila; ?>]" value="X" onClick="quitar_fila_mod(this, <?Php echo count($rs_interes); ?>); asignar_total_fac(); valor_renta_compra()"><!-- antes val_fac_fila !-->	  	    	                
	    </td>
	  </tr>
	<?Php 
if (count($rs_interes) > 0)
{
foreach($rs_interes as $row_rs_interes)
{ 
	$fila++;
	?>
	<tr>
	  <td>
	    <input name="datos[<?Php echo $fila; ?>,1]" type="hidden" id="datos[<?Php echo $fila; ?>,1]" 
				value="<?Php echo $row_rs_interes['Pro_Cod']?>">
	    <input name="datos[<?Php echo $fila; ?>,2]" type="hidden" id="datos[<?Php echo $fila; ?>,2]" 
			value="<?Php echo $row_rs_interes['Pro_Ide']?>" size="3" maxlength="5" readonly="true">	  </td>			  
	  <td align="center">
	  		<input name="datos[<?Php echo $fila; ?>,3]" type="text" id="datos[<?Php echo $fila; ?>,3]" 
			value="<?Php echo $row_rs_interes['Vet_Can']?>" size="4" maxlength="4" readonly="true" style="text-align:right">	  
      </td>
	  <td>
	  		<input name="datos[<?Php echo $fila; ?>,4]" type="text" id="datos[<?Php echo $fila; ?>,4]" 
		  	value="<?Php echo $row_rs_interes['Ite_Lar']?>" size="40" maxlength="25" readonly="true">	  
      </td>
	  <td align="center">	  
	  		<input name="datos[<?Php echo $fila; ?>,5]" type="text" id="datos[<?Php echo $fila; ?>,5]" 
			value="<?Php echo number_format($row_rs_interes['Vet_Pru'],2); ?>" style="text-align:right" size="7" maxlength="8" readonly="true">	  
      </td>   
	  <td  align="center">
	  		<input name="datos[<?Php echo $fila; ?>,6]" type="text" id="datos[<?Php echo $fila; ?>,6]" 
			value="<?Php echo number_format($row_rs_interes['Vet_Imp'],2); ?>" style="text-align:right" size="8" maxlength="8" readonly="true">	  
      </td>
	  <td  align="center">
	  		<input name="datos[<?Php echo $fila; ?>,7]" type="text" id="datos[<?Php echo $fila; ?>,7]" 
			value="<?Php echo $row_rs_interes['Vet_Dec']?>" style="text-align:right" size="3" maxlength="2" readonly='true'>	  </td>
	  <td align="center">
	  		<input name="datos[<?Php echo $fila; ?>,8]" type="text" id="datos[<?Php echo $fila; ?>,8]" 
			value="<?Php echo $row_rs_interes['Iva_Por']?>" style="text-align:right" size="3" maxlength="3" readonly="true">

<input name="datos[<?Php echo $fila; ?>,9]" type="hidden" id="datos[<?Php echo $fila; ?>,9]" value="<?Php echo 
			$row_rs_interes['Iva_Cod']?>">
	    
	    <input name="datos[<?Php echo $fila; ?>,10]" type="hidden" 
			id="datos[<?Php echo $fila; ?>,10]" value="<?Php echo $row_rs_interes['Nge_Cod']; ?>">
            
	    <input name="datos[<?Php echo $fila; ?>,11]" type="hidden" id="datos[<?Php echo $fila; ?>,11]" value="<?Php 
			echo $row_rs_interes['Vet_Rec']; ?>">
            
	    <input name="datos[<?Php echo $fila; ?>,12]" type="hidden" id="datos[<?Php echo $fila; ?>,12]" value="<?Php 
			echo $row_rs_interes['Asi_Int']?>">	      

		<input name="datos[<?Php echo $fila; ?>,13]" type="hidden" id="datos[<?Php echo $fila; ?>,13]" value="<?Php 
			echo $row_rs_cliente['Cnt_Cod']?>">
            
            <input name="datos[<?Php echo $fila; ?>,14]" type="hidden" id="datos[<?Php echo $fila; ?>,14]" value="<?Php 
			echo $row_rs_cliente['Vet_Int']?>">               
            	  </td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
	  <td align="right">&nbsp;</td>
	  <td align="right">&nbsp;	             
	    </td>
	  </tr>	
<?Php
	}//FIn del foreach interes
 }//Fin del if (count($rs_interes) > 0)
}//Fin del foreach cliente

	/**  
	* Retorno los calculos de las facturas 
	*/
	$resultados = explode('*',$obBD_con1->calculos($codigo, $obBD_conexion));
	?>
	</tbody>
	<tr>
	  <td>&nbsp;</td>
	  <td>&nbsp;</td>
		<td colspan="2" align="right">SUBTOTAL:</td>
		<td align="center">
		  <input name="t_subtotal" type="text" align="left" id="t_subtotal" style="text-align:right" size="8" maxlength="8" readonly="true" 
				value="<?Php echo formato_numero($resultados[0],2,1);  ?>">		</td>
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
	  <td>&nbsp;</td>
	  <td colspan="2" align="right">TARIFA 0%:</td>
	  <td align="center">
	    <input name="t_iva0" type="text" align="right" id="t_iva0" style="text-align:right" size="8" maxlength="8" readonly="true" 
				value="<?Php echo formato_numero($resultados[1],2,1); ?>">	  </td>
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
	  <td>&nbsp;</td>
	  <td colspan="2" align="right">TARIFA 12%:</td>
	  <td align="center">
	    <input name="t_iva12" type="text" align="center" id="t_iva12" style="text-align:right" size="8" maxlength="8" readonly="true" 
				value="<?Php echo formato_numero($resultados[2],2,1); ?>">	  </td>
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
	  <td>&nbsp;</td>
		<td colspan="2" align="right" >12% I.V.A.:</td>
		<td  align="center">
		  <input name="t_iva" type="text" id="t_iva" value="<?Php echo formato_numero($resultados[3],2,1); ?>" style="text-align:right" 
				size="8" readonly="true">		</td>
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
	  <td>&nbsp;</td>
		<td colspan="2" align="right" >% DESCUENTO:
		  <input name="activar1" type="checkbox" id="activar1" value="checkbox"  onClick="validar_text(); asignar_total_fac()" 
			<?php if ($Vet_Des != 0) { echo "checked='checked'"; } ?>>
		  <input name="Vet_Des" type="text" id="Vet_Des" size="2" maxlength="5" value="<?Php echo $rs_cliente[0]['Vet_Des']; ?>" 			
		  <?php if ($rs_cliente[0]['Vet_Des'] == 0) { echo "readonly='true'"; } ?> onBlur="numerico(this)" 
		  onKeyUp="validar_text();asignar_total_fac()">
		  </td>
		<td align="center">
		  <input name="t_descuento" type="text" align="right" id="t_descuento" style="text-align:right" size="8" maxlength="8" readonly="true" 
		  value="<?Php echo formato_numero($resultados[4],2,1); ?>">		</td>
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
     <tfoot>   
	<tr class="Cabecera1" height="35">
	  <td>&nbsp;</td>
	  <td height="24">&nbsp;</td>
		<td colspan="2" align="right">TOTAL:	</td>
		<td align="center">
		  <input name="t_rubros" type="text" align="left" id="t_rubros" style="text-align:right" size="8" maxlength="8" readonly="true" 
			value="<?php echo formato_numero($resultados[5],2,1); ?>">		</td>
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
	
	<table width="506" border="0">
	<tbody id="e_contenido">
	</tbody>
	</table>
	<input id="nfilas" name="nfilas" type="hidden" value="<? echo $fila; ?>">
	<input id="nfilas_elim" name="nfilas_elim" type="hidden" value="">	
	

<table width="600" border="0" cellspacing="0" cellpadding="0">
 <tr>
    <td>
     <div id="cont_fon_iva" class="bgtransparent" style="display:none">
        </div>        
        <div id="cont_cua_iva"  class="bgmodal"   style="display:none" >
        <div id="cont_cua_iva_titu"></div>
                 <?Php 
                 /* Codigo del periodo contable */
				 //$Pla_Cod=20;
				// $Pec_Cod=20;
                 $Com_Pec_Cod = $Pec_Cod;
               include('../COMPONENTES/tesComBusRentaIva.php'); ?>
        </div>
     </td>
   </tr>
 </table>      
    
<table width="100%" border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td width="3">&nbsp;</td>
    <td width="109" valign="top">
    <button type="button" name="button1" id="button1" class="btn btn-success fileinput-button" title="Deudas" onClick="document.getElementById('tipo_boton').value=1; ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?deudas=1&codigo=<?php echo $codigo_cli; ?>','ajax_modal')">
           <i class="icon-th icon-white"></i>
           <span>Deudas</span>
    </button> 
    </td>
    <td width="125" valign="top"><button type="button" name="button2" id="button2" class="btn btn-success fileinput-button" title="Buscar Item" onclick=" document.getElementById('tipo_boton').value=2; ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_rubro=1&ajax_Cli_Cod=<?php echo $codigo_cli; ?>','ajax_modal');">
           <i class="icon-plus icon-white"></i>
           <span>Items</span>
           </button>
            <script language="javascript">
		       ShowHide('cheque'); //Utilizado para el segundo pago
			   ShowHide('button2'); //oculta boton
			   ShowHide('button1'); //oculta boton
	        </script>
           
                </td>
    <td width="404" align="right">&nbsp;</td>
    <td width="524" align="left"><fieldset>
      <legend>
        <label class="Titulos2">Datos de la retenci&oacute;n</label>
        </legend>
      <table width="100%" border="0" cellpadding="0" cellspacing="0" >
        <tr>
          <td class="Etiqueta1"> Fecha Emisi&oacute;n:</td>
          <td align="right" class="Etiqueta1"><div align="left">
            <input name="Ret_Fec" id="Ret_Fec" type="text" onkeyup="mascara(this,'-',patron,true)" value="<? echo $row_rs_cliente['Ret_Fec'];?>" size="10" maxlength="15" />
          </div></td>
        </tr>
        <tr>
          <td class="Etiqueta1">Num. Secuencia:</td>
          <td align="left" class="Etiqueta1"><div align="left">
            <input name="Num_Ret" id="Num_Ret" type="text"  size="20" maxlength="15" value="<? echo $row_rs_cliente['Ret_Num'];?>" align="right" />
          </div></td>
        </tr>
        <tr>
          <td class="Etiqueta1">Num. Autorizaci&oacute;n:</td>
          <td class="Etiqueta1"><div align="left">
            <input name="Num_Aut" id="Num_Aut" type="text"  size="35" maxlength="37" value="<? echo $row_rs_cliente['Ret_Aut'];?>"  align="right" />
          </div></td>
        </tr>
        <tr>
          <td width="22%" class="Etiqueta1"><input name="Hdd_Ret" id="Hdd_Ret" type="hidden" value="N" />
            Renta:&nbsp;</td>
          <td width="78%" align="left" class="Etiqueta1"><div align="left">
            <input name="Ren_Ren" id="Ren_Ren" type="text"  size="5" maxlength="8"  align="right" readonly="readonly" value="<?Php echo round($base_renta,3); ?>" style="text-align:right" />
            &nbsp;+&nbsp; I.V.A:
            <input name="Rei_Iva" id="Rei_Iva" type="text" class="" size="5" maxlength="8" readonly="readonly" value="<?Php echo round($base_iva,3); ?>"  style="text-align:right" />
            &nbsp;=&nbsp;
            <?Php 
		       $bases_ivas=round($base_renta,2)+round($base_iva,2); //$bases_ivas=$base_renta+$base_iva;
		    ?>
            <input name="Riv_Tot" id="Riv_Tot" type="text" class="" size="5" maxlength="8" readonly="readonly" value="<?Php echo round($bases_ivas,4); ?>" style="text-align:right" />
            Valor retenido </div></td>
        </tr>
        <tr>
          <td class="Etiqueta1" >Valor a pagar:&nbsp;</td>
          <td align="" class="Etiqueta1"><div align="left">
            <?Php  		/* valor a pagar */
			$valor_retenciones=round($bases_ivas,3);//Agregado round 2010-05-05 antes $valor_retenciones=round($base_renta,3)+round($base_iva,3);
	   		$valor_cheque=round($resultados[5],2)-$valor_retenciones; //Es necesario redondear $resultados[5] porque su valor se agrega decimales sin el programador requerirlo 
?>
            <input name="Val_Pcc" id="Val_Pcc" type="text"  size="5" maxlength="8"  align="right" readonly="readonly" value="<?Php echo round($valor_cheque,2); ?>" style="text-align:right" />
          </div></td>
        </tr>
      </table>
    </fieldset></td>
    <input id="nfilas2" name="nfilas2" type="hidden" value="0">
  </tr>
</table>
<br>
</fieldset>
<br>
<table width="303" border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td width="106">
    <button type="button" class="btn btn-inverse fileinput-button" title="Atrás" onClick="campos_hide(this.form, '<?Php echo "txt_busqueda*op_opciones*hdd_volver*cmb_anio*cmb_mes*Tic_Cod"; ?>', '<?Php echo $volver_busqueda.'*'.$volver_op.'*1*'.$volver_anio.'*'.$volver_mes.'*'.$volver_Tic_Cod; ?>')">
                    <i class=" icon-arrow-left icon-white"></i>
                    <span>&nbsp;&nbsp;Atr&aacute;s&nbsp;&nbsp;</span>
       </button>
    </td>
    <td width="197">
    <button type="button" class="btn btn-primary start" title="Guardar" onclick="validar_facturacion(this.form)">
           <i class="icon-book icon-white"></i>
           <span>Guardar</span>
    </button>             
      <input name="hdd_save" type="hidden" id="hdd_save" value="insertar">
      
     <input name="txt_busqueda" id="txt_busqueda" type="hidden" value="<?Php echo $volver_busqueda;?>">
	      <input name="op_opciones" id="op_opciones" type="hidden" value="<?Php echo $volver_op;?>">
	      <input name="cmb_anio" id="volver_anio" type="hidden" value="<?Php echo $volver_anio;?>">				
	      <input name="cmb_mes" id="cmb_mes" type="hidden" value="<?Php echo $volver_mes;?>">
	      <input name="Tic_Cod" id="volver_Tic_Cod" type="hidden" value="<?Php echo $volver_Tic_Cod;?>">  
          <input type="hidden" id="Vnd_Cod" name="Vnd_Cod" value="<? echo $Vnd_Cod;?>" />
      </td>
  </tr>
</table>
<br>
<!--</div>-->
	  <input id="tipo_boton" name="tipo_boton" type="hidden" value="">
   	<?Php
	/** 
	* Control para activar el 2 tipo de pago 
	*/
	if (count($rs_pago_fac) == 1)
	{ ?>
     <script language="javascript">
		 ShowHide('cheque'); //Utilizado para el segundo pago
	 </script>
	<?php
	}//Fin del if ($total_rs_pago_fac)
}//Fin del if ($codigo > 0 && !(isset($hdd_save))) 
$cant_modal = 2 + count($rs_buscar);
?><input name="cantmodal" id="cantmodal" type="hidden" value="<?php echo $cant_modal; ?>">
<div id="bgtransparent" class="bgtransparent" style="display:none" onclick="closeModal()">
</div>
<div id="bgmodal"  class="bgmodal"  style="display:none">		
	<div id="ajax_modal"></div>
</div>
  </form></td>
  </tr>  
</table>
</div>
<!-- Librerias para el tratamiento de la interfaz - cajas de texto -->
<script type="text/javascript" src="../VALIDACIONES/fac_par_fac_ven.js"></script>
<script type="text/javascript" src="../../Librerias/textbox/main.js"></script>
</BODY>
</HTML>
<?php
@$obBD_conexion->cerrar();
?>