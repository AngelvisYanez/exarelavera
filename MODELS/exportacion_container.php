<?php 
require_once(dirname(__file__)."/../DATA/libs/AbstractModel.php");
class exportacion_container extends AbstractModel{
    protected $_name = 'exportacion_container';
    protected $_primary = array('Exc_Cod');
    protected $_state = 'Exc_Est';
    
    public function _selectBasic($cond=null,$limits=false){
        return $this->select(); 
    }
    public function _selectBasicGrid($cond=null,$limits=false){
        $sel=$this->select(true,array("*","IF(COALESCE((SELECT COUNT(productor_tarja.Exc_Cod) AS total FROM productor_tarja WHERE exportacion_container.Exc_Cod=productor_tarja.Exc_Cod GROUP BY productor_tarja.Exc_Cod),0)>0,'s','n')AS tarja"));
        $this->sqlByNombre("setEmpCod", $sel);  
        if(isset($cond['op_opciones']))
            $sel->where($cond['op_opciones']=="b"?"(Exc_Vap LIKE ?)":"Exc_Sem= ?", $cond['op_opciones']=="b"?"%{$cond['search']}%":$cond['search']);
        $sel->group("$this->_name.Exc_Cod");
        return $sel; 
    }
    public function formatData($data, $type, $allData=null){
        $format=array(
            'Exc_Ano'=>$data['Exc_Ano'],
            'Exc_Sem'=>$data['Exc_Sem'],
            'Exc_Vap'=>$data['Exc_Vap'],
            'Exc_Con'=>$data['Exc_Con'],
            'Exc_Ter'=>$data['Exc_Ter'],
            'Exc_Can'=>$data['Exc_Can'],
            'Exc_Bod'=>$data['Exc_Bod'],
            'Exc_Pto'=>$data['Exc_Pto'],
            'Exc_Zon'=>$data['Exc_Zon'],
            'Exc_Obs'=>$data['Exc_Obs'],
            'Exc_Fec'=>$data['Exc_Fec'],
            'Exc_Pla'=>$data['Exc_Pla'],
            'Exc_Cho'=>$data['Exc_Cho'],
            'Exc_Aco'=>$data['Exc_Aco']
        );
        if($type!='I'){ 
            $format['Exc_Cod']=$data['Exc_Cod'];
        }else{
            $format['Emp_Cod']=$_SESSION['Ses_Emp_Cod'];
        }
        return $format;
    }
    public function sqlByNombre($id,$Par_Sql,$cond=null){
        $sql="";
        switch($id){
            case "":
                $sql="";
                //echo $sql.'<br/>';
                break;
            case "setEmpCod":                
                $Par_Sql->where("$this->_name.Emp_Cod=?",$_SESSION['Ses_Emp_Cod']);
                //echo $sql.'<br/>';
                break;
            default: throw new Exception ("No existe la sql denominada $id!");
        }
        //echo $sql."<br/>";
        return $sql;
    }
    public function sqlByNumero($id,$Par_Sql,$cond=null){
        $sql="";
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