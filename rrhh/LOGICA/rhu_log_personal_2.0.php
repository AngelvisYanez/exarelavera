<?php
require_once ('../../auditoria/LOGICA/aud_log_auditoria.php');
require_once("rhu_sql_personal_2.0.php");

/******************************************************/
/******************************************************/
/*   Clase para conexion a la capa de acceso a datos  */
/******************************************************/
/******************************************************/

class Class_Log_Conexion_rrhh extends MysqlConexion{

}//Fin de clase Class_Log_Conexion

/******************************************************/
/******************************************************/

/******************************************************/
/******************************************************/
/*  Clase para los datos a la capa de acceso a datos  */
/******************************************************/
/******************************************************/

class Class_Log_Datos_rrhh extends MysqlDatos{
    function __construct() {
        $this->setSentencias('sentencias_rrhh');
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
                    <td colspan="<?php echo $colspan;?>" class="TITULO_REPORTE_2"><b><?Php echo $row_institucion['Emp_Nom']; ?></b></td>
                </tr>
                <tr align="center">
                    <td valign="top" colspan="<?php echo $colspan;?>" class="Texto_Reporte"><div align="center"><strong>R.U.C.:</strong> &nbsp;<?php echo $row_institucion['Emp_Ruc']; ?>&nbsp;		      <strong>TELEFONO:</strong>&nbsp;<?php echo $row_institucion['Suc_Te1']; ?></div></td>
                </tr>
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
}//Fin de clase Class_Log_Conexion
