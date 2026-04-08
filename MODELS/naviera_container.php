<?php 
use \Exception;
require_once(dirname(__file__)."/../DATA/libs/AbstractModel.php");
class naviera_container extends AbstractModel{
    protected $_name = 'naviera_container'; 
    protected $_primary = array('Nco_Cod');	
    protected $_state = 'Nco_Est';
    //protected $_fields = array(); // se declara las filas que va a tener el arreglo en caso de insert o update //opcional
    
    /* crea una sql basica global para la tabla */
    public function _selectBasic($cond=null,$limits=false){         
        return $this->select()
            ->joinLeft('exporta_planif_container', "exporta_planif_container.Nco_Cod=$this->_name.Nco_Cod", array('Pde_Cod'=>'Pde_Cod','Asignado'=>new Zend_Db_Expr("IF(exporta_planif_container.Pde_Cod IS NOT NULL,'S','N')"))); 
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
                $sql->where("Emp_Cod=?",$_SESSION['Ses_Emp_Cod']);
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "setVapor":                
                $sql->join('naviera_vapor', "naviera_vapor.Vap_Cod=$this->_name.Vap_Cod",array('Nav_Cod','Vap_Nom','Vap_Via','Vap_Ano','Vap_Sem','Vap_Cof'))
                    ->join('exporta_dist', "exporta_dist.Edi_Cod=naviera_vapor.Edi_Cod",array('Edi_Nom'))
                    ->join('exporta_dest', "exporta_dest.Exd_Cod=naviera_vapor.Exd_Cod",array('Exd_Nom'))
                    ->join('naviera_exporta', "naviera_exporta.Nav_Cod=naviera_vapor.Nav_Cod",array('Nav_Nom','Nav_Tip'));
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "setPlanif":                
                $sql->joinLeft('exporta_planif_det', "exporta_planif_det.Pde_Cod=exporta_planif_container.Pde_Cod", array('*'))
                    ->joinLeft('exporta_planif', "exporta_planif_det.Pln_Cod=exporta_planif.Pln_Cod", array('*'))
                    ->joinLeft('banano_marca', "banano_marca.Bam_Cod=exporta_planif.Bam_Cod", array('Bam_Nom'))
                    ->joinLeft('cliente', "cliente.Cli_Cod=exporta_planif.Cli_Cod", array('Cli_Tic'))
                    ->joinLeft('persona', "persona.Prs_Cod=cliente.Prs_Cod",array('Ruc'=>new Zend_Db_Expr("IF(Cli_Ruf IS NULL OR TRIM(Cli_Ruf)='',persona.Prs_Ced,Cli_Ruf)"),'Cliente'=>new Zend_Db_Expr("IF(Cli_Fac IS NULL OR TRIM(Cli_Fac)='',CONCAT(persona.Prs_Ape,' ',persona.Prs_Nom),Cli_Fac)"),'Prs_Nom','Prs_Ape','Prs_Ced','Prs_Dir','Ide_Cod'));
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "setCliente":                
                $sql->joinLeft('exporta_planif_det', "exporta_planif_det.Pde_Cod=exporta_planif_container.Pde_Cod", array())
                    ->joinLeft('exporta_planif', "exporta_planif_det.Pln_Cod=exporta_planif.Pln_Cod", array())
                    ->joinLeft('banano_marca', "banano_marca.Bam_Cod=exporta_planif.Bam_Cod", array('Bam_Nom'))
                    ->joinLeft('cliente', "cliente.Cli_Cod=exporta_planif.Cli_Cod", array('Cli_Tic'))
                    ->joinLeft('persona', "persona.Prs_Cod=cliente.Prs_Cod",array('Ruc'=>new Zend_Db_Expr("IF(Cli_Ruf IS NULL OR TRIM(Cli_Ruf)='',persona.Prs_Ced,Cli_Ruf)"),'Cliente'=>new Zend_Db_Expr("IF(Cli_Fac IS NULL OR TRIM(Cli_Fac)='',CONCAT(persona.Prs_Ape,' ',persona.Prs_Nom),Cli_Fac)"),'Prs_Nom','Prs_Ape','Prs_Ced','Prs_Dir','Ide_Cod'));
                //echo $this->getSqlString($sql)."<br/>";
                break;
            /*case "setTarjas":                
                $sql->joinLeft('productor_tarja', "productor_tarja.Nco_Cod=$this->_name.Nco_Cod", array(
                        
                    ))
                    ->group("$this->_name.Nco_Cod");
                //echo $this->getSqlString($sql)."<br/>";
                break; */
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