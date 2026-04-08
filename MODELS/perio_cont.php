<?php
use \Exception;
require_once(dirname(__file__)."/../DATA/libs/AbstractModel.php");
class perio_cont extends AbstractModel{
    protected $_name = 'perio_cont';
    protected $_primary = array('Pec_Cod');
    protected $_state = 'Pec_Est';

    public function _selectBasic($cond=null,$limits=false){
        return $this->select(true,array(
            '*','Year'=>"YEAR(Pec_Fei)"
        ))->join('plan_cuenta','plan_cuenta.Pla_Cod = '.$this->_name.'.Pla_Cod');
    }
    public function _selectBasicGrid($cond=null,$limits=false){
        $sel=$this->_selectBasic();
        return $sel;
    }
    public function sqlByNombre($id,$Par_Sql,$cond=null){
        if(is_object($Par_Sql)){ $sql=$Par_Sql; $Par_Sql=$cond; }else $sql='';
        switch($id){
            case "":
                $sql="";
                //echo $sql.'<br/>';
                break;
            case "setEmpCod":
                $sql->where("plan_cuenta.Emp_Cod=?",$_SESSION['Ses_Emp_Cod']);
                //echo $sql.'<br/>';
                break;
            case "getByYear":
                $sql->where("plan_cuenta.Emp_Cod=?",$_SESSION['Ses_Emp_Cod']);
                $sql->where("YEAR($this->_name.Pec_Fei)=?",$Par_Sql['Year']);
                //echo $sql.'<br/>';
                break;
            case "getByDate":
                $sql->where("plan_cuenta.Emp_Cod=?",$_SESSION['Ses_Emp_Cod']);
                $sql->where("'$Par_Sql[Date]' BETWEEN $this->_name.Pec_Fei AND $this->_name.Pec_Fef");
                break;
            case "getPerioByFec":
                $selectOther = $this->select(false)->from(array('plc'=>'plan_cuenta'))
                    ->addCols(null, array('Maximo'=> new Zend_Db_Expr("MAX(plc.Pla_Cod)")))
                    ->where("plc.Emp_Cod=?",$_SESSION['Ses_Emp_Cod'])
                    ->group('plc.Pla_Cod');
                //$Par_Sql->join(array('sand'=>$selectOther), "sand.Pla_Cod = perio_cont.Pla_Cod", array('Pla_Cod','Pec_Cod','Pec_Fei));
                $sql->join(array('sand'=>$selectOther), "sand.Pla_Cod = perio_cont.Pla_Cod", array('*'));
                break;
            case "order":
                $sql->order("Year DESC");
                //echo $sql.'<br/>';
                break;
            /*case "getPeriodo":
                $sql->where("$Caj_Fec BETWEEN $this->_name.Pec_Fei AND $this->_name.Pec_Fef");
                //echo $sql.'<br/>';
                break;*/
            default: $this->sqlByParams($id,$sql,array(
                    'isActive'=>"$this->_name.$this->_state='A'"
                )); //echo $this->getSqlString($sql)."<br/>";
        }
        //echo $sql."<br/>";
        return $sql;
    }
    public function sqlByNumero($id,$Par_Sql,$cond=null){
        if(is_object($Par_Sql)){ $sql=$Par_Sql; $Par_Sql=$cond; }else $sql='';
        switch($id){
            case 0:
                $sql="";
                //echo $sql.'<br/>';
                break;
            default: throw new Exception ("No existe la sql denominada $id!");
        }
        //echo $sql."<br/>";
        return $sql;
    }
    public function getForSelect(){
        $sel=$this->_selectBasic()->order('perio_cont.Pec_Fei DESC')->where('perio_cont.Pec_Est=?','A');
        $this->sqlByNombre('setEmpCod', $sel);
        return $sel;
    }
}