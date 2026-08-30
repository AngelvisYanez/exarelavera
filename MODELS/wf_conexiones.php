<?php
require_once(dirname(__file__)."/../DATA/libs/AbstractModel.php");

class wf_conexiones extends AbstractModel {
    protected $_name = 'wf_conexiones';
    protected $_primary = array('Con_Cod');
    protected $_state = null;

    public function _selectBasic($cond=null, $limits=false){
        return $this->select()
            ->join(array('ori'=>'wf_nodos'), "ori.Nod_Cod = $this->_name.Nod_Ori", array('Ori_Nom'=>'Nod_Nom', 'Ori_Tip'=>'Nod_Tip'))
            ->join(array('des'=>'wf_nodos'), "des.Nod_Cod = $this->_name.Nod_Des", array('Des_Nom'=>'Nod_Nom', 'Des_Tip'=>'Nod_Tip'));
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
