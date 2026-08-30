<?php
require_once(dirname(__file__)."/../DATA/libs/AbstractModel.php");
class det_ant_cccc extends AbstractModel{
    protected $_name = 'det_ant_cccc';
    protected $_primary = array('Ddc_Cod');
    //protected $_state = 'CAMPOS_ESTADO';
    //protected $_fields = array(); // se declara las filas que va a tener el arreglo en caso de insert o update //opcional

    /* crea una sql basica global para la tabla */
    public function _selectBasic($cond=null){
        return $this->select(true, array('*','codigoCompra'=>"CONCAT(tpAst.Tia_Abr, '-', MONTH(cprbnt.Com_Fec), '-', cprbnt.Com_Num)"))
                    ->join(array('atc'=>'anticipos_clientes'), "atc.Ant_Cod = $this->_name.Ant_Cod", array('*'))
                    ->join(array('cli'=>'cliente'), "cli.Cli_Cod = atc.Cli_Cod")
                    ->joinLeft(array('prs'=>'persona'),"prs.Prs_Cod = cli.Prs_Cod", array('*', 'nombre'=>"concat(prs.Prs_Nom,' ',prs.Prs_Ape)", 'cedProv'=>'prs.Prs_Ced'))
                    ->joinLeft(array('dccppc'=>'det_ccpp_c'), "dccppc.Dcc_Cod = $this->_name.Dcc_Cod", array('*'))
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
            // reemplazado por implementacion de fecha corte
            case "subGrid":
                $sql->where('det_ant_cccc.Ant_Cod=?', "{$Par_Sql['movAnticipo']}");
            
                // Verificar si las fechas están definidas en $Par_Sql
                // if (!empty($Par_Sql['txt_fec_ini']) && !empty($Par_Sql['txt_fec_fin'])) {
                if (!empty($Par_Sql['Pec_Cod']) && $Par_Sql['Pec_Cod'] === 'Corte') {
                    $sql->where('dccppc.Cpc_Fec >= ?', $Par_Sql['txt_fec_ini']);
                    $sql->where('dccppc.Cpc_Fec <= ?', $Par_Sql['txt_fec_fin']);
                }
                break;
            case "sumByPacCod":
                // Obtiene la suma de consumos previos para un Pac_Cod específico
                $sql = "SELECT COALESCE(SUM(Ddc_Val), 0) as total_consumido FROM det_ant_cccc WHERE Pac_Cod = {$Par_Sql['Pac_Cod']}";
                break;
            default: $this->sqlByParams($id,$sql,array(
                    'isActive'=>"$this->_name.$this->_state='A'"
                )); //echo $this->getSqlString($sql)."<br/>";
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
            default: throw new Exception ("No existe la sql numero $id!");
        }
        //echo $this->getSqlString($sql)."<br/>";
        return $sql;
    }
}