<?php require_once('../../administrador/LOGICA/seguridad.php');
	  require_once('../../Librerias/operacion.php'); 
	  require_once('../LOGICA/logica.php');
      require_once('../../Librerias/procedimientos/almacenados_academico.php');	
//*******************Busqueda del proveedor************************************************************************************
echo $Pun_Cod;
	if ($txt_busqueda != "")
	{
	    if ($op_opciones == "d")
		{
			$rs_buscar = consultas_tes(422,$txt_busqueda);
		}
		else
		{
			$rs_buscar = consultas_tes(423,$txt_busqueda);
		}  
		$row_rs_buscar = mysqli_fetch_assoc($rs_buscar);
 	    $total_rs_buscar = mysqli_num_rows($rs_buscar); 
	}
	else
	{
	//*********************Consulta realizada en base el proveedor seleccionado***********************************************

		if (isset($codigo))
		{
			$rs_consulta = consultas_tes(424,$codigo);
			$row_rs_consulta = mysqli_fetch_assoc($rs_consulta);
			$total_rs_consulta = mysqli_num_rows ($rs_consulta);	
			//*********************Consulta de las ciudades****************************************************************************
			$rs_ciudades = ciudad();
			$row_rs_ciudades= mysqli_fetch_assoc($rs_ciudades);
			
			//Consulta del vendedor en base al punto de impresion
	         $rs_ven_punto = consultas_tes(463,"");
	         $row_rs_ven_punto = mysqli_fetch_assoc($rs_ven_punto); 
             $total_rs_ven_punto = mysqli_num_rows($rs_ven_punto);		
	            
		}
	}
//*********************Almacena los datos modificados********************************************************************************
	if (isset($hdd_save))
	{
    	$conexion=open_trans_tes();
		insercionesv_tes(409, $Prs_Ced.'*'.$Prs_Nom.'*'.$Prs_Ape.'*'.$Prs_Sex.'*'.$Prs_Dir.'*'.$Ciu_Cod.'*'.$Prs_Tel.'*'.
		$Prs_Cel.'*'.$codigo, $conexion);	
						
		insercionesv_tes(425, $Pun_Cod.'*'.$codigo, $conexion);		
		close_trans_tes($conexion);
		
		unset($codigo);
		unset($row_rs_consulta);
	 }			
?>

<HTML>
	<HEAD>		
		<link href="../../Estilos/Estilo1.css" rel="stylesheet" type="text/css">
		<link href="../../mascaras/model1/estilos/interfaz.css" rel="stylesheet" type="text/css">
		<link href="../../mascaras/model1/estilos/estilo1.css" rel="stylesheet" type="text/css">
		<link href="../../Estilos/Interfaz1.css" rel="stylesheet" type="text/css">	
		<script language="javascript" src="../Librerias/java.js"></script>
		
		<script language="javascript" src="../VALIDACIONES/Validaciones.js"></script>
		<script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>
		
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	<?php 
if (isset($hdd_save)) 
{ ?>
	<script language="javascript">
	ir('tes_mod_vendedor.php');
	</script>
<?Php 
}
?>
	</HEAD>
<BODY>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr class="BarraTitulo">
	  <td height="10">&raquo; modificacion de vendedor </td>
  </tr>
	<tr>
        <td height="389" valign="top">
          <form name="form1" method="post" action="<?Php $_SERVER['PHP_SELF']?>">
   <fieldset>
   <br>
  <legend>
  <label class="Titulos2">Buscar por:</label>
  </legend>
<table width="495" border="0">
    <tr>
      <td width="205"><input name="op_opciones" type="radio" value="d" checked>
        <span class="LetraNegra">Apellido</span></td>
      <td width="266"><input type="radio" name="op_opciones" value="r">
        <span class="LetraNegra">C&eacute;dula</span></td>
    </tr>
  </table>
  <table width="495" height="36" border="0" cellspacing="0">
    <tr>
      <td width="88" height="28" class="BarraBusqueda"><div align="right">Busqueda:</div></td>
      <td width="340" class="BarraBusqueda"><input name="txt_busqueda" type="text" id="txt_busqueda" value="" size="50" maxlength="50" style="text-transform:uppercase "></td>
      <td width="81"><div align="center">
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
 <br>
<FIELDSET>
<LEGEND>
<label class="Titulos2">Resultados de la busqueda</label>
</LEGEND>
	<table border="1" cellpadding="0" cellspacing="0">
	  <tr class="Cabecera1">
          <td width="80">C&eacute;dula</td>
          <td width="364">Nombres y Apellidos </td>
		  <td width="22">&nbsp;</td>
      </tr>
	  <?Php do { ?>
	  <tr class="Fondo">
		<td><?Php echo $row_rs_buscar['Prs_Ced']; ?></td>
		<td><?Php echo $row_rs_buscar['Prs_Ape'];?>  <?Php echo $row_rs_buscar['Prs_Nom']; ?></td>
		<td align="center"><a href="<?Php echo $_POST['form2'];?>?codigo=<?Php echo $row_rs_buscar['Prs_Cod'];?>" title="Editar"><img src="../../imagenes/editar.jpg" height="18" width="18" border="0"></a></td>		
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
</form>
  
<form method="post" name="form2" action="<?Php $_SERVER['form1'] ?>">
<?Php
if (isset($codigo) && !(isset($txt_busqueda)))
{
?>
<FIELDSET>
<LEGEND>
<label class="Titulos2">Datos a modificar</label>
</LEGEND>
<table width="100%" border="0">
  <tr>
    <td width="965"><span class="Titulos2">NOTA:</span> <span class="LetraNegra">Los campos que se encuentran marcados con un asterisco ( </span><span class="Asterisco">*</span><span class="LetraNegra"> ) son campos obligatorios y no pueden ser dejados en blanco. </span></td>
  </tr>
</table>
<hr>
<table border="0">
  <tr>
    <td class="Etiqueta1"><span class="Asterisco"> *</span> C&eacute;dula:</td>
    <td><span class="LetraNegra">
      <input name="Prs_Ced" type="text" readonly="true" size="10" maxlength="10" onBlur="numerico(this)" value="<?Php echo $row_rs_consulta['Prs_Ced']?>">
    </span></td>
  </tr>
  <tr>
    <td class="Etiqueta1"><span class="Asterisco">*</span> Nombres:</td>
    <td><input name="Prs_Nom" type="text" readonly="true" size="30" maxlength="30" style="text-transform:uppercase" value="<?Php echo $row_rs_consulta['Prs_Nom']?>"></td>
  </tr>
  <tr>
    <td class="Etiqueta1"><span class="Asterisco">* </span>Apellidos: </td>
    <td><input name="Prs_Ape" type="text" readonly="true" style="text-transform:uppercase" value="<?Php echo $row_rs_consulta['Prs_Ape']?>" size="30" maxlength="30">    </td>
  </tr>
  <tr>
    <td class="Etiqueta1"><span class="Asterisco">*</span> Sexo: </td>
    <td><select name="Prs_Sex" id="Prs_Sex">
      <?php
		  	if (isset($codigo) && $row_rs_consulta >0)
			{
			?>
      <option <?Php if($row_rs_consulta['Prs_Sex']=='F'){ echo "selected";} ?> value="F">Femenino </option>
      <option <?Php if($row_rs_consulta['Prs_Sex']=='M'){ echo "selected";} ?> value="M">Masculino </option>
      <?php
				} 
		?>
    </select>
	</td>
  </tr>
  <tr>
    <td class="Etiqueta1"><span class="Asterisco">*</span> Direcci&oacute;n domiciliaria:</td>
    <td><input name="Prs_Dir" type="text" style="text-transform:uppercase" id="Prs_Dir" value="<?Php echo $row_rs_consulta['Prs_Dir']?>" size="50" maxlength="50"></td>
  </tr>
  <tr>
    <td class="Etiqueta1"><span class="Asterisco">*</span> Ciudad: </td>
    <td><select name="Ciu_Cod" id="Ciu_Cod">
      <?php
		  	if (isset($codigo) && $row_rs_consulta >0)
			{
				do {  
			?>
      <option <?Php if($row_rs_ciudades['Ciu_Cod'] == $row_rs_consulta['Ciu_Cod']){ echo "selected";} ?> value="<?php echo $row_rs_ciudades['Ciu_Cod']?>"><?php echo $row_rs_ciudades['Ciu_Des']?></option>
      <?php
				} while ($row_rs_ciudades = mysqli_fetch_assoc($rs_ciudades));
				  $rows = mysqli_num_rows($rs_ciudades);
				  if($rows > 0) 
				  {
				      mysqli_data_seek($rs_ciudades, 0);
					  $row_rs_ciudades = mysqli_fetch_assoc($rs_ciudades);
			   	  }
			}
		?>
    </select></td>
  </tr>
  <tr>
    <td class="Etiqueta1"> Tel&eacute;fono:</td>
    <td><input name="Prs_Tel" type="text" value="<?Php echo $row_rs_consulta['Prs_Tel']?>" size="15" maxlength="15" onBlur="numerico(this)"></td>
  </tr>
  <tr>
    <td class="Etiqueta1">Celular:</td>
    <td><input name="Prs_Cel" type="text" value="<?Php echo $row_rs_consulta['Prs_Cel']?>" size="15" maxlength="15" onBlur="numerico(this)">    </td>
  </tr>
</table>
</FIELDSET>
       <fieldset>
       <br>
	   <LEGEND>
       <label class="Titulos2">Punto de Impresión</label>
       </LEGEND>
	   <table width="556">
	     <tr>
        <td width="134" class="Etiqueta1"><span class="Asterisco">*</span> Ubicación:</td>
        <td width="410">
				 <select name="Pun_Cod" id="Pun_Cod">
				 <option></option>
			     <?php
				      do {  
			      ?>
                 <option <?Php if($row_rs_ven_punto['Pun_Cod'] == $row_rs_consulta['Pun_Cod']) { echo "selected";} ?> 
				 value="<?php echo $row_rs_ven_punto['Pun_Cod']?>"><?Php echo $row_rs_ven_punto['Pun_Des']?></option>	 
			     <?Php 
				 } while($row_rs_ven_punto=mysqli_fetch_assoc($rs_ven_punto));  
				?>
            </select>
    </table>
	</fieldset>
  <br>
 <table width="100" border="0" class="Azul">
  <tr>
    <td width="100%" height="23"><div align="center"><font color="#3162a6" face="Arial, Helvetica, sans-serif">
		<input name="btn_guardar" type="button" class="Boton_Guardar" id="Boton" value= "Actualizar" onClick=<?Php if ($total_rs_persona == 0){ ?>"validar_persona_vendedor(document.form2)"<?Php } else {?> "validar_vendedor(document.form2)" <?Php } ?>>
        <?php 
	} ?>
        <input name="hdd_save" type="hidden" id="hdd_save" value="insertar">
    </font></div>	</td>
  </tr>
</table>
  </form>       
 </td>
 </tr>
</table>	    
</BODY>
</HTML>
<?Php
	if (isset ($rs_buscar))
	{
		mysqli_free_result($rs_buscar);
	}
	
	if (isset($rs_consulta))
	{
		mysqli_free_result($rs_consulta); 		
	}
	
	if (isset($rs_ciudades))
	{
		mysqli_free_result($rs_ciudades);		
	}
		
?>