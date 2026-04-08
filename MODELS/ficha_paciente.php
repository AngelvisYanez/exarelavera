<?php 
use \Exception;

require_once(dirname(__file__)."/../DATA/libs/AbstractModel.php");


class ficha_paciente extends AbstractModel{
    protected $_name = 'ficha_paciente'; 
    protected $_primary = array('Fic_Cod');	
    protected $_state = 'Fic_Est';
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
                $sql="INSERT INTO ficha_paciente (Pac_Cod, Fic_Num, Fic_Fec, Fic_Mot, Fic_Hea, Fic_Obs) VALUES ($Par_Sql[Pac_Cod], '$Par_Sql[Fic_Num]', '$Par_Sql[Fic_Fec]', '$Par_Sql[Fic_Mot]', '$Par_Sql[Fic_Hea]', '$Par_Sql[Fic_Obs]')";
                break;
            case 1:
                $sql="INSERT INTO ficha_medicamento VALUES ($Par_Sql[Fic_Cod], $Par_Sql[Med_Cod], '$Par_Sql[Med_Dos]', '$Par_Sql[Med_Dur]')";
                //ChromePhp::log($sql);
                break;
            default: throw new Exception ("No existe la sql numero $id!");
        }
        //echo $this->getSqlString($sql)."<br/>";
        return $sql;
    }
}