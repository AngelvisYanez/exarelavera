<?Php 
/**
 * Logica de las paginas para el control de kardex
 *
 * @author Lewis Chimarro
 * @version 1.0
 * Fecha de actualización:	2013-01-08

 *
 * @package tesoreria.LOGICA
 */

require_once ('../../auditoria/LOGICA/aud_log_auditoria.php');
require_once("fac_sql_factu_prueba.php");

/**
 * Clase para conexion a la capa de acceso a datos
 * 
 * @author Lewis Chimarro
 *
 * @package tesoreria.LOGICA
*/

class Class_Log_Conexion_Factu extends MysqlConexion{

}//Fin de clase Class_Log_Conexion

/**
 * Clase para acceder a los datos
 * @author Lewis Chimarro
 *
 */

class Class_Log_Datos_Factu extends MysqlDatos{	
    /**
    * Realiza una consulta en la base de datos -  STARDARD
    *
    * @param int $sen_sql numero de la sql
    * @param string $param cadena de valores para el filtrado de la busqueda
    * @param Class_Log_Conexion_Pro $obBD para realizar la conexcion correspondiente
    * @return result si existen datos de retorno
    */
    function consultasobBD($sen_sql,$param, $obBD){
        $Par_Sql= $this->parametros($param);
        return $this->consulta(sentencias_doc($sen_sql,$Par_Sql), $obBD->conexion);
    }

    /**
    * Realiza una consulta en la base de datos -  STARDARD
    *
    * @param int $sen_sql numero de la sql
    * @param string $param cadena de valores para el filtrado de la busqueda
    * @param Class_Log_Conexion_Pro $obBD para realizar la conexcion correspondiente
    * @return result si existen datos de retorno
    */
    function operacionobBD($sen_sql,$param, $obBD){
        $Par_Sql= $this->parametros($param);
        return $this->grabarv_registros(sentencias_doc($sen_sql,$Par_Sql), $obBD->conexion);
    }

    /**
     * Ejecuta cualquier consulta a la base de datos -  STARDARD
     * @param int $sen_sql numero de la sql
     * @param string $param cadena de valores para el filtrado de la busqueda
     * @param Class_Log_Conexion_Cli $obBD para realizar la conexcion correspondiente
     * @return array $row fila de datos
     */
    function getRowConsulta($sen_sql,$param,$obBD){
        $result = $this->consultasobBD($sen_sql,$param,$obBD);
        $row =  $this->fetch_assoc($result);
        $this->free_result($result);
        return $row;
    }
    function getRowConsultaSql($sen_sql,$obBD){
        $result = $this->consulta($sen_sql, $obBD->conexion);
        $row =  $this->fetch_assoc($result);
        $this->free_result($result);
        return $row;
    }
    /**
     * Ejecuta cualquier consulta a la base de datos -  STARDARD
     * @param int $sen_sql numero de la sql
     * @param string $param cadena de valores para el filtrado de la busqueda
     * @param Class_Log_Conexion_pac $obBD para realizar la conexcion correspondiente
     * @param Class_Log_Datos_Cli $obDT para la abtraccion de los datos
     * @return array $array arreglo de datos asociados
     */ 
    function getArrayConsulta($sen_sql,$param,$obBD){
        $result = $this->consultasobBD($sen_sql,$param,$obBD);
        $array = array();
        while($row_rs = $this->fetch_assoc($result)){
                $array[] = $row_rs;
        }
        $this->free_result($result);
        return $array;
    }
    function getArrayConsultaSql($sen_sql,$obBD){
        $result = $this->consulta($sen_sql, $obBD->conexion);
        $array = array();
        while($row_rs = $this->fetch_assoc($result)){
                $array[] = $row_rs;
        }
        $this->free_result($result);
        return $array;
    }
    /**
     * Inserta o actualiza o elimina los datos de una sola transacccion -  STARDARD
     * @param int $sen_sql numero de la sql
     * @param string $param cadena de datos
     * @param Class_Log_Datos_Cli $obBD objeto de conexion
     */
    function insertUpdateDelete($sen_sql,$param, $obBD){		
        $this->inicio_transaccion($obBD->conexion);		
            $this->operacionobBD($sen_sql,$param,$obBD);//Realiza Insert, Update o Delete
        $this->fin_transaccion($obBD->conexion);		
    }

    function codigoSecMensualAuto($Pec_Cod, $mes, $obBD){			
        /*  Codificación numerica en base al periodo contable y mensualmente  */
        $row_rs_numsec= $this->getRowConsultaSql("SELECT IFNULL(MAX(Cop_Sec),0)+1 AS Cop_Sec FROM compras WHERE  Pec_Cod ='$Pec_Cod' AND MONTH(Cop_Fec)='$mes';", $obBD);        
        return $row_rs_numsec['Cop_Sec'];
    }
    function codigoComprAutomatic($Tia_Cod, $Pec_Cod, $mes, $obBD){	
        /*  Codificación numerica en base al periodo contable y mensualmente  */
        $row_rs_numcom= $this->getRowConsultaSql("SELECT IFNULL(MAX(Com_Num),0)+1 AS Com_Num FROM comprobantes WHERE Pec_Cod ='$Pec_Cod' AND MONTH(Com_Fec)='$mes' AND Tia_Cod='$Tia_Cod';", $obBD);        
        return $row_rs_numcom['Com_Num'];
    }
    /**
    * Formato standar para reportes
    * @param int $sucursal Código de la sucursal
    * @param string $titulo Título del reporte
    * @param string $subtitulo Subtitulo del reporte
    */
    function cabeceraReporteStandar($sucursal, $titulo, $subtitulo,$obBD){
        /* Consulta de la cabecera del reporte */
        $row_institucion= $this->getRowConsultaSql("SELECT empresas.Emp_Nom, Emp_Ruc, ciudad.Ciu_Des, sucursal.Ciu_Cod, sucursal.Suc_Dir, sucursal.Suc_Te1, sucursal.Suc_Te2, sucursal.Suc_Fax, sucursal.Suc_Cor, sucursal.Suc_Web, sucursal.Suc_Des, empresas.Emp_Log FROM empresas, sucursal, ciudad WHERE sucursal.Suc_Cod = $sucursal AND empresas.Emp_Cod = sucursal.Emp_Cod AND sucursal.Ciu_Cod = ciudad.Ciu_Cod", $obBD);        
        /* Consulta la provicia y pais de la sucursal */
        $row_provincia= $this->getRowConsultaSql("SELECT provincia.Pro_Nom, pais.Pas_Nom FROM provincia INNER JOIN ciudad ON (provincia.Pro_Cod = ciudad.Pro_Cod) INNER JOIN regiones ON (provincia.Reg_Cod = regiones.Reg_Cod) INNER JOIN pais ON (regiones.Pas_Cod = pais.Pas_Cod) WHERE ciudad.Ciu_Cod = ".$row_institucion['Ciu_Cod'], $obBD);
        ?>
            <table width="100%" border="0" cellpadding="0" cellspacing="0">
                <tr align="center">
                  <td width="5%" rowspan="5" valign="top"><img src="<?php echo $row_institucion['Emp_Log']; ?>" width="83" height="67" /></td>
                  <td width="75%" class="TITULO_REPORTE_2"><?Php echo $row_institucion['Emp_Nom']; ?></td>
                </tr>
                <tr align="center">
                  <td valign="top" class="Texto_Reporte"><div align="center"><strong>R.U.C.:</strong> &nbsp;<?php echo $row_institucion['Emp_Ruc']; ?>&nbsp;		      <strong>TELEFONO:</strong>&nbsp;<?php echo $row_institucion['Suc_Te1']; ?></div></td>
                </tr>
                <tr align="center">
                    <td valign="top" class="Texto_Reporte"><div align="center"><strong>DIRECCION:</strong>&nbsp;<?php echo $row_institucion['Suc_Dir']; ?></div></td>
                </tr>
                <tr align="center">
                    <td valign="top" class="Texto_Reporte"><div align="center"><strong>E-MAIL:</strong> &nbsp;<?php echo $row_institucion['Suc_Cor']; ?></div></td>
                </tr>
                <tr align="center">
                    <td align="center" valign="top" class="Texto_Reporte"><div align="center"><?Php 
                        if (count($row_provincia) > 0){
                                $provincia = " - ".$row_provincia['Pro_Nom'].' - '.$row_provincia['Pas_Nom'];
                        }else{
                                $provincia = "";					
                        }
                        echo $row_institucion['Ciu_Des'].$provincia;?></div>
                    </td>
                </tr>
                <tr align="center">
                    <td colspan="2" valign="top"><hr /></td>
                </tr>
                <tr align="center">
                    <td colspan="2" valign="top" class="TITULO_REPORTE"><? echo $titulo; ?></td>
                </tr>
                <tr align="center">
                    <td colspan="2" valign="top" class="TITULO_REPORTE"><? echo $subtitulo; ?></td>
                </tr>
            </table>
            <?php
    } 
    /**
     * Formato standar para reportes
     * @param int $sucursal Código de la sucursal
     * @param string $usuario Código del usuario 
     */	
    function pieReporteStandar($sucursal, $usuario, $obBD){ 
        /* Consulta de la cabecera del reporte */
        $row_institucion= $this->getRowConsultaSql("SELECT empresas.Emp_Nom, Emp_Ruc, ciudad.Ciu_Des, sucursal.Ciu_Cod, sucursal.Suc_Dir, sucursal.Suc_Te1, sucursal.Suc_Te2, sucursal.Suc_Fax, sucursal.Suc_Cor, sucursal.Suc_Web, sucursal.Suc_Des, empresas.Emp_Log FROM empresas, sucursal, ciudad WHERE sucursal.Suc_Cod = $sucursal AND empresas.Emp_Cod = sucursal.Emp_Cod AND sucursal.Ciu_Cod = ciudad.Ciu_Cod", $obBD);
        /* Consulta los datos del usuario */
        $row_usuario= $this->getRowConsultaSql("SELECT Prs_Ape, Prs_Nom FROM persona, usuarios WHERE persona.Prs_Cod = usuarios.Prs_Cod AND usuarios.Usu_Cod = ".$usuario, $obBD);
        
        $fecha=explode("-",date("Y-m-d"));	
        $fechaHoy =	$row_institucion['Ciu_Des'].", ".$fecha[2]." de ".mes($fecha[1],1)." ".$fecha[0] ;	

            ?>
            <table width="100%" border="0" cellpadding="0" cellspacing="0">
              <tr align="center">
                <td valign="top"><hr /></td>
              </tr>
              <tr align="center">
                <td width="75%" valign="top" class="Texto_Reporte"><div align="center"><strong>FECHA IMPRESI&Oacute;N:</strong> &nbsp;<?php echo $fechaHoy; ?>&nbsp;		      <strong>USUARIO:</strong>&nbsp;<?php echo $row_usuario['Prs_Ape'].' '.$row_usuario['Prs_Nom'] ; ?></div></td>
               </tr>
            </table>
            <?php
    }
    function updateStock($Suc_Cod,$kardex,$insertKardex,$obBD_conexionCon,$obBD_conexionIns=null){        
        if(empty($obBD_conexionIns)) $obBD_conexionIns=$obBD_conexionCon;
        $row_rs_stock= $this->getRowConsultaSql("SELECT * FROM stock WHERE Pro_Cod=$kardex[Pro_Cod] AND Suc_Cod=$Suc_Cod", $obBD_conexionCon);  
        $row_rs_prome= $this->getRowConsultaSql("SELECT Pro_Cod,Pro_Stk,Pro_Prp FROM producto WHERE Pro_Cod=$kardex[Pro_Cod] ", $obBD_conexionCon);
        
        $newStk=array(
            'Can'=>($kardex['IoE']=='I'?$kardex['Kar_Can']:-$kardex['Kar_Sal']),
            'Pru'=>($kardex['IoE']=='I'?$kardex['Kar_Prs']:$kardex['Kar_Pre']),
            'Imp'=>($kardex['IoE']=='I'?$kardex['Kar_Ims']:-$kardex['Kar_Ime'])
        );
        if(empty($row_rs_stock)){
            $this->grabarv_registros("INSERT INTO stock(Pro_Cod,Suc_Cod,Stk_Can) VALUES($kardex[Pro_Cod],$Suc_Cod,$newStk[Can]);", $obBD_conexionIns->conexion);
        }else{
            $this->grabarv_registros("UPDATE stock SET Stk_Can=".($row_rs_stock['Stk_Can']+$newStk['Can'])." WHERE Pro_Cod=$kardex[Pro_Cod] AND Suc_Cod=$Suc_Cod;", $obBD_conexionIns->conexion);
        }
        //var_dump($newStk);
        if(!empty($row_rs_prome['Pro_Stk'])&&!empty($row_rs_prome['Pro_Prp'])){
            $newStk['Can']=$row_rs_prome['Pro_Stk']*1+$newStk['Can']*1;
            $newStk['Imp']=round(($row_rs_prome['Pro_Stk']*$row_rs_prome['Pro_Prp'])*1,2)+$newStk['Imp']*1;
            $newStk['Pru']=($newStk['Can']*1===0?$row_rs_prome['Pro_Prp']*1:round($newStk['Imp']/$newStk['Can'],8));
        }
        //var_dump($newStk);
        $this->grabarv_registros("UPDATE producto SET Pro_Stk=$newStk[Can],Pro_Prp=$newStk[Pru] WHERE Pro_Cod=$kardex[Pro_Cod] ;", $obBD_conexionIns->conexion);
        if($insertKardex){
            $sqlKardex="INSERT INTO kardex_ie (Vet_Cod,Aju_Cod,Vnd_Cod,Cop_Cod,Pro_Cod,Kar_Fec,Kar_Hor,Kar_Can,Kar_Pre,Kar_Ime,Kar_Sal,Kar_Prs,Kar_Ims,Kar_Des,Iva_Cod,Gia_Cod,Kar_Int)
                        VALUES(".(empty($kardex['Vet_Cod'])?0:$kardex['Vet_Cod']).",".(empty($kardex['Aju_Cod'])?0:$kardex['Aju_Cod']).",$kardex[Vnd_Cod],".(empty($kardex['Cop_Cod'])?0:$kardex['Cop_Cod']).",$kardex[Pro_Cod],
                        '$kardex[Kar_Fec]','$kardex[Kar_Hor]',
                        ".(empty($kardex['Kar_Can'])?0:$kardex['Kar_Can']).",".(empty($kardex['Kar_Pre'])?0:$kardex['Kar_Pre']).",".(empty($kardex['Kar_Ime'])?0:$kardex['Kar_Ime']).",
                        ".(empty($kardex['Kar_Sal'])?0:$kardex['Kar_Sal']).",".(empty($kardex['Kar_Prs'])?0:$kardex['Kar_Prs']).",".(empty($kardex['Kar_Ims'])?0:$kardex['Kar_Ims']).",
                        ".(empty($kardex['Kar_Des'])?0:$kardex['Kar_Des']).",$kardex[Iva_Cod],".(empty($kardex['Gia_Cod'])?0:$kardex['Gia_Cod']).",".(empty($kardex['Kar_Int'])?1:$kardex['Kar_Int'])."); ";
            $this->grabarv_registros($sqlKardex, $obBD_conexionIns->conexion);
        }
    }
    function reportes($pagina, $empresa, $obBD_conexion){
        //$pag = explode("/", $pagina);
        $Pcs_Nom='fac_alt_fac_com_%.php'; //str_replace("_mod_", "_alt_", $pag[count($pag)-1]);
        $row_rs_proceso= $this->getRowConsultaSql("SELECT Pcs_Cod FROM procesos WHERE Pcs_Nom LIKE '$Pcs_Nom' ORDER BY Pcs_Nom DESC LIMIT 1;", $obBD_conexion);
        $row_rs_reporte= $this->getArrayConsultaSql("SELECT reportes.Rep_Cod, procesos.Pcs_Nom, reportes.Rep_Ord, rutas.Rut_Des FROM procesos
                                        INNER JOIN reportes ON (procesos.Pcs_Cod = reportes.Rep_Req)
                                        INNER JOIN rutas ON (procesos.Rut_Cod = rutas.Rut_Cod) WHERE reportes.Pcs_Cod = $row_rs_proceso[Pcs_Cod] AND reportes.Emp_Cod = $empresa ORDER BY reportes.Rep_Ord", $obBD_conexion);
        $i=0; $reporte=array();
        foreach ($row_rs_reporte as $row){
            $i++;
            $reporte[$i] = $row['Rut_Des'].$row['Pcs_Nom'];
        } return $reporte;
    }
    function retencionElectronica($Emp_Cod, $Suc_Cod, $Prs_Cod, $data, $obBD_conexion, $Ret_Xml=NULL){ 
        $ban=true;
        try{   
            if(!empty($Ret_Xml) && file_exists($Emp_Cod."/".$Ret_Xml.'.xml'))
                    unlink($Emp_Cod."/".$Ret_Xml.'.xml');
             
            $Tan_Cod=6; $Esq_Rec=0;	// Tabla Esquema
            $meseRet = explode('-', $data['Ret_Fec']);
            $data['Ret_Fec']=$meseRet[2].'/'.$meseRet[1].'/'.$meseRet[0];
            $data['PeriodoFiscal']=$meseRet[1].'/'.$meseRet[0];
            
            $tan_sql="SELECT Esq_Cod,Esq_Rec,Esq_Des,Esq_Xml,Esq_Ord FROM esquema WHERE esquema.Tan_Cod=$Tan_Cod AND esquema.Esq_Rec={Esq_Rec} AND esquema.Esq_Est='A' order by Esq_Ord Asc";
            $rs_esquema = $this->getArrayConsultaSql(str_replace("{Esq_Rec}",$Esq_Rec,$tan_sql), $obBD_conexion); // Consultamos las estiquetas Raiz del XML Factura Electronica
            /* Inicio de bucle de la identificación */	
            foreach($rs_esquema as $row){ // Asignamos las etiquetas consultadas a la variable "$etiqueta[]"
                $Eti_raiz[] = $row['Esq_Xml'];
                $Cod_raiz[] = $row['Esq_Cod'];					
            } unset ($row);	
            $armado_xml = "<".$Eti_raiz[0].">";  //<infoTributaria>
            /* Consultamos informacion de la empresa */
            $rs_infoEmpresa = $this->getRowConsultaSql("SELECT empresas.Emp_Ruc,empresas.Emp_Nom,empresas.Emp_Reg,if(empresas.Emp_Cnt='S','SI','NO')as Emp_Cnt,empresas.Emp_Cor,confi_fact.Cof_Fac,confi_fact.Cof_Gce,sucursal.Ciu_Cod,sucursal.Suc_Sri,sucursal.Suc_Des,sucursal.Suc_Dir,sucursal.Suc_Te1,sucursal.Suc_Dir,confi_fact.Cof_Fte,confi_fact.Cof_Clv
                FROM empresas
                INNER JOIN sucursal ON (empresas.Emp_Cod = sucursal.Emp_Cod)
                INNER JOIN confi_fact ON (empresas.Emp_Cod = confi_fact.Emp_Cod)
                WHERE sucursal.Suc_Cod=$Suc_Cod",$obBD_conexion);
//            /* Consultamos informacion del proveedor */
            $Cliente = $this->getRowConsultaSql("SELECT persona.Prs_Ced,persona.Prs_Ape,persona.Prs_Nom,persona.Prs_Dir,persona.Prs_Tel,persona.Prs_Cor,identifica.Ide_Prv
                                            FROM persona
                                            INNER JOIN identifica ON identifica.Ide_Cod=persona.Ide_Cod
                                            WHERE  persona.Prs_Cod=$Prs_Cod", $obBD_conexion);
            $rs_infoCliente=  array_merge($Cliente,$data);
            /* consultamos las etiquetas Raiz del XML */
            $rs_infoTributaria = $this->getArrayConsultaSql(str_replace("{Esq_Rec}",$Cod_raiz[0],$tan_sql), $obBD_conexion);				
            /* Calculo del Digito verificador de la claveAcceso*/
            	
            $TipoAmbienteCE=$rs_infoEmpresa['Cof_Fac'];
            $TipoEmisionCE=$rs_infoEmpresa['Cof_Fte'];

            /*Control para generar la clave de acceso de tipo  1=Normal  2=Indisponibilidad de Sistema WebService SRI*/
            if($rs_infoEmpresa['Cof_Fte']=='1'){
                $cadena=str_replace("/","",$rs_infoCliente['Ret_Fec'])."07".$rs_infoEmpresa['Emp_Ruc'].$rs_infoEmpresa['Cof_Fac'].$rs_infoEmpresa['Suc_Sri'].$rs_infoCliente['Pun_Sri'].$rs_infoCliente['Ret_Num']."12345678".$rs_infoEmpresa['Cof_Fte'];
            }else{
                /*preguntamos si el txt aun posee numeros para usar*/
                if(count(file($Emp_Cod."/".$rs_infoEmpresa['Cof_Clv']))!=0){	
                    $file = file($Emp_Cod."/".$rs_infoEmpresa['Cof_Clv']);
                    /*clave de acceso de tipo emision INDISPONIBILIDAD DEL SISTEMA*/
                    $cadena=str_replace("/","",$rs_infoCliente['Ret_Fec'])."07".$rs_infoEmpresa['Emp_Ruc'].$rs_infoEmpresa['Cof_Fac'].substr($file[0], 14, 23).$rs_infoEmpresa['Cof_Fte'];
                }else{					
                    /*clave de acceso de tipo emision NORMAL*/
                    $cadena=str_replace("/","",$rs_infoCliente['Ret_Fec'])."07".$rs_infoEmpresa['Emp_Ruc'].$rs_infoEmpresa['Cof_Fac'].$rs_infoEmpresa['Suc_Sri'].$rs_infoCliente['Pun_Sri'].$rs_infoCliente['Ret_Num']."12345678".$rs_infoEmpresa['Cof_Fte'];							    				
                    $TipoAmbienteCE=$rs_infoEmpresa['Cof_Fac'];
                    $TipoEmisionCE="1";												
                }
            }

            $factor = 2; $suma = 0; //echo $cadena;
            for($i = strlen($cadena) - 1; $i >= 0; $i--) {
                $suma += $factor * $cadena[$i];
                $factor = $factor % 7 == 0 ? 2 : $factor + 1;
            }
            $dv = 11 - $suma % 11;				
            $dv = $dv == 11 ? 0 : ($dv == 10 ? "1" : $dv);
            $claveAcceso=$cadena.$dv;
            /*-------------------------*/
            foreach($rs_infoTributaria as $row){
                    $Eti_infoTri[] = $row['Esq_Xml'];
                    $Cod_infoTri[] = $row['Esq_Cod'];					
            } unset ($row);						
            $armado_xml.="<".$Eti_infoTri[0].">".$TipoAmbienteCE."</".$Eti_infoTri[0].">".                 //<ambiente>
                "<".$Eti_infoTri[1].">".$TipoEmisionCE."</".$Eti_infoTri[1].">".                           //<tipoEmision>
                "<".$Eti_infoTri[2].">".$rs_infoEmpresa['Emp_Nom']."</".$Eti_infoTri[2].">".  //<razonSocial>
                "<".$Eti_infoTri[3].">".$rs_infoEmpresa['Emp_Cor']."</".$Eti_infoTri[3].">".  //<nombreComercial>
                "<".$Eti_infoTri[4].">".$rs_infoEmpresa['Emp_Ruc']."</".$Eti_infoTri[4].">".               //<ruc>
                "<".$Eti_infoTri[5].">".$claveAcceso."</".$Eti_infoTri[5].">".                             //<claveAcceso>
                "<".$Eti_infoTri[6].">".'07'."</".$Eti_infoTri[6].">".                                     //<codDoc>
                "<".$Eti_infoTri[7].">".$rs_infoEmpresa['Suc_Sri']."</".$Eti_infoTri[7].">".               //<estab> 
                "<".$Eti_infoTri[8].">".$rs_infoCliente['Pun_Sri']."</".$Eti_infoTri[8].">".               //<ptoEmi>
                "<".$Eti_infoTri[9].">".$rs_infoCliente['Ret_Num']."</".$Eti_infoTri[9].">".      //<secuencial>
                "<".$Eti_infoTri[10].">".$rs_infoEmpresa['Suc_Dir']."</".$Eti_infoTri[10].">";//<dirMatriz>						
            $armado_xml .="</".$Eti_raiz[0].">"; //</infoTributaria>
            $armado_xml .="<".$Eti_raiz[1].">";  //<infoCompRetencion> 				

            /* consultamos los totales de la venta sin impuesto y total del descuento */
            //$rs_infoTotales = $obBD_con1->getRowConsulta(1051, $Cop_Cod, $obBD_conexion);				
            /* consultamos el periodo contable */
            //$rs_perContable = $obBD_con1->getRowConsulta(1053, $Emp_Cod, $obBD_conexion);
            $rs_infoFactura = $this->getArrayConsultaSql(str_replace("{Esq_Rec}",$Cod_raiz[1],$tan_sql), $obBD_conexion);								

            foreach($rs_infoFactura as $row){
                $Eti_infoFac[] = $row['Esq_Xml'];
                $Cod_infoFac[] = $row['Esq_Cod'];					
            } unset ($row);
            $armado_xml.="<".$Eti_infoFac[0].">".$rs_infoCliente['Ret_Fec']."</".$Eti_infoFac[0].">".             //<fechaEmision>
                "<".$Eti_infoFac[1].">".utf8_encode($rs_infoEmpresa['Suc_Dir'])."</".$Eti_infoFac[1].">";       //<dirEstablecimiento>
            if($rs_infoEmpresa['Emp_Reg']!=''){				
                $armado_xml.="<".$Eti_infoFac[2].">".$rs_infoEmpresa['Emp_Reg']."</".$Eti_infoFac[2].">";       //<contribuyenteEspecial>
            }
            $armado_xml.="<".$Eti_infoFac[3].">".$rs_infoEmpresa['Emp_Cnt']."</".$Eti_infoFac[3].">". 	    //<obligadoContabilidad>
                "<".$Eti_infoFac[4].">".$rs_infoCliente['Ide_Prv']."</".$Eti_infoFac[4].">".  	            //<tipoIdentificacionSujetoRetenido>
                "<".$Eti_infoFac[5].">".utf8_encode($rs_infoCliente['Prs_Nom']." ".$rs_infoCliente['Prs_Ape'])."</".$Eti_infoFac[5].">".//<razonSocialSujetoRetenido> 
                "<".$Eti_infoFac[6].">".$rs_infoCliente['Prs_Ced']."</".$Eti_infoFac[6].">".	           //<identificacionSujetoRetenido>
                "<".$Eti_infoFac[7].">".$data['PeriodoFiscal']."</".$Eti_infoFac[7].">";//<periodoFiscal>
            $armado_xml .="</".$Eti_raiz[1].">";  //</infoCompRetencion> 	$rs_perContable['PerCon']
            $armado_xml .="<".$Eti_raiz[2].">"; 	//<impuestos>						

            $rs_infoFactura = $this->getArrayConsultaSql(str_replace("{Esq_Rec}",$Cod_raiz[2],$tan_sql), $obBD_conexion);
            //print_r($rs_infoFactura);
            foreach($rs_infoFactura as $row){
                    $Eti_infoImp[] = $row['Esq_Xml'];
                    $Cod_infoImp[] = $row['Esq_Cod'];					
            }unset ($row);							
            $rs_infoFactura = $this->getArrayConsultaSql(str_replace("{Esq_Rec}",$Cod_infoImp[0],$tan_sql), $obBD_conexion);																

            foreach($rs_infoFactura as $row){
                    $Eti_infoImps[] = $row['Esq_Xml'];
                    $Cod_infoImps[] = $row['Esq_Cod'];					
            }				
            //Consultamos los Datos de la retencion
            $rs_retInfoFact = $this->getArrayConsultaSql("SELECT 
                    retencion.Ret_Cod,if(det_retenc.Ret_Imp = 'R','1','2')as ImpCod,if(det_retenc.Ret_Imp = 'I', if(renta_iva.Ren_Por = '30', '1', if(renta_iva.Ren_Por = '70', '2', '3')), renta_iva.Ren_Sri) AS codigo,((det_retenc.Ret_Bas*renta_iva.Ren_Por)/100) as ValRet,
                    renta_iva.Ren_Por,det_retenc.Ret_Bas,sustento.Tri_Sri,tipo_compr.Tic_Sri,compras.Cop_Num,date_format(compras.Cop_Fec,'%d/%m/%Y') as Cop_Fec
                FROM renta_iva
                    INNER JOIN det_retenc ON (renta_iva.Ren_Cod = det_retenc.Ren_Cod)
                    INNER JOIN retencion ON (det_retenc.Ret_Cod = retencion.Ret_Cod)
                    INNER JOIN compras ON (retencion.Cop_Cod = compras.Cop_Cod)
                    INNER JOIN tipo_compr ON (compras.Tic_Cod = tipo_compr.Tic_Cod)
                    INNER JOIN sustento ON (compras.Tri_Cod = sustento.Tri_Cod)
                WHERE retencion.Ret_Cod = '$data[Ret_Cod]]' AND retencion.Ret_Est = 'A'", $obBD_conexion);
            foreach($rs_retInfoFact as $Ret_Info){
                $armado_xml .="<".$Eti_infoImp[0].">"; 	//<impuesto>																												
                $armado_xml .="<".$Eti_infoImps[0].">".$Ret_Info['ImpCod']."</".$Eti_infoImps[0].">". 	    //<codigo>
                    "<".$Eti_infoImps[1].">".$Ret_Info['codigo']."</".$Eti_infoImps[1].">".                     //<codigoRetencion>
                    "<".$Eti_infoImps[2].">".formato_numero($Ret_Info['Ret_Bas'],2,1)."</".$Eti_infoImps[2].">".//<baseImponible>
                    "<".$Eti_infoImps[3].">".formato_numero($Ret_Info['Ren_Por'],1,1)."</".$Eti_infoImps[3].">".//<porcentajeRetener>
                    "<".$Eti_infoImps[4].">".formato_numero($Ret_Info['ValRet'],2,1)."</".$Eti_infoImps[4].">". //<valorRetenido>
                    "<".$Eti_infoImps[5].">".str_pad($Ret_Info['Tic_Sri'], 2, "0", STR_PAD_LEFT)."</".$Eti_infoImps[5].">". //<codDocSustento>
                    "<".$Eti_infoImps[6].">".str_replace("-","",$Ret_Info['Cop_Num'])."</".$Eti_infoImps[6].">".//<numDocSustento>
                    "<".$Eti_infoImps[7].">".$Ret_Info['Cop_Fec']."</".$Eti_infoImps[7].">";                    //<fechaEmisionDocSustento>
                $armado_xml .="</".$Eti_infoImp[0].">";  //</impuesto>			
            } unset ($row);
            $armado_xml .="</".$Eti_raiz[2].">"; 	//</impuestos>	

            if($rs_infoCliente['Prs_Dir']!="" or $rs_infoCliente['Prs_Tel']!='' or $rs_infoCliente['Prs_Cor']!=''){
                $armado_xml .="<".$Eti_raiz[3].">"; //<infoAdicional>
                $rs_infoAdicional = $this->getArrayConsultaSql(str_replace("{Esq_Rec}",$Cod_raiz[3],$tan_sql), $obBD_conexion);

                foreach($rs_infoAdicional as $row){
                    $Eti_infoAdicional[] = $row['Esq_Xml'];
                    $Cod_infoAdicional[] = $row['Esq_Cod'];					
                }
                if($rs_infoCliente['Prs_Dir']!='')
                { $armado_xml.="<".$Eti_infoAdicional[0]." nombre='Dirección'>".utf8_encode($rs_infoCliente['Prs_Dir'])."</".$Eti_infoAdicional[0].">";	}
                if($rs_infoCliente['Prs_Tel']!='')
                { $armado_xml.="<".$Eti_infoAdicional[0]." nombre='Teléfono'>".$rs_infoCliente['Prs_Tel']."</".$Eti_infoAdicional[0].">"; }
                if($rs_infoCliente['Prs_Cor']!='')
                { $armado_xml.="<".$Eti_infoAdicional[0]." nombre='Email'>".$rs_infoCliente['Prs_Cor']."</".$Eti_infoAdicional[0].">"; }
                $armado_xml .="</".$Eti_raiz[3].">";  //</infoAdicional>
            }
            $armado_xml ='<comprobanteRetencion id="comprobante" version="1.0.0">'.$armado_xml.'</comprobanteRetencion>';
            $buffer = utf8_encode('<?xml version="1.0" encoding="UTF-8"?>'.$armado_xml);
            //var_dump($buffer);
            if (!file_exists($Emp_Cod)) mkdir($Emp_Cod, 0777, true);
            $archivo = $Emp_Cod."/".$claveAcceso.".xml";	
            $xml=new DomDocument("1.0","UTF-8");
            $xml->loadXML($buffer);
            $xml->xmlStandalone=true;
            $xml->formatOut=true;
            $xml->saveXML();
            $xml->save($archivo);
        }catch(Exception $e){ $ban=false; }
        return $ban;
    }
    function getRetClaveAcceso($Emp_Cod, $Suc_Cod, $Aut_Cod, $Ret_Fec, $Ret_Num, $obBD_conexion){ 
        try{
            $rs_infoCliente = $this->getRowConsultaSql("SELECT autorizaci.Aut_Sri,autorizaci.Pun_Sri,sucursal.Suc_Sri, Aut_Fci, Aut_Cad, Aut_Ini, Aut_Fin
                FROM puntos_imp
                   INNER JOIN autorizaci ON (puntos_imp.Pun_Cod = autorizaci.Pun_Cod)
                   INNER JOIN sucursal ON (puntos_imp.Suc_Cod = sucursal.Suc_Cod)
                WHERE autorizaci.Aut_Cod =$Aut_Cod;", $obBD_conexion);				
            $rs_infoEmpresa = $this->getRowConsultaSql("SELECT 
                    empresas.Emp_Ruc,empresas.Emp_Nom,empresas.Emp_Reg,empresas.Emp_Cor,confi_fact.Cof_Fac,confi_fact.Cof_Gce,sucursal.Ciu_Cod,sucursal.Suc_Sri,sucursal.Suc_Des,sucursal.Suc_Dir,sucursal.Suc_Te1,sucursal.Suc_Dir,confi_fact.Cof_Fte,confi_fact.Cof_Clv 
                FROM empresas
                    INNER JOIN sucursal ON (empresas.Emp_Cod = sucursal.Emp_Cod)
                    INNER JOIN confi_fact ON (empresas.Emp_Cod = confi_fact.Emp_Cod)
                WHERE sucursal.Suc_Cod=$Suc_Cod;", $obBD_conexion);

            $ceroDoc="";
            for($i=strlen($Ret_Num); $i<=9-1; $i++){
                $ceroDoc=$ceroDoc."0";
            }				
            $TipoAmbienteCE=$rs_infoEmpresa['Cof_Fac'];
            $TipoEmisionCE=$rs_infoEmpresa['Cof_Fte'];
            /*Control para generar la clave de acceso de tipo  1=Normal  2=Indisponibilidad de Sistema WebService SRI*/
            if($rs_infoEmpresa['Cof_Fte']=='1'){	
                    /*clave de acceso de tipo emision NORMAL*/
                    $cadena=date("dmY",strtotime($Ret_Fec))."07".$rs_infoEmpresa['Emp_Ruc'].$rs_infoEmpresa['Cof_Fac'].$rs_infoEmpresa['Suc_Sri'].$rs_infoCliente['Pun_Sri'].$ceroDoc.$Ret_Num."12345678".$rs_infoEmpresa['Cof_Fte'];
            }else{
                if(count(file($Emp_Cod."/".$rs_infoEmpresa['Cof_Clv']))!=0){ /* preguntamos si el txt aun posee numeros para usar */            
                    $file = file($Emp_Cod."/".$rs_infoEmpresa['Cof_Clv']);
                    /*clave de acceso de tipo emision INDISPONIBILIDAD DEL SISTEMA*/
                    $cadena=date("dmY",strtotime($Ret_Fec))."07".$rs_infoEmpresa['Emp_Ruc'].$rs_infoEmpresa['Cof_Fac'].substr($file[0], 14, 23).$rs_infoEmpresa['Cof_Fte'];
                }else{					
                    /*clave de acceso de tipo emision NORMAL*/
                    $cadena=date("dmY",strtotime($Ret_Fec))."07".$rs_infoEmpresa['Emp_Ruc'].$rs_infoEmpresa['Cof_Fac'].$rs_infoEmpresa['Suc_Sri'].$rs_infoCliente['Pun_Sri'].$ceroDoc.$Ret_Num."12345678".$rs_infoEmpresa['Cof_Fte'];							    				
                    $TipoAmbienteCE=$rs_infoEmpresa['Cof_Fac'];
                    $TipoEmisionCE="1";												
                }
            }

            $factor = 2; $suma = 0;
            for($i = strlen($cadena) - 1; $i >= 0; $i--) {
                $suma += $factor * $cadena[$i];
                $factor = $factor % 7 == 0 ? 2 : $factor + 1;
            }
            $dv = 11 - $suma % 11;				
            $dv = $dv == 11 ? 0 : ($dv == 10 ? "1" : $dv);      
            return $cadena.$dv;
        }catch(Exception $e){ return null; }
    } 
    function createUsuCliente($Emp_Cod, $Suc_Cod, $Prs_Cod, $Prs_Ced, $obBD_conexionCon,$obBD_conexionIns=null){ 
        $ban=true;
        if(empty($obBD_conexionIns)) $obBD_conexionIns=$obBD_conexionCon;
        try{
            /* Consultamos si existe usuario */
            $row_rs_usuario = $this->getRowConsultaSql("SELECT Usu_Cod, Usu_Ced, Suc_Cod FROM usuarios WHERE Suc_Cod = '$Suc_Cod' AND Usu_Ced='$Prs_Ced' AND Usu_Est='A'", $obBD_conexionCon);					
            $total_usuario=$row_rs_usuario['Suc_Cod'] > 0? 1 : 0;
            if($total_usuario==0){            
                /* creamos el usuario en la base local Prs_Cod,Suc_Cod,Usu_Ced,Usu_Pal,Usu_Tip,Usu_Est,Usu_Cad */
                $this->grabarv_registros("INSERT INTO usuarios (Prs_Cod,Suc_Cod,Usu_Ced,Usu_Pal,Usu_Cad,Usu_Tip) VALUES ('$Prs_Cod','$Suc_Cod','$Prs_Ced',md5('$Prs_Ced'),'N','C')",$obBD_conexionIns->conexion);
                $UsuCodPrv = $this->insercionid($obBD_conexionIns->conexion);
                /* Consultamos si existe el perfil "Clientes" */
                $row_rs_perfil = $this->getRowConsultaSql("SELECT Per_Cod,Per_Des FROM perfiles WHERE Per_Des = 'Clientes' AND Emp_Cod = '$Emp_Cod' AND Per_Est='A'", $obBD_conexionCon);					
                $total_rs_perfil=$row_rs_perfil['Per_Cod'] > 0? 1 : 0;
                if($total_rs_perfil!=0){
                    $this->grabarv_registros("INSERT INTO usuarperfi (Usu_Cod,Per_Cod) VALUES ('$UsuCodPrv','$row_rs_perfil[Per_Cod]')",$obBD_conexionIns->conexion); /* asignamos el perfil "Clientes" para el cliente */
                }
            }else
                $UsuCodPrv=$row_rs_usuario['Usu_Cod'];     

            /* Para la base master */
            $obBD_conexion_master = new Class_Log_Conexion_Factu;  
            $obBD_ins1_master = new Class_Log_Datos_Factu;

            /* Busco codigo de la empresa en la tabla data*/
            $row_rs_DatEmp = $this->getRowConsultaSql("SELECT data.Dat_Cod FROM data WHERE data.Emp_Cod = '$Emp_Cod'", $obBD_conexion_master);			
            /* Busco si existe ya el usuario en la master */
            $row_rs_existeUsu = $this->getRowConsultaSql("SELECT Suc_Cod, Acc_Usr FROM access WHERE Suc_Cod = '$Suc_Cod' AND Dat_Cod = '$row_rs_DatEmp[Dat_Cod]' AND Acc_Usr = '$Prs_Ced'", $obBD_conexion_master);			
            $total_existeUsu=$row_rs_existeUsu['Suc_Cod'] > 0? 1 : 0;
            if($total_existeUsu==0){           
                $obBD_ins1_master->inicio_transaccion($obBD_conexion_master->conexion);/* Inicio de la transaccion	*/
                $obBD_ins1_master->grabarv_registros("INSERT INTO access (Suc_Cod, Dat_Cod, Acc_Usr) VALUES ('$Suc_Cod', '$row_rs_DatEmp[Dat_Cod]', '$Prs_Ced')", $obBD_conexion_master->conexion);/* creamos el usuario en la base master */
                $obBD_ins1_master->fin_transaccion_nomsn($obBD_conexion_master->conexion);
                if($obBD_ins1_master->Error!=0) $ban=false;
            }
        }catch(Exception $e){ $ban=false; }
        return $ban;
    }
    function sendMailRet($data,$body){ 
        $ban=true;        
        try{
            require '../../Librerias/PHPMail/class.phpmailer.php';            
            $mail = new PHPMailer(true); // Crear una nueva  instancia de PHPMailer habilitando el tratamiento de excepciones
            // Configuramos el protocolo SMTP con autenticación
            $mail->IsSMTP();
            $mail->SMTPAuth = true;
            $mail->IsHTML(true);
            // Configuración del servidor SMTP
            $mail->Port = 25;
            $mail->Host = 'ofsercont.com';
            $mail->Username = "facturacion.electronica@ofsercont.com";
            $mail->Password = "p.123456";
            // Configuración cabeceras del mensaje
            $mail->From = "facturacion.electronica@ofsercont.com";
            $mail->FromName = $data['{Emp_Nom}'];
            $mail->AddAddress(trim($data['{Prs_Cor}']),strtoupper($data['{proveedor}']));
            //$mail->AddAddress("destino2@correo.com","Nombre 2");
            //$mail->AddCC("copia1@correo.com","Nombre copia 1");
            //$mail->AddBCC("copia1@correo.com","Nombre copia 1");
            $mail->Subject = "Comprobante Electrónico";
            // Creamos en una variable el cuerpo, contenido HMTL, del correo //$body  = "Proebando los correos con un tutorial<br>";
            $mail->Body = $body;
            // Ficheros adjuntos //$mail->AddAttachment("misImagenes/foto1.jpg", "developandoFoto.jpg");
            $mail->Send(); // Enviar el correo
        }catch(Exception $e) { $ban=false; }
        return $ban;
    }
}
