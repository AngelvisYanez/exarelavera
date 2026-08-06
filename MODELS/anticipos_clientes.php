<?php


require_once(dirname(__file__) . "/../DATA/libs/AbstractModel.php");
class anticipos_clientes extends AbstractModel
{
    protected $_name = 'anticipos_clientes';
    protected $_primary = array('Ant_Cod');
    protected $_state = 'Ant_Est';
    //protected $_fields = array(); // se declara las filas que va a tener el arreglo en caso de insert o update //opcional

    /* crea una sql basica global para la tabla */
    public function _selectBasic($cond = null)
    {
        return $this->select(true, array('*', 'codigoCompra' => "CONCAT(tpAst.Tia_Abr, '-', MONTH(cprbnt.Com_Fec), '-', cprbnt.Com_Num)"))
            ->join(array('cli' => 'cliente'), "cli.Cli_Cod = $this->_name.Cli_Cod")
            ->joinLeft(array('prs' => 'persona'), "prs.Prs_Cod = cli.Prs_Cod", array('*', 'nombre' => "concat(prs.Prs_Nom,' ',prs.Prs_Ape)", 'cedProv' => 'prs.Prs_Ced'))
            ->join(array('cprbnt' => 'comprobantes'), "cprbnt.Com_Cod = $this->_name.Com_Cod", array('*'))
            ->join(array('tpAst' => 'tipo_asien'), "tpAst.Tia_Cod = cprbnt.Tia_Cod");
    }
    /* crea una sql standart para jqgrid, condiciones incluidas */
    public function _selectBasicGrid($cond = null)
    {
        $sel = $this->_selectBasic();
        $this->sqlByNombre("setEmpCod", $sel);
        if (isset($cond['op_opciones'])) {
            if ($cond['op_opciones'] == 'c') {
                $sel->where("prs.Prs_Ced = ?", $cond["search"]);
                $sel->where("Ant_Fec BETWEEN '$cond[txt_fec_ini] 00:00:00' AND '$cond[txt_fec_fin] 23:59:59'", null);
            } else {
                $sel->where("(UPPER(prs.Prs_Nom) LIKE UPPER(?)) OR UPPER(prs.Prs_Ape) LIKE UPPER(?)", "%$cond[search]%");
                $sel->where("Ant_Fec BETWEEN '$cond[txt_fec_ini] 00:00:00' AND '$cond[txt_fec_fin] 23:59:59'", null);
            }
        }
        return $sel;
    }
    /* formatea el array para insert o update */
    public function formatData($data, $type, $allData = null)
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
                $sql->where("Emp_Cod=?", $_SESSION['Ses_Emp_Cod']);
                //echo $this->getSqlString($sql)."<br/>";
                break;
            // reemplazado por implementacion de fecha corte
            // case "getDetAntCCCC":
            //     $sql->joinLeft( array('daCCCC' => 'det_ant_cccc'), "daCCCC.Ant_Cod = $this->_name.Ant_Cod", array('Ddc_Cod', 'Ddc_Val'));

            //     if (!empty($Par_Sql['Pec_Cod']) && $Par_Sql['Pec_Cod'] === 'Corte') {
            //         // Usar COALESCE para combinar Cpc_Fec (detalle) y Ant_Fec (principal)
            //         $sql->joinLeft( array('dccppc' => 'det_ccpp_c'), "dccppc.Dcc_Cod = daCCCC.Dcc_Cod", 
            //             array('Cpc_Fec' => new Zend_Db_Expr("COALESCE(dccppc.Cpc_Fec, $this->_name.Ant_Fec)"))
            //         );

            //         if (!empty($Par_Sql['txt_fec_ini']) && !empty($Par_Sql['txt_fec_fin'])) {
            //             // Filtrar por la fecha combinada (Cpc_Fec o Ant_Fec)
            //             $sql->where("COALESCE(dccppc.Cpc_Fec, $this->_name.Ant_Fec) >= ?", $Par_Sql['txt_fec_ini']);
            //             $sql->where("COALESCE(dccppc.Cpc_Fec, $this->_name.Ant_Fec) <= ?", $Par_Sql['txt_fec_fin']);
            //         }
            //     }
            //     break;
            // reemplaza al anterior con mejora para el caso de que no haya fecha en el detalle
            case "getDetAntCCCC":
                $sql->joinLeft(array('daCCCC' => 'det_ant_cccc'), "daCCCC.Ant_Cod = $this->_name.Ant_Cod", array('Ddc_Cod', 'Ddc_Val'));
                if (!empty($Par_Sql['Pec_Cod']) && $Par_Sql['Pec_Cod'] === 'Corte') {
                    // LEFT JOIN al detalle con posible fecha
                    $sql->joinLeft(
                        array('dccppc' => 'det_ccpp_c'),
                        "dccppc.Dcc_Cod = daCCCC.Dcc_Cod",
                        array('Cpc_Fec' => new Zend_Db_Expr("COALESCE(dccppc.Cpc_Fec, $this->_name.Ant_Fec)"))
                    );

                    if (!empty($Par_Sql['txt_fec_ini']) && !empty($Par_Sql['txt_fec_fin'])) {
                        $sql->where("(
                            EXISTS (
                                SELECT 1
                                FROM det_ccpp_c d
                                INNER JOIN det_ant_cccc dacc ON dacc.Dcc_Cod = d.Dcc_Cod
                                WHERE dacc.Ant_Cod = $this->_name.Ant_Cod
                                AND d.Cpc_Fec >= ? AND d.Cpc_Fec <= ?
                            )
                            OR (
                                $this->_name.Ant_Fec >= ? AND $this->_name.Ant_Fec <= ?
                            )
                        )", array(
                            $Par_Sql['txt_fec_ini'], // EXISTS -> Cpc_Fec
                            $Par_Sql['txt_fec_fin'],
                            $Par_Sql['txt_fec_ini'], // OR -> Ant_Fec
                            $Par_Sql['txt_fec_fin'],
                        ));
                    }
                }
                break;
            case "getUsuario":
                $sql->join(array('usr' => 'usuarios'), "usr.Usu_Cod = cprbnt.Usu_Cod", array('usuario' => "concat(prsn.Prs_Nom,' ',prsn.Prs_Ape)", 'Usu_Cod'));
                $sql->join(array('prsn' => 'persona'), "prsn.Prs_Cod = usr.Prs_Cod", array('Prs_Nom', 'Prs_Ape'));
                break;
            case "isActiveAndUsed":
                $sql->where("$this->_name.$this->_state='A' OR $this->_name.$this->_state='U' OR $this->_name.$this->_state='C'");
                $sql->order("FIELD(Ant_Est, 'U', 'A', 'C'), Ant_Fec asc");
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "isInactive":
                $sql->where("$this->_name.$this->_state='I'");
                //echo $this->getSqlString($sql)."<br/>";
                break;
                
            case "filterByEstado":
                // Filtro dinámico por estado del anticipo (igual que anticipos proveedores)
                // Par_Sql['Ant_Est_Filter'] puede ser: 'AUC' (Todos), 'AU' (Por Consumir), 'C' (Consumidos), 'I' (Anulados)
                $estadoFiltro = isset($Par_Sql['Ant_Est_Filter']) ? $Par_Sql['Ant_Est_Filter'] : 'AU';
                switch ($estadoFiltro) {
                    case 'AUC': // Todos - A, U y C
                        $sql->where("$this->_name.$this->_state='A' OR $this->_name.$this->_state='U' OR $this->_name.$this->_state='C'");
                        $sql->order("FIELD(Ant_Est, 'U', 'A', 'C'), Ant_Fec asc");
                        break;
                    case 'AU': // Por Consumir - A y U
                    default:
                        $sql->where("$this->_name.$this->_state='A' OR $this->_name.$this->_state='U'");
                        $sql->order("FIELD(Ant_Est, 'U', 'A'), Ant_Fec asc");
                        break;
                    case 'C': // Consumidos - Solo C
                        $sql->where("$this->_name.$this->_state='C'");
                        $sql->order("Ant_Fec asc");
                        break;
                    case 'I': // Anulados - Solo I
                        $sql->where("$this->_name.$this->_state='I'");
                        $sql->order("Ant_Fec asc");
                        break;
                }
                break;

            // caso para fecha corte - posible uso en el futuro
            case "Corte":
                $sql->where("$this->_name.$this->_state != 'I'");
                $sql->order("FIELD(Ant_Est, 'U', 'A', 'C'), Ant_Fec asc");
                break;
            case "pagos":
                //sum(anticipos_proveedores.Atp_Val) AS Atp_Val,  round((sum(anticipos_proveedores.Atp_Val)-sum(det_ant_ccpp.Dac_Val)),2) AS tot_anti
                $ant = "Ant_Val";
                $sql->addCols(null, array('sumaAntVal' => new Zend_Db_Expr($this->castDecimal("($ant)"))));
                $sql->addCols(null, array('sumaDdcVal' => new Zend_Db_Expr($this->castDecimal("IF(Ddc_Val IS NULL,0,SUM(Ddc_Val))"))));
                $sql->addCols(null, array('tot_anti' => new Zend_Db_Expr($this->castDecimal("($ant)") . "-IF(Ddc_Val IS NULL,0,SUM(Ddc_Val))")));
                $sql->group("$this->_name.Ant_Cod");
                break;
            case "pagos-corte":
                $ant = "Ant_Val";
                // SUMA de anticipos
                $sql->addCols(null, array('sumaAntVal' => new Zend_Db_Expr($this->castDecimal("($ant)"))));
                // SUMA de detalles dentro del rango
                $sql->addCols(null, array(
                    'sumaDdcVal' => new Zend_Db_Expr($this->castDecimal("
                        SUM(IF(dccppc.Cpc_Fec >= '{$Par_Sql['txt_fec_ini']}' AND dccppc.Cpc_Fec <= '{$Par_Sql['txt_fec_fin']}',IFNULL(Ddc_Val, 0),0))
                    "))
                ));
                // TOTAL pendiente = Ant_Val - suma de detalles válidos
                $sql->addCols(null, array(
                    'tot_anti' => new Zend_Db_Expr($this->castDecimal("
                        ($ant) - 
                        SUM(IF(dccppc.Cpc_Fec >= '{$Par_Sql['txt_fec_ini']}' AND dccppc.Cpc_Fec <= '{$Par_Sql['txt_fec_fin']}',IFNULL(Ddc_Val, 0),0))
                    "))
                ));

                $sql->group("$this->_name.Ant_Cod");
                break;
            case "pagoAnticipo":
                $pago = "IF(Pac_Val IS NULL,0,Pac_Val)";
                $selectOther = $this->select(false)->from(array('pac' => 'pag_anticipo_cli'))
                    ->joinLeft(array('tpsPg' => 'tipos_pago'), "tpsPg.Pag_Cod=pac.Pag_Cod", array('Pag_Abr', 'Pag_Des'))
                    ->addCols(null, array($pagos = 'Pagos' => new Zend_Db_Expr($this->castDecimal("IF($pago IS NULL,0,SUM($pago))"))))
                    ->where("pac.Ant_Cod = Ant_Cod")
                    ->group('pac.Ant_Cod');
                $sql->joinLeft(array('pagosAntCli' => $selectOther), "pagosAntCli.Ant_Cod = anticipos_clientes.Ant_Cod", array('Pagos', 'Pac_Cod'));
                break;
            case "pagoAnticipo2":
                $selectOther = $this->select(false)->from(array('pac' => 'pag_anticipo_cli'))
                    ->joinLeft(array('tpsPg' => 'tipos_pago'), "tpsPg.Pag_Cod=pac.Pag_Cod", array('Pag_Abr', 'Pag_Des'))
                    ->joinLeft(array('chq' => 'cheques_ext'), "chq.Che_Cod=pac.Che_Cod", array('Che_Fec', 'Che_Num', 'Bak_Cod', 'Che_Est'))
                    ->where('pac.Asi_Cod = ?', "{$Par_Sql['Asi_Cod']}")
                    ->where("pac.Ant_Cod = Ant_Cod")
                    ->group('pac.Ant_Cod');
                $sql->joinLeft(array('pagosAntCli' => $selectOther), "pagosAntCli.Ant_Cod = anticipos_clientes.Ant_Cod", array('Pac_Est', 'Pac_Cto', 'Pac_Ctd', 'Pag_Cod', 'Pag_Des', 'Che_Cod', 'Pag_Abr', 'Che_Fec', 'Che_Num', 'Bak_Cod', 'Che_Est'));
                break;
            case "searchPagos":
                $sql->join(array('pagosAntClient' => 'pag_anticipo_cli'), "pagosAntClient.Ant_Cod = $this->_name.Ant_Cod", array('*'))
                    //->where('pac.Asi_Cod = ?',"{$Par_Sql['Asi_Cod']}")
                    ->group('pagosAntClient.Pac_Cod');
                break;
            case "byAsiento":
                $sql->join(array('ast' => 'asientos'), "ast.Com_Cod = cprbnt.Com_Cod", array('Asi_Cod', 'Asi_Deh', 'Asi_Con', 'Asi_Glo', 'Pld_Cod', 'Debe' => "IF(ast.Asi_Deh='D',ast.Asi_Val,'')", 'Haber' => "IF(ast.Asi_Deh='H',ast.Asi_Val,'')"));
                $sql->join(array('pld' => 'det_plan'), "pld.Pld_Cod= ast.Pld_Cod", array('*'));
                $sql->order('Asi_Deh asc');
                break;
            default:
                throw new Exception("No existe la sql denominada $id!");
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
                $sql = "";
                //echo $this->getSqlString($sql)."<br/>";
                break;
            default:
                throw new Exception("No existe la sql numero $id!");
        }
        //echo $this->getSqlString($sql)."<br/>";
        return $sql;
    }
}
