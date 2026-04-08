<?php 
use \Exception;
require_once(dirname(__file__)."/../DATA/libs/AbstractModel.php");
class acopio extends AbstractModel{
    protected $_name = 'acopio'; 
    protected $_primary = array('Aco_Cod');	
    protected $_state = 'Aco_Est';	
    
    public function _selectBasic($cond=null){         
        return $this->select()
            ->join('sucursal',"sucursal.Suc_Cod=$this->_name.Suc_Cod",array('Suc_Des'))
            ->join('empresas',"empresas.Emp_Cod=sucursal.Emp_Cod",array())
            ->join('acopio_tipo',"acopio_tipo.Act_Tip=$this->_name.Act_Tip",array('Act_Des','Act_Acu','Act_Gen')); 
    }
    public function _selectBasicGrid($cond=null){ 
        $sel=$this->_selectBasic();   
        $this->sqlByNombre("setEmpCod", $sel);
        return $sel; 
    }
    public function formatData($data, $type, $allData=null){ 
        return ($type=='I')?$data:$data;        
    }
    public function sqlByNombre($id,$Par_Sql,$cond=null){
        if(is_object($Par_Sql)){ $sql=$Par_Sql; $Par_Sql=$cond; }else $sql='';
        switch($id){
            case "":
                $sql="";
                //echo $sql.'<br/>';
                break;
            case "setEmpCod":                
                $sql->where("empresas.Emp_Cod=?",$_SESSION['Ses_Emp_Cod']);
                //echo $sql.'<br/>';
                break;
            case "setSucCod":                
                $sql->where("acopio.Suc_Cod=?",$_SESSION['Ses_Suc_Cod']);
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "byMyUsuario":  
                if(!$sql->hasTable('acopio_usuario')) 
                    $sql->join('acopio_usuario', "acopio_usuario.Aco_Cod=$this->_name.Aco_Cod", array('Aco_Cod'));
                $sql->where("acopio_usuario.Usu_Cod=?",$_SESSION['Ses_Usu_Cod']);
                //echo $sql.'<br/>';
                break;
            case "setUsuarios":
                $sql->join('acopio_usuario', "acopio_usuario.Aco_Cod=$this->_name.Aco_Cod", array('Aco_Cod'));
                //echo $this->getSqlString($sql)."<br/>";
                break;
            default: $this->sqlByParams($id,$sql,array(
                    "isActive"=>"$this->_name.$this->_state='A'",
                    'isTipo'=>"$this->_name.Act_Tip=?",
                    'orderByNom'=>"Aco_Des ASC"
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
}