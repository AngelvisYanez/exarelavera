<?php
require_once(dirname(__file__)."/../DATA/libs/AbstractModel.php");

class wf_flujos_modelos extends AbstractModel {
    protected $_name = 'wf_flujos_modelos';
    protected $_primary = array('Wfm_Cod');
    protected $_state = 'Wfm_Est';

    public function _selectBasic($cond=null, $limits=false){
        return $this->select();
    }

    public function _selectBasicGrid($cond=null, $limits=false){
        $sel = $this->_selectBasic();
        $this->sqlByNombre("setEmpCod", $sel);
        if (isset($cond['search']) && !empty($cond['search'])) {
            $sel->where("Wfm_Nom LIKE ?", "%{$cond['search']}%");
        }
        return $sel;
    }

    public function sqlByNombre($id, $Par_Sql, $cond=null){
        switch ($id) {
            case "setEmpCod":
                $Par_Sql->where("$this->_name.Emp_Cod = ?", $_SESSION['Ses_Emp_Cod']);
                break;
            case "isActive":
                $Par_Sql->where("$this->_name.$this->_state = 'A'");
                break;
            default:
                throw new Exception("No existe la sql por nombre: $id");
        }
        return $Par_Sql;
    }

    public function sqlByNumero($id, $Par_Sql, $cond=null){
        switch ($id) {
            case 0:
                return "";
            default:
                throw new Exception("No existe la sql por numero: $id");
        }
    }
}
