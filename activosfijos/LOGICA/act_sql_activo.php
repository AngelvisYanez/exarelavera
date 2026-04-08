<?php
	
	function sentencias_activo($id,$Par_Sql)
	{
		switch($id)
		{	
			/**
			 * Insertar un nuevo activo
			 */
			case 601:
				$sql = "INSERT INTO activo (Tia_Cod, Pri_Cod, Suc_Cod, Est_Cod, Act_Des, Act_Obs, Act_Cdc, Act_Can, Act_Bar, Act_Gen, Act_Val, Act_Pde,Act_Res, Act_Ann, Act_Fec,Act_Ffd, Act_Gar, Act_Fot, Act_Dac) 
						VALUES ('$Par_Sql[0]','$Par_Sql[1]','$Par_Sql[2]','$Par_Sql[3]','$Par_Sql[5]','$Par_Sql[6]','$Par_Sql[7]','$Par_Sql[8]','$Par_Sql[9]','M','$Par_Sql[11]','$Par_Sql[12]','$Par_Sql[13]','$Par_Sql[14]','$Par_Sql[15]','$Par_Sql[16]','$Par_Sql[17]',null,$Par_Sql[19])";
				//echo $sql;
				return $sql;
			break;
			
			/**
			 * Modificar para agregar la foto del activo
			 */
			case 602:
				$sql = "UPDATE activo SET Act_Fot='$Par_Sql[1]' WHERE Act_Cod= '$Par_Sql[0]'";
				return $sql;
			break;
			
			/**
			 * Modificar para agregar el código de barras
			 */
			case 603:
				$sql = "UPDATE activo SET Act_Bar='$Par_Sql[1]', Act_Gen='$Par_Sql[2]' WHERE Act_Cod=$Par_Sql[0]";
				//echo $sql;
				return $sql;
			break;
			
			/**
			 * Tomar todos los tipos de activo de un nivel, cualquiera que este sea 
			 */
			/*case 604:
				$sql = "SELECT Tia_Cod, Tia_Des, Tia_Est, Tia_Rec, Tia_Cdc, Tia_Dep, Emp_Cod, Tia_Tip, Tia_Obs,Tia_Cod as id, Tia_Rec as parent, Tia_Des as text FROM tipo_activo WHERE Tia_Rec=$Par_Sql[0]";
				return $sql;
			break;*/
				
			/**
			 * Tomar el codigo Tia_Rec de un codigo determinado. Sirve para regresar de un directorio
			 */
			/*case 606:
				$sql = "SELECT Tia_Rec, Tia_Des FROM tipo_activo WHERE  Tia_Cod=$Par_Sql[0]";
				return $sql;
			break;*/
				
			/**
			 * Tomar la descripcion Tia_Des del nivel superior. Sirve para regresar de un directorio
			 */
			/*case 607:
				$sql = "SELECT Tia_Des FROM tipo_activo WHERE Tia_Cod=$Par_Sql[0] AND Emp_Cod = $Par_Sql[1]";
				return $sql;
			break;*/	
			
			/**
			 * Tomar todos los tipos de activo de todos los niveles para presentarlos en el jtree
			 */
			case 608:
				$sql = "SELECT Tia_Cod, Tia_Des, Tia_Est, Tia_Rec, Tia_Cdc, Tia_Dep, Emp_Cod, Tia_Tip, Tia_Obs, Tia_Cod as id, 
						CAST(IF(Tia_Rec=0,'#',Tia_Rec) AS CHAR) as parent, CONCAT(Tia_Cdc,' - ',Tia_Des) as 'text',
						IF(Tia_Rec=0,'fa fa-hand-o-right red bold',IF(Tia_Tip='G','glyphicon glyphicon-folder-open blue','fa fa-file-text green')) as icon 
						FROM tipo_activo 
						where Emp_Cod=$Par_Sql[0]";
				//echo $sql;
				return $sql;
			break;
			
			/**
			 * Permite determinar si el código para la categoría de tipo de activo existe
			 */
			case 609:
				$sql = "SELECT MAX(CAST((SUBSTRING_INDEX(Tia_Cdc, '.', -1) + 0)AS DECIMAL)) AS max 
						FROM tipo_activo 
						WHERE Tia_Rec=$Par_Sql[0] AND Emp_Cod=$Par_Sql[1]";
				return $sql;
			break;
			
			/**
			 * Permite verificar de que la persona este registrada como perito
			 */
			case 610:
				$sql = "SELECT perito.Pri_Cod, persona.Prs_Nom, persona.Prs_Ape
						FROM persona,perito
						WHERE persona.Prs_Ced='$Par_Sql[0]' AND persona.Prs_Cod=perito.Prs_Cod AND perito.Pri_Est='A'";
				return $sql;
			break;
			
			/**
			 * Permite listar los proveedores
			 */
			case 611:
				if($Par_Sql['op_opciones']=="d") {$search="(Prs_Ape LIKE '%$Par_Sql[search]%' OR Prs_Nom LIKE '%$Par_Sql[search]%')";}
				else {$search="Prs_Ced LIKE '$Par_Sql[search]%'";}
				if(isset($Par_Sql["limits"])){
					$Par_Sql["limits"]="ORDER BY Prs_Ape $Par_Sql[limits]";
					$campos=" Prv_Cod, Prs_Ced, CONCAT(Prs_Ape,' ',Prs_Nom) as proveedor, Prv_Fax,Prs_Dir, IF (Prv_Est='A','Activo','Inactivo') as Prv_Est";
				}
				else{$campos="COUNT(Prv_Cod) as total";$Par_Sql["limits"]="";}
				$sql="SELECT $campos FROM proveedore, persona WHERE $search AND proveedore.Prs_Cod=persona.Prs_Cod AND proveedore.Emp_Cod = $Par_Sql[Emp_Cod] $Par_Sql[limits]";
//				echo $sql;
				return $sql;
            break;
			
			/**
			 * Permite listar los estados de activo para llenar el comoboBox
			 */
			case 612:
				$sql = "SELECT Est_Cod, Est_Des 
						FROM estado_act 
						WHERE Est_Est='A'";
				return $sql; 
			break;
			
			/**
			 * Permite listar los activos registrados y cuyo estado sea activo
			 */
			case 613:
				if($Par_Sql['op_BuscarActivo']=="d") {$search="(Act_Des LIKE '%$Par_Sql[search_activo]%')";}
				else {$search="Act_Bar LIKE '$Par_Sql[search_activo]%'";}
				if(isset($Par_Sql["limits"]))
				{
					$Par_Sql["limits"]="ORDER BY activo.Act_Des $Par_Sql[limits]";
					$campos="activo.Act_Cod,activo.Tia_Cod,activo.Pri_Cod,activo.Est_Cod,estado_act.Est_Des,activo.Act_Des,activo.Act_Obs,activo.Act_Cdc,activo.Act_Can,activo.Act_Bar, activo.Act_Pde,
					activo.Act_Gen,activo.Act_Val,activo.Act_Res,activo.Act_Ann,activo.Act_Fec,activo.Act_Gar,activo.Act_Fot,per_perito.Prs_Ced AS ced_perito, 
					CONCAT(per_perito.Prs_Nom,' ',per_perito.Prs_Ape)AS nom_perito,CONCAT(tipo_activo.Tia_Cdc,' - ',tipo_activo.Tia_Des) AS Tia_Des, Act_Dac";
				}
				else
				{
					$campos="COUNT(Act_Cod) as total";$Par_Sql["limits"]="";
				}
				$sql="SELECT $campos FROM activo 
					  LEFT JOIN perito ON activo.Pri_Cod=perito.Pri_Cod
					  LEFT JOIN persona AS per_perito ON perito.Prs_Cod=per_perito.Prs_Cod                       
					  INNER JOIN tipo_activo ON activo.Tia_Cod=tipo_activo.Tia_Cod 
					  INNER JOIN estado_act ON activo.Est_Cod=estado_act.Est_Cod 
					  WHERE $search AND activo.Act_Est='A' AND activo.Suc_Cod = $Par_Sql[Suc_Cod] $Par_Sql[limits]";
				return $sql;
            break;


            //CONSULTAS PARA CONSULTAR ACTIVO
            case 6133:
				$sql="SELECT activo.*,per_perito.Prs_Ced AS ced_perito, 
                                        CONCAT(per_perito.Prs_Nom,' ',per_perito.Prs_Ape)AS nom_perito,CONCAT(tipo_activo.Tia_Cdc,' - ',tipo_activo.Tia_Des) AS Tia_Des FROM activo 
					  LEFT JOIN perito ON activo.Pri_Cod=perito.Pri_Cod
					  LEFT JOIN persona AS per_perito ON perito.Prs_Cod=per_perito.Prs_Cod                       
					  INNER JOIN tipo_activo ON activo.Tia_Cod=tipo_activo.Tia_Cod 
					  INNER JOIN estado_act ON activo.Est_Cod=estado_act.Est_Cod 
					  WHERE activo.Act_Est='A' AND activo.Act_Cod = $Par_Sql[Act_Cod] AND activo.Suc_Cod = $Par_Sql[Suc_Cod] $Par_Sql[limits]";
				return $sql;
            break;


            case 61333:
				$sql="SELECT * FROM config_activo WHERE Act_Cod = $Par_Sql[0]";
	        return $sql;
			
			/**
			 * Modificar los datos de un activo
			 */
			case 614:
				$sql = "UPDATE activo SET Tia_Cod='$Par_Sql[1]',Pri_Cod='$Par_Sql[2]',Est_Cod='$Par_Sql[3]', Prv_Cod='$Par_Sql[4]', 
						Act_Des='$Par_Sql[5]',Act_Obs='$Par_Sql[6]',Act_Cdc='$Par_Sql[21]', Act_Can='$Par_Sql[8]',Act_Bar='$Par_Sql[9]',Act_Gen='$Par_Sql[10]',Act_Gar='$Par_Sql[11]'
						WHERE Act_Cod=$Par_Sql[0]";
				//echo $sql;
				return $sql;
			break;
			
			/**
			 * Permite listar los estados de activo para llenar el comoboBox
			 */
			case 615:
				$sql = "SELECT Tia_Cod,CONCAT(Tia_Cdc,' - ',Tia_Des) AS descripcion 
						FROM tipo_activo 
						WHERE Tia_Tip='G' AND Emp_Cod=$Par_Sql[0]";
				//echo $sql; 
				return $sql; 
			break;
			
			/**
			 * Permite listar los campos concertiente a un tipo de activo específico
			 */
			case 616:
				$sql = "SELECT campos_act.Cam_Cod,campos_act.Cam_Lar,campos_act.Cam_Cor,campos_act.Cam_Tip,campos_plan.Cam_Ord,campos_plan.Cam_Req
						FROM tipo_activo,campos_act,campos_plan
						WHERE tipo_activo.Tia_Cod='$Par_Sql[0]' AND tipo_activo.Tia_Cod=campos_plan.Tia_Cod AND campos_plan.Cam_Cod=campos_act.Cam_Cod";
				//echo $sql;
				return $sql; 
			break;
			
			/**
			 * Permite insertar en la tabla det_activo
			 */
			case 617:
				$sql = "INSERT INTO det_activo (Act_Cod, Cam_Cod, Act_Val) 
						VALUES ('$Par_Sql[0]','$Par_Sql[1]','$Par_Sql[2]')";
				//echo $sql;
				return $sql; 
			break;
			
			/**
			 * Permite listar campos del tipo de activo con la informacion registrada en la tabla det_activo
			 */
			case 618:
				$sql = "SELECT det_activo.Act_Cod,det_activo.Cam_Cod,det_activo.Act_Val,
       					campos_act.Cam_Lar,campos_act.Cam_Cor,campos_act.Cam_Tip,campos_act.Cam_Obs,campos_act.Cam_Bus,
       					campos_plan.Cam_Ord,campos_plan.Cam_Req
						FROM det_activo,campos_act,campos_plan 
						WHERE det_activo.Act_Cod='$Par_Sql[0]' AND det_activo.Cam_Cod=campos_act.Cam_Cod 
						AND campos_act.Cam_Cod=campos_plan.Cam_Cod";
				//echo $sql;
				return $sql; 
			break;
			
			/**
			 * Modificar los datos de un activo
			 */
			case 619:
				$sql = "UPDATE det_activo SET Act_Val='$Par_Sql[2]'
						WHERE Act_Cod=$Par_Sql[0] AND Cam_Cod=$Par_Sql[1]";
				//echo $sql;
				return $sql;
			break;
			
			/**
         * Permite listar los peritos
         */
        case 620:
                if($Par_Sql['op_opciones']=="d") {$search="(Prs_Ape LIKE '%$Par_Sql[search]%' OR Prs_Nom LIKE '%$Par_Sql[search]%')";}
                else {$search="Prs_Ced LIKE '$Par_Sql[search]%'";}
                if(isset($Par_Sql["limits"])){
                        $Par_Sql["limits"]="ORDER BY Prs_Ape $Par_Sql[limits]";
                        $campos="Pri_Cod,Prs_Ced,CONCAT(Prs_Nom,' ',Prs_Ape) AS perito, IF (Pri_Est='A','Activo','Inactivo') as Pri_Est";
                }
                else{$campos="COUNT(Pri_Cod) as total";$Par_Sql["limits"]="";}
                $sql="SELECT $campos FROM perito,persona WHERE $search AND perito.Prs_Cod=persona.Prs_Cod AND perito.Emp_Cod = $Par_Sql[Emp_Cod] $Par_Sql[limits]";
                return $sql;
        break;
        
        /**
         * Extrae la información del campo Act_Fot de un activo
         */
        case 621:
                $sql="SELECT activo.Act_Cod,Act_Fec,Act_Res,Act_Ann,Act_Val,Ite_Lar,Act_Fot 
                      FROM activo 
                      INNER JOIN activo_compra ON activo.Act_Cod = activo_compra.Act_Cod
                      INNER JOIN producto ON activo_compra.Pro_Cod=producto.Pro_Cod
                      INNER JOIN item ON producto.Ite_Cod=item.Ite_Cod
                      WHERE activo.Act_Cod=$Par_Sql[0]";
                //echo $sql;
                return $sql;
        break;
    
        /**
         * Permite listar los productos registrados como activos fijos dentro de las facturas de compra
         */
        case 622:
                
                //Esta condición presenta todos los productos que consten dentro de una factura de compra y además que esten catalogados como activos fijos
                if($Par_Sql['Tipo']=='CFC'){
                    if($Par_Sql['op_opciones']=='d'){$search="(Ite_Lar LIKE '%$Par_Sql[search]%')";}
                    else{$search="Cop_Num LIKE '%$Par_Sql[search]%'";}
                        $sql="SELECT CONCAT(compras.Cop_Cod,'',det_compra.Cop_Int) AS llave,compras.Cop_Cod,det_compra.Cop_Int,det_compra.Cop_Can,producto.Pro_Cod,Cop_Num,Ite_Lar,Adq_Des,CONCAT(Pld_Cdc,' - ',Pld_Des) AS cuenta,Cop_Pru,Cop_Fec,Iva_Por,proveedore.Prv_Cod,CONCAT(Prs_Ape,' ',Prs_Nom) AS proveedor,CONCAT(Tri_Sri,'.  ',Tri_Des) AS Tri_Des,IF(det_compra.Iva_Cos='S','SI','NO')AS Iva_Cos                        
                        FROM compras
                        INNER JOIN det_compra ON det_compra.Cop_Cod=compras.Cop_Cod
                        INNER JOIN producto ON producto.Pro_Cod=det_compra.Pro_Cod
                        INNER JOIN produ_plan ON producto.Pro_Cod=produ_plan.Pro_Cod
                        INNER JOIN det_plan ON produ_plan.Pld_Cod=det_plan.Pld_Cod
                        INNER JOIN item ON producto.Ite_Cod=item.Ite_Cod
                        INNER JOIN categorias ON item.Cat_Cod=categorias.Cat_Cod
                        INNER JOIN adquisicio ON adquisicio.Adq_Cod=producto.Adq_Cod
                        INNER JOIN proveedore ON compras.Prv_Cod=proveedore.Prv_Cod 
                        INNER JOIN persona ON proveedore.Prs_Cod=persona.Prs_Cod
                        INNER JOIN sustento ON compras.Tri_Cod=sustento.Tri_Cod
                        INNER JOIN iva ON det_compra.Iva_Cod=iva.Iva_Cod
                        INNER JOIN precios ON precios.Pro_Cod=producto.Pro_Cod
                        WHERE $search AND (compras.Cop_Cod,det_compra.Cop_Int,producto.Pro_Cod) NOT IN (SELECT IF(ISNULL(Cop_Cod),'001',Cop_Cod) AS Cop_Cod,IF(ISNULL(Cop_Int),'001',Cop_Int) AS Cop_Int,Pro_Cod FROM activo_compra) AND produ_plan.Tip_Pld='C' AND adquisicio.Adq_Cod=2 AND producto.Pro_Est='A' AND precios.Suc_Cod=$Par_Sql[Suc_Cod]";
                }else{
                        if($Par_Sql['op_opciones']=='d'){$search="(Ite_Lar LIKE '%$Par_Sql[search]%')";}
                        else{$search="producto.Pro_Cod LIKE '%$Par_Sql[search]%'";}
                        $sql="SELECT producto.Pro_Cod,Ite_Lar,CONCAT(Pld_Cdc,' - ',Pld_Des) AS cuenta,Pre_Pvp,Iva_Por FROM producto 
                        INNER JOIN produ_plan ON producto.Pro_Cod=produ_plan.Pro_Cod
                        INNER JOIN det_plan ON produ_plan.Pld_Cod=det_plan.Pld_Cod
                        INNER JOIN item ON producto.Ite_Cod=item.Ite_Cod
                        INNER JOIN categorias ON item.Cat_Cod=categorias.Cat_Cod
                        INNER JOIN adquisicio ON adquisicio.Adq_Cod=producto.Adq_Cod
                        INNER JOIN precios ON precios.Pro_Cod=producto.Pro_Cod
                        INNER JOIN iva ON iva.Iva_Cod=producto.Iva_Cod
                        WHERE $search AND producto.Adq_Cod=2 AND producto.Pro_Est='A' AND precios.Suc_Cod=$Par_Sql[Suc_Cod]";
                }
                //echo $sql;
                return $sql;
        
        /**
         * Permite listar el plan de cuentas
         */
        case 623:
                if($Par_Sql[3]=="d") {$search="det_plan.Pld_Des LIKE '%$Par_Sql[0]%'";}
                else {$search="det_plan.Pld_Cdc LIKE '$Par_Sql[0]%'";}
                if($Par_Sql[4]==""){$campos="COUNT(det_plan.Pld_Cod) as total";}
                else{
                    $Par_Sql[4]="ORDER BY det_plan.Pld_Cod ".$Par_Sql[4];
                    $campos="det_plan.Pld_Cod, det_plan.Pld_Cdc,det_plan.Pld_Rec, det_plan.Pld_Des, empresas.Emp_Nom, Pla_Obs,
                            IF (parent2.Pld_cod IS NOT NULL, CONCAT(parent.Pld_Des,' <b>(',parent2.Pld_Des,')</b>'), parent.Pld_Des) as Pld_Grupo,
                            IF (det_plan.Pld_Tip='G', 'Grupo', 'Detalle') as Pld_Tip, IF (det_plan.Pld_Est='A', 'Activa', 'Inactiva') as Pld_Est ";
                }
                $sql="SELECT $campos
                      FROM det_plan 
                      INNER JOIN plan_cuenta ON plan_cuenta.Pla_Cod=det_plan.Pla_Cod
                      INNER JOIN perio_cont ON plan_cuenta.Pla_Cod=perio_cont.Pla_Cod
                      INNER JOIN empresas ON plan_cuenta.Emp_Cod=empresas.Emp_Cod 
                      LEFT JOIN det_plan as parent ON det_plan.Pld_Rec=parent.Pld_Cod
                      LEFT JOIN det_plan as parent2 ON parent.Pld_Rec=parent2.Pld_Cod
                      WHERE plan_cuenta.Emp_Cod=$Par_Sql[1] AND plan_cuenta.Pla_Est='A' 
                      AND $search AND Pec_Cod =$Par_Sql[2] 
                      AND det_plan.Pld_Tip = 'D' $Par_Sql[4]";
                //echo $sql;
                return $sql;
        
        /**
         * Permite listar los periódos contables
         */
        case 624:
		$sql="SELECT perio_cont.Pec_Cod,perio_cont.Pec_Fei,perio_cont.Pec_Fef,perio_cont.Pec_Est,Year(Pec_Fei) AS Periodo,perio_cont.Pla_Cod
                      FROM plan_cuenta
                      INNER JOIN perio_cont ON (plan_cuenta.Pla_Cod = perio_cont.Pla_Cod)
                      WHERE
                      Pec_Est = 'A' AND plan_cuenta.Emp_Cod = $Par_Sql[0]
                      ORDER BY Pec_Fei DESC";
		//echo $sql;
		return $sql;
        
        /**
         * Insert dentro de la tabla activo_compra
         */
        case 625:
		$sql="INSERT INTO activo_compra(Act_Cod,Cop_Cod,Cop_Int,Pro_Cod) VALUES('$Par_Sql[0]',".(empty($Par_Sql[1])?'NULL':$Par_Sql[1]).",".(empty($Par_Sql[2])?'NULL':$Par_Sql[2]).",'$Par_Sql[3]')";
		//echo $sql;
                return $sql;
        
        /**
         * Insert dentro de la tabla activo_ccontable
         */
        case 626:
		$sql="INSERT INTO activo_ccontable(Act_Cod,Pld_Cod,Acc_Tip) VALUES('$Par_Sql[0]','$Par_Sql[1]','$Par_Sql[2]')";
		//echo $sql;
                return $sql;
        
        /**
         * Select para obtener el códiigo del perito (Ninguna)
         */
        case 627:
		$sql="SELECT Pri_Cod FROM perito WHERE Pri_Esp='(Ninguna)'";
		//echo $sql;
                return $sql;
                
            
        /**
         * Consultas para el archivo: act_alt_depreciacion_1.0.php
         * Select para extraer todos los activos fijos
         */
        case 628:
                if(!empty($Par_Sql[Suc_Cod])){$condicion="AND Suc_Cod='$Par_Sql[Suc_Cod]'";}
                if(!empty($Par_Sql[Act_Cod])){$condicion=$condicion." AND activo.Act_Cod='$Par_Sql[Act_Cod]'";}
                if(!empty($Par_Sql[Pec_Cod])){$condicion=$condicion." AND comprobantes.Pec_Cod='$Par_Sql[Pec_Cod]'";}
                if(!empty($Par_Sql[Pld_Cod])){$condicion=$condicion." AND activo_ccontable.Pld_Cod='$Par_Sql[Pld_Cod]'";}
		$sql="SELECT DISTINCT(activo.Act_Cod), activo_deprecia.*, Act_Val,Act_Res,Act_Ann,Act_Des,Act_Fec,Act_Ffd,Ite_Lar,dp.Pld_Des,CONCAT(dp2.Pld_Cdc,' - ',dp2.Pld_Des) AS cc
                      FROM activo 
                      INNER JOIN activo_compra ON activo.Act_Cod=activo_compra.Act_Cod
                      INNER JOIN producto ON activo_compra.Pro_Cod=producto.Pro_Cod
                      INNER JOIN item ON producto.Ite_Cod=item.Ite_Cod
                      INNER JOIN activo_ccontable ON activo_ccontable.Act_Cod=activo.Act_Cod
                      INNER JOIN det_plan AS dp ON activo_ccontable.Pld_Cod=dp.Pld_Cod
                      INNER JOIN produ_plan ON producto.Pro_Cod=produ_plan.Pro_Cod
                      INNER JOIN det_plan AS dp2 ON produ_plan.Pld_Cod=dp2.Pld_Cod
                      LEFT JOIN activo_deprecia ON activo_deprecia.Act_Cod=activo.Act_Cod
                      LEFT JOIN comprobantes ON comprobantes.Com_Cod=activo_deprecia.Com_Cod
                      WHERE Act_Est='A' AND activo_ccontable.Acc_Tip='DE' $condicion";
		//echo $sql;
                return $sql;


        case 6288:

			$sql="SELECT * FROM activo_deprecia WHERE Act_Cod = $Par_Sql[0]";
	        return $sql;       


        /**
         * Select para comprobar si el activo ya ha sido depreciado
         */
        case 629:
		$sql="SELECT Act_Cod,Com_Cod,MAX(Acd_Fpd)AS Acd_Fdp FROM activo_deprecia WHERE Act_Cod='$Par_Sql[0]' GROUP BY Act_Cod";
		//echo $sql;
                return $sql;
        
        /**
         * Select para obtener el Prv_Cod que servira para insertar un registro en la tabla comprobantes
         */
        case 630:
		$sql="SELECT proveedore.Prv_Cod 
                      FROM proveedore,compra_prov 
                      WHERE Emp_Cod='$Par_Sql[0]' AND proveedore.Prv_Cod=compra_prov.Prv_Cod";
		//echo $sql;
                return $sql;
                
        /**
         * Select para obtener las cuentas de depreciación y depreciación acumulada correspondiente a un activo
         */
        case 631:
			$sql="SELECT * FROM activo_ccontable WHERE Act_Cod='$Par_Sql[0]'";
			//echo $sql;
	        return $sql;
                
        /**
         * Insert a efectuarse en la tabla comprobantes
         */
        case 632:
		$sql="INSERT INTO comprobantes(Pec_Cod,Prv_Cod,Usu_Cod,Com_Num,Com_Fec,Com_Val,Tia_Cod,Com_Gen) 
                      VALUES ('$Par_Sql[0]','$Par_Sql[1]','$Par_Sql[2]','$Par_Sql[3]','$Par_Sql[4]','$Par_Sql[5]','$Par_Sql[6]','A')";
		//echo $sql;
                return $sql;
                
        /**
         * Insert a efectuarse en la tabla activo_deprecia
         */
        case 633:
		$sql="INSERT INTO activo_deprecia(Com_Cod,Act_Cod,Acd_Fpd,Acd_Tip) 
                      VALUES ('$Par_Sql[0]','$Par_Sql[1]','$Par_Sql[2]','$Par_Sql[3]')";
		//echo $sql;
                return $sql;
        
        /**
         * Select para obtener el historial de depreciacion de un activo informacion extraida de la tabla activo_deprecia
         */
        case 634:
		if($Par_Sql['op_opciones']=="d") {$search="Ite_Lar LIKE '%$Par_Sql[search]%'";}
                else {if($Par_Sql['op_opciones']=="c") {$search="activo.Act_Cod LIKE '%$Par_Sql[search]%'";}else{$search="Act_Fec LIKE '%$Par_Sql[search]%'";}}
                if(isset($Par_Sql["limits"])){
                        $Par_Sql["limits"]="ORDER BY activo.Act_Cod $Par_Sql[limits]";
                        $campos="activo.Act_Cod,Act_Val,Act_Res,Act_Ann,Act_Fec,producto.Pro_Cod,Ite_Lar,Cop_Num";$Par_Sql["group"]="GROUP BY activo.Act_Cod";
                }
                else{$campos="COUNT(distinct(activo.Act_Cod)) as total";$Par_Sql["limits"]="";}
                $sql="SELECT $campos FROM activo 
                      INNER JOIN activo_deprecia ON activo.Act_Cod=activo_deprecia.Act_Cod
                      INNER JOIN activo_compra ON activo.Act_Cod=activo_compra.Act_Cod
                      INNER JOIN producto ON activo_compra.Pro_Cod=producto.Pro_Cod
                      LEFT JOIN compras ON activo_compra.Cop_Cod=compras.Cop_Cod
                      INNER JOIN item ON producto.Ite_Cod=item.Ite_Cod
                      INNER JOIN categorias ON item.Cat_Cod=categorias.Cat_Cod
                      WHERE $search AND Act_Est='A' AND Suc_Cod= $Par_Sql[Suc_Cod] $Par_Sql[group] $Par_Sql[limits]";
                //echo $sql;
                return $sql;
        
        /**
         * Select para listar los tipos de asiento
         */
        case 635:
		$sql="SELECT Tia_Cod,CONCAT(Tia_Abr,' - ',Tia_Des) AS Tia_Des,Tia_Abr FROM tipo_asien WHERE Tia_Ini='D' AND Tia_Est='A'";
		//echo $sql;
                return $sql;
                
        /**
         * Insert para efectuar en la tabla asiento
         */
        case 636:
		$sql="INSERT INTO asientos(Com_Cod,Asi_Deh,Asi_Val,Pld_Cod) VALUES('$Par_Sql[0]','$Par_Sql[1]','$Par_Sql[2]','$Par_Sql[3]')";
		//echo $sql;
                return $sql;
                
        /**
         * Update para actualizar el valor de la sumatoria de la depreciacion mensual en la tabla comprobantes
         */
        case 637:
		$sql="UPDATE comprobantes SET Com_Val='$Par_Sql[1]' WHERE Com_Cod='$Par_Sql[0]'";
		//echo $sql;
                return $sql;
        
        /**
         * Update para actualizar el valor de la sumatoria de la depreciacion mensual en la tabla comprobantes
         */
        case 638:
                $sql="SELECT Acd_Fpd FROM activo_deprecia WHERE Act_Cod='$Par_Sql[0]' AND Acd_Est='A'";
		//echo $sql;
                return $sql;
                
        /****** QUERYS PARA EL MANIPULACIÓN DENTRO DEL ARCHIVO act_alt_custodio_2.0.php ******/
        case 639:
                if($Par_Sql['op_opciones']=="d") {$search="(Prs_Ape LIKE '%$Par_Sql[search]%')";}
                else {$search="Prs_Ced LIKE '$Par_Sql[search]%'";}
                if(isset($Par_Sql["limits"])){
                        $Par_Sql["limits"]="ORDER BY empleado $Par_Sql[limits]";
                        $campos="Con_Cod,Prs_Ced,CONCAT(Prs_Ape,' ',Prs_Nom) AS empleado,Dep_Des";
                }
                else{$campos="COUNT(Con_Cod) as total";$Par_Sql["limits"]="";}
                $sql="SELECT $campos FROM personal 
                    INNER JOIN persona ON persona.Prs_Cod=personal.Prs_Cod
                    INNER JOIN contratos_lab ON contratos_lab.Per_Cod=personal.Per_Cod
                    INNER JOIN tiposcargo ON tiposcargo.Tic_Cod=contratos_lab.Tic_Cod
                    INNER JOIN departamen ON departamen.Dep_Cod=tiposcargo.Dep_Cod
                    WHERE $search AND Per_Est='A' AND personal.Emp_Cod = $Par_Sql[Emp_Cod] $Par_Sql[limits]";
                //echo $sql;
                return $sql;
        
        /**
         * Insert para efectuar en la tabla custodio
         */
        case 640:
		$sql="INSERT INTO custodio(Con_Cod,Cus_Fec) VALUES('$Par_Sql[0]','$Par_Sql[1]')";
		//echo $sql;
                return $sql;
                
        /**
         * Se obtiene el número mayor del campo Aca_Num de la tabal acta_activo
         */
        case 641:
		$sql="SELECT IF(ISNULL(MAX(Aca_Num)),0,MAX(Aca_Num)) AS Aca_Num FROM acta_activo";
		//echo $sql;
                return $sql;
                
        /**
         * Insert para efectuar en la tabla act_activo
         */
        case 642:
		$sql="INSERT INTO acta_activo(Aca_Num,Aca_Fec,Aca_Hor) VALUES('$Par_Sql[0]','$Par_Sql[1]','$Par_Sql[2]')";
		//echo $sql;
                return $sql;      
        /**
         * Insert para efectuar en la tabla asignacion
         */
        case 643:
		$sql="INSERT INTO asignacion(Cus_Cod,Act_Cod,Sec_Cod,Aca_Cod,Asg_Fec,Asg_Hor,Asg_Con,Asg_Raz) VALUES('$Par_Sql[0]','$Par_Sql[1]','$Par_Sql[2]','$Par_Sql[3]','$Par_Sql[4]','$Par_Sql[5]','$Par_Sql[6]','$Par_Sql[7]')";
		//echo $sql;
                return $sql;
        /**
         * Select para obtener información del custodio con el proposito de cargar información para imprimir el acta entrega-recepción
         */
        case 644:
		$sql = "SELECT Emp_Log,Emp_Nom,Emp_Ruc,Suc_Dir,Suc_Te1,Suc_Te2,Suc_Cor,CONCAT(Ciu_Des,' - ',Pro_Nom,' - ',Pas_Nom) AS ciudad,Aca_Fec,
                        CONCAT(Prs_Ape,' ',Prs_Nom) AS custodio,Prs_Ced,Asg_Fec
                        FROM custodio
                        INNER JOIN contratos_lab ON contratos_lab.Con_Cod=custodio.Con_Cod
                        INNER JOIN personal ON personal.Per_Cod=contratos_lab.Per_Cod
                        INNER JOIN persona ON persona.Prs_Cod=personal.Prs_Cod
                        INNER JOIN sucursal ON sucursal.Suc_Cod=personal.Suc_Cod
                        INNER JOIN empresas ON empresas.Emp_Cod=sucursal.Emp_Cod
                        INNER JOIN ciudad ON ciudad.Ciu_Cod=sucursal.Ciu_Cod
                        INNER JOIN provincia ON provincia.Pro_Cod=ciudad.Pro_Cod
                        INNER JOIN pais ON pais.Pas_Cod=ciudad.Pas_Cod
                        INNER JOIN asignacion ON asignacion.Cus_Cod=custodio.Cus_Cod
                        INNER JOIN acta_activo ON acta_activo.Aca_Cod=asignacion.Aca_Cod
                        WHERE custodio.Cus_Cod='$Par_Sql[0]'";
		//echo $sql;
                return $sql;
        /**
         * Permite listar los Tipos de activo pero que solo que sean de tipo GRUPO
         */
        case 645:
                $sql = "SELECT Tia_Cod,CONCAT(Tia_Cdc,' - ',Tia_Des) AS descripcion,Tia_Tip
                        FROM tipo_activo 
                        WHERE Tia_Tip='D' AND Tia_Rec='$Par_Sql[0]' ORDER BY descripcion";
                //echo $sql; 
				return $sql; 
        break;


        /*Consultas nuevas para la ventana act_mod_activo_2.0*/
        case 701:
                $sql = "SELECT Cop_Cod, Pro_Cod FROM activo_compra WHERE Act_Cod = $Par_Sql[Act_Cod]";
				return $sql; 
        break;

        /*Se busca los datos del producto con respecto a un activo*/
        case 702:
                 
                if($Par_Sql['Tipo']=='CFC')
                {
                        $sql="SELECT CONCAT(compras.Cop_Cod,'',det_compra.Cop_Int) AS llave,compras.Cop_Cod,det_compra.Cop_Int,det_compra.Cop_Can,producto.Pro_Cod,Cop_Num,Ite_Lar,Adq_Des,CONCAT(Pld_Cdc,' - ',Pld_Des) AS cuenta,Cop_Pru,Cop_Fec,Iva_Por,proveedore.Prv_Cod,CONCAT(Prs_Ape,' ',Prs_Nom) AS proveedor,CONCAT(Tri_Sri,'.  ',Tri_Des) AS Tri_Des,IF(det_compra.Iva_Cos='S','SI','NO')AS Iva_Cos                        
                        FROM compras
                        INNER JOIN det_compra ON det_compra.Cop_Cod=compras.Cop_Cod
                        INNER JOIN producto ON producto.Pro_Cod=det_compra.Pro_Cod
                        INNER JOIN produ_plan ON producto.Pro_Cod=produ_plan.Pro_Cod
                        INNER JOIN det_plan ON produ_plan.Pld_Cod=det_plan.Pld_Cod
                        INNER JOIN item ON producto.Ite_Cod=item.Ite_Cod
                        INNER JOIN categorias ON item.Cat_Cod=categorias.Cat_Cod
                        INNER JOIN adquisicio ON adquisicio.Adq_Cod=producto.Adq_Cod
                        INNER JOIN proveedore ON compras.Prv_Cod=proveedore.Prv_Cod 
                        INNER JOIN persona ON proveedore.Prs_Cod=persona.Prs_Cod
                        INNER JOIN sustento ON compras.Tri_Cod=sustento.Tri_Cod
                        INNER JOIN iva ON det_compra.Iva_Cod=iva.Iva_Cod
                        INNER JOIN precios ON precios.Pro_Cod=producto.Pro_Cod
                        WHERE produ_plan.Tip_Pld='C' AND adquisicio.Adq_Cod=2 AND producto.Pro_Est='A' AND precios.Suc_Cod=$Par_Sql[Suc_Cod] AND producto.Pro_Cod = $Par_Sql[Pro_Cod]";
                }
                else
                {
                        $sql="SELECT producto.Pro_Cod,Ite_Lar,CONCAT(Pld_Cdc,' - ',Pld_Des) AS cuenta,Pre_Pvp,Iva_Por FROM producto 
                        INNER JOIN produ_plan ON producto.Pro_Cod=produ_plan.Pro_Cod
                        INNER JOIN det_plan ON produ_plan.Pld_Cod=det_plan.Pld_Cod
                        INNER JOIN item ON producto.Ite_Cod=item.Ite_Cod
                        INNER JOIN categorias ON item.Cat_Cod=categorias.Cat_Cod
                        INNER JOIN adquisicio ON adquisicio.Adq_Cod=producto.Adq_Cod
                        INNER JOIN precios ON precios.Pro_Cod=producto.Pro_Cod
                        INNER JOIN iva ON iva.Iva_Cod=producto.Iva_Cod
                        WHERE  producto.Adq_Cod=2 AND producto.Pro_Est='A' AND precios.Suc_Cod=$Par_Sql[Suc_Cod] AND producto.Pro_Cod=$Par_Sql[Pro_Cod]";
                }
                return $sql;
        break;

         /* Select para obtener las cuentas de depreciación y depreciación acumulada correspondiente a un activo */
        case 703:
			$sql="SELECT CONCAT(Pec_Cod,'*',Year(Pec_Fei)) as Periodo, Acc_Tip, det_plan.Pld_Cod, Pld_Cdc,Pld_Des FROM activo_ccontable 
					INNER JOIN det_plan on activo_ccontable.Pld_Cod = det_plan.Pld_Cod
					INNER JOIN plan_cuenta on plan_cuenta.Pla_Cod = det_plan.Pla_Cod
					INNER JOIN perio_cont on perio_cont.Pla_Cod = plan_cuenta.Pla_Cod
					WHERE Act_Cod=$Par_Sql[0] AND Pec_Est = 'A' ORDER BY Pec_Cod Desc  LIMIT 2";
	        return $sql;
	    break;

	     case 704:
			$sql="SELECT perito.Pri_Cod, CONCAT(Prs_Nom, ' ', Prs_Ape) as Perito 
					FROM activo
					INNER JOIN perito ON perito.Pri_Cod = activo.Pri_Cod
					INNER JOIN persona ON persona.Prs_Cod = perito.Prs_cod
					WHERE Act_Cod=$Par_Sql[0]";
	        return $sql;
	    break;

	    case 705:
		$sql="SELECT perio_cont.Pec_Cod,perio_cont.Pec_Fei,perio_cont.Pec_Fef,perio_cont.Pec_Est,Year(Pec_Fei) AS Periodo,perio_cont.Pla_Cod
                      FROM plan_cuenta
                      INNER JOIN perio_cont ON (plan_cuenta.Pla_Cod = perio_cont.Pla_Cod)
                      WHERE
                      Pec_Est = 'A' AND plan_cuenta.Emp_Cod = $Par_Sql[0]
                      ORDER BY Pec_Fei DESC LIMIT 1";
			return $sql;
		break;

		case 706:
		$sql="SELECT Are_Cod, Are_Des FROM areas_rrhh WHERE Emp_Cod = $Par_Sql[0]";
			return $sql;
		break;

		case 707:
		$sql="SELECT departamen.Dep_Cod, departamen.Dep_Des 
				FROM departamen 
				INNER JOIN activo_departamento ON activo_departamento.Dep_Cod=departamen.Dep_Cod  
				WHERE Are_Cod = $Par_Sql[Are_Cod] AND Dep_Rec = 0 AND Emp_Cod = $Par_Sql[Emp_Cod]";
			return $sql;
		break;

		case 708:
            $sql = "SELECT * FROM activo_departamento
						INNER JOIN det_plan ON activo_departamento.Pld_Cod=det_plan.Pld_Cod 
						WHERE Dep_Cod = $Par_Sql[0]";
			return $sql;              
        break;

        case 709:
				$sql = "SELECT activo_porcent.Apr_Por
						FROM tipo_activo
						INNER JOIN activo_porcent ON tipo_activo.Apr_Cod = activo_porcent.Apr_Cod
						WHERE tipo_activo.Tia_Cod='$Par_Sql[0]' AND Apr_Est = 'A'";
				return $sql; 
			break;

		case 710:
				$sql = "SELECT * FROM sucursal
					INNER JOIN empresas ON sucursal.Emp_Cod = empresas.Emp_Cod
					AND sucursal.Suc_Cod = $Par_Sql[0]";
				return $sql; 
			break;

		//Consulta de departamentos parametrizados para asignar a activos
        case 711:

        		$search=" (departamen.Dep_Des LIKE '%$Par_Sql[search]%') ";
                if(isset($Par_Sql["limits"])){
                        $Par_Sql["limits"]="ORDER BY Dep_Des $Par_Sql[limits]";
                        $campos=" departamen.Dep_Cod, Dep_Des, Pld_Des ";
                }
                else{$campos="COUNT(departamen.Dep_Cod) as total";$Par_Sql["limits"]="";}

                $sql="SELECT $campos FROM activo_departamento 
						INNER JOIN departamen ON activo_departamento.Dep_Cod = departamen.Dep_Cod
						INNER JOIN det_plan ON activo_departamento.Pld_Cod = det_plan.Pld_Cod
						WHERE $search  AND Emp_Cod = $Par_Sql[Emp_Cod] $Par_Sql[limits]";
                return $sql;
            break;

        //Realiza la asignacion de activos a un departamento 
        case 712:
		$sql="INSERT INTO asignacion(Asg_Cod,Asg_Typ,Act_Cod,Aca_Cod,Asg_Fec,Asg_Hor,Asg_Fas,Asg_Raz,Asg_Con,Asg_Ord) 
				VALUES('$Par_Sql[Asg_Cod]','$Par_Sql[Asg_Typ]','$Par_Sql[Act_Cod]','$Par_Sql[Aca_Cod]',
						'$Par_Sql[Asg_Fec]','$Par_Sql[Asg_Hor]','$Par_Sql[Asg_Fas]','$Par_Sql[Asg_Raz]' ,'$Par_Sql[Asg_Con]', 1)";
                return $sql;
            break;

        //Consulta de asignaciones para custodio y departamentos
        case 713:
	        $activo = '';
	        if($Par_Sql[Act_Cod] != ''){
	        	$activo = " AND activo.Act_Cod = " . $Par_Sql[Act_Cod];
	        }

			$sql="SELECT 
				asig.Asg_Cod,
				asig.Asg_Typ,
				activo.Act_Cod, 
				activo.Act_Des,
				IF(asig.Asg_Typ = 'C', 'Custodio', 'Departamento') as Tipo,
				acta_activo.Aca_Num,
				asig.Asg_Fec,

				IF(asig.Asg_Typ = 'C', 
				(SELECT CONCAT(Prs_Nom, ' ', Prs_Ape) FROM custodio 
				 INNER JOIN contratos_lab ON contratos_lab.Con_Cod = custodio.Con_Cod 
				 INNER JOIN personal ON contratos_lab.Per_Cod = personal.Per_Cod 
				 INNER JOIN persona ON persona.Prs_Cod = personal.Prs_Cod 
				 WHERE Cus_Cod = asig.Asg_Cod),
				  
				(SELECT Dep_Des FROM departamen 
				 WHERE Dep_Cod = asig.Asg_Cod)
				) as DepaCustodio,

				asig.Asg_Raz,
				asig.Asg_Fas,
				IF(asig.Asg_Con = 'C', 'Confirmada', 'No confirmada') as Estado
				FROM asignacion as asig
				INNER JOIN activo ON asig.Act_Cod  = activo.Act_Cod
				INNER JOIN acta_activo ON acta_activo.Aca_Cod  = asig.Aca_Cod
				WHERE activo.Suc_Cod = $Par_Sql[Suc_Cod]" . $activo;
	        return $sql;
	        break;

	    //Realiza la asignacion de activos a un departamento 
        case 714:
			$sql="SELECT departamen.Dep_Cod, Dep_Des 
			FROM asignacion 
			INNER JOIN departamen ON asignacion.Asg_Cod = departamen.Dep_Cod
			WHERE asignacion.Act_Cod = $Par_Sql[0] AND asignacion.Asg_Typ = 'D' LIMIT 1";
        return $sql;
        break;

        case 720:
			  $sql = "UPDATE activo SET Tia_Cod='$Par_Sql[Tia_Cod]',Pri_Cod='$Par_Sql[Pri_Cod]',Est_Cod='$Par_Sql[Est_Cod]',
			                            Act_Des='$Par_Sql[Act_Des]',Act_Obs='$Par_Sql[Act_Obs]',Act_Cdc='$Par_Sql[Act_Cdc]',Act_Bar='$Par_Sql[Act_Bar]',
			                            Act_Gen='$Par_Sql[Act_Gen]',Act_Val='$Par_Sql[Act_Val]',Act_Pde='$Par_Sql[Act_Pde]',Act_Res='$Par_Sql[Act_Res]',
			                            Act_Ann='$Par_Sql[Act_Ann]',Act_Fec='$Par_Sql[Act_Fec]',Act_Ffd='$Par_Sql[Act_Ffd]',Act_Gar='$Par_Sql[Act_Gar]',
			                            Act_Dac='$Par_Sql[Act_Dac]'
						WHERE Act_Cod=$Par_Sql[Act_Cod]";
				return $sql;
			break;

		case 721:
		  $sql = "DELETE FROM det_activo WHERE Act_Cod = $Par_Sql[0]";
			return $sql;
		break;

		case 722:
		  $sql = "DELETE FROM activo_ccontable WHERE Act_Cod = $Par_Sql[0]";
			return $sql;
		break;

		case 723:
			$sql = "UPDATE activo SET Act_Fot='$Par_Sql[Act_Fot]' WHERE Act_Cod=$Par_Sql[Act_Cod]";
			return $sql;
		break;

		case 724:
			$sql = "UPDATE asignacion SET Asg_Cod=$Par_Sql[Dep_Cod]
			WHERE Asg_Cod = $Par_Sql[Dep_Cod] 
			AND Act_Cod = $Par_Sql[Act_Cod] 
			AND Asg_Typ = 'D' 
			AND Asg_Ord = 1";
			return $sql;
		break;
	}
}
?>