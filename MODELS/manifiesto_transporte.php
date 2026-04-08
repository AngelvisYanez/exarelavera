<?php 
use \Exception;
require_once(dirname(__file__)."/../DATA/libs/AbstractModel.php");
class manifiesto_transporte extends AbstractModel{
    protected $_name = 'manifiesto_transporte'; 
    protected $_primary = array('Mat_Cod');	
    protected $_state = 'Mat_Est';
    //protected $_fields = array(); // se declara las filas que va a tener el arreglo en caso de insert o update //opcional
    
    /* crea una sql basica global para la tabla */
    public function _selectBasic($cond=null,$limits=false){         
        return $this->select(true,array('*'));            
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
                $sql->where("$this->_name.Emp_Cod=?",$_SESSION['Ses_Emp_Cod']);
                //echo $this->getSqlString($sql)."<br/>";
                break; 
             case "isActive":
                $sql->where("$this->_name.$this->_state='A'");
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "getVehiculo":
                $sql->join("vehiculo", "vehiculo.Mat_Cod = $this->_name.Mat_Cod",array('*'));
                //echo $this->getSqlString($sql)."<br/>";
                break;  
            case "getVehiculoByPla": 
                $sql->join("vehiculo", "vehiculo.Mat_Cod = $this->_name.Mat_Cod",array('*'));
                $sql->join("manifiesto_vehiculo", "manifiesto_vehiculo.Veh_Cod = vehiculo.Veh_Cod",array('*'));                
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
            case 1:
                $sql="select coalesce((select count(manifiesto.Veh_Cod)as total from manifiesto 
                            inner join vehiculo v on manifiesto.Veh_Cod = v.Veh_Cod 	
                        where v.Veh_Pla=vehiculo.Veh_Pla and Man_Tes not like '%GS%' and Man_Est = 'A' group by v.Veh_Pla
                        ),0)as total,
                        manifiesto_transporte.*, vehiculo.*, manifiesto_vehiculo.*
                    from manifiesto_transporte
                        inner join vehiculo on vehiculo.Mat_Cod = manifiesto_transporte.Mat_Cod
                    inner join manifiesto_vehiculo on manifiesto_vehiculo.Veh_Cod = vehiculo.Veh_Cod
                    where (`Mat_Est` = 'A' and `manifiesto_vehiculo`.`Pla_Cod` = $Par_Sql[Pla_Cod])";
                //echo $this->getSqlString($sql)."<br/>";
                break;
            default: throw new Exception ("No existe la sql numero $id!");
        }
        //echo $this->getSqlString($sql)."<br/>";
        return $sql; 
    }
}