<?php
require_once(dirname(__file__)."/../DATA/libs/AbstractModel.php");
class retcre_vta extends AbstractModel{
    protected $_name = 'retcre_vta';
    protected $_primary = array('Rvt_Cod');
    protected $_state = 'Rvt_Est';
    //protected $_fields = array(); // se declara las filas que va a tener el arreglo en caso de insert o update //opcional

    /* crea una sql basica global para la tabla */
    public function _selectBasic($cond=null,$limits=false){
        return $this->select();
    }
    /* crea una sql standart para jqgrid, condiciones incluidas */
    public function _selectBasicGrid($cond=null,$limits=false){
        $sel=$this->_selectBasic();
        $this->sqlByNombre("setEmpCod", $sel);
        if($cond['op_opciones']){
            if($cond['op_opciones']=="c"){
                $sel->where("CONCAT(pers.Prs_Nom,' ',pers.Prs_Ape)LIKE ?", "%{$cond['search']}%");
                $sel->where("Rvt_Est='A'");
            }elseif($cond['op_opciones']=="d"){
                $sel->where("Rvt_Num=?",$cond['search']);
            }elseif($cond['op_opciones']=="e"){
                $sel->where("Rvt_Est='I'");
                //$sel->where("Rvt_Est=?",$cond['search']);
            }elseif ($cond['op_opciones']=='f'){
                $sel->where("Rvt_Fec BETWEEN ? AND ?", array("{$cond['Fec_Ini']} 00:00:00", "{$cond['Fec_Fin']} 23:59:59"));
                $sel->where("CONCAT(pers.Prs_Nom,' ',pers.Prs_Ape)LIKE ?", "%{$cond['search']}%");
            }else{
                $sel->where("(UPPER(CONCAT(persona.Prs_Ape,' ',persona.Prs_Nom)) LIKE UPPER(?)) OR UPPER(Cli_Fac) LIKE UPPER(?)","%$cond[search]%");
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
                $sql->where("clnt.Emp_Cod=?",$_SESSION['Ses_Emp_Cod']);
                //echo $this->getSqlString($sql)."<br/>";
                break;
             case "addTipo":
                $sql->addCols(null,array('tipo'=>"IF($this->_name.Rvt_Tem= 'E','Electronica','Fisica')"));
                //echo $this->getSqlString($sql)."<br/>";
                break;

            case "isActive":
                $sql->where("$this->_name.$this->_state='A'");

                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "byCliente":
                $sql->join(array('clnt'=>'cliente'),"clnt.Cli_Cod = $this->_name.Cli_Cod");
                //echo $this->getSqlString($sql)."<br/>";tipo_compr
                break;
            case "byProveedor":
                $sql->join(array('prveed'=>'proveedore'),"prveed.Prv_Cod = $this->_name.Prv_Cod");
                //echo $this->getSqlString($sql)."<br/>";tipo_compr Bank_Cod
                break;
            case "byDetPlan":
                $sql->join(array('detP'=>'det_plan'),"detP.Pld_Cod = $this->_name.Pld_Cod", array('cuenta'=>"CONCAT(Pld_Cdc,' ',Pld_Des)","detP.Pld_Cod, detP.Pld_Rec, detP.Pla_Cod, detP.Pld_Cdc, detP.Pld_Des, detP.Pld_Tip, detP.Pld_Est, detP.Pld_Deb, detP.Pld_Cre"));
                //echo $this->getSqlString($sql)."<br/>";tipo_compr Bank_Cod
                break;
            case "byPersonaPrv":
                $sql->join(array('prsnt'=>'persona'),"prsnt.Prs_Cod = prveed.Prs_Cod", array('proveedor'=>"CONCAT(prsnt.Prs_Ape,' ',prsnt.Prs_Nom)"));
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "byPersona":
                $sql->join(array('pers'=>'persona'),"pers.Prs_Cod = clnt.Prs_Cod", array('cliente'=>"CONCAT(pers.Prs_Ape,' ',pers.Prs_Nom)",'ruc'=>"pers.Prs_Ced","pers.Prs_Cod, pers.Prs_Cor, pers.Prs_Ced, pers.Prs_Ape, pers.Prs_Nom, pers.Prs_Dir"));
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "byUsuarios":
                $sql->join(array('usu'=>'usuarios'),"usu.Usu_Cod = $this->_name.Usu_Cod");
                //echo $this->getSqlString($sql)."<br/>";tipo_compr
                break;
            case "byPersonaUsu":
                $sql->join(array('prs'=>'persona'),"prs.Prs_Cod = usu.Prs_Cod", array('usuario'=>"CONCAT(prs.Prs_Ape,' ',prs.Prs_Nom)","prs.Prs_Cod, prs.Prs_Cor, prs.Prs_Ced, prs.Prs_Ape, prs.Prs_Nom, prs.Prs_Dir"));
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "byDetalleRet":
                $sql->join(array('retDet'=>'retcrevta_det'),"retDet.Rvt_Cod = $this->_name.Rvt_Cod");
                //echo $this->getSqlString($sql)."<br/>";tipo_compr
                break;
            case "byRentaIva":
                $sql->join(array('rentIva'=>'renta_iva'),"rentIva.Ren_Cod = retDet.Ren_Cod");
                //echo $this->getSqlString($sql)."<br/>";tipo_compr
                break;
            case "setRenTot":
                $renta="(IF(Ren_Ret='R',".$this->castDecimal("(retDet.Rvt_Bas*rentIva.Ren_Por)/100").",0))";
                $iva="(IF(Ren_Ret='I',".$this->castDecimal("(retDet.Rvt_Bas*rentIva.Ren_Por)/100").",0))";
                $total="( $renta + $iva )";
                //SUM(IF(Ren_Ret='R',(Rvt_Bas*Ren_Por)/100,0))as renTot,SUM(IF(Ren_Ret='I',(Rvt_Bas*Ren_Por)/100,0))as ivaTot
                $sql->addCols(null,array('renTot'=>$this->expr($this->castDecimal("SUM($renta)")),
                                           'ivaTot'=>new Zend_Db_Expr($this->castDecimal("SUM($iva)")),
                                           'Total'=>new Zend_Db_Expr($this->castDecimal("SUM($total)")),
                                         ));

                $sql->group("$this->_name.Rvt_Cod");
                break;

            case "byPeriodo":
                $sql->join(array('perioC'=>'perio_cont'),"perioC.Pec_Cod = $this->_name.Pec_Cod");
                //echo $this->getSqlString($sql)."<br/>";tipo_compr
                break;
            case "byTipoCompra":
                $sql->join(array('tipoC'=>'tipo_compr'),"tipoC.Tic_Cod = $this->_name.Tic_Cod");
                //echo $this->getSqlString($sql)."<br/>";tipo_compr
                break;
            case "getNext":
                $sql=$this->select(false, array());
                $sql->from('retcre_vta', array('total' => 'IFNULL(MAX(Rvt_Cod),0)'));
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
                $sql="";
                //echo $this->getSqlString($sql)."<br/>";
                break;
            default: throw new Exception ("No existe la sql numero $id!");
        }
        //echo $this->getSqlString($sql)."<br/>";
        return $sql;
    }
}