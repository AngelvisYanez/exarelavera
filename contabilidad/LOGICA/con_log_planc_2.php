<?Php 
/**
 * Logica de las paginas que tienen que ver con clientes
 *
 * @author Lewis Chimarro
 * @version 1.0
 * Fecha de actualizaci�n:	2012-04-19
 *
 * @package contabilidad.LOGICA
 */
require_once ('../../auditoria/LOGICA/aud_log_auditoria.php');
require_once("con_sql_planc_2.php");

/**
 * Clase para conexion a la capa de acceso a datos
 * 
 * @author Lewis Chimarro 
 *
 * @package contabilidad.LOGICA
*/

class Class_Log_Conexion_Con extends MysqlConexion{

}//Fin de clase Class_Log_Conexion

/**
 * Clase para acceder a los datos
 * @author Lewis Chimarro
 *
 */

class Class_Log_Datos_Con extends MysqlDatosContab{
    function __construct() {
        $this->setSentencias('sentencias_con');
    } 
	/**
	 * Obtener las filas del plan de cuentas ya con el html
	 * @param number $cod codigo del plan de cuentas
	 * @param number $np codigo recursivo
	 * @param Class_Log_Conexion $obBD
	 * @return string
	 */
	function obtenerPlanCuentas($cod, $np, $obBD){
		/**
		 * Obtener Cuentas
		 */
		$row_nodosrep = $this->getArrayConsulta(315, $cod.'*'.$np, $obBD);
		$str = "";
		
		foreach($row_nodosrep as $row){
			$str .= '<tr class="Texto_normal_11">';
			$str .= '<td>'.$row['Pld_Cdc'].'</td>';
			$str .= '<td>'.str_repeat("&nbsp;", strlen($row['Pld_Cdc'])).$row['Pld_Des'].'</td>';
			$str .= '<td>'.$row['Pld_Deb'].'</td>';
			$str .= '<td>'.$row['Pld_Cre'].'</td>';
			$str .= '</tr>';
			
			$str .=  $this->obtenerPlanCuentas($cod, $row['Pld_Cod'], $obBD);
		}
		
		return $str;
	}

	/**
	 * Carga la estructura del plan de cuentas
	 * @param int $cod codigo del plan de cuentas
	 * @param int $np codigo de la recursidad de la cuenta
	 * @param strint $categoria
	 */
  function cargarNodos($cod, $np, $categoria, $obBD)
  {
		$j=$j+1;
		//$espacios=str_repeat("&nbsp;", strlen($cuenta));
		$row_nodosrep = $this->getArrayConsulta(315, $cod.'*'.$np, $obBD);
	if ($np == 0)
	{
	?>
<table width="100%" border="1" style="border-collapse:collapse" cellpadding="0" cellspacing="0">
		<tr class="Texto_Listados">
			<td align="center" bgcolor="#CCCCCC"><strong>C&oacute;digo</strong></td>
		  <td width="70%" align="center" bgcolor="#CCCCCC"><strong>Cuentas</strong></td>
		  <td align="center" bgcolor="#CCCCCC"><strong>Clasificaci&oacute;n</strong></td>
			<td align="center" bgcolor="#CCCCCC"><strong>Tipo</strong></td>														
		</tr>	
	<?Php
	}		
		if (count($row_nodosrep) > 0)
			{
				foreach($row_nodosrep as $row)
				{
					if ($np == 0){ $categoria = $row['Pld_Des']; } 
				?>
				<tr>
		    <?Php
					/* Control para agregar cero a las cuentas de detalle */
					if ($row['Pld_Tip']=='D')
					{
						$cuenta = $this->mascaraCuenta($row['Pld_Cdc']);
					}
					else
					{
						$cuenta = $row['Pld_Cdc'];						
					} ?>						
					<td><?Php echo $cuenta; ?>
					</td>
					<td><?Php $espacios=str_repeat("&nbsp;", strlen($cuenta));
							echo $espacios.$row['Pld_Des']; ?></td>
					<td align="center"><?Php echo $categoria; ?></td>
					<td align="center">
						<?php if ($row['Pld_Tip'] == 'D'){ echo "Detalle"; }else{ 
								if ($row['Pld_Tip'] == 'G'){ echo "GRUPO"; } }?>
					</td>
				<tr>
					<?php									
					$this->cargarNodos($cod,$row['Pld_Cod'], $categoria, $obBD);
				} //Fin del foreach($row_nodosrep as $row)
			}//Fin del if ($total_rs_nodosrep > 0)
	if ($np == 0)
	{		
	?>
	</table>
	<?Php				
	}//Fin del if ($np == 0)
  }//function cargar_nodos($cod,$np)

	/**
	 * Agrega un cero al No de cuenta, cuando se trata de detalle
	 * @param int $cuenta Codigo de la cuenta contable
	 */
	function mascaraCuenta($cuenta)
	{
		$array_cuenta=explode('.',$cuenta);
		$indice=count($array_cuenta)-1;
		/**
		 * Pregunta para saber si la cantidad del numero de la ultima cuenta
		 * es menor a 10 
		 */
		if ($array_cuenta[$indice]<10 and count($array_cuenta)>1)
		{
			$concatenado = '0'.$array_cuenta[$indice];
			$retorno = $array_cuenta[0];
			for ($i=1;$i<=count($array_cuenta)-2;$i++)
			{
				$retorno = $retorno.'.'.$array_cuenta[$i];
			}
				return $retorno.'.'.$concatenado;
		}
		else
		{
				return $cuenta;
		}
	}

	/**
	 * Formato standar para reportes
	 * @param int $sucursal C�digo de la sucursal
	 * @param string $titulo T�tulo del reporte
	 * @param string $subtitulo Subtitulo del reporte
	 */	
	function cabeceraReporteStandar($sucursal, $titulo, $subtitulo,$obBD)
	{ 
		/**
		 * Consulta de la cabecera del reporte 
		 */
		$row_institucion = $this->getRowConsulta(126, $sucursal, $obBD);
		/**
		 * Consulta la provicia y pais de la sucursal 
		 */
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
			if (count($row_provincia) > 0){
				$provincia = " - ".$row_provincia['Pro_Nom'].' - '.$row_provincia['Pas_Nom'];
			}else{
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
	 * @param int $sucursal C�digo de la sucursal
	 * @param string $usuario C�digo del usuario 
	 */	
	function pieReporteStandar($sucursal, $usuario, $obBD)
	{ 
		/**
		 * Consulta de la cabecera del reporte 
		 */
		$row_institucion = $this->getRowConsulta(126, $sucursal, $obBD);	
		
		/**
		 * Consulta los datos del usuario 
		 */
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
		    <td align="center" width="50%" valign="top" class="Texto_Reporte"><strong>Usuario:</strong>&nbsp;<?php echo $row_usuario['Prs_Ape'].' '.$row_usuario['Prs_Nom'] ; ?></td>
	      </tr>
	    </table>
<?php
	} 
	
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
	
}