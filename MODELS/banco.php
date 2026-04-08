<?php
use \Exception;
require_once(dirname(__file__)."/../DATA/libs/AbstractModel.php");
class banco extends AbstractModel{
    protected $_name = 'banco';
    protected $_primary = array('Ban_Cod');
    protected $_state = 'Ban_Est';
    //protected $_fields = array(); // se declara las filas que va a tener el arreglo en caso de insert o update //opcional

    /* crea una sql basica global para la tabla */
    public function _selectBasic($cond=null,$limits=false){
        return $this->select();
    }
    /* crea una sql standart para jqgrid, condiciones incluidas */
    public function _selectBasicGrid($cond=null,$limits=false){
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
                $sql->where("plan_cuenta.Emp_Cod=?",$_SESSION['Ses_Emp_Cod']);
                //echo $this->getSqlString($sql)."<br/>";
                break;
            /*case "isActive":
                $sql->where("$this->_name.$this->_state='A'");
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "isTipo":
                $sql->where("$this->_name.Ban_tip='B'");
                //echo $this->getSqlString($sql)."<br/>";
                break;*/
            case "byDetPlan":
                $sql->join('det_plan',"det_plan.Pld_Cod=$this->_name.Pld_Cod");
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "byPlan":
                $sql->join('plan_cuenta',"plan_cuenta.Pla_Cod=det_plan.Pla_Cod");
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "setPeriodo":
                $sql->join('det_plan', "det_plan.Pld_Cod=$this->_name.Pld_Cod", array('Pld_Cdc','Pld_Des','Pla_Cod'));
                $sql->join('plan_cuenta', "plan_cuenta.Pla_Cod=det_plan.Pla_Cod", array());
                $sql->join('perio_cont', "perio_cont.Pla_Cod=plan_cuenta.Pla_Cod", array('Pec_Cod'));
                //echo $this->getSqlString($sql)."<br/>";
                break;
            default: $this->sqlByParams($id,$sql,array(
                    'isActive'=>"$this->_name.$this->_state='A'",
                    "isTipo"=>"$this->_name.Ban_tip='B'"
                )); //echo $this->getSqlString($sql)."<br/>";
        }
        //echo $this->getSqlString($sql)."<br/>";
        return $sql;
    }
    /* crea sentencia por id numero sql */
    public function sqlByNumero($id,$Par_Sql,$cond=null){
        if(is_object($Par_Sql)){ $sql=$Par_Sql; $Par_Sql=$cond; }else $sql='';
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