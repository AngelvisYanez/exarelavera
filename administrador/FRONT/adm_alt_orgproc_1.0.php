<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<?php	
/* 
* Alias:	Registrar
* Descripci�n: Permite registrar los organizado y procesos del sistema
* Fecha de actualizaci�n:	2011-02-21
* Desarrollador:	Lewis Chimarro
* Fecha de actualizaci�n:	2013-06-08
* Desarrollador:	Fabian Gallardo
*/

require_once('../LOGICA/seguridad.php');
require_once('../LOGICA/adm_log_orgproc.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');	
require_once('../../Librerias/postclass.php');

/**
 * objeto para la conexion
 * @var Class_Log_Conexion_Adm
 */
$obBD_conexion = new Class_Log_Conexion_Admo($Ses_Dat_Dis);

/**
 * objeto para consultas
 * @var Class_Log_Datos_Adm
 */
$obBD_con1 =  new Class_Log_Datos_Admo;

/**
 * Llamado de la libreria para evitar el reenvio de datos
 * @var Post_Block
 */
$thisPost = new Post_Block;

/* 
* Grabado de un departamento nuevo en algun nodo del plan 
*/
if (isset($ndirectorio))
{
	if ($thisPost->postBlock($_POST['postID'])) 
	{		
		if (trim($Org_Img) != "")
		{
			/* 
			* Grabado de la imagen 1 
			*/
			$flag1 = upLoadImg_2('Org_Img', $Org_Img,'',2048,"../LOGICA/images/");
			$file1 = explode("/",$flag1);
		}
		else
		{
			$file1[0] = "folder.gif";
		}
		 
		if (trim($Org_Ime) != "")
		{
			/* 
			* Grabado de la imagen 2 
			*/
			$flag2=upLoadImg_2('Org_Ime', $Org_Ime,'',2048,"../LOGICA/images/");
			$file2 = explode("/",$flag2);
		}
		else
		{
			$file2[0] = "folder-expanded.gif";
		}

		if ($flag1 != "0" and  $flag2 != "0")
		{
			/*
			* Inicio de la transaccion
			*/
			$obBD_con1->inicio_transaccion($obBD_conexion->conexion);
			/*
			* Variable de los parametros para la subido de la imagen
			*/
			$params = $np.'*'.$Org_Det.'*'.$Org_Des.'*'.$file1[count($file1)-1].'*'.$file2[count($file2)-1];
			/*
			* Variable que me indica si la imagen se subio correctacmente
			*/					
			$obBD_con1->operacionobBD(1, $params, $obBD_conexion);	
			/*
			* Fin del la transaccion
			*/
			$obBD_con1->fin_transaccion($obBD_conexion->conexion);
		}//Fin del if ($flag1 != "0" and  $flag2 != "0")
	}//Fin del if ($thisPost->postBlock($_POST['postID'])) 
}

/* 
* Grabado de un departamento nuevo en algun nodo del plan 
*/
if (isset($nproceso))
{
	if ($thisPost->postBlock($_POST['postID'])) 
	{	
		$ruta = explode("*",$Rut_Cod);
		if (trim($Pcs_Nom) != "")
		{
			/* 
			* Separa la ruta de la pagina, para encontrar la palabra administrador 
			*/
			$adm = explode ("/", $ruta);		
	
			if (in_array("administrador", $adm))//"Existe en el ARRAY"
			{ $ruta_save = ""; }
			else //"No existe en el ARRAY"
			{ $ruta_save = "../..".$ruta[1]; }
		
			/*
			* Grabado del archivo php hasta 1MB 
			*/
			$flag3=upLoadFile_1('Pcs_Nom', $Pcs_Nom,'',1048576, $ruta_save, 'php');
			$file3 = explode("/",$flag3);
		}
		elseif (trim($Pcs_Nom2) != "")
		{
			$flag3 = "Si";	
			$file3[0] = $Pcs_Nom2;
		}
		

		if ($flag3 != "0")
		{
			/*
			* Inicio de la transaccion
			*/
			$obBD_con1->inicio_transaccion($obBD_conexion->conexion);
			/*
			*  Variable de parametros para el ingreso de distributivo
			*/
			$param_dis = $np.'*'.$Pcs_Lin.'*'.$file3[count($file3)-1].'*'.$ruta[0].'*'.$Pcs_Tip.'*'.$Pcs_Det.'*'.$Tpr_Cod.'*'.$Pcs_Ord;
			/*
			* Inserci�n de distributivo 
			*/
			$obBD_con1->operacionobBD(2, $param_dis, $obBD_conexion);	
			/*
			* Fin del la transaccion
			*/
			$obBD_con1->fin_transaccion($obBD_conexion->conexion);
		}//Fin del if ($flag1 == "0")
	}//Fin del if ($thisPost->postBlock($_POST['postID'])) 
}
?>
<HTML>
	<HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
		<?Php require_once("../../mascaras/model1/estilos/estilos.php"); ?>								
		<script type="text/javascript" src="../VALIDACIONES/adm_val_orgproc.js"></script>	
		<script type="text/javascript" src="../../Librerias/validaciones/validacion.js"></script>	
         <!--Librerias para interfaz -->               
	    <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>
         <script type="text/javascript" src="../../Librerias/validaciones/interfaz.modals.js"></script>

		<script type="text/javascript">$(function() {$('#set1 *').tooltip({showURL: false});});</script>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	</HEAD>
<?php
if (!isset($np))
{
	$np=0;
}
/* 
* Cargado de los nodos - Codigo Empresa, Nodo Padre 
*/
if (isset($np))
{
	$rs_nodos = $obBD_con1->getArrayConsulta(3, $np, $obBD_conexion);
}
?>
<BODY>
<div id="set1">
<table width="100%" border="0" cellpadding="0" cellspacing="0" class="table">
	<tr class="BarraTitulo">
	  <td height="10">&raquo; Registro de Directorios y Procesos </td>
  </tr>
	<tr>
            <td height="389" align="left" valign="top" style="padding-left:10px;padding-right:10px;">
	<br>
 <?php	
		/*
		* Consulta la ruta del departamento 
		*/
		if ($np==0) 
		{
			echo "<strong>INICIO</strong>";
			$separador='';
		} 
		else 
		{
			$rs_direc = $obBD_con1->getArrayConsulta(4, $np, $obBD_conexion);
			
			foreach($rs_direc as $row_rs_direc){
				echo "Ud. est&aacute dentro de <strong><img src='../../mascaras/model1/imagenes/32x32/folder-expanded.png' width='22' height='25' title='Directorio'> ".strtoupper($row_rs_direc['Org_Des'])."</strong>";
			}
			$separador='.';
		}//Fin del if ($np==0)  
		?>    
	<table width="100%" border="1" cellpadding="0" cellspacing="0" class="fixedHeader01">      
    <thead>
	  <tr>
	    <th width="10%"><strong>C&oacute;d. Int.</strong></th>
	    <th colspan="2">
	      Directorios/Procesos       </th>
	    <th width="2%">&nbsp;</th>
	    </tr>    
    </thead>
    <tbody>    
	  <?Php
    /* 
	* Consulta los procesos del Directorio 
	*/
	$rs_procesos = $obBD_con1->getArrayConsulta(5, $np, $obBD_conexion);
	
	/*
	* Consulta los cargos del departamento seleccionado, que se detalla el nombre en la cabecera 
	*/
	if (count($rs_procesos) > 0)		
	{ 
       foreach($rs_procesos as $row_rs_procesos){
	?>
	  <tr>
	    <td align="center" ><?php echo $row_rs_procesos['Pcs_Cod']; ?></td>
	    <td width="39%"> 
			 <?Php	
			   if (isset($row_rs_procesos['Pcs_Est']) && $row_rs_procesos['Pcs_Est'] == 'I') 
			   { 
			   		$color = '#FF0000'; 
					$anulada++;
				} 
				else
				{
					$color = '';	
				}
					 echo '<font color="'.$color.'">';
					if ($row_rs_procesos["Pcs_Tip"] == 'P')
					{
						$img_pag = '<img style="vertical-align:middle" src="../../mascaras/model1/imagenes/32x32/proceso.png" width="20" height="18" title="P�gina de tipo Proceso">';
					}
					else
					{
						$img_pag = '<img style="vertical-align:middle" src="../../mascaras/model1/imagenes/32x32/reporte.png" width="20" height="18" title="P�gina de tipo Reporte">';						
					}
					echo "Orden: ".$row_rs_procesos['Pcs_Ord'].' - '.$img_pag.$row_rs_procesos['Pcs_Lin']."</font><br>"; 
          ?>
        </td>
	    <td width="49%"><?Php echo $row_rs_procesos['Rut_Des']."<b>".$row_rs_procesos['Pcs_Nom']."</b>"; ?></td>
	    <td align="center">&nbsp;</td>
	   </tr>
      <?Php
	   } 
	}//Fin del if (count($rs_procesos) > 0)	
	
   /*
   * Valida si existen nodos 
   */
        //echo(count($rs_nodos).'dfgdfgdfgdfgdfgdfgdf');
   $anulada=0;     
   if (count($rs_nodos) > 0) 
   { 
	  foreach($rs_nodos as $row_rs_nodos){ 	 ?>         
	  <tr>
            <td align="center"><?php echo $row_rs_nodos['Org_Cod']; ?>
            </td>
            <td colspan="2">
            <?Php
			if ($row_rs_nodos['Org_Mod'] == 'I') 
		    { 
				$color_d = '#FF0000'; 
				$anulada++;
			} 
			else
			{
				$color_d = '';	
			}				
			?>
			<img style="vertical-align:middle" src="../LOGICA/images/<?Php echo $row_rs_nodos['Org_Img'] ?>" width="22" height="25" title="Departamento"><?php echo "<font color='".$color_d."'><strong> ".$row_rs_nodos['Org_Des']."</strong></font>"; ?>

          </td>
            <form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post" name= "form2" id="form2">
            <td align="center">
            <?Php 
			if ($row_rs_nodos['Org_Mod'] == 'A') 
			   { ?>			
            <input type="hidden" name="np" id="np" value="<?php echo $row_rs_nodos['Org_Cod'];?>"/>	
            <button type="button" class="btn btn-success btn-mini" onclick="submit()"><i class="icon-arrow-right icon-white"></i></button>
            <!--<input name="img1"  id="img1" type="image" src="../../mascaras/model1/imagenes/32x32/forward.png" width="18" height="18" title="Elegir" style="cursor:pointer">-->
            <?Php
			   }
			   else
			   { echo "&nbsp;"; } ?>
            </td>
            </form> 
        </tr>
<?Php		
	  }
   } 
   else 
   { ?>            				
	  <tr><td colspan="4"><?Php echo error_alerta("No hay <strong>DIRECTORIOS</strong> que mostrar!", 1); ?></td>
	    </tr>
<?php }//Fin del  if (count($rs_nodos) > 0) ?>
		</tbody>
	  </table>
           
   	<br>
<table border="0" cellpadding="0" cellspacing="0">
  <tr>
  <form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post" name= "form3" id="form3">
	<td align="right">
	<?Php
	if (isset($rs_direc) && count($rs_direc) >0)
	{ 
		foreach($rs_direc as $row_rs_direc){
		?>
			<input type="hidden" name="np" id="np" value="<?php echo $row_rs_direc['Org_Niv'];?>"/> 
            <button name="atras" type="button" class="btn btn-inverse fileinput-button" title="Atr&aacute;s" id="atras" value="Atras" onClick="submit()" >
               <i class=" icon-arrow-left icon-white"></i>
               <span>&nbsp;&nbsp;Atras&nbsp;&nbsp;</span>
       		 </button>&nbsp;&nbsp;
            <?Php
		}
	}//Fin del if (count($rs_direc) >0)
	?></td>
  </form>
  <?Php
  
  /*
  * Solo crea directorios en caso de no haber procesos 
  */
  if (count($rs_procesos) == 0)
  {
  ?>
	<td align="right"><button id="Directorio" type="button" class="btn btn-success fileinput-button" title="Agregar directorio" value="" onclick="botones_dic(1);Muestra_Aparecer();">
           <i class=" icon-folder-close icon-white"></i>
           <span>Directorio</span>
</button>
    </td>
    <?Php
  }//Fin del if ($total_rs_procesos > 0)
  
    /*
	* np = 0, significa que se encuentra en la raiz del arbol 
	*/
	if ($np!=0) 
	 { 
	 	if (count($rs_nodos) == 0)
		{
	 ?>
			<td align="right"><button type="button" name="Proceso" class="btn btn-success fileinput-button" title="Agregar proceso" id="Proceso" value="" onClick="botones_dic(2);Muestra_Aparecer();">
           <i class=" icon-cog icon-white"></i>
           <span>Proceso</span>
</button>
     <?Php
		}//Fin del if (count($rs_nodos) == 0)
	 } //Fin del if ($np!=0)
	 ?>
		  </tr>
      
	</table>

 <?php 
	
	/* Parametro de la busqueda por fecha en compras 
	* Control para setear el arreglo solo cuando tenga valores
	*/
	if ($anulada > 0)
	{		
		$com_leyenda[1]=$anulada;
	}//Fin del if ($anulada > 0) ?><br>
 <?Php require_once('../../componentes/FRONT/com_con_leyenda.php');?>




<div id="bgtransparent" class="bgtransparent" style="display:none" onClick="closeModal();"></div>
	  <div id="bgmodal"  class="bgmodal" style="display:none" >
 
     <div id="id_directorio">
       <?php
      
      /* 
      * Solo crea directorios en caso de no haber procesos 
      */
      if (count($rs_procesos) == 0)
      {
        ?>
       <form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post" enctype="multipart/form-data" name="form4" id="form4">        
        <FIELDSET>    
        <LEGEND>
        <label class="Titulos2">Datos a registrar <img style="vertical-align:middle" src="../../mascaras/model1/imagenes/32x32/folder.png" width="18" height="18"></label>
        </LEGEND>
        <?php  
        
        /*
        * Creacion del campo REPOST
        */
        $thisPost->startPost(); ?>    
            <table width="100%" border="0" cellpadding="0" cellspacing="0">
              <tr>
                <td width="13%" class="Etiqueta1"><span class="Asterisco">*</span> Directorio:&nbsp;</td>
                <td width="87%" class="LetraNegra"><input name="Org_Des" type="text" id="Org_Des" size="50" maxlength="50">&nbsp;</td>
              </tr>
              <tr>
                <td class="Etiqueta1"><span class="Asterisco">*</span> Detalle:&nbsp;</td>
                <td class="LetraNegra"><textarea name="Org_Det" cols="70" rows="3" id="Org_Det"></textarea></td>
                </tr>
              <tr>
                <td class="Etiqueta1">Forder cerrado:&nbsp;</td>
                <td class="LetraNegra"><input name="Org_Img" type="file" class="Boton" id="Org_Img" size="40">
                  Por defecto: folder.png</td>
                </tr>
              <tr>
                <td class="Etiqueta1">Folder abierto:&nbsp;</td>
                <td class="LetraNegra"><input name="Org_Ime" type="file" class="Boton" id="Org_Ime" size="40">
                  Por defecto: folder-expanded.png</td>
                </tr>
              <tr>
                <td class="LetraNegra">&nbsp;</td>
                <td class="LetraNegra">
                  <input name="np" type="hidden" id="np" value="<?php echo $np; ?>">
                <input name="ndirectorio" type="hidden" id="ndirectorio" value="ndirectorio">	
                </td>
              </tr>
            </table>
        </FIELDSET>
        <br>
         <button type="button" name="btn_save" id="btn_save" class="btn btn-primary fileinput-button" title="Guardar" onclick="validar_requeridos(this.form, 'Org_Des*Org_Det', 1)" value="Guardar"><i class=" icon-book icon-white"></i><span>&nbsp;&nbsp;Guardar&nbsp;&nbsp;</span></button>
    
          </form> 	 
      <?php
        }//Fin del if (count($rs_procesos) == 0)
        ?>      
    </div>   
     
    <div id="id_proceso">
      <form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post" enctype="multipart/form-data" name= "form4" id="form">
        <FIELDSET>
          <LEGEND>
            <label class="Titulos2">Datos a registrar <img style="vertical-align:middle" src="../../mascaras/model1/imagenes/32x32/proceso.png" width="20" height="20"></label>
          </LEGEND>
          <?php  
			/* 
			* Consulta las rutas 
			*/
			$rs_rutas = $obBD_con1->getArrayConsulta(6, '', $obBD_conexion);
		    
			/* 
			* Consulta los procesos del Directorio 
			*/
			$rs_tipo_proce = $obBD_con1->getArrayConsulta(7, '', $obBD_conexion);
		    
			/*
			* Creacion del campo REPOST
			*/
			$thisPost->startPost(); ?>
          <table width="100%" border="0" cellpadding="0" cellspacing="0">
            <tr>
              <td class="Etiqueta1">Directorio:&nbsp;</td>
              <td class="LetraNegra"><?Php echo strtoupper($row_rs_direc['Org_Des']); ?></td>
            </tr>
            <tr>
              <td width="13%" class="Etiqueta1"><span class="Asterisco">*</span> Proceso:&nbsp;</td>
              <td width="87%" class="LetraNegra"><input name="Pcs_Lin" type="text" id="Pcs_Lin" size="50" maxlength="50">
                &nbsp;</td>
            </tr>
            <tr>
              <td class="Etiqueta1"><span class="Asterisco">*</span> P&aacute;gina:&nbsp;</td>
              <td class="LetraNegra"><input name="Pcs_Nom2" type="text" id="Pcs_Nom2" size="50" maxlength="50" onBlur="caracter(this, '.')">                <input name="Pcs_Nom" type="file" class="Boton" id="Pcs_Nom" size="40">
                <input type="checkbox" name="pagina" id="pagina" onClick="
                	document.getElementById('Pcs_Nom').value=''; document.getElementById('Pcs_Nom2').value='';
                    ShowHide('Pcs_Nom'); 	 
                    ShowHide('Pcs_Nom2'); 	 
                "> 
                Subir p&aacute;gina</td>
            </tr>
            <tr>
              <td class="Etiqueta1"><span class="Asterisco">*</span> Ruta:&nbsp;</td>
              <td class="LetraNegra">
	              <table border="0" cellpadding="0" cellspacing="0" class="LetraNegra">               
                <?Php
					foreach($rs_rutas as $row_rs_rutas){ ?>
                    <tr><td>
                     <input name="Rut_Cod" id="Rut_Cod" type="radio" value="<?php echo $row_rs_rutas['Rut_Cod'].'*'.$row_rs_rutas['Rut_De2']; ?>">
					 <?php echo $row_rs_rutas['Rut_De2']; ?>
                     </td></tr>
                    <?Php
                    } ?>
				</table>
               </td>
            </tr>
            <tr>
              <td class="Etiqueta1"><span class="Asterisco">*</span> Tipo:&nbsp;</td>
              <td class="LetraNegra"><select name="Pcs_Tip" id="Pcs_Tip">
                <option value="">Seleccione...</option>
                <option value="P">Proceso</option>
                <option value="R">Reporte</option>
                </select></td>
            </tr>
            <tr>
              <td class="Etiqueta1"><span class="Asterisco">*</span> Acceso:&nbsp;</td>
              <td class="LetraNegra"><select name="Tpr_Cod" id="Tpr_Cod">
                <option value="">Seleccione...</option>
                <?Php
				foreach($rs_tipo_proce as $row_rs_tipo_proce){
				?>
                    <option value="<?php echo $row_rs_tipo_proce['Tpr_Cod']; ?>"><?php echo $row_rs_tipo_proce['Tpr_Des']; ?></option>
                <?Php
				 }
				?>
              </select></td>
            </tr>
            <tr>
              <td class="Etiqueta1"><span class="Asterisco">*</span> Detalle:&nbsp;</td>
              <td class="LetraNegra"><textarea name="Pcs_Det" cols="70" rows="3" id="Pcs_Det"></textarea></td>
            </tr>
            <tr>
              <td class="Etiqueta1">Orden:&nbsp;</td>
              <td class="LetraNegra"><input name="Pcs_Ord" type="text" id="Pcs_Ord" size="3" maxlength="3" onKeyPress="return validar_numeric(event)"></td>
            </tr>
            <tr>
              <td class="LetraNegra">&nbsp;</td>
              <td class="LetraNegra">
                <input name="np" type="hidden" id="np" value="<?php echo $np; ?>">
                <input name="nproceso" type="hidden" id="nproceso" value="nproceso">
                </td>
            </tr>
          </table>
        </FIELDSET>
       
        <button type="button" class="btn btn-primary fileinput-button" title="Guardar" onclick="if (validar_options(this.form) == true){ if (document.getElementById('pagina').checked == true){ validar_requeridos(this.form, 'Pcs_Lin*Pcs_Nom*Rut_Cod*Pcs_Tip*Tpr_Cod*Pcs_Det', 1)} else {validar_requeridos(this.form, 'Pcs_Lin*Pcs_Nom2*Rut_Cod*Pcs_Tip*Tpr_Cod*Pcs_Det', 1)} }" value="Guardar"><i class=" icon-book icon-white"></i><span>&nbsp;&nbsp;Guardar&nbsp;&nbsp;</span></button>
       

      </form>
      <script type="text/javascript">
		 ShowHide('Pcs_Nom'); 	 
	  </script>
    </div>
    </div>
    
    <?php
  /* 
  * Solo crea directorios en caso de no haber procesos 
  */
  if (count($rs_procesos) == 0)
  {
	?>    
    <script type="text/javascript">
	 ShowHide('id_proceso'); 	 
	</script>
  <?Php
  }//FIn del if (count($rs_procesos) == 0)
  ?>
  
   </td>
  </tr>
</table>
</div>	  
</BODY></HTML>
<?php
/* 
* cierro las conexiones 
*/
$obBD_con1->liberar();
$obBD_conexion->cerrar();

?>