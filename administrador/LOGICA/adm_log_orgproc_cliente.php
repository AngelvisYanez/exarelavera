<?Php 
/**
 * Logica de las paginas que tienen que ver con usuarios
 *
 * @author car.87cod :)
 * @version 2.0
 * Fecha de actualización:	2012-04-18
 *
 * @package administrador.LOGICA
 */
require_once ('../../auditoria/LOGICA/aud_log_auditoria.php');
require_once("adm_sql_orgproc_cliente.php");

/**
 * Clase para conexion a la capa de acceso a datos
 *
 * @author car.87cod :)
 *
 * @package administrador.LOGICA
 */
class Class_Log_Conexion_Admo extends MysqlConexion{ }

/**
 * Clase para acceder a los datos
 * @author car.87cod :)
 *
 * @package administrador.LOGICA
 */
class Class_Log_Datos_Admo extends MysqlDatos{
    function __construct() {
        $this->setSentencias('sentencias_admo');
    } 
	
	/**
	 * Buscar una persona registrada en las tablas
	 * [estudiante - cliente - proveedor - personal]
	 * 
	 * @param number $Ses_Emp_Cod codigo de la empresa
	 * @param string $txt_busqueda lo que se va ha buscar
	 * @param string $op_opciones la opcion que escojio
	 * @param Class_Log_Datos_Adm $obBD_conexion objeto de conexcion
	 * @return array resultado de la busqueda
	 */
	function searchPersona($Ses_Emp_Cod,$txt_busqueda,$op_opciones,$obBD_conexion)
	{
		/**
		 * Arreglo de personas encontradas
		 * @var array
		 */
		$Arr_Persona = null;
		
		
		if($op_opciones == "d")
		{
			/**
			 * para recorrer las sql
			 * @var int
			 */
			$id = 0;
			do
			{
				$id++;
				$Arr_Persona = $this->getArrayConsulta($id,$Ses_Emp_Cod.'*'.$txt_busqueda, $obBD_conexion);
			}
			while((count($Arr_Persona) == 0) && ($id != 4));
		}
		else
		{
			$id = 4;
			do
			{
				$id++;
				$Arr_Persona = $this->getArrayConsulta($id,$Ses_Emp_Cod.'*'.$txt_busqueda, $obBD_conexion);
			}
			while((count($Arr_Persona) == 0) && ($id != 8));
		}
		
		$this->id_tabla = $id;
		
		return $Arr_Persona;
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
		$row_institucion = $this->getRowConsulta(22, $sucursal, $obBD);
		/* Consulta la provicia y pais de la sucursal */
		$row_provincia = $this->getRowConsulta(21, $row_institucion['Ciu_Cod'], $obBD);
			
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
			$row_institucion = $this->getRowConsulta(22, $sucursal, $obBD);	
			/* Consulta los datos del usuario */
			$row_usuario = $this->getRowConsulta(23, $usuario, $obBD);
			
			$fecha=explode("-",date("Y-m-d"));	
	   	    $fechaHoy =	$row_institucion['Ciu_Des'].", ".$fecha[2]." de ".mes($fecha[1],1)." ".$fecha[0] ;	
				
		?>
			<table width="80%" border="0" cellpadding="0" cellspacing="0">
	   		  <tr align="center">
			    <td valign="top"><hr /></td>
	  		  </tr>
			  <tr align="center">
			    <td width="75%" valign="top" class="Texto_Reporte"><div align="center"><strong>FECHA IMPRESI&Oacute;N:</strong> &nbsp;<?php echo $fechaHoy; ?>&nbsp;		      <strong>USUARIO:</strong>&nbsp;<?php echo $row_usuario['Prs_Ape'].' '.$row_usuario['Prs_Nom'] ; ?></div></td>
		      </tr>
		    </table>
	<?php
		} 
}