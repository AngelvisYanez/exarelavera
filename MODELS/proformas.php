<?php
require_once(dirname(__file__)."/../DATA/libs/AbstractModel.php");
class proformas extends AbstractModel{
    protected $_name = 'proformas';
    protected $_primary = array('Prf_Cod');
    protected $_state = 'Prf_Est';

    public function _selectBasic($cond=null,$limits=false){
        return $this->select()
            ->addCols('', array('Prf_Estado'=>"IF (proformas.Prf_Est='A', 'Activa', 'Inactiva')") )
            ->join('cliente', "cliente.Cli_Cod=$this->_name.Cli_Cod", array())
            ->join('persona', "persona.Prs_Cod=cliente.Prs_Cod", array('Cliente'=>"CONCAT(persona.Prs_Nom,' ',persona.Prs_Ape)",'Prs_Nom','Prs_Ape','Prs_Ced','Prs_Dir','Prs_Cor'))->join('vendedor', "vendedor.Vnd_Cod=$this->_name.Vnd_Cod",array())
            ->join(
                array('persona_vnd'=>'persona'), "persona_vnd.Prs_Cod=vendedor.Prs_Cod", array('Vendedor'=>"CONCAT(persona_vnd.Prs_Nom,' ',persona_vnd.Prs_Ape)")
            )
            ->join('puntos_imp', "puntos_imp.Pun_Cod=vendedor.Pun_Cod", array())
            ->where("puntos_imp.Suc_Cod=?",$_SESSION['Ses_Suc_Cod'])
	    ->order("Prf_Num DESC");
    }
    
    public function _selectBasicGrid($cond=null,$limits=false){
        $sel=$this->_selectBasic();
        $this->sqlByNombre("setEmpCod", $sel);
        if(isset($cond['op_opciones'])){
            if($cond['op_opciones']=="h"){
                $sel->where("CONCAT(persona.Prs_Nom,' ',persona.Prs_Ape)LIKE ?", "%{$cond['search']}%");
            }elseif($cond['op_opciones']=="c"){
                $sel->where("Prf_Num=?",$cond['search']);
            }elseif($cond['op_opciones']=="d"){
                $sel->where("CONCAT(persona.Prs_Nom,' ',persona.Prs_Ape)LIKE ?", "%{$cond['search']}%");
            }else{
                if(empty($cond['desde'])&& empty($cond['hasta'])){
                    $sel->where("Prf_Fec >= ? AND  Prf_Fec <= ?", array($cond['desdeT']??'', $cond['hastaT']??''));
                }else{
                    $sel->where("Prf_Fec >= ? AND  Prf_Fec <= ?", array($cond['desde'], $cond['hasta']));
                }
            }
        }
        //SELECT * FROM pruebas  WHERE fecha BETWEEN '20121201 00:00' AND '20121202 23:59'

        //$sel->where($cond['op_opciones']=="c"?"Prf_Num=?":"CONCAT(Prs_Nom,' ',Prs_Ape)LIKE '%{$cond['search']}%'", $cond['search'], $cond['search']);
            //$sel->where($cond['op_opciones']=="c"?"Prf_Num=?":"CONCAT(Prs_Nom,' ',Prs_Ape)LIKE '%{$cond['search']}%'", $cond['search'], $cond['search']);
            //$sel->where($cond['op_opciones']=="c"?"persona.Prs_Ced LIKE '{$cond['search']}%'":"persona.Cliente  LIKE '%{$cond['search']}%'", null);
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
                $Par_Sql->where("cliente.Emp_Cod=?",$_SESSION['Ses_Emp_Cod']);
                //echo $sql.'<br/>';
                break;
            case "byPerCod":
                //$sql="";
                $Par_Sql->where("persona.Prs_Cod='$cond[Prs_Cod]'", null);
                //echo $sql.'<br/>'  select *  from persona INNER JOIN cliente ON persona.Prs_Cod=cliente.Prs_Cod where(cliente.Emp_Cod='6');;
                break;
            case "byVendedor":
                //$sql="";
                $Par_Sql->where("vendedor.Vnd_Cod=$this->_name.Vnd_Cod", null);
                //echo $sql.'<br/>'  select *  from persona INNER JOIN cliente ON persona.Prs_Cod=cliente.Prs_Cod where(cliente.Emp_Cod='6');;
                break;
            case "detProf":
            // $this->IVA="( CAST( $this->Importe_Descu + $this->ICE  AS DECIMAL(20,2) )*Iva_Por/100 )";// AS IVA
                $valor="IF(Prf_Pru IS NULL,0,Prf_Pru)";
                $valorIva = "CAST((Prf_Pru * IF(Iva_Por>0,Iva_Por,0))/100 AS DECIMAL (20,2))";
                //$porcIva = "CAST($valorIva/100) AS DECIMAL(20,2)";
                $selectOther = $this->select(false)->from(array('det_prof'=>'proformas_det'))
                    //->join(array('product'=>'producto'), "product.Pro_Cod=det_prof.Pro_Cod",array('Iva_Cod'))
                    ->join(array('ivas'=>'iva'),"ivas.Iva_Cod=det_prof.Iva_Cod",array('Iva_Por'))
                    ->addCols(null, array($valores= 'Valores'=> new Zend_Db_Expr($this->castDecimal("IF($valor IS NULL,0,SUM($valor))"))))
                    ->addCols(null, array($valoresIva= 'Iva'=> new Zend_Db_Expr($this->castDecimal("IF($valorIva IS NULL,0,SUM($valorIva))"))))
                    ->where("det_prof.Prf_Cod= Prf_Cod")
                    ->group('det_prof.Prf_Cod');
                $Par_Sql->join(array('detProf'=>$selectOther), "detProf.Prf_Cod= $this->_name.Prf_cod", array('Valores','Iva'));
                $Par_Sql->addCols(null, array('Total'=>new Zend_Db_Expr($this->castDecimal("($valores+$valoresIva)"))));
                break;
            case "isActive":
                //$Par_Sql->array("IF(COALESCE((SELECT COUNT(productor_tarja.Vet_Cod) AS total FROM productor_tarja WHERE $this->_name.Prh_Cod=productor_tarja.Prh_Cod GROUP BY productor_tarja.Prh_Cod),0)>0,'s','n')AS tarja"));
                //$Par_Sql->count();
                //echo $sql.'<br/>'  select *  from persona INNER JOIN cliente ON persona.Prs_Cod=cliente.Prs_Cod where(cliente.Emp_Cod='6');;
                $Par_Sql->where("$this->_name.$this->_state='A'");
                break;

            default: throw new Exception ("No existe la sql denominadas $id!");
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
            case 1:
                $search="Prs_Ced LIKE '$Par_Sql[0]%'";
                $campos=" Cli_Cod, persona.Prs_Cod, Prs_Ced, CONCAT(Prs_Ape,' ',Prs_Nom) as cliente, Cli_Dir, Prs_Dir, Prs_Cor, IF (Cli_Est='A','Activo','Inactivo') as Cli_Est";
                $sql="SELECT $campos FROM cliente, persona WHERE Prs_Ced!='0' AND Ide_Cod IS NOT NULL AND $search AND cliente.Prs_Cod=persona.Prs_Cod AND cliente.Emp_Cod = $Par_Sql[1] $Par_Sql[3]";
                //echo $sql.'<br/>';
                break;
            default: throw new Exception ("No existe la sql denominada $id!");
        }
        //$sql="SELECT $campos FROM cliente, persona WHERE Prs_Ced!='0' AND Ide_Cod IS NOT NULL AND $search AND cliente.Prs_Cod=persona.Prs_Cod AND cliente.Emp_Cod = $Par_Sql[1] $Par_Sql[3]";
        //echo $sql."<br/>";
        return $sql;
      }

}