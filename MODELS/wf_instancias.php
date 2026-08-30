<?php
require_once(dirname(__file__)."/../DATA/libs/AbstractModel.php");

class wf_instancias extends AbstractModel {
    protected $_name = 'wf_instancias';
    protected $_primary = array('Ins_Cod');
    protected $_state = 'Ins_Est';

    public function _selectBasic($cond=null, $limits=false){
        return $this->select()
            ->join('wf_flujos_modelos', "wf_flujos_modelos.Wfm_Cod = $this->_name.Wfm_Cod", array('Wfm_Nom'))
            ->joinLeft('wf_nodos', "wf_nodos.Nod_Cod = $this->_name.Nod_Act", array('Nod_Nom', 'Nod_Tip', 'Dep_Cod', 'Per_Cod'));
    }

    public function _selectBasicGrid($cond=null, $limits=false){
        $sel = $this->_selectBasic();
        if (isset($cond['Ins_Est']) && !empty($cond['Ins_Est'])) {
            $sel->where("$this->_name.Ins_Est = ?", $cond['Ins_Est']);
        }
        if (isset($cond['Ins_Ent_Typ']) && !empty($cond['Ins_Ent_Typ'])) {
            $sel->where("$this->_name.Ins_Ent_Typ = ?", $cond['Ins_Ent_Typ']);
        }
        if (isset($cond['Ins_Ent_Cod']) && !empty($cond['Ins_Ent_Cod'])) {
            $sel->where("$this->_name.Ins_Ent_Cod = ?", $cond['Ins_Ent_Cod']);
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
