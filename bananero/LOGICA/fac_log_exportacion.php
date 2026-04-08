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
class Class_Log_Datos_Exportacion extends MysqlDatosContab{
    function __construct() {
        //$this->setSentencias('sentencias_rol');
    }
    function formatDataExp($data){
        return array(
            'Vet_Cod'=>$data['Vet_Cod'],
            'Paf_Cod'=>empty($data['Paf_Cod'])?null:$data['Paf_Cod'],
            'Pas_Cod'=>$data['Pas_Cod'],
            'Reg_Cod'=>$data['Reg_Cod'],
            'Reg_Den'=>$data['Reg_Den'],
            'Ref_Cod'=>$data['Ref_Cod'],
            'Eve_Ren'=>empty($data['Eve_Ren'])?0:$data['Eve_Ren'],
            'Eve_Fob'=>$data['Eve_Fob'],
            'Eve_Fec'=>$data['Eve_Fec'],
            'Ein_Cod'=>empty($data['Ein_Cod'])?NULL:$data['Ein_Cod'],
            'Eve_Dot'=>empty($data['Eve_Dot'])?NULL:$data['Eve_Dot'],
            'Edi_Cod'=>empty($data['Edi_Cod'])?NULL:$data['Edi_Cod'],
            'Eve_Ano'=>empty($data['Eve_Ano'])?NULL:$data['Eve_Ano'],
            'Ere_Cod'=>empty($data['Ere_Cod'])?NULL:$data['Ere_Cod'],
            'Eve_Cor'=>empty($data['Eve_Cor'])?NULL:$data['Eve_Cor'],
            'Eve_Ver'=>empty($data['Eve_Ver'])?NULL:$data['Eve_Ver'],
            'Eve_Rel'=>$data['Eve_Rel'],
        );
    }
}