<?php 

require_once ('../../auditoria/LOGICA/aud_log_auditoria.php');
require_once("tes_sql_104.php");


class Class_Log_Conexion_Anx extends MysqlConexion{

}


class Class_Log_Datos_Anx extends MysqlDatos{
	function __construct() {
        $this->setSentencias('sentencias_anx');
    }
	/**
	 * Formato standar para reportes
	 * @param int $sucursal C�digo de la sucursal
	 * @param string $titulo T�tulo del reporte
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
			    <td width="75%" class="TITULO_REPORTE_2"><?php echo $row_institucion['Emp_Nom']; ?></td>
			  </tr>
			  <tr align="center">
			    <td valign="top" class="Texto_Reporte"><div align="center"><strong>R.U.C.:</strong> &nbsp;<?php echo $row_institucion['Emp_Ruc']; ?>&nbsp;		      <strong>TELEFONO:</strong>&nbsp;<?php echo $row_institucion['Suc_Te1']; ?></div></td>
		      </tr>
			  <tr align="center">
			    <td valign="top" class="Texto_Reporte"><div align="center"><strong>DIRECCIÓN:</strong>&nbsp;<?php echo $row_institucion['Suc_Dir']; ?></div></td>
		      </tr>
			  <tr align="center">
			    <td valign="top" class="Texto_Reporte"><div align="center"><strong>E-MAIL:</strong> &nbsp;<?php echo $row_institucion['Suc_Cor']; ?></div></td>
		      </tr>
			  <tr align="center">
			    <td align="center" valign="top" class="Texto_Reporte"><div align="center">
			<?php
				if (count($row_provincia) > 0)
				{
					$provincia = " - ".$row_provincia['Pro_Nom'].' - '.$row_provincia['Pas_Nom'];
				}
				else
				{
					$provincia = "";					
				}
				echo $row_institucion['Ciu_Des'].$provincia;
			?>
				</div></td>
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
		 * @param int $sucursal C�digo de la sucursal
		 * @param string $usuario C�digo del usuario 
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
		/**
		* Agrega la serie a numeros de factura que solo contienen el secuencia 
		*/
		function establecimiento($codigo)
		{
			if ($codigo != "")
			{
				$estab = explode('-',$codigo);
				
				if (count($estab) == 1)	
				{
					unset($estab);
					$estab[0] = "001";
					$estab[1] = "001";
					$estab[2] = $codigo;				
				}
			}
			return $estab;
		}
              
        /* Elimina cualquier tipo de letra en un codigo de retencion */	
		function cod_air($codigo)
		{
			$air = substr($codigo,0,3);
			return $air;
		}
                                
}
?>