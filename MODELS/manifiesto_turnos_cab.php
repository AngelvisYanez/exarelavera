<?php
use \Exception;
require_once(dirname(__file__)."/../DATA/libs/AbstractModel.php");
class manifiesto_turnos_cab extends AbstractModel{
    protected $_name = 'manifiesto_turnos_cab';
    protected $_primary = array('Tur_Cod');
    protected $_state = 'Tur_Est';
    //protected $_fields = array(); // se declara las filas que va a tener el arreglo en caso de insert o update //opcional

    /* crea una sql basica global para la tabla */
    public function _selectBasic($cond=null,$limits=false){
        return $this->select();
    }
    /* crea una sql standart para jqgrid, condiciones incluidas */
    public function _selectBasicGrid($cond=null,$limits=false){
        $sel=$this->_selectBasic();
        $this->sqlByNombre("setEmpCod", $sel);
        
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
                //ChromePhp::log($this->getSqlString($sql));
                break;            
            case "orderByDes": 
                $sql->order('Tur_Fei asc');
                break;
            case "getTurnosDetalle":
                $sql->join('manifiesto_turnos_det', "manifiesto_turnos_det.Tur_Cod = $this->_name.Tur_Cod", 
                array('Tud_Cod'=>'Tud_Cod','hora_inicio'=>'Tud_Hin','hora_fin'=>'Tud_Hfi','cupos'=>'Tud_Cup','activo'=>'IF(Tud_Est="A",true,false)')
                );
                $sql->join('manifiesto_celdas', "manifiesto_celdas.Cel_Cod = manifiesto_turnos_det.Cel_Cod", array('*'));
                $sql->where("Tud_Est!='I'");
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