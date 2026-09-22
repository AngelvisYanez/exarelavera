<?php
use \Exception;
require_once(dirname(__file__)."/../DATA/libs/AbstractModel.php");

class adq_tipos_requerimientos extends AbstractModel {
    protected $_name = 'adq_tipos_requerimientos';
    protected $_primary = array('Trq_Cod');
    protected $_state = 'Trq_Est';

    public function _selectBasic($cond=null, $limits=false){
        return $this->select()
            ->join('wf_flujos_modelos', "wf_flujos_modelos.Wfm_Cod = $this->_name.Wfm_Cod", array('Wfm_Nom'));
    }

    public function _selectBasicGrid($cond=null, $limits=false){
        $sel = $this->_selectBasic();
        $this->sqlByNombre("setEmpCod", $sel);
        if (isset($cond['search']) && !empty($cond['search'])) {
            $sel->where("Trq_Des LIKE ?", "%{$cond['search']}%");
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
