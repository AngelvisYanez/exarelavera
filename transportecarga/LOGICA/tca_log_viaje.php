<?
require_once ('../../auditoria/LOGICA/aud_log_auditoria.php');
require_once("tca_sql_viaje.php");

/******************************************************/
/******************************************************/
/*   Clase para conexion a la capa de acceso a datos  */
/******************************************************/
/******************************************************/

class Class_Log_Conexion_Viaje extends MysqlConexion{

}//Fin de clase Class_Log_Conexion

/******************************************************/
/******************************************************/

/******************************************************/
/******************************************************/
/*  Clase para los datos a la capa de acceso a datos  */
/******************************************************/
/******************************************************/

class Class_Log_Datos_Viaje extends MysqlDatosContab{
	
    function __construct(){ 
        $this->setSentencias('sentencias_viaje'); 
    }
    function cabeceraReporteStandar($sucursal, $titulo, $subtitulo,$colspan,$obBD)
    {
        /* Consulta de la cabecera del reporte */
        $result1= $this->consulta("SELECT empresas.Emp_Nom, Emp_Ruc, ciudad.Ciu_Des, sucursal.Ciu_Cod, sucursal.Suc_Dir, sucursal.Suc_Te1, sucursal.Suc_Te2, sucursal.Suc_Fax, sucursal.Suc_Cor, sucursal.Suc_Web, sucursal.Suc_Des, empresas.Emp_Log FROM empresas, sucursal, ciudad WHERE sucursal.Suc_Cod = $sucursal AND empresas.Emp_Cod = sucursal.Emp_Cod AND sucursal.Ciu_Cod = ciudad.Ciu_Cod", $obBD->conexion);
        $row_institucion =  $this->fetch_assoc($result1);		
        $this->free_result($result1);

        /* Consulta la provicia y pais de la sucursal */
        $result2= $this->consulta("SELECT provincia.Pro_Nom, pais.Pas_Nom FROM provincia INNER JOIN ciudad ON (provincia.Pro_Cod = ciudad.Pro_Cod) INNER JOIN regiones ON (provincia.Reg_Cod = regiones.Reg_Cod) INNER JOIN pais ON (regiones.Pas_Cod = pais.Pas_Cod) WHERE ciudad.Ciu_Cod = ".$row_institucion['Ciu_Cod'], $obBD->conexion);
        $row_provincia =  $this->fetch_assoc($result2);		
        $this->free_result($result2);		
        ?>
            <table width="100%" border="0" cellpadding="0" cellspacing="0">
                <tr align="center">
                    <td width="75%" colspan="<?php echo $colspan;?>" class="TITULO_REPORTE_2"><b><?Php echo $row_institucion['Emp_Nom']; ?></b></td>
                </tr>
                <tr align="center">
                    <td valign="top" colspan="<?php echo $colspan;?>" class="Texto_Reporte"><div align="center"><strong>R.U.C.:</strong> &nbsp;<?php echo $row_institucion['Emp_Ruc']; ?>&nbsp;		      <strong>TELEFONO:</strong>&nbsp;<?php echo $row_institucion['Suc_Te1']; ?></div></td>
                </tr>
<!--                    <tr align="center">
                    <td valign="top" colspan="<?php echo $colspan;?>" class="Texto_Reporte"><div align="center"><strong>DIRECCION:</strong>&nbsp;<?php echo $row_institucion['Suc_Dir']; ?></div></td>
                </tr>
                <tr align="center">
                    <td valign="top" colspan="<?php echo $colspan;?>" class="Texto_Reporte"><div align="center"><strong>E-MAIL:</strong> &nbsp;<?php echo $row_institucion['Suc_Cor']; ?></div></td>
                </tr>-->
                <tr align="center">
                    <td align="center" valign="top" colspan="<?php echo $colspan;?>" class="Texto_Reporte"><div align="center"><?Php 
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
                    <td colspan="<?php echo $colspan;?>" valign="top" class="TITULO_REPORTE"><b><? echo $titulo; ?></b></td>
                </tr>
                <tr align="center">
                    <td colspan="<?php echo $colspan;?>" valign="top" class="TITULO_REPORTE"><? echo $subtitulo; ?></td>
                </tr>
            </table>
    <?php
    } 
    
    /**
	* Formato standar para reportes
	* @param int $sucursal Código de la sucursal
	* @param string $titulo Título del reporte
	* @param string $subtitulo Subtitulo del reporte
	*/
	function cabeceraReporteStandar1($sucursal, $titulo, $subtitulo,$obBD)
	{
		/* Consulta de la cabecera del reporte */
                $result1= $this->consulta("SELECT empresas.Emp_Nom, Emp_Ruc, ciudad.Ciu_Des, sucursal.Ciu_Cod, sucursal.Suc_Dir, sucursal.Suc_Te1, sucursal.Suc_Te2, sucursal.Suc_Fax, sucursal.Suc_Cor, sucursal.Suc_Web, sucursal.Suc_Des, empresas.Emp_Log FROM empresas, sucursal, ciudad WHERE sucursal.Suc_Cod = $sucursal AND empresas.Emp_Cod = sucursal.Emp_Cod AND sucursal.Ciu_Cod = ciudad.Ciu_Cod", $obBD->conexion);
                $row_institucion =  $this->fetch_assoc($result1);		
		$this->free_result($result1);
		
		/* Consulta la provicia y pais de la sucursal */
                $result2= $this->consulta("SELECT provincia.Pro_Nom, pais.Pas_Nom FROM provincia INNER JOIN ciudad ON (provincia.Pro_Cod = ciudad.Pro_Cod) INNER JOIN regiones ON (provincia.Reg_Cod = regiones.Reg_Cod) INNER JOIN pais ON (regiones.Pas_Cod = pais.Pas_Cod) WHERE ciudad.Ciu_Cod = ".$row_institucion['Ciu_Cod'], $obBD->conexion);
                $row_provincia =  $this->fetch_assoc($result2);		
		$this->free_result($result2);		
			
		?>
				<table width="100%" border="0" cellpadding="0" cellspacing="0">
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
			 * @param int $sucursal Código de la sucursal
			 * @param string $usuario Código del usuario 
			 */	
			function pieReporteStandar($sucursal, $usuario, $obBD)
			{ 
				/* Consulta de la cabecera del reporte */
				$result1= $this->consulta("SELECT empresas.Emp_Nom, Emp_Ruc, ciudad.Ciu_Des, sucursal.Ciu_Cod, sucursal.Suc_Dir, sucursal.Suc_Te1, sucursal.Suc_Te2, sucursal.Suc_Fax, sucursal.Suc_Cor, sucursal.Suc_Web, sucursal.Suc_Des, empresas.Emp_Log FROM empresas, sucursal, ciudad WHERE sucursal.Suc_Cod = $sucursal AND empresas.Emp_Cod = sucursal.Emp_Cod AND sucursal.Ciu_Cod = ciudad.Ciu_Cod", $obBD->conexion);
                                $row_institucion =  $this->fetch_assoc($result1);		
                                $this->free_result($result1);
                
				/* Consulta los datos del usuario */
                                $result2= $this->consulta("SELECT Prs_Ape, Prs_Nom FROM persona, usuarios WHERE persona.Prs_Cod = usuarios.Prs_Cod AND usuarios.Usu_Cod = ".$usuario, $obBD->conexion);
                                $row_usuario =  $this->fetch_assoc($result2);		
                                $this->free_result($result2);				
				
				$fecha=explode("-",date("Y-m-d"));	
		   	        $fechaHoy =	$row_institucion['Ciu_Des'].", ".$fecha[2]." de ".mes($fecha[1],1)." ".$fecha[0] ;	
					
			?>
				<table width="100%" border="0" cellpadding="0" cellspacing="0">
		   		  <tr align="center">
				    <td valign="top"><hr /></td>
		  		  </tr>
				  <tr align="center">
				    <td width="75%" valign="top" class="Texto_Reporte"><div align="center"><strong>FECHA IMPRESI&Oacute;N:</strong> &nbsp;<?php echo $fechaHoy; ?>&nbsp;		      <strong>USUARIO:</strong>&nbsp;<?php echo $row_usuario['Prs_Ape'].' '.$row_usuario['Prs_Nom'] ; ?></div></td>
			      </tr>
			    </table>
		<?php
			}
}//Fin de clase Class_Log_Conexion


