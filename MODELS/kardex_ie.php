<?php
use \Exception;
require_once(dirname(__file__)."/../DATA/libs/AbstractModel.php");
class kardex_ie extends AbstractModel{
    protected $_name = 'kardex_ie';
    protected $_primary = array('Kar_Int', 'Vet_Cod', 'Iva_Cod','Aju_cod','Vnd_Cod','Cop_Cod','Pro_Cod');
    protected $_state = 'Kar_Est';
    //protected $_fields = array(); // se declara las filas que va a tener el arreglo en caso de insert o update //opcional

    /* crea una sql basica global para la tabla */
    public function _selectBasic($cond=null,$limits=false){
        return $this->select();
    }
    /* crea una sql standart para jqgrid, condiciones incluidas */
    public function _selectBasicGrid($cond=null,$limits=false){
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
            case "getNext":
                $sql=$this->select(false, array());
                $sql->from('kardex_ie', array('total' => 'IFNULL(MAX(Kar_Int),0)'));
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

            /* Busqueda de kardex por producto para obtener Saldo, Stock y Promedio */
            case 0:
                $sql=" SELECT 
                        IF( kardex_ie.Vet_Cod!=0,'3',IF(kardex_ie.Cop_Cod!=0,'1',IF(kardex_ie.Aju_Cod!=0,'2',''))) AS orden,
                        Kar_Int, Vet_Cod, Iva_Cod, Aju_Cod, Vnd_Cod, Cop_Cod, Pro_Cod, Gia_Cod, Kar_Sal, Kar_Pre, Kar_Ime, Kar_Can, Kar_Prs, Kar_Ims
                        FROM kardex_ie
                        WHERE 
                        Kar_Est='A' AND Pro_Cod = $Par_Sql[0]
                        ORDER BY Kar_Fec, orden, Kar_Hor";
                break;
            case 1:
                $sql=" Select Pro_Cod FROM stock WHERE Suc_cod = $Par_Sql[0]";
                break;

            case 2:
                $sql=" UPDATE kardex_ie SET Kar_Stock=$Par_Sql[Stock], Kar_Promedio=$Par_Sql[Promedio], Kar_Saldo=$Par_Sql[Saldo]
                       WHERE Kar_Int = $Par_Sql[Kar_Int] AND Vet_Cod = $Par_Sql[Vet_Cod] AND Iva_Cod = $Par_Sql[Iva_Cod] AND Aju_Cod = $Par_Sql[Aju_Cod] AND Vnd_Cod = $Par_Sql[Vnd_Cod] AND Cop_Cod = $Par_Sql[Cop_Cod] AND Pro_Cod = $Par_Sql[Pro_Cod] AND Gia_Cod = $Par_Sql[Gia_Cod]";
                break;
                
            case 3:
                $sql="SELECT Sum(Kar_Sal * Kar_Promedio) as Costo FROM kardex_ie WHERE Kar_Est = 'A' AND Vet_Cod = $Par_Sql[0]";
                break;

            case 4:
                $sql="SELECT v.Vet_Cod, v.Pro_Cod, p.Pld_Cod, Pld_Des, '' as Glosa,
                        IF(p.Tip_Pld = 'C', 'H', 'D') as Det_Tip,
                        SUM(Kar_Sal * Kar_Promedio) as Asi_Val
                        FROM  kardex_ie as k, ventas_det as v, produ_plan as p, det_plan as d
                          WHERE (k.Pro_Cod = v.Pro_Cod AND k.Vet_Cod = v.Vet_Cod AND k.Kar_Int = v.Vet_Ite)
                          AND v.Pro_Cod = p.Pro_Cod
                          AND p.Pld_Cod = d.Pld_Cod
                          AND v.Vet_Cod = $Par_Sql[0]
                          AND p.Tip_Pld in ('C', 'O')
                          GROUP BY d.Pld_Cod
                          ORDER BY Vet_Cod";
                break;

            case 5:
                $sql="SELECT v.Vet_Cod, v.Pro_Cod, p.Pld_Cod, Pld_Des, '' as Glosa,
                        IF(p.Tip_Pld = 'C', 'H', 'D') as Det_Tip,
                        SUM(Kar_Sal * Kar_Promedio) as Asi_Val
                        FROM  kardex_ie as k, ventas_det as v, produ_plan as p, det_plan as d
                          WHERE (k.Pro_Cod = v.Pro_Cod AND k.Vet_Cod = v.Vet_Cod AND k.Kar_Int = v.Vet_Ite)
                          AND v.Pro_Cod = p.Pro_Cod
                          AND p.Pld_Cod = d.Pld_Cod
                          AND v.Vet_Cod in $Par_Sql[0]
                          AND p.Tip_Pld in ('C', 'O')
                          GROUP BY d.Pld_Cod
                          ORDER BY Vet_Cod";
                break;

            case 6:
                $sql="SELECT  SUM(Kar_Sal * Kar_Promedio) as Asi_Val
                        FROM  kardex_ie as k, ventas_det as v, produ_plan as p, det_plan as d
                          WHERE (k.Pro_Cod = v.Pro_Cod AND k.Vet_Cod = v.Vet_Cod AND k.Kar_Int = v.Vet_Ite)
                          AND v.Pro_Cod = p.Pro_Cod
                          AND p.Pld_Cod = d.Pld_Cod
                          AND v.Vet_Cod IN $Par_Sql[0]
                          AND p.Tip_Pld in ('C', 'O')
                          GROUP BY Tip_Pld LIMIT 1";
                break;
            case 7:
                $sql ="SELECT 
                        IF( kardex_ie.Vet_Cod!=0,'3',IF(kardex_ie.Cop_Cod!=0,'1',IF(kardex_ie.Aju_Cod!=0,'2',''))) AS orden,
                        Kar_Fec, Kar_Stock, Kar_Promedio, Kar_Saldo
                        FROM kardex_ie
                        WHERE Kar_Est='A' 
                        AND Pro_Cod = $Par_Sql[Pro_Cod]
                        AND Kar_Fec < '$Par_Sql[Fecha] 00:00:00'
                        ORDER BY Kar_Fec Desc, orden Desc, Kar_Hor Desc LIMIT 1";

                break;
             case 8:
                $sql ="SELECT 
                        IF( kardex_ie.Vet_Cod!=0,'3',IF(kardex_ie.Cop_Cod!=0,'1',IF(kardex_ie.Aju_Cod!=0,'2',''))) AS orden,
                        Kar_Fec, SUM(Kar_Can - Kar_Sal) As Kar_Stock, Kar_Promedio, Kar_Saldo
                        FROM kardex_ie
                        WHERE Kar_Est='A' 
                        AND Pro_Cod = $Par_Sql[Pro_Cod]
                        AND Kar_Fec <= '$Par_Sql[Fecha] 00:00:00'
                        ORDER BY Kar_Fec Desc, orden Desc, Kar_Hor Desc LIMIT 1";

                break;

             case 9:
                $sql ="SELECT 
                        IF( kardex_ie.Vet_Cod!=0,'3',IF(kardex_ie.Cop_Cod!=0,'1',IF(kardex_ie.Aju_Cod!=0,'2',''))) AS orden,
                        Kar_Fec, Kar_Sal, (Kar_Sal * Kar_Promedio) as Kar_Ime, Kar_Can, Kar_Ims
                        FROM kardex_ie
                        WHERE Kar_Est='A' 
                        AND Pro_Cod = $Par_Sql[Pro_Cod]
                        AND Kar_Fec BETWEEN '$Par_Sql[Fecha_Ini] 00:00:00' AND '$Par_Sql[Fecha_Fin] 00:00:00'
                        ORDER BY Kar_Fec, orden, Kar_Hor ";

                break;

            case 10:
                $sql ="SELECT 
                        IF( kardex_ie.Vet_Cod!=0,'3',IF(kardex_ie.Cop_Cod!=0,'1',IF(kardex_ie.Aju_Cod!=0,'2',''))) AS orden,
                        Kar_Fec, Kar_Sal, Kar_Ime, Kar_Can, Kar_Ims, Kar_Pre
                        FROM kardex_ie
                        WHERE Kar_Est='A' 
                        AND Pro_Cod = $Par_Sql[Pro_Cod]
                        AND Kar_Fec BETWEEN '$Par_Sql[Fecha_Ini] 00:00:00' AND '$Par_Sql[Fecha_Fin] 00:00:00'
                        ORDER BY Kar_Fec, orden, Kar_Hor ";

                break;

            case 11:
                $sql ="SELECT 
                        IF( kardex_ie.Vet_Cod!=0,'3',IF(kardex_ie.Cop_Cod!=0,'1',IF(kardex_ie.Aju_Cod!=0,'2',''))) AS orden,
                        Kar_Fec, Kar_Sal, Kar_Ime, Kar_Can, Kar_Ims, Kar_Pre, ventas_det.Vet_Imp
                        FROM kardex_ie
                        LEFT JOIN ventas_det ON ventas_det.Vet_Cod = kardex_ie.Vet_Cod AND ventas_det.Pro_Cod = kardex_ie.Pro_Cod
                        WHERE Kar_Est='A' 
                        AND kardex_ie.Pro_Cod = $Par_Sql[Pro_Cod]
                        AND Kar_Fec BETWEEN '$Par_Sql[Fecha_Ini] 00:00:00' AND '$Par_Sql[Fecha_Fin] 00:00:00'
                        ORDER BY Kar_Fec, orden, Kar_Hor ";

                break;
            case 12:
                $sql ="SELECT Sum(kardex_ie.Kar_Can) - SUM(kardex_ie.Kar_Sal) as Stk_Can from kardex_ie where kardex_ie.Pro_Cod = $Par_Sql[Pro_Cod] and kardex_ie.Kar_Est = 'A' $Par_Sql[Bodega]";
                break;

            default: throw new Exception ("No existe la sql numero $id!");
        }
        return $sql;
    }
}