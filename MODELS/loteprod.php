<?php
use \Exception;
require_once(dirname(__file__)."/../DATA/libs/AbstractModel.php");
class loteprod extends AbstractModel{
    protected $_name = 'loteprod';
    protected $_primary = array('Lte_Cod', 'Pro_Cod');
    protected $_state = 'Lte_Est';
    //protected $_fields = array(); // se declara las filas que va a tener el arreglo en caso de insert o update //opcional

    /* crea una sql basica global para la tabla */
    public function _selectBasic($cond=null,$limits=false){
        return $this->select()

            ->join(array('prd' =>'producto'), "prd.Pro_Cod = $this->_name.Pro_Cod", array('*','Producto'=>"CONCAT(Ite_Lar,IF(Pro_Obs IS NULL OR TRIM(Pro_Obs)='' OR Pro_Obs=Ite_Lar,'',CAST(CONCAT(' - ',Pro_Obs)AS CHAR) ) )"))
            ->join(array('itm' =>'item'), "itm.Ite_Cod = prd.Ite_Cod", array('Cat_Cod','Ite_Lar','Ite_Cor','Ite_Est'))
            ->joinLeft(array('mrc' =>'marca'), "mrc.Mar_Cod = prd.Mar_Cod",array('Mar_Des'));;
    }
    /* crea una sql standart para jqgrid, condiciones incluidas */
    public function _selectBasicGrid($cond=null,$limits=false){

        $sel=$this->_selectBasic()->addCols('',array("dias"=>$this->expr("IF(TIMESTAMPDIFF(DAY, CURDATE(),$this->_name.Lte_Cad)<0,'CADUCADO', CAST(TIMESTAMPDIFF(DAY, CURDATE(),$this->_name.Lte_Cad)AS CHAR))")));
        $this->sqlByNombre("setEmpCod", $sel);
        if(isset($cond['op_opciones'])){
            if($cond['op_opciones']=='p'){
                $sel->where("loteprod.Lte_Ser = ?",$cond["search"]);
            }else{
                if($cond['op_opciones']=='mes'){
                    if($cond['chkM'] == 'on'){
                    $mes = 30;
                    $inpt = "$cond[Sel_Mes]";
                    $calc = (int)$inpt * $mes;
                    $diference = "TIMESTAMPDIFF(DAY, CURDATE(), $this->_name.Lte_Cad)";
                    $convertido = (int)$diference;
                    //echo($calc);
                    //"DATEDIFF($this->_name.Lte_Cad, CURDATE())" chkD = Caducados / chkM = Por Caducarse /WHERE DATEDIFF(invoice_dt,ord_date)<10;
                    $sel->where($diference."<= $calc");
                    $sel->where($diference.">= '0'");

                    }else{
                        $diference = "TIMESTAMPDIFF(DAY, CURDATE(),$this->_name.Lte_Cad)";
                        $sel->where($diference."< '0'");
                    }

                }elseif ($cond['op_opciones']=='dia'){
                    if($cond['chkM'] == 'on'){
                        $inpt = "$cond[Sel_Dia]";
                        $diference = "TIMESTAMPDIFF(DAY, CURDATE(), $this->_name.Lte_Cad)";
                        $sel->where($diference."<= $inpt");
                        $sel->where($diference.">= '0'");

                    }else{
                        $diference = "TIMESTAMPDIFF(DAY, CURDATE(),$this->_name.Lte_Cad)";
                        $sel->where($diference."< '0'");
                    }
                     //$sel->where("Caj_Fec BETWEEN '$cond[Fec_Ini] 00:00:00' AND '$cond[Fec_Fin] 23:59:59'",null);
                }else{
                    $sel->where("(UPPER(Ite_Lar) LIKE UPPER(?)) OR UPPER(Ite_Cor) LIKE UPPER(?)","%$cond[search]%");
                }
            }

        }
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
                $sql->where("mrc.Emp_Cod=?",$_SESSION['Ses_Emp_Cod']);
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "isActive":
                $sql->where("$this->_name.$this->_state='A'");
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "numDias":
               ;
                break;
            case "getProducto":

                break;
            case "getProveedor":
                $sql->joinLeft(array('prv' => 'proveedore'),"prv.Prv_Cod = $this->_name.Prv_Cod");
                $sql->joinLeft(array('prs'=>'persona'), "prs.Prs_Cod = prv.Prs_Cod");
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
            default: throw new Exception ("No existe la sql numero $id!");
        }
        //echo $this->getSqlString($sql)."<br/>";
        return $sql;
    }
}