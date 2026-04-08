<?php
use \Exception;
require_once(dirname(__file__)."/../DATA/libs/AbstractModel.php");
class manifiesto_anticipo extends AbstractModel{
    protected $_name = 'manifiesto_anticipo';
    protected $_primary = array('Ama_Cod');
    protected $_state = 'Ama_Est';
    //protected $_fields = array(); // se declara las filas que va a tener el arreglo en caso de insert o update //opcional

    /* crea una sql basica global para la tabla */
    public function _selectBasic($cond=null,$limits=false){
        return $this->select(true,array('*'))
            ->join('cliente', "cliente.Cli_Cod = $this->_name.Cli_Cod")
            ->join(array('prs_cli'=>'persona'),"prs_cli.Prs_Cod = cliente.Prs_Cod", array('*', 'cliente'=>"concat(prs_cli.Prs_Nom,' ',prs_cli.Prs_Ape)", 'Cli_Ced'=>'prs_cli.Prs_Ced'))            
            ->join('usuario',"usuario.Usu_Cod = $this->_name.Usu_Cod", array(''))
            ->join(array('prs_usu'=>'persona'),"prs_usu.Prs_Cod = usuario.Prs_Cod", array('usuario'=>"concat(prs_usu.Prs_Nom,' ',prs_usu.Prs_Ape)"))
            ->join('banco',"banco.Ban_Cod = $this->_name.Ban_Cod", array(''))
            ->join('det_plan',"det_plan.Pld_Cod = banco.Pld_Cod", array('Pld_Des'))
            ->joinLeft('bancos',"bancos.Bak_Cod = $this->_name.Bak_Cod", array('Bak_Des'));
    }
    /* crea una sql standart para jqgrid, condiciones incluidas */
    public function _selectBasicGrid($cond=null,$limits=false){
        $sel=$this->_selectBasic();        
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
                $sql->where("cliente.Emp_Cod=?",$_SESSION['Ses_Emp_Cod']);
                //ChromePhp::log($this->getSqlString($sql));
                break;                       
            case "isActive":
                $sql->where("$this->_name.$this->_state='A'");
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
            case 1:
                // $sql="select CAST((SUM(Ant_Val)-COALESCE((
                //     select COALESCE(SUM(Ddc_Val),0) from det_ant_cccc where det_ant_cccc.Ant_Cod=anticipos_clientes.Ant_Cod GROUP BY det_ant_cccc.Ant_Cod),0)) as decimal(10,2))as saldo
                //     from manifiesto_anticipo
                //     inner join anticipos_clientes on manifiesto_anticipo.Ama_Cod = anticipos_clientes.Ama_Cod
                //     WHERE manifiesto_anticipo.Pla_Cod = $Par_Sql[Pla_Cod] AND (anticipos_clientes.Ant_Est= 'A' OR  anticipos_clientes.Ant_Est= 'U') GROUP BY /*anticipos_clientes.Ant_Cod,manifiesto_anticipo.Pla_Cod having saldo>0*/ manifiesto_anticipo.Pla_Cod ";
                // $sql="SELECT 
                //             CAST(SUM(saldo) AS DECIMAL(10,2)) AS saldo
                //         FROM (
                //             SELECT
                //                 CAST(
                //                     SUM(Ant_Val) 
                //                     - COALESCE((
                //                         SELECT SUM(Ddc_Val)
                //                         FROM det_ant_cccc 
                //                         WHERE det_ant_cccc.Ant_Cod = anticipos_clientes.Ant_Cod
                //                     ), 0)
                //                 AS DECIMAL(10,2)) AS saldo
                //             FROM manifiesto_anticipo
                //             INNER JOIN anticipos_clientes 
                //                 ON manifiesto_anticipo.Ama_Cod = anticipos_clientes.Ama_Cod
                //             WHERE manifiesto_anticipo.Pla_Cod = $Par_Sql[Pla_Cod]
                //             AND anticipos_clientes.Ant_Est IN ('A','U')
                //             GROUP BY anticipos_clientes.Ant_Cod
                //         ) AS tabla_saldos";
                $pla_cod = (int)$Par_Sql['Pla_Cod'];
                $cli_cod_filter = isset($Par_Sql['Cli_Cod']) ? " AND manifiesto_anticipo.Cli_Cod = " . (int)$Par_Sql['Cli_Cod'] : "";
                
                $sql="SELECT 
                            CAST(SUM(saldo) AS DECIMAL(10,2)) AS saldo
                        FROM (
                            SELECT
                                CAST(
                                    MAX(anticipos_clientes.Ant_Val) 
                                    - COALESCE((
                                        SELECT SUM(det_ant_cccc.Ddc_Val)
                                        FROM det_ant_cccc 
                                        INNER JOIN comprobantes ON comprobantes.Com_Cod = det_ant_cccc.Com_Cod
                                        WHERE det_ant_cccc.Ant_Cod = anticipos_clientes.Ant_Cod
                                        AND comprobantes.Com_Est != 'I'
                                    ), 0)
                                AS DECIMAL(16,2)) AS saldo
                            FROM manifiesto_anticipo
                            INNER JOIN anticipos_clientes 
                                ON manifiesto_anticipo.Ama_Cod = anticipos_clientes.Ama_Cod
                            WHERE manifiesto_anticipo.Pla_Cod = $pla_cod
                            AND manifiesto_anticipo.Ama_Est = 'A'
                            $cli_cod_filter
                            AND anticipos_clientes.Ant_Est IN ('A','U')
                            GROUP BY anticipos_clientes.Ant_Cod
                        ) AS tabla_saldos";
                //echo $this->getSqlString($sql)."<br/>";
                return $sql;
            default: throw new Exception ("No existe la sql numero $id!");
        }
        //echo $this->getSqlString($sql)."<br/>";
        return $sql;
    } 
}