<?php
use \Exception;
require_once(dirname(__file__)."/../DATA/libs/AbstractModel.php");
class viaje extends AbstractModel{
    protected $_name = 'viaje';
    protected $_primary = array('Via_Cod');
    protected $_state = 'Via_Est';
    protected $_defaults = array('Via_Cod','Ori_Cod','Des_Cod');
    //protected $_fields = array(); // se declara las filas que va a tener el arreglo en caso de insert o update //opcional

    /* crea una sql basica global para la tabla */
    public function _selectBasic($cond=null){
        return $this->select()
            ->addCols('', array('Via_Dia'=>$this->expr("DAYOFWEEK(Via_Fec)-1"),'Via_Fac'=>$this->expr("IF(ISNULL($this->_name.Vet_Cod),'NF','F')") ))
            ->join('cliente',"$this->_name.Cli_Cod = cliente.Cli_Cod",array())
            ->join('persona', "persona.Prs_Cod=cliente.Prs_Cod",array('Ruc'=>new Zend_Db_Expr("IF(Cli_Ruf IS NULL OR TRIM(Cli_Ruf)='',persona.Prs_Ced,Cli_Ruf)"),'Cliente'=>new Zend_Db_Expr("IF(Cli_Fac IS NULL OR TRIM(Cli_Fac)='',CONCAT(persona.Prs_Ape,' ',persona.Prs_Nom),Cli_Fac)"),'Prs_Nom','Prs_Ape','Prs_Ced','Prs_Dir','Ide_Cod'))
            ->joinLeft('chofer', "$this->_name.Cho_Cod=chofer.Cho_Cod", array('Cho_Tli','Cho_Tel','Cho_Est'))
            ->joinLeft(array('persona_cho'=>'persona'),"persona_cho.Prs_Cod=chofer.Prs_Cod",array('Con_Duc'=>$this->expr($this->concat(array("Prs_Ape","Prs_Nom"),'persona_cho')),"Ruc_Chofer"=>'Prs_Ced',"Chofer"=>$this->expr($this->concat(array("Prs_Ape","Prs_Nom"),'persona_cho')) ))
            ->joinLeft('vehiculo',"vehiculo.Veh_Cod=$this->_name.Veh_Cod", array('Aut_Mot'=>'Veh_Pla','Veh_Pla','Veh_Mar','Veh_Col','Prv_Cod'))
            ->joinLeft('proveedore',"proveedore.Prv_Cod=vehiculo.Prv_Cod", array())
            ->joinLeft(array('persona_prv'=>'persona'),"persona_prv.Prs_Cod=proveedore.Prs_Cod", array("Ruc_Provee"=>'Prs_Ced',"Proveedor"=>$this->expr($this->concat(array("Prs_Ape","Prs_Nom"),'persona_prv')) ))
            ->join('cargamento',"cargamento.Car_Cod=$this->_name.Car_Cod", array('Pro_Cod','Car_Cod','Car_Des'))
            ->join('modo_trabajo',"modo_trabajo.Mot_Cod=$this->_name.Mot_Cod", array('Mot_Cod','Mot_Des'))
            ->joinLeft(array('origen'=>'viaje_lugar'),"origen.Vlu_Cod=$this->_name.Ori_Cod", array('Ori_Zon'=>'Vlu_Zon','Ori_Aco'=>'Vlu_Aco'))
            ->joinLeft(array('destino'=>'viaje_lugar'),"destino.Vlu_Cod=$this->_name.Des_Cod", array('Des_Zon'=>'Vlu_Zon','Des_Aco'=>'Vlu_Aco'))
        ;
    }
    /* crea una sql standart para jqgrid, condiciones incluidas */
    public function _selectBasicGrid($cond=null){
        $sel=$this->_selectBasic();
        $this->sqlByNombre("setEmpCod", $sel);
        if($this->hasVal($cond,'Prv_Cod')) $sel->where("proveedore.Prv_Cod=?",$cond['Prv_Cod']);
        if($this->hasVal($cond,'Cli_Cod')) $sel->where("cliente.Cli_Cod=?",$cond['Cli_Cod']);
        if($this->hasVal($cond,'Cho_Cod')) $sel->where("chofer.Cho_Cod=?",$cond['Cho_Cod']);
        if($this->hasVal($cond,'Via_Cod_Used')) $this->sqlByNombre("notInViaCods",$sel,$cond);
        if($this->hasVal($cond,'Via_Sem')){
            $sel->where("viaje.Via_Sem=?",$cond['Via_Sem']);
            if($this->hasVal($cond,'Via_Year')) $sel->where("YEAR(viaje.Via_Fec)=?",$cond['Via_Year']);
        }else
            if(!$this->hasVal($cond,'byDates')||$cond['byDates']=='S') $this->sqlByNombre("byDates", $sel, $cond);
        if(isset($cond['op_opciones']))
            switch($cond['op_opciones']){
                case 'F':  $this->sqlByNombre("hasVetCod", $sel); break;
                case 'NF': $this->sqlByNombre("notHasVetCod", $sel); break;
                default:  break;
            }
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
            case "byDates":
                if($this->hasVal($Par_Sql, 'Fec_Ini')) $sql->where("$this->_name.Via_Fec>=?",$Par_Sql['Fec_Ini']);
                if($this->hasVal($Par_Sql, 'Fec_Fin')) $sql->where("$this->_name.Via_Fec<=?",$Par_Sql['Fec_Fin']);
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "notInViaCods":
                if($this->hasVal($cond,'Via_Cod_Used')) $sql->where("$this->_name.Via_Cod NOT IN(".(is_array($Par_Sql['Via_Cod_Used'])?implode(',',$Par_Sql['Via_Cod_Used']):$Par_Sql['Via_Cod_Used']).")");
                //echo $this->getSqlString($sql)."<br/>";
                break;
            /*case "isActive":
                $sql->where("$this->_name.$this->_state='A'");
                //echo $this->getSqlString($sql)."<br/>";
                break;*/
            /*case "isCliente":
                $sql->where("viaje.Cli_Cod='$Cli_Cod'");
                //echo $this->getSqlString($sql)."<br/>";
                break;*/
            /*case "isPersona":
                $sql->where("persona.Prs_Ced='$Prs_Ced'");
                //echo $this->getSqlString($sql)."<br/>";
                break;*/
            /*case "isNullVenta":
                $sql->where("$this->_name.Vet_Cod IS NULL");
                //echo $this->getSqlString($sql)."<br/>";
                break;*/
            /*case "byCliente":
                $sql->join(array('clie'=>'cliente'),"$this->_name.Cli_Cod = clie.Cli_Cod");
                //echo $this->getSqlString($sql)."<br/>";
                break;*/
            /*case "byPersona":
                $sql->join(array('pers'=>'persona'),"chof.Prs_Cod=pers.Prs_Cod", array('Con_Duc'=>"CONCAT(Prs_Nom,' ',Prs_Ape)"));
                //echo $this->getSqlString($sql)."<br/>";
                break;*/
            /*case "byChofer":
                $sql->joinLeft(array('chof'=>'chofer'),"$this->_name.Cho_Cod=chof.Cho_Cod");
                //echo $this->getSqlString($sql)."<br/>";
                break;*/
            /*case "byVehiculo":
                $sql->joinLeft(array('veh'=>'vehiculo'),"veh.Veh_Cod=$this->_name.Veh_Cod", array('Aut_Mot'=>'Veh_Pla'));
                //echo $this->getSqlString($sql)."<br/>";
                break;*/
            /*case "byCargamento":
                $sql->join(array('carg'=>'cargamento'),"carg.Car_Cod=$this->_name.Car_Cod", array('Car_Cod','Car_Des'));
                //echo $this->getSqlString($sql)."<br/>";array('Prf_Est'=>"IF (proformas.Prf_Est='A', 'Activa', 'Inactiva')")
                break;*/
            /*case "byModoTrabajo":
                $sql->join(array('modtrabajo'=>'modo_trabajo'),"modtrabajo.Mot_Cod=$this->_name.Mot_Cod", array('Mot_Cod','Mot_Des'));
                //echo $this->getSqlString($sql)."<br/>";modo_trabajo
                break;*/
            /*case "notVenta":
                $sql->where("viaje.Ved_Cod IS NULL");
                break;*/
            /*case "byGroup":
                $sql->group(array('cliente.Prs_Cod'));
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "byOrder":
                $sql->order(array('pers.Prs_Ape'));
                //echo $this->getSqlString($sql)."<br/>";
                break;*/
            default: $this->sqlByParams($id,$sql,array(
                    'isActive'=>"$this->_name.$this->_state='A'",
                    "hasVetCod"=>"$this->_name.Vet_Cod IS NOT NULL",
                    "notHasVetCod"=>"$this->_name.Vet_Cod IS NULL"
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