<?php require_once('../../administrador/LOGICA/seguridad.php');
	  require_once('../LOGICA/logica.php');	
	  require_once('../../Librerias/operacion.php'); 	    
      require_once('../../Librerias/procedimientos/almacenados_academico.php');
   	  require_once('../../Librerias/procedimientos/almacenados_standar.php');	  
if (isset($Comprobar))
{
	//Consulta de verificacion en la tabla persona
	$rs_persona = consultas_tes(402, $Prs_Ced);
	$row_rs_persona = mysqli_fetch_assoc ($rs_persona);
	$total_rs_persona = mysqli_num_rows ($rs_persona);
	$Prs_Cod = $row_rs_persona['Prs_Cod'];
	//Consulta de la existencia del vendedor en la tabla persona
	$rs_comprobar = consultas_tes(420, $Prs_Cod);
	$total_rs_comprobar = mysqli_num_rows ($rs_comprobar);
}	  
	if (isset($hdd_save)) 
	{
			//***************Inicio de la transaccion***********************
			$conexion=open_trans_tes();
			//**************************************************************			
				//Consulta de verificacion en la tabla persona
				//********************************************
				$rs_persona = consultas_tes(402, $Prs_Ced);
				$total_rs_persona = mysqli_num_rows ($rs_persona);
				if ($total_rs_persona == 0) //Entra solo cuando la persona no este registrada
				{			
				insercionesv_tes(401, $Prs_Ced.'*'.$Prs_Nom.'*'.$Prs_Ape.'*'.$Prs_Sex.'*'.$Prs_Dir.'*'.$Prs_Tel.'*'.$Prs_Cel.'*'.$Ciu_Cod, $conexion);					
					$Prs_Cod = mysqli_insert_id($conexion);
				}					
				//**************Agregar Cliente******************************			
				insercionesv_tes(421, $Prs_Cod.'*'.$Pun_Cod, $conexion);		
			//**************Fin de la transaccion****************************	
			close_trans_tes($conexion);
			//***************************************************************	
			$Prs_Ced = "";			
?>
<?Php
	}	  
?>
<HTML>
	<HEAD>
		<TITLE>Ginus</TITLE>
		<link href="../../Estilos/Estilo1.css" rel="stylesheet" type="text/css">
		<link href="../../Estilos/Interfaz1.css" rel="stylesheet" type="text/css">
		<link href="../../mascaras/model1/estilos/estilo1.css" rel="stylesheet" type="text/css">
		<link href="../../mascaras/model1/estilos/interfaz.css" rel="stylesheet" type="text/css">		
		<script language="javascript" src="../Librerias/java.js"></script>
		<script language="javascript" src="../VALIDACIONES/Validaciones.js"></script>
		<script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	<?php 
if (isset($hdd_save)) 
{ ?>
	<script language="javascript">
	ir('tes_alt_vendedor.php');
	</script>
<?Php 
}
?>
	</HEAD>
<BODY>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr class="BarraTitulo">
	  <td height="10">&raquo; registro de vendedor </td>
  </tr>
	<tr>
        <td height="389" valign="top">
         <form method="post" name= "form1" action="<?php echo $_SERVER['PHP_SELF'];?>">
  <table width="100%" height="23"
   border="0" align="left" bgcolor="#C7E0CD" >
  </table>
  <br>
<FIELDSET>
<LEGEND>
<label class="Titulos2">Datos a registrar</label>
</LEGEND>
  <table width="537" border="0">
  <tr>
       <td width="758"><?Php echo mensaje_requerido(); ?></td>
  </tr>
  </table>
  <table border="0">
      <tr>
        <td width="107" class="Etiqueta1"><span class="Asterisco">*</span> C&eacute;dula:</td>
        <td width="306" class="LetraNegra"><input name="Prs_Ced" type="text" size="13" maxlength="13" onBlur="numerico(this)" value="<?Php echo $Prs_Ced ?>">        
		 <span class="Texto_Reporte_Rojo">
          <input name="Prs_Cod" type="hidden" id="Prs_Cod" value="<?php echo $row_rs_persona['Prs_Cod']; ?>">
        </span>&nbsp;&nbsp;&nbsp;<span class="Texto_Reporte_Rojo">&nbsp;
        <?Php if (isset($Comprobar)){if ($total_rs_comprobar > 0 ) { echo "El vendedor ya existe";} else {echo "EL VENDEDOR NO EXISTE";}} ?>
        </span></td>
        <td width="84">
<?php 
if (!(isset($Comprobar)) || $total_rs_comprobar > 0)
{
?>		
<input name="Comprobar" type="submit" class="Boton_Actualizar" id="Comprobar" value="Comprobar">
<?Php
}
?></td>
      </tr>
  <?php 
	if (isset($Comprobar) && $total_rs_persona == 0)
	{
		//*********************Consulta de las ciudades*********************************************************************************
		$rs_ciudad = ciudad();
		$row_rs_ciudad = mysqli_fetch_assoc($rs_ciudad);
		$total_rs_ciudad = mysqli_num_rows($rs_ciudad);		
	?>	
      <tr>
        <td class="Etiqueta1"><span class="Asterisco">*</span> Nombres:</td>
        <td><input name="Prs_Nom" type="text" size="30" maxlength="30" style="text-transform:uppercase" value=""></td>
        <td>&nbsp;        </td>
      </tr>
      <tr>
        <td class="Etiqueta1"><span class="Asterisco">* </span>Apellidos: </td>
        <td>
          <input name="Prs_Ape" type="text" style="text-transform:uppercase" value="" size="30" maxlength="30">        </td>
        <td>&nbsp;</td>
      </tr>
      <tr>
        <td class="Etiqueta1"><span class="Asterisco">*</span> Sexo: </td>
        <td><select name="Prs_Sex">
            <option></option>
            <?php 
				$row_rs_cod = array ("M","F");
				$row_rs_des = array ("Masculino", "Femenino");
				for ($i=0;$i<count($row_rs_cod);$i++) 
			 	{  
	  ?>
            <option value="<?php echo $row_rs_cod[$i]?>"><?php echo $row_rs_des[$i]?></option>
            <?php
			 	}
			?>
        </select></td>
        <td>&nbsp;</td>
      </tr>
      <tr>
        <td class="Etiqueta1"><span class="Asterisco">*</span> Direcci&oacute;n domiciliaria:</td>
        <td><input name="Prs_Dir" type="text" style="text-transform:uppercase" id="Prs_Dir" value="" size="50" maxlength="50"></td>
        <td class="Asterisco">&nbsp;</td>
      </tr>
      <tr>
        <td class="Etiqueta1"><span class="Asterisco">*</span> Ciudad: </td>
        <td><select name="Ciu_Cod">
            <option></option>
            <?php
				do {  
			?>
            <option value="<?php echo $row_rs_ciudad['Ciu_Cod']?>"><?php echo $row_rs_ciudad['Ciu_Des']?></option>
            <?php
				} while ($row_rs_ciudad = mysqli_fetch_assoc($rs_ciudad));
		?>
        </select></td>
        <td>&nbsp;</td>
      </tr>
      <tr>
        <td class="Etiqueta1"> Tel&eacute;fono:</td>
        <td><input name="Prs_Tel" type="text" value="" size="15" maxlength="15" onBlur="numerico(this)"></td>
        <td>&nbsp;</td>
      </tr>
      <tr>
        <td class="Etiqueta1">Celular:</td>
        <td><input name="Prs_Cel" type="text" value="" size="15" maxlength="15" onBlur="numerico(this)"></td>
        <td>&nbsp;</td>
      </tr>
	   </table>
	   </FIELDSET>	
	   <br>
	   <FIELDSET>
       <LEGEND>
       <label class="Titulos2">Punto de Impresión</label>
       </LEGEND>
	   <table width="257">
	     <tr>
        <td width="136" class="Etiqueta1"><span class="Asterisco">*</span> Ubicación:</td>
        <td width="86"><label>
				 <?Php //Consulta del vendedor en base al punto de impresion
	             $rs_ven_punto = consultas_tes(463, '');
	             $row_rs_ven_punto = mysqli_fetch_assoc($rs_ven_punto); 
	             ?><select name="Pun_Cod">
			     <?Php do { ?>
                 <option value="<?Php echo $row_rs_ven_punto['Pun_Cod'];  ?>"  <?Php if($_REQUEST['Pun_Cod']==$row_rs_ven_punto['Pun_Cod']) { echo "selected=selected";} ?>><?Php echo $row_rs_ven_punto['Pun_Des'];   ?></option>
			     <?Php } while($row_rs_ven_punto=mysqli_fetch_assoc($rs_ven_punto));  ?>
            </select>
            </label></td>
        <td width="19">&nbsp;</td>
      </tr>
	  <?php 
	} ?>
    </table>
 </FIELDSET>	
<?php 
if (isset($Comprobar) && $total_rs_comprobar == 0)
{
?>	
<br>   
    <table border="0" class="Azul">
      <tr> 
        <td height="23"><font color="#3162a6" face="Arial, Helvetica, sans-serif">
          <input name="button" type="button" class="Boton_Guardar" onClick=<?Php if ($total_rs_persona == 0){ ?>"validar_persona_vendedor(document.form1)"<?Php } else {?> "validar_vendedor(document.form1)" <?Php } ?> value="Guardar" >
          <input name="hdd_save" type="hidden" id="hdd_save" value="insertar">
          <?php
}
?>
        </font></td>
      </tr>
    </table>
	     </form>        </td>
  </tr>
</table>	    
</BODY></HTML>
<?php
if (isset($rs_persona))
{
}
if (isset($rs_comprobar))
{
}
if (isset($rs_ciudad))
{
}
if (isset($rs_existe))
{
}
?>