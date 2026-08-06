<?php
require_once(dirname(__file__)."/../DATA/libs/AbstractModel.php");
class productor_tarja extends AbstractModel{
    protected $_name = 'productor_tarja';
    protected $_primary = array('Prt_Cod');
    protected $_state = 'Prt_Est';

    public function _selectBasic($cond=null,$limits=false){
        return $this->select()->join('productor_haci',"productor_haci.Prh_Cod=$this->_name.Prh_Cod",array('Prd_Cod','Prh_Nom','Prh_Mag'))
                ->join('banano_marca',"banano_marca.Bam_Cod=$this->_name.Bam_Cod",array('Bam_Nom','Bam_Des','Bam_Tam'));
    }
    public function _selectBasicGrid($cond=null,$limits=false){
        $sel=$this->_selectBasic();
        $this->sqlByNombre("setEmpCod", $sel);
        if($this->hasVal($cond,'Prt_Ano')) $sel->where("$this->_name.Prt_Ano=?",$cond['Prt_Ano']);
        if($this->hasVal($cond,'Prt_Sem')) $sel->where("$this->_name.Prt_Sem=?",$cond['Prt_Sem']);
        if($this->hasVal($cond,'Bam_Cod')) $sel->where("$this->_name.Bam_Cod=?",$cond['Bam_Cod']);
        if($this->hasVal($cond,'Prd_Cod')) $sel->where("productor_haci.Prd_Cod=?",$cond['Prd_Cod']);
        if($this->hasVal($cond,'Prt_Num')) $sel->where("$this->_name.Prt_Num=?",$cond['Prt_Num']);
        return $sel;
    }
    public function formatData($data, $type, $allData=null){
        return ($type=='I')?$data:$data;
    }
    public function sqlByNombre($id,$Par_Sql,$cond=null){
        if(is_object($Par_Sql)){ $sql=$Par_Sql; $Par_Sql=$cond; }else $sql='';
        switch($id){
            case "":
                $sql="";
                //echo $sql.'<br/>';
                break;
            case "setEmpCod":
                $sql->where("banano_marca.Emp_Cod=?",$_SESSION['Ses_Emp_Cod']);
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "isActive":
                $sql->where("$this->_name.$this->_state='A'");
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "setByNcoCod":
                $sql->where("$this->_name.Nco_Cod=?",$cond['Nco_Cod']);
                //echo $sql.'<br/>';
                break;
            case "setByLibCod":
                $sql->where("$this->_name.Lib_Cod=?",$cond['Lib_Cod']);
                //echo $sql.'<br/>';
                break;
            case "notHasLiquidacion":
                $sql->where("$this->_name.Lib_Cod IS NULL");
                //echo $sql.'<br/>';
                break;
            case "getNext":
                $sql=$this->select(true, array('next'=>'IF(MAX(Prt_Num)IS NULL, 1, MAX(Prt_Num)+1 )'))->join('banano_marca',"banano_marca.Bam_Cod=$this->_name.Bam_Cod",array());
                $sql->where("banano_marca.Emp_Cod=?",$_SESSION['Ses_Emp_Cod']);
                //echo $sql.'<br/>';
                break;
            case "getByPrtNum":
                $sql=$this->select()->join('banano_marca',"banano_marca.Bam_Cod=$this->_name.Bam_Cod",array());
                $sql->where("banano_marca.Emp_Cod=?",$_SESSION['Ses_Emp_Cod']);
                $sql->where("$this->_name.Prt_Num=?",$Par_Sql['Prt_Num']);
                if(isset($Par_Sql['Prt_Cod'])) $sql->where("$this->_name.Prt_Cod!=?",$Par_Sql['Prt_Cod']);
                //echo $sql.'<br/>';
                break;
            case "getSumatoriasByLib":
                $sql=$this->select(true,array(
                    'Prt_Cad'=>'SUM(Prt_Cad)',
                    'Prt_Car'=>'SUM(Prt_Car)',
                    'Prt_Cah'=>'SUM(Prt_Cah)',
                    'Prt_Caf'=>'SUM(Prt_Caf)',
                    'Prt_Caj'=>'SUM(Prt_Caj)',
                ))->group("$this->_name.Lib_Cod");
                $this->sqlByNombre('setByLibCod',$sql,$Par_Sql);
                //echo $sql.'<br/>';
                break;
            case "getHaciendasByLib":
                $sql=$this->select(true,array('Prh_Cod'=>"$this->_name.Prh_Cod",'Prt_Car'=>'SUM(Prt_Car)'))
                    ->join('productor_haci',"productor_haci.Prh_Cod=$this->_name.Prh_Cod",array('Prh_Nom','Prh_Mag'))
                    ->group("$this->_name.Lib_Cod");
                $this->sqlByNombre('setByLibCod',$sql,$Par_Sql);
                //echo $sql.'<br/>';
                break;
            case "getContenedoresByLib":
                $sql=$this->select(true,array('Prh_Cod'=>"$this->_name.Prh_Cod",'Prt_Car'=>'SUM(Prt_Car)'))
                    ->join('exportacion_container',"exportacion_container.Exc_Cod=$this->_name.Exc_Cod",array('Nave'=>'Exc_Vap','Contenedor'=>'Exc_Con'))
                    ->group("$this->_name.Exc_Cod");
                $this->sqlByNombre('setByLibCod',$sql,$Par_Sql);
                //echo $sql.'<br/>';
                break;
            case "getContenedoresByLib2":
                $sql=$this->select(true,array('Prh_Cod'=>"$this->_name.Prh_Cod",'Prt_Car'=>'SUM(Prt_Car)'))
                    ->join('naviera_container',"naviera_container.Nco_Cod=$this->_name.Nco_Cod",array('Contenedor'=>'Nco_Nom'))
                    ->join('naviera_vapor',"naviera_vapor.Vap_Cod=naviera_container.Vap_Cod",array('Nave'=>'Vap_Nom'))
                    ->group("$this->_name.Nco_Cod");
                $this->sqlByNombre('setByLibCod',$sql,$Par_Sql);
                //echo $sql.'<br/>';
                break;
            case "setProductor":
                $sql->join('productor_bana',"productor_bana.Prd_Cod=productor_haci.Prd_Cod",array('Prv_Cod','Prd_Cau'))
                        ->join('proveedore', "proveedore.Prv_Cod=productor_bana.Prv_Cod", array())
                        ->join('persona', "persona.Prs_Cod=proveedore.Prs_Cod",array('Productor'=>"CONCAT(Prs_Nom,' ',Prs_Ape)",'Prs_Nom','Prs_Ape','Prs_Ced','Prs_Dir'));
                //echo $sql.'<br/>';
                break;
            case "totalesByContainer":
                $sql=$this->select(true,array('Prt_Tip'=>"$this->_name.Prt_Tip",'total'=>'SUM(Prt_Car)'))
                    ->group("$this->_name.Prt_Tip");
                $this->sqlByNombre('setByNcoCod',$sql,$Par_Sql);
                $this->sqlByNombre('isActive',$sql,$Par_Sql);
                //echo $sql.'<br/>';
                break;
            case "setByPlanificacion":
                $sql->join('naviera_container',"naviera_container.Nco_Cod=$this->_name.Nco_Cod",array())
                    ->join('exporta_planif_container',"naviera_container.Nco_Cod=exporta_planif_container.Nco_Cod", array())
                    ->join('exporta_planif_det',"exporta_planif_det.Pde_Cod=exporta_planif_container.Pde_Cod", array());

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