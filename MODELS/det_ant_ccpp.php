<?php
require_once(dirname(__file__)."/../DATA/libs/AbstractModel.php");
class det_ant_ccpp extends AbstractModel{
    protected $_name = 'det_ant_ccpp';
    protected $_primary = array('Dac_Cod');
    //protected $_state = '';
    //protected $_fields = array(); // se declara las filas que va a tener el arreglo en caso de insert o update //opcional

    /* crea una sql basica global para la tabla */
    public function _selectBasic($cond=null){
        return $this->select(true, array('*','codigoCompra'=>"CONCAT(tpAst.Tia_Abr, '-', MONTH(cprbnt.Com_Fec), '-', cprbnt.Com_Num)"))
            ->join(array('atp'=>'anticipos_proveedores'), "atp.Atp_Cod = $this->_name.Atp_Cod", array('*','det_ant_ccpp.Com_Cod','cprbnt.Com_Fec','cprbnt.Com_Val'))
            ->join(array('prv' =>'proveedore'), "prv.Prv_Cod = atp.Prv_Cod",array('prv.Prv_Cod as prvCod'))
            ->join(array('pap' =>'pago_anticipo_proveedores'), "$this->_name.Pap_Cod = pap.Pap_Cod AND pap.Pap_Est!='I'",array('*'))
            ->joinLeft(array('prs'=>'persona'), "prs.Prs_Cod = prv.Prs_Cod", array('prs.Prs_Cod','Prs_Ced','nombre'=>"concat(prs.Prs_Nom,' ',prs.Prs_Ape)"))
            ->joinLeft('tipos_pago','pap.Pag_Cod = tipos_pago.Pag_Cod',array('Pag_Abr'))
            ->join(array('cprbnt'=>'comprobantes'), "cprbnt.Com_Cod = $this->_name.Com_Cod", array('*'))
            ->join(array('tpAst'=>'tipo_asien'), "tpAst.Tia_Cod = cprbnt.Tia_Cod");  
    }
    /* crea una sql standart para jqgrid, condiciones incluidas */
    public function _selectBasicGrid($cond=null){
        $sel=$this->_selectBasic();
        return $sel;
    }
    /* formatea el array para insert o update */
    public function formatData($data, $type, $allData=null){
        return ($type=='I')?$data:$data;
    }
    /* crea sentencia por id nombre sql */
    public function sqlByNombre($id,$Par_Sql,$cond=null){
        if(is_object($Par_Sql)){ $sql=$Par_Sql; $Par_Sql=$cond; }else $sql='';
        switch($id){
            case "":
                $sql="";
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "setEmpCod":
                $sql->where("Emp_Cod=?",$_SESSION['Ses_Emp_Cod']);
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "isActive":
                $sql->where("$this->_name.$this->_state='A'");
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "subGrid":
                $sql->where('det_ant_ccpp.Atp_Cod=?',"{$Par_Sql['movAnticipo']}");
                // filtro de fecha corte
                // if (!empty($Par_Sql['txt_fec_ini']) && !empty($Par_Sql['txt_fec_fin'])) {
                if (!empty($Par_Sql['Pec_Cod']) && $Par_Sql['Pec_Cod'] === 'Corte') {
                    $sql->where('cprbnt.Com_Fec >= ?', $Par_Sql['txt_fec_ini']);
                    $sql->where('cprbnt.Com_Fec <= ?', $Par_Sql['txt_fec_fin']);
                }
                break;

            default: throw new Exception ("No existe la sql denominada $id!");
        }
        //echo $this->getSqlString($sql)."<br/>";
        return $sql;
    }
    /* crea sentencia por id numero sql */
    public function sqlByNumero($id,$Par_Sql,$cond=null){
        $sql=(is_object($Par_Sql)?$Par_Sql:'');
        switch($id){
            case 0:
                $sql="";
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case 2:
                /* Eliminamos un cruce anticipo manual */
                $sql="delete pago_anticipo_proveedores, det_ant_ccpp from pago_anticipo_proveedores inner join det_ant_ccpp ON pago_anticipo_proveedores.Pap_Cod = det_ant_ccpp.Pap_Cod where det_ant_ccpp.Com_Cod = $Par_Sql[Com_Cod]";    
            default: throw new Exception ("No existe la sql numero $id!");
        }
        //echo $this->getSqlString($sql)."<br/>";
        return $sql;
    }
}