<?php require_once('../../administrador/LOGICA/seguridad.php');
	  require_once('../../Librerias/operacion.php'); 
  	  require_once('../LOGICA/logica.php');	  
   	  require_once('../../Librerias/procedimientos/almacenados_standar.php');	  
		//*********************Almacena los datos modificados***********************************************************************************
	if (isset($hdd_save))
	{
		$var_codigo = $row_rs_consulta['Caj_Fec'];
		//***************Inicio de la transaccion***********************
		$conexion=open_trans_tes();
		//**************************************************************			
		$Caj_Fec = date("$ann_ini/$mes_ini/$dia_ini");	
		$Caj_Fef = date("$ann_ini/$mes_ini/$dia_ini");		
		//**************Fin de la transaccion****************************	
		close_trans_tes($conexion);
		//***************************************************************
		unset($codigo); 	
    }
	//*******************Busqueda de la apertura de caja***********************************************************************************
	if ($ann_ini != "" && $mes_ini != "" && $dia_ini != "")
    {
			$rs_buscar = consultas_tes(8, "$ann_ini/$mes_ini/$dia_ini".'*'.$Pun_Cod);	
	        $row_rs_buscar = mysqli_fetch_assoc($rs_buscar);
  	        $total_rs_buscar = mysqli_num_rows($rs_buscar); 
	}
	else
	{
	//*********************Consulta realizada en base al comprobante seleccionado***********************************************************************
		if (isset($codigo))
		{
			$rs_consulta = consultas_tes(9, $codigo);
			$row_rs_consulta = mysqli_fetch_assoc($rs_consulta);
			$total_rs_consulta = mysqli_num_rows ($rs_consulta);		//************************************************************************************************************************************************
		}
	}				
/*Consulta del vendedor en base al codigo de la persona*/
$rs_vendedor = consultas_tes(24, $Prs_Pee);
$row_rs_vendedor = mysqli_fetch_assoc ($rs_vendedor);
?>
<HTML>
	<HEAD>
		<TITLE>Ginus</TITLE>
		<link href="../../Estilos/Interfaz1.css" rel="stylesheet" type="text/css">
		<link href="../../Estilos/Estilo1.css" rel="stylesheet" type="text/css">
		<script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>
    	<script language="javascript" src="../validaciones/validaciones.js"></script>
		<script language="javascript" src="../../Librerias/fecha.js"></script>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	</HEAD>
<BODY>
<table width="540" border="0" align="center" cellpadding="0" cellspacing="0">
	<tr class="Titulos1">
	  <td height="10"><span class="Titulos1">&raquo;</span> CONSULTA DE CAJA </td>
    </tr>
	<tr>
        <td height="20" valign="top">
         <form name="form1" method="post" action="<?Php $_SERVER['PHP_SELF']?>">
         <input name="Pun_Cod" type="hidden" id="Pun_Cod" value="<?PHP echo $row_rs_vendedor['Pun_Cod']; ?>">
         <br>
            <FIELDSET>
            <LEGEND>
            <label class="Titulos2">Buscar por:</label>
            </LEGEND>
        <table width="98%" height="36" border="0" cellpadding="0" cellspacing="0">
        <tr class="">
		    <td width="99" class="Cabecera"><strong>  Fecha: </strong></td>
        <td width="489" class="Cabecera">A&ntilde;o
            <select name="ann_ini" onChange="asignaDias(document.form1.dia_ini, document.form1.mes_ini, document.form1.ann_ini)">
              <option></option>
              <?Php   
				for ($i=date("Y")-1; $i<= date("Y"); $i++)
				{
			?>
              <option value="<?Php echo $i ?>"><?Php echo $i ?> </option>
              <?Php
				}
			?>
            </select>
  mes
  <select name="mes_ini" onChange="asignaDias(document.form1.dia_ini, document.form1.mes_ini, document.form1.ann_ini)">
    <option></option>
    <?Php 
			//*******************Iniciacion del arreglo de meses**********************************************************************
				$row_rs_des = array ("Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", 
						"Octubre", "Noviembre", "Diciembre");
				for ($i=1; $i<=12;$i++)
				{
			?>
    <option value="<?Php echo $i; ?>"> <?Php echo $row_rs_des[$i-1] ?> </option>
    <?Php
				}
			?>
  </select>
  dia <span class="Label1">
  <select name="dia_ini" id="dia_ini">
  <option></option>
               <?Php
			  	for ($i=1; $i<=31;$i++)
				{
				?>
    <option value="<?Php echo $i; ?>"><?Php echo $i; ?> </option>
    <?Php
			  	}
			   ?>
  </select>
</span> </td>
             <td width="99" class="Cabecera">&nbsp;</td>
			<td width="85"><div align="center">
			  <input name="btn_buscar" type="button" class="Boton_Buscar" title "Buscar" id=              "btn_buscar" onClick="validar_buscarcaja()" value="Buscar">
			  </div>			  </td>
        </tr>
      </table>
</FIELDSET>
  <?Php
  	if(isset($dia_ini, $mes_ini, $ann_ini))
	{
		if($total_rs_buscar != 0)
		{
  ?>
  <br>
<FIELDSET>
<LEGEND>
<label class="Titulos2">Resultados de la busqueda</label>
</LEGEND>
	<table width="530"  height="20" border="1" cellpadding="0" cellspacing="0" class="Fondo">
	  <tr class="Cabecera">
          <td width="90">Fecha de apertura </td>
		  <td width="70">Hora de apertura </td>
          <td width="90">Fecha de cierre</td>
          <td width="70">Hora de cierre </td>
          <td width="20">Existencia</td>
    	  <td width="10">Estado</td>
		  <td width="100">Observaciones</td>
      </tr>
	  <?Php do { ?>
	  <tr class="Fondo">
		<td align="center"><?Php echo $row_rs_buscar['Caj_Fec']; ?>&nbsp;</td>
		<td align="center"><?Php echo $row_rs_buscar['Caj_Hoi']; ?>&nbsp;</td>
		<td align="center"><?Php echo $row_rs_buscar['Caj_Fef']; ?>&nbsp;</td>
		<td align="center"><?Php echo $row_rs_buscar['Caj_Hof']; ?>&nbsp;</td>
    	<td align="center"><?Php echo $row_rs_buscar['Caj_Exi']; ?>&nbsp;</td>
		<td align="center"><?Php echo $row_rs_buscar['Caj_Est']; ?>&nbsp;</td>
		<td align="center"><?Php echo $row_rs_buscar['Caj_Obs']; ?>&nbsp;</td>
     </tr>
	  <?Php } while ($row_rs_buscar = mysqli_fetch_assoc($rs_buscar)); ?>
  </table>
</FIELDSET>
  <?Php 
  	}
	else
	{
  ?>
  		<label class="Alertas">No hay resultados que mostrar</label>
  <?Php
	}
  }
  ?>
         </form></td>
  </tr>
</table>	   
</BODY></HTML>
<?Php
if (isset ($rs_buscar))
	{
	}
	if (isset($rs_consulta))
	{
	}
?>
