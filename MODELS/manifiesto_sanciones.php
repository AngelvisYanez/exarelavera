<?php
use \Exception;
require_once(dirname(__file__) . "/../DATA/libs/AbstractModel.php");

/**
 * Modelo para sanciones de Vehículos (VE), Choferes (CH) y Plantas (PL).
 * Tabla: manifiesto_sanciones
 * Campos: Msa_Cod (PK), Msa_Tip (VE|CH|PL), Veh_Cod, Cho_Cod, Pla_Cod, Msa_Fei, Msa_Fef, Msa_Obs
 */
class manifiesto_sanciones extends AbstractModel
{
    protected $_name = 'manifiesto_sanciones';
    protected $_primary = array('Msa_Cod');
    protected $_state = 'Msa_Est';

    /**
     * Sobrescribe setCount para consultas UNION: envuelve en subquery antes de contar,
     * evitando que "COUNT(*) AS total" se concatene con el SELECT del UNION.
     */
    public function setCount($sel)
    {
        $sql = $this->getSqlString($sel);
        if (stripos($sql, 'UNION') !== false) {
            $count = array('total' => $this->expr('COUNT(*)'));
            $total = $this->select(false)->from(array('tbl' => $this->expr('(' . rtrim($sql) . ')')), $count);
            $sel->setDataSelect($total->getDataSelect());
        } else {
            parent::setCount($sel);
        }
    }

    public function _selectBasic($cond = null, $limits = false)
    {
        return $this->select(true, array('*'));
    }

    /**
     * Según Msa_Tip en where devuelve consulta con joins para grid:
     * VE: Veh_Cod, Veh_Pla, Msa_Fei, Msa_Fef, Msa_Tip, Msa_Obs
     * CH: Cho_Cod, Prs_Ced, Prs_Nom, Msa_Fei, Msa_Fef, Msa_Tip, Msa_Obs
     * PL: Pla_Cod, Prs_Ced (cliente), Prs_Nom (cliente), Msa_Fei, Msa_Fef, Msa_Tip, Msa_Obs
     * Si no hay Msa_Tip (unified) devuelve UNION de los tres tipos con columna Identificador
     */
    public function _selectBasicGrid($cond = null, $limits = false)
    {
        $tipo = null;
        $unified = false;
        if (is_array($cond)) {
            if (isset($cond['where']['manifiesto_sanciones.Msa_Tip'])) {
                $tipo = $cond['where']['manifiesto_sanciones.Msa_Tip'];
            } elseif (isset($cond['where']['Msa_Tip'])) {
                $tipo = $cond['where']['Msa_Tip'];
            }
            if (isset($cond['unified']) && $cond['unified']) {
                $unified = true;
            }
        }

        $nm = $this->_name;

        if ($unified) {
            return $this->_selectBasicGridUnified($cond);
        }
        if ($tipo === 'VE') {
            return $this->select(true, array(
                'Msa_Cod', 'Veh_Cod', 'Msa_Fei', 'Msa_Fef', 'Msa_Tip', 'Msa_Obs'
            ))
                ->join('vehiculo', "vehiculo.Veh_Cod = $nm.Veh_Cod", array('Veh_Pla'));
        }
        if ($tipo === 'CH') {
            $sel = $this->select(true, array(
                'Msa_Cod', 'Cho_Cod', 'Msa_Fei', 'Msa_Fef', 'Msa_Tip', 'Msa_Obs'
            ))
                ->join('chofer', "chofer.Cho_Cod = $nm.Cho_Cod", array())
                ->join('persona', "persona.Prs_Cod = chofer.Prs_Cod", array('Prs_Ced'));
            $sel->addCols(null, array('Prs_Nom' => $this->expr("CONCAT(persona.Prs_Nom,' ',IFNULL(persona.Prs_Ape,''))")));
            return $sel;
        }
        if ($tipo === 'PL') {
            $sel = $this->select(true, array(
                'Msa_Cod', 'Pla_Cod', 'Msa_Fei', 'Msa_Fef', 'Msa_Tip', 'Msa_Obs'
            ))
                ->join('manifiesto_plantas', "manifiesto_plantas.Pla_Cod = $nm.Pla_Cod", array())
                ->join('cliente', "cliente.Cli_Cod = manifiesto_plantas.Cli_Cod", array())
                ->join('persona', "persona.Prs_Cod = cliente.Prs_Cod", array('Prs_Ced'));
            $sel->addCols(null, array('Prs_Nom' => $this->expr("CONCAT(persona.Prs_Nom,' ',IFNULL(persona.Prs_Ape,''))")));
            return $sel;
        }
        
        return $this->_selectBasic();
    }

    /**
     * Consulta unificada de sanciones (VE, CH, PL) para un solo grid.
     * Usa LEFT JOIN + COALESCE en lugar de UNION para compatibilidad con el framework.
     * Filtros: filtro_tipo (VE|CH|PL), filtro_vigentes (solo donde fecha actual entre Msa_Fei y Msa_Fef)
     * Devuelve: Msa_Cod, Msa_Tip, Veh_Cod, Cho_Cod, Pla_Cod, Identificador, Prs_Ced, Prs_Nom, Msa_Fei, Msa_Fef, Msa_Obs
     */
    protected function _selectBasicGridUnified($cond = null)
    {
        $nm = $this->_name;
        $identificador = $this->expr("coalesce( vehiculo.Veh_Pla, manifiesto_plantas.Pla_Nom,CONCAT(IFNULL(persona_ch.Prs_Nom, ''),' ', IFNULL(persona_ch.Prs_Ape, '')))");
        $prsCed = $this->expr("COALESCE(persona_ch.Prs_Ced, persona_pl.Prs_Ced,vehiculo.Veh_Pla)");
        $prsNom = $this->expr("COALESCE(
            CONCAT(IFNULL(persona_ch.Prs_Nom,''),' ',IFNULL(persona_ch.Prs_Ape,'')),
            CONCAT(IFNULL(persona_pl.Prs_Nom,''),' ',IFNULL(persona_pl.Prs_Ape,''))
        )");
        $sel = $this->select(true, array(
            "$nm.Msa_Cod", "$nm.Msa_Tip", "$nm.Veh_Cod", "$nm.Cho_Cod", "$nm.Pla_Cod",
            "$nm.Msa_Fei", "$nm.Msa_Fef", "$nm.Msa_Obs"
        ));
        $sel->addCols(null, array(
            'Identificador' => $identificador,
            'Prs_Ced' => $prsCed,
            'Prs_Nom' => $prsNom
        ));
        $sel->joinLeft('vehiculo', "vehiculo.Veh_Cod = $nm.Veh_Cod AND $nm.Msa_Tip = 'VE'", array());
        $sel->joinLeft('chofer', "chofer.Cho_Cod = $nm.Cho_Cod AND $nm.Msa_Tip = 'CH'", array());
        $sel->joinLeft(array('persona_ch' => 'persona'), "persona_ch.Prs_Cod = chofer.Prs_Cod", array());
        $sel->joinLeft('manifiesto_plantas', "manifiesto_plantas.Pla_Cod = $nm.Pla_Cod AND $nm.Msa_Tip = 'PL'", array());
        $sel->joinLeft('cliente', "cliente.Cli_Cod = manifiesto_plantas.Cli_Cod", array());
        $sel->joinLeft(array('persona_pl' => 'persona'), "persona_pl.Prs_Cod = cliente.Prs_Cod", array());
        $sel->where("$nm.Msa_Est = ?", 'A');

        if (is_array($cond)) {
            $filtroTipo = null;
            if (!empty($cond['filtro_tipo']) && in_array($cond['filtro_tipo'], array('VE', 'CH', 'PL'), true)) {
                $filtroTipo = $cond['filtro_tipo'];
            } elseif (is_array($cond['where'])) {
                if (isset($cond['where']['manifiesto_sanciones.Msa_Tip']) && in_array($cond['where']['manifiesto_sanciones.Msa_Tip'], array('VE', 'CH', 'PL'), true)) {
                    $filtroTipo = $cond['where']['manifiesto_sanciones.Msa_Tip'];
                } elseif (isset($cond['where']['Msa_Tip']) && in_array($cond['where']['Msa_Tip'], array('VE', 'CH', 'PL'), true)) {
                    $filtroTipo = $cond['where']['Msa_Tip'];
                }
            }
            if ($filtroTipo !== null) {
                $sel->where("$nm.Msa_Tip = ?", $filtroTipo);
            }
        }

        if (is_array($cond) && !empty($cond['filtro_vigentes'])) {
            $sel->where("NOW() >= $nm.Msa_Fei AND NOW() <= $nm.Msa_Fef");
        }
        if (is_array($cond) && isset($cond['filtro_identificacion'])) {
            $val = trim((string)$cond['filtro_identificacion']);
            if ($val !== '') {
                $sel->where("(COALESCE(persona_ch.Prs_Ced, persona_pl.Prs_Ced, vehiculo.Veh_Pla) LIKE ?)", '%' . $val . '%');
            }
        }
        if (is_array($cond) && isset($cond['filtro_nombres'])) {
            $val = trim((string)$cond['filtro_nombres']);
            if ($val !== '') {
                $sel->where("(COALESCE(vehiculo.Veh_Pla, manifiesto_plantas.Pla_Nom, CONCAT(IFNULL(persona_ch.Prs_Nom,''),' ',IFNULL(persona_ch.Prs_Ape,''))) LIKE ?)", '%' . $val . '%');
            }
        }
        return $sel;
    }

    public function formatData($data, $type, $allData = null)
    {
        if ($type === 'I') {
            if (isset($data['Msa_Tip'])) {
                if ($data['Msa_Tip'] === 'VE') {
                    $data['Cho_Cod'] = null;
                    $data['Pla_Cod'] = null;
                } elseif ($data['Msa_Tip'] === 'CH') {
                    $data['Veh_Cod'] = null;
                    $data['Pla_Cod'] = null;
                } elseif ($data['Msa_Tip'] === 'PL') {
                    $data['Veh_Cod'] = null;
                    $data['Cho_Cod'] = null;
                }
            }
        }
        return $data;
    }

    public function sqlByNombre($id, $Par_Sql, $cond = null)
    {
        if (is_object($Par_Sql)) {
            $sql = $Par_Sql;
            $Par_Sql = $cond;
        } else {
            $sql = '';
        }
        switch ($id) {
            case 'setEmpCod':
                if (is_object($sql)) {
                    $sql->where("vehiculo.Emp_Cod=?", $_SESSION['Ses_Emp_Cod']);
                }
                break;
            case 'isActive':
                if (is_object($sql)) {
                    $sql->where("$this->_name.$this->_state='A'");
                }
                break;
            case 'isInactive':
                if (is_object($sql)) {
                    $sql->where("$this->_name.$this->_state='I'");
                }
                break;
            default:
                break;
        }
        return $sql;
    }

    public function sqlByNumero($id,$Par_Sql,$cond=null){
        $sql=(is_object($Par_Sql)?$Par_Sql:'');
        switch ($id) {
            case 1:
                $tipo = isset($Par_Sql['tipo']) ? "'AND Msa_Tip = '".$Par_Sql['tipo']."'" : '';
                $sql="SELECT 
                        manifiesto_sanciones.Msa_Cod , manifiesto_sanciones.Msa_Tip,
                        manifiesto_sanciones.Cho_Cod,
                        if(Msa_Tip='CH',prs_cho.Prs_Ced,if(Msa_Tip='PL',prs_pla.Prs_Ced, vehiculo.Veh_Pla))as identi,
                        if(Msa_Tip='CH',concat(prs_cho.Prs_Ape,' ',prs_cho.Prs_Nom),
                        if(Msa_Tip='PL',concat(prs_pla.Prs_Ape,' ',prs_pla.Prs_Nom), CONCAT(if(Veh_Tit='V','VOLQUETA',if(Veh_Tit='B','BUS','CAMIONETA')),' ',vehiculo.Veh_Mar)))as nombre,  
                        manifiesto_sanciones.Msa_Fei,
                        manifiesto_sanciones.Msa_Fef,  
                        manifiesto_sanciones.Msa_Obs 
                        FROM manifiesto_sanciones 
                            LEFT JOIN vehiculo ON (manifiesto_sanciones.Veh_Cod = vehiculo.Veh_Cod)
                            LEFT JOIN chofer ON (manifiesto_sanciones.Cho_Cod = chofer.Cho_Cod)
                            LEFT JOIN persona as prs_cho ON (chofer.Prs_Cod = prs_cho.Prs_Cod)
                            LEFT JOIN manifiesto_plantas ON (manifiesto_sanciones.Pla_Cod = manifiesto_plantas.Pla_Cod)
                            LEFT JOIN cliente ON (manifiesto_plantas.Cli_Cod = cliente.Cli_Cod)
                            LEFT JOIN persona as prs_pla ON (cliente.Prs_Cod = prs_pla.Prs_Cod)
                        WHERE Msa_Fef >= '$Par_Sql[fecha]' and Msa_Est = 'A' $tipo";
                break;            
                case 2:
                    $tipo = isset($Par_Sql['tipo']) ? "'AND Msa_Tip = '".$Par_Sql['tipo']."'" : '';
                    $sql="SELECT 
                            manifiesto_sanciones.Msa_Cod , manifiesto_sanciones.Msa_Tip,
                            manifiesto_sanciones.Cho_Cod,
                            if(Msa_Tip='CH',prs_cho.Prs_Ced,if(Msa_Tip='PL',prs_pla.Prs_Ced, vehiculo.Veh_Pla))as identi,
                            if(Msa_Tip='CH',concat(prs_cho.Prs_Ape,' ',prs_cho.Prs_Nom),
                            if(Msa_Tip='PL',concat(prs_pla.Prs_Ape,' ',prs_pla.Prs_Nom), CONCAT(if(Veh_Tit='V','VOLQUETA',if(Veh_Tit='B','BUS','CAMIONETA')),' ',vehiculo.Veh_Mar)))as nombre,  
                            manifiesto_sanciones.Msa_Fei,
                            manifiesto_sanciones.Msa_Fef,  
                            manifiesto_sanciones.Msa_Obs 
                            FROM manifiesto_sanciones 
                                LEFT JOIN vehiculo ON (manifiesto_sanciones.Veh_Cod = vehiculo.Veh_Cod)
                                LEFT JOIN chofer ON (manifiesto_sanciones.Cho_Cod = chofer.Cho_Cod)
                                LEFT JOIN persona as prs_cho ON (chofer.Prs_Cod = prs_cho.Prs_Cod)
                                LEFT JOIN manifiesto_plantas ON (manifiesto_sanciones.Pla_Cod = manifiesto_plantas.Pla_Cod)
                                LEFT JOIN cliente ON (manifiesto_plantas.Cli_Cod = cliente.Cli_Cod)
                                LEFT JOIN persona as prs_pla ON (cliente.Prs_Cod = prs_pla.Prs_Cod)
                            WHERE Msa_Fef >= '$Par_Sql[fecha]' and Msa_Est = 'A' $tipo";
                    break;            
            default:
                break;
        }
        return $sql;
    }
}
