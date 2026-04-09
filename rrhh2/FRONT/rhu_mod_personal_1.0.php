<?php  
/**
* Descripcion: Permite modificar el personal de la Empresa 
* Fecha de actualizacion:	2016-03-17  Desarrollador: Jose Cumbicos
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/rhu_log_personal.php');	
require_once('../../Librerias/postclass.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');	

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_rhu($Ses_Dat_Dis);
/* Cracion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_rhu;

/* consultar librerias */
//include("../../componentes/FRONT/ajax_pai_reg_pro_ciu_par.php"); 
/* Llamado de la libreria para evitar el reenvio de datos */
$thisPost = new Post_Block;
/* Consulta de las ciudades */
$row_rs_ciudad=$obBD_con1->getArrayConsulta(7,'', $obBD_conexion);
$total_rs_ciudad = count($row_rs_ciudad['Ciu_Cod']);
/* Consulta de las nacionalidades */
$row_rs_pais=$obBD_con1->getArrayConsulta(6,'',$obBD_conexion);
$total_rs_pais = count($row_rs_pais['Pas_Cod']);
/* Consulta de  de ESCUELAS */
$row_rs_escuelas=$obBD_con1->getArrayConsulta(8,'',$obBD_conexion);
$total_rs_escuelas = count($row_rs_escuelas['Esc_Cod']);  

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

if (isset($codigo))
{
	$row_rs_consulta = $obBD_con1->getRowConsulta(12,$codigo,$obBD_conexion);			
	$total_rs_consulta = $row_rs_consulta['Prs_Ced']>0?1:0;
}


/* Almacena los datos modificados */
if (isset($hdd_save))
{  
	if ($thisPost->postBlock($_POST['postID'])) 
	{
		/* Cracion del objeto mysql para las inserciones */
		$obBD_ins1 =  new Class_Log_Datos_Rhu;		
		/*Inicio de la transaccion */
		$obBD_ins1->inicio_transaccion($obBD_conexion->conexion);

		$Prs_Fec = date("$ann_ini-$mes_ini-$dia_ini");	
		//Modificación de usuarios de tipo P=Personal
		$obBD_ins1->grabarv_registros(sentencias_rhu(13, $obBD_ins1->parametros($Prs_Ced.'*'.$hdd_prs)),$obBD_conexion->conexion);	
		//insercionesv(99, $Prs_Ced.'*'.$codigo, $conexion);
	  	$obBD_ins1->grabarv_registros(sentencias_rhu(14, $obBD_ins1->parametros($Per_Tit.'*'.$Per_Obs.'*'.$Per_Car.'*'.$hdd_prs)),$obBD_conexion->conexion);
		//insercionesv(100, $Per_Tit.'*'.$Per_Tip.'*'.$Esc_Int.'*'.$Per_Obs.'*'.$Per_Car.'*'.$codigo, $conexion);									
		$obBD_ins1->grabarv_registros(sentencias_rhu(15, $obBD_ins1->parametros($Prs_Ced.'*'.$Prs_Nom.'*'.$Prs_Ape.'*'.$Prs_Sex.'*'.$Prs_Fec.'*'.$Prs_Esc.'*'.$Prs_Dir.'*'.$Ciu_Cod_N.'*'.$Prs_Tel.'*'.$Prs_Te2.'*'.$Prs_Cel.'*'.$Prs_Cor.'*'.$hdd_prs.'*'.$Ide_Cod.'*'.$Pas_Cod.'*'.$Par_Cod.'*'.$Prs_San)),$obBD_conexion->conexion);
		
		//**************Fin de la transaccion****************************	
	$obBD_ins1->fin_transaccion($obBD_conexion->conexion);
		//***************************************************************
		unset($codigo); 
	}//if ($thisPost->postBlock($_POST['postID'])) 
}//Fin del if (isset($hdd_save))

if(isset($ajax_pai_cod))
{    	
	
	/**
	 * Cargado de regiones 
	 */
	$arrRegiones = $obBD_con1->getArrayConsulta(108, $Pas_Cod, $obBD_conexion);
	?>
    <select name="Reg_Cod" id="Reg_Cod" style="text-transform:uppercase" onChange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_pro_cod=1&Pas_Cod=<?Php echo $Pas_Cod; ?>&Reg_Cod=' + this.value,'div_provincias')"   >
       <option value="">Seleccione...</option>
       <?Php 
       foreach($arrRegiones as $row_rs_regiones){ ?>
       <option value="<?Php echo $row_rs_regiones['Reg_Cod']; ?>">&raquo;&nbsp;<?Php echo $row_rs_regiones['Reg_Nom'];?></option>
       <?Php
		}
	   ?>
    </select>
	<?Php 	
	exit();
}

/**
 * Obtener el combo de datos de provincias segun la region seleccionada
 * @var $Reg_Cod Codigo principal de la region
 */
if(isset($ajax_pro_cod)) 
{	
   /**
    * Cargado de provincias 
    */
	$arrProvincias = $obBD_con1->getArrayConsulta(107, $Reg_Cod, $obBD_conexion);
?>
	
	 <select name="Pro_Cod" id="Pro_Cod" style="text-transform:uppercase" onChange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_reg_cod=1&Pas_Cod=<?Php echo $Pas_Cod; ?>&Reg_Cod=<?Php echo $Reg_Cod; ?>&Pro_Cod=' + this.value,'div_ciudades')"    >
     	<option value="">Seleccione...</option>
        <?Php foreach($arrProvincias as $row_rs_provincias) { ?>
        <option value="<?Php echo $row_rs_provincias['Pro_Cod']; ?>">&raquo;&nbsp;<?Php echo $row_rs_provincias['Pro_Nom'];  ?></option>
        <?Php
		}?>
     </select>
	<?Php
	exit();
}

/**
 * Obtener el combo de datos de ciudades segun la provincia seleccionada
 * @var $Pro_Cod Codigo principal de la provincia
 */
if(isset($ajax_reg_cod)) 
{	
   /**
    * Cargado de ciudades 
    */
	$arrCiudades = $obBD_con1->getArrayConsulta(109, $Pro_Cod, $obBD_conexion);?>

	 <select name="Ciu_Cod" id="Ciu_Cod" style="text-transform:uppercase" onChange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_parro_cod=1&Pas_Cod=<?Php echo $Pas_Cod; ?>&Pro_Cod=<?Php echo $Pro_Cod; ?>&Reg_Cod=<?Php echo $Reg_Cod; ?>&Ciu_Cod=' + this.value,'div_parroquias')"    >
        <option value="">Seleccione...</option>
        <?Php foreach($arrCiudades as $row_rs_ciudades){ ?>
        <option value="<?Php echo $row_rs_ciudades['Ciu_Cod']; ?>">&raquo;&nbsp;<?Php echo $row_rs_ciudades['Ciu_Des'];  ?></option>
        <?Php 
		}
	?>
     </select>
	<?Php	
	exit();
}

/**
 * Obtener el combo de datos de parroquias segun la ciudad seleccionada
 * @var $Ciu_Cod Codigo principal de la ciudad
 */
if(isset($ajax_parro_cod)) 
{   		
	$arrParroquia = $obBD_con1->getArrayConsulta(110, $Ciu_Cod, $obBD_conexion);
	?>
	
	 <select name="Par_Cod" id="Par_Cod" style="text-transform:uppercase"    >
        <option value="">Seleccione...</option>
        <?Php foreach($arrParroquia as $row_rs_parroquias) { ?>
        <option value="<?Php echo $row_rs_parroquias['Par_Cod']; ?>">&raquo;&nbsp;<?Php echo $row_rs_parroquias['Par_Nom'];?></option>
        <?Php 
		}
		?>
      </select>
	<?Php
	exit();
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
        <!--Librerias para calendario -->       
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.datepicker.js"></script> 
        <!--Librerias para modal -->       
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.modals.js"></script> 				
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
        <link rel="stylesheet" type="text/css" media="all" href="../../Librerias/jscalendar/calendar-win2k-cold-1.css" title="win2k-cold-1" />	
	</HEAD>
<BODY>
<table width="100%" border="0" cellpadding="0" cellspacing="0" class="table">
	<tr class="BarraTitulo">
	  <td height="10">&raquo; modificacion de personal </td>
  </tr>
	<tr>
    <td height="389" valign="top">
  
  <form action="<?Php echo $_SERVER['PHP_SELF']  ?>" method="post" name="form1" id="form1">
        <?Php include("../../componentes/FRONT/com_con_persona.php"); ?>
    <?Php  echo "</form>";   ?>
   
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
	  foreach($rs_buscar as $row_rs_buscar) { //Abrir el } while ($row_rs_buscar = mysqli_fetch_assoc($rs_buscar)       
		 echo '<form name=form6 method=post  action='.$_SERVER['PHP_SELF'].'>';
		 ?>	
      <tr class="Fondo">
        <td align="center"><?Php echo $row_rs_buscar['Per_Cod']; ?></td>
        <td align="center"><?Php echo $row_rs_buscar['Prs_Ced']; ?></td>
        <td align="center"><?Php echo $row_rs_buscar['Prs_Ape']; ?></td>
        <td align="center"><?Php echo $row_rs_buscar['Prs_Nom']; ?></td>
        <td align="center" width="2%"><?Php echo $row_rs_buscar['Prs_Est'] ?>
            <input name="codigo" id="codigo" type="hidden" value="<?Php echo $row_rs_buscar['Per_Cod'];?>">
            <input name="volver_busqueda" id="volver_busqueda" type="hidden" value="<?Php echo $txt_busqueda;?>">
            <input name="volver_op" id="volver_op" type="hidden" value="<?Php echo $op_opciones;?>">
			<input name="op" id="op" type="hidden" value="1">            
			<input name="hdd_distri" id="hdd_distri" type="hidden" value="1">
			<input name="hdd_ape" id="hdd_ape" type="hidden" value="<?Php echo $row_rs_buscar['Prs_Ape']; ?>">
			<input name="hdd_nom" id="hdd_nom" type="hidden" value="<?Php echo $row_rs_buscar['Prs_Nom']; ?>">
			<input name="hdd_prs" id="hdd_prs" type="hidden" value="<?Php echo $row_rs_buscar['Prs_Cod']; ?>">
            <button type="button" class="btn btn-success btn-mini" title="Elegir" onClick="this.form.submit()"><i class=" icon-arrow-right icon-white"></i>
        	</button>
            <?Php
		?>
        </td>
      </tr><?Php echo "</form>";  
        } 
  	}//Fin del if($total_rs_buscar != 0)
	else
	{ ?>
      <tr>
        <td align="center">&nbsp;</td>
        <td align="center">&nbsp;</td>
        <td align="center"><?Php echo error_alerta("No hay resultados que mostrar", 1); ?></td>
        <td align="center">&nbsp;</td>
        <td align="center">&nbsp;</td>
      </tr>
      <?Php	 }//Fin del else if($total_rs_buscar != 0) ?>
      </tbody>
    </table>
    </FIELDSET>
<?Php  } /*fin del if(isset($hdd_buscar) )*/?> 
<!---->
<form method="post" name="form2" action="<?Php echo $_SERVER['form1'] ?>">

<?Php if (isset($codigo)){ ?>
<FIELDSET>
<LEGEND>
<label class="Titulos2">Datos a registrar</label>
</LEGEND>
<?Php echo mensaje_requerido(); //Muestra el mensaje de requerido  
/* Creacion del campo repost */
$thisPost->startPost();
?>  
<FIELDSET>
<LEGEND>
<label class="Titulos2">Datos personales</label>
</LEGEND>
  
<table width="100%"  border="0">
  <tr>
    <td width="18%"  class="Etiqueta1"><span class="Asterisco">*</span> C&eacute;dula:</td>
    <td width="82%" >
      <input name="Prs_Ced" id="Prs_Ced" type="text" size="13" maxlength="13" value="<?Php echo $row_rs_consulta['Prs_Ced'] ?>" onBlur="numerico(document.form2.Prs_Ced)">	    </td>
  </tr>
  <tr>
    <td  class="Etiqueta1"><span class="Asterisco">*</span>Tipo de documento :</td>
    <td><?Php

	$rs_identifica=$obBD_con1->getArrayConsulta(5,'',$obBD_conexion);	
	$total_rs_identifica=$obBD_con1->numregistros();
		?>
      <select name="Ide_Cod" id="Ide_Cod">
        <option></option>
        <?php
		foreach($rs_identifica as $datos) {  
		?>
        <option value="<?php echo $datos['Ide_Cod']?>" <?Php  if($row_rs_consulta['Ide_Cod']==$datos['Ide_Cod']){ echo "selected";}?>><?php echo $datos['Ide_Des']?></option>
        <?php }?>
      </select></td>
  </tr>
  <tr>
    <td  class="Etiqueta1"><span class="Asterisco">*</span> Nombres:</td>
    <td>
      <input name="Prs_Nom" id="Prs_Nom" type="text" size="35" maxlength="30" value="<?Php echo $row_rs_consulta['Prs_Nom'] ?>" style="text-transform:uppercase ">    </td>
  </tr>
  <tr>
    <td class="Etiqueta1"><span class="Asterisco">* </span>Apellidos: </td>
    <td>
      <input name="Prs_Ape" id="Prs_Ape" type="text" value="<?Php echo $row_rs_consulta['Prs_Ape'] ?>" size="35" maxlength="30" style="text-transform:uppercase ">    </td>
  </tr>
  <tr>
    <td class="Etiqueta1"><span class="Asterisco">*</span> G&eacute;nero: </td>
    <td>
	<?php
	if ($total_rs_persona == 0)
	{ ?>
      <select id="Prs_Sex" name="Prs_Sex">
	  <?php 
			//if (isset($codigo) && $row_rs_consulta >0)
			//{
				$row_rs_cod = array ("M","F");
				$row_rs_des = array ("Masculino", "Femenino");

				for ($i=0;$i<count($row_rs_cod);$i++) 
			 	{  
	  ?>
            <option <?Php if($row_rs_cod[$i] == $row_rs_consulta['Prs_Sex']){echo "selected";}?> value="<?php echo $row_rs_cod[$i]?>"><?php echo $row_rs_des[$i]?></option>
            <?php
			 	}
			//}
			?>        
      </select> 
	 <?Php
	} //else { echo $row_rs_persona['Prs_Sex']; }
	?>	     </td>
  </tr>
  <tr>
    <td class="Etiqueta1">Tipo de Sangre: </td>
    <td class="LetraNegra"><input name="Prs_San" type="text" id="Prs_San" style="text-transform:uppercase" value="<?Php echo $row_rs_consulta['Prs_San']; ?>" size="4" maxlength="4"></td>
  </tr>
  <tr>
    <td class="Etiqueta1"><span class="Asterisco">* </span>Fecha de nacimiento: </td>
    <td class="LetraNegra">A&ntilde;o
        <select id="ann_ini" name="ann_ini" onChange="asignaDias(document.form2.dia_ini, document.form2.mes_ini, document.form2.ann_ini)">
          <option></option>
          <?Php   
				for ($i=1930; $i<= date("Y")-15; $i++)
				{
			?>
          <option <?Php if($i == $row_rs_consulta['Ann_Ini']){echo "selected";}?> value="<?Php echo $i ?>"><?Php echo $i ?> </option>
          <?Php
				}
			?>
        </select>
mes
<select id="mes_ini" name="mes_ini" onChange="asignaDias(document.form2.dia_ini, document.form2.mes_ini, document.form2.ann_ini)">
  <option></option>
  <?Php 
		  		//*******************Iniciacion del arreglo de meses***********************************************************************************************************
				$row_rs_des = array ("Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", 
						"Octubre", "Noviembre", "Diciembre");
				for ($i=1; $i<=12;$i++)
				{
			?>
  <option <?Php if($i == $row_rs_consulta['Mes_Ini']){echo "selected";}?> value="<?Php echo $i; ?>"> <?Php echo $row_rs_des[$i-1] ?> </option>
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
  <option <?Php if($i == $row_rs_consulta['Dia_Ini']){echo "selected";}?> value="<?Php echo $i; ?>"><?Php echo $i; ?> </option>
  <?Php
			  	}
			   ?>
</select>
</span></span></td>
  </tr>
  <tr>
    <td class="Etiqueta1"><span class="Asterisco">* </span>  Pa&iacute;s de nacimiento:</td>
    <td>
	<?Php
  	/* consultar los datos de nacimiento del personal*/
	$row_rs_datos_provincia=$obBD_con1->getRowConsulta(16,$row_rs_consulta['Par_Cod'],$obBD_conexion);		
	
	/* consulto los paises en la base de datos */
	$rs_paises=$obBD_con1->getArrayConsulta(106,'', $obBD_conexion);
	$total_row_rs_paises = count($rs_paises);
?>
<select name="Pas_Cod" id="Pas_Cod" 
onchange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_pai_cod=1&Pas_Cod=' + this.value,'div_regiones')" style="text-transform:uppercase">
             <option></option>
        <?Php foreach($rs_paises as $row_rs_paises){ ?>  
          <option value="<?  echo $row_rs_paises['Pas_Cod']; ?>"  <?Php if($row_rs_datos_provincia['Pas_Cod']==$row_rs_paises['Pas_Cod']){ ?> selected="selected" <?Php }?>><?  echo $row_rs_paises['Pas_Nom']; ?></option>
         <?Php } ?> 
    </select></td>
  </tr><tr>
    <td class="Etiqueta1"><span class="Asterisco">* </span>Regi&oacute;n de nacimiento: </td>
    <td><div id="div_regiones" >
	<?Php /* consultar las regiones */
	$rs_regiones=$obBD_con1->getArrayConsulta(108,$row_rs_datos_provincia['Pas_Cod'],$obBD_conexion);	
	$total_row_rs_regiones = count($rs_regiones);
	?>
      <select name="Reg_Cod" id="Reg_Cod" onChange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_pro_cod=1&Pas_Cod=<?Php echo $Pas_Cod; ?>&Reg_Cod=' + this.value,'div_provincias')" >
	    <?Php foreach($rs_regiones as $row_rs_regiones){  ?>
        <option value="<?Php echo $row_rs_regiones['Reg_Cod'] ?>"  
		<?Php if($row_rs_datos_provincia['Reg_Cod']==$row_rs_regiones['Reg_Cod']){ ?> selected="selected" <?Php   } ?>    ><?Php echo $row_rs_regiones['Reg_Nom'] ?></option>
		<?Php }?>
      </select>
    </div></td>
  </tr>
  
  <tr>
    <td class="Etiqueta1"><span class="Asterisco">* </span>Provincia de nacimiento: </td>
    <td><div id="div_provincias">
	<?Php
	/* provincia de nacimiento */
	  $rs_provincia=$obBD_con1->getArrayConsulta(107,$row_rs_datos_provincia['Reg_Cod'],$obBD_conexion);
	  $row_rs_consulta_provincia=$obBD_con1->registros();
	 ?>
 <select name="Pro_Cod" id="Pro_Cod" onChange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_reg_cod=1&Pas_Cod=<?Php echo $Pas_Cod; ?>&Reg_Cod=<?Php echo $Reg_Cod; ?>&Pro_Cod=' + this.value,'div_ciudades')" >
 <?Php foreach($rs_provincia as $row) { ?>
    <option value="<?Php echo $row['Pro_Cod'] ?>"
		 <?Php if($row['Pro_Cod']==$row_rs_consulta_provincia['Pro_Cod']){ ?> selected="selected" <? }?>><?Php echo $row['Pro_Nom'] ?></option>
	<?Php }?>	 
      </select>
    </div></td>
  </tr>
  <tr>
    <td class="Etiqueta1"><span class="Asterisco">* </span>Ciudad de nacimiento: </td>
    <td><div id="div_ciudades">
	<?Php 
	/* consulta ciudades ciudades */
	$rs_ciudades=$obBD_con1->getArrayConsulta(109,$row_rs_datos_provincia['Pro_Cod'],$obBD_conexion);	
	$total_rs_consciudad=count($rs_ciudades);
	  ?>
	 <select name="Ciu_Cod" id="Ciu_Cod" onChange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_parro_cod=1&Pas_Cod=<?Php echo $Pas_Cod; ?>&Pro_Cod=<?Php echo $Pro_Cod; ?>&Reg_Cod=<?Php echo $Reg_Cod; ?>&Ciu_Cod=' + this.value,'div_parroquias')" >
	 <?Php foreach($rs_ciudades as $datos) {  ?>
     	<option value="<?Php echo $datos['Ciu_Cod'] ?>"   
<?Php if($row_rs_datos_provincia['Ciu_Cod']==$datos['Ciu_Cod']){ ?> selected="selected" <?Php   } ?> ><?Php echo $datos['Ciu_Des'] ?></option>   
	<?Php  } ?>	
     </select>
	  </div></td>
  </tr>
  <tr>
    <td class="Etiqueta1"><span class="Asterisco">* </span>Parroquia de nacimiento: </td>
    <td><div id="div_parroquias">
	<?Php 
	
	/* consulta de parroquias */
	$rs_parroquias=$obBD_con1->getArrayConsulta(110,$row_rs_datos_provincia['Ciu_Cod'],$obBD_conexion);
	$row_rs_consulta_parroquias=$obBD_con1->registros();
	?>	
      <select name="Par_Cod" id="Par_Cod">
	  <?Php  foreach($rs_parroquias as $datos) {  ?>
	         <option value="<?Php echo $datos['Par_Cod'] ?>"
			 <?Php if($row_rs_datos_provincia['Par_Cod']==$datos['Par_Cod']){ ?> selected="selected" <? }?>><? echo $datos['Par_Nom'] ?></option>   
	<?Php }?>	 
      </select>
    </div></td>
  </tr>
  <tr>
    <td class="Etiqueta1"><span class="Asterisco">*</span> Estado civil: </td>
    <td><select name="Prs_Esc" id="Prs_Esc">
	<?php 	if (isset($codigo)&& $row_rs_consulta >0)
			{	$row_rs_cod = array ("S","C","D","V","U");
				$row_rs_des = array ("Soltero/a", "Casado/a", "Divorciado/a", "Viudo/a", "Unión libre");
				for ($i=0;$i<count($row_rs_cod);$i++) {   ?>
		<option <?Php if($row_rs_des[$i] == $row_rs_consulta['Prs_Esc']){echo "selected";}?> value="<?php echo $row_rs_cod[$i]?>"><?php echo $row_rs_des[$i]?></option>
	 <?php
			 	}
			}
	 ?>      
    </select></td>
  </tr>
  <tr>
    <td class="Etiqueta1"><span class="Asterisco">*</span> Direcci&oacute;n domiciliaria:</td>
    <td><input name="Prs_Dir" type="text" id="Prs_Dir" value="<?Php echo $row_rs_consulta['Prs_Dir'] ?>" size="50" maxlength="50" style="text-transform:uppercase "></td>
  </tr>
  <tr>
    <td class="Etiqueta1"><span class="Asterisco">*</span> Ciudad domiciliaria:</td>
    <td class="LetraNegra"><?php
	if ($total_rs_persona == 0)
	{ ?>
        <select name="Ciu_Cod_N" id="Ciu_Cod_N">
          <option></option>
          <?php
			foreach($row_rs_ciudad as $row){  
		?>
          <option <?Php  if($row_rs_consulta['Ciu_Cod']==$row['Ciu_Cod']){ ?> selected="selected" <?Php }  ?>   value="<?php echo $row['Ciu_Cod']?>"><?php echo $row['Ciu_Des']?></option>
          <?php
			};
	?>
        </select>
        <?Php
	} else { echo $row_rs_consulta['Ciu_Des']; }
	?>    </td>
  </tr>
  <tr>
    <td class="Etiqueta1"><span class="Asterisco">*</span> Nacionalidad: </td>
    <td class="LetraNegra"><?php
	if ($total_rs_persona == 0)
	{ ?>
        <select name="Pas_Cod" id="Pas_Cod">
          <?php
			foreach($row_rs_pais as $row){  
		?>
          <option <?Php  if($row_rs_consulta['Pas_Cod']==$row['Pas_Cod']){ echo "selected";  } ?>  value="<?php echo $row['Pas_Cod']?>"><?php echo $row['Pas_Nac']?></option>
          <? }?>
        </select>
        <?Php
	} else { echo $row_rs_consulta['Pas_Nac']; }
	?></td>
  </tr>
  <tr>
    <td class="Etiqueta1"> Tel&eacute;fono 1:</td>
    <td class="LetraNegra"><input name="Prs_Tel" id="Prs_Tel" type="text" value="<?Php echo $row_rs_consulta['Prs_Tel'] ?>" size="15" maxlength="15" onBlur="numerico(document.form2.Prs_Tel)">
      &nbsp;<span class="Etiqueta1">Tel&eacute;fono 2 :
        <input name="Prs_Te2" id="Prs_Te2" type="text" value="<?Php echo $row_rs_consulta['Prs_Te2'] ?>" size="15" maxlength="15" onBlur="numerico(document.form2.Prs_Te2)">
        &nbsp;&nbsp;Celular:
        <input name="Prs_Cel" id="Prs_Cel" type="text" value="<?Php echo $row_rs_consulta['Prs_Cel'] ?>" size="15" maxlength="15" onBlur="numerico(document.form2.Prs_Cel)">
      </span></td>
  </tr>
  <tr>
    <td class="Etiqueta1">Correo electr&oacute;nico: </td>
    <td><input name="Prs_Cor" id="Prs_Cor" type="text" value="<?Php echo $row_rs_consulta['Prs_Cor'] ?>" size="50" maxlength="50" onBlur="correo(document.form2.Prs_Cor)"></td>
  </tr>
 </table>
 </FIELDSET>
 
 <FIELDSET>
<LEGEND>
<label class="Titulos2">Datos laborales</label>
</LEGEND>
 <table width="100%" cellpadding="0">
  <tr>
    <td width="18%" class="Etiqueta1"><span class="Asterisco">*</span> Iniciales t&iacute;tulo:</td>
    <td width="85%"><input name="Per_Tit" id="Per_Tit" type="text" value="<?Php echo $row_rs_consulta['Per_Tit'] ?>" size="5" maxlength="5" style="text-transform:uppercase " /></td>
  </tr>
    
  <tr>
    <td class="Etiqueta1"><span class="Asterisco">*</span> Carga familiar:</td>
    <td><input name="Per_Car" id="Per_Car" type="text" value="<?Php echo $row_rs_consulta['Per_Car'] ?>" size="2" maxlength="2" onblur="numerico(document.form2.Per_Car)" /></td>
  </tr>
  <tr>
    <td class="Etiqueta1">Observaci&oacute;n:</td>
    <td><textarea name="Per_Obs" cols="35" id="Per_Obs"><?Php echo $row_rs_consulta['Per_Obs'] ?></textarea></td>
  </tr>
</table>

</FIELDSET>
</FIELDSET> 
  <input name="hdd_save" type="hidden" id="hdd_save" value="insertar">
  <table border="0" class="Azul">
  <tr>
    <td  height="23">
       <button name="btn_guardar" type="button" class="btn btn-primary start" title="Guardar" onClick= "validar_requeridos(this.form, 'Prs_Ced*Prs_Nom*Prs_Ape*Prs_Sex*ann_ini*mes_ini*dia_ini*Pas_Cod*Reg_Cod*Pro_Cod*Ciu_Cod*Par_Cod*Prs_Esc*Prs_Dir*Ciu_Cod_N*Per_Car*Per_Tit', 1)" value= "Actualizar"><i class="icon-book icon-white"></i><span>&nbsp;&nbsp;Guardar</span></button>
 	  </td>
  </tr>
</table><br />
  <input name="hdd_prs" type="hidden" id="hdd_prs" value="<?Php echo $hdd_prs ?>">
  <?Php //
  } // fin del if (isset($codigo))?>
</form>        
</td>
  </tr>
</table>	   
</BODY></HTML>
<?php
/* Cierro las conexiones */
$obBD_con1->liberar();
$obBD_conexion->cerrar();
?>