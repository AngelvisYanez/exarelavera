<?php
use \Exception;
require_once(dirname(__file__)."/../DATA/libs/AbstractModel.php");
class productor_bana extends AbstractModel{
    protected $_name = 'productor_bana';
    protected $_primary = array('Prd_Cod');
    protected $_state = 'Prd_Est';

    public function _selectBasic($cond=null,$limits=false){
        return $this->select()->join(
                'proveedore', "proveedore.Prv_Cod=$this->_name.Prv_Cod", array()
            )->join(
                'persona', "persona.Prs_Cod=proveedore.Prs_Cod",
                array('Productor'=>$this->expr($this->concat(array('Prs_Nom','Prs_Ape'))),'Prs_Nom','Prs_Ape','Prs_Ced','Prs_Dir')
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
        return ($type=='I')?array(
            'Prv_Cod'=>$data['Prv_Cod'], 'Prd_Est'=>'A',
            'Prd_Cup'=>$data['Prd_Cup'], 'Prd_Cau'=>$data['Prd_Cau']
        ):$data;
    }
    public function sqlByNombre($id,$Par_Sql,$cond=null){
        if(is_object($Par_Sql)){ $sql=$Par_Sql; $Par_Sql=$cond; }else $sql='';
        switch($id){
            case "":
                $sql="";
                //echo $sql.'<br/>';
                break;
            case "setEmpCod":
                $sql->where("proveedore.Emp_Cod=?",$_SESSION['Ses_Emp_Cod']);
                //echo $sql.'<br/>';
                break;
            case "isActive":
                $sql->where("$this->_name.$this->_state=?",'A');
                //echo $sql.'<br/>';
                break;
            case "setAcopio":
                $sql->join('acopio', "acopio.Prd_Cod=$this->_name.Prd_Cod", array('Aco_Cod','Aco_Des'));
                //echo $sql.'<br/>';
                break;
            default: throw new Exception ("No existe la sql denominada $id!");
        }
        //echo $sql."<br/>";
        return $sql;
    }
    public function sqlByNumero($id,$Par_Sql,$cond=null){
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