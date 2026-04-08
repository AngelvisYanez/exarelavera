<?php 
use \Exception;
require_once(dirname(__file__)."/../DATA/libs/AbstractModel.php");
class autorizacion extends AbstractModel{
    protected $_name = 'autorizaci'; 
    protected $_primary = array('Aut_Cod');	
    protected $_state = 'Aut_Est';
    //protected $_fields = array(); // se declara las filas que va a tener el arreglo en caso de insert o update //opcional
    
    /* crea una sql basica global para la tabla */
    public function _selectBasic($cond=null,$limits=false){         
        return $this->select()
            ->join('tipo_compr',"tipo_compr.Tic_Cod=$this->_name.Tic_Cod",array('Tic_Sri','Tic_Des'))
            ->join('puntos_imp',"puntos_imp.Pun_Cod=$this->_name.Pun_Cod",array('Pun_Des','Suc_Cod'))
            ->join('sucursal',"sucursal.Suc_Cod=puntos_imp.Suc_Cod",array('Emp_Cod'))
            ->join('vendedor',"vendedor.Pun_Cod=$this->_name.Pun_Cod",array('Vnd_Cod')); 
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
            case "setSucCod":                
                $sql->where("sucursal.Suc_Cod=?",$_SESSION['Ses_Suc_Cod']);
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "setEmpCod":                
                $sql->where("sucursal.Emp_Cod=?",$_SESSION['Ses_Emp_Cod']);
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "setPrsCod":                
                $sql->where("vendedor.Prs_Cod=?",$_SESSION['Ses_Prs_Cod']);
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "isActive":
                $sql->where("$this->_name.$this->_state='A' AND vendedor.Vnd_Est='A' AND tipo_compr.Tic_Est='A'");
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "isActiveToday":
                $sql->where("? BETWEEN Aut_Fci AND Aut_Cad", date("Y-m-d"));
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