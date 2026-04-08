<?php
/**
 * Retorna consulta sql a ejecutarse
 * 
 * @author Erik Niebla
 * @version 1.0
 * Fecha de actualizaci�n:	2015-07-22
 * 
 * @param int $id
 * @param array $Par_Sql
 * @return string $sql
 * 
 * @package tesoreria.LOGICA
 */

function sentencias_emp($id,$Par_Sql)
{
    $sql="";
    switch($id)
    {      
        case 1://Insertar Empresa en exa_master
            $sql="INSERT INTO empresas (Emp_Nom,Emp_Est,Emp_Ruc,Emp_Cor) VALUES('$Par_Sql[nom]','A','$Par_Sql[ruc]','$Par_Sql[abr]');";
            break; 
        case 2://Insertar Empresa y base en exa_master
            $sql="INSERT INTO data (Emp_Cod,Dat_Dis) VALUES ($Par_Sql[ultimo],'$Par_Sql[base]');";
            break; 
        case 3://Insertar Empresa en exa
            $sql="INSERT INTO empresas (Emp_cod,Emp_Ruc,Emp_Nom,Emp_Log,Emp_Reg,Emp_Rep,Emp_Rre,Emp_Con,Emp_Rco,Emp_Cor)
                  VALUES ($Par_Sql[ultimo],'$Par_Sql[ruc]','$Par_Sql[nom]','../../imagenes/$Par_Sql[nom_img]','$Par_Sql[reg]','$Par_Sql[nom_rep]','$Par_Sql[ruc_rep]','$Par_Sql[nom_con]','$Par_Sql[ruc_con]','$Par_Sql[abr]');";
            //echo $sql.'<br>';
            break;
        case 4://busqueda de empresas en master        
            if(isset($Par_Sql["limits"])){
                $Par_Sql["limits"]="ORDER BY Emp_Nom $Par_Sql[limits]";
                $campos=" empresas.Emp_Cod,Emp_Nom,Emp_Cor,Emp_Est,Dat_Cod,Dat_Dis,IF (Emp_Est='A','Activo','Inactivo') as Emp_Est ";
            }else{$campos="COUNT(empresas.Emp_Cod) as total";$Par_Sql["limits"]="";}
            $sql="SELECT $campos FROM empresas
                    INNER JOIN data ON data.Emp_Cod=empresas.Emp_Cod
                    WHERE Emp_Nom LIKE '%$Par_Sql[search]%' $Par_Sql[limits]";
            break;
        case 5://busqueda de sucursales en una base hijo
            if(isset($Par_Sql["limits"])){
                $Par_Sql["limits"]="ORDER BY Suc_Des $Par_Sql[limits]";
                $campos=" * ";
            }else{$campos="COUNT(sucursal.Emp_Cod) as total";$Par_Sql["limits"]="";}
            $sql="SELECT $campos FROM sucursal WHERE sucursal.Emp_Cod=$Par_Sql[Emp_Cod];";
            break;
        case 6://listado de ciudades
            $sql="SELECT * FROM ciudad;";
            break;
        case 7://Insertar sucursal en exa_master
            $sql="INSERT INTO sucursal (Emp_Cod,Suc_Est,Suc_Des) VALUES($Par_Sql[Emp_Cod],'A','$Par_Sql[Suc_Des]');";
            break;
        case 8://Insertar sucursal en base hija
            $sql="INSERT INTO sucursal SET 
                Suc_Cod=$Par_Sql[ultimo],Ciu_Cod=$Par_Sql[Ciu_Cod],Emp_Cod=$Par_Sql[Emp_Cod],Suc_Sri='$Par_Sql[Suc_Sri]',Suc_Des='$Par_Sql[Suc_Des]',Suc_Dir='$Par_Sql[Suc_Dir]',Suc_Te1='$Par_Sql[Suc_Te1]',Suc_Te2='$Par_Sql[Suc_Te2]',Suc_Fax='$Par_Sql[Suc_Fax]',Suc_Cor='$Par_Sql[Suc_Cor]',Suc_Web='$Par_Sql[Suc_Web]',Suc_Est='A';";
            break;
        case 9://Registrar config_fact en hija
            $sql="INSERT INTO confi_fact SET Emp_Cod=$Par_Sql[Emp_Cod],Cof_Con='N',Cof_Gce='N',Cof_Fac='1',Cof_Fte='1';";
            break;
        case 10://ver si ya existe la marca NINGUNA
            $sql="SELECT COUNT(Mar_Cod) as total FROM marca WHERE Mar_Des='NINGUNA' AND Emp_Cod=$Par_Sql[Emp_Cod];";
            break;
        case 11://reistrar marca NINGUNA
            $sql="INSERT INTO marca SET Mar_Des='NINGUNA',Mar_Est='A',Emp_Cod=$Par_Sql[Emp_Cod];";
            break;
        case 12://ver si ya existe la config_fact
            $sql="SELECT COUNT(Cof_Cod) as total FROM confi_fact WHERE Emp_Cod=$Par_Sql[Emp_Cod];";
            break;
        case 13:// grabar tipo_preci
            $sql="INSERT INTO tipo_preci SET Suc_Cod=$Par_Sql[ultimo],Tpv_Des='Standar',Tpv_Est='A',Tpv_Def='D';";
            break;
        case 14://ver si ya existe  NINGUNA en ubicacion
            $sql="SELECT COUNT(Ubi_Cod) as total FROM ubicacion WHERE Ubi_Des='NINGUNA' AND Emp_Cod=$Par_Sql[Emp_Cod];";
            break;
        case 15:// grabar ubicacion ninguna
            $sql="INSERT INTO ubicacion SET Ubi_Des='NINGUNA',Ubi_Est='A',Ubi_Rec=0,Emp_Cod=$Par_Sql[Emp_Cod];";
            break;
        case 16://ver si ya existe plan de cuentas
            $sql="SELECT COUNT(Pla_Cod) as total FROM plan_cuenta WHERE Emp_Cod=$Par_Sql[Emp_Cod];";
            break;
        case 17://registrar plan de cuentas
            $sql="INSERT INTO plan_cuenta SET Emp_Cod=$Par_Sql[Emp_Cod],Pla_Fec='$Par_Sql[fec_plan]';";
            break;
        case 18://registrar periodo 
            $sql="INSERT INTO perio_cont SET Pec_Fei='$Par_Sql[ini_per]',Pec_Fef='$Par_Sql[fin_per]',Pec_Est='A',Pla_Cod=$Par_Sql[plan];";
            break;
        case 19://ver si ya existe perfiles
            $sql="SELECT COUNT(Per_Cod) as total FROM perfiles WHERE Per_Des='Gerente' AND Emp_Cod=$Par_Sql[Emp_Cod];";
            break;
        case 20://insertar perfil
            $sql="INSERT INTO perfiles SET Per_Des='$Par_Sql[per_descrip]',Per_Est='A',Emp_Cod=$Par_Sql[emp_codigo];";
            break;
        case 21://insertar perfiorgan
            $sql="$Par_Sql[0];";
            break;
        case 22://insertar punto_imp
            $sql="INSERT INTO puntos_imp SET Suc_Cod=$Par_Sql[ultimo],Pun_Des='Caja Principal',Pun_Est='A';";
            break;
        
        
        case 23://insertar access
            $sql="INSERT INTO access SET Suc_Cod=$Par_Sql[ultimo],Dat_Cod=$Par_Sql[Dat_Cod],Acc_Usr='$Par_Sql[Adm_Ced]',Acc_Est='A';";
            //echo $sql;
            break;
        case 24://buscar perfil
            $sql="SELECT * FROM perfiles WHERE Per_Des='$Par_Sql[per_descrip]' AND Per_Est='A' AND Emp_Cod=$Par_Sql[emp_codigo];";
            break;
        case 25://buscar perfil
            $sql="SELECT Prs_Cod FROM persona WHERE Prs_Ced='$Par_Sql[Adm_Ced]' AND Prs_Est='A';";
            break;
        case 26://insertar usuario
            $sql="INSERT INTO usuarios SET Prs_Cod=$Par_Sql[Prs_Cod],Suc_Cod=$Par_Sql[ultimo],Usu_Ced='$Par_Sql[Adm_Ced]',Usu_Pal='957cb28374f70a68fee2a20f6a4f1a4c',Usu_Est='A',Usu_Tip='A',Usu_Cad='N',Usu_Men='T';";
            //echo $sql.'<br>';
	    //ChromePhp::log($sql); 
            break;
        case 27://insertar usuario_perfil
            $sql="INSERT INTO usuarperfi SET Usu_Cod=$Par_Sql[Usu_Cod],Per_Cod=$Par_Sql[per_cod_1];";
            //echo $sql.'<br>';
            break;
		case 28://busca empresa por ruc
            $sql="SELECT Emp_Ruc,Emp_Nom,Emp_Cod FROM empresas WHERE Emp_Ruc='$Par_Sql[ruc]'";
            //echo $sql.'<br>';
            break;
        case 29://insertar usuario_perfil
            $sql="INSERT INTO usuarperfi SET Usu_Cod=$Par_Sql[Usu_Cod],Per_Cod=$Par_Sql[per_cod_2];";
            //echo $sql.'<br>';
            break;
    }
    //echo $sql."<br/>";
    return $sql;    
}



