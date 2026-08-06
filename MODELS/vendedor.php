<?php 
require_once(dirname(__file__)."/../DATA/libs/AbstractModel.php");
class vendedor extends AbstractModel{
    protected $_name = 'vendedor'; 
    protected $_primary = array('Vnd_Cod');	
    protected $_state = 'Vnd_Est';
    //protected $_fields = array(); // se declara las filas que va a tener el arreglo en caso de insert o update //opcional
    
    /* crea una sql basica global para la tabla */
    public function _selectBasic($cond=null,$limits=false){         
        return $this->select()->join(
                'persona', "persona.Prs_Cod=$this->_name.Prs_Cod",
                array('Vendedor'=>"CONCAT(Prs_Nom,' ',Prs_Ape)",'Prs_Nom','Prs_Ape','Prs_Ced','Prs_Dir', 'Prs_Cor')
            )->join(
                'puntos_imp', "puntos_imp.Pun_Cod=$this->_name.Pun_Cod",
                array('Punto'=>'Pun_Des','Suc_Cod')
            );  
    }
    /* crea una sql standart para jqgrid, condiciones incluidas */
    public function _selectBasicGrid($cond=null,$limits=false){ 
        $sel=$this->_selectBasic();
        $this->sqlByNombre("setEmpCod", $sel);        
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
                //echo $this->getSqlString($sql)."<br/>" Ses_Usu_Cod;
                break;
            case "setEmpCod":                
                $sql->where("puntos_imp.Suc_Cod=?",$_SESSION['Ses_Suc_Cod']);
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "setSucCod":                
                $sql->where("puntos_imp.Suc_Cod=?",$_SESSION['Ses_Suc_Cod']);
                //echo $this->getSqlString($sql)."<br/>";
                break;    
            /*case "setUsuCod":                
                $sql->where("persona.Prs_Cod=?",$_SESSION['Ses_Usu_Cod']);
                //echo $this->getSqlString($sql)."<br/>";
                break;*/
            case "setPrsCod":                
                $sql->where("persona.Prs_Cod=?",$_SESSION['Ses_Prs_Cod']);
                //echo $this->getSqlString($sql)."<br/>";
                break;    
            case "isActive":
                $sql->where("$this->_name.$this->_state='A'");
                //echo $this->getSqlString($sql)."<br/>";
                break;
            default: throw new Exception ("No existe la sql denominada $id!");
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