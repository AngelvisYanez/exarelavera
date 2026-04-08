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
class Class_Log_Datos_Tarja extends MysqlDatosContab{
    function __construct() {
        //$this->setSentencias('sentencias_rol');
    }
    function formatTarjaInsert($data){
        return array(
            'Prh_Cod'=>$data['Prh_Cod'],
            'Bam_Cod'=>$data['Bam_Cod'],
            'Lib_Cod'=>(!isset($data['Lib_Cod'])||empty($data['Bam_Cod']))?NULL:$data['Lib_Cod'],
            'Prt_Num'=>$data['Prt_Num'],
            'Prt_Ano'=>$data['Prt_Ano'],
            'Prt_Sem'=>$data['Prt_Sem'],
            'Prt_Grp'=>$data['Prt_Grp'],
            'Prt_Fec'=>$data['Prt_Fec'],
            'Prt_Hoe'=>(empty($data['Prt_Hoe']))?null:$data['Prt_Hoe'],
            'Prt_Hos'=>(empty($data['Prt_Hos']))?null:$data['Prt_Hos'],
            'Prt_Nqc'=>$data['Prt_Nqc'],

            'Prt_Por'=>$data['Prt_Por'],
            'Prt_Obs'=>empty($data['Prt_Obs'])||$data['Prt_Obs']==null?' ':$data['Prt_Obs'],
            'Prt_Cam'=>$data['Prt_Cam'],
            'Prt_Cad'=>$data['Prt_Cad'],
            'Prt_Car'=>$data['Prt_Car'],
            'Prt_Cah'=>$data['Prt_Cah'],
            'Prt_Caf'=>$data['Prt_Caf'],
            'Prt_Caj'=>$data['Prt_Caj'],
            'Exc_Cod'=>(!isset($data['Exc_Cod'])||empty($data['Exc_Cod']))?NULL:$data['Exc_Cod'],
            'Nco_Cod'=>(!isset($data['Nco_Cod'])||empty($data['Nco_Cod']))?NULL:$data['Nco_Cod'],

            'Prt_Eva'=>$data['Prt_Eva'],
            'Prt_Mag'=>$data['Prt_Mag'],
            'Prt_Tip'=>$data['Prt_Tip'],
        );
    }
    function formatTarjaUpdate($data){
        return array(
            'Prt_Cod'=>$data['Prt_Cod'],
            'Prh_Cod'=>$data['Prh_Cod'],
            'Bam_Cod'=>$data['Bam_Cod'],
            'Lib_Cod'=>(!isset($data['Lib_Cod'])||empty($data['Bam_Cod']))?NULL:$data['Lib_Cod'],
            'Prt_Num'=>$data['Prt_Num'],
            'Prt_Ano'=>$data['Prt_Ano'],
            'Prt_Sem'=>$data['Prt_Sem'],
            'Prt_Grp'=>$data['Prt_Grp'],
            'Prt_Fec'=>$data['Prt_Fec'],
            'Prt_Hoe'=>(empty($data['Prt_Hoe']))?null:$data['Prt_Hoe'],
            'Prt_Hos'=>(empty($data['Prt_Hos']))?null:$data['Prt_Hos'],
            'Prt_Nqc'=>$data['Prt_Nqc'],

            'Prt_Por'=>$data['Prt_Por'],
            'Prt_Obs'=>empty($data['Prt_Obs'])||$data['Prt_Obs']==null?' ':$data['Prt_Obs'],
            'Prt_Cam'=>$data['Prt_Cam'],
            'Prt_Cad'=>$data['Prt_Cad'],
            'Prt_Car'=>$data['Prt_Car'],
            'Prt_Cah'=>$data['Prt_Cah'],
            'Prt_Caf'=>$data['Prt_Caf'],
            'Prt_Caj'=>$data['Prt_Caj'],
            'Exc_Cod'=>(!isset($data['Exc_Cod'])||empty($data['Exc_Cod']))?NULL:$data['Exc_Cod'],
            'Nco_Cod'=>(!isset($data['Nco_Cod'])||empty($data['Nco_Cod']))?NULL:$data['Nco_Cod'],

            'Prt_Eva'=>$data['Prt_Eva'],
            'Prt_Mag'=>$data['Prt_Mag'],
            'Prt_Tip'=>$data['Prt_Tip'],

        );
    }
    function formatTarjaDet($get,$Prt_Cod){
        $get['Prt_Cod']=$Prt_Cod;
        $get['Ptd_Can']=(!isset($get['Ptd_Can'])||empty($get['Ptd_Can'])||$get['Ptd_Can']==null)?'0':$get['Ptd_Can'];
        return $get;
    }
    function getTiposCaja(){
        return array(
            array('value'=>'R/T', 'label'=>'Racimo Tronchado'),
            array('value'=>'S/P', 'label'=>'Separacion'),
            array('value'=>'C/D', 'label'=>'Corte Directo'),
            array('value'=>'DED', 'label'=>'Dedos'),
            array('value'=>'REEM','label'=>'Reempaque')
        );
    }
}