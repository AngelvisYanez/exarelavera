<?php require_once('../../administrador/LOGICA/seguridad.php');
	  require_once('../../Librerias/operacion.php'); 
	  require_once('../LOGICA/logica.php');	  
   	  require_once('../../Librerias/procedimientos/almacenados_standar.php');	  
 if (isset($hdd_save)) { 
 	  $rs_consultar = consultas_tes(102, $Bak_Des);
	  $total_rs_consultar = mysqli_num_rows($rs_consultar);
	  if ($total_rs_consultar == 0) {
	  	    $rs_insbancos = insercionesu_tes(101, $Bak_Des);
	  }
	  else
		{
?>
			<script type="text/javascript">	
				alert ("�No se ha podido guardar los datos porque el banco ya existe en la base de datos!");
			</script>
<?Php
		}
}
?>
<HTML>
	<HEAD>
		<TITLE>Ginus</TITLE>
		<link href="../../Estilos/Estilo1.css" rel="stylesheet" type="text/css">
		<script type="text/javascript" src="../../Librerias/validaciones/validacion.js"></script>
		<script type="text/javascript" src="../../Librerias/validaciones/tesoreria.js"></script>
		<script type="text/javascript" src="../../Librerias/fecha.js"></script>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	</HEAD>
<BODY>
<table width="560" border="0" align="center" cellpadding="0" cellspacing="0">
	<tr class="Titulos1">
	  <td height="10">&raquo; registro de bancos </td>
  </tr>
	<tr>
	  	<td height="389" valign="top">
         <form method="post" name= "form" action="<?php echo $_SERVER['PHP_SELF'];?>">
  <table width="100%" height="23"
   border="0" align="left" bgcolor="#C7E0CD" >
  </table>
  <br>
<FIELDSET>
<LEGEND>
<label class="Titulos2">Datos a registrar</label>
</LEGEND>
  <table width="100%" border="0">
  <tr>
       <td width="758"><?Php echo mensaje_requerido(); ?></td>
  </tr>
  </table>
  <table border="0">
      <tr>
        <td width="107" class="Etiquetas"><span class="Asterisco">*</span> Descripci&oacute;n:</td>
        <td width="306"><input name="Bak_Des" type="text" size="30" maxlength="30" style="text-transform:uppercase" value=""></td>
        </tr>
    </table>
</FIELDSET>	 
<table width="100%" border="0" class="Azul">
    <tr>
      <td width="100%" height="23"><p><font color="#3162a6" face="Arial, Helvetica, sans-serif">
	  <br>
        <input name="button" type="button" class="Boton" value="Guardar" onClick="confirmacion(document.form)">
      </font>
          <input name="hdd_save" type="hidden" id="hdd_save">
      </p></td>
    </tr>
  </table>
</form>        </td>
  </tr>
</table>	    
</BODY></HTML>
<?php
if (isset($rs_insbancos))
{
}
?>