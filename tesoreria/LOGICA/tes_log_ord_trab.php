<?php
require_once ('../../auditoria/LOGICA/aud_log_auditoria.php');
require_once("tes_sql_ord_trab.php");

class Class_Log_Conexion_Inventario extends MysqlConexion{}

class Class_Logica_Inventario extends MysqlDatosContab{
        
    function __construct(){
            $this->setSentencias('sentencias_facturaVenta');
    }

    function validarParametros($Suc_Cod, $obBD){
        $producto = $this->getRowConsulta('producto.1', $Suc_Cod, $obBD);
        $response = $this->mensaje();
        $response['contar'] = count($producto);
        return $response;
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

    function saveFicha($datos,$obBD){
        $medic = $datos['Medicamento'];
        $this->operacionobBD('ficha_paciente.0', $datos, $obBD);
        $Fic_Cod = $this->insercionid($obBD);
        $tamano = count($medic) - 1;
        for($i=0; $i<$tamano;$i++){
            $medic[$i]['Fic_Cod'] = $Fic_Cod;

            $this->operacionobBD('ficha_paciente.1', $medic[$i], $obBD);
        }
        return $this->mensaje();
    }

    function mensaje(){
        if($this->Error==0) {$response=array('success'=>true, 'message'=>'La transacción se realizo con éxito!');} 
        else {$response=array('success'=>false,'message'=>'No se pudo realizar la transacción!','error'=>$this->MsgError);}
        return $response;
    }

}