<?php
require_once(dirname(__file__)."/../DATA/libs/AbstractModel.php");
class persona extends AbstractModel{
    protected $_name = 'persona';
    protected $_primary = array('Prs_Cod');
    protected $_state = 'Prs_Est';
    //protected $_fields = array(); // se declara las filas que va a tener el arreglo en caso de insert o update //opcional

    /* crea una sql basica global para la tabla */
    public function _selectBasic($cond=null,$limits=false){
        return $this->select()->addCols('', array('Persona'=>"CONCAT(Prs_Nom,' ',Prs_Ape)"))
            ->joinLeft('identifica', "identifica.Ide_Cod=persona.Ide_Cod",array('Ide_Sri','Ide_Prc','Ide_Prv','Ide_Pre','Ide_Des'))
        ;
    }
    /* crea una sql standart para jqgrid, condiciones incluidas */
    public function _selectBasicGrid($cond=null,$limits=false){
        $sel=$this->_selectBasic();
        if(isset($cond['op_opciones']))
            $sel->where($cond['op_opciones']=="c"?"Prs_Ced=?":"CONCAT(Prs_Nom,' ',Prs_Ape)LIKE '%{$cond['search']}%'", $cond['search']);
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
            /*case "setEmpCod":
                $sql->where("Emp_Cod=?",$_SESSION['Ses_Emp_Cod']);
                //echo $this->getSqlString($sql)."<br/>";
                break;*/
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
            /*case "isActiveIdt":
                $sql->where("idt.Ide_Est = 'A'");
                //echo $this->getSqlString($sql)."<br/>";
                break;*/
            /*case "byVariosIngresos":
                $sql->where("$this->_name.Prs_Ape = 'VARIOS INGRESOS'");
                //echo $this->getSqlString($sql)."<br/>";
                break;*/
            default: $this->sqlByParams($id,$sql,array(
                    'isActive'      =>"$this->_name.$this->_state='A'",
                    'isConsF'       =>"identifica.Ide_Prv=07",
                    'isActiveIdt'   =>"idt.Ide_Est = 'A'"
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