<?php
require_once(dirname(__file__)."/../DATA/libs/AbstractModel.php");
class det_actividad_labor extends AbstractModel{
    protected $_name = 'det_actividad_labor';
    protected $_primary = array('Det_Cod');
    protected $_state = '';
    //protected $_fields = array(); // se declara las filas que va a tener el arreglo en caso de insert o update //opcional

    /* crea una sql basica global para la tabla */
    public function _selectBasic($cond=null,$limits=false){
        return $this->select();
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
            case "isActive":
                $sql->where("$this->_name.$this->_state='A'");
                //echo $this->getSqlString($sql)."<br/>";
                break;

            case "byLabor":
                $sql->join(array('lbr'=>'labores'),"lbr.Lab_Cod = $this->_name.Lab_Cod");
                //echo $this->getSqlString($sql)."<br/>";tipo_compr
                $sql->group("$this->_name.Det_Cod");
                break;

            case "byTipoPagoLabor":
                $sql->join(array('tpl'=>'tipo_pago_labor'),"tpl.Tpg_Cod = lbr.Tpg_Cod");
                //echo $this->getSqlString($sql)."<br/>";tipo_compr
                break;
             case "byPersonal":
                $sql->join(array('prsnl'=>'personal'),"prsnl.Per_Cod = $this->_name.Per_Cod");
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "byTrabajador":
                $sql->join(array('prsn'=>'persona'),"prsn.Prs_Cod = prsnl.Prs_Cod", array('personal'=>"CONCAT(prsn.Prs_Ape,' ',prsn.Prs_Nom)","prsn.Prs_Cod, prsn.Prs_Cor, prsn.Prs_Ced, prsn.Prs_Ape, prsn.Prs_Nom, prsn.Prs_Dir"));
                //echo $this->getSqlString($sql)."<br/>";
                break;
            default: throw new Exception ("No existe la sql denominada $id!");
        }
        //echo $this->getSqlString($sql)."<br/>";
        return $sql;
    }
    /* crea sentencia por id numero sql */
    public function sqlByNumero($id,$Par_Sql,$cond=null){
        $sql=(is_object($Par_Sql)?$Par_Sql:'');
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