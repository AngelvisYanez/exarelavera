<?php 
require_once(dirname(__file__)."/../DATA/libs/AbstractModel.php");
class manifiesto_usuario extends AbstractModel{
    protected $_name = 'manifiesto_usuario'; 
    protected $_primary = array('Usu_Cod','Cli_Cod');	
    protected $_state = '';
    //protected $_fields = array(); // se declara las filas que va a tener el arreglo en caso de insert o update //opcional
    
    /* crea una sql basica global para la tabla */
    public function _selectBasic($cond=null,$limits=false){         
        return $this->select(true,array('*'))
            ->join('cliente', "cliente.Cli_Cod = $this->_name.Cli_Cod")->group($this->expr('cliente.Cli_Cod'))
            ->join('persona',"persona.Prs_Cod = cliente.Prs_Cod", array('nombre '=>"concat(persona.Prs_Nom,' ',persona.Prs_Ape)","persona.Prs_Ced"))
            ->joinLeft('manifiesto_plantas',"manifiesto_plantas.Pla_Cod = $this->_name.Pla_Cod", array('Pla_Nom'=>'manifiesto_plantas.Pla_Nom','Pla_Lic'=>'manifiesto_plantas.Pla_Lic')); //linea aumentada, si pasa algo quitarla
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
            case "getSaldoAnticipos":
                $sql->addCols('',array("CAST((SUM(Ant_Val)-IF(Ddc_Val IS NULL, 0, SUM(Ddc_Val))) AS DECIMAL(20, 2))as saldo"));
                $sql->joinLeft('anticipos_clientes',"anticipos_clientes.Cli_Cod = cliente.Cli_Cod", array(''));
                $sql->joinLeft('det_ant_cccc', "det_ant_cccc.Ant_Cod = anticipos_clientes.Ant_Cod",array(''));
            case "getNombrePlanta":               
                $sql->joinLeft('manifiesto_plantas', "manifiesto_plantas.Pla_Cod = $this->_name.Pla_Cod", array('Pla_Nom'=>'manifiesto_plantas.Pla_Nom','Pla_Lic'=>'manifiesto_plantas.Pla_Lic'));
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