<?php require_once('../../administrador/LOGICA/seguridad.php');
	  require_once('../../Librerias/operacion.php'); 
	  require_once('../LOGICA/logica.php');
      require_once('../../Librerias/procedimientos/almacenados_academico.php');	
		
?>

<HTML>
	<HEAD>		
		<link href="../../Estilos/Estilo1.css" rel="stylesheet" type="text/css">
		<link href="../../Estilos/Interfaz1.css" rel="stylesheet" type="text/css">
		<link href="../../mascaras/model1/estilos/interfaz.css" rel="stylesheet" type="text/css">
		<link href="../../mascaras/model1/estilos/estilo1.css" rel="stylesheet" type="text/css">
		
		
		<script language="javascript" src="../Librerias/java.js"></script>
		<script language="javascript" src="../Librerias/validaciones/validacion.js"></script>
		<script language="javascript" src="../../Librerias/validaciones/matricula.js"></script>
		<script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>
		
		
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	</HEAD>
<BODY>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr class="BarraTitulo">
	  <td height="10">&raquo; listado de clientes </td>
  </tr>
	<tr>
        <td height="389" valign="top">
          <form name="form1" method="post" action="<?Php $_SERVER['PHP_SELF']?>">
		   <?Php if (!isset($hdd_save))
			{
			?>		
			<BR>
			<fieldset>
			<LEGEND>
			<label class="Titulos2">Escoja las letras con las que desee buscar</label>
			</LEGEND>	
		<p>A
		    <input name="Niv_Cod[1]" type="checkbox" value="A" >
		  B
		  <input name="Niv_Cod[2]" type="checkbox" value="B">
		  C
		  <input name="Niv_Cod[3]" type="checkbox" value="C">
		  D
		  <input name="Niv_Cod[4]" type="checkbox" value="D">
		  E
		  <input name="Niv_Cod[5]" type="checkbox" value="E">
		  F
		  <input name="Niv_Cod[6]" type="checkbox" value="F">	
		  G
		  <input name="Niv_Cod[7]" type="checkbox" value="G">
  		  H
  		  <input name="Niv_Cod[8]" type="checkbox" value="H">
		  I
		  <input name="Niv_Cod[9]" type="checkbox" value="I">
		  J
		  <input name="Niv_Cod[10]" type="checkbox" value="J">
		  K
		  <input name="Niv_Cod[11]" type="checkbox" value="K">
		  L
		  <input name="Niv_Cod[12]" type="checkbox" value="L">
		  M
		  <input name="Niv_Cod[13]" type="checkbox" value="M">	
		  N
		  <input name="Niv_Cod[14]" type="checkbox" value="N">O
  		    <input name="Niv_Cod[15]" type="checkbox" value="O">
		  P
		  <input name="Niv_Cod[16]" type="checkbox" value="P">
		  Q
		      <input name="Niv_Cod[17]" type="checkbox" value="Q">
		  R
		  <input name="Niv_Cod[18]" type="checkbox" value="R">
		  S
		  <input name="Niv_Cod[19]" type="checkbox" value="S">
		  T
		  <input name="Niv_Cod[20]" type="checkbox" value="T">	
		  U
		  <input name="Niv_Cod[21]" type="checkbox" value="U">
  		  V
  		  <input name="Niv_Cod[22]" type="checkbox" value="V">
		  W
		  <input name="Niv_Cod[23]" type="checkbox" value="W">
		  X
		  <input name="Niv_Cod[24]" type="checkbox" value="X">
		  Y
		  <input name="Niv_Cod[25]" type="checkbox" value="Y">
		  Z
		  <input name="Niv_Cod[26]" type="checkbox" value="Z">
	    </p>
		<p>
		    <span class="Letras">TODOS</span>
		    <input name="Niv_Cod[27]" type="checkbox" value="TODOS" onClick="todo_check(form1, 27, this)">
	    </p>
		  </fieldset>
		<br>
		   <input name="hdd_save" type="hidden" id="hdd_save" value="seleccionar">
		   <input name="Boton" type="button" class="Boton_Aceptar" id="Aceptar" value= "Aceptar" onClick="contar_check(form1,27)">						
          <?Php 
		  }
		  if (isset($hdd_save))
			{
				$cod2=envio_parametros(27, $Niv_Cod)
			?>					
			<br>
				<FIELDSET>
					<LEGEND>
					<label class="Titulos2">Impresi&oacute;n de listado de clientes </label>
					</LEGEND>	
					<table width="539" border="0" align="center">
  						<tr>
  						  <td class="LetraNegra">&nbsp;</td>
  						</tr>
  						<tr>
						<td width="533" class="LetraNegra">Si desea obtener en  papel los datos de los clientes presione el bot&oacute;n <span class="Titulos3">&laquo;Imprimir&raquo;</span></td>
						</tr>
						<tr>
						  <td><div align="center"><img src="../../imagenes/impresora.jpg" width="150" height="142"></div></td>
					  </tr>
						<tr>
						    <td><div align="center">
								<a href="tes_imp_cliente.php?niv=<?Php echo "$cod2";?>" target="_blank" class="href">
									<< Imprimir>></a>
						      
					        </div></td>
						</tr>
	  		  </table>
			  </fieldset>
			<?php
			}//if (isset($hdd_save3))
			?>
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