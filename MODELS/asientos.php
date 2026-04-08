<?php
use \Exception;
require_once(dirname(__file__)."/../DATA/libs/AbstractModel.php");


class asientos extends AbstractModel{
    protected $_name = 'asientos';
    protected $_primary = array('Asi_Cod');
    //protected $_state = 'CAMPOS_ESTADO';
    //protected $_fields = array(); // se declara las filas que va a tener el arreglo en caso de insert o update //opcional

    /* crea una sql basica global para la tabla */
    public function _selectBasic($cond=null){
        return $this->select(true, array('*','Debe'=>"IF(Asi_Deh='D',Asi_Val,'')", 'Haber'=>"IF(Asi_Deh='H',Asi_Val,'')"));
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
            case "ByDetPlan":
                $sql->join('det_plan',"det_plan.Pld_Cod=$this->_name.Pld_Cod", array('*'));
                break;
            case "comprobante":
                $sql->join('comprobantes',"comprobantes.Com_Cod=$this->_name.Com_Cod", array('*','Com_Codigo'=>"CONCAT(tipo_asien.Tia_Abr, '-', LPAD(MONTH(comprobantes.Com_Fec), 2, '0'), '-', comprobantes.Com_Num)"))
                    ->join('tipo_asien', "comprobantes.Tia_Cod = tipo_asien.Tia_Cod", array())
                    ->joinLeft('cliente',"cliente.Cli_Cod=comprobantes.Cli_Cod", array())
                    ->joinLeft(array('prs_cliente'=>'persona'),"prs_cliente.Prs_Cod=cliente.Prs_Cod", array())
                    ->joinLeft('proveedore',"proveedore.Prv_Cod=comprobantes.Prv_Cod", array())
                    ->joinLeft(array('prs_proveedore'=>'persona'),"prs_proveedore.Prs_Cod=proveedore.Prs_Cod", array())
                    ->addCols('',array($this->expr("CONCAT(IFNULL(prs_cliente.Prs_Ape,prs_proveedore.Prs_Ape),' ',IFNULL(prs_cliente.Prs_Nom,prs_proveedore.Prs_Nom)) AS Inv_Nom")));
                break;
            case "conciliacion":
                $sql->joinLeft('conciliacion_banc_asientos',"conciliacion_banc_asientos.Asi_Cod=$this->_name.Asi_Cod", array('Cob_Cod'));
                break;
            case "conciliacion_menor":
                $sql->joinLeft('conciliacion_banc_asientos',"conciliacion_banc_asientos.Asi_Cod=$this->_name.Asi_Cod ", array());
                $sql->joinLeft('conciliacion_bancaria',"conciliacion_bancaria.Cob_Cod=conciliacion_banc_asientos.Cob_Cod AND conciliacion_bancaria.Cob_Fec<='".$Par_Sql['Cob_Fec']."'", array('Cob_Cod','Cob_Fec'));
                break;
            case "tipo_pago":
                $sql->joinLeft('cheques',"cheques.Asi_Cod=$this->_name.Asi_Cod", array('Che_Num', $this->expr('Che_Num As Doc_Num')));
                $sql->addCols('',array($this->expr("IF(cheques.Asi_Cod IS NOT NULL, 'Cheque', '- Manuales')AS pago_tipo")));
                break;
            case "saldo":
                $sql->addCols(null,$this->expr("(IF(Asi_Deh='D',1,-1)*Asi_Val)AS Asi_Sald"));
                break;
            case 'data':
                $sql->join('det_plan', "det_plan.Pld_Cod = $this->_name.Pld_Cod", array('Pld_Cdc','Pld_Des'))
                    ->addCols('',array(
                    $this->expr("IF(Asi_Deh='D',Asi_Val,NULL) AS Asi_Debe"),
                    $this->expr("IF(Asi_Deh='H',Asi_Val,NULL) AS Asi_Haber"),
                ))->order('Asi_Deh')->order('Pld_Cdc');
                break;
            /* case "byCheques":
                //$selectOther = $this->select(false)->from(array('chq'=>'cheques'))
                //                                   ->where("chq.Che_Est <> 'P'");
                //$sql->joinLeft(array('chqe'=>$selectOther), "chqe.Asi_Cod=asientos.Asi_Cod", array('*'));
                $selectOther = $this->select(false)->from('cheques')
                            ->where("`cheques`.`Asi_Cod` = `asientos`.`Asi_Cod` AND cheques.Che_Est <> 'P' ");
                $sql->where("NOT IN (".$this->getSqlString($selectOther).")");
                break; */
           default: $this->sqlByParams($id,$sql,array(
                    'isActive'=>"comprobantes.Com_Est='A'"
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
                $sql="INSERT INTO asientos SET Com_Cod=$Par_Sql[Com_Cod], Asi_Deh='$Par_Sql[Asi_Deh]', Asi_Val=$Par_Sql[Asi_Val], Asi_Con=UPPER('$Par_Sql[Asi_Con]'), Asi_Glo=UPPER('$Par_Sql[Asi_Glo]'), Pld_Cod=$Par_Sql[Pld_Cod]";
                break;
            case 1:
                $sql="DELETE FROM asientos where
                    asientos.Asi_Cod not in (SELECT cheques.Asi_Cod from cheques where cheques.Asi_Cod = asientos.Asi_Cod and cheques.Che_Est = 'P') and
                    asientos.Com_Cod=$Par_Sql[Com_Cod];";
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case 2:
                $sql="DELETE FROM asientos where
                    asientos.Asi_Cod not in (
                        select pga.Asi_Cod from pag_anticipo_cli as pga inner join cheques_ext as che on(che.Che_Cod=pga.Che_Cod and che.Che_Est='P') where pga.Asi_Cod=asientos.Asi_Cod
                    ) and
                    asientos.Com_Cod=$Par_Sql[Com_Cod];";
                //echo $this->getSqlString($sql)."<br/>";
                break;
            default: throw new Exception ("No existe la sql numero $id!");
        }
        //echo $this->getSqlString($sql)."<br/>";
        return $sql;
    }
}