<?php 
/* 
 *Descripción: Consulta de instructivos
 *Desarrollador: Lewis Chimarro
 *Fecha de actualización:	2013/0/26
*/

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');	
/**
* Muestra el archivo pdf
*/
if (isset($codigo))
{
	$mi_pdf = 'docs/'.$codigo; 
	header('Content-type: application/pdf');
	header('Content-Disposition: inline; filename="'.$mi_pdf.'"');
	readfile($mi_pdf);	
}
?> 
<HTML>
	<HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
		<?Php require_once("../../mascaras/model1/estilos/estilos.php"); ?>
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>        
        <script type="text/javascript">
          $(function() {
                $('#set1 *').tooltip({showURL: false});
          });              			
		</script>        
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	</HEAD>
<BODY>
<div id="set1">
<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr class="BarraTitulo">
	  <td height="10">&raquo; Instructivo de Activos Fijos</td>
  </tr>
	<tr>
	  	<td valign="top">
        <br>
        <?Php
		if (!isset($codigo))
		{
		?>      
	<FIELDSET>
    <LEGEND>
    <label class="Titulos2">Listado de instructivos</label>
    </LEGEND>          
        <table class="fixedHeader01" width="100%"  cellpadding="0" cellspacing="0">
	  	  <thead>
	  	    <tr>
	  	      <th width="11%">No.</th>
	  	      <th width="67%">Archivo</th>
	  	      <th width="4%">&nbsp;</th>
  	        </tr>
  	      </thead>
	  	  <tbody>
	  	    <?Php
			$row_file = array(0=>array("Fil_Des" => "Control de Administración de Bienes", "Fil_Ide" =>"control.administracion.bienes.1.0.pdf"),
							  1=>array("Fil_Des" => "Politicas de Activos Fijos Ecu Machala", "Fil_Ide" =>"politicas.activos.fijos.ecu.machala.1.0.pdf"), 
							  2=>array("Fil_Des" => "Reglamento General de Bienes Sector Público Sustitutivo", "Fil_Ide" =>"reglamento.general.bienes.sector.publico.sustitutivo.1.0.pdf")
							);
			 
		foreach($row_file as $row )  
		 { 
		 	$i++;
	  ?>
	  	    <tr >
	  	      <td align="center"><font color="<? echo $rojo;?>"><?php echo $i; ?></font></td>
	  	      <td><?Php echo $row['Fil_Des'] ?></td>
	  	      <form action="<? echo $_SERVER['PHP_SELF'];?>" method="post" name= "frml" id="form3">
	  	        <td align="center" width="4%"><button type="image" name="imageField" class='btn btn-info btn-mini' width="22" height="22" title="Seleccionar"> <i class='icon-search icon-white'></i> </button>
	  	          <input type="hidden" name="codigo" id="codigo" value="<?Php echo $row['Fil_Ide']; ?>"/></td>
  	          </form>
  	        </tr>
	  	    <?Php 
			}
	  	    ?>
  	      </tbody>
	  	  </table>	
	<?Php
	/**
	 *  Muestra la barra de estados con la cantidad de registros encontrados 
	 */
		echo barra_estado($i);
	?>          
          </FIELDSET>	
    	<?Php
		}//Fin del if (isset($codigo))
		?>
		</td>
	</tr>
</table>
</div>    
</BODY>
</HTML>