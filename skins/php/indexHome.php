<?php
// Declaración de archivos necesarios
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require_once('../../Librerias/config.php/register_globals.php');
require_once('../../administrador/LOGICA/logica.php');

// require_once('../../administrador/LOGICA/TreeMenu.php');

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Adm($_SESSION['Ses_Dat_Dis']);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_Adm;


// Construir condición de perfiles (Par_Sql[0])
$cond_perfiles = '1=1'; // Valor por defecto
if (!empty($_SESSION['Ses_Lis_Per'])) {
    $perfiles = array();
    foreach ($_SESSION['Ses_Lis_Per'] as $item) {
        $perfiles[] = "perfiorgan.Per_Cod = " . intval($item);
    }
    if (!empty($perfiles)) {
        $cond_perfiles = implode(" OR ", $perfiles);
    }
}

// Construir condición adicional (Par_Sql[1]) — puedes usar otro filtro o también 1=1
$cond_extra = '1=1'; // Esto evita que se genere una condición vacía
// Crear arreglo con ambos parámetros
$parametros = array($cond_perfiles, $cond_extra);
// Consulta para obtener los procesos
$rs_procesos = $obBD_con1->consulta(sentencias_adm(118, $obBD_con1->parametros($parametros)),$obBD_conexion->conexion );
// Consulta el registro de la llave electrónica en vigencia
$llave = $obBD_con1->getArrayConsulta(119, $_SESSION['Ses_Emp_Cod'], $obBD_conexion);
// Consulta para obtener los procesos y asignar accesos directos
$accesosDir = $obBD_con1->consulta(sentencias_adm(120, $obBD_con1->parametros($parametros)),$obBD_conexion->conexion );
// Consulta registros de accesos directos
// $search = $obBD_con1->consulta(sentencias_adm(121, $_SESSION['Ses_Emp_Cod'], $_SESSION['Ses_Prv_Cod'],$obBD_conexion));
// $AccDirInsert = $obBD_con1->consulta(sentencias_adm(122,));
// consulta los documentos electronicos activos de la empresa - sucursal
$docElect = $obBD_con1->getArrayConsulta(123,  $_SESSION['Ses_Suc_Cod'] . '*' . $_SESSION['Ses_Prs_Cod'], $obBD_conexion);

// Obtiene el Tic_Cod y Pun_Cod de los documentos electrónicos
$ticCods = array();
$punCods = array();
if (is_array($docElect)) {
    foreach ($docElect as $row) {
        if (isset($row['Tic_Cod'])) {
            $ticCods[] = $row['Tic_Cod'];
        }
        if (isset($row['Pun_Cod'])) {
            $punCods[] = $row['Pun_Cod'];
        }
    }
}

// Muestra la cantidad de documentos electrónicos que tiene la empresa - sucursal
$docCount = $obBD_con1->getArrayConsulta(124,  array($ticCods, $punCods, $_SESSION['Ses_Prs_Cod'],$_SESSION['Ses_Suc_Cod']), $obBD_conexion);
// enrutamiento de boton registrar ventas
$hrefRuta = '';
$total = $obBD_con1->num_rows($rs_procesos);

if ($total > 0) {
    while ($fila = $obBD_con1->fetch_array($rs_procesos)) {
        $ruta = trim($fila['Rut_Des']) . trim($fila['Pcs_Nom']);

        // Si solo hay un registro, usa ese
        if ($total == 1) {
            $hrefRuta = $ruta;
        } else {
            // Si hay más de uno, busca el específico (por ejemplo, el que contiene "fac_alt_fac_ven_3.2.php")
            if (strpos($fila['Pcs_Nom'], 'fac_alt_fac_ven_3.2.php') !== false) {
                $hrefRuta = $ruta;
                break;
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="es">
<!-- <html> -->
    <head>
        <title>ACERCA DE EXA</title>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <!-- <link rel="stylesheet" href="../../skins/css/bootstrap.min.css" /> -->
        <link rel="stylesheet" href="../../framework/jquery/bootstrap/bootstrap-3.3.5/css/tooltip.min.css" />
        <link rel="stylesheet" href="../../framework/plugins/fonts/font-awesome/font-awesome-4.4.0/css/font-awesome.min.css" />
        <link rel="stylesheet" href="../../skins/fonts/fontelo/fontello.css?x=0" />
        <link rel="stylesheet" href="../../skins/css/estilo-index.css"/>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700;800&display=swap" rel="stylesheet">
        <!-- text fonts -->
        <link rel="stylesheet" href="../../skins/css/ace-fonts.css" />
        <!-- exa styles -->
        <link id="pagestyle" href="../../skins/css/exa3.css" rel="stylesheet" />
        <script type="text/javascript">
            window.jQuery || document.write("<script src='../../skins/js/jquery.js'>" + "<" + "/script>");
        </script><!-- <![endif]-->
    </head>
    <!-- <body bgcolor="#FFFFFF" leftmargin="0" topmargin="0" marginwidth="0" marginheight="0" style="text-align:center; background: url('../img/Fondo-Exa-1.gif') no-repeat;background-size: cover;"> -->
    <body bgcolor="#FFFFFF" leftmargin="0" topmargin="0" marginwidth="0" marginheight="0" class="home-body">
        <div class="col-xl-12 col-sm-12 mb-xl-0 mb-4 text-center home-welcome-header" style="color: white; display: flex; align-items: center; justify-content: center; margin-top: 35px; flex-wrap: wrap; flex-shrink: 0;">
            <img src="../../imagenes/ingresar/favicon.png" alt="" class="img-fluid" width="50" style="margin-top: -15px; margin-right: 12px;">
            <h1 class="mb-3 home-welcome-title" style="margin: 0; font-size: clamp(1.25rem, 4vw, 2.5rem); line-height: 1.2;">
                Sistema Integral de Gesti&oacute;n Operativa y Contable
            </h1>
        </div>

        <style>
            .home-body {
                text-align: center;
                background: url('../../mascaras/model1/img/logo/backgroundHome.png') no-repeat;
                background-size: cover;
                background-position: center top;
                background-attachment: fixed;
                position: relative;
                overflow-y: auto;
                min-height: 100vh;
                display: flex;
                flex-direction: column;
                margin: 0;
            }
            @media (max-width: 768px) {
                .home-body {
                    background-position: center 15% !important;
                    background-attachment: scroll;
                    background-size: cover !important;
                    background-repeat: no-repeat !important;
                }
                .home-welcome-header {
                    margin-top: 15px !important;
                }
                .container-fluid {
                    margin-top: 180px !important; /* Desplaza los botones hacia abajo para dejar ver el logo */
                }
                .custom-btn {
                    width: 85% !important;
                    margin: 0 auto 12px auto !important;
                }
            }
            .home-welcome-title {
                color: #1766af !important;
                font-family: 'Montserrat', 'Segoe UI', 'Helvetica Neue', Arial, sans-serif !important;
                font-weight: 800 !important;
                letter-spacing: 0.03em;
                text-shadow: 0 1px 2px rgba(0, 0, 0, 0.14);
            }
            .custom-btn {
                display: flex;
                align-items: center;
                justify-content: center;
                width: 350px;
                padding: 8px 0;
                background: #e3e3e3;
                color: #000000 !important;
                border: none;
                border-radius: 8px;
                font-size: 1.15rem;
                font-family: 'Segoe UI', 'Arial', sans-serif;
                font-weight: 600;
                box-shadow: 0 4px 16px rgba(78, 84, 200, 0.15);
                transition: background 0.2s, transform 0.2s, box-shadow 0.2s;
                text-decoration: none;
                margin: 0 10px 10px 10px;
                max-width: 98vw;
            }
            .custom-btn:hover {
                /* background: linear-gradient(90deg, #2746a2 0%, #12188e 100%); */
                background: linear-gradient(90deg, rgb(81, 180, 237) 100%);
                transform: translateY(-2px) scale(1.03);
                box-shadow: 0 8px 24px rgba(78, 84, 200, 0.22);
                color: #fff !important;
                text-decoration: none;
            }
            .custom-btn i {
                margin-right: 8px;
                font-size: 1.2em;
            }
            @media (max-width: 900px) {
                .col-xl-12.text-center {
                    flex-direction: column !important;
                    align-items: center !important;
                }
                .col-xl-12.text-center h1 {
                    font-size: 28px !important;
                    margin-top: 10px;
                }
            }
            @media (max-width: 600px) {
                .row.menu-btns {
                    flex-direction: column !important;
                    align-items: center !important;
                    gap: 0 !important;
                }
                .custom-btn {
                    width: 90vw;
                    min-width: 180px;
                    max-width: 98vw;
                    font-size: 1rem;
                    margin: 0 0 14px 0;
                }
                .col-xl-12.text-center h1 {
                    font-size: 20px !important;
                }
                .col-xl-12.text-center img {
                    margin-right: 0 !important;
                }
            }

            @media (max-width: 991px) {
                .row.justify-center {
                    flex-direction: column !important;
                    align-items: center !important;
                    gap: 24px !important;
                }
                .col-md-6.col-12 {
                    justify-content: center !important;
                    margin-bottom: 18px;
                    max-width: 98vw !important;
                }
            }
        </style>

        <div class="home-shortcuts-middle" style="flex: 1; display: flex; flex-direction: column; justify-content: center; min-height: 0; width: 100%;">
        <fieldset class="scheduler-border">
            <legend class="scheduler-border" style="color: white; font-size: 1.2rem; font-weight: 600; margin-left: 130px; margin-bottom: 20px; text-align: left;">Mis Accesos Directos</legend>
            <div class="container-fluid">
                <div class="row menu-btns justify-center" style="display: flex; justify-content: center; margin-top: 0; flex-wrap: wrap;">
                    <div class="col-xl-3 col-sm-5 col-12 mb-xl-0 mb-4" style="display: flex; justify-content: center;">
                        <a class="custom-btn btn bg-white me-2 text-secondary shadow w-100 fs-6" href="<?php echo htmlspecialchars($hrefRuta); ?>" id="ventas-link">
                            <i class="fa fa-credit-card text-primary"></i> Registrar Ventas
                        </a>
                    </div>
                    <div class="col-xl-3 col-sm-6 col-12 mb-xl-0 mb-4" style="display: flex; justify-content: center;">
                        <a class="custom-btn btn bg-white me-2 text-secondary shadow w-100 fs-6" href="/tesoreria/FRONT/tes_alt_cliente_1.0.php">
                            <i class="fa fa-user text-primary"></i> Registrar Clientes
                        </a>
                    </div>
                    <?php if (isset($_SESSION['Ses_Prs_Cod']) && $_SESSION['Ses_Prs_Cod'] == 1): ?>
                        <div class="col-xl-3 col-sm-6 col-12 mb-xl-0 mb-4" style="display: flex; justify-content: center;">
                            <a class="custom-btn btn bg-white me-2 text-secondary shadow w-100 fs-6" href="/contabilidad/FRONT/con_alt_autorizaci_2.0.php"> <!-- REGISTRO PARA ADMINISTRADOR -->
                                <i class="fa fa-archive text-primary"></i> Registrar Autorizaciones
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="col-xl-3 col-sm-6 col-12 mb-xl-0 mb-4" style="display: flex; justify-content: center;">
                            <a class="custom-btn btn bg-white me-2 text-secondary shadow w-100 fs-6" href="/contabilidad/FRONT/con_alt_autorizaciusu_2.0.php"> <!-- REGISTRO PARA CLIENTES -->
                                <i class="fa fa-archive text-primary"></i> Registrar Autorizaciones
                            </a>
                        </div>
                    <?php endif; ?>
                    <div class="col-xl-3 col-sm-6 col-12 mb-xl-0 mb-4" style="display: flex; justify-content: center;">
                        <a class="custom-btn btn bg-white me-2 text-secondary shadow w-100 fs-6" href="../../facturacion/FRONT/fac_alt_aut_sri_1.php">
                            <i class="fa fa-send text-primary"></i>  Documentos Autorizados
                        </a>
                    </div>

                    <?php
                    // Generar estructura de accesos directos agrupados por Org_Des y Pcs_Lin
                    $accesosAgrupados = array();
                    if ($obBD_con1->num_rows($accesosDir) > 0) {
                        while ($row = $obBD_con1->fetch_array($accesosDir)) {
                            // Convertir cada campo a ISO-8859-1 para mostrar correctamente las comas y caracteres especiales
                            $org = mb_convert_encoding($row['Org_Des'], 'ISO-8859-1', 'UTF-8');
                            $lin = mb_convert_encoding($row['Pcs_Lin'], 'ISO-8859-1', 'UTF-8');
                            $acceso = array(
                                'Pcs_Cod' => $row['Pcs_Cod'],
                                'Org_Cod' => $row['Org_Cod'],
                                'Org_Des' => $row['Org_Des'],
                                'Pcs_Lin' => $row['Pcs_Lin'],
                                'Rut_Des' => $row['Rut_Des'],
                                'Pcs_Nom' => $row['Pcs_Nom'],
                                'Pcs_Ico' => $row['Pcs_Ico'],
                            );
                            if (!isset($accesosAgrupados[$org])) {
                                $accesosAgrupados[$org] = array();
                            }
                            $accesosAgrupados[$org][] = $acceso;
                        }
                    }
                    ?>
                    <div class="row" id="shortcut-row-2" style="flex-wrap: wrap; justify-content: center; text-align: center;"></div>
                    <div class="row" style="display: flex; justify-content: center;">
                        <div class="col-xl-3 col-sm-6 col-6 mb-xl-0 mb-4" style="display: flex; justify-content: center;">
                            <button class="custom-btn btn bg-white me-2 text-secondary shadow w-30 fs-6" id="addShortcutBtn" type="button">
                                <i class="fa fa-plus text-prima" style="color: #a02525;"></i> Add
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </fieldset>
        </div>

        <!-- Modal para seleccionar acceso directo -->
        <div class="modal" tabindex="-1" id="shortcutModal" style="display:none;position:fixed;z-index:9999;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.25);align-items:center;justify-content:center;">
            <div style="background:#fff;padding:28px 24px;border-radius:12px;max-width:420px;width:96vw;box-shadow:0 4px 24px rgba(0,0,0,0.18);position:relative;">
                <button type="button" id="closeShortcutModal" style="position:absolute;top:10px;right:12px;background:none;border:none;font-size:1.5em;color:#a02525;cursor:pointer;">×</button>
                <h4 style="margin-bottom:18px;font-size:1.15em;font-weight:bold;color:#2746a2;">Agregar acceso directo</h4>
                <form id="shortcutForm">
                    <label for="shortcutSelect" style="font-weight:600;">Seleccione un acceso:</label>
                    <select id="shortcutSelect" class="form-control" style="width:100%;margin-bottom:18px;">
                        <option value=""><< Seleccione >></option>
                        <?php foreach ($accesosAgrupados as $org => $items): ?>
                            <optgroup label="<?php echo htmlspecialchars($org, ENT_QUOTES, 'ISO-8859-1'); ?>">
                                <?php foreach ($items as $item): ?>
                                    <option 
                                        value="<?php echo htmlspecialchars(json_encode(array(
                                            'url' => trim($item['Rut_Des']) . trim($item['Pcs_Nom']),
                                            'icon' => $item['Pcs_Ico'],
                                            'label' => $item['Pcs_Lin'],
                                            'pcs_cod' => $item['Pcs_Cod'],
                                        )), ENT_QUOTES, 'ISO-8859-1'); ?>"
                                        data-pcs-cod="<?php echo htmlspecialchars($item['Pcs_Cod']); ?>"
                                    >
                                        <?php echo htmlspecialchars($item['Pcs_Lin'], ENT_QUOTES, 'ISO-8859-1'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="custom-btn" style="width:100%;margin-top:8px;">Agregar</button>
                </form>
            </div>
        </div>

        <script type="text/javascript">
            (function() {
                var addedShortcuts = 0;
                var maxShortcuts = 4;
                var rowIndex = 2;
                var addBtn = document.getElementById('addShortcutBtn');
                var modal = document.getElementById('shortcutModal');
                var closeModalBtn = document.getElementById('closeShortcutModal');
                var shortcutForm = document.getElementById('shortcutForm');
                var shortcutSelect = document.getElementById('shortcutSelect');
                // Para controlar los accesos ya usados
                var usedPcsCods = [];

                function updateAddBtnVisibility() {
                    addBtn.style.display = (addedShortcuts >= maxShortcuts) ? 'none' : '';
                }

                function showModal() {
                    modal.style.display = 'flex';
                    shortcutSelect.value = '';
                    // Deshabilitar opciones ya usadas
                    var opts = shortcutSelect.options;
                    for (var i = 0; i < opts.length; i++) {
                        var opt = opts[i];
                        if (opt.getAttribute('data-pcs-cod') && usedPcsCods.indexOf(opt.getAttribute('data-pcs-cod')) !== -1) {
                            opt.disabled = true;
                        } else {
                            opt.disabled = false;
                        }
                    }
                }
                function hideModal() {
                    modal.style.display = 'none';
                }

                addBtn.onclick = function() {
                    showModal();
                };
                closeModalBtn.onclick = function() {
                    hideModal();
                };
                modal.onclick = function(e) {
                    if (e.target === modal) hideModal();
                };

                shortcutForm.onsubmit = function(e) {
                    if (e.preventDefault) e.preventDefault();
                    else e.returnValue = false;
                    var val = shortcutSelect.value;
                    if (!val) return false;
                    var data = JSON.parse(val);
                    // Si ya está usado, no permitir
                    if (usedPcsCods.indexOf(String(data.pcs_cod)) !== -1) return false;

                    // Find the last row with shortcuts
                    var currentRow = document.getElementById('shortcut-row-' + rowIndex);
                    if (!currentRow) {
                        currentRow = document.createElement('div');
                        currentRow.className = 'row';
                        currentRow.id = 'shortcut-row-' + rowIndex;
                        var addBtnRow = addBtn.parentNode.parentNode;
                        addBtnRow.parentNode.insertBefore(currentRow, addBtnRow);
                    }
                    var currentShortcuts = currentRow.querySelectorAll('.col-xl-3').length;
                    if (currentShortcuts >= 4) {
                        rowIndex++;
                        currentRow = document.createElement('div');
                        currentRow.className = 'row';
                        currentRow.id = 'shortcut-row-' + rowIndex;
                        var addBtnRow2 = addBtn.parentNode.parentNode;
                        addBtnRow2.parentNode.insertBefore(currentRow, addBtnRow2);
                    }

                    var div = document.createElement('div');
                    div.className = "col-xl-3 col-sm-6 col-6 mb-xl-0 mb-4 shortcut-btn-wrapper";
                    div.style.position = "relative";
                    div.style.display = "flex";
                    div.style.justifyContent = "center";
                    div.innerHTML = ''
                        + '<a class="custom-btn btn bg-white me-2 text-secondary shadow w-100 fs-6" style="position: relative;" href="' + data.url + '" target="_blank">'
                        + '<i class="' + (data.icon ? data.icon : 'fa fa-star') + ' text-primary"></i> ' + data.label
                        + '</a>'
                        + '<span class="shortcut-remove-x" title="Eliminar" style="display:none; position:absolute;top:-4px; right:10px; z-index:10; font-size:1.3em;color:#e53935;cursor:pointer;background:#fff;border-radius:50%;width:24px;height:24px;align-items:center;justify-content:center;line-height:22px;text-align:center;font-weight:bold;box-shadow:0 1px 4px rgba(0,0,0,0.08);">×</span>';
                    currentRow.appendChild(div);

                    // Mostrar la X al hacer hover
                    div.onmouseenter = function() {
                        div.querySelector('.shortcut-remove-x').style.display = 'flex';
                    };
                    div.onmouseleave = function() {
                        div.querySelector('.shortcut-remove-x').style.display = 'none';
                    };

                    // Eliminar shortcut al hacer click en la X
                    div.querySelector('.shortcut-remove-x').onclick = function(ev) {
                        if (!ev) ev = window.event;
                        if (ev.stopPropagation) ev.stopPropagation();
                        else ev.cancelBubble = true;
                        div.parentNode.removeChild(div);
                        addedShortcuts--;
                        // Quitar del array de usados
                        var idx = usedPcsCods.indexOf(String(data.pcs_cod));
                        if (idx !== -1) usedPcsCods.splice(idx, 1);
                        updateAddBtnVisibility();
                    };

                    addedShortcuts++;
                    updateAddBtnVisibility();
                    hideModal();
                    return false;
                };

                updateAddBtnVisibility();
            })();
        </script>
        
        <div class="container-fluid home-bottom-cards" style="margin-top: 30px; flex-shrink: 0;">
            <div class="row justify-center" style="display: flex; justify-content: center; align-items: flex-start; gap: 32px; margin-bottom: 32px;">
                <!-- Contenedor 1 -->
                <div class="col-md-6 col-12" style="display: flex; align-items: stretch; justify-content: flex-end; min-width: 260px; max-width: 480px;">
                    <!-- Tarjeta de Información de la Llave Electrónica -->
                    <div style="background: #e6e6e6; border-radius: 18px; box-shadow: 0 4px 18px rgba(39,70,162,0.10); padding: 32px 28px; display: flex; align-items: center; width: 100%; min-width: 260px; max-width: 420px;">
                        <div>
                            <p style="margin: 0 0 8px 0; font-weight: bold; color: #a02525; font-size: 16px;">Información de la Llave Electrónica</p>
                            <div style="font-size: 17px; color: #222; margin-bottom: 12px;">
                                <div style="margin-bottom: 10px; display: flex; align-items: center; flex-direction: column; align-items: flex-start;">
                                    <strong style="min-width: 60px; text-align: left; margin-right: 8px; text-decoration: underline;">Llave:</strong>
                                    <span id="llaveRut" style="letter-spacing:1px; font-size: 11px;">
                                        <?php echo isset($llave[0]['Lla_Rut']) ? htmlspecialchars($llave[0]['Lla_Rut']) : 'No disponible'; ?>
                                    </span>
                                </div>
                                <div style="text-align: left;">
                                    <strong style="text-decoration: underline;">Caducidad:</strong>
                                    <span id="llaveCad">
                                        <?php echo isset($llave[0]['Lla_Cad']) ? htmlspecialchars($llave[0]['Lla_Cad']) : 'No disponible'; ?>
                                    </span>
                                </div>
                                <?php
                                if (isset($llave[0]['Lla_Cad']) && !empty($llave[0]['Lla_Cad'])) {
                                    $fechaCaducidad = DateTime::createFromFormat('Y-m-d', $llave[0]['Lla_Cad']);
                                    $fechaHoy = new DateTime();
                                    if ($fechaCaducidad !== false) {
                                        $diasRestantes = $fechaHoy->diff($fechaCaducidad)->format('%r%a');
                                        echo '<div style="margin-top: 6px; text-align: left;">';
                                        if ($diasRestantes >= 0) {
                                            echo '<span style="color:#222; font-weight:bold; text-decoration: underline;">Días restantes:</span> <span style="color:#27ae60; font-weight:bold;"> ' . $diasRestantes . '</span>';
                                        } else {
                                            echo '<span style="color:#c0392b; font-weight:bold; text-align: center; text-decoration: underline;"> * Su firma ha expirado * </span>';
                                        }
                                        echo '</div>';
                                    }
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Contenedor 2 -->
                <div class="col-md-6 col-12" style="display: flex; align-items: stretch; justify-content: center; min-width: 260px; max-width: 480px;">
                    <!-- Tarjeta Informativa de Documentos Electrónicos -->
                    <div style="background: #e6e6e6; border-radius: 18px; box-shadow: 0 4px 18px rgba(39,70,162,0.10); padding: 36px 28px; display: flex; align-items: center; width: 100%; min-width: 260px; max-width: 420px;">
                        <div style="width:100%;">
                            <p style="margin: 0 0 8px 0; font-weight: bold; color: #a02525; font-size: 16px;">Información de Documentos Electrónicos</p>
                            <?php
                            // Prepara los datos para JS
                            $docData = array();
                            if (is_array($docCount) && count($docCount) > 0) {
                                foreach ($docCount as $row) {
                                    $docData[] = array(
                                        'Tic_Cod' => $row['Tic_Cod'],
                                        'Tic_Des' => $row['Tic_Des'],
                                        'Aut_Fin' => $row['Aut_Fin'],
                                        'Hecho'   => isset($row['Hecho']) ? $row['Hecho'] : '-',
                                        'Faltantes' => isset($row['Faltantes']) ? $row['Faltantes'] : '-',
                                    );
                                }
                            }
                            ?>
                            <div style="margin-bottom: 12px;">
                                <label for="docSelect" style="font-weight:600; font-size: 15px;">Documento:</label>
                                <select id="docSelect" class="form-control" style="width:100%; height:35px; max-width:260px; font-size:12px; display:inline-block; appearance:auto; -webkit-appearance:auto; -moz-appearance:auto;">
                                    <?php if (!empty($docData)): ?>
                                        <?php foreach ($docData as $i => $row): ?>
                                            <option value="<?php echo $i; ?>"><?php echo htmlspecialchars($row['Tic_Des']); ?></option>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <option value="">No hay documentos electrónicos</option>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <table style="width:100%; font-size:15px; color:#222; border-collapse:collapse;">
                                <thead>
                                    <tr style="background:#e3e3e3;">
                                        <th style="padding:6px 8px;">Block Fin</th>
                                        <th style="padding:6px 8px;">Hecho</th>
                                        <th style="padding:6px 8px;">Faltantes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($docData)): ?>
                                        <tr id="docDataRow">
                                            <td style="padding:5px 8px;" id="tdAutFin"><?php echo htmlspecialchars($docData[0]['Aut_Fin']); ?></td>
                                            <td style="padding:5px 8px;" id="tdHecho"><?php echo htmlspecialchars($docData[0]['Hecho']); ?></td>
                                            <td style="padding:5px 8px;" id="tdFaltantes"><?php echo htmlspecialchars($docData[0]['Faltantes']); ?></td>
                                        </tr>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3" style="padding:8px; text-align:center; color:#888;">No hay documentos electrónicos.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <script type="text/javascript">
                    // Datos de documentos electrónicos para el select
                    var docData = <?php echo json_encode($docData); ?>;
                    var docSelect = document.getElementById('docSelect');
                    if (docSelect && docData.length > 0) {
                        docSelect.addEventListener('change', function() {
                            var idx = parseInt(this.value, 10);
                            if (!isNaN(idx) && docData[idx]) {
                                document.getElementById('tdAutFin').textContent = docData[idx]['Aut_Fin'];
                                document.getElementById('tdHecho').textContent = docData[idx]['Hecho'];
                                document.getElementById('tdFaltantes').textContent = docData[idx]['Faltantes'];
                            }
                        });
                    }
                </script>
                <!-- Contenedor 3 -->
                <div style="background: #e6e6e6; border-radius: 18px; box-shadow: 0 4px 18px rgba(39,70,162,0.10); padding: 36px 28px; display: flex; align-items: center; width: 100%; min-width: 260px; max-width: 420px;">
                    <!-- Tarjeta Informativa de EXA -->
                    <div>
                        <p style="margin: 0 0 8px 0; font-weight: bold; color: #222; font-size: 15px;">Creado por desarrolladores para contadores</p>
                        <div style="font-size: 21px; font-weight: bold; color: #a02525; margin-bottom: 10px; text-decoration: underline;">Exa Contable Está Evolucionando</div>
                        <div style="color: #444; font-size: 15px; line-height: 1.5; text-align: justify;">
                            Traemos para ti el mejor sistema contable del mercado actual, con muchas funciones y ventajas.
                        </div>
                    </div>
                    <div style="margin-left: 24px;">
                        <div style="background: #222; border-radius: 16px; width: 90px; height: 90px; display: flex; align-items: center; justify-content: center;">
                            <span style="font-size: 44px; color: #fff;">🚀</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
