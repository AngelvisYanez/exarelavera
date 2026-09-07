<?php
/**
 * Módulo de Mapeo Interactivo - Relavera Comunitaria El Tablón
 * Redirección principal al FRONT del módulo
 * @package mapeo
 */
if (!isset($_SESSION)) {
    session_start();
}
header('Location: FRONT/map_alt_mapeo_interactivo.php');
exit;
