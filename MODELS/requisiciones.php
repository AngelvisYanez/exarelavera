<?php
require_once(dirname(__file__)."/../DATA/libs/AbstractModel.php");
class requisiciones extends AbstractModel{
    protected $_name = 'requisiciones';
    protected $_primary = array('Req_Cod');
    protected $_state = 'Req_Est';

    public function _selectBasic($cond=null,$limits=false){
        return $this->select()
            ->addCols('', array('Req_Estado'=>"IF (requisiciones.Req_Est='A', 'Activa', 'Inactiva')") )
            ->join('cliente', "cliente.Cli_Cod=$this->_name.Cli_Cod", array() )
            ->join(
                'persona', "persona.Prs_Cod=cliente.Prs_Cod", array('Cliente'=>"CONCAT(persona.Prs_Nom,' ',persona.Prs_Ape)",'Prs_Nom','Prs_Ape','Prs_Ced','Prs_Dir','Prs_Cor')
            )->join('vendedor', "vendedor.Vnd_Cod=$this->_name.Vnd_Cod",array())
            ->join(
                array('persona_vnd'=>'persona'), "persona_vnd.Prs_Cod=vendedor.Prs_Cod", array('Vendedor'=>"CONCAT(persona_vnd.Prs_Nom,' ',persona_vnd.Prs_Ape)")
            ); /*->join(
                'requisiciones_det', "requisiciones_det.Req_Cod=$this->_name.Req_Cod",
                array('Pfd_Int','Pro_Cod','Req_Cant','Req_Uni','Req_Imp','Req_Pru')
                //select * from vendedor inner join persona on persona.prs_cod=vendedor.prs_cod  where vendedor.prs_cod='1';
            )*/
    }
    public function _selectBasicGrid($cond=null,$limits=false){
        $sel=$this->_selectBasic();
        $this->sqlByNombre("setEmpCod", $sel);
        if(isset($cond['op_opciones'])){
            if($cond['op_opciones']=="h"){
                $sel->where("CONCAT(persona.Prs_Nom,' ',persona.Prs_Ape)LIKE '%{$cond['search']}%'");
            }elseif($cond['op_opciones']=="c"){
                $sel->where("Req_Num=?",$cond['search']);
            }elseif($cond['op_opciones']=="d"){
                $sel->where("CONCAT(persona.Prs_Nom,' ',persona.Prs_Ape)LIKE '%{$cond['search']}%'");
            }else{
                if(empty($cond['desde'])&& empty($cond['hasta'])){
                    $sel->where("Req_Fec >= '{$cond['desdeT']}' AND  Req_Fec <= '{$cond['hastaT']}'");
                }else{
                    $sel->where("Req_Fec >= '{$cond['desde']}' AND  Req_Fec <= '{$cond['hasta']}'");
                }
            }
        }
        //SELECT * FROM pruebas  WHERE fecha BETWEEN '20121201 00:00' AND '20121202 23:59'

        //$sel->where($cond['op_opciones']=="c"?"Req_Num=?":"CONCAT(Prs_Nom,' ',Prs_Ape)LIKE '%{$cond['search']}%'", $cond['search'], $cond['search']);
            //$sel->where($cond['op_opciones']=="c"?"Req_Num=?":"CONCAT(Prs_Nom,' ',Prs_Ape)LIKE '%{$cond['search']}%'", $cond['search'], $cond['search']);
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
                $valor="IF(Req_Pru IS NULL,0,Req_Pru)";
                $valorIva = "CAST((Req_Pru * IF(Iva_Por>0,Iva_Por,0))/100 AS DECIMAL (20,2))";
                //$porcIva = "CAST($valorIva/100) AS DECIMAL(20,2)";
                $selectOther = $this->select(false)->from(array('det_prof'=>'requisiciones_det'))
                    //->join(array('product'=>'producto'), "product.Pro_Cod=det_prof.Pro_Cod",array('Iva_Cod'))
                    ->join(array('ivas'=>'iva'),"ivas.Iva_Cod=det_prof.Iva_Cod",array('Iva_Por'))
                    ->addCols(null, array($valores= 'Valores'=> new Zend_Db_Expr($this->castDecimal("IF($valor IS NULL,0,SUM($valor))"))))
                    ->addCols(null, array($valoresIva= 'Iva'=> new Zend_Db_Expr($this->castDecimal("IF($valorIva IS NULL,0,SUM($valorIva))"))))
                    ->where("det_prof.Req_Cod= Req_Cod")
                    ->group('det_prof.Req_Cod');
                $Par_Sql->join(array('detProf'=>$selectOther), "detProf.Req_Cod= $this->_name.Req_cod", array('Valores','Iva'));
                $Par_Sql->addCols(null, array('Total'=>new Zend_Db_Expr($this->castDecimal("($valores+$valoresIva)"))));
                break;
            case "isActive":
                //$Par_Sql->array("IF(COALESCE((SELECT COUNT(productor_tarja.Vet_Cod) AS total FROM productor_tarja WHERE $this->_name.Prh_Cod=productor_tarja.Prh_Cod GROUP BY productor_tarja.Prh_Cod),0)>0,'s','n')AS tarja"));
                //$Par_Sql->count();
                //echo $sql.'<br/>'  select *  from persona INNER JOIN cliente ON persona.Prs_Cod=cliente.Prs_Cod where(cliente.Emp_Cod='6');;
                $Par_Sql->where("$this->_name.$this->_state='A'");
                break;
            case "getRequisiciones":
                $sql = "SELECT * FROM requisiciones 
                WHERE Emp_Cod = 161";
                //ChromePhp::log("MODEL GETREQUISICIONES",$sql);
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
                if($Par_Sql["op_opciones"] == "c"){
                    $filtro = " AND Prs_Ced LIKE '%$Par_Sql[search]%'";
                }else{
                    $filtro = " AND Prs_Nom LIKE '%$Par_Sql[search]%' OR Prs_Ape LIKE '%$Par_Sql[search]%'";
                }
                $sql = "SELECT Per_Cod, persona.Prs_Cod, Per_Car, Prs_Ced, Prs_Dir, Prs_Cor,
                        CONCAT(Prs_Nom,' ',Prs_Ape) AS Req_Nom
                        FROM personal
                        INNER JOIN persona ON (persona.Prs_Cod = personal.Prs_Cod) 
                        WHERE Emp_Cod = $Par_Sql[Emp_Cod] AND Per_Req = 1 AND Per_Est = 'A' $filtro";
                break;
            // GET TODAS LAS REQUISICIONES POR EMPRESA
            case 2:
                //ChromePhp::log("PAR_SQL",$Par_Sql);
                $filtro = '';
                //  
                if($Par_Sql["op_opciones"] == "requisitor"){
                    $filtro = " AND (Prs_Ced LIKE '%$Par_Sql[search]%' OR CONCAT(Prs_Nom,' ',Prs_Ape) LIKE '%$Par_Sql[search]%')";
                }else if($Par_Sql["op_opciones"] == "numero"){
                    $filtro = " AND Req_Num = $Par_Sql[search]";
                }else{
                    $filtro = " AND Req_Fec_Ent between '$Par_Sql[desde]' AND '$Par_Sql[hasta] 23:59:59'";
                }
                
                $sql = "SELECT Req_Cod, requisiciones.Emp_Cod, Usu_Cod, Req_Fec_Cre, Req_Fec_Ent, requisiciones.Per_Cod, Req_Num,
                Req_Obs, Req_Tip, Rtp_Des, 
                CONCAT(Prs_Nom,' ',Prs_Ape) AS Requisitor
                FROM requisiciones
                INNER JOIN personal ON (requisiciones.Per_Cod = personal.Per_Cod)
                INNER JOIN persona ON (personal.Prs_Cod = persona.Prs_Cod)
                LEFT JOIN requisiciones_tipo ON (requisiciones.Req_Tip = requisiciones_tipo.Rtp_Cod)
                WHERE requisiciones.Emp_Cod = $Par_Sql[Emp_Cod]".$filtro."
                ORDER BY Req_Cod DESC LIMIT 200";
                 //ChromePhp::log("SQL",$sql);
                break;
            case 3:
                $sql = "SELECT IFNULL(MAX(Req_Num),0) AS total FROM requisiciones 
                    INNER JOIN personal ON personal.Per_Cod=requisiciones.Per_Cod 
                    INNER JOIN persona ON persona.Prs_Cod=personal.Prs_Cod 
                    INNER JOIN sucursal ON personal.Emp_Cod=sucursal.Emp_Cod 
                    WHERE (sucursal.Emp_Cod='$Par_Sql[Emp_Cod]');";
                break;
            case 4:
                $sql = "SELECT Req_Cod, requisiciones.Emp_Cod, usuarios.Usu_Cod, Req_Fec_Cre, Req_Fec_Ent, requisiciones.Per_Cod, Req_Num,
                Req_Obs, Req_Tip, Rtp_Des, persona.Prs_Ced, persona.Prs_Dir, persona.Prs_Cor, Rtp_Des, Req_Per_Sol, Req_Ent_Com, 
                IF(Req_Ent_Par=1,'SI','NO') as Req_Ent_Par,
                CONCAT(per.Prs_Nom,' ',per.Prs_Ape) AS Usuario,
                CONCAT(persona.Prs_Nom,' ',persona.Prs_Ape) AS Requisitor
                FROM requisiciones
                INNER JOIN personal ON (requisiciones.Per_Cod = personal.Per_Cod)
                INNER JOIN persona ON (personal.Prs_Cod = persona.Prs_Cod)
                INNER JOIN usuarios ON (usuarios.Usu_Cod = requisiciones.Usu_Cod) 
                INNER JOIN persona as per ON (usuarios.Prs_Cod = per.Prs_Cod)
                LEFT JOIN requisiciones_tipo ON (requisiciones.Req_Tip = requisiciones_tipo.Rtp_Cod)
                    WHERE requisiciones.Emp_Cod = $Par_Sql[Emp_Cod] AND requisiciones.Req_Cod = $Par_Sql[Req_Cod]";
                    //ChromePhp::log('SQL REQ 4',$sql);
                break;
            default: throw new Exception ("No existe la sql denominada $id!");
        }
        //$sql="SELECT $campos FROM cliente, persona WHERE Prs_Ced!='0' AND Ide_Cod IS NOT NULL AND $search AND cliente.Prs_Cod=persona.Prs_Cod AND cliente.Emp_Cod = $Par_Sql[1] $Par_Sql[3]";
        //echo $sql."<br/>";
        return $sql;
      }


      /* public function getRequisiciones(){
            //ChromePhp::log("DENTRO GETREQUISICIONES");
            $sql = "SELECT * FROM requisiciones 
            WHERE Emp_Cod = 161";
            //ChromePhp::log("MODEL GETREQUISICIONES",$sql);
            return $sql;
      } */
}