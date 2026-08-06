<?php
require_once(dirname(__file__)."/../DATA/libs/AbstractModel.php");
class manifiesto_tecnico extends AbstractModel{
    protected $_name = 'manifiesto_tecnico';
    protected $_primary = array('Mat_Cod');
    protected $_state = 'Mat_Est';
    //protected $_fields = array(); // se declara las filas que va a tener el arreglo en caso de insert o update //opcional

    /* crea una sql basica global para la tabla */
    public function _selectBasic($cond=null,$limits=false){
        return $this->select(true,array('*'))
            ->join('usuario', "usuario.Usu_Cod = $this->_name.Usu_Cod")
            ->join('persona', "persona.Prs_Cod = usuario.Prs_Cod",array('tecnico'=>"concat(prs_cli.Prs_Nom,' ',prs_cli.Prs_Ape)"))
            ->join('manifiesto_nivel_humedad', "manifiesto_nivel_humedad.Hum_Cod = $this->_name.Hum_Cod",array('*'))
            ;
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
                //ChromePhp::log($this->getSqlString($sql));
                break;            

            case "orderByDes":
                $sql->order('Tde_Des asc');
                break;

            case "isActive":
                $sql->where("$this->_name.$this->_state='A'");
                //echo $this->getSqlString($sql)."<br/>";
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