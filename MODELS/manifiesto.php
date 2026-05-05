<?php
use \Exception;
require_once(dirname(__file__)."/../DATA/libs/AbstractModel.php");
class manifiesto extends AbstractModel{
    protected $_name = 'manifiesto';
    protected $_primary = array('Man_Cod');
    protected $_state = 'Man_Est';
    //protected $_fields = array(); // se declara las filas que va a tener el arreglo en caso de insert o update //opcional

    /* crea una sql basica global para la tabla */
    public function _selectBasic($cond=null){ 
        return $this->select(true,array("manifiesto.*,IF(Man_Tip='P','PENDIENTE',IF(Man_Tip='A','APROBADO',IF(Man_Tip='F','FACTURADO',IF(Man_Tip='GE','GARITA IN',IF(Man_Tip='GS','GARITA OUT','RECHAZADO')))))as estado,DATE_FORMAT(Man_Fec, '%H:%i') AS Man_Hor,
                if(LOCATE('GE', Man_Tes) > 0, 'GE', 0) as Man_Tip_1,
                if(LOCATE('A', Man_Tes) > 0, 'A', 0) as Man_Tip_2,
                if(LOCATE('GS', Man_Tes) > 0, 'GS', 0) as Man_Tip_3,
                if(LOCATE('F', Man_Tes) > 0, 'F', 0) as Man_Tip_4,
                if(LOCATE('R', Man_Tes) > 0, 'R', 0) as Man_Tip_5,             
                DATE(Man_Fec) AS Man_Fec,DATE_FORMAT(Man_Fes, '%H:%i') AS Man_Fes_Hor,CONCAT('M',$this->_name.Pla_Cod,'-',LPAD(Man_Num,6,0)) as ManNum,DATE(Man_Fes) AS Man_Fes,
                DATE_FORMAT(Man_Fea, '%H:%i') AS Man_Fea_Hor,DATE(Man_Fea) AS Man_Fea,cast(Man_Pes*(Man_Pun/1000) as decimal(10,2))as total,
                DATE_FORMAT($this->_name.Man_Sys, '%Y-%m-%d %H:%i:%s') AS Man_Sys_Formatted"))
            ->join('cliente', "cliente.Cli_Cod = $this->_name.Cli_Cod")
            ->join(array('prs_cli'=>'persona'),"prs_cli.Prs_Cod = cliente.Prs_Cod", array('*', 'cliente'=>"concat(prs_cli.Prs_Nom,' ',prs_cli.Prs_Ape)", 'Cli_Ced'=>'prs_cli.Prs_Ced'))            
            ->joinLeft('manifiesto_chofer',"manifiesto_chofer.Cho_Cod = $this->_name.Cho_Cod", array(''))
            ->join('chofer',"chofer.Cho_Cod = $this->_name.Cho_Cod", array(''))
            ->join(array('prs_cho'=>'persona'),"prs_cho.Prs_Cod = chofer.Prs_Cod", array('chofer'=>"concat(prs_cho.Prs_Nom,' ',prs_cho.Prs_Ape)", 'Cho_Cor'=>'prs_cho.Prs_Cor'))
            ->join('vehiculo',"vehiculo.Veh_Cod = $this->_name.Veh_Cod", array('Veh_Pla'))
            ->join('manifiesto_plantas',"manifiesto_plantas.Pla_Cod = $this->_name.Pla_Cod", array('Pla_Nom','Pla_Lic'))
            ->join('manifiesto_desechos', "manifiesto_desechos.Tde_Cod = $this->_name.Tde_Cod")
            ->joinLeft('manifiesto_transporte', "manifiesto_transporte.Mat_Cod = $this->_name.Mat_Cod",array('*'))
            ->joinLeft('manifiesto_celdas', "manifiesto_celdas.Cel_Cod = $this->_name.Cel_Cod",array('*'))
            ->joinLeft('ventas',"ventas.Vet_Cod = $this->_name.Vet_Cod", array('Vet_Num'))
            ->joinLeft(array('usu' => 'usuarios'),"usu.Usu_Cod = $this->_name.Usu_Cod", array(''))
            ->joinLeft(array('prs_usu'=>'persona'), "prs_usu.Prs_Cod = usu.Prs_Cod",array( 'usuario_creador'=>"concat(prs_usu.Prs_Nom,' ',prs_usu.Prs_Ape)"))
            ->joinLeft('manifiesto_tecnico', "manifiesto_tecnico.Man_Cod = $this->_name.Man_Cod AND manifiesto_tecnico.Mat_Est = 'A'", array(''))
            ->joinLeft(array('usu_tec'=>'usuarios'), "usu_tec.Usu_Cod = manifiesto_tecnico.Usu_Cod", array(''))
            ->joinLeft(array('prs_tec'=>'persona'), "prs_tec.Prs_Cod = usu_tec.Prs_Cod", array('tecnico'=>"concat(prs_tec.Prs_Nom,' ',prs_tec.Prs_Ape)"));
    }
    /* crea una sql standart para jqgrid, condiciones incluidas */
    public function _selectBasicGrid($cond=null){
        $sel=$this->_selectBasic(); 
        $this->sqlByNombre("setEmpCod", $sel);
        if(isset($cond['op_opciones'])){
            if($cond['op_opciones']=='c'){
                $sel->where("prs_cli.Prs_Ced = ?",$cond["search"]);
                $sel->where("Man_Fec BETWEEN '$cond[txt_fec_ini] 00:00:00' AND '$cond[txt_fec_fin] 23:59:59'",null);
            }if($cond['op_opciones']=='p'){
                $sel->where("(UPPER(prs_cli.Prs_Nom) LIKE UPPER(?)) OR UPPER(prs_cli.Prs_Ape) LIKE UPPER(?)","%$cond[search]%");
                $sel->where("Man_Fec BETWEEN '$cond[txt_fec_ini] 00:00:00' AND '$cond[txt_fec_fin] 23:59:59'",null);
            }if($cond['op_opciones']=='m'){
                $searchMan = isset($cond['search']) ? trim($cond['search']) : '';
                // Formato "M11-0012": M + Pla_Cod + guión + Man_Num
                if (preg_match('/^M\s*(\d+)\s*-\s*(\d+)\s*$/i', $searchMan, $m)) {
                    $sel->where("$this->_name.Pla_Cod = ?", (int)$m[1]);
                    $sel->where("$this->_name.Man_Num = ?", (int)$m[2]);
                } else {
                    $sel->where("$this->_name.Man_Num = ?", $searchMan);
                }
                if (!empty($cond['txt_fec_ini']) && !empty($cond['txt_fec_fin'])) {
                    $sel->where("Man_Fec BETWEEN '$cond[txt_fec_ini] 00:00:00' AND '$cond[txt_fec_fin] 23:59:59'", null);
                }
            }if($cond['op_opciones']=='pl'){
                $sel->where("UPPER(vehiculo.Veh_Pla) LIKE UPPER(?)","%$cond[search]%");
                $sel->where("Man_Fec BETWEEN '$cond[txt_fec_ini] 00:00:00' AND '$cond[txt_fec_fin] 23:59:59'",null);
            }
        }
        
        // Aplicar ordenamiento si se especifica
        if(isset($cond['ordenar_por']) && !empty($cond['ordenar_por'])){
            switch($cond['ordenar_por']){
                case 'cliente':
                    $sel->order('prs_cli.Prs_Nom ASC, prs_cli.Prs_Ape ASC');
                    break;
                case 'fecha':
                    $sel->order('manifiesto.Man_Fec DESC');
                    break;
                case 'placa':
                    $sel->order('vehiculo.Veh_Pla ASC');
                    break;
                case 'manifiesto':
                    $sel->order('manifiesto.Pla_Cod ASC, manifiesto.Man_Num ASC');
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
                $sql->where("cliente.Emp_Cod=?",$_SESSION['Ses_Emp_Cod']);
                //echo $this->getSqlString($sql)."<br/>";
                break;                       
            case "getUsuario":
                $sql->join(array('usu' => 'usuarios'),"usu.Usu_Cod = $this->_name.Usu_Cod", array(''));
                $sql->join(array('prs_usu'=>'persona'), "prs_usu.Prs_Cod = usu.Prs_Cod",array( 'usuario'=>"concat(prs_usu.Prs_Nom,' ',prs_usu.Prs_Ape)"));
                break;
            case "getDataTecnico":
                $sql->joinLeft('manifiesto_tecnico', "manifiesto_tecnico.Man_Cod = $this->_name.Man_Cod",array('*'));                                
                $sql->joinLeft(array('humedad'=>'manifiesto_nivel_humedad'), "humedad.Hum_Cod = manifiesto_tecnico.Hum_Cod",array('Hum_Des','Hum_Rie'));                
                break;
            case "Man_MaxNum":
                $sql->addCols('COALESCE(MAX(Man_Num), 1) as Man_MaxNum');
                break;
            case "isActive":
                    $sql->where("$this->_name.$this->_state in ('A')");
                    //echo $this->getSqlString($sql)."<br/>";
                break;
            case "isInactive":
                    $sql->where("$this->_name.$this->_state in ('I')");
                    //echo $this->getSqlString($sql)."<br/>";
                break;
            case "getFacturados":
                    $sql->where("($this->_name.Vet_Cod IS NOT NULL AND $this->_name.Vet_Cod != 0)");
                    //echo $this->getSqlString($sql)."<br/>";
                break;
            case "getSinFactura":
                    $sql->where("($this->_name.Vet_Cod IS NULL OR $this->_name.Vet_Cod = 0)");
                    //echo $this->getSqlString($sql)."<br/>";
                break;
            case "getPendiente":
                    // Buscar solo cuando Man_Tes sea exactamente 'P' o NULL/vacío (no cuando contenga 'P' con otros estados)
                    $sql->where("($this->_name.Man_Tes = 'P' OR $this->_name.Man_Tes IS NULL OR $this->_name.Man_Tes = '')");
                    //echo $this->getSqlString($sql)."<br/>";
                break;
            case "getGaritaIn":
                    $sql->where("LOCATE('GE', $this->_name.Man_Tes) > 0");
                    //echo $this->getSqlString($sql)."<br/>";
                break;
            case "getAprobado":
                    $sql->where("LOCATE('A', $this->_name.Man_Tes) > 0");
                    //echo $this->getSqlString($sql)."<br/>";
                break;
            case "getGaritaOut":
                    $sql->where("LOCATE('GS', $this->_name.Man_Tes) > 0");
                    //echo $this->getSqlString($sql)."<br/>";
                break;
            case "getFacturadoManTes":
                    $sql->where("LOCATE('F', $this->_name.Man_Tes) > 0");
                    //echo $this->getSqlString($sql)."<br/>";
                break;
            case "getRechazado":
                    $sql->where("LOCATE('R', $this->_name.Man_Tes) > 0");
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
                $sql="SELECT COALESCE(MAX(Man_Num), 0) as Man_MaxNum                 
                FROM manifiesto 
                INNER JOIN cliente ON cliente.Cli_Cod = manifiesto.Cli_Cod                
                INNER JOIN manifiesto_plantas ON manifiesto_plantas.Pla_Cod = manifiesto.Pla_Cod
                WHERE cliente.Emp_Cod=$Par_Sql[Emp_Cod] AND cliente.Cli_Cod=$Par_Sql[Cli_Cod] AND manifiesto_plantas.Pla_Cod='$Par_Sql[Pla_Cod]'";
                //echo $this->getSqlString($sql)."<br/>";
                break;  
            case 2: /* consulta de manifiestos pendientes (incluye Veh_Pla y Emp_Cod para bloqueo por placa y empresa) */
                $sql="SELECT manifiesto.*, persona.Prs_Ced, vehiculo.Veh_Pla, cliente.Emp_Cod
                FROM manifiesto
                INNER JOIN chofer ON manifiesto.Cho_Cod = chofer.Cho_Cod
                INNER JOIN persona ON persona.Prs_Cod = chofer.Prs_Cod
                INNER JOIN vehiculo ON manifiesto.Veh_Cod = vehiculo.Veh_Cod AND vehiculo.Veh_Est = 'A'
                INNER JOIN cliente ON cliente.Cli_Cod = manifiesto.Cli_Cod
                WHERE Man_Est='A' AND vehiculo.Emp_Cod = $Par_Sql[Emp_Cod] AND Man_Tes IN ('P','GE','A') AND manifiesto.Pla_Cod=$Par_Sql[Pla_Cod]";
                break;
            case 3: /* consulta de manifiestos en ruta segun vehiculo y chofer*/
                $sql="SELECT * FROM manifiesto 
                inner join chofer on manifiesto.Cho_Cod = chofer.Cho_Cod
                inner join vehiculo on manifiesto.Veh_Cod = vehiculo.Veh_Cod AND vehiculo.Veh_Est = 'A'
                WHERE Man_Est='A' AND Man_Tes in ('P','GE','A') AND (manifiesto.Veh_Cod=$Par_Sql[Veh_Cod] OR manifiesto.Cho_Cod=$Par_Sql[Cho_Cod])";
                break;
            case 4: /* manifiestos en ruta por placa y empresa (para validar bloqueo de vehículo) */
                $vpl = isset($Par_Sql['Veh_Pla']) ? "'" . addslashes($Par_Sql['Veh_Pla']) . "'" : "''";
                $emp = isset($Par_Sql['Emp_Cod']) ? $Par_Sql['Emp_Cod'] : '0';
                $sql = "SELECT manifiesto.Man_Cod, manifiesto.Veh_Cod FROM manifiesto
                INNER JOIN vehiculo ON manifiesto.Veh_Cod = vehiculo.Veh_Cod AND vehiculo.Veh_Est = 'A'
                INNER JOIN cliente ON cliente.Cli_Cod = manifiesto.Cli_Cod
                WHERE Man_Est='A' AND Man_Tes IN ('P','GE','A') AND vehiculo.Veh_Pla = $vpl AND cliente.Emp_Cod = $emp";
                break;
            default: throw new Exception ("No existe la sql numero $id!");
        }
        //echo $this->getSqlString($sql)."<br/>";
        return $sql;
    }
}