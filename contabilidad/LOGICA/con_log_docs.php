<?Php 
/**
 * Logica de las paginas para comprobantes contables
 *
 * @author Erik Niebla
 * @version 1.0
 * Fecha de actualización:	2017-06-08
 */
require_once ('../../auditoria/LOGICA/aud_log_auditoria.php');
require_once("con_sql_docs.php");

/* Clase para conexion a la capa de acceso a datos */
class Class_Log_Conexion_Doc extends MysqlConexion{ }

/* Clase para acceder a los datos */
class Class_Log_Datos_Doc extends MysqlDatosContab{
    function __construct() {
        $this->setSentencias('sentencias_doc');
    }
    function reportes($pagina, $empresa, $obBD_conexion)
    {
        $pag=explode("/",$pagina);
        $Pcs_Nom=str_replace("_mod_", "_alt_", $pag[count($pag)-1]);
        $row_rs_proceso= $this->getRowConsultaSql("SELECT Pcs_Cod FROM procesos WHERE Pcs_Nom LIKE '$Pcs_Nom' ORDER BY Pcs_Nom DESC LIMIT 1;", $obBD_conexion);

        $row_rs_reporte= $this->getArrayConsultaSql("SELECT reportes.Rep_Cod, procesos.Pcs_Nom, reportes.Rep_Ord, rutas.Rut_Des FROM procesos
                                        INNER JOIN reportes ON (procesos.Pcs_Cod = reportes.Rep_Req)
                                        INNER JOIN rutas ON (procesos.Rut_Cod = rutas.Rut_Cod) 
                                        WHERE reportes.Pcs_Cod = $row_rs_proceso[Pcs_Cod] AND reportes.Emp_Cod = $empresa ORDER BY reportes.Rep_Ord", $obBD_conexion);

            $i=0;$reporte=array();
            foreach ($row_rs_reporte as $row)
            {
                $reporte[$row['Rep_Ord']] = $row['Rut_Des'].$row['Pcs_Nom'];
            }
            return $reporte;
    }
}
