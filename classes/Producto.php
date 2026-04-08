<?php

/**
 * Clase que contiene los metodos para llamar desde la API
 *
 * @author angeloni
 * @version 1.0
 * Fecha de actualizaci�n:	2021-04-29
 *
 * @package classes
 */

class ProductoClass {
    protected $_conexion = null;                  // Correo gerencial
    protected $_datos = null;
    
    function __construct($conexion, $datos){
        $this->conexion = $conexion;
        $this->datos = $datos;
    }

    public function getProductos($body){
        $data=$body;
        $consulta = $this->datos->getArrayConsulta( 1028,$data['Cat_Rec'].'*'.$data['Emp_Cod'], $this->conexion);
        $response['data'] = $consulta;
        if ($this->datos->Error == 0) {
            $response['status'] = true;
            $response['message'] = "Consulta exitosa";
        }else{
            $response['status'] = false; 
            $response['message'] = "No se ha logrado realizar la Transaccion".$this->datos->MsgError;
        }
        $this->datos->echoJson($response);
    }

    public function setProducto($body){
        $data=$body;
        // $this->datos->inicio_transaccion($this->conexion);
        $this->datos->inicio_transaccion($this->conexion);				
		$this->datos->operacionobBD(1026,$data['Cat_Cdc']."*".$data['Cat_Des']."*".$data['Cat_Tip']."*".$data['Cat_Rec']."*".$data['Emp_Cod'],$this->conexion);
		// $this->datos->fin_transaccion($this->conexion);
        $this->datos->fin_transaccion_nomsn($this->conexion);
        if($this->datos->Error==0) {
            $response['status'] = true;
            $response['message'] = "Transaccion exitosa";
            $response['data'] = $data;
        } else {
            $response['status'] = false;
            $response['message'] = "No se pudo realizar la transaccion!' ".$this->datos->MsgError;
            $response['data'] = $data;
        }
        $this->datos->echoJson($response);
    }

    public function updateProducto($body){
        $data=$body;
        $this->datos->inicio_transaccion($this->conexion);
        $this->datos->operacionobBD(1050, $data,$this->conexion);
        $this->datos->fin_transaccion_nomsn($this->conexion);
        if($this->datos->Error==0) {
            $response['status'] = true;
            $response['message'] = "Transaccion exitosa";
        } else {
            $response['status'] = false;
            $response['message'] = "No se pudo realizar la transaccion!' ".$this->datos->MsgError;
        }
        $this->datos->echoJson($response);
    }
    
}