<?php
require_once ('../../auditoria/LOGICA/aud_log_auditoria.php');
require_once("fac_sql_factura.php");

/******************************************************/
/******************************************************/
/*   Clase para conexion a la capa de acceso a datos  */
/******************************************************/
/******************************************************/

class Class_Log_Conexion_facturaVenta extends MysqlConexion{

}//Fin de clase Class_Log_Conexion

/******************************************************/
/******************************************************/

/******************************************************/
/******************************************************/
/*  Clase para los datos a la capa de acceso a datos  */
/******************************************************/
/******************************************************/

class Class_Log_Datos_facturaVenta extends MysqlDatosContab{
		function __construct(){
			$this->setSentencias('sentencias_facturaVenta');
		}

    /**
     * Codigo de los comprobantes
     * @param int $Tia_Cod Tipo de comprobante
     * @param int $Pec_Cod periodo contable
     * @param int $mes mes
     */
    function codigoComprAuto($Tia_Cod, $Pec_Cod, $mes, $obBD_conexion){
			return $this->getComNumPecAuto($Tia_Cod, $Pec_Cod, $mes, $obBD_conexion);
    }


    /*
    * Funcion que devuelve un arreglo de los reportes del proceso
    */
    function reportes($pagina, $empresa, $obBD_conexion)
    {
        $pag=explode("/",$pagina);
        $Pcs_Nom=str_replace("_mod_", "_alt_", $pag[count($pag)-1]);
        $row_rs_proceso= $this->getRowConsultaSql("SELECT Pcs_Cod FROM procesos WHERE Pcs_Nom LIKE '$Pcs_Nom' ORDER BY Pcs_Nom DESC LIMIT 1;", $obBD_conexion);

        $row_rs_reporte= $this->getArrayConsultaSql("SELECT reportes.Rep_Cod, procesos.Pcs_Nom, reportes.Rep_Ord, rutas.Rut_Des FROM procesos
                                        INNER JOIN reportes ON (procesos.Pcs_Cod = reportes.Rep_Req)
                                        INNER JOIN rutas ON (procesos.Rut_Cod = rutas.Rut_Cod) 
                                        WHERE reportes.Pcs_Cod = $row_rs_proceso[Pcs_Cod] AND reportes.Emp_Cod = $empresa ORDER BY reportes.Rep_Ord", $obBD_conexion);

            $i=0;$reporte=array();
            foreach ($row_rs_reporte as $row)
            {
                $reporte[$row['Rep_Ord']] = $row['Rut_Des'].$row['Pcs_Nom'];
            }
            return $reporte;
    }

    /**
     * graba en la base de datos auditoria
     * @param string $Request_Uri pagina donde se estan modificando valores
     * @param number $Ses_Usu_Cod codigo del usuario
     * @param Class_Log_Conexion $obBD_conexion
     * @return number codigo de error my sql si lo hubiese [0 = 'Sin errores']
     */
    function grabarAuditoria($Ses_Dat_Dis,$Request_Uri, $Ses_Usu_Cod, $obBD_conexion){
        if($this->Error == 0){
            $objAud = new Class_Log_Datos_Aud;

            $aux = explode('*', $objAud->grabarAuditoria($Ses_Dat_Dis,$Request_Uri, $Ses_Usu_Cod, $this, $obBD_conexion));

            foreach ($aux as $row){
                $this->grabarv_registros($row,$obBD_conexion->conexion);
                if($this->Error > 0){
                    return $this->Error;
                }
            }
            $objAud->GuardarCierreSesion($_SESSION['Ses_Ses_Cod'], date('Y-m-d H:i:s'), $Ses_Usu_Cod);
        }else{
            return $this->Error;
        }
    }
    
        function CantidadStock($pro_cod,$items){
            $cantiStock=0;
            foreach ($items as $key => $val) {
                if ($val['Pro_Cod'] === $pro_cod){
                    $cantiStock +=(1)*$val['Vet_Can'];
                }
            }
            return $cantiStock;
        }
    
    
//        
//          
//            
//                ACTUALIZACION EN TABLA STOCK,KARDEX_IE Y TABLA PRODUCTO
//	function updateStock($Suc_Cod,$kardex,$insertKardex,$obBD_conexionCon,$obBD_conexionIns=null){
//	        if(empty($obBD_conexionIns)) $obBD_conexionIns=$obBD_conexionCon;
//	        $row_rs_stock= $this->getRowConsultaSql("SELECT * FROM stock WHERE Pro_Cod=$kardex[Pro_Cod] AND Suc_Cod=$Suc_Cod", $obBD_conexionCon);
//	        $row_rs_prome= $this->getRowConsultaSql("SELECT Pro_Cod,Pro_Stk,Pro_Prp FROM producto WHERE Pro_Cod=$kardex[Pro_Cod] ", $obBD_conexionCon);
//
//	        $newStk=array(
//	            'Can'=>($kardex['IoE']=='I'?$kardex['Kar_Can']:-$kardex['Kar_Sal']),
//	            'Pru'=>($kardex['IoE']=='I'?$kardex['Kar_Prs']:$kardex['Kar_Pre']),
//	            'Imp'=>($kardex['IoE']=='I'?$kardex['Kar_Ims']:-$kardex['Kar_Ime'])
//	        );
//	        if(empty($row_rs_stock)){
//	            $this->grabarv_registros("INSERT INTO stock(Pro_Cod,Suc_Cod,Stk_Can) VALUES($kardex[Pro_Cod],$Suc_Cod,$newStk[Can]);", $obBD_conexionIns->conexion);
//	        }else{
//	            $this->grabarv_registros("UPDATE stock SET Stk_Can=".($row_rs_stock['Stk_Can']+$newStk['Can'])." WHERE Pro_Cod=$kardex[Pro_Cod] AND Suc_Cod=$Suc_Cod;", $obBD_conexionIns->conexion);
//	        }
//	        //var_dump($newStk);
//	        if(!empty($row_rs_prome['Pro_Stk'])&&!empty($row_rs_prome['Pro_Prp'])){
//	            $newStk['Can']=$row_rs_prome['Pro_Stk']*1+$newStk['Can']*1;
//	            $newStk['Imp']=round(($row_rs_prome['Pro_Stk']*$row_rs_prome['Pro_Prp'])*1,2)+$newStk['Imp']*1;
//	            $newStk['Pru']=($newStk['Can']*1===0?($row_rs_prome['Pro_Prp']*1):round($newStk['Imp']/$newStk['Can'],8));
//	        }
//	        //var_dump($newStk);
//	        $this->grabarv_registros("UPDATE producto SET Pro_Stk=$newStk[Can],Pro_Prp=$newStk[Pru] WHERE Pro_Cod=$kardex[Pro_Cod] ;", $obBD_conexionIns->conexion);
//	        if($insertKardex){
//	            $sqlKardex="INSERT INTO kardex_ie (Vet_Cod,Aju_Cod,Vnd_Cod,Cop_Cod,Pro_Cod,Kar_Fec,Kar_Hor,Kar_Can,Kar_Pre,Kar_Ime,Kar_Sal,Kar_Prs,Kar_Ims,Kar_Des,Iva_Cod,Gia_Cod,Kar_Int)
//	                        VALUES(".(empty($kardex['Vet_Cod'])?0:$kardex['Vet_Cod']).",".
//                                (empty($kardex['Aju_Cod'])?0:$kardex['Aju_Cod']).",$kardex[Vnd_Cod],".
//                                (empty($kardex['Cop_Cod'])?0:$kardex['Cop_Cod']).",$kardex[Pro_Cod],'$kardex[Kar_Fec]','$kardex[Kar_Hor]',
//	                        ".(empty($kardex['Kar_Can'])?0:$kardex['Kar_Can']).",".(empty($kardex['Kar_Pre'])?0:$kardex['Kar_Pre']).",".(empty($kardex['Kar_Ime'])?0:$kardex['Kar_Ime']).",
//	                        ".(empty($kardex['Kar_Sal'])?0:$kardex['Kar_Sal']).",".(empty($kardex['Kar_Prs'])?0:$kardex['Kar_Prs']).",".(empty($kardex['Kar_Ims'])?0:$kardex['Kar_Ims']).",
//	                        ".(empty($kardex['Kar_Des'])?0:$kardex['Kar_Des']).",$kardex[Iva_Cod],".(empty($kardex['Gia_Cod'])?0:$kardex['Gia_Cod']).",".(empty($kardex['Kar_Int'])?1:$kardex['Kar_Int'])."); ";
//	            $this->grabarv_registros($sqlKardex, $obBD_conexionIns->conexion);
//	        }
//	    }
    
    function sendMailDoc($data,$body){ 
        $ban=true;        
        if(empty($data['{Prs_Cor}'])||empty($body)||strlen($data['{Prs_Cor}'])<4) return false;
        try{
            require '../../Librerias/PHPMail/class.phpmailer.php';            
            $mail = new PHPMailer(true); // Crear una nueva  instancia de PHPMailer habilitando el tratamiento de excepciones
            // Configuramos el protocolo SMTP con autenticaci�n
            $mail->IsSMTP();
            $mail->SMTPAuth = true;
            $mail->IsHTML(true);
            // Configuraci�n del servidor SMTP
            $mail->Port = 25;
            $mail->Host = 'ofsercont.com';
            $mail->Username = "facturacion.electronica@ofsercont.com";
            $mail->Password = "p.123456";
            // Configuraci�n cabeceras del mensaje
            $mail->From = "facturacion.electronica@ofsercont.com";
            $mail->FromName = $data['{Emp_Nom}'];
            $mail->AddAddress(trim($data['{Prs_Cor}']),strtoupper($data['{proveedor}']));
            //$mail->AddAddress("destino2@correo.com","Nombre 2");
            //$mail->AddCC("copia1@correo.com","Nombre copia 1");
            //$mail->AddBCC("copia1@correo.com","Nombre copia 1");
            $mail->Subject = "Comprobante Electr�nico";
            // Creamos en una variable el cuerpo, contenido HMTL, del correo //$body  = "Proebando los correos con un tutorial<br>";
            $mail->Body = $body;
            // Ficheros adjuntos //$mail->AddAttachment("misImagenes/foto1.jpg", "developandoFoto.jpg");
            $mail->Send(); // Enviar el correo
        }catch(Exception $e) { $ban=false; }
        return $ban;
    }
    function getDocClaveAcceso($Emp_Cod, $Suc_Cod, $Tic_Sri, $Aut_Cod, $Vet_Fec, $Vet_Num, $obBD_conexion){
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
            for($i=strlen($Vet_Num); $i<=9-1; $i++){
                $ceroDoc=$ceroDoc."0";
            }
            $TipoAmbienteCE=$rs_infoEmpresa['Cof_Fac'];
            $TipoEmisionCE=$rs_infoEmpresa['Cof_Fte'];
            /*Control para generar la clave de acceso de tipo  1=Normal  2=Indisponibilidad de Sistema WebService SRI*/
            if($rs_infoEmpresa['Cof_Fte']=='1'){
                    /*clave de acceso de tipo emision NORMAL*/
                    $cadena=date("dmY",strtotime($Vet_Fec)).str_pad($Tic_Sri, 2, "0", STR_PAD_LEFT).$rs_infoEmpresa['Emp_Ruc'].$rs_infoEmpresa['Cof_Fac'].$rs_infoEmpresa['Suc_Sri'].$rs_infoCliente['Pun_Sri'].$ceroDoc.$Vet_Num."12345678".$rs_infoEmpresa['Cof_Fte'];
            }else{
                if(count(file($Emp_Cod."/".$rs_infoEmpresa['Cof_Clv']))!=0){ /* preguntamos si el txt aun posee numeros para usar */
                    $file = file($Emp_Cod."/".$rs_infoEmpresa['Cof_Clv']);
                    /*clave de acceso de tipo emision INDISPONIBILIDAD DEL SISTEMA*/
                    $cadena=date("dmY",strtotime($Vet_Fec)).str_pad($Tic_Sri, 2, "0", STR_PAD_LEFT).$rs_infoEmpresa['Emp_Ruc'].$rs_infoEmpresa['Cof_Fac'].substr($file[0], 14, 23).$rs_infoEmpresa['Cof_Fte'];
                }else{
                    /*clave de acceso de tipo emision NORMAL*/
                    $cadena=date("dmY",strtotime($Vet_Fec)).str_pad($Tic_Sri, 2, "0", STR_PAD_LEFT).$rs_infoEmpresa['Emp_Ruc'].$rs_infoEmpresa['Cof_Fac'].$rs_infoEmpresa['Suc_Sri'].$rs_infoCliente['Pun_Sri'].$ceroDoc.$Vet_Num."12345678".$rs_infoEmpresa['Cof_Fte'];
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
            $obBD_conexion_master = new Class_Log_Conexion_facturaVenta;
            $obBD_ins1_master = new Class_Log_Datos_facturaVenta;

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
    function documentoElectronico($Tan_Cod, $Emp_Cod, $Suc_Cod, $Tic_Sri, $data, $obBD_conexion, $Vet_Xml=NULL){ 
        $ban=true;
        try{   
            if(!empty($Vet_Xml) && file_exists($Emp_Cod."/".$Vet_Xml.'.xml'))
                unlink($Emp_Cod."/".$Vet_Xml.'.xml');             
            $Esq_Rec=0;	// Tabla Esquema
            $meseRet = explode('-', $data['Vet_Fec']);
            $data['Vet_Fec']=$meseRet[2].'/'.$meseRet[1].'/'.$meseRet[0];
            $data['PeriodoFiscal']=$meseRet[1].'/'.$meseRet[0];
            
            $tan_sql="SELECT Esq_Cod,Esq_Rec,Esq_Des,Esq_Xml,Esq_Ord FROM esquema WHERE esquema.Tan_Cod=$Tan_Cod AND esquema.Esq_Rec={Esq_Rec} AND esquema.Esq_Est='A' order by Esq_Ord Asc";
            $rs_esquema = $this->getArrayConsultaSql(str_replace("{Esq_Rec}",$Esq_Rec,$tan_sql), $obBD_conexion); // Consultamos las estiquetas Raiz del XML Factura Electronica
            /* Inicio de bucle de la identificaci�n */	
            foreach($rs_esquema as $row){ // Asignamos las etiquetas consultadas a la variable "$etiqueta[]"
                $Eti_raiz[] = $row['Esq_Xml'];
                $Cod_raiz[] = $row['Esq_Cod'];					
            } unset ($row);	
            $armado_xml = "<".$Eti_raiz[0].">";  //<infoTributaria>
            /* Consultamos informacion de la empresa */
            $rs_infoEmpresa = $this->getRowConsultaSql("SELECT empresas.Emp_Ruc,empresas.Emp_Nom,empresas.Emp_Reg,if(empresas.Emp_Cnt='S','SI','NO')as Emp_Cnt,empresas.Emp_Cor,confi_fact.Cof_Fac,confi_fact.Cof_Gce,if(Cof_Con='S','SI','NO')as Cof_Con, sucursal.Ciu_Cod,sucursal.Suc_Sri,sucursal.Suc_Des,sucursal.Suc_Dir,sucursal.Suc_Te1,sucursal.Suc_Dir,confi_fact.Cof_Fte,confi_fact.Cof_Clv
                FROM empresas
                INNER JOIN sucursal ON (empresas.Emp_Cod = sucursal.Emp_Cod)
                INNER JOIN confi_fact ON (empresas.Emp_Cod = confi_fact.Emp_Cod)
                WHERE sucursal.Suc_Cod=$Suc_Cod",$obBD_conexion);
//            /* Consultamos informacion del cliente */
            $Cliente = $this->getRowConsultaSql("SELECT 
                  persona.Prs_Ced,persona.Prs_Ape,persona.Prs_Nom,persona.Prs_Dir,persona.Prs_Tel,persona.Prs_Cor,cliente.Cli_Cod,
                  if(cliente.Cli_Con='NO','NO','SI')as Cli_Con,Tpc_Sri,ventas.Vet_Cod,CAST(ventas.Vet_Obs as CHAR(255))as VetObs,ventas.Vet_Num,identifica.Ide_Prv,date_format(caja_aper.Caj_Fec, '%d/%m/%Y') AS fecha,ventas.Vet_Des,autorizaci.Pun_Sri,tipo_compr.Tic_Sri,ventas.Vet_Ntd,date_format(ventas.Vet_Fdm, '%d/%m/%Y')as Vet_Fdm,ventas.Vet_Nns
                FROM
                  persona
                  INNER JOIN cliente ON (persona.Prs_Cod = cliente.Prs_Cod)
                  INNER JOIN identifica ON (persona.Ide_Cod = identifica.Ide_Cod)
                  INNER JOIN ventas ON (cliente.Cli_Cod = ventas.Cli_Cod)
				  INNER JOIN tipopagocom ON (ventas.Tpc_Cod = tipopagocom.Tpc_Cod)
                  INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod)
                  INNER JOIN autorizaci ON (ventas.Aut_Cod = autorizaci.Aut_Cod)
                  INNER JOIN tipo_compr ON (ventas.Tic_Cod = tipo_compr.Tic_Cod)
                WHERE ventas.Vet_Cod='$data[Vet_Cod]'", $obBD_conexion);
            $rs_infoCliente=  array_merge($Cliente,$data);
            /* consultamos las etiquetas Raiz del XML */
            $rs_infoTributaria = $this->getArrayConsultaSql(str_replace("{Esq_Rec}",$Cod_raiz[0],$tan_sql), $obBD_conexion);				
            /* Calculo del Digito verificador de la claveAcceso*/
            	
            $TipoAmbienteCE=$rs_infoEmpresa['Cof_Fac'];
            $TipoEmisionCE=$rs_infoEmpresa['Cof_Fte'];

            /*Control para generar la clave de acceso de tipo  1=Normal  2=Indisponibilidad de Sistema WebService SRI*/
            if($rs_infoEmpresa['Cof_Fte']=='1'){
                $cadena=str_replace("/","",$rs_infoCliente['Vet_Fec']).str_pad($Tic_Sri, 2, "0", STR_PAD_LEFT).$rs_infoEmpresa['Emp_Ruc'].$rs_infoEmpresa['Cof_Fac'].$rs_infoEmpresa['Suc_Sri'].$rs_infoCliente['Pun_Sri'].$rs_infoCliente['Vet_Num']."12345678".$rs_infoEmpresa['Cof_Fte'];
            }else{
                /*preguntamos si el txt aun posee numeros para usar*/
                if(count(file($Emp_Cod."/".$rs_infoEmpresa['Cof_Clv']))!=0){	
                    $file = file($Emp_Cod."/".$rs_infoEmpresa['Cof_Clv']);
                    /*clave de acceso de tipo emision INDISPONIBILIDAD DEL SISTEMA*/
                    $cadena=str_replace("/","",$rs_infoCliente['Vet_Fec']).str_pad($Tic_Sri, 2, "0", STR_PAD_LEFT).$rs_infoEmpresa['Emp_Ruc'].$rs_infoEmpresa['Cof_Fac'].substr($file[0], 14, 23).$rs_infoEmpresa['Cof_Fte'];
                }else{					
                    /*clave de acceso de tipo emision NORMAL*/
                    $cadena=str_replace("/","",$rs_infoCliente['Vet_Fec']).str_pad($Tic_Sri, 2, "0", STR_PAD_LEFT).$rs_infoEmpresa['Emp_Ruc'].$rs_infoEmpresa['Cof_Fac'].$rs_infoEmpresa['Suc_Sri'].$rs_infoCliente['Pun_Sri'].$rs_infoCliente['Vet_Num']."12345678".$rs_infoEmpresa['Cof_Fte'];							    				
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
            } unset($row);						 
            $armado_xml.="<".$Eti_infoTri[0].">".$rs_infoEmpresa['Cof_Fac']."</".$Eti_infoTri[0].">". //<ambiente>
                "<".$Eti_infoTri[1].">".$rs_infoEmpresa['Cof_Fte']."</".$Eti_infoTri[1].">".  //<tipoEmision>
                "<".$Eti_infoTri[2].">".utf8_encode($rs_infoEmpresa['Emp_Nom'])."</".$Eti_infoTri[2].">".  //<razonSocial>
                "<".$Eti_infoTri[3].">".utf8_encode($rs_infoEmpresa['Emp_Cor'])."</".$Eti_infoTri[3].">".  //<nombreComercial>
                "<".$Eti_infoTri[4].">".$rs_infoEmpresa['Emp_Ruc']."</".$Eti_infoTri[4].">".  //<ruc>
                "<".$Eti_infoTri[5].">".$claveAcceso."</".$Eti_infoTri[5].">".  	      //<claveAcceso>
                "<".$Eti_infoTri[6].">".str_pad($rs_infoCliente['Tic_Sri'],2,"0", STR_PAD_LEFT)."</".$Eti_infoTri[6].">".  //<codDoc>
                "<".$Eti_infoTri[7].">".$rs_infoEmpresa['Suc_Sri']."</".$Eti_infoTri[7].">".  //<estab> 
                "<".$Eti_infoTri[8].">".$rs_infoCliente['Pun_Sri']."</".$Eti_infoTri[8].">".  //<ptoEmi>
                "<".$Eti_infoTri[9].">".$rs_infoCliente['Vet_Num']."</".$Eti_infoTri[9].">".  //<secuencial>
                "<".$Eti_infoTri[10].">".utf8_encode($rs_infoEmpresa['Suc_Dir'])."</".$Eti_infoTri[10].">";//<dirMatriz>						
            $armado_xml .="</".$Eti_raiz[0].">"; //</infoTributaria>
            $armado_xml .="<".$Eti_raiz[1].">";  //<infoFactura> 				
				
            /* consultamos los totales de la venta sin impuesto y total del descuento */            
            $rs_infoTotales = $this->getRowConsultaSql("SELECT ventas.Vet_Cod, ventas.Vet_Obs, sum(ventas_det.Vet_Imp)as total, ((sum(ventas_det.Vet_Imp)*ventas.Vet_Des)/100)as Dscto
                FROM ventas
                    INNER JOIN ventas_det ON (ventas.Vet_Cod = ventas_det.Vet_Cod)
                WHERE ventas.Vet_Cod='$data[Vet_Cod]' GROUP by ventas_det.Vet_Cod", $obBD_conexion);
            $rs_infoFactura = $this->getArrayConsultaSql(str_replace("{Esq_Rec}",$Cod_raiz[1],$tan_sql), $obBD_conexion);
            foreach($rs_infoFactura as $row){
                $Eti_infoFac[] = $row['Esq_Xml'];
                $Cod_infoFac[] = $row['Esq_Cod'];					
            } unset($row);
            $armado_xml.="<".$Eti_infoFac[0].">".$rs_infoCliente['Vet_Fec']."</".$Eti_infoFac[0].">";    //<fechaEmision>				
            if($rs_infoEmpresa['Emp_Reg']!=''){	
                $armado_xml.="<".$Eti_infoFac[1].">".$rs_infoEmpresa['Emp_Reg']."</".$Eti_infoFac[1].">";//<contribuyenteEspecial>
            }
            $armado_xml.="<".$Eti_infoFac[2].">".$rs_infoEmpresa['Cof_Con']."</".$Eti_infoFac[2].">".   //<obligadoContabilidad>
                "<".$Eti_infoFac[3].">".$rs_infoCliente['Ide_Prv']."</".$Eti_infoFac[3].">".            //<tipoIdentificacionComprador>
                "<".$Eti_infoFac[4].">".utf8_encode(trim($rs_infoCliente['Prs_Nom']." ".$rs_infoCliente['Prs_Ape']))."</".$Eti_infoFac[4].">".//<razonSocialComprador>
                "<".$Eti_infoFac[5].">".$rs_infoCliente['Prs_Ced']."</".$Eti_infoFac[5].">".            //<identificacionComprador>
                "<".$Eti_infoFac[6].">".formato_numero($rs_infoTotales['total'],2,1)."</".$Eti_infoFac[6].">".//<totalSinImpuestos>
                "<".$Eti_infoFac[7].">".formato_numero($rs_infoTotales['Dscto'],2,1)."</".$Eti_infoFac[7].">".//<totalDescuento>
                "<".$Eti_infoFac[8].">";    								//<totalConImpuestos>
            
            $rs_totImpuesto = $this->getArrayConsultaSql(str_replace("{Esq_Rec}",$Cod_infoFac[8],$tan_sql), $obBD_conexion);									
            foreach($rs_totImpuesto as $row){
                $Eti_totImp[] = $row['Esq_Xml'];
                $Cod_totImp[] = $row['Esq_Cod'];					
            } unset ($row);
            $rs_importeImptos = $this->getArrayConsultASql("SELECT iva.Iva_Cod, iva.Iva_Sri, iva.Iva_Por, sum(ventas_det.Vet_Imp) AS imp
                FROM iva INNER JOIN ventas_det ON (iva.Iva_Cod = ventas_det.Iva_Cod)
                WHERE ventas_det.Vet_Cod = '$data[Vet_Cod]'
                GROUP BY iva.Iva_Cod", $obBD_conexion);
            $desgloDscto=$rs_infoTotales['Dscto']/count($rs_importeImptos);																										
            $totalBase=0;
            $totalValor=0;
            foreach($rs_importeImptos as $datosImpto){
                $armado_xml.="<".$Eti_totImp[0].">";   //<totalImpuesto>

                $rs_totImpuestoDato =  $this->getArrayConsultaSql(str_replace("{Esq_Rec}", $Cod_totImp[0],$tan_sql), $obBD_conexion); 
                foreach($rs_totImpuestoDato as $row){
                     $Eti_totImpDat[] = $row['Esq_Xml'];
                     $Cod_totImpDat[] = $row['Esq_Cod'];					
                } unset ($row);
                /** Sumo todos los valores gnerados por cuention de importe y valor del impuesto */
                $totalBase=$totalBase+($datosImpto['imp']-$desgloDscto);
                $totalValor=$totalValor+formato_numero(((($datosImpto['imp']-$desgloDscto)*$datosImpto['Iva_Por'])/100),2,1);

                $armado_xml.="<".$Eti_totImpDat[0].">2</".$Eti_totImpDat[0].">".                         //<codigo>
                    "<".$Eti_totImpDat[1].">".$datosImpto['Iva_Sri']."</".$Eti_totImpDat[1].">".	 //<codigoPorcentaje>
                    "<".$Eti_totImpDat[2].">".formato_numero(($datosImpto['imp']-$desgloDscto),2,1)."</".$Eti_totImpDat[2].">".//<baseImponible>
                    "<".$Eti_totImpDat[3].">".formato_numero(((($datosImpto['imp']-$desgloDscto)*$datosImpto['Iva_Por'])/100),2,1)."</".$Eti_totImpDat[3].">";	 //<valor>										
                $armado_xml.="</".$Eti_totImp[0].">";  //</totalImpuesto>
            }
            $armado_xml.="</".$Eti_infoFac[8].">".  //</totalConImpuestos>
                "<".$Eti_infoFac[9].">0.00</".$Eti_infoFac[9].">".    //<propina>
                "<".$Eti_infoFac[10].">".formato_numero(($totalBase + $totalValor),2,1)."</".$Eti_infoFac[10].">".   //<importeTotal>
                "<".$Eti_infoFac[11].">DOLAR</".$Eti_infoFac[11].">". //<moneda>	
                "<pagos><pago><formaPago>".$rs_infoCliente['Tpc_Sri']."</formaPago><total>".formato_numero(($totalBase + $totalValor),2,1)."</total></pago></pagos>";
            $armado_xml .="</".$Eti_raiz[1].">";// </infoFactura>
            $armado_xml .="<".$Eti_raiz[2].">"; // <detalles>
				
            $rs_detalle = $this->getArrayConsultaSql(str_replace("{Esq_Rec}", $Cod_raiz[2], $tan_sql), $obBD_conexion); 
            foreach($rs_detalle as $row){
                $Eti_detalle[] = $row['Esq_Xml'];
                $Cod_detalle[] = $row['Esq_Cod'];					
            } unset($row);
				
            /*  consultamos los Items de la factura  */
            $rs_items = $this->getArrayConsultaSql("SELECT producto.Pro_Cod,concat(item.Ite_Lar,' ',producto.Pro_Obs)as Pro_Obs, ventas_det.Vet_Can,ventas_det.Vet_Pru,ventas.Vet_Des,iva.Iva_Sri,iva.Iva_Por
                FROM iva
                  INNER JOIN ventas_det ON (iva.Iva_Cod = ventas_det.Iva_Cod)
                  INNER JOIN ventas ON (ventas_det.Vet_Cod = ventas.Vet_Cod)
                  INNER JOIN producto ON (ventas_det.Pro_Cod = producto.Pro_Cod)
                  INNER JOIN item ON (producto.Ite_Cod = item.Ite_Cod)
                WHERE ventas_det.Vet_Cod = '$data[Vet_Cod]'", $obBD_conexion);				
            foreach($rs_items as $row_itemDetalle){					
                $armado_xml.="<".$Eti_detalle[0].">";    //<detalle>
                $rs_cabDetalle = $this->getArrayConsultaSql(str_replace("{Esq_Rec}", $Cod_detalle[0], $tan_sql), $obBD_conexion);
                foreach($rs_cabDetalle as $row){
                    $Eti_cabDetalle[] = $row['Esq_Xml'];
                    $Cod_cabDetalle[] = $row['Esq_Cod'];					
                } unset ($row);
                $armado_xml.="<".$Eti_cabDetalle[0].">".$row_itemDetalle['Pro_Cod']."</".$Eti_cabDetalle[0].">".           //<codigoPrincipal>
                    "<".$Eti_cabDetalle[1].">".$row_itemDetalle['Pro_Cod']."</".$Eti_cabDetalle[1].">".	                   //<codigoAuxiliar>   	
                    "<".$Eti_cabDetalle[2].">".trim($row_itemDetalle['Pro_Obs'])."</".$Eti_cabDetalle[2].">". //<descripcion>
                    "<".$Eti_cabDetalle[3].">".formato_numero($row_itemDetalle['Vet_Can'],2,1)."</".$Eti_cabDetalle[3].">".                    //<cantidad>
                    "<".$Eti_cabDetalle[4].">".formato_numero($row_itemDetalle['Vet_Pru'],6,1)."</".$Eti_cabDetalle[4].">".//<precioUnitario>  
                    "<".$Eti_cabDetalle[5].">".$desgloDscto."</".$Eti_cabDetalle[5].">".                                   //<descuento>
                    "<".$Eti_cabDetalle[6].">".formato_numero((($row_itemDetalle['Vet_Can']*$row_itemDetalle['Vet_Pru']) - $desgloDscto),2,1)."</".$Eti_cabDetalle[6].">".//<precioTotalSinImpuesto>
                    "<".$Eti_cabDetalle[7].">";                                                                            //<impuestos>
                $rs_cabDetalleDat = $this->getArrayConsultaSql(str_replace("{Esq_Rec}", $Cod_cabDetalle[7], $tan_sql), $obBD_conexion); 
                foreach($rs_cabDetalleDat as $row){
                    $Eti_cabDetalleDat[] = $row['Esq_Xml'];
                    $Cod_cabDetalleDat[] = $row['Esq_Cod'];					
                } unset ($row);
                $armado_xml.="<".$Eti_cabDetalleDat[0].">";  //<impuesto>
                
                $rs_cabDetalleDatos = $this->getArrayConsultaSql(str_replace("{Esq_Rec}", $Cod_cabDetalleDat[0], $tan_sql), $obBD_conexion);
                foreach($rs_cabDetalleDatos as $row){
                     $Eti_cabDetalleDatos[] = $row['Esq_Xml'];
                     $Cod_cabDetalleDatos[] = $row['Esq_Cod'];					
                }
                $armado_xml.="<".$Eti_cabDetalleDatos[0].">2</".$Eti_cabDetalleDatos[0].">".                     //<codigo>
                    "<".$Eti_cabDetalleDatos[1].">".$row_itemDetalle['Iva_Sri']."</".$Eti_cabDetalleDatos[1].">".//<codigoPorcentaje>
                    "<".$Eti_cabDetalleDatos[2].">".$row_itemDetalle['Iva_Por']."</".$Eti_cabDetalleDatos[2].">".//<tarifa>
                    "<".$Eti_cabDetalleDatos[3].">".formato_numero((($row_itemDetalle['Vet_Can']*$row_itemDetalle['Vet_Pru']) - $desgloDscto),2,1)."</".$Eti_cabDetalleDatos[3].">".  //<baseImponible>
                    "<".$Eti_cabDetalleDatos[4].">".formato_numero(((($row_itemDetalle['Vet_Can']*$row_itemDetalle['Vet_Pru']) - $desgloDscto)*$row_itemDetalle['Iva_Por']/100),2,1)."</".$Eti_cabDetalleDatos[4].">";   //<valor>
                $armado_xml.="</".$Eti_cabDetalleDat[0].">";  //</impuesto>
                $armado_xml.="</".$Eti_cabDetalle[7].">";    //</impuestos>
                $armado_xml.="</".$Eti_detalle[0].">"; //<detalle>
            }//foreach($rs_items as $row_itemDetalle)
            $armado_xml .="</".$Eti_raiz[2].">";  //</detalles>

            if($rs_infoCliente['Prs_Dir']!="" or $rs_infoCliente['Prs_Tel']!='' or $rs_infoCliente['Prs_Cor']!=''){
                $armado_xml .="<".$Eti_raiz[3].">"; //<infoAdicional>
                $rs_infoAdicional = $this->getArrayConsultaSql(str_replace("{Esq_Rec}",$Cod_raiz[3],$tan_sql), $obBD_conexion);

                foreach($rs_infoAdicional as $row){
                    $Eti_infoAdicional[] = $row['Esq_Xml'];
                    $Cod_infoAdicional[] = $row['Esq_Cod'];					
                }
                if($rs_infoCliente['Prs_Dir']!='')
                { $armado_xml.="<".$Eti_infoAdicional[0]." nombre='Direcci�n'>".$rs_infoCliente['Prs_Dir']."</".$Eti_infoAdicional[0].">";	}
                if($rs_infoCliente['Prs_Tel']!='')
                { $armado_xml.="<".$Eti_infoAdicional[0]." nombre='Tel�fono'>".$rs_infoCliente['Prs_Tel']."</".$Eti_infoAdicional[0].">"; }
                if($rs_infoCliente['Prs_Cor']!='')
                { $armado_xml.="<".$Eti_infoAdicional[0]." nombre='Email'>".$rs_infoCliente['Prs_Cor']."</".$Eti_infoAdicional[0].">"; }
			    if($rs_infoCliente['VetObs']!='')
				{$armado_xml.="<".$Eti_infoAdicional[0]." nombre='Observacion'>".$rs_infoCliente['VetObs']."</".$Eti_infoAdicional[0].">";}
                $armado_xml .="</".$Eti_raiz[3].">";  //</infoAdicional>
            }
            if(!mb_detect_encoding($armado_xml, 'UTF-8', true)) $armado_xml=utf8_encode($armado_xml);
            $armado_xml ='<factura version="2.1.0" id="comprobante">'.$armado_xml.'</factura>';
            $buffer = '<?xml version="1.0" encoding="UTF-8"?>'.$armado_xml;
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

}//Fin de clase Class_Log_Conexion
