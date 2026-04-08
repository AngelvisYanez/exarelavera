<?php
use \Exception;
require_once(dirname(__file__)."/../DATA/libs/AbstractModel.php");
class cheques extends AbstractModel{
    protected $_name = 'cheques';
    protected $_primary = array('Che_Cod','Prv_Cod', 'Ban_Cod','Asi_Cod');
    protected $_state = 'Che_Est';
    //protected $_fields = array(); // se declara las filas que va a tener el arreglo en caso de insert o update //opcional

    /* crea una sql basica global para la tabla */
    public function _selectBasic($cond=null){
        return $this->select(true,array('*', 'estado'=> new Zend_Db_Expr("CASE WHEN Che_Est = 'P' THEN 'Protestado' WHEN Che_Est = 'S' THEN 'Sin Cobrar' WHEN Che_Est = 'I' THEN 'Anulado' WHEN Che_Est = 'A' THEN 'Activo' WHEN Che_Est = 'C' THEN 'Cobrado' ELSE 'SIN ESTADO DEFINIDO' END")))
            ->join(array('ast'=>'asientos'), "ast.Asi_Cod = $this->_name.Asi_Cod", array('*'));
           // ->addCols(null, array('estado'=> new Zend_Db_Expr("Che_Est CASE WHEN Che_Est = 'P' THEN 'Protestado' WHEN Che_Est = 'S' THEN 'Sin Cobrar' WHEN Che_Est = 'I' THEN 'Anulado' WHEN Che_Est = 'A' THEN 'Activo' WHEN Che_Est = 'C' THEN 'Cobrado' ELSE 'SIN ESTADO DEFINIDO' END")));

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
            case "isActive":
                $sql->where("$this->_name.$this->_state='A'");
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "byNumCheqAndBanc":
                $sql->where("$this->_name.Che_Num=?", "{$Par_Sql['Che_Num']}")
                     ->where("$this->_name.Ban_Cod=?", "{$Par_Sql['Ban_Cod']}");
                break;
            case "byCheques":
                $sql->join(array('pap'=>'pago_anticipo_proveedores'),"pap.Asi_Cod=ast.Asi_Cod", array('Pap_Cto', 'Pap_Cod','Pap_Est') );
                $sql->join(array('antp' => 'anticipos_proveedores'), "antp.Atp_Cod=pap.Atp_Cod", array('Atp_Fec','Atp_Cod','Prv_Cod'));
                $sql->join(array('cmp'=>'comprobantes'), "cmp.Com_Cod=antp.Com_Cod", array('Com_Cod','Tia_Cod'));
                 $sql->group("$this->_name.Che_Cod");
            break;

            case "byDetAntCCPP":
                $sql->joinLeft(array('daccpp'=>'det_ant_ccpp'), "daccpp.Atp_Cod = antp.Atp_Cod", array('Dac_Cod','Dac_Val'));
                $sql->addCols('', array("Anularse"=>"IF(daccpp.Atp_Cod IS NULL, 'S','N')"));
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