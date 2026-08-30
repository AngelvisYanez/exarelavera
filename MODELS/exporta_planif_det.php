<?php 
require_once(dirname(__file__)."/../DATA/libs/AbstractModel.php");
class exporta_planif_det extends AbstractModel{
    protected $_name = 'exporta_planif_det'; 
    protected $_primary = array('Pde_Cod');	
    protected $_state = 'Pln_Est';
    //protected $_fields = array(); // se declara las filas que va a tener el arreglo en caso de insert o update //opcional
    
    /* crea una sql basica global para la tabla */
    public function _selectBasic($cond=null,$limits=false){         
        return $this->select()
            ->join('exporta_planif', "exporta_planif.Pln_Cod=$this->_name.Pln_Cod", array('Cli_Cod','Bam_Cod','Exd_Cod','Pln_Fec','Pln_Ano','Pln_Sem'))
            ->join('cliente', "cliente.Cli_Cod=exporta_planif.Cli_Cod", array('Cli_Tic')); 
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
                $sql->where("cliente.Emp_Cod=?",$_SESSION['Ses_Emp_Cod']);
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "isActive":
                $sql->where("$this->_name.$this->_state='A'");
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "setPersona":                
                $sql->join('persona', "persona.Prs_Cod=cliente.Prs_Cod",array('Ruc'=>new Zend_Db_Expr("IF(Cli_Ruf IS NULL OR TRIM(Cli_Ruf)='',persona.Prs_Ced,Cli_Ruf)"),'Cliente'=>new Zend_Db_Expr("IF(Cli_Fac IS NULL OR TRIM(Cli_Fac)='',CONCAT(persona.Prs_Ape,' ',persona.Prs_Nom),Cli_Fac)"),'Prs_Nom','Prs_Ape','Prs_Ced','Prs_Dir','Ide_Cod')); 
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "countContenedores": 
                $sql=$this->select(true,array('total'=>new Zend_Db_Expr("COUNT(*)"),'suma'=>new Zend_Db_Expr("SUM(naviera_container.Nco_Can)")))
                    ->join('exporta_planif_container', "exporta_planif_container.Pde_Cod=$this->_name.Pde_Cod", array())
                    ->join('naviera_container', "naviera_container.Nco_Cod=exporta_planif_container.Nco_Cod", array())
                    ->where("exporta_planif_det.Pde_Cod=?",$Par_Sql[0]); 
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