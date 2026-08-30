<?php
require_once(dirname(__file__)."/../DATA/libs/AbstractModel.php");
class cargamento extends AbstractModel{
    protected $_name = 'cargamento';
    protected $_primary = array('Car_Cod');
    protected $_state = 'Car_Est';
    //protected $_fields = array(); // se declara las filas que va a tener el arreglo en caso de insert o update //opcional

    /* crea una sql basica global para la tabla */
    public function _selectBasic($cond=null,$limits=false){
        return $this->select()
            ->join('producto',"producto.Pro_Cod=$this->_name.Pro_Cod",array('*','Producto'=>"CONCAT(Ite_Lar,IF(Pro_Obs IS NULL OR TRIM(Pro_Obs)='' OR Pro_Obs=Ite_Lar,'',CAST(CONCAT(' - ',Pro_Obs)AS CHAR) ) )"))
            ->join('item', "item.Ite_Cod=producto.Ite_Cod", array('Cat_Cod','Ite_Lar','Ite_Cor','Ite_Est'))
            ->join('categorias', "categorias.Cat_Cod=item.Cat_Cod", array('Cat_Des'))
        ;
    }
    /* crea una sql standart para jqgrid, condiciones incluidas */
    public function _selectBasicGrid($cond=null,$limits=false){
        $sel=$this->_selectBasic();
        $this->sqlByNombre("setEmpCod", $sel);
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
                $sql->where("categorias.Emp_Cod=?",$_SESSION['Ses_Emp_Cod']);
                //echo $this->getSqlString($sql)."<br/>";
                break;
            /*case "isActive":
                $sql->where("$this->_name.$this->_state='A'");
                //echo $this->getSqlString($sql)."<br/>";
                break;*/
            /*case "byProducto":
                $sql->join(array('prod'=>'producto'),"prod.Pro_Cod=$this->_name.Pro_Cod");
                break;
            case "byItem":
                $sql->join(array('itm'=>'item'),"prod.Ite_Cod=itm.Ite_Cod");
                break;
            case "byCategorias":
                $sql->join(array('ctg'=>'categorias'),"ctg.Cat_Cod=itm.Cat_Cod");
                break;*/
            /*case "isNullVenta":
                $sql->where("$this->_name.Vet_Cod IS NULL");
                //echo $this->getSqlString($sql)."<br/>";"viaje.Cli_Cod='$Cli_Cod'"
                break;*/
            /*case "isCargamento":
                $sql->where("$this->_name.Car_Cod='$Car_Cod'");
                //echo $this->getSqlString($sql)."<br/>";
                break;*/
            /*case "byGroup":
                $sql->group(array('cliente.Prs_Cod'));
                break;*/
            default: $this->sqlByParams($id,$sql,array(
                    'isActive'=>"$this->_name.$this->_state='A'"
                )); //echo $this->getSqlString($sql)."<br/>";
        }
        //echo $this->getSqlString($sql)."<br/>";
        return $sql;
    }
    /* crea sentencia por id numero sql */
    public function sqlByNumero($id,$Par_Sql,$cond=null){
        if(is_object($Par_Sql)){ $sql=$Par_Sql; $Par_Sql=$cond; }else $sql='';
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