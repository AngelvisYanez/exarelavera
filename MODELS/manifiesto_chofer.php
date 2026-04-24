<?php 
use \Exception;
require_once(dirname(__file__)."/../DATA/libs/AbstractModel.php");
class manifiesto_chofer extends AbstractModel{
    protected $_name = 'manifiesto_chofer'; 
    protected $_primary = array('Cho_Cod','Pla_Cod');	
    protected $_state = '';
    //protected $_fields = array(); // se declara las filas que va a tener el arreglo en caso de insert o update //opcional
    
    /* crea una sql basica global para la tabla */
    public function _selectBasic($cond=null,$limits=false){         
        return $this->select(true,array('*'))
            ->join('manifiesto_plantas', "manifiesto_plantas.Pla_Cod = $this->_name.Pla_Cod", array('manifiesto_plantas.Cli_Cod'))            
            ->join('chofer',"chofer.Cho_Cod = $this->_name.Cho_Cod", array('*'))
            ->join('persona',"persona.Prs_Cod = chofer.Prs_Cod", array('nombre'=>"concat(persona.Prs_Nom,' ',persona.Prs_Ape)","persona.Prs_Ced"));            
    }
    /* crea una sql standart para jqgrid, condiciones incluidas */
    public function _selectBasicGrid($cond=null,$limits=false){ 
        $sel=$this->_selectBasic();        
        $this->sqlByNombre("setEmpCod", $sel);
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
             case "setEmpCod":
                $sql->where("cliente.Emp_Cod=?",$_SESSION['Ses_Emp_Cod']);
                //echo $this->getSqlString($sql)."<br/>";
                break; 
            case "":
                $sql="";
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
            /* Igual que manifiesto_transporte.1: subconsulta "total" = manifiestos activos en ruta (no cerrados con GS) por Cho_Cod. */
            case 1:
                /* Mismo alcance que selectWhere(Cli_Cod, Cho_Est): no filtrar por una sola Pla_Cod (si no, el select puede quedar vacío). */
                $cli = isset($Par_Sql['Cli_Cod']) ? intval($Par_Sql['Cli_Cod']) : 0;
                $sql = "SELECT COALESCE((
                    SELECT COUNT(*) FROM manifiesto m
                    WHERE m.Cho_Cod = chofer.Cho_Cod
                      AND m.Man_Tes NOT LIKE '%GS%'
                      AND m.Man_Est = 'A'
                ), 0) AS total,
                manifiesto_chofer.*, chofer.*, manifiesto_plantas.Cli_Cod,
                CONCAT(IFNULL(persona.Prs_Nom,''), ' ', IFNULL(persona.Prs_Ape,'')) AS nombre, persona.Prs_Ced
                FROM manifiesto_chofer
                INNER JOIN manifiesto_plantas ON manifiesto_plantas.Pla_Cod = manifiesto_chofer.Pla_Cod
                INNER JOIN chofer ON chofer.Cho_Cod = manifiesto_chofer.Cho_Cod
                INNER JOIN persona ON persona.Prs_Cod = chofer.Prs_Cod
                WHERE chofer.Cho_Est = 'A'
                  AND manifiesto_plantas.Cli_Cod = $cli";
                break;
            default: throw new Exception ("No existe la sql numero $id!");
        }
        //echo $this->getSqlString($sql)."<br/>";
        return $sql;
    }
}