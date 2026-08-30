<?php
require_once(dirname(__file__)."/../DATA/libs/AbstractModel.php");
class cliente extends AbstractModel{
    protected $_name = 'cliente';
    protected $_primary = array('Cli_Cod');
    protected $_state = 'Cli_Est';
    //protected $_fields = array(); // se declara las filas que va a tener el arreglo en caso de insert o update //opcional

    /* crea una sql basica global para la tabla */
    public function _selectBasic($cond=null,$limits=false){
        return $this->select()->join(
                'persona', "persona.Prs_Cod=$this->_name.Prs_Cod",array('Ruc'=>'Prs_Ced','Cliente'=>"CONCAT(Prs_Nom,' ',Prs_Ape)",'Prs_Nom','Prs_Ape','Prs_Ced','Prs_Dir', 'Prs_Cor', 'Ide_Cod')
            )->joinLeft('identifica', "identifica.Ide_Cod=persona.Ide_Cod",array('Ide_Sri','Ide_Prc','Ide_Prv','Ide_Pre','Ide_Des'))
            ->where("$this->_name.Cli_Est = 'A'");
    }
    /* crea una sql standart para jqgrid, condiciones incluidas */
    public function _selectBasicGrid($cond=null,$limits=false){
        $sel=$this->_selectBasic();
        $this->sqlByNombre("setEmpCod", $sel);
        if(isset($cond['op_opciones']))
            $sel->where($cond['op_opciones']=="c"?"Prs_Ced=?":"CONCAT(Prs_Nom,' ',Prs_Ape)LIKE ?", $cond['op_opciones']=="c"?$cond['search']:"%{$cond['search']}%");
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
                $sql->where("$this->_name.Emp_Cod=?",$_SESSION['Ses_Emp_Cod']);
                //echo $this->getSqlString($sql)."<br/>";
                break;
            /*case "isActive":
                $sql->where("$this->_name.$this->_state='A'");
                //echo $this->getSqlString($sql)."<br/>";
                break;*/
            /*case "byConsF":
                $sql->join(array('idt' => 'identifica'), "idt.Ide_Cod = persona.Ide_Cod");
                //echo $this->getSqlString($sql)."<br/>";
                break;*/
            /*case "isConsF":
                $sql->where("idt.Ide_Prv = 07");
                //echo $this->getSqlString($sql)."<br/>";
                break;*/
            /*case "byVariosIngresos":
                $sql->where("persona.Prs_Ape = 'VARIOS INGRESOS'");
                //echo $this->getSqlString($sql)."<br/>";
                break;*/
            /*case "byVentas":
                $sql->join(array('vnts' => 'ventas'), "$this->_name.Cli_Cod = vnts.Cli_Cod");
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "byCcppC":
                $sql->join(array('ccppC' => 'ccpp_cobrar'), "vnts.Vet_Cod = ccppC.Vet_Cod");
                //echo $this->getSqlString($sql)."<br/>";
                break;
             case "byCcppCD":
                $sql->join(array('detccppCD' => 'det_ccpp_c'), "ccppC.Cpc_Cod = detccppCD.Cpc_Cod");
                //$sql->group('ccpp_cobrar.Cpc_Cod');
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "byAsient":
                $sql->join(array('asits' => 'asientos'), "detccppCD.Asi_Cod = asits.Asi_Cod", array('valorTotal'=>"SUM(asits.Asi_Val)"));
                $sql->group('asits.Com_Cod');
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "isAsientoCod":
                $sql->where('asits.Pld_Cod=?',"{$Par_Sql['Bak_Cod']}");
                //$sql->group('asientos.Pld_Cod');
                break;*/
            default:  $this->sqlByParams($id,$sql,array(
                    'isActive'=>"$this->_name.$this->_state='A'",
                    'isConsF'=>"identifica.Ide_Prv=07"
                )); //echo $this->getSqlString($sql)."<br/>";
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