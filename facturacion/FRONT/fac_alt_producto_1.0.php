<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?Php 
/**
* Descripci�n: Permite registrar los rubros o productos del inventario
* Fecha de actualizaci�n:	2011-04-28
* Desarrollador:	Nebil Oyola
* Fecha de actualizaci�n:	2012-jun-08
* Desarrollador:	Lewis Chimarro
* Fecha de actualizaci�n:	2015-ene-28
* Desarrollador:	Lewis Chimarro
*/	
require_once('../../administrador/LOGICA/seguridad.php');	 
require_once('../LOGICA/fac_log_producto.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');	
require_once('../../Librerias/postclass.php');	

/** 
* Creaci�n del objeto para evitar el reenvio 
*/
$thisPost = new Post_Block;
/** 
* Creacion del Objeto de conexion 
*/  
$obBD_conexion = new Class_Log_Conexion_Pro($Ses_Dat_Dis);
/**
* Creaci�n del Objeto para consultas
*/
$obBD_con1 =  new Class_Log_Datos_Pro;

/**
* Ajax marca - actualiza los datos
*/
if ($ajax_marca==1)
{
	/**
	* Carga las marcas activas 
	*/
    $rs_marca= $obBD_con1->getArrayConsulta(428,$Ses_Emp_Cod,$obBD_conexion);   
	$total_rs_marca =count($rs_marca);
?>
	<select name="Mar_Cod" id="Mar_Cod" onChange="ajax_datos('<?Php echo 	$_SERVER['PHP_SELF']; ?>?ajax_mar_val=1&Cat_Cod1='+document.getElementById('Cat_Cod1').value+'&Ite_Cod='+document.getElementById('Ite_Cod').value+'&Mar_Cod='+document.getElementById('Mar_Cod').value,'contenedormarca')">
            <option value="">Seleccione...</option>
            <?php  
			foreach($rs_marca as $row_rs_marca) {  			
	      ?>
            <option  value="<?Php echo $row_rs_marca['Mar_Cod']; ?>">
            <?php 
				echo $row_rs_marca['Mar_Des']; ?>
            </option>
            <?php } ?>
          </select>
          <div id="contenedormarca"></div>	
<?Php	
	exit();
}
 
/**
* Valida la marca al momento de guardar el producto
*/
if(isset($ajax_mar_val))
{   
	if($Ite_Cod<>'' || $Ite_Cod<>0 )
	{ 
	  	if($Mar_Cod<>'' || $Mar_Cod<>0 )
		{
     		$row_rs_ite_mar= $obBD_con1->getRowConsulta(1008,$Ite_Cod.'*'.$Mar_Cod,$obBD_conexion);
     		$total_rs_ite_mar=$row_rs_ite_mar['Pro_Cod'] > 0? 1 : 0;
			//$row_rs_ite_mar = $obBD_con1->registros();
	 		//$total_rs_ite_mar =  $obBD_con1->numregistros();
			
 		     	if ($total_rs_ite_mar<=0 or 1==1)
		    	{		
					echo "<img src='../../mascaras/model1/imagenes/32x32/aceptar.jpg'  width='22' height='22'>".' ' ;
				}
			 	else
				{
					echo "<img src='../../mascaras/model1/imagenes/32x32/gtk-no.gif'  width='22' height='22' >".' '." El Producto ya se encuentra registrado con la Marca";
				}
		}
		else
		{
				echo "<img src='../../mascaras/model1/imagenes/32x32/advertencia.png'  width='22' height='22'>"." No olvide de seleccionar la Marca" ;	
		}
	}
	else
	{ 
		echo "<img src='../../mascaras/model1/imagenes/32x32/advertencia.png'  width='22' height='22'>"." No olvide de seleccionar la Categoria" ;			
	}
exit();	
}
/** 
* En esta opcion se graba el item para secargado 
*/
if($ajax_1==1)
{
  		if($ajax_2==2)
		{
		   $row_rs_ite_cat= $obBD_con1->getRowConsulta(1009,trim($txt_b).'*'.$Cat_Cod,$obBD_conexion);          		   
		   $total_rs_ite_cat=$row_rs_ite_cat['Ite_Cod'] > 0? 1 : 0;
 		     if ($total_rs_ite_cat<=0)
		     {
		            $obBD_ins1 =  new Class_Log_Datos_Pro;
					/**
					* Inicio de la transaccion
					*/
					$obBD_ins1->inicio_transaccion($obBD_conexion->conexion);
					$obBD_ins1->consultasobBD(1005,$Cat_Cod.'*'.$text_cor1.'*'.$text_lar1,$obBD_conexion);	
					$Pro_Cod = $obBD_ins1->insercionid ($obBD_conexion->conexion);
					//echo "Guardado";
					$obBD_ins1->fin_transaccion($obBD_conexion->conexion);
		     }
			 else
			 { ?>
					 <script>alert('�Producto y Marca ya Existen!');</script>
					<?Php
			 }//Fin del else if ($total_rs_ite_cat<=0)
		}	  

	 	/**
		* Consulta los items de una empresa 
		*/
		$rs_consulta = $obBD_con1->getArrayConsulta(1022,trim($txt_b).'*'.$Ses_Emp_Cod,$obBD_conexion);    	
		$total_rs_consulta =  count($rs_consulta);
		
	   if ($total_rs_consulta>0)
	   { ?>	 
          <FIELDSET>
          <LEGEND>
          <label class="Titulos2">Resultados de la busqueda</label>
          </LEGEND>
            <table width="100%" cellpadding="0" cellspacing="0" border="1" class="fixedHeader01" >
              <thead>
              <tr>
                  <th width="4%">C�d. Int.</th>
                  <th width="30%">Descripci&oacute;n</th>
                  <th width="46%">Descripci�n Larga</th>
                  <th width="20%">Descripci�n Corta </th>                  
                  <th width="2%">&nbsp;</th>
              </tr>
              </thead>
              <tbody>
             <? foreach($rs_consulta as $row_rs_consulta){ ?>
                   <tr>
                       <td align="center"><?Php echo  $row_rs_consulta['Ite_Cod']; ?></td>
                       <td><?Php echo marcar_cadena($txt_b,$row_rs_consulta['Cat_Des'],'#FFFF00',1); ?></td>
                       <td><?Php echo  marcar_cadena($txt_b, $row_rs_consulta['Ite_Lar'],'#FFFF00',1);?></td>
                       <td><?Php echo $row_rs_consulta['Ite_Cor']; ?></td>
                       <td align="center">
                       <button type="button" class="btn btn-success btn-mini" title="Elegir" onClick="ponPrefijo('<? echo $row_rs_consulta["Ite_Cod"]; ?>','<? echo $row_rs_consulta["Ite_Cor"]; ?>','<? echo  $row_rs_consulta["Ite_Lar"]; ?>','<? echo  $row_rs_consulta["Cat_Cod"]; ?>','<? echo  $row_rs_consulta["Cat_Cdc"]; ?>','<? echo  $row_rs_consulta["Cat_Cod"]; ?>');
                       ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_mar_val=1&Ite_Cod='+ document.getElementById('Ite_Cod').value+'&Cat_Cod1='+ document.getElementById('Cat_Cod1').value,'contenedormarca'); ">
                    <i class=" icon-arrow-right icon-white"></i>
                </button>               
                       </td>		
                  </tr>
                  <? }?>
                 </tbody>
               </table>
  		<?php echo barra_estado($total_rs_consulta+0); ?>
  	<? }else{                       
            echo error_alerta("�No hay resultados que mostrar!", 1);?>   
   </FIELDSET>
	<br>
   <FIELDSET>
   <LEGEND>
   <label class="Titulos2">Datos a registrar</label>
   </LEGEND>
    <table width="500" border="0" align="left" cellpadding="0" cellspacing="0">
      <tr>
        <td><table width="500" border="0" cellpadding="0" cellspacing="0">
          <tr>
            <td class="Etiqueta1"><span class="Asterisco">*</span> Categor�a: </td>
            <td>
            <?php
			/**
			* Carga la categoria 
			*/
            $rs_categoria= $obBD_con1->getArrayConsulta(1030,$Ses_Emp_Cod,$obBD_conexion);
            $total_rs_categoria = count($rs_categoria);			
			?>
            <select name="Cat_Cod" id="Cat_Cod">
                <option value="">Seleccione...</option>
			<?php    
            foreach($rs_categoria as $row_rs_categoria){ ?>
            <option  value="<?Php echo $row_rs_categoria['Cat_Cod']; ?>">
                <?php if ($total_rs_categoria >0)
                       {
                           echo ">>".$row_rs_categoria['Grupo'].'>_'.ucfirst(strtolower($row_rs_categoria['Cat_Des']));
                       } ?>
            </option>
 		<?php  
			} ?>
			</select>
            </td>
          </tr>
          <tr>
            <td class="Etiqueta1"><span class="Asterisco">*</span> Descripci�n Corta: </td>
            <td><input type="text" id="text_cor" name="text_cor" size="30" maxlength="30" title="No escriba indicios de Marca"></td>
          </tr>
           <tr>
            <td class="Etiqueta1"><span class="Asterisco">*</span> Descripci�n Larga: </td>
            <td><input type="text" id="text_lar" name="text_lar" size="50" maxlength="50" title="No escriba indicios de Marca" value="<?Php echo $txt_b; ?>"><br /> </td>
          </tr>
           <tr>
            <td colspan="2">
            <span class="Alertas3">
            	<?php 
				echo error_alerta(" No escriba indicios de Marca", 3);
				?>
            </span>    
           </td>
          </tr>          
        </table></td>
      </tr>		
    </table>		
</FIELDSET>
<br>
<table width="110" border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td align="left">
    <button type="button" class="btn btn-primary start" title="Guardar" onclick="ajax_datos_tesoreria('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_1=1&ajax_2=2&text_cor1='+ document.getElementById('text_cor').value +'&Cat_Cod='+document.getElementById('Cat_Cod').value +'&text_lar1='+document.getElementById('text_lar').value+'&txt_b='+document.getElementById('text_lar').value ,'busqueda_item',this.form, 'Cat_Cod*text_cor*text_lar', 1)">
                   <i class="icon-book icon-white"></i>
                   <span>Guardar</span>
       </button> 
  </tr>
</table>
<?Php 		
	}//FIn del if ($total_rs_consulta>0)		
	exit();
}

if(isset($hdd_save))
{
 /**
 * Evitar el reenvio de formularios 
 */	
 if ($thisPost->postBlock($_POST['postID']))
	{	
	 /**
	 * Verifica si el producto y marca existen 
	 */
	 $row_rs_ite_mar= $obBD_con1->getRowConsulta(1008,$Ite_Cod.'*'.$Mar_Cod,$obBD_conexion);
     $total_rs_ite_mar=$row_rs_ite_mar['Pro_Cod'] > 0? 1 : 0;

	 	/**
		*  Evalua si el producto existe o no 
		*/
 		if ($total_rs_ite_mar<=0 or 1==1)
		{
		  $obBD_ins1 =  new Class_Log_Datos_Pro;
		
		  /**
		  * Consulta de  secuencias 
		  */
		  $row_rs_con_sec= $obBD_con1->getRowConsulta(1034,$Cat_Cod1,$obBD_conexion);
          $total_rs_con_sec=$row_rs_con_sec['Cat_Cdc'] > 0? 1 : 0;		 
		  $Cat_Cdc=$row_rs_con_sec['Cat_Cdc'];
		  /**
		  * Consulta la secuencia asignada al producto
		  */
		  $row_rs_con_sec= $obBD_con1->getRowConsulta(1033,$Cat_Cod1,$obBD_conexion);
		  $total_rs_con_sec=$row_rs_con_sec['Cat_Cdc'] > 0? 1 : 0;
		  $Pro_Secuencia=$row_rs_con_sec['Pro_Sec']+1;
	 	  $Pro_Cdc1=$Cat_Cdc.'.'.$Pro_Secuencia;
		  		  
		  if ($Pro_Uni=="")
			{
				$Pro_Uni=0;
			}
		  if ($Pro_Dsc=="")
			{
				$Pro_Dsc=0;
			}

		  /**
		  * Inicio de la transaccion de guardado
		  */
		  $obBD_ins1->inicio_transaccion($obBD_conexion->conexion);
		  /**
		  * Inserci�n del producto
		  */
		  $obBD_ins1->operacionobBD(1007,$Adq_Cod.'*'.$Ite_Cod.'*'.$Mar_Cod.'*'.$Iva_Cod.'*'. $Pro_Obs.'*'.$Pro_Bar.'*'.$Ubi_Cod.'*'.$Uni_Cod.'*'.$Pro_Secuencia.'*'.$Pro_Cdc1.'*'.$Pro_Uni.'*'.$Pro_Dsc.'*'.$Pre_Cod,$obBD_conexion);	
		 $Pro_Cod = $obBD_ins1->insercionid ($obBD_conexion->conexion);
		  /**
		  * Guardo en la tabla Stock
		  */
		  $obBD_ins1->operacionobBD(1205,'0'.'*'.$Ses_Suc_Cod.'*'.$Pro_Cod,$obBD_conexion);	
		  /**
		  * Consulta el tipo de precio  
		  */
		  $row_rs_con_tp= $obBD_con1->getRowConsulta(1019,'D*'.$Ses_Suc_Cod,$obBD_conexion);         
	      $total_rs_con_tp=$row_rs_con_tp['Tpv_Cod'] > 0? 1 : 0;		 
		  
		  if ($total_rs_con_tp>0)
		  {
		    $Tpv_Cod=$row_rs_con_tp['Tpv_Cod'];
		  }
		  else
		  {
		  	$Tpv_Cod=0;
		  }
		  /**
		  * Inserta un precio por defecto
		  */
		  $obBD_ins1->operacionobBD(1018,$Pro_Cod.'*'.$Pre_Pvp.'*'.'Precio 1'.'*'.$Ses_Suc_Cod.'*'.$Tpv_Cod,$obBD_conexion);	
		 $Vet_Cod = $obBD_ins1->insercionid ($obBD_conexion->conexion);
		 /**
		 * Genera el Codigo de Barra senececitan 12 caracteres para generar
		 */
		$Pro_var='';/* esta variable crea una cadena del codigo de barra*/
		$Pro_varg='';
		if($Pro_Gen==1)
		{
		switch ( strlen($Pro_Cod)) {
			case 1:
			 $Pro_var=$Pro_Cod."00000000000";
			break;
			case 2:
			 $Pro_var=$Pro_Cod."0000000000";
			break;
			case 3:
			 $Pro_var=$Pro_Cod."000000000";
			break;
			case 4:
			 $Pro_var=$Pro_Cod."00000000";
			break;
			case 5:
			 $Pro_var=$Pro_Cod."0000000";
			break;
			case 6:
			 $Pro_var=$Pro_Cod."000000";
			break;
			case 7:
			 $Pro_var=$Pro_Cod."00000";
			break;
			case 8:
			 $Pro_var=$Pro_Cod."0000";
			break;
			case 9:
			 $Pro_var=$Pro_Cod."000";
			break;
			case 10:
			 $Pro_var=$Pro_Cod."00";
			break;
			case 11:
			$Pro_var=$Pro_Cod."0";
			break;
		}
		$Pro_Bar=$Pro_var;
		$Pro_varg='G';
		}
		else
		{
		$Pro_varg='M';
		}
		/**
		* Actualiza el codigo de barras en el producto insertado
		*/
		$obBD_ins1->operacionobBD(1023,$Pro_Cod.'*'.$Pro_Bar.'*'.$Pro_varg,$obBD_conexion);	 
		//echo DOC;//Funciona con jquery-modal
   		$obBD_ins1->fin_transaccion($obBD_conexion->conexion);		 
   		}//Fin del if ($total_rs_ite_mar<=0)		
	}	
}
	/**
	* Carga las marcas activas 
	*/
    $rs_marca= $obBD_con1->getArrayConsulta(428,$Ses_Emp_Cod,$obBD_conexion);   
	$total_rs_marca =count($rs_marca);
	/** 
	* Carga todos los ivas actuales
	*/	
	$rs_iva= $obBD_con1->getArrayConsulta(429,'',$obBD_conexion);    
	$total_rs_iva =  count($rs_iva);
	/** 
	* Carga todas las adquisiciones activas
	*/		
	$rs_adq= $obBD_con1->getArrayConsulta(712,'',$obBD_conexion);
	$total_rs_adq =  count($rs_adq);				
	/** 
	* Carga todas las categorias tipo detalle de la empresa
	*/		
	$row_rs_categoria= $obBD_con1->getRowConsulta(432,$Ses_Emp_Cod,$obBD_conexion);    
	$total_rs_categoria=$row_rs_categoria['Cat_Cod'] > 0? 1 : 0;
	
	/** 
	* Carga todas las ubicaciones de donde se colocaran los productos
	*/		
	$rs_ubicacion= $obBD_con1->getArrayConsulta(1003,$Ses_Emp_Cod,$obBD_conexion);    
	$total_rs_ubicacion = count($rs_ubicacion);
	/** 
	* Carga todas tipos de unidades
	*/			
	$rs_unidad= $obBD_con1->getArrayConsulta(1004,'',$obBD_conexion);
	$total_rs_unidad = count($rs_unidad);
?>	 
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>		  	
		<?Php require_once("../../mascaras/model1/estilos/estilos.php"); ?>
		<script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>        
		<script language="javascript" src="../VALIDACIONES/fac_val_producto.js"></script>        
		<link rel="stylesheet" type="text/css" href="../../Librerias/jquery/modal/css/modal.css">
        <script type="text/javascript" src="../../Librerias/jquery/modal/js/jquery.js"></script>
        <script type="text/javascript" src="../../Librerias/jquery/modal/js/modal.js"></script>        
	    <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>
		<script type="text/javascript"> 
          $(function() {
                $('#set1 *').tooltip({showURL: false});
          });              			
		</script>                
		<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">		
</head>
<body>
<div id="set1">
<table width="100%" border="0" cellpadding="0" cellspacing="0" class="table">
	<tr class="BarraTitulo">
	  <td>&raquo;<span class="Estilo1"></span> Registrar Productos </td>
  </tr>
	<tr>
      <td valign="top"><form method="post" name="form2" action="<?Php echo $_SERVER['PHP_SELF']; ?>" >
<FIELDSET>
<LEGEND>
<label class="Titulos2">Datos a registrar </label>
</LEGEND>
<?Php 
    mensaje_requerido(); 
	/**
	* Creacion del campo repost 
	*/
	$thisPost->startPost();	//}//Fin del if ($thisPost->postBlock($_POST['postID']))
?>
<FIELDSET>
<LEGEND>
<label class="Titulos2">De la categor�a</label>
</LEGEND>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td width="13%" class="Etiqueta1">C&oacute;d. Int. Categoria:  </td>
    <td width="87%" class="LetraNegra"><table border="0" cellpadding="0" cellspacing="0">
        <tr>
          <td width="155">&nbsp;<input disabled="disabled" style="border:none; background:none" name="Ite_Cod2" type="text" id="Ite_Cod2"  size="15" maxlength="30" />
            <input  border="0" name="Ite_Cod" type="hidden" id="Ite_Cod"  size="15" maxlength="30" /></td>
          <td width="214">&nbsp;</td>
        </tr>
      </table></td>
  </tr>
  <tr>
    <td class="Etiqueta1">C&oacute;d. Secuencial: </td>
    <td width="87%" class="LetraNegra">&nbsp;<input readonly="readonly" style="border:none; background:none" name="Cat_Cod12" type="text" id="Cat_Cod12" value="" size="15" maxlength="30" />
      <input border="0" name="Cat_Cod1" type="hidden" id="Cat_Cod1" value="" size="15" maxlength="30" />
      <input border="0" name="Cat_Cdc" type="hidden" id="Cat_Cdc" value="" size="15" maxlength="30" /></td>
  </tr>
  
  <tr>
    <td class="Etiqueta1">Descripci�n  Corta: </td>
    <td class="LetraNegra">&nbsp;<input readonly="readonly" border="0" name="Ite_Cor" style="border:none; background:none" type="text" id="Ite_Cor" value="<?Php echo $row_rs_consulta['Ite_Cor']; ?>" size="15" maxlength="30" /></td>
  </tr>
  <tr>
    <td class="Etiqueta1">Descripci�n Larga: </td>
    <td class="LetraNegra">&nbsp;<input readonly="readonly" name="Ite_Lar"  style="border:none; background:none" type="text" id="Ite_Lar" value="<?Php echo $row_rs_consulta['Ite_Lar']; ?>" size="25" maxlength="30" />
	&nbsp;&nbsp;
	<button type="button" name="button" id="button" class="btn btn-success fileinput-button" title="Seleccionar">
           <i class=" icon-check icon-white"></i>
           <span>Seleccionar</span>
    </button>     
    </td>
  </tr>
  </table>
 </FIELDSET> 
<FIELDSET>
<LEGEND>
<label class="Titulos2">Del producto</label>
</LEGEND> 
  <table width="100%" border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td width="13%" class="Etiqueta1">Detalle del Producto: </td>
    <td width="87%" class="LetraNegra"><input name="Pro_Obs" type="text" id="Pro_Obs" style="width:200" value="" size="60" /></td>
  </tr>
  <tr>
    <td class="Etiqueta1"><span class="Asterisco">* </span>Marca: </td>
    <td class="LetraNegra"><label></label>
      <table width="49%" border="0" cellpadding="0" cellspacing="0">
        <tr>
          <td width="37%"><div id="div_marca"><select name="Mar_Cod" id="Mar_Cod" onChange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_mar_val=1&Cat_Cod1='+document.getElementById('Cat_Cod1').value+'&Ite_Cod='+document.getElementById('Ite_Cod').value+'&Mar_Cod='+document.getElementById('Mar_Cod').value,'contenedormarca')">
            <option value="">Seleccione...</option>
            <?php  
			foreach($rs_marca as $row_rs_marca) {  			
	      ?>
            <option  value="<?Php echo $row_rs_marca['Mar_Cod']; ?>">
            <?php 
				echo $row_rs_marca['Mar_Des']; ?>
            </option>
            <?php } ?>
          </select><div id="contenedormarca"></div></div></td>
          <td width="63%">&nbsp;<button type="button" name="button" id="button" class="btn btn-success btn-mini" title="Actualizar marca" onclick="ajax_datos('<?Php echo $_SERVER['PHP_SELF']?>?ajax_marca=1','div_marca')">
           <i class=" icon-refresh icon-white"></i>
           <span></span>
    </button>              </td>
        </tr>
      </table>     </td>
  </tr>
  <tr>
    <td class="Etiqueta1"><span class="Asterisco">* </span>Adquisici&oacute;n: </td>
    <td class="LetraNegra"><label>
    <select name="Adq_Cod" id="Adq_Cod">
      <option value="">Seleccione...</option>
      <?php  
			foreach($rs_adq as $row_rs_adq){  		
	      ?>
      <option  value="<?Php echo $row_rs_adq['Adq_Cod']; ?>">
      <?php 				echo $row_rs_adq['Adq_Des'];
		  ?>
      </option>
      <?php } ?>
    </select>
    </label></td>
  </tr>
  <tr>
    <td class="Etiqueta1"><span class="Asterisco">* </span>Iva: </td>
    <td><select onchange="changeIva()" name="Iva_Cod" id="Iva_Cod">
      <option value="">Seleccione...</option>
      <?php  
			foreach($rs_iva as $row_rs_iva) {  			
	      ?>
      <option   value="<?Php echo $row_rs_iva['Iva_Cod']; ?>"><?php  echo $row_rs_iva['Iva_Por'].'%'; ?></option>
      <?php
		} ?>
    </select></td>
  </tr>
  <tr>
    <td class="Etiqueta1"><span class="Asterisco">*</span> C&oacute;digo de barra: </td>
    <td>
      <table width="559" border="0" cellpadding="0" cellspacing="0">
        <tr>
          <td width="123"><span class="LetraNegra">
            <input name="Pro_Bar" disabled="disabled" type="text" id="Pro_Bar" size="15" maxlength="15" value="" />
          </span></td>
          <td width="22"><span class="LetraNegra">
            <input name="Pro_Gen" type="checkbox" id="Pro_Gen" onClick="check_generar()"  value="1" checked>
          </span></td>
          <td width="414"><div class="LetraNegra" id='contenedorcheck'> Generar c&oacute;digo automaticamente</div></td>
        </tr>
      </table>      </td>
  </tr>  
  <tr>
    <td class="Etiqueta1"><span class="Asterisco">*</span> Ubicaci&oacute;n: </td>
    <td><select name="Ubi_Cod" id="Ubi_Cod">
      <option value="">Seleccione...</option>
      <?php  
			foreach($rs_ubicacion as $row_rs_ubicacion) {  			
	      ?>
      <option    value="<?Php echo $row_rs_ubicacion['Ubi_Cod']; ?>">
        <?php echo $row_rs_ubicacion['Ubi_Des']; ?>        </option>
      <?php }  ?>
    </select></td>
    </tr>
    <tr>
    <td class="Etiqueta1"><span class="Asterisco">*</span> Presentaci&oacute;n: </td>
    <td>
    <?
    	 $row_rs_present= $obBD_con1->getArrayConsulta(1207,'',$obBD_conexion);         	    
	?>
    <select name="Pre_Cod" id="Pre_Cod">
      <option value="">Seleccione...</option>
      <? foreach($row_rs_present as $row){?>
      <option value="<?Php echo $row['Pre_Cod']; ?>"><?php echo $row['Pre_Des']; ?></option>
      <?php }?>
    </select></td>
    </tr>
  <tr>
    <td class="Etiqueta1"><span class="Asterisco">*</span> Tipo de Medida: </td>
    <td><table width="337" border="0" cellpadding="0" cellspacing="0">
        <tr>
          <td width="100"><select name="Uni_Cod" id="Uni_Cod" onChange="if (this.value==1){ document.getElementById('Pro_Uni').readOnly=true; document.getElementById('Pro_Uni').value = '1';  }else{ document.getElementById('Pro_Uni').readOnly =false;
document.getElementById('Pro_Uni').value = ''; }">
            <!--<option value="">Seleccione...</option>-->
            <?php  
			foreach($rs_unidad as $row_rs_unidad){  ?>
            <option    value="<?Php echo $row_rs_unidad['Uni_Cod']; ?>"> <?php echo $row_rs_unidad['Uni_Des']; ?></option>
            <?php }  ?>
          </select></td>
          <td width="72" class="Etiqueta1"><span class="Asterisco">*</span> Medida:</td>
          <td width="165">
            <div class="LetraNegra" id="div_1">                       
            <input name="Pro_Uni" type="text" id="Pro_Uni"  onBlur="if (this.value>=1){ alert ('�Ingresar valores iguales a uno, � seleccione el elemento UNIDAD! ');this.focus(); } "   onKeyPress="return validar_decimal(event)" value="1"  size="8" maxlength="8" readonly="readonly"  border="0" /></div></td>
        </tr>
      </table></td>
  </tr>
  <tr>
    <td class="Etiqueta1"><span class="Asterisco">*</span> Precio por unidad: </td>
    <td><input onchange="updateUnitario(this.value)" onKeyPress="return validar_decimal(event)" border="0" name="Pre_Pvp" type="text" id="Pre_Pvp"  size="8" maxlength="8" style="text-align:right" />
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class="Etiqueta1">Precio Inc. IVA: </span>
            <input onchange="updateNeto(this.value)" id="PreNet"  type="text" size="10" style="text-align:right" disabled="disabled" />
            <span><input onKeyPress="return validar_decimal(event)" id="ChkNet" type="checkbox" onChange="changeUnitario()"  value="1" style="position:relative;top:3px;"/></span><span class="Etiqueta1"> Desglosar IVA </span>
            <script>
                function changeUnitario(){  
                    if($('#ChkNet').is(':checked')) {                           
                            $('#Pre_Pvp').attr('readonly','readonly');
                            $('#PreNet').removeAttr('disabled');
                        } else {  
                            $('#PreNet').attr('disabled','disabled');
                            $('#Pre_Pvp').removeAttr('readonly');
                        }      
                        $('#PreNet').val('');$('#Pre_Pvp').val('');
                }
                function changeIva(){  
                    if($('#ChkNet').is(':checked')) updateNeto($('#PreNet').val());
                    else updateUnitario($('#Pre_Pvp').val());
                }
                function updateNeto(value){
                    var Iva_Por=$("#Iva_Cod option:selected").text().replace("%", "");value='0'+value;
                    if(!isNaN(Iva_Por))
                        $('#Pre_Pvp').val(Math.round(10000*parseFloat(value)/(1+(parseFloat(Iva_Por)/100)))/10000);
                    else
                        alert('Seleccione el I.V.A.');
                }
                function updateUnitario(value){
                    var Iva_Por=$("#Iva_Cod option:selected").text().replace("%", "");value='0'+value;
                    if(!isNaN(Iva_Por))
                        $('#PreNet').val(Math.round(10000*(parseFloat(value)+parseFloat(value)*((parseFloat(Iva_Por)/100))))/10000);
                    else
                        alert('Seleccione el I.V.A.');
                }
            </script>
    </td>
    </td>
  </tr>
  <tr>
    <td class="Etiqueta1">Descuento:</td>
    <td><input name="Pro_Dsc" type="text" id="Pro_Dsc" style="text-align:right" onKeyPress="return validar_decimal(event)" value="0"  size="8" maxlength="8" border="0" /></td>
  </tr>
  <tr>
    <td class="Etiqueta1">&nbsp;</td>
    <td>&nbsp;</td>
    </tr>
</table>
<input name="hdd_save" type="hidden" id="hdd_save" value="insertar" />
</FIELDSET>
</FIELDSET>
<br />
<table width="139" border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td width="139" align="left">
    <button type="button" class="btn btn-primary start" title="Guardar" onclick="validar_requeridos(this.form, 'Cat_Cod1*Mar_Cod*Ite_Cod*Adq_Cod*Iva_Cod*Ubi_Cod*Uni_Cod*Pro_Uni*Pre_Pvp', 1)">
           <i class="icon-book icon-white"></i>
           <span>Guardar</span>
    </button> 
    </td>
  </tr>
</table>
<br />
<div id="bgtransparent" class="bgtransparent" style="display:none" onclick="closeModal()">
</div>
<div id="bgmodal"  class="bgmodal"  style="display:none">		
<table width="100%" border="0" cellpadding="0" cellspacing="0">
 <tr>
   <td>  
      <FIELDSET>
      <LEGEND>
      <label class="Titulos2">Buscar categoria:</label>
      </LEGEND>
      <?PHP mensaje_requerido(); ?>
      <table width="510" height="36" border="0" cellspacing="0">
        <tr>
          <td height="28" class="BarraBusqueda"><span class="Asterisco">* </span>Descripci�n:
            <input type="text" name="txt_busqueda"  id="txt_busqueda" size="40" maxlength="50" />
            &nbsp; <button type="button" class="btn btn-success btn-primary" title="Buscar" onClick="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_1=1&txt_b='+ document.getElementById('txt_busqueda').value+'&ajax_2='+1 ,'busqueda_item')">
              <i class="icon-search icon-white"></i>
              <span>Buscar</span>
            </button> </td>
          </tr>
      </table>
      </fieldset>      
       <div id="busqueda_item"></div><br>
	 </td>
  </tr>
</table> 
</div>
</form>
      </td>
	</tr>
</table>       
<script type="text/javascript" src="../VALIDACIONES/fac_par_producto.js"></script>
<script type="text/javascript" src="../../Librerias/textbox/main.js"></script>    
</div>
</body>
</html>
<?Php 
@$obBD_con1->liberar();
@$obBD_conexion->cerrar();
?>