<?php 
use \Exception;
require_once(dirname(__file__)."/../DATA/libs/AbstractModel.php");
class manifiesto_celdas extends AbstractModel{
    protected $_name = 'manifiesto_celdas'; 
    protected $_primary = array('Cel_Cod');	
    protected $_state = 'Cel_Est';
    //protected $_fields = array(); // se declara las filas que va a tener el arreglo en caso de insert o update //opcional
    
    /* crea una sql basica global para la tabla */
    public function _selectBasic($cond=null,$limits=false){         
        return $this->select(true,array('*'));
    }
    /* crea una sql standart para jqgrid, condiciones incluidas */
    public function _selectBasicGrid($cond=null,$limits=false){ 
        $sel=$this->_selectBasic();        
        $this->sqlByNombre("setEmpCod", $sel);
                
        
        // Aplicar filtros de búsqueda si existen
        if (is_array($cond) && !empty($cond['op_opciones']) && !empty($cond['search'])) {
            $searchTerm = addslashes($cond['search']);
            if ($cond['op_opciones'] == "n") {
                // Búsqueda por nombre
                $sel->where("$this->_name.Cel_Nom LIKE ?", "%$searchTerm%");
            } else if ($cond['op_opciones'] == "c") {
                // Búsqueda por código/número
                $sel->where("$this->_name.Cel_Num LIKE ?", "%$searchTerm%");
            }
        }
        
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
                $sql->where("$this->_name.Emp_Cod=?",$_SESSION['Ses_Emp_Cod']);
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "orderByCelCod":
                $sql->order("$this->_name.Cel_Cod ASC");
                //echo $this->getSqlString($sql)."<br/>";
                break;              
            case "getGrupos":          
                $sql->where("$this->_name.Cel_Tip = 'G'");
                break;
            case "getDetalles":          
                $sql->where("$this->_name.Cel_Tip = 'D'");
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

