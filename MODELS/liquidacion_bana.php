<?php
require_once(dirname(__file__)."/../DATA/libs/AbstractModel.php");
class liquidacion_bana extends AbstractModel{
    protected $_name = 'liquidacion_bana';
    protected $_primary = array('Lib_Cod');
    protected $_state = 'Lib_Est';
    protected $_detail = array('liquidacion_bana_det');
    protected $_fields = array('Lib_Cod','Prd_Cod','Bam_Cod','Com_Cod','Cop_Cod','Lib_Num','Lib_Int','Lib_Ano','Lib_Sem','Lib_Fec','Lib_Obs','Lib_Cin','Lib_Caj','Lib_Mag','Lib_Pru','Lib_Pra');
    protected $_defaults = array('Bam_Cod');

    public function _selectBasic($cond=null,$limits=false){
        return $this->select(true, array('*', 'Bam_Nom'=>$this->expr("(SELECT DISTINCT CAST(GROUP_CONCAT(DISTINCT CONCAT(bm.Bam_Nom, ' ',bm.Bam_Tam) SEPARATOR ', ') AS CHAR) AS Bam_Noms  FROM productor_tarja AS pt INNER JOIN banano_marca AS bm ON pt.Bam_Cod=bm.Bam_Cod WHERE pt.Prt_Est='A' AND pt.Lib_Cod=$this->_name.Lib_Cod)")))
                ->join('productor_bana',"productor_bana.Prd_Cod=$this->_name.Prd_Cod",array('Prv_Cod'))
                ->join('proveedore', "proveedore.Prv_Cod=productor_bana.Prv_Cod", array())
                ->join('persona', "persona.Prs_Cod=proveedore.Prs_Cod",array('Productor'=>"CONCAT(Prs_Nom,' ',Prs_Ape)",'Prs_Nom','Prs_Ape','Prs_Ced','Prs_Dir'));//->join('banano_marca',"banano_marca.Bam_Cod=$this->_name.Bam_Cod",array('Bam_Nom','Bam_Des','Bam_Tam'));
    }
    public function _selectBasicGrid($cond=null,$limits=false){
        $sel=$this->_selectBasic();
        $this->sqlByNombre("setEmpCod", $sel);
        if($this->hasVal($cond,'Lib_Ano')) $sel->where("$this->_name.Lib_Ano=?",$cond['Lib_Ano']);
        if($this->hasVal($cond,'Lib_Sem')) $sel->where("$this->_name.Lib_Sem=?",$cond['Lib_Sem']);
        //if($this->hasVal($cond,'Bam_Cod')) $sel->where("$this->_name.Bam_Cod=?",$cond['Bam_Cod']);
        if($this->hasVal($cond,'Prd_Cod')) $sel->where("$this->_name.Prd_Cod=?",$cond['Prd_Cod']);
        if($this->hasVal($cond,'Lib_Num')) $sel->where("$this->_name.Lib_Num=?",$cond['Lib_Num']);
        return $sel;
    }
    public function formatData($data, $type, $allData=null){
        if($type=='I'){
            unset($data['Lib_Cod']);
        }else{
            if(isset($allData['Lib_Est']))
                $data['Lib_Est']=$allData['Lib_Est'];
        }
        return $data;
    }
    public function sqlByNombre($id,$Par_Sql,$cond=null){
        if(is_object($Par_Sql)){ $sql=$Par_Sql; $Par_Sql=$cond; }else $sql='';
        switch($id){
            case "":
                $sql="";
                //echo $sql.'<br/>';
                break;
            case "setEmpCod":
                //$sql->where("banano_marca.Emp_Cod=?",$_SESSION['Ses_Emp_Cod']);
                $sql->where("proveedore.Emp_Cod=?",$_SESSION['Ses_Emp_Cod']);
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "getNext":
                $sql=$this->_selectBasic()->unsetCols()->addCols('', array('next'=>'IF(MAX(Lib_Num)IS NULL, 1, MAX(Lib_Num)+1 )')) ;//->join('banano_marca',"banano_marca.Bam_Cod=$this->_name.Bam_Cod",array());
                $this->sqlByNombre("setEmpCod", $sql); //$sql->where("banano_marca.Emp_Cod=?",$_SESSION['Ses_Emp_Cod']);
                //echo $sql.'<br/>';
                break;
            case "getNext2":
                $sql=$this->_selectBasic()->unsetCols()->addCols('', array('next'=>'IF(MAX(Lib_Num)IS NULL, 1, MAX(Lib_Num)+1 )'));//->join('banano_marca',"banano_marca.Bam_Cod=$this->_name.Bam_Cod",array());
                $this->sqlByNombre("setEmpCod", $sql); //$sql->where("banano_marca.Emp_Cod=?",$_SESSION['Ses_Emp_Cod']);
                $sql->where("Lib_Mag=?",$Par_Sql[0]);
                //echo $sql.'<br/>';
                break;
            case "getByLibNum":
                $sql=$this->_selectBasic();//->join('banano_marca',"banano_marca.Bam_Cod=$this->_name.Bam_Cod",array());
                $this->sqlByNombre("setEmpCod", $sql); //$sql->where("banano_marca.Emp_Cod=?",$_SESSION['Ses_Emp_Cod']);
                $sql->where("$this->_name.Lib_Num=?",$Par_Sql['Lib_Num']);
                if(isset($Par_Sql['Lib_Cod'])) $sql->where("$this->_name.Lib_Cod!=?",$Par_Sql['Lib_Cod']);
                //echo $sql.'<br/>';
                break;
            case "getByLibNum2":
                $sql=$this->_selectBasic();//->join('banano_marca',"banano_marca.Bam_Cod=$this->_name.Bam_Cod",array());
                $this->sqlByNombre("setEmpCod", $sql); //$sql->where("banano_marca.Emp_Cod=?",$_SESSION['Ses_Emp_Cod']);
                $sql->where("$this->_name.Lib_Num=?",$Par_Sql['Lib_Num']);
                $sql->where("$this->_name.Lib_Mag=?",$Par_Sql['Lib_Mag']);
                if(isset($Par_Sql['Lib_Cod'])) $sql->where("$this->_name.Lib_Cod!=?",$Par_Sql['Lib_Cod']);
                //echo $sql.'<br/>';
                break;
            /*case "setProductor":
                $sql->join('productor_bana',"productor_bana.Prd_Cod=$this->_name.Prd_Cod",array('Prv_Cod'))
                        ->join('proveedore', "proveedore.Prv_Cod=productor_bana.Prv_Cod", array())
                        ->join('persona', "persona.Prs_Cod=proveedore.Prs_Cod",array('Productor'=>"CONCAT(Prs_Nom,' ',Prs_Ape)",'Prs_Nom','Prs_Ape','Prs_Ced','Prs_Dir'));
                //echo $sql.'<br/>';
                break;*/
             case "setTotales":
                $ingre="SUM(IF(Lid_Tip='I',Lid_Imp,0))";
                $descu="SUM(IF(Lid_Tip='D',Lid_Imp,0))";
                $sql->join('liquidacion_bana_det',"liquidacion_bana_det.Lib_Cod=$this->_name.Lib_Cod",array('Ingresos'=>$this->expr($ingre),'Descuentos'=>$this->expr($descu),'Total'=>$this->expr("$ingre-$descu")));
                //echo $sql.'<br/>';
                break;
            default: $this->sqlByParams($id,$sql,array(
                    'isActive'=>"$this->_name.$this->_state='A'"
                )); //echo $this->getSqlString($sql)."<br/>";
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