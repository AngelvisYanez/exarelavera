<?php 
use \Exception;
require_once(dirname(__file__)."/../DATA/libs/AbstractModel.php");
class naviera_vapor extends AbstractModel{
    protected $_name = 'naviera_vapor'; 
    protected $_primary = array('Vap_Cod');	
    protected $_state = 'Vap_Est';
    //protected $_fields = array(); // se declara las filas que va a tener el arreglo en caso de insert o update //opcional
    
    /* crea una sql basica global para la tabla */
    public function _selectBasic($cond=null,$limits=false){         
        return $this->select()
            ->join('naviera_exporta',"naviera_exporta.Nav_Cod=$this->_name.Nav_Cod",array('Nav_Nom','Nav_Tip'))
            ->join('exporta_dist',"exporta_dist.Edi_Cod=$this->_name.Edi_Cod",array('Edi_Nom','Edi_Sri'))
            ->join('exporta_dest',"exporta_dest.Exd_Cod=$this->_name.Exd_Cod",array('Exd_Nom','Exd_Sri'))
            ->join('pais',"pais.Pas_Cod=exporta_dest.Pas_Cod",array('Pas_Nom','Pas_Sri')); 
    }
    /* crea una sql standart para jqgrid, condiciones incluidas */
    public function _selectBasicGrid($cond=null,$limits=false){ 
        $sel=$this->_selectBasic();    
        $this->sqlByNombre("setEmpCod", $sel);
        if($this->hasVal($cond,'Vap_Ano')) $sel->where("$this->_name.Vap_Ano=?",$cond['Vap_Ano']);
        if($this->hasVal($cond,'Vap_Sem')) $sel->where("$this->_name.Vap_Sem=?",$cond['Vap_Sem']);
        if($this->hasVal($cond,'search')) $sel->where("$this->_name.Vap_Nom LIKE ?","%$cond[search]%");
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
                $sql->where("naviera_exporta.Emp_Cod=?",$_SESSION['Ses_Emp_Cod']);
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "isActive":
                $sql->where("$this->_name.$this->_state='A'");
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "countContenedores": 
                $sql=$this->select(true,array('total'=>new Zend_Db_Expr("COUNT(*)")))
                    ->join('naviera_container', "naviera_container.Vap_Cod=$this->_name.Vap_Cod", array())
                    ->where("naviera_container.Vap_Cod=?",$Par_Sql[0]); 
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
                $sql="";
                //echo $this->getSqlString($sql)."<br/>";
                break;
            default: throw new Exception ("No existe la sql numero $id!");
        }
        //echo $this->getSqlString($sql)."<br/>";
        return $sql;
    }
}