<?php 
use \Exception;
require_once(dirname(__file__)."/../DATA/libs/AbstractModel.php");
class exporta_planif extends AbstractModel{
    protected $_name = 'exporta_planif'; 
    protected $_primary = array('Pln_Cod');	
    //protected $_state = '';
    //protected $_fields = array(); // se declara las filas que va a tener el arreglo en caso de insert o update //opcional
    
    /* crea una sql basica global para la tabla */
    public function _selectBasic($cond=null,$limits=false){         
        return $this->select()
            ->join('exporta_dest',"exporta_dest.Exd_Cod=$this->_name.Exd_Cod",array('Exd_Nom','Exd_Sri'))
            ->join('pais',"pais.Pas_Cod=exporta_dest.Pas_Cod",array('Pas_Nom','Pas_Sri'))
            ->join('banano_marca',"banano_marca.Bam_Cod=$this->_name.Bam_Cod",array('Bam_Nom','Bam_Des','Bam_Tam'))
            ->join('cliente', "cliente.Cli_Cod=$this->_name.Cli_Cod", array('Cli_Tic'))
            ->join('persona', "persona.Prs_Cod=cliente.Prs_Cod",array('Ruc'=>new Zend_Db_Expr("IF(Cli_Ruf IS NULL OR TRIM(Cli_Ruf)='',persona.Prs_Ced,Cli_Ruf)"),'Cliente'=>new Zend_Db_Expr("IF(Cli_Fac IS NULL OR TRIM(Cli_Fac)='',CONCAT(persona.Prs_Ape,' ',persona.Prs_Nom),Cli_Fac)"),'Prs_Nom','Prs_Ape','Prs_Ced','Prs_Dir','Ide_Cod')); 
    }
    /* crea una sql standart para jqgrid, condiciones incluidas */
    public function _selectBasicGrid($cond=null,$limits=false){ 
        $sel=$this->_selectBasic(); 
        $this->sqlByNombre("setEmpCod", $sel);
        if($this->hasVal($cond,'Pln_Ano')) $sel->where("$this->_name.Pln_Ano=?",$cond['Pln_Ano']);
        if($this->hasVal($cond,'Pln_Sem')) $sel->where("$this->_name.Pln_Sem=?",$cond['Pln_Sem']);
        if($this->hasVal($cond,'Bam_Cod')) $sel->where("$this->_name.Bam_Cod=?",$cond['Bam_Cod']);
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
                $sql->where("banano_marca.Emp_Cod=?",$_SESSION['Ses_Emp_Cod']);
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
                $sql="";
                //echo $this->getSqlString($sql)."<br/>";
                break;
            default: throw new Exception ("No existe la sql numero $id!");
        }
        //echo $this->getSqlString($sql)."<br/>";
        return $sql;
    }
}