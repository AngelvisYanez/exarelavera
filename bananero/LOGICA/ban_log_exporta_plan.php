<?Php
/**
 * Logica de las paginas para roles
 *
 * @author Erik Niebla
 * @version 1.0
 * Fecha de actualización:	2018-05-18
 */
require_once ('../../auditoria/LOGICA/aud_log_auditoria.php');

/* Clase para acceder a los datos */
class Class_Log_Datos_ExportaPlanif extends MysqlDatosContab{
    function __construct() {
        //$this->setSentencias('sentencias_rol');
    }
    function formatUpdate($data){
        return array(
            'Pln_Cod'=>isset($data['Pln_Cod'])?$data['Pln_Cod']:'',
            'Cli_Cod'=>$data['Cli_Cod'],
            'Exd_Cod'=>$data['Exd_Cod'],
            'Bam_Cod'=>$data['Bam_Cod'],
            'Pln_Fec'=>isset($data['Pln_Fec'])&&!empty($data['Pln_Fec'])?$data['Pln_Fec']:null,
            'Pln_Ano'=>$data['Pln_Ano'],
            'Pln_Can'=>$data['Pln_Can'],
            'Pln_Sem'=>$data['Pln_Sem']
        );
    }
}