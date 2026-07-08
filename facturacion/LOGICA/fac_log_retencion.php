<?Php 
/**
 * Logica de las paginas de retención
 *
 * @author Lewis Chimarro
 * @version 1.0
 * Fecha de actualización:	2012-08-20
 *
 * @package tesoreria.LOGICA
 */
require_once ('../../auditoria/LOGICA/aud_log_auditoria.php');
require_once("fac_sql_retencion.php");

/**
 * Clase para conexion a la capa de acceso a datos
 * 
 * @author Lewis Chimarro 
 *
 * @package tesoreria.LOGICA
*/

class Class_Log_Conexion_Ret extends MysqlConexion{

}//Fin de clase Class_Log_Conexion

/**
 * Clase para acceder a los datos
 * @author Lewis Chimarro
 *
 */

class Class_Log_Datos_Ret extends MysqlDatos{
    /* Declaro la funcion de las sqls */
    function __construct() { $this->setSentencias('sentencias_ret'); }  

	/**
	 * graba en la base de datos auditoria
	 * @param string $Request_Uri pagina donde se estan modificando valores
	 * @param number $Ses_Usu_Cod codigo del usuario
	 * @param Class_Log_Conexion $obBD_conexion
	 * @return number codigo de error my sql si lo hubiese [0 = 'Sin errores']
	 */
	function grabarAuditoria($Request_Uri, $Ses_Usu_Cod, $obBD_conexion){
		if($this->Error == 0){
			$objAud = new Class_Log_Datos_Aud;
				
			$aux = explode('*', $objAud->grabarAuditoria($Request_Uri, $Ses_Usu_Cod, $this, $obBD_conexion));
	
			foreach ($aux as $row){
				$this->grabarv_registros($row,$obBD_conexion->conexion);
				if($this->Error > 0){
					return $this->Error;
				}
			}
			$objAud->GuardarCierreSesion($_SESSION['Ses_Ses_Cod'], date('Y-m-d H:i:s'), $Ses_Usu_Cod);
		}else{
			return $this->Error;
		}
	}
	
	/**
	 * Formato standar para reportes
	 * @param int $sucursal Código de la sucursal
	 * @param string $titulo Título del reporte
	 * @param string $subtitulo Subtitulo del reporte
	 */	
	function cabeceraReporteStandar($sucursal, $titulo, $subtitulo,$obBD)
	{ 
		/* Consulta de la cabecera del reporte */
		$row_institucion = $this->getRowConsulta(126, $sucursal, $obBD);
		/* Consulta la provicia y pais de la sucursal */
		$row_provincia = $this->getRowConsulta(3, $row_institucion['Ciu_Cod'], $obBD);	
			
	?>
		<table width="80%" border="0" cellpadding="0" cellspacing="0">
		  <tr align="center">
		    <td width="5%" rowspan="5" valign="top"><img src="<?php echo $row_institucion['Emp_Log']; ?>" width="83" height="67" /></td>
		    <td width="75%" class="TITULO_REPORTE_2"><?Php echo $row_institucion['Emp_Nom']; ?></td>
		  </tr>
		  <tr align="center">
		    <td valign="top" class="Texto_Reporte"><div align="center"><strong>R.U.C.:</strong> &nbsp;<?php echo $row_institucion['Emp_Ruc']; ?>&nbsp;		      <strong>TELEFONO:</strong>&nbsp;<?php echo $row_institucion['Suc_Te1']; ?></div></td>
	      </tr>
		  <tr align="center">
		    <td valign="top" class="Texto_Reporte"><div align="center"><strong>DIRECCION:</strong>&nbsp;<?php echo $row_institucion['Suc_Dir']; ?></div></td>
	      </tr>
		  <tr align="center">
		    <td valign="top" class="Texto_Reporte"><div align="center"><strong>E-MAIL:</strong> &nbsp;<?php echo $row_institucion['Suc_Cor']; ?></div></td>
	      </tr>
		  <tr align="center">
		    <td align="center" valign="top" class="Texto_Reporte"><div align="center"><?Php 
			if (count($row_provincia) > 0)
			{
				$provincia = " - ".$row_provincia['Pro_Nom'].' - '.$row_provincia['Pas_Nom'];
			}
			else
			{
				$provincia = "";					
			}
			echo $row_institucion['Ciu_Des'].$provincia;?></div></td>
  		  </tr>
		  <tr align="center">
		    <td colspan="2" valign="top"><hr /></td>
  		  </tr>
		  <tr align="center">
		    <td colspan="2" valign="top" class="TITULO_REPORTE"><?php echo $titulo; ?></td>
  		  </tr>
		  <tr align="center">
		    <td colspan="2" valign="top" class="TITULO_REPORTE"><?php echo $subtitulo; ?></td>
	      </tr>
	    </table>
<?php
	} 

	/**
	 * Formato standar para reportes
	 * @param int $sucursal Código de la sucursal
	 * @param string $usuario Código del usuario 
	 */	
	function pieReporteStandar($sucursal, $usuario, $obBD)
	{ 
		/* Consulta de la cabecera del reporte */
		$row_institucion = $this->getRowConsulta(126, $sucursal, $obBD);	
		/* Consulta los datos del usuario */
		$row_usuario = $this->getRowConsulta(4, $usuario, $obBD);
		
		$fecha=explode("-",date("Y-m-d"));	
   	    $fechaHoy =	$row_institucion['Ciu_Des'].", ".$fecha[2]." de ".mes($fecha[1],1)." ".$fecha[0].", ".date("H:m:s") ;	
			
	?>
		<table width="80%" border="0" cellpadding="0" cellspacing="0">
   		  <tr align="center">
		    <td colspan="2" valign="top"><hr /></td>
  		  </tr>
		  <tr>
		    <td align="center" width="50%" valign="top" class="Texto_Reporte"><div align="center"><strong>Fecha de Impresi&oacute;n:</strong> &nbsp;<?php echo $fechaHoy; ?>&nbsp;</div></td>
		    <td align="center" width="50%" valign="top" class="Texto_Reporte"><div align="center"><strong>Usuario:</strong>&nbsp;<?php echo $row_usuario['Prs_Ape'].' '.$row_usuario['Prs_Nom'] ; ?></div></td>
	      </tr>
	    </table>
<?php
	}	
		

	/** 
	* Funcion que devuelve un arreglo de los reportes del proceso 
	*/
	function reportes($pagina, $empresa, $obBD_conexion)
	{
		$pag = explode("/", $pagina);
                $row_rs_proceso= $this->getRowConsultaSql("SELECT Pcs_Cod FROM procesos WHERE Pcs_Nom LIKE '".$pag[count($pag)-1]."' ORDER BY Pcs_Nom DESC LIMIT 1;", $obBD_conexion);
        
		$row_rs_reporte = $this->getArrayConsulta(13, $row_rs_proceso['Pcs_Cod'].'*'.$empresa, $obBD_conexion);
		
		$i=0;
                $reporte=array();
		foreach ($row_rs_reporte as $row)
		{
			$i++;
			$reporte[$i] = $row['Rut_Des'].$row['Pcs_Nom'];		
		}		
		return $reporte;
	}	

	/**
	* Elimina cualquier tipo de letra en un codigo de retencion 
	*/	
	function codAir($codigo)
	{
		$air = substr($codigo,0,3);
		return $air;
	}
			
}