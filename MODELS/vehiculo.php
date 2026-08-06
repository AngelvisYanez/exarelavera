<?php
require_once(dirname(__file__)."/../DATA/libs/AbstractModel.php");
class vehiculo extends AbstractModel{
    protected $_name = 'vehiculo';
    protected $_primary = array('Veh_Cod');
    protected $_state = 'Veh_Est';
    //protected $_fields = array(); // se declara las filas que va a tener el arreglo en caso de insert o update //opcional

    /* crea una sql basica global para la tabla */
    public function _selectBasic($cond=null){
        return $this->select()
            ->joinLeft('proveedore', "proveedore.Prv_Cod=$this->_name.Prv_Cod", array('Prv_Tic','Prv_Con','Prv_Esp'))
            ->joinLeft('persona', "persona.Prs_Cod=proveedore.Prs_Cod",array('Proveedor'=>$this->expr("IF(Prv_Com IS NULL OR Prv_Com='',".$this->concat(array('persona.Prs_Ape','persona.Prs_Nom')).",Prv_Com)"),'Ruc'=>'Prs_Ced','Prs_Nom','Prs_Ape','Prs_Ced','Prs_Dir'))
        ;
    }
    /* crea una sql standart para jqgrid, condiciones incluidas */
    public function _selectBasicGrid($cond=null){
        $sel=$this->_selectBasic();
        $this->sqlByNombre("setEmpCod", $sel);
        if(isset($cond['isActive']) && $cond['isActive']=='S') $this->sqlByNombre("isActive", $sel);
        if(isset($cond['op_opciones'])){
            switch($cond['op_opciones']){
                case 'n': $this->sqlByNombre("isPrvCodNull", $sel); break;
                case 'c': $sel->where("persona.Prs_Ced LIKE ?","$cond[search]%"); break;
                case 'p': $sel->where("$this->_name.Veh_Pla LIKE ?","$cond[search]%"); break;
                default: $sel->where("(UPPER(CONCAT(persona.Prs_Ape,' ',persona.Prs_Nom)) LIKE UPPER(?)) OR (UPPER(Prv_Com) LIKE UPPER(?) )","%$cond[search]%"); break;
            }
        }
        return $sel;
    }
    /* formatea el array para insert o update */
    public function formatData($data, $type, $allData=null){
        if(isset($data['Prv_Cod'])&&empty($data['Prv_Cod']))$data['Prv_Cod']=null;
        if($type=='I'&&!isset($data['Emp_Cod'])) $data['Emp_Cod']=$_SESSION['Ses_Emp_Cod'];
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
            case "setLastChofer":
                $viaje=$this->select(false)->from('viaje',array('Cho_Cod'))->where("viaje.{$this->_primary[0]}=$this->_name.{$this->_primary[0]}")->order("Via_Cod DESC")->limit(1);
                $sql->joinLeft('chofer',"chofer.Cho_Cod=(".$this->toStr($viaje).")",array('Cho_Cod','Cho_Tli','Cho_Tel'))
                    ->joinLeft(array('persona_cho'=>'persona'),"persona_cho.Prs_Cod=chofer.Prs_Cod",array("Ruc_Chofer"=>'Prs_Ced',"Chofer"=>$this->expr($this->concat(array("Prs_Ape","Prs_Nom"),'persona_cho')) ));
                //echo $this->getSqlString($sql)."<br/>";
                break;
            default: $this->sqlByParams($id,$sql,array(
                    'isActive'=>"$this->_name.$this->_state='A'",
                    'isPrvCodNull'=>"$this->_name.Prv_Cod IS NULL"
                )); //echo $this->getSqlString($sql)."<br/>";
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