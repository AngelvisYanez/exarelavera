<?php
use \Exception;
require_once(dirname(__file__)."/../DATA/libs/AbstractModel.php");

class wf_instancias_nodos extends AbstractModel {
    protected $_name = 'wf_instancias_nodos';
    protected $_primary = array('Isn_Cod');
    protected $_state = null;

    public function _selectBasic($cond=null, $limits=false){
        return $this->select()
            ->join('wf_nodos', "wf_nodos.Nod_Cod = $this->_name.Nod_Cod", array('Nod_Nom', 'Nod_Tip'))
            ->joinLeft('usuarios', "usuarios.Usu_Cod = $this->_name.Usu_Cod", array())
            ->joinLeft('persona', "persona.Prs_Cod = usuarios.Prs_Cod", array('Usuario_Nom' => "CONCAT(persona.Prs_Nom, ' ', persona.Prs_Ape)"))
            ->joinLeft('departamen', "departamen.Dep_Cod = $this->_name.Dep_Cod", array('Dep_Des'));
    }

    public function _selectBasicGrid($cond=null, $limits=false){
        $sel = $this->_selectBasic();
        if (isset($cond['Ins_Cod']) && !empty($cond['Ins_Cod'])) {
            $sel->where("$this->_name.Ins_Cod = ?", $cond['Ins_Cod']);
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
