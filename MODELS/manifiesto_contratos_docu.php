<?php
require_once(dirname(__file__) . "/../DATA/libs/AbstractModel.php");

class manifiesto_contratos_docu extends AbstractModel {
    protected $_name = 'manifiesto_contratos_docu';
    protected $_primary = array('Mcd_Cod');
    protected $_state = 'Mcd_Est';

    public function _selectBasic($cond = null, $limits = false) {
        return $this->select(true, array('*'));
    }

    public function _selectBasicGrid($cond = null, $limits = false) {
        return $this->_selectBasic();
    }

    public function formatData($data, $type, $allData = null) {
        return ($type == 'I') ? $data : $data;
    }

    public function sqlByNombre($id, $Par_Sql, $cond = null) {
        if (is_object($Par_Sql)) { $sql = $Par_Sql; $Par_Sql = $cond; } else { $sql = ''; }
        switch ($id) {
            case "":
                $sql = "";
                break;
            default:
                throw new Exception("No existe la sql denominada $id!");
        }
        return $sql;
    }

    public function sqlByNumero($id, $Par_Sql, $cond = null) {
        if (is_object($Par_Sql)) { $sql = $Par_Sql; $Par_Sql = $cond; } else { $sql = ''; }
        switch ($id) {
            case 0:
                $sql = "";
                break;
            default:
                throw new Exception("No existe la sql numero $id!");
        }
        return $sql;
    }
}
