<?php require_once('../../administrador/LOGICA/seguridad.php');
  	  require_once('../LOGICA/logica.php');	  
      require_once('../../Librerias/procedimientos/almacenados_academico.php');
   	  require_once('../../Librerias/procedimientos/almacenados_standar.php');	  

//*********************Almacena los datos modificados***********************************************************************************
	if (isset($hdd_save))
	{
		//$modbancos = $row_rs_consultar['Bak_Des'];
		//************************ Modificación de los datos del banco *****************************************************************
			insercionesu_tes(103, $Bak_Des.'*'.$codigo);
		//***************************************************************
		unset($codigo); 	
    }
	
		//*******************Busqueda del banco ******************
	if ($txt_busqueda != "")
	{
	  	$rs_buscar = consultas_tes(104, $codigo);
		$row_rs_buscar = mysqli_fetch_assoc($rs_buscar);
		$total_rs_buscar = mysqli_num_rows ($rs_buscar);	
	}
	else
	{
//*********************Consulta realizada en base al código seleccionada***********************************************************************
		if (isset($codigo))
		    {
			$rs_consultar = consultas_tes(105, $codigo);
			$row_rs_consultar = mysqli_fetch_assoc($rs_consultar);
			$total_rs_consultar = mysqli_num_rows ($rs_consultar);
			}
		}	
?>
<HTML>
	<HEAD>
		<TITLE>Ginus</TITLE>
		<link href="../../Estilos/Estilo1.css" rel="stylesheet" type="text/css">
		<link href="../../Estilos/Interfaz1.css" rel="stylesheet" type="text/css">
		<link href="../../mascaras/model1/estilos/estilo1.css" rel="stylesheet" type="text/css">
		<link href="../../mascaras/model1/estilos/interfaz.css" rel="stylesheet" type="text/css">
		
		
		<script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>
		<script language="javascript" src="../VALIDACIONES/Validaciones.js"></script>
		<script language="javascript" src="../../Librerias/fecha.js"></script>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	</HEAD>
<BODY>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr class="BarraTitulo">
	  <td height="10"><span class="BarraTitulo">&raquo;</span> modificacion de bancos </td>
  </tr>
	<tr>
        <td height="389" valign="top">
          <form name="form1" method="post" action="<?Php $_SERVER['PHP_SELF']?>">
            <br>
  <label class="Titulos2"></label>
  <FIELDSET>
   <table width="495" height="36" border="0" cellpadding="0" cellspacing="0">
    <tr>
      <td width="110" height="28" class="BarraBusqueda"><div align="right">Busqueda:</div></td>
      <td width="376" class="BarraBusqueda"><input name="txt_busqueda" type="text" id="txt_busqueda" value="" size="50" maxlength="50" style="text-transform:uppercase "></td>
      <td width="93"><div align="center">
        <input name="btn_buscar" type="button" class="Boton_Buscar" id="btn_buscar" onClick="validar_buscar()" value="Buscar">
      </div></td>
    </tr>
  </table>
</FIELDSET>
  
  <?Php
  	if(isset($txt_busqueda))
	{
		if($total_rs_buscar != 0)
		{
  ?>
<FIELDSET>
<LEGEND>
<label class="Titulos2">Resultados de la busqueda</label>
</LEGEND>
	<table width="512" border="1" cellpadding="0" cellspacing="0">
	  <tr class="Cabecera1">
          <td width="402">Descripci&oacute;n</td>
		  <td width="55">&nbsp;</td>
      </tr>
	  <?Php do { ?>
	  <tr class="Fondo">
	  <td height="20"><?php echo $row_rs_buscar['Bak_Des']; ?></td>
	  <td align="center"><a href="<?Php echo $_POST['form2'];?>?codigo=<?Php echo $row_rs_buscar['Bak_Cod'];?>" title="Editar"><img src="../../imagenes/editar.jpg" width="18" height="18" border="0"></a> 
  </td>
	  </tr>
	  <?Php } while ($row_rs_buscar = mysqli_fetch_assoc($rs_buscar));?>
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
</form>
<form method="post" name="form2" action="<?Php $_SERVER['form2'] ?>">
<?Php 

if ($total_rs_consultar > 0) { ?>

<FIELDSET>
<LEGEND>
<label class="Titulos2">Datos a modificar</label>
</LEGEND>
<table width="100%" border="0">
  <tr>
    <td width="965"><?Php echo mensaje_requerido(); ?></td>
  </tr>
</table>
<table border="0">
  <tr>
    <td width="133" class="Etiqueta1"><span class="Asterisco">*</span> Descripci&oacute;n:</td>
    <td width="343">
      <input name="Bak_Des" id="Bak_Des" type="text" size="30" maxlength="30" style="text-transform:uppercase" value="<?Php echo $row_rs_consultar['Bak_Des'] ?>">    </td>
  </tr>
</table>
</FIELDSET> 
  <br>
  <input name="hdd_save" type="hidden" id="hdd_save" value="insertar">
  <table width="100" border="0" class="Azul">
  <tr>
    <td width="100%" height="23"><div align="center"><font color="#3162a6" face="Arial, Helvetica, sans-serif">
   <input name="btn_guardar" type="button" class="Boton_Actualizar" onClick= "validar_bancos(form2)" value= "Actualizar">
	  </font></div></td>
  </tr>
</table>
<?Php } ?>
</form>      </td>
  </tr>

</table>	   
</BODY></HTML>
<?Php
if (isset ($rs_buscar))
	{
		mysqli_free_result($rs_buscar);
	}
	if (isset($rs_consultar))
	{
		mysqli_free_result($rs_consultar); 
	}
?>
