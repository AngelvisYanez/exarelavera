<?php

use \Exception;

require_once(dirname(__file__) . "/../DATA/libs/AbstractModel.php");

class ventas extends AbstractModel
{
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
    protected $Prop;
    protected $Aux_Tot;

    protected function _initCalculos()
    {
        // Assuming these fields already exist in the ventas_det table
        // $this->Importe = "ventas_det.Vet_Imp"; // Field from ventas_det table
        // $this->Descu = "ventas_det.Vet_Dec"; // Field from ventas_det table
        // $this->Importe_Descu = "CAST((($this->Importe) - $this->Descu) AS DECIMAL(20,5))"; // Calculation of amount after discount
        // $this->ICE = "ventas_det.Vet_Ice"; // Field from ventas_det table
        // $this->IVA = "(CAST($this->Importe_Descu + $this->ICE AS DECIMAL(20,5)) * Iva_Por / 100)"; // Calculation of IVA
        if ($_SESSION['Ses_Emp_Cod'] == 534 || $_SESSION['Ses_Emp_Cod'] == 531 || $_SESSION['Ses_Emp_Cod'] == 44 || $_SESSION['Ses_Emp_Cod'] == 340) {
            $this->Vet_Imp = "(Vet_Pru * Vet_Can)";
            $this->Importe = "CAST(($this->Vet_Imp-($this->Vet_Imp * Vet_Dec/100)) AS DECIMAL(20, 4))"; // AS Importe,
            $this->Descu = "($this->Importe * ventas.Vet_Des / 100)";
            $this->Importe_Descu = "/*CAST(*/(($this->Importe) - $this->Descu) /*AS DECIMAL(20, 2))*/"; // Cálculo del importe después del descuento
            $this->ICE = "ventas_det.Vet_Ice"; // Campo de la tabla ventas_det
            $this->IVA = "(/*CAST*/($this->Importe_Descu + $this->ICE /*AS DECIMAL(20, 2)*/) * Iva_Por / 100)"; // Cálculo del IVA
            // $this->Prop = "ventas.Vet_Prop"; // Campo de la tabla ventas
            $this->Aux_Tot = "/*CAST(*/$this->Importe_Descu + $this->IVA + $this->ICE /*AS DECIMAL(20, 2))*/";
        } else {
            $this->Vet_Imp = "(Vet_Pru * Vet_Can)";
            $this->Importe = "CAST(($this->Vet_Imp-($this->Vet_Imp * Vet_Dec/100)) AS DECIMAL(20, 2))"; // AS Importe,
            $this->Descu = "($this->Importe * ventas.Vet_Des / 100)";
            $this->Importe_Descu = "CAST((($this->Importe) - $this->Descu) AS DECIMAL(20, 2))"; // Calculation of amount after discount
            $this->ICE = "ventas_det.Vet_Ice"; // Field from ventas_det table
            $this->IVA = "(CAST($this->Importe_Descu + $this->ICE AS DECIMAL(20, 2)) * Iva_Por / 100)"; // Calculation of IVA
            // $this->Prop = "ventas.Vet_Prop"; // Campo de la tabla ventas
            $this->Aux_Tot = "/*CAST(*/$this->Importe_Descu + $this->IVA + $this->ICE /*AS DECIMAL(20, 2))*/";
        }
    }

    /* crea una sql basica global para la tabla */
    public function _selectBasic($cond = null, $limits = false)
    {
        return $this->select(true, array('ventas.Vet_Cod as VetCod', 'ventas.Vet_Cod', 'Aut_Cod', 'Cli_Cod', 'Ciu_Cod', 'Caj_Cod', 'Vnd_Cod', 'ventas.Vet_Num', 'Vet_Des', 'Vet_Obs', 'Vet_Aut', 'Vet_Xml', 'Vet_Sri', 'Vet_Est', 'Ret_Num', 'Ret_Fec', 'Ret_Aut', 'Vet_Ntd', 'Vet_Fdm', 'Vet_Nns', 'Vet_Tpv', 'Tpc_Cod', 'Vet_Sys'/*, 'tipos_pago.Pag_Des', 'ventas.Vet_Prop'*/))
            ->joinLeft('caja_aper', "caja_aper.Caj_Cod=$this->_name.Caj_Cod", array('Caj_Fec'))
            ->addCols(null, array(
                'Secuencia'     => new Zend_Db_Expr("CONCAT(Suc_Sri,'-',Pun_Sri,'-',LPAD(CAST(ventas.Vet_Num AS CHAR),9,'0'))"),
                'Autorizacion'  => new Zend_Db_Expr("IF(Vet_Xml IS NULL OR TRIM(Vet_Xml)='', Aut_Sri, IF(Vet_Sri IS NULL OR TRIM(Vet_Sri)='','PENDIENTE',Vet_Sri))"),
                'Ret_Exi'       => new Zend_Db_Expr("IF(ventas.Ret_Fec IS NULL OR ventas.Ret_Fec = '0000-00-00','N','S')"),
                // 'FormasPago'    => new Zend_Db_Expr("GROUP_CONCAT(DISTINCT tipos_pago.Pag_Des ORDER BY tipos_pago.Pag_Des SEPARATOR ', ')")
                // 'Com_Codigo'       =>new Zend_Db_Expr("IF(ventas_compr.Com_Cod IS NULL,'N','S')") // Reemplazo de Com_Exi
            ))
            ->joinLeft('cliente', "cliente.Cli_Cod=$this->_name.Cli_Cod", array('Cli_Tic'))
            ->joinLeft('persona', "persona.Prs_Cod=cliente.Prs_Cod", array('Ruc' => new Zend_Db_Expr("IF(Cli_Ruf IS NULL OR TRIM(Cli_Ruf)='',persona.Prs_Ced,Cli_Ruf)"), 'Cliente' => new Zend_Db_Expr("IF(Cli_Fac IS NULL OR TRIM(Cli_Fac)='',CONCAT(persona.Prs_Ape,' ',persona.Prs_Nom),Cli_Fac)"), 'Prs_Nom', 'Prs_Ape', 'Prs_Ced', 'Prs_Dir', 'Ide_Cod'))
            ->joinLeft('identifica', "identifica.Ide_Cod=persona.Ide_Cod", array('Ide_Sri', 'Ide_Prv', 'Ide_Pre'))
            ->joinLeft('tipopagocom', "tipopagocom.Tpc_Cod=$this->_name.Tpc_Cod", array('Tpc_Sri' => new Zend_Db_Expr("LPAD(CAST(Tpc_Sri AS CHAR),2,'0')")))
            ->joinLeft('autorizaci', "autorizaci.Aut_Cod=$this->_name.Aut_Cod", array('Tic_Cod'))
            ->joinLeft('puntos_imp', "puntos_imp.Pun_Cod=autorizaci.Pun_Cod", array())
            ->joinLeft('tipo_compr', "tipo_compr.Tic_Cod=autorizaci.Tic_Cod", array('Tic_Sri' => new Zend_Db_Expr("LPAD(CAST(Tic_Sri AS CHAR),2,'0')"), 'Tic_Des'))
            ->joinLeft('sucursal', "sucursal.Suc_Cod=puntos_imp.Suc_Cod", array())
            ->joinLeft('vendedor', "vendedor.Vnd_Cod=ventas.Vnd_Cod", array(/*'Vnd_Cod'*/))
            // ->joinLeft('pago_venta', 'pago_venta.Vet_Cod=ventas.Vet_Cod', array())
            //  ->joinLeft('tipos_pago', 'tipos_pago.Pag_Cod=pago_venta.Pag_Cod', array())
            ->joinLeft(array('persona_ven' => 'persona'), "persona_ven.Prs_Cod=vendedor.Prs_Cod", array('Vendedor' => new Zend_Db_Expr("CONCAT(persona_ven.Prs_Ape,' ',persona_ven.Prs_Nom)")))/*->group('ventas.Vet_Cod')*/
            // union adicional para obtener el estado del comprobante
            /*->joinLeft('ventas_compr', "ventas_compr.Vet_Cod=$this->_name.Vet_Cod", array())
            ->joinLeft('comprobantes', "comprobantes.Com_Cod=ventas_compr.Com_Cod", array('Com_Est', 'Com_Cod')  )*/;
    }
    /* crea una sql standart para jqgrid, condiciones incluidas */
    public function _selectBasicGrid($cond = null, $limits = false)
    {
        $sel = $this->_selectBasic();
        $this->sqlByNombre("setEmpCod", $sel);

        if (!empty($cond['Tic_Cod']) && is_numeric($cond['Tic_Cod'])) $this->sqlByNombre("byTipoCompr", $sel, $cond);
        if ($this->hasVal($cond, 'range') && $cond['range'] == 'S') $this->sqlByNombre("byDateRange", $sel, $cond);
        if (isset($cond['op_opciones']) && isset($cond["search"])) {
            if ($cond['op_opciones'] == 'd') {
                $sel->where("ventas.Vet_Num = ?", $cond["search"]);
            } else {
                if ($cond['op_opciones'] == 'c') {
                    $sel->where("persona.Prs_Ced LIKE ? OR Cli_Ruf LIKE ?", "$cond[search]%");
                } elseif ($cond['op_opciones'] == 'f') {
                    $sel->where("Caj_Fec BETWEEN '$cond[Fec_Ini] 00:00:00' AND '$cond[Fec_Fin] 23:59:59'", null);
                } elseif ($cond['op_opciones'] == 'cd') {
                    $sel->where("ventas.Cli_Cod=?", $cond["Cli_Cod"]);
                } else {
                    $sel->where("(UPPER(CONCAT(persona.Prs_Ape,' ',persona.Prs_Nom)) LIKE UPPER(?)) OR UPPER(Cli_Fac) LIKE UPPER(?)", "%$cond[search]%");
                }
            }
        }
        if (!empty($cond['Pec_Cod']) && $cond['op_opciones'] !== 'd') {
            $sel->where("Caj_Fec BETWEEN '$cond[fecha_inicio] 00:00:00' AND '$cond[fecha_fin] 23:59:59'", null);
            if (!empty($cond['Cmb_Mes']))  $sel->where("MONTH(Caj_Fec)=?", "$cond[Cmb_Mes]");
        }
        return $sel;
    }
    public function setCount($sql)
    {
        $data = $sql->getDataSelect();
        if (!array_key_exists('lola', $data['from']))
            $sql->unsetFrom()->unsetCols()->from($this->_name, array('total' => 'COUNT(*)'));
        if (array_key_exists('ventas_det', $data['from'])) {
            $total = $this->select(false)->from(array('tbl' => new Zend_Db_Expr('(' . $this->getSqlString($sql) . ')')), array('total' => 'COUNT(*)'));
            $sql->setDataSelect($total->getDataSelect());
        }
    }
    /* formatea el array para insert o update */
    public function formatData($data, $type, $allData=null)
    {
        return ($type == 'I') ? $data : $data;
    }
    /* crea sentencia por id nombre sql */
    public function sqlByNombre($id, $Par_Sql, $cond = null)
    {
        if (is_object($Par_Sql)) {
            $sql = $Par_Sql;
            $Par_Sql = $cond;
        } else $sql = '';
        switch ($id) {
            case "":
                $sql = "";
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "setEmpCod":
                $sql->where("cliente.Emp_Cod=?", $_SESSION['Ses_Emp_Cod']);
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "setSucCod":
                $sql->where("puntos_imp.Suc_Cod=?", $_SESSION['Ses_Suc_Cod']);
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "bySucCod":
                if (!empty($Par_Sql['Suc_Cod'])) {
                    $sql->where("sucursal.Suc_Cod=?", $Par_Sql['Suc_Cod']);
                } else {
                    throw new Exception("El código de sucursal (Suc_Cod) no está definido o está vacío.");
                }
                //echo $this->getSqlString($sql)."<br/>";
                break;

            case "setUsuario":
                $sql->join('vendedor', "vendedor.Vnd_Cod=compras.Vnd_Cod", array('Vnd_Cod'))
                    ->join('puntos_imp', "puntos_imp.Pun_Cod=vendedor.Pun_Cod", array('Pun_Cod'))
                    ->join(array('persona_ven' => 'persona'), "persona_ven.Prs_Cod=vendedor.Prs_Cod", array('Vendedor' => new Zend_Db_Expr("CONCAT(persona_ven.Prs_Ape,' ',persona_ven.Prs_Nom)")));
                //echo $this->getSqlString($sql)."<br/>";
                break;

            case "setExportacion":
                $sql->joinLeft('exporta_vent', "exporta_vent.Vet_Cod=$this->_name.Vet_Cod", array('Eve_Cod', 'Ref_Cod'));
                $sql->addCols('', array("Exportacion" => "IF(exporta_vent.Eve_Cod IS NULL,'N','S')"));
                //echo $this->getSqlString($sql)."<br/>";
                break;

            case "setTotales":
                $this->setTotalesCols($sql);
                // $sql->group('ventas.Vet_Cod'); // comentada por reemplazo del CustomGroupBy, si da problema en totales descomentar
                // Ordenamiento en base a la variable CustomOrderBy
                $orderBy = isset($Par_Sql['CustomOrderBy']) && !empty($Par_Sql['CustomOrderBy']) ? $Par_Sql['CustomOrderBy'] : 'caja_aper.Caj_Fec ASC';
                switch ($orderBy) {
                    case 'Caj_Fec ASC':
                        $sql->order('caja_aper.Caj_Fec ASC');
                        break;
                    case 'Caj_Fec DESC':
                        $sql->order('caja_aper.Caj_Fec DESC');
                        break;
                    case 'Cliente ASC':
                        $sql->order(new Zend_Db_Expr("CONCAT(persona.Prs_Ape, ' ', persona.Prs_Nom) ASC")); // se opto por ponerlo asi ya que con solo Cliente da error
                        break;
                    case 'Tic_Des DESC':
                        $sql->order('tipo_compr.Tic_Sri DESC');
                        break;
                    case 'Vet_Num ASC':
                        $sql->order('ventas.Vet_Num ASC');
                        break;
                    default:
                        $sql->order($orderBy);
                        break;
                }

                $grupo = isset($Par_Sql['CustomGroupBy']) && !empty($Par_Sql['CustomGroupBy']) ? $Par_Sql['CustomGroupBy'] : 'ventas_Vet_Cod';
                switch ($grupo) {
                    case 'Agr_Cli':
                        $sql->group('ventas.Cli_Cod');
                        break;
                    case 'Agr_Prod':
                        $sql->group('ventas_det.Pro_Cod');
                        break;
                    default:
                        $sql->group('ventas.Vet_Cod');
                        break;
                }
                //echo $this->getSqlString($sql)."<br/>";
                break;

            case "setVentasDet":
                if (!$sql->hasTable("ventas_det")) $sql->join('ventas_det', "ventas_det.Vet_Cod=$this->_name.Vet_Cod", array('*'));
                //echo $this->getSqlString($sql)."<br/>";
                break;

            case "setItemsVent":
                $this->_initCalculos();
                if (!$sql->hasTable("ventas_det")) $sql->join('ventas_det', "ventas_det.Vet_Cod = $this->_name.Vet_Cod", array('total' => $this->expr('COUNT(*)')));
                $sql->addCols('', array(
                    'Cantidad' => new Zend_Db_Expr($this->castDecimal("SUM($this->Importe)")),
                    'Veces' => new Zend_Db_Expr($this->castDecimal("SUM(Vet_Can)"))
                ));
                break;

            case "setProductoInfo":
                if (!$sql->hasTable("ventas_det")) $sql->join('ventas_det', "ventas_det.Vet_Cod = $this->_name.Vet_Cod", array('Vet_Can', 'Vet_Pru', 'Vet_Dec'));
                $sql->join('producto', "producto.Pro_Cod=ventas_det.Pro_Cod", array('*'))
                    ->join('item', "producto.Ite_Cod = item.Ite_Cod", array('*'))
                    ->join('marca', "producto.Mar_Cod=marca.Mar_Cod", array('Mar_Des'));
                break;

            case "setAliCuotas":
                $this->_initCalculos();
                $selectP = $this->select(false)->from(array('alp' => 'ali_pagos'))
                    ->join('cliente', "cliente.Cli_Cod=alp.Cli_Cod", array(''))
                    ->addCols(null, array('Pagos' => new Zend_Db_Expr('SUM(IFNULL(Ali_Pag,0))')))
                    //->where("alp.Vet_Cod = Vet_Cod")
                    ->where("cliente.Emp_Cod=?", $_SESSION['Ses_Emp_Cod'])
                    ->group('alp.Vet_Cod');
                if (isset($Par_Sql['Cli_Cod'])) $selectP->where('alp.Cli_Cod =?', $Par_Sql['Cli_Cod']); //Cambio pedido el 22/05/2019 //no viene Cli_Cod y no funciono
                $sql->joinLeft(array('lola' => $selectP), "lola.Vet_Cod = ventas.Vet_Cod", array('Pagos'));
                $sql->addCols(null, array('Resto' => new Zend_Db_Expr($this->castDecimal($this->castDecimal("SUM( $this->Importe_Descu + $this->ICE + $this->IVA )") . "-IF(Pagos IS NULL,0,Pagos)"))));
                //echo $this->getSqlString($sql)."<br/>";
                break;

            case "isSummary":
                $sql->unsetCols();
                $this->setTotalesCols($sql);
                // Asegurar que tipo_compr esté disponible para aplicar el factor de Nota de Crédito
                if (!$sql->hasTable("tipo_compr")) {
                    if (!$sql->hasTable("autorizaci")) {
                        $sql->joinLeft('autorizaci', "autorizaci.Aut_Cod=$this->_name.Aut_Cod", array());
                    }
                    $sql->joinLeft('tipo_compr', "tipo_compr.Tic_Cod=autorizaci.Tic_Cod", array('Tic_Sri' => new Zend_Db_Expr("LPAD(CAST(tipo_compr.Tic_Sri AS CHAR),2,'0')")));
                } else {
                    // Si ya existe, asegurar que Tic_Sri esté en las columnas
                    $sql->addCols(null, array('Tic_Sri' => new Zend_Db_Expr("LPAD(CAST(tipo_compr.Tic_Sri AS CHAR),2,'0')")));
                }
                $sql->group('ventas.Vet_Cod');
                $sql->setDataSelect($this->getSummaryCols($sql));
                //echo $this->getSqlString($sql)."<br/>";
                break;

            case "byVndCod":
                $sql->where("ventas.Vnd_Cod=?", $Par_Sql['Vnd_Cod']);
                break;

            case "hasRetencion":
                if (isset($Par_Sql['Chk_Ret']) && $Par_Sql['Chk_Ret'] === 'S') {
                    $sql->where("ventas.Ret_Num IS NOT NULL");
                } else {
                    $sql->where("ventas.Ret_Num IS NULL");
                }
                //echo $this->getSqlString($sql)."<br/>";
                break;

            case "byVetAut":
                if (!empty($Par_Sql['Vet_Aut']) && $Par_Sql['Vet_Aut'] !== 'T' && in_array($Par_Sql['Vet_Aut'], array('S', 'N'), true)) {
                    $sql->where("ventas.Vet_Aut=?", $Par_Sql['Vet_Aut']);
                }
                break;

            case "hasReembolso":
                $sql->where("EXISTS (SELECT 1 FROM venta_reembolsos reemb WHERE reemb.Vet_Cod = ventas.Vet_Cod)");
                break;

            case "byPunCod":
                if (!empty($Par_Sql['Pun_Sri']) && $Par_Sql['Pun_Sri'] !== 'T') {
                    $sql->where("autorizaci.Pun_Sri = ?", $Par_Sql['Pun_Sri']);
                } else {
                    throw new Exception("El código de punto de venta no está definido.");
                }
                //echo $this->getSqlString($sql)."<br/>";
                break;

            case "byTipoCompr":
                if ($Par_Sql['Tic_Cod'] == 24) {
                    $sql->where("EXISTS (SELECT 1 FROM venta_reembolsos reemb WHERE reemb.Vet_Cod = ventas.Vet_Cod)");
                    $sql->where("tipo_compr.Tic_Cod=?", 1);
                } else {
                    $sql->where("tipo_compr.Tic_Cod=?", $Par_Sql['Tic_Cod']);
                }
                //echo $this->getSqlString($sql)."<br/>";
                break;

            case "byTipPago":
                if (!$sql->hasTable("ccpp_cobrar")) {
                    $sql->joinLeft('ccpp_cobrar', "ccpp_cobrar.Vet_Cod = ventas.Vet_Cod", array());
                }
                $sql->addCols(null, array(
                    'Pago' => new Zend_Db_Expr("IF(ccpp_cobrar.Cpc_Cod IS NULL, 'Contado', 'Credito')")
                ))
                    ->group('ventas.Vet_Cod')
                    ->where("IF(ccpp_cobrar.Cpc_Cod IS NULL, 'Contado', 'Credito') = ?", $Par_Sql['For_Cod']);
                break;

            case "bySustento":
                $sql->where("sustento.Tri_Cod=?", $Par_Sql['Tri_Cod']);
                //echo $this->getSqlString($sql)."<br/>";
                break;

            case "byDateRange":
                $sql->where('Caj_Fec BETWEEN ? AND ?', array($Par_Sql['Fec_Ini'], $Par_Sql['Fec_Fin']));
                //->where('Cop_Fec<=?',$Par_Sql['Fec_Fin']);
                //echo $this->getSqlString($sql)."<br/>";
                break;

            case "byCliCod":
                $sql->where('cliente.Cli_Cod=?', $Par_Sql['Cli_Cod']);
                //echo $this->getSqlString($sql)."<br/>";
                break;

            //Documento de pago
            case "byPagCod":
                if (!$sql->hasTable("pago_venta")) {
                    $sql->joinLeft('pago_venta', 'pago_venta.Vet_Cod = ventas.Vet_Cod', array());
                }
                if (!$sql->hasTable("tipos_pago")) {
                    $sql->joinLeft('tipos_pago', 'tipos_pago.Pag_Cod = pago_venta.Pag_Cod', array());
                }
                $sql->where('tipos_pago.Pag_Cod = ?', $Par_Sql['Pag_Cod']);
                break;

            default:
                $this->sqlByParams($id, $sql, array(
                    'isActive' => "$this->_name.$this->_state='A'",
                    'isInactive' => "$this->_name.$this->_state='I'",
                    'groupByAliCod' => 'lola.Ali_Cod',
                    'isTicCod' => "tipo_compr.Tic_Sri=?",
                    // 'hasSaldo'=>"(Pagos IS NULL OR Pagos >= 0)",
                    'hasAliPag' => "Ali_Pag IS NOT NULL"
                )); //echo $this->getSqlString($sql)."<br/>";
        }
        //echo $this->getSqlString($sql)."<br/>";
        return $sql;
    }
    /* crea sentencia por id numero sql */
    public function sqlByNumero($id, $Par_Sql, $cond = null)
    {
        $sql = (is_object($Par_Sql) ? $Par_Sql : '');
        switch ($id) {
            case 0:
                $sql = "SELECT ventas.Vet_Cod, tipo_compr.Tic_Cod, Tic_Des, ventas.Vet_Num, 
                        Concat(Prs_Ape, ' ', Prs_Nom) as Cliente,  Caj_Fec,
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
                $sql = "SELECT ventas_det.* , Ite_Lar, renta.Ren_Sri as Ret_Ren_Sri, renta.Ren_Cod as Ret_Ren_Cod, renta.Ren_Por as Ret_Ren_Por, renta.Ren_Con as Ret_Ren_Con, renta.Ren_Tip as Ret_Ren_Tip, renta.Ren_Ret as Ret_Ren_Ret, renta.Ren_Est as Ret_Ren_Est,  iva_imp.Ren_Sri as Iva_Ren_Sri,  iva_imp.Ren_Cod as Iva_Ren_Cod,
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
                $sql = "SELECT COUNT(*) as total FROM ccpp_cobrar WHERE Vet_Cod = '$Par_Sql[0]';";
                break;
            case 3:
                $sql = "SELECT COUNT(Pro_Cod) as Cantidad FROM produ_plan WHERE Pro_cod = $Par_Sql[0] AND Tip_Pld in ('C', 'O');";
                break;
            case 4:
                $sql = "SELECT Ite_Lar, v.Vet_Can, v.Vet_Pru, ROUND(k.Kar_Promedio,4) as Promedio,
                        v.Vet_Imp, ROUND(Kar_Promedio * Kar_Sal,4) AS Costo, ROUND(v.Vet_Imp - (Kar_Promedio * Kar_Sal),4) AS Utilidad FROM
                        kardex_ie as k, ventas_det as v, producto as p, item as i
                        WHERE (k.Pro_Cod = v.Pro_Cod AND k.Vet_Cod = v.Vet_Cod AND k.Kar_Int = v.Vet_Ite)
                        AND v.Pro_Cod = p.Pro_Cod
                        AND p.Ite_Cod = i.Ite_Cod
                        AND k.Vet_Cod = $Par_Sql[0]
                        AND k.Kar_Est = 'A'
                        GROUP BY k.Kar_Int, k.Vet_Cod, k.Pro_Cod";
                break;
            case 5:
                $sql = "SELECT Vet_Can,Vet_Can AS 'index', ventas_det.Pro_Cod,ventas_det.Iva_Cod,Iva_Por,Iva_Sri,Ite_Lar,Vet_Can,Vet_Pru,Vet_Imp,Vet_Dec,Uni_Des FROM ventas_det 
                    INNER JOIN producto ON producto.Pro_Cod=ventas_det.Pro_Cod
                    INNER JOIN item ON producto.Ite_Cod = item.Ite_Cod
                    INNER JOIN unidad ON producto.Uni_Cod=unidad.Uni_Cod
                    INNER JOIN iva ON iva.Iva_Cod=ventas_det.Iva_Cod
                WHERE Vet_Cod=$Par_Sql[0] ORDER BY Vet_Int;";
                //ChromePhp::log($sql);
                break;
            case 6: //Select para cargar los datos de la tabla perio_cont
                $sql = "SELECT Pec_Cod,Pec_Fei,Pec_Fef,CAST(SUBSTRING_INDEX(Pec_Fei,'-',1) AS char) AS Anio,perio_cont.Pla_Cod
                    FROM perio_cont
                    LEFT JOIN plan_cuenta ON perio_cont.Pla_Cod=plan_cuenta.Pla_Cod
                    WHERE plan_cuenta.Emp_Cod='$Par_Sql[0]' AND Pec_Est='A' ORDER BY Pec_Fei DESC";
                //ChromePhp::log($sql);
                break;
            case 7:
                $where_doc = "Tic_Sri='0' OR Tic_Sri='1' OR Tic_Sri='2' OR Tic_Sri='41' OR Tic_Sri='44' OR Tic_Sri='47' OR Tic_Sri='48' OR Tic_Sri='49' OR Tic_Sri='50' OR Tic_Sri='51' OR Tic_Sri='52'";
                if (empty($Par_Sql['limits'])) {
                    $campos = "COUNT(ventas.Vet_Cod) AS total";
                } else {
                    $campos = "ventas.*,
                            vende.Prs_Ape,
                            vende.Prs_Nom,
                            ciudad.Ciu_Des,
                            Tic_Des,Emp_Cod,
                            ventas_compr.Com_Cod,
                            tipo_compr.Tic_Sri,
                            ccpp_cobrar.Cpc_Cod,
                            tipopagocom.*,
                            Caj_Fec as Vet_Fec,
                            concat(vende.Prs_Ape,' ',vende.Prs_Nom)as vendedor_per,
                            concat(cliente_ven.Prs_Ape,' ',cliente_ven.Prs_Nom)as cliente_per,
                            cliente_ven.Prs_Ced,
                            comprobantes.Pec_Cod,
                            if(ccpp_cobrar.Cpc_Cod is null,'Contado','Credito')as Pago,
                            if(ventas_compr.Com_Cod is null,'N','S')as Com_Exi,
                            ventas_compr.Com_Cod,
                            // ventas.Vet_Prop,
                            if(ventas.Ret_Fec is null || ventas.Ret_Fec = '0000-00-00','N','S')as Ret_Exi";
                }
                $Par_Sql['Tic_Cod'] = (!empty($Par_Sql['Tic_Cod']) ? "AND ventas.Tic_Cod=$Par_Sql[Tic_Cod]" : '');
                if ($Par_Sql['op_opciones'] == 'd') {
                    $search = "AND ventas.Vet_Num = '$Par_Sql[search]'";
                    $Par_Sql['Cmb_Mes'] = $Par_Sql['Pec_Cod'] = '';
                } else {
                    $Par_Sql['Cmb_Mes'] = (!empty($Par_Sql['Pec_Cod']) && !empty($Par_Sql['Cmb_Mes']) ? "AND MONTH(Caj_Fec)=$Par_Sql[Cmb_Mes]" : '');
                    $Par_Sql['Pec_Cod'] = (!empty($Par_Sql['Pec_Cod']) ? "AND Caj_Fec BETWEEN '$Par_Sql[fecha_inicio] 00:00:00' AND '$Par_Sql[fecha_fin] 23:59:59'" : '');
                    if ($Par_Sql['op_opciones'] == 'c')
                        $search = "AND cliente_ven.Prs_Ced LIKE '$Par_Sql[search]%'";
                    else
                        $search = "AND (UPPER(CONCAT(cliente_ven.Prs_Ape,' ',cliente_ven.Prs_Nom)) LIKE UPPER('%$Par_Sql[search]%'))";
                }

                if (isset($Par_Sql["mis_ingresos"])) {
                    if ($Par_Sql["mis_ingresos"] == 'S') {
                        $filtroUsuario = "AND vendedor.Prs_cod = $_SESSION[Ses_Prs_Cod]";
                    }
                } else {
                    $filtroUsuario = '';
                }

                $sql = "SELECT $campos FROM ventas
                        INNER JOIN vendedor ON vendedor.Vnd_Cod = ventas.Vnd_Cod
                        INNER JOIN persona as vende ON vendedor.Prs_Cod = vende.Prs_Cod
                        left join ventas_compr on ventas_compr.Vet_Cod=ventas.Vet_Cod
                        inner join cliente on cliente.Cli_Cod= ventas.Cli_Cod
                        INNER JOIN persona as cliente_ven ON cliente_ven.Prs_Cod = cliente.Prs_Cod
                        left join ccpp_cobrar on ccpp_cobrar.Vet_Cod=ventas.Vet_Cod
                        INNER JOIN ciudad ON ciudad.Ciu_Cod = ventas.Ciu_Cod
                        left join tipopagocom on tipopagocom.Tpc_Cod = ventas.Tpc_Cod
                        left join comprobantes on comprobantes.Com_Cod = ventas_compr.Com_Cod AND comprobantes.Com_Est='A'
                        INNER JOIN autorizaci on ventas.Aut_Cod = autorizaci.Aut_Cod
                        INNER JOIN puntos_imp ON puntos_imp.Pun_Cod = autorizaci.Pun_Cod AND puntos_imp.Suc_Cod=$_SESSION[Ses_Suc_Cod]
                        INNER JOIN tipo_compr ON tipo_compr.Tic_Cod = ventas.Tic_Cod
                        inner join caja_aper on caja_aper.Caj_Cod=ventas.Caj_Cod
                    WHERE ($where_doc) AND ventas.Vet_Est<>'E'  AND cliente.Emp_Cod=$Par_Sql[Emp_Cod] $Par_Sql[Tic_Cod] $Par_Sql[Pec_Cod] $Par_Sql[Cmb_Mes] $filtroUsuario $search
                    $Par_Sql[order] $Par_Sql[limits] ;";
                //echo $sql.'<br/>';
                //ChromePhp::log($sql);
                break;
            case 8:
                $sql = "SELECT " . (!empty($Par_Sql[1]) ? "SUM(det_ccpp_c.Cpc_Val)" : "COUNT(det_ccpp_c.Cpc_Cod)") . "AS total FROM det_ccpp_c INNER JOIN comprobantes ON det_ccpp_c.Com_Cod=comprobantes.Com_Cod WHERE Cpc_Cod='$Par_Sql[0]' " . (!empty($Par_Sql[1]) ? "AND Cpc_Est='$Par_Sql[1]' AND Com_Est='A'" : '') . ";";
                //echo $sql.'<br/>';
                break;
            default:
                throw new Exception("No existe la sql numero $id!");
        }
        //echo $this->getSqlString($sql)."<br/>";
        return $sql;
    }
    public function getRetencionVet($Vet_Cod)
    {
        $this->_initCalculos();
        $sql = $this->select(false)->from('ventas_det', array(
            'Tot_Renta' => new Zend_Db_Expr($this->castDecimal("SUM( IF(ventas_det.Ren_Cod IS NOT NULL,IF(renta_imp.Ren_Por>0,($this->Importe_Descu*renta_imp.Ren_Por/100),0),0) )")),
            'Tot_Iva' => new Zend_Db_Expr($this->castDecimal("SUM( IF(ventas_det.Ren_Iva IS NOT NULL,IF(iva_imp.Ren_Por>0 AND Iva_Por!=0,($this->IVA*iva_imp.Ren_Por/100),0),0) )"))

        ))->join('ventas', "ventas.Vet_Cod = ventas_det.Vet_Cod", array('Ret_Num', 'Ret_Fec', 'Ret_Fec'))
            ->join('iva', "iva.Iva_Cod=ventas_det.Iva_Cod", array())
            ->joinLeft(array('renta_imp' => 'renta_iva'), 'renta_imp.Ren_Cod= ventas_det.Ren_Cod', array())
            ->joinLeft(array('iva_imp' => 'renta_iva'), 'iva_imp.Ren_Cod= ventas_det.Ren_Iva', array())
            ->where("ventas.Vet_Cod=?", $Vet_Cod)->group('ventas.Vet_Cod');
        return $sql;
    }

    private function setTotalesCols($sql)
    {
        $this->_initCalculos();
        $sql->addCols(null, array(
            'Importe'   => new Zend_Db_Expr($this->castDecimal("SUM($this->Importe)")),
            'Descu'     => new Zend_Db_Expr($this->castDecimal("SUM($this->Descu)")),
            'Importe_Descu' => new Zend_Db_Expr($this->castDecimal("SUM($this->Importe_Descu)")),
            'NoIVA'     => new Zend_Db_Expr($this->castDecimal("SUM(IF(Iva_Por = 0 AND Iva_Sri = 6, $this->Importe, 0))")),
            'Sub_0'     => new Zend_Db_Expr($this->castDecimal("SUM(IF(Iva_Por = 0 AND Iva_Sri = 0,  $this->Importe , '0'))")),
            'Sub_5'     => new Zend_Db_Expr($this->castDecimal("SUM(IF(Iva_Por = 5,  $this->Importe , '0'))")),
            'Sub_8'     => new Zend_Db_Expr($this->castDecimal("SUM(IF(Iva_Por = 8,  $this->Importe , '0'))")),
            'Sub_12'    => new Zend_Db_Expr($this->castDecimal("SUM(IF(Iva_Por = 12, $this->Importe , '0'))")),
            'Sub_15'     => new Zend_Db_Expr($this->castDecimal("SUM(IF(Iva_Por != 0 AND Iva_Por != 5  AND Iva_Por!=8  AND Iva_Por!=12,  $this->Importe , '0'))")),
            // 'Sub_12'    =>new Zend_Db_Expr($this->castDecimal("SUM(IF(Iva_Por != 0, $this->Importe_Descu , '0'))")), // reemplazo por los demas % del IVA
            'Ice_Tot'   => new Zend_Db_Expr($this->castDecimal("SUM(IF(Vet_Ice != 0, $this->ICE, 0))")),
            'Iva_Tot'   => new Zend_Db_Expr($this->castDecimal("SUM(IF(Iva_Por != 0, $this->IVA, 0))")),
            // 'Total'     =>new Zend_Db_Expr($this->castDecimal("SUM( $this->Importe_Descu + $this->ICE + $this->IVA )"))
            // 'Prop'      => new Zend_Db_Expr($this->castDecimal("SUM(tbl.Prop)")),
            // 'Total' => new Zend_Db_Expr($this->castDecimal("SUM($this->Aux_Tot)")) // Usar la variable Total
            'Total' => new Zend_Db_Expr($this->castDecimal("SUM($this->Aux_Tot)")), // Usar la variable Total
            'Tot_Renta' => new Zend_Db_Expr($this->castDecimal("SUM( IF(ventas_det.Ren_Cod IS NOT NULL,IF(renta_imp.Ren_Por>0,($this->Importe_Descu*renta_imp.Ren_Por/100),0),0) )")),
            'Tot_Iva' => new Zend_Db_Expr($this->castDecimal("SUM( IF(ventas_det.Ren_Iva IS NOT NULL,IF(iva_imp.Ren_Por>0 AND Iva_Por!=0,($this->IVA*iva_imp.Ren_Por/100),0),0) )"))
        ));
        $sql->join('ventas_det', "ventas_det.Vet_Cod=$this->_name.Vet_Cod", array())
            ->join('iva', "iva.Iva_Cod=ventas_det.Iva_Cod", array())
            ->joinLeft(array('renta_imp' => 'renta_iva'), 'renta_imp.Ren_Cod= ventas_det.Ren_Cod', array())
            ->joinLeft(array('iva_imp' => 'renta_iva'), 'iva_imp.Ren_Cod= ventas_det.Ren_Iva', array());
    }
    private function getSummaryCols($sql, $menosNC = false)
    {
        $sumas = $this->select(false)->from(array('tbl' => new Zend_Db_Expr('(' . $this->getSqlString($sql) . ')')), array(
            'Importe'   => new Zend_Db_Expr($this->castDecimal("SUM(IF(tbl.Tic_Sri = '04', -1 * tbl.Importe, tbl.Importe))")),
            'Descu'     => new Zend_Db_Expr($this->castDecimal("SUM(IF(tbl.Tic_Sri = '04', -1 * tbl.Descu, tbl.Descu))")),
            'Importe_Descu' => new Zend_Db_Expr($this->castDecimal("SUM(IF(tbl.Tic_Sri = '04', -1 * tbl.Importe_Descu, tbl.Importe_Descu))")),
            'NoIVA'     => new Zend_Db_Expr($this->castDecimal("SUM(IF(tbl.Tic_Sri = '04', -1 * tbl.NoIVA, tbl.NoIVA))")),
            'Sub_0'     => new Zend_Db_Expr($this->castDecimal("SUM(IF(tbl.Tic_Sri = '04', -1 * tbl.Sub_0, tbl.Sub_0))")),
            'Sub_5'     => new Zend_Db_Expr($this->castDecimal("SUM(IF(tbl.Tic_Sri = '04', -1 * tbl.Sub_5, tbl.Sub_5))")),
            'Sub_8'     => new Zend_Db_Expr($this->castDecimal("SUM(IF(tbl.Tic_Sri = '04', -1 * tbl.Sub_8, tbl.Sub_8))")),
            'Sub_12'    => new Zend_Db_Expr($this->castDecimal("SUM(IF(tbl.Tic_Sri = '04', -1 * tbl.Sub_12, tbl.Sub_12))")),
            'Sub_15'     => new Zend_Db_Expr($this->castDecimal("SUM(IF(tbl.Tic_Sri = '04', -1 * tbl.Sub_15, tbl.Sub_15))")),
            'Ice_Tot'   => new Zend_Db_Expr($this->castDecimal("SUM(IF(tbl.Tic_Sri = '04', -1 * tbl.Ice_Tot, tbl.Ice_Tot))")),
            'Iva_Tot'   => new Zend_Db_Expr($this->castDecimal("SUM(IF(tbl.Tic_Sri = '04', -1 * tbl.Iva_Tot, tbl.Iva_Tot))")),
            // 'Prop'      => new Zend_Db_Expr($this->castDecimal("SUM(tbl.Prop)")),
            // 'Total'     => new Zend_Db_Expr($this->castDecimal("SUM(IF(tbl.Tic_Sri = '04', -1 * tbl.Total, tbl.Total))"))
            'Total'     => new Zend_Db_Expr($this->castDecimal("SUM(IF(tbl.Tic_Sri = '04', -1 * tbl.Total, tbl.Total))")),
            'Tot_Renta' => new Zend_Db_Expr($this->castDecimal("SUM(IF(tbl.Tic_Sri = '04', -1 * IFNULL(tbl.Tot_Renta,0), IFNULL(tbl.Tot_Renta,0)))")),
            'Tot_Iva'   => new Zend_Db_Expr($this->castDecimal("SUM(IF(tbl.Tic_Sri = '04', -1 * IFNULL(tbl.Tot_Iva,0), IFNULL(tbl.Tot_Iva,0)))"))
        ));
        return $sumas->getDataSelect();
    }
    public function nextNum($Par_Sql)
    {
        $num = 'ventas.Vet_Num';
        $notExist = $this->select(false)
            ->from(array('n' => $this->_name), array($this->expr('NULL')))
            ->join(array('na' => 'autorizaci'), "na.Aut_Cod=n.Aut_Cod", array())
            ->join(array('np' => 'puntos_imp'), "np.Pun_Cod=na.Pun_Cod", array())
            ->where("n.$num=t.$num+1 AND np.Suc_Cod=$_SESSION[Ses_Suc_Cod] AND na.Aut_Sri='$Par_Sql[Aut_Sri]' AND na.Tic_Cod=$Par_Sql[Tic_Cod] AND n.$num BETWEEN $Par_Sql[Aut_Ini] AND $Par_Sql[Aut_Fin]");
        $minimo = $this->select(false)
            ->from(array('t' => $this->_name), array($this->expr("MIN(t.$num)+1")))
            ->join(array('ta' => 'autorizaci'), "ta.Aut_Cod=t.Aut_Cod", array())
            ->join(array('tp' => 'puntos_imp'), "tp.Pun_Cod=ta.Pun_Cod", array())
            ->where("tp.Suc_Cod=$_SESSION[Ses_Suc_Cod] AND ta.Aut_Sri='$Par_Sql[Aut_Sri]' AND ta.Tic_Cod=$Par_Sql[Tic_Cod] AND t.$num BETWEEN $Par_Sql[Aut_Ini] AND $Par_Sql[Aut_Fin]")
            ->where("NOT EXISTS(\n\t" . $this->getSqlString($notExist) . "\n\t)");
        return $this->select(true, array('next' => $this->expr("CASE WHEN MAX($num)IS NOT NULL AND MAX($num)>=$Par_Sql[Aut_Fin] THEN(\n " . $this->getSqlString($minimo) . "\n )\n ELSE IFNULL(MAX($num),$Par_Sql[Aut_Ini]-1)+1 END AS 'next'")))
            ->join('autorizaci', "autorizaci.Aut_Cod=$this->_name.Aut_Cod", array())
            ->join('puntos_imp', "puntos_imp.Pun_Cod=autorizaci.Pun_Cod", array())
            ->where("Suc_Cod=$_SESSION[Ses_Suc_Cod] AND autorizaci.Aut_Sri='$Par_Sql[Aut_Sri]' AND autorizaci.Tic_Cod=$Par_Sql[Tic_Cod] AND $num BETWEEN $Par_Sql[Aut_Ini] AND $Par_Sql[Aut_Fin]");
    }
    public function tipo_doc_pago($Vet_Cod)
    {
        $sql = $this->select(false)
            ->from('pago_venta', array(
                'FormasPago' => new Zend_Db_Expr("GROUP_CONCAT( tipos_pago.Pag_Des ORDER BY tipos_pago.Pag_Des SEPARATOR ', ')")
            ))
            ->joinLeft('tipos_pago', 'tipos_pago.Pag_Cod = pago_venta.Pag_Cod', array())
            ->where('pago_venta.Vet_Cod = ?', $Vet_Cod)
             ->group('pago_venta.Vet_Cod');

        return ($sql); // Retorna directamente los datos
    }
}