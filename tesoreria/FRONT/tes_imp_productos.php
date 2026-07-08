<?php

/**
 * Permite copiar productos de otras empresas a la empresa actual
 *
 * @author Sistema EXA
 * @version 1.0
 * Fecha de actualización: 2025-01-XX
 *
 * @package facturacion.FRONT
 */

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tes_log_producto_1.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require_once('../../DATA/MysqlConexion.php');

/* objeto para la conexion */
$obBD_conexion = new Class_Log_Conexion_Pro($Ses_Dat_Dis);
/* objeto para consultas */
$obBD_con1 = new Class_Log_Datos_Pro;

// Ajax para cargar bases de datos disponibles
if (isset($loadBasesDatos)) {
    require_once('../../administrador/LOGICA/logica.php');
    $obBD_conexion_master = new Class_Log_Conexion_Adm();
    $obBD_master = new Class_Log_Datos_Adm();
    
    // Obtener bases de datos únicas desde la tabla data
    $sql = "SELECT DISTINCT Dat_Dis FROM exa_master.data WHERE Dat_Dis IS NOT NULL AND Dat_Dis != '' ORDER BY Dat_Dis";
    $bases_datos_raw = $obBD_master->getArrayConsultaSql($sql, $obBD_conexion_master);
    
    $bases_datos = array();
    foreach ($bases_datos_raw as $base) {
        $bases_datos[] = array(
            'Dat_Dis' => $base['Dat_Dis'],
            'Emp_Nom' => strtoupper($base['Dat_Dis'])
        );
    }
    
    echo json_encode(array('success'=>true, 'bases'=>$bases_datos));
    exit();
}

// Ajax para cargar empresas de una base de datos específica
if (isset($loadEmpresasByDB)) {
    if (empty($Dat_Dis)) {
        echo json_encode(array('success'=>false, 'message'=>'No se ha seleccionado una base de datos!'));
        exit();
    }
    
    require_once('../../administrador/LOGICA/logica.php');
    $obBD_conexion_master = new Class_Log_Conexion_Adm();
    $obBD_master = new Class_Log_Datos_Adm();
    
    // Obtener empresas de la base de datos seleccionada
    $sql = "SELECT DISTINCT E.Emp_Cod, E.Emp_Nom, E.Emp_Cor
            FROM `" . addslashes($Dat_Dis) . "`.empresas E
            INNER JOIN `" . addslashes($Dat_Dis) . "`.sucursal S ON E.Emp_Cod = S.Emp_Cod
            INNER JOIN exa_master.access A ON S.Suc_Cod = A.Suc_Cod
            WHERE E.Emp_Est='A' AND S.Suc_Est='A' AND A.Acc_Est='A' 
            AND A.Acc_Usr='" . mysqli_real_escape_string($obBD_conexion_master->conexion, trim($Ses_Usu_Ced)) . "'
            AND E.Emp_Cod != " . intval($_SESSION['Ses_Emp_Cod']) . "
            ORDER BY E.Emp_Nom";
    
    $empresas = $obBD_master->getArrayConsultaSql($sql, $obBD_conexion_master);
    
    echo json_encode(array('success'=>true, 'empresas'=>$empresas));
    exit();
}

// Ajax para cargar categorías de la empresa destino
if (isset($loadCategorias)) {
    $categorias = $obBD_con1->getArrayConsulta(1, array($Ses_Emp_Cod, 'D'), $obBD_conexion);
    echo json_encode(array('success'=>true, 'categorias'=>$categorias));
    exit();
}

// Ajax para cargar categorías de la empresa origen
if (isset($loadCategoriasOrigen)) {
    if (empty($Dat_Dis) || empty($Emp_Cod_Origen)) {
        echo json_encode(array('success'=>false, 'message'=>'Debe seleccionar una base de datos y empresa origen'));
        exit();
    }
    
    // Crear conexión a la base de datos origen
    $obBD_conexion_origen = new Class_Log_Conexion_Pro($Dat_Dis);
    if (!$obBD_conexion_origen->conexion) {
        echo json_encode(array('success'=>false, 'message'=>'No se pudo conectar a la base de datos origen'));
        exit();
    }
    
    $categorias = $obBD_con1->getArrayConsulta(1, array($Emp_Cod_Origen, 'D'), $obBD_conexion_origen);
    $obBD_conexion_origen->cerrar();
    
    echo json_encode(array('success'=>true, 'categorias'=>$categorias));
    exit();
}

// Ajax para cargar sucursales de la empresa origen
if (isset($loadSucursalesOrigen)) {
    if (empty($Dat_Dis) || empty($Emp_Cod_Origen)) {
        echo json_encode(array('success'=>false, 'message'=>'Debe seleccionar una base de datos y empresa origen'));
        exit();
    }
    
    // Crear conexión a la base de datos origen
    $obBD_conexion_origen = new Class_Log_Conexion_Pro($Dat_Dis);
    if (!$obBD_conexion_origen->conexion) {
        echo json_encode(array('success'=>false, 'message'=>'No se pudo conectar a la base de datos origen'));
        exit();
    }
    
    $sql = "SELECT Suc_Cod, Suc_Des FROM sucursal WHERE Emp_Cod = " . intval($Emp_Cod_Origen) . " AND Suc_Est = 'A' ORDER BY Suc_Des";
    $sucursales = $obBD_con1->getArrayConsultaSql($sql, $obBD_conexion_origen);
    $obBD_conexion_origen->cerrar();
    
    echo json_encode(array('success'=>true, 'sucursales'=>$sucursales));
    exit();
}

// Ajax para cargar sucursales de la empresa destino
if (isset($loadSucursalesDestino)) {
    $sql = "SELECT Suc_Cod, Suc_Des FROM sucursal WHERE Emp_Cod = " . intval($_SESSION['Ses_Emp_Cod']) . " AND Suc_Est = 'A' ORDER BY Suc_Des";
    $sucursales = $obBD_con1->getArrayConsultaSql($sql, $obBD_conexion);
    
    echo json_encode(array('success'=>true, 'sucursales'=>$sucursales));
    exit();
}

// Ajax para obtener productos de otra empresa
if (isset($productosOrigenAjax)) {
    // Necesitamos la base de datos para hacer la consulta
    if (!empty($_GET['Dat_Dis'])) {
        // Crear conexión a la base de datos origen
        $obBD_conexion_origen = new Class_Log_Conexion_Pro($_GET['Dat_Dis']);
        $obBD_con1->getPageGridJson(200, $_GET, $obBD_conexion_origen, true);
    } else {
        // Si no se especifica, usar la base de datos actual
        $obBD_con1->getPageGridJson(200, $_GET, $obBD_conexion, true);
    }
}

// Ajax para copiar productos
if (isset($copiarProductosAjax)) {
    // Configurar headers para JSON y evitar errores
    header('Content-Type: application/json; charset=utf-8');
    
    // Iniciar buffer de salida para capturar cualquier output inesperado
    ob_start();
    
    $response = array('success' => false, 'message' => '', 'copiados' => 0, 'existentes' => 0, 'errores' => 0);
    
    try {
        if (!isset($_POST['empresa_origen']) || empty($_POST['empresa_origen'])) {
            $response['message'] = 'Debe seleccionar una empresa origen';
            ob_end_clean();
            echo json_encode($response);
            exit;
        }
        
        if (!isset($_POST['Dat_Dis']) || empty($_POST['Dat_Dis'])) {
            $response['message'] = 'Debe seleccionar una base de datos';
            ob_end_clean();
            echo json_encode($response);
            exit;
        }
        
        if (!isset($_POST['categoria_destino']) || empty($_POST['categoria_destino'])) {
            $response['message'] = 'Debe seleccionar una categoría destino';
            ob_end_clean();
            echo json_encode($response);
            exit;
        }
        
        if (!isset($_POST['productos']) || empty($_POST['productos'])) {
            $response['message'] = 'Debe seleccionar al menos un producto';
            ob_end_clean();
            echo json_encode($response);
            exit;
        }
        
        $empresa_origen = intval($_POST['empresa_origen']);
        $empresa_destino = intval($_SESSION['Ses_Emp_Cod']);
        $dat_dis_origen = $_POST['Dat_Dis'];
        $categoria_destino = intval($_POST['categoria_destino']);
        $productos_ids = json_decode($_POST['productos'], true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $response['message'] = 'Error al procesar los datos de productos: ' . json_last_error_msg();
            ob_end_clean();
            echo json_encode($response);
            exit;
        }
        
        if ($empresa_origen == $empresa_destino) {
            $response['message'] = 'No puede copiar productos de la misma empresa';
            ob_end_clean();
            echo json_encode($response);
            exit;
        }
    
        // Crear conexión a la base de datos origen para leer los productos
        $obBD_conexion_origen = new Class_Log_Conexion_Pro($dat_dis_origen);
        if (!$obBD_conexion_origen->conexion) {
            throw new Exception("No se pudo conectar a la base de datos de origen: " . $obBD_conexion_origen->Error);
        }
        
        $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
        
        $copiados = 0;
        $existentes = 0;
        $errores = 0;
        $productos_existentes = array();
        
        foreach ($productos_ids as $pro_cod_origen) {
        try {
            // Obtener datos del producto origen desde la base de datos origen (incluyendo precios de la sucursal origen)
            $sql_producto_origen = "SELECT producto.*, item.*, categorias.Cat_Des, marca.Mar_Des, adquisicio.Adq_Des, iva.Iva_Por, ubicacion.Ubi_Des, unidad.Uni_Des, presentaci.Pre_Des, lineas.Lin_Des,
                                   precios.Pre_Pvp, precios.Pre_Des as Pre_Des_Nombre, precios.Tpv_Cod, tipo_preci.Tpv_Des
                                   FROM producto 
                                   INNER JOIN item ON producto.Ite_Cod = item.Ite_Cod 
                                   INNER JOIN categorias ON item.Cat_Cod = categorias.Cat_Cod
                                   LEFT JOIN marca ON producto.Mar_Cod = marca.Mar_Cod
                                   LEFT JOIN adquisicio ON producto.Adq_Cod = adquisicio.Adq_Cod
                                   LEFT JOIN iva ON producto.Iva_Cod = iva.Iva_Cod
                                   LEFT JOIN ubicacion ON producto.Ubi_Cod = ubicacion.Ubi_Cod
                                   LEFT JOIN unidad ON producto.Uni_Cod = unidad.Uni_Cod
                                   LEFT JOIN presentaci ON producto.Pre_Cod = presentaci.Pre_Cod
                                   LEFT JOIN lineas ON producto.Lin_Cod = lineas.Lin_Cod
                                   LEFT JOIN precios ON (producto.Pro_Cod = precios.Pro_Cod AND precios.Suc_Cod = " . $sucursal_origen . " AND precios.Pre_Est = 'A')
                                   LEFT JOIN tipo_preci ON (precios.Tpv_Cod = tipo_preci.Tpv_Cod AND tipo_preci.Tpv_Des = 'Standar')
                                   WHERE producto.Pro_Cod = " . intval($pro_cod_origen) . " 
                                   AND categorias.Emp_Cod = " . $empresa_origen;
            
            $result = mysqli_query($obBD_conexion_origen->conexion, $sql_producto_origen);
            if (!$result || mysqli_num_rows($result) == 0) {
                $errores++;
                continue;
            }
            
            $producto_data = mysqli_fetch_assoc($result);
            $nombre_producto = trim($producto_data['Ite_Lar']);
            $marca_cod_origen = $producto_data['Mar_Cod'];
            
            // Verificar si el producto ya existe (mismo Ite_Lar, misma Marca, misma Categoría)
            $sql_existe_producto = "SELECT producto.Pro_Cod FROM producto 
                                    INNER JOIN item ON (producto.Ite_Cod = item.Ite_Cod)
                                    WHERE item.Ite_Lar = '" . mysqli_real_escape_string($obBD_conexion->conexion, $producto_data['Ite_Lar']) . "' 
                                    AND producto.Mar_Cod = " . intval($marca_cod_origen) . "
                                    AND item.Cat_Cod = " . $categoria_destino;
            $result_producto = mysqli_query($obBD_conexion->conexion, $sql_existe_producto);
            
            if (mysqli_num_rows($result_producto) > 0) {
                $existentes++;
                $productos_existentes[] = $nombre_producto . ' (Marca: ' . ($producto_data['Mar_Des'] ?: 'NINGUNA') . ')';
                continue;
            }
            
            // Verificar/crear marca en empresa destino
            $mar_cod_destino = null;
            if ($marca_cod_origen) {
                $sql_marca = "SELECT Mar_Cod FROM marca WHERE Mar_Des = '" . mysqli_real_escape_string($obBD_conexion->conexion, $producto_data['Mar_Des']) . "' AND Emp_Cod = " . $empresa_destino;
                $result_marca = mysqli_query($obBD_conexion->conexion, $sql_marca);
                if (mysqli_num_rows($result_marca) > 0) {
                    $marca_row = mysqli_fetch_assoc($result_marca);
                    $mar_cod_destino = $marca_row['Mar_Cod'];
                } else {
                    // Crear marca si no existe
                    $sql_insert_marca = "INSERT INTO marca (Emp_Cod, Mar_Des) VALUES (" . $empresa_destino . ", '" . mysqli_real_escape_string($obBD_conexion->conexion, $producto_data['Mar_Des']) . "')";
                    if (mysqli_query($obBD_conexion->conexion, $sql_insert_marca)) {
                        $mar_cod_destino = mysqli_insert_id($obBD_conexion->conexion);
                    } else {
                        $mar_cod_destino = null; // Si falla, usar NULL
                    }
                }
            }
            
            // Verificar/crear unidad (las unidades son globales, no por empresa)
            $uni_cod_destino = $producto_data['Uni_Cod'];
            if ($uni_cod_destino) {
                $sql_unidad = "SELECT Uni_Cod FROM unidad WHERE Uni_Cod = " . intval($uni_cod_destino);
                $result_unidad = mysqli_query($obBD_conexion->conexion, $sql_unidad);
                if (mysqli_num_rows($result_unidad) == 0) {
                    $uni_cod_destino = null; // Si no existe, usar NULL
                }
            }
            
            // Verificar/crear ubicación
            $ubi_cod_destino = null;
            if ($producto_data['Ubi_Cod']) {
                $sql_ubicacion = "SELECT Ubi_Cod FROM ubicacion WHERE Ubi_Des = '" . mysqli_real_escape_string($obBD_conexion->conexion, $producto_data['Ubi_Des']) . "' AND Emp_Cod = " . $empresa_destino;
                $result_ubicacion = mysqli_query($obBD_conexion->conexion, $sql_ubicacion);
                if (mysqli_num_rows($result_ubicacion) > 0) {
                    $ubicacion_row = mysqli_fetch_assoc($result_ubicacion);
                    $ubi_cod_destino = $ubicacion_row['Ubi_Cod'];
                } else {
                    // Crear ubicación si no existe
                    $sql_insert_ubicacion = "INSERT INTO ubicacion (Emp_Cod, Ubi_Des, Ubi_Est) VALUES (" . $empresa_destino . ", '" . mysqli_real_escape_string($obBD_conexion->conexion, $producto_data['Ubi_Des']) . "', 'A')";
                    if (mysqli_query($obBD_conexion->conexion, $sql_insert_ubicacion)) {
                        $ubi_cod_destino = mysqli_insert_id($obBD_conexion->conexion);
                    } else {
                        $ubi_cod_destino = null;
                    }
                }
            }
            
            // Obtener código de IVA (buscar por porcentaje)
            $iva_cod_destino = null;
            if ($producto_data['Iva_Por'] !== null) {
                $sql_iva = "SELECT Iva_Cod FROM iva WHERE Iva_Por = " . floatval($producto_data['Iva_Por']) . " AND Iva_Est = 'A' LIMIT 1";
                $result_iva = mysqli_query($obBD_conexion->conexion, $sql_iva);
                if (mysqli_num_rows($result_iva) > 0) {
                    $iva_row = mysqli_fetch_assoc($result_iva);
                    $iva_cod_destino = $iva_row['Iva_Cod'];
                }
            }
            
            // Obtener adquisición (buscar por descripción)
            $adq_cod_destino = null;
            if ($producto_data['Adq_Cod']) {
                $sql_adq = "SELECT Adq_Cod FROM adquisicio WHERE Adq_Des = '" . mysqli_real_escape_string($obBD_conexion->conexion, $producto_data['Adq_Des']) . "' AND Adq_Est = 'A' LIMIT 1";
                $result_adq = mysqli_query($obBD_conexion->conexion, $sql_adq);
                if (mysqli_num_rows($result_adq) > 0) {
                    $adq_row = mysqli_fetch_assoc($result_adq);
                    $adq_cod_destino = $adq_row['Adq_Cod'];
                }
            }
            
            // Obtener presentación (buscar por descripción)
            $pre_cod_destino = null;
            if ($producto_data['Pre_Cod']) {
                $sql_pre = "SELECT Pre_Cod FROM presentaci WHERE Pre_Des = '" . mysqli_real_escape_string($obBD_conexion->conexion, $producto_data['Pre_Des']) . "' AND Pre_Est = 'A' LIMIT 1";
                $result_pre = mysqli_query($obBD_conexion->conexion, $sql_pre);
                if (mysqli_num_rows($result_pre) > 0) {
                    $pre_row = mysqli_fetch_assoc($result_pre);
                    $pre_cod_destino = $pre_row['Pre_Cod'];
                }
            }
            
            // Obtener línea (buscar por descripción en la empresa destino)
            $lin_cod_destino = null;
            if ($producto_data['Lin_Cod']) {
                $sql_lin = "SELECT Lin_Cod FROM lineas WHERE Lin_Des = '" . mysqli_real_escape_string($obBD_conexion->conexion, $producto_data['Lin_Des']) . "' AND Emp_Cod = " . $empresa_destino . " AND Lin_Est = 'A' LIMIT 1";
                $result_lin = mysqli_query($obBD_conexion->conexion, $sql_lin);
                if (mysqli_num_rows($result_lin) > 0) {
                    $lin_row = mysqli_fetch_assoc($result_lin);
                    $lin_cod_destino = $lin_row['Lin_Cod'];
                }
            }
            
            // Obtener secuencia para la categoría destino
            $row_sec = $obBD_con1->getRowConsulta(19, array($categoria_destino), $obBD_conexion);
            $pro_sec = 1;
            $pro_ide = 1;
            if ($row_sec && isset($row_sec['siguiente'])) {
                $pro_ide = $row_sec['siguiente'];
            }
            
            // Obtener código de categoría
            $row_cat = $obBD_con1->getRowConsulta(8, array($categoria_destino, $empresa_destino), $obBD_conexion);
            $cat_cdc = '1';
            if ($row_cat && isset($row_cat['Cat_Cdc'])) {
                $cat_cdc = $row_cat['Cat_Cdc'];
            }
            $pro_cdc = $cat_cdc . '.1';
            
            // Insertar item con la categoría destino
            $ite_cor = !empty($producto_data['Ite_Cor']) ? mysqli_real_escape_string($obBD_conexion->conexion, $producto_data['Ite_Cor']) : '';
            $ite_lar = mysqli_real_escape_string($obBD_conexion->conexion, $producto_data['Ite_Lar']);
            
            $sql_insert_item = "INSERT INTO item (Cat_Cod, Ite_Cor, Ite_Lar, Ite_Est) 
                               VALUES (" . $categoria_destino . ", '" . $ite_cor . "', '" . $ite_lar . "', 'A')";
            
            if (!mysqli_query($obBD_conexion->conexion, $sql_insert_item)) {
                $error_msg = mysqli_error($obBD_conexion->conexion);
                error_log("Error al insertar item: " . $error_msg . " - SQL: " . $sql_insert_item);
                $errores++;
                continue;
            }
            
            $ite_cod_destino = mysqli_insert_id($obBD_conexion->conexion);
            
            // Insertar producto
            $pro_obs = !empty($producto_data['Pro_Obs']) ? mysqli_real_escape_string($obBD_conexion->conexion, $producto_data['Pro_Obs']) : '';
            $pro_uni = !empty($producto_data['Pro_Uni']) ? floatval($producto_data['Pro_Uni']) : 1;
            $pro_dsc = !empty($producto_data['Pro_Dsc']) ? floatval($producto_data['Pro_Dsc']) : 0;
            $pro_bar = !empty($producto_data['Pro_Bar']) ? mysqli_real_escape_string($obBD_conexion->conexion, $producto_data['Pro_Bar']) : '';
            
            $mar_cod_sql = $mar_cod_destino ? intval($mar_cod_destino) : 'NULL';
            $iva_cod_sql = $iva_cod_destino ? intval($iva_cod_destino) : 'NULL';
            $adq_cod_sql = $adq_cod_destino ? intval($adq_cod_destino) : 'NULL';
            $ubi_cod_sql = $ubi_cod_destino ? intval($ubi_cod_destino) : 'NULL';
            $uni_cod_sql = $uni_cod_destino ? intval($uni_cod_destino) : 'NULL';
            $pre_cod_sql = $pre_cod_destino ? intval($pre_cod_destino) : 'NULL';
            $lin_cod_sql = $lin_cod_destino ? intval($lin_cod_destino) : 'NULL';
            
            $sql_insert_producto = "INSERT INTO producto (Ite_Cod, Mar_Cod, Iva_Cod, Pro_Ide, Pre_Cod, Pro_Obs, Pro_Est, Pro_Por, Adq_Cod, Ubi_Cod, Uni_Cod, Pro_Bar, Pro_Gen, Pro_Sec, Pro_Cdc, Pro_Uni, Pro_Dsc, Pro_Fec, Lin_Cod) 
                                   VALUES (
                                       " . $ite_cod_destino . ",
                                       " . $mar_cod_sql . ",
                                       " . $iva_cod_sql . ",
                                       " . $pro_ide . ",
                                       " . $pre_cod_sql . ",
                                       '" . $pro_obs . "',
                                       'A',
                                       NULL,
                                       " . $adq_cod_sql . ",
                                       " . $ubi_cod_sql . ",
                                       " . $uni_cod_sql . ",
                                       '" . $pro_bar . "',
                                       'G',
                                       " . $pro_sec . ",
                                       '" . $pro_cdc . "',
                                       " . $pro_uni . ",
                                       " . $pro_dsc . ",
                                       NULL,
                                       " . $lin_cod_sql . "
                                   )";
            
            if (mysqli_query($obBD_conexion->conexion, $sql_insert_producto)) {
                $pro_cod_destino = mysqli_insert_id($obBD_conexion->conexion);
                $copiados++;
                
                // Copiar precios de la sucursal origen a la sucursal destino
                if (!empty($producto_data['Pre_Pvp']) && $producto_data['Pre_Pvp'] > 0) {
                    // Obtener Tpv_Cod del tipo de precio "Standar" en la sucursal destino
                    $sql_tpv = "SELECT Tpv_Cod FROM tipo_preci WHERE Tpv_Des = 'Standar' AND Suc_Cod = " . $sucursal_destino . " LIMIT 1";
                    $result_tpv = mysqli_query($obBD_conexion->conexion, $sql_tpv);
                    $tpv_cod = 1; // Default
                    if ($result_tpv && mysqli_num_rows($result_tpv) > 0) {
                        $tpv_row = mysqli_fetch_assoc($result_tpv);
                        $tpv_cod = $tpv_row['Tpv_Cod'];
                    }
                    
                    $pre_pvp = floatval($producto_data['Pre_Pvp']);
                    $pre_des = !empty($producto_data['Pre_Des_Nombre']) ? mysqli_real_escape_string($obBD_conexion->conexion, $producto_data['Pre_Des_Nombre']) : 'Precio 1';
                    
                    $sql_insert_precio = "INSERT INTO precios (Pro_Cod, Pre_Pvp, Pre_Des, Suc_Cod, Tpv_Cod, Pre_Est) 
                                         VALUES (" . $pro_cod_destino . ", " . $pre_pvp . ", '" . $pre_des . "', " . $sucursal_destino . ", " . $tpv_cod . ", 'A')
                                         ON DUPLICATE KEY UPDATE Pre_Pvp = " . $pre_pvp;
                    
                    if (!mysqli_query($obBD_conexion->conexion, $sql_insert_precio)) {
                        error_log("Error al insertar precio: " . mysqli_error($obBD_conexion->conexion));
                    }
                }
                
                // No se copia el stock - se deja en 0 o se debe registrar manualmente después
            } else {
                $error_msg = mysqli_error($obBD_conexion->conexion);
                error_log("Error al insertar producto: " . $error_msg . " - SQL: " . $sql_insert_producto);
                $errores++;
            }
            
        } catch (Exception $e) {
            error_log("Excepción al copiar producto: " . $e->getMessage());
            $errores++;
        }
        }
        
        if ($errores == 0) {
            $obBD_con1->fin_transaccion($obBD_conexion->conexion);
            $response['success'] = true;
            
            // Construir mensaje detallado
            $mensaje = "";
            if ($copiados > 0) {
                $mensaje = "Se copiaron $copiados producto(s) correctamente a la empresa actual en la categoría seleccionada.";
            }
            
            if ($existentes > 0) {
                if ($copiados > 0) {
                    $mensaje .= "\n\n";
                }
                $mensaje .= "$existentes producto(s) ya estaban registrados en la empresa actual y no se copiaron para evitar duplicados.";
                
                if (count($productos_existentes) <= 10) {
                    $mensaje .= "\n\nProductos que ya existían:\n• " . implode("\n• ", $productos_existentes);
                } else {
                    $mensaje .= "\n\n(Se omitieron " . count($productos_existentes) . " productos que ya existían)";
                }
            }
            
            if ($copiados == 0 && $existentes > 0) {
                $mensaje = "Ningún producto fue copiado. Todos los productos seleccionados ya estaban registrados en la empresa actual.";
                if (count($productos_existentes) <= 10) {
                    $mensaje .= "\n\nProductos que ya existían:\n• " . implode("\n• ", $productos_existentes);
                }
            }
            
            $response['message'] = $mensaje;
        } else {
            $obBD_con1->rollBack($obBD_conexion->conexion);
            $mensaje = "Error al copiar productos.\n";
            if ($copiados > 0) {
                $mensaje .= "Se copiaron $copiados producto(s) antes del error.\n";
            }
            if ($existentes > 0) {
                $mensaje .= "$existentes producto(s) ya existían.\n";
            }
            $mensaje .= "Ocurrieron $errores error(es). La operación fue revertida.";
            $response['message'] = $mensaje;
        }
        
        $response['copiados'] = $copiados;
        $response['existentes'] = $existentes;
        $response['errores'] = $errores;
        $response['productos_existentes'] = $productos_existentes;
        
        // Cerrar conexión de origen
        if (isset($obBD_conexion_origen)) {
            $obBD_conexion_origen->cerrar();
        }
        
    } catch (Exception $e) {
        // Si hay algún error no capturado
        if (isset($obBD_con1) && $obBD_con1->inTransaction) {
            $obBD_con1->rollBack($obBD_conexion->conexion);
        }
        $response['success'] = false;
        $response['message'] = 'Error inesperado: ' . $e->getMessage();
        $response['errores'] = isset($errores) ? $errores : 0;
        error_log("Error general al copiar productos: " . $e->getMessage() . " - Trace: " . $e->getTraceAsString());
        
        // Cerrar conexión de origen en caso de error
        if (isset($obBD_conexion_origen)) {
            $obBD_conexion_origen->cerrar();
        }
    }
    
    // Limpiar cualquier output inesperado y enviar JSON
    ob_end_clean();
    echo json_encode($response);
    exit;
}
?>
<!DOCTYPE HTML>
<HTML>

<HEAD>
    <TITLE><?Php echo "Copiar Productos [EXA]"; ?></TITLE>
    <meta charset="utf-8">
    <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
</HEAD>

<BODY>

    <div class="panel panel-main">
        <div class="panel-heading exa-header">
            <h3 class="panel-title">&raquo; Copiar Productos de Otra Empresa</h3>
        </div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div id="lista" class="row">
                <div class="col-md-12">
                    <form id="frm_bus" name="frm_bus" class="form-horizontal normal">
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Seleccionar Empresa Origen y Categoría Destino</legend>
                            <div class="form-group">
                                <label class="col-sm-2 control-label label-xs">Base de Datos:</label>
                                <div class="col-sm-4">
                                    <select id="selBaseDatos" name="selBaseDatos" class="form-control input-xs">
                                        <option value="">-- Seleccione una base de datos --</option>
                                    </select>
                                </div>
                                <div class="col-sm-6">
                                    <label class="control-label label-xs">Empresa Destino (Actual):</label>
                                    <div class="form-control-static"><?php echo htmlspecialchars($_SESSION['Ses_Emp_Nom']); ?></div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label label-xs">Empresa Origen:</label>
                                <div class="col-sm-4">
                                    <select id="empresa_origen" name="empresa_origen" class="form-control input-xs" disabled>
                                        <option value="">-- Primero seleccione una base de datos --</option>
                                    </select>
                                </div>
                                <label class="col-sm-2 control-label label-xs">Categoría Destino:</label>
                                <div class="col-sm-4">
                                    <select id="categoria_destino" name="categoria_destino" class="form-control input-xs">
                                        <option value="">-- Cargando categorías --</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label label-xs">Categoría Origen:</label>
                                <div class="col-sm-4">
                                    <select id="categoria_origen" name="categoria_origen" class="form-control input-xs" disabled>
                                        <option value="">-- Primero seleccione una empresa origen --</option>
                                    </select>
                                </div>
                                <div class="col-sm-6">
                                    <small class="text-muted">Filtre por categoría de la empresa origen para ver solo productos de esa categoría</small>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label label-xs">Sucursal Origen:</label>
                                <div class="col-sm-4">
                                    <select id="sucursal_origen" name="sucursal_origen" class="form-control input-xs" disabled>
                                        <option value="">-- Primero seleccione una empresa origen --</option>
                                    </select>
                                </div>
                                <label class="col-sm-2 control-label label-xs">Sucursal Destino:</label>
                                <div class="col-sm-4">
                                    <select id="sucursal_destino" name="sucursal_destino" class="form-control input-xs">
                                        <option value="">-- Cargando sucursales --</option>
                                    </select>
                                </div>
                            </div>
                        </fieldset>

                        <fieldset class="exa-fieldset" id="fieldset_productos" style="display:none;">
                            <legend class="Titulos2">Búsqueda de Productos</legend>
                            <div class="form-group">
                                <label class="col-sm-2 control-label label-xs">Filtrar por:</label>
                                <div class="col-sm-4 radioset">
                                    <input id="rad_bb1" name="est_opciones" type="radio" value="a" checked="" onclick="setfocus(this.form.search)" /><label for="rad_bb1">Activo</label>
                                    <input id="rad_bb2" name="est_opciones" type="radio" value="i" onclick="setfocus(this.form.search)" /><label for="rad_bb2">Inactivo</label>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-sm-2 control-label label-xs">Búsqueda:</label>
                                <div class="col-sm-5">
                                    <div class="input-group">
                                        <input type="text" id="search" name="search" onkeydown="if (event.keyCode === 13) cargarProductos()" class="form-control input-xs" placeholder="Ingrese índice de búsqueda" autofocus="">
                                        <span class="input-group-btn">
                                            <button class="btn btn-success btn-xs" type="button" title="Buscar Producto" onclick="cargarProductos()"><span class="glyphicon glyphicon-search"></span> Buscar</button>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </fieldset>
                    </form>
                    
                    <div style="min-height:300px; margin-top: 20px;" id="div_grid">
                        <table id="Lis_Pro_Origen"></table>
                        <div id="Pag_Pro_Origen"></div>
                        <div style="padding-top: 10px; padding-bottom: 0px;">
                            <button type="button" onclick="copiarProductos()" class="btn btn-primary btn-sm" title="Copiar productos seleccionados" id="btn_copiar" disabled>
                                <i class="glyphicon glyphicon-copy"></i> <span>Copiar Productos Seleccionados</span>
                            </button>
                            <button type="button" onclick="seleccionarTodos()" class="btn btn-info btn-sm" title="Seleccionar todos">
                                <i class="glyphicon glyphicon-check"></i> <span>Seleccionar Todos</span>
                            </button>
                            <button type="button" onclick="deseleccionarTodos()" class="btn btn-info btn-sm" title="Deseleccionar todos">
                                <i class="glyphicon glyphicon-unchecked"></i> <span>Deseleccionar Todos</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        var baseDatosSeleccionada = null;
        var empresaOrigenSeleccionada = null;
        var categoriaDestinoSeleccionada = null;

        // Cargar categorías y sucursales destino al iniciar
        $(function() {
            // Cargar categorías destino
            $.get('?loadCategorias=1', function(response) {
                try {
                    var data = typeof response === 'string' ? JSON.parse(response) : response;
                    if (data.success && data.categorias) {
                        public $select = $('#categoria_destino');
                        $select.empty().append('<option value="">-- Seleccione una categoría --</option>');
                        $.each(data.categorias, function(i, cat) {
                            $select.append('<option value="' + cat.Cat_Cod + '">' + cat.Cat_Des + '</option>');
                        });
                    }
                } catch (e) {
                    console.error('Error al cargar categorías:', e);
                }
            }, 'json');
            
            // Cargar sucursales destino
            $.get('?loadSucursalesDestino=1', function(response) {
                try {
                    var data = typeof response === 'string' ? JSON.parse(response) : response;
                    if (data.success && data.sucursales) {
                        public $select = $('#sucursal_destino');
                        $select.empty().append('<option value="">-- Seleccione una sucursal --</option>');
                        $.each(data.sucursales, function(i, suc) {
                            $select.append('<option value="' + suc.Suc_Cod + '">' + suc.Suc_Des + '</option>');
                        });
                    }
                } catch (e) {
                    console.error('Error al cargar sucursales destino:', e);
                }
            }, 'json');
        });

        // Cuando se selecciona una categoría
        $('#categoria_destino').on('change', function() {
            categoriaDestinoSeleccionada = $(this).val();
            if (categoriaDestinoSeleccionada && empresaOrigenSeleccionada) {
                $('#fieldset_productos').show();
                $('#btn_copiar').prop('disabled', false);
            } else {
                $('#fieldset_productos').hide();
                $('#btn_copiar').prop('disabled', true);
            }
        });

        // Cuando se selecciona una empresa (usando Select2)
        $('#empresa_origen').on('select2:select', function (e) {
            var empresa_origen = $(this).val();
            if (empresa_origen) {
                empresaOrigenSeleccionada = empresa_origen;
                
                // Cargar categorías de la empresa origen
                $('#categoria_origen').prop('disabled', true).empty().append('<option value="">Cargando categorías...</option>');
                $.get('?loadCategoriasOrigen=1', { 
                    Dat_Dis: baseDatosSeleccionada, 
                    Emp_Cod_Origen: empresa_origen 
                }, function(response) {
                    try {
                        var data = typeof response === 'string' ? JSON.parse(response) : response;
                        if (data.success && data.categorias) {
                            public $select = $('#categoria_origen');
                            $select.empty().append('<option value="">-- Todas las categorías --</option>');
                            $.each(data.categorias, function(i, cat) {
                                $select.append('<option value="' + cat.Cat_Cod + '">' + cat.Cat_Des + '</option>');
                            });
                            $select.prop('disabled', false);
                        } else {
                            $('#categoria_origen').empty().append('<option value="">No hay categorías disponibles</option>').prop('disabled', true);
                        }
                    } catch (e) {
                        console.error('Error al cargar categorías origen:', e);
                        $('#categoria_origen').empty().append('<option value="">Error al cargar</option>').prop('disabled', true);
                    }
                }, 'json');
                
                // Cargar sucursales de la empresa origen
                $('#sucursal_origen').prop('disabled', true).empty().append('<option value="">Cargando sucursales...</option>');
                $.get('?loadSucursalesOrigen=1', { 
                    Dat_Dis: baseDatosSeleccionada, 
                    Emp_Cod_Origen: empresa_origen 
                }, function(response) {
                    try {
                        var data = typeof response === 'string' ? JSON.parse(response) : response;
                        if (data.success && data.sucursales) {
                            public $select = $('#sucursal_origen');
                            $select.empty().append('<option value="">-- Seleccione una sucursal --</option>');
                            $.each(data.sucursales, function(i, suc) {
                                $select.append('<option value="' + suc.Suc_Cod + '">' + suc.Suc_Des + '</option>');
                            });
                            $select.prop('disabled', false);
                        } else {
                            $('#sucursal_origen').empty().append('<option value="">No hay sucursales disponibles</option>').prop('disabled', true);
                        }
                    } catch (e) {
                        console.error('Error al cargar sucursales origen:', e);
                        $('#sucursal_origen').empty().append('<option value="">Error al cargar</option>').prop('disabled', true);
                    }
                }, 'json');
                
                if (categoriaDestinoSeleccionada) {
                    $('#fieldset_productos').show();
                    $('#btn_copiar').prop('disabled', false);
                    cargarProductos();
                }
            } else {
                $('#fieldset_productos').hide();
                $('#btn_copiar').prop('disabled', true);
            }
        });

        // Cuando se deselecciona la empresa
        $('#empresa_origen').on('select2:clear', function (e) {
            empresaOrigenSeleccionada = null;
            $('#categoria_origen').empty().append('<option value="">-- Primero seleccione una empresa origen --</option>').prop('disabled', true);
            $('#sucursal_origen').empty().append('<option value="">-- Primero seleccione una empresa origen --</option>').prop('disabled', true);
            $('#fieldset_productos').hide();
            $('#btn_copiar').prop('disabled', true);
        });
        
        // Cuando se selecciona una categoría origen, recargar productos
        $('#categoria_origen').on('change', function() {
            if (empresaOrigenSeleccionada && categoriaDestinoSeleccionada) {
                cargarProductos();
            }
        });

        function cargarProductos() {
            var empresa_origen = $('#empresa_origen').val();
            if (!empresa_origen) {
                alert('Debe seleccionar una empresa origen');
                return;
            }
            
            if (!baseDatosSeleccionada) {
                alert('Debe seleccionar una base de datos');
                return;
            }
            
            empresaOrigenSeleccionada = empresa_origen;
            $('#fieldset_productos').show();
            $('#btn_copiar').prop('disabled', false);
            
            var postData = $("#frm_bus").getData("productosOrigenAjax");
            postData['Emp_Cod_Origen'] = empresa_origen;
            postData['Dat_Dis'] = baseDatosSeleccionada;
            
            // Agregar filtro de categoría origen si está seleccionada
            var categoria_origen = $('#categoria_origen').val();
            if (categoria_origen) {
                postData['Cat_Cod_Origen'] = categoria_origen;
            }
            
            // Recargar el grid con los nuevos datos
            $("#Lis_Pro_Origen").jqGrid('setGridParam', {
                postData: postData
            }).trigger('reloadGrid');
        }

        function seleccionarTodos() {
            var ids = $("#Lis_Pro_Origen").jqGrid('getDataIDs');
            for (var i = 0; i < ids.length; i++) {
                $("#Lis_Pro_Origen").jqGrid('setSelection', ids[i], true);
            }
        }

        function deseleccionarTodos() {
            $("#Lis_Pro_Origen").jqGrid('resetSelection');
        }

        function copiarProductos() {
            var empresa_origen = $('#empresa_origen').val();
            var categoria_destino = $('#categoria_destino').val();
            var sucursal_origen = $('#sucursal_origen').val();
            var sucursal_destino = $('#sucursal_destino').val();
            
            if (!empresa_origen) {
                alert('Debe seleccionar una empresa origen');
                return;
            }
            
            if (!categoria_destino) {
                alert('Debe seleccionar una categoría destino');
                return;
            }
            
            if (!sucursal_origen) {
                alert('Debe seleccionar una sucursal origen');
                return;
            }
            
            if (!sucursal_destino) {
                alert('Debe seleccionar una sucursal destino');
                return;
            }
            
            var ids = $("#Lis_Pro_Origen").jqGrid('getGridParam', 'selarrrow');
            if (ids.length == 0) {
                alert('Debe seleccionar al menos un producto para copiar');
                return;
            }
            
            if (!confirm('¿Está seguro de copiar ' + ids.length + ' producto(s) a la empresa actual en la categoría seleccionada?\n\nSe copiarán los precios de la sucursal origen a la sucursal destino (si existen).\nEl stock NO se copiará.')) {
                return;
            }
            
            $('#btn_copiar').prop('disabled', true).html('<i class="glyphicon glyphicon-refresh glyphicon-spin"></i> Copiando...');
            
            $.ajax({
                url: '?copiarProductosAjax=1',
                type: 'POST',
                data: {
                    empresa_origen: empresa_origen,
                    Dat_Dis: baseDatosSeleccionada,
                    categoria_destino: categoria_destino,
                    sucursal_origen: sucursal_origen,
                    sucursal_destino: sucursal_destino,
                    productos: JSON.stringify(ids)
                },
                dataType: 'json',
                success: function(response) {
                    $('#btn_copiar').prop('disabled', false).html('<i class="glyphicon glyphicon-copy"></i> <span>Copiar Productos Seleccionados</span>');
                    
                    if (response && response.message !== undefined) {
                        var mensaje = response.message;
                        if (response.success) {
                            alert(mensaje.replace(/\n/g, '\n'));
                            cargarProductos(); // Recargar grid
                        } else {
                            alert('Error: ' + mensaje.replace(/\n/g, '\n'));
                        }
                    } else {
                        console.error('Respuesta inesperada:', response);
                        alert('Error: Respuesta inesperada del servidor. Revise la consola para más detalles.');
                    }
                },
                error: function(xhr, status, error) {
                    $('#btn_copiar').prop('disabled', false).html('<i class="glyphicon glyphicon-copy"></i> <span>Copiar Productos Seleccionados</span>');
                    
                    console.error('Error AJAX:', status, error);
                    console.error('Status Code:', xhr.status);
                    console.error('Response Text:', xhr.responseText);
                    
                    var errorMsg = 'Error al copiar los productos.';
                    try {
                        var jsonResponse = JSON.parse(xhr.responseText);
                        if (jsonResponse.message) {
                            errorMsg = jsonResponse.message;
                        }
                    } catch (e) {
                        if (xhr.responseText && xhr.responseText.trim().length > 0) {
                            errorMsg += '\n\nDetalles del servidor:\n' + xhr.responseText.substring(0, 500);
                        }
                    }
                    
                    alert(errorMsg + '\n\nPor favor, revise la consola (F12) para más detalles.');
                }
            });
        }

        $(function() {
            // Cargar bases de datos disponibles
            $.get('?loadBasesDatos=1', function(response) {
                try {
                    var data = typeof response === 'string' ? JSON.parse(response) : response;
                    if (data.success && data.bases) {
                        public $select = $('#selBaseDatos');
                        $select.empty().append('<option value="">-- Seleccione una base de datos --</option>');
                        $.each(data.bases, function(i, base) {
                            $select.append('<option value="' + base.Dat_Dis + '">' + base.Emp_Nom + '</option>');
                        });
                    }
                } catch (e) {
                    console.error('Error al cargar bases de datos:', e);
                }
            }, 'json');

            // Inicializar Select2 para el selector de base de datos
            $('#selBaseDatos').select2({
                placeholder: 'Seleccione una base de datos...',
                allowClear: true
            });

            // Cuando se selecciona una base de datos
            $('#selBaseDatos').on('select2:select', function (e) {
                var Dat_Dis = $(this).val();
                baseDatosSeleccionada = Dat_Dis;
                
                if (Dat_Dis) {
                    // Cargar empresas de esta base de datos
                    $('#empresa_origen').prop('disabled', true).empty().append('<option value="">Cargando empresas...</option>');
                    
                    $.get('?loadEmpresasByDB=1', { Dat_Dis: Dat_Dis }, function(response) {
                        try {
                            var data = typeof response === 'string' ? JSON.parse(response) : response;
                            if (data.success && data.empresas) {
                                public $select = $('#empresa_origen');
                                $select.empty().append('<option value="">-- Seleccione una empresa --</option>');
                                $.each(data.empresas, function(i, empresa) {
                                    var empresa_nombre = empresa.Emp_Nom || 'Empresa ' + empresa.Emp_Cod;
                                    var empresa_codigo = empresa.Emp_Cor || '';
                                    $select.append('<option value="' + empresa.Emp_Cod + '" data-empresa="' + 
                                                  empresa_nombre.replace(/"/g, '&quot;') + '">' + 
                                                  empresa_nombre + (empresa_codigo ? ' (' + empresa_codigo + ')' : '') + '</option>');
                                });
                                $select.prop('disabled', false);
                                
                                if ($('#empresa_origen').hasClass('select2-hidden-accessible')) {
                                    $('#empresa_origen').select2('destroy');
                                }
                                
                                $('#empresa_origen').select2({
                                    placeholder: 'Busque y seleccione una empresa...',
                                    allowClear: true
                                });
                            } else {
                                $('#empresa_origen').empty().append('<option value="">No hay empresas disponibles</option>').prop('disabled', true);
                                alert(data.message || 'No se encontraron empresas en esta base de datos');
                            }
                        } catch (e) {
                            console.error('Error al cargar empresas:', e);
                            alert('Error al cargar las empresas');
                            $('#empresa_origen').empty().append('<option value="">Error al cargar</option>').prop('disabled', true);
                        }
                    }, 'json');
                } else {
                    $('#empresa_origen').select2('destroy').empty().append('<option value="">-- Primero seleccione una base de datos --</option>').prop('disabled', true);
                    $('#fieldset_productos').hide();
                    $('#btn_copiar').prop('disabled', true);
                }
            });

            // Cuando se deselecciona la base de datos
            $('#selBaseDatos').on('select2:clear', function (e) {
                baseDatosSeleccionada = null;
                $('#empresa_origen').select2('destroy').empty().append('<option value="">-- Primero seleccione una base de datos --</option>').prop('disabled', true);
                $('#fieldset_productos').hide();
                $('#btn_copiar').prop('disabled', true);
            });

            // Inicio Grid para presentar los productos de la empresa origen
            $("#Lis_Pro_Origen").createGrid({
                url: '?productosOrigenAjax=1',
                postData: $("#frm_bus").getData("productosOrigenAjax"),
                height: 295,
                multiselect: true,
                colModel: [{
                        label: 'Cod.Int.',
                        name: 'Pro_Cod',
                        width: 30,
                        align: "left",
                        key: true
                    },
                    {
                        label: 'Categoría',
                        name: 'Cat_Des',
                        width: 80,
                        align: "left"
                    },
                    {
                        label: 'Desc. Larga',
                        name: 'Ite_Lar',
                        width: 200,
                        align: "left"
                    },
                    {
                        label: 'Desc. Corta',
                        name: 'Ite_Cor',
                        width: 80,
                        align: "left"
                    },
                    {
                        label: 'Detalle',
                        name: 'Pro_Obs',
                        width: 100,
                        align: "left"
                    },
                    {
                        label: 'Marca',
                        name: 'Mar_Des',
                        width: 80,
                        align: "left"
                    },
                    {
                        label: 'Ubicación',
                        name: 'Ubi_Des',
                        width: 80,
                        align: "left"
                    },
                    {
                        label: 'Unidad',
                        name: 'Uni_Des',
                        width: 60,
                        align: "left"
                    },
                ]
            }, false, "#Pag_Pro_Origen");
        });
    </script>
    <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
</BODY>

</HTML>
<?php
/**
 * Cierre de las conexiones
 */
$obBD_con1->liberar();
$obBD_conexion->cerrar();
?>

