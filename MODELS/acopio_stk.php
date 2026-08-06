<?php 
require_once(dirname(__file__)."/../DATA/libs/AbstractModel.php");
class acopio_stk extends AbstractModel{
    protected $_name = 'acopio_stk'; 
    protected $_primary = array('Aco_Cod','Pro_Cod');	
    protected $_state = '';	
    
    public function _selectBasic($cond=null){         
        return $this->select()
            ->join('acopio', "acopio.Aco_Cod = $this->_name.Aco_Cod", array('Aco_Des'))
            ->joinLeft('productor_bana', "productor_bana.Prd_Cod = acopio.Prd_Cod", array('Mar_Des'))
            ->join('producto', "producto.Pro_Cod = $this->_name.Pro_Cod",array('Producto'=>"CONCAT(Ite_Lar,IF(Pro_Obs IS NULL OR TRIM(Pro_Obs)='' OR Pro_Obs=Ite_Lar,'',CAST(CONCAT(' - ',Pro_Obs)AS CHAR) ) )"))
            ->join('item', "item.Ite_Cod = producto.Ite_Cod", array('Cat_Cod','Ite_Lar','Ite_Cor','Ite_Est'))
            ->join('categorias', "categorias.Cat_Cod = item.Cat_Cod", array('Cat_Des'))
            ->joinLeft('marca', "marca.Mar_Cod = producto.Ite_Cod", array('Mar_Des'))
            ->join('adquisicio', "adquisicio.Adq_Cod = producto.Adq_Cod", array('Adq_Des'))
            ->join('unidad', "unidad.Uni_Cod = producto.Uni_Cod", array('Uni_Des'))
            ->join('ubicacion', "ubicacion.Ubi_Cod = producto.Ubi_Cod", array('Ubi_Des'))
            ->join('iva', "iva.Iva_Cod = producto.Iva_Cod", array('Iva_Por')); 
    }
    public function _selectBasicGrid($cond=null){ 
        $sel=$this->_selectBasic();        
        return $sel; 
    }
    public function formatData($data, $type, $allData=null){ 
        return ($type=='I')?$data:$data;
    }
    public function sqlByNombre($id,$Par_Sql,$cond=null){
        $sql="";
        switch($id){
            case "":
                $sql="";
                //echo $sql.'<br/>';
                break;
            case "setEmpCod":                
                $Par_Sql->where("categorias.Emp_Cod=?",$_SESSION['Ses_Emp_Cod']);
                //echo $sql.'<br/>';
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
                $sql="";
                //echo $sql.'<br/>';
                break;
            default: throw new Exception ("No existe la sql denominada $id!");
        }
        //echo $sql."<br/>";
        return $sql;
    }
}