<?php
require_once(dirname(__file__)."/../DATA/libs/AbstractModel.php");

class adq_solicitudes_cotizaciones extends AbstractModel {
    protected $_name = 'adq_solicitudes_cotizaciones';
    protected $_primary = array('Cot_Cod');
    protected $_state = null;

    public function _selectBasic($cond=null, $limits=false){
        return $this->select()
            ->join('proveedore', "proveedore.Prv_Cod = $this->_name.Prv_Cod", array())
            ->join('persona', "persona.Prs_Cod = proveedore.Prs_Cod", array('Proveedor_Nom' => "IF(proveedore.Prv_Com IS NULL OR proveedore.Prv_Com='', CONCAT(persona.Prs_Nom,' ',persona.Prs_Ape), proveedore.Prv_Com)", 'Proveedor_Ruc' => 'Prs_Ced'));
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
