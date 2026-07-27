<?php

/**
 * Clase que contiene los metodos para llamar desde la API
 *
 * @author angeloni
 * @version 1.0
 * Fecha de actualizaci�n:	2021-04-22
 *
 * @package classes
 */

class ProveedorClass {
    protected $conexion = null;
    protected $datos = null;
    
    function __construct($conexion, $datos){
        $this->conexion = $conexion;
        $this->datos = $datos;
    }

    public function getProveedores($body){
        $consulta = $this->datos->getArrayConsulta( 1001,''.'*'.$body['Emp_Cod'], $this->conexion);
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

    public function setProveedor($body){
        $data=$body;
        $this->datos->inicio_transaccion($this->conexion);
        // SI PERSONA EXISTE
        $existCedula = $this->datos->getArrayConsulta(1000, array('Prs_Ced'=> $body['Prs_Ced']),$this->conexion);
        if(count($existCedula) == 0){
            $this->datos->operacionobBD(7,$data,$this->conexion);
            $data['Prs_Cod'] = $this->datos->insercionid($this->conexion);
        }else{
            $data['Prs_Cod'] = $existCedula[0]["Prs_Cod"];
            $this->datos->operacionobBD(6,$data,$this->conexion);
        }
        $this->datos->operacionobBD(8,$data,$this->conexion);
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

    public function updateProveedor($body){
        $data=$body;
        $this->datos->inicio_transaccion($this->conexion);                  
        //ACTUALIZA CAMPOS DE PROVEEDOR
        $this->datos->operacionobBD(4,$data,$this->conexion);
        //ACTUALIZA CAMPOS DE PERSONA
        $this->datos->operacionobBD(6,$data,$this->conexion); 
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
    
}