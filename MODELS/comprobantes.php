<?php
use \Exception;
require_once(dirname(__file__)."/../DATA/libs/AbstractModel.php");

class comprobantes extends AbstractModel{
    protected $_name = 'comprobantes';
    protected $_primary = array('Com_Cod');
    protected $_state = 'Com_Est';
    /* protected $_fields = array(
        'Com_Cod'=>null,
        'Pec_Cod'=>null,
        'Tia_Cod'=>null,
        'Cli_Cod'=>null,
        'Prv_Cod'=>null,
        'Usu_Cod'=>null,
        'Com_Num'=>null,
        'Com_Fec'=>null,
        'Com_Con'=>null,
        'Com_Tip'=>null,
        'Com_Val'=>null,
        'Com_Obs'=>null,
        'Com_Gen'=>null
    ); */

    public function _selectBasic($cond=null,$limits=false){
        return $this->select();
    }
    public function _selectBasicGrid($cond=null,$limits=false){
        $sel=$this->_selectBasic();
        return $sel;
    }
    public function formatData($data, $type, $allData=null){
        $data['Usu_Cod']=$_SESSION['Ses_Usu_Cod'];
        if($type=='I'){
            unset($data['Com_Cod']);
        }else{
            unset($data['Com_Gen']);
        }
        return $data;
    }
    public function sqlByNombre($id,$Par_Sql,$cond=null){
        if(is_object($Par_Sql)){ $sql=$Par_Sql; $Par_Sql=$cond; }else $sql='';
        switch($id){
            case "":
                $sql="";
                //echo $sql.'<br/>';
                break;
            case "data":
                $sql->join('tipo_asien', "comprobantes.Tia_Cod = tipo_asien.Tia_Cod", array(
                        'Tia_Des','Tia_Ini','Tia_Ini_Long'=>"IF(Tia_Ini='I','INGRESO',IF(Tia_Ini='E','EGRESO','DIARIO'))",
                        'Com_Codigo'=>"CONCAT(tipo_asien.Tia_Abr, '-', LPAD(MONTH(comprobantes.Com_Fec), 2, '0'), '-', comprobantes.Com_Num)"
                    ))
                    ->joinLeft('cliente',"cliente.Cli_Cod=comprobantes.Cli_Cod", array())
                    ->joinLeft(array('prs_cliente'=>'persona'),"prs_cliente.Prs_Cod=cliente.Prs_Cod", array())
                    ->joinLeft('proveedore',"proveedore.Prv_Cod=comprobantes.Prv_Cod", array())
                    ->joinLeft(array('prs_proveedore'=>'persona'),"prs_proveedore.Prs_Cod=proveedore.Prs_Cod", array())
                    ->joinLeft('usuarios',"usuarios.Usu_Cod=comprobantes.Usu_Cod", array())
                    ->joinLeft(array('prs_usuario'=>'persona'),"prs_usuario.Prs_Cod=usuarios.Prs_Cod", array('Usu_Nom'=>"CONCAT(IFNULL(prs_usuario.Prs_Ape,''),' ',IFNULL(prs_usuario.Prs_Nom,''))"))
                    ->addCols('',array(
                        $this->expr("CONCAT(IFNULL(prs_cliente.Prs_Ape,prs_proveedore.Prs_Ape),' ',IFNULL(prs_cliente.Prs_Nom,prs_proveedore.Prs_Nom)) AS Inv_Nom"),
                        $this->expr("IFNULL(prs_cliente.Prs_Ced,prs_proveedore.Prs_Ced) AS Inv_Ced")
                    ));
                break;
            case "byComprobanteCheque":
                $selectOther = $this->select(false)->from(array('asts'=>'asientos'))
                    ->joinLeft(array('chqs'=>'cheques'),"chqs.Asi_Cod=asts.Asi_Cod")
                    ->where("chqs.Che_Est <> 'P'");
                $sql=join(array('newSelect'=>$selectOther), "newSelect.Com_Cod=$this->_name.Com_Cod");
                //echo $sql.'<br/>';
                break;
            default: throw new Exception ("No existe la sql denominada $id!");
        }
        //echo $sql."<br/>";
        return $sql;
    }
    public function sqlByNumero($id,$Par_Sql,$cond=null){
        $sql="";
        switch($id){
            case 0:
            $sql="INSERT INTO comprobantes SET Pec_Cod=$Par_Sql[Pec_Cod], Prv_Cod=".(empty($Par_Sql['Prv_Cod'])?'NULL':$Par_Sql['Prv_Cod']).", Cli_Cod=".(empty($Par_Sql['Cli_Cod'])?'NULL':$Par_Sql['Cli_Cod']).", Com_Num='$Par_Sql[Com_Num]', Com_Fec='$Par_Sql[Com_Fec]', Com_Con=UPPER('$Par_Sql[Com_Con]'), Tia_Cod='$Par_Sql[Tia_Cod]', Com_Val=$Par_Sql[Com_Val], Com_Obs=UPPER('$Par_Sql[Com_Obs]'), Com_Gen='$Par_Sql[Com_Gen]', Com_Tip='$Par_Sql[Com_Tip]', Usu_Cod=$Par_Sql[Usu_Cod] ";
            break;
            case 1://anticipos proveedores
                $sql="UPDATE comprobantes
                INNER JOIN asientos ON (comprobantes.Com_Cod=asientos.Com_Cod)
                LEFT JOIN cheques ON (asientos.Asi_Cod=cheques.Asi_Cod AND Che_Est<>'P')
              SET
                Com_Est='I', Che_Est='I'
              WHERE comprobantes.Com_Cod=$Par_Sql[Com_Cod];";
                //echo $sql.'<br/>';
                break;
            case 2://anticipos clientes
                $sql="UPDATE comprobantes
                INNER JOIN asientos ON (comprobantes.Com_Cod=asientos.Com_Cod)
                INNER JOIN pag_anticipo_cli ON (asientos.Asi_Cod=pag_anticipo_cli.Asi_Cod)
                LEFT JOIN  cheques_ext ON (pag_anticipo_cli.Che_Cod=cheques_ext.Che_Cod AND Che_Est<>'P')
                SET
                    Com_Est='I', Che_Est='I'
                WHERE comprobantes.Com_Cod=$Par_Sql[Com_Cod];";
                break;
            case 3:
                $sql="INSERT INTO ventas_costo VALUES ($Par_Sql[Vet_Cod], $Par_Sql[Com_Cod]);";
            break;
            case 4:
                $sql="SELECT Tia_Ini,
                SUM(IF(Asi_Deh='D',Asi_Val,0))-SUM(IF(Asi_Deh='H',Asi_Val,0)) AS Diferencia,
                IF(Tia_Ini='D','Diario',IF(Tia_Ini='I','Ingreso','Egreso'))AS Tipo,
                CAST(CONCAT(Tia_Abr,'-',LPAD(MONTH(Com_Fec),2,'0'),'-',Com_Num)AS char)AS Codigo,
                'Ventas' AS Doc,
                Caj_Fec AS Doc_Fec,
                IF(ventas_costo.Com_Cod IS NOT NULL,CONCAT(Suc_Sri,'-',Pun_Sri,'-',CAST(LPAD(Vet_Num,9,'0')AS char)),IF(compr_auto.Cop_Cod IS NOT NULL,Cop_Num,''))AS Doc_Num,
                compras.Cop_Cod,
                ventas.Vet_Cod,
                comprobantes.*,
                Tia_Des, 
                IF(comprobantes.Prv_Cod IS NOT NULL,prs_prv.Prs_Ced,prs_cli.Prs_Ced)AS Prs_Ced, 
                IF(comprobantes.Prv_Cod IS NOT NULL,CONCAT(prs_prv.Prs_Ape,' ',prs_prv.Prs_Nom),CONCAT(prs_cli.Prs_Ape,' ',prs_cli.Prs_Nom))AS Persona
                    FROM comprobantes
                    INNER JOIN asientos ON comprobantes.Com_Cod = asientos.Com_Cod
                    INNER JOIN tipo_asien ON tipo_asien.Tia_Cod=comprobantes.Tia_Cod
                    INNER JOIN perio_cont ON perio_cont.Pec_Cod=comprobantes.Pec_Cod
                    INNER JOIN plan_cuenta ON perio_cont.Pla_Cod=plan_cuenta.Pla_Cod
                    INNER JOIN ventas_costo ON comprobantes.Com_Cod=ventas_costo.Com_Cod
                    LEFT JOIN ventas_compr ON comprobantes.Com_Cod=ventas_compr.Com_Cod
                    LEFT JOIN ventas ON ventas.Vet_Cod=ventas_costo.Vet_Cod
                    LEFT JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod) 
                    LEFT JOIN puntos_imp ON caja_aper.Pun_Cod=puntos_imp.Pun_Cod
                    LEFT JOIN autorizaci ON autorizaci.Aut_Cod=ventas.Aut_Cod 
                    LEFT JOIN sucursal ON sucursal.Suc_Cod=puntos_imp.Suc_Cod
                    LEFT JOIN compr_auto ON comprobantes.Com_Cod=compr_auto.Com_Cod
                    LEFT JOIN compras ON compras.Cop_Cod=compr_auto.Cop_Cod
                    LEFT JOIN tipo_compr AS tip_com_cop ON tip_com_cop.Tic_Cod=compras.Tic_Cod
                    LEFT JOIN tipo_compr AS tip_com_vet ON tip_com_vet.Tic_Cod=ventas.Tic_Cod
                    LEFT JOIN proveedore ON comprobantes.Prv_Cod=proveedore.Prv_Cod   
                    LEFT JOIN persona AS prs_prv ON prs_prv.Prs_Cod=proveedore.Prs_Cod 
                    LEFT JOIN cliente ON comprobantes.Cli_Cod=cliente.Cli_Cod
                    LEFT JOIN persona AS prs_cli ON prs_cli.Prs_Cod=cliente.Prs_Cod 
                    WHERE Com_Est<>'E' 
                    AND plan_cuenta.Emp_Cod=$Par_Sql[Emp_Cod]
                    AND sucursal.Suc_Cod = $Par_Sql[Suc_Cod]
                    AND Com_Fec BETWEEN '$Par_Sql[Fec_Ini] 00:00:00' AND '$Par_Sql[Fec_Fin] 23:59:59'
                    AND tipo_asien.Tia_Abr='CV'
                    AND ventas.Vet_Cod IS NOT NULL
                    GROUP BY comprobantes.Com_Cod
                    ORDER BY Com_Fec";
            break;
            case 5:
                $sql="DELETE FROM comprobantes WHERE Com_Cod = $Par_Sql[0];";
            break;
            case 6:
                $sql="SELECT  det_plan.Pld_Cod, det_plan.Pld_Des,asientos.Asi_Val, Asi_Deh,
                        IF(Asi_Deh = 'D', Asi_Val, '') as Debe,
                        IF(Asi_Deh = 'H', Asi_Val, '') as Haber
                        FROM comprobantes 
                        INNER JOIN asientos ON asientos.Com_Cod = comprobantes.Com_Cod
                        INNER JOIN det_plan ON det_plan.Pld_Cod = asientos.Pld_Cod
                        WHERE comprobantes.Com_Cod = $Par_Sql[0] ORDER BY Asi_Deh";
            break;

            default: throw new Exception ("No existe la sql denominada $id!");
        }
        //echo $sql."<br/>";
        return $sql;
    }
    public function getComprobanteByVetCod($Vet_Cod){
        $sql=$this->select(false)->from('comprobantes',array(
                'Com_Cod','Com_Fec', 'Com_Est',
                'Com_Codigo'=>"CONCAT(tipo_asien.Tia_Abr, '-', LPAD(MONTH(comprobantes.Com_Fec), 2, '0'), '-', comprobantes.Com_Num)",
            ))->join('tipo_asien', "comprobantes.Tia_Cod = tipo_asien.Tia_Cod", array())
              ->join('ventas_compr', "comprobantes.Com_Cod = ventas_compr.Com_Cod", array())
              ->where("Vet_Cod=?",$Vet_Cod);
        return $sql;
    }
    public function getComprobanteByCopCod($Cop_Cod){
        $sql=$this->select(false)->from('comprobantes',array(
                'Com_Cod','Com_Fec',
                'Com_Codigo'=>"CONCAT(tipo_asien.Tia_Abr, '-', LPAD(MONTH(comprobantes.Com_Fec), 2, '0'), '-', comprobantes.Com_Num)",
            ))->join('tipo_asien', "comprobantes.Tia_Cod = tipo_asien.Tia_Cod", array())
              ->join('compr_auto', "comprobantes.Com_Cod = compr_auto.Com_Cod", array())
              ->where("compr_auto.Cop_Cod=?",$Cop_Cod);
        return $sql;
    }
    public function getMayor($Par_Sql){
        $ini=isset($Par_Sql['Year'])?"$Par_Sql[Year]-01-01 00:00:00":(isset($Par_Sql['Fec_Ini'])?"$Par_Sql[Fec_Ini] 00:00:00":'');
        $fin=isset($Par_Sql['Year'])?"$Par_Sql[Year]-12-31 23:59:59":(isset($Par_Sql['Fec_Ini'])?"$Par_Sql[Fec_Ini] 23:59:59":'');
        $sqlAsientos=$this->select(false)->from('comprobantes',array("Com_Cod", "Pec_Cod", "Com_Fec", 'Com_Codigo'=>$this->expr("CONCAT(Tia_Abr,'-',CAST(LPAD(MONTH(comprobantes.Com_Fec),2,'0')AS CHAR),'-',CAST(comprobantes.Com_Num AS CHAR))"), "Com_Con", "Com_Obs"))
            ->join('tipo_asien', "comprobantes.Tia_Cod = tipo_asien.Tia_Cod", array())
            ->join('asientos', "comprobantes.Com_Cod = asientos.Com_Cod", array(/*"Asi_Cod","Asi_Deh",*/"Pld_Cod","Debe"=>$this->expr("IF(Asi_Deh='D',Asi_Val,NULL)"), "Haber"=>$this->expr("IF(Asi_Deh='H',Asi_Val,NULL)")))
            ->where("Com_Est='A' AND Pec_Cod=?",$Par_Sql['Pec_Cod']);
        if(isset($Par_Sql['Pld_Cod'])) $sqlAsientos->where("asientos.Pld_Cod=?",$Par_Sql['Pld_Cod']);
        if(!empty($ini)) $sqlAsientos->where("Com_Fec>=?",$ini);
        if(!empty($fin)) $sqlAsientos->where("Com_Fec<=?",$fin);
        $sql=$this->select(false)->from('det_plan',array("Pla_Cod", "Pld_Cod", "Pld_Tip", "Pld_Cdc", "Pld_Des"))
            ->join(array('tabla'=>$this->expr("(".$this->getSqlString($sqlAsientos).")")),'det_plan.Pld_Cod=tabla.Pld_Cod',array(
                "Debe"=>$this->expr("SUM(Debe)"), "Haber"=>$this->expr("SUM(Haber)"),
                'Acreedor'=>$this->expr("IF(COALESCE(SUM(Debe),0)-COALESCE(SUM(Haber),0)>0,COALESCE(SUM(Debe),0)-COALESCE(SUM(Haber),0),NULL)"),
                'Deudor'=>$this->expr("IF(COALESCE(SUM(Haber),0)-COALESCE(SUM(Debe),0)>0,COALESCE(SUM(Haber),0)-COALESCE(SUM(Debe),0),NULL)")
            ))
            ->join('plan_cuenta','plan_cuenta.Pla_Cod=det_plan.Pla_Cod', array())
            ->join('perio_cont', 'plan_cuenta.Pla_Cod=perio_cont.Pla_Cod', array())
            ->where("perio_cont.Pec_Cod=?",$Par_Sql['Pec_Cod'])
            ->group("Pld_Cod");
        if(isset($Par_Sql['Pld_Cod'])) $sql->where("det_plan.Pld_Cod=?",$Par_Sql['Pld_Cod']);
        return $sql;
    }
}