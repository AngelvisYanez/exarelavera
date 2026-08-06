<?php
require_once(dirname(__file__)."/../DATA/libs/AbstractModel.php");
class producto extends AbstractModel{
    protected $_name = 'producto';
    protected $_primary = array('Pro_Cod');
    protected $_state = 'Pro_Est';

    public function _selectBasic($cond=null,$limits=false){
        return $this->select(true,array('*','Producto'=>"CONCAT(Ite_Lar,IF(Pro_Obs IS NULL OR TRIM(Pro_Obs)='' OR Pro_Obs=Ite_Lar,'',CAST(CONCAT(' - ',Pro_Obs)AS CHAR) ) )"))
            ->join('item', "item.Ite_Cod = $this->_name.Ite_Cod", array('Cat_Cod','Ite_Lar','Ite_Cor','Ite_Est'))
            ->join('categorias', "categorias.Cat_Cod = item.Cat_Cod", array('Cat_Des'))
            ->joinLeft('marca', "marca.Mar_Cod = $this->_name.Ite_Cod", array('Mar_Des'=>$this->expr("IF(Mar_Des IS NULL,'NINGUNA',Mar_Des)")))
            ->join('adquisicio', "adquisicio.Adq_Cod = $this->_name.Adq_Cod", array('Adq_Des'))
            ->join('unidad', "unidad.Uni_Cod = $this->_name.Uni_Cod", array('Uni_Des'))
            ->join('ubicacion', "ubicacion.Ubi_Cod = $this->_name.Ubi_Cod", array('Ubi_Des'))
            //->join('produ_plan', "produ_plan.Pro_Cod = $this->_name.Pro_Cod", array('Pro_Cod','produ_plan.Pld_Cod','Tip_Pld'))
            //->join('det_plan', "det_plan.Pld_Cod=produ_plan.Pld_Cod", array('Pld_Cdc','Pld_Des','Pla_Cod'))
            ->join('precios', "precios.Pro_Cod = $this->_name.Pro_Cod", array('Pre_Pvp'))
            //->join('tipo_preci', "tipo_preci.Tpv_Cod = precios.Tpv_Cod", array('Pre_Pvp'))
            ->join('iva', "iva.Iva_Cod = $this->_name.Iva_Cod", array('Iva_Por'))
            ;
    }
    public function _selectBasicGrid($cond=null,$limits=false){
        $sel=$this->_selectBasic();
        $this->sqlByNombre("setEmpCod", $sel);
        //$this->sqlByNombre("setSucCod", $sel);
        if(isset($cond['op_opciones'])){
            if($cond['op_opciones']=="c") $sel->where("producto.Pro_Bar='$cond[search]'"); else{
                $search=""; 
                $array=explode(" ",strtoupper($cond['search']));
                foreach($array as $ar){
                    if(!empty($ar) && $ar!='') $search.=(($search!=''?" AND ":"")."CAST(UPPER(CONCAT(Ite_Lar,Pro_Obs)) AS CHAR)LIKE UPPER('%$ar%')");                    
                }
                if($search=='') $search="1=1";
                $sel->where($search, null);
            }           
        }if(isset($cond['Fin_Cod']))
            $sel->where("$this->_name.Pro_Cod!=?",$cond['Fin_Cod']);
        return $sel;
    }
    public function formatData($data, $type, $allData=null){
        return ($type=='I')?$data:$data;
    }
    public function sqlByNombre($id,$Par_Sql,$cond=null){
        //$sql="";
        if(is_object($Par_Sql)){ $sql=$Par_Sql; $Par_Sql=$cond; }else $sql='';
        switch($id){
            case "":
                $sql="";
                //echo $sql.'<br/>';
                break;
            case "setSucCod":
                $sql->join('stock', "stock.Pro_Cod = $this->_name.Pro_Cod", array('*'))
                    ->where("stock.Suc_Cod=?",$_SESSION['Ses_Suc_Cod']);
                //echo $sql.'<br/>';
                break;
            case "setEmpCod":
                $sql->where("categorias.Emp_Cod=?",$_SESSION['Ses_Emp_Cod']);
                //echo $sql.'<br/>';
                break;
            case "isActive":
                $sql->where("$this->_name.$this->_state='A'");
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "addStock":
                // $sql->addCols(null,array(
                //     'Stock'=>new Zend_Db_Expr("IF(stk.Stk_Can < 0 OR stk.Stk_Can=0,stk.Stk_Can,stk.Stk_Can)")
                // ));
                // Añadir stock con LEFT JOIN para incluir productos sin stock
                if(isset($_SESSION['Ses_Suc_Cod'])){
                    $sql->joinLeft(array('stk'=>'stock'), "stk.Pro_Cod = $this->_name.Pro_Cod AND stk.Suc_Cod = ".$_SESSION['Ses_Suc_Cod'], array('Stk_Can'=>$this->expr("IFNULL(stk.Stk_Can, 0)")));
                } else {
                    $sql->joinLeft(array('stk'=>'stock'), "stk.Pro_Cod = $this->_name.Pro_Cod", array('Stk_Can'=>$this->expr("IFNULL(stk.Stk_Can, 0)")));
                }
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "haveNotStock":
                $sql->where("stk.Stk_Can <= 0");
                break;
            case "haveNotPrice":
                $sql->where("precios.Pre_Pvp <= 0");
                break;
            case "byStock":
                $sql->join(array('stk'=>'stock'), "$this->_name.Pro_Cod = stk.Pro_Cod");
                break;
            default: throw new Exception ("No existe la sql denominada $id!");
        }
        //echo $sql."<br/>";
        return $sql;
    }
    public function sqlByNumero($id,$Par_Sql,$cond=null){
        $sql="";
        switch($id){
             case 0:
                $sql=" UPDATE producto SET Pro_Stk =$Par_Sql[Stock], Pro_Prp=$Par_Sql[Promedio]
                       WHERE Pro_Cod = $Par_Sql[Producto]";
                break;

            case 1:
                $sql="SELECT COUNT(stock.Pro_Cod) as Cantidad FROM stock
                        INNER JOIN produ_plan ON produ_plan.Pro_Cod = stock.Pro_Cod
                        INNER JOIN producto ON stock.Pro_Cod = producto.Pro_Cod
                        INNER JOIN ventas_det ON ventas_det.Pro_Cod = producto.Pro_Cod
                        WHERE Suc_cod = $Par_Sql[0]
                        AND Tip_Pld in ('C', 'O')
                        AND producto.Adq_Cod = 1
                        GROUP BY stock.Pro_Cod 
                        HAVING count(stock.Pro_Cod) < 2";
                break;
                
            default: throw new Exception ("No existe la sql denominada $id!");
        }
        //echo $sql."<br/>";
        return $sql;
    }
}