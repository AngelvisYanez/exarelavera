<?php
require_once(dirname(__file__)."/../DATA/libs/AbstractModel.php");
class det_plan extends AbstractModel{
    protected $_name = 'det_plan';
    protected $_primary = array('Pld_Cod');
    protected $_state = 'Pld_Est';

    public function _selectBasic($cond=null,$limits=false){
        return $this->select(true,array(
                'Pld_Esta'=>"IF (det_plan.Pld_Est='A', 'Activa', 'Inactiva')",
                'Pld_Tipo'=>"IF (det_plan.Pld_Tip='G', 'Grupo', 'Detalle')",
                "det_plan.Pld_Cod, det_plan.Pld_Cdc, det_plan.Pld_Rec, det_plan.Pld_Des, Pla_Obs, det_plan.Pld_Tip, det_plan.Pld_Est"
            ))->join(
                'plan_cuenta','plan_cuenta.Pla_Cod=det_plan.Pla_Cod', array()
            )->joinLeft(
                array('parent'=>'det_plan'), 'det_plan.Pld_Rec=parent.Pld_Cod', array()
            )->joinLeft(
                array('parent2'=>'det_plan'), 'parent.Pld_Rec=parent2.Pld_Cod', array(
                    'Pld_Grupo'=>"IF (parent2.Pld_cod IS NOT NULL, CONCAT(parent.Pld_Des,' <b>(',parent2.Pld_Des,')</b>'), parent.Pld_Des)"
                )
            );
    }
    public function _selectBasicGrid($cond=null,$limits=false){
        $sel=$this->_selectBasic();
        $this->sqlByNombre("setEmpCod", $sel);
        if(isset($cond['Pec_Cod'])||isset($cond['perio_cont.Pec_Cod']))$this->sqlByNombre("setPerioCont", $sel,$cond);
        if(isset($cond['op_opciones']))
            $sel->where($cond['op_opciones']=="c"?"det_plan.Pld_Cdc LIKE ?":"det_plan.Pld_Des LIKE ?", $cond['op_opciones']=="c"?"{$cond['search']}%":"%{$cond['search']}%");
        return $sel;
    }
    public function formatData($data, $type, $allData=null){
        return ($type=='I')?$data:$data;
    }
    public function sqlByNombre($id,$Par_Sql,$cond=null){
        if(is_object($Par_Sql)){ $sql=$Par_Sql; $Par_Sql=$cond; }else $sql='';
        switch($id){
            case "setEmpCod":
                $sql->where("plan_cuenta.Emp_Cod=?",$_SESSION['Ses_Emp_Cod']);
                //echo $sql.'<br/>';
                break;
            case 'setPerioCont':
                $sql->join('perio_cont', 'plan_cuenta.Pla_Cod=perio_cont.Pla_Cod', array());
                break;
            case "byPecCod":
                if(!$sql->hasTable('perio_cont')) $this->sqlByNombre("setPerioCont", $sql);
                $sql->where("perio_cont.Pec_Cod=?", $Par_Sql['Pec_Cod']);
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "setPlanParam":
                $sql->join('plan_param', "$this->_name.Pld_Cod = plan_param.Pld_Cod",array('Tpa_Cod'));
                $sql->join('tipo_param', "tipo_param.Tpa_Cod = plan_param.Tpa_Cod",array('Tpa_Abr'));
                //echo $this->getSqlString($sql)."<br/>";
                break;
            /*case "byPlanParam":
                $sql->join(array('planParam' => 'plan_param'), "$this->_name.Pld_Cod = planParam.Pld_Cod");
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "byTipoPlan":
                $sql->join(array('tipPlan' => 'tipo_param'), "tipPlan.Tpa_Cod = planParam.Tpa_Cod");
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "isTpr":
                $sql->where("tipPlan.Tpa_Abr='RTJ'");
                //$sql->group('asientos.Pld_Cod');
                break;*/
            case "isDetalle":
                $sql->where("det_plan.Pld_Tip = 'D'");
                //echo $this->getSqlString($sql)."<br/>";
                break;
           /*  case "byPlanCuenta":
                $selectOther = $this->select(false)->from(array('planCuenta'=>'plan_cuenta'), array(new Zend_Db_Expr('max(Pla_Cod) as maxId')))
                                                    ->where("planCuenta.Emp_Cod='$_SESSION[Ses_Emp_Cod]'");
                $sql->join(array('pCuentas'=>$selectOther), "pCuentas.Pla_Cod=$this->_name.Pla_Cod", array('*'));
                break; */
            case "byPerioCont":
                $sql->join(array('periocont'=>'perio_cont'), "plan_cuenta.Pla_Cod=periocont.Pla_Cod", array('*'));
                break;
            case "byBanco":
                $sql->join(array('bank'=>'banco'), "bank.Pld_Cod= $this->_name.Pld_Cod", array('Ban_Cod','Ban_Cue'))
                    ->where('bank.Ban_Tip=?',"{$Par_Sql['Ban_Tip']}");
                break;
            /*case "isActive":
                $sql->where("plan_cuenta.Pla_Est='A' AND det_plan.Pld_Est='A'");
                //echo $this->getSqlString($sql)."<br/>";
                break;*/
            default:
                $this->sqlByParams($id,$sql,array(
                    "isActive"=>"plan_cuenta.Pla_Est='A' AND det_plan.Pld_Est='A'",
                    'isParam'=>'tipo_param.Tpa_Abr=?',
                    'orderByCdc'=>"IF(det_plan.Pld_Tip='G',det_plan.Pld_Cdc,CAST( LEFT( det_plan.Pld_Cdc, LENGTH( det_plan.Pld_Cdc ) - LENGTH(SUBSTRING_INDEX(det_plan.Pld_Cdc, '.', -1) )  ) AS CHAR )),IF(det_plan.Pld_Tip='G', 1, CAST((SUBSTRING_INDEX(det_plan.Pld_Cdc, '.', -1) + 0)AS DECIMAL))"
                )); //echo $this->getSqlString($sql)."<br/>";
        }
        //echo $this->getSqlString($sql)."<br/>";
        return $sql;
    }
    public function sqlByNumero($id,$Par_Sql,$cond=null){
        if(is_object($Par_Sql)){ $sql=$Par_Sql; $Par_Sql=$cond; }else $sql='';
        switch($id){
            case 0:
                $sql="";
                //echo $sql.'<br/>';
                break;
            case 12:
                if ($Par_Sql['op_opciones'] == "d") {
                    $search = "(det_plan.Pld_Des LIKE '%$Par_Sql[search]%')";
                } else {
                    $search = "det_plan.Pld_Cdc LIKE '$Par_Sql[search]%'";
                }
                $campos = empty($Par_Sql['limits']) ? " COUNT(det_plan.Pld_Cod) AS total" : " * ";
                $sql = "SELECT $campos
                FROM det_plan WHERE  $search AND det_plan.Pld_Tip = 'D' AND
                    det_plan.Pla_Cod = (select max(plan_cuenta.Pla_Cod) from plan_cuenta WHERE (plan_cuenta.Emp_Cod = '$_SESSION[Ses_Emp_Cod]'))
                    ORDER BY SUBSTRING_INDEX(Pld_Cdc, '.', -20) $Par_Sql[limits];";
                return $sql;
            default: throw new Exception ("No existe la sql denominada $id!");
        }
        //echo $sql."<br/>";
        return $sql;
    }
}