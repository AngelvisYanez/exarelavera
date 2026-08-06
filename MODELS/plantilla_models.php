<?php
require_once(dirname(__file__)."/../DATA/libs/AbstractModel.php");
class perio_cont extends AbstractModel{
    protected $_name = 'NOMBRE_TABLA';
    protected $_primary = array('LLAVES_PRIMARIA1');
    protected $_state = 'CAMPOS_ESTADO';
    //protected $_fields = array(); // elimina fields q no pertenecen a este arreglo, se puede poner defaults para insert diferentes a null //opcional
    //protected $_defaults = array(); // fields default en caso de estar empty insert y update sin key is null //opcional

    /* crea una sql basica global para la tabla */
    public function _selectBasic($cond=null){
        return $this->select();
    }
    /* crea una sql standart para jqgrid, condiciones incluidas */
    public function _selectBasicGrid($cond=null){
        $sel=$this->_selectBasic();
        //$this->sqlByNombre("setEmpCod", $sel);
        //if($this->orNull($cond,'isActive')=='S') $this->sqlByNombre("isActive", $sel);
        return $sel;
    }
    /* formatea el array para insert o update */
    /*public function formatData($data, $type, $allData=null){
        //if($type=='I'&&!$this->hasVal($data,'Emp_Cod')) $data['Emp_Cod']=$_SESSION['Ses_Emp_Cod'];
        return $data;
    }*/
    /* crea sentencia por id nombre sql */
    public function sqlByNombre($id,$Par_Sql,$cond=null){
        if(is_object($Par_Sql)){ $sql=$Par_Sql; $Par_Sql=$cond; }else $sql='';
        switch($id){
            case "":
                $sql="";
                //echo $this->toStr($sql)."<br/>";
                break;
            /*case "setEmpCod":
                $sql->where("Emp_Cod=?",$_SESSION['Ses_Emp_Cod']);
                //echo $this->toStr($sql)."<br/>";
                break;*/
            default: $this->sqlByParams($id,$sql,array(
                    'isActive'=>"$this->_name.$this->_state='A'",
                    //'notIsActive'=>"$this->_name.$this->_state='I'"
                )); //echo $this->toStr($sql)."<br/>";
        }
        //echo $this->toStr($sql)."<br/>";
        return $sql;
    }
    /* crea sentencia por id numero sql */
    public function sqlByNumero($id,$Par_Sql,$cond=null){
        if(is_object($Par_Sql)){ $sql=$Par_Sql; $Par_Sql=$cond; }else $sql='';
        switch($id){
            case 0:
                $sql="";
                //echo $this->toStr($sql)."<br/>";
                break;
            default: throw new Exception ("No existe la sql numero $id!");
        }
        //echo $this->toStr($sql)."<br/>";
        return $sql;
    }
}