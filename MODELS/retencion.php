<?php 
use \Exception;
require_once(dirname(__file__)."/../DATA/libs/AbstractModel.php");
class retencion extends AbstractModel{
    protected $_name = 'retencion'; 
    protected $_primary = array('Ret_Cod');	
    protected $_state = 'Ret_Est';
    //protected $_fields = array(); // se declara las filas que va a tener el arreglo en caso de insert o update //opcional
    
    private $depo;
    private $base;
    private $renta;
    private $iva;
    private $total;
    
    public function initCalculos(){
        $this->depo=$this->castDecimal("SUM(IF(Ret_Dep IS NULL,0,Ret_Dep))");
        $this->base=$this->castDecimal("SUM(Ret_Bas)");
        $this->renta=$this->castDecimal("SUM(IF(Ret_Imp='R',IF(Ren_Por>0,(Ret_Bas*Ren_Por/100),0),0))");
        $this->iva=$this->castDecimal("SUM(IF(Ret_Imp='I',IF(Ren_Por>0,(Ret_Bas*Ren_Por/100),0),0))"); 
        $this->total=$this->castDecimal("SUM(IF(Ren_Por>0,(Ret_Bas*Ren_Por/100),0))");    
    }
    
    /* crea una sql basica global para la tabla */
    public function _selectBasic($cond=null,$limits=false){         
        return $this->select()
            ->addCols(null,array(
                'Secuencia'     =>$this->expr("CONCAT(Suc_Sri,'-',Pun_Sri,'-',LPAD(CAST(Ret_Num AS CHAR),9,'0'))"),
                'Autorizacion'  =>$this->expr("IF(Ret_Xml IS NULL OR TRIM(Ret_Xml)='', Aut_Sri, IF(Ret_Sri IS NULL OR TRIM(Ret_Sri)='','PENDIENTE',Ret_Sri))"),
                'Autorizacion1'  =>$this->expr("IF(Ret_Xml IS NULL OR TRIM(Ret_Xml)='', Aut_Sri, IF(Ret_Sri IS NULL OR TRIM(Ret_Sri)='','',Ret_Sri))")
            ))
            ->join('compras', "compras.Cop_Cod=$this->_name.Cop_Cod", array('compras.Prv_Cod','Cop_Num','Cop_Fec'))
            ->join('tipo_compr', "tipo_compr.Tic_Cod=compras.Tic_Cod", array('Tic_Sri'=>$this->expr("LPAD(CAST(Tic_Sri AS CHAR),2,'0')"),'Tic_Des'))
            ->join('proveedore', "proveedore.Prv_Cod=compras.Prv_Cod", array('Prv_Tic'))
            ->join('persona', "persona.Prs_Cod=proveedore.Prs_Cod",array('Proveedor'=>$this->expr("IF(Prv_Com IS NULL OR Prv_Com='',".$this->concat(array('persona.Prs_Ape','persona.Prs_Nom')).",Prv_Com)"),'Ruc'=>'Prs_Ced'))
            ->join('autorizaci', "autorizaci.Aut_Cod=$this->_name.Aut_Cod", array())
            ->join('puntos_imp', "puntos_imp.Pun_Cod=autorizaci.Pun_Cod", array())
            ->join('sucursal', "sucursal.Suc_Cod=puntos_imp.Suc_Cod", array())
            ->join('vendedor', "vendedor.Vnd_Cod=$this->_name.Vnd_Cod", array())
            ->join(array('persona_ven'=>'persona'), "persona_ven.Prs_Cod=vendedor.Prs_Cod", array('Vendedor'=>new Zend_Db_Expr("CONCAT(persona_ven.Prs_Ape,' ',persona_ven.Prs_Nom)")));
    }
    /* crea una sql standart para jqgrid, condiciones incluidas */
    public function _selectBasicGrid($cond=null,$limits=false){ 
        $sel=$this->_selectBasic();  
        $this->sqlByNombre("setEmpCod", $sel);
        if(isset($cond['op_opciones'])){
            switch($cond['op_opciones']) {
                case 'd':
                    if(strlen($cond["search"])<=9)
                        $sel->where("$this->_name.Ret_Num = ?",$cond["search"]);
                    else
                        $sel->where("CONCAT(Suc_Sri,'-',Pun_Sri,'-',LPAD(CAST(Ret_Num AS CHAR),9,'0')) = ?",$cond["search"]);
                    break;
                case 'c':
                    $sel->where("persona.Prs_Ced LIKE ?","$cond[search]%");
                    break;
                case 'p':
                    $sel->where("(UPPER(CONCAT(persona.Prs_Ape,' ',persona.Prs_Nom)) LIKE UPPER(?)) OR (UPPER(Prv_Com) LIKE UPPER(?) )","%$cond[search]%");
                    break;
                case 'b':
                    $sel->where("compras.Cop_Num=?",$cond['search']);
                    break;
            }
            if(($cond['op_opciones']!='d' && $cond['op_opciones']!='d') && ( isset($cond['Year']) && strlen($cond['Year'])==4 && isset($cond['Month'])  ) ){
                $sel->where("YEAR(Ret_Fec)=?",$cond['Year']);
                if(strlen($cond['Month'])>0)$sel->where("MONTH(Ret_Fec)=?",$cond['Month']);
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
        $sql=(is_object($Par_Sql)?$Par_Sql:'');
        switch($id){
            case "":
                $sql="";
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "setEmpCod":                
                $sql->where("proveedore.Emp_Cod=?",$_SESSION['Ses_Emp_Cod']);
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "setSucCod":                
                $sql->where("sucursal.Suc_Cod=?",$_SESSION['Ses_Suc_Cod']);
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "isActive":
                $sql->where("$this->_name.$this->_state='A'");
                //echo $this->getSqlString($sql)."<br/>";
                break;
            
            case "setTotales":  
                $this->initCalculos();
                $sql->join('det_retenc', "det_retenc.Ret_Cod=$this->_name.Ret_Cod", array())
                    ->join('renta_iva', "renta_iva.Ren_Cod=det_retenc.Ren_Cod", array())
                    ->addCols(null,array(
                        'Tot_Depo'=>$this->expr($this->depo),
                        'Tot_Base'=>$this->expr($this->base),
                        'Tot_Renta'=>$this->expr($this->renta),
                        'Tot_Iva'=>$this->expr($this->iva),
                        'Total'=>$this->expr($this->total)
                    ));//->group("$this->_name.Ret_Cod");
                /*$total=$this->select(false)->from(array('tbl'=>$this->expr('('.$this->getSqlString($sql).')')),array('*'))
                    ->addCols(null,array(
                        'Tot_Renta'=>$this->expr("SUM(tbl.Renta)"),
                        'Tot_Iva'=>$this->expr("SUM(tbl.Iva)"),
                        'Total'=>$this->expr("SUM(tbl.Renta+tbl.Iva)")
                    ))->group("tbl.Ret_Cod");
                $sql->setDataSelect($total->getDataSelect()); */               
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
    public function nextNum($Par_Sql){
        $num='Ret_Num';
        $notExist=$this->select(false)
            ->from(array('n'=>$this->_name),array($this->expr('NULL')))
            ->join(array('na'=>'autorizaci'), "na.Aut_Cod=n.Aut_Cod", array())
            ->join(array('np'=>'puntos_imp'), "np.Pun_Cod=na.Pun_Cod", array())
            ->where("n.$num=t.$num+1 AND np.Suc_Cod=$_SESSION[Ses_Suc_Cod] AND na.Aut_Sri='$Par_Sql[Aut_Sri]' AND na.Tic_Cod=$Par_Sql[Tic_Cod] AND n.$num BETWEEN $Par_Sql[Aut_Ini] AND $Par_Sql[Aut_Fin]");
        $minimo=$this->select(false)
            ->from(array('t'=>$this->_name),array($this->expr("MIN(t.$num)+1")))
            ->join(array('ta'=>'autorizaci'), "ta.Aut_Cod=t.Aut_Cod", array())
            ->join(array('tp'=>'puntos_imp'), "tp.Pun_Cod=ta.Pun_Cod", array())
            ->where("tp.Suc_Cod=$_SESSION[Ses_Suc_Cod] AND ta.Aut_Sri='$Par_Sql[Aut_Sri]' AND ta.Tic_Cod=$Par_Sql[Tic_Cod] AND t.$num BETWEEN $Par_Sql[Aut_Ini] AND $Par_Sql[Aut_Fin]")
            ->where("NOT EXISTS(\n\t".$this->getSqlString($notExist)."\n\t)");
        return $this->select(true,array('next'=>$this->expr("CASE WHEN MAX($num)IS NOT NULL AND MAX($num)>=$Par_Sql[Aut_Fin] THEN(\n ".$this->getSqlString($minimo)."\n )\n ELSE IFNULL(MAX($num),$Par_Sql[Aut_Ini]-1)+1 END")))
            ->join('autorizaci', "autorizaci.Aut_Cod=$this->_name.Aut_Cod", array())
            ->join('puntos_imp', "puntos_imp.Pun_Cod=autorizaci.Pun_Cod", array())
            ->where("Suc_Cod=$_SESSION[Ses_Suc_Cod] AND autorizaci.Aut_Sri='$Par_Sql[Aut_Sri]' AND autorizaci.Tic_Cod=$Par_Sql[Tic_Cod] AND $num BETWEEN $Par_Sql[Aut_Ini] AND $Par_Sql[Aut_Fin]");
    }
}