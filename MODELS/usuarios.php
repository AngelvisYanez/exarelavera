<?php 
require_once(dirname(__file__)."/../DATA/libs/AbstractModel.php");
class usuarios extends AbstractModel{
    protected $_name = 'usuarios'; 
    protected $_primary = array('Usu_Cod');	
    protected $_state = 'Usu_Est';
    //protected $_fields = array(); // se declara las filas que va a tener el arreglo en caso de insert o update //opcional
    
    /* crea una sql basica global para la tabla */
    public function _selectBasic($cond=null,$limits=false){         
        return $this->select()->join(
            'persona', "persona.Prs_Cod=$this->_name.Prs_Cod", 
            array('Usuario'=>"CONCAT(Prs_Nom,' ',Prs_Ape)",'Prs_Nom','Prs_Ape','Prs_Ced','Prs_Dir','Prs_Cor'/*, 'Usu_Est'=>"IF (usuarios.Usu_Est='A', 'Activa', 'Inactiva')"*/)
        )->join('sucursal','sucursal.Suc_Cod=usuarios.Suc_Cod',array('Suc_Des')); 
    }
    /* crea una sql standart para jqgrid, condiciones incluidas */
    public function _selectBasicGrid($cond=null,$limits=false){ 
        $sel=$this->_selectBasic();
        $this->sqlByNombre("setEmpCod", $sel);
        if(isset($cond['op_opciones'])){
            if($cond['op_opciones']=="c"){
                $sel->where("Usu_Ced=?",$cond['search']);
            }elseif($cond['op_opciones']=="d"){
                $sel->where("CONCAT(Prs_Nom,' ',Prs_Ape)LIKE ?", "%{$cond['search']}%");
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
                $sql->where("sucursal.Emp_Cod=?",$_SESSION['Ses_Emp_Cod']);
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "setSucCod":                
                $sql->where("usuarios.Suc_Cod=?",$_SESSION['Ses_Suc_Cod']);
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "setPerfiles":                
                $sql->join('usuarperfi', "usuarperfi.Usu_Cod=$this->_name.Usu_Cod", array())
                    ->join('perfiles', "perfiles.Per_Cod=usuarperfi.Per_Cod", array());
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "setAcopioUsuario":                
                $sql->join('acopio_usuario', "acopio_usuario.Usu_Cod=$this->_name.Usu_Cod", array('Aco_Cod'));
                //echo $this->getSqlString($sql)."<br/>";
                break;            
            /*case "isActive":
                $sql->where("$this->_name.$this->_state='A'");
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "notIsAdmin":
                $sql->where("perfiles.Per_Des !='Administrador de Sistemas'");
                break;
            case "notIsInterno":
                $sql->where("persona.Prs_Int !='S'");*
                break;*/
            case "joinPlanta":
                $sql->joinLeft('manifiesto_usuario', "manifiesto_usuario.Usu_Cod=$this->_name.Usu_Cod", array())
                    ->joinLeft('manifiesto_plantas', "manifiesto_plantas.Pla_Cod=manifiesto_usuario.Pla_Cod", array('Planta' => $this->expr("GROUP_CONCAT(DISTINCT manifiesto_plantas.Pla_Nom SEPARATOR ', ')")));
                break;
            case "notIsAdmin":
                $sql->where("perfiles.Per_Des !='Administrador de Sistemas'");
                break;
            case "notIsSpecialProfiles":
                $sql->where("perfiles.Per_Des NOT IN ('Plantas', 'Gerente', 'Contador', 'Admin_Oper', 'Administrador de Sistemas')");
                break;
            case "notHasAdminProfile":
                $sql->where("usuarios.Usu_Cod NOT IN (SELECT up.Usu_Cod FROM usuarperfi up JOIN perfiles p ON p.Per_Cod = up.Per_Cod WHERE p.Per_Des = 'Administrador de Sistemas')");
                break;
            case "notHasPlantasProfile":
                $sql->where("usuarios.Usu_Cod NOT IN (SELECT up.Usu_Cod FROM usuarperfi up JOIN perfiles p ON p.Per_Cod = up.Per_Cod WHERE p.Per_Des = 'Plantas')");
                break;
            case "setOnlyPlantas":
                $sql->where("perfiles.Per_Des = 'Plantas'");
                break;
            default: $this->sqlByParams($id,$sql,array(
                'isActive'=>"$this->_name.$this->_state='A'",
                'isNotCliente'=>"$this->_name.Usu_Tip!='C'",
                "notIsAdmin"=>"perfiles.Per_Des!='Administrador de Sistemas'",
                'notIsInterno'=>"persona.Prs_Int!='S'"
                ));
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