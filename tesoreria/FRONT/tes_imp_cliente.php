<?php require_once('../../administrador/LOGICA/seguridad.php');
	  require_once('../../Librerias/operacion.php');
  	  require_once('../LOGICA/logica.php');
      require_once('../../Librerias/procedimientos/almacenados_academico.php');	 
//*********************Separa la cadena de caracteres y forma un arreglo********************************************************************
$nivel = explode("-",$niv); 
?>				
<html>
<head>
<title>Ginus</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link href="../../Estilos/Estilo1.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="../Librerias/validaciones/validacion.js"></script>
</head>
<body class="Cuerpo">
<table width="652"  height="968" border="0" align="center">
  <tr>
    <td width="646" height="100" valign="top">	  
	<table width="592" border="0" align="center" class="Titulos3">
      <tr align="center">
        <td width="128" rowspan="5" valign="middle"><img src="../../imagenes/logo.jpg" width="128" height="100"></td>
        <td height="14" colspan="2">&nbsp;</td>
      </tr>
      <tr align="center">
        <td height="14" colspan="2">UNIVERSIDAD TECNOLOGICA &quot;SAN ANTONIO DE MACHALA&quot; </td>
        </tr>
      <tr align="center">
        <td colspan="2">&nbsp;</td>
        </tr>
      <tr align="center" valign="top">
        <td width="403" height="14" class="Etiquetas"><div align="center" class="Titulos3">LISTADO DE CLIENTES</div></td>
        <td width="47" class="Etiquetas">&nbsp;</td>
      </tr>
      <tr align="center" valign="top">
        <td height="14" colspan="2">&nbsp;</td>
        </tr>
    </table></td>
  </tr>
  <tr valign="top">
    <td height="843" valign="top"><table width="100%" height="121"  border="0">
      <tr>
        <td height="117">
          <div align="left">
            <?php
		  $j=0; //Inicializa la variable para el ITEM
		  $nota = 0; //Inicializa la variable contador
		  $j=0; //Inicializa la variable para el ITEM
		  $nota = 0; //Inicializa la variable contador
		  for ($i=0;$i<=count($nivel)-2;$i++)
			{?>
			<label class="TITULO_REPORTE"><?php echo $nivel[$i]; 
			?></label>
            <table width="95%"  border="1" cellpadding="0" cellspacing="0" bordercolor="#666666">
              <tr class="TITULO_REPORTE">
                <td width="6%">item</td>
                  <td width="20%">c&eacute;dula</td>
                  <td width="40%">nombres</td>
                  <td width="40%">direccion</td>
                  <td>tel&eacute;fono</td>
                  <td width="32%">cupo</td>
                  <td width="32%">estado</td>
                </tr>
              <?Php
				$rs_reporte=consultas_tes(451,$nivel[$i]);
				$row_rs_reporte = mysqli_fetch_assoc ($rs_reporte);		
		 		 if ($row_rs_reporte!=NULL)
		  		{
				  do{
				  $j++;
				  ?>
              <tr class="Texto_Reporte">
                <td class="LetraNegra" align="center"><?Php echo "$j"; ?>&nbsp;</td>
                  <td><?Php echo $row_rs_reporte['Prs_Ced']?>&nbsp;</td>
                  <td><?Php echo $row_rs_reporte['Prs_Ape']?> <?Php echo $row_rs_reporte['Prs_Nom']?>&nbsp;</td>
			      <td><?Php echo $row_rs_reporte['Prs_Dir']?>&nbsp;</td>
                  <td align="center"><?Php if ($row_rs_reporte['Prs_Tel']!=""){ echo $row_rs_reporte['Prs_Tel']; }else{ echo "&nbsp;"; } ?>&nbsp;</td>
                  <td align="center"><?Php echo $row_rs_reporte['Cli_Cup']?>&nbsp;</td>
                  <td align="center"><?Php echo $row_rs_reporte['Cli_Est']?>&nbsp;</td>
                </tr>
              <?php
		  } while ($row_rs_reporte = mysqli_fetch_assoc ($rs_reporte));		  		  
		  }
		  ?>
            </table>
			<br>
            <?php
		   }
		  ?>
          </div></td></tr>
    </table></td>
  </tr>
</table>
</body>
</html>
<?Php
?>	