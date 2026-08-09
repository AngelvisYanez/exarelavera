<?php 
require_once(dirname(__file__)."/../DATA/libs/AbstractModel.php");
class productor_tarja_det extends AbstractModel{
    protected $_name = 'productor_tarja_det'; 
    protected $_primary = array('Prt_Cod','Pro_Cod');	
    protected $_state = '';	
    
    public function _selectBasic($cond=null,$limits=false){         
        return $this->select()
            ->join('producto', "producto.Pro_Cod = $this->_name.Pro_Cod",array('Producto'=>"CONCAT(Ite_Lar,IF(Pro_Obs IS NULL OR TRIM(Pro_Obs)='' OR Pro_Obs=Ite_Lar,'',CAST(CONCAT(' - ',Pro_Obs)AS CHAR) ) )"))
            ->join('item', "item.Ite_Cod = producto.Ite_Cod", array('Cat_Cod','Ite_Lar','Ite_Cor','Ite_Est'))
            ->join('categorias', "categorias.Cat_Cod = item.Cat_Cod", array('Cat_Des'))
            ->joinLeft('marca', "marca.Mar_Cod = producto.Ite_Cod", array('Mar_Des'))
            ->join('adquisicio', "adquisicio.Adq_Cod = producto.Adq_Cod", array('Adq_Des'))
            ->join('unidad', "unidad.Uni_Cod = producto.Uni_Cod", array('Uni_Des'))
            ->join('ubicacion', "ubicacion.Ubi_Cod = producto.Ubi_Cod", array('Ubi_Des'))
            ->join('iva', "iva.Iva_Cod = producto.Iva_Cod", array('Iva_Por')); 
    }
    public function _selectBasicGrid($cond=null,$limits=false){ 
        $sel=$this->_selectBasic();        
        return $sel; 
    }
    public function formatData($data, $type, $allData=null){ 
        return ($type=='I')?$data:$data;
    }
    public function sqlByNombre($id,$Par_Sql,$cond=null){
        $sql="";
        switch($id){
            case "byTarjas":  
                $Par_Sql->unsetColsbyTable($this->_name)->addCols($this->_name,array('Prt_Cod','Pro_Cod','Total'=>"SUM($this->_name.Ptd_Can)"));
                $Par_Sql->where('Prt_Cod=?', $cond['Prt_Cod']);                
                $Par_Sql->group ( "$this->_name.Pro_Cod" );                
                //echo $sql.'<br/>';
                break;
			case "basic":
                $sql=$this->select()->where("$this->_name.Prt_Cod=?",$Par_Sql[0]);
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