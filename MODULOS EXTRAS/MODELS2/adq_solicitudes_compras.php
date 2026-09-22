<?php
use \Exception;
require_once(dirname(__file__)."/../DATA/libs/AbstractModel.php");

class adq_solicitudes_compras extends AbstractModel {
    protected $_name = 'adq_solicitudes_compras';
    protected $_primary = array('Scm_Cod');
    protected $_state = null;

    public function _selectBasic($cond=null, $limits=false){
        return $this->select()
            ->join('adq_solicitudes', "adq_solicitudes.Sol_Cod = $this->_name.Sol_Cod", array('Sol_Num', 'Sol_Fec', 'Sol_Val_Est'))
            ->join('compras', "compras.Cop_Cod = $this->_name.Cop_Cod", array('Cop_Num', 'Cop_Fec'));
    }

    public function _selectBasicGrid($cond=null, $limits=false){
        $sel = $this->_selectBasic();
        if (isset($cond['Sol_Cod']) && !empty($cond['Sol_Cod'])) {
            $sel->where("$this->_name.Sol_Cod = ?", $cond['Sol_Cod']);
        }
        if (isset($cond['Cop_Cod']) && !empty($cond['Cop_Cod'])) {
            $sel->where("$this->_name.Cop_Cod = ?", $cond['Cop_Cod']);
        }
        return $sel;
    }

    public function sqlByNombre($id, $Par_Sql, $cond=null){
        switch ($id) {
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
