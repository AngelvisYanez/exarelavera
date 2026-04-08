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
		$Caj_Hof = date("H:i:s"); 
		//**************************************************************				
		insercionesv_tes(50, $Caj_Exi.'*'.$Caj_Obs.'*'.$Caj_Cod, $conexion);  
		//**************************************************************						
		close_trans_tes($conexion);
		//*************************************************************** 	
        $codigo =0;
    }

//*******************Busqueda de los datos de caja*************************************************************************
if ($ann_ini != "" && $mes_ini != "" && $dia_ini != "")
{
		$rs_buscar = consultas_tes(48, "$ann_ini/$mes_ini/$dia_ini".'*'.$Pun_Cod);	
        $row_rs_buscar = mysqli_fetch_assoc($rs_buscar);
        $total_rs_buscar = mysqli_num_rows($rs_buscar); 
}
else
{	
	if (isset($codigo))
	{
	$rs_consultar = consultas_tes(49, $codigo);	
	$row_rs_consultar = mysqli_fetch_assoc($rs_consultar);
	$total_rs_consultar = mysqli_num_rows($rs_consultar); 
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
	  <td height="10"><span class="Titulos1">&raquo;</span> MODIFICAR  CAJA </td>
    </tr>
	<tr>
        <td height="20" valign="top">
         <form name="form1" method="post" action="<?Php echo $_SERVER['PHP_SELF'];?>">
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
			  <input name="btn_buscar" type="button" class="Boton_Buscar" title "Buscar" id="btn_buscar" onClick="validar_buscarcaja()" value="Buscar">
			  </div>			  </td>
        </tr>
      </table>
</FIELDSET>
  <?Php
  	if(isset($dia_ini))
	{
		if($total_rs_buscar != 0)
		{
  ?>
<br>
<FIELDSET>
<LEGEND>
<label class="Titulos2">Resultados de la busqueda</label>
</LEGEND>
	<table width="500"  height="20" border="1" cellpadding="0" cellspacing="0" class="Fondo">
	  <tr class="Cabecera">
          <td width="119">Fecha de Apertura</td>
          <td width="126">Existencia</td>
		  <td width="130">Observaciones</td>
		  <td width="13">&nbsp;</td>
      </tr>
	  <?Php do { ?>
	  <tr class="Fondo">
		<td align="center"><?Php echo $row_rs_buscar['Caj_Fec']; ?>&nbsp;</td>
    	<td align="center"><?Php echo $row_rs_buscar['Caj_Exi']; ?>&nbsp;</td>
		<td align="center"><?Php echo $row_rs_buscar['Caj_Obs']; ?>&nbsp;</td>
		 <td width="22" align="center"><p><a href="<?Php echo $_POST['form2']?>?codigo=<?Php echo $row_rs_buscar['Caj_Cod'];?>" title="Editar"><img src="../../imagenes/editar.jpg" width="20" height="20" border="0"></a> 
            </td>
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
 </p>
 </form>
 <form action="<?Php echo $_POST['form2'] ?>" method="post" name="form2" id="form2"> 
  <FIELDSET>
  <?Php
//********************Opcion 1 *********************************************************************************		
if ($codigo>0)	//Edicion de la Apertura de Caja			
{

?>
  <input type="hidden" name="hiddenField" value="<?PHP echo $row_rs_vendedor['Pun_Cod']; ?>">
  <br>
    <LEGEND>
          <label class="Titulos2">Datos a Modificar </label>
    </LEGEND>
	<?php mensaje_requerido() ?>
		  <table width="511" border="0" class="LetraNegra">			 
			 <tr>
			   <td class="Etiquetas"><div align="left">Fecha de Apetura:</div></td>
			   <td width="151"><div align="left"><?php echo $row_rs_consultar['Caj_Fec']; ?></div></td>
			   <td width="94"><span class="Etiquetas">Fecha de Cierre: </span></td>
			   <td width="141"><?php echo $row_rs_consultar['Caj_Fef']; ?></td>
		    </tr>
			 <tr>
			   <td class="Etiquetas"><div align="left">Hora de Apertura: </div></td>
			   <td><div align="left"><?php echo $row_rs_consultar['Caj_Hoi']; ?></div></td>
			   <td><span class="Etiquetas">Hora de Cierre: </span></td>
			   <td><?php echo $row_rs_consultar['Caj_Hof']; ?></td>
		    </tr>
			 <tr>
			   <td height="20" class="Etiquetas">&nbsp;</td>
			   <td>&nbsp;</td>
			   <td>&nbsp;</td>
			   <td>&nbsp;</td>
		    </tr>
			 <tr>
                 <td width="107" class="Etiquetas"><div align="left">Existencia en Caja:</div></td>
                 <td colspan="3"><span class="Fondo4">
		     <input name="Caj_Exi" id="Caj_Exi" type="text"  size="20" maxlength="20" value="<?php echo $row_rs_consultar['Caj_Exi']; ?>">
               <input name="Caj_Cod" type="hidden" id="Caj_Cod" value="<?php echo $row_rs_consultar['Caj_Cod']; ?>">
                 </span></td>
            </tr>
			  <tr>
                  <td class="Etiquetas"><div align="left">Observaciones:</div></td>
                  <td colspan="3"><span class="Fondo4">
                   <textarea name="Caj_Obs" id="Caj_Obs" cols="40" rows="4"><?php echo $row_rs_consultar['Caj_Obs']; ?></textarea>
                  </span>			    </td>
              </tr>
        </table>
	      <input name="codigo" type="hidden" id="codigo" value="<?php echo $codigo; ?>">
        </FIELDSET>
		<br>
	      <table width="118" border="0" class="Azul">
            <tr>
              <td width="100%" height="23"><div align="left"><font color="#3162a6" face                  ="Arial, Helvetica, sans-serif">
                  <input name="btn_guardar" type="button" class="Boton_Guardar" id="btn_guardar" title= "Guardar" onClick= "validar_caja(form2)" value="Guardar">
                   </font>
                  <input name="hdd_save" type="hidden" id="hdd_save" value="insertar">
              </div>			  </td>
            </tr>
          </table>
   
<?Php
} //Fin del if ($op==1)	
?>
</form></td>
</tr>
</table>	   
</BODY></HTML>
<?Php
if (isset ($rs_buscar))
	{
		mysqli_free_result($rs_buscar);
	}
	if (isset($rs_consulta))
	{
		mysqli_free_result($rs_consulta); 
	}
?>
