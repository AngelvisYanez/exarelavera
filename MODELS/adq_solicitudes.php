<?php
use \Exception;
require_once(dirname(__file__)."/../DATA/libs/AbstractModel.php");

class adq_solicitudes extends AbstractModel {
    protected $_name = 'adq_solicitudes';
    protected $_primary = array('Sol_Cod');
    protected $_state = 'Sol_Est';

    public function _selectBasic($cond=null, $limits=false){
        return $this->select()
            ->join('adq_tipos_requerimientos', "adq_tipos_requerimientos.Trq_Cod = $this->_name.Trq_Cod", array('Trq_Des', 'Trq_Req_Fac', 'Trq_Req_Cot'))
            ->join('usuarios', "usuarios.Usu_Cod = $this->_name.Usu_Sol", array())
            ->join('persona', "persona.Prs_Cod = usuarios.Prs_Cod", array('Solicitante_Nom' => "CONCAT(persona.Prs_Nom, ' ', persona.Prs_Ape)"))
            ->join('departamen', "departamen.Dep_Cod = $this->_name.Dep_Sol", array('Dep_Des'))
            ->joinLeft('proveedore', "proveedore.Prv_Cod = $this->_name.Prv_Sug", array())
            ->joinLeft(array('pers_prov' => 'persona'), "pers_prov.Prs_Cod = proveedore.Prs_Cod", array('Proveedor_Sugerido' => "IF(proveedore.Prv_Com IS NULL OR proveedore.Prv_Com='', CONCAT(pers_prov.Prs_Nom,' ',pers_prov.Prs_Ape), proveedore.Prv_Com)"));
    }

    public function _selectBasicGrid($cond=null, $limits=false){
        $sel = $this->_selectBasic();
        $this->sqlByNombre("setEmpCod", $sel);
        
        if (isset($cond['Sol_Est']) && !empty($cond['Sol_Est'])) {
            $sel->where("$this->_name.Sol_Est = ?", $cond['Sol_Est']);
        }
        if (isset($cond['Suc_Cod']) && !empty($cond['Suc_Cod'])) {
            $sel->where("$this->_name.Suc_Cod = ?", $cond['Suc_Cod']);
        }
        if (isset($cond['search']) && !empty($cond['search'])) {
            $sel->where("($this->_name.Sol_Num = ? OR $this->_name.Sol_Num LIKE ? OR persona.Prs_Nom LIKE ? OR persona.Prs_Ape LIKE ?)", array($cond['search'], "%{$cond['search']}%", "%{$cond['search']}%", "%{$cond['search']}%"));
        }
        return $sel;
    }

    public function sqlByNombre($id, $Par_Sql, $cond=null){
        switch ($id) {
            case "setEmpCod":
                $Par_Sql->where("$this->_name.Emp_Cod = ?", $_SESSION['Ses_Emp_Cod']);
                break;
            default:
                throw new Exception("No existe la sql por nombre: $id");
        }
        return $Par_Sql;
    }

    public function sqlByNumero($id, $Par_Sql, $cond=null){
        switch ($id) {
            case 1:
                // Obtener número máximo de solicitud para autoincremento por sucursal
                return "SELECT IFNULL(MAX(Sol_Num), 0) + 1 AS SiguienteNum FROM adq_solicitudes WHERE Emp_Cod = $Par_Sql[Emp_Cod] AND Suc_Cod = $Par_Sql[Suc_Cod];";
            default:
                throw new Exception("No existe la sql por numero: $id");
        }
    }
}
