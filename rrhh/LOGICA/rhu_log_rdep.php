<?php
/**
 * Lógica para procesar archivos RDEP
 * 
 * @author Sistema
 * @version 1.0
 */

require_once('../../DATA/MysqlConexion.php');
require_once('../../DATA/MysqlDatos.php');
require_once('rhu_sql_rdep.php');

/* Clase para conexion a la capa de acceso a datos */
class Class_Log_Conexion_Rdep extends MysqlConexion { }

/* Clase para acceder a los datos */
class Class_Log_Datos_Rdep extends MysqlDatosContab {
    function __construct() {
        $this->setSentencias('sentencias_rdep');
    }
}

/**
 * Procesa los archivos subidos y genera el RDEP
 */
function procesarArchivosRdep($post, $files, $obBD_con1, $obBD_conexion) {
    try {
        $resultado = array(
            'success' => false,
            'message' => '',
            'data' => array()
        );
        
        // Validar que se hayan subido los archivos
        if (!isset($files['consolidado']) || $files['consolidado']['error'] !== UPLOAD_ERR_OK) {
            $resultado['message'] = 'Error al subir el archivo consolidado';
            return $resultado;
        }
        
        // Procesar archivo consolidado (XLSX/XLS)
        $consolidado = procesarConsolidado($files['consolidado']);
        if (!$consolidado['success']) {
            $resultado['message'] = $consolidado['message'];
            return $resultado;
        }
        
        // Procesar CSV Décimo Tercero
        $decimoTercero = array();
        if (isset($files['decimoTercero']) && $files['decimoTercero']['error'] === UPLOAD_ERR_OK) {
            $decimoTercero = procesarCsvDecimoTercero($files['decimoTercero']);
        }
        
        // Procesar CSV Décimo Cuarto
        $decimoCuarto = array();
        if (isset($files['decimoCuarto']) && $files['decimoCuarto']['error'] === UPLOAD_ERR_OK) {
            $decimoCuarto = procesarCsvDecimoCuarto($files['decimoCuarto']);
        }
        
        // Procesar CSV Utilidades
        $utilidades = array();
        if (isset($files['utilidades']) && $files['utilidades']['error'] === UPLOAD_ERR_OK) {
            $utilidades = procesarCsvUtilidades($files['utilidades']);
        }
        
        // Combinar todos los datos
        $datosCombinados = combinarDatosRdep($consolidado['data'], $decimoTercero, $decimoCuarto, $utilidades);
        
        $resultado['success'] = true;
        $resultado['message'] = 'Archivos procesados correctamente';
        $resultado['data'] = $datosCombinados;
        
        return $resultado;
        
    } catch (Exception $e) {
        return array(
            'success' => false,
            'message' => 'Error: ' . $e->getMessage(),
            'data' => array()
        );
    }
}

/**
 * Procesa el archivo consolidado (XLSX/XLS)
 */
function procesarConsolidado($archivo) {
    try {
        $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        
        if ($extension === 'xlsx' || $extension === 'xls') {
            // Leer archivo Excel usando PHPExcel o similar
            // Por ahora retornamos estructura básica
            return array(
                'success' => true,
                'message' => 'Archivo consolidado procesado',
                'data' => array() // Se procesará en JavaScript
            );
        } else {
            return array(
                'success' => false,
                'message' => 'Formato de archivo no válido. Se requiere XLSX o XLS'
            );
        }
    } catch (Exception $e) {
        return array(
            'success' => false,
            'message' => 'Error al procesar consolidado: ' . $e->getMessage()
        );
    }
}

/**
 * Procesa el CSV de Décimo Tercero
 */
function procesarCsvDecimoTercero($archivo) {
    try {
        $datos = array();
        $handle = fopen($archivo['tmp_name'], 'r');
        
        if ($handle === false) {
            return array('success' => false, 'message' => 'No se pudo abrir el archivo');
        }
        
        // Leer encabezados
        $headers = fgetcsv($handle, 0, ';');
        
        // Leer datos
        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            if (count($row) >= 2) {
                $cedula = trim($row[0]);
                if (!empty($cedula)) {
                    $datos[$cedula] = array(
                        'cedula' => $cedula,
                        'nombres' => isset($row[1]) ? trim($row[1]) : '',
                        'apellidos' => isset($row[2]) ? trim($row[2]) : '',
                        'genero' => isset($row[3]) ? trim($row[3]) : '',
                        'ocupacion' => isset($row[4]) ? trim($row[4]) : '',
                        'total_ganado' => isset($row[5]) ? floatval(str_replace(',', '.', $row[5])) : 0,
                        'dias_laborados' => isset($row[6]) ? intval($row[6]) : 0,
                        'tipo_deposito' => isset($row[7]) ? trim($row[7]) : '',
                        'jornada_parcial' => isset($row[8]) ? trim($row[8]) : '',
                        'horas_jornada' => isset($row[9]) ? trim($row[9]) : '',
                        'discapacidad' => isset($row[10]) ? trim($row[10]) : '',
                        'valor_retencion' => isset($row[11]) ? floatval(str_replace(',', '.', $row[11])) : 0,
                        'mensualiza' => isset($row[12]) ? trim($row[12]) : ''
                    );
                }
            }
        }
        
        fclose($handle);
        
        return array('success' => true, 'data' => $datos);
        
    } catch (Exception $e) {
        return array('success' => false, 'message' => 'Error: ' . $e->getMessage());
    }
}

/**
 * Procesa el CSV de Décimo Cuarto
 */
function procesarCsvDecimoCuarto($archivo) {
    try {
        $datos = array();
        $handle = fopen($archivo['tmp_name'], 'r');
        
        if ($handle === false) {
            return array('success' => false, 'message' => 'No se pudo abrir el archivo');
        }
        
        // Leer encabezados
        $headers = fgetcsv($handle, 0, ';');
        
        // Leer datos
        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            if (count($row) >= 2) {
                $cedula = trim($row[0]);
                if (!empty($cedula)) {
                    $datos[$cedula] = array(
                        'cedula' => $cedula,
                        'nombres' => isset($row[1]) ? trim($row[1]) : '',
                        'apellidos' => isset($row[2]) ? trim($row[2]) : '',
                        'genero' => isset($row[3]) ? trim($row[3]) : '',
                        'ocupacion' => isset($row[4]) ? trim($row[4]) : '',
                        'dias_laborados' => isset($row[5]) ? intval($row[5]) : 0,
                        'tipo_pago' => isset($row[6]) ? trim($row[6]) : '',
                        'jornada_parcial' => isset($row[7]) ? trim($row[7]) : '',
                        'horas_jornada' => isset($row[8]) ? trim($row[8]) : '',
                        'discapacidad' => isset($row[9]) ? trim($row[9]) : '',
                        'fecha_jubilacion' => isset($row[10]) ? trim($row[10]) : '',
                        'valor_retencion' => isset($row[11]) ? floatval(str_replace(',', '.', $row[11])) : 0,
                        'mensualiza' => isset($row[12]) ? trim($row[12]) : ''
                    );
                }
            }
        }
        
        fclose($handle);
        
        return array('success' => true, 'data' => $datos);
        
    } catch (Exception $e) {
        return array('success' => false, 'message' => 'Error: ' . $e->getMessage());
    }
}

/**
 * Procesa el CSV de Utilidades
 */
function procesarCsvUtilidades($archivo) {
    try {
        $datos = array();
        $handle = fopen($archivo['tmp_name'], 'r');
        
        if ($handle === false) {
            return array('success' => false, 'message' => 'No se pudo abrir el archivo');
        }
        
        // Leer encabezados
        $headers = fgetcsv($handle, 0, ';');
        
        // Leer datos
        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            if (count($row) >= 2) {
                $cedula = trim($row[0]);
                if (!empty($cedula)) {
                    $datos[$cedula] = array(
                        'cedula' => $cedula,
                        'nombres' => isset($row[1]) ? trim($row[1]) : '',
                        'apellidos' => isset($row[2]) ? trim($row[2]) : '',
                        'genero' => isset($row[3]) ? trim($row[3]) : '',
                        'ocupacion' => isset($row[4]) ? trim($row[4]) : '',
                        'cargas_familiares' => isset($row[5]) ? intval($row[5]) : 0,
                        'dias_laborados' => isset($row[6]) ? intval($row[6]) : 0,
                        'tipo_pago_utilidad' => isset($row[7]) ? trim($row[7]) : '',
                        'jornada_parcial' => isset($row[8]) ? trim($row[8]) : '',
                        'horas_jornada' => isset($row[9]) ? trim($row[9]) : '',
                        'discapacidad' => isset($row[10]) ? trim($row[10]) : '',
                        'ruc_empresa_complementaria' => isset($row[11]) ? trim($row[11]) : '',
                        'decimo_tercero_2021' => isset($row[12]) ? floatval(str_replace(',', '.', $row[12])) : 0,
                        'decimo_cuarto_2021' => isset($row[13]) ? floatval(str_replace(',', '.', $row[13])) : 0,
                        'participacion_utilidades_2022' => isset($row[14]) ? floatval(str_replace(',', '.', $row[14])) : 0,
                        'salarios_percibidos_2021' => isset($row[15]) ? floatval(str_replace(',', '.', $row[15])) : 0,
                        'fondos_reserva_2021' => isset($row[16]) ? floatval(str_replace(',', '.', $row[16])) : 0,
                        'comisiones_2021' => isset($row[17]) ? floatval(str_replace(',', '.', $row[17])) : 0,
                        'beneficios_adicionales_2021' => isset($row[18]) ? floatval(str_replace(',', '.', $row[18])) : 0,
                        'anticipo_utilidad' => isset($row[19]) ? floatval(str_replace(',', '.', $row[19])) : 0,
                        'retencion_judicial' => isset($row[20]) ? floatval(str_replace(',', '.', $row[20])) : 0,
                        'impuesto_retencion' => isset($row[21]) ? floatval(str_replace(',', '.', $row[21])) : 0,
                        'informacion_mdt' => isset($row[22]) ? trim($row[22]) : '',
                        'tipo_pago_salario_digno' => isset($row[23]) ? trim($row[23]) : ''
                    );
                }
            }
        }
        
        fclose($handle);
        
        return array('success' => true, 'data' => $datos);
        
    } catch (Exception $e) {
        return array('success' => false, 'message' => 'Error: ' . $e->getMessage());
    }
}

/**
 * Combina todos los datos de los diferentes archivos
 */
function combinarDatosRdep($consolidado, $decimoTercero, $decimoCuarto, $utilidades) {
    $resultado = array();
    
    // Obtener todas las cédulas únicas
    $cedulas = array();
    
    if (is_array($consolidado)) {
        foreach ($consolidado as $row) {
            if (isset($row['cedula'])) {
                $cedulas[$row['cedula']] = true;
            }
        }
    }
    
    foreach (array_keys($cedulas) as $cedula) {
        $registro = array(
            'cedula' => $cedula,
            'consolidado' => isset($consolidado[$cedula]) ? $consolidado[$cedula] : array(),
            'decimo_tercero' => isset($decimoTercero['data'][$cedula]) ? $decimoTercero['data'][$cedula] : array(),
            'decimo_cuarto' => isset($decimoCuarto['data'][$cedula]) ? $decimoCuarto['data'][$cedula] : array(),
            'utilidades' => isset($utilidades['data'][$cedula]) ? $utilidades['data'][$cedula] : array()
        );
        
        $resultado[] = $registro;
    }
    
    return $resultado;
}

?>

