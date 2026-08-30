<?php
require_once(dirname(__file__)."/../DATA/libs/AbstractModel.php");

class adq_solicitudes_det extends AbstractModel {
    protected $_name = 'adq_solicitudes_det';
    protected $_primary = array('Sde_Cod');
    protected $_state = null;

    public function _selectBasic($cond=null, $limits=false){
        return $this->select()
            ->joinLeft('producto', "producto.Pro_Cod = $this->_name.Pro_Cod", array('Pro_Nom', 'Pro_Cor'));
    }

    public function _selectBasicGrid($cond=null, $limits=false){
        $sel = $this->_selectBasic();
        if (isset($cond['Sol_Cod']) && !empty($cond['Sol_Cod'])) {
            $sel->where("$this->_name.Sol_Cod = ?", $cond['Sol_Cod']);
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
