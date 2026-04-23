<?php
use \Exception;
require_once(dirname(__file__)."/../DATA/libs/AbstractModel.php");
class anticipos_proveedores extends AbstractModel{
    protected $_name = 'anticipos_proveedores';
    protected $_primary = array('Atp_Cod');
    protected $_state = 'Atp_Est';
    //protected $_fields = array(); // se declara las filas que va a tener el arreglo en caso de insert o update //opcional

    /* crea una sql basica global para la tabla
     * Optimizado: columnas explícitas en JOINs (menos transferencia, mejor uso de índices),
     * CONCAT con IFNULL para evitar NULL en nombre, codigoCompra calculado una sola vez.
     * Recomendado en BD: índices en (Prv_Cod), (Com_Cod), (Atp_Est, Atp_Fec) y FKs en tablas relacionadas.
     */
    public function _selectBasic($cond=null){
        // Reemplazo del '*' por columnas explícitas, solo las necesarias de anticipos_proveedores:
        $colsMain = array(
            // anticipos_proveedores fields (campos más relevantes, puedes agregar/quitar según necesidad)
            'Atp_Cod',  'Prv_Cod', 'Com_Cod', 'Atp_Fec', 'Atp_Val', 'Atp_Est',  'Atp_Obs',
            // Incluye otros campos de anticipos_proveedores según los requieras
            'codigoCompra' => "CONCAT(tpAst.Tia_Abr, '-', MONTH(cprbnt.Com_Fec), '-', cprbnt.Com_Num)"
        );
        return $this->select(true, $colsMain)
            ->join( array('prv' => 'proveedore'), "prv.Prv_Cod = $this->_name.Prv_Cod", array('Prv_Cod', 'Emp_Cod', 'Prs_Cod'))
            ->joinLeft( array('prs' => 'persona'),
                "prs.Prs_Cod = prv.Prs_Cod",
                array( 'Prs_Cod', 'Prs_Ced', 'Prs_Dir',
                    'nombre' => "TRIM(CONCAT(IFNULL(prs.Prs_Nom,''), ' ', IFNULL(prs.Prs_Ape,'')))",
                    'cedProv' => 'prs.Prs_Ced' )
            )->join(  array('cprbnt' => 'comprobantes'),
                "cprbnt.Com_Cod = $this->_name.Com_Cod",
                array('Com_Cod', 'Com_Fec', 'Com_Num', 'Com_Con', 'Com_Val', 'Com_Est', 'Tia_Cod', 'Usu_Cod', 'Pec_Cod')
            )->join( array('tpAst' => 'tipo_asien'), "tpAst.Tia_Cod = cprbnt.Tia_Cod", array('Tia_Abr'));
    }






    /* crea una sql standart para jqgrid, condiciones incluidas */
    public function _selectBasicGrid($cond=null){
        $sel=$this->_selectBasic();
        $this->sqlByNombre("setEmpCod", $sel);
        if(isset($cond['op_opciones'])){
            if($cond['op_opciones']=='c'){
                $sel->where("prs.Prs_Ced = ?",$cond["search"]);
                $sel->where("Atp_Fec BETWEEN '$cond[txt_fec_ini] 00:00:00' AND '$cond[txt_fec_fin] 23:59:59'",null);
            }else{
                $sel->where("(UPPER(prs.Prs_Nom) LIKE UPPER(?)) OR UPPER(prs.Prs_Ape) LIKE UPPER(?)","%$cond[search]%");
                $sel->where("Atp_Fec BETWEEN '$cond[txt_fec_ini] 00:00:00' AND '$cond[txt_fec_fin] 23:59:59'",null);
            }
        }
        return $sel;
    }
    /* formatea el array para insert o update */
    public function formatData($data, $type, $allData=null){
        return ($type=='I')?$data:$data;
    }

    /**
     * Conteo rápido para el grid: evita GROUP BY y usa COUNT(DISTINCT Atp_Cod)
     * para no ejecutar la consulta pesada dos veces (count + datos).
     */
    public function setCount($sel){
        if (!is_object($sel)) return parent::setCount($sel);
        $sel->reset('group');
        $sel->unsetCols();
        $sel->addCols(null, array('total' => $this->expr('COUNT(DISTINCT ' . $this->_name . '.Atp_Cod)')));
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
                $sql->where("prv.Emp_Cod=?",$_SESSION['Ses_Emp_Cod']);
                //echo $this->getSqlString($sql)."<br/>";
                break;
            case "isActiveAndUsed":
                // Optimizado: usar IN en lugar de múltiples OR para mejor uso de índices
                $sql->where("$this->_name.$this->_state IN ('A', 'U', 'C')");
                // Optimizado: usar CASE en lugar de FIELD() para mejor rendimiento
                $sql->order("CASE WHEN $this->_name.Atp_Est = 'U' THEN 1 WHEN $this->_name.Atp_Est = 'A' THEN 2 WHEN $this->_name.Atp_Est = 'C' THEN 3 ELSE 4 END, $this->_name.Atp_Fec ASC");
                break;
            case "isInactived":
                $sql->where("$this->_name.$this->_state='I'");
                //echo $this->getSqlString($sql)."<br/>";
                break;
            // case "getDetAntCCPP":
            //     $sql->joinLeft(array('daCcpp'=>'det_ant_ccpp'), "daCcpp.Atp_Cod = $this->_name.Atp_Cod", array('Dac_Cod','Dac_Val'));
            //     // filtro de fecha corte
            //     if (!empty($Par_Sql['Pec_Cod']) && $Par_Sql['Pec_Cod'] === 'Corte') {
            //         if (!empty($Par_Sql['txt_fec_ini']) && !empty($Par_Sql['txt_fec_fin'])) {
            //             // Filtrar por la fecha combinada (Cpc_Fec o Ant_Fec) considerando los detalles
            //             $sql->where("EXISTS (SELECT 1 
            //                 FROM comprobantes c 
            //                 WHERE c.Com_Cod = daCcpp.Com_Cod 
            //                 AND IFNULL(c.Com_Fec, $this->_name.Atp_Fec) >= ?
            //                 AND IFNULL(c.Com_Fec, $this->_name.Atp_Fec) <= ?
            //             )", array($Par_Sql['txt_fec_ini'], $Par_Sql['txt_fec_fin']));
            //         }
            //     }
            //     break;
            // reemplaza al anterior con mejora para el caso de que no haya fecha en el detalle


            case "filterByEstado":
                // NUEVO: Filtro dinámico por estado del anticipo
                // Par_Sql['Atp_Est_Filter'] puede ser: 'AUC' (Todos), 'AU' (Por Consumir), 'C' (Consumidos), 'I' (Anulados)
                $estadoFiltro = isset($Par_Sql['Atp_Est_Filter']) ? $Par_Sql['Atp_Est_Filter'] : 'AU';
                
                switch($estadoFiltro) {
                    case 'AUC': // Todos - A, U y C - Orden: primero U, luego A, luego C
                        $sql->where("$this->_name.$this->_state IN ('A', 'U', 'C')");
                        $sql->order("CASE WHEN $this->_name.Atp_Est = 'U' THEN 1 WHEN $this->_name.Atp_Est = 'A' THEN 2 WHEN $this->_name.Atp_Est = 'C' THEN 3 ELSE 4 END, Atp_Fec ASC");
                        break;
                    case 'AU': // Por Consumir - A y U - Orden: primero U, luego A
                    default:
                        $sql->where("$this->_name.$this->_state IN ('A', 'U')");
                        $sql->order("CASE WHEN $this->_name.Atp_Est = 'U' THEN 1 WHEN $this->_name.Atp_Est = 'A' THEN 2 ELSE 3 END, Atp_Fec ASC");
                        break;
                    case 'C': // Consumidos - Solo C
                        $sql->where("$this->_name.$this->_state = 'C'");
                        $sql->order("Atp_Fec ASC");
                        break;
                    case 'I': // Anulados - Solo I
                        $sql->where("$this->_name.$this->_state = 'I'");
                        $sql->order("Atp_Fec ASC");
                        break;
                }
                break;


            case "getDetAntCCPP":
                $sql->joinLeft(array('daCcpp' => 'det_ant_ccpp'), "daCcpp.Atp_Cod = $this->_name.Atp_Cod", array('Dac_Cod', 'Dac_Val'));
                if (!empty($Par_Sql['Pec_Cod']) && $Par_Sql['Pec_Cod'] === 'Corte') {
                    if (!empty($Par_Sql['txt_fec_ini']) && !empty($Par_Sql['txt_fec_fin'])) {
                        $fecIni = $Par_Sql['txt_fec_ini'];
                        $fecFin = $Par_Sql['txt_fec_fin'];
                        // Optimizado: JOIN con comprobantes solo cuando la fecha está en el rango (evita traer registros innecesarios)
                        $sql->joinLeft(
                            array('comp_fec' => 'comprobantes'),
                            "comp_fec.Com_Cod = daCcpp.Com_Cod AND comp_fec.Com_Fec BETWEEN '$fecIni' AND '$fecFin'",
                            array()
                        );
                        // Optimizado: Lógica simplificada - una sola subconsulta EXISTS eliminada
                        // Si hay detalle con comprobante en rango (comp_fec existe) O si no hay detalle pero el anticipo está en rango
                        $sql->where("(
                            comp_fec.Com_Cod IS NOT NULL OR
                            (daCcpp.Dac_Cod IS NULL AND $this->_name.Atp_Fec BETWEEN '$fecIni' AND '$fecFin')
                        )");
                    }
                }
                break;

            case "getDetAntCCPP1":
                $ant = "Atp_Val";
                $sql->joinLeft(array('daCcpp' => 'det_ant_ccpp'), "daCcpp.Atp_Cod = $this->_name.Atp_Cod", array('Dac_Cod', 'Dac_Val'));
                // Filtro de fecha corte: mismo patrón que getDetAntCCPP (JOIN comprobantes por rango, sin EXISTS en el JOIN)
                if (!empty($Par_Sql['Pec_Cod']) && $Par_Sql['Pec_Cod'] === 'Corte' && !empty($Par_Sql['txt_fec_ini']) && !empty($Par_Sql['txt_fec_fin'])) {
                    $fecIni = $Par_Sql['txt_fec_ini'];
                    $fecFin = $Par_Sql['txt_fec_fin'];
                    $sql->joinLeft(
                        array('comp_fec1' => 'comprobantes'),
                        "comp_fec1.Com_Cod = daCcpp.Com_Cod AND comp_fec1.Com_Fec BETWEEN '$fecIni' AND '$fecFin'",
                        array()
                    );
                    $sql->where("(
                        comp_fec1.Com_Cod IS NOT NULL OR
                        (daCcpp.Dac_Cod IS NULL AND $this->_name.Atp_Fec BETWEEN '$fecIni' AND '$fecFin')
                    )");
                    $sql->addCols(null, array(
                        'sumaAtpVal' => new Zend_Db_Expr($this->castDecimal("($ant)")),
                        'sumaDacVal' => new Zend_Db_Expr($this->castDecimal("IFNULL(SUM(CASE WHEN comp_fec1.Com_Cod IS NOT NULL THEN daCcpp.Dac_Val ELSE 0 END), 0)")),
                        'tot_anti'   => new Zend_Db_Expr($this->castDecimal("($ant) - IFNULL(SUM(CASE WHEN comp_fec1.Com_Cod IS NOT NULL THEN daCcpp.Dac_Val ELSE 0 END), 0)"))
                    ));
                } else {
                    $sql->addCols(null, array(
                        'sumaAtpVal' => new Zend_Db_Expr($this->castDecimal("($ant)")),
                        'sumaDacVal' => new Zend_Db_Expr($this->castDecimal("IF(daCcpp.Dac_Val IS NULL, 0, SUM(daCcpp.Dac_Val))")),
                        'tot_anti'   => new Zend_Db_Expr($this->castDecimal("($ant) - IF(daCcpp.Dac_Val IS NULL, 0, SUM(daCcpp.Dac_Val))"))
                    ));
                }
                $sql->group("$this->_name.Atp_Cod");
                break;

            case "getUsuario":
                // Optimizado: Un solo JOIN con alias y campos específicos, evitando doble JOIN innecesario e invirtiendo el orden para hacer uso directo de las llaves.
                $sql->join(
                    array('usr' => 'usuarios'),
                    "usr.Usu_Cod = cprbnt.Usu_Cod",
                    array('Usu_Cod')
                );
                $sql->join(
                    array('prsn' => 'persona'),
                    "prsn.Prs_Cod = usr.Prs_Cod",
                    array(
                        'Prs_Nom',
                        'Prs_Ape',
                        'usuario' => new Zend_Db_Expr("CONCAT(prsn.Prs_Nom,' ',prsn.Prs_Ape)")
                    )
                );
                break;
            case "getPerfil":
                $sql->join(array('usuperf'=>'usuarperfi'), "usuperf.Usu_Cod=usr.Usu_Cod", array('Usu_Cod', 'Per_Cod'));
                $sql->join(array('prfl'=>'perfiles'), "prfl.Per_Cod=usuperf.Per_Cod", array('Per_Cod','Per_Des','Per_Est','Emp_cod'));
                break;
            case "pagos":
                // Si ya se usó getDetAntCCPP1 (Corte), no duplicar addCols ni GROUP BY
                if (empty($Par_Sql['Pec_Cod']) || $Par_Sql['Pec_Cod'] !== 'Corte') {
                    $ant = "Atp_Val";
                    $sql->addCols(null, array(
                        'sumaAtpVal' => new Zend_Db_Expr($this->castDecimal($ant)),
                        'sumaDacVal' => new Zend_Db_Expr($this->castDecimal("COALESCE(SUM(Dac_Val), 0)")),
                        'tot_anti'   => new Zend_Db_Expr($this->castDecimal("$ant - COALESCE(SUM(Dac_Val), 0)"))
                    ));
                    $sql->group("$this->_name.Atp_Cod");
                }
                break;
            case "tipoPago":
                // Optimizado: Este JOIN ya está incluido en pagoAnticipo cuando se ejecuta antes
                // Solo agregar si pagoAnticipo no se ha ejecutado (para compatibilidad con consultas que no lo usan)
                // Nota: Si pagoAnticipo se ejecuta antes, este filtro es redundante pero no causa error
                $sql->joinLeft(array('tpsPg'=>'tipos_pago'), "tpsPg.Pag_Cod=pagosantprv.Pag_Cod",array('Pag_Cod','Pag_Abr','Pag_Des'));
                break;
            case "pagoAnticipo":
                // Subconsulta mínima (sin JOIN tipos_pago); tipoPago lo agrega después en el exterior
                $selecOther = $this->select(false)->from(array('pap' => 'pago_anticipo_proveedores'), array(
                    'Atp_Cod' => 'pap.Atp_Cod',
                    'Pagos'   => new Zend_Db_Expr($this->castDecimal("COALESCE(SUM(COALESCE(pap.Pap_Val, 0)), 0)")),
                    'Pag_Cod' => new Zend_Db_Expr('MAX(pap.Pag_Cod)')
                ))->group('pap.Atp_Cod');
                $sql->joinLeft(array('pagosantprv' => $selecOther), "pagosantprv.Atp_Cod = anticipos_proveedores.Atp_Cod", array('Pagos', 'Pag_Cod'));
                break;
            case "pagoAnticipo2":
               $selecOther = $this->select(false)->from(array('pap'=>'pago_anticipo_proveedores' ))
                                                  ->joinLeft(array('tpsPg'=>'tipos_pago'), "tpsPg.Pag_Cod=pap.Pag_Cod",array('Pag_Abr','Pag_Des'))
                                                  ->joinLeft(array('chq'=>'cheques'), "chq.Asi_Cod=pap.Asi_Cod",array('Che_Cod','Che_Fec','Che_Num','Ban_Cod','Che_Est'))
                                                  ->where("pap.Atp_Cod = Atp_Cod")
                                                  ->where('pap.Asi_Cod = ?',"{$Par_Sql['Asi_Cod']}")
                                                  ->group('pap.Atp_Cod');
                $sql->joinLeft(array('pagosantprv'=>$selecOther), "pagosantprv.Atp_Cod = anticipos_proveedores.Atp_Cod", array('Pap_Est','Pap_Cto','Pag_Cod','Pag_Des','Che_Cod','Pag_Abr','Che_Fec','Che_Num','Ban_Cod','Che_Est'));
//                    ->joinLeft(array('tpsPg'=>'tipos_pago'), "tpsPg.Pag_Cod=pagosantprv.Pag_Cod",array('Pag_Cod','Pag_Abr','Pag_Des')) ->where('pap.Asi_Cod = ?',"{$Par_Sql['Asi_Cod']}")
                break;

            case "subGrid":
                $sql->where('anticipos_proveedores.Atp_Cod=?',"{$Par_Sql['movAnticipo']}");
                break;

            case "byAsiento":
                    $sql->join(array('ast'=>'asientos'), "ast.Com_Cod = cprbnt.Com_Cod", array('Asi_Cod','Asi_Deh','Asi_Con','Asi_Glo','Pld_Cod', 'Debe'=>"IF(ast.Asi_Deh='D',ast.Asi_Val,'')", 'Haber'=>"IF(ast.Asi_Deh='H',ast.Asi_Val,'')"));
                    $sql->join(array('pld'=>'det_plan'), "pld.Pld_Cod= ast.Pld_Cod", array('*'));
                    $sql->order('Asi_Deh asc');
                break;
            case"byCheques":
                $sql->joinLeft(array('chq'=>'cheques'), "chq.Asi_Cod= ast.Asi_Cod", array('*'));
                $sql->where("chq.Che_Est <> 'P'");
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
           //sentencia para obtener todos los proveedores registrados de la empresa
            case 1:
                if ($Par_Sql['op_opciones'] == "c") {
                    $search = "(persona.Prs_Ced LIKE '%$Par_Sql[searchPrv]%')";
                } else {
                    $search = "(CONCAT(persona.Prs_Nom, ' ',persona.Prs_Ape)) LIKE '%$Par_Sql[searchPrv]%'";
                }
                $campos = empty($Par_Sql['limits']) ? " COUNT(proveedore.Prv_Cod) AS total" : " proveedore.Prv_Cod,	persona.Prs_Cod, persona.Prs_Ced, IF(persona.Prs_Nom=persona.Prs_Ape, persona.Prs_Nom,concat(persona.Prs_Nom, ' ', persona.Prs_Ape)) as nombre, persona.Prs_Dir";
                $ordenar = empty($Par_Sql['limits']) ? "" : "ORDER by nombre";
                $sql = "SELECT $campos
                                FROM persona,proveedore
                                WHERE  $search AND proveedore.Emp_Cod='$_SESSION[Ses_Emp_Cod]'
                                AND proveedore.Prs_Cod = persona.Prs_Cod
                                AND proveedore.Prv_Est = 'A'
                                $ordenar
                                $Par_Sql[limits];";
                return $sql;
            case 2:
                $sql="SELECT anticipos_proveedores.Atp_Cod,Atp_Fec,CONCAT(Tia_Abr,'-',month(comprobantes.Com_Fec),'-',comprobantes.Com_Num)as Com_Num,CAST(Atp_Val-coalesce(SUM(Dac_Val),0) as decimal(10,2))as Atp_Val,
                coalesce(SUM(Dac_Val),0)as cruces, CAST(Atp_Val-coalesce(SUM(Dac_Val),0) as decimal(10,2))as pendiente
                FROM anticipos_proveedores
                    INNER JOIN comprobantes ON (anticipos_proveedores.Com_Cod = comprobantes.Com_Cod)
                    INNER JOIN tipo_asien ON (comprobantes.Tia_Cod = tipo_asien.Tia_Cod)
                    LEFT JOIN det_ant_ccpp ON (anticipos_proveedores.Atp_Cod = det_ant_ccpp.Atp_Cod)
                    LEFT JOIN comprobantes as comp ON (det_ant_ccpp.Com_Cod = comp.Com_Cod and comp.Com_Est='A')
                WHERE anticipos_proveedores.Prv_Cod='$Par_Sql[Prv_Cod]' AND Atp_Est!='I' group by anticipos_proveedores.Atp_Cod having pendiente>0";
                return $sql;
            case 3:
                $sql="SELECT anticipos_proveedores.Atp_Cod,Atp_Fec,if(SUM(if(comp.Com_Cod=$Par_Sql[Com_Cod],Dac_Val,0))>0,'S','N')as chkAnt,
                        CONCAT(Tia_Abr, '-', month(comprobantes.Com_Fec), '-', comprobantes.Com_Num)as Com_Num,
                        cast(Atp_Val-coalesce(SUM(Dac_Val), 0) as decimal(10,2)) + coalesce(SUM(if(comp.Com_Cod=$Par_Sql[Com_Cod],Dac_Val,0)), 0)as Atp_Val,
                        coalesce(SUM(if(comp.Com_Cod=$Par_Sql[Com_Cod],Dac_Val,0)), 0)as cruce,
                        cast(Atp_Val-coalesce(SUM(Dac_Val), 0) as decimal(10, 2))as pendiente	
                    FROM anticipos_proveedores
                        INNER JOIN comprobantes on (anticipos_proveedores.Com_Cod = comprobantes.Com_Cod)
                        INNER JOIN tipo_asien on (comprobantes.Tia_Cod = tipo_asien.Tia_Cod)
                        LEFT JOIN det_ant_ccpp on (anticipos_proveedores.Atp_Cod = det_ant_ccpp.Atp_Cod)
                        LEFT JOIN comprobantes as comp on (det_ant_ccpp.Com_Cod = comp.Com_Cod and comp.Com_Est = 'A')
                    WHERE anticipos_proveedores.Prv_Cod = '$Par_Sql[Prv_Cod]' and Atp_Est != 'I' 
                    GROUP BY anticipos_proveedores.Atp_Cod";                    
                return $sql;
            case 4:
                /** Actualiza el estado del anticipo según el total de abonos */
                $sql="UPDATE anticipos_proveedores a
                LEFT JOIN (
                    SELECT a.Atp_Cod,a.Atp_Val,CAST(COALESCE(SUM(d.Dac_Val), 0) as decimal(10,2)) AS TotalAbonado
                    FROM det_ant_ccpp d 
                        LEFT JOIN anticipos_proveedores a ON d.Atp_Cod  = a.Atp_Cod
                        LEFT JOIN comprobantes on d.Com_Cod = comprobantes.Com_Cod 
                    WHERE a.Atp_Cod = $Par_Sql[Atp_Cod] AND comprobantes.Com_Est = 'A'
                    GROUP BY a.Atp_Cod, a.Atp_Val
                ) t ON a.Atp_Cod = t.Atp_Cod
                SET a.Atp_Est = CASE
                    WHEN COALESCE(t.TotalAbonado, 0) = 0 THEN 'A'
                    WHEN COALESCE(t.TotalAbonado, 0) < a.Atp_Val THEN 'U'
                    ELSE 'C'
                END
                WHERE a.Atp_Cod = $Par_Sql[Atp_Cod]";
                return $sql;
            default: throw new Exception ("No existe la sql numero $id!");
        }
        //echo $this->getSqlString($sql)."<br/>";
        return $sql;
    }
}





