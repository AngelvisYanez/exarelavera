<?php
use \Exception;
require_once(dirname(__file__)."/../DATA/libs/AbstractModel.php");
class viaje_lugar extends AbstractModel{
    protected $_name = 'viaje_lugar';
    protected $_primary = array('Vlu_Cod');
    protected $_state = 'Vlu_Est';
    //protected $_fields = array(); // se declara las filas que va a tener el arreglo en caso de insert o update //opcional

    /* crea una sql basica global para la tabla */
    public function _selectBasic($cond=null){
        return $this->select();
    }
    /* crea una sql standart para jqgrid, condiciones incluidas */
    public function _selectBasicGrid($cond=null){
        $sel=$this->_selectBasic();
        $this->sqlByNombre("setEmpCod", $sel);
        if(isset($cond['isActive']) && $cond['isActive']=='S') $this->sqlByNombre("isActive", $sel);
        return $sel;
    }
    /* formatea el array para insert o update */
    public function formatData($data, $type, $allData=null){
        if($type=='I'&&!isset($data['Emp_Cod'])) $data['Emp_Cod']=$_SESSION['Ses_Emp_Cod'];
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
                $sql->where("$this->_name.Emp_Cod=?",$_SESSION['Ses_Emp_Cod']);
                //echo $this->getSqlString($sql)."<br/>";
                break;
            default: $this->sqlByParams($id,$sql,array(
                    'isActive'=>"$this->_name.$this->_state='A'",
                    'isOrigen'=>"$this->_name.Vlu_Tip='O'",
                    'isDestino'=>"$this->_name.Vlu_Tip='D'"
                )); //echo $this->getSqlString($sql)."<br/>";
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