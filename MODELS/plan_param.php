<?php 
use \Exception;
require_once(dirname(__file__)."/../DATA/libs/AbstractModel.php");
class plan_param extends AbstractModel{
    protected $_name = 'plan_param'; 
    protected $_primary = array('Tpa_Cod','Pld_Cod');	
    protected $_state = 'Ppc_Est';
    //protected $_fields = array(); // se declara las filas que va a tener el arreglo en caso de insert o update //opcional
    
    /* crea una sql basica global para la tabla */
    public function _selectBasic($cond=null,$limits=false){         
        return $this->select()
            ->join('tipo_param',"tipo_param.Tpa_Cod=$this->_name.Tpa_Cod",array('Tpa_Abr','Tpa_Des'))
            ->join('det_plan',"det_plan.Pld_Cod=$this->_name.Pld_Cod",array('Pld_Cdc','Pld_Des'))
            ->join('plan_cuenta',"plan_cuenta.Pla_Cod=det_plan.Pla_Cod",array())
        ; 
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
        $sql=(is_object($Par_Sql)?$Par_Sql:'');
        switch($id){
            case "":
                $sql="";
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "setEmpCod":                
                $sql->where("plan_cuenta.Emp_Cod=?",$_SESSION['Ses_Emp_Cod']);
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "isActive":
                $sql->where("$this->_name.$this->_state='A' AND tipo_param.Tpa_Est='A' AND det_plan.Pld_Est='A'");
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "getByAbr":                
                $sql->where("tipo_param.Tpa_Abr=?",$cond['Tpa_Abr']);
                //echo $this->getSqlString($sql)."<br/>";
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