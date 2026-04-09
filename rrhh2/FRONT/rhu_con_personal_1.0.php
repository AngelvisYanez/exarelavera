<?php    
/**
* Descripcion: Permite consultar el personal de la Empresa 
* Fecha de actualizacion:	2016-03-18  Desarrollador: Jose Cumbicos
*/
require_once('../../administrador/LOGICA/seguridad.php'); 
require_once('../LOGICA/rhu_log_personal.php');	  
require_once('../../Librerias/procedimientos/almacenados_standar.php');	
require_once('../../Librerias/postclass.php');	  

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_rhu($Ses_Dat_Dis);
/* Cracion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_rhu;

$thisPost = new Post_Block;


 if (isset($codigo))
{
	$row_rs_consulta = $obBD_con1->getRowConsulta(12,$codigo,$obBD_conexion);			
	$total_rs_consulta = $row_rs_consulta['Prs_Ced']>0?1:0;
}
   
if ($txt_busqueda != ""  )
{   
	   if ($op_opciones == "d")
		{
			$rs_buscar = $obBD_con1->getArrayConsulta(10,$txt_busqueda.'*'.$Ses_Emp_Cod,$obBD_conexion);								
		}
		else 
		{
			$rs_buscar = $obBD_con1->getArrayConsulta(11,$txt_busqueda.'*'.$Ses_Emp_Cod,$obBD_conexion);					
		}	 		 		 
	   $total_rs_buscar = count($rs_buscar);		 
} 
	     
 ?>   
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<HTML>
	<HEAD>		
        <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
		<?php require_once("../../mascaras/model1/estilos/estilos.php");?>
		<script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>
        <!--Librerias para interfaz -->       
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>
        <link rel="stylesheet" type="text/css" media="all" href="../../Librerias/jscalendar/calendar-win2k-cold-1.css" title="win2k-cold-1" />					
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	</HEAD>
<BODY>
<table width="100%" border="0" align="center" cellpadding="0" cellspacing="0" class="table">
	<tr class="BarraTitulo">
	  <td height="10">&raquo; consulta de personal</td>
  </tr>
  <tr>
   <td height="389" valign="top">
  <form action="<?Php $_SERVER['PHP_SELF']  ?>" method="post" name="form1" id="form1">
        <?Php include("../../componentes/FRONT/com_con_persona.php"); ?>
    <?Php  echo "</form>";   ?>
   <?Php
				/* Control del menu */
				/*$pagina1 = $_SERVER['PHP_SELF']."?op=1";
				$pagina2 = $_SERVER['PHP_SELF']."?op=2";			
				tabs(2, 'Individual'.'*'.'Todos', $pagina1.'*'.$pagina2, $op);*/
				/*****************************************************************************/
				?>
				<!--<div id="ContTabul">-->
				<?Php
   
   
/* switch ($op)
{
	case 1: /*OPCION 1*/			
?>			
			<?Php 			
			
			?> 
 <?php if(isset($hdd_buscar))
	{ ?>
    <FIELDSET>
		<LEGEND>
		<label class="Titulos2">Resultados de la búsqueda</label>
		</LEGEND>
    <table width="100%" border="1" cellpadding="0" cellspacing="0" class="fixedHeader03">      
      <thead>
      <tr>
        <th width="9%">C&oacute;d. Int. </th>
        <th width="10%">C&eacute;dula</th>
        <th width="38%">Apellidos</th>
        <th width="41%">Nombres</th>
        <th width="2%">&nbsp;</th>
      </tr>
     </thead>
    <tbody>
      <?Php 
	 if($total_rs_buscar > 0)
	 {	  
	  foreach($rs_buscar as $row_rs_buscar){ //Abrir el } while ($row_rs_buscar = mysqli_fetch_assoc($rs_buscar) 
      
		 echo '<form name=form6 method=post  action='.$_SERVER['PHP_SELF'].'>';
		 ?>	
      <tr >
        <td align="center"><?Php echo $row_rs_buscar['Prs_Cod']; ?></td>
        <td align="center"><?Php echo $row_rs_buscar['Prs_Ced']; ?></td>
        <td align="center"><?Php echo $row_rs_buscar['Prs_Ape']; ?></td>
        <td align="center"><?Php echo $row_rs_buscar['Prs_Nom']; ?></td>
        <td align="center"><?Php echo $row_rs_buscar['Prs_Est'] ?>
            <input name="codigo" id="codigo" type="hidden" value="<?Php echo $row_rs_buscar['Per_Cod'];?>">
            <input name="volver_busqueda" id="volver_busqueda" type="hidden" value="<?Php echo $txt_busqueda;?>">
            <input name="volver_op" id="volver_op" type="hidden" value="<?Php echo $op_opciones;?>">
			  <input name="op" id="op" type="hidden" value="1">
            <input type="image" name="imageField" src="../../mascaras/model1/imagenes/forward.png" width="22" height="22">
			<input name="hdd_distri" id="hdd_distri" type="hidden" value="1">
			<input name="hdd_ape" id="hdd_ape" type="hidden" value="<?Php echo $row_rs_buscar['Prs_Ape']; ?>">
			<input name="hdd_nom" id="hdd_nom" type="hidden" value="<?Php echo $row_rs_buscar['Prs_Nom']; ?>">
            <?Php
		?>
        </td>
      </tr><?Php echo "</form>";  
        }  
  	}//Fin del if($total_rs_buscar != 0)
	else
	{ ?>
      <tr>
        <td colspan="5" align="center"><?Php echo error_alerta("No hay resultados que mostrar", 1); ?></td>
      </tr>
      <?Php	 }//Fin del else if($total_rs_buscar != 0) ?>
     </tbody>
    </table>
    </FIELDSET>
<?Php  } /*fin del if(isset($hdd_buscar) )*/?> 


  
<form method="post" name="form2" action="<?Php $_POST['form1'] ?>">
<?Php if ($total_rs_consulta > 0) { ?>

<FIELDSET>
<LEGEND>
<label class="Titulos2">Datos seleccionados </label>
</LEGEND>

<table border="0" >
  <tr>
    <td width="143" class="Etiqueta1">C&eacute;dula:</td>
    <td width="333" class="LetraNegra"  style="text-transform:uppercase" ><?Php echo $row_rs_consulta['Prs_Ced'] ?></td>
  </tr>
  <tr>
    <td class="Etiqueta1">Tipo de documento: </td>
    <td class="LetraNegra"  style="text-transform:uppercase"  ><?Php echo $row_rs_consulta['Ide_Des'] ?></td>
  </tr>
  <tr>
    <td class="Etiqueta1">Nombres:</td>
    <td class="LetraNegra"   style="text-transform:uppercase" ><?Php echo $row_rs_consulta['Prs_Nom'] ?></td>
  </tr>
  <tr>
    <td class="Etiqueta1">Apellidos: </td>
    <td class="LetraNegra"  style="text-transform:uppercase"  ><?Php echo $row_rs_consulta['Prs_Ape'] ?></td>
  </tr>
  <tr>
    <td class="Etiqueta1"> Sexo: </td>
    <td class="LetraNegra"  style="text-transform:uppercase"  ><?Php echo $row_rs_consulta['Prs_Sex'] ?></td>
  </tr>
  <tr>
    <td class="Etiqueta1"> Estado civil: </td>
    <td class="LetraNegra"  style="text-transform:uppercase" ><?Php echo $row_rs_consulta['Prs_Esc'] ?></td>
  </tr>
  <?Php    		
		/* consultar los datos de nacimiento del personal  701*/ 
  		$row_rs_datos_provincia=$obBD_con1->getRowConsulta(16, $row_rs_consulta['Par_Cod'],$obBD_conexion);						
   ?>
  <tr>
    <td class="Etiqueta1">Pais:</td>
    <td class="LetraNegra"  style="text-transform:uppercase" ><?Php echo $row_rs_datos_provincia['Pas_Nom']; ?></td>
  </tr>
  <tr>
    <td class="Etiqueta1">Regi&oacute;n:</td>
    <td class="LetraNegra"  style="text-transform:uppercase" ><span class="LetraNegra" style="text-transform:uppercase"><?Php echo $row_rs_datos_provincia['Reg_Nom']; ?></span></td>
  </tr>
  <tr>
    <td class="Etiqueta1">Provincia:</td>
    <td class="LetraNegra"  style="text-transform:uppercase" ><span class="LetraNegra" style="text-transform:uppercase"><?Php echo $row_rs_datos_provincia['Pro_Nom']; ?></span></td>
  </tr>
  <tr>
    <td class="Etiqueta1">Ciudad:</td>
    <td class="LetraNegra"  style="text-transform:uppercase" ><span class="LetraNegra" style="text-transform:uppercase"><?Php echo $row_rs_datos_provincia['Ciu_Des']; ?></span></td>
  </tr>
  <tr>
    <td class="Etiqueta1">Parroquia:</td>
    <td class="LetraNegra"  style="text-transform:uppercase" ><span class="LetraNegra" style="text-transform:uppercase"><?Php echo $row_rs_datos_provincia['Par_Nom']; ?></span></td>
  </tr>
  <tr>
    <td class="Etiqueta1">Direcci&oacute;n domiciliaria:</td>
    <td class="LetraNegra"  style="text-transform:uppercase" ><?Php echo $row_rs_consulta['Prs_Dir'] ?></td>
  </tr>
  <tr>
    <td class="Etiqueta1">Ciudad:</td>
    <td class="LetraNegra"  style="text-transform:uppercase" ><?Php echo $row_rs_consulta['Ciu_Des'] ?></td>
  </tr>
  <tr>
    <td class="Etiqueta1">Tel&eacute;fono 1: </td>
    <td class="LetraNegra"><?Php echo $row_rs_consulta['Prs_Tel'] ?></td>
  </tr>
  <tr>
    <td class="Etiqueta1">Tel&eacute;fono 2: </td>
    <td class="LetraNegra"  style="text-transform:uppercase"  ><?Php echo $row_rs_consulta['Prs_Te2'] ?></td>
  </tr>
  <tr>
    <td class="Etiqueta1">Celular:</td>
    <td class="LetraNegra"  style="text-transform:uppercase" ><?Php echo $row_rs_consulta['Prs_Cel'] ?></td>
  </tr>
  <tr>
    <td class="Etiqueta1">Correo electr&oacute;nico: </td>
    <td class="LetraNegra" style="text-transform:uppercase" ><?Php echo $row_rs_consulta['Prs_Cor'] ?></td>
  </tr>
  <tr>
    <td class="Etiqueta1">Carga familiar:</td>
    <td class="LetraNegra" style="text-transform:uppercase" ><?Php echo $row_rs_consulta['Per_Car'] ?></td>
  </tr>
  
  <tr>
    <td class="Etiqueta1">Iniciales t&iacute;tulo:</td>
    <td class="LetraNegra"  style="text-transform:uppercase" ><?Php echo $row_rs_consulta['Per_Tit'] ?></td>
  </tr>
  <tr>
    <td class="Etiqueta1">Observaci&oacute;n:</td>
    <td class="LetraNegra"  style="text-transform:uppercase" ><?Php echo $row_rs_consulta['Per_Obs'] ?></td>
  </tr>
  <tr>
    <td class="Etiqueta1">Estado:</td>
    <td class="LetraNegra"  style="text-transform:uppercase" ><?Php echo $row_rs_consulta['Per_Est'] ?></td>
  </tr>
</table>
</FIELDSET>
<br>
<?Php } ?>
  </form>   
 <?php /* break;
 case 2: 
 
 
echo "Hola";
 
 
 
 
 break;
 
 
 }*/
  ?>
 
   
  
  
  
  
       </td>
  </tr>

</table>	   
</BODY></HTML>
<?php
@$obBD_con1->free_result($rs_consulta);
@$obBD_con1->free_result($rs_buscar);

/*********** Cierro las conexiones **********************/
  		@$obBD_con1->liberar();
	  	@$obBD_conexion->cerrar();
/********************************************************/
?>