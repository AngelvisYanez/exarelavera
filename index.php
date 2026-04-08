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
} //Fin del if($Browser == "WML") 
/* Ajax para identificar el numero de empresas */
if (isset($ajax_empresas2)) {
    /* Creacion del Objeto de conexion */
    $obBD_conexion = new Class_Log_Conexion_Log;
    /* Creacion del objeto mysql para las consultas */
    $obBD_con1 =  new Class_Log_Datos_Log;
    $rs_empresas = $obBD_con1->getArrayConsulta(1, trim($ajax_username), $obBD_conexion);
    utf8_encode_deep($rs_empresas);
    $conteo = count($rs_empresas);
    $html = '';
    if ($conteo > 0) {
        if ($conteo > 1) {
            $html = $html . "<option value=''></option>";
            foreach ($rs_empresas as $row_rs_empresas) {
                $html = $html . "<option value='" . $row_rs_empresas['Emp_Cod'] . "' data-Emp_Nom='" . ($row_rs_empresas['Emp_Nom']) . "' data--suc_-cod='$row_rs_empresas[Suc_Cod]'> " .  ($row_rs_empresas['Emp_Cor']) . ' (' . ($row_rs_empresas['Suc_Des']) . ") </option>";
                // $html = $html . "<option value='" . $row_rs_empresas['Emp_Cod'] . "' data-Emp_Nom='" . ($row_rs_empresas['Emp_Nom']) . "' data--suc_-cod='$row_rs_empresas[Suc_Cod]'>" . ($row_rs_empresas['Emp_Cor']) . ' (' . ($row_rs_empresas['Suc_Des']) . ")</option>";
            }
        } else {
            //var_dump($rs_empresas);
            // $html = $html . "<option value='" . $rs_empresas[0]['Emp_Cod'] . "' selected='selected'  data-Emp_Nom='" . $rs_empresas[0]['Emp_Nom'] . "' data--suc_-cod='$row_rs_empresas[Suc_Cod]'>" . $rs_empresas[0]['Emp_Cor'] . "</option>";

            $html = $html . "<option value='" . $rs_empresas[0]['Emp_Cod'] . "' selected='selected'  data-Emp_Nom='" . $rs_empresas[0]['Emp_Nom'] . "' data--suc_-cod='$row_rs_empresas[Suc_Cod]'>" . $rs_empresas[0]['Emp_Cor'] . "</option>";
        } //Fin del if ($total_rs_empresas > 1)
    }
    $obBD_conexion->cerrar();
    $res = array('success' => true, 'conteo' => $conteo, 'html' => $html);
    echo json_encode($res);
    exit();
} //Fin del if (isset($ajax_empresas))
if (isset($_SESSION) && !(!isset($_SESSION['Ses_Lis_Per']) || !isset($_SESSION['Ses_Emp_Cod']) || !isset($_SESSION['Ses_Usu_Ced']))) header('Location: ' . './administrador/FRONT/home.php');
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EXA [Software Contable] - Iniciar Sesión</title>
    <link rel="shortcut icon" type="image/x-icon" href="imagenes/ingresar/favicon.png" />
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&family=Saira:wght@400;600&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <!--Agregar items-->
    <link type="text/css" rel="stylesheet" href="./framework/plugins/animate/animate-3.4.0.min.css" />
    <link rel="stylesheet" href="./Librerias/tooltip/jquery.tooltip.css" />
    <link rel="stylesheet" type="text/css" media="screen" href="framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />

    <style>
        body {
            color: #ffffff;
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
            background: url('/imagenes/ingresar/bg.png') no-repeat center center fixed;
            background-size: cover;
            background-color: #161616;
        }

        h1,
        h2,
        h3,
        h4 {
            font-family: 'Saira', sans-serif;
        }

        .row {
            position: relative;
            z-index: 2;
            height: 100%;
        }

        .carousel-item {
            height: 100vh;
            min-height: 300px;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            transition: transform 0.6s ease-in-out, opacity 0.6s ease-in-out;
        }

        option {
            width: 100%;
        }

        .carousel-caption {
            background-color: transparent;
            padding: 20px;
            border-radius: 10px;
            position: absolute;
            right: 15%;
            bottom: 10rem;
            left: 15%;
            padding-top: 1.25rem;
            padding-bottom: 1.25rem;
            color: #fff;
            text-align: center;

        }

        .carousel-indicators button {
            background-color: #ffffff;
        }

        .carousel-indicators button.active {
            background-color: #A02525;
        }

        .login-section {
            width: 380px;
            background: #ffffff;
            border-radius: 20px;
            padding: 20px;
            color: #161616;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }

        .login-section .form-control,
        .login-section .btn {
            border-radius: 10px !important;
            height: 45px !important;
        }

        .login-section .form-control {
            padding-left: 45px !important;
            display: flex;
            align-items: center;
            /* border: 1px solid #A02525 !important;*/
        }

        .login-section .form-control-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #A02525;
        }

        /* .over {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }*/
        .over.desc {
            font-size: 10px !important;
        }

        .logo img {
            width: 220px;
        }

        .homepage-btn {
            position: absolute;
            top: 20px;
            left: 20px;
            z-index: 10;
            background-color: #A02525;
            border: none;
            border-radius: 50px;
            padding: 10px 15px;
            font-size: 16px;
            transition: background-color 0.3s ease-in-out;
        }

        .homepage-btn:hover {
            background-color: #161616;
        }

        .btn-primary {
            background-color: #A02525;
            border: none;
            height: 45px;
            transition: background-color 0.3s ease-in-out;
        }

        .btn-primary:hover {
            background-color: #161616;
        }

        .carousel-indicators {
            bottom: 50px;
        }

        footer {
            text-align: center;
            padding: 8px 0;
            background-color: #161616;
            color: #ffffff;
            font-size: 14px;
            position: fixed;
            bottom: 0;
            width: 100%;
            z-index: 2;
        }

        .text-rojo {
            color: #A02525;
        }

        .aviso-flotante {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: #fff;
            border: 1px solid #ccc;
            padding: 20px;
            z-index: 1000;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
            display: none;
            /* Ocultar inicialmente el aviso */
        }

        /* Estilos para el botón de cerrar */
        .cerrar-aviso {
            position: absolute;
            top: 5px;
            right: 5px;
            cursor: pointer;
            font-size: 20px;
            color: white;
            background: red;
            border-radius: 100%;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Estilos para la imagen dentro del aviso */
        .aviso-flotante img {
            width: 600px;
            height: auto;
        }

        @media (max-width: 768px) {
            .carousel-item {
                height: 50vh;
                min-height: 300px;
                background-size: cover;
                background-position: center;
                background-repeat: no-repeat;
                transition: transform 0.6s ease-in-out, opacity 0.6s ease-in-out;
            }

            .carousel-caption {
                padding: 5px;
                padding: 20px;
                position: absolute;
                right: 15%;
                bottom: 5rem;
                left: 15%;
                padding-top: 1.25rem;
                padding-bottom: 1.25rem;
                color: #fff;
                text-align: center;

            }

            .login-section {
                margin-top: 50px;
            }

            .carousel {
                margin-top: 20px;
            }

            .homepage-btn {
                position: static;
                display: block;
                margin: 0 auto 20px;
                width: fit-content;
            }

            .aviso-flotante img {
                width: 325px;
            }
        }

        /* Estilos para personalizar Select2 */
        .select2-container--default .select2-selection--single {
            padding-left: 45px !important;
            border-radius: 10px !important;
            height: 45px !important;
            border: 1px solid #ccc;
            display: flex;
            align-items: center;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        /* Borde y sombra celeste al estar enfocado o seleccionado */
        .select2-container--default .select2-selection--single:focus,
        .select2-container--default .select2-selection--single.select2-selection--focus,
        .select2-container--default.select2-container--focus .select2-selection--single {
            border-color:rgba(34, 144, 223, 0.71) !important;
            box-shadow: 0 0 0 4px rgba(28, 156, 253, 0.34) !important;
            outline: none !important;
        }

        .select2-container--open .select2-dropdown--below {
            background-color: #ffffff;
            color: var(--bs-body-color);
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 36px;
            padding-top: 8px;
            padding-left: 8px;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px;
        }

        .select2-container--default .select2-results__option--highlighted {
            background-color:lightblue;
            /* color: white; */
            color: var(--bs-body-color);
        }

        /* Estilo para la descripción de las opciones */
        .select2-results__option .text-muted {
            font-size: 0.8em;
            color:rgb(99, 99, 99);
            display: block;
            margin-top: 2px;
        }

    </style>
</head>

<body>
    <div class="container px-3 py-3">
        <a href="https://exacontable.com" target="_blank" class="btn homepage-btn text-white">
            <i class="bi bi-house-door-fill"></i> Homepage
        </a>
        <div class="row">
            <!-- Carousel Section -->
            <div class="col-md-6 col-lg-7 d-flex align-items-center justify-content-center order-md-1 order-2 p-0">
                <div id="loginCarousel" class="carousel slide w-100" data-bs-ride="carousel">
                    <div class="carousel-inner">
                        <div class="carousel-item active">
                            <div class="carousel-caption d-flex flex-column align-items-center justify-content-center" style="height:60%;">
                                <img src="imagenes/ingresar/1.png" alt="" class="img-fluid" style="max-width: 100%; max-height: 400px; display: block; margin: 0 auto;">
                            </div>
                        </div>
                        <div class="carousel-item">
                            <div class="carousel-caption d-flex flex-column align-items-center justify-content-center" style="height:60%;">
                                <img src="imagenes/ingresar/2.png" alt="" class="img-fluid" style="max-width: 100%; max-height: 400px; display: block; margin: 0 auto;">
                            </div>
                        </div>
                        <div class="carousel-item">
                            <div class="carousel-caption d-flex flex-column align-items-center justify-content-center" style="height:60%;">
                                <img src="imagenes/ingresar/3.png" alt="" class="img-fluid" style="max-width: 100%; max-height: 400px; display: block; margin: 0 auto;">
                            </div>
                        </div>
                    </div>
                    <button class="carousel-control-prev" type="button" data-bs-target="#loginCarousel" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Previous</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#loginCarousel" data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Next</span>
                    </button>
                    <div class="carousel-indicators">
                        <button type="button" data-bs-target="#loginCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
                        <button type="button" data-bs-target="#loginCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
                        <button type="button" data-bs-target="#loginCarousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
                    </div>
                </div>
            </div>

            <!-- Login Section -->
            <!--div class="col-md-6 col-lg-5 d-flex align-items-center justify-content-center order-md-2 order-1"-->
            <div class="col-md-6 col-lg-5 d-flex align-items-center justify-content-center order-md-2 order-1">
                <div class="login-section card">
                    <div class="card-body">
                        <div class="text-center logo mb-4">
                            <img src="imagenes/ingresar/logo.png" alt="EXA Logo" class="img-fluid">
                        </div>
                        <h4 class="text-center mb-4 fw-bold">Iniciar Sesión</h4>
                        <p>Inicie sesi&oacute;n con su cuenta registrada</p>
                        <form action="administrador/FRONT/adm_con_control_1.2.php" method="post" name="acceso" id="acceso">
                            <div class="login-fields">
                                <!--div class="form-group position-relative mb-3">
                                    <input class="form-control" type="text" id="user_name" name="user_name" value="" placeholder="Usuario" class="login username-field"
                                        onBlur="if(trim(this.value) !== ''){ loadEmp(this.value);/*ajax_classic('<?php echo $_SERVER['PHP_SELF']; ?>?ajax_empresas=1&ajax_username='+this.value,'div_empresas');*/ }" />
                                    <i class="bi bi-person-fill form-control-icon"></i>
                                </div-->
                                <div class="form-group position-relative mb-3">
                                    <input class="form-control" type="text" id="user_name" name="user_name" value="" placeholder="Usuario" class="login username-field"
                                        onBlur="if(trim(this.value) !== ''){ loadEmp(this.value); }" />
                                    <i class="bi bi-person-fill form-control-icon"></i>
                                </div>

                                <div class="form-group position-relative mb-3">
                                    <input class="form-control" type="password" id="password" name="password" value="" placeholder="Contraseña" class="login password-field" oncontextmenu="return false"
                                        onKeyPress="if (event.keyCode===13){document.getElementById('encryptor').value = md5(document.getElementById('password').value); this.form.submit();}else{return  validar_injections(event);}" />
                                    <i class="bi bi-lock-fill form-control-icon"></i>
                                </div>
                                <div class="form-group position-relative mb-3" id="div_empresas" style="display: none;">
                                    <select class="form-select form-control" name="Emp_Cod" id="Emp_Cod" data-placeholder="Seleccione Empresa...">
                                        <option selected></option>
                                    </select>
                                    <i class="bi bi-building form-control-icon"></i>
                                </div>
                                <!-- <div class="form-group position-relative mb-3" id="div_empresas" style="display: none;">
                                    <select class="form-select form-control text-center" name="Emp_Cod" id="Emp_Cod" data-placeholder="Seleccione Empresa..." style="text-align-last:center;">
                                        <option selected></option>
                                    </select>
                                    <i class="bi bi-building form-control-icon"></i>
                                </div> -->
                            </div>

                            <!-- /login-fields -->
                            <div class="form-group login-actions">
                                <input type="hidden" name="encryptor" id="encryptor" />
                                <input type="hidden" name="Suc_Cod" id="Suc_Cod" />
                                <button class="btn btn-primary w-100" type="button"
                                    onClick="document.getElementById('Suc_Cod').value=$('#Emp_Cod option:selected').data('Suc_Cod'); document.getElementById('encryptor').value = md5(document.getElementById('password').value); this.form.submit();">
                                    Entrar <i class="bi bi-box-arrow-in-right"></i>
                                </button>
                            </div>
                            <?php if ((isset($_GET["errorusuario"]) && $_GET["errorusuario"] == "si") || (isset($_GET["errorsistema"]) && $_GET["errorsistema"] == "si")) {
                                echo '<div class="alert alert-error"><span>' . (isset($_GET["errorusuario"]) ? 'Datos incorrectos' : 'Error del Sistema') . '</span></div>';
                            } ?>
                            <!-- .actions -->
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div align="center" style="text-align:center" class=""><span class="span6"> &copy; <?php date_default_timezone_set('UTC');
                                                                                            echo date("Y"); ?>. EXA Sistema Contable - Todos los derechos reservados.</span></div>
    </footer>

    <!-- Bootstrap & jQuery JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="mascaras/model1/js/libs/modernizr-2.5.3.min.js"></script>
    <script src="framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
    <script src="mascaras/model2/js/signin.js"></script>
    <script language="javascript" src="Librerias/validaciones/validacion.js"></script>
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>


    <script type="text/javascript">
        // metodos funcionales pero con el limitante de chosen
        // $(document).ready(function() {
        //     $('#browser *').tooltip({
        //         showURL: false
        //     });
        //     $('#Emp_Cod').chosenDesc({
        //         width: '100%',
        //         template: function(t, d) {
        //             return '<div class="over"><b>' + t + '</b></div><div class="over desc">' + d['emp_nom'] + '</div>';
        //         }
        //     });

        //     $("#Emp_Cod_chosen").addClass('bs-chosen').find('.chosen-single').addClass('form-select form-control');
        //     $("#Emp_Cod_chosen").find(".chosen-search").find('input').addClass('text rounded');


        //     //$("#Emp_Cod_chosen").find(".chosen-search").find('input').addClass('text form-control rounded');
        //     // $("#Emp_Cod_chosen").find(".chosen-search").find('input').addClass('form-select form-control');
        // });


        // function loadEmp(ced) {
        //     $.post('', {
        //         ajax_empresas2: true,
        //         ajax_username: ced
        //     }, function(r) {
        //         $('#Emp_Cod').html(r['html']);
        //         if (r['success'] && r['conteo'] > 1) {
        //             $('#div_empresas').show();
        //         } else {
        //             $('#div_empresas').hide();
        //         }
        //         $('#Emp_Cod').trigger('chosen:updated');
        //     }, 'json').fail(function() {
        //         $('#Emp_Cod').html('<option value=""></option>');
        //         $('#div_empresas').hide();
        //         $('#Emp_Cod').trigger('chosen:updated');
        //     });
        // }
        // nuevo bloque de codigo para Select2
        $(document).ready(function() {
            // 1. Inicialización de Select2
            $('#Emp_Cod').select2({
            width: '100%',
            placeholder: "Seleccione Empresa...",
            templateResult: formatOption,
            templateSelection: formatSelection,
            escapeMarkup: function(m) { return m; },
            minimumInputLength: 0,
            dropdownParent: $('#div_empresas'),
            dropdownCssClass: 'select2-dropdown-below'
            });

            // 2. Sobrescribir posición del dropdown (siempre abajo)
            $.fn.select2.amd.require(['select2/dropdown'], function(Dropdown) {
            var originalPosition = Dropdown.prototype._positionDropdown;
            
            Dropdown.prototype._positionDropdown = function() {
                originalPosition.apply(this, arguments);
                this.$dropdown
                .removeClass('select2-dropdown--above')
                .addClass('select2-dropdown--below')
                .css({
                    'top': this.$container.outerHeight(),
                    'bottom': 'auto', 'left': 0
                });
            };
            });

            // 3. Habilitar búsqueda instantánea al enfocar
            $('#Emp_Cod').on('select2:opening', function() {
            $(this).data('search-focus', true);
            });

            $('#Emp_Cod').on('select2:open', function() {
            if ($(this).data('search-focus')) {
                setTimeout(function() {
                var $search = $('.select2-search__field');
                $search.focus();

                // Asegura que el padre sea relativo para posicionar el icono
                $search.parent().css('position', 'relative');

                // Elimina iconos previos para evitar duplicados
                $search.parent().find('.search-icon').remove();

                // Agrega el icono de lupa dentro del input, pegado a la derecha
                var icon = $('<i class="bi bi-search search-icon"></i>').css({
                    position: 'absolute',
                    right: '10px',
                    top: '50%',
                    transform: 'translateY(-50%)',
                    color: '#A02525',
                    'pointer-events': 'none',
                    'font-size': '1.2em'
                });
                $search.after(icon);

                // Ajusta el padding del input para que no tape el texto
                // $search.css('padding-right', '2em', 'border-radius', '10px');
                $search.css({ 'padding-right': '2.2em', 'border-radius': '10px' });

                // Manejar entrada directa de texto
                $search.on('input', function() {
                    if ($(this).val().length > 0) {
                    $('.select2-results__options').scrollTop(0);
                    }
                });
                }, 100);
            }
            });

            // 4. Abrir dropdown al hacer focus en el select
            $('.select2-selection').on('focus', function() {
            $('#Emp_Cod').select2('open');
            });
        });

        // Formatear cómo se muestran las opciones en el dropdown
        function formatOption(data) {
            if (!data.id) return data.text;
            
            var $option = $(data.element);
            return $(
                '<div>' + 
                '<strong>' + data.text + '</strong>' +
                '<div class="text-muted">' + $option.data('emp_nom') + '</div>' +
                '</div>'
            );
        }

        // Formatear cómo se muestra la opción seleccionada
        function formatSelection(data) {
            if (!data.id) return data.text;
            return data.text;
        }

        // Función para cargar empresas (modificada para Select2)
        function loadEmp(ced) {
            $.post('', {
                ajax_empresas2: true,
                ajax_username: ced
            }, function(r) {
                $('#Emp_Cod').html(r['html']);
                if (r['success'] && r['conteo'] > 1) {
                    $('#div_empresas').show();
                    // Actualizar Select2 después de cambiar las opciones
                    $('#Emp_Cod').trigger('change.select2');
                } else {
                    $('#div_empresas').hide();
                }
            }, 'json').fail(function() {
                $('#Emp_Cod').html('<option value=""></option>');
                $('#div_empresas').hide();
                $('#Emp_Cod').trigger('change.select2');
            });
        }
    </script>

    <script>
        var socketVentanas, Ses_Usu_Cod = 0,
            Ses_Emp_Cod = 0;
        $.isUnd = function(v) {
            return v === undefined;
        };
        $.varValid = $.vv = function(v) {
            return (v !== null && !$.isUnd(v));
        };
        $.isObject = $.isObj = function(v) {
            return $.vv(v) && !$.isArray(v) && typeof v === 'object';
        };
        $.jsonParser = function(v) {
            if ($.isArray(v) || $.isObj(v)) {
                return JSON.stringify(v);
            } else {
                try {
                    return JSON.parse(v);
                } catch (e) {
                    return v;
                }
            }
        };
        $.setLocalStore = function(name, data) {
            localStorage.setItem(name, $.jsonParser(data));
            if ($.isUnd(data)) localStorage.removeItem(name);
        };
        $.getLocalStore = function(name) {
            var data = localStorage.getItem(name);
            if ($.varValid(data)) return $.jsonParser(data);
        };
        $.getCookie = function(cname) {
            var na = cname + "=",
                dc = decodeURIComponent(document.cookie),
                ca = dc.split(';');
            for (var i = 0; i < ca.length; i++) {
                var c = ca[i];
                while (c.charAt(0) === ' ') {
                    c = c.substring(1);
                }
                if (c.indexOf(na) === 0) {
                    return c.substring(na.length, c.length);
                }
            }
            return "";
        };

        (function($) {
            $.fn.chosenDesc = function(options) {
                return this.each(function() {
                    options = (typeof options !== 'undefined' ? options : {});
                    var $select = $(this),
                        descMap = {},
                        template = (typeof options['template'] !== 'undefined' ? options['template'] : function(text, templateData) {
                            return text;
                        });
                    $select.find('option').filter(function() {
                        return $(this).text();
                    }).each(function(i) {
                        $(this).attr('data-numero', i);
                        descMap[i] = $(this).data();
                        descMap[i]['opttxtsaved'] = $(this).text();
                    });
                    $select.chosen(options);
                    $chosen = $select.next().addClass('chosenDesc-container');
                    $select.bind('chosen:searchready', function() {
                        setTimeout(function() {
                            $chosen.find('.chosen-results li').each(function(i) {
                                $li = $(this), index = $li.attr('data-option-array-index') - 1;
                                $li.html(template($li.html(), descMap[index]));
                            });
                        }, 0);
                    });
                    $select.bind('chosen:showing_dropdown chosen:activate', function() {
                        setTimeout(function() {
                            $chosen.find('.chosen-results li').each(function(i) {
                                $li = $(this);
                                $li.html(template(descMap[i]['opttxtsaved'], descMap[i]));
                            });
                        }, 0);
                    });
                    $select.bind('chosen:updated', function() {
                        descMap = {};
                        $select.find('option').filter(function() {
                            return $(this).text();
                        }).each(function(i) {
                            $(this).attr('data-numero', i);
                            descMap[i] = $(this).data();
                            descMap[i]['opttxtsaved'] = $(this).text();
                        });
                    });
                });
            };
        })(jQuery);
    </script>
<!--div class="aviso-flotante" id="avisoFlotante">
        <span class="cerrar-aviso" onclick="cerrarAviso()">&times;</span>
        <img src="mascaras\model1\img\logo\Norm_Trib.jpeg" alt="Aviso" id="imagenAviso">
    </div>


    <script>
        window.addEventListener('load', function() {
            var avisoFlotante = document.getElementById('avisoFlotante');
            avisoFlotante.style.display = 'block'; // Mostrar el aviso flotante al cargar la página
        });

        // Función para cerrar el aviso flotante
        function cerrarAviso() {
            var avisoFlotante = document.getElementById('avisoFlotante');
            avisoFlotante.style.display = 'none'; // Ocultar el aviso flotante al hacer clic en el botón de cerrar
        }
    </script>
    </div-->
</body>

</html>