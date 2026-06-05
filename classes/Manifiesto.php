<?php

// Cambiar temporalmente el directorio de trabajo para simular la ejecución desde un FRONT del módulo relavera
// Esto permite que los requires relativos (../../) dentro de man_log_manifiesto.php se resuelvan correctamente.
$old_cwd = getcwd();
chdir(__DIR__ . "/../relavera/FRONT");
require_once __DIR__ . "/../relavera/LOGICA/man_log_manifiesto.php";
chdir($old_cwd);

class ManifiestoClass
{
    protected $_conexion = null;
    protected $_datos = null;

    public function __construct($conexion, $datos)
    {
        $this->_conexion = $conexion;
        $this->_datos = $datos;
    }

    public function getManifiestos($body)
    {
        $datos = [
            "setWhere" => ["setEmpCod", "isActive"],
            "order" => "manifiesto.Man_Sys DESC"
        ];

        if (!empty($body["search"])) {
            $datos["search"] = $body["search"];
            $datos["op_opciones"] = $body["op_opciones"] ?? "p";
        }

        if (!empty($body["filtro_estado"])) {
            $estado = $body["filtro_estado"];
            if ($estado === "P") {
                $datos["setWhere"][] = "getPendiente";
            } elseif ($estado === "GE") {
                $datos["setWhere"][] = "getGaritaIn";
            } elseif ($estado === "A") {
                $datos["setWhere"][] = "getAprobado";
            } elseif ($estado === "GS") {
                $datos["setWhere"][] = "getGaritaOut";
            } elseif ($estado === "F") {
                $datos["setWhere"][] = "getFacturadoManTes";
            } elseif ($estado === "R") {
                $datos["setWhere"][] = "getRechazado";
            }
        }

        if (!empty($body["fec_ini"]) && !empty($body["fec_fin"])) {
            $datos["txt_fec_ini"] = $body["fec_ini"];
            $datos["txt_fec_fin"] = $body["fec_fin"];
        }

        $page = isset($body["page"]) ? (int) $body["page"] : 1;
        $rows = isset($body["rows"]) ? (int) $body["rows"] : 50;
        $offset = ($page - 1) * $rows;
        $datos["page"] = $page;
        $datos["rows"] = $rows;

        $resultado = $this->_datos->getPageGrid(
            "manifiesto.selectWhere",
            $datos,
            $this->_conexion,
            true
        );

        $response = [
            "status" => true,
            "message" => "Consulta exitosa",
            "data" => $resultado
        ];

        utf8_encode_deep($response);
        echo json_encode($response);
        exit();
    }

    public function getManifiesto($body)
    {
        $manCod = $body["Man_Cod"] ?? 0;

        $consulta = $this->_datos->getRowConsulta(
            6,
            ["Man_Cod" => $manCod],
            $this->_conexion
        );

        $response = ["data" => $consulta];
        if ($this->_datos->Error == 0) {
            $response["status"] = true;
            $response["message"] = "Consulta exitosa";
        } else {
            $response["status"] = false;
            $response["message"] = "Error: " . $this->_datos->MsgError;
        }
        utf8_encode_deep($response);
        echo json_encode($response);
        exit();
    }
}