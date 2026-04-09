<?php 
/**
* Descripcion: Permite registrar el personal de la Empresa 
* Fecha de actualizacion:	2016-03-16  Desarrollador: Jose Cumbicos
*/

require_once('../../administrador/LOGICA/seguridad.php'); 
require_once('../LOGICA/rhu_log_personal.php');	  
require_once('../../Librerias/procedimientos/almacenados_standar.php');	
require_once('../../Librerias/postclass.php');	  

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_rhu($Ses_Dat_Dis);
/* Cracion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_rhu;

//require_once("../../componentes/FRONT/ajax_pai_reg_pro_ciu_par.php"); 
 
/*Llamado al componente*/

/* Llamado de la libreria para evitar el reenvio de datos */
$thisPost = new Post_Block;
/* Comprobación de la existencia de persona - estudiante */  
if(isset($hdd_comprobar))
{
	/* Cuando es igual a cero, equivale a ingresar los datos de persona - estudiante 
	por primera vez */
	if ($Prs_Ced!="")
	{
		/* Consulta de los datos de la persona */
		$row_rs_persona=$obBD_con1->getRowConsulta(1,trim($Prs_Ced),$obBD_conexion);
		$total_rs_persona=$row_rs_persona['Prs_Cod'] > 0? 1 : 0;
		$Prs_Cod=$row_rs_persona['Prs_Cod'];		
		/*Consulta de la existencia de la persona en la tabla personal */
		$row_rs_comprobar=$obBD_con1->getRowConsulta(2,$Prs_Cod.'*'.$Ses_Emp_Cod, $obBD_conexion);		
		$total_rs_comprobar=$row_rs_comprobar['Per_Cod'] > 0? 1 : 0;
	
		/* Inicializa el evento */
		$event = 0;
		
		/* Control para saber si se muestra o no el formulario */
		if ($total_rs_comprobar == 0 && $total_rs_persona == 0)
		{
			/* Registra persona - personal*/
			$event = 1;
		}	  
		else
		{
			/* Registra personal */
			if ($total_rs_comprobar == 0 && $total_rs_persona > 0)
			{
				$event = 2;
			}
		}//FIn del if ($total_rs_comprobar == 0 && $total_rs_persona == 0)	
	}//Fin del if ($Prs_Ced!=0)
	else
	{
		$event = 1;
	}//Fin del else if ($Prs_Ced!=0)
}//Fin del if(isset($comprobari))	
/* Consulta los datos de la persona si $event == 1 */

if ($event == 2)
{   	
	$row_rs_persona=$obBD_con1->getRowConsulta(4,trim($Prs_Cod),$obBD_conexion);	
	$total_rs_persona=$row_rs_persona['Prs_Cod'] > 0? 1 : 0;	
}//Fin del if ($event == 1) 

/****************/
if (isset($hdd_save)) 
{
	if ($thisPost->postBlock($_POST['postID'])) { 
	/* Cracion del objeto mysql para las inserciones */
		$obBD_ins1 =  new Class_Log_Datos_Rhu;				
		$obBD_ins1->inicio_transaccion($obBD_conexion->conexion);
		
		/* Si $event = 1, entonces la persona no esta registrada */
		if ($event == 1)
		{
					
			$Prs_Fec = $ann_ini."-".$mes_ini."-".$dia_ini;		
			/* Inserción de la persona */
			$obBD_ins1->grabarv_registros(sentencias_rhu(3, $obBD_ins1->parametros($Ciu_Cod.'*'.$Prs_Ced.'*'.$Prs_Nom.'*'.$Prs_Ape.'*'.$Prs_Fec.'*'.$Prs_Dir.'*'.$Prs_Tel.'*'.$Prs_Te2.'*'.$Prs_Cel.'*'.$Prs_Sex.'*'.$Prs_Esc.'*'.$Prs_Cor.'*'.$Prs_San.'*'.$Pas_Cod.'*'.$Ide_Cod.'*'.$Par_Cod)), $obBD_conexion->conexion);	
			$Prs_Cod = $obBD_ins1->insercionid($obBD_conexion->conexion);
			
		}//Fin del if ($event == 1)
		
		/* Inserción del personal */
		$obBD_ins1->grabarv_registros(sentencias_rhu(9, $obBD_ins1->parametros($Prs_Cod.'*'.$Per_Obs.'*'.$Per_Tit.'*'.$Per_Car.'*'.$Ses_Emp_Cod)),$obBD_conexion->conexion);	
		/*$Per_Cod = $obBD_ins1->insercionid($obBD_conexion->conexion);	*/	
		unset($hdd_comprobar);										
		/****************************************************************/
		$obBD_ins1->fin_transaccion($obBD_conexion->conexion);
		/***************************************************************/																								?>
<script LANGUAGE="JavaScript">	
				<!--	alert ("¡¡¡ No se ha podido GUARDAR los datos!!!\nEl personal con cédula/R.U.C.: <?Php // echo $Prs_Ced; ?> ya existe en la base de datos"); -->
</script> 
<?Php unset($event);
		//}//Fin del else if ($total_rs_existe == 0)
	} //if ($thisPost->postBlock($_POST['postID']))
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
/* Consulta de las ciudades */
$row_rs_ciudad=$obBD_con1->getArrayConsulta(7,'', $obBD_conexion);
$total_rs_ciudad = $row_rs_ciudad['Ciu_Cod'] > 0? 1 : 0;
/* Consulta de las nacionalidades */
$row_rs_pais=$obBD_con1->getArrayConsulta(6,'',$obBD_conexion);
$total_rs_pais = $row_rs_pais['Pas_Cod'] > 0? 1 : 0;
/* Consulta de  de ESCUELAS */
$row_rs_escuelas=$obBD_con1->getRowConsulta(8,'',$obBD_conexion);
$total_rs_escuelas = $row_rs_escuelas['Esc_Cod'] > 0? 1 : 0;

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
<table width="100%" border="0" cellpadding="0" cellspacing="0" class="table">
	<tr class="BarraTitulo">
	  <td height="10">&raquo; registro de personal </td>
  </tr>
	<tr>
        <td height="389" valign="top">

  <?Php     
	/* Entra cuando niega un evento */
	if (!($event > 0)) 
	{ 
	?>
<form method="post" name= "form1" action="<? echo $_SERVER['PHP_SELF'];?>">
      <input name="opiden" type="hidden" id="opiden" value="N">
      <fieldset>
        <legend>
          <label class="Titulos2">Nacionalidad:</label>
        </legend>
        <table width="100%" border="0" cellpadding="0">
          <tr>
            <td width="4%"  align="right" class="LetraNegra"><input name="opn" type="radio" value="0" onClick="document.getElementById('opiden').value='N'; setfocus(this.form.Prs_Ced)" checked="checked"></td>
            <td width="96%" class="LetraNegra">Nacional
              &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
              <input name="opn" type="radio" value="0" onClick="document.getElementById('opiden').value='E'; setfocus(this.form.Prs_Ced)">
              Extranjero </td>
          </tr>
        </table>
      </fieldset>
      <fieldset>
        <legend>
          <label class="Titulos2">Comprobar registro</label>
        </legend>
        <table width="100%"  border="0">
          <tr>
            <td width="20%" align="right"  class="Etiqueta1"><span class="Asterisco">*</span> C&eacute;dula:</td>
            <td width="80%"  class="LetraNegra"><input name="Prs_Ced" type="text" id="Prs_Ced" onBlur="if(document.getElementById('opiden').value == 'N'){ validarDocumento(this.form.Prs_Ced)}" 
		 onKeyPress="Ent_Sub();"
		 value="<?Php //echo $Prs_Ced ?>" size="13" maxlength="13">
                <span class="Texto_Reporte_Rojo">&nbsp;&nbsp;&nbsp;&nbsp;
                <?Php 
		  if (isset($hdd_comprobar))
		  {
		  	if ($total_rs_comprobar > 0) 
		  	{ 
		  		echo "El personal con cédula/R.U.C.: ".$Prs_Ced.", ya existe";
			}//Fin del if ($total_rs_comprobar > 0 )  
		  	else 
		  	{
		  		echo "El personal no existe";
			}
		  } //Fin del if (isset($hdd_comprobar))
		  ?>
              </span> </td>
          </tr>
        </table>
        </fieldset>
      <br>
      <input name="hdd_comprobar" type="hidden" id="hdd_comprobar" value="insertar">
      <table width="129">
        <tr>
          <td width="121"><button name="Btn_comprobar" type="button" class="btn btn-primary start" title="Comprobar" id="Btn_comprobar" value="Comprobar" onClick="validar_requeridos(this.form, 'Prs_Ced', 0)" ><i class=" icon-refresh icon-white"></i><span>&nbsp;Comprobar</span></button>
          </td>
        </tr>
      </table>
</form>
<?Php
}//Fin del if ($event > 0)
/* Solo entra cuando hay que insertar en persona - estudiante ó estudiante */

if ($event > 0)
{ 
?>
<table width="100%"  border="0">
<tr>
  <td> 
<form method="post" name= "form1" id="form1" action="<? echo $_SERVER['PHP_SELF'];?>">
<FIELDSET>
<LEGEND>
<label class="Titulos2">Datos a registrar</label>
</LEGEND>
<?Php echo mensaje_requerido(); //Muestra el mensaje de requerido  
/* Creacion del campo repost */
$thisPost->startPost();
?>  
<input name="event" type="hidden" id="event" value="<?Php echo $event; ?>">
<FIELDSET>
<LEGEND>
<label class="Titulos2">Datos Personales</label>
</LEGEND>
<table width="100%" border="0">
  <tr>
	<td width="20%" class="Etiqueta1"><span class="Asterisco">*</span> C&eacute;dula/R.U.C.:</td>
	<td width="80%" class="LetraNegra">
	  <input name="Prs_Ced" type="text" id="Prs_Ced" onBlur="validarDocumento(this)" value="<?Php echo $Prs_Ced;?>" size="13" maxlength="13"
	  <?php if ($event != 0){ echo "readonly='true' style='border:none'"; } ?>>
	<?php
	if ($total_rs_persona >0)
	{ ?>
	  <input name="Prs_Cod" type="hidden" id="Prs_Cod" value="<?php echo $row_rs_persona['Prs_Cod']; ?>">
	<?Php
	} 	
	?>	</td>
	</tr>
	<?php 	
	$rs_identifica=$obBD_con1->getArrayConsulta(5,'',$obBD_conexion);	
	$total_rs_identifica=$rs_identifica['Ide_Cod'] > 0? 1 : 0;
	?>
  <tr>
    <td class="Etiqueta1"><span class="Asterisco">* </span>Tipo de documento: </td>
    <td class="LetraNegra"><?php if ($total_rs_persona == 0)
	{ ?><select name="Ide_Cod" id="Ide_Cod">
            <option></option>
            <?php
				foreach($rs_identifica as $row_rs_identifica) {  
			?>
            <option value="<?php echo $row_rs_identifica['Ide_Cod']?>"><?php echo $row_rs_identifica['Ide_Des']?></option>
            <?php
				};
		?>
        </select>
		 <?Php
	} else { echo $row_rs_persona['Ide_Des']; }
	?></td>
  </tr>
  <tr>
	<td class="Etiqueta1"><span class="Asterisco">*</span> Nombres:</td>
	<td class="LetraNegra">
	<?php
	if ($total_rs_persona == 0)
	{ ?>	
	<input name="Prs_Nom" type="text" id="Prs_Nom" style="text-transform:uppercase" value="" size="50" maxlength="50">
	<?Php
	} else { echo $row_rs_persona['Prs_Nom']; } ?>	</td>
	</tr>
  <tr>
	<td class="Etiqueta1"><span class="Asterisco">* </span>Apellidos: </td>
	<td class="LetraNegra">
	<?php
	if ($total_rs_persona == 0)
	{ ?>		
	  <input name="Prs_Ape" type="text" id="Prs_Ape" style="text-transform:uppercase" value="" size="50" maxlength="50">
	<?Php
	} else { echo $row_rs_persona['Prs_Ape']; } ?>	          </td>
	</tr>
  <tr>
	<td class="Etiqueta1"><span class="Asterisco">* </span>Genero: </td>
	<td class="LetraNegra">
	<?php
	if ($total_rs_persona == 0)
	{ ?>			
	<select name="Prs_Sex" id="Prs_Sex">
		<option value=""></option>
		<option value="M">MASCULINO</option>
		<option value="F">FEMENINO</option>		
	</select>	
	<?Php
	} else { echo $row_rs_persona['Prs_Sex']; }
	?>	</td>
	</tr>
<?php
if ($total_rs_persona == 0)
{ ?>  	
  <tr>
	<td class="Etiqueta1">Tipo de Sangre: </td>
	<td class="LetraNegra"><input name="Prs_San" type="text" id="Prs_San" style="text-transform:uppercase" value="<?Php echo $row_rs_persona['Prs_San']; ?>" size="4" maxlength="4"></td>
	</tr>
<?Php
}//Fin del if ($total_rs_persona == 0)
?>
  <tr>
	<td class="Etiqueta1">  <span class="Asterisco">*</span> Fecha de nacimiento : </td>
	<td class="LetraNegra">
	<?php
	if ($row_rs_persona['Prs_Fec'] == "")
	{ ?>				
        <span class="Asterisco">A&ntilde;o
        <select name="ann_ini" onChange="asignaDias(document.form2.dia_ini, document.form2.mes_ini, document.form2.ann_ini)" id="ann_ini">
          <?Php   
                    for ($i=1950; $i<= date("Y"); $i++)
                    {
                ?>
          <option value="<?Php echo $i ?>" <?Php if($anio==$i) { echo "selected"; }  ?> ><?Php echo $i ?> </option>
          <?Php
                    }
                ?>
        </select>
		mes
		<select name="mes_ini" onChange="asignaDias(document.form2.dia_ini, document.form2.mes_ini, document.form2.ann_ini)" id="mes_ini">
  		<?Php 
			/*Iniciacion del arreglo de meses*/
			$row_rs_des = array ("Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", 
					"Octubre", "Noviembre", "Diciembre");
			for ($i=1; $i<=12;$i++)
			{
			?>
        <option value="<?Php echo $i; ?>" <?Php if($mes==$i) { echo "selected"; }  ?>  > <?Php echo $row_rs_des[$i-1] ?> </option>
        <?Php } ?>
		</select>
        dia <span class="Label1">
        <select name="dia_ini" id="dia_ini">
          <?Php	for ($i=1; $i<=31;$i++)
                {
          ?>
          <option value="<?Php echo $i; ?>" <?Php if($dia==$i) { echo "selected"; }  ?>  ><?Php echo $i; ?> </option>
          <?Php
                        }
                       ?>
        </select>
		</span></span> 
	<?Php
	} else { echo $row_rs_persona['Prs_Fec']; }
	?>		</td>
	</tr>
  </table>
<table width="100%" border="0">
  <tr>
    <td width="20%" class="Etiqueta1"><span class="Asterisco">*</span> Pa&iacute;s de nacimiento:</td>
    <td class="LetraNegra">
	<?Php	
	/* consulto los paises en la base de datos */
	$rs_paises=$obBD_con1->getArrayConsulta(106,'', $obBD_conexion);		
?>
    <select name="Pas_Cod" id="Pas_Cod" onChange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_pai_cod=1&Pas_Cod=' + this.value,'div_regiones')"  
    style="text-transform:uppercase">
    		<option value="">Seleccione...</option>
        <?Php foreach($rs_paises as $row_rs_paises){ ?>  
          <option <?PHP if($row_rs_paises['Pas_Cod']== $row_rs_persona['Pas_Cod'] ){ echo "selected";}?> value="<?  echo $row_rs_paises['Pas_Cod']; ?>"><?  echo $row_rs_paises['Pas_Nom']; ?></option>
         <?Php } ?> 
    </select></td>
  </tr>
  <tr>
    <td width="20%" class="Etiqueta1"><span class="Asterisco">*</span>Region de nacimiento:</td>
    <td width="80%" class="LetraNegra"><div id="div_regiones" >
      <select name="Reg_Cod" id="Reg_Cod">
      <option value="">Seleccione...</option>
      </select>
    </div></td>
  </tr>
  <tr>
    <td width="20%" class="Etiqueta1"><span class="Asterisco">*</span>Provincia de nacimiento:</td>
    <td class="LetraNegra"><div id="div_provincias">
      <select name="Pro_Cod" id="Pro_Cod">
        <option value="">Seleccione...</option>
      </select>
    </div></td>
  </tr>
  <tr>
    <td width="20%" class="Etiqueta1"><span class="Asterisco">*</span>Ciudad de nacimiento:</td>
    <td class="LetraNegra">
	<div id="div_ciudades">
	 <select name="Ciu_Cod" id="Ciu_Cod" >
       
         <option value="">Seleccione...</option>
        
     </select>
	  </div>
    </td>
  </tr>
  <tr>
    <td width="20%" class="Etiqueta1"><span class="Asterisco">*</span>Parroquia de nacimiento:</td>
    <td class="LetraNegra">
	<div id="div_parroquias">
      <select name="Par_Cod" id="Par_Cod">
      <option value="">Seleccione...</option>
      </select>
    </div></td>
  </tr>
</table>

 <table width="100%">
   <tr>
	<td width="20%" class="Etiqueta1"><span class="Asterisco">*</span> Estado Civil : </td>
	<td class="LetraNegra"><?php
	if ($total_rs_persona == 0)
	{ ?>
	  <select name="Prs_Esc" id="Prs_Esc">
	    <?php 
			$row_rs_cod = array ("S","C","D","V","U");
			$row_rs_des = array ("SOLTERO/A", "CASADO/A", "DIVORCIADO/A", "VIUDO/A", "UNION lIBRE");
			for ($i=0;$i<count($row_rs_cod);$i++) 
			{  
  ?>
	    <option value="<?php echo $row_rs_cod[$i]?>" <?php if ($row_rs_cod[$i]==$row_rs_persona['Prs_Esc']) { echo "selected"; } ?>><?php echo $row_rs_des[$i]?></option>
	    <?php
			}
 ?>
	    </select>
	<?Php
	} else { echo $row_rs_persona['Prs_Esc']; }
	?>		</td></tr>
  
  <tr>
	<td class="Etiqueta1"><span class="Asterisco">*</span> Direcci&oacute;n domiciliaria:</td>
	<td class="LetraNegra">
	<?php
	if ($total_rs_persona == 0)
	{ ?>			
	<input name="Prs_Dir" type="text" style="text-transform:uppercase" id="Prs_Dir" value="" size="50" maxlength="50">
	<?Php
	} else { echo $row_rs_persona['Prs_Dir']; echo $row_rs_persona['Pas_Nom']; }
	?>	</td>
	</tr>
  <tr>
	<td class="Etiqueta1"><span class="Asterisco">*</span> Ciudad domiciliaria:</td>
	<td class="LetraNegra">
	  <?php
	if ($total_rs_persona == 0)
	{ ?>
	  <select name="Ciu_Cod" id="Ciu_Cod">
	    <option>Seleccione...</option>
	    <?php
			foreach($row_rs_ciudad as $datos) {  
		?>
	    <option value="<?php echo $datos['Ciu_Cod']?>"><?php echo $datos['Ciu_Des']?></option>
	    <?php } ?>
	    </select>
	<?Php
	} else { echo $row_rs_persona['Ciu_Des']; }
	?>		</td></tr>
  <tr>
	<td class="Etiqueta1"><span class="Asterisco">*</span> Nacionalidad: </td>
	<td class="LetraNegra"><?php
	if ($$row_rs_persona['Pas_Nac'] == "")
	{ ?>
	  <select name="Pas_Cod" id="Pas_Cod">
	    <option>Seleccione...</option>
		<?php
			foreach($row_rs_pais as $row){  
		?>
	    <option value="<?php echo $row['Pas_Cod']?>"><?php echo $row['Pas_Nac']?></option>
	    <?php } ?>
	    </select>
	  <?Php
	} else { echo $row_rs_persona['Pas_Nac']; }
	?></td>
  </tr>
<?php
if ($total_rs_persona == 0)
{ ?>  
  <tr>
	<td class="Etiqueta1"> Tel&eacute;fono 1:</td>
	<td class="LetraNegra">
	  	  
	  <input name="Prs_Tel" type="text" id="Prs_Tel" onBlur="numerico(this)" value="" size="15" maxlength="15">
	  &nbsp;<span class="Etiqueta1">Tel&eacute;fono 2 :
	    <input name="Prs_Te2" type="text" id="Prs_Te2" onBlur="numerico(this)" value="" size="15" maxlength="15">
	    &nbsp;&nbsp;Celular:
	    <input name="Prs_Cel" type="text" id="Prs_Cel" onBlur="numerico(this)" value="" size="15" maxlength="15">
	    </span></td></tr>
<?Php
}//Fin del if ($total_rs_persona == 0)
?>  
  <tr>
	<td class="Etiqueta1">Correo Electr&oacute;nico</td>
	<td class="LetraNegra"><?php
	if ($row_rs_persona['Prs_Cor'] == "")
	{ ?>
	<input name="Prs_Cor" type="text" id="Prs_Cor" onBlur="correo(this)" value="" size="50" maxlength="50">
<?Php
	} else { echo $row_rs_persona['Prs_Cor']; }
	?>	</td>
	</tr>
  <tr>
    <td colspan="2"><br></td>
  </tr>
</table>
</FIELDSET>
<FIELDSET>
<LEGEND>
<label class="Titulos2">Datos Laborales </label>
</LEGEND>
<table width="100%" border="0" cellpadding="0">
  <tr>
    <td width="20%" class="Etiqueta1"><span class="Asterisco">* </span>Iniciales de titulo:</td>
    <td width="80%"><span class="LetraNegra">
      <input name="Per_Tit" type="text" id="Per_Tit" value="" size="5" maxlength="5" style="text-transform:uppercase">
      </span></td>
  </tr>
  
  <tr>
    <td class="Etiqueta1"><span class="Asterisco">* </span>Carga familiar:</td>
    <td>
      <input name="Per_Car" type="text" id="Per_Car" value="" size="3" maxlength="3">	</td>
  </tr>
  <tr>
    <td class="Etiqueta1">Observacion</td>
    <td><label>
      <textarea name="Per_Obs" cols="30" rows="5" id="Per_Obs" style="text-transform:uppercase" ></textarea>
    </label></td>
  </tr>
</table>
</FIELDSET>

</FIELDSET>
<br>
<table width="100%" border="0" class="Azul">
      <tr> 
        <td width="100%">
          <input type="button" class="Boton_Guardar" title="Guardar" onClick="<?Php if ($event == 2){ ?>validar_requeridos(this.form, 'Per_Tit', 1)	
		  			 <?Php  } else { ?> validar_requeridos(this.form, 'Prs_Ced*Prs_Nom*Prs_Ape*Prs_Sex*ann_ini*mes_ini*dia_ini*Pas_Cod*Reg_Cod*Pro_Cod*Ciu_Cod*Par_Cod*Prs_Esc*Prs_Dir*Ciu_Cod*Per_Car*Per_Tit*Ide_Cod', 1) <?Php } ?>"	value="Guardar">
          </td>
      </tr>
    </table>
	  <input name="hdd_save" type="hidden" id="hdd_save" value="insertar">
</form>  
  </td>
</tr>      
</table> 
  
<?Php
}//Fin del if ($event > 0)
?>
</td>
  </tr>
</table>	    
</BODY></HTML>
<?php
/* Cierro las conexiones */
$obBD_con1->liberar();
$obBD_conexion->cerrar();
?>