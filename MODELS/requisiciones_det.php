<?php 
require_once(dirname(__file__)."/../DATA/libs/AbstractModel.php");
class requisiciones_det extends AbstractModel{
    protected $_name = 'requisiciones_det'; 
    protected $_primary = array('Req_Cod', 'Rqd_Int', 'Pro_Cod');	
    protected $_state = '';
    //protected $_fields = array(); // se declara las filas que va a tener el arreglo en caso de insert o update //opcional
    
    /* crea una sql basica global para la tabla */
    public function _selectBasic($cond=null,$limits=false){         
        return $this->select()->join(
            'requisiciones', "requisiciones.Req_Cod=$this->_name.Req_Cod", array()
        ); 
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
            case "setEmpCod":                
                $sql->where("Emp_Cod=?",$_SESSION['Ses_Emp_Cod']);
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
                $sql = "SELECT requisiciones_det.Rqd_Int, Req_Cod, Ite_Lar, marca.Mar_Des, categorias.Cat_Des, Rqd_Cant, Rqd_Uni
                FROM requisiciones_det
                INNER JOIN producto ON (requisiciones_det.Pro_Cod = producto.Pro_Cod)
                INNER JOIN item ON (item.Ite_Cod = producto.Ite_Cod)
                INNER JOIN marca ON (producto.Mar_Cod = marca.Mar_Cod)
                INNER JOIN categorias ON (categorias.Cat_Cod = item.Cat_Cod)
                WHERE Req_Cod = $Par_Sql[Req_Cod]";
                //echo $this->getSqlString($sql)."<br/>";
                break;
            default: throw new Exception ("No existe la sql numero $id!");
        }
        //echo $this->getSqlString($sql)."<br/>";
        return $sql;
    }
}