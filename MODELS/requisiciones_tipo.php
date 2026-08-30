<?php 
require_once(dirname(__file__)."/../DATA/libs/AbstractModel.php");
class requisiciones_tipo extends AbstractModel{
    protected $_name = 'requisiciones_tipo'; 
    protected $_primary = array('Req_Cod', 'Rqd_Int', 'Pro_Cod');	
    protected $_state = '';
    //protected $_fields = array(); // se declara las filas que va a tener el arreglo en caso de insert o update //opcional
    
    /* crea una sql basica global para la tabla */
    public function _selectBasic($cond=null,$limits=false){         
        return $this->select()->join(
            'requisiciones', "requisiciones.Req_Cod=$this->_name.Req_Cod", array()
        ); 
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
            case "getTiposRequisiciones":
                $sql="SELECT requisiciones_tipo.Rtp_Cod, requisiciones_tipo.Rtp_Des  FROM requisiciones_tipo
                        WHERE Emp_Cod = $Par_Sql[Emp_Cod]";
                        //ChromePhp::log($sql);
                //echo $this->getSqlString($sql)."<br/>";
                break;
            default: throw new Exception ("No existe la sql denominada $id!");
        }
        //echo $this->getSqlString($sql)."<br/>";
        return $sql;
    }
    /* crea sentencia por id numero sql */
    public function sqlByNumero($id,$Par_Sql,$cond=null){
        if(is_object($Par_Sql)){ $sql=$Par_Sql; $Par_Sql=$cond; }else $sql='';
        switch($id){
            case 0:
                $sql="SELECT requisiciones_tipo.Rtp_Cod, requisiciones_tipo.Rtp_Des 
                FROM requisiciones_tipo
                        WHERE Emp_Cod = $Par_Sql[Emp_Cod] AND Rtp_Est = 'A'";
                        //ChromePhp::log($sql);
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case 1:
                $sql="SELECT requisiciones_tipo.Rtp_Cod, requisiciones_tipo.Rtp_Des, 
                IF(Rtp_Est='A','ACTIVO','INACTIVO') AS Rtp_Est  
                FROM requisiciones_tipo
                        WHERE Emp_Cod = $Par_Sql[Emp_Cod]";
                        //ChromePhp::log($sql);
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case 2:
                $sql="INSERT INTO requisiciones_tipo ( Rtp_Des, Emp_Cod ) VALUES
                         ( '$Par_Sql[Rtp_Des]',$Par_Sql[Emp_Cod])";
                        //ChromePhp::log($sql);
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case 3:
                $sql="SELECT requisiciones_tipo.Rtp_Cod, requisiciones_tipo.Rtp_Des, Rtp_Est
                    FROM requisiciones_tipo
                    WHERE Rtp_Cod = $Par_Sql[Rtp_Cod]";
                    //ChromePhp::log($sql);
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case 4:
                $sql="UPDATE requisiciones_tipo 
                SET Rtp_Des='$Par_Sql[Rtp_Des]',Rtp_Est='$Par_Sql[Rtp_Est]' 
                WHERE Rtp_Cod =$Par_Sql[Rtp_Cod]";
                    //ChromePhp::log($sql);
                //echo $this->getSqlString($sql)."<br/>";
                break;
            default: throw new Exception ("No existe la sql numero $id!");
        }
        //echo $this->getSqlString($sql)."<br/>";
        return $sql;
    }
}