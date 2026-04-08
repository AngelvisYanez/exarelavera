<?Php 
/**
 * Logica de las paginas para el control de kardex
 *
 * @author Lewis Chimarro
 * @version 1.0
 * Fecha de actualizaci�n:	2013-01-08

 *
 * @package tesoreria.LOGICA
 */

require_once ('../../auditoria/LOGICA/aud_log_auditoria.php');
require_once("tes_sql_kardex.php");


/**
 * Clase para conexion a la capa de acceso a datos
 * 
 * @author Lewis Chimarro
 *
 * @package tesoreria.LOGICA
*/

class Class_Log_Conexion_Kar extends MysqlConexion{

}//Fin de clase Class_Log_Conexion

/**
 * Clase para acceder a los datos
 * @author Lewis Chimarro
 *
 */

class Class_Log_Datos_Kar extends MysqlDatos{
	function __construct() {
        $this->setSentencias('sentencias_kar');
    }

    function getPromedio($pro_cod, $Ses_Suc_Cod, $obBD)
	{
		$kardex = $this->getArrayConsulta(104888, $pro_cod, $obBD);
		$promedio = array();

		if(count($kardex)>0)
		{                   
            $x=COUNT($kardex);
            for($i=1;$i<$x;$i++)
            {
            	if($i == 1){
            		$kardex[$i-1]['Stock']= $kardex[$i-1]['Kar_Can']*1 - $kardex[$i-1]['Kar_Sal'];
	                $kardex[$i-1]['Saldo']= ($kardex[$i-1]['Kar_Ims']*1) - ($kardex[$i-1]['Kar_Ime']*1);
	                $kardex[$i-1]['Promedio']=$kardex[$i-1]['Saldo']/$kardex[$i-1]['Stock'];
            	}

                if($kardex[$i]['Kar_Sal']*1!=0) //Realiza venta
                { 
                  if($kardex[$i-1]['Promedio'] != null)
                  {
                    $kardex[$i]['Kar_Pre']=$kardex[$i-1]['Promedio'];
                    $kardex[$i]['Kar_Ime']= floatval($kardex[$i]['Kar_Pre'])*floatval($kardex[$i]['Kar_Sal']);
                  }
                  else
                  {
                    $kardex[$i]['Kar_Ime']= floatval($kardex[$i]['Kar_Pre'])*floatval($kardex[$i]['Kar_Sal']);
                  }
                }

                $kardex[$i]['Stock']=$kardex[($i-1)]['Stock']*1+$kardex[$i]['Kar_Can']*1-$kardex[$i]['Kar_Sal'];
                $kardex[$i]['Saldo']= ($kardex[($i-1)]['Saldo']*1) + ($kardex[$i]['Kar_Ims']*1) - ($kardex[$i]['Kar_Ime']*1);
                $kardex[$i]['Promedio']=($kardex[$i]['Stock']!=0?$kardex[$i]['Saldo']/$kardex[$i]['Stock']:$kardex[($i-1)]['Promedio']);
            }
            $promedio['Promedio'] = $kardex[$x-1]['Promedio'];
            $promedio['Saldo'] = $kardex[$x-1]['Saldo'];
            $promedio['Stock'] = $kardex[$x-1]['Stock'];
        }
        else
        {
            $promedio['Promedio']=0;$promedio['Saldo']=0;$promedio['Stock']=0;
        }

        /*$promedio['Promedio'] = round(floatval($promedio['Promedio']),5);*/
		$promedio['Promedio'] = number_format($promedio['Promedio'], 6, '.', '');
        $promedio['Saldo'] = round(floatval($promedio['Saldo']), 2);
        $promedio['Stock'] = round(floatval($promedio['Stock']), 5) ;

        $this->operacionobBD(6001, array('Pro_Cod'=>$pro_cod, 'Ses_Suc_Cod'=>$Ses_Suc_Cod, 'Promedio'=>$promedio['Promedio'], 'Stock'=>$promedio['Stock']), $obBD);
        $this->operacionobBD(6002, array('Pro_Cod'=>$pro_cod, 'Promedio'=>$promedio['Promedio'], 'Stock'=>$promedio['Stock']), $obBD);

        return $promedio;
	}
	function cabeceraReporteStandar($sucursal, $titulo, $subtitulo,$obBD)
	{
		/* Consulta de la cabecera del reporte */
		$row_institucion = $this->getRowConsulta(5001, $sucursal, $obBD);
		/* Consulta la provicia y pais de la sucursal */
		$row_provincia = $this->getRowConsulta(5000, $row_institucion['Ciu_Cod'], $obBD);
			
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
				    <td colspan="2" valign="top" class="TITULO_REPORTE"><? echo $titulo; ?></td>
		  		  </tr>
				  <tr align="center">
				    <td colspan="2" valign="top" class="TITULO_REPORTE"><? echo $subtitulo; ?></td>
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
				$row_institucion = $this->getRowConsulta(5001, $sucursal, $obBD);	
				/* Consulta los datos del usuario */
				$row_usuario = $this->getRowConsulta(5003, $usuario, $obBD);
				
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
?>