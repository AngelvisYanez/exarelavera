<?php
require_once(dirname(__file__)."/../DATA/libs/AbstractModel.php");
class actividad_labor extends AbstractModel{
    protected $_name = 'actividad_labor';
    protected $_primary = array('Act_Cod');
    protected $_state = 'Act_Est';
    //protected $_fields = array(); // se declara las filas que va a tener el arreglo en caso de insert o update //opcional

    /* crea una sql basica global para la tabla */
    public function _selectBasic($cond=null,$limits=false){
        return $this->select();
    }
    /* crea una sql standart para jqgrid, condiciones incluidas */
    public function _selectBasicGrid($cond=null,$limits=false){
        $sel=$this->_selectBasic();
        //$this->sqlByNombre("setEmpCod", $sel);
        if($cond['op_opciones']){
            if($cond['op_opciones']=="fnc"){
                 $sel->where("Fnc_Des LIKE '%{$cond['Cod_Bus']}%'");
            }elseif ($cond['op_opciones']=='lbr'){
                 $sel->where("CONCAT(prsn.Prs_Nom,' ',prsn.Prs_Ape)LIKE '%{$cond['Cod_Bus']}%'");
            }elseif ($cond['op_opciones']=='fch'){
                $sel->where("Act_Fec BETWEEN '$cond[Fec_Ini] 00:00:00' AND '$cond[Fec_Fin] 23:59:59'",null);
            }else{

            }

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
                $sql->where("Emp_Cod=?",$_SESSION['Ses_Emp_Cod']);
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "isActive":
                $sql->where("$this->_name.$this->_state='A'");
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "byPeriodo":
                $sql->join(array('prdo'=>'perio_cont'),"prdo.Pec_Cod = $this->_name.Pec_Cod");
                //echo $this->getSqlString($sql)."<br/>";tipo_compr
                break;
            case "byFinca":
                $sql->join(array('fnc'=>'finca_actividad'),"fnc.Fnc_Cod = $this->_name.Fnc_Cod");
                //echo $this->getSqlString($sql)."<br/>";tipo_compr
                break;
            case "byDetAct":
                $sql->join(array('dfnc'=>'det_actividad_labor'),"dfnc.Act_Cod = $this->_name.Act_Cod");
                //echo $this->getSqlString($sql)."<br/>";tipo_compr

                $sql->group("dfnc.Det_Cod");
                break;
            case "byLabor":
                $sql->join(array('lbr'=>'labores'),"lbr.Lab_Cod = dfnc.Lab_Cod");
                //echo $this->getSqlString($sql)."<br/>";tipo_compr
                //$sql->group("$this->_name.Det_Cod");
                break;
             case "byUsuarios":
                $sql->join(array('usu'=>'usuarios'),"usu.Usu_Cod = $this->_name.Usu_Cod");
                //echo $this->getSqlString($sql)."<br/>";tipo_compr
                break;
            case "byPersonaUsu":
                $sql->join(array('prs'=>'persona'),"prs.Prs_Cod = usu.Prs_Cod", array('usuario'=>"CONCAT(prs.Prs_Ape,' ',prs.Prs_Nom)","prs.Prs_Cod, prs.Prs_Cor, prs.Prs_Ced, prs.Prs_Ape, prs.Prs_Nom, prs.Prs_Dir"));
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "byTrabajador":
                $sql->join(array('prsn'=>'persona'),"prsn.Prs_Cod = prsnl.Prs_Cod", array('personal'=>"CONCAT(prsn.Prs_Ape,' ',prsn.Prs_Nom)","prsn.Prs_Cod, prsn.Prs_Cor, prsn.Prs_Ced, prsn.Prs_Ape, prsn.Prs_Nom, prsn.Prs_Dir"));
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "byPersonal":
                $sql->join(array('prsnl'=>'personal'),"prsnl.Per_Cod = dfnc.Per_Cod");
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "addSemana":
                $sql->addCols(null,array('Semana'=>"Concat('Semana  #', $this->_name.Act_Sem)"));
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "xFecha":
                $sql->where("Act_Fec BETWEEN '$cond[Fec_Fin] 00:00:00' AND '$cond[Fec_Ini] 23:59:59'",null);
                break;
            case "byTipoPagoLabor":
                $sql->join(array('tpg'=>'tipo_pago_labor'),"tpg.Tpg_Cod = lbr.Tpg_Cod");
                break;
            case "isMovActCod":
                $sql->where('dfnc.Act_Cod=?',"{$Par_Sql['movLabor']}");
                break;
            case "isMovPerCod":
                $sql->where('dfnc.Per_Cod=?',"{$Par_Sql['Per_Cod']}");
                break;
            case "addTotal":
               $total = "(Det_Val * Det_Can)";
                $sql->addCols(null, array('total'=>new Zend_Db_Expr($this->castDecimal("$total"))));
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