    function getArrayConsultaSql($sql, $obBD = null)
    {
        $result = $this->consulta($sql, $obBD);
        $a = array();
        if ($result) {
            while ($row = $this->fetch_assoc($result)) {
                $a[] = $row;
            }
            $this->free_result($result);
        }
        return $a;
    }
}

#[AllowDynamicProperties]
class MysqlDatosContab extends MysqlDatos
{
    function getReportHeader($sucursal, $titulo, $subtitulo, $obBD)
    {
        return "";
    }

    function getReportFooter($sucursal, $usuario, $obBD)
    {
        return "";
    }
}
?>
