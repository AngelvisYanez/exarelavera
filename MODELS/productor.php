<?php 
require_once(dirname(__file__)."/../DATA/libs/AbstractModel.php");
class productor extends AbstractModel{
    protected $_name = 'productor_bana'; 
    protected $_primary = array('Prd_Cod');	
    protected $_state = 'Prd_Est';	
    
    public function _selectBasic($cond=null,$limits=false){         
        return $this->select()->join(
                'proveedore', "proveedore.Prv_Cod=$this->_name.Prv_Cod", array()
            )->join(
                'persona', "persona.Prs_Cod=proveedore.Prs_Cod",
                array('Productor'=>"CONCAT(Prs_Nom,' ',Prs_Ape)",'Prs_Nom','Prs_Ape','Prs_Ced','Prs_Dir')
            ); 
    }
    public function _selectBasicGrid($cond=null,$limits=false){ 
        $sel=$this->_selectBasic();
        $this->sqlByNombre("setEmpCod", $sel);
        if(isset($cond['op_opciones']))
            $sel->where($cond['op_opciones']=="c"?"Prs_Ced=?":"CONCAT(Prs_Nom,' ',Prs_Ape)LIKE ?", $cond['op_opciones']=="c"?$cond['search']:"%{$cond['search']}%");  
        return $sel; 
    }
    public function formatData($data, $type, $allData=null){         
        return ($type=='I')?array(
            'Prv_Cod'=>$data['Prv_Cod'], 'Prd_Est'=>'A'
        ):$data;
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