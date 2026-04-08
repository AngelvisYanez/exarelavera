<?php
use \Exception;
require_once(dirname(__file__)."/../DATA/libs/AbstractModel.php");
class compras extends AbstractModel{
    protected $_name = 'compras';
    protected $_primary = array('Cop_Cod');
    protected $_state = 'Cop_Est';
    //protected $_fields = array(); // se declara las filas que va a tener el arreglo en caso de insert o update //opcional

    protected $Cop_Imp;
    protected $Importe;
    protected $Descu;
    protected $Importe_Descu;
    protected $ICE;
    protected $IVA;
    protected $IRPBNR;
    protected $TresxMil;
    protected $IVAPres;
    protected $Prop;
    protected $Adic;
    protected $Total;
    /* crea una sql basica global para la tabla */
    function _initCalculos(){
        $this->Cop_Imp="(Cop_Pru * Cop_Can)";
        // $this->Importe="CAST( ($this->Cop_Imp-($this->Cop_Imp * Cop_Dec/100)) AS decimal(20,2) )";// AS Importe,
        // $this->Descu ="( $this->Importe * compras.Cop_Des/100 )";
        // $this->Importe_Descu="/*CAST(*/ (( $this->Importe ) - $this->Descu) /*AS decimal(20,2) )*/";// AS Importe_Descu,
        $this->Importe="CAST( $this->Cop_Imp AS decimal(20,2) )";// AS Importe,
        //$this->Descu = "CAST( ( $this->Cop_Imp * IFNULL(det_compra.Cop_Dec,0) / 100 ) AS decimal(20,2) )";

        $this->Descu = "CASE   WHEN compras.Cop_Des > 0  THEN ( $this->Importe * compras.Cop_Des / 100 )
                    WHEN IFNULL(det_compra.Cop_Dec,0) > 0
                    THEN CAST( ( $this->Cop_Imp * det_compra.Cop_Dec / 100 ) AS DECIMAL(20,2) )  ELSE 0  END";

        $this->Importe_Descu="CAST( ( $this->Cop_Imp - $this->Descu ) AS decimal(20,2) )";// AS Importe_Descu,
        $this->ICE="CAST( $this->Importe_Descu *(IF(det_compra.Cop_Ice IS NOT NULL,det_compra.Cop_Ice/100,0))  AS decimal(20,2) )";// AS ICE,
        $this->IVA="( /*CAST*/( $this->Importe_Descu + $this->ICE  /*AS decimal(20,2)*/ )*Iva_Por/100 )";// AS IVA 
        $this->IRPBNR="IF(Cop_Irb IS NULL,0,Cop_Irb)";
        // $this->TresxMil="IF(Cop_imp_comb IS NULL,0,Cop_imp_comb)";
        // $this->IVAPres="IF(Cop_iva_pres IS NULL,0,Cop_iva_pres)";
        // $this->Prop="IF(Cop_Prop IS NULL,0,Cop_Prop)";
        // $this->Adic="IF(Cop_Adic IS NULL,0,Cop_Adic)";
        $this->Total= "$this->Importe_Descu + $this->ICE + $this->IVA";
    }
    public function _selectBasic($cond=null,$limits=false){
        return $this->select()
            ->join('tipo_compr', "tipo_compr.Tic_Cod=$this->_name.Tic_Cod", array('Tic_Sri'=>$this->expr("LPAD(CAST(Tic_Sri AS CHAR),2,'0')"),'Tic_Des'))
            ->join('sustento', "sustento.Tri_Cod=$this->_name.Tri_Cod", array('Tri_Sri'=>$this->expr("LPAD(CAST(Tri_Sri AS CHAR),2,'0')")))
            ->joinLeft('tipopagocom', "tipopagocom.Tpc_Cod=$this->_name.Tpc_Cod", array('Tpc_Sri'=>$this->expr("LPAD(CAST(Tpc_Sri AS CHAR),2,'0')")))
            ->join('proveedore', "proveedore.Prv_Cod=$this->_name.Prv_Cod", array('Prv_Tic'))
            ->join('persona', "persona.Prs_Cod=proveedore.Prs_Cod",
                array('Proveedor'=>$this->expr("IF(Prv_Com IS NULL OR Prv_Com='',".$this->concat(array('persona.Prs_Ape','persona.Prs_Nom')).",Prv_Com)"),
                            'Ruc'=>$this->expr("if(Cop_Ide='1',
                                                    if(LENGTH(persona.Prs_Ced)<13,concat(persona.Prs_Ced,'001'),persona.Prs_Ced),
                                                    if(Cop_Ide='2',
                                                        if(LENGTH(persona.Prs_Ced)>10,SUBSTRING(persona.Prs_Ced,1,10),persona.Prs_Ced),
                                                        persona.Prs_Ced  
                                                    )
                                                )"),
                            'Prs_Ced'=>$this->expr("if(Cop_Ide='1',
                                                    if(LENGTH(persona.Prs_Ced)<13,concat(persona.Prs_Ced,'001'),persona.Prs_Ced),
                                                    if(Cop_Ide='2',
                                                        if(LENGTH(persona.Prs_Ced)>10,SUBSTRING(persona.Prs_Ced,1,10),persona.Prs_Ced),
                                                        persona.Prs_Ced  
                                                    )
                                                )"),
                            'Prs_Nom','Prs_Ape','Prs_Dir'));
    }

    /* crea una sql standart para jqgrid, condiciones incluidas */
    public function _selectBasicGrid($cond=null,$limits=false){
        $sel=$this->_selectBasic();
        $this->sqlByNombre("setEmpCod", $sel);
        if(isset($cond['op_opciones']) && isset($cond["search"])){
            switch($cond['op_opciones']){
                case 'd':
                    $sel->where("compras.Cop_Num = ?",$cond["search"]);
                    break;
                case 'c':
                    $sel->where("persona.Prs_Ced LIKE ?","$cond[search]%");
                    break;
                default:
                    $sel->where("(UPPER(CONCAT(persona.Prs_Ape,' ',persona.Prs_Nom)) LIKE UPPER(?)) OR (UPPER(Prv_Com) LIKE UPPER(?) )","%$cond[search]%");
                    break;
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
                $sql->where("proveedore.Emp_Cod=?",$_SESSION['Ses_Emp_Cod']);
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "setSucCod":
                $sql->where("puntos_imp.Suc_Cod=?",$_SESSION['Ses_Suc_Cod']);
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "bySucCod":
                $sql->where("puntos_imp.Suc_Cod=?",$Par_Sql['Suc_Cod']);
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "setIdentifica":
                $sql->joinLeft('identifica', "identifica.Ide_Cod=persona.Ide_Cod",array('Ide_Prv'=>new Zend_Db_Expr("COALESCE(Cop_Ide,Ide_Prv)"),'Ide_Prv_Ree'=>new Zend_Db_Expr("IF(Cop_Ide=1,4,IF(Cop_Ide=2,5,6))")));
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "isActive":
                $sql->where("$this->_name.$this->_state='A'");
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "isInactive":
                $sql->where("$this->_name.$this->_state='I'");
                //echo $this->getSqlString($sql)."<br/>";
                break; 
            case "setUsuario":
                $sql->join('vendedor', "vendedor.Vnd_Cod=compras.Vnd_Cod", array('Vnd_Cod'))
                    ->join('puntos_imp', "puntos_imp.Pun_Cod=vendedor.Pun_Cod", array('Pun_Cod'))
                    ->join(array('persona_ven'=>'persona'), "persona_ven.Prs_Cod=vendedor.Prs_Cod", array('Vendedor'=>new Zend_Db_Expr("CONCAT(persona_ven.Prs_Ape,' ',persona_ven.Prs_Nom)")));
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "notInReembolsos":
                $sql->joinLeft('venta_reembolsos', "venta_reembolsos.Cop_Cod=compras.Cop_Cod",array());
                $sql->where("venta_reembolsos.Vet_Cod IS NULL");
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "setRetencion":
                $sql->joinLeft('retencion', "retencion.Cop_Cod=$this->_name.Cop_Cod AND retencion.Ret_Est='A'", array("Ret_Cod","Ret_Num",'Ret_Data'=>$this->expr("IF(Ret_Cod IS NULL,'N','S')")));
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "hasRetencion":
                if (isset($Par_Sql['Chk_Ret']) && $Par_Sql['Chk_Ret'] === 'S') {
                    $sql->where("retencion.Ret_Cod IS NOT NULL");
                } else {
                    $sql->where("retencion.Ret_Cod IS NULL");
                }
                //echo $this->getSqlString($sql)."<br/>";
                break;
            // case "byPunCod":
            //     if (!empty($Par_Sql['Pun_Sri']) && $Par_Sql['Pun_Sri'] !== 'T') {
            //         $sql->where("autorizaci.Pun_Sri = ?", $Par_Sql['Pun_Sri']);
            //     } else {
            //         throw new Exception("El código de punto de compra no está definido.");
            //     }
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "byTipoCompr":
                $sql->where("tipo_compr.Tic_Cod=?",$Par_Sql['Tic_Cod']);
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "byTipPago":
                if (!$sql->hasTable("ccpp_pagar")) {
                    $sql->joinLeft('ccpp_pagar', "ccpp_pagar.Cop_Cod = compras.Cop_Cod", array());
                }
                $sql->addCols(null, array(
                    'Pago' => new Zend_Db_Expr("IF(ccpp_pagar.Cpp_Cod IS NULL, 'Contado', 'Credito')")
                ))
                ->group('compras.Cop_Cod')
                ->where("IF(ccpp_pagar.Cpp_Cod IS NULL, 'Contado', 'Credito') = ?", $Par_Sql['For_Cod']);
                break;
            case "bySustento":
                $sql->where("sustento.Tri_Cod=?",$Par_Sql['Tri_Cod']);
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "byDateRange":
                $sql->where('Cop_Fec BETWEEN ? AND ?',array($Par_Sql['Fec_Ini'],$Par_Sql['Fec_Fin']));
                    //->where('Cop_Fec<=?',$Par_Sql['Fec_Fin']);
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "byPrvCod":
                $sql->where('proveedore.Prv_Cod=?',$Par_Sql['Prv_Cod']);
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "setTotales":
                $this->_initCalculos();
                $sql->addCols(null,array(
                    'Importe'   =>$this->expr($this->castDecimal("SUM($this->Importe)")),
                    'Descu'     =>$this->expr($this->castDecimal("SUM($this->Descu)")),
                    'Importe_Descu'=>$this->expr($this->castDecimal("SUM($this->Importe_Descu)")),
                    'NoIVA'     =>$this->expr($this->castDecimal("SUM(IF(Iva_Por = 0 AND Iva_Sri = 6, $this->Importe_Descu, 0))")),
                    'Sub_0'     =>$this->expr($this->castDecimal("SUM(IF(Iva_Por = 0 AND Iva_Sri = 0,  $this->Importe_Descu , '0'))")),
                    'Sub_12'    =>$this->expr($this->castDecimal("SUM(IF(Iva_Por = 12  , $this->Importe_Descu , '0'))")),
                    'Sub_15'    =>$this->expr($this->castDecimal("SUM(IF(Iva_Por != 0 AND Iva_Por != 5  AND Iva_Por!=8  AND Iva_Por!=12  , $this->Importe_Descu , '0'))")),
                    'Sub_5'     =>$this->expr($this->castDecimal("SUM(IF(Iva_Por = 5, $this->Importe_Descu , '0'))")),
                    'Sub_8'     =>$this->expr($this->castDecimal("SUM(IF(Iva_Por = 8, $this->Importe_Descu , '0'))")),
                    'Sub_Ice'   =>$this->expr($this->castDecimal("SUM(IF(Cop_Ice != 0, $this->Importe_Descu , 0))")),
                    'Iva'    =>'CAST(SUM(Iva_Por)/SUM(IF(Iva_Por>0,1,0))AS DECIMAL(2,0))',
                    'Ice'    =>"IF(Cop_Ice IS NULL,0,Cop_Ice)",
                    'Irbpnr'    =>$this->expr($this->IRPBNR),
                    'Ice_Tot'   =>$this->expr($this->castDecimal("SUM(IF(Cop_Ice != 0, $this->ICE, 0))")),
                    'Iva_Tot'   =>$this->expr($this->castDecimal("SUM(IF(Iva_Por != 0, $this->IVA, 0))")),
                    // 'TresxMil'   =>$this->expr($this->castDecimal("SUM(IF(Cop_imp_comb IS NULL,0,Cop_imp_comb))")),
                    // 'IVAPres'   =>$this->expr($this->castDecimal("SUM(IF(Cop_iva_pres IS NULL,0,Cop_iva_pres))")),
                    // 'Prop'   =>$this->expr($this->castDecimal("SUM(IF(Cop_Prop IS NULL,0,Cop_Prop))")),
                    // 'Adic'   =>$this->expr($this->castDecimal("SUM(IF(Cop_Adic IS NULL,0,Cop_Adic))")),
                    'Total'   =>$this->expr($this->castDecimal("SUM( $this->Total ) + $this->IRPBNR")),
                    'Serie' => ("SUBSTRING(Cop_Num, 1, 7)"),
                    'Secuencia' => ("SUBSTRING(Cop_Num, 9, 18)")
                ));

                $sql->join('det_compra', "det_compra.Cop_Cod=$this->_name.Cop_Cod", array())
                    ->join('iva', "iva.Iva_Cod=det_compra.Iva_Cod", array());
                $sql->order((isset($Par_Sql['limits'])&&$Par_Sql['limits']&&isset($Par_Sql['CustomOrderBy'])&&!empty($Par_Sql['CustomOrderBy'])?"$Par_Sql[CustomOrderBy],":'').'Iva_Por ASC');
                // $sql->group('compras.Cop_Cod'); // forma original
                if (isset($Par_Sql['CustomGroupBy']) && $Par_Sql['CustomGroupBy'] === 'Agr_Prv') {
                    $sql->group('compras.Prv_Cod');
                } else {
                    $sql->group('compras.Cop_Cod');
                } // nuevo agrupamiento - por defecto por Cop_Cod
                //$sql->order('compras.Cop_Fec DESC,Cop_Ice DESC,Iva',"DESC");
                //echo $this->getSqlString($sql)."<br/>";
                break;
            
            case "setTotalesGlobales":
                $this->_initCalculos();
                $sql->unsetCols()->addCols(null, array(
                    'Importe'   => $this->expr($this->castDecimal("SUM( IF(Tic_Sri=4,-1,1)* $this->Importe)")),
                    'Descu'     => $this->expr($this->castDecimal("SUM( IF(Tic_Sri=4,-1,1)* $this->Descu)")),
                    'Importe_Descu' => $this->expr($this->castDecimal("SUM( IF(Tic_Sri=4,-1,1)* $this->Importe_Descu)")),
                    'NoIVA'     => $this->expr($this->castDecimal("SUM( IF(Tic_Sri=4,-1,1)* IF((Iva_Por = 0 AND Iva_Sri = 6), $this->Importe_Descu, 0))")),
                    'Sub_0'     => $this->expr($this->castDecimal("SUM( IF(Tic_Sri=4,-1,1)* IF(Iva_Por = 0,  $this->Importe_Descu , '0'))")),
                    'Sub_12'    => $this->expr($this->castDecimal("SUM( IF(Tic_Sri=4,-1,1)* IF(Iva_Por = 12, $this->Importe_Descu , '0'))")),
                    'Sub_15'    => $this->expr($this->castDecimal("SUM( IF(Tic_Sri=4,-1,1)* IF(Iva_Por != 0 AND Iva_Por != 5 AND Iva_Por != 8 AND Iva_Por != 12, $this->Importe_Descu , '0'))")),
                    'Sub_5'     => $this->expr($this->castDecimal("SUM( IF(Tic_Sri=4,-1,1)* IF(Iva_Por = 5, $this->Importe_Descu , '0'))")),
                    'Sub_8'     => $this->expr($this->castDecimal("SUM( IF(Tic_Sri=4,-1,1)* IF(Iva_Por = 8, $this->Importe_Descu , '0'))")),
                    'Sub_Ice'   => $this->expr($this->castDecimal("SUM( IF(Tic_Sri=4,-1,1)* IF(Cop_Ice != 0, $this->Importe_Descu , 0))")),
                    'Irbpnr'    => $this->expr("IF(Tic_Sri=4,-1,1)*{$this->IRPBNR}"),
                    'Ice_Tot'   => $this->expr($this->castDecimal("SUM( IF(Tic_Sri=4,-1,1)* IF(Cop_Ice != 0, $this->ICE, 0))")),
                    'Iva_Tot'   => $this->expr($this->castDecimal("SUM( IF(Tic_Sri=4,-1,1)* IF(Iva_Por != 0, $this->IVA, 0))")),
                    'Total'     => $this->expr($this->castDecimal("SUM( IF(Tic_Sri=4,-1,1)*($this->Total + $this->IRPBNR) )"))
                ));
                $sql->join('det_compra', "det_compra.Cop_Cod=$this->_name.Cop_Cod", array())
                    ->join('iva', "iva.Iva_Cod=det_compra.Iva_Cod", array());
                $sql->order('Iva_Por ASC');
                $sql->group('compras.Cop_Cod');

                $total = $this->select(false)->from(array('tbl' => $this->expr('(' . $this->getSqlString($sql) . ')')), array(
                        'Importe'   => $this->expr('SUM(tbl.Importe)'),
                        'Descu'     => $this->expr('SUM(tbl.Descu)'),
                        'Importe_Descu' => $this->expr('SUM(tbl.Importe_Descu)'),
                        'NoIVA'     => $this->expr('SUM(tbl.NoIVA)'),
                        'Sub_0'     => $this->expr('SUM(tbl.Sub_0)'),
                        'Sub_12'    => $this->expr('SUM(tbl.Sub_12)'),
                        'Sub_15'    => $this->expr('SUM(tbl.Sub_15)'),
                        'Sub_5'     => $this->expr('SUM(tbl.Sub_5)'),
                        'Sub_8'     => $this->expr('SUM(tbl.Sub_8)'),
                        'Sub_Ice'   => $this->expr('SUM(tbl.Sub_Ice)'),
                        'Irbpnr'    => $this->expr('SUM(tbl.Irbpnr)'),
                        'Ice_Tot'   => $this->expr('SUM(tbl.Ice_Tot)'),
                        'Iva_Tot'   => $this->expr('SUM(tbl.Iva_Tot)'),
                        'Total'     => $this->expr('SUM(tbl.Total)')
                    )
                );
                $sql->setDataSelect($total->getDataSelect());
                break;

            case "setTotalDetalle":
                $this->_initCalculos();
                $sql->addCols(null,array(
                    'Cop_Can',
                    'Cop_Pru',
                    'Importe_Descu'=>$this->expr($this->castDecimal("SUM($this->Importe_Descu)")),
                    'Descu'     =>$this->expr($this->castDecimal("SUM($this->Descu)")),
                    'Iva'    =>"Iva_Por",
                    'Ice'    =>"IF(Cop_Ice IS NULL,0,Cop_Ice)",
                    'Irbpnr'    =>$this->expr($this->IRPBNR),
                    'Ice_Tot'   =>$this->expr($this->castDecimal("SUM(IF(Cop_Ice != 0, $this->ICE, 0))")),
                    'Iva_Tot'   =>$this->expr($this->castDecimal("SUM(IF(Iva_Por != 0, $this->IVA, 0))")),
                    // 'TresxMil'   =>$this->expr($this->castDecimal("SUM(IF(Cop_imp_comb IS NULL,0,Cop_imp_comb))")),
                    // 'IVAPres'   =>$this->expr($this->castDecimal("SUM(IF(Cop_iva_pres IS NULL,0,Cop_iva_pres))")),
                    'Prop'   =>$this->expr($this->castDecimal("SUM(IF(Cop_Prop IS NULL,0,Cop_Prop))")),
                    'Adic'   =>$this->expr($this->castDecimal("SUM(IF(Cop_Adic IS NULL,0,Cop_Adic))")),
                    'Total'   =>$this->expr($this->castDecimal("SUM( $this->Total ) + $this->IRPBNR"))
                ));
                $sql->join('det_compra', "det_compra.Cop_Cod=$this->_name.Cop_Cod", array())
                    ->join('iva', "iva.Iva_Cod=det_compra.Iva_Cod", array())
                    ->join('producto', "producto.Pro_Cod = det_compra.Pro_Cod",array('Producto'=>"CONCAT(Ite_Lar,IF(Pro_Obs IS NULL OR TRIM(Pro_Obs)='' OR Pro_Obs=Ite_Lar,'',CAST(CONCAT(' - ',Pro_Obs)AS CHAR) ) )"))
                    ->join('item', "item.Ite_Cod = producto.Ite_Cod", array())
                    ->group('compras.Cop_Cod,det_compra.Cop_Int')/*->unsetCols(array('Total','Cop_Can'))*/;
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
            case 1:
                $sql="SELECT COUNT(*) as total FROM ccpp_pagar WHERE Cop_Cod = '$Par_Sql[0]';";
                //echo $this->getSqlString($sql)."<br/>";
                break;

            case 2:
                $sql =" SELECT count(compras.Cop_Cod) as Activo FROM compras 
                        INNER JOIN det_compra ON compras.Cop_Cod = det_compra.Cop_Cod
                        INNER JOIN adquisicio ON det_compra.Adq_Cod = adquisicio.Adq_Cod
                        WHERE adquisicio.Adq_Cor= 'A'
                        AND compras.Cop_Cod = $Par_Sql[0]";
                break;

            case 3:
                $sql = "SELECT count(compras.Cop_Cod) as Reembolso FROM compras 
                        INNER JOIN det_compra ON compras.Cop_Cod = det_compra.Cop_Cod
                        INNER JOIN producto ON producto.Pro_Cod = det_compra.Pro_Cod
                        INNER JOIN item ON item.Ite_Cod = producto.Ite_Cod
                        WHERE item.Ite_Lar like '%reembolso%'
                        AND compras.Cop_Cod = $Par_Sql[0]";
                break;

            default: throw new Exception ("No existe la sql numero $id!");
        }
        //echo $this->getSqlString($sql)."<br/>";
        return $sql;
    }
    public function setCount($sql){
        $sql->unsetFrom()->unsetCols()->from($this->_name,array('total'=>'COUNT(*)'));
        $data=$sql->getDataSelect();
        if(array_key_exists('det_compra',$data['from'])){
            $total=$this->select(false)->from(array('tbl'=>$this->expr('('.$this->getSqlString($sql).')')),array('total'=>'COUNT(*)'));
            $sql->setDataSelect($total->getDataSelect());
        }
    }
}