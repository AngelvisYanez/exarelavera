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

class ClienteClass {
    protected $_codigo = 0;                     // Codigo del cliente
    protected $_persona_codigo = 0;             // codigo de la persona
    protected $_cupo = 0;                       // Cupo de credito de un cliente
    protected $_estado = 'A';                   // Estado del cliente A=activo, I=inactivo
    protected $_empresa_codigo = 0;             // Empresa codigo
    protected $_tipo_contribuyente = null;      // Tipo de contribuyente N=natural, J=juridico
    protected $_ruc_empresa = null;             // Ruc de la empresa a nombre de quién saldra la factura
    protected $_obligado_contabilidad = null;   // Obligado de llevar contabilidad S=si,N=no
    protected $_nombre_fac = null;              // Empresa a nombre de quien saldrá la factura
    protected $_direccion_fac = null;           // Dirección a nombre de quién saldrá la factura
    protected $_tipo_empresa = null;            // Tipo de empresa: P=publica, R=privada
    protected $_correo = null;                  // Correo gerencial
    protected $_conexion = null;                  // Correo gerencial
    protected $_datos = null;                  // Correo gerencial
    
    function __construct($conexion, $datos){
        $this->conexion = $conexion;
        $this->datos = $datos;
    }

    public function getClientes($body){
        $data=$body;
        $consulta = $this->datos->getArrayConsulta(1000, array('Emp_Cod'=> $body['Emp_Cod']),$this->conexion);
        $response['data'] = $consulta;
        if ($this->datos->Error == 0) {
            $response['status'] = true;
            $response['message'] = "Consulta exitosa";
        }else{
            $response['status'] = false; 
            $response['message'] = "No se ha logrado realizar la Transaccion".$this->datos->MsgError; }
        $this->datos->echoJson($response);
    }

    public function setCliente($body){
        $data=$body;
        $data['Emp_Cod']=$body["Emp_Cod"];
        $data['Cli_Cor']=$body["Cli_Cor"];
        $this->datos->inicio_transaccion($this->conexion);
        $this->datos->operacionobBD(19,$data,$this->conexion);
        $data['Prs_Cod'] = $this->datos->insercionid($this->conexion);
        $this->datos->operacionobBD(12,$body["Prs_Ced"].'*'.$body["Prs_Nom"].'*'.$body["Prs_Ape"].'*'.$body["Prs_Sex"].'*'.$body["Prs_Dir"].'*'.$body["Prs_Tel"].'*'.$body["Prs_Te2"].'*'.$body["Prs_Cel"].'*'.$body["Ciu_Cod"].'*'.$body["Ide_Cod"].'*'.(empty($pers['Prs_Cor'])&&!empty($body["Prs_Cor"])?$body["Prs_Cor"]:'').'*'.$data['Prs_Cod'],$this->conexion);
        $this->datos->operacionobBD(20,$data,$this->conexion);
        $this->datos->fin_transaccion_nomsn($this->conexion);
        if ($this->datos->Error == 0) { $responce['success'] = true; }
        else{ $responce['success'] = false; $responce['message'] = "No se ha logrado realizar la Transaccion".$this->datos->MsgError; }
        $this->datos->echoJson($responce);
    }

    public function updateCliente($body){
        $data=$body;
        $this->datos->inicio_transaccion($this->conexion->conexion);                  
        $this->datos->operacionobBD(12,utf8_decode($body["Prs_Ced"].'*'.$body["Prs_Nom"].'*'.$body["Prs_Ape"].'*'.$body["Prs_Sex"].'*'.$body["Prs_Dir"].'*'.$body["Prs_Tel"].'*'.$body["Prs_Te2"].'*'.$body["Prs_Cel"].'*'.$body["Ciu_Cod"].'*'.$body["Ide_Cod"].'*'.(!empty($body["Prs_Cor"])?$body["Prs_Cor"]:'').'*'.$body["Prs_Cod"]),$this->conexion); 
        $this->datos->operacionobBD(26,$body["Prs_Cod"].'*'.$body["Cli_Tic"].'*'.$body["Cli_Con"].'*'.$body["Cli_Cod"].'*'.$body["Prs_Cor"],$this->conexion); 
        $this->datos->fin_transaccion_nomsn($this->conexion->conexion);
        if ($this->datos->Error == 0) { $responce['success'] = true; }
        else{ $responce['success'] = false; $responce['message'] = "No se ha logrado realizar la Transaccion"; }
        $this->datos->echoJson($responce);
    }
    
}