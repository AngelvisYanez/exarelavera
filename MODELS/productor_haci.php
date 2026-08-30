<?php 
require_once(dirname(__file__)."/../DATA/libs/AbstractModel.php");
class productor_haci extends AbstractModel{
    protected $_name = 'productor_haci'; 
    protected $_primary = array('Prh_Cod');	
    protected $_state = 'Prh_Est';	
    
    public function _selectBasic($cond=null,$limits=false){         
        return $this->select()->join(
                'productor_bana', "productor_bana.Prd_Cod=$this->_name.Prd_Cod", array('Prv_Cod')
            )->join(
                'proveedore', "proveedore.Prv_Cod=productor_bana.Prv_Cod", array('Prs_Cod')
            )->join(
                'persona', "persona.Prs_Cod=proveedore.Prs_Cod",
                array('Productor'=>"CONCAT(Prs_Nom,' ',Prs_Ape)",'Prs_Nom','Prs_Ape','Prs_Ced','Prs_Dir')
            ); 
    }
    public function _selectBasicGrid($cond=null,$limits=false){         
        $sel=$this->_selectBasic();
        $this->sqlByNombre("setEmpCod", $sel);
        return $sel; 
    }
    public function formatData($data, $type, $allData=null){ 
        return ($type=='I')?$data:$data;
    }
    public function sqlByNombre($id,$Par_Sql,$cond=null){
        $sql="";
        switch($id){
            case "":
                $sql="";
                //echo $sql.'<br/>';
                break;
            case "setEmpCod":                
                $Par_Sql->where("proveedore.Emp_Cod=?",$_SESSION['Ses_Emp_Cod']);
                //echo $sql.'<br/>';
                break;
            case "basic":
                $sql=$this->select()->where('productor_haci.Prd_Cod=?',$Par_Sql[0]);
                //echo $sql.'<br/>';
                break;
            default: throw new Exception ("No existe la sql denominada $id!");
        }
        //echo $sql."<br/>";
        return $sql;
    }
    public function sqlByNumero($id,$Par_Sql,$cond=null){
        $sql="";
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