<?php
use \Exception;
require_once(dirname(__file__)."/../DATA/libs/AbstractModel.php");
class proveedore extends AbstractModel{
    protected $_name = 'proveedore';
    protected $_primary = array('Prv_Cod');
    protected $_state = 'Prv_Est';

    public function _selectBasic($cond=null,$limits=false){
        return $this->select(true,array('Prv_Cod','Prv_Con','Prv_Esp'))->join(
                'persona', "persona.Prs_Cod=$this->_name.Prs_Cod",array('Proveedor'=>$this->expr("IF(Prv_Com IS NULL OR Prv_Com='',".$this->concat(array('persona.Prs_Ape','persona.Prs_Nom')).",Prv_Com)"),'Ruc'=>'Prs_Ced','Prs_Nom','Prs_Ape','Prs_Ced','Prs_Dir')
            );
    }
    public function _selectBasicGrid($cond=null,$limits=false){
        $sel=$this->_selectBasic();
        $this->sqlByNombre("setEmpCod", $sel);
        if(isset($cond['op_opciones']))
            $sel->where($cond['op_opciones']=="c"?"Prs_Ced=?":"CONCAT(Prs_Nom,' ',Prs_Ape)LIKE '%{$cond['search']}%'", $cond['search']);
        return $sel;
    }
    public function formatData($data, $type, $allData=null){
        return ($type=='I')?$data:$data;
    }
    public function sqlByNombre($id,$Par_Sql,$cond=null){
        if(is_object($Par_Sql)){ $sql=$Par_Sql; $Par_Sql=$cond; }else $sql='';
        switch($id){
            case "setEmpCod":
                $sql->where("$this->_name.Emp_Cod=?",$_SESSION['Ses_Emp_Cod']);
                //echo $sql.'<br/>';
                break;
            case "isNotProductor":
                $sql->joinLeft('productor_bana', "productor_bana.Prv_Cod=$this->_name.Prv_Cod", array() );
                $sql->where("productor_bana.Prd_Cod IS NULL");
                //echo $sql.'<br/>';
                break;
            case "isProductor":
                $sql->joinLeft('productor_bana', "productor_bana.Prv_Cod=$this->_name.Prv_Cod", array() );
                $sql->where("productor_bana.Prd_Cod IS NOT NULL");
                //echo $sql.'<br/>';
                break;
            default: $this->sqlByParams($id,$sql,array(
                    'isActive'=>"$this->_name.$this->_state='A'"
                ));
        }
        //echo $sql."<br/>";
        return $sql;
    }
    public function sqlByNumero($id,$Par_Sql){
        if(is_object($Par_Sql)){ $sql=$Par_Sql; $Par_Sql=$cond; }else $sql='';
        switch($id){
            case 0:
                $sql="";
                //echo $sql.'<br/>';
                break;
            default: throw new Exception ("No existe la sql denominada $id!");
        }
        //echo $sql."<br/>";
        return $sql;
    }
}