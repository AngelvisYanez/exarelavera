<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Administración de Flayers - Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap 3.3.5 -->
    <link rel="stylesheet" href="../../framework/jquery/bootstrap/bootstrap-3.3.5/css/bootstrap.min.css" />
    <link rel="stylesheet" href="../../framework/plugins/fonts/font-awesome/font-awesome-4.4.0/css/font-awesome.min.css" />
    <!-- jQuery Confirm (Alertas Modernas) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-confirm/3.3.4/jquery-confirm.min.css">
    <!-- Ace Styles (from home.php) -->
    <link rel="stylesheet" href="../../skins/css/ace.css" class="ace-main-stylesheet" id="main-ace-style" />
    
    <style>
        body {
            background-color: #f1f3f6 !important;
            font-family: 'Open Sans', sans-serif;
            padding: 15px;
        }
        .panel-main {
            border: 1px solid #aac1d8;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border-radius: 4px;
        }
        .exa-header {
            background: #305e8c !important; 
            color: white !important;
            padding: 8px 15px !important;
            border-bottom: 2px solid #254a6d !important;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .exa-header h3 {
            margin: 0;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .exa-body {
            background: #eef6fb !important;
            padding: 25px !important;
            min-height: 500px;
        }
        /* Grid de Tarjetas Compactas */
        .flayer-card-grid {
            background: #ffffff;
            border: 1px solid #aac1d8;
            border-top: 3px solid #438eb9;
            margin-bottom: 20px;
            padding: 15px;
            position: relative;
            height: 220px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .flayer-card-grid:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .card-order-badge {
            position: absolute;
            top: -10px;
            right: 10px;
            background: #ff892a;
            color: white;
            padding: 2px 8px;
            font-size: 10px;
            font-weight: bold;
            border-radius: 10px;
        }
        .card-title-text {
            font-weight: bold;
            color: #305e8c;
            font-size: 13px;
            margin-bottom: 5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .card-desc-text {
            font-size: 11px;
            color: #666;
            height: 45px;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
        }
        .card-img-preview {
            height: 60px;
            background: #f9f9f9;
            border: 1px solid #eee;
            margin: 10px 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: #ccc;
            overflow: hidden;
        }
        .card-img-preview img {
            max-height: 100%;
            max-width: 100%;
        }
        .card-actions {
            border-top: 1px solid #eee;
            padding-top: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        /* Estilos del Modal */
        .modal-header-exa {
            background: #305e8c;
            color: white;
        }
        .label-custom {
            font-weight: 700;
            color: #555;
            margin-bottom: 5px;
            display: block;
            font-size: 11px;
            text-transform: uppercase;
        }
        .bg-config-modal {
            background: #f4f7f9;
            padding: 15px;
            border: 1px solid #e1e8ed;
            border-radius: 4px;
        }
        .btn-exa {
            background-color: #82af6f !important;
            border: 1px solid #628f4f !important;
            color: white !important;
            border-radius: 3px;
            font-weight: 600;
            padding: 6px 15px;
            transition: all 0.2s;
        }
        .btn-exa-dark {
            background-color: #abbac3 !important;
            border-color: #9babb3 !important;
            color: white !important;
            border-radius: 3px;
            font-weight: 600;
        }
    </style>
</head>
<body class="no-skin">
    <div class="panel panel-main">
        <div class="panel-heading exa-header">
            <h3 class="panel-title">
                » GESTIÓN DE FLAYERS (VISTA GALERÍA)
                <button class="btn btn-xs btn-exa" onclick="abrirModalNuevo()">
                    <i class="fa fa-plus"></i> Nuevo Flayer
                </button>
            </h3>
        </div>
        
        <div class="panel-body exa-body">
            <div id="flayersContainer" class="row">
                <!-- Las tarjetas se cargarán aquí -->
            </div>
        </div>
    </div>

    <!-- MODAL PARA NUEVO / EDITAR -->
    <div class="modal fade" id="flayerModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header modal-header-exa">
                    <button type="button" class="close" data-dismiss="modal" style="color:white">&times;</button>
                    <h4 class="modal-title" id="modalTitle">Nuevo Flayer</h4>
                </div>
                <div class="modal-body">
                    <form id="flayerForm">
                        <input type="hidden" id="editIndex" value="-1">
                        
                        <div class="row">
                            <div class="col-xs-9">
                                <label class="label-custom">TÍTULO PRINCIPAL</label>
                                <input type="text" id="mTitulo" class="form-control" placeholder="Ej: Bienvenidos a EXA" required>
                            </div>
                            <div class="col-xs-3">
                                <label class="label-custom text-right">ORDEN</label>
                                <input type="number" id="mOrden" class="form-control text-center" value="1">
                            </div>
                        </div>

                        <div style="margin-top: 15px;">
                            <label class="label-custom">CONTENIDO / DESCRIPCIÓN</label>
                            <textarea id="mDescripcion" class="form-control" rows="3" placeholder="Escribe el mensaje aquí..."></textarea>
                        </div>

                        <div class="bg-config-modal" style="margin-top: 15px;">
                            <div class="row">
                                <div class="col-sm-6 col-xs-12" style="margin-bottom: 10px;">
                                    <label class="label-custom"><i class="fa fa-desktop text-info"></i> FONDO PREDEF.</label>
                                    <select id="mImagen" class="form-control">
                                        <option value="0">Ninguno</option>
                                        <option value="1">Opción 1</option>
                                        <option value="2">Opción 2</option>
                                        <option value="3">Opción 3</option>
                                    </select>
                                </div>
                                <div class="col-sm-6 col-xs-12">
                                    <label class="label-custom"><i class="fa fa-cloud-upload text-info"></i> ADJUNTO</label>
                                    <input type="file" id="mFile" accept="image/*" class="form-control" style="height: auto; font-size: 11px; padding: 5px;">
                                    <div id="mAdjuntoActual" style="margin-top:5px"></div>
                                </div>
                            </div>
                        </div>

                        <div style="margin-top: 15px;">
                            <label style="cursor:pointer">
                                <input type="checkbox" id="mActivo" checked>
                                <span style="font-weight: bold; color: #444;"> PUBLICAR ESTE FLAYER</span>
                            </label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-exa-dark" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-exa" onclick="guardarFlayer()">
                        <i class="fa fa-save"></i> Guardar Flayer
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="../../skins/js/jquery.js"></script>
    <script src="../../framework/jquery/bootstrap/bootstrap-3.3.5/js/bootstrap.custom.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-confirm/3.3.4/jquery-confirm.min.js"></script>
    <script src="../VALIDACIONES/flayers.js"></script>
</body>
</html>
