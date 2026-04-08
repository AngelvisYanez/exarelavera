<?php 
use \Exception;
require_once(dirname(__file__)."/../DATA/libs/AbstractModel.php");
class banano_marca extends AbstractModel{
    protected $_name = 'banano_marca'; 
    protected $_primary = array('Bam_Cod');	
    protected $_state = 'Bam_Est';	
    
    public function _selectBasic($cond=null,$limits=false){         
        return $this->select(); 
    }
    public function _selectBasicGrid($cond=null,$limits=false){ 
        $sel=$this->_selectBasic();        
        return $sel; 
    }
    public function formatData($data, $type, $allData=null){ 
        return ($type=='I')?$data:$data;
    }
    public function sqlByNombre($id,$Par_Sql){
        $sql=(is_object($Par_Sql)?$Par_Sql:'');
        switch($id){
            case "":
                $sql="";
                //echo $sql.'<br/>';
                break;
            case "setEmpCod":                
                $sql->where("$this->_name.Emp_Cod=?",$_SESSION['Ses_Emp_Cod']);
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "isActive":
                $sql->where("$this->_name.$this->_state='A'");
                //echo $this->getSqlString($sql)."<br/>";
                break;
            default: throw new Exception ("No existe la sql denominada $id!");
        }
        //echo $sql."<br/>";
        return $sql;
    }
    public function sqlByNumero($id,$Par_Sql){
        $sql=(is_object($Par_Sql)?$Par_Sql:'');
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
}