<?php

/**
 * Descripción: Página de inicio del sistema
 * Fecha de actualización: 2012-11-25
 * Desarrollador: Lewis Chimarro
 * Fecha de actualización: 2013-08-12
 * Desarrollador: Lewis Chimarro
 */
require_once('Librerias/config.php/register_globals.php');
require_once('Librerias/procedimientos/almacenados_standar.php');
require_once('administrador/LOGICA/adm_log_login.php');

/**
 * Variable el tipo de navegador 
 */
$Browser = detectar_acceso();
/**
 * URL de acceso al sistema WAP 
 */
$wmlredirect = "../movil/FRONT/mov_pag_inicial.php"; // URL ABSOLUTO para su archivo VML  

if ($Browser == "WML") {
    header("Location: " . $wmlredirect);
    exit;
}

/* Ajax para identificar el numero de empresas */
if (isset($_POST['ajax_empresas2'])) {
    $obBD_conexion = new Class_Log_Conexion_Log;
    $obBD_con1 = new Class_Log_Datos_Log;
    $ajax_username = isset($_POST['ajax_username']) ? trim($_POST['ajax_username']) : '';
    $rs_empresas = $obBD_con1->getArrayConsulta(1, $ajax_username, $obBD_conexion);
    utf8_encode_deep($rs_empresas);
    $conteo = count($rs_empresas);
    $html = '';
    if ($conteo > 0) {
        if ($conteo > 1) {
            $html = "<option value=''></option>";
            foreach ($rs_empresas as $row_rs_empresas) {
                $sucBr = isset($row_rs_empresas['Suc_Cod']) ? (int) $row_rs_empresas['Suc_Cod'] : 0;
                $empNom = htmlspecialchars(isset($row_rs_empresas['Emp_Nom']) ? $row_rs_empresas['Emp_Nom'] : '', ENT_QUOTES, 'UTF-8');
                $empCor = htmlspecialchars(!empty($row_rs_empresas['Emp_Cor']) ? $row_rs_empresas['Emp_Cor'] : (isset($row_rs_empresas['Emp_Nom']) ? $row_rs_empresas['Emp_Nom'] : ''), ENT_QUOTES, 'UTF-8');
                $sucDes = htmlspecialchars(isset($row_rs_empresas['Suc_Des']) ? $row_rs_empresas['Suc_Des'] : '', ENT_QUOTES, 'UTF-8');
                $html .= "<option value='" . (int) $row_rs_empresas['Emp_Cod'] . "' data-emp-nom='" . $empNom . "' data-suc-cod='" . $sucBr . "'>" . $empCor . " (" . $sucDes . ")</option>";
            }
        } else {
            $sucBr0 = isset($rs_empresas[0]['Suc_Cod']) ? (int) $rs_empresas[0]['Suc_Cod'] : 0;
            $empNom0 = htmlspecialchars(isset($rs_empresas[0]['Emp_Nom']) ? $rs_empresas[0]['Emp_Nom'] : '', ENT_QUOTES, 'UTF-8');
            $empCor0 = htmlspecialchars(!empty($rs_empresas[0]['Emp_Cor']) ? $rs_empresas[0]['Emp_Cor'] : (isset($rs_empresas[0]['Emp_Nom']) ? $rs_empresas[0]['Emp_Nom'] : ''), ENT_QUOTES, 'UTF-8');
            $sucDes0 = htmlspecialchars(isset($rs_empresas[0]['Suc_Des']) ? $rs_empresas[0]['Suc_Des'] : '', ENT_QUOTES, 'UTF-8');
            $html = "<option value='" . (int) $rs_empresas[0]['Emp_Cod'] . "' selected='selected' data-emp-nom='" . $empNom0 . "' data-suc-cod='" . $sucBr0 . "'>" . $empCor0 . " (" . $sucDes0 . ")</option>";
        }
    }
    $obBD_conexion->cerrar();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array('success' => true, 'conteo' => $conteo, 'html' => $html));
    exit();
}

/* AJAX para cambio de contraseña obligatorio */
if (isset($_POST['ajax_change_pass'])) {
    header('Content-Type: application/json; charset=utf-8');
    $obBD_conexion = new Class_Log_Conexion_Log($_SESSION['Ses_Dat_Dis']);
    $obBD_con1 = new Class_Log_Datos_Log;
    $res = array('success' => false);
    
    $old_pass = isset($_POST['old_pass']) ? trim($_POST['old_pass']) : '';
    $new_pass = isset($_POST['new_pass']) ? trim($_POST['new_pass']) : '';
    
    if (empty($old_pass) || empty($new_pass)) {
        $res['message'] = "Complete todos los campos.";
        echo json_encode($res);
        exit();
    }
    
    $old_pass_safe = mysqli_real_escape_string($obBD_conexion->conexion, $old_pass);
    $new_pass_safe = mysqli_real_escape_string($obBD_conexion->conexion, $new_pass);
    $usu_cod = (int) $_SESSION['Ses_Usu_Cod'];
    
    $check = $obBD_con1->getRowConsultaSql(
        "SELECT Usu_Cod, Usu_Pal FROM usuarios WHERE Usu_Cod = $usu_cod AND Usu_Est = 'A'",
        $obBD_conexion
    );
    
    $passwordValid = false;
    if ($check) {
        $storedHash = $check['Usu_Pal'];
        if (strpos($storedHash, '$2y$') === 0) {
            $passwordValid = password_verify($old_pass, $storedHash);
        } else {
            $passwordValid = ($storedHash === md5($old_pass));
        }
    }
    
    if ($passwordValid) {
        $obBD_con1->inicio_transaccion($obBD_conexion);
        $new_hash = password_hash($new_pass, PASSWORD_BCRYPT, ['cost' => 12]);
        $new_hash_safe = mysqli_real_escape_string($obBD_conexion->conexion, $new_hash);
        $obBD_con1->consulta("UPDATE usuarios SET Usu_Pal = '$new_hash_safe' WHERE Usu_Cod = $usu_cod", $obBD_conexion);
        if ($obBD_con1->fin_transaccion_nomsn($obBD_conexion)) {
            $res['success'] = true;
            $res['message'] = "¡Contraseña actualizada! Por favor, inicie sesión con su nueva clave.";
            @session_destroy();
        } else {
            $res['message'] = "Error interno al guardar los cambios.";
        }
    } else {
        $res['message'] = "La clave actual no es correcta.";
    }
    echo json_encode($res);
    exit();
}

if (isset($_SESSION) && isset($_SESSION['Ses_Lis_Per']) && isset($_SESSION['Ses_Emp_Cod']) && isset($_SESSION['Ses_Usu_Ced'])) {
    header('Location: ./administrador/FRONT/home.php');
    exit();
}

$http_host = isset($_SERVER['HTTP_HOST']) ? strtolower($_SERVER['HTTP_HOST']) : '';
$host_base = preg_replace('/:\d+$/', '', $http_host);
$es_localhost = in_array($host_base, array('localhost', '127.0.0.1', '::1'), true);
$relavera_por_host = (strpos($http_host, 'relavera.erpexa') !== false) || $es_localhost;
if (isset($_GET['portal']) && $_GET['portal'] === 'exa') {
    $es_portal_relavera = false;
} elseif (isset($_GET['portal']) && $_GET['portal'] === 'relavera') {
    $es_portal_relavera = true;
} else {
    $es_portal_relavera = $relavera_por_host;
}

$path_logo_rcet = __DIR__ . DIRECTORY_SEPARATOR . 'imagenes' . DIRECTORY_SEPARATOR . 'ingresar' . DIRECTORY_SEPARATOR . 'logo-rcet.png';
$tiene_logo_rcet = is_file($path_logo_rcet);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $es_portal_relavera ? 'RCET · Portal Relavera · Acceso' : 'EXA [Software Contable] - Iniciar Sesión'; ?></title>
    <link rel="shortcut icon" type="image/x-icon" href="imagenes/ingresar/favicon.png" />
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Saira:wght@400;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" 
          onerror="this.onerror=null;this.href='framework/jquery/bootstrap/bootstrap-5.3.0/css/bootstrap.min.css';">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link type="text/css" rel="stylesheet" href="./framework/plugins/animate/animate-3.4.0.min.css" />
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <style>
        :root {
            --login-accent: #801326;
            --login-accent-hover: #580d19;
            --login-footer-bg: #3d0710;
        }

        body {
            color: #161616;
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
            background-color: #581825 !important;
            background-image: none !important;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .login-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem 4rem;
            position: relative;
            z-index: 10;
        }

        .login-section {
            width: 100%;
            max-width: 380px;
            background: #ffffff;
            border-radius: 18px;
            padding: 0;
            color: #161616;
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.15);
            overflow: visible;
        }

        .login-section.card .card-body {
            padding: 2rem 1.8rem;
            overflow: visible;
        }

        .login-section .form-control,
        .login-section .btn {
            border-radius: 10px !important;
            height: 44px !important;
            font-size: 0.92rem;
        }

        .login-section .form-control {
            padding-left: 42px !important;
            display: flex;
            align-items: center;
            border: 1px solid #dcdcdc;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .login-section .form-control:focus {
            border-color: #801326;
            box-shadow: 0 0 0 3px rgba(128, 19, 38, 0.2);
        }

        .login-section .form-control-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #801326;
            font-size: 1.05rem;
            z-index: 5;
        }

        .login-section h4 {
            font-family: 'Saira', sans-serif;
            font-size: 1.35rem;
            font-weight: 700;
            color: #222;
        }

        .logo img {
            max-width: 180px;
            height: auto;
        }

        .btn-primary {
            background-color: #801326;
            border: none;
            height: 44px;
            font-weight: 600;
            font-size: 0.95rem;
            letter-spacing: 0.02em;
            transition: all 0.3s ease;
        }

        .btn-primary:hover,
        .btn-primary:focus,
        .btn-primary:active {
            background-color: #580d19 !important;
            box-shadow: 0 4px 14px rgba(88, 13, 25, 0.4);
        }

        .top-home-bar {
            position: absolute;
            top: 18px;
            left: 20px;
            z-index: 100;
        }

        .homepage-btn {
            background-color: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.25);
            border-radius: 50px;
            padding: 7px 16px;
            font-size: 13px;
            color: #ffffff !important;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: background 0.3s;
        }

        .homepage-btn:hover {
            background-color: rgba(255, 255, 255, 0.28);
        }

        footer {
            text-align: center;
            padding: 10px 15px;
            background-color: #3d0710;
            color: rgba(255, 255, 255, 0.8);
            font-size: 12px;
            width: 100%;
            z-index: 10;
        }

        .select2-container {
            width: 100% !important;
        }

        .select2-container--default .select2-selection--single {
            padding-left: 42px !important;
            border-radius: 10px !important;
            height: 44px !important;
            border: 1px solid #dcdcdc;
            display: flex;
            align-items: center;
        }

        .select2-container--default.select2-container--focus .select2-selection--single,
        .select2-container--default .select2-selection--single:focus {
            border-color: #801326 !important;
            box-shadow: 0 0 0 3px rgba(128, 19, 38, 0.2) !important;
            outline: none !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 42px;
            padding-left: 0;
            font-size: 0.9rem;
            color: #222 !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 42px;
            right: 8px;
        }

        .select2-dropdown.select2-rcet-empresa {
            z-index: 2051 !important;
            color: #161616;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.18);
            border: 1px solid #ddd;
        }

        .select2-dropdown.select2-rcet-empresa .select2-results__option {
            padding: 8px 12px;
            font-size: 0.85rem;
        }

        .select2-dropdown.select2-rcet-empresa .select2-results__option--highlighted {
            background-color: #801326 !important;
            color: #fff !important;
        }

        .select2-dropdown.select2-rcet-empresa .select2-results__option--highlighted .text-muted {
            color: rgba(255, 255, 255, 0.85) !important;
        }

        .select2-results__option .text-muted {
            font-size: 0.75rem;
            color: #666;
            display: block;
            margin-top: 2px;
        }
    </style>
</head>

<body>
    <!-- Top Home Link -->
    <div class="top-home-bar">
        <a href="https://exacontable.com" target="_blank" rel="noopener noreferrer" class="homepage-btn">
            <i class="bi bi-house-door-fill"></i> Homepage
        </a>
    </div>

    <!-- Centered Login Box -->
    <div class="login-wrapper">
        <div class="login-section card animate__animated animate__fadeIn">
            <div class="card-body">
                <div class="text-center logo mb-4">
                    <img src="imagenes/ingresar/logo.png" alt="EXA Logo" class="img-fluid">
                </div>
                <h4 class="text-center mb-1 fw-bold">Iniciar Sesión</h4>
                <p class="small text-secondary text-center mb-4">Ingrese su RUC / Cédula para acceder</p>
                
                <form action="administrador/FRONT/adm_con_control_1.2.php" method="post" name="acceso" id="acceso">
                    <div class="login-fields">
                        <div class="form-group position-relative mb-3">
                            <input class="form-control" type="text" id="user_name" name="user_name" value="" placeholder="Usuario (RUC / Cédula)" autocomplete="username"
                                onblur="loadEmp(this.value);" />
                            <i class="bi bi-person-fill form-control-icon"></i>
                        </div>

                        <div class="form-group position-relative mb-3">
                            <input class="form-control" type="password" id="password" name="password" value="" placeholder="Contraseña" autocomplete="current-password"
                                onKeyPress="if (event.keyCode===13){ handleLogin(); } else { return validar_injections(event); }" />
                            <i class="bi bi-lock-fill form-control-icon"></i>
                        </div>

                        <div class="form-group position-relative mb-3" id="div_empresas" style="display: none;">
                            <select class="form-select form-control" name="Emp_Cod" id="Emp_Cod" data-placeholder="Seleccione Empresa...">
                                <option selected></option>
                            </select>
                            <i class="bi bi-building form-control-icon"></i>
                        </div>
                    </div>

                    <div class="form-group login-actions mt-4">
                        <input type="hidden" name="encryptor" id="encryptor" />
                        <input type="hidden" name="Suc_Cod" id="Suc_Cod" />
                        <button class="btn btn-primary w-100" type="button" id="btnLogin" onclick="handleLogin();">
                            Entrar <i class="bi bi-box-arrow-in-right ms-1"></i>
                        </button>
                    </div>

                    <?php if ((isset($_GET["errorusuario"]) && $_GET["errorusuario"] == "si") || (isset($_GET["errorsistema"]) && $_GET["errorsistema"] == "si") || (isset($_GET["errordispositivo"]) && $_GET["errordispositivo"] == "si")) {
                        $errorMsg = 'Error del Sistema';
                        if (isset($_GET["errorusuario"])) $errorMsg = 'Usuario o contraseña incorrectos';
                        if (isset($_GET["errordispositivo"])) $errorMsg = 'Acceso denegado: dispositivo no autorizado.';
                        echo '<div class="alert alert-danger mt-3 py-2 small text-center" style="border-radius: 8px;"><span>' . $errorMsg . '</span></div>';
                    } ?>
                </form>
            </div>
        </div>

        <!-- Segundo Ambiente: Cambio de Contraseña -->
        <div class="login-section card animate__animated animate__fadeIn" id="change-pass-section" style="display: none;">
            <div class="card-body">
                <div class="text-center mb-4">
                    <h4 class="fw-bold" style="color: var(--login-accent);">Nueva Contraseña</h4>
                </div>
                <p class="small text-secondary mb-4 text-center">Por favor, actualice sus credenciales para continuar de forma segura.</p>
                
                <div id="change-pass-alert"></div>

                <form id="form-change-pass">
                    <div class="form-group position-relative mb-3">
                        <input class="form-control" type="password" id="old_pass" placeholder="Clave Actual" required />
                        <i class="bi bi-key-fill form-control-icon"></i>
                    </div>
                    <div class="form-group position-relative mb-3">
                        <input class="form-control" type="password" id="new_pass" placeholder="Nueva Clave" required />
                        <i class="bi bi-shield-lock-fill form-control-icon"></i>
                    </div>
                    <div class="form-group position-relative mb-3">
                        <input class="form-control" type="password" id="conf_pass" placeholder="Confirmar Clave" required />
                        <i class="bi bi-check-circle-fill form-control-icon"></i>
                    </div>
                    
                    <div class="login-actions mt-4">
                        <button class="btn btn-primary w-100" type="button" id="btnSavePass" onclick="saveNewPass()">
                            Actualizar Contraseña <i class="bi bi-save-fill ms-1"></i>
                        </button>
                        <button class="btn btn-link w-100 mt-2 text-decoration-none small text-secondary" type="button" onclick="location.href='administrador/LOGICA/logout.php'">
                            Cancelar y Salir
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de cambio de contraseña obligatorio -->
    <div class="modal fade" id="modalDefaultPass" tabindex="-1" role="dialog" aria-labelledby="modalLabel" data-bs-backdrop="static" data-bs-keyboard="false" style="z-index: 9999;">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content" style="border-radius: 16px; box-shadow: 0 15px 50px rgba(0,0,0,0.4); border: none; overflow: hidden;">
                <div class="modal-header" style="background: #801326; color: #fff; border: none; padding: 18px 24px;">
                    <h5 class="modal-title fw-bold" id="modalLabel">
                        <i class="bi bi-shield-lock-fill me-2"></i> ALERTA DE SEGURIDAD
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body text-center" style="padding: 35px 30px; background: #fff;">
                    <div class="mb-3">
                        <i class="bi bi-key-fill" style="font-size: 54px; color: #801326; opacity: 0.9;"></i>
                    </div>
                    <p class="mb-3" style="font-size: 1.05rem; color: #333; line-height: 1.6;">
                        Su cuenta está usando una contraseña genérica.<br>
                        <strong style="color: #801326;">Se le recomienda cambiar su contraseña</strong> para continuar.
                    </p>
                    <div class="py-2">
                        <a href="javascript:void(0)" class="fs-5 fw-bold"
                            style="color: #801326; text-decoration: underline;"
                            onclick="switchToChangePass()">
                            Cambiar Contraseña
                        </a>
                    </div>
                    <p class="text-secondary small mt-3">
                        De lo contrario, no podrá acceder al sistema.
                    </p>
                </div>
                <div class="modal-footer justify-content-center" style="background: #fdfdfd; border-top: 1px solid #f0f0f0; padding: 12px;">
                    <small class="text-muted"><i class="bi bi-info-circle me-1"></i> Esta es una política de seguridad obligatoria.</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div><span>&copy; <?php date_default_timezone_set('UTC'); echo date("Y"); ?>. EXA Sistema Contable - Todos los derechos reservados.</span></div>
    </footer>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script>window.jQuery || document.write('<script src="skins/js/jquery.js"><\/script>')</script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>window.bootstrap || document.write('<script src="framework/jquery/bootstrap/bootstrap-5.3.0/js/bootstrap.bundle.min.js"><\/script>')</script>

    <script src="mascaras/model2/js/signin.js"></script>
    <script type="text/javascript" src="Librerias/validaciones/validacion.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script type="text/javascript">
        var empTimeout = null;

        $(document).ready(function() {
            $('#Emp_Cod').select2({
                width: '100%',
                placeholder: "Seleccione Empresa...",
                templateResult: formatOption,
                templateSelection: formatSelection,
                escapeMarkup: function(m) { return m; },
                minimumInputLength: 0,
                dropdownParent: $('body'),
                dropdownCssClass: 'select2-dropdown-below select2-rcet-empresa'
            });

            $('#user_name').on('input', function() {
                clearTimeout(empTimeout);
                var val = $(this).val();
                empTimeout = setTimeout(function() {
                    loadEmp(val);
                }, 350);
            });

            if ($('#user_name').val().trim() !== '') {
                loadEmp($('#user_name').val());
            }
        });

        function formatOption(data) {
            if (!data.id) return data.text;
            var $option = $(data.element);
            var empNom = $option.data('empNom') || $option.data('emp-nom') || '';
            return $(
                '<div class="select2-option-wrap">' +
                '<span class="select2-option-title fw-bold">' + data.text + '</span>' +
                (empNom ? '<div class="text-muted">' + empNom + '</div>' : '') +
                '</div>'
            );
        }

        function formatSelection(data) {
            if (!data.id) return data.text;
            return data.text;
        }

        function loadEmp(ced) {
            ced = (ced || '').trim();
            if (!ced) {
                $('#Emp_Cod').html('<option value=""></option>').trigger('change.select2');
                $('#div_empresas').hide();
                return;
            }
            $.post('', {
                ajax_empresas2: true,
                ajax_username: ced
            }, function(r) {
                if (r && r.success && r.conteo > 0) {
                    $('#Emp_Cod').html(r.html);
                    $('#div_empresas').slideDown(200);
                    $('#Emp_Cod').trigger('change.select2');
                } else {
                    $('#Emp_Cod').html('<option value=""></option>');
                    $('#div_empresas').slideUp(200);
                    $('#Emp_Cod').trigger('change.select2');
                }
            }, 'json').fail(function() {
                $('#Emp_Cod').html('<option value=""></option>');
                $('#div_empresas').slideUp(200);
                $('#Emp_Cod').trigger('change.select2');
            });
        }

        function handleLogin() {
            var user = $('#user_name').val().trim();
            var pass = $('#password').val();
            var o = document.querySelector('#Emp_Cod option:checked');
            var emp = $('#Emp_Cod').val();
            var suc = o ? (o.getAttribute('data-suc-cod') || '') : '';
            
            if (!user || !pass) {
                document.getElementById('acceso').submit();
                return;
            }

            if (!emp && $('#div_empresas').is(':visible')) {
                alert('Por favor seleccione una empresa para ingresar.');
                return;
            }

            document.getElementById('encryptor').value = md5(pass);
            document.getElementById('Suc_Cod').value = suc;
            document.getElementById('acceso').submit();
        }

        function switchToChangePass() {
            $('#modalDefaultPass').modal('hide');
            $('.login-section:not(#change-pass-section)').fadeOut(300, function() {
                $('#change-pass-section').fadeIn(400);
            });
        }

        function showAlert(message, type) {
            var alertHtml = '<div class="alert alert-' + type + ' alert-dismissible fade show" role="alert">' +
                message +
                '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
                '</div>';
            $('#change-pass-alert').html(alertHtml);
        }

        function saveNewPass() {
            var oldP = $('#old_pass').val();
            var newP = $('#new_pass').val();
            var confP = $('#conf_pass').val();

            if (!oldP || !newP || !confP) {
                showAlert('Todos los campos son obligatorios.', 'danger');
                return;
            }

            if (newP !== confP) {
                showAlert('La nueva clave y su confirmación no coinciden.', 'danger');
                return;
            }

            if (newP === '123456') {
                showAlert('No puede utilizar la contraseña genérica por defecto.', 'warning');
                return;
            }

            if (newP.length < 6) {
                showAlert('La contraseña debe tener al menos 6 caracteres.', 'warning');
                return;
            }

            $('#btnSavePass').prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Guardando...');

            $.post('', {
                ajax_change_pass: 1,
                old_pass: oldP,
                new_pass: newP
            }, function(r) {
                $('#btnSavePass').prop('disabled', false).html('Actualizar Contraseña <i class="bi bi-save-fill ms-1"></i>');
                if (r.success) {
                    showAlert(r.message, 'success');
                    setTimeout(function() {
                        window.location.href = 'index.php';
                    }, 2500);
                } else {
                    showAlert(r.message, 'danger');
                }
            }, 'json').fail(function() {
                $('#btnSavePass').prop('disabled', false).html('Actualizar Contraseña <i class="bi bi-save-fill ms-1"></i>');
                showAlert('Ocurrió un error inesperado al actualizar la contraseña.', 'danger');
            });
        }
    </script>
</body>

</html>
