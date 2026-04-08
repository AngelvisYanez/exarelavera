<?php
use \Exception;
require_once(dirname(__file__)."/../DATA/libs/AbstractModel.php");
class cheques_ext extends AbstractModel{
    protected $_name = 'cheques_ext';
    protected $_primary = array('Che_Cod');
    protected $_state = 'Che_Est';
    //protected $_fields = array(); // se declara las filas que va a tener el arreglo en caso de insert o update //opcional

    /* crea una sql basica global para la tabla */
    public function _selectBasic($cond=null){
        return $this->select(true,array('*', 'estado'=> new Zend_Db_Expr("CASE WHEN Che_Est = 'P' THEN 'Protestado' WHEN Che_Est = 'S' THEN 'Sin Cobrar' WHEN Che_Est = 'I' THEN 'Anulado' WHEN Che_Est = 'A' THEN 'Activo' WHEN Che_Est = 'C' THEN 'Cobrado' ELSE 'SIN ESTADO DEFINIDO' END")));
    }
    /* crea una sql standart para jqgrid, condiciones incluidas */
    public function _selectBasicGrid($cond=null){
        $sel=$this->_selectBasic();
        return $sel;
    }
    /* formatea el array para insert o update */
    public function formatData($data, $type, $allData=null){
        return ($type=='I')?$data:$data;
    }
    /* crea sentencia por id nombre sql */
    public function sqlByNombre($id,$Par_Sql,$cond=null){
        if(is_object($Par_Sql)){ $sql=$Par_Sql; $Par_Sql=$cond; }else $sql='';
        switch($id){
            case "":
                $sql="";
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "setEmpCod":
                $sql->where("Emp_Cod=?",$_SESSION['Ses_Emp_Cod']);
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "byChequesclientes":
                $sql->join(array('pac'=>'pag_anticipo_cli'),"pac.Che_Cod=$this->_name.Che_Cod", array('Pac_Cto','Pac_Cod','Pac_Est'));
                $sql->join(array('antc'=>'anticipos_clientes'),"antc.Ant_Cod=pac.Ant_Cod",array('Ant_Fec','Ant_Cod','Cli_Cod'));
                $sql->join(array('asnt'=>'asientos'),"asnt.Asi_Cod=pac.Asi_Cod",array('*'));
                $sql->join(array('cmp'=>'comprobantes'), "cmp.Com_Cod=asnt.Com_Cod",array('Com_Cod','Tia_Cod'));
                $sql->group("$this->_name.Che_Cod");
                break;
            case "byDetAntCCCC":
                $sql->joinLeft(array('daccc'=>'det_ant_cccc'),"daccc.Ant_Cod=antc.Ant_Cod",array('Dcc_Cod','Ddc_Val'));
                //$sql->joinLeft(array('daccppc'=>'det_ccpp_c'),"daccppc.Dcc_Cod=daccc.Dcc_Cod",array('Com_Cod','Cpc_Val'));
                break;
                default: throw new Exception ("No existe la sql denominada $id!");
        }
        //echo $this->getSqlString($sql)."<br/>";
        return $sql;
    }
    /* crea sentencia por id numero sql */
    public function sqlByNumero($id,$Par_Sql,$cond=null){
        $sql=(is_object($Par_Sql)?$Par_Sql:'');
        switch($id){
            case 0:
                $sql="";
                //echo $this->getSqlString($sql)."<br/>";
                break;
            default: throw new Exception ("No existe la sql numero $id!");
        }
        //echo $this->getSqlString($sql)."<br/>";
        return $sql;
    }
}