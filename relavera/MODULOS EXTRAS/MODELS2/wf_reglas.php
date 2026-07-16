<?php
use \Exception;
require_once(dirname(__file__)."/../DATA/libs/AbstractModel.php");

class wf_reglas extends AbstractModel {
    protected $_name = 'wf_reglas';
    protected $_primary = array('Reg_Cod');
    protected $_state = 'Reg_Est';

    public function _selectBasic($cond=null, $limits=false){
        return $this->select();
    }

    public function _selectBasicGrid($cond=null, $limits=false){
        $sel = $this->_selectBasic();
        if (isset($cond['Wfm_Cod']) && !empty($cond['Wfm_Cod'])) {
            $sel->where("$this->_name.Wfm_Cod = ?", $cond['Wfm_Cod']);
        }
        return $sel;
    }

    public function sqlByNombre($id, $Par_Sql, $cond=null){
        switch ($id) {
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
