<?php
require_once(dirname(__file__)."/../DATA/libs/AbstractModel.php");
class liquidacion_bana_det extends AbstractModel{
    protected $_name = 'liquidacion_bana_det';
    protected $_primary = array('Lib_Cod','Lid_Tip','Lid_Grp','Lib_Int');
    protected $_state = '';

    public function _selectBasic($cond=null,$limits=false){
        return $this->select()
            ->join('liquidacion_bana',"liquidacion_bana.Lib_Cod=$this->_name.Lib_Cod",array('Lib_Pru'));
    }
    public function _selectBasicGrid($cond=null,$limits=false){
        $sel=$this->_selectBasic();
        return $sel;
    }
    public function formatData($data, $type, $allData=null){
        return ($type=='I')?array(
            'Lib_Cod'=>$data['Lib_Cod'],
            'Lid_Tip'=> strlen($data['Lid_Tip'])>'0'?$data['Lid_Tip']:null,
            'Lid_Grp'=>(!isset($data['Lid_Grp'])||$data['Lid_Grp']===''||is_null($data['Lid_Grp'])?-1:$data['Lid_Grp']),
            'Lid_Int'=>$data['Lid_Int'],
            'Pro_Cod'=>(!isset($data['Pro_Cod'])||empty($data['Pro_Cod'])?null:$data['Pro_Cod']),
            'Lid_Des'=>$data['Lid_Des'],
            'Lid_Can'=>$data['Lid_Can'],
            'Lid_Pru'=>(!isset($data['Lid_Pru']))?0:$data['Lid_Pru'],
            'Lid_Imp'=>(!isset($data['Lid_Imp']))?0:$data['Lid_Imp'],
        ):$data;
    }
    public function sqlByNombre($id,$Par_Sql,$cond=null){
        if(is_object($Par_Sql)){ $sql=$Par_Sql; $Par_Sql=$cond; }else $sql='';
        switch($id){
            case "":
                $sql="";
                //echo $sql.'<br/>';
                break;
			case "noProducto":
                $sql->where("$this->_name.Pro_Cod IS NULL",null);
                //echo $sql.'<br/>';
                break;
            case "setProducto":
                $sql->join('producto', "producto.Pro_Cod = $this->_name.Pro_Cod",array('*','Producto'=>"CONCAT(Ite_Lar,IF(Pro_Obs IS NULL OR TRIM(Pro_Obs)='' OR Pro_Obs=Ite_Lar,'',CAST(CONCAT(' - ',Pro_Obs)AS CHAR) ) )"))
                    ->join('item', "item.Ite_Cod = producto.Ite_Cod", array('Cat_Cod','Ite_Lar','Ite_Cor','Ite_Est'))
                    ->join('categorias', "categorias.Cat_Cod = item.Cat_Cod", array('Cat_Des'))
                    ->joinLeft('marca', "marca.Mar_Cod = producto.Ite_Cod", array('Mar_Des'))
                    ->join('adquisicio', "adquisicio.Adq_Cod = producto.Adq_Cod", array('Adq_Des'))
                    ->join('unidad', "unidad.Uni_Cod = producto.Uni_Cod", array('Uni_Des'))
                    ->join('ubicacion', "ubicacion.Ubi_Cod = producto.Ubi_Cod", array('Ubi_Des'))
                    ->join('iva', "iva.Iva_Cod = producto.Iva_Cod", array('Iva_Por'));
                //echo $sql.'<br/>';
                break;
            default: throw new Exception ("No existe la sql denominada $id!");
        }
        //echo $sql."<br/>";
        return $sql;
    }
    public function sqlByNumero($id,$Par_Sql,$cond=null){
        if(is_object($Par_Sql)){ $sql=$Par_Sql; $Par_Sql=$cond; }else $sql='';
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