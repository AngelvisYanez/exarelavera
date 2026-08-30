<?Php 

/**
 * Logica de las paginas involucradas en el control de inventarios
 * @author Alejandro Camahco
 * @version 1.0
 * Fecha de actualizaci�n:	2021/07/07
**/

require_once ('../../auditoria/LOGICA/aud_log_auditoria.php');

class Class_Log_Conexion_Inventario extends MysqlConexion{}

class Class_Logica_Inventario extends MysqlDatosContab{

	function getProductoSSP($pro_cod,$actualizar,$obBD)
	{
		$kardex = $this->getArrayConsulta('kardex_ie.0', $pro_cod, $obBD);
		$promedio = array();

		if(count($kardex)>0)
		{                   
            $x=COUNT($kardex);
            for($i=1;$i<$x;$i++)
            {
            	if($i == 1){
            		$kardex[$i-1]['Stock']= $kardex[$i-1]['Kar_Can']*1 - $kardex[$i-1]['Kar_Sal'];
	                $kardex[$i-1]['Saldo']= ($kardex[$i-1]['Kar_Ims']*1) - ($kardex[$i-1]['Kar_Ime']*1);
	                $kardex[$i-1]['Promedio']=($kardex[$i-1]['Stock']!=0?$kardex[$i-1]['Saldo']/$kardex[$i-1]['Stock']:0);
	                $this->operacionobBD('kardex_ie.2', $kardex[$i-1], $obBD);
            	}

                if($kardex[$i]['Kar_Sal']*1!=0)
                { 
                  if($kardex[$i-1]['Promedio'] != null)
                  {
                    $kardex[$i]['Kar_Pre']=$kardex[$i-1]['Promedio'];
                    $kardex[$i]['Kar_Ime']= floatval($kardex[$i]['Kar_Pre'])*floatval($kardex[$i]['Kar_Sal']);
                  }
                  else
                  {
                    $kardex[$i]['Kar_Ime']= floatval($kardex[$i]['Kar_Pre'])*floatval($kardex[$i]['Kar_Sal']);
                  }
                }

                $kardex[$i]['Stock']=$kardex[($i-1)]['Stock']*1+$kardex[$i]['Kar_Can']*1-$kardex[$i]['Kar_Sal'];
                $kardex[$i]['Saldo']= ($kardex[($i-1)]['Saldo']*1) + ($kardex[$i]['Kar_Ims']*1) - ($kardex[$i]['Kar_Ime']*1);
                $kardex[$i]['Promedio']=($kardex[$i]['Stock']!=0?$kardex[$i]['Saldo']/$kardex[$i]['Stock']:$kardex[($i-1)]['Promedio']);

                if($actualizar){
                	$kardex[$i]['Promedio'] = round(floatval($kardex[$i]['Promedio']),5);
			        $kardex[$i]['Saldo'] = round(floatval($kardex[$i]['Saldo']), 5);
			        $kardex[$i]['Stock'] = round(floatval($kardex[$i]['Stock']), 5) ;
                	$this->operacionobBD('kardex_ie.2', $kardex[$i], $obBD);
                }
            }
            $promedio['Promedio'] = $kardex[$x-1]['Promedio'];
            $promedio['Saldo'] = $kardex[$x-1]['Saldo'];
            $promedio['Stock'] = $kardex[$x-1]['Stock'];
        }
        else
        {
            $promedio['Promedio']=0;$promedio['Saldo']=0;$promedio['Stock']=0;
        }

        $promedio['Promedio'] = round(floatval($promedio['Promedio']),5);
        $promedio['Saldo'] = round(floatval($promedio['Saldo']), 5);
        $promedio['Stock'] = round(floatval($promedio['Stock']), 5) ;

        if($actualizar)
        {
            $promedio['Producto'] = $pro_cod;
            $this->operacionobBD('producto.0', $promedio, $obBD);
            $this->operacionobBD('stock.0', $promedio, $obBD);
        }
        return $promedio;
	}

	function validarParametros($Suc_Cod, $obBD){
		$producto = $this->getRowConsulta('producto.1', $Suc_Cod, $obBD);
		$response = $this->mensaje();
		$response['contar'] = count($producto);
		return $response;
	}


	function updateKardex($Suc_Cod, $obBD){
		$productos = $this->getArrayConsulta('kardex_ie.1', $Suc_Cod, $obBD);
		foreach ($productos as $producto => $key) {
			$valores = $this->getProductoSSP($key['Pro_Cod'], true, $obBD);
		}
		return $this->mensaje();
	}

	function getCostoVenta($Vet_Cod,$obBD){
		 $costo = $this->getRowConsulta('kardex_ie.3', $Vet_Cod, $obBD);
		 return $costo['Costo'];
	}

	function saveComCosto($datos,$obBD){
		$ventas = $datos['rows'];
		$guardo = true;
		foreach ($ventas as $key => $value) {
			$costosVenta = $this->getArrayConsulta('kardex_ie.4', $value['Vet_Cod'], $obBD);
			$verificar = $this->saveComprobante($value, $costosVenta, $obBD);
			if($verificar == false){
				$guardo = false;
			}
		}
		return $this->mensaje();
	}

	function saveComCostoPeriodo($datos,$obBD){
		$costo = $this->getRowConsulta('kardex_ie.6', $datos['codigos'], $obBD);
		$datos['Costo'] = $costo['Asi_Val'];
		$costosVenta = $this->getArrayConsulta('kardex_ie.5', $datos['codigos'], $obBD);
		$verificar = $this->saveComprobantePeriodo($datos, $costosVenta, $obBD);
		return $this->mensaje();
	}

	function deleteComCosto($datos,$obBD){
		$comprobantes = $datos['rows'];
		foreach ($comprobantes as $key => $value) {
			$this->operacionobBD('comprobantes.5', $value['Com_Cod'], $obBD);
		}
		return $this->mensaje();
	}

	function deleteComprobante($codigo,$obBD){
		$this->operacionobBD('comprobantes.5', $codigo, $obBD);
		return $this->mensaje();
	}

	function saveComprobantePeriodo($datos, $cuentas, $obBD){
		$valida = $this->validaCierrePeriodo('comprobantes','Com_Fec','Com_Cod',$datos['Caj_Fec'],null,$obBD);
		$Pec_Cod = $this->getPerioCont($_SESSION['Ses_Emp_Cod'], $datos['Caj_Fec'], $obBD);
	    $datos['Pec_Cod'] = $Pec_Cod['Pec_Cod'];
		$Tia_Cod = $this->getRowConsulta('tipo_asien.0','CV', $obBD);
		$datos['Tia_Cod'] = intval($Tia_Cod['Tia_Cod']);
	    $datos['Prv_Cod'] = $this->getProveeClie($_SESSION['Ses_Emp_Cod'], 'Prv_Cod', $obBD);
	    $datos['Com_Num'] = $this->getComNumPecAuto($datos['Tia_Cod'], $datos['Pec_Cod'], $datos['Caj_Fec'], $obBD);
	    $datos['Com_Fec'] = $datos['Caj_Fec'];
	    $datos['Com_Con'] = 'Costo de Venta de los documentos en el periodo ' . $datos['Fec_Ini'] . ' - ' . $datos['Fec_Fin'];
	    $datos['Com_Obs'] = '';
	    $datos['Com_Tip'] = 'D';
	    $datos['Com_Gen'] = 'A';
	    $datos['Usu_Cod'] = $_SESSION['Ses_Usu_Cod'];
	    $datos['Com_Val'] = $datos['Costo'];
   		
   		$this->inicio_transaccion($obBD); 
        $this->operacionobBD('comprobantes.0',$datos,$obBD);
        $Com_Cod = $this->insercionid($obBD);

        foreach ($cuentas as $row)
        {                    
         	$asiento=array('Com_Cod'=>$Com_Cod, 'Asi_Deh'=>$row['Det_Tip'], 'Asi_Con'=>$row['Pld_Des'], 'Asi_Glo'=>$row['Glosa'], 'Pld_Cod'=>$row['Pld_Cod'], 'Asi_Val'=>$row['Asi_Val'] );
            $this->operacionobBD('asientos.0',$asiento,$obBD);
        }

        foreach ($datos['codigosArray'] as $key => $value)
        {
	        $relacion = array('Vet_Cod' => $value['Vet_Cod'], 'Com_Cod' => $Com_Cod);
			$this->operacionobBD('comprobantes.3',$relacion,$obBD);
		}

        $this->fin_transaccion_nomsn($obBD);
	}

	function saveComprobante($datos, $cuentas, $obBD){
		$valida = $this->validaCierrePeriodo('comprobantes','Com_Fec','Com_Cod',$datos['Caj_Fec'],null,$obBD);
		$Pec_Cod = $this->getPerioCont($_SESSION['Ses_Emp_Cod'], $datos['Caj_Fec'], $obBD);
	    $datos['Pec_Cod'] = $Pec_Cod['Pec_Cod'];
		$Tia_Cod = $this->getRowConsulta('tipo_asien.0','CV', $obBD);
		$datos['Tia_Cod'] = intval($Tia_Cod['Tia_Cod']);
	    $datos['Prv_Cod'] = $this->getProveeClie($_SESSION['Ses_Emp_Cod'], 'Prv_Cod', $obBD);
	    $datos['Com_Num'] = $this->getComNumPecAuto($datos['Tia_Cod'], $datos['Pec_Cod'], $datos['Caj_Fec'], $obBD);
	    $datos['Com_Fec'] = $datos['Caj_Fec'];
	    $datos['Com_Con'] = 'Costo de Venta de la factura Nro. ' . $datos['Vet_Num'];
	    $datos['Com_Obs'] = '';
	    $datos['Com_Tip'] = 'D';
	    $datos['Com_Gen'] = 'A';
	    $datos['Usu_Cod'] = $_SESSION['Ses_Usu_Cod'];
	    $datos['Com_Val'] = $datos['Costo'];
   		
   		$this->inicio_transaccion($obBD); 
        $this->operacionobBD('comprobantes.0',$datos,$obBD);
        $Com_Cod = $this->insercionid($obBD);

        foreach ($cuentas as $row)
        {                    
         	$asiento=array('Com_Cod'=>$Com_Cod, 'Asi_Deh'=>$row['Det_Tip'], 'Asi_Con'=>$row['Pld_Des'], 'Asi_Glo'=>$row['Glosa'], 'Pld_Cod'=>$row['Pld_Cod'], 'Asi_Val'=>$row['Asi_Val'] );
            $this->operacionobBD('asientos.0',$asiento,$obBD);
        }

        $relacion = array('Vet_Cod' => $datos['Vet_Cod'], 'Com_Cod' => $Com_Cod);
		$this->operacionobBD('comprobantes.3',$relacion,$obBD);
        $this->fin_transaccion_nomsn($obBD);
	}

	function mensaje(){
		if($this->Error==0) {$response=array('success'=>true, 'message'=>'La transacci�n se realizo con �xito!');} 
		else {$response=array('success'=>false,'message'=>'No se pudo realizar la transacci�n!','error'=>$this->MsgError);}
		return $response;
	}

}