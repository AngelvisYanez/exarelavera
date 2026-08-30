<?php
require_once(dirname(__file__)."/../DATA/libs/AbstractModel.php");

class ventas extends AbstractModel{
    protected $_name = 'ventas';
    protected $_primary = array('Vet_Cod');
    protected $_state = 'Vet_Est';
    //protected $_fields = array(); // se declara las filas que va a tener el arreglo en caso de insert o update //opcional

    protected $Vet_Imp;
    protected $Importe;
    protected $Descu;
    protected $Importe_Descu;
    protected $ICE;
    protected $IVA;
    function _initCalculos(){
        $this->Vet_Imp="(Vet_Pru * Vet_Can)";
        $this->Importe="CAST( ($this->Vet_Imp-($this->Vet_Imp * Vet_Dec/100)) AS DECIMAL(20,2) )";// AS Importe,
        $this->Descu ="( $this->Importe * ventas.Vet_Des/100 )";
        $this->Importe_Descu="CAST( (( $this->Importe ) - $this->Descu) AS DECIMAL(20,2) )";// AS Importe_Descu,
        $this->ICE="CAST( $this->Importe_Descu *(IF(ventas_det.Vet_Ice IS NOT NULL,ventas_det.Vet_Ice/100,0))  AS DECIMAL(20,2) )";// AS ICE,
        $this->IVA="( CAST( $this->Importe_Descu + $this->ICE  AS DECIMAL(20,2) )*Iva_Por/100 )";// AS IVA
    }
    /* crea una sql basica global para la tabla */
    public function _selectBasic($cond=null,$limits=false){
        return $this->select(true,array('ventas.Vet_Cod as VetCod','ventas.Vet_Cod', 'Aut_Cod', 'Cli_Cod', 'Ciu_Cod', 'Caj_Cod', 'Vnd_Cod', 'Vet_Num', 'Vet_Des', 'Vet_Obs', 'Vet_Aut', 'Vet_Xml', 'Vet_Sri', 'Vet_Est', 'Ret_Num', 'Ret_Fec', 'Ret_Aut', 'Vet_Ntd', 'Vet_Fdm', 'Vet_Nns', 'Vet_Tpv', 'Tpc_Cod','Vet_Sys'))
            ->join('caja_aper', "caja_aper.Caj_Cod=$this->_name.Caj_Cod", array('Caj_Fec'))
            ->addCols(null,array(
                'Secuencia'     =>new Zend_Db_Expr("CONCAT(Suc_Sri,'-',Pun_Sri,'-',LPAD(CAST(Vet_Num AS CHAR),9,'0'))"),
                'Autorizacion'  =>new Zend_Db_Expr("IF(Vet_Xml IS NULL OR TRIM(Vet_Xml)='', Aut_Sri, IF(Vet_Sri IS NULL OR TRIM(Vet_Sri)='','PENDIENTE',Vet_Sri))")
            ))
            ->join('cliente', "cliente.Cli_Cod=$this->_name.Cli_Cod", array('Cli_Tic'))
            ->join('persona', "persona.Prs_Cod=cliente.Prs_Cod",array('Ruc'=>new Zend_Db_Expr("IF(Cli_Ruf IS NULL OR TRIM(Cli_Ruf)='',persona.Prs_Ced,Cli_Ruf)"),'Cliente'=>new Zend_Db_Expr("IF(Cli_Fac IS NULL OR TRIM(Cli_Fac)='',CONCAT(persona.Prs_Ape,' ',persona.Prs_Nom),Cli_Fac)"),'Prs_Nom','Prs_Ape','Prs_Ced','Prs_Dir','Ide_Cod'))
            ->join('identifica', "identifica.Ide_Cod=persona.Ide_Cod", array('Ide_Sri','Ide_Prv','Ide_Pre'))
            ->joinLeft('tipopagocom', "tipopagocom.Tpc_Cod=$this->_name.Tpc_Cod", array('Tpc_Sri'=>new Zend_Db_Expr("LPAD(CAST(Tpc_Sri AS CHAR),2,'0')")))
            ->join('autorizaci', "autorizaci.Aut_Cod=$this->_name.Aut_Cod", array('Tic_Cod'))
            ->join('puntos_imp', "puntos_imp.Pun_Cod=autorizaci.Pun_Cod", array())
            ->join('tipo_compr', "tipo_compr.Tic_Cod=autorizaci.Tic_Cod", array('Tic_Sri'=>new Zend_Db_Expr("LPAD(CAST(Tic_Sri AS CHAR),2,'0')"),'Tic_Des'))
            ->join('sucursal', "sucursal.Suc_Cod=puntos_imp.Suc_Cod", array())
            ->join('vendedor', "vendedor.Vnd_Cod=ventas.Vnd_Cod", array(/*'Vnd_Cod'*/))
            ->join(array('persona_ven'=>'persona'), "persona_ven.Prs_Cod=vendedor.Prs_Cod", array('Vendedor'=>new Zend_Db_Expr("CONCAT(persona_ven.Prs_Ape,' ',persona_ven.Prs_Nom)")))
                ;
    }
    /* crea una sql standart para jqgrid, condiciones incluidas */
    public function _selectBasicGrid($cond=null,$limits=false){
        $sel=$this->_selectBasic();
        $this->sqlByNombre("setEmpCod", $sel);

        if(!empty($cond['Tic_Cod'])&&is_numeric($cond['Tic_Cod'])) $this->sqlByNombre("byTipoCompr", $sel, $cond);
        if($this->hasVal($cond, 'range')&&$cond['range']=='S') $this->sqlByNombre("byDateRange", $sel, $cond);
        if(isset($cond['op_opciones']) && isset($cond["search"])){
            if($cond['op_opciones']=='d'){
                $sel->where("ventas.Vet_Num = ?",$cond["search"]);
            }else{
                if($cond['op_opciones']=='c'){
                    $sel->where("persona.Prs_Ced LIKE ? OR Cli_Ruf LIKE ?","$cond[search]%");
                }elseif ($cond['op_opciones']=='f'){
                     $sel->where("Caj_Fec BETWEEN '$cond[Fec_Ini] 00:00:00' AND '$cond[Fec_Fin] 23:59:59'",null);
                }elseif ($cond['op_opciones']=='cd'){
                    $sel->where("ventas.Cli_Cod=?",$cond["Cli_Cod"]);
                }else{
                    $sel->where("(UPPER(CONCAT(persona.Prs_Ape,' ',persona.Prs_Nom)) LIKE UPPER(?)) OR UPPER(Cli_Fac) LIKE UPPER(?)","%$cond[search]%");
                }
            }
        }
        if(!empty($cond['Pec_Cod']) && $cond['op_opciones'] !=='d'){
            $sel->where("Caj_Fec BETWEEN '$cond[fecha_inicio] 00:00:00' AND '$cond[fecha_fin] 23:59:59'",null);
            if(!empty($cond['Cmb_Mes']))  $sel->where("MONTH(Caj_Fec)=?","$cond[Cmb_Mes]");
        }
        return $sel;
    }
    public function setCount($sql){
        $data=$sql->getDataSelect();
        if(!array_key_exists('lola',$data['from']))
            $sql->unsetFrom()->unsetCols()->from($this->_name,array('total'=>'COUNT(*)'));
        if(array_key_exists('ventas_det',$data['from'])){
            $total=$this->select(false)->from(array('tbl'=>new Zend_Db_Expr('('.$this->getSqlString($sql).')')),array('total'=>'COUNT(*)'));
            $sql->setDataSelect($total->getDataSelect());
        }
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
                $sql->where("cliente.Emp_Cod=?",$_SESSION['Ses_Emp_Cod']);
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "setSucCod":
                $sql->where("puntos_imp.Suc_Cod=?",$_SESSION['Ses_Suc_Cod']);
                //echo $this->getSqlString($sql)."<br/>";
                break;

            case "setExportacion":
                $sql->joinLeft('exporta_vent', "exporta_vent.Vet_Cod=$this->_name.Vet_Cod", array('Eve_Cod','Ref_Cod'));
                $sql->addCols('',array("Exportacion"=>"IF(exporta_vent.Eve_Cod IS NULL,'N','S')"));
                //echo $this->getSqlString($sql)."<br/>";
                break;

            case "setTotales":
                $this->setTotalesCols($sql);
                $sql->group('ventas.Vet_Cod');
                //echo $this->getSqlString($sql)."<br/>";
                break;

            case "setVentasDet":
                if(!$sql->hasTable("ventas_det")) $sql->join('ventas_det',"ventas_det.Vet_Cod=$this->_name.Vet_Cod", array('*'));
                //echo $this->getSqlString($sql)."<br/>";
                break;

            case "setItemsVent":
                $this->_initCalculos();
                if(!$sql->hasTable("ventas_det"))$sql->join('ventas_det',"ventas_det.Vet_Cod = $this->_name.Vet_Cod", array('total' =>$this->expr('COUNT(*)') ));
                $sql->addCols('',array(
                    'Cantidad' =>new Zend_Db_Expr($this->castDecimal("SUM($this->Importe)")),
                    'Veces'=>new Zend_Db_Expr($this->castDecimal("SUM(Vet_Can)"))
                ));
                break;
            case "setProductoInfo":
                if(!$sql->hasTable("ventas_det")) $sql->join('ventas_det',"ventas_det.Vet_Cod = $this->_name.Vet_Cod", array('Vet_Can','Vet_Pru','Vet_Dec') );
                $sql->join('producto', "producto.Pro_Cod=ventas_det.Pro_Cod",array('*'))
                    ->join('item', "producto.Ite_Cod = item.Ite_Cod",array('*'))
                    ->join('marca', "producto.Mar_Cod=marca.Mar_Cod",array('Mar_Des'));
                break;
            case "setAliCuotas":
                $this->_initCalculos();
                $selectP = $this->select(false)->from(array('alp'=>'ali_pagos'))
                    ->join('cliente', "cliente.Cli_Cod=alp.Cli_Cod", array(''))
                    ->addCols(null,array('Pagos'=> new Zend_Db_Expr('SUM(IFNULL(Ali_Pag,0))')))
                    //->where("alp.Vet_Cod = Vet_Cod")
                    ->where("cliente.Emp_Cod=?",$_SESSION['Ses_Emp_Cod'])
                    ->group('alp.Vet_Cod');
                if(isset($Par_Sql['Cli_Cod'])) $selectP->where('alp.Cli_Cod =?',$Par_Sql['Cli_Cod']); //Cambio pedido el 22/05/2019 //no viene Cli_Cod y no funciono
                $sql->joinLeft(array('lola'=>$selectP), "lola.Vet_Cod = ventas.Vet_Cod", array('Pagos'));
                $sql->addCols(null,array('Resto'=> new Zend_Db_Expr($this->castDecimal($this->castDecimal("SUM( $this->Importe_Descu + $this->ICE + $this->IVA )")."-IF(Pagos IS NULL,0,Pagos)"))));
                //echo $this->getSqlString($sql)."<br/>";
                break;

            case "isSummary":
                $sql->unsetCols();
                $this->setTotalesCols($sql);
                $sql->group('ventas.Vet_Cod');
                $sql->setDataSelect($this->getSummaryCols($sql));
                //echo $this->getSqlString($sql)."<br/>";
                break;

            case "byTipoCompr":
                $sql->where("tipo_compr.Tic_Cod=?",$Par_Sql['Tic_Cod']);
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "bySustento":
                $sql->where("sustento.Tri_Cod=?",$Par_Sql['Tri_Cod']);
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "byDateRange":
                $sql->where('Caj_Fec BETWEEN ? AND ?',array($Par_Sql['Fec_Ini'],$Par_Sql['Fec_Fin']));
                    //->where('Cop_Fec<=?',$Par_Sql['Fec_Fin']);
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "byCliCod":
                $sql->where('cliente.Cli_Cod=?',$Par_Sql['Cli_Cod']);
                //echo $this->getSqlString($sql)."<br/>";
                break;
            default: $this->sqlByParams($id,$sql,array(
                    'isActive'=>"$this->_name.$this->_state='A'",
                    'isInactive'=>"$this->_name.$this->_state='I'",
                    'groupByAliCod'=>'lola.Ali_Cod',
                    'isTicCod'=>"tipo_compr.Tic_Sri=?",
//                    'hasSaldo'=>"(Pagos IS NULL OR Pagos >= 0)",
                    'hasAliPag'=>"Ali_Pag IS NOT NULL"
                )); //echo $this->getSqlString($sql)."<br/>";
        }
        //echo $this->getSqlString($sql)."<br/>";
        return $sql;
    }
    /* crea sentencia por id numero sql */
    public function sqlByNumero($id,$Par_Sql,$cond=null){
        $sql=(is_object($Par_Sql)?$Par_Sql:'');
        switch($id){
            case 0:
                $sql="SELECT ventas.Vet_Cod, tipo_compr.Tic_Cod, Tic_Des, ventas.Vet_Num, Concat(Prs_Ape, ' ', Prs_Nom) as Cliente,  Caj_Fec,
                      Sum(Kar_Sal * Kar_Promedio) as Costo
                        FROM ventas
                        INNER JOIN kardex_ie ON kardex_ie.Vet_Cod = ventas.Vet_Cod
                        INNER JOIN cliente ON cliente.Cli_Cod= ventas.Cli_Cod
                        INNER JOIN persona ON cliente.Prs_Cod = persona.Prs_Cod
                        INNER JOIN autorizaci on ventas.Aut_Cod = autorizaci.Aut_Cod
                        INNER JOIN puntos_imp ON puntos_imp.Pun_Cod = autorizaci.Pun_Cod AND puntos_imp.Suc_Cod=$Par_Sql[Suc_Cod]
                        INNER JOIN tipo_compr ON tipo_compr.Tic_Cod = ventas.Tic_Cod
                        INNER JOIN caja_aper ON caja_aper.Caj_Cod=ventas.Caj_Cod
                        LEFT JOIN ventas_costo ON ventas_costo.Vet_Cod = ventas.Vet_Cod
                          WHERE ventas.Vet_Est ='A'  
                          AND cliente.Emp_Cod=$Par_Sql[Emp_Cod]
                          AND Caj_Fec BETWEEN '$Par_Sql[Fec_Ini] 00:00:00' AND '$Par_Sql[Fec_Fin] 23:59:59'
                          AND ventas_costo.Vet_Cod IS NULL
                          AND Kar_Est = 'A'
                          GROUP BY ventas.Vet_Cod
                          ORDER BY Vet_Cod";
                break;
            case 1:
                $sql="SELECT ventas_det.* , Ite_Lar, renta.Ren_Sri as Ret_Ren_Sri, renta.Ren_Cod as Ret_Ren_Cod, renta.Ren_Por as Ret_Ren_Por, renta.Ren_Con as Ret_Ren_Con, renta.Ren_Tip as Ret_Ren_Tip, renta.Ren_Ret as Ret_Ren_Ret, renta.Ren_Est as Ret_Ren_Est,  iva_imp.Ren_Sri as Iva_Ren_Sri,  iva_imp.Ren_Cod as Iva_Ren_Cod,
                iva_imp.Ren_Por as Iva_Ren_Por,  iva_imp.Ren_Con as Iva_Ren_Con,  iva_imp.Ren_Tip as Iva_Ren_Tip, iva_imp.Ren_Ret as Iva_Ren_Ret, iva_imp.Ren_Est as Iva_Ren_Est, ivas.Iva_Por as Iva_Por, ivas.Iva_Cod as Iva_Cod, det_plan.Pld_Cdc, ice.*, unidad.*, adquisicio.*
                FROM producto
                left join adquisicio on adquisicio.Adq_Cod = producto.Adq_Cod
                INNER JOIN item ON producto.Ite_Cod = item.Ite_Cod
                INNER JOIN ventas_det ON ventas_det.Pro_Cod = producto.Pro_Cod
                LEFT join renta_iva as renta on renta.Ren_Cod= ventas_det.Ren_Cod
                LEFT join renta_iva as iva_imp on iva_imp.Ren_Cod = ventas_det.Ren_Iva
                left join iva as ivas  on ivas.Iva_Cod = ventas_det.Iva_Cod
                left join ice on ice.Ice_Int = producto.Ice_Int
                left join unidad on unidad.Uni_Cod = producto.Uni_Cod
                left join produ_plan on produ_plan.Pro_Cod = producto.Pro_Cod and (Tip_Pld='V' OR Tip_Pld='I')
                left join det_plan on produ_plan.Pld_Cod = det_plan.Pld_Cod
                where Vet_Cod=$Par_Sql[0] order by Vet_Int";
                break;
            case 2:
                $sql="SELECT COUNT(*) as total FROM ccpp_cobrar WHERE Vet_Cod = '$Par_Sql[0]';";
                break;
            case 3:
                $sql="SELECT COUNT(Pro_Cod) as Cantidad FROM produ_plan WHERE Pro_cod = $Par_Sql[0] AND Tip_Pld in ('C', 'O');";
                break;
            case 4:
                $sql="SELECT Ite_Lar, v.Vet_Can, v.Vet_Pru, ROUND(k.Kar_Promedio,4) as Promedio,
                        v.Vet_Imp, ROUND(Kar_Promedio * Kar_Sal,4) AS Costo, ROUND(v.Vet_Imp - (Kar_Promedio * Kar_Sal),4) AS Utilidad FROM
                        kardex_ie as k, ventas_det as v, producto as p, item as i
                        WHERE (k.Pro_Cod = v.Pro_Cod AND k.Vet_Cod = v.Vet_Cod AND k.Kar_Int = v.Vet_Ite)
                        AND v.Pro_Cod = p.Pro_Cod
                        AND p.Ite_Cod = i.Ite_Cod
                        AND k.Vet_Cod = $Par_Sql[0]
                        AND k.Kar_Est = 'A'
                        GROUP BY k.Kar_Int, k.Vet_Cod, k.Pro_Cod";
                break;
            default: throw new Exception ("No existe la sql numero $id!");
        }
        //echo $this->getSqlString($sql)."<br/>";
        return $sql;
    }
    public function getRetencionVet($Vet_Cod){
        $this->_initCalculos();
        $sql=$this->select(false)->from('ventas_det',array(
                'Tot_Renta'=>new Zend_Db_Expr($this->castDecimal("SUM( IF(ventas_det.Ren_Cod IS NOT NULL,IF(renta_imp.Ren_Por>0,($this->Importe_Descu*renta_imp.Ren_Por/100),0),0) )")),
                'Tot_Iva'=>new Zend_Db_Expr($this->castDecimal("SUM( IF(ventas_det.Ren_Iva IS NOT NULL,IF(iva_imp.Ren_Por>0 AND Iva_Por!=0,($this->IVA*iva_imp.Ren_Por/100),0),0) )"))

            ))->join('ventas', "ventas.Vet_Cod = ventas_det.Vet_Cod", array('Ret_Num','Ret_Fec','Ret_Fec'))
              ->join('iva', "iva.Iva_Cod=ventas_det.Iva_Cod", array())
              ->joinLeft(array('renta_imp'=>'renta_iva'),'renta_imp.Ren_Cod= ventas_det.Ren_Cod',array())
              ->joinLeft(array('iva_imp'=>'renta_iva'),'iva_imp.Ren_Cod= ventas_det.Ren_Iva',array())
              ->where("ventas.Vet_Cod=?",$Vet_Cod)->group('ventas.Vet_Cod');
        return $sql;
    }
    private function setTotalesCols($sql){
        $this->_initCalculos();
        $sql->addCols(null,array(
            'Importe'   =>new Zend_Db_Expr($this->castDecimal("SUM($this->Importe)")),
            'Descu'     =>new Zend_Db_Expr($this->castDecimal("SUM($this->Descu)")),
            'Importe_Descu'=>new Zend_Db_Expr($this->castDecimal("SUM($this->Importe_Descu)")),
            'Sub_0'     =>new Zend_Db_Expr($this->castDecimal("SUM(IF(Iva_Por = 0,  $this->Importe_Descu , '0'))")),
            'Sub_12'    =>new Zend_Db_Expr($this->castDecimal("SUM(IF(Iva_Por != 0 AND Iva_Por != 5  AND Iva_Por!=8 , $this->Importe_Descu , '0'))")),
            'Sub_5'    =>new Zend_Db_Expr($this->castDecimal("SUM(IF(Iva_Por = 5, $this->Importe_Descu , '0'))")),
            'Sub_8'    =>new Zend_Db_Expr($this->castDecimal("SUM(IF(Iva_Por = 8, $this->Importe_Descu , '0'))")),

            'Ice_Tot'   =>new Zend_Db_Expr($this->castDecimal("SUM(IF(Vet_Ice != 0, $this->ICE, 0))")),
            'Iva_Tot'   =>new Zend_Db_Expr($this->castDecimal("SUM(IF(Iva_Por != 0, $this->IVA, 0))")),
            'Total'     =>new Zend_Db_Expr($this->castDecimal("SUM( $this->Importe_Descu + $this->ICE + $this->IVA )"))
        ));
        $sql->join('ventas_det', "ventas_det.Vet_Cod=$this->_name.Vet_Cod", array())
            ->join('iva', "iva.Iva_Cod=ventas_det.Iva_Cod", array());
    }
    private function getSummaryCols($sql,$menosNC=false){
        $sumas=$this->select(false)->from(array('tbl'=>new Zend_Db_Expr('('.$this->getSqlString($sql).')')),array(
            'Importe'   =>new Zend_Db_Expr($this->castDecimal("SUM(tbl.Importe)")),
            'Descu'     =>new Zend_Db_Expr($this->castDecimal("SUM(tbl.Descu)")),
            'Importe_Descu'=>new Zend_Db_Expr($this->castDecimal("SUM(tbl.Importe_Descu)")),
            'Sub_0'     =>new Zend_Db_Expr($this->castDecimal("SUM(tbl.Sub_0)")),
            'Sub_12'    =>new Zend_Db_Expr($this->castDecimal("SUM(tbl.Sub_12)")),
            'Sub_5'    =>new Zend_Db_Expr($this->castDecimal("SUM(tbl.Sub_5)")),
            'Sub_8'    =>new Zend_Db_Expr($this->castDecimal("SUM(tbl.Sub_8)")),
            'Ice_Tot'   =>new Zend_Db_Expr($this->castDecimal("SUM(tbl.Ice_Tot)")),
            'Iva_Tot'   =>new Zend_Db_Expr($this->castDecimal("SUM(tbl.Iva_Tot)")),
            'Total'     =>new Zend_Db_Expr($this->castDecimal("SUM(tbl.Total)"))
        ));
        return $sumas->getDataSelect();
    }
    public function nextNum($Par_Sql){
        $num='Vet_Num';
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
        return $this->select(true,array('next'=>$this->expr("CASE WHEN MAX($num)IS NOT NULL AND MAX($num)>=$Par_Sql[Aut_Fin] THEN(\n ".$this->getSqlString($minimo)."\n )\n ELSE IFNULL(MAX($num),$Par_Sql[Aut_Ini]-1)+1 END AS 'next'")))
            ->join('autorizaci', "autorizaci.Aut_Cod=$this->_name.Aut_Cod", array())
            ->join('puntos_imp', "puntos_imp.Pun_Cod=autorizaci.Pun_Cod", array())
            ->where("Suc_Cod=$_SESSION[Ses_Suc_Cod] AND autorizaci.Aut_Sri='$Par_Sql[Aut_Sri]' AND autorizaci.Tic_Cod=$Par_Sql[Tic_Cod] AND $num BETWEEN $Par_Sql[Aut_Ini] AND $Par_Sql[Aut_Fin]");
    }
}