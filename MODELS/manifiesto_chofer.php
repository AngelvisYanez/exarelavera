<?php 
use \Exception;
require_once(dirname(__file__)."/../DATA/libs/AbstractModel.php");
class manifiesto_chofer extends AbstractModel{
    protected $_name = 'manifiesto_chofer'; 
    protected $_primary = array('Cho_Cod','Pla_Cod');	
    protected $_state = '';
    //protected $_fields = array(); // se declara las filas que va a tener el arreglo en caso de insert o update //opcional
    
    /* crea una sql basica global para la tabla */
    public function _selectBasic($cond=null,$limits=false){         
        return $this->select(true,array('*'))
            ->join('manifiesto_plantas', "manifiesto_plantas.Pla_Cod = $this->_name.Pla_Cod", array('manifiesto_plantas.Cli_Cod'))            
            ->join('chofer',"chofer.Cho_Cod = $this->_name.Cho_Cod", array('*'))
            ->join('persona',"persona.Prs_Cod = chofer.Prs_Cod", array('nombre'=>"concat(persona.Prs_Nom,' ',persona.Prs_Ape)","persona.Prs_Ced"));            
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
             case "setEmpCod":
                $sql->where("cliente.Emp_Cod=?",$_SESSION['Ses_Emp_Cod']);
                //echo $this->getSqlString($sql)."<br/>";
                break; 
            case "":
                $sql="";
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