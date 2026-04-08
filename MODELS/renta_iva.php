<?php
use \Exception;
require_once(dirname(__file__)."/../DATA/libs/AbstractModel.php");
class renta_iva extends AbstractModel{
    protected $_name = 'renta_iva';
    protected $_primary = array('Ren_Cod');
    protected $_state = 'Ren_Est';
    //protected $_fields = array(); // se declara las filas que va a tener el arreglo en caso de insert o update //opcional

    /* crea una sql basica global para la tabla */
    public function _selectBasic($cond=null,$limits=false){
        return $this->select(true,array(
            'Adq_Cod','Ren_Cod','Ren_Sri','Ren_Con','Ren_Por','Ren_Tip','Ren_Ret','Ren_Est',
            'Ren_Rete'=>$this->expr("IF(renta_iva.Ren_Ret='R', 'RENTA','IVA')"),
            'Ren_Tipo'=>$this->expr("IF(renta_iva.Ren_Tip='B','BIENES','SERVICIO')"),
            'Ren_Esta'=>$this->expr("IF(renta_iva.Ren_Est='A','Activo','Anulado')")
        ));
    }
    /* crea una sql standart para jqgrid, condiciones incluidas */
    public function _selectBasicGrid($cond=null,$limits=false){
        $sel=$this->_selectBasic();
        $this->sqlByNombre("setEmpCod", $sel);
        if(isset($cond['op_opciones']))
            $sel->where($cond['op_opciones']=="c"?"Ren_Sri LIKE '%{$cond['search']}%'":"Ren_Con LIKE '%{$cond['search']}%'", $cond['search']);
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
            case "byOrder":
                $sql->order("$this->_name.Ren_Sri DESC");
                //echo $this->getSqlString($sql)."<br/>";
                //ChromePhp::log($this->getSqlString($sql));
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